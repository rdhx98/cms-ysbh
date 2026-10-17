<?php

namespace App\Content\Blocks;

/**
 * Padding per sumbu (samping dan atas-bawah), rilis 70. Murni, tanpa Laravel.
 *
 * Dipakai di DUA tempat dengan kunci yang sama (`pad_x`, `pad_y`):
 *   - kartu di Kartu Builder: data.cards[i].container.pad_x / pad_y, menimpa sumbu itu dari "Ruang Dalam" (container.padding);
 *   - setiap blok tingkat atas: data.pad_x / pad_y, dicetak pembungkus di sections.blade.php (seperti jarak bawah, rilis 32).
 *
 * Nilai tersimpan = KUNCI ("none" | "s" | "m" | "l" | "xl"), bukan kelas; kosong/tidak ada = tidak ada penimpaan. Kunci di luar daftar dibuang.
 * Setiap kelas menyertakan varian `md:` penuh: bila hanya kelas dasar, `md:p-6` dari "Ruang Dalam" akan menimpanya di layar lebar.
 *
 * Kelas yang harus terbaca pemindai Tailwind (ditulis utuh):
 * px-0 md:px-0 px-2 md:px-4 px-4 md:px-6 px-6 md:px-8 px-8 md:px-12 py-0 md:py-0 py-2 md:py-4 py-4 md:py-6 py-6 md:py-8 py-8 md:py-12
 */
final class BoxPadding
{
    public const X = [
        'none' => 'px-0 md:px-0',
        's' => 'px-2 md:px-4',
        'm' => 'px-4 md:px-6',
        'l' => 'px-6 md:px-8',
        'xl' => 'px-8 md:px-12',
    ];

    public const Y = [
        'none' => 'py-0 md:py-0',
        's' => 'py-2 md:py-4',
        'm' => 'py-4 md:py-6',
        'l' => 'py-6 md:py-8',
        'xl' => 'py-8 md:py-12',
    ];

    /** Label tombol di panel (urutan = urutan tombol; "" ditambah di depan oleh options()). */
    public const LABELS = ['none' => '0', 's' => 'S', 'm' => 'M', 'l' => 'L', 'xl' => 'XL'];

    private const TITLES = ['none' => 'Tanpa (0)', 's' => 'Kecil', 'm' => 'Sedang', 'l' => 'Besar', 'xl' => 'Luas'];

    /**
     * Pilihan untuk kontrol segmented. $emptyLabel = nama pilihan "tidak ada penimpaan" ("Ikuti" di kartu, "Tanpa" di blok).
     *
     * @return list<array{value:string,label:string,title:string}>
     */
    public static function options(string $emptyLabel): array
    {
        $out = [['value' => '', 'label' => $emptyLabel, 'title' => $emptyLabel]];
        foreach (self::LABELS as $k => $label) {
            $out[] = ['value' => $k, 'label' => $label, 'title' => self::TITLES[$k]];
        }

        return $out;
    }

    /** Kelas samping untuk satu nilai ('' bila kosong/tidak sah). */
    public static function x(mixed $value): string
    {
        return is_string($value) ? (self::X[$value] ?? '') : '';
    }

    /** Kelas atas-bawah untuk satu nilai ('' bila kosong/tidak sah). */
    public static function y(mixed $value): string
    {
        return is_string($value) ? (self::Y[$value] ?? '') : '';
    }

    /**
     * Kelas padding dari larik data yang memuat pad_x / pad_y. '' bila tidak ada yang diatur.
     *
     * @param array<mixed> $data
     */
    public static function classes(array $data): string
    {
        return trim(self::x($data['pad_x'] ?? null) . ' ' . self::y($data['pad_y'] ?? null));
    }
}
