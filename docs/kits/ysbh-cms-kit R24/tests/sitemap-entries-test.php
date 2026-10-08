<?php
/**
 * Bahan peta situs dari basis data:  php tests/sitemap-entries-test.php <bootstrap-lab.php> <support.php>
 * PublicLookup::sitemapEntries() dengan model landing yang SEBENARNYA, lalu ujung ke ujung sampai XML.
 */
[$_, $bootstrap, $support] = $argv + [null, null, null];
$root = dirname(__DIR__);
require $bootstrap;
require $support;
foreach (['ReadOnlyModel', 'Page', 'Post', 'Category', 'Snippet', 'Media'] as $m) { require "$root/landing-app/app/Models/$m.php"; }

use App\Content\{PublicLookup as PL, Sitemap};
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Schema;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 500) . "]" : '') . "\n"; }

boot_database();
foreach (['pages', 'posts'] as $tb) {
    Schema::create($tb, function ($t) use ($tb) { $t->id(); $t->text('title')->nullable(); $t->text('slug')->nullable(); $t->text('content')->nullable(); $t->string('status')->default('offline'); if ($tb === 'posts') { $t->text('published_at')->nullable(); } $t->text('updated_at')->nullable(); $t->text('created_at')->nullable(); });
}
$T = '{"id":"T","en":"T"}';
$page = fn (?string $slug, string $status, ?string $updated, string $title = '{"id":"T","en":"T"}') => DB::table('pages')->insertGetId(['title' => $title, 'slug' => $slug, 'status' => $status, 'content' => 'null', 'updated_at' => $updated, 'created_at' => $updated]);
$post = fn (?string $slug, string $status, ?string $published, ?string $updated, string $title = '{"id":"T","en":"T"}') => DB::table('posts')->insertGetId(['title' => $title, 'slug' => $slug, 'status' => $status, 'content' => 'null', 'published_at' => $published, 'updated_at' => $updated, 'created_at' => $updated]);

$page('{"id":"tentang-kami","en":"about-us"}', 'online', '2026-10-01 10:00:00');          // 1
$page('{"id":"draf","en":"draft"}', 'offline', '2026-10-02 10:00:00');                  // 2 offline
$page('{"id":"sama","en":"sama"}', 'online', '2026-10-03 10:00:00');                    // 3 slug identik antar bahasa: DUA alamat (/sama dan /id/sama)
$page('lama-sekali', 'online', '2026-10-04 10:00:00');                                  // 4 baris lama: teks polos (tak pernah bisa dibuka di situs: dilewati)
$page('{"id":"Slug Rusak","en":"slug-ok"}', 'online', '0000-00-00 00:00:00');           // 5 slug id tak sah; tanggal mustahil
$page(null, 'online', '2026-10-05 10:00:00');                                           // 6 tanpa slug
$page('{"fr":"salut"}', 'online', '2026-10-06 10:00:00');                               // 7 bahasa di luar daftar
$page('{"id":"artikel","en":"articles"}', 'online', '2026-10-07 10:00:00');             // 8 kepala daftar artikel
$page('{"id":"beranda","en":"home"}', 'online', '2026-10-08 10:00:00');                 // 9 beranda
$page('{"id":"hanya-id","en":"only-id-en"}', 'online', '2026-10-09 10:00:00', '{"id":"Hanya ID","en":""}');   // 10 judul en kosong = belum diterjemahkan
$post('{"id":"artikel-a","en":"article-a"}', 'published', '2026-09-01 08:00:00', '2026-09-30 09:00:00');   // 1
$post('{"id":"artikel-b"}', 'published', '2026-08-15 08:00:00', null, '{"id":"B"}');                      // 2 hanya id; tanpa updated_at -> pakai published_at
$post('{"id":"artikel-draf"}', 'draft', null, '2026-09-29 09:00:00');                                     // 3 draf
$post('{"id":"../etc/passwd","en":"a b"}', 'published', '2026-08-01 08:00:00', null);                      // 4 slug berbahaya

echo "\nPublicLookup::sitemapEntries (rilis 24: satu baris per halaman per BAHASA, bertanda bahasa)\n";
$r = PL::sitemapEntries(['id', 'en']);
$keys = array_map(fn ($x) => $x['kind'] . ':' . $x['locale'] . ':' . $x['slug'], $r);
check('halaman online per bahasa: tentang-kami (id) + about-us (en), sama di KEDUA bahasa, slug-ok (en; yang sah saja), artikel + articles, beranda + home, hanya-id (id saja: judul en kosong); urut id naik', array_slice($keys, 0, 10) === ['page:id:tentang-kami', 'page:en:about-us', 'page:id:sama', 'page:en:sama', 'page:en:slug-ok', 'page:id:artikel', 'page:en:articles', 'page:id:beranda', 'page:en:home', 'page:id:hanya-id'], json_encode($keys));
check('artikel terbit per bahasa: artikel-a (id) + article-a (en), artikel-b (id saja); draf TIDAK; slug berbahaya ("../etc/passwd", "a b") TIDAK; halaman lebih dulu dari artikel', array_values(array_filter($keys, fn ($k) => str_starts_with($k, 'article:'))) === ['article:id:artikel-a', 'article:en:article-a', 'article:id:artikel-b'] && array_search('article:id:artikel-a', $keys, true) > array_search('page:id:hanya-id', $keys, true), json_encode($keys));
check('halaman offline, tanpa slug, baris lama teks polos, dan bahasa di luar daftar tidak masuk', !array_filter($keys, fn ($k) => preg_match('/:(draf|draft|lama-sekali|salut)$/', $k)));
check('hanya slug yang SAH sebagai slug (tidak ada spasi, titik-titik, huruf besar, garis miring)', array_filter($keys, fn ($k) => !preg_match('/^(page|article):[a-z]{2}:[a-z0-9]+(?:-[a-z0-9]+)*$/', $k)) === [], json_encode($keys));
check('judul bahasa itu kosong = belum diterjemahkan = tidak masuk (hanya-id ada untuk id, "only-id-en" untuk en TIDAK)', in_array('page:id:hanya-id', $keys, true) && !in_array('page:en:only-id-en', $keys, true));
$lm = fn (string $k) => ($r[array_search($k, $keys, true)] ?? ['lastmod' => 'TIDAK-ADA'])['lastmod'];
check('lastmod: updated_at halaman; tanggal mustahil (0000-00-00) -> null; artikel: updated_at, atau published_at bila updated_at kosong', $lm('page:id:tentang-kami') === '2026-10-01' && $lm('page:en:about-us') === '2026-10-01' && $lm('page:en:slug-ok') === null && $lm('article:id:artikel-a') === '2026-09-30' && $lm('article:id:artikel-b') === '2026-08-15', json_encode([$lm('page:id:tentang-kami'), $lm('page:en:slug-ok'), $lm('article:id:artikel-a'), $lm('article:id:artikel-b')]));
check('daftar bahasa dihormati: hanya ["id"] -> tanpa baris en; ["en"] -> tanpa baris id; dengan "fr" -> tidak ada (judul fr kosong); urutan baris mengikuti urutan daftar bahasa', (function () { $id = array_unique(array_column(PL::sitemapEntries(['id']), 'locale')); $en = array_unique(array_column(PL::sitemapEntries(['en']), 'locale')); $fr = array_map(fn ($x) => $x['slug'], PL::sitemapEntries(['fr'])); return $id === ['id'] && $en === ['en'] && $fr === [] && array_map(fn ($x) => $x['locale'], PL::sitemapEntries(['en', 'id'], 1)) === ['en', 'id', 'en', 'id']; })());   // "salut" (fr) tak masuk: judul fr kosong = belum diterjemahkan
check('batas per jenis ($limit) berlaku untuk halaman dan artikel masing-masing; batas < 1 menjadi 1', (function () { $a = PL::sitemapEntries(['id', 'en'], 1); $kinds = array_count_values(array_column($a, 'kind')); return ($kinds['page'] ?? 0) === 2 && ($kinds['article'] ?? 0) === 2 && PL::sitemapEntries(['id'], 0) === PL::sitemapEntries(['id'], 1); })());
check('hasil identik dipanggil dua kali (tanpa keadaan tersisa) dan tanpa baris kembar kind+bahasa+slug', PL::sitemapEntries(['id', 'en']) === $r && count($keys) === count(array_unique($keys)));
DB::table('posts')->update(['slug' => 'bukan json {']);
check('kolom slug bukan JSON dan bukan slug sah ("bukan json {"): dilewati, peta situs TIDAK jatuh', (function () { $x = PL::sitemapEntries(['id', 'en']); return !array_filter($x, fn ($y) => $y['kind'] === 'article'); })());

echo "\nUjung ke ujung: basis data -> alamat per bahasa -> XML\n";
DB::table('posts')->where('id', 1)->update(['slug' => '{"id":"artikel-a","en":"article-a"}']);
$PT = ['en' => '/{slug}', 'id' => '/id/{slug}']; $AT = ['en' => '/articles/{slug}', 'id' => '/id/artikel/{slug}']; $HS = ['en' => 'home', 'id' => 'beranda']; $HP = ['en' => '/', 'id' => '/id'];
$entries = Sitemap::entries(PL::sitemapEntries(['en', 'id']), ['/articles', '/id/artikel', '/programs/malaria'], 'https://ysbh.org', $PT, $AT, $HS, $HP);
$locs = array_column($entries, 'loc');
check('alamat tiap bahasa memakai templatnya: /about-us dan /id/tentang-kami; /articles/article-a dan /id/artikel/artikel-a; jalur statis ikut', in_array('https://ysbh.org/about-us', $locs, true) && in_array('https://ysbh.org/id/tentang-kami', $locs, true) && in_array('https://ysbh.org/articles/article-a', $locs, true) && in_array('https://ysbh.org/id/artikel/artikel-a', $locs, true) && in_array('https://ysbh.org/programs/malaria', $locs, true), json_encode($locs));
check('slug sama di dua bahasa menjadi DUA alamat ("/sama" dan "/id/sama"), bukan satu', in_array('https://ysbh.org/sama', $locs, true) && in_array('https://ysbh.org/id/sama', $locs, true));
check('beranda: "https://ysbh.org/" (en) dan "https://ysbh.org/id" (id); "/home" dan "/id/beranda" (yang hanya mengalihkan) TIDAK ada', in_array('https://ysbh.org/', $locs, true) && in_array('https://ysbh.org/id', $locs, true) && !in_array('https://ysbh.org/home', $locs, true) && !in_array('https://ysbh.org/id/beranda', $locs, true), json_encode($locs));
check('halaman CMS kepala daftar ("articles" en, "artikel" id) dan jalur statis /articles, /id/artikel menjadi SATU alamat masing-masing dengan lastmod dari halaman CMS (2026-10-07)', count(array_keys($locs, 'https://ysbh.org/articles', true)) === 1 && count(array_keys($locs, 'https://ysbh.org/id/artikel', true)) === 1 && ($entries[array_search('https://ysbh.org/articles', $locs, true)]['lastmod'] ?? null) === '2026-10-07' && ($entries[array_search('https://ysbh.org/id/artikel', $locs, true)]['lastmod'] ?? null) === '2026-10-07');
check('draf, offline, dan slug berbahaya tidak muncul di alamat mana pun', !array_filter($locs, fn ($l) => preg_match('#/(draf|draft|artikel-draf|etc|passwd|salut|only-id-en)#', $l)), json_encode($locs));
$xml = Sitemap::xml($entries);
preg_match_all('#<loc>(.*?)</loc>#', $xml, $m);
check('XML memuat tepat alamat yang sama, dan tidak ada karakter istimewa mentah', $m[1] === $locs && !preg_match('/[<>"\'&](?!(amp|lt|gt|quot|apos);)/', implode('', $m[1])) && str_starts_with($xml, '<?xml version="1.0" encoding="UTF-8"?>'));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
