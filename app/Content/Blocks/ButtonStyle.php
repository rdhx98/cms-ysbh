<?php

namespace App\Content\Blocks;

/**
 * Gaya tombol -> kelas Tailwind. SEMUA kelas ditulis utuh di berkas ini (tidak dirakit dari potongan string),
 * supaya pemindai Tailwind menemukannya dan gaya tidak hilang saat build. Nilai di luar daftar jatuh ke bawaan:
 * data dari browser tidak bisa menyisipkan kelas sembarang.
 */
final class ButtonStyle
{
    public const VARIANTS = ['solid', 'outline', 'ghost'];
    public const COLORS = ['foresty', 'coral', 'aurum', 'charcoal', 'white'];
    public const SIZES = ['sm', 'md', 'lg'];
    public const ALIGNS = ['left', 'center', 'right'];
    /** Aksi tombol (rilis 102): tautan biasa, atau menyalin teks ke papan klip (mis. nomor rekening). */
    public const ACTIONS = ['link', 'copy'];

    private const BASE = 'inline-flex items-center justify-center gap-2 border-2 font-bold transition-colors';

    /** Kebulatan sudut (rilis 103): nilai tersimpan => kelas (ditulis utuh agar terbaca Tailwind). Bawaan 'full' = pil, tampilan sebelum rilis ini. */
    public const RADIUS_DEFAULT = 'full';

    public const RADII = [
        'none' => 'rounded-none',
        'sm' => 'rounded-md',
        'md' => 'rounded-xl',
        'full' => 'rounded-full',
    ];

    private const SIZE = [
        'sm' => 'px-4 py-2 text-xs',
        'md' => 'px-6 py-3 text-sm',
        'lg' => 'px-8 py-4 text-base',
    ];

    private const LOOK = [
        'solid' => [
            'foresty' => 'border-foresty bg-foresty text-forest-on hover:opacity-90',
            'coral' => 'border-coral bg-coral text-coral-on hover:opacity-90',
            'aurum' => 'border-aurum bg-aurum text-aurum-on hover:opacity-90',
            'charcoal' => 'border-charcoal bg-charcoal text-charcoal-on hover:opacity-90',
            'white' => 'border-white bg-white text-charcoal hover:opacity-90',
        ],
        'outline' => [
            'foresty' => 'border-foresty bg-transparent text-foresty hover:bg-foresty hover:text-forest-on',
            'coral' => 'border-coral bg-transparent text-coral hover:bg-coral hover:text-coral-on',
            'aurum' => 'border-aurum bg-transparent text-aurum hover:bg-aurum hover:text-aurum-on',
            'charcoal' => 'border-charcoal bg-transparent text-charcoal hover:bg-charcoal hover:text-charcoal-on',
            'white' => 'border-white bg-transparent text-white hover:bg-white hover:text-charcoal',
        ],
        'ghost' => [
            'foresty' => 'border-transparent bg-transparent text-foresty hover:bg-foresty/10',
            'coral' => 'border-transparent bg-transparent text-coral hover:bg-coral/10',
            'aurum' => 'border-transparent bg-transparent text-aurum hover:bg-aurum/10',
            'charcoal' => 'border-transparent bg-transparent text-charcoal hover:bg-charcoal/10',
            'white' => 'border-transparent bg-transparent text-white hover:bg-white/10',
        ],
    ];

    /** Tata letak baris tombol: [rata, bila ditumpuk di layar kecil]. */
    private const ROW = [
        'left' => ['justify-start', 'items-stretch sm:flex-row sm:items-center sm:justify-start'],
        'center' => ['justify-center', 'items-stretch sm:flex-row sm:items-center sm:justify-center'],
        'right' => ['justify-end', 'items-stretch sm:flex-row sm:items-center sm:justify-end'],
    ];

    public static function radius(mixed $value): string
    {
        return self::RADII[is_string($value) && isset(self::RADII[$value]) ? $value : self::RADIUS_DEFAULT];
    }

    public static function button(mixed $variant, mixed $color, mixed $size, bool $stacked = false, mixed $radius = null): string
    {
        $variant = in_array($variant, self::VARIANTS, true) ? $variant : 'solid';
        $color = in_array($color, self::COLORS, true) ? $color : 'foresty';
        $size = in_array($size, self::SIZES, true) ? $size : 'md';

        return self::BASE . ' ' . self::radius($radius) . ' ' . self::SIZE[$size] . ' ' . self::LOOK[$variant][$color] . ($stacked ? ' w-full sm:w-auto' : '');
    }

    public static function row(mixed $align, bool $stackMobile): string
    {
        $align = in_array($align, self::ALIGNS, true) ? $align : 'left';

        return 'flex flex-wrap gap-3 ' . ($stackMobile ? 'flex-col ' . self::ROW[$align][1] : self::ROW[$align][0]);
    }
}
