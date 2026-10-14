<?php

namespace App\Content\Blocks;

/**
 * Gaya blok Tabel (rilis 34) -> kelas Tailwind, dan teks sel -> HTML aman. Murni, tanpa Laravel.
 * Semua kelas ditulis UTUH (terbaca pemindai Tailwind); nilai di luar daftar jatuh ke bawaan, jadi data dari browser tidak bisa
 * menyisipkan kelas sembarang. Sel = teks BIASA: di-escape dulu; hanya baris baru (<br>), **tebal** (<strong>), dan tautan https yang
 * ditambahkan, sehingga "usia <5 tahun" tampil apa adanya dan tidak ada HTML dari penulis yang lolos.
 */
final class TableStyle
{
    public const BORDERS = ['rows', 'grid', 'none'];
    public const DENSITIES = ['compact', 'normal', 'relaxed'];
    public const COLORS = ['foresty', 'coral', 'aurum', 'charcoal', 'light'];
    public const ALIGNS = ['left', 'center', 'right'];

    public const MAX_COLUMNS = 12;
    public const MAX_ROWS = 100;
    public const MAX_CELL = 600;

    private const HEAD = [
        'foresty' => 'bg-foresty text-white',
        'coral' => 'bg-coral text-white',
        'aurum' => 'bg-aurum text-gray-900',
        'charcoal' => 'bg-charcoal text-white',
        'light' => 'bg-gray-100 text-gray-800',
    ];

    /**
     * Warna yang sama dalam bentuk CSS langsung (rilis 38.3): variabel tema bila ada, nilai tetap bila tidak. Dipasang sebagai atribut style di
     * <thead> dan tiap <th>, supaya warna tidak bergantung pada Tailwind men-generate kelas bg-coral/bg-aurum/bg-charcoal (hanya muncul di berkas PHP,
     * dan variabel tema yang tidak dipakai utilitas apa pun tidak dicetak) dan tidak kalah oleh aturan CSS umum untuk <th>.
     * Isinya dari daftar tetap, bukan dari data pengguna.
     */
    private const HEAD_STYLE = [
        'foresty' => 'background-color:var(--color-foresty,#064F3B);color:#fff',
        'coral' => 'background-color:var(--color-coral,#E42326);color:#fff',
        'aurum' => 'background-color:var(--color-aurum,#EBCC26);color:#111827',
        'charcoal' => 'background-color:var(--color-charcoal,#2B2B2B);color:#fff',
        'light' => 'background-color:#F3F4F6;color:#1F2937',
    ];

    private const CELL_PAD = [
        'compact' => 'px-3 py-1.5',
        'normal' => 'px-4 py-3',
        'relaxed' => 'px-5 py-4',
    ];

    private const ALIGN = ['left' => 'text-left', 'center' => 'text-center', 'right' => 'text-right'];

    /** Pembungkus gulir: batas luar tabel. */
    public static function frame(string $border): string
    {
        return $border === 'none' ? 'overflow-x-auto' : 'overflow-x-auto rounded-xl border border-gray-200';
    }

    /** Garis di antara baris / kotak penuh. Dipasang di <tbody> (baris) atau di tiap sel (kotak). */
    public static function body(string $border): string
    {
        return self::border($border) === 'rows' ? 'divide-y divide-gray-200' : '';
    }

    public static function cellBorder(string $border): string
    {
        return self::border($border) === 'grid' ? 'border border-gray-200' : '';
    }

    public static function head(string $color): string
    {
        return self::HEAD[self::color($color)];
    }

    public static function headStyle(string $color): string
    {
        return self::HEAD_STYLE[self::color($color)];
    }

    public static function pad(string $density): string
    {
        return self::CELL_PAD[self::density($density)];
    }

    public static function align(string $align): string
    {
        return self::ALIGN[self::alignOf($align)];
    }

    public static function stripe(): string
    {
        return 'even:bg-gray-50';
    }

    public static function border(mixed $v): string
    {
        return is_string($v) && in_array($v, self::BORDERS, true) ? $v : 'rows';
    }

    public static function density(mixed $v): string
    {
        return is_string($v) && in_array($v, self::DENSITIES, true) ? $v : 'normal';
    }

    public static function color(mixed $v): string
    {
        return is_string($v) && in_array($v, self::COLORS, true) ? $v : 'foresty';
    }

    public static function alignOf(mixed $v): string
    {
        return is_string($v) && in_array($v, self::ALIGNS, true) ? $v : 'left';
    }

    /** Teks sel -> HTML aman. */
    public static function cell(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return '';
        }
        $lines = [];
        foreach (explode("\n", $text) as $line) {
            $out = '';
            // setiap potongan di-escape SENDIRI-SENDIRI: tanda ** tidak pernah menyisip ke dalam atribut tautan
            foreach (preg_split('/\*\*(.+?)\*\*/us', $line, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $i => $part) {
                $html = FaqText::line($part);
                $out .= $i % 2 === 1 ? '<strong>' . $html . '</strong>' : $html;
            }
            $lines[] = $out;
        }

        return implode('<br>', $lines);
    }
}
