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
check('rute CMS ada: /artikel, /artikel/{slug}, /{slug} (dengan batasan slug)', str_contains($web, "Route::livewire('/artikel', 'articles-index')->name('articles')") && str_contains($web, "Route::livewire('/artikel/{slug}', 'article-show')->name('article.show')") && str_contains($web, "->name('page.show')->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')"));
check("'/{slug}' adalah rute PALING AKHIR (rute statis di atasnya menang)", str_contains(end($routes), "'/{slug}'"), end($routes));
$staticNames = ['home', 'about', 'contact', 'programs', 'programs-malaria', 'programs-imunisasi', 'programs-kia', 'programs-tbc', 'programs-hiv', 'credibility', 'transparancies', 'impact'];
check('semua rute statis lama tetap ada dengan namanya (navigasi di basis data tidak putus)', !array_filter($staticNames, fn ($n) => !str_contains($web, "->name('$n')")), implode(',', array_filter($staticNames, fn ($n) => !str_contains($web, "->name('$n')"))));
check("rute lama '/articles' (placeholder ke halaman kontak) diganti pengalihan 301 ke /artikel; nama 'articles' dimiliki indeks baru", str_contains($web, "Route::redirect('/articles', '/artikel', 301)") && substr_count($web, "->name('articles')") === 1);
$firstCatch = strpos($web, "Route::livewire('/{slug}'");
check('tidak ada rute lain didaftarkan SESUDAH /{slug}', $firstCatch !== false && !preg_match('/Route::\w+\(/', substr($web, $firstCatch + 30)));
if (!function_exists('env')) { function env($k, $d = null) { return $d; } }
$cfg = (static fn () => require __DIR__ . '/../landing-app/config/cms.php')();
preg_match("/Route::livewire\('([^']+)', 'page-show'\)/", $web, $mp); preg_match("/Route::livewire\('([^']+)', 'article-show'\)/", $web, $ma);
check("templat config('cms.public') SAMA dengan jalur rute yang dibaca dari web.php: page.show dan article.show", ($cfg['public']['page'] ?? null) === ($mp[1] ?? 'x') && ($cfg['public']['article'] ?? null) === ($ma[1] ?? 'y'), json_encode([$cfg['public'] ?? null, $mp[1] ?? null, $ma[1] ?? null]));
check("landing tidak mengisi 'base' (jalur relatif); sampul memakai templat {file} dari folder yang dipakai editor artikel (storage/articles)", ($cfg['public']['base'] ?? 'x') === '' && ($cfg['public']['cover'] ?? '') === '/storage/articles/{file}');
$cms = (static fn () => require '/mnt/user-data/uploads/cms.php')();
check('design, lucide, dan fonts di config landing = config CMS (berkas Anda disalin apa adanya; hanya public, reserved_slugs, articles_index_slug, dan sitemap_static ditambahkan)', !is_file('/mnt/user-data/uploads/cms.php') || (($cms['design'] ?? 1) === $cfg['design'] && ($cms['lucide'] ?? 1) === $cfg['lucide'] && ($cms['fonts'] ?? 1) === $cfg['fonts'] && (static function () use ($cfg, $cms) { $extra = array_values(array_diff(array_keys($cfg), array_keys($cms))); sort($extra); return $extra === ['articles_index_slug', 'public', 'reserved_slugs', 'sitemap_static'] && !array_diff(array_keys($cms), array_keys($cfg)); })()));
check('lucide tidak kosong (ikon blok tampil)', count($cfg['lucide'] ?? []) > 0);

echo "\nSlug terlarang dan kepala daftar artikel\n";
require_once "$root/app/Content/Slug.php";
$stripped = (string) preg_replace('#^\s*(?://|\#).*$#m', '', $web);
preg_match_all("#Route::(?:view|get|post|any|redirect|livewire|match)\(\s*['\"]/([^'\"/{}?]+)['\"]#", $stripped, $mm);
$single = array_values(array_filter(array_unique($mm[1]), fn ($seg) => \App\Content\Slug::isValid($seg)));   // segmen bertitik (sitemap.xml) bukan slug: tak bisa bertabrakan
$index = $cfg['articles_index_slug'] ?? null;
$guard = \App\Content\Slug::reserved($cfg['reserved_slugs'] ?? [], $index);
$gap = array_values(array_filter($single, fn ($seg) => $seg !== $index && !in_array($seg, $guard, true)));
check('SETIAP rute statis satu-segmen di web.php tercatat sebagai slug terlarang (atau slug kepala): tidak ada halaman CMS yang bisa tersimpan tetapi mustahil dibuka', $gap === [] && count($single) >= 7, 'tak terjaga: ' . json_encode($gap) . ' dari ' . json_encode($single));
check("'articles_index_slug' sama dengan jalur rute articles-index di web.php", preg_match("#Route::livewire\('/([^'/{}?]+)', 'articles-index'\)#", $web, $mi) === 1 && $mi[1] === $index, json_encode([$index, $mi[1] ?? null]));
check('config landing memuat reserved_slugs (daftar teks) dan articles_index_slug (teks); isi sama dengan contoh untuk CMS', is_array($cfg['reserved_slugs'] ?? null) && is_string($index) && $index !== '' && (static fn () => require __DIR__ . '/../contoh-kode/config-slug-terlarang.php')() === ['reserved_slugs' => $cfg['reserved_slugs'], 'articles_index_slug' => $index]);
$ai = $read('resources/views/components/⚡articles-index.blade.php');
check('daftar artikel: kepala dari halaman CMS (articlesHeader + config articles_index_slug) dan judul bawaan bila tidak ada', str_contains($ai, 'PublicLookup::articlesHeader((string) config(\'cms.articles_index_slug\'') && str_contains($ai, "'Artikel'") && str_contains($ai, "'Articles'"));
check('pengantar HANYA di halaman 1; penutup sesudah daftar (urutan di templat: pengantar, daftar, penutup)', str_contains($ai, '@if ($page === 1 && ($this->header[\'intro\'][\'order\']') && ($p1 = strpos($ai, "header['intro']['blocks']")) < ($p2 = strpos($ai, 'x-blocks.render.latest-articles-builder')) && $p2 < strpos($ai, "header['closing']['blocks']"));
check('canonical PER HALAMAN (halaman 2+ menunjuk dirinya, bukan halaman 1) dan judul halaman 2+ diberi nomor', str_contains($ai, "view()->share('canonical', \$this->page > 1 ? url()->current() . '?page=' . \$this->page") && str_contains($ai, '(halaman ') && str_contains($ai, '(page '));
check('daftar artikel: tetap 404 untuk halaman di luar jangkauan dan tidak menambah {!! !!}', str_contains($ai, 'abort_if($this->page > 1 && $this->feed[\'rows\'] === [], 404)') && !str_contains($template($ai), '{!!'));

echo "\nPeta situs\n";
preg_match_all("#Route::(?:view|get|livewire)\(\s*['\"](/[^'\"{}?]*)['\"]#", $stripped, $mp);
$staticPaths = array_values(array_unique(array_filter($mp[1], fn ($pth) => $pth !== '/sitemap.xml' && !str_contains($pth, '.'))));
$listed = $cfg['sitemap_static'] ?? [];
sort($staticPaths); $sortedListed = $listed; sort($sortedListed);
check("config('cms.sitemap_static') = TEPAT jalur GET statis di web.php (tidak ada yang terlewat, tidak ada alamat mati)", $staticPaths === $sortedListed && count($staticPaths) >= 9, json_encode([array_values(array_diff($staticPaths, $listed)), array_values(array_diff($listed, $staticPaths))]));
check("rute /sitemap.xml didaftarkan SEBELUM '/{slug}' dan memakai SitemapController", ($ps = strpos($web, "'/sitemap.xml'")) !== false && $ps < strpos($web, "Route::livewire('/{slug}'") && str_contains($web, 'SitemapController::class'));
$sc = $read('app/Http/Controllers/SitemapController.php');
check('pengendali: kegagalan baca dan hasil KOSONG dibalas 503 (bukan peta kosong 200); Content-Type XML; cache publik singkat; tanpa {!! !!}', substr_count($sc, 'abort(503') === 2 && str_contains($sc, 'application/xml; charset=UTF-8') && str_contains($sc, 'public, max-age=600') && str_contains($sc, 'catch (\Throwable') && !str_contains($sc, '{!!'));
check('pengendali memakai Sitemap::entries/xml dan PublicLookup::sitemapEntries (logika diuji ada di kit)', str_contains($sc, 'Sitemap::entries(') && str_contains($sc, 'Sitemap::xml(') && str_contains($sc, 'PublicLookup::sitemapEntries('));
$rb = $read('public/robots.txt');
check('robots.txt menunjuk /sitemap.xml dan tidak memblokir situs ("Disallow: /")', preg_match('#^Sitemap:\s*https?://\S+/sitemap\.xml\s*$#mi', $rb) === 1 && !preg_match('#^Disallow:\s*/\s*$#mi', $rb));
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
check('markup lain di header TIDAK berubah: logo, tombol bahasa, menu mobile, dan sticky tetap ada', str_contains($hRaw, 'DESKTOP STICKY NAV') && str_contains($h, 'mobileMenuOpen') && str_contains($h, "lucide-globe") && substr_count($h, 'wire:navigate') === 3);

echo "\nHead dan CSS\n";
$hd = $read('resources/views/partials/head.blade.php');
check('warna latar dari basis data divalidasi #RRGGBB sebelum masuk CSS; nilai mentah tidak dicetak langsung', str_contains($hd, "preg_match('/^#[0-9a-fA-F]{6}$/D'") && substr_count($hd, '{{ $landingBg }}') === 1 && strpos($hd, 'preg_match') < strpos($hd, '{{ $landingBg }}'));
check('judul/deskripsi/canonical/Open Graph dari $title dan $description; aturan judul lama (beranda) dipertahankan', str_contains($hd, '$title') && str_contains($hd, '$description') && str_contains($hd, 'rel="canonical"') && str_contains($hd, 'og:title') && str_contains($hd, "routeIs('home')") && str_contains($hd, "'Sinar Bhakti Husada'"));
check('@vite, @livewireStyles, dan @fonts tetap dimuat', str_contains($hd, '@vite') && str_contains($hd, '@livewireStyles') && str_contains($hd, '@fonts'));
check('deskripsi dibersihkan (tanpa tag) dan dibatasi 160 karakter', str_contains($hd, 'strip_tags') && str_contains($hd, 'limit('));
$css = $read('resources/css/app.css');
check('app.css: warna charcoal didefinisikan (dipakai 27 kelas kit), kit di app/ dan config/ dipindai, dan [x-cloak] disembunyikan', str_contains($css, '--color-charcoal: #1f2937') && str_contains($css, "@source '../../app';") && str_contains($css, "@source '../../config';") && preg_match('/\[x-cloak\]\s*\{\s*display:\s*none\s*!important/', $css) === 1);
check('app.css: semua @font-face, tema warna Yayasan, dan animasi lama tetap ada', substr_count($css, '@font-face') === 10 && str_contains($css, '--color-foresty:#064F3B') && str_contains($css, '.animate-spin-slower'));

echo "\nKomponen halaman\n";
$sfc = ['page-show', 'article-show', 'articles-index'];
check('ketiganya memakai layout landing (components.layouts.app) dan PublicLookup', !array_filter($sfc, fn ($n) => !str_contains($read("resources/views/components/⚡$n.blade.php"), "#[Layout('components.layouts.app')]") || !str_contains($read("resources/views/components/⚡$n.blade.php"), 'PublicLookup')));
check('TIDAK ADA {!! ... !!} di templat komponen halaman dan 404 (semua keluaran di-escape; HTML lewat <x-content.sections>)', !array_filter(array_merge(array_map(fn ($n) => $template($read("resources/views/components/⚡$n.blade.php")), $sfc), [$read('resources/views/errors/404.blade.php')]), fn ($t) => str_contains($t, '{!!')));
check('page-show dan article-show: 404 bila tidak ditemukan; berbagi $title/$description (dibaca head); bahasa diset dari slug yang cocok', !array_filter(['page-show', 'article-show'], fn ($n) => !str_contains($read("resources/views/components/⚡$n.blade.php"), 'abort_if($found === null, 404)') || !str_contains($read("resources/views/components/⚡$n.blade.php"), "view()->share('title'") || !str_contains($read("resources/views/components/⚡$n.blade.php"), 'app()->setLocale($this->lang)')));
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

echo "\nModel\n";
foreach (['Page', 'Post', 'Category', 'Snippet', 'Media'] as $c) {
    check("$c: turunan ReadOnlyModel (landing tidak menulis)", str_contains($read("app/Models/$c.php"), 'extends ReadOnlyModel'));
}
check('Media memakai SoftDeletes (berkas yang dihapus di CMS tidak tampil)', str_contains($read('app/Models/Media.php'), 'use SoftDeletes;'));
check('Navigation: href dan isCurrent tidak melempar galat (try/catch) dan memakai LinkResolver untuk url', str_contains($read('app/Models/Navigation.php'), 'LinkResolver::make()') && substr_count($read('app/Models/Navigation.php'), 'catch (\Throwable') >= 2);

echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
