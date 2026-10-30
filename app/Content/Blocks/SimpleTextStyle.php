<?php

namespace App\Content\Blocks;

/**
 * Gaya blok Paragraf Biasa (tipe "simple-text", rilis 92). Murni, tanpa Laravel.
 *
 * Berbeda dengan blok Paragraf (Tiptap, format per bahasa), semua gaya di sini diatur SATU KALI untuk seluruh bahasa:
 * ukuran, rata, dan warna disimpan sebagai KUNCI pendek, lalu diubah menjadi kelas dari daftar tetap di bawah. Nilai lain dibuang
 * dan diganti bawaan, karena hasilnya dicetak ke atribut class.
 *
 * Kelas yang harus terbaca pemindai Tailwind (ditulis utuh):
 * text-sm text-base md:text-lg text-lg md:text-xl text-xl md:text-2xl leading-relaxed text-left text-center text-right text-ink-soft text-forest text-coral
 */
final class SimpleTextStyle
{
    public const SIZES = ['sm', 'md', 'lg', 'xl'];
    public const ALIGNS = ['left', 'center', 'right'];
    public const COLORS = ['', 'muted', 'forest', 'coral'];

    private const SIZE = [
        'sm' => 'text-sm leading-relaxed',
        'md' => 'text-base md:text-lg leading-relaxed',
        'lg' => 'text-lg md:text-xl leading-relaxed',
        'xl' => 'text-xl md:text-2xl leading-relaxed',
    ];

    private const ALIGN = ['left' => 'text-left', 'center' => 'text-center', 'right' => 'text-right'];

    /** '' = ikuti warna seksi (otomatis terbaca di latar apa pun). */
    private const COLOR = ['' => '', 'muted' => 'text-ink-soft', 'forest' => 'text-forest', 'coral' => 'text-coral'];

    public static function size(mixed $v): string
    {
        return is_string($v) && in_array($v, self::SIZES, true) ? $v : 'md';
    }

    public static function align(mixed $v): string
    {
        return is_string($v) && in_array($v, self::ALIGNS, true) ? $v : 'left';
    }

    public static function color(mixed $v): string
    {
        return is_string($v) && in_array($v, self::COLORS, true) ? $v : '';
    }

    /** Kelas pembungkus teks untuk tiga nilai (nilai tak sah = bawaan). */
    public static function classes(mixed $size, mixed $align, mixed $color): string
    {
        return trim(self::SIZE[self::size($size)] . ' ' . self::ALIGN[self::align($align)] . ' ' . self::COLOR[self::color($color)]);
    }
}
