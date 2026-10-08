<?php
/**
 * Memperbarui aplikasi CMS dari kit dengan satu perintah.
 *
 *   php tools/pasang-cms.php <folder-cms>            LIHAT rencana saja (tidak mengubah apa pun)
 *   php tools/pasang-cms.php <folder-cms> --apply    pasang
 *
 * Yang dipasang ke CMS:
 *   K. semua berkas kit di app/, resources/, dan database/ (kode, editor, tampilan, migrasi milik kit)
 *   T. table-of-contents.blade.php versi perbaikan (menggantikan versi lama di CMS: ada celah keamanan)
 *   H. berkas yang harus DIHAPUS (HAPUS.txt) bila ada di CMS
 * Tidak dipasang: landing-app/ (khusus landing), tests/, tools/, docs/, contoh-kode/, patch/, opsional/.
 * Hal yang harus Anda isi sendiri (tool ini TIDAK mengubah config/route/.env): lihat docs/PASANG-CMS.md.
 *
 * PENGAMAN (sama seperti pasang-landing.php, ditambah pengaman khusus CMS)
 *  - Bawaannya hanya MELIHAT. Tidak ada yang ditulis tanpa --apply.
 *  - Semua pemeriksaan SEBELUM menulis; satu masalah = tidak ada yang berubah.
 *  - Berkas yang sudah ada dan BERBEDA (dan yang akan dihapus) dicadangkan dulu ke <cms>/storage/pasang-cadangan/<tanggal-jam>/.
 *    Berkas yang SAMA tidak disentuh.
 *  - Berkas MILIK ANDA tidak pernah ditimpa walaupun suatu hari kit memuat berkas dengan jalur yang sama: model Anda (Page, Post, Category,
 *    User, Setting, Navigation, ...), app/Providers, routes, config, bootstrap, public, storage, .env, vendor, node_modules.
 *  - Setelah selesai, pemindai direktif Blade dijalankan pada CMS (menangkap bug "@context" pada JSON-LD).
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
$usage = "Pemakaian:\n  php tools/pasang-cms.php <folder-cms>            (melihat rencana)\n  php tools/pasang-cms.php <folder-cms> --apply    (memasang)\n\nContoh:\n  php tools/pasang-cms.php C:\\proyek\\cms-ysbh";
if (count($pos) !== 1) {
    $fail($usage);
}
$cms = realpath($pos[0]);
if ($cms === false || !is_dir($cms)) {
    $fail("Folder CMS tidak ditemukan: {$pos[0]}\n\n$usage");
}
if (!is_file("$cms/artisan") || !is_file("$cms/composer.json")) {
    $fail("Folder CMS bukan proyek Laravel (tidak ada artisan dan composer.json): $cms\nPastikan Anda menunjuk folder utama proyek CMS.");
}
$norm = fn (string $p) => rtrim(str_replace('\\', '/', $p), '/') . '/';
if ($norm($cms) === $norm($kit) || str_starts_with($norm($cms), $norm($kit))) {
    $fail("Folder CMS tidak boleh sama dengan, atau berada di dalam, folder kit ini:\n  kit: $kit\n  CMS: $cms");
}
$safeRel = fn (string $r) => $r !== '' && !str_contains($r, '..') && !str_contains($r, "\0") && !str_contains($r, '\\') && $r[0] !== '/' && !preg_match('/^[A-Za-z]:/', $r);

/** Jalur milik ANDA: tidak pernah ditimpa atau dihapus oleh alat ini. */
$protectedPrefix = ['app/Providers/', 'routes/', 'config/', 'bootstrap/', 'public/', 'storage/', 'vendor/', 'node_modules/', '.env', 'database/seeders/', 'database/factories/', 'app/Http/', 'lang/'];
$protectedModels = ['Page', 'Post', 'Category', 'User', 'Setting', 'Navigation', 'Tag', 'PlainTag'];
$isProtected = function (string $rel) use ($protectedPrefix, $protectedModels): bool {
    foreach ($protectedPrefix as $p) {
        if ($rel === rtrim($p, '/') || str_starts_with($rel, $p)) {
            return true;
        }
    }
    foreach ($protectedModels as $m) {
        if ($rel === "app/Models/$m.php") {
            return true;
        }
    }

    return false;
};

// ------------------------------------------------------------------ rencana
$problems = [];
/** @var list<array{g:string,src:string,rel:string}> $plan */
$plan = [];
foreach (['app', 'resources', 'database'] as $top) {
    if (!is_dir("$kit/$top")) {
        $problems[] = "K: folder $top/ tidak ada di kit ini.";
        continue;
    }
    $found = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$kit/$top", FilesystemIterator::SKIP_DOTS)) as $f) {
        if ($f->isFile()) {
            $found[] = $top . '/' . str_replace('\\', '/', substr($f->getPathname(), strlen("$kit/$top") + 1));
        }
    }
    sort($found);
    foreach ($found as $rel) {
        if (!$safeRel($rel)) {
            $problems[] = "K: jalur tidak aman di kit: $rel";
        } elseif ($isProtected($rel)) {
            $problems[] = "K: kit memuat berkas dengan jalur yang DILINDUNGI (milik Anda): $rel. Alat ini menolak menimpanya; pasang manual setelah membandingkannya.";
        } else {
            $plan[] = ['g' => 'K', 'src' => "$kit/$rel", 'rel' => $rel];
        }
    }
}
$tocSrc = "$kit/landing-app/resources/views/components/table-of-contents.blade.php";
if (!is_file($tocSrc)) {
    $problems[] = 'T: table-of-contents.blade.php perbaikan tidak ada di landing-app/ pada kit ini.';
} else {
    $plan[] = ['g' => 'T', 'src' => $tocSrc, 'rel' => 'resources/views/components/table-of-contents.blade.php'];
}
$seen = [];
foreach ($plan as $it) {
    if (isset($seen[$it['rel']])) {
        $problems[] = "Bentrok: {$it['rel']} muncul di dua kelompok ({$seen[$it['rel']]} dan {$it['g']}).";
    }
    $seen[$it['rel']] = $it['g'];
}
// berkas yang harus dihapus
$remove = [];
foreach (preg_split('/\R/', (string) @file_get_contents("$kit/HAPUS.txt")) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
        continue;
    }
    if (!$safeRel($line) || $isProtected($line)) {
        $problems[] = "H: HAPUS.txt memuat jalur yang tidak aman atau dilindungi: $line";
    } elseif (is_file("$cms/$line") || is_link("$cms/$line")) {
        $remove[] = $line;
    }
}
// tujuan harus tetap di dalam CMS (tautan simbolik), diperiksa SEBELUM menulis
foreach (array_merge(array_column($plan, 'rel'), $remove) as $rel) {
    $dest = "$cms/$rel";
    if (is_link($dest)) {
        $problems[] = "Tujuan adalah tautan simbolik (tidak akan ditimpa atau dihapus): $rel";
        continue;
    }
    $anc = dirname($dest);
    while (!is_dir($anc) && dirname($anc) !== $anc) {
        $anc = dirname($anc);
    }
    $realAnc = realpath($anc);
    if ($realAnc === false || !str_starts_with($norm($realAnc), $norm($cms))) {
        $problems[] = "Tujuan berada di luar folder CMS (folder di dalam CMS menunjuk ke tempat lain): $rel";
    }
}
if ($problems) {
    echo "\nTIDAK ADA YANG DIUBAH. Ada masalah yang harus dibereskan dulu:\n";
    foreach ($problems as $p) {
        echo "  - $p\n";
    }
    exit(2);
}
foreach ($plan as &$it2) {
    $dest = "$cms/{$it2['rel']}";
    $it2['status'] = !is_file($dest) ? 'BARU' : (hash_file('sha256', $dest) === hash_file('sha256', $it2['src']) ? 'SAMA' : 'GANTI');
}
unset($it2);

// ------------------------------------------------------------------ tampilkan rencana
echo "\n" . ($apply ? 'MEMASANG KE CMS' : 'RENCANA (belum mengubah apa pun)') . "\n  CMS: $cms\n";
$c = ['BARU' => 0, 'GANTI' => 0, 'SAMA' => 0];
foreach ($plan as $i) { $c[$i['status']]++; }
$kCount = count(array_filter($plan, fn ($i) => $i['g'] === 'K'));
echo "\nBerkas kit untuk CMS: $kCount berkas + daftar isi perbaikan\n";
$newByDir = [];
foreach ($plan as $i) {
    if ($i['status'] === 'GANTI') {
        echo "  GANTI  {$i['rel']}" . ($i['g'] === 'T' ? '   (versi perbaikan: celah keamanan dan teks [cite: 1])' : '') . "\n";
    } elseif ($i['status'] === 'BARU') {
        $newByDir[dirname($i['rel'])] = ($newByDir[dirname($i['rel'])] ?? 0) + 1;
    }
}
if ($newByDir) {
    echo "  BARU (berkas yang belum ada di CMS), per folder:\n";
    ksort($newByDir);
    foreach ($newByDir as $d => $n) { echo "    $n berkas  $d/\n"; }
}
foreach ($remove as $r) { echo "  HAPUS  $r   (dicadangkan dulu)\n"; }
echo "\nRingkasan: {$c['BARU']} baru, {$c['GANTI']} diganti, {$c['SAMA']} sudah sama (tidak disentuh), " . count($remove) . " dihapus.\n";
$todo = array_values(array_filter($plan, fn ($i) => $i['status'] !== 'SAMA'));
if (!$todo && !$remove) {
    echo "CMS sudah sama dengan kit: tidak ada yang perlu dipasang.\n";
}
if (!$apply) {
    if ($todo || $remove) {
        echo "\nIni baru rencana. Berkas yang \"GANTI\" dan \"HAPUS\" dicadangkan lebih dulu.\nPERIKSA baris GANTI: bila ada berkas kit yang pernah Anda sunting sendiri di CMS, ia akan diganti (versi Anda aman di cadangan).\nUntuk memasang, ulangi perintah yang sama dengan tambahan  --apply\n";
    }
    exit(0);
}

// ------------------------------------------------------------------ pasang
$backupRoot = "$cms/storage/pasang-cadangan/" . date('Ymd-His');
$backedUp = 0;
foreach (array_merge(array_filter($todo, fn ($i) => $i['status'] === 'GANTI'), array_map(fn ($r) => ['rel' => $r], $remove)) as $i) {
    $b = "$backupRoot/{$i['rel']}";
    if (!is_dir(dirname($b)) && !mkdir(dirname($b), 0777, true)) {
        $fail("Gagal membuat folder cadangan: " . dirname($b) . "\nTidak ada berkas CMS yang diganti atau dihapus.", 1);
    }
    if (!copy("$cms/{$i['rel']}", $b)) {
        $fail("Gagal mencadangkan {$i['rel']}. Pemasangan dihentikan sebelum mengganti apa pun.", 1);
    }
    $backedUp++;
}
$written = 0;
foreach ($todo as $i) {
    $dest = "$cms/{$i['rel']}";
    $dir = dirname($dest);
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
        $fail("Gagal membuat folder: $dir\nSebagian berkas mungkin sudah terpasang; cadangan ada di $backupRoot", 1);
    }
    if (!copy($i['src'], $dest) || hash_file('sha256', $dest) !== hash_file('sha256', $i['src'])) {
        $fail("Gagal menyalin {$i['rel']} (atau isinya tidak cocok sesudah disalin).\nCadangan ada di $backupRoot", 1);
    }
    $written++;
}
$removed = 0;
foreach ($remove as $r) {
    if (!unlink("$cms/$r")) {
        $fail("Gagal menghapus $r. Cadangan ada di $backupRoot", 1);
    }
    $removed++;
}
if ($written > 0 || $removed > 0) {
    echo "\nSELESAI: $written berkas dipasang, $removed dihapus" . ($backedUp ? ", $backedUp berkas lama dicadangkan di:\n  $backupRoot\n  (untuk membatalkan: salin isi folder cadangan itu kembali ke proyek CMS)" : '.') . "\n";
}

// ------------------------------------------------------------------ pemindai direktif Blade
echo "\nMemeriksa tampilan CMS (tests/blade-scan.php)...\n";
$out = [];
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$kit/tests/blade-scan.php") . ' ' . escapeshellarg($cms) . ' 2>&1', $out, $code);
foreach ($out as $l) {
    if (preg_match('/GALAT|==>/', $l)) {
        echo "  $l\n";
    }
}
echo $code === 0 ? "  Pemindai: tidak ada tabrakan direktif Blade.\n" : "  Pemindai menemukan masalah (baris GALAT di atas). Kirimkan keluaran ini bila Anda tidak yakin.\n";
echo "\nLangkah berikutnya, di folder proyek CMS (panduan: docs/PASANG-CMS.md dan docs/BAHASA.md):\n  1. config/cms.php, SEKARANG PER BAHASA (contoh lengkap: contoh-kode/config-slug-terlarang.php):\n       default_locale => 'en'; reserved_slugs, home_slug, articles_index_slug => peta ['en' => ..., 'id' => ...]; public.base/page/article/home/articles\n     config/app.php: 'supported_locales' => ['en', 'id']\n  2. php artisan optimize:clear\n  3. npm run build\n  4. php artisan cms:audit-slugs          (slug bentrok/terlarang, per bahasa)\n  5. php artisan cms:audit-translations   (halaman/artikel yang belum diterjemahkan atau setengah jadi)\n  6. (opsional) php artisan cms:seed-pages  lalu --apply: kerangka halaman offline + tempat NPWP/rekening kosong (docs/HALAMAN-SITUS.md)\n";
exit(0);
