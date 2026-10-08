<?php
/**
 * Beranda dari CMS (rilis 23):  php tests/beranda-test.php [akar-kit]
 * LinkResolver::homeUrl, LinkResolver::address (slug beranda -> "/"), dan Sitemap::entries($homeSlug). Murni, tanpa basis data.
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
$GLOBALS['cfg'] = [];
if (!function_exists('config')) { function config($key = null, $default = null) { return $GLOBALS['cfg'][$key] ?? $default; } }
require_once "$root/app/Content/Slug.php";
require_once "$root/app/Content/Links/LinkResolver.php";
require_once "$root/app/Content/Sitemap.php";

use App\Content\Links\LinkResolver as L;
use App\Content\Sitemap;

$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 300) . "]" : '') . "\n"; }

echo "\nhomeUrl(): hanya slug beranda yang menjadi \"/\"\n";
check('slug beranda tanpa alamat dasar -> "/"; dengan alamat dasar -> "https://host/"', L::homeUrl('home', 'home') === '/' && L::homeUrl('home', 'home', '') === '/' && L::homeUrl('home', 'home', null) === '/' && L::homeUrl('home', 'home', 'https://ysbh.org') === 'https://ysbh.org/');
check('garis miring dan spasi di alamat dasar dirapikan; port boleh', L::homeUrl('home', 'home', ' https://ysbh.org/ ') === 'https://ysbh.org/' && L::homeUrl('home', 'home', 'http://localhost:8001') === 'http://localhost:8001/');
check('slug LAIN -> null (halaman biasa tidak pernah menjadi "/")', L::homeUrl('tentang', 'home') === null && L::homeUrl('home-2', 'home') === null && L::homeUrl('hom', 'home') === null);
check('pencocokan persis (huruf besar-kecil dibedakan: slug sah selalu huruf kecil)', L::homeUrl('Home', 'home') === null && L::homeUrl('home', 'Home') === null);
check('slug kosong -> null, bahkan bila slug beranda juga kosong (tidak ada "beranda tanpa nama")', L::homeUrl('', 'home') === null && L::homeUrl('', '') === null);
foreach ([null, '', 0, false, ['home'], 'Home!', 'a b', '-home', 'home/', '../home', "home\n", 'a--b', 'home_'] as $bad) {
    check('slug beranda tidak sah (' . json_encode($bad) . ') -> null: tidak ada beranda', L::homeUrl('home', $bad) === null && L::homeUrl((string) (is_scalar($bad) ? $bad : ''), $bad) === null);
}
foreach (['javascript:alert(1)', '//evil.test', 'ysbh.org', 'https://ysbh.org/jalur', 'https://user@ysbh.org', 'ftp://ysbh.org', ['https://ysbh.org']] as $badBase) {
    check('alamat dasar tidak sah (' . json_encode($badBase) . ') -> null, bukan alamat relatif', L::homeUrl('home', 'home', $badBase) === null);
}

echo "\naddress(): tautan internal ke halaman beranda\n";
$GLOBALS['cfg'] = ['cms.public.page' => '/{slug}', 'cms.public.article' => '/artikel/{slug}', 'cms.public.base' => '', 'cms.home_slug' => 'home'];
check('address(page, home) = "/" ; halaman lain tetap "/{slug}"', L::address('page', 'home') === '/' && L::address('page', 'tentang-kami') === '/tentang-kami');
check('artikel ber-slug "home" TIDAK menjadi "/" (beranda hanya untuk halaman)', L::address('article', 'home') === '/artikel/home');
$GLOBALS['cfg']['cms.public.base'] = 'https://ysbh.org';
check('dengan alamat dasar (CMS menautkan ke landing): "https://ysbh.org/"', L::address('page', 'home') === 'https://ysbh.org/' && L::address('page', 'x') === 'https://ysbh.org/x');
$GLOBALS['cfg']['cms.public.base'] = 'ysbh.org';
check('alamat dasar rusak: null (bukan "/" yang menyesatkan)', L::address('page', 'home') === null);
$GLOBALS['cfg'] = ['cms.public.page' => '/{slug}', 'cms.public.base' => ''];
check('config tanpa home_slug (CMS yang belum diperbarui): "/home" seperti sebelumnya; landing mengalihkannya 301 ke "/"', L::address('page', 'home') === '/home');
$GLOBALS['cfg']['cms.home_slug'] = '';
check('home_slug kosong: tidak ada beranda, "/home"', L::address('page', 'home') === '/home');

echo "\nSitemap::entries(): beranda sebagai \"/\"\n";
$B = 'https://ysbh.org'; $PT = '/{slug}'; $AT = '/artikel/{slug}';
$rows = [['kind' => 'page', 'slug' => 'home', 'lastmod' => '2026-10-01'], ['kind' => 'page', 'slug' => 'tentang-kami', 'lastmod' => '2026-09-01'], ['kind' => 'article', 'slug' => 'home', 'lastmod' => '2026-08-01']];
$locs = fn (array $e) => array_column($e, 'loc');
check('halaman beranda -> "{base}/" ; halaman lain "{base}/{slug}" ; ARTIKEL ber-slug home tetap /artikel/home', $locs(Sitemap::entries($rows, ['/artikel'], $B, $PT, $AT, 'home')) === ["$B/", "$B/tentang-kami", "$B/artikel/home", "$B/artikel"], json_encode($locs(Sitemap::entries($rows, ['/artikel'], $B, $PT, $AT, 'home'))));
check('tanpa $homeSlug (perilaku lama): beranda masih "/home"', in_array("$B/home", $locs(Sitemap::entries($rows, [], $B, $PT, $AT)), true) && !in_array("$B/", $locs(Sitemap::entries($rows, [], $B, $PT, $AT)), true));
check('$homeSlug tidak sah (null, "", bukan teks, "Bukan Slug"): perilaku lama, tidak pernah "/" liar', array_filter([null, '', 5, ['home'], 'Bukan Slug'], fn ($h) => in_array("$B/", $locs(Sitemap::entries($rows, [], $B, $PT, $AT, $h)), true)) === []);
$e = Sitemap::entries($rows, ['/', '/artikel'], $B, $PT, $AT, 'home');
check('"/" ada di jalur statis DAN beranda dari basis data: satu entri saja, lastmod beranda dipertahankan', count(array_filter($locs($e), fn ($l) => $l === "$B/")) === 1 && array_values(array_filter($e, fn ($x) => $x['loc'] === "$B/"))[0]['lastmod'] === '2026-10-01', json_encode($e));
check('alamat dasar tidak sah: hasil KOSONG (beranda pun tidak lolos sebagai "/")', Sitemap::entries($rows, ['/'], 'ysbh.org', $PT, $AT, 'home') === [] && Sitemap::entries($rows, [], 'https://ysbh.org/x', $PT, $AT, 'home') === []);
check('templat halaman rusak tidak menjatuhkan beranda: beranda tetap "/" (tidak butuh templat), halaman lain hilang', $locs(Sitemap::entries($rows, [], $B, 'tanpa-penanda', $AT, 'home')) === ["$B/", "$B/artikel/home"]);
check('keluaran XML memuat <loc>https://…/</loc> bentuk sah', str_contains(Sitemap::xml(Sitemap::entries($rows, [], $B, $PT, $AT, 'home')), "<loc>$B/</loc>"));

echo "\nSifat (acak): hanya slug yang SAMA dengan slug beranda yang pernah menjadi \"/\"\n";
mt_srand(2310);
$alpha = 'abcdefghijklmnopqrstuvwxyzABC0123456789-_/.?# %"<>\\';
$rnd = function () use ($alpha) { $n = mt_rand(0, 8); $s = ''; for ($i = 0; $i < $n; $i++) { $s .= $alpha[mt_rand(0, strlen($alpha) - 1)]; } return $s; };
$bad = 0; $hits = 0;
for ($i = 0; $i < 4000; $i++) {
    $slug = $rnd(); $home = mt_rand(0, 3) ? $slug : $rnd(); $base = ['', 'https://ysbh.org', 'x', $rnd()][mt_rand(0, 3)];
    $r = L::homeUrl($slug, $home, $base);
    if ($r !== null) { $hits++; if ($slug !== $home || $slug === '' || !str_ends_with($r, '/') || !preg_match('#^(?:https?://[A-Za-z0-9.-]+(?::\d{1,5})?)?/$#D', $r) || !\App\Content\Slug::isValid($slug)) { $bad++; } }
    elseif ($slug === $home && \App\Content\Slug::isValid($slug) && ($base === '' || $base === 'https://ysbh.org')) { $bad++; }   // beranda sah dengan alamat dasar sah HARUS menghasilkan alamat
}
check("4000 kombinasi acak: tidak ada pelanggaran ($hits beranda sah ditemukan)", $bad === 0 && $hits > 100, "pelanggaran $bad, hits $hits");

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
