<?php
/**
 * Pemindai route() ke rute yang dihapus (tools/rute-hilang.php):  php tests/rute-hilang-test.php [akar-kit]
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
require_once "$root/tools/rute-hilang.php";
$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 400) . "]" : '') . "\n"; }
function rrm(string $d): void { if (!is_dir($d)) return; foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) { $f->isDir() && !$f->isLink() ? rmdir($f->getPathname()) : unlink($f->getPathname()); } rmdir($d); }
$tmp = sys_get_temp_dir() . '/rute-hilang-' . getmypid(); rrm($tmp); mkdir("$tmp/resources/views", 0775, true); mkdir("$tmp/app", 0775, true); mkdir("$tmp/routes", 0775, true);
function scan(string $tmp, string $file, string $code, array $skip = []): array { @mkdir(dirname("$tmp/$file"), 0775, true); file_put_contents("$tmp/$file", $code); $r = ysbh_find_removed_route_refs($tmp, $skip); unlink("$tmp/$file"); return array_map(fn ($x) => "{$x['line']}:{$x['name']}", array_values(array_filter($r, fn ($x) => $x['file'] === $file))); }
$V = 'resources/views/probe.blade.php';

echo "\nYang HARUS ditemukan\n";
foreach ([
    "{{ route('about') }}" => ['1:about'], '{{ route("contact") }}' => ['1:contact'], "{{ route( 'programs' ) }}" => ['1:programs'],
    "{{ route(\n  'impact'\n) }}" => ['1:impact'],                                                                  // dipecah ke beberapa baris: dilaporkan di baris route( dimulai
    "x\n\n{{ route('credibility') }}" => ['3:credibility'], "<?php return to_route('transparancies'); ?>" => ['1:transparancies'],
    "{{ \$u->route('programs-hiv') }}" => ['1:programs-hiv'], "return redirect()->route('programs-malaria');" => ['1:programs-malaria'],
    "{{ route('about') }} {{ route('about') }}" => ['1:about', '1:about'], "a\r\n{{ route('programs-tbc') }}\r\n" => ['2:programs-tbc'],
    "<a href=\"{{ route('programs-imunisasi', ['x' => 1]) }}\">" => ['1:programs-imunisasi'], "{{ route('programs-kia') }}" => ['1:programs-kia'],
    "<?php // catatan\n\$x = route('about');" => ['2:about'], "https://x.test/a // bukan komentar PHP\n{{ route('about') }}" => ['2:about'],
] as $code => $want) {
    check('ditemukan ' . json_encode($want) . ' dalam ' . json_encode(substr($code, 0, 40)), scan($tmp, $V, $code) === $want, json_encode(scan($tmp, $V, $code)));
}
echo "\nYang TIDAK boleh dituduh\n";
foreach ([
    "{{ route('home') }}", "{{ route('articles') }}", "{{ route('page.show', 'x') }}", "{{ route('article.show', 'x') }}", "{{ route('sitemap') }}",
    "{{ request()->routeIs('about') }}", "{{ Route::has('about') }}", "{{ route('about-us') }}", "{{ route('programs.list') }}", "{{ route('about_us') }}", "{{ route('xabout') }}",
    "{{ myroute('about') }}", "{{ my_route('about') }}", "{{ route(\$name) }}", "{{ route('') }}", "route about", "'route' => 'about'", "{{ url('/about') }}",
    "{{-- route('about') --}}", "/* route('about') */", "// route('about')", "# route('about')", "    // route('about')", "<?php // route('about')\n?>", "<?php \$a = 1; // route('about')\n?>",
    "{{-- a\nb\nc --}}{{ route('home') }}", "#[Attr] {{ route('home') }}",
] as $code) {
    check('tidak dituduh: ' . json_encode($code), scan($tmp, $V, $code) === [], json_encode(scan($tmp, $V, $code)));
}
echo "\nJangkauan dan pengecualian\n";
check('berkas di app/ dan routes/ ikut diperiksa', scan($tmp, 'app/Support/Menu.php', "<?php return route('about');") === ['1:about'] && scan($tmp, 'routes/api.php', "<?php return route('contact');") === ['1:contact']);
check('berkas yang akan diganti/dihapus pemasang ($skipRel) dilewati; berkas lain tidak', scan($tmp, $V, "{{ route('about') }}", [$V]) === [] && scan($tmp, $V, "{{ route('about') }}", ['lain.php']) === ['1:about']);
check('berkas bukan .php (css, js, md) tidak diperiksa', (function () use ($tmp) { file_put_contents("$tmp/resources/views/a.js", "route('about')"); file_put_contents("$tmp/resources/views/a.md", "route('about')"); $r = ysbh_find_removed_route_refs($tmp); unlink("$tmp/resources/views/a.js"); unlink("$tmp/resources/views/a.md"); return $r === []; })());
check('folder yang bukan resources/views, app, routes (vendor, storage, tests) tidak diperiksa', (function () use ($tmp) { @mkdir("$tmp/vendor/x", 0775, true); @mkdir("$tmp/storage/framework/views", 0775, true); file_put_contents("$tmp/vendor/x/a.php", "<?php route('about');"); file_put_contents("$tmp/storage/framework/views/a.php", "<?php route('about');"); $r = ysbh_find_removed_route_refs($tmp); rrm("$tmp/vendor"); rrm("$tmp/storage"); return $r === []; })());
check('folder tidak ada: hasil kosong, tidak melempar galat', ysbh_find_removed_route_refs("$tmp/tidak-ada") === []);
check('tautan simbolik ke berkas di luar tidak diikuti', !function_exists('symlink') || (function () use ($tmp) { file_put_contents("$tmp/luar.php", "<?php route('about');"); $ok = @symlink("$tmp/luar.php", "$tmp/app/tautan.php"); $r = ysbh_find_removed_route_refs($tmp); @unlink("$tmp/app/tautan.php"); unlink("$tmp/luar.php"); return !$ok || $r === []; })());
check('urutan hasil: per berkas lalu per baris (stabil)', (function () use ($tmp) { file_put_contents("$tmp/app/b.php", "<?php\nroute('about');\nroute('contact');"); file_put_contents("$tmp/app/a.php", "<?php route('impact');"); $r = ysbh_find_removed_route_refs($tmp); unlink("$tmp/app/b.php"); unlink("$tmp/app/a.php"); return array_map(fn ($x) => "{$x['file']}:{$x['line']}", $r) === ['app/a.php:1', 'app/b.php:2', 'app/b.php:3']; })());
echo "\nDaftar nama dan pengganti\n";
$names = ysbh_removed_route_names();
check('11 nama rute yang dihapus; "home" dan "articles" TIDAK termasuk (masih ada)', count($names) === 11 && !in_array('home', $names, true) && !in_array('articles', $names, true) && count(array_unique($names)) === 11);
check('SEMUA nama ini memang tidak ada lagi di landing-app/routes/web.php (daftar tidak boleh menuduh rute yang masih hidup)', !array_filter($names, fn ($n) => str_contains((string) file_get_contents("$root/landing-app/routes/web.php"), "->name('$n')")));
check('penggantian: about -> /about; programs-malaria -> /programs/malaria; semuanya jalur sah yang diawali /', (function () use ($names) { foreach ($names as $n) { $p = ysbh_removed_route_path($n); if (!preg_match('#^/[a-z]+(?:/[a-z]+)?$#', $p)) { return false; } } return ysbh_removed_route_path('about') === '/about' && ysbh_removed_route_path('programs-malaria') === '/programs/malaria' && ysbh_removed_route_path('programs-imunisasi') === '/programs/imunisasi'; })());
check('berkas dapat di-require dua kali tanpa galat (fungsi dijaga function_exists)', (function () use ($root) { require "$root/tools/rute-hilang.php"; return true; })());
rrm($tmp);
echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
