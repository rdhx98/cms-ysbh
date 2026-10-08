<?php
/**
 * Penjaga slug terlarang:  php tests/slug-reserved-test.php <bootstrap-lab.php> <support.php>
 * Slug::reserved/isReserved, aturan ReservedSlug, ContentRules (validator sungguhan + SQLite), dan SlugAudit.
 */
[$_, $bootstrap, $support] = $argv + [null, null, null];
$root = dirname(__DIR__);
require $bootstrap;
require $support;

use App\Content\{ContentRules, ContentType as T, Slug, SlugAudit};
use App\Content\Rules\ReservedSlug;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Schema;

// config() ada di aplikasi Laravel, tidak di lab: pengganti yang membaca $GLOBALS['cfg']
$GLOBALS['cfg'] = [];
if (!function_exists('config')) { function config($key = null, $default = null) { return array_key_exists($key, $GLOBALS['cfg']) ? $GLOBALS['cfg'][$key] : $default; } }

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 300) . "]" : '') . "\n"; }
function ruleFails(ReservedSlug $r, mixed $v): ?string { $msg = null; $r->validate('slug.id', $v, function ($m) use (&$msg) { $msg = (string) $m; }); return $msg; }

echo "\nSlug::reserved / isReserved\n";
$base = Slug::reserved();
check('bawaan teknis terlarang: articles, storage, build, up, livewire, ...; "artikel" TIDAK (milik halaman kepala)', !array_diff(['articles', 'storage', 'build', 'fonts', 'logo', 'up', 'livewire', 'sitemap', 'robots'], $base) && !in_array('artikel', $base, true));
check('tambahan dari config digabung; huruf besar dan spasi dinormalkan; duplikat dan nilai bukan teks diabaikan', (function () { $r = Slug::reserved(['About', ' contact ', 'about', 7, null, ['x'], '', '  ']); return in_array('about', $r, true) && in_array('contact', $r, true) && count(array_keys($r, 'about', true)) === 1 && !in_array('', $r, true) && !in_array('7', $r, true); })());
check('config berhuruf besar ("ABOUT") dinormalkan di DAFTAR: slug "about" tetap ditolak, dan daftar tidak menyimpan versi besar', Slug::isReserved('about', ['ABOUT']) && Slug::reserved(['ABOUT']) === array_values(array_unique(array_merge(Slug::RESERVED, ['about']))) && !in_array('ABOUT', Slug::reserved(['ABOUT']), true));
check('slug yang diizinkan dikeluarkan walau ada di daftar (bawaan maupun config)', !Slug::isReserved('artikel', ['artikel'], 'artikel') && !Slug::isReserved('articles', [], 'articles') && Slug::isReserved('articles'));
check('isReserved tidak peka huruf besar-kecil dan spasi', Slug::isReserved(' ABOUT ', ['about']) && !Slug::isReserved('tentang-kami', ['about']));
check('daftar yang sama dipanggil dua kali menghasilkan hasil yang sama (tidak ada keadaan tersisa)', Slug::reserved(['a']) === Slug::reserved(['a']) && !in_array('a', Slug::reserved(), true));

echo "\nAturan ReservedSlug (langsung)\n";
$rule = new ReservedSlug(['about'], 'artikel');
$m = ruleFails($rule, 'about');
check('slug terlarang ditolak dengan pesan yang menyebut slug dan alasannya', $m !== null && str_contains($m, 'about') && str_contains($m, 'alamat tetap situs') && str_contains($m, ':attribute'), (string) $m);
check('slug biasa lolos; slug yang diizinkan ("artikel") lolos', ruleFails($rule, 'tentang-kami') === null && ruleFails($rule, 'artikel') === null);
check('format tidak sah atau bukan teks: aturan ini DIAM (aturan regex yang menolaknya) dan tidak memantulkan isi aneh ke pesan', ruleFails($rule, 'About Us!') === null && ruleFails($rule, "about\n") === null && ruleFails($rule, '<script>') === null && ruleFails($rule, 123) === null && ruleFails($rule, null) === null);

echo "\nContentRules dengan validator sungguhan\n";
$db = boot_database();
Schema::create('users', function ($t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
(require "$root/database/migrations/2026_10_07_000001_create_snippets_table.php")->up();
foreach (['pages', 'posts'] as $tb) { Schema::create($tb, function ($t) { $t->id(); $t->text('title')->nullable(); $t->text('slug')->nullable(); $t->text('content')->nullable(); }); }
DB::table('pages')->insert(['title' => '{"id":"Tentang","en":"About"}', 'slug' => '{"id":"tentang-kami","en":"about-us"}', 'content' => 'null']);
$dict = ['validation.required' => 'The :attribute field is required.', 'validation.unique' => 'The :attribute has already been taken.', 'validation.regex' => 'The :attribute format is invalid.'];
$translator = new class($dict) implements Illuminate\Contracts\Translation\Translator {
    public function __construct(private array $d) {}
    public function get($key, array $replace = [], $locale = null) { return $this->d[$key] ?? $key; }
    public function choice($key, $number, array $replace = [], $locale = null) { return $this->d[$key] ?? $key; }
    public function getLocale() { return 'id'; } public function setLocale($locale) {}
};
$factory = new Illuminate\Validation\Factory($translator, Illuminate\Container\Container::getInstance());
$factory->setPresenceVerifier(new Illuminate\Validation\DatabasePresenceVerifier($db->getDatabaseManager()));
$errs = function (array $slug, T $type, $id = null) use ($factory) {
    $data = ['status' => 'online', 'content' => [], 'titles' => ['id' => 'Judul', 'en' => 'Title'], 'slug' => $slug];
    return $factory->make($data, ContentRules::for($type, ['id', 'en'], $id), [], ContentRules::attributes($type, ['id', 'en']))->errors();
};

$GLOBALS['cfg'] = ['cms.reserved_slugs' => ['about', 'contact', 'programs']];   // seperti config/cms.php CMS
$e = $errs(['id' => 'about', 'en' => 'about-us-baru'], T::Page);
check('halaman: slug.id "about" (rute statis dari config) DITOLAK dengan pesan bernama "Slug (ID)"', $e->has('slug.id') && !$e->has('slug.en') && str_contains($e->first('slug.id'), 'Slug (ID)') && str_contains($e->first('slug.id'), 'alamat tetap situs'), json_encode($e->get('slug.id')));
$e = $errs(['id' => 'halaman-baru', 'en' => 'contact'], T::Page);
check('setiap bahasa diperiksa sendiri: slug.en "contact" ditolak, slug.id biasa lolos', $e->has('slug.en') && !$e->has('slug.id') && str_contains($e->first('slug.en'), 'Slug (EN)'));
check('slug teknis bawaan ("sitemap", "storage") ditolak walau TIDAK ada di config', $errs(['id' => 'sitemap', 'en' => 'storage'], T::Page)->has('slug.id') && $errs(['id' => 'x', 'en' => 'storage'], T::Page)->has('slug.en'));
$e = $errs(['id' => 'artikel', 'en' => 'about-ours'], T::Page);
check('"artikel" lolos sebagai slug halaman (tidak ada galat slug.id)', !$e->has('slug.id'), json_encode($e->get('slug.id')));
$e = $errs(['id' => 'articles', 'en' => 'x-en'], T::Page);
check('"articles" (pengalihan 301 ke /artikel) tetap terlarang karena rute pengalihan menang', $e->has('slug.id'));
$e = $errs(['id' => 'about', 'en' => 'contact'], T::Article);
check('ARTIKEL tidak terkena: slug "about" boleh (alamatnya /artikel/about)', !$e->has('slug.id') && !$e->has('slug.en'));
$e = $errs(['id' => 'about', 'en' => 'contact'], T::Snippet);
check('snippet tidak memakai slug: tidak ada galat slug', !$e->has('slug.id') && !$e->has('slug.en'));
$e = $errs(['id' => 'about', 'en' => 'about-us-lain'], T::Page, 1);
check('MENGEDIT halaman yang slug-nya terlarang juga ditolak (harus diganti); aturan unik tetap berjalan (about-us milik halaman lain)', $e->has('slug.id') && !$errs(['id' => 'tentang-kami', 'en' => 'about-us'], T::Page, 1)->has('slug.id'));
check('aturan unik lama tetap bekerja berdampingan: slug "tentang-kami" milik halaman lain ditolak untuk halaman baru', $errs(['id' => 'tentang-kami', 'en' => 'x-en'], T::Page)->has('slug.id'));
$e = $errs(['id' => 'About Us!', 'en' => 'x-en'], T::Page);
check('format tidak sah ditolak oleh aturan regex dengan SATU pesan format (pesan terlarang tidak ikut, tanpa memantulkan isi aneh)', $e->has('slug.id') && count($e->get('slug.id')) === 1 && !str_contains($e->first('slug.id'), 'alamat tetap'), json_encode($e->get('slug.id')));
$GLOBALS['cfg'] = [];
check('tanpa config sama sekali (belum ditambahkan di CMS): hanya daftar teknis berlaku, aplikasi tidak error; "about" lolos (BELUM terjaga)', !$errs(['id' => 'about', 'en' => 'contact'], T::Page)->has('slug.id') && $errs(['id' => 'build', 'en' => 'x-en'], T::Page)->has('slug.id'));
$GLOBALS['cfg'] = ['cms.reserved_slugs' => 'bukan-array', 'cms.articles_index_slug' => ''];
check('config salah bentuk (bukan array, slug kosong): tidak error; bawaan berlaku dan "artikel" tetap diizinkan', $errs(['id' => 'build', 'en' => 'x-en'], T::Page)->has('slug.id') && !$errs(['id' => 'artikel', 'en' => 'x-en'], T::Page)->has('slug.id'));
$GLOBALS['cfg'] = ['cms.reserved_slugs' => ['berita'], 'cms.articles_index_slug' => 'berita'];
check('slug kepala diganti ("berita"): kini diizinkan walau ada di daftar terlarang, dan "artikel" menjadi slug biasa', !$errs(['id' => 'berita', 'en' => 'x-en'], T::Page)->has('slug.id') && !$errs(['id' => 'artikel', 'en' => 'x-en'], T::Page)->has('slug.id'));

echo "\nSlugAudit (halaman lama yang sudah bertabrakan)\n";
$GLOBALS['cfg'] = [];
$rows = [
    ['id' => 1, 'title' => '{"id":"Tentang Kami","en":"About"}', 'slug' => '{"id":"about","en":"about-us"}'],
    ['id' => 2, 'title' => '{"id":"Kontak"}', 'slug' => '{"id":"kontak","en":"contact"}'],
    ['id' => 3, 'title' => '{"id":"Kepala"}', 'slug' => '{"id":"artikel","en":"articles"}'],
    ['id' => 4, 'title' => 'Teks lama', 'slug' => 'storage'],
    ['id' => 5, 'title' => null, 'slug' => null],
    ['id' => 6, 'title' => '{"id":"Aman"}', 'slug' => '{"id":"tentang-kami"}'],
    ['id' => 7, 'title' => ['id' => 'Larik'], 'slug' => ['id' => 'Programs', 'en' => 7]],
];
$found = SlugAudit::conflicts($rows, ['about', 'contact', 'programs'], 'artikel');
$by = array_map(fn ($f) => $f['id'] . ':' . $f['locale'] . ':' . $f['slug'], $found);
check('menemukan: about (id 1), contact en (id 2), articles en (id 3, rute pengalihan), teks polos "storage" (id 4, bahasa "*"), "Programs" tanpa peduli huruf (id 7)', $by === ['1:id:about', '2:en:contact', '3:en:articles', '4:*:storage', '7:id:Programs'], json_encode($by));
check('TIDAK menandai: slug biasa, "artikel" (diizinkan), baris kosong, nilai bukan teks', !in_array(6, array_column($found, 'id'), true) && !in_array(5, array_column($found, 'id'), true) && !in_array('3:id:artikel', $by, true));
check('judul dibaca dari JSON, teks polos, atau larik; baris tanpa judul = ""', $found[0]['title'] === 'Tentang Kami' && $found[3]['title'] === 'Teks lama' && $found[4]['title'] === 'Larik');
check('tanpa baris atau tanpa konflik: daftar kosong', SlugAudit::conflicts([], ['about']) === [] && SlugAudit::conflicts([['id' => 1, 'title' => 'x', 'slug' => '{"id":"halaman"}']], ['about']) === []);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
