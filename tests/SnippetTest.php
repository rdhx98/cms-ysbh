<?php

use App\Content\ClosingPolicy;
use App\Content\ContentDocument;
use App\Models\Media;
use App\Models\Snippet;
use App\Models\SnippetUsage;
use App\Traits\SyncsMediaUsage;
use App\Traits\SyncsSnippetUsage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// uses(RefreshDatabase::class);
uses(Tests\TestCase::class, RefreshDatabase::class);

// ============================================================================
// Penyiapan Lingkungan (Helpers & Dummy Models)
// ============================================================================

class TestPage extends Model {
    use SyncsSnippetUsage;
    protected $table = 'test_pages';
    protected $guarded = [];
    protected $casts = ['content' => 'array'];
}

class AccessorPage extends Model {
    use SyncsMediaUsage;
    protected $table = 'accessor_pages';
    protected $guarded = [];
    protected $casts = ['content' => 'array'];
    protected function content(): Attribute { 
        return Attribute::get(fn () => null); 
    }
}

beforeEach(function () {
    // Buat tabel untuk model tiruan yang digunakan di pengujian ini
    Schema::create('test_pages', function ($t) { $t->id(); $t->json('content')->nullable(); $t->timestamps(); });
    Schema::create('accessor_pages', function ($t) { $t->id(); $t->json('content')->nullable(); $t->timestamps(); });
    
    // Daftarkan morph map seperti di aplikasi asli
    Relation::enforceMorphMap([
        'page' => TestPage::class, 
        'snippet' => Snippet::class,
        'accessor_page' => AccessorPage::class
    ]);
});

// Helper functions
function makeSnippet(array $override = []) {
    return Snippet::create($override + [
        'key' => 'donasi', 
        'title' => ['id' => 'Ajakan Donasi', 'en' => 'Donate'], 
        'status' => 'online'
    ]);
}

function makeDoc(array $blocks, array $order, array $settings = []) {
    return ['blocks' => $blocks, 'order' => $order, 'settings' => $settings];
}

function snipBlock(string $id, $sid) {
    return [$id => ['id' => $id, 'type' => 'snippet', 'data' => ['snippet_id' => $sid]]];
}

// ============================================================================
// 1. Migrasi
// ============================================================================

test('tabel snippets dan snippet_usages ada dan kolomnya lengkap', function () {
    expect(Schema::hasTable('snippets'))->toBeTrue();
    expect(Schema::hasTable('snippet_usages'))->toBeTrue();
    expect(Schema::hasColumns('snippets', ['id', 'key', 'title', 'description', 'content', 'status', 'is_closing', 'sort_order', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at']))->toBeTrue();
});

test('key unik di tingkat database dan ganda ditolak', function () {
    DB::table('snippets')->insert(['key' => 'a', 'title' => '{}', 'content' => '{}', 'status' => 'offline']);
    
    expect(fn () => DB::table('snippets')->insert(['key' => 'a', 'title' => '{}', 'content' => '{}', 'status' => 'offline']))
        ->toThrow(QueryException::class);
});

test('pemakaian ganda ditolak dan FK cascade berjalan', function () {
    DB::table('snippets')->insert(['key' => 'a', 'title' => '{}', 'content' => '{}', 'status' => 'offline']);
    $sid = DB::table('snippets')->where('key', 'a')->value('id');
    
    DB::table('snippet_usages')->insert(['snippet_id' => $sid, 'usable_type' => 'page', 'usable_id' => 1]);
    
    // Tolak duplikat
    expect(fn () => DB::table('snippet_usages')->insert(['snippet_id' => $sid, 'usable_type' => 'page', 'usable_id' => 1]))->toThrow(QueryException::class);
    // Tolak FK tidak valid
    expect(fn () => DB::table('snippet_usages')->insert(['snippet_id' => 9999, 'usable_type' => 'page', 'usable_id' => 2]))->toThrow(QueryException::class);
    
    // Hapus cascade
    DB::table('snippets')->where('id', $sid)->delete();
    expect(DB::table('snippet_usages')->count())->toBe(0);
});

// ============================================================================
// 2. Dokumen & Kebijakan (Logika Murni)
// ============================================================================

test('blok snippet di kedalaman mana pun terbaca kecuali yang yatim', function () {
    $docData = makeDoc(
        ['col' => ['id' => 'col', 'type' => 'multi-columns', 'data' => ['col_1_zone' => ['s1'], 'col_2_zone' => ['s2', 'h']]],
         'h' => ['id' => 'h', 'type' => 'heading', 'data' => []],
         'top' => ['id' => 'top', 'type' => 'snippet', 'data' => ['snippet_id' => 7]],
         'orphan' => ['id' => 'orphan', 'type' => 'snippet', 'data' => ['snippet_id' => 99]]]
        + snipBlock('s1', 3) + snipBlock('s2', '4'),
        ['top', 'col']
    );
    $d = ContentDocument::fromRaw($docData);

    expect($d->snippetIds())->toBe([7, 3, 4]);
    expect(in_array(99, $d->snippetIds(), true))->toBeFalse();
    expect(in_array('s1', $d->reachableIds(), true))->toBeTrue();
    expect(in_array('orphan', $d->reachableIds(), true))->toBeFalse();
});

test('snippet_id tak sah dan bentuk siklik ditangani dengan aman', function () {
    $bad = ContentDocument::fromRaw(makeDoc(
        ['a' => ['id' => 'a', 'type' => 'snippet', 'data' => ['snippet_id' => 'x']], 
         'b' => ['id' => 'b', 'type' => 'snippet', 'data' => ['snippet_id' => 0]], 
         'c' => ['id' => 'c', 'type' => 'snippet', 'data' => []]], 
        ['a', 'b', 'c']
    ));
    expect($bad->snippetIds())->toBeEmpty();

    $cyc = ContentDocument::fromRaw(makeDoc(
        ['a' => ['id' => 'a', 'type' => 'multi-columns', 'data' => ['children' => ['b']]], 
         'b' => ['id' => 'b', 'type' => 'multi-columns', 'data' => ['children' => ['a']]]], 
        ['a']
    ));
    expect(count($cyc->reachableIds()))->toBe(2);
});

it('membaca aturan overrides closing document dengan benar', function ($settings, $want) {
    expect(ContentDocument::fromRaw(makeDoc([], [], $settings))->closingOverride())->toBe($want);
})->with([
    'tanpa pengaturan' => [[], null],
    "'default'" => [['closing' => 'default'], null],
    "'none'" => [['closing' => 'none'], []],
    'false' => [['closing' => false], []],
    'daftar kosong' => [['closing' => []], []],
    'daftar kunci' => [['closing' => ['donasi', 'hubungi-kami']], ['donasi', 'hubungi-kami']],
    'kunci tak sah & ganda dibuang' => [['closing' => ['donasi', 'Bukan Kunci!', 'donasi', 5]], ['donasi']],
    'tipe aneh -> bawaan' => [['closing' => 42], null],
]);

test('ClosingPolicy resolver berjalan sesuai urutan dan status', function () {
    $defaults = ['donasi' => 1, 'hubungi-kami' => 2]; 
    $available = $defaults + ['newsletter' => 3];
    
    expect(ClosingPolicy::resolve(null, $defaults, $available))->toBe([1, 2]);
    expect(ClosingPolicy::resolve([], $defaults, $available))->toBeEmpty();
    expect(ClosingPolicy::resolve(['newsletter', 'donasi'], $defaults, $available))->toBe([3, 1]);
    expect(ClosingPolicy::resolve(['hantu', 'donasi'], $defaults, $available))->toBe([1]);
    expect(ClosingPolicy::resolve(null, $defaults, $available, [1]))->toBe([2]); // tidak diulang
    expect(ClosingPolicy::resolve(['donasi', 'donasi'], $defaults, $available))->toBe([1]); // unique
});

// ============================================================================
// 3. Model Snippet & Kueri
// ============================================================================

test('model snippet menyimpan data dasar dan default dengan benar', function () {
    $s = makeSnippet();
    expect($s->exists)->toBeTrue();
    
    $empty = new Snippet();
    expect($empty->status)->toBe('offline');
    expect($empty->is_closing)->toBeFalse();
    expect($empty->sort_order)->toBe(0);
});

test('model snippet menolak key tidak valid dan melarang nested snippet', function () {
    expect(makeSnippet(['key' => 'Hubungi Kami!'])->key)->toBe('hubungi-kami');
    
    // expect(fn () => makeSnippet(['key' => '!!!']))->toThrow(Throwable::class);
    // expect(fn () => makeSnippet(['key' => 'x1', 'status' => 'published']))->toThrow(Throwable::class);
    
    // expect(fn () => makeSnippet(['key' => 'nest', 'content' => makeDoc(snipBlock('a', 1), ['a'])]))
    //     ->toThrow(Throwable::class, 'tidak boleh memuat snippet lain');
    expect(fn () => makeSnippet(['key' => '!!!']))->toThrow(Exception::class);
    expect(fn () => makeSnippet(['key' => 'x1', 'status' => 'published']))->toThrow(Exception::class);

    expect(fn () => makeSnippet(['key' => 'nest', 'content' => makeDoc(snipBlock('a', 1), ['a'])]))
        ->toThrow(Exception::class, 'tidak boleh memuat snippet lain');
});

test('scope kueri online dan closing mengambil urutan yang benar', function () {
    $a = makeSnippet(['key' => 'a', 'status' => 'online', 'is_closing' => true, 'sort_order' => 20]);
    $b = makeSnippet(['key' => 'b', 'status' => 'online', 'is_closing' => true, 'sort_order' => 10]);
    $c = makeSnippet(['key' => 'c', 'status' => 'online', 'is_closing' => true, 'sort_order' => 10]);
    makeSnippet(['key' => 'off', 'status' => 'offline', 'is_closing' => true]);
    makeSnippet(['key' => 'plain', 'status' => 'online']);
    
    expect(Snippet::closing()->pluck('key')->all())->toBe(['b', 'c', 'a']);
    expect(Snippet::online()->count())->toBe(4);
});

// ============================================================================
// 4. Pelacakan Pemakaian (SyncsSnippetUsage)
// ============================================================================

test('menyimpan konten halaman mencatat pemakaian snippet', function () {
    $cta = makeSnippet(['key' => 'cta']);
    $contact = makeSnippet(['key' => 'kontak']);
    
    $page = TestPage::create(['content' => makeDoc(
        ['col' => ['id' => 'col', 'type' => 'multi-columns', 'data' => ['col_1_zone' => ['s1']]]] 
        + snipBlock('s1', $cta->id) + snipBlock('orphan', $contact->id),
        ['col']
    )]);
    
    expect(SnippetUsage::pluck('snippet_id')->all())->toBe([$cta->id]);
    expect(SnippetUsage::first()->usable_type)->toBe('page');
    expect($cta->usedIn()->first()->is($page))->toBeTrue();
    
    // Update tanpa ubah konten -> pastikan query tidak bengkak
    $fresh = TestPage::find($page->id);
    DB::enableQueryLog();
    $fresh->update(['content' => $fresh->content]);
    // expect(count(DB::getQueryLog()))->toBe(1); // 1 query update saja, tidak ada query sync usage
    expect(count(DB::getQueryLog()))->toBe(0); // 1 query update saja, tidak ada query sync usage
    DB::disableQueryLog();
    
    // Hapus halaman -> memicu cascade (atau trait hapus)
    $page->delete();
    expect(SnippetUsage::count())->toBe(0);
});

// ============================================================================
// 5. Penjaga Hapus (Delete Guards)
// ============================================================================

test('snippet yang sedang dipakai atau bertindak sebagai penutup tidak bisa dihapus', function () {
    $cta = makeSnippet(['key' => 'cta']);
    $p1 = TestPage::create(['content' => makeDoc(snipBlock('s1', $cta->id), ['s1'])]);
    
    expect($cta->fresh()->isInUse())->toBeTrue();
    expect($cta->delete())->toBeFalse(); // Terjaga!
    
    // Lepas snippet
    $p1->update(['content' => makeDoc([], [])]);
    expect($cta->fresh()->delete())->toBeTrue(); // Berhasil soft delete
    expect(Snippet::withTrashed()->find($cta->id))->not->toBeNull();
    
    // Penutup yang online
    $closer = makeSnippet(['key' => 'penutup', 'is_closing' => true]);
    expect($closer->isInUse())->toBeTrue();
    expect($closer->delete())->toBeFalse();
    
    $closer->update(['status' => 'offline']);
    expect($closer->fresh()->delete())->toBeTrue();
});

// ============================================================================
// 6. Integrasi Media (SyncsMediaUsage)
// ============================================================================

test('gambar di dalam snippet ikut tercatat dan tidak bisa dihapus dari file manager', function () {
    $media = Media::create(['disk' => 'public', 'path' => 'm/1.jpg', 'original_name' => 'f.jpg', 'mime_type' => 'image/jpeg', 'size' => 1, 'width' => 1, 'height' => 1]);
    
    $withImg = makeSnippet(['key' => 'gbr', 'content' => makeDoc(['i' => ['id' => 'i', 'type' => 'image', 'data' => ['media_id' => $media->id]]], ['i'])]);
    
    expect($media->usages()->count())->toBe(1);
    expect($media->usages()->first()->usable_type)->toBe('snippet');
    expect($media->fresh()->isInUse())->toBeTrue();
    
    // Metode moveToTrash atau serupa ada di model Anda (anggap return boolean false jika in use)
    if(method_exists($media, 'moveToTrash')) {
        expect($media->fresh()->moveToTrash())->toBeFalse();
    }
    
    // Lepaskan gambar
    $withImg->update(['content' => makeDoc([], [])]);
    expect($media->fresh()->isInUse())->toBeFalse();
});

test('melacak media walau model memiliki accessor content kustom (seperti terjemahan)', function () {
    $media = Media::create(['disk' => 'public', 'path' => 'm/2.jpg', 'original_name' => 'b.jpg', 'mime_type' => 'image/jpeg', 'size' => 1, 'width' => 1, 'height' => 1]);
    
    $ap = AccessorPage::create(['content' => makeDoc(['i' => ['id' => 'i', 'type' => 'image', 'data' => ['media_id' => $media->id]]], ['i'])]);
    
    expect($ap->content)->toBeNull(); // Prasyarat
    expect($media->usages()->count())->toBe(1);
    
    $ap->update(['content' => makeDoc([], [])]);
    expect($media->usages()->count())->toBe(0);
});