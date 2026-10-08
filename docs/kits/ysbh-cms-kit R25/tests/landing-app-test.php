<?php
/**
 * Kontrak statis berkas landing tahap 1 (landing-app/):  php tests/landing-app-test.php [akar-kit]
 * Rute vs templat alamat, urutan rute, perbaikan di header/head/CSS, dan aturan keamanan komponen halaman.
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
$L = "$root/landing-app";
$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
$read = fn (string $p) => (string) file_get_contents("$L/$p");
$noComments = fn (string $s) => (string) preg_replace('/\{\{--.*?--\}\}/s', '', $s);   // komentar Blade tidak dihitung
$template = fn (string $s) => (string) substr($s, (int) strpos($s, '?>') + 2);            // bagian templat komponen (sesudah blok PHP)

echo "\nRute\n";
$web = $read('routes/web.php');
preg_match_all('/^Route::(\w+)\(([^;]*?)\)(?:->[^;]*)?;/m', $web, $m, PREG_SET_ORDER);
$routes = array_map(fn ($r) => $r[0], $m);
check('rute CMS dua bahasa ada: EN /articles, /articles/{slug}; ID /id/artikel, /id/artikel/{slug}, /id/{slug}; EN /{slug} (dengan batasan slug)', str_contains($web, "Route::livewire('/articles', 'articles-index')->name('articles')") && str_contains($web, "Route::livewire('/articles/{slug}', 'article-show')->name('article.show')") && str_contains($web, "Route::livewire('/id/artikel', 'articles-index')->name('id.articles')") && str_contains($web, "Route::livewire('/id/artikel/{slug}', 'article-show')->name('id.article.show')") && str_contains($web, "Route::livewire('/id/{slug}', 'page-show')->name('id.page.show')->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')") && str_contains($web, "->name('page.show')->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')"));
check("'/{slug}' adalah rute PALING AKHIR (rute lain di atasnya menang)", str_contains(end($routes), "'/{slug}'"), end($routes));
check("beranda EN: Route::livewire('/', 'page-show')->name('home') adalah rute PERTAMA; beranda ID '/id' sebelum '/id/{slug}' (halaman CMS, bukan Blade statis)", isset($routes[0]) && str_contains($routes[0], "Route::livewire('/', 'page-show')->name('home')") && ($pi = strpos($web, "Route::livewire('/id', 'page-show')->name('id.home')")) !== false && $pi < strpos($web, "Route::livewire('/id/{slug}'"), $routes[0] ?? '');
check("semua rute '/id…' didaftarkan SEBELUM '/{slug}' (kalau tidak, \"id\" dianggap slug halaman bahasa bawaan), dan tanpa Route::prefix/group (pemeriksa membaca rute satu per satu)", (function () use ($web) { $catch = strpos($web, "Route::livewire('/{slug}'"); preg_match_all("#Route::\w+\('(/id[^']*)'#", $web, $ids, PREG_OFFSET_CAPTURE); foreach ($ids[0] as [$_, $off]) { if ($off > $catch) return false; } return count($ids[0]) === 4 && !preg_match('/Route::(?:prefix|group)\(/', $web); })());
check('landing TIDAK lagi punya halaman statis: tidak ada Route::view, dan tidak ada nama rute lama (about, contact, programs*, credibility, transparancies, impact)', !str_contains($web, 'Route::view(') && !array_filter(['about', 'contact', 'programs', 'programs-malaria', 'programs-imunisasi', 'programs-kia', 'programs-tbc', 'programs-hiv', 'credibility', 'transparancies', 'impact'], fn ($n) => str_contains($web, "->name('$n')")));
check('hanya nama rute ini yang ada: home, id.home, sitemap, robots, articles, id.articles, article.show, id.article.show, page.show, id.page.show', (function () use ($web) { preg_match_all("/->name\('([^']+)'\)/", $web, $mn); $got = $mn[1]; sort($got); $want = ['articles', 'article.show', 'home', 'id.articles', 'id.article.show', 'id.home', 'id.page.show', 'page.show', 'robots', 'sitemap']; sort($want); return $got === $want; })());
check("tidak ada pengalihan '/articles' -> '/artikel' lagi (rilis 24: '/articles' adalah daftar artikel EN; '/artikel' di EN dialihkan 301 ke padanannya oleh page-show lewat PublicLookup::findPage)", !str_contains($web, 'Route::redirect(') && substr_count($web, "->name('articles')") === 1);
$firstCatch = strpos($web, "Route::livewire('/{slug}'");
check('tidak ada rute lain didaftarkan SESUDAH /{slug}', $firstCatch !== false && !preg_match('/Route::\w+\(/', substr($web, $firstCatch + 30)));
if (!function_exists('env')) { function env($k, $d = null) { return $d; } }
$cfg = (static fn () => require __DIR__ . '/../landing-app/config/cms.php')();
$routePath = function (string $name) use ($web): ?string { return preg_match("/Route::livewire\('(\/[^']*)', '[a-z-]+'\)->name\('" . preg_quote($name, '/') . "'\)/", $web, $mr) ? $mr[1] : null; };
check("templat config('cms.public') SAMA dengan jalur rute yang dibaca dari web.php, PER BAHASA: page, article, home, articles", ($cfg['public']['page'] ?? null) === ['en' => $routePath('page.show'), 'id' => $routePath('id.page.show')] && ($cfg['public']['article'] ?? null) === ['en' => $routePath('article.show'), 'id' => $routePath('id.article.show')] && ($cfg['public']['home'] ?? null) === ['en' => $routePath('home'), 'id' => $routePath('id.home')] && ($cfg['public']['articles'] ?? null) === ['en' => $routePath('articles'), 'id' => $routePath('id.articles')], json_encode([$cfg['public'] ?? null]));
check("landing tidak mengisi 'base' (jalur relatif); sampul memakai templat {file} dari folder yang dipakai editor artikel (storage/articles)", ($cfg['public']['base'] ?? 'x') === '' && ($cfg['public']['cover'] ?? '') === '/storage/articles/{file}');
$cmsFile = '/mnt/user-data/uploads/cms.php';
$cms = is_file($cmsFile) ? (static fn () => require '/mnt/user-data/uploads/cms.php')() : null;   // berkas milik Anda: hanya dibandingkan bila ada di mesin ini
check('design, lucide, dan fonts di config landing = config CMS (berkas Anda disalin apa adanya; hanya public, default_locale, reserved_slugs, home_slug, articles_index_slug, dan sitemap_static ditambahkan)', $cms === null || (($cms['design'] ?? 1) === $cfg['design'] && ($cms['lucide'] ?? 1) === $cfg['lucide'] && ($cms['fonts'] ?? 1) === $cfg['fonts'] && (static function () use ($cfg, $cms) { $extra = array_values(array_diff(array_keys($cfg), array_keys($cms))); sort($extra); return $extra === ['articles_index_slug', 'default_locale', 'home_slug', 'public', 'reserved_slugs', 'sitemap_static'] && !array_diff(array_keys($cms), array_keys($cfg)); })()));
check('lucide tidak kosong (ikon blok tampil)', count($cfg['lucide'] ?? []) > 0);

echo "\nSlug terlarang dan kepala daftar artikel\n";
require_once "$root/app/Content/Languages.php";
require_once "$root/app/Content/Slug.php";
$stripped = (string) preg_replace('#^\s*(?://|\#).*$#m', '', $web);
preg_match_all("#Route::(?:view|get|post|any|redirect|livewire|match)\(\s*['\"]/(?:(id)/)?([^'\"/{}?]+)['\"]#", $stripped, $mm, PREG_SET_ORDER);
$default = $cfg['default_locale'] ?? 'en';
$localesAll = ['en', 'id'];
$indexOf = fn (string $loc) => \App\Content\Languages::indexSlug($cfg['articles_index_slug'] ?? null, $loc);
$guardOf = fn (string $loc) => \App\Content\Slug::reserved($cfg['reserved_slugs'] ?? [], $indexOf($loc), $loc, $localesAll, $default);
$single = [];
foreach ($mm as $one) { $loc = $one[1] !== '' ? 'id' : 'en'; if (\App\Content\Slug::isValid($one[2])) { $single[] = [$loc, $one[2]]; } }   // segmen bertitik (sitemap.xml) bukan slug: tak bisa bertabrakan
$gap = array_values(array_filter($single, fn ($x) => $x[1] !== $indexOf($x[0]) && !in_array($x[1], $guardOf($x[0]), true)));
check('SETIAP rute statis satu-segmen di web.php (per bahasa: "/id" di en, "/id/artikel" di id, ...) tercatat sebagai slug terlarang di bahasanya (atau slug kepala): tidak ada halaman CMS yang bisa tersimpan tetapi mustahil dibuka', $gap === [] && $single !== [], 'tak terjaga: ' . json_encode($gap) . ' dari ' . json_encode($single));
check("kode bahasa lain ('id') otomatis terlarang di bahasa bawaan (rute '/id' adalah beranda ID) tetapi BOLEH di bahasa itu sendiri; 'articles' bukan lagi terlarang (kepala daftar EN)", \App\Content\Slug::isReserved('id', $cfg['reserved_slugs'], $indexOf('en'), 'en', $localesAll, $default) && !\App\Content\Slug::isReserved('id', $cfg['reserved_slugs'], $indexOf('id'), 'id', $localesAll, $default) && !\App\Content\Slug::isReserved('articles', $cfg['reserved_slugs'], $indexOf('en'), 'en', $localesAll, $default));
check("'articles_index_slug' per bahasa sama dengan jalur rute articles-index di web.php (EN '/articles', ID '/id/artikel')", ($cfg['articles_index_slug'] ?? null) === ['en' => ltrim((string) $routePath('articles'), '/'), 'id' => preg_replace('#^id/#', '', ltrim((string) $routePath('id.articles'), '/'))], json_encode([$cfg['articles_index_slug'] ?? null, $routePath('articles'), $routePath('id.articles')]));
$sample = (static fn () => require __DIR__ . '/../contoh-kode/config-slug-terlarang.php')();
check('config landing memuat reserved_slugs (peta bahasa), home_slug, articles_index_slug, default_locale dan public (page, article, home, articles); isi sama dengan contoh untuk CMS', is_array($cfg['reserved_slugs']['en'] ?? null) && is_array($cfg['reserved_slugs']['id'] ?? null) && $default === 'en' && $sample['default_locale'] === $cfg['default_locale'] && $sample['reserved_slugs'] === $cfg['reserved_slugs'] && $sample['home_slug'] === $cfg['home_slug'] && $sample['articles_index_slug'] === $cfg['articles_index_slug'] && $sample['public'] === array_intersect_key($cfg['public'], array_flip(['page', 'article', 'home', 'articles'])), json_encode($sample));
check("beranda: config('cms.home_slug') per bahasa adalah slug sah ('home' en, 'beranda' id); reserved_slugs KOSONG per bahasa karena landing tidak punya rute satu-segmen sendiri", ($cfg['home_slug'] ?? null) === ['en' => 'home', 'id' => 'beranda'] && \App\Content\Slug::isValid('home') && \App\Content\Slug::isValid('beranda') && $cfg['reserved_slugs'] === ['en' => [], 'id' => []]);
$ai = $read('resources/views/components/⚡articles-index.blade.php');
check('daftar artikel: kepala dari halaman CMS (articlesHeader + config articles_index_slug BAHASA itu) dan judul bawaan bila tidak ada', str_contains($ai, "PublicLookup::articlesHeader(Languages::indexSlug(config('cms.articles_index_slug'), \$this->lang)") && str_contains($ai, "'Artikel'") && str_contains($ai, "'Articles'") && str_contains($ai, 'articlesPage($this->page, self::PER_PAGE, $this->lang)'));
check('pengantar HANYA di halaman 1; penutup sesudah daftar (urutan di templat: pengantar, daftar, penutup)', str_contains($ai, '@if ($page === 1 && ($this->header[\'intro\'][\'order\']') && ($p1 = strpos($ai, "header['intro']['blocks']")) < ($p2 = strpos($ai, 'x-blocks.render.latest-articles-builder')) && $p2 < strpos($ai, "header['closing']['blocks']"));
check('canonical PER HALAMAN (halaman 2+ menunjuk dirinya, bukan halaman 1) dan judul halaman 2+ diberi nomor', str_contains($ai, "view()->share('canonical', \$this->page > 1 ? url()->current() . '?page=' . \$this->page") && str_contains($ai, '(halaman ') && str_contains($ai, '(page '));
check('daftar artikel: tetap 404 untuk halaman di luar jangkauan dan tidak menambah {!! !!}', str_contains($ai, 'abort_if($this->page > 1 && $this->feed[\'rows\'] === [], 404)') && !str_contains($template($ai), '{!!'));

echo "\nPeta situs\n";
preg_match_all("#Route::(?:view|get|livewire)\(\s*['\"](/[^'\"{}?]*)['\"]#", $stripped, $mp);
$staticPaths = array_values(array_unique(array_filter($mp[1], fn ($pth) => $pth !== '/sitemap.xml' && !in_array($pth, ['/', '/id'], true) && !str_contains($pth, '.'))));   // '/' dan '/id' = beranda dari CMS (page-show), masuk dari basis data
$listed = $cfg['sitemap_static'] ?? [];
sort($staticPaths); $sortedListed = $listed; sort($sortedListed);
check("config('cms.sitemap_static') = TEPAT jalur GET statis di web.php (tidak ada yang terlewat, tidak ada alamat mati)", $staticPaths === $sortedListed && $staticPaths === ['/articles', '/id/artikel'], json_encode([array_values(array_diff($staticPaths, $listed)), array_values(array_diff($listed, $staticPaths))]));
check("rute /sitemap.xml didaftarkan SEBELUM '/{slug}' dan memakai SitemapController", ($ps = strpos($web, "'/sitemap.xml'")) !== false && $ps < strpos($web, "Route::livewire('/{slug}'") && str_contains($web, 'SitemapController::class'));
$sc = $read('app/Http/Controllers/SitemapController.php');
check('pengendali: kegagalan baca dan hasil KOSONG dibalas 503 (bukan peta kosong 200); Content-Type XML; cache publik singkat; tanpa {!! !!}', substr_count($sc, 'abort(503') === 2 && str_contains($sc, 'application/xml; charset=UTF-8') && str_contains($sc, 'public, max-age=600') && str_contains($sc, 'catch (\Throwable') && !str_contains($sc, '{!!'));
check('pengendali memakai Sitemap::entries/xml dan PublicLookup::sitemapEntries (logika diuji ada di kit)', str_contains($sc, 'Sitemap::entries(') && str_contains($sc, 'Sitemap::xml(') && str_contains($sc, 'PublicLookup::sitemapEntries('));
check("pengendali meneruskan config('cms.home_slug') dan config('cms.public.home') ke Sitemap::entries (beranda masuk sebagai jalur beranda bahasanya, bukan \"/home\" yang hanya mengalihkan)", preg_match("#Sitemap::entries\(\\\$rows,[^;]*config\('cms\.home_slug'\), config\('cms\.public\.home'\)\);#", $sc) === 1);
check('public/robots.txt TIDAK ada di landing-app (berkas statis menutupi rute dinamis /robots.txt); pemasang menghapusnya dari proyek', !is_file("$L/public/robots.txt"));
check("rute /robots.txt didaftarkan SEBELUM '/{slug}' dan memakai RobotsController", ($pr = strpos($web, "'/robots.txt'")) !== false && $pr < strpos($web, "Route::livewire('/{slug}'") && str_contains($web, 'RobotsController::class'));
require_once "$L/app/Http/Controllers/RobotsController.php";
$rc = \App\Http\Controllers\RobotsController::class;
check('robots.txt: peta situs mengikuti APP_URL (skema://host[:port] saja); tidak memblokir situs', $rc::body('https://ysbh.org', 'http://x.test') === "User-agent: *\nDisallow:\n\nSitemap: https://ysbh.org/sitemap.xml\n" && !preg_match('#^Disallow:\s*/\s*$#mi', $rc::body('https://a.b', 'http://c.d')));
check('robots.txt: APP_URL kosong atau rusak -> alamat permintaan; keduanya rusak -> tanpa baris Sitemap (tidak pernah alamat rusak)', $rc::body('', 'http://landing-ysbh.test') === "User-agent: *\nDisallow:\n\nSitemap: http://landing-ysbh.test/sitemap.xml\n" && $rc::body('https://a.b/jalur', 'http://c.d') === "User-agent: *\nDisallow:\n\nSitemap: http://c.d/sitemap.xml\n" && $rc::body('javascript:alert(1)', 'bukan-alamat') === "User-agent: *\nDisallow:\n");
check('robots.txt: tidak ada nama domain yang tertulis di kode pengendali (tidak bisa salah domain)', !preg_match('#https?://[a-z0-9.-]+\.(?:org|com|id|test)#i', (string) preg_replace('#^\s*(?://|\*|/\*).*$#m', '', $read('app/Http/Controllers/RobotsController.php'))));
$extra = array_values(array_diff(array_keys($cfg), array_keys($cms ?? [])));

echo "\nDaftar isi (dari komponen CMS Anda, dengan perbaikan)\n";
$toc = $read('resources/views/components/table-of-contents.blade.php');
check('komponen ada di landing-app (pengganti stand-in sementara); folder _sementara sudah tidak ada', $toc !== '' && !is_dir("$L/_sementara"));
check('anchor ke ekspresi Alpine lewat @js (dua tempat: @click dan :class), tidak ada lagi \'{{ $item[\'anchor\'] }}\'', substr_count($toc, '@js($item[\'anchor\'])') === 2 && !preg_match('/[\'"`]\s*\{\{\s*\$item\[\'anchor\'\]/', $toc));
check('teks sisa "[cite: n]" tidak ada (sebelumnya tampil setelah setiap judul)', !preg_match('/\[cite:\s*\d+\]/', $toc));
check('desain dan perilaku Anda utuh: sticky, border-foresty, checkScroll, scrollTo halus, @scroll.window.passive, judul item lewat {{ }} (ter-escape)', str_contains($toc, 'sticky top-24') && str_contains($toc, 'border-foresty') && str_contains($toc, 'checkScroll()') && str_contains($toc, "behavior: 'smooth'") && str_contains($toc, '@scroll.window.passive="checkScroll"') && str_contains($toc, "{{ \$item['title'] }}") && !str_contains($toc, '{!!'));
check('judul kartu mengikuti bahasa halaman (bukan "Daftar Isi" tetap di situs berbahasa Inggris)', str_contains($toc, "'label' => null") && str_contains($toc, "app()->getLocale() === 'id' ? 'Daftar Isi' : 'Contents'") && str_contains($toc, '{{ $label }}'));

echo "\nHeader\n";
$h = $noComments($read('resources/views/components/layouts/header.blade.php'));
check('tidak ada lagi route($link->route_name) (galat bila rute hilang atau butuh parameter)', substr_count($h, 'route($link->route_name)') === 0);
check('semua tautan menu (desktop, lengket, mobile) memakai $link->href dan $link->isCurrent()', substr_count($h, '$link->href') === 3 && substr_count($h, '$link->isCurrent()') === 3);
check('<header> tidak lagi bersarang (satu pasang)', substr_count($h, '<header') === 1 && substr_count($h, '</header>') === 1);
$hRaw = $read('resources/views/components/layouts/header.blade.php');
check('markup lain di header TIDAK berubah: logo, menu mobile, dan sticky tetap ada; tombol bahasa Alpine (tidak berfungsi) diganti <x-layouts.lang-switch> (tautan ke versi bahasa lain) di desktop dan menu mobile', str_contains($hRaw, 'DESKTOP STICKY NAV') && str_contains($h, 'mobileMenuOpen') && substr_count($h, 'wire:navigate') === 3 && substr_count($h, '<x-layouts.lang-switch') === 2 && !str_contains($h, "x-data=\"{ lang: 'ID' }\"") && !str_contains($h, 'console.log'));
$ls = $read('resources/views/components/layouts/lang-switch.blade.php');
check('lang-switch: tautan BIASA (<a href>, tanpa wire:navigate agar <html lang> ikut berganti), tujuan dari $alternates atau beranda bahasa itu, bahasa yang sedang dibuka dilewati, ikon globe, tanpa {!! !!}', str_contains($ls, '<a href=') && !str_contains($ls, 'wire:navigate') && str_contains($ls, '$alts[$code] ??') && str_contains($ls, 'LinkResolver::homeAddress($code)') && str_contains($ls, '@continue($code === $currentLocale)') && str_contains($ls, 'lucide-globe') && !str_contains($ls, '{!!'));

echo "\nHead dan CSS\n";
$hd = $read('resources/views/partials/head.blade.php');
check('warna latar dari basis data divalidasi #RRGGBB sebelum masuk CSS; nilai mentah tidak dicetak langsung', str_contains($hd, "preg_match('/^#[0-9a-fA-F]{6}$/D'") && substr_count($hd, '{{ $landingBg }}') === 1 && strpos($hd, 'preg_match') < strpos($hd, '{{ $landingBg }}'));
check('judul/deskripsi/canonical/Open Graph dari $title dan $description; aturan judul lama (beranda en dan id) dipertahankan', str_contains($hd, '$title') && str_contains($hd, '$description') && str_contains($hd, 'rel="canonical"') && str_contains($hd, 'og:title') && str_contains($hd, "routeIs('home', 'id.home')") && str_contains($hd, "'Sinar Bhakti Husada'"));
check('hreflang: dari $alternates, hanya bila ada dua bahasa atau lebih, plus x-default = bahasa bawaan; alamat dijadikan absolut (url())', str_contains($hd, 'rel="alternate" hreflang="{{ $code }}"') && str_contains($hd, 'hreflang="x-default"') && str_contains($hd, 'count($alts) > 1') && str_contains($hd, "url(\$u)") && str_contains($hd, "Languages::fromConfig()['default']"));
check('judul beranda ditentukan editor (tanpa akhiran nama situs); halaman lain "judul | nama situs"', str_contains($hd, "request()->routeIs('home', 'id.home') ? \$title : \$title . ' | ' . \$siteName"));
check('@vite, @livewireStyles, dan @fonts tetap dimuat', str_contains($hd, '@vite') && str_contains($hd, '@livewireStyles') && str_contains($hd, '@fonts'));
check('deskripsi dibersihkan (tanpa tag) dan dibatasi 160 karakter', str_contains($hd, 'strip_tags') && str_contains($hd, 'limit('));
$css = $read('resources/css/app.css');
check('app.css: warna charcoal didefinisikan (dipakai 27 kelas kit), kit di app/ dan config/ dipindai, dan [x-cloak] disembunyikan', str_contains($css, '--color-charcoal: #1f2937') && str_contains($css, "@source '../../app';") && str_contains($css, "@source '../../config';") && preg_match('/\[x-cloak\]\s*\{\s*display:\s*none\s*!important/', $css) === 1);
check('app.css: semua @font-face, tema warna Yayasan, dan animasi lama tetap ada', substr_count($css, '@font-face') === 10 && str_contains($css, '--color-foresty:#064F3B') && str_contains($css, '.animate-spin-slower'));

echo "\nKomponen halaman\n";
$sfc = ['page-show', 'article-show', 'articles-index'];
check('ketiganya memakai layout landing (components.layouts.app) dan PublicLookup', !array_filter($sfc, fn ($n) => !str_contains($read("resources/views/components/⚡$n.blade.php"), "#[Layout('components.layouts.app')]") || !str_contains($read("resources/views/components/⚡$n.blade.php"), 'PublicLookup')));
check('TIDAK ADA {!! ... !!} di templat komponen halaman dan 404 (semua keluaran di-escape; HTML lewat <x-content.sections>)', !array_filter(array_merge(array_map(fn ($n) => $template($read("resources/views/components/⚡$n.blade.php")), $sfc), [$read('resources/views/errors/404.blade.php')]), fn ($t) => str_contains($t, '{!!')));
check('page-show dan article-show: 404 bila tidak ditemukan; berbagi $title/$description/$alternates (dibaca head dan sakelar bahasa); bahasa dari ALAMAT (Languages::forRequest), dipulihkan di permintaan Livewire (hydrate)', !array_filter(['page-show', 'article-show'], fn ($n) => !str_contains($read("resources/views/components/⚡$n.blade.php"), 'abort_if($found === null, 404)') || !str_contains($read("resources/views/components/⚡$n.blade.php"), "view()->share('title'") || !str_contains($read("resources/views/components/⚡$n.blade.php"), "view()->share('alternates'") || !str_contains($read("resources/views/components/⚡$n.blade.php"), '$this->lang = Languages::forRequest()') || !str_contains($read("resources/views/components/⚡$n.blade.php"), 'app()->setLocale($this->lang)')));
check('slug bahasa lain -> 301 ke padanannya di bahasa ALAMAT itu (bukan ke bahasa lain), atau 404 bila belum diterjemahkan: page-show dan article-show', !array_filter(['page-show', 'article-show'], fn ($n) => !str_contains($read("resources/views/components/⚡$n.blade.php"), "\$found['redirect'] !== null") || !str_contains($read("resources/views/components/⚡$n.blade.php"), 'redirect($to, 301)') || !str_contains($read("resources/views/components/⚡$n.blade.php"), '$this->lang);')));
check('article-show membagikan currentArticleId (blok Artikel Terbaru tidak menampilkan artikel yang sedang dibuka)', str_contains($read('resources/views/components/⚡article-show.blade.php'), "view()->share('currentArticleId'"));
check('articles-index: 404 bila halaman di luar jangkauan; kartu memakai renderer blok dengan data dari articlesPage', str_contains($read('resources/views/components/⚡articles-index.blade.php'), 'abort_if($this->page > 1') && str_contains($read('resources/views/components/⚡articles-index.blade.php'), 'x-blocks.render.latest-articles-builder') && str_contains($read('resources/views/components/⚡articles-index.blade.php'), 'articlesPage'));
$unlocked = [];
foreach ($sfc as $n) {
    $lines = explode("\n", $read("resources/views/components/⚡$n.blade.php"));
    foreach ($lines as $i => $line) {
        if (preg_match('/^\s*public (?:int|string|array) \$\w+/', $line) && !preg_match('/#\[Locked\]/', $lines[$i - 1] ?? '') && !preg_match('/#\[Locked\]/', $lines[$i - 2] ?? '')) {
            $unlocked[] = "$n: " . trim($line);
        }
    }
}
check('SEMUA properti publik Livewire bertanda #[Locked] (tidak bisa diubah dari peramban)', $unlocked === [], implode(' | ', $unlocked));

echo "\nBeranda dan halaman sementara\n";
$ps = $read('resources/views/components/⚡page-show.blade.php');
check("page-show: \$slug opsional (rute \"/\" dan \"/id\" tanpa parameter = beranda bahasa itu); slug beranda dari config('cms.home_slug') bahasa itu", str_contains($ps, 'mount(?string $slug = null)') && str_contains($ps, "Languages::homeSlug(config('cms.home_slug'), \$this->lang)") && str_contains($ps, '$isHome = $slug === null'));
check('"/{home_slug}" dialihkan 301 ke jalur beranda bahasa itu ("/home" -> "/", "/id/beranda" -> "/id") SEBELUM membaca basis data (satu halaman, satu alamat)', ($pa = strpos($ps, "redirect(LinkResolver::homeAddress(\$this->lang) ?? '/', 301)")) !== false && $pa < strpos($ps, 'PublicLookup::findPage('));
check('beranda belum ada atau offline = 503 (+ Retry-After), bukan 404; halaman lain tetap 404', str_contains($ps, "abort(503, 'Situs sedang disiapkan.', ['Retry-After' => '3600'])") && str_contains($ps, 'abort_if($found === null, 404)') && strpos($ps, 'abort(503') < strpos($ps, 'abort_if($found === null, 404)'));
$e404 = $read('resources/views/errors/404.blade.php'); $e503 = $read('resources/views/errors/503.blade.php');
check("404 dan 503: dua bahasa mengikuti bahasa ALAMAT (Languages::forRequest, karena galat dilempar sebelum bahasa ditetapkan); 503 memakai layout landing; tanpa {!! !!}; tombol 404 ke beranda bahasa itu", str_contains($e404, "Languages::forRequest() === 'id'") && str_contains($e404, 'Page not found') && str_contains($e503, "Languages::forRequest() === 'id'") && str_contains($e503, 'Our site is being prepared') && str_contains($e503, '<x-layouts.app>') && !str_contains($e404 . $e503, '{!!') && str_contains($e404, 'LinkResolver::homeAddress()'));

echo "\nModel\n";
foreach (['Page', 'Post', 'Category', 'Snippet', 'Media'] as $c) {
    check("$c: turunan ReadOnlyModel (landing tidak menulis)", str_contains($read("app/Models/$c.php"), 'extends ReadOnlyModel'));
}
check('Media memakai SoftDeletes (berkas yang dihapus di CMS tidak tampil)', str_contains($read('app/Models/Media.php'), 'use SoftDeletes;'));
check('Navigation: href dan isCurrent tidak melempar galat (try/catch) dan memakai LinkResolver untuk url', str_contains($read('app/Models/Navigation.php'), 'LinkResolver::make()') && substr_count($read('app/Models/Navigation.php'), 'catch (\Throwable') >= 2);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
