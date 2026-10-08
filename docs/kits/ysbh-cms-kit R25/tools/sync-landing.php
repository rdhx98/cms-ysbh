<?php
/**
 * Menyinkronkan berkas kit yang dibutuhkan situs PUBLIK ke proyek landing (aplikasi terpisah dari CMS).
 *
 *   php tools/sync-landing.php <folder-landing>            PERIKSA saja: tampilkan berkas yang baru/berbeda (tidak mengubah apa pun)
 *   php tools/sync-landing.php <folder-landing> --apply    salin (timpa) berkas yang baru/berbeda
 *
 * Daftar berkas: landing-files.txt (dibuat php tools/landing-files.php --write). Kode keluar 0 = landing sudah sama dengan kit;
 * 1 = ada yang berbeda (mode periksa) atau gagal; 2 = pemakaian salah / folder bukan proyek Laravel.
 * Opsi: --kit=<folder> memakai folder kit lain (untuk pengujian).
 *
 * PENGAMAN: folder tujuan harus berisi `artisan` dan `composer.json` (proyek Laravel); jalur di daftar harus relatif dan tanpa "..";
 * berkas yang akan ditimpa dan berbeda ditampilkan lebih dulu (mode periksa adalah bawaan). Berkas yang SAMA tidak disentuh.
 * Catatan: berkas milik landing sendiri yang Anda sunting dengan sengaja dan ada di daftar akan ditimpa dengan --apply; periksa dulu.
 */
$args = array_slice($argv, 1);
$apply = in_array('--apply', $args, true);
$kit = null;
$target = null;
foreach ($args as $a) {
    if (str_starts_with($a, '--kit=')) {
        $kit = substr($a, 6);
    } elseif (!str_starts_with($a, '--') && $target === null) {
        $target = $a;
    }
}
$kit = realpath($kit ?? dirname(__DIR__));

$fail = function (string $msg, int $code = 2) {
    fwrite(STDERR, "$msg\n");
    exit($code);
};
if ($target === null || $kit === false) {
    $fail("Pemakaian: php tools/sync-landing.php <folder-landing> [--apply]");
}
$target = realpath($target);
if ($target === false || !is_dir($target)) {
    $fail("Folder landing tidak ditemukan.");
}
if (!is_file("$target/artisan") || !is_file("$target/composer.json")) {
    $fail("Ditolak: '$target' bukan proyek Laravel (tidak ada artisan/composer.json). Tidak ada yang diubah.");
}
if ($target === $kit) {
    $fail("Ditolak: folder landing sama dengan folder kit.");
}
$listFile = "$kit/landing-files.txt";
if (!is_file($listFile)) {
    $fail("landing-files.txt tidak ada di $kit (jalankan: php tools/landing-files.php --write).");
}

$paths = [];
foreach (preg_split('/\R/', (string) file_get_contents($listFile)) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
        continue;
    }
    if (str_contains($line, '..') || str_starts_with($line, '/') || str_starts_with($line, '\\') || preg_match('/^[A-Za-z]:/', $line) || str_contains($line, "\0") || !preg_match('#^(app|resources)/#', $line)) {
        $fail("Ditolak: jalur tidak aman di landing-files.txt: $line");
    }
    $paths[] = $line;
}

$new = $diff = $same = $missingInKit = [];
foreach ($paths as $rel) {
    $src = "$kit/$rel";
    $dst = $target . '/' . $rel;
    if (!is_file($src)) {
        $missingInKit[] = $rel;
    } elseif (!is_file($dst)) {
        $new[] = $rel;
    } elseif (hash_file('sha256', $src) !== hash_file('sha256', $dst)) {
        $diff[] = $rel;
    } else {
        $same[] = $rel;
    }
}
if ($missingInKit) {
    $fail("Daftar menyebut berkas yang tidak ada di kit:\n  " . implode("\n  ", $missingInKit), 1);
}

foreach ($new as $r) echo "  BARU      $r\n";
foreach ($diff as $r) echo "  BERBEDA   $r\n";
$todo = array_merge($new, $diff);

if (!$apply) {
    echo "\nPeriksa: " . count($same) . " sama, " . count($new) . " baru, " . count($diff) . " berbeda (dari " . count($paths) . " berkas). Tidak ada yang diubah.\n";
    if ($todo) {
        echo "Untuk menyalin: php tools/sync-landing.php \"$target\" --apply\n";
    }
    exit($todo ? 1 : 0);
}

foreach ($todo as $rel) {
    $dst = $target . '/' . $rel;
    if (!is_dir(dirname($dst)) && !mkdir(dirname($dst), 0775, true) && !is_dir(dirname($dst))) {
        $fail("Gagal membuat folder untuk $rel", 1);
    }
    if (!copy("$kit/$rel", $dst) || hash_file('sha256', "$kit/$rel") !== hash_file('sha256', $dst)) {
        $fail("Gagal menyalin $rel", 1);
    }
}
echo "\nDisalin: " . count($new) . " baru + " . count($diff) . " diperbarui; " . count($same) . " sudah sama (tidak disentuh).\n";
echo "Berikutnya di landing: php artisan view:clear\n";
exit(0);
