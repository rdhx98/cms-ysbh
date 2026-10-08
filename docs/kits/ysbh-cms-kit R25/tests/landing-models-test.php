<?php
/**
 * Uji model baca-saja untuk landing (landing-app/app/Models/*.php, yang Anda pasang) terhadap kode kit (PublicLookup, FileInfo):
 *
 *   php tests/landing-models-test.php <bootstrap-lab.php> <support.php> <folder-migrasi-media>
 *
 * Memastikan contoh model benar-benar memenuhi kontrak kit (kolom, scope, soft delete), dan menolak penulisan. Model kit/CMS TIDAK dipakai.
 */
[$_, $bootstrap, $support, $mediaMigrations] = $argv + [null, null, null, null];
$root = dirname(__DIR__);
require $bootstrap;
require $support;
foreach (['ReadOnlyModel', 'Page', 'Post', 'Category', 'Snippet', 'Media'] as $model) { require "$root/landing-app/app/Models/$model.php"; }   // model landing yang SEBENARNYA (sebelum autoload lab sempat memuat model kit)

use App\Content\Blocks\FileInfo;
use App\Content\PublicLookup as PL;
use App\Models\{Category, Media, Page, Post, Snippet};
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Schema;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
function throws(callable $fn): ?string { try { $fn(); return null; } catch (Throwable $e) { return get_class($e) . ': ' . $e->getMessage(); } }

boot_database();
Illuminate\Container\Container::getInstance()->instance('filesystem', new class { function disk($n = null) { return new class { function url($p) { return "https://ysbh.org/storage/$p"; } }; } });
Schema::create('users', function ($t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
foreach (glob($mediaMigrations . '/*media*.php') as $file) { (require $file)->up(); }
(require "$root/database/migrations/2026_10_07_000001_create_snippets_table.php")->up();
foreach (['pages', 'posts'] as $tb) {
    Schema::create($tb, function ($t) use ($tb) { $t->id(); $t->text('title')->nullable(); $t->text('slug')->nullable(); $t->text('content')->nullable(); $t->text('meta_title')->nullable(); $t->text('meta_description')->nullable(); $t->string('status')->default('draft'); $t->timestamp('published_at')->nullable(); if ($tb === 'posts') { $t->string('featured_image')->default('default.webp'); $t->unsignedBigInteger('category_id')->nullable(); } $t->timestamps(); });
}
Schema::create('categories', function ($t) { $t->id(); $t->text('name'); $t->text('slug')->nullable(); });
$now = date('Y-m-d H:i:s');

echo "\nModel landing = kontrak kit\n";
check('kelas yang dipakai adalah model LANDING (landing-app/app/Models), bukan model kit/CMS', (new ReflectionClass(Page::class))->getFileName() === realpath("$root/landing-app/app/Models/Page.php") && (new ReflectionClass(Media::class))->getFileName() === realpath("$root/landing-app/app/Models/Media.php"));
DB::table('pages')->insert(['title' => '{"id":"T","en":"T"}', 'slug' => '{"id":"tentang","en":"about"}', 'status' => 'online', 'content' => '{"blocks":{"p1":{"id":"p1","type":"paragraph","data":{"text":{"id":"Isi"}}}},"order":["p1"],"settings":{}}', 'created_at' => $now, 'updated_at' => $now]);
DB::table('pages')->insert(['title' => '{}', 'slug' => '{"id":"draf","en":"draft"}', 'status' => 'offline', 'content' => 'null', 'created_at' => $now, 'updated_at' => $now]);
$f = PL::findPage('about', 'id');
check('findPage (rilis 24, ketat): slug bahasa lain -> pengalihan ke padanan di bahasa yang diminta, model Page sungguhan; halaman offline tidak', $f && $f['locale'] === 'id' && is_string($f['redirect']) && $f['model'] instanceof Page && PL::findPage('draf', 'id') === null);
DB::table('categories')->insert(['name' => '{"id":"Kesehatan","en":"Health"}', 'slug' => '{}']);
DB::table('posts')->insert(['title' => '{"id":"Judul","en":"Title"}', 'slug' => '{"id":"artikel-a","en":"article-a"}', 'content' => '"<p>Lama</p>"', 'status' => 'published', 'category_id' => 1, 'published_at' => '2026-10-08 09:00:00', 'featured_image' => 'cover.webp', 'meta_description' => '{"id":"Ringkas","en":""}', 'created_at' => $now, 'updated_at' => $now]);
DB::table('posts')->insert(['title' => '{"id":"Draf"}', 'slug' => '{"id":"draf-a"}', 'content' => 'null', 'status' => 'draft', 'category_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
$lt = PL::latestArticles(5);
check('findArticle dan latestArticles: hanya yang terbit; nilai mentah; kategori terpetakan', PL::findArticle('article-a', 'id')['model'] instanceof Post && count($lt['rows']) === 1 && $lt['rows'][0]['featured_image'] === 'cover.webp' && $lt['categories'][1] === '{"id":"Kesehatan","en":"Health"}', json_encode($lt));
DB::table('snippets')->insert(['key' => 'donasi', 'title' => '{"id":"D"}', 'content' => '{"blocks":{"s1":{"id":"s1","type":"paragraph","data":{"text":{"id":"Donasi penutup"}}}},"order":["s1"],"settings":{}}', 'status' => 'online', 'is_closing' => 1, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now]);
DB::table('snippets')->insert(['key' => 'draf', 'title' => '{"id":"X"}', 'content' => '{"blocks":{"s2":{"id":"s2","type":"paragraph","data":{"text":{"id":"TIDAK TAMPIL"}}}},"order":["s2"],"settings":{}}', 'status' => 'offline', 'is_closing' => 1, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now]);
DB::table('snippets')->insert(['key' => 'promo', 'title' => '{"id":"P"}', 'content' => '{"blocks":{"s3":{"id":"s3","type":"paragraph","data":{"text":{"id":"BUKAN PENUTUP"}}}},"order":["s3"],"settings":{}}', 'status' => 'online', 'is_closing' => 0, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now]);
$doc = PL::document($f['model']->getRawOriginal('content'));
check('dokumen halaman: isi sendiri + snippet penutup ONLINE (Snippet landing: scope online() dan closing() bekerja); yang offline dan yang online tetapi BUKAN penutup tidak', $doc['order'] === ['p1', 's1'], json_encode($doc['order']));

echo "\nMedia: soft delete dan URL\n";
$m1 = DB::table('media')->insertGetId(['disk' => 'public', 'path' => 'galeri/a.jpg', 'original_name' => 'a.jpg', 'mime_type' => 'image/jpeg', 'size' => 100, 'created_at' => $now, 'updated_at' => $now]);
$m2 = DB::table('media')->insertGetId(['disk' => 'public', 'path' => 'galeri/b.jpg', 'original_name' => 'b.jpg', 'mime_type' => 'image/jpeg', 'size' => 100, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => $now]);
$info = FileInfo::lookup([$m1, $m2]);
check('FileInfo::lookup: URL dari Media::url() (disk public); berkas yang DIHAPUS di CMS (deleted_at terisi) tidak ikut', array_keys($info) === [$m1] && $info[$m1]['url'] === 'https://ysbh.org/storage/galeri/a.jpg', json_encode($info));
check('tanpa trait SoftDeletes hal ini akan gagal: contoh Media memakainya (peringatan di komentar)', in_array(Illuminate\Database\Eloquent\SoftDeletes::class, class_uses(Media::class), true));

echo "\nBaca-saja di tingkat kode\n";
foreach ([
    'Page::create' => fn () => Page::create(['title' => 'x']), 'Post::create' => fn () => Post::create(['title' => 'x']),
    'Snippet::create' => fn () => Snippet::create(['key' => 'x']), 'Category::create' => fn () => Category::create(['name' => 'x']),
    'Page->save' => function () { $p = Page::query()->first(); $p->status = 'offline'; $p->save(); },
    'Post->delete' => fn () => Post::query()->first()->delete(), 'Media->delete (soft)' => fn () => Media::query()->find(1)->delete(),
    'Media->forceDelete' => fn () => Media::query()->find(1)->forceDelete(), 'Page->update' => fn () => Page::query()->first()->update(['status' => 'offline']),
] as $label => $fn) {
    $err = throws($fn);
    check("$label ditolak (LogicException), tidak ada yang tertulis", $err !== null && str_contains($err, 'hanya membaca'), (string) $err);
}
check('data tidak berubah setelah semua upaya menulis', DB::table('pages')->where('status', 'online')->count() === 1 && DB::table('posts')->count() === 2 && DB::table('media')->whereNull('deleted_at')->count() === 1);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
