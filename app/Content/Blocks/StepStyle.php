<?php

namespace App\Content\Blocks;

use App\Content\Palette;

/**
 * Gaya Grup Langkah (timeline), rilis 66. Murni, tanpa Laravel (selain Palette, yang juga murni).
 *
 * Satu-satunya sumber: registri (panel kanan) dan renderer step-group. Nilai dari browser TIDAK dicetak sebagai kelas: setiap nilai
 * dicocokkan ke daftar bentuk atau ke slot palet (Palette::slotFor), sisanya jatuh ke bawaan. Kelas yang dihasilkan memakai nama slot
 * (bg-forest, text-forest-on) yang sudah dibuat oleh @source inline palet; kelas bentuk dan keburaman ditulis UTUH di bawah ini.
 *
 * Data (semuanya opsional; data lama tanpa kunci baru tetap tampil sama seperti sebelumnya):
 *   node_shape   none | square | circle (bawaan) | rounded
 *   node_bg      bg-{slot}|bg-white      latar angka (diabaikan bila bentuk "none")
 *   node_text    text-{slot}|text-white  warna angka di atas latar
 *   node_ink     text-{slot}|text-white  warna angka bila bentuk "none" (tanpa latar)
 *   line_color   bg-{slot}               warna garis
 *   line_opacity opacity-30|60|100       kekuatan garis
 *   marker       number (bawaan) | dot     penanda: lingkaran berangka, atau titik kecil dengan label di sisi poros (rilis 74)
 *   orientation  vertical (bawaan) | horizontal   (LAMA: horizontal-top / horizontal-bottom, tetap dibaca)
 *   axis         left (bawaan) | center   poros VERTIKAL: di kiri, atau di tengah dengan kartu selang-seling (LAMA: side = left)
 *   axis_h       top (bawaan) | bottom | center   poros HORIZONTAL (rilis 77). Bila belum ada: dari orientasi lama (-top / -bottom),
 *                atau center bila axis lama = center
 *   reverse      true = urutan dibalik (vertikal: bawah ke atas; horizontal: kanan ke kiri), tanpa mengurutkan ulang blok (rilis 77)
 *   start        right (bawaan) | left   sisi kartu pertama pada poros tengah
 *   dot_size     s | m (bawaan) | l      ukuran titik
 *   label_color  text-{slot}|text-white  warna label pada mode titik
 *   start_edge   bottom (bawaan) | top   sisi kartu pertama pada poros tengah horizontal (rilis 75)
 *   fit          fixed (bawaan) | overflow   horizontal: poros memenuhi kontainer, atau selebar isi dan bisa digeser (rilis 75)
 *   justify      start (bawaan) | center | between   horizontal fixed: sebaran langkah
 *   step_width   s | m (bawaan) | l          horizontal: lebar tiap langkah (220 / 280 / 340px)
 *   labels       {idAnak: {id, en}}      label per langkah (teks polos); pada mode angka menimpa angka urut bila terisi
 *   node_color   LAMA: "bg-foresty text-white" (dipakai bila node_bg / node_text belum ada)
 *   line_color   LAMA: "bg-foresty/30"   (garis + keburaman dalam satu nilai)
 *
 * Kelas yang harus terbaca pemindai Tailwind (ditulis utuh): rounded-none rounded-full rounded-xl opacity-30 opacity-60 opacity-100
 * bg-white text-white shadow-sm rounded-xs rounded-sm rounded-md h-3 w-3 mt-1.5 h-4 w-4 mt-1 h-5 w-5 mt-0.5 text-base leading-6 font-bold
 */
final class StepStyle
{
    public const SHAPE_DEFAULT = 'circle';

    /** Label panel. Urutan = urutan tombol. */
    public const SHAPES = [
        'none' => 'Kosong',
        'square' => 'Siku',
        'circle' => 'Lingkaran',
        'rounded' => 'Tumpul',
    ];

    private const SHAPE_CLASS = [
        'none' => 'rounded-none',
        'square' => 'rounded-none',
        'circle' => 'rounded-full',
        'rounded' => 'rounded-xl',
    ];

    public const MARKER_DEFAULT = 'number';

    public const MARKERS = [
        'number' => 'Angka',
        'dot' => 'Titik + label',
    ];

    public const ORIENTATION_DEFAULT = 'vertical';

    public const ORIENTATIONS = [
        'vertical' => 'Vertikal',
        'horizontal' => 'Horizontal',
    ];

    public const AXIS_DEFAULT = 'left';

    /** Poros vertikal. */
    public const AXES = [
        'left' => 'Kiri',
        'center' => 'Tengah',
    ];

    public const AXIS_H_DEFAULT = 'top';

    /** Poros horizontal: garis di atas kartu, di bawah kartu, atau di tengah (kartu selang-seling atas/bawah). */
    public const AXES_H = [
        'top' => 'Atas',
        'bottom' => 'Bawah',
        'center' => 'Tengah',
    ];

    public const START_DEFAULT = 'right';

    public const STARTS = [
        'right' => 'Kanan',
        'left' => 'Kiri',
    ];

    public const EDGE_DEFAULT = 'bottom';

    public const EDGES = [
        'bottom' => 'Bawah',
        'top' => 'Atas',
    ];

    public const FIT_DEFAULT = 'fixed';

    public const FITS = [
        'fixed' => 'Tetap',
        'overflow' => 'Geser',
    ];

    public const JUSTIFY_DEFAULT = 'start';

    public const JUSTIFIES = [
        'start' => 'Kiri',
        'center' => 'Tengah',
        'between' => 'Antar',
    ];

    public const WIDTH_DEFAULT = 'm';

    public const WIDTHS = [
        's' => 'Sempit',
        'm' => 'Sedang',
        'l' => 'Lebar',
    ];

    /** Lebar langkah (px) per pilihan; dicetak sebagai angka bulat di style (bukan nilai dari browser). */
    public const WIDTH_PX = ['s' => 220, 'm' => 280, 'l' => 340];

    /** Lebar terkecil langkah pada mode Tetap sebelum poros mulai bisa digeser. */
    public const WIDTH_MIN = 160;

    /** Jumlah langkah terbanyak pada tata letak horizontal (kelas penempatan kolom ditulis utuh sampai 12). */
    public const HORIZONTAL_MAX = 12;

    public const DOT_DEFAULT = 'm';

    public const DOT_SIZES = [
        's' => 'Kecil',
        'm' => 'Sedang',
        'l' => 'Besar',
    ];

    /** Ukuran titik + margin atas agar titik sejajar dengan tengah baris pertama label (text-base leading-6 = 24px). */
    private const DOT_CLASS = [
        's' => 'h-3 w-3 mt-1.5',
        'm' => 'h-4 w-4 mt-1',
        'l' => 'h-5 w-5 mt-0.5',
    ];

    private const DOT_PX = ['s' => 12, 'm' => 16, 'l' => 20];

    /**
     * Sudut bentuk "Tumpul" pada titik. rounded-xl (12px) pada titik 12-20px menjadi lingkaran penuh, jadi sudutnya diperkecil sebanding
     * dengan ukuran titik (rilis 77, Tailwind v4): kecil rounded-xs 2px, sedang rounded-sm 4px, besar rounded-md 6px.
     */
    private const DOT_ROUNDED = ['s' => 'rounded-xs', 'm' => 'rounded-sm', 'l' => 'rounded-md'];

    public const LABEL_MAX = 60;
    public const LABEL_COLOR_DEFAULT = 'forest';

    public const OPACITY_DEFAULT = 'opacity-30';

    public const OPACITY = [
        'opacity-30' => 'Samar',
        'opacity-60' => 'Sedang',
        'opacity-100' => 'Penuh',
    ];

    public const BG_DEFAULT = 'forest';
    public const TEXT_DEFAULT = 'text-white';
    public const INK_DEFAULT = 'forest';
    public const LINE_DEFAULT = 'forest';

    /**
     * @return array{shape:string,badge:string,line:string,marker:string,axis:string,start:string,dot:string,label:string,edge:string,fit:string,justify:string,width:int,half:int,layout:string,pos:string,reverse:bool}
     *   layout = vertical | horizontal-top | horizontal-bottom (dipakai renderer); pos = left | top | bottom | center; axis = side | center (turunan pos);
     *   reverse = urutan langkah dibalik. badge = kelas lingkaran angka (tanpa ukuran/posisi); line = kelas potongan garis; dot = kelas titik ('' bila bentuk Kosong);
     *   label = kelas teks label mode titik
     */
    public static function resolve(array $d): array
    {
        $shape = self::str($d['node_shape'] ?? null);
        $shape = isset(self::SHAPE_CLASS[$shape]) ? $shape : self::SHAPE_DEFAULT;

        // ---- latar dan teks angka (kunci baru lebih dulu, lalu string lama "bg-foresty text-white")
        $legacy = preg_split('/\s+/', self::str($d['node_color'] ?? null)) ?: [];
        $legacyBg = $legacyText = '';
        foreach ($legacy as $t) {
            if (str_starts_with($t, 'bg-') && $legacyBg === '') {
                $legacyBg = $t;
            } elseif (str_starts_with($t, 'text-') && $legacyText === '') {
                $legacyText = $t;
            }
        }
        $bg = self::color(self::first($d['node_bg'] ?? null, $legacyBg), 'bg-', true) ?? self::BG_DEFAULT;
        $bgClass = 'bg-' . $bg;

        if ($shape === 'none') {
            $ink = self::color(self::first($d['node_ink'] ?? null), 'text-', true) ?? self::INK_DEFAULT;
            $badge = 'text-' . $ink . ' rounded-none';
        } else {
            $explicit = self::first($d['node_text'] ?? null, $legacyText);
            $text = self::color($explicit, 'text-', true);
            $textClass = $text !== null ? 'text-' . $text : ($explicit === '' ? Palette::autoText($bgClass) : self::TEXT_DEFAULT);
            $badge = $bgClass . ' ' . $textClass . ' ' . self::SHAPE_CLASS[$shape] . ' shadow-sm';
        }

        // ---- garis
        $lineRaw = self::str($d['line_color'] ?? null);
        $alpha = null;
        if (preg_match('#^(bg-[a-z0-9-]+)/(\d{1,3})$#D', $lineRaw, $m) === 1) {
            $lineRaw = $m[1];
            $alpha = (int) $m[2];
        }
        $line = self::color($lineRaw, 'bg-', true) ?? self::LINE_DEFAULT;
        $opacity = self::str($d['line_opacity'] ?? null);
        if (!isset(self::OPACITY[$opacity])) {
            $opacity = $alpha === null ? self::OPACITY_DEFAULT : ($alpha <= 45 ? 'opacity-30' : ($alpha <= 80 ? 'opacity-60' : 'opacity-100'));
        }

        // ---- rilis 74: penanda titik + label, poros, sisi awal
        $marker = self::pickKey($d['marker'] ?? null, self::MARKERS, self::MARKER_DEFAULT);
        // rilis 77: orientasi dua pilihan + poros menurut orientasi. Data lama (horizontal-top / -bottom, axis = side) tetap terbaca sama.
        $orient = self::str($d['orientation'] ?? null);
        $horizontal = str_starts_with($orient, 'horizontal');
        $axisRaw = self::str($d['axis'] ?? null);
        if ($horizontal) {
            $legacyPos = $axisRaw === 'center' ? 'center' : (str_ends_with($orient, 'bottom') ? 'bottom' : 'top');
            $pos = self::pickKey($d['axis_h'] ?? null, self::AXES_H, $legacyPos);
        } else {
            $pos = $axisRaw === 'center' ? 'center' : 'left';
        }
        $axis = $pos === 'center' ? 'center' : 'side';
        $layout = !$horizontal ? 'vertical' : ($pos === 'bottom' ? 'horizontal-bottom' : 'horizontal-top');
        $reverse = ($d['reverse'] ?? false) === true || ($d['reverse'] ?? null) === 1 || ($d['reverse'] ?? null) === '1';
        $start = self::pickKey($d['start_side'] ?? null, self::STARTS, self::START_DEFAULT);
        $dotSize = self::pickKey($d['dot_size'] ?? null, self::DOT_SIZES, self::DOT_DEFAULT);
        $dotRound = $shape === 'rounded' ? self::DOT_ROUNDED[$dotSize] : self::SHAPE_CLASS[$shape];
        $dot = $shape === 'none' ? '' : $bgClass . ' ' . $dotRound . ' ' . self::DOT_CLASS[$dotSize];
        $labelInk = self::color(self::first($d['label_color'] ?? null), 'text-', true) ?? self::LABEL_COLOR_DEFAULT;
        $label = 'text-base leading-6 font-bold text-' . $labelInk;

        $edge = self::pickKey($d['start_edge'] ?? null, self::EDGES, self::EDGE_DEFAULT);
        $fit = self::pickKey($d['fit'] ?? null, self::FITS, self::FIT_DEFAULT);
        $justify = self::pickKey($d['justify'] ?? null, self::JUSTIFIES, self::JUSTIFY_DEFAULT);
        $width = self::pickKey($d['step_width'] ?? null, self::WIDTHS, self::WIDTH_DEFAULT);
        // setengah lebar penanda (px): ke mana garis poros horizontal dimulai/berakhir (tengah penanda pertama/terakhir)
        $half = $marker === 'dot' ? ($shape === 'none' ? 0 : (int) (self::DOT_PX[$dotSize] / 2)) : 20;

        return ['shape' => $shape, 'badge' => $badge, 'line' => 'bg-' . $line . ' ' . $opacity,
            'marker' => $marker, 'axis' => $axis, 'start' => $start, 'dot' => $dot, 'label' => $label,
            'edge' => $edge, 'fit' => $fit, 'justify' => $justify, 'width' => self::WIDTH_PX[$width], 'half' => $half,
            'layout' => $layout, 'pos' => $pos, 'reverse' => $reverse];
    }

    /**
     * Label satu langkah dalam bahasa $lang: bahasa itu, lalu Indonesia, lalu bahasa lain yang terisi; '' bila kosong.
     * Teks polos (tanpa tag), dipotong; dicetak renderer lewat {{ }}. Peta salah bentuk dibaca sebagai kosong.
     *
     * @param array<mixed> $data data Grup Langkah
     */
    public static function label(array $data, string $childId, string $lang): string
    {
        $all = $data['labels'] ?? null;
        $row = is_array($all) ? ($all[$childId] ?? null) : null;
        if (is_string($row)) {
            $row = ['id' => $row];
        }
        if (!is_array($row)) {
            return '';
        }
        $order = array_values(array_unique(array_merge([$lang, 'id'], array_keys($row))));
        foreach ($order as $l) {
            $v = $row[$l] ?? null;
            if (is_string($v) || is_int($v)) {
                $t = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $v)) ?? '');
                if ($t !== '') {
                    return mb_substr($t, 0, self::LABEL_MAX);
                }
            }
        }

        return '';
    }

    /** @param array<string,string> $options */
    private static function pickKey(mixed $v, array $options, string $default): string
    {
        return is_string($v) && isset($options[$v]) ? $v : $default;
    }

    /** "bg-foresty" / "bg-forest" / "bg-custom-3" -> slot ("forest" / "custom-3"); "bg-white" -> "white" (bila $white); selain itu null. */
    private static function color(string $value, string $prefix, bool $white): ?string
    {
        if ($value === '' || !str_starts_with($value, $prefix)) {
            return null;
        }
        $name = substr($value, strlen($prefix));
        if ($white && $name === 'white') {
            return 'white';
        }
        if (str_ends_with($name, '-on')) {
            $name = substr($name, 0, -3);   // "text-forest-on" -> slot forest, kelas "-on" dibentuk ulang di bawah
            $slot = Palette::slotFor($name);

            return $slot === null ? null : $slot . '-on';
        }

        return Palette::slotFor($name);
    }

    private static function str(mixed $v): string
    {
        return is_string($v) ? trim($v) : '';
    }

    private static function first(mixed ...$values): string
    {
        foreach ($values as $v) {
            $s = self::str($v);
            if ($s !== '') {
                return $s;
            }
        }

        return '';
    }
}
