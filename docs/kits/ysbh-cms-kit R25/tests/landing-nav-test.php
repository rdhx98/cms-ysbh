<?php
/**
 * Uji model Navigation landing (landing-app/app/Models/Navigation.php):  php tests/landing-nav-test.php <bootstrap-lab.php> <support.php>
 * href (rute bernama atau url aman, tidak pernah galat) dan isCurrent (aktif untuk rute turunan dan jalur turunan).
 */
[$_, $bootstrap, $support] = $argv + [null, null, null];
$root = dirname(__DIR__);
require $bootstrap;
// Pengganti app() yang bahasanya dapat diganti per pengujian (stub bawaan lab selalu 'id'); harus didefinisikan SEBELUM support.php
$GLOBALS['__locale'] = 'en';   // bahasa bawaan situs (rilis 24: EN, tanpa awalan)
if (!function_exists('app')) { function app($x = null) { if ($x === 'router') { return \Illuminate\Container\Container::getInstance()->make('router'); } return new class { public function getLocale() { return $GLOBALS['__locale']; } }; } }
if (!function_exists('config')) { function config($key = null, $default = null) { return $GLOBALS['__cfg'][$key] ?? $default; } }
$GLOBALS['__cfg'] = [];
require $support;

if (!trait_exists('Spatie\\Translatable\\HasTranslations')) { eval('namespace Spatie\\Translatable; trait HasTranslations {}'); }   // paket Spatie tidak ada di lab

use App\Models\Navigation;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
{

    // --- pengganti bagian aplikasi Laravel yang tidak ada di lab ---------------------------------------------------------------
    $GLOBALS['routes'] = ['home' => '/', 'programs' => '/programs', 'programs-malaria' => '/programs/malaria', 'article.show' => null /* butuh parameter */];
    $GLOBALS['current'] = ['name' => 'home', 'path' => '/', 'host' => 'ysbh.org'];
    $container = Container::getInstance();
    Facade::setFacadeApplication($container);
    $container->instance('router', new class { public function has(string $n): bool { return array_key_exists($n, $GLOBALS['routes']); } });
    if (!function_exists('route')) {
        function route(string $name): string { if (($GLOBALS['routes'][$name] ?? null) === null) throw new \RuntimeException("Missing required parameter for [$name]"); return 'https://ysbh.org' . $GLOBALS['routes'][$name]; }
    }
    if (!function_exists('request')) {
        function request() { return new class {
            public function routeIs(string ...$patterns): bool { foreach ($patterns as $p) if (fnmatch($p, $GLOBALS['current']['name'])) return true; return false; }
            public function path(): string { return ltrim($GLOBALS['current']['path'], '/') ?: '/'; }
            public function getHost(): string { return $GLOBALS['current']['host']; }
        }; }
    }
    require "$root/landing-app/app/Models/Navigation.php";

    $pass = $fail = 0;
    function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [$x]" : '') . "\n"; }
    $nav = function (?string $route, ?string $url) { $n = new Navigation(); $n->setRawAttributes(['route_name' => $route, 'url' => $url]); return $n; };
    $at = function (string $name, string $path, string $host = 'ysbh.org') { $GLOBALS['current'] = ['name' => $name, 'path' => $path, 'host' => $host]; };

    echo "\nhref\n";
    check('rute bernama yang ada: alamat dari route()', $nav('programs', null)->href === 'https://ysbh.org/programs');
    check('rute bernama MENANG atas url bila keduanya terisi', $nav('programs', '/lain')->href === 'https://ysbh.org/programs');
    check('rute tidak ada di aplikasi (salah ketik / dihapus): jatuh ke url, bukan galat', $nav('tidak-ada', '/tentang-kami')->href === '/tentang-kami');
    check('rute butuh parameter (article.show): jatuh ke url / "#", bukan galat', $nav('article.show', '/artikel/x')->href === '/artikel/x' && $nav('article.show', null)->href === '#');
    check('halaman CMS lewat url: jalur dan URL absolut http(s) diterima apa adanya', $nav(null, '/tentang-kami')->href === '/tentang-kami' && $nav(null, 'https://ysbh.org/artikel')->href === 'https://ysbh.org/artikel' && $nav('', ' /spasi ')->href === '/spasi');
    check('mailto, tel, dan #anchor diterima', $nav(null, 'mailto:contact@ysbh.org')->href === 'mailto:contact@ysbh.org' && $nav(null, 'tel:+6282382295759')->href === 'tel:+6282382295759' && $nav(null, '#kontak')->href === '#kontak');
    foreach (['javascript:alert(1)', 'data:text/html,x', '//evil.test/x', 'vbscript:x', "/x\ny", 'file:///etc/passwd', 'http://', ' '] as $bad) {
        check("url tak aman '" . str_replace("\n", '\\n', $bad) . "' -> '#'", $nav(null, $bad)->href === '#', $nav(null, $bad)->href);
    }
    check('tanpa rute dan tanpa url -> "#"', $nav(null, null)->href === '#' && $nav('', '')->href === '#');

    echo "\nisCurrent\n";
    $at('programs-malaria', '/programs/malaria');
    check("rute turunan: menu 'programs' aktif di halaman 'programs-malaria'; 'home' tidak", $nav('programs', null)->isCurrent() === true && $nav('home', null)->isCurrent() === false);
    $at('home', '/');
    check("menu 'home' aktif di beranda; 'programs' tidak", $nav('home', null)->isCurrent() === true && $nav('programs', null)->isCurrent() === false);
    $at('page.show', '/tentang-kami');
    check('url: jalur yang sama aktif; jalur lain tidak', $nav(null, '/tentang-kami')->isCurrent() === true && $nav(null, '/kontak')->isCurrent() === false);
    $at('page.show', '/tentang-kami/sub');
    check('url: halaman DI BAWAH jalur menu ikut aktif ("/tentang-kami" di "/tentang-kami/sub")', $nav(null, '/tentang-kami')->isCurrent() === true);
    $at('page.show', '/tentang-kami-lain');
    check('url: awalan kata yang sama bukan turunan ("/tentang-kami" tidak aktif di "/tentang-kami-lain")', $nav(null, '/tentang-kami')->isCurrent() === false);
    $at('articles', '/artikel');
    check('url "/artikel" aktif di indeks; url "/" hanya aktif di beranda', $nav(null, '/artikel')->isCurrent() === true && $nav(null, '/')->isCurrent() === false);
    $at('article.show', '/artikel/imunisasi');
    check('menu "/artikel" tetap aktif di halaman artikel', $nav(null, '/artikel')->isCurrent() === true);
    $at('home', '/');
    check('url "/" aktif di beranda; "#" dan alamat luar tidak pernah aktif', $nav(null, '/')->isCurrent() === true && $nav(null, null)->isCurrent() === false && $nav(null, 'https://evil.test/')->isCurrent() === false && $nav(null, '#kontak')->isCurrent() === false);
    $at('page.show', '/tentang-kami', 'ysbh.org');
    check('URL absolut ke host yang sama dihitung; host lain tidak', $nav(null, 'https://ysbh.org/tentang-kami')->isCurrent() === true && $nav(null, 'https://lain.org/tentang-kami')->isCurrent() === false);
    $threw = null; try { $nav('tidak-ada', null)->isCurrent(); $nav('article.show', '/x')->isCurrent(); } catch (\Throwable $e) { $threw = $e; }
    check('rute hilang / butuh parameter: isCurrent tidak melempar galat', $threw === null, $threw ? $threw->getMessage() : '');


    echo "\nRilis 24: menu dua bahasa\n";
    foreach (['ReadOnlyModel', 'Page', 'Post', 'Category', 'Snippet', 'Media'] as $m) { require_once "$root/landing-app/app/Models/$m.php"; }
    boot_database();
    \Illuminate\Support\Facades\Schema::create('pages', function ($t) { $t->id(); $t->text('title')->nullable(); $t->text('slug')->nullable(); $t->text('content')->nullable(); $t->string('status')->default('offline'); $t->text('updated_at')->nullable(); $t->text('created_at')->nullable(); });
    $mk = fn (string $slug, string $title, string $status = 'online') => \Illuminate\Database\Capsule\Manager::table('pages')->insert(['title' => $title, 'slug' => $slug, 'status' => $status, 'content' => 'null']);
    $mk('{"id":"tentang-kami","en":"about-us"}', '{"id":"Tentang","en":"About"}');
    $mk('{"id":"hanya-id","en":""}', '{"id":"Hanya ID","en":""}');
    $mk('{"id":"draf","en":"draft"}', '{"id":"D","en":"D"}', 'offline');
    $GLOBALS['__cfg'] = ['cms.public.page' => ['en' => '/{slug}', 'id' => '/id/{slug}'], 'cms.public.home' => ['en' => '/', 'id' => '/id'], 'cms.public.articles' => ['en' => '/articles', 'id' => '/id/artikel'], 'cms.articles_index_slug' => ['en' => 'articles', 'id' => 'artikel'], 'cms.home_slug' => ['en' => 'home', 'id' => 'beranda'], 'cms.public.base' => ''];
    \App\Content\PublicLookup::flush();
    $hrefs = function (string $locale) use ($nav) { $GLOBALS['__locale'] = $locale; \App\Content\PublicLookup::flush(); return array_map(fn ($u) => $nav(null, $u)->href, ['/about-us', '/tentang-kami', '/id/tentang-kami', '/', '/id', '/articles', '/artikel', '/hanya-id', '/draf', '/tidak-ada', '/programs/malaria', 'https://ysbh.org/about-us', '#kontak', '/about-us?x=1#bagian']); };
    $en = $hrefs('en'); $id = $hrefs('id');
    check('pembaca EN: satu isian menuju versi EN, apa pun bahasa isiannya ("/about-us", "/tentang-kami", "/id/tentang-kami" -> "/about-us"); "/" dan "/id" -> "/"; indeks artikel -> "/articles"', array_slice($en, 0, 7) === ['/about-us', '/about-us', '/about-us', '/', '/', '/articles', '/articles'], json_encode($en));
    check('pembaca ID: versi ID dengan awalan ("/id/tentang-kami"); "/" -> "/id"; indeks -> "/id/artikel"', array_slice($id, 0, 7) === ['/id/tentang-kami', '/id/tentang-kami', '/id/tentang-kami', '/id', '/id', '/id/artikel', '/id/artikel'], json_encode($id));
    check('halaman belum diterjemahkan ke EN ("/hanya-id") -> tetap menuju versi yang ada ("/id/hanya-id"), bukan menu mati; di ID -> "/id/hanya-id"', $en[7] === '/id/hanya-id' && $id[7] === '/id/hanya-id');
    check('halaman offline / tidak ada / bersegmen banyak / alamat luar / anchor: TIDAK diubah; query dan fragmen dipertahankan', $en[8] === '/draf' && $en[9] === '/tidak-ada' && $en[10] === '/programs/malaria' && $en[11] === 'https://ysbh.org/about-us' && $en[12] === '#kontak' && $en[13] === '/about-us?x=1#bagian' && $id[13] === '/id/tentang-kami?x=1#bagian', json_encode([$en, $id]));
    // tanpa basis data / konfigurasi rusak: menu tetap tampil
    \Illuminate\Support\Facades\Schema::drop('pages'); $GLOBALS['__locale'] = 'en'; \App\Content\PublicLookup::flush();
    $threw = null; try { $x = $nav(null, '/tentang-kami')->href; } catch (\Throwable $e) { $threw = $e; }
    check('basis data tidak siap (tabel hilang): href tidak melempar galat dan memakai alamat yang diketik', $threw === null && $x === '/tentang-kami', $threw ? $threw->getMessage() : $x);

    echo "\nRilis 24: menu aktif di dua bahasa\n";
    $GLOBALS['__locale'] = 'id';   // bahasa mengikuti alamat: di bawah /id/… bahasanya id
    $at('id.page.show', '/id/tentang-kami');
    check('beranda ID ("/id") hanya aktif di dirinya sendiri, BUKAN di semua halaman /id/…; beranda ("/") tidak aktif di halaman ID', $nav(null, '/id')->isCurrent() === false && $nav(null, '/')->isCurrent() === false);
    $at('id.home', '/id');
    check('"/id" aktif di beranda ID', $nav(null, '/id')->isCurrent() === true && $nav(null, '/')->isCurrent() === true);   // di bahasa id, "/" dan "/id" sama-sama menuju beranda ID
    $at('id.article.show', '/id/artikel/imunisasi');
    check('menu "/id/artikel" aktif di halaman artikel ID (turunan jalur)', $nav(null, '/id/artikel')->isCurrent() === true);
    $GLOBALS['__locale'] = 'en';
    $at('home', '/');
    check('"/" aktif di beranda EN; "/id" di EN menuju beranda EN juga (awalan bahasa tertulis dibaca sebagai halamannya)', $nav(null, '/')->isCurrent() === true && $nav(null, '/id')->isCurrent() === true);
    $GLOBALS['routes'] += ['id.programs' => '/id/program', 'articles' => '/articles', 'id.articles' => '/id/artikel'];
    $GLOBALS['__locale'] = 'id';
    check("rute bernama: bahasa selain bawaan memakai rute berawalan bahasa bila ada ('articles' -> /id/artikel, 'programs' -> /id/program), bila tidak rute biasa ('home' -> /)", $nav('articles', null)->href === 'https://ysbh.org/id/artikel' && $nav('programs', null)->href === 'https://ysbh.org/id/program' && $nav('home', null)->href === 'https://ysbh.org/');
    $GLOBALS['__locale'] = 'en';
    check("rute bernama di bahasa bawaan: rute biasa ('articles' -> /articles)", $nav('articles', null)->href === 'https://ysbh.org/articles');
    $at('id.articles', '/id/artikel');
    $GLOBALS['__locale'] = 'id';
    check("isCurrent rute bernama di ID: 'articles' aktif di rute 'id.articles'", $nav('articles', null)->isCurrent() === true);

    echo "\n==> $pass lulus, $fail gagal\n";
    exit($fail ? 1 : 0);
}
