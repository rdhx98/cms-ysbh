<?php

namespace App\Content\Blocks;

/** Gaya blok Galeri / Logo -> kelas Tailwind (semua ditulis utuh; nilai di luar daftar jatuh ke bawaan). */
final class GalleryStyle
{
    public const MODES = ['photos', 'logos'];
    public const LAYOUTS = ['grid', 'carousel'];
    public const COLUMNS = ['2', '3', '4', '5', '6'];
    public const RATIOS = ['1:1', '4:3', '3:2', '16:9'];

    private const GRID = [
        2 => 'grid grid-cols-2 gap-4',
        3 => 'grid grid-cols-2 gap-4 md:grid-cols-3',
        4 => 'grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4',
        5 => 'grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5',
        6 => 'grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6',
    ];

    /** Lebar butir pada carousel dengan jarak gap-4 (1rem): (100% - (n-1) x 1rem) / n. Ponsel selalu 2, tablet paling banyak 3. */
    private const BASIS = [
        2 => 'basis-[calc(50%-0.5rem)]',
        3 => 'basis-[calc(50%-0.5rem)] md:basis-[calc(33.333%-0.667rem)]',
        4 => 'basis-[calc(50%-0.5rem)] md:basis-[calc(33.333%-0.667rem)] lg:basis-[calc(25%-0.75rem)]',
        5 => 'basis-[calc(50%-0.5rem)] md:basis-[calc(33.333%-0.667rem)] lg:basis-[calc(20%-0.8rem)]',
        6 => 'basis-[calc(50%-0.5rem)] md:basis-[calc(33.333%-0.667rem)] lg:basis-[calc(16.666%-0.833rem)]',
    ];

    private const RATIO = ['1:1' => 'aspect-square', '4:3' => 'aspect-[4/3]', '3:2' => 'aspect-[3/2]', '16:9' => 'aspect-video'];

    public static function mode(mixed $v): string
    {
        return in_array($v, self::MODES, true) ? $v : 'photos';
    }

    public static function layout(mixed $v): string
    {
        return in_array($v, self::LAYOUTS, true) ? $v : 'grid';
    }

    /** Disimpan sebagai teks ('3') supaya cocok dengan nilai pilihan di inspektur; dipakai sebagai bilangan bulat saat dirender. */
    public static function columns(mixed $v): string
    {
        $v = is_int($v) ? (string) $v : $v;

        return in_array($v, self::COLUMNS, true) ? $v : '3';
    }

    public static function ratio(mixed $v): string
    {
        return in_array($v, self::RATIOS, true) ? $v : '4:3';
    }

    public static function grid(mixed $columns): string
    {
        return self::GRID[(int) self::columns($columns)];
    }

    public static function basis(mixed $columns): string
    {
        return self::BASIS[(int) self::columns($columns)];
    }

    /** Petak gambar: logo = kotak putih 3:2 dengan gambar utuh; foto = potongan mengisi petak dengan rasio pilihan. */
    public static function tile(mixed $mode, mixed $ratio): string
    {
        return self::mode($mode) === 'logos'
            ? 'flex aspect-[3/2] w-full items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white p-4 outline-none focus-visible:ring-2 focus-visible:ring-foresty/50'
            : 'block w-full overflow-hidden rounded-xl bg-gray-100 outline-none focus-visible:ring-2 focus-visible:ring-foresty/50 ' . self::RATIO[self::ratio($ratio)];
    }

    public static function image(mixed $mode, bool $grayscale): string
    {
        if (self::mode($mode) === 'logos') {
            return 'max-h-full max-w-full object-contain transition'
                . ($grayscale ? ' grayscale opacity-70 group-hover:grayscale-0 group-hover:opacity-100 group-focus-visible:grayscale-0 group-focus-visible:opacity-100' : '');
        }

        return 'h-full w-full object-cover transition-transform duration-300 group-hover:scale-105';
    }
}
