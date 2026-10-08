<?php
/**
 * Pemasang landing (tools/pasang-landing.php):  php tests/pasang-landing-test.php [akar-kit]
 * Murni (tanpa lab). Membangun proyek landing dan CMS tiruan di folder sementara, lalu menjalankan alatnya sungguhan.
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 500) . "]" : '') . "\n"; }
function rrm(string $d): void { if (!is_dir($d)) return; foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) { $f->isDir() && !$f->isLink() ? rmdir($f->getPathname()) : unlink($f->getPathname()); } rmdir($d); }
function put(string $p, string $c): void { @mkdir(dirname($p), 0775, true); file_put_contents($p, $c); }
function files(string $d): array { $o = []; if (!is_dir($d)) return $o; foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS)) as $f) { if ($f->isFile()) $o[str_replace('\\', '/', substr($f->getPathname(), strlen($d) + 1))] = md5_file($f->getPathname()); } ksort($o); return $o; }
function run(string $kit, array $args): array { exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$kit/tools/pasang-landing.php") . ' ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1', $out, $code); return [$code, implode("\n", $out)]; }
function project(string $d): void { rrm($d); put("$d/artisan", '<?php'); put("$d/composer.json", '{}'); }

$tmp = sys_get_temp_dir() . '/pasang-landing-' . getmypid();
rrm($tmp); mkdir($tmp, 0775, true);
$types = ['heading', 'paragraph', 'eyebrow', 'image', 'card-builder', 'step-group', 'multi-columns'];
$mkCms = function (string $d) use ($types) { project($d); foreach ($types as $t) { put("$d/resources/views/components/blocks/render/$t.blade.php", "@props(['block' => [], 'data' => [], 'lang' => 'id', 'allContent' => []])\n<div data-r=\"$t\"></div>\n"); } };
$mkLanding = function (string $d) { project($d); put("$d/.env", "APP_KEY=rahasia\nDB_PASSWORD=rahasia\n"); put("$d/vendor/laravel/x.php", '<?php // vendor'); put("$d/node_modules/a/index.js", '// node'); put("$d/database/database.sqlite", 'SQLITE'); put("$d/app/Models/User.php", '<?php // milik landing'); };
$sizeA = count(array_filter(preg_split('/\R/', (string) file_get_contents("$root/landing-files.txt")) ?: [], fn ($l) => trim($l) !== '' && trim($l)[0] !== '#'));
$sizeB = count(files("$root/landing-app"));
$total = $sizeA + $sizeB + 7;

echo "\nRencana (tanpa --apply)\n";
$L = "$tmp/landing"; $C = "$tmp/cms"; $mkLanding($L); $mkCms($C);
$before = files($L);
[$c, $o] = run($root, [$L, $C]);
check("rencana: kode 0, melaporkan $total berkas baru (A=$sizeA, B=$sizeB, C=7) dan menyuruh memakai --apply", $c === 0 && str_contains($o, "$total baru, 0 diganti") && str_contains($o, '--apply') && str_contains($o, 'belum mengubah apa pun'), $o);
check('rencana TIDAK menulis apa pun ke landing (isi folder identik; tidak ada folder cadangan)', files($L) === $before && !is_dir("$L/storage/pasang-cadangan"));
check('rencana menyebut berkas khusus landing satu per satu (rute, header, model)', str_contains($o, 'routes/web.php') && str_contains($o, 'header.blade.php') && str_contains($o, 'app/Models/Navigation.php'));

echo "\nPasang (--apply) pada landing yang bersih\n";
[$c, $o] = run($root, [$L, $C, '--apply']);
check("kode 0 dan melaporkan \"SELESAI: $total berkas dipasang\"", $c === 0 && str_contains($o, "SELESAI: $total berkas dipasang"), $o);
$bad = [];
foreach (preg_split('/\R/', (string) file_get_contents("$root/landing-files.txt")) as $l) { $l = trim($l); if ($l !== '' && $l[0] !== '#' && md5_file("$L/$l") !== md5_file("$root/$l")) { $bad[] = $l; } }
foreach (files("$root/landing-app") as $rel => $h) { if (($now = @md5_file("$L/$rel")) !== $h) { $bad[] = $rel; } }
foreach ($types as $t) { if (md5_file("$L/resources/views/components/blocks/render/$t.blade.php") !== md5_file("$C/resources/views/components/blocks/render/$t.blade.php")) { $bad[] = $t; } }
check('SETIAP berkas di tujuan identik dengan sumbernya (kit, landing-app, dan renderer CMS)', $bad === [], implode(',', array_slice($bad, 0, 5)));
check('berkas yang TIDAK boleh disentuh tetap utuh: .env, vendor, node_modules, database, dan model User milik landing', file_get_contents("$L/.env") === "APP_KEY=rahasia\nDB_PASSWORD=rahasia\n" && file_get_contents("$L/vendor/laravel/x.php") === '<?php // vendor' && file_get_contents("$L/node_modules/a/index.js") === '// node' && file_get_contents("$L/database/database.sqlite") === 'SQLITE' && file_get_contents("$L/app/Models/User.php") === '<?php // milik landing');
check('CMS tidak diubah sama sekali (hanya dibaca)', files($C) === (function () use ($mkCms) { $mkCms($GLOBALS['tmp'] . '/cms-ref'); return files($GLOBALS['tmp'] . '/cms-ref'); })());
check('tidak ada folder cadangan bila tidak ada yang diganti; pemeriksa dijalankan otomatis dan hasilnya dilaporkan', !is_dir("$L/storage/pasang-cadangan") && str_contains($o, 'Memeriksa hasilnya') && preg_match('/==> \d+ galat, \d+ peringatan/', $o) === 1 && str_contains($o, 'php artisan view:clear') && str_contains($o, 'npm run build'));

echo "\nDijalankan ulang (idempoten)\n";
$snap = files($L);
[$c, $o] = run($root, [$L, $C, '--apply']);
check('kedua kalinya: kode 0, "sudah sama", tidak ada yang ditulis, tidak ada cadangan baru', $c === 0 && str_contains($o, 'tidak ada yang perlu dipasang') && !str_contains($o, 'SELESAI') && files($L) === $snap && !is_dir("$L/storage/pasang-cadangan"), $o);

echo "\nBerkas milik landing yang berbeda: dicadangkan lalu diganti\n";
$L2 = "$tmp/landing2"; $mkLanding($L2);
put("$L2/routes/web.php", "<?php // rute milik saya, sudah saya ubah\n");
put("$L2/resources/css/app.css", "/* css saya */\n");
put("$L2/app/Models/Navigation.php", "<?php // navigation saya\n");
$oldWeb = file_get_contents("$L2/routes/web.php");
[$c, $o] = run($root, [$L2, $C]);
check('rencana menampilkan GANTI untuk ketiga berkas dan menyebut cadangan; tidak menulis apa pun', $c === 0 && str_contains($o, 'GANTI  routes/web.php') && str_contains($o, 'GANTI  resources/css/app.css') && str_contains($o, 'GANTI  app/Models/Navigation.php') && str_contains($o, '3 diganti') && file_get_contents("$L2/routes/web.php") === $oldWeb && !is_dir("$L2/storage/pasang-cadangan"), $o);
[$c, $o] = run($root, [$L2, $C, '--apply']);
$bk = glob("$L2/storage/pasang-cadangan/*", GLOB_ONLYDIR) ?: [];
check('--apply: tepat SATU folder cadangan berisi ketiga berkas lama dengan isi aslinya dan jalur yang sama', $c === 0 && count($bk) === 1 && file_get_contents("$bk[0]/routes/web.php") === $oldWeb && file_get_contents("$bk[0]/resources/css/app.css") === "/* css saya */\n" && file_get_contents("$bk[0]/app/Models/Navigation.php") === "<?php // navigation saya\n" && count(files($bk[0])) === 3, $o);
check('berkas baru terpasang (isi = landing-app) dan pesan menyebut jalur cadangan serta cara membatalkan', md5_file("$L2/routes/web.php") === md5_file("$root/landing-app/routes/web.php") && str_contains($o, '3 berkas lama dicadangkan') && str_contains($o, 'membatalkan'));

echo "\nTanpa folder CMS\n";
$L3 = "$tmp/landing3"; $mkLanding($L3);
[$c, $o] = run($root, [$L3, '--apply']);
check('tanpa jalur CMS: kelompok C DILEWATI dengan petunjuk, sisanya terpasang (' . ($sizeA + $sizeB) . ' berkas)', $c === 0 && str_contains($o, 'DILEWATI') && str_contains($o, 'folder CMS tidak diberikan') && str_contains($o, 'SELESAI: ' . ($sizeA + $sizeB) . ' berkas dipasang') && !is_file("$L3/resources/views/components/blocks/render/heading.blade.php"), $o);

echo "\nPenolakan SEBELUM menulis (tidak pernah setengah jadi)\n";
$L4 = "$tmp/landing4"; $mkLanding($L4); $snap4 = files($L4);
$C4 = "$tmp/cms4"; $mkCms($C4); put("$C4/resources/views/components/blocks/render/paragraph.blade.php", "@props([\"blockId\", \"code\", \"block\", \"allContent\" => []])\n<x-blocks.editor.wrapper :block-id=\"\$blockId\"></x-blocks.editor.wrapper>\n");
[$c, $o] = run($root, [$L4, $C4, '--apply']);
check('renderer dari CMS yang ternyata komponen EDITOR: kode 2, menyebut berkasnya, dan landing TIDAK berubah sama sekali (termasuk kelompok A dan B)', $c === 2 && str_contains($o, 'komponen EDITOR') && str_contains($o, 'paragraph.blade.php') && str_contains($o, 'TIDAK ADA YANG DIUBAH') && files($L4) === $snap4, $o);
unlink("$C4/resources/views/components/blocks/render/eyebrow.blade.php");
put("$C4/resources/views/components/blocks/render/paragraph.blade.php", "<p></p>\n");
[$c, $o] = run($root, [$L4, $C4, '--apply']);
check('renderer hilang di CMS: kode 2, menyebut berkas dan petunjuk folder "render" (bukan "blocks"), landing tidak berubah', $c === 2 && str_contains($o, 'eyebrow.blade.php') && str_contains($o, "folder 'render'") && files($L4) === $snap4, $o);
put("$C4/resources/views/components/blocks/render/eyebrow.blade.php", '');
[$c, $o] = run($root, [$L4, $C4, '--apply']);
check('renderer kosong di CMS ditolak', $c === 2 && str_contains($o, 'kosong') && files($L4) === $snap4, $o);
@mkdir("$tmp/kosong", 0775, true);
$kitLaravel = "$tmp/kit-laravel"; mkdir($kitLaravel); put("$kitLaravel/artisan", '<?php'); put("$kitLaravel/composer.json", '{}');   // kit yang LOLOS pemeriksaan "proyek Laravel": hanya penjaga "kit" yang bisa menolaknya
foreach (['tools', 'landing-app'] as $d) { foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$d", FilesystemIterator::SKIP_DOTS)) as $f) { if ($f->isFile()) { put("$kitLaravel/$d/" . str_replace('\\', '/', substr($f->getPathname(), strlen("$root/$d") + 1)), file_get_contents($f->getPathname())); } } }
put("$kitLaravel/landing-files.txt", "# kecil\napp/Content/Slug.php\n"); put("$kitLaravel/app/Content/Slug.php", '<?php // slug'); project("$kitLaravel/di-dalam");
foreach ([
    ['landing bukan proyek Laravel', $root, ["$tmp/kosong", $C], 'Folder landing bukan proyek Laravel'],
    ['landing tidak ada', $root, ["$tmp/tidak-ada", $C], 'Folder landing tidak ditemukan'],
    ['CMS bukan proyek Laravel', $root, [$L4, "$tmp/kosong"], 'Folder CMS bukan proyek Laravel'],
    ['CMS tidak ada', $root, [$L4, "$tmp/tidak-ada"], 'Folder CMS tidak ditemukan'],
    ['CMS sama dengan landing', $root, [$L4, $L4], 'Folder CMS dan landing sama'],
    ['landing = folder kit itu sendiri (kit tiruan yang lolos pemeriksaan Laravel)', $kitLaravel, [$kitLaravel, $C], 'tidak boleh sama dengan, atau berada di dalam, folder kit'],
    ['landing berada DI DALAM folder kit', $kitLaravel, ["$kitLaravel/di-dalam", $C], 'tidak boleh sama dengan, atau berada di dalam, folder kit'],
    ['tanpa argumen', $root, [], 'Pemakaian'],
    ['argumen terlalu banyak', $root, [$L4, $C, "$tmp/x"], 'Pemakaian'],
] as [$label, $kitDir, $a, $needle]) {
    [$c, $o] = run($kitDir, array_merge($a, ['--apply']));
    check("ditolak (kode 2) oleh penjaga yang tepat: $label", $c === 2 && str_contains($o, $needle), "kode $c :: $o");
}
check('semua penolakan tidak mengubah landing', files($L4) === $snap4);

echo "\nTautan simbolik di dalam landing yang menunjuk ke luar\n";
$L7 = "$tmp/landing7"; $mkLanding($L7); $outside = "$tmp/di-luar"; mkdir($outside);
if (@symlink($outside, "$L7/routes")) {
    $snap7 = files($L7);
    [$c, $o] = run($root, [$L7, $C, '--apply']);
    check('folder landing (routes) berupa tautan simbolik ke luar: kode 2 SEBELUM menulis; tidak ada berkas tertulis di luar maupun di dalam landing', $c === 2 && str_contains($o, 'di luar folder landing') && str_contains($o, 'routes/web.php') && files($outside) === [] && files($L7) === $snap7 && !is_dir("$L7/storage/pasang-cadangan"), $o);
    unlink("$L7/routes");
    put("$outside/x.txt", 'luar'); symlink("$outside/x.txt", "$L7/public-link"); @mkdir("$L7/public"); symlink("$outside/x.txt", "$L7/public/robots.txt");
    [$c, $o] = run($root, [$L7, $C, '--apply']);
    check('berkas tujuan berupa tautan simbolik: ditolak (tidak menimpa berkas di luar lewat tautan)', $c === 2 && str_contains($o, 'tautan simbolik') && file_get_contents("$outside/x.txt") === 'luar', $o);
} else {
    echo "  (dilewati: sistem ini tidak mengizinkan tautan simbolik)\n";
}

echo "\nKegagalan saat menulis: cadangan dulu, baru mengganti\n";
$L5 = "$tmp/landing5"; $mkLanding($L5); put("$L5/routes/web.php", "<?php // milik saya\n"); put("$L5/storage/pasang-cadangan", 'ini berkas, bukan folder');   // membuat folder cadangan mustahil
$snap5 = files($L5);
[$c, $o] = run($root, [$L5, $C, '--apply']);
check('folder cadangan tidak bisa dibuat: kode 1, menyebut alasannya, dan berkas milik landing TIDAK diganti serta tidak ada berkas baru yang ditulis', $c === 1 && str_contains($o, 'Gagal membuat folder cadangan') && str_contains($o, 'Tidak ada berkas landing yang diganti') && files($L5) === $snap5, $o);

echo "\nJalur tidak aman di daftar berkas\n";
$kitCopy = "$tmp/kitcopy"; mkdir($kitCopy);
foreach (['tools', 'landing-app'] as $d) { foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$d", FilesystemIterator::SKIP_DOTS)) as $f) { if ($f->isFile()) { put("$kitCopy/$d/" . str_replace('\\', '/', substr($f->getPathname(), strlen("$root/$d") + 1)), file_get_contents($f->getPathname())); } } }
$lf = (string) file_get_contents("$root/landing-files.txt");
foreach (preg_split('/\R/', $lf) as $l) { if (trim($l) !== '' && trim($l)[0] !== '#' && is_file("$root/" . trim($l))) { put("$kitCopy/" . trim($l), file_get_contents("$root/" . trim($l))); } }
$L6 = "$tmp/landing6"; $mkLanding($L6); $snap6 = files($L6);
put("$tmp/evil.php", "ASLI");                                  // ../evil.php dari kit tiruan = berkas yang ADA di luar kit
put("$kitCopy/etc/passwd", 'x'); put("$kitCopy/C:/Windows/x.php", 'x'); put("$kitCopy/a\\b.php", 'x');   // berkas-berkas ini ADA: hanya penjaga jalur yang bisa menolaknya
foreach (['../evil.php', '/etc/passwd', 'C:/Windows/x.php', 'a\\b.php', 'app/../../evil.php'] as $evil) {
    put("$kitCopy/landing-files.txt", $lf . "\n$evil\n");
    [$c, $o] = run($kitCopy, [$L6, '--apply']);
    check("jalur tidak aman di daftar berkas, padahal berkasnya ADA ($evil): kode 2 oleh penjaga jalur; landing dan berkas di luar tidak berubah", $c === 2 && str_contains($o, 'jalurnya tidak aman') && files($L6) === $snap6 && file_get_contents("$tmp/evil.php") === 'ASLI', $o);
}

rrm($tmp);
echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
