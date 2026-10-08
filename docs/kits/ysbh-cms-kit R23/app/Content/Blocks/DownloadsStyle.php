<?php

namespace App\Content\Blocks;

/** Gaya Daftar unduhan -> kelas Tailwind (semua ditulis utuh; nilai di luar daftar jatuh ke bawaan). */
final class DownloadsStyle
{
    public const LAYOUTS = ['list', 'cards'];
    public const COLORS = ['foresty', 'coral', 'aurum', 'charcoal'];

    private const BADGE = [
        'foresty' => 'bg-foresty/10 text-foresty',
        'coral' => 'bg-coral/10 text-coral',
        'aurum' => 'bg-aurum/25 text-charcoal',
        'charcoal' => 'bg-charcoal/10 text-charcoal',
    ];

    private const HOVER = [
        'foresty' => 'hover:border-foresty/50',
        'coral' => 'hover:border-coral/50',
        'aurum' => 'hover:border-aurum',
        'charcoal' => 'hover:border-charcoal/50',
    ];

    private const ICON = [
        'foresty' => 'text-foresty',
        'coral' => 'text-coral',
        'aurum' => 'text-charcoal',
        'charcoal' => 'text-charcoal',
    ];

    public static function layout(mixed $v): string
    {
        return in_array($v, self::LAYOUTS, true) ? $v : 'list';
    }

    public static function color(mixed $v): string
    {
        return in_array($v, self::COLORS, true) ? $v : 'foresty';
    }

    public static function wrapper(mixed $layout): string
    {
        return self::layout($layout) === 'cards' ? 'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3' : 'flex flex-col gap-3';
    }

    public static function row(mixed $layout, mixed $color): string
    {
        $base = 'group flex rounded-xl border border-gray-200 bg-white shadow-sm transition-colors outline-none focus-visible:ring-2 focus-visible:ring-foresty/40 ' . self::HOVER[self::color($color)];

        return $base . (self::layout($layout) === 'cards' ? ' h-full flex-col gap-3 p-5' : ' items-center gap-4 p-4');
    }

    public static function badge(mixed $color): string
    {
        return 'inline-flex shrink-0 items-center justify-center rounded-lg px-2.5 py-1 text-[11px] font-extrabold tracking-wide uppercase ' . self::BADGE[self::color($color)];
    }

    public static function icon(mixed $color): string
    {
        return 'h-5 w-5 shrink-0 transition-transform group-hover:translate-y-0.5 ' . self::ICON[self::color($color)];
    }
}
