<?php
/**
 * Menelusuri berkas kit yang dibutuhkan situs PUBLIK (landing) untuk merender konten, lalu menulis/memeriksa `landing-files.txt`.
 *
 *   php tools/landing-files.php            tampilkan daftar hasil penelusuran
 *   php tools/landing-files.php --write    tulis ke landing-files.txt
 *   php tools/landing-files.php --check    bandingkan dengan landing-files.txt (kode keluar 1 bila berbeda)
 *
 * Titik masuk: mesin seksi, isi artikel lama, dan SEMUA renderer blok di components/blocks/render. Ketergantungan: kelas App\... yang
 * disebut berkas itu (termasuk kelas satu namespace yang disebut tanpa awalan), dan semua modul blok (ditemukan lewat glob, bukan disebut).
 * Model (App\Models\*) tidak disertakan: milik aplikasi masing-masing.
 */
$kit = realpath(dirname(__DIR__));
$mode = $argv[1] ?? '';
$listFile = "$kit/landing-files.txt";

function trace_landing(string $kit): array
{
    $queue = ['resources/views/components/content/sections.blade.php', 'resources/views/components/content/body.blade.php'];
    foreach (glob("$kit/resources/views/components/blocks/render/*.blade.php") ?: [] as $p) {
        $queue[] = substr($p, strlen($kit) + 1);
    }
    foreach (glob("$kit/app/Editor/Blocks/*.php") ?: [] as $p) {
        $queue[] = substr($p, strlen($kit) + 1);   // modul blok ditemukan lewat glob
    }
    $queue = array_map(fn ($p) => str_replace('\\', '/', $p), $queue);
    $seen = [];
    $fileOf = function (string $class) use ($kit): ?string {
        if (str_starts_with($class, 'App\\Models\\')) {
            return null;
        }
        $p = preg_replace('#/+#', '/', 'app/' . str_replace('\\', '/', substr($class, 4)) . '.php');

        return is_file("$kit/$p") ? $p : null;
    };

    // Kode landing yang BUKAN tampilan (pengendali, model, komponen di landing-app/) juga memakai kelas kit: kelas yang mereka rujuk ikut
    // diturunkan dari sumbernya, tidak ditulis dengan tangan (daftar tulisan tangan pasti menyimpang suatu saat).
    if (is_dir("$kit/landing-app")) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$kit/landing-app", FilesystemIterator::SKIP_DOTS)) as $fileInfo) {
            if (!preg_match('/\.php$/', $fileInfo->getFilename())) {
                continue;
            }
            preg_match_all('/App\\\\Content\\\\[A-Za-z0-9_\\\\]+/', (string) file_get_contents($fileInfo->getPathname()), $refs);
            foreach ($refs[0] as $ref) {
                if (($p = $fileOf(rtrim(preg_replace('/\\\\+/', '\\', $ref), '\\'))) !== null) {
                    $queue[] = $p;
                }
            }
        }
    }

    while ($queue) {
        $f = array_pop($queue);
        if (isset($seen[$f]) || !is_file("$kit/$f")) {
            continue;
        }
        $seen[$f] = true;
        $src = file_get_contents("$kit/$f");

        preg_match_all('/App\\\\[A-Za-z0-9_\\\\]+/', $src, $m);
        foreach ($m[0] as $c) {
            if (($p = $fileOf(rtrim(preg_replace('/\\\\+/', '\\', $c), '\\'))) !== null) {
                $queue[] = $p;
            }
        }
        preg_match_all('/use\s+(App\\\\[A-Za-z0-9_\\\\]+)\\\\\{([^}]*)\}/', $src, $m, PREG_SET_ORDER);
        foreach ($m as $g) {
            foreach (explode(',', $g[2]) as $part) {
                if (($p = $fileOf($g[1] . '\\' . trim($part))) !== null) {
                    $queue[] = $p;
                }
            }
        }
        if (str_starts_with($f, 'app/')) {
            $dir = dirname($f);
            foreach ([
                '/\b([A-Z][A-Za-z0-9_]*)(?:::|\s*\()/',
                '/\bnew\s+([A-Z][A-Za-z0-9_]*)/',
                '/(?:implements|extends|:)\s+\??([A-Z][A-Za-z0-9_]*)/',
            ] as $re) {
                preg_match_all($re, $src, $mm);
                foreach ($mm[1] as $name) {
                    if (is_file("$kit/$dir/$name.php")) {
                        $queue[] = "$dir/$name.php";
                    }
                }
            }
        }
    }
    $files = array_keys($seen);
    sort($files, SORT_STRING);

    return $files;
}

/**
 * Kebutuhan yang HARUS disediakan aplikasi landing sendiri (bukan bagian kit). Hanya dari kode yang DIPAKAI SAAT MERENDER: berkas Blade dan
 * app/Content/**. Kelas definisi editor (app/Editor/**) ikut terkirim karena modul blok menyatukan definisi dan pembersih, tetapi tidak dipanggil
 * saat merender, jadi komponen/konfigurasi yang hanya disebut di sana tidak dihitung.
 */
function landing_requirements(string $kit, array $files): array
{
    $models = $comps = $config = [];
    foreach ($files as $f) {
        $isBlade = str_ends_with($f, '.blade.php');
        if (!$isBlade && !str_starts_with($f, 'app/Content/')) {
            continue;
        }
        $src = file_get_contents("$kit/$f");
        preg_match_all('/App\\\\Models\\\\([A-Za-z]+)/', $src, $m);
        foreach ($m[1] as $x) $models[$x] = true;
        if ($isBlade) {
            preg_match_all('/<x-([a-z0-9.\-]+)/', $src, $m);
            foreach ($m[1] as $x) $comps[$x] = true;
        }
        preg_match_all("/config\('([a-z0-9_.]+[a-z0-9_])'/", $src, $m);
        foreach ($m[1] as $x) $config[$x] = true;
    }
    foreach (['content.body', 'content.sections', 'dynamic-component'] as $own) {
        unset($comps[$own]);
    }
    ksort($models); ksort($comps); ksort($config);

    return ['models' => array_keys($models), 'components' => array_keys($comps), 'config' => array_keys($config)];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    $files = trace_landing($kit);
    $text = "# Berkas kit yang dibutuhkan situs PUBLIK (landing). Dibuat otomatis: php tools/landing-files.php --write\n"
        . "# Jangan disunting tangan; sinkronkan ke proyek landing dengan: php tools/sync-landing.php <folder-landing> --apply\n"
        . implode("\n", $files) . "\n";

    if ($mode === '--write') {
        file_put_contents($listFile, $text);
        echo "Ditulis: landing-files.txt (" . count($files) . " berkas)\n";
    } elseif ($mode === '--check') {
        $current = is_file($listFile) ? file_get_contents($listFile) : '';
        if ($current === $text) {
            echo "landing-files.txt sesuai penelusuran (" . count($files) . " berkas)\n";
        } else {
            $have = array_values(array_filter(array_map('trim', explode("\n", $current)), fn ($l) => $l !== '' && $l[0] !== '#'));
            echo "landing-files.txt USANG.\n  kurang: " . (implode(', ', array_diff($files, $have)) ?: '-') . "\n  berlebih: " . (implode(', ', array_diff($have, $files)) ?: '-') . "\n";
            exit(1);
        }
    } else {
        echo implode("\n", $files) . "\n\n";
        $req = landing_requirements($kit, $files);
        echo "Model yang harus ada di landing: " . implode(', ', $req['models']) . "\n";
        echo "Komponen milik aplikasi yang dipakai: " . implode(', ', $req['components']) . "\n";
        echo "Kunci config() yang dibaca: " . implode(', ', $req['config']) . "\n";
    }
}
