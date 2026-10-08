<?php

namespace App\Content\Blocks;

/** Gaya blok Video -> kelas Tailwind (semua ditulis utuh; nilai di luar daftar jatuh ke bawaan). */
final class VideoStyle
{
    public const RATIOS = ['16:9', '4:3', '1:1', '9:16', '21:9'];
    public const WIDTHS = ['full', 'lg', 'md'];

    private const RATIO = [
        '16:9' => 'aspect-video',
        '4:3' => 'aspect-[4/3]',
        '1:1' => 'aspect-square',
        '9:16' => 'aspect-[9/16]',
        '21:9' => 'aspect-[21/9]',
    ];

    private const WIDTH = [
        'full' => 'w-full',
        'lg' => 'mx-auto w-full max-w-3xl',
        'md' => 'mx-auto w-full max-w-md',
    ];

    public static function ratio(mixed $v): string
    {
        return in_array($v, self::RATIOS, true) ? $v : '16:9';
    }

    public static function width(mixed $v): string
    {
        return in_array($v, self::WIDTHS, true) ? $v : 'full';
    }

    public static function ratioClass(mixed $v): string
    {
        return self::RATIO[self::ratio($v)];
    }

    public static function widthClass(mixed $v): string
    {
        return self::WIDTH[self::width($v)];
    }
}
