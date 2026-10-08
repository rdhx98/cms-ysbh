<?php
/**
 * Uji model Snippet dengan Eloquent + SQLite sungguhan (bukan mock).
 *
 *   php tests/snippet-test.php <bootstrap-lab.php> <support.php> <folder-migrasi-media>
 *
 * Dijalankan di lab saya (Laravel 13 + Carbon, tanpa aplikasi penuh). Untuk proyek Anda, bagian "uji" bisa
 * dipindah apa adanya ke Pest/PHPUnit memakai RefreshDatabase; yang berubah hanya cara menyiapkan database.
 */
[$_, $bootstrap, $support, $mediaMigrations] = $argv + [null, null, null, null];
require $bootstrap;
require $support;

use App\Content\ClosingPolicy;
use App\Content\ContentDocument;
use App\Models\Media;
use App\Models\Snippet;
use App\Models\SnippetUsage;
use App\Traits\SyncsSnippetUsage;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

$pass = $fail = 0;
function check(string $name, bool $ok, string $extra = ''): void {
    global $pass, $fail; $ok ? $pass++ : $fail++;
    echo ($ok ? '  PASS  ' : '  FAIL  ') . $name . ($ok || $extra === '' ? '' : "   [$extra]") . "\n";
}
function section(string $t): void { echo "\n$t\n"; }
function throws(callable $fn): ?string { try { $fn(); return null; } catch (Throwable $e) { return get_class($e) . ': ' . $e->getMessage(); } }

// ---------------------------------------------------------------- penyiapan
$db = boot_database();
Schema::create('users', function ($t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
// hanya migrasi media (folder migrasi gabungan juga memuat migrasi snippet, yang dibuat di baris berikutnya)
foreach (glob($mediaMigrations . '/*media*.php') as $file) { (require $file)->up(); }
(require __DIR__ . '/../database/migrations/2026_10_07_000001_create_snippets_table.php')->up();

// Model berisi blok yang memakai trait (Page/Post sungguhan butuh Spatie penuh; ini cukup untuk trait-nya)
class TestPage extends Model {
    use SyncsSnippetUsage;
    protected $table = 'test_pages';
    protected $guarded = [];
    protected $casts = ['content' => 'array'];
}
Schema::create('test_pages', function ($t) { $t->id(); $t->json('content')->nullable(); $t->timestamps(); });
Relation::enforceMorphMap(['page' => TestPage::class, 'snippet' => Snippet::class]);

$mk = fn (array $o = []) => Snippet::create($o + ['key' => 'donasi', 'title' => ['id' => 'Ajakan Donasi', 'en' => 'Donate'], 'status' => 'online']);
$doc = fn (array $blocks, array $order, array $settings = []) => ['blocks' => $blocks, 'order' => $order, 'settings' => $settings];
$snipBlock = fn (string $id, $sid) => [$id => ['id' => $id, 'type' => 'snippet', 'data' => ['snippet_id' => $sid]]];

// ================================================================ 1. migrasi
section('1. Migrasi');
check('tabel snippets dan snippet_usages ada', Schema::hasTable('snippets') && Schema::hasTable('snippet_usages'));
check('kolom snippets lengkap', Schema::hasColumns('snippets', ['id', 'key', 'title', 'description', 'content', 'status', 'is_closing', 'sort_order', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at']));
$unique = array_column(array_filter(DB::select("PRAGMA index_list('snippets')"), fn ($i) => $i->unique), 'name');
check('key unik di tingkat database', count($unique) >= 1);
DB::table('snippets')->insert(['key' => 'a', 'title' => '{}', 'content' => '{}', 'status' => 'offline']);
check('key ganda ditolak database', throws(fn () => DB::table('snippets')->insert(['key' => 'a', 'title' => '{}', 'content' => '{}', 'status' => 'offline'])) !== null);
$sid = DB::table('snippets')->where('key', 'a')->value('id');
DB::table('snippet_usages')->insert(['snippet_id' => $sid, 'usable_type' => 'page', 'usable_id' => 1]);
check('pemakaian ganda (snippet+halaman sama) ditolak', throws(fn () => DB::table('snippet_usages')->insert(['snippet_id' => $sid, 'usable_type' => 'page', 'usable_id' => 1])) !== null);
check('pemakaian menunjuk snippet yang tidak ada ditolak (FK)', throws(fn () => DB::table('snippet_usages')->insert(['snippet_id' => 9999, 'usable_type' => 'page', 'usable_id' => 2])) !== null);
DB::table('snippets')->where('id', $sid)->delete();
check('hapus baris snippet menghapus pemakaiannya (cascade)', DB::table('snippet_usages')->count() === 0);

// ================================================================ 2. dokumen & kebijakan (logika murni)
section('2. ContentDocument: referensi snippet');
$d = ContentDocument::fromRaw($doc(
    ['col' => ['id' => 'col', 'type' => 'multi-columns', 'data' => ['col_1_zone' => ['s1'], 'col_2_zone' => ['s2', 'h']]],
     'h' => ['id' => 'h', 'type' => 'heading', 'data' => []],
     'top' => ['id' => 'top', 'type' => 'snippet', 'data' => ['snippet_id' => 7]],
     'orphan' => ['id' => 'orphan', 'type' => 'snippet', 'data' => ['snippet_id' => 99]]]
    + $snipBlock('s1', 3) + $snipBlock('s2', '4'),
    ['top', 'col']
));
check('blok snippet di kedalaman mana pun terbaca (angka & string angka)', $d->snippetIds() === [7, 3, 4], json_encode($d->snippetIds()));
check('blok yatim tidak dihitung', !in_array(99, $d->snippetIds(), true));
check('reachableIds hanya yang tersambung', in_array('s1', $d->reachableIds(), true) && !in_array('orphan', $d->reachableIds(), true));
$bad = ContentDocument::fromRaw($doc(['a' => ['id' => 'a', 'type' => 'snippet', 'data' => ['snippet_id' => 'x']], 'b' => ['id' => 'b', 'type' => 'snippet', 'data' => ['snippet_id' => 0]], 'c' => ['id' => 'c', 'type' => 'snippet', 'data' => []]], ['a', 'b', 'c']));
check('snippet_id tak sah (huruf, nol, kosong) diabaikan', $bad->snippetIds() === [] && !$bad->referencesSnippets());
check("tipe 'snippet' dikenali juga dengan ejaan lain", ContentDocument::fromRaw($doc(['a' => ['id' => 'a', 'type' => 'Snippet', 'data' => ['snippet_id' => 5]]], ['a']))->snippetIds() === [5]);
$cyc = ContentDocument::fromRaw($doc(['a' => ['id' => 'a', 'type' => 'multi-columns', 'data' => ['children' => ['b']]], 'b' => ['id' => 'b', 'type' => 'multi-columns', 'data' => ['children' => ['a']]]], ['a']));
check('data siklik tidak membuat loop', count($cyc->reachableIds()) === 2);

foreach ([
    'tanpa pengaturan' => [[], null],
    "'default'" => [['closing' => 'default'], null],
    "'none'" => [['closing' => 'none'], []],
    'false' => [['closing' => false], []],
    'daftar kosong' => [['closing' => []], []],
    'daftar kunci' => [['closing' => ['donasi', 'hubungi-kami']], ['donasi', 'hubungi-kami']],
    'kunci tak sah & ganda dibuang' => [['closing' => ['donasi', 'Bukan Kunci!', 'donasi', 5]], ['donasi']],
    'tipe aneh -> bawaan' => [['closing' => 42], null],
] as $name => [$settings, $want]) {
    check("settings.closing: $name", ContentDocument::fromRaw($doc([], [], $settings))->closingOverride() === $want);
}

section('2b. ClosingPolicy');
$defaults = ['donasi' => 1, 'hubungi-kami' => 2]; $available = $defaults + ['newsletter' => 3];
check('null -> semua penutup bawaan, berurutan', ClosingPolicy::resolve(null, $defaults, $available) === [1, 2]);
check('[] -> tanpa penutup', ClosingPolicy::resolve([], $defaults, $available) === []);
check('daftar kunci menggantikan bawaan (boleh snippet non-penutup)', ClosingPolicy::resolve(['newsletter', 'donasi'], $defaults, $available) === [3, 1]);
check('kunci yang tidak ada/offline dilewati', ClosingPolicy::resolve(['hantu', 'donasi'], $defaults, $available) === [1]);
check('snippet yang sudah disisipkan manual tidak diulang di akhir', ClosingPolicy::resolve(null, $defaults, $available, [1]) === [2]);
check('kunci ganda hanya sekali', ClosingPolicy::resolve(['donasi', 'donasi'], $defaults, $available) === [1]);

// ================================================================ 3. model
section('3. Model Snippet');
$s = $mk();
check('mass assignment lewat #[Fillable] variadic bekerja', $s->exists && $s->key === 'donasi');
check('nilai bawaan: offline, bukan penutup, urutan 0, isi kosong', (new Snippet)->status === 'offline' && (new Snippet)->is_closing === false && (new Snippet)->sort_order === 0 && !(new Snippet)->document()->referencesSnippets());
check('casts: title array, is_closing boolean, sort_order integer', is_array($s->fresh()->getTranslations('title')) && $s->fresh()->is_closing === false && is_int($s->fresh()->sort_order));
check("key dinormalkan saat disimpan: 'Hubungi Kami!' -> 'hubungi-kami'", $mk(['key' => 'Hubungi Kami!'])->key === 'hubungi-kami');
check('key kosong / tanpa karakter sah ditolak', throws(fn () => $mk(['key' => '!!!'])) !== null && throws(fn () => $mk(['key' => ''])) !== null);
check('status tidak sah ditolak', str_contains((string) throws(fn () => $mk(['key' => 'x1', 'status' => 'published'])), 'Status snippet'));
check('key ganda ditolak', throws(fn () => $mk(['key' => 'donasi'])) !== null);
check('snippet tidak boleh memuat snippet lain', str_contains((string) throws(fn () => $mk(['key' => 'nest', 'content' => $doc($snipBlock('a', 1), ['a'])])), 'tidak boleh memuat snippet lain'));
check('snippet berisi blok biasa diterima & isi tersimpan bolak-balik', ($n = $mk(['key' => 'cta', 'content' => $doc(['h' => ['id' => 'h', 'type' => 'heading', 'data' => ['text' => ['id' => 'Bantu Kami']]]], ['h'])]))->fresh()->document()->order === ['h']);
check('label(): bahasa yang diminta, lalu bahasa lain, lalu key', $s->label('en') === 'Donate' && $s->label('fr') === 'Ajakan Donasi' && $mk(['key' => 'tanpa-judul', 'title' => ['id' => '', 'en' => '']])->label() === 'tanpa-judul');

section('3b. Kueri');
Snippet::query()->forceDelete();
$a = $mk(['key' => 'a', 'status' => 'online', 'is_closing' => true, 'sort_order' => 20]);
$b = $mk(['key' => 'b', 'status' => 'online', 'is_closing' => true, 'sort_order' => 10]);
$c = $mk(['key' => 'c', 'status' => 'online', 'is_closing' => true, 'sort_order' => 10]);
$off = $mk(['key' => 'off', 'status' => 'offline', 'is_closing' => true]);
$plain = $mk(['key' => 'plain', 'status' => 'online']);
check('scope closing: hanya online + penutup, urut sort_order lalu id', Snippet::closing()->pluck('key')->all() === ['b', 'c', 'a'], json_encode(Snippet::closing()->pluck('key')->all()));
check('scope online mengecualikan offline', Snippet::online()->count() === 4);
check('onlineByKey dikunci oleh key', Snippet::onlineByKey()->keys()->sort()->values()->all() === ['a', 'b', 'c', 'plain']);
check('ClosingPolicy dengan data nyata dari kueri', ClosingPolicy::resolve(null, Snippet::closing()->pluck('id', 'key')->all(), Snippet::onlineByKey()->map->id->all()) === [$b->id, $c->id, $a->id]);

// ================================================================ 4. pelacakan pemakaian
section('4. SyncsSnippetUsage (halaman memakai snippet)');
Snippet::query()->forceDelete();
$cta = $mk(['key' => 'cta', 'status' => 'online']);
$contact = $mk(['key' => 'kontak', 'status' => 'online']);
$q = 0; DB::listen(function () use (&$q) { $q++; });

$page = TestPage::create(['content' => $doc(
    ['col' => ['id' => 'col', 'type' => 'multi-columns', 'data' => ['col_1_zone' => ['s1']]]] + $snipBlock('s1', $cta->id) + $snipBlock('orphan', $contact->id),
    ['col']
)]);
check('blok snippet di dalam kolom tercatat; blok yatim tidak', SnippetUsage::pluck('snippet_id')->all() === [$cta->id]);
check("tercatat dengan nama morph 'page' (morph map)", SnippetUsage::first()->usable_type === 'page' && $cta->usedIn()->first()->is($page));

$fresh = TestPage::find($page->id);
$q = 0; $fresh->update(['content' => $fresh->content]);
$noOp = $q;
$page->update(['content' => $doc(['col' => ['id' => 'col', 'type' => 'multi-columns', 'data' => ['col_1_zone' => ['s1', 's2']]]] + $snipBlock('s1', $cta->id) + $snipBlock('s2', $contact->id), ['col'])]);
check('menambah blok kedua -> pemakaian bertambah', SnippetUsage::orderBy('snippet_id')->pluck('snippet_id')->all() === [$cta->id, $contact->id]);
check('menyimpan tanpa mengubah konten tidak menyentuh pemakaian (0 kueri)', $noOp === 0, "kueri: $noOp");

$page->update(['content' => $doc(['s2' => $snipBlock('s2', $contact->id)['s2']], ['s2'])]);
check('menghapus blok -> pemakaiannya ikut hilang', SnippetUsage::pluck('snippet_id')->all() === [$contact->id]);

$page->update(['content' => $doc([], [], ['closing' => ['cta']])]);
check('settings.closing yang menyebut snippet dihitung sebagai pemakaian', SnippetUsage::pluck('snippet_id')->all() === [$cta->id]);
$page->update(['content' => $doc($snipBlock('x', 424242), ['x'])]);
check('blok menunjuk snippet yang tidak ada -> diabaikan tanpa galat FK', SnippetUsage::count() === 0);
$page->update(['content' => $doc($snipBlock('s1', $cta->id), ['s1'])]);
$page->delete();
check('hapus halaman -> pemakaiannya dibersihkan', SnippetUsage::count() === 0);

// ================================================================ 5. penjaga hapus
section('5. Penjaga hapus');
$p1 = TestPage::create(['content' => $doc($snipBlock('s1', $cta->id), ['s1'])]);
check('dipakai halaman -> isInUse() true', $cta->fresh()->isInUse());
check('delete() ditolak (mengembalikan false) dan baris tetap ada', $cta->delete() === false && Snippet::find($cta->id) !== null);
check('forceDelete() juga ditolak', $cta->forceDelete() === false && Snippet::withTrashed()->find($cta->id) !== null);
$p1->update(['content' => $doc([], [])]);
check('setelah tidak dipakai -> bisa dihapus (soft delete)', $cta->fresh()->delete() === true && Snippet::find($cta->id) === null && Snippet::withTrashed()->find($cta->id) !== null);
check('snippet terhapus tidak muncul di scope online/closing', !Snippet::online()->where('key', 'cta')->exists());
Snippet::withTrashed()->find($cta->id)->restore();
check('restore() mengembalikannya', Snippet::find($cta->id) !== null);

$closer = $mk(['key' => 'penutup', 'status' => 'online', 'is_closing' => true]);
check('penutup yang online dihitung dipakai (muncul di semua halaman) -> tak bisa dihapus', $closer->isInUse() && $closer->delete() === false);
$closer->update(['status' => 'offline']);
check('penutup yang offline tidak tampil di mana pun -> boleh dihapus', !$closer->fresh()->isInUse() && $closer->fresh()->delete() === true);
check('forceDelete snippet tak terpakai bekerja', $mk(['key' => 'sementara'])->forceDelete() === true && Snippet::withTrashed()->where('key', 'sementara')->doesntExist());

// ================================================================ 6. gambar di dalam snippet ikut tercatat di file manager
section('6. Gambar di dalam snippet -> media "Digunakan Di"');
$media = Media::create(['disk' => 'public', 'path' => 'm/1.jpg', 'original_name' => 'foto.jpg', 'mime_type' => 'image/jpeg', 'size' => 1000, 'width' => 10, 'height' => 10]);
$withImg = $mk(['key' => 'dengan-gambar', 'content' => $doc(['i' => ['id' => 'i', 'type' => 'image', 'data' => ['media_id' => $media->id]]], ['i'])]);
check("media tercatat dipakai oleh snippet (usable_type 'snippet')", $media->usages()->count() === 1 && $media->usages()->first()->usable_type === 'snippet');
check('media yang dipakai snippet tidak bisa dibuang ke Sampah', $media->fresh()->isInUse() && $media->fresh()->moveToTrash() === false);
$withImg->update(['content' => $doc([], [])]);
check('gambar dicopot dari snippet -> media bebas lagi', $media->fresh()->isInUse() === false && $media->fresh()->moveToTrash() === true);

section('6b. Media: model yang accessor `content`-nya tidak mengembalikan isi (seperti Translatable pada Page)');
class AccessorPage extends Model {
    use App\Traits\SyncsMediaUsage;
    protected $table = 'accessor_pages'; protected $guarded = []; protected $casts = ['content' => 'array'];
    // Meniru HasTranslations: membaca $page->content memberi "terjemahan locale aktif" — kosong untuk {blocks, order, settings}
    protected function content(): Illuminate\Database\Eloquent\Casts\Attribute { return Illuminate\Database\Eloquent\Casts\Attribute::get(fn () => null); }
}
Schema::create('accessor_pages', function ($t) { $t->id(); $t->json('content')->nullable(); $t->timestamps(); });
Relation::enforceMorphMap(['accessor_page' => AccessorPage::class]);
$media2 = Media::create(['disk' => 'public', 'path' => 'm/2.jpg', 'original_name' => 'b.jpg', 'mime_type' => 'image/jpeg', 'size' => 1, 'width' => 1, 'height' => 1]);
$ap = AccessorPage::create(['content' => $doc(['i' => ['id' => 'i', 'type' => 'image', 'data' => ['media_id' => $media2->id]]], ['i'])]);
check('(prasyarat) accessor memang mengembalikan null untuk model ini', $ap->content === null);
check('gambar pada model BARU DIBUAT tetap tercatat (membaca atribut mentah)', $media2->usages()->count() === 1);
$ap->update(['content' => $doc(['i' => ['id' => 'i', 'type' => 'image', 'data' => ['media_id' => $media2->id]], 'j' => ['id' => 'j', 'type' => 'heading', 'data' => []]], ['i', 'j'])]);
check('mengubah konten tanpa mencopot gambar tidak menghapus catatannya', $media2->usages()->count() === 1);
$ap->update(['content' => $doc([], [])]);
check('mencopot gambar menghapus catatannya', $media2->usages()->count() === 0);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
