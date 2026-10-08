<?php
/**
 * Menyiapkan LAB pengujian (sekali saja):  php tests/lab/setup.php [--force]
 *
 * Mengunduh dua pustaka sumber terbuka dengan versi TERTENTU dari GitHub ke tests/lab/_src (tidak menyentuh proyek Anda, tidak memakai
 * Composer): Laravel 13.30.0 (hanya untuk Eloquent, validator, view; bukan aplikasi) dan Carbon 3.10.0.
 * Butuh: koneksi internet; untuk mengunduh curl ATAU (allow_url_fopen + openssl); untuk mengekstrak SALAH SATU dari: ekstensi zip, ekstensi
 * phar + zlib, atau perintah `tar` (Windows 10+ punya). Aman dijalankan ulang (melewati yang sudah ada).
 */
$force = in_array('--force', $argv, true);
$dest = __DIR__ . '/_src';
$libs = [
    ['nama' => 'Laravel 13.30.0', 'url' => 'https://codeload.github.com/laravel/framework/%s/refs/tags/v13.30.0', 'dari' => 'framework-13.30.0', 'ke' => 'framework-13.30.0', 'tanda' => 'framework-13.30.0/src/Illuminate/Database/Eloquent/Model.php'],
    ['nama' => 'Carbon 3.10.0', 'url' => 'https://codeload.github.com/CarbonPHP/carbon/%s/refs/tags/3.10.0', 'dari' => 'carbon-3.10.0', 'ke' => 'Carbon-3.10.0', 'tanda' => 'Carbon-3.10.0/src/Carbon/Carbon.php'],
];

function fail(string $msg): never { fwrite(STDERR, "GAGAL: $msg\n"); exit(1); }
function download(string $url): string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 300, CURLOPT_USERAGENT => 'ysbh-kit-lab']);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($data === false || $code !== 200) { fail("unduh $url (HTTP $code) $err"); }

        return $data;
    }
    $ctx = stream_context_create(['http' => ['follow_location' => 1, 'timeout' => 300, 'user_agent' => 'ysbh-kit-lab']]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) { fail("unduh $url (aktifkan ekstensi curl, atau allow_url_fopen dan openssl)"); }

    return $data;
}
function rmrf(string $dir): void
{
    if (!is_dir($dir)) { return; }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

// Cara mengekstrak, urut: ZipArchive -> PharData (tar.gz) -> perintah `tar`
$cara = class_exists('ZipArchive') ? 'zip' : ((class_exists('PharData') && extension_loaded('zlib')) ? 'phar' : 'tar');
if (in_array(getenv('LAB_EXTRACT'), ['zip', 'phar', 'tar'], true)) { $cara = getenv('LAB_EXTRACT'); }   // penimpa (untuk menguji jalur cadangan)
if ($cara === 'zip' && !class_exists('ZipArchive')) { fail('LAB_EXTRACT=zip tetapi ekstensi zip tidak aktif'); }
if ($cara === 'phar' && !(class_exists('PharData') && extension_loaded('zlib'))) { fail('LAB_EXTRACT=phar tetapi ekstensi phar dan zlib tidak lengkap'); }
if ($cara === 'tar') {
    exec('tar --version 2>&1', $o, $c);
    if ($c !== 0) { fail('tidak ada cara mengekstrak: aktifkan ekstensi zip, atau phar dan zlib, atau pasang perintah tar'); }
}
function extractArchive(string $cara, string $file, string $to): void
{
    @mkdir($to, 0777, true);
    if ($cara === 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) { fail('berkas unduhan bukan zip yang sah'); }
        $zip->extractTo($to);
        $zip->close();
    } elseif ($cara === 'phar') {
        try { (new PharData($file))->extractTo($to, null, true); } catch (Throwable $e) { fail('ekstrak tar.gz: ' . $e->getMessage()); }
    } else {
        exec('tar -xzf ' . escapeshellarg($file) . ' -C ' . escapeshellarg($to) . ' 2>&1', $out, $code);
        if ($code !== 0) { fail('perintah tar gagal: ' . implode(' ', $out)); }
    }
}
if (!is_dir($dest) && !mkdir($dest, 0777, true)) { fail("tidak bisa membuat $dest"); }
echo "Cara ekstrak: $cara\n";

foreach ($libs as $lib) {
    if (!$force && is_file("$dest/{$lib['tanda']}")) { echo "ada      {$lib['nama']}\n"; continue; }
    echo "mengunduh {$lib['nama']} ... ";
    // nama berkas UNIK per pustaka: PharData menyimpan arsip yang sudah dibuka menurut nama berkasnya, sehingga nama yang sama membuat
    // unduhan kedua mengekstrak ulang arsip pertama
    $archive = "$dest/_unduhan-" . substr(md5($lib['url']), 0, 8) . '.' . ($cara === 'zip' ? 'zip' : 'tar.gz');
    file_put_contents($archive, download(sprintf($lib['url'], $cara === 'zip' ? 'zip' : 'tar.gz')));
    $tmp = "$dest/_tmp";
    rmrf($tmp);
    extractArchive($cara, $archive, $tmp);
    unlink($archive);
    if (!is_dir("$tmp/{$lib['dari']}")) { fail("isi unduhan tidak berisi {$lib['dari']} (struktur arsip berubah?)"); }
    rmrf("$dest/{$lib['ke']}");
    rename("$tmp/{$lib['dari']}", "$dest/{$lib['ke']}");
    rmrf($tmp);
    if (!is_file("$dest/{$lib['tanda']}")) { fail("berkas penanda {$lib['tanda']} tidak ditemukan sesudah ekstrak"); }
    echo "selesai\n";
}
@unlink(__DIR__ . '/_cache/classmap.json');   // peta kelas dibuat ulang pada pemakaian berikutnya
echo "\nLab siap. Jalankan semua pengujian: php tests/run-all.php\n";
