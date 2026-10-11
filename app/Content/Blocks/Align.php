<?php

namespace App\Content\Blocks;

/**
 * Rata teks blok Judul (rilis 33). Murni, tanpa Laravel.
 *
 * Judul disimpan satu baris TANPA <p>, jadi perataan tidak bisa ditulis di HTML-nya. Toolbar Tiptap menulisnya ke `data.align`
 * ("left" | "center" | "right") pada blok, dan PEMBUNGKUS blok di sections.blade.php mencetak kelasnya. "left" (bawaan) tidak
 * mencetak apa pun, jadi tampilan Judul yang sudah ada tidak berubah. Nilai lain dibuang, karena dicetak ke atribut class.
 *
 * Kelas ditulis lengkap di bawah supaya terbaca pemindai Tailwind: text-center text-right
 */
final class Align
{
    /** Tipe yang memakai rata teks tingkat blok. Paragraf tidak: ia meratakan tiap <p> di dalam HTML-nya. */
    public const TYPES = ['heading'];

    private const CLASSES = ['center' => 'text-center', 'right' => 'text-right'];

    public const VALUES = ['left', 'center', 'right'];

    public static function valid(mixed $value): string
    {
        return is_string($value) && in_array($value, self::VALUES, true) ? $value : 'left';
    }

    public static function appliesTo(?string $type): bool
    {
        return in_array(str_replace('_', '-', strtolower((string) $type)), self::TYPES, true);
    }

    /** Kelas untuk pembungkus satu blok tingkat atas; '' bila bukan Judul, atau rata kiri. */
    public static function wrapperClass(array $block): string
    {
        if (!self::appliesTo($block['type'] ?? '')) {
            return '';
        }
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        return self::CLASSES[self::valid($data['align'] ?? null)] ?? '';
    }
}
