<?php

namespace App\Content\Blocks;

/**
 * Jarak bawah blok tingkat atas (rilis 32). Murni, tanpa Laravel.
 *
 * Dua cara jarak diterapkan, TANPA saling menggandakan:
 *   - Blok yang renderernya SENDIRI sudah mencetak `data.margin_bottom` (judul, paragraf, eyebrow, gambar) atau `data.grid.margin_bottom`
 *     (kartu) tidak disentuh pembungkus: lihat OWN.
 *   - Semua blok tingkat atas lain (tombol, kolom, grup langkah, FAQ, callout, video, galeri, unduhan, artikel terbaru, dan modul
 *     mana pun yang ditambahkan nanti) mendapat kelas margin dari PEMBUNGKUS di sections.blade.php, memakai `data.margin_bottom`.
 *
 * Nilainya selalu kelas Tailwind dari cms.design.margin_bottom (mis. "mb-4 md:mb-6"); bentuk lain dibuang dan diganti bawaan,
 * karena dicetak ke atribut class.
 */
final class Spacing
{
    public const DEFAULT = 'mb-4 md:mb-6';

    /** Tipe yang renderernya mencetak margin sendiri (dikanonkan: garis tengah, huruf kecil). */
    public const OWN = ['heading', 'paragraph', 'eyebrow', 'image', 'card-builder'];

    /** Satu atau dua kelas margin: "mb-0", "mb-8", "mb-4 md:mb-6" (awalan responsif sm|md|lg|xl). */
    private const PATTERN = '/^mb-\d{1,2}(?: (?:sm|md|lg|xl):mb-\d{1,2}){0,2}$/D';

    public static function canonical(?string $type): string
    {
        return str_replace('_', '-', strtolower((string) $type));
    }

    /** Nilai yang sah (string kelas margin) atau null. */
    public static function valid(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $v = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return preg_match(self::PATTERN, $v) === 1 ? $v : null;
    }

    public static function owns(?string $type): bool
    {
        return in_array(self::canonical($type), self::OWN, true);
    }

    /**
     * Kelas margin yang harus dipasang PEMBUNGKUS untuk satu blok tingkat atas. '' bila renderer blok itu mengurusnya sendiri.
     *
     * @param array<string,mixed> $block
     * @param list<string>        $allowed nilai yang ada di cms.design.margin_bottom; kosong = cukup berbentuk sah
     */
    public static function wrapperClass(array $block, array $allowed = []): string
    {
        if (self::owns($block['type'] ?? '')) {
            return '';
        }
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        $v = self::valid($data['margin_bottom'] ?? null);
        if ($v === null || ($allowed !== [] && !in_array($v, $allowed, true))) {
            return self::DEFAULT;
        }

        return $v;
    }

    /**
     * Pembersih modul hanya mengembalikan kunci miliknya. Panggil ini sesudahnya supaya margin_bottom yang sah tidak ikut terbuang.
     *
     * @param array<string,mixed> $before data sebelum dibersihkan
     * @param array<string,mixed> $after  data hasil pembersih modul
     * @return array<string,mixed>
     */
    public static function keep(array $before, array $after): array
    {
        if (array_key_exists('margin_bottom', $before) && ($v = self::valid($before['margin_bottom'])) !== null) {
            $after['margin_bottom'] = $v;
        }

        return $after;
    }
}
