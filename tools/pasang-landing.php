<?php
/**
 * Memasang SEMUA berkas yang dibutuhkan landing dengan satu perintah.
 *
 *   php tools/pasang-landing.php <folder-landing> <folder-cms>            LIHAT rencana saja (tidak mengubah apa pun)
 *   php tools/pasang-landing.php <folder-landing> <folder-cms> --apply    pasang
 *
 * Tiga kelompok berkas:
 *   A. dari kit      : berkas bersama CMS dan landing (daftar: landing-files.txt)
 *   B. dari landing-app/ : berkas khusus landing (rute, model, halaman, header, dst.)
 *   C. dari CMS Anda : tujuh renderer blok inti (resources/views/components/blocks/render/*.blade.php)
 * <folder-cms> boleh dihilangkan: kelompok C dilewati dan Anda diberi tahu.
 *
 * PENGAMAN
 *  - Bawaannya hanya MELIHAT. Tidak ada yang ditulis tanpa --apply.
 *  - Semua pemeriksaan dilakukan SEBELUM menulis; bila ada satu masalah, tidak ada yang berubah (tidak pernah setengah jadi).
 *  - Berkas yang sudah ada dan BERBEDA dicadangkan dulu ke <landing>/storage/pasang-cadangan/<tanggal-jam>/ (jalur yang sama), lalu diganti.
 *    Berkas yang SAMA tidak disentuh. .env, vendor, node_modules, dan database tidak pernah disentuh.
 *  - Renderer dari CMS yang ternyata komponen EDITOR (bukan tampilan publik) ditolak.
 *  - Setelah selesai, pemeriksa (tools/check-landing.php) dijalankan otomatis.
 *
 * Kode keluar: 0 = selesai/rencana dibuat; 1 = gagal saat menulis; 2 = pemakaian salah atau pemeriksaan awal menolak.
 */
$args = array_slice($argv, 1);
$apply = in_array('--apply', $args, true);
$pos = array_values(array_filter($args, fn ($a) => !str_starts_with($a, '--')));
$kit = realpath(dirname(__DIR__));

$fail = function (string $msg, int $code = 2): never {
    fwrite(STDERR, "\n$msg\n");
    exit($code);
};
$usage = "Pemakaian:\n  php tools/pasang-landing.php <folder-landing> <folder-cms>            (melihat rencana)\n  php tools/pasang-landing.php <folder-landing> <folder-cms> --apply    (memasang)\n\nContoh:\n  php tools/pasang-landing.php C:\\proyek\\landing-ysbh C:\\proyek\\cms-ysbh";
if (count($pos) < 1 || count($pos) > 2) {
    $fail($usage);
}

$isLaravel = fn (string $d) => is_file("$d/artisan") && is_file("$d/composer.json");
$landing = realpath($pos[0]);
if ($landing === false || !is_dir($landing)) {
    $fail("Folder landing tidak ditemukan: {$pos[0]}\n\n$usage");
}
if (!$isLaravel($landing)) {
    $fail("Folder landing bukan proyek Laravel (tidak ada artisan dan composer.json): $landing\nPastikan Anda menunjuk folder utama proyek landing.");
}
$norm = fn (string $p) => rtrim(str_replace('\\', '/', $p), '/') . '/';
if ($norm($landing) === $norm($kit) || str_starts_with($norm($landing), $norm($kit))) {
    $fail("Folder landing tidak boleh sama dengan, atau berada di dalam, folder kit ini:\n  kit    : $kit\n  landing: $landing");
}
$cms = null;
if (isset($pos[1])) {
    $cms = realpath($pos[1]);
    if ($cms === false || !is_dir($cms)) {
        $fail("Folder CMS tidak ditemukan: {$pos[1]}");
    }
    if (!$isLaravel($cms)) {
        $fail("Folder CMS bukan proyek Laravel (tidak ada artisan dan composer.json): $cms");
    }
    if ($norm($cms) === $norm($landing)) {
        $fail("Folder CMS dan landing sama. Keduanya harus dua proyek yang berbeda:\n  $landing");
    }
}

$safeRel = fn (string $r) => $r !== '' && !str_contains($r, '..') && !str_contains($r, "\0") && !str_contains($r, '\\') && $r[0] !== '/' && !preg_match('/^[A-Za-z]:/', $r);

// ------------------------------------------------------------------ rencana
/** @var list<array{g:string,src:string,rel:string}> $plan */
$plan = [];
$problems = [];

foreach (preg_split('/\R/', (string) file_get_contents("$kit/landing-files.txt")) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
        continue;
    }
    if (!$safeRel($line) || !is_file("$kit/$line")) {
        $problems[] = "A: berkas kit tidak ada atau jalurnya tidak aman: $line";
        continue;
    }
    $plan[] = ['g' => 'A', 'src' => "$kit/$line", 'rel' => $line];
}
if (is_dir("$kit/landing-app")) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$kit/landing-app", FilesystemIterator::SKIP_DOTS));
    $found = [];
    foreach ($it as $f) {
        if ($f->isFile()) {
            $found[] = str_replace('\\', '/', substr($f->getPathname(), strlen("$kit/landing-app") + 1));
        }
    }
    sort($found);
    foreach ($found as $rel) {
        $plan[] = ['g' => 'B', 'src' => "$kit/landing-app/$rel", 'rel' => $rel];
    }
} else {
    $problems[] = 'B: folder landing-app/ tidak ada di kit ini.';
}
$renderTypes = ['heading', 'paragraph', 'eyebrow', 'image', 'card-builder', 'step-group', 'multi-columns'];
if ($cms !== null) {
    foreach ($renderTypes as $t) {
        $rel = "resources/views/components/blocks/render/$t.blade.php";
        $src = "$cms/$rel";
        if (!is_file($src)) {
            $problems[] = "C: tidak ada di CMS: $rel\n     (pastikan ini folder 'render', bukan 'blocks' atau 'blocks/editor')";
            continue;
        }
        $code = (string) file_get_contents($src);
        if (str_contains($code, 'x-blocks.editor') || str_contains($code, 'x-editor.') || preg_match('/@props\s*\(\s*\[[^\]]*["\'](?:blockId|activeLocales)["\']/s', $code)) {
            $problems[] = "C: $rel di CMS adalah komponen EDITOR, bukan tampilan publik. Yang dibutuhkan ada di folder resources/views/components/blocks/render/.";
            continue;
        }
        if (trim($code) === '') {
            $problems[] = "C: berkas kosong di CMS: $rel";
            continue;
        }
        $plan[] = ['g' => 'C', 'src' => $src, 'rel' => $rel];
    }
}
$seen = [];
foreach ($plan as $it2) {
    if (isset($seen[$it2['rel']])) {
        $problems[] = "Bentrok: {$it2['rel']} muncul di dua kelompok ({$seen[$it2['rel']]} dan {$it2['g']}).";
    }
    $seen[$it2['rel']] = $it2['g'];
}
// Tujuan HARUS tetap di dalam folder landing, juga bila ada tautan simbolik di dalamnya: diperiksa SEBELUM menulis apa pun.
foreach ($plan as $it4) {
    $dest = "$landing/{$it4['rel']}";
    if (is_link($dest)) {
        $problems[] = "Tujuan adalah tautan simbolik (tidak akan ditimpa): {$it4['rel']}";
        continue;
    }
    $anc = dirname($dest);
    while (!is_dir($anc) && dirname($anc) !== $anc) {
        $anc = dirname($anc);
    }
    $realAnc = realpath($anc);
    if ($realAnc === false || !str_starts_with($norm($realAnc), $norm($landing))) {
        $problems[] = "Tujuan berada di luar folder landing (folder di dalam landing menunjuk ke tempat lain): {$it4['rel']}";
    }
}
foreach ($plan as &$it3) {
    $dest = "$landing/{$it3['rel']}";
    $it3['status'] = !is_file($dest) ? 'BARU' : (hash_file('sha256', $dest) === hash_file('sha256', $it3['src']) ? 'SAMA' : 'GANTI');
}
unset($it3);

if ($problems) {
    echo "\nTIDAK ADA YANG DIUBAH. Ada masalah yang harus dibereskan dulu:\n";
    foreach ($problems as $p) {
        echo "  - $p\n";
    }
    exit(2);
}

// ------------------------------------------------------------------ tampilkan rencana
$names = ['A' => 'A. Berkas bersama dari kit', 'B' => 'B. Berkas khusus landing (landing-app/)', 'C' => 'C. Renderer blok dari CMS Anda'];
echo "\n" . ($apply ? 'MEMASANG' : 'RENCANA (belum mengubah apa pun)') . "\n  landing: $landing\n  CMS    : " . ($cms ?? '(tidak diberikan)') . "\n";
$counts = ['BARU' => 0, 'GANTI' => 0, 'SAMA' => 0];
foreach (['A', 'B', 'C'] as $g) {
    $items = array_values(array_filter($plan, fn ($i) => $i['g'] === $g));
    if (!$items && $g === 'C') {
        echo "\n{$names[$g]}\n  DILEWATI: folder CMS tidak diberikan. Tambahkan jalur CMS di perintah agar tujuh renderer ikut disalin.\n";
        continue;
    }
    $c = ['BARU' => 0, 'GANTI' => 0, 'SAMA' => 0];
    foreach ($items as $i) { $c[$i['status']]++; $counts[$i['status']]++; }
    echo "\n{$names[$g]}: " . count($items) . " berkas ({$c['BARU']} baru, {$c['GANTI']} diganti, {$c['SAMA']} sudah sama)\n";
    foreach ($items as $i) {
        if ($i['status'] === 'GANTI') {
            echo "  GANTI  {$i['rel']}\n";                       // selalu ditampilkan: berkas yang sudah ada dan akan diganti
        } elseif ($i['status'] === 'BARU' && $g !== 'A') {
            echo "  BARU   {$i['rel']}\n";                       // kelompok A (47 berkas) hanya dihitung; daftarnya ada di landing-files.txt
        }
    }
}
$todo = array_values(array_filter($plan, fn ($i) => $i['status'] !== 'SAMA'));
echo "\nRingkasan: {$counts['BARU']} baru, {$counts['GANTI']} diganti, {$counts['SAMA']} sudah sama (tidak disentuh).\n";
if (!$todo) {
    echo "Landing sudah sama dengan kit: tidak ada yang perlu dipasang.\n";
}
if (!$apply) {
    if ($todo) {
        echo "\nIni baru rencana. Berkas yang \"GANTI\" akan dicadangkan lebih dulu.\nUntuk memasang, ulangi perintah yang sama dengan tambahan  --apply\n";
    }
    exit(0);
}

// ------------------------------------------------------------------ pasang
$backupRoot = "$landing/storage/pasang-cadangan/" . date('Ymd-His');
$written = 0;
$backedUp = 0;
foreach ($todo as $i) {
    $dest = "$landing/{$i['rel']}";
    if ($i['status'] === 'GANTI') {
        $b = "$backupRoot/{$i['rel']}";
        if (!is_dir(dirname($b)) && !mkdir(dirname($b), 0777, true)) {
            $fail("Gagal membuat folder cadangan: " . dirname($b) . "\nTidak ada berkas landing yang diganti.", 1);
        }
        if (!copy($dest, $b)) {
            $fail("Gagal mencadangkan {$i['rel']}. Pemasangan dihentikan sebelum mengganti apa pun.", 1);
        }
        $backedUp++;
    }
}
foreach ($todo as $i) {
    $dest = "$landing/{$i['rel']}";
    $dir = dirname($dest);
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
        $fail("Gagal membuat folder: $dir\nSebagian berkas mungkin sudah terpasang; cadangan ada di $backupRoot", 1);
    }
    $realDir = realpath($dir);
    if ($realDir === false || !str_starts_with($norm($realDir), $norm($landing))) {
        $fail("Tujuan di luar folder landing ditolak: {$i['rel']}", 1);
    }
    if (!copy($i['src'], $dest) || hash_file('sha256', $dest) !== hash_file('sha256', $i['src'])) {
        $fail("Gagal menyalin {$i['rel']} (atau isinya tidak cocok sesudah disalin).\nCadangan ada di $backupRoot", 1);
    }
    $written++;
}
if ($written > 0) {
    echo "\nSELESAI: $written berkas dipasang" . ($backedUp ? ", $backedUp berkas lama dicadangkan di:\n  $backupRoot\n  (untuk membatalkan: salin isi folder cadangan itu kembali ke proyek landing)" : '.') . "\n";
}

// ------------------------------------------------------------------ pemeriksa
echo "\nMemeriksa hasilnya (tools/check-landing.php)...\n";
$out = [];
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$kit/tools/check-landing.php") . ' ' . escapeshellarg($landing) . ' 2>&1', $out, $code);
foreach ($out as $l) {
    if (preg_match('/GALAT|PERINGATAN|==>/', $l)) {
        echo "  $l\n";
    }
}
echo $code === 0 ? "  Pemeriksa: tidak ada galat.\n" : "  Pemeriksa menemukan galat (lihat baris GALAT di atas). Kirimkan keluaran ini bila Anda tidak yakin.\n";
echo "\nLangkah berikutnya, di folder proyek landing:\n  php artisan view:clear\n  npm run build\nLalu periksa .env (APP_URL) dan config/app.php ('supported_locales' => ['en', 'id']). Panduan: docs/PASANG-LANDING.md\n";
exit(0);
