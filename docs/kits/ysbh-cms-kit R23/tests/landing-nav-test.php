<?php
/**
 * Uji model Navigation landing (landing-app/app/Models/Navigation.php):  php tests/landing-nav-test.php <bootstrap-lab.php> <support.php>
 * href (rute bernama atau url aman, tidak pernah galat) dan isCurrent (aktif untuk rute turunan dan jalur turunan).
 */
[$_, $bootstrap, $support] = $argv + [null, null, null];
$root = dirname(__DIR__);
require $bootstrap;
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

    echo "\n==> $pass lulus, $fail gagal\n";
    exit($fail ? 1 : 0);
}
