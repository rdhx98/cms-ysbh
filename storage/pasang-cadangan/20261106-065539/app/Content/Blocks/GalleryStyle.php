<?php

namespace App\Content\Blocks;

/** Gaya blok Galeri / Logo -> kelas Tailwind (semua ditulis utuh; nilai di luar daftar jatuh ke bawaan). */
final class GalleryStyle
{
    public const MODES = ['photos', 'logos'];
    public const LAYOUTS = ['grid', 'carousel'];
    public const COLUMNS = ['1', '2', '3', '4', '5', '6'];
    public const RATIOS = ['1:1', '4:3', '3:2', '16:9'];
    /** Rilis 113: tinggi kartu saat lebarnya mengikuti gambar, animasi masuk, skala saat disorot. */
    public const HEIGHTS = ['sm', 'md', 'lg'];
    public const ANIMATIONS = ['none', 'fade', 'up', 'zoom'];
    public const SCALES = ['none', 'sm', 'md', 'lg'];

    // 1 kolom (rilis 35): satu gambar selebar blok di semua ukuran layar (ponsel juga 1, bukan 2)
    private const GRID = [
        1 => 'grid grid-cols-1 gap-4',
        2 => 'grid grid-cols-2 gap-4',
        3 => 'grid grid-cols-2 gap-4 md:grid-cols-3',
        4 => 'grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4',
        5 => 'grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5',
        6 => 'grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6',
    ];

    /** Lebar butir pada carousel dengan jarak gap-4 (1rem): (100% - (n-1) x 1rem) / n. Ponsel selalu 2, tablet paling banyak 3. */
    private const BASIS = [
        1 => 'basis-full',
        2 => 'basis-[calc(50%-0.5rem)]',
        3 => 'basis-[calc(50%-0.5rem)] md:basis-[calc(33.333%-0.667rem)]',
        4 => 'basis-[calc(50%-0.5rem)] md:basis-[calc(33.333%-0.667rem)] lg:basis-[calc(25%-0.75rem)]',
        5 => 'basis-[calc(50%-0.5rem)] md:basis-[calc(33.333%-0.667rem)] lg:basis-[calc(20%-0.8rem)]',
        6 => 'basis-[calc(50%-0.5rem)] md:basis-[calc(33.333%-0.667rem)] lg:basis-[calc(16.666%-0.833rem)]',
    ];

    private const RATIO = ['1:1' => 'aspect-square', '4:3' => 'aspect-[4/3]', '3:2' => 'aspect-[3/2]', '16:9' => 'aspect-video'];

    /** Tinggi kartu pada "mengikuti gambar": logo (termasuk ruang tepi) dan foto. */
    private const HEIGHT_LOGO = ['sm' => 'h-16', 'md' => 'h-24', 'lg' => 'h-32'];
    private const HEIGHT_PHOTO = ['sm' => 'h-40', 'md' => 'h-56', 'lg' => 'h-72'];

    /** Skala gambar saat disorot (tertulis utuh untuk pemindai Tailwind). "Kurangi gerakan" membatalkannya. */
    private const SCALE = [
        'none' => '',
        'sm' => 'group-hover:scale-105 motion-reduce:group-hover:scale-100',
        'md' => 'group-hover:scale-110 motion-reduce:group-hover:scale-100',
        'lg' => 'group-hover:scale-125 motion-reduce:group-hover:scale-100',
    ];

    /** Animasi masuk: keadaan awal (sebelum terlihat) dan akhir. Akhir selalu mencakup semua sumbu supaya peralihan mulus. */
    private const ENTER_FROM = ['none' => '', 'fade' => 'opacity-0', 'up' => 'opacity-0 translate-y-6', 'zoom' => 'opacity-0 scale-95'];
    private const ENTER_TO = 'opacity-100 translate-y-0 scale-100';

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

    public static function height(mixed $v): string
    {
        return in_array($v, self::HEIGHTS, true) ? $v : 'md';
    }

    public static function animation(mixed $v): string
    {
        return in_array($v, self::ANIMATIONS, true) ? $v : 'up';
    }

    /** Bawaan bila kosong: foto "sm" (sama dengan perilaku sebelum rilis 113), logo "none" (dulu logo tidak membesar). */
    public static function scale(mixed $v, mixed $mode = 'photos'): string
    {
        return in_array($v, self::SCALES, true) ? $v : (self::mode($mode) === 'logos' ? 'none' : 'sm');
    }

    /**
     * Warna latar kartu: "bg-white", "bg-transparent" (tanpa latar) atau "bg-{slot palet}" (nama lama seperti bg-foresty dinormalkan). Selain itu bg-white.
     * Kelas-kelasnya ada di safelist palet (Palette::safelist), jadi tetap dibuat walau dibentuk di PHP.
     */
    public static function cardBg(mixed $v): string
    {
        if ($v === 'bg-white' || $v === 'bg-transparent') {
            return $v;
        }
        if (is_string($v) && str_starts_with($v, 'bg-') && class_exists(\App\Content\Palette::class) && ($slot = \App\Content\Palette::slotFor(substr($v, 3))) !== null) {
            return 'bg-' . $slot;
        }

        return 'bg-white';
    }

    /** Pembungkus grid "mengikuti gambar": baris yang membungkus, kartu selebar gambarnya. */
    public static function followWrap(mixed $mode): string
    {
        return 'flex flex-wrap items-start gap-4 ' . (self::mode($mode) === 'logos' ? 'justify-center' : 'justify-start');
    }

    /** Pembungkus butir (<li> / butir carousel) saat animasi masuk aktif: [kelas awal, kelas akhir, kelas peralihan]. */
    public static function enter(mixed $animation): array
    {
        $a = self::animation($animation);

        return $a === 'none' ? ['', '', ''] : [self::ENTER_FROM[$a], self::ENTER_TO, 'transition duration-700 ease-out motion-reduce:transition-none'];
    }

    /**
     * Petak gambar. Sama rata: logo = kotak 3:2 dengan gambar utuh, foto = potongan berrasio pilihan, selebar kolom.
     * Mengikuti gambar: tinggi tetap (sm/md/lg), lebar mengikuti lebar gambar sehingga petak bisa berbeda lebar.
     * $bg = latar (cardBg), $border = garis tepi kartu logo.
     */
    public static function tile(mixed $mode, mixed $ratio, bool $follow = false, mixed $height = 'md', mixed $bg = 'bg-white', bool $border = true): string
    {
        $logos = self::mode($mode) === 'logos';
        $bg = self::cardBg($bg);
        $ring = 'outline-none focus-visible:ring-2 focus-visible:ring-foresty/50';
        $edge = $logos && $border ? 'border border-gray-200' : '';
        $h = $logos ? self::HEIGHT_LOGO[self::height($height)] : self::HEIGHT_PHOTO[self::height($height)];

        if ($logos) {
            $shape = $follow ? "flex w-fit max-w-full min-w-24 items-center justify-center overflow-hidden rounded-xl p-4 $h" : 'flex aspect-[3/2] w-full items-center justify-center overflow-hidden rounded-xl p-4';
        } else {
            $shape = $follow ? "block w-fit max-w-full overflow-hidden rounded-xl $h" : 'block w-full overflow-hidden rounded-xl ' . self::RATIO[self::ratio($ratio)];
        }

        return trim(preg_replace('/\s+/', ' ', "$shape $bg $edge $ring"));
    }

    /**
     * Gambar. Dua efek saat disorot yang berdiri sendiri (rilis 115): $grayscale = hitam-putih saat diam, berwarna saat disorot (logo); $scale = none|sm|md|lg.
     */
    public static function image(mixed $mode, bool $grayscale, mixed $scale = null, bool $follow = false): string
    {
        $logos = self::mode($mode) === 'logos';
        $scale = self::scale($scale, $mode);
        $zoom = self::SCALE[$scale];

        if ($logos) {
            $base = $follow ? 'h-full w-auto max-w-full object-contain' : 'max-h-full max-w-full object-contain';
            $gray = $grayscale ? ' grayscale opacity-70 group-hover:grayscale-0 group-hover:opacity-100 group-focus-visible:grayscale-0 group-focus-visible:opacity-100' : '';
            $motion = $grayscale || $zoom !== '' ? ' transition duration-300' : '';

            return trim("$base$motion$gray " . $zoom);
        }

        $base = $follow ? 'block h-full w-auto max-w-full object-cover' : 'h-full w-full object-cover';

        return trim($base . ($zoom !== '' ? ' transition-transform duration-300 ' . $zoom : ''));
    }
}
