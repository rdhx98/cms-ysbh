<?php

namespace App\Content\Blocks;

/**
 * Gaya elemen Kartu Builder, rilis 69 (68+69). Murni, tanpa Laravel.
 *
 * Satu-satunya sumber untuk panel (BlockRegistry) dan renderer card-builder. Nilai dari browser TIDAK dicetak sebagai kelas:
 * setiap nilai dicocokkan ke daftar di bawah, sisanya jatuh ke bawaan. Semua kelas ditulis UTUH di berkas ini agar terbaca pemindai Tailwind.
 *
 * Teks (data.style):
 *   size            text-[11px] XS | text-[13px] S | text-[15px] M (bawaan) | text-[18px] L | text-[21px] XL | text-[28px] 2XL
 *   weight          font-normal (bawaan) | font-semibold | font-bold          (pilih SATU)
 *   effects         larik: "italic" | "underline" | "line-through"           (boleh digabung)
 *   text_transform  uppercase | lowercase | capitalize | normal-case (bawaan) (klik lagi = kembali ke normal-case)
 * Semua elemen: margin  mb-0 | mb-2 | mb-3 | mb-4 | mb-6 (panel); renderer juga menerima mb-1 mb-1.5 mb-5 mb-8 (data lama).
 * Gambar (data.style): ratio auto|1/1|4/3|3/2|16/9, fit cover|contain, radius.
 *
 * Kelas yang harus terbaca pemindai Tailwind (ditulis utuh):
 * text-[11px] text-[13px] text-[15px] text-[18px] text-[21px] text-[28px] font-normal font-semibold font-bold italic underline line-through
 * uppercase lowercase capitalize normal-case mb-0 mb-1 mb-1.5 mb-2 mb-3 mb-4 mb-5 mb-6 mb-8 aspect-square aspect-[4/3] aspect-[3/2] aspect-video
 * object-cover object-contain rounded-none rounded-lg rounded-2xl rounded-full w-full
 */
final class ElementStyle
{
    public const SIZE_DEFAULT = 'text-[15px]';

    /** Label panel; urutan = urutan tombol. */
    public const SIZES = [
        'text-[11px]' => 'XS',
        'text-[13px]' => 'S',
        'text-[15px]' => 'M',
        'text-[18px]' => 'L',
        'text-[21px]' => 'XL',
        'text-[28px]' => '2XL',
    ];

    public const WEIGHT_DEFAULT = 'font-normal';

    public const WEIGHTS = [
        'font-normal' => 'Normal',
        'font-semibold' => 'Semi',
        'font-bold' => 'Bold',
    ];

    /** nilai tersimpan => kelas. */
    public const EFFECTS = [
        'italic' => 'italic',
        'underline' => 'underline',
        'line-through' => 'line-through',
    ];

    public const CASE_DEFAULT = 'normal-case';

    public const CASES = [
        'uppercase' => 'uppercase',
        'lowercase' => 'lowercase',
        'capitalize' => 'capitalize',
    ];

    /** Pilihan di panel ("Jarak Bawah"). */
    public const MARGINS = [
        'mb-0' => '0',
        'mb-2' => 'Kecil',
        'mb-3' => 'Standar',
        'mb-4' => 'Sedang',
        'mb-6' => 'Besar',
    ];

    /** Nilai yang tetap dihormati renderer (termasuk yang pernah tersimpan oleh panel lama). */
    private const MARGIN_OK = ['mb-0', 'mb-1', 'mb-1.5', 'mb-2', 'mb-3', 'mb-4', 'mb-5', 'mb-6', 'mb-8'];

    public const MARGIN_TEXT = 'mb-2';
    public const MARGIN_OTHER = 'mb-3';

    public const RATIO_DEFAULT = 'auto';

    public const RATIOS = [
        'auto' => 'Asli',
        '1/1' => '1:1',
        '4/3' => '4:3',
        '3/2' => '3:2',
        '16/9' => '16:9',
    ];

    private const RATIO_CLASS = [
        'auto' => '',
        '1/1' => 'aspect-square',
        '4/3' => 'aspect-[4/3]',
        '3/2' => 'aspect-[3/2]',
        '16/9' => 'aspect-video',
    ];

    public const FIT_DEFAULT = 'cover';

    public const FITS = [
        'cover' => 'Penuhi',
        'contain' => 'Muat',
    ];

    private const FIT_CLASS = ['cover' => 'object-cover', 'contain' => 'object-contain'];

    public const RADIUS_DEFAULT = 'rounded-lg';

    public const RADII = [
        'rounded-none' => 'Siku',
        'rounded-lg' => 'Agak Bulat',
        'rounded-2xl' => 'Bulat',
        'rounded-full' => 'Lingkaran',
    ];

    /**
     * Kelas gaya teks (bukan pill): ukuran, ketebalan, efek, huruf. Urutan tetap supaya keluaran stabil.
     *
     * @param array<mixed> $style
     */
    public static function textClasses(array $style): string
    {
        $size = self::pick($style['size'] ?? null, array_keys(self::SIZES), self::SIZE_DEFAULT);
        $weight = self::pick($style['weight'] ?? null, array_keys(self::WEIGHTS), self::WEIGHT_DEFAULT);
        $classes = [$size, $weight];
        foreach (self::effects($style['effects'] ?? null) as $e) {
            $classes[] = self::EFFECTS[$e];
        }
        $classes[] = self::transform($style['text_transform'] ?? null);

        return implode(' ', $classes);
    }

    /**
     * Efek sah (urutan baku, tanpa duplikat). Larik, atau (data aneh) teks tunggal.
     *
     * @return list<string>
     */
    public static function effects(mixed $value): array
    {
        $list = is_array($value) ? $value : (is_string($value) && $value !== '' ? [$value] : []);
        $out = [];
        foreach (array_keys(self::EFFECTS) as $e) {
            if (in_array($e, $list, true)) {
                $out[] = $e;
            }
        }

        return $out;
    }

    public static function transform(mixed $value): string
    {
        return is_string($value) && isset(self::CASES[$value]) ? self::CASES[$value] : self::CASE_DEFAULT;
    }

    /** Jarak bawah: nilai sah atau bawaan jenis elemen ($text = true untuk teks). */
    public static function margin(mixed $value, bool $text = false): string
    {
        return self::pick($value, self::MARGIN_OK, $text ? self::MARGIN_TEXT : self::MARGIN_OTHER);
    }

    /**
     * Kelas gambar elemen Gambar.
     *
     * @param array<mixed> $style
     * @return array{wrap:string,img:string,ratio:string}  wrap = kelas pembungkus (margin + rata), img = kelas <img>
     */
    public static function image(array $style): array
    {
        $ratio = self::pick($style['ratio'] ?? null, array_keys(self::RATIOS), self::RATIO_DEFAULT);
        $fit = self::pick($style['fit'] ?? null, array_keys(self::FITS), self::FIT_DEFAULT);
        $radius = self::pick($style['radius'] ?? null, array_keys(self::RADII), self::RADIUS_DEFAULT);
        $margin = self::margin($style['margin'] ?? null);
        $box = self::RATIO_CLASS[$ratio];

        // rasio "asli": gambar mengikuti ukuran aslinya (h-auto); selain itu kotak berrasio dan gambar mengisinya
        $img = $ratio === 'auto'
            ? 'block h-auto w-full ' . $radius
            : 'block h-full w-full ' . self::FIT_CLASS[$fit] . ' ' . $radius;

        return [
            'wrap' => implode(' ', array_filter(['w-full overflow-hidden', $radius, $box, $margin], fn ($c) => $c !== '')),
            'img' => $img,
            'ratio' => $ratio,
        ];
    }

    /** @param list<string> $allowed */
    private static function pick(mixed $value, array $allowed, string $default): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }
}
