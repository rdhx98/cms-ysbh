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
function run(string $kit, array $args): array { exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$kit/tools/pasang-landing.php") . ' ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1', $out, $code); clearstatcache(); return [$code, implode("\n", $out)]; }
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
put("$kitLaravel/landing-files.txt", "# kecil\napp/Content/Slug.php\n"); put("$kitLaravel/landing-hapus.txt", file_get_contents("$root/landing-hapus.txt")); put("$kitLaravel/app/Content/Slug.php", '<?php // slug'); project("$kitLaravel/di-dalam");
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
    put("$outside/x.txt", 'luar'); @mkdir("$L7/config"); symlink("$outside/x.txt", "$L7/config/cms.php");
    [$c, $o] = run($root, [$L7, $C, '--apply']);
    check('berkas tujuan (dipasang) berupa tautan simbolik: ditolak (tidak menimpa berkas di luar lewat tautan)', $c === 2 && str_contains($o, 'Tujuan adalah tautan simbolik') && str_contains($o, 'config/cms.php') && file_get_contents("$outside/x.txt") === 'luar', $o);
    unlink("$L7/config/cms.php"); @mkdir("$L7/public"); symlink("$outside/x.txt", "$L7/public/robots.txt");
    [$c, $o] = run($root, [$L7, $C, '--apply']);
    check('berkas yang akan DIHAPUS berupa tautan simbolik (public/robots.txt -> luar): ditolak; berkas di luar tidak disentuh dan tautan tetap ada', $c === 2 && str_contains($o, 'Berkas yang akan dihapus adalah tautan simbolik') && file_get_contents("$outside/x.txt") === 'luar' && is_link("$L7/public/robots.txt"), $o);
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
put("$kitCopy/landing-hapus.txt", file_get_contents("$root/landing-hapus.txt"));
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

echo "\nH. Penghapusan halaman prototipe lama (selalu dicadangkan)\n";
$hapusList = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) file_get_contents("$root/landing-hapus.txt")) ?: []), fn ($l) => $l !== '' && $l[0] !== '#'));
$mkProto = function (string $d) use ($mkLanding, $hapusList) {
    $mkLanding($d);
    foreach ($hapusList as $rel) { put("$d/$rel", "ISI-LAMA $rel\n"); }
    // milik Anda, TIDAK tercatat: harus selamat
    put("$d/resources/views/pages/privasi.blade.php", "halaman saya\n"); put("$d/resources/views/components/cards/hero.blade.php", "kartu\n");
    put("$d/resources/views/components/layouts/app.blade.php", "<x-layouts.header /> {{ \$slot }} <x-layouts.footer />\n"); put("$d/resources/views/components/layouts/footer.blade.php", "<footer>{{ route('home') }}</footer>\n");
    put("$d/public/logo/emblem.svg", "<svg/>"); put("$d/public/favicon.ico", "ico");
};
$H = "$tmp/landingH"; $mkProto($H);
$userFiles = ['resources/views/pages/privasi.blade.php', 'resources/views/components/cards/hero.blade.php', 'resources/views/components/layouts/footer.blade.php', 'public/logo/emblem.svg', 'public/favicon.ico', '.env', 'app/Models/User.php'];
check('daftar landing-hapus.txt: 12 halaman Blade prototipe + public/robots.txt (13 jalur), tanpa duplikat; tidak ada layout, footer, header, kartu, atau aset', count($hapusList) === 13 && count(array_unique($hapusList)) === 13 && !array_filter($hapusList, fn ($l) => preg_match('#layouts|footer|header|cards|logo|\.env|Models#', $l)), implode(',', $hapusList));
$snapH = files($H);
[$c, $o] = run($root, [$H, $C]);
check('rencana: menampilkan 13 baris HAPUS, tidak menghapus apa pun, tidak membuat cadangan', $c === 0 && substr_count($o, '  HAPUS  ') === 13 && str_contains($o, '13 dihapus') && files($H) === $snapH && !is_dir("$H/storage/pasang-cadangan"), $o);
[$c, $o] = run($root, [$H, $C, '--apply']);
$bkH = glob("$H/storage/pasang-cadangan/*", GLOB_ONLYDIR) ?: [];
$gone = array_filter($hapusList, fn ($rel) => !file_exists("$H/$rel"));
check('--apply: ke-13 berkas terhapus dari landing, dan kode 0', $c === 0 && count($gone) === 13 && str_contains($o, '13 berkas prototipe lama dihapus'), $o);
check('SETIAP berkas yang dihapus ada di folder cadangan dengan isi aslinya dan jalur yang sama (satu folder cadangan)', count($bkH) === 1 && !array_filter($hapusList, fn ($rel) => @file_get_contents("$bkH[0]/$rel") !== "ISI-LAMA $rel\n"));
check('milik Anda yang TIDAK tercatat selamat utuh: halaman lain di pages/, kartu, layout, footer, aset, .env, model User', !array_filter($userFiles, fn ($rel) => !is_file("$H/$rel")) && file_get_contents("$H/resources/views/pages/privasi.blade.php") === "halaman saya\n" && file_get_contents("$H/.env") === "APP_KEY=rahasia\nDB_PASSWORD=rahasia\n");
check('yang tidak dihapus juga tidak dicadangkan (cadangan hanya berisi yang dihapus)', array_keys(files($bkH[0])) === (function () use ($hapusList) { $x = $hapusList; sort($x); return $x; })(), json_encode(array_keys(files($bkH[0]))));
$snapH2 = files($H);
[$c, $o] = run($root, [$H, $C, '--apply']);
check('dijalankan ulang: tidak ada yang dihapus lagi, tidak ada cadangan baru, "tidak ada yang perlu dipasang"', $c === 0 && files($H) === $snapH2 && count(glob("$H/storage/pasang-cadangan/*", GLOB_ONLYDIR)) === 1 && str_contains($o, 'tidak ada yang perlu dipasang') && !str_contains($o, 'berkas prototipe lama dihapus'), $o);

put("$H/resources/views/pages/about.blade.php", "kembali\n");   // landing sudah sama dengan kit, KECUALI satu halaman prototipe yang muncul lagi
[$c, $o] = run($root, [$H, $C]);
check('hanya ada yang perlu DIHAPUS (semua berkas pasang sudah sama): rencana tetap menyuruh --apply dan TIDAK berkata "tidak ada yang perlu dipasang"', $c === 0 && substr_count($o, '  HAPUS  ') === 1 && str_contains($o, '--apply') && str_contains($o, 'baru rencana') && !str_contains($o, 'tidak ada yang perlu dipasang') && is_file("$H/resources/views/pages/about.blade.php"), $o);
[$c, $o] = run($root, [$H, $C, '--apply']);
check('hanya penghapusan: --apply menghapusnya dan mencadangkannya (cadangan kedua)', $c === 0 && !is_file("$H/resources/views/pages/about.blade.php") && str_contains($o, '1 berkas prototipe lama dihapus') && count(glob("$H/storage/pasang-cadangan/*", GLOB_ONLYDIR)) >= 1, $o);
$H2 = "$tmp/landingH2"; $mkLanding($H2); put("$H2/resources/views/pages/about.blade.php", "ISI\n");
[$c, $o] = run($root, [$H2, $C, '--apply']);
check('hanya yang ADA yang dihapus: satu halaman prototipe saja -> "1 berkas prototipe lama dihapus", sisanya dilewati tanpa galat', $c === 0 && str_contains($o, '1 berkas prototipe lama dihapus') && str_contains($o, '12 lainnya tidak ada di landing Anda') && !is_file("$H2/resources/views/pages/about.blade.php"), $o);

// kit tiruan dengan landing-hapus.txt yang dimodifikasi: SETIAP penjaga diuji sendiri
$mkKit = function (string $name, string $hapusText, ?callable $extra = null) use ($root, $tmp) {
    $k = "$tmp/$name"; mkdir($k);
    foreach (['tools', 'landing-app'] as $d) { foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$d", FilesystemIterator::SKIP_DOTS)) as $f) { if ($f->isFile()) { put("$k/$d/" . str_replace('\\', '/', substr($f->getPathname(), strlen("$root/$d") + 1)), file_get_contents($f->getPathname())); } } }
    foreach (preg_split('/\R/', (string) file_get_contents("$root/landing-files.txt")) as $l) { if (trim($l) !== '' && trim($l)[0] !== '#') { put("$k/" . trim($l), file_get_contents("$root/" . trim($l))); } }
    put("$k/landing-files.txt", file_get_contents("$root/landing-files.txt")); put("$k/landing-hapus.txt", $hapusText);
    if ($extra) { $extra($k); }

    return $k;
};
$guards = [
    'jalur di luar resources/views/pages dan public/robots.txt (model User)' => ['app/Models/User.php', 'tidak sah'],
    'jalur .env' => ['.env', 'tidak sah'],
    'jalur config/cms.php (berkas yang dipasang kit)' => ['config/cms.php', 'tidak sah'],
    'jalur naik folder (..)' => ['resources/views/pages/../../../.env', 'tidak sah'],
    'jalur mutlak' => ['/etc/passwd', 'tidak sah'],
    'jalur Windows (C:)' => ['C:/x/resources/views/pages/a.php', 'tidak sah'],
    'garis miring terbalik' => ['resources/views/pages\\a.blade.php', 'tidak sah'],
    'folder (berakhiran /)' => ['resources/views/pages/', 'tidak sah'],
    'public/robots.txt.bak (awalan mirip)' => ['public/robots.txt.bak', 'tidak sah'],
    'public/index.php' => ['public/index.php', 'tidak sah'],
];
$LG = "$tmp/landingG"; $mkProto($LG); $snapG = files($LG);
foreach ($guards as $label => [$entry, $needle]) {
    $k = $mkKit('kitG', "resources/views/pages/about.blade.php\n$entry\n");
    [$c, $o] = run($k, [$LG, '--apply']);
    check("landing-hapus.txt memuat $label: ditolak (kode 2) SEBELUM menulis; satu pun berkas landing tidak berubah", $c === 2 && str_contains($o, $needle) && str_contains($o, 'TIDAK ADA YANG DIUBAH') && files($LG) === $snapG, $o);
    rrm($k);
}
$k = $mkKit('kitG2', "resources/views/pages/about.blade.php\n", fn ($k) => put("$k/landing-app/resources/views/pages/about.blade.php", "dipasang sekaligus dihapus\n"));
[$c, $o] = run($k, [$LG, '--apply']);
check('berkas yang muncul di landing-app (dipasang) SEKALIGUS di daftar hapus: Bentrok, kode 2, tidak ada perubahan', $c === 2 && str_contains($o, 'akan dipasang sekaligus dihapus') && files($LG) === $snapG, $o);
rrm($k);
$k = $mkKit('kitG3', "");
unlink("$k/landing-hapus.txt");
[$c, $o] = run($k, [$LG, '--apply']);
check('landing-hapus.txt tidak ada di kit: kode 2 dengan pesan jelas, tidak ada perubahan', $c === 2 && str_contains($o, 'landing-hapus.txt tidak ada') && files($LG) === $snapG, $o);
rrm($k);
$LD = "$tmp/landingD"; $mkLanding($LD); mkdir("$LD/resources/views/pages/landing.blade.php", 0775, true); $snapD = files($LD);
[$c, $o] = run($root, [$LD, $C, '--apply']);
check('yang akan dihapus ternyata FOLDER (bukan berkas): ditolak, kode 2, tidak ada perubahan', $c === 2 && str_contains($o, 'ternyata folder') && files($LD) === $snapD && is_dir("$LD/resources/views/pages/landing.blade.php"), $o);

echo "\nroute() ke rute yang dihapus: ditolak sebelum menulis\n";
$LR = "$tmp/landingR"; $mkProto($LR);
put("$LR/resources/views/components/layouts/footer.blade.php", "<footer>\n<a href=\"{{ route('about') }}\">A</a>\n<a href=\"{{ route(\"programs-malaria\") }}\">M</a>\n</footer>\n");
put("$LR/resources/views/pages/landing.blade.php", "{{ route('programs') }} {{ route('credibility') }}\n");                 // dihapus pemasang: tidak dihitung
put("$LR/resources/views/components/layouts/header.blade.php", "{{ route('contact') }}\n");                                 // diganti pemasang: tidak dihitung
put("$LR/resources/views/components/probe.blade.php", "{{-- route('impact') --}}\n<?php // route('transparancies')\n?>{{ route('home') }} {{ request()->routeIs('about') }} {{ route('about-us') }}\n");   // komentar dan nama lain: tidak dihitung
$snapR = files($LR);
[$c, $o] = run($root, [$LR, $C]);
check('rencana (tanpa --apply) pun menolak dengan kode 2 dan tidak mengubah apa pun', $c === 2 && files($LR) === $snapR, $o);
check("pesan menyebut berkas:baris dan penggantinya: footer.blade.php:2 route('about') -> url('/about'); :3 programs-malaria -> url('/programs/malaria')", str_contains($o, 'layouts/footer.blade.php:2') && str_contains($o, "route('about')  ->  url('/about')") && str_contains($o, 'layouts/footer.blade.php:3') && str_contains($o, "url('/programs/malaria')") && str_contains($o, '--abaikan-rute-hilang'), $o);
preg_match_all('#^\s{6}(\S+):(\d+)\s#m', $o, $mm, PREG_SET_ORDER);
check('tepat dua pemanggilan dilaporkan (footer:2 dan footer:3)', count($mm) === 2 && $mm[0][1] === 'resources/views/components/layouts/footer.blade.php' && $mm[0][2] === '2' && $mm[1][2] === '3', json_encode($mm));
[$c, $o] = run($root, [$LR, $C, '--apply']);
check('--apply: ditolak sama, "TIDAK ADA YANG DIUBAH", landing identik (tidak ada yang dihapus, dipasang, atau dicadangkan)', $c === 2 && str_contains($o, 'TIDAK ADA YANG DIUBAH') && files($LR) === $snapR && !is_dir("$LR/storage/pasang-cadangan"), $o);
put("$LR/resources/views/components/layouts/footer.blade.php", "<footer>{{ url('/about') }} {{ route('home') }}</footer>\n");
[$c, $o] = run($root, [$LR, $C, '--apply']);
check('footer diperbaiki -> pemasangan jalan (kode 0), prototipe terhapus, header diganti', $c === 0 && !is_file("$LR/resources/views/pages/landing.blade.php") && md5_file("$LR/resources/views/components/layouts/header.blade.php") === md5_file("$root/landing-app/resources/views/components/layouts/header.blade.php"), $o);
$LR2 = "$tmp/landingR2"; $mkProto($LR2); put("$LR2/resources/views/components/layouts/footer.blade.php", "<footer>{{ route('about') }}</footer>\n");
[$c, $o] = run($root, [$LR2, $C, '--apply', '--abaikan-rute-hilang']);
check('--abaikan-rute-hilang: pemeriksaan dilewati dan pemasangan jalan (kode 0); footer Anda tidak diubah alat', $c === 0 && str_contains(file_get_contents("$LR2/resources/views/components/layouts/footer.blade.php"), "route('about')"), $o);
[$c, $o] = run($root, [$LR2, $C]);
check('sesudah dipasang, pemeriksa landing (3d) tetap melaporkan route(\'about\') sebagai GALAT agar tidak terlupakan', str_contains((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$root/tools/check-landing.php") . ' ' . escapeshellarg($LR2) . ' 2>&1'), "layouts/footer.blade.php:1 memanggil route('about')"));

rrm($tmp);
echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
