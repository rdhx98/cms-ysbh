<?php
/**
 * Bahasa situs (rilis 24):  php tests/languages-test.php [akar-kit]
 * Languages, NavLinks, Slug per bahasa, LinkResolver::homeUrl/pathUrl/publicUrl per bahasa, Sitemap::entries per bahasa. Semua murni (tanpa Laravel).
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
foreach (['Languages', 'Slug', 'NavLinks', 'Links/LinkResolver', 'Sitemap'] as $f) { require_once "$root/app/Content/$f.php"; }

use App\Content\{Languages as Lg, NavLinks, Sitemap, Slug};
use App\Content\Links\LinkResolver as L;

$pass = $fail = 0;
$GLOBALS['cfg'] = []; $GLOBALS['path'] = '';
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 400) . "]" : '') . "\n"; }

echo "\nLanguages: kode dan bahasa bawaan\n";
check('codes: daftar bersih, urutan dipertahankan, duplikat dan kode tidak sah dibuang', Lg::codes(['en', 'id', 'en', 'ID', 'x', 'pt-br', 7, null]) === ['en', 'id', 'pt-br']);
check('codes: bukan larik atau tidak ada yang sah = cadangan', Lg::codes('en') === ['en', 'id'] && Lg::codes([]) === ['en', 'id'] && Lg::codes(['??'], ['en']) === ['en']);
check('defaultOf: bahasa yang diatur bila termasuk daftar; jika tidak bahasa pertama', Lg::defaultOf('id', ['en', 'id']) === 'id' && Lg::defaultOf('fr', ['en', 'id']) === 'en' && Lg::defaultOf(null, ['id', 'en']) === 'id' && Lg::defaultOf(5, []) === 'en');
check('prefix: bahasa bawaan dan kosong tanpa awalan; bahasa lain "/kode"', Lg::prefix('en', 'en') === '' && Lg::prefix('', 'en') === '' && Lg::prefix('id', 'en') === '/id');

echo "\nLanguages::fromPath (bahasa hanya dari alamat)\n";
$L = ['en', 'id'];
check('"id" dan "id/..." = id; "/id/artikel/x" = id', Lg::fromPath('id', $L, 'en') === 'id' && Lg::fromPath('/id/tentang-kami', $L, 'en') === 'id' && Lg::fromPath('id/artikel/x', $L, 'en') === 'id');
check('tanpa awalan = bahasa bawaan; "/" dan "" juga', Lg::fromPath('about-us', $L, 'en') === 'en' && Lg::fromPath('/', $L, 'en') === 'en' && Lg::fromPath('', $L, 'en') === 'en');
check('"/en/..." bukan awalan (bahasa bawaan tanpa awalan): tetap bahasa bawaan', Lg::fromPath('en/about', $L, 'en') === 'en');
check('segmen pertama yang mirip tetapi bukan kode ("idx", "identitas") = bahasa bawaan; kode di segmen kedua diabaikan', Lg::fromPath('identitas', $L, 'en') === 'en' && Lg::fromPath('about/id', $L, 'en') === 'en');
check('bila bahasa bawaan id: "en/..." = en, tanpa awalan = id', Lg::fromPath('en/about', $L, 'id') === 'en' && Lg::fromPath('tentang', $L, 'id') === 'id' && Lg::fromPath('id/x', $L, 'id') === 'id');

echo "\nLanguages::setting dan turunannya (teks = semua bahasa, peta = per bahasa)\n";
check('setting: teks berlaku untuk semua bahasa (dipangkas)', Lg::setting(' /{slug} ', 'id') === '/{slug}' && Lg::setting('/{slug}', 'en') === '/{slug}');
check('setting: peta mengambil bahasanya; tidak ada / kosong / bukan teks = cadangan', Lg::setting(['en' => '/a', 'id' => '/id/a'], 'id') === '/id/a' && Lg::setting(['en' => '/a'], 'id', 'x') === 'x' && Lg::setting(['id' => ' '], 'id', 'x') === 'x' && Lg::setting(['id' => 5], 'id', 'x') === 'x' && Lg::setting(null, 'id') === '');
check('homePath: dari config; jika tidak: "/" (bawaan) atau "/kode"', Lg::homePath(['en' => '/', 'id' => '/id'], 'id', 'en') === '/id' && Lg::homePath(null, 'en', 'en') === '/' && Lg::homePath(null, 'id', 'en') === '/id' && Lg::homePath(['en' => '/x'], 'id', 'en') === '/id');
check('indexSlug: bawaan "artikel" (id) / "articles" (lain); config menimpa', Lg::indexSlug(null, 'id') === 'artikel' && Lg::indexSlug(null, 'en') === 'articles' && Lg::indexSlug(['id' => 'berita'], 'id') === 'berita' && Lg::indexSlug(['id' => 'berita'], 'en') === 'articles' && Lg::indexSlug('semua', 'en') === 'semua');
check('homeSlug: bawaan "beranda" (id) / "home" (lain); config menimpa', Lg::homeSlug(null, 'id') === 'beranda' && Lg::homeSlug(null, 'en') === 'home' && Lg::homeSlug(['en' => 'start'], 'en') === 'start' && Lg::homeSlug(['en' => 'start'], 'id') === 'beranda');
check('isMap: peta berkunci teks saja; daftar, kosong, bukan larik bukan peta', Lg::isMap(['en' => 'a']) && !Lg::isMap(['a', 'b']) && !Lg::isMap([]) && !Lg::isMap('x') && !Lg::isMap([0 => 'a', 'id' => 'b']));
check('fromConfig tanpa config terisi (atau di luar Laravel): ["en","id"] / en', Lg::fromConfig() === ['locales' => ['en', 'id'], 'default' => 'en']);

echo "\nSlug: terlarang per bahasa\n";
check('"articles" bukan lagi terlarang bawaan; "storage" tetap', !in_array('articles', Slug::RESERVED, true) && in_array('storage', Slug::RESERVED, true));
check('bahasa bawaan: kode bahasa lain ("id") terlarang (beranda /id); bahasa id: tidak', Slug::isReserved('id', [], null, 'en', ['en', 'id'], 'en') && !Slug::isReserved('id', [], null, 'id', ['en', 'id'], 'en') && !Slug::isReserved('en', [], null, 'en', ['en', 'id'], 'en'));
check('tanpa bahasa (gabungan): kode bahasa non-bawaan ikut terlarang', Slug::isReserved('id', [], null, null, ['en', 'id'], 'en'));
check('peta config: hanya bahasa itu (+ "*" untuk semua)', Slug::isReserved('about', ['en' => ['about']], null, 'en') && !Slug::isReserved('about', ['en' => ['about']], null, 'id') && Slug::isReserved('tentang', ['*' => ['tentang']], null, 'id') && Slug::isReserved('tentang', ['*' => ['tentang']], null, 'en'));
check('peta config tanpa bahasa (null): gabungan semua bahasa; nilai bukan larik diabaikan', Slug::isReserved('tentang', ['id' => ['tentang']]) && !Slug::isReserved('x', ['id' => 'x']));
check('daftar lama (tanpa kunci bahasa) berlaku untuk semua bahasa', Slug::isReserved('about', ['about'], null, 'id') && Slug::isReserved('about', ['about'], null, 'en'));
check('slug kepala daftar artikel bahasa itu diizinkan walau terdaftar', !Slug::isReserved('artikel', ['id' => ['artikel']], 'artikel', 'id') && Slug::isReserved('artikel', ['id' => ['artikel']], null, 'id'));

echo "\nLinkResolver: jalur tetap, beranda, templat per bahasa\n";
check('pathUrl: "/" , "/id", "/id/artikel" sah; + alamat dasar', L::pathUrl('/') === '/' && L::pathUrl('/id') === '/id' && L::pathUrl('/id/artikel', 'https://ysbh.org/') === 'https://ysbh.org/id/artikel' && L::pathUrl('/', 'https://ysbh.org') === 'https://ysbh.org/');
check('pathUrl: rusak = null (tanpa awal "/", "//", garis miring di ujung, spasi, "..", bukan teks, alamat dasar rusak)', array_reduce(['id', '//x', '/x/', '/a b', '/a?b', '', '/a//b'], fn ($c, $p) => $c && L::pathUrl($p) === null, true) && L::pathUrl(['/']) === null && L::pathUrl('/id', 'ysbh.org') === null && L::pathUrl('/id', ['x']) === null);
check('homeUrl: slug = slug beranda -> alamat beranda bahasa itu', L::homeUrl('home', 'home', 'https://ysbh.org', '/') === 'https://ysbh.org/' && L::homeUrl('beranda', 'beranda', 'https://ysbh.org', '/id') === 'https://ysbh.org/id');
check('homeUrl: slug lain / slug beranda kosong, bukan teks, atau tidak sah / jalur rusak = null', L::homeUrl('about', 'home', '', '/') === null && L::homeUrl('home', '', '', '/') === null && L::homeUrl('home', ['home'], '', '/') === null && L::homeUrl('Home!', 'Home!', '', '/') === null && L::homeUrl('', '', '', '/') === null && L::homeUrl('home', 'home', '', 'id') === null);
check('publicUrl dengan templat per bahasa yang sudah dipilih Languages::setting', L::publicUrl('tentang-kami', Lg::setting(['en' => '/{slug}', 'id' => '/id/{slug}'], 'id'), 'https://ysbh.org') === 'https://ysbh.org/id/tentang-kami' && L::publicUrl('about-us', Lg::setting(['en' => '/{slug}', 'id' => '/id/{slug}'], 'en'), 'https://ysbh.org') === 'https://ysbh.org/about-us');

echo "\nNavLinks::localize (tautan menu satu isian untuk dua bahasa)\n";
$loc = ['en', 'id'];
$pages = ['about-us' => ['en' => 'about-us', 'id' => 'tentang-kami'], 'tentang-kami' => ['en' => 'about-us', 'id' => 'tentang-kami'], 'only-id' => ['id' => 'hanya-id'], 'hanya-id' => ['id' => 'hanya-id']];
$sibling = function (string $slug, string $lang) use ($pages) { $p = $pages[$slug] ?? null; if ($p === null) { return null; } if (isset($p[$lang])) { return ['locale' => $lang, 'slug' => $p[$lang]]; } $f = array_key_first($p); return ['locale' => $f, 'slug' => $p[$f]]; };
$pageAddr = fn (string $slug, string $lang) => ($lang === 'en' ? '' : '/id') . '/' . $slug;
$homeAddr = fn (string $lang) => $lang === 'en' ? '/' : '/id';
$indexAddr = fn (string $lang) => $lang === 'en' ? '/articles' : '/id/artikel';
$nav = fn (string $url, string $lang) => NavLinks::localize($url, $lang, $loc, 'en', ['articles', 'artikel'], $sibling, $pageAddr, $homeAddr, $indexAddr);
check('"/" dan "/id" -> beranda bahasa pembaca', $nav('/', 'en') === '/' && $nav('/', 'id') === '/id' && $nav('/id', 'en') === '/' && $nav('/id', 'id') === '/id');
check('satu slug -> padanan di bahasa pembaca (slug lain bahasa pun)', $nav('/about-us', 'id') === '/id/tentang-kami' && $nav('/tentang-kami', 'en') === '/about-us' && $nav('/id/tentang-kami', 'en') === '/about-us' && $nav('/about-us', 'en') === '/about-us');
check('belum diterjemahkan: bahasa yang ada; tidak dikenal: apa adanya', $nav('/only-id', 'en') === '/id/hanya-id' && $nav('/tidak-ada', 'en') === '/tidak-ada');
check('slug kepala daftar (bahasa mana pun) -> daftar artikel bahasa pembaca', $nav('/articles', 'id') === '/id/artikel' && $nav('/artikel', 'en') === '/articles');
check('query dan fragmen dipertahankan', $nav('/about-us?a=1#x', 'id') === '/id/tentang-kami?a=1#x' && $nav('/?a=1', 'id') === '/id?a=1' && $nav('/articles#top', 'id') === '/id/artikel#top');
check('apa adanya: http(s), mailto, tel, #anchor, "//host", jalur bersegmen banyak, kosong, slug tidak sah', array_reduce(['https://x.org/a', 'mailto:a@b.c', 'tel:+62', '#top', '//evil.com', '/a/b/c', '', '/Bad Slug', 'about-us'], fn ($c, $u) => $c && $nav($u, 'id') === $u, true));
check('"//host" (protokol-relatif) tidak pernah diubah, juga bila berbentuk slug; "/en/..." (bahasa bawaan tak berawalan) dan jalur bersegmen banyak dengan segmen pertama halaman sungguhan tidak diubah', $nav('//about-us', 'id') === '//about-us' && $nav('/en/about-us', 'id') === '/en/about-us' && $nav('/about-us/extra', 'id') === '/about-us/extra' && $nav('/id/about-us/extra', 'en') === '/id/about-us/extra');
check('beranda tanpa alamat (null) -> apa adanya', NavLinks::localize('/', 'id', $loc, 'en', [], $sibling, $pageAddr, fn () => null, $indexAddr) === '/');
check('kepala daftar tanpa alamat (null) -> dicari sebagai halaman biasa', NavLinks::localize('/artikel', 'id', $loc, 'en', ['artikel'], fn () => null, $pageAddr, $homeAddr, fn () => null) === '/artikel');

echo "\nSitemap::entries per bahasa\n";
$rows = [
    ['kind' => 'page', 'slug' => 'home', 'locale' => 'en', 'lastmod' => '2026-01-01'], ['kind' => 'page', 'slug' => 'beranda', 'locale' => 'id', 'lastmod' => '2026-01-02'],
    ['kind' => 'page', 'slug' => 'about-us', 'locale' => 'en', 'lastmod' => null], ['kind' => 'page', 'slug' => 'tentang-kami', 'locale' => 'id', 'lastmod' => null],
    ['kind' => 'article', 'slug' => 'first', 'locale' => 'en', 'lastmod' => null], ['kind' => 'article', 'slug' => 'pertama', 'locale' => 'id', 'lastmod' => null],
];
$page = ['en' => '/{slug}', 'id' => '/id/{slug}']; $art = ['en' => '/articles/{slug}', 'id' => '/id/artikel/{slug}'];
$homeSlug = ['en' => 'home', 'id' => 'beranda']; $homePaths = ['en' => '/', 'id' => '/id'];
$e = Sitemap::entries($rows, ['/articles', '/id/artikel'], 'https://ysbh.org', $page, $art, $homeSlug, $homePaths);
$locs = array_column($e, 'loc');
check('setiap baris memakai templat bahasanya; beranda di "/" dan "/id", bukan "/home" atau "/id/beranda"', $locs === ['https://ysbh.org/', 'https://ysbh.org/id', 'https://ysbh.org/about-us', 'https://ysbh.org/id/tentang-kami', 'https://ysbh.org/articles/first', 'https://ysbh.org/id/artikel/pertama', 'https://ysbh.org/articles', 'https://ysbh.org/id/artikel'], json_encode($locs));
check('lastmod ikut barisnya', $e[0]['lastmod'] === '2026-01-01' && $e[1]['lastmod'] === '2026-01-02' && $e[2]['lastmod'] === null);
check('alamat dasar rusak = kosong', Sitemap::entries($rows, [], 'ysbh.org', $page, $art, $homeSlug, $homePaths) === []);
check('templat teks (bentuk lama) berlaku untuk semua bahasa', array_column(Sitemap::entries([['kind' => 'page', 'slug' => 'x', 'locale' => 'id', 'lastmod' => null]], [], 'https://ysbh.org', '/{slug}', '/a/{slug}'), 'loc') === ['https://ysbh.org/x']);
check('peta tanpa kunci bahasa baris itu: baris dibuang (tidak dikarang)', Sitemap::entries([['kind' => 'page', 'slug' => 'x', 'locale' => 'fr', 'lastmod' => null]], [], 'https://ysbh.org', $page, $art) === []);
check('jalur beranda per bahasa tidak ada kuncinya: halaman beranda tetap "/{slug}" (landing mengalihkannya)', array_column(Sitemap::entries([['kind' => 'page', 'slug' => 'beranda', 'locale' => 'id', 'lastmod' => null]], [], 'https://ysbh.org', $page, $art, $homeSlug, ['en' => '/']), 'loc') === ['https://ysbh.org/id/beranda']);
check('artikel dengan slug yang sama dengan slug beranda tidak dijadikan beranda', array_column(Sitemap::entries([['kind' => 'article', 'slug' => 'home', 'locale' => 'en', 'lastmod' => null]], [], 'https://ysbh.org', $page, $art, $homeSlug, $homePaths), 'loc') === ['https://ysbh.org/articles/home']);
check('jalur statis tidak sah dibuang; yang sah ditambahkan tanpa lastmod', array_column(Sitemap::entries([], ['/ok', '//evil', 'x', 5], 'https://ysbh.org', $page, $art), 'loc') === ['https://ysbh.org/ok']);

echo "\nLinkResolver::address / homeAddress / indexAddress dan Languages::fromConfig / forRequest (config, aplikasi, rute, dan permintaan TIRUAN)\n";
$GLOBALS['cfg'] = [];
function config($key = null, $default = null) { return array_key_exists($key, $GLOBALS['cfg']) ? $GLOBALS['cfg'][$key] : $default; }
function app($x = null) { static $o; return $o ??= new class { public string $loc = 'en'; public array $routes = []; public function getLocale() { return $this->loc; } public function setLocale($l) { $this->loc = $l; } public function has($n) { return in_array($n, $this->routes, true); } }; }
function route($n, $s) { return "ROUTE[$n:$s]"; }
function request() { return new class { public function path() { return $GLOBALS['path']; } }; }

$GLOBALS['cfg'] = ['app.supported_locales' => ['en', 'id'], 'cms.default_locale' => 'en', 'app.fallback_locale' => 'id', 'cms.home_slug' => ['en' => 'home', 'id' => 'beranda'],
    'cms.public.page' => ['en' => '/{slug}', 'id' => '/id/{slug}'], 'cms.public.article' => ['en' => '/articles/{slug}', 'id' => '/id/artikel/{slug}'],
    'cms.public.home' => ['en' => '/', 'id' => '/id'], 'cms.public.articles' => ['en' => '/articles', 'id' => '/id/artikel'], 'cms.public.base' => ''];
check('halaman: templat bahasa masing-masing', L::address('page', 'about-us', 'en') === '/about-us' && L::address('page', 'tentang-kami', 'id') === '/id/tentang-kami');
check('artikel: templat bahasa masing-masing', L::address('article', 'first', 'en') === '/articles/first' && L::address('article', 'pertama', 'id') === '/id/artikel/pertama');
check('slug beranda bahasa itu -> alamat beranda; slug beranda bahasa LAIN bukan beranda', L::address('page', 'home', 'en') === '/' && L::address('page', 'beranda', 'id') === '/id' && L::address('page', 'home', 'id') === '/id/home' && L::address('page', 'beranda', 'en') === '/beranda');
check('artikel ber-slug "home" tetap artikel (beranda hanya untuk halaman)', L::address('article', 'home', 'en') === '/articles/home');
app()->setLocale('id');
check('bahasa tidak diberikan = bahasa permintaan ini (app()->getLocale())', L::address('page', 'tentang-kami') === '/id/tentang-kami' && L::homeAddress() === '/id' && L::indexAddress() === '/id/artikel');
app()->setLocale('en');
check('homeAddress dan indexAddress per bahasa', L::homeAddress('en') === '/' && L::homeAddress('id') === '/id' && L::indexAddress('en') === '/articles' && L::indexAddress('id') === '/id/artikel');
$GLOBALS['cfg']['cms.public.base'] = 'https://ysbh.org';
check('dengan alamat dasar: absolut untuk halaman, beranda, dan daftar', L::address('page', 'tentang-kami', 'id') === 'https://ysbh.org/id/tentang-kami' && L::homeAddress('id') === 'https://ysbh.org/id' && L::indexAddress('en') === 'https://ysbh.org/articles' && L::address('page', 'home', 'en') === 'https://ysbh.org/');
$GLOBALS['cfg']['cms.public.base'] = 'ysbh.org';
check('alamat dasar rusak: null, bukan alamat yang salah', L::homeAddress('en') === null && L::indexAddress('en') === null && L::address('page', 'about-us', 'en') === null);
$GLOBALS['cfg']['cms.public.base'] = '';
$GLOBALS['cfg']['cms.public.articles'] = ['en' => '/articles'];
check('daftar artikel tidak diatur untuk sebuah bahasa = null', L::indexAddress('id') === null && L::indexAddress('en') === '/articles');
unset($GLOBALS['cfg']['cms.public.home']);
check('jalur beranda tidak diatur: "/" (bahasa bawaan) dan "/kode" (lainnya)', L::homeAddress('en') === '/' && L::homeAddress('id') === '/id');
unset($GLOBALS['cfg']['cms.public.page'], $GLOBALS['cfg']['cms.public.article']);
check('tanpa templat dan tanpa rute: null (tidak melempar galat)', L::address('page', 'x', 'en') === null && L::address('article', 'x', 'id') === null);
app()->routes = ['page.show', 'id.page.show', 'article.show', 'id.article.show'];
check('tanpa templat: rute bernama; bahasa bawaan "page.show", bahasa lain "id.page.show"', L::address('page', 'x', 'en') === 'ROUTE[page.show:x]' && L::address('page', 'x', 'id') === 'ROUTE[id.page.show:x]' && L::address('article', 'x', 'en') === 'ROUTE[article.show:x]' && L::address('article', 'x', 'id') === 'ROUTE[id.article.show:x]');
app()->routes = ['page.show'];
check('rute bahasa lain tidak ada: null (bukan rute bahasa bawaan yang salah)', L::address('page', 'x', 'id') === null && L::address('page', 'x', 'en') === 'ROUTE[page.show:x]');

check('fromConfig: bahasa dari app.supported_locales; bahasa bawaan dari cms.default_locale', Lg::fromConfig() === ['locales' => ['en', 'id'], 'default' => 'en']);
$GLOBALS['cfg']['cms.default_locale'] = 'id';
check('fromConfig: cms.default_locale menang atas app.fallback_locale', Lg::fromConfig()['default'] === 'id');
unset($GLOBALS['cfg']['cms.default_locale']);
check('fromConfig: tanpa cms.default_locale = app.fallback_locale; tidak termasuk daftar = bahasa pertama', Lg::fromConfig()['default'] === 'id' && (function () { $GLOBALS['cfg']['app.fallback_locale'] = 'fr'; return Lg::fromConfig()['default'] === 'en'; })());
$GLOBALS['cfg']['app.supported_locales'] = ['en', 'id', 'pt-br']; $GLOBALS['cfg']['cms.default_locale'] = 'en';
check('fromConfig: bahasa ketiga dikenali', Lg::fromConfig()['locales'] === ['en', 'id', 'pt-br']);
$GLOBALS['cfg']['app.supported_locales'] = ['en', 'id'];
$GLOBALS['path'] = 'id/tentang-kami'; app()->setLocale('en');
check('forRequest: bahasa dari alamat permintaan DAN diterapkan ke aplikasi (setLocale)', Lg::forRequest() === 'id' && app()->getLocale() === 'id');
$GLOBALS['path'] = 'about-us';
check('forRequest: tanpa awalan = bahasa bawaan, dan diterapkan', Lg::forRequest() === 'en' && app()->getLocale() === 'en');
$GLOBALS['path'] = '/';
check('forRequest: beranda bahasa bawaan', Lg::forRequest() === 'en');

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
