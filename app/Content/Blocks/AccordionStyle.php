<?php

namespace App\Content\Blocks;

/**
 * Gaya akordion/FAQ -> kelas Tailwind. Semua kelas ditulis UTUH di berkas ini (terdeteksi pemindai Tailwind); nilai di luar daftar
 * jatuh ke bawaan, sehingga data dari browser tidak bisa menyisipkan kelas sembarang.
 */
final class AccordionStyle
{
    public const STYLES = ['boxed', 'lines'];
    public const COLORS = ['foresty', 'coral', 'aurum', 'charcoal'];
    public const MARKERS = ['chevron', 'plus'];

    private const WRAPPER = [
        'boxed' => 'flex flex-col gap-3',
        'lines' => 'divide-y divide-gray-200 border-y border-gray-200',
    ];

    private const ITEM = [
        'boxed' => [
            'foresty' => 'group rounded-2xl border border-gray-200 bg-white shadow-sm transition-colors open:border-foresty/40',
            'coral' => 'group rounded-2xl border border-gray-200 bg-white shadow-sm transition-colors open:border-coral/40',
            'aurum' => 'group rounded-2xl border border-gray-200 bg-white shadow-sm transition-colors open:border-aurum/60',
            'charcoal' => 'group rounded-2xl border border-gray-200 bg-white shadow-sm transition-colors open:border-charcoal/40',
        ],
        'lines' => [
            'foresty' => 'group', 'coral' => 'group', 'aurum' => 'group', 'charcoal' => 'group',
        ],
    ];

    /** Warna judul saat terbuka (emas di atas putih kurang terbaca, jadi aurum memakai charcoal). */
    private const TITLE = [
        'foresty' => 'group-open:text-foresty',
        'coral' => 'group-open:text-coral',
        'aurum' => 'group-open:text-charcoal',
        'charcoal' => 'group-open:text-charcoal',
    ];

    private const MARKER = [
        'foresty' => 'text-foresty',
        'coral' => 'text-coral',
        'aurum' => 'text-aurum',
        'charcoal' => 'text-charcoal',
    ];

    private const SUMMARY = [
        'boxed' => 'px-5 py-4',
        'lines' => 'px-1 py-5',
    ];

    private const ANSWER = [
        'boxed' => 'px-5 pb-5',
        'lines' => 'px-1 pb-5',
    ];

    public static function style(mixed $v): string
    {
        return in_array($v, self::STYLES, true) ? $v : 'boxed';
    }

    public static function color(mixed $v): string
    {
        return in_array($v, self::COLORS, true) ? $v : 'foresty';
    }

    public static function wrapper(mixed $style): string
    {
        return self::WRAPPER[self::style($style)];
    }

    public static function item(mixed $style, mixed $color): string
    {
        return self::ITEM[self::style($style)][self::color($color)];
    }

    public static function summary(mixed $style, mixed $color): string
    {
        return 'flex cursor-pointer list-none items-center justify-between gap-4 text-left text-base font-semibold text-gray-900 outline-none focus-visible:ring-2 focus-visible:ring-foresty/40 [&::-webkit-details-marker]:hidden '
            . self::SUMMARY[self::style($style)] . ' ' . self::TITLE[self::color($color)];
    }

    public static function answer(mixed $style): string
    {
        return 'text-[15px] leading-relaxed text-gray-700 [&_a]:font-semibold [&_a]:underline [&_li]:mb-1 [&_p]:mb-3 [&_p:last-child]:mb-0 [&_ul]:list-disc [&_ul]:pl-5 '
            . self::ANSWER[self::style($style)];
    }

    public static function marker(mixed $color): string
    {
        return 'h-5 w-5 shrink-0 ' . self::MARKER[self::color($color)];
    }
}