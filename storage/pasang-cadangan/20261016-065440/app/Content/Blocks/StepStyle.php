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
 *   node_color   LAMA: "bg-foresty text-white" (dipakai bila node_bg / node_text belum ada)
 *   line_color   LAMA: "bg-foresty/30"   (garis + keburaman dalam satu nilai)
 *
 * Kelas yang harus terbaca pemindai Tailwind (ditulis utuh): rounded-none rounded-full rounded-xl opacity-30 opacity-60 opacity-100
 * bg-white text-white shadow-sm
 */
final class StepStyle
{
    public const SHAPE_DEFAULT = 'circle';

    /** Label panel. Urutan = urutan tombol. */
    public const SHAPES = [
        'none' => 'Kosong',
        'square' => 'Siku',
        'circle' => 'Lingkaran',
        'rounded' => 'Rounded',
    ];

    private const SHAPE_CLASS = [
        'none' => 'rounded-none',
        'square' => 'rounded-none',
        'circle' => 'rounded-full',
        'rounded' => 'rounded-xl',
    ];

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
     * @return array{shape:string,badge:string,line:string}  badge = kelas lingkaran angka (tanpa ukuran/posisi); line = kelas potongan garis
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

        return ['shape' => $shape, 'badge' => $badge, 'line' => 'bg-' . $line . ' ' . $opacity];
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
