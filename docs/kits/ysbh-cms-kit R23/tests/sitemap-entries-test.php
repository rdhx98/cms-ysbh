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
$page = fn (?string $slug, string $status, ?string $updated) => DB::table('pages')->insertGetId(['title' => '{"id":"T"}', 'slug' => $slug, 'status' => $status, 'content' => 'null', 'updated_at' => $updated, 'created_at' => $updated]);
$post = fn (?string $slug, string $status, ?string $published, ?string $updated) => DB::table('posts')->insertGetId(['title' => '{"id":"T"}', 'slug' => $slug, 'status' => $status, 'content' => 'null', 'published_at' => $published, 'updated_at' => $updated, 'created_at' => $updated]);

$page('{"id":"tentang-kami","en":"about-us"}', 'online', '2026-10-01 10:00:00');          // 1
$page('{"id":"draf","en":"draft"}', 'offline', '2026-10-02 10:00:00');                  // 2 offline
$page('{"id":"sama","en":"sama"}', 'online', '2026-10-03 10:00:00');                    // 3 slug identik antar bahasa
$page('lama-sekali', 'online', '2026-10-04 10:00:00');                                  // 4 baris lama: teks polos
$page('{"id":"Slug Rusak","en":"slug-ok"}', 'online', '0000-00-00 00:00:00');           // 5 slug id tak sah; tanggal mustahil
$page(null, 'online', '2026-10-05 10:00:00');                                           // 6 tanpa slug
$page('{"fr":"salut"}', 'online', '2026-10-06 10:00:00');                               // 7 bahasa di luar daftar
$page('{"id":"artikel","en":"articles-head"}', 'online', '2026-10-07 10:00:00');        // 8 kepala daftar artikel
$post('{"id":"artikel-a","en":"article-a"}', 'published', '2026-09-01 08:00:00', '2026-09-30 09:00:00');   // 1
$post('{"id":"artikel-b"}', 'published', '2026-08-15 08:00:00', null);                                    // 2 tanpa updated_at -> pakai published_at
$post('{"id":"artikel-draf"}', 'draft', null, '2026-09-29 09:00:00');                                     // 3 draf
$post('{"id":"../etc/passwd","en":"a b"}', 'published', '2026-08-01 08:00:00', null);                      // 4 slug berbahaya

echo "\nPublicLookup::sitemapEntries\n";
$r = PL::sitemapEntries(['id', 'en']);
$keys = array_map(fn ($x) => $x['kind'] . ':' . $x['slug'], $r);
check('halaman online per bahasa: tentang-kami + about-us, sama (SATU kali), teks polos lama-sekali, slug-ok (yang sah saja), artikel + articles-head; urut id naik', array_slice($keys, 0, 7) === ['page:tentang-kami', 'page:about-us', 'page:sama', 'page:lama-sekali', 'page:slug-ok', 'page:artikel', 'page:articles-head'], json_encode($keys));
check('artikel terbit per bahasa: artikel-a + article-a, artikel-b; draf TIDAK; slug berbahaya ("../etc/passwd", "a b") TIDAK; halaman lebih dulu dari artikel', array_slice($keys, 7) === ['article:artikel-a', 'article:article-a', 'article:artikel-b'], json_encode($keys));
check('halaman offline, tanpa slug, dan bahasa di luar daftar tidak masuk; slug sama antar bahasa tidak ganda', !in_array('page:draf', $keys, true) && !in_array('page:draft', $keys, true) && !in_array('page:salut', $keys, true) && count(array_keys($keys, 'page:sama', true)) === 1);
check('hanya slug yang SAH sebagai slug (tidak ada spasi, titik-titik, huruf besar, garis miring)', array_filter($keys, fn ($k) => !preg_match('/^(page|article):[a-z0-9]+(?:-[a-z0-9]+)*$/', $k)) === [], json_encode($keys));
$by = array_column($r, 'lastmod', null);
$lm = fn (string $k) => ($r[array_search($k, $keys, true)] ?? ['lastmod' => 'TIDAK-ADA'])['lastmod'];
check('lastmod: updated_at halaman; tanggal mustahil (0000-00-00) -> null; artikel: updated_at, atau published_at bila updated_at kosong', $lm('page:tentang-kami') === '2026-10-01' && $lm('page:about-us') === '2026-10-01' && $lm('page:slug-ok') === null && $lm('article:artikel-a') === '2026-09-30' && $lm('article:artikel-b') === '2026-08-15', json_encode([$lm('page:tentang-kami'), $lm('page:slug-ok'), $lm('article:artikel-a'), $lm('article:artikel-b')]));
check('daftar bahasa dihormati: hanya ["id"] -> tanpa slug en; ["en"] -> tanpa slug id; dengan "fr" -> salut ikut (dan baris lama teks polos selalu ikut)', (function () { $id = array_map(fn ($x) => $x['slug'], PL::sitemapEntries(['id'])); $en = array_map(fn ($x) => $x['slug'], PL::sitemapEntries(['en'])); $fr = array_map(fn ($x) => $x['slug'], PL::sitemapEntries(['fr'])); return !in_array('about-us', $id, true) && in_array('tentang-kami', $id, true) && !in_array('tentang-kami', $en, true) && in_array('about-us', $en, true) && $fr === ['lama-sekali', 'salut']; })());   // baris lama bertipe teks polos tidak terikat bahasa: ikut untuk daftar bahasa mana pun
check('batas per jenis ($limit) berlaku untuk halaman dan artikel masing-masing; batas < 1 menjadi 1', (function () { $a = PL::sitemapEntries(['id', 'en'], 1); $kinds = array_count_values(array_column($a, 'kind')); return ($kinds['page'] ?? 0) === 2 && ($kinds['article'] ?? 0) === 2 && PL::sitemapEntries(['id'], 0) === PL::sitemapEntries(['id'], 1); })());
check('hasil identik dipanggil dua kali (tanpa keadaan tersisa) dan tanpa baris kembar kind+slug', PL::sitemapEntries(['id', 'en']) === $r && count($keys) === count(array_unique($keys)));
DB::table('posts')->update(['slug' => 'bukan json {']);
check('kolom slug bukan JSON dan bukan slug sah ("bukan json {"): dilewati, peta situs TIDAK jatuh', (function () { $x = PL::sitemapEntries(['id', 'en']); return !array_filter($x, fn ($y) => $y['kind'] === 'article'); })());

echo "\nUjung ke ujung: basis data -> alamat -> XML\n";
DB::table('posts')->where('id', 1)->update(['slug' => '{"id":"artikel-a","en":"article-a"}']);
$entries = Sitemap::entries(PL::sitemapEntries(['id', 'en']), ['/', '/about', '/artikel', '/programs/malaria'], 'https://ysbh.org', '/{slug}', '/artikel/{slug}');
$locs = array_column($entries, 'loc');
check('kedua alamat bahasa muncul (/tentang-kami dan /about-us; /artikel/artikel-a dan /artikel/article-a); jalur statis ikut', in_array('https://ysbh.org/tentang-kami', $locs, true) && in_array('https://ysbh.org/about-us', $locs, true) && in_array('https://ysbh.org/artikel/artikel-a', $locs, true) && in_array('https://ysbh.org/artikel/article-a', $locs, true) && in_array('https://ysbh.org/', $locs, true) && in_array('https://ysbh.org/programs/malaria', $locs, true), json_encode($locs));
check('halaman CMS "artikel" (kepala daftar) dan jalur statis /artikel menjadi SATU alamat dengan lastmod dari halaman CMS (2026-10-07)', count(array_keys($locs, 'https://ysbh.org/artikel', true)) === 1 && ($entries[array_search('https://ysbh.org/artikel', $locs, true)]['lastmod'] ?? null) === '2026-10-07');
check('draf, offline, dan slug berbahaya tidak muncul di alamat mana pun', !array_filter($locs, fn ($l) => preg_match('#/(draf|draft|artikel-draf|etc|passwd|salut)#', $l)), json_encode($locs));
$xml = Sitemap::xml($entries);
preg_match_all('#<loc>(.*?)</loc>#', $xml, $m);
check('XML memuat tepat alamat yang sama, dan tidak ada karakter istimewa mentah', $m[1] === $locs && !preg_match('/[<>"\'&](?!(amp|lt|gt|quot|apos);)/', implode('', $m[1])) && str_starts_with($xml, '<?xml version="1.0" encoding="UTF-8"?>'));

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
