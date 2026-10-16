<?php

namespace App\Editor;

use App\Content\Palette;
use App\Content\PublicLookup;

/**
 * Rilis 58: pilihan warna untuk kontrol swatches, diambil dari PALET (Pengaturan > Menu > Palet) menggantikan config('cms.design.*_colors').
 *
 *   PaletteOptions::colors('bg_colors')  ->  [['name'=>'Hijau Forest','value'=>'bg-forest'], ..., ['name'=>'Kustom 1','value'=>'bg-custom-1']]
 *
 * Nama kunci sengaja SAMA dengan config('cms.design.*') supaya pemanggil tinggal mengganti satu panggilan. Pilihan di luar palet (Putih, Transparan)
 * tetap ditambahkan. Slot kustom yang kosong tidak tampil. Pengaturan opsional di config/cms.php:
 *   'palette' => ['pickers' => true,                      // false = kembali memakai config('cms.design.*') seperti sebelum rilis 58
 *                 'hide' => ['card_bg_colors' => ['amber']]]  // slot yang disembunyikan dari picker tertentu
 */
final class PaletteOptions
{
    /** kunci config('cms.design.*') => [jenis, pilihan tetap di luar palet] */
    private const MAP = [
        'bg_colors' => ['bg', ['white']],
        'card_bg_colors' => ['bg', ['white']],
        'mini_bg_colors' => ['bg', ['white']],
        'text_colors' => ['text', ['white']],
        'card_border_colors' => ['border', ['transparent', 'white']],
        'avatar_border_colors' => ['border', ['white']],
        'eyebrow_colors' => ['hex', []],
    ];

    public static function enabled(): bool
    {
        return ! function_exists('config') || (bool) config('cms.palette.pickers', true);
    }

    /** @return list<array<string,mixed>> */
    public static function colors(string $configKey): array
    {
        if (! isset(self::MAP[$configKey]) || ! self::enabled() || ! class_exists(Palette::class)) {
            return function_exists('config') ? (array) config('cms.design.' . $configKey, []) : [];
        }

        [$kind, $extra] = self::MAP[$configKey];
        $hide = function_exists('config') ? (array) config('cms.palette.hide.' . $configKey, []) : [];

        try {
            $palette = PublicLookup::palette();
        } catch (\Throwable) {
            $palette = Palette::defaults();   // PublicLookup / basis data tidak tersedia: pakai warna bawaan, jangan jatuhkan editor
        }

        return Palette::swatchOptions($kind, $palette, array_values(array_map('strval', $hide)), $extra);
    }
}
