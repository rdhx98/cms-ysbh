<?php
/**
 * Mencetak blok @theme palet warna (rilis 57) + baris @source inline (rilis 58):  php tools/palette-theme.php
 * Tempel hasilnya di berkas CSS Tailwind (landing DAN CMS: resources/css/app.css) menggantikan definisi lama warna-warna palet.
 * Daftar nama yang harus dihapus dari @theme lama dicetak dengan:  php tools/palette-theme.php --names
 * Memeriksa apakah berkas CSS Anda sudah memuat SEMUA slot (rilis 67b):  php tools/palette-theme.php --check resources/css/app.css [lain.css ...]
 * Daftar kelas harfiah (cadangan bila Tailwind < 4.1):  php tools/palette-theme.php --safelist > resources/views/palette-safelist.html
 *   Slot yang tidak ada di CSS = kelasnya (mis. bg-kia) tidak dibuat Tailwind, jadi warna itu tidak tampil di kanvas/situs.
 */
require __DIR__ . '/../app/Content/Palette.php';

use App\Content\Palette;

if (in_array('--names', $argv, true)) {
    echo implode("\n", array_map(fn (string $n) => "--color-$n", Palette::names())), "\n";
    exit(0);
}
if (in_array('--safelist', $argv, true)) {
    echo "<!-- Dibuat oleh php tools/palette-theme.php --safelist. Hanya dipindai Tailwind; tidak dimuat sebagai tampilan. -->\n<div class=\"hidden\">\n", Palette::safelist(), "</div>\n";
    exit(0);
}
if (in_array('--check', $argv, true)) {
    $files = array_values(array_filter(array_slice($argv, array_search('--check', $argv, true) + 1), fn ($a) => !str_starts_with($a, '--')));
    if ($files === []) {
        fwrite(STDERR, "Pemakaian: php tools/palette-theme.php --check resources/css/app.css [lain.css ...]\n");
        exit(2);
    }
    $bad = 0;
    foreach ($files as $f) {
        if (!is_file($f)) {
            echo "$f: berkas tidak ada\n";
            $bad++;
            continue;
        }
        // versi Tailwind: node_modules/tailwindcss terdekat di atas berkas CSS, atau package.json
        $version = null;
        for ($dir = dirname(realpath($f)), $i = 0; $i < 5 && $version === null; $dir = dirname($dir), $i++) {
            foreach (["$dir/node_modules/tailwindcss/package.json" => 'version', "$dir/package.json" => 'dep'] as $pj => $how) {
                if (!is_file($pj)) { continue; }
                $j = json_decode((string) file_get_contents($pj), true) ?: [];
                $version = $how === 'version' ? ($j['version'] ?? null) : ($j['devDependencies']['tailwindcss'] ?? $j['dependencies']['tailwindcss'] ?? null);
                if ($version !== null) { break; }
            }
        }
        $sup = Palette::supportsSourceInline($version);
        echo "$f: Tailwind " . ($version ?? 'tidak terbaca') . ($sup === false ? '  <-- DI BAWAH 4.1: @source inline TIDAK dikenal; pakai: php tools/palette-theme.php --safelist (lihat docs/PALET.md)' : ($sup === true ? ' (mengenal @source inline)' : '')) . "\n";
        if ($sup === false) { $bad++; }
        $r = Palette::checkCss((string) file_get_contents($f));
        if ($r['theme'] === [] && $r['source'] === []) {
            echo "$f: OK, semua " . count(Palette::keys()) . " slot ada (@theme dan @source inline)\n";
            continue;
        }
        $bad++;
        echo "$f: KURANG\n";
        if ($r['theme']) { echo '  @theme tanpa --color-{slot} / --color-{slot}-on untuk: ' . implode(', ', $r['theme']) . "\n"; }
        if ($r['source']) { echo '  @source inline tidak memuat: ' . implode(', ', $r['source']) . "\n"; }
        echo "  -> tempel ulang keluaran `php tools/palette-theme.php` (lihat docs/PALET.md), hapus blok palet lama, lalu `npm run build`.\n";
    }
    exit($bad ? 1 : 0);
}
echo Palette::themeBlock(), "\n", Palette::sourceBlock();