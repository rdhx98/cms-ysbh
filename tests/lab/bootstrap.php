<?php
// LAB pengujian: Eloquent + SQLite sungguhan (Laravel 13.30.0 + Carbon 3.10.0) tanpa aplikasi Laravel penuh.
// Siapkan sekali: php tests/lab/setup.php  (mengunduh kedua pustaka ke tests/lab/_src). Kelas App\* dimuat dari folder kit INI (bukan dari tempat lain).
error_reporting(E_ALL & ~E_DEPRECATED);
$roots = [__DIR__ . '/_src/framework-13.30.0/src/Illuminate', __DIR__ . '/_src/Carbon-3.10.0/src'];
foreach ($roots as $r_) { if (!is_dir($r_)) { fwrite(STDERR, "Lab belum disiapkan ($r_ tidak ada). Jalankan dulu: php tests/lab/setup.php\n"); exit(3); } }
@mkdir(__DIR__ . '/_cache', 0777, true);
$cache = __DIR__ . '/_cache/classmap.json';
if (!is_file($cache)) {
    $map = [];
    foreach ($roots as $src) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->getExtension() !== 'php' || str_ends_with($f->getFilename(), 'helpers.php') || str_ends_with($f->getFilename(), 'functions.php')) continue;
            $code = file_get_contents($f->getPathname());
            if (!preg_match('/^namespace\s+([^;]+);/m', $code, $ns)) continue;
            if (preg_match_all('/^(?:abstract\s+|final\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/m', $code, $m)) {
                foreach ($m[1] as $name) $map[$ns[1] . '\\' . $name] = $f->getPathname();
            }
        }
    }
    file_put_contents($cache, json_encode($map));
}
$GLOBALS['__map'] = json_decode(file_get_contents($cache), true);
spl_autoload_register(function ($c) { if (isset($GLOBALS['__map'][$c])) require_once $GLOBALS['__map'][$c]; });
require_once __DIR__ . '/stubs.php';
foreach (['Collections/functions.php','Collections/helpers.php','Support/functions.php','Support/helpers.php'] as $h) require_once $roots[0] . '/' . $h;
spl_autoload_register(function ($c) {
    if (str_starts_with($c, 'App\\')) {
        $rel = str_replace('\\', '/', substr($c, 4));
        foreach ([dirname(__DIR__, 2) . '/app/'] as $base) {
            if (is_file($f = $base . $rel . '.php')) { require_once $f; return; }
        }
    }
});
