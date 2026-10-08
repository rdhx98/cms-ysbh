<?php

namespace App\Content\Blocks;

/**
 * Gaya Callout -> kelas Tailwind dan ikon bawaan per jenis. Semua kelas ditulis UTUH (terdeteksi pemindai Tailwind); nilai di luar daftar
 * jatuh ke bawaan, sehingga data dari browser tidak bisa menyisipkan kelas sembarang. Ikon bawaan berupa SVG sendiri (konstanta tepercaya),
 * tidak bergantung pada daftar ikon aplikasi, dan HTML di dalamnya tidak pernah berasal dari data.
 */
final class CalloutStyle
{
    public const TONES = ['info', 'success', 'warning', 'danger', 'neutral', 'brand'];
    public const STYLES = ['soft', 'outline', 'solid'];

    private const BOX = [
        'soft' => [
            'info' => 'border-sky-500 bg-sky-50 text-sky-900',
            'success' => 'border-emerald-500 bg-emerald-50 text-emerald-900',
            'warning' => 'border-amber-500 bg-amber-50 text-amber-900',
            'danger' => 'border-red-500 bg-red-50 text-red-900',
            'neutral' => 'border-gray-400 bg-gray-50 text-gray-800',
            'brand' => 'border-foresty bg-foresty/5 text-foresty',
        ],
        'outline' => [
            'info' => 'border-sky-500 bg-white text-gray-800',
            'success' => 'border-emerald-500 bg-white text-gray-800',
            'warning' => 'border-amber-500 bg-white text-gray-800',
            'danger' => 'border-red-500 bg-white text-gray-800',
            'neutral' => 'border-gray-400 bg-white text-gray-800',
            'brand' => 'border-foresty bg-white text-gray-800',
        ],
        'solid' => [
            'info' => 'border-sky-700 bg-sky-700 text-white',
            'success' => 'border-emerald-700 bg-emerald-700 text-white',
            'warning' => 'border-amber-400 bg-amber-400 text-gray-900',
            'danger' => 'border-red-700 bg-red-700 text-white',
            'neutral' => 'border-gray-800 bg-gray-800 text-white',
            'brand' => 'border-foresty bg-foresty text-white',
        ],
    ];

    private const SHAPE = [
        'soft' => 'rounded-xl border-l-4 p-5',
        'outline' => 'rounded-xl border-2 p-5',
        'solid' => 'rounded-xl border-2 p-5',
    ];

    /** Warna ikon: pada gaya lembut/garis mengikuti jenis; pada gaya penuh mengikuti warna teks (putih, atau gelap untuk kuning). */
    private const ICON = [
        'info' => 'text-sky-600',
        'success' => 'text-emerald-600',
        'warning' => 'text-amber-600',
        'danger' => 'text-red-600',
        'neutral' => 'text-gray-500',
        'brand' => 'text-foresty',
    ];

    /** Isi <svg> ikon bawaan (24x24, garis). Konstanta tepercaya. */
    private const PATH = [
        'info' => '<circle cx="12" cy="12" r="10" /><path d="M12 16v-4M12 8h.01" />',
        'success' => '<circle cx="12" cy="12" r="10" /><path d="m9 12 2 2 4-4" />',
        'warning' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" /><path d="M12 9v4M12 17h.01" />',
        'danger' => '<path d="M12 16h.01M12 8v4M15.312 2a2 2 0 0 1 1.414.586l4.688 4.688A2 2 0 0 1 22 8.688v6.624a2 2 0 0 1-.586 1.414l-4.688 4.688a2 2 0 0 1-1.414.586H8.688a2 2 0 0 1-1.414-.586l-4.688-4.688A2 2 0 0 1 2 15.312V8.688a2 2 0 0 1 .586-1.414l4.688-4.688A2 2 0 0 1 8.688 2z" />',
        'neutral' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />',
        'brand' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />',
    ];

    /** Nama jenis untuk pembaca layar dan awalan teks tersembunyi. */
    private const LABEL = [
        'id' => ['info' => 'Informasi', 'success' => 'Berhasil', 'warning' => 'Peringatan', 'danger' => 'Bahaya', 'neutral' => 'Catatan', 'brand' => 'Catatan'],
        'en' => ['info' => 'Information', 'success' => 'Success', 'warning' => 'Warning', 'danger' => 'Danger', 'neutral' => 'Note', 'brand' => 'Note'],
    ];

    public static function tone(mixed $v): string
    {
        return in_array($v, self::TONES, true) ? $v : 'info';
    }

    public static function style(mixed $v): string
    {
        return in_array($v, self::STYLES, true) ? $v : 'soft';
    }

    public static function box(mixed $style, mixed $tone): string
    {
        $style = self::style($style);

        return 'relative w-full ' . self::SHAPE[$style] . ' ' . self::BOX[$style][self::tone($tone)];
    }

    public static function icon(mixed $style, mixed $tone): string
    {
        $tone = self::tone($tone);
        $color = self::style($style) === 'solid' ? ($tone === 'warning' ? 'text-gray-900' : 'text-white') : self::ICON[$tone];

        return 'mt-0.5 h-6 w-6 shrink-0 ' . $color;
    }

    public static function iconPath(mixed $tone): string
    {
        return self::PATH[self::tone($tone)];
    }

    public static function label(mixed $tone, string $lang): string
    {
        return self::LABEL[$lang === 'id' ? 'id' : 'en'][self::tone($tone)];
    }
}
