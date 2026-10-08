<?php
/**
 * Pemasang CMS (tools/pasang-cms.php):  php tests/pasang-cms-test.php [akar-kit]
 * Murni (tanpa lab). Proyek CMS tiruan di folder sementara; alatnya dijalankan sungguhan.
 */
$root = realpath($argv[1] ?? dirname(__DIR__));
$pass = $fail = 0;
function check(string $n, bool $ok, string $x = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  PASS  ' : '  FAIL  ') . $n . (!$ok && $x !== '' ? "   [" . substr($x, 0, 600) . "]" : '') . "\n"; }
function rrm(string $d): void { if (!is_dir($d) && !is_link($d)) return; if (is_link($d)) { unlink($d); return; } foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) { ($f->isDir() && !$f->isLink()) ? rmdir($f->getPathname()) : unlink($f->getPathname()); } rmdir($d); }
function put(string $p, string $c): void { @mkdir(dirname($p), 0775, true); file_put_contents($p, $c); }
function files(string $d): array { $o = []; if (!is_dir($d)) return $o; foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS)) as $f) { if ($f->isFile() || $f->isLink()) $o[str_replace('\\', '/', substr($f->getPathname(), strlen($d) + 1))] = $f->isLink() ? 'link' : md5_file($f->getPathname()); } ksort($o); return $o; }
function run(string $kit, array $args): array { exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$kit/tools/pasang-cms.php") . ' ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1', $out, $code); return [$code, implode("\n", $out)]; }
function project(string $d): void { rrm($d); put("$d/artisan", '<?php'); put("$d/composer.json", '{}'); }
function copyTree(string $from, string $to): void { foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS)) as $f) { if ($f->isFile()) { put($to . '/' . str_replace('\\', '/', substr($f->getPathname(), strlen($from) + 1)), file_get_contents($f->getPathname())); } } }

$tmp = sys_get_temp_dir() . '/pasang-cms-' . getmypid();
rrm($tmp); mkdir($tmp, 0775, true);
$kitFiles = [];
foreach (['app', 'resources', 'database'] as $top) { foreach (files("$root/$top") as $rel => $h) { $kitFiles["$top/$rel"] = $h; } }
$tocRel = 'resources/views/components/table-of-contents.blade.php';
$expect = count($kitFiles) + 1;
/** Berkas milik pengguna: tidak boleh pernah berubah. */
$mine = ['.env' => "APP_KEY=rahasia\n", 'vendor/laravel/x.php' => '<?php // vendor', 'node_modules/a/i.js' => '// node', 'app/Models/Page.php' => "<?php // Page milik saya (parsed_content)\n", 'app/Models/Post.php' => "<?php // Post milik saya\n", 'app/Models/User.php' => '<?php // User', 'app/Providers/AppServiceProvider.php' => '<?php // provider', 'app/Http/Controllers/X.php' => '<?php // x', 'routes/web.php' => "<?php // rute CMS saya\n", 'config/cms.php' => "<?php return ['design' => []];\n", 'config/app.php' => "<?php return [];\n", 'resources/css/app.css' => '/* css CMS saya */', 'resources/js/app.js' => '// app.js saya', 'resources/views/components/blocks/render/heading.blade.php' => '<h2>renderer saya</h2>', 'resources/views/components/blocks/render/paragraph.blade.php' => '<p>renderer saya</p>', 'database/seeders/DatabaseSeeder.php' => '<?php // seeder'];
$mkCms = function (string $d) use ($mine) { project($d); foreach ($mine as $rel => $c) { put("$d/$rel", $c); } };
$mineIntact = function (string $d) use ($mine): bool { foreach ($mine as $rel => $c) { if (@file_get_contents("$d/$rel") !== $c) { return false; } } return true; };

echo "\nRencana pada CMS tanpa berkas kit\n";
$A = "$tmp/cmsA"; $mkCms($A); $beforeA = files($A);
[$c, $o] = run($root, [$A]);
check("kode 0; melaporkan $expect berkas baru (" . count($kitFiles) . " kit + daftar isi) dan menyuruh memakai --apply", $c === 0 && str_contains($o, "$expect baru, 0 diganti") && str_contains($o, '--apply'), $o);
check('rencana TIDAK menulis apa pun (isi folder identik; tidak ada cadangan)', files($A) === $beforeA && !is_dir("$A/storage/pasang-cadangan"));
check('baru ditampilkan per folder (bukan 100 baris), termasuk folder berkas baru seperti app/Console/Commands', str_contains($o, 'app/Console/Commands/') && str_contains($o, 'resources/views/components/editor/') && substr_count($o, "\n") < 60);

echo "\nPasang pada CMS bersih\n";
[$c, $o] = run($root, [$A, '--apply']);
check("kode 0 dan \"SELESAI: $expect berkas dipasang, 0 dihapus\"", $c === 0 && str_contains($o, "SELESAI: $expect berkas dipasang, 0 dihapus"), $o);
$bad = []; foreach ($kitFiles as $rel => $h) { if (@md5_file("$A/$rel") !== $h) { $bad[] = $rel; } }
check('SETIAP berkas kit di CMS identik dengan sumbernya; daftar isi = versi perbaikan', $bad === [] && md5_file("$A/$tocRel") === md5_file("$root/landing-app/$tocRel"), implode(',', array_slice($bad, 0, 4)));
check('BERKAS MILIK ANDA tidak berubah sama sekali (.env, vendor, model Page/Post/User, provider, rute, config, css/js, renderer heading/paragraph, seeder)', $mineIntact($A));
check('yang khusus landing atau pengembangan TIDAK ikut: landing-app, tests, tools, docs, contoh-kode, patch, opsional, landing-files.txt, HAPUS.txt', !array_filter(array_keys(files($A)), fn ($f) => preg_match('#^(landing-app|tests|tools|docs|contoh-kode|patch|opsional)/#', $f) || in_array($f, ['landing-files.txt', 'HAPUS.txt', 'MANIFEST.sha256', 'PERUBAHAN.md'], true)));
check('pemindai direktif Blade dijalankan dan hasilnya dilaporkan; langkah berikutnya disebut (config, optimize:clear, npm run build, cms:audit-slugs)', str_contains($o, 'Memeriksa tampilan CMS') && str_contains($o, 'Pemindai: tidak ada tabrakan') && str_contains($o, 'optimize:clear') && str_contains($o, 'npm run build') && str_contains($o, 'cms:audit-slugs'), $o);
check('tidak ada folder cadangan (tidak ada yang diganti)', !is_dir("$A/storage/pasang-cadangan"));
$snap = files($A);
[$c, $o] = run($root, [$A, '--apply']);
check('dijalankan ulang: kode 0, "sudah sama", tidak menulis, tidak membuat cadangan, tidak mencetak SELESAI', $c === 0 && str_contains($o, 'tidak ada yang perlu dipasang') && !str_contains($o, 'SELESAI') && files($A) === $snap, $o);

echo "\nCMS yang TERTINGGAL (versi lama, berkas hilang, sisa yang harus dihapus)\n";
$B = "$tmp/cmsB"; $mkCms($B);
foreach ($kitFiles as $rel => $h) { put("$B/$rel", file_get_contents("$root/$rel")); }
put("$B/$tocRel", "<?php /* TOC versi LAMA dari CMS: [cite: 1] */\n");
$older = ['app/Content/PublicLookup.php', 'app/Content/Blocks/BlockSanitizer.php', 'app/Content/Slug.php', 'app/Content/ContentRules.php', 'app/Content/Links/LinkResolver.php', 'resources/views/components/content/body.blade.php'];
foreach ($older as $rel) { put("$B/$rel", file_get_contents("$B/$rel") . "\n// versi lama\n"); }
$missing = ['app/Content/Sitemap.php', 'app/Content/Blocks/RichText.php', 'app/Content/Blocks/CoreBlocks.php', 'app/Console/Commands/AuditSlugs.php'];
foreach ($missing as $rel) { unlink("$B/$rel"); }
put("$B/app/Content/Rules/UniqueSlug.php", "<?php // sisa lama\n"); put("$B/resources/views/components/editor/outline-sementara.blade.php", "<div>sisa</div>\n");
$oldContents = []; foreach (array_merge($older, [$tocRel, 'app/Content/Rules/UniqueSlug.php', 'resources/views/components/editor/outline-sementara.blade.php']) as $rel) { $oldContents[$rel] = file_get_contents("$B/$rel"); }
$beforeB = files($B);
[$c, $o] = run($root, [$B]);
check('rencana: 7 GANTI (6 berkas kit lama + daftar isi), 4 BARU, 2 HAPUS, sisanya sudah sama; tidak menulis', $c === 0 && str_contains($o, '4 baru, 7 diganti') && str_contains($o, '2 dihapus') && files($B) === $beforeB, $o);
check('rencana menyebut setiap berkas GANTI dan HAPUS dengan namanya, dan memberi catatan untuk daftar isi', str_contains($o, 'GANTI  app/Content/PublicLookup.php') && str_contains($o, 'GANTI  resources/views/components/table-of-contents.blade.php') && str_contains($o, 'celah keamanan') && str_contains($o, 'HAPUS  app/Content/Rules/UniqueSlug.php') && str_contains($o, 'HAPUS  resources/views/components/editor/outline-sementara.blade.php'));
[$c, $o] = run($root, [$B, '--apply']);
$bk = glob("$B/storage/pasang-cadangan/*", GLOB_ONLYDIR) ?: [];
check('--apply: "SELESAI: 11 berkas dipasang, 2 dihapus"; tepat satu folder cadangan', $c === 0 && str_contains($o, 'SELESAI: 11 berkas dipasang, 2 dihapus') && count($bk) === 1, $o);
$okBk = count($bk) === 1; foreach ($oldContents as $rel => $content) { $okBk = $okBk && @file_get_contents("$bk[0]/$rel") === $content; }
check('cadangan berisi ISI LAMA dari kesembilan berkas (6 versi lama, daftar isi lama, dan kedua sisa yang dihapus) pada jalur yang sama', $okBk && count(files($bk[0])) === 9);
$bad = []; foreach ($kitFiles as $rel => $h) { if (@md5_file("$B/$rel") !== $h) { $bad[] = $rel; } }
check('sesudahnya semua berkas kit identik dengan sumber, daftar isi = perbaikan, kedua sisa lama sudah terhapus', $bad === [] && md5_file("$B/$tocRel") === md5_file("$root/landing-app/$tocRel") && !is_file("$B/app/Content/Rules/UniqueSlug.php") && !is_file("$B/resources/views/components/editor/outline-sementara.blade.php"), implode(',', $bad));
check('berkas milik Anda tetap utuh walau CMS sedang diperbarui (termasuk renderer heading/paragraph Anda yang sebaris dengan renderer kit)', $mineIntact($B));

echo "\nPengaman: berkas milik Anda dan jalur berbahaya\n";
$kitCopy = "$tmp/kitcopy"; mkdir($kitCopy);
$mkKit = function () use ($kitCopy, $root, $tocRel) { rrm($kitCopy); mkdir($kitCopy); foreach (['tools', 'app', 'resources', 'database'] as $d) { copyTree("$root/$d", "$kitCopy/$d"); } put("$kitCopy/landing-app/$tocRel", file_get_contents("$root/landing-app/$tocRel")); put("$kitCopy/tests/blade-scan.php", file_get_contents("$root/tests/blade-scan.php")); put("$kitCopy/HAPUS.txt", "app/Content/Rules/UniqueSlug.php\n"); };
$C = "$tmp/cmsC"; $mkCms($C); $beforeC = files($C);
foreach (['app/Models/Page.php', 'app/Providers/X.php', 'database/seeders/DatabaseSeeder.php', 'app/Models/User.php', 'app/Http/Controllers/Y.php'] as $collide) {
    $mkKit(); put("$kitCopy/$collide", "<?php // dari kit\n");
    [$c, $o] = run($kitCopy, [$C, '--apply']);
    check("kit yang memuat berkas ber-jalur MILIK ANDA ($collide): kode 2, menyebut DILINDUNGI, CMS tidak berubah", $c === 2 && str_contains($o, 'DILINDUNGI') && str_contains($o, $collide) && files($C) === $beforeC && $mineIntact($C), $o);
}
foreach (['../luar.php', 'routes/web.php', '.env', 'app/Models/Page.php', 'config/cms.php', '/etc/passwd', 'C:/x.php'] as $evil) {
    $mkKit(); put("$kitCopy/HAPUS.txt", "app/Content/Rules/UniqueSlug.php\n$evil\n");
    [$c, $o] = run($kitCopy, [$C, '--apply']);
    check("HAPUS.txt memuat jalur berbahaya/dilindungi ($evil): kode 2; berkas Anda TIDAK dihapus; CMS tidak berubah", $c === 2 && str_contains($o, 'HAPUS.txt') && files($C) === $beforeC && $mineIntact($C), $o);
}
$mkKit(); put("$kitCopy/HAPUS.txt", "# komentar\n\napp/Content/Rules/TidakAda.php\n");
[$c, $o] = run($kitCopy, [$C]);
check('HAPUS.txt dengan berkas yang tidak ada di CMS: diabaikan diam-diam (tidak menuduh), komentar dan baris kosong dilewati', $c === 0 && !str_contains($o, 'HAPUS  ') && str_contains($o, '0 dihapus'), $o);

echo "\nPenolakan sebelum menulis\n";
@mkdir("$tmp/kosong", 0775, true);
$kitLaravel = "$tmp/kit-laravel"; $mkKit(); rrm($kitLaravel); mkdir($kitLaravel); foreach (['tools', 'app', 'resources', 'database'] as $d) { copyTree("$kitCopy/$d", "$kitLaravel/$d"); } put("$kitLaravel/artisan", '<?php'); put("$kitLaravel/composer.json", '{}'); put("$kitLaravel/HAPUS.txt", ''); put("$kitLaravel/landing-app/$tocRel", 'x'); put("$kitLaravel/tests/blade-scan.php", '<?php'); project("$kitLaravel/di-dalam");
foreach ([['CMS bukan proyek Laravel', $root, ["$tmp/kosong"], 'bukan proyek Laravel'], ['CMS tidak ada', $root, ["$tmp/tidak-ada"], 'tidak ditemukan'], ['CMS = folder kit (kit tiruan yang lolos pemeriksaan Laravel)', $kitLaravel, [$kitLaravel], 'tidak boleh sama dengan'], ['CMS di DALAM folder kit', $kitLaravel, ["$kitLaravel/di-dalam"], 'tidak boleh sama dengan'], ['tanpa argumen', $root, [], 'Pemakaian'], ['argumen berlebih', $root, [$C, "$tmp/x"], 'Pemakaian']] as [$label, $kitDir, $a, $needle]) {
    [$c, $o] = run($kitDir, array_merge($a, ['--apply']));
    check("ditolak (kode 2) oleh penjaga yang tepat: $label", $c === 2 && str_contains($o, $needle), "kode $c :: $o");
}
$D = "$tmp/cmsD"; $mkCms($D); $outside = "$tmp/di-luar"; mkdir($outside);
$canLink = @symlink(__FILE__, "$tmp/probe-link");   // kemampuan tautan simbolik diperiksa TERPISAH (Windows tanpa izin tidak bisa)
@unlink("$tmp/probe-link");
if ($canLink) {
    // folder yang ditulis kit dan belum dibuat fixture: resources/views/components/editor dijadikan tautan ke luar
    @mkdir("$D/resources/views/components", 0775, true);
    $made = symlink($outside, "$D/resources/views/components/editor");
    check('(syarat uji) tautan simbolik folder berhasil dibuat; bila gagal, uji ini GAGAL (tidak dilewati diam-diam)', $made);
    $snapD = files($D); [$c, $o] = run($root, [$D, '--apply']);
    check('folder di dalam CMS berupa tautan simbolik ke luar: kode 2 SEBELUM menulis; tidak ada yang tertulis di luar maupun di dalam CMS', $c === 2 && str_contains($o, 'di luar folder CMS') && files($outside) === [] && files($D) === $snapD, $o);
    unlink("$D/resources/views/components/editor"); put("$outside/x.txt", 'luar');
    @mkdir("$D/app/Content", 0775, true);
    $made2 = symlink("$outside/x.txt", "$D/app/Content/PublicLookup.php");
    check('(syarat uji) tautan simbolik berkas berhasil dibuat', $made2);
    [$c, $o] = run($root, [$D, '--apply']);
    check('berkas tujuan berupa tautan simbolik ke luar: ditolak; berkas di luar tidak tertimpa', $c === 2 && str_contains($o, 'tautan simbolik') && file_get_contents("$outside/x.txt") === 'luar', $o);
} else {
    echo "  (dilewati: sistem ini tidak mengizinkan membuat tautan simbolik; pengaman itu tidak teruji di sini)\n";
}

echo "\nKegagalan saat menulis: cadangan dulu, baru mengganti\n";
$E = "$tmp/cmsE"; $mkCms($E);
foreach ($kitFiles as $rel => $h) { put("$E/$rel", file_get_contents("$root/$rel")); }
put("$E/app/Content/PublicLookup.php", "<?php // lama\n"); put("$E/app/Content/Rules/UniqueSlug.php", "<?php // sisa\n"); put("$E/storage/pasang-cadangan", 'ini berkas, bukan folder'); unlink("$E/app/Content/Sitemap.php");
$snapE = files($E);
[$c, $o] = run($root, [$E, '--apply']);
check('folder cadangan tidak bisa dibuat: kode 1; berkas yang akan diganti, berkas yang akan dihapus, dan berkas baru: SEMUANYA tidak berubah', $c === 1 && str_contains($o, 'Gagal membuat folder cadangan') && str_contains($o, 'Tidak ada berkas CMS yang diganti atau dihapus') && files($E) === $snapE && is_file("$E/app/Content/Rules/UniqueSlug.php") && !is_file("$E/app/Content/Sitemap.php"), $o);

rrm($tmp);
echo "\n==> $pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
