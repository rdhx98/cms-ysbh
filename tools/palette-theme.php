<?php
/**
 * Mencetak blok @theme palet warna (rilis 57) + baris @source inline (rilis 58):  php tools/palette-theme.php
 * Tempel hasilnya di berkas CSS Tailwind (landing DAN CMS: resources/css/app.css) menggantikan definisi lama warna-warna palet.
 * Daftar nama yang harus dihapus dari @theme lama dicetak dengan:  php tools/palette-theme.php --names
 */
require __DIR__ . '/../app/Content/Palette.php';

use App\Content\Palette;

if (in_array('--names', $argv, true)) {
    echo implode("\n", array_map(fn (string $n) => "--color-$n", Palette::names())), "\n";
    exit(0);
}
echo Palette::themeBlock(), "\n", Palette::sourceBlock();
