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

    /**
     * Animasi masuk per gambar (rilis 116: terpicu gulir, keadaan awal ada di HTML pertama). ENTER_FROM = kelas keadaan tersembunyi yang DICABUT skrip saat
     * gambar masuk ke layar; ENTER_REDUCE = padanannya untuk "kurangi gerakan" (tampil langsung); ENTER_BASE = peralihan.
     */
    private const ENTER_FROM = ['none' => '', 'fade' => 'opacity-0', 'up' => 'opacity-0 translate-y-6', 'zoom' => 'opacity-0 scale-95'];
    private const ENTER_REDUCE = [
        'none' => '',
        'fade' => 'motion-reduce:opacity-100',
        'up' => 'motion-reduce:opacity-100 motion-reduce:translate-y-0',
        'zoom' => 'motion-reduce:opacity-100 motion-reduce:scale-100',
    ];
    private const ENTER_BASE = 'transition duration-700 ease-out motion-reduce:transition-none';

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

    /** Warna garis tepi kartu logo yang tidak berasal dari palet (ditulis utuh untuk pemindai Tailwind). "border-transparent" = tanpa garis. */
    public const BORDERS = ['border-gray-200', 'border-transparent', 'border-white'];

    /**
     * Warna garis tepi kartu logo (rilis 116, menggantikan sakelar card_border): salah satu BORDERS atau "border-{slot palet}" (nama lama dinormalkan).
     * Selain itu abu-abu terang bawaan.
     */
    public static function borderColor(mixed $v): string
    {
        if (in_array($v, self::BORDERS, true)) {
            return $v;
        }
        if (is_string($v) && str_starts_with($v, 'border-') && class_exists(\App\Content\Palette::class) && ($slot = \App\Content\Palette::slotFor(substr($v, 7))) !== null) {
            return 'border-' . $slot;
        }

        return 'border-gray-200';
    }

    /** Warna keterangan yang bukan dari palet (ditulis utuh untuk pemindai Tailwind). */
    public const CAPTION_COLORS = ['text-gray-600', 'text-white'];

    /** Warna keterangan gambar (rilis 119): salah satu CAPTION_COLORS atau "text-{slot palet}" (nama lama dinormalkan). Selain itu abu-abu bawaan. */
    public static function captionColor(mixed $v): string
    {
        if (in_array($v, self::CAPTION_COLORS, true)) {
            return $v;
        }
        if (is_string($v) && str_starts_with($v, 'text-') && class_exists(\App\Content\Palette::class) && ($slot = \App\Content\Palette::slotFor(substr($v, 5))) !== null) {
            return 'text-' . $slot;
        }

        return 'text-gray-600';
    }

    /** Batas baris keterangan (rilis 120): none = semua baris, l1..l3 = dipotong "…" pada baris itu. Kelasnya ditulis utuh untuk pemindai Tailwind. */
    public const LINES = ['none', 'l1', 'l2', 'l3'];
    private const CLAMP = ['none' => '', 'l1' => 'line-clamp-1', 'l2' => 'line-clamp-2', 'l3' => 'line-clamp-3'];

    public static function captionLines(mixed $v): string
    {
        return in_array($v, self::LINES, true) ? $v : 'none';
    }

    /**
     * Kelas keterangan gambar. Rilis 119: tebal, miring, garis bawah, warna. Rilis 120: batas baris dan "menimpa gambar":
     * - di bawah gambar (bawaan): "mt-2 text-sm break-words" + warna pilihan;
     * - menimpa ($overlay): bilah gelap di dasar petak, teks putih (warna pilihan tidak dipakai, karena latarnya gelap), muncul saat petak disorot atau
     *   salah satu isinya difokus (kelompok bernama "fig" pada <figure>), selalu tampak di layar sentuh (hover: none) dan pada "kurangi gerakan" tanpa peralihan.
     *   `pointer-events-none`: klik tetap sampai ke tautan / pembesar di bawahnya. $always = selalu tampak (kanvas editor, supaya bisa disunting).
     * "w-0 min-w-full" (lebar mengikuti gambar) dan rata tengah (Logo) ditambahkan oleh renderer karena bergantung pada tata letak.
     */
    public static function caption(bool $bold, bool $italic, bool $underline, mixed $color, mixed $lines = 'none', bool $overlay = false, bool $always = false): string
    {
        $font = ($bold ? ' font-bold' : '') . ($italic ? ' italic' : '') . ($underline ? ' underline' : '');
        $clamp = self::CLAMP[self::captionLines($lines)];
        $clamp = $clamp !== '' ? ' ' . $clamp : '';

        if (! $overlay) {
            return trim('mt-2 text-sm break-words ' . self::captionColor($color) . $font . $clamp);
        }

        $show = $always
            ? 'opacity-100'
            : 'opacity-0 transition-opacity duration-300 group-hover/fig:opacity-100 group-focus-within/fig:opacity-100 [@media(hover:none)]:opacity-100 motion-reduce:transition-none';

        return trim('pointer-events-none absolute inset-x-0 bottom-0 rounded-b-xl bg-black/60 px-3 py-2 text-sm break-words text-white ' . $show . $font . $clamp);
    }

    /** Pembungkus grid "mengikuti gambar": baris yang membungkus, kartu selebar gambarnya. */
    public static function followWrap(mixed $mode): string
    {
        return 'flex flex-wrap items-start gap-4 ' . (self::mode($mode) === 'logos' ? 'justify-center' : 'justify-start');
    }

    /**
     * Kelas butir (<li> / butir carousel) bila animasi masuk aktif: [kelas tersembunyi yang dicabut skrip (juga dicetak di data-from), kelas tetap
     * (peralihan + padanan "kurangi gerakan")]. "none": ['', ''].
     */
    public static function enter(mixed $animation): array
    {
        $a = self::animation($animation);

        return $a === 'none' ? ['', ''] : [self::ENTER_FROM[$a], self::ENTER_BASE . ' ' . self::ENTER_REDUCE[$a]];
    }

    /**
     * Petak gambar. Sama rata: logo = kotak 3:2 dengan gambar utuh, foto = potongan berrasio pilihan, selebar kolom.
     * Mengikuti gambar: tinggi tetap (sm/md/lg), lebar mengikuti lebar gambar sehingga petak bisa berbeda lebar.
     * $bg = latar (cardBg), $border = warna garis tepi kartu logo (borderColor).
     */
    public static function tile(mixed $mode, mixed $ratio, bool $follow = false, mixed $height = 'md', mixed $bg = 'bg-white', mixed $border = 'border-gray-200'): string
    {
        $logos = self::mode($mode) === 'logos';
        $bg = self::cardBg($bg);
        $ring = 'outline-none focus-visible:ring-2 focus-visible:ring-foresty/50';
        $edge = $logos ? 'border ' . self::borderColor($border) : '';
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
