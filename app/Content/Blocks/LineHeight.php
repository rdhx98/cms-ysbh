<?php

namespace App\Content\Blocks;

/**
 * Jarak antar baris blok Judul (rilis 106). Murni, tanpa Laravel.
 *
 * Judul besar yang membungkus ke beberapa baris (mis. judul hero di kolom sempit) terlihat jarang bila ia mewarisi tinggi baris
 * isi halaman (1.5). Penulis memilih di panel blok Judul: `data.line_height` = "rapat" | "sedang" | "longgar". Kelasnya dicetak oleh
 * PEMBUNGKUS blok (sections.blade.php untuk blok tingkat atas, LayoutStyle::columnChildClass untuk blok di dalam kolom), jadi berkas
 * renderer Judul milik proyek tidak perlu diubah (lihat catatan kelas di bawah).
 *
 * Bawaan "sedang" (1.25) dicetak juga untuk Judul lama yang belum punya nilai: itu yang membuat judul lama lebih rapat. Yang ingin tetap
 * seperti dulu memilih "longgar" (1.5). Nilai di luar daftar dibuang dan diganti bawaan, karena dicetak ke atribut class.
 *
 * Dua bentuk kelas dipasang bersama: `leading-*` pada pembungkus (diwarisi H1/H2, yang ukuran hurufnya tidak membawa tinggi baris) dan
 * `[&_*]:leading-*` (menimpa semua turunan). Yang kedua dibutuhkan H3: `text-xl md:text-2xl` menetapkan tinggi barisnya sendiri dan Tailwind v4
 * tidak mewariskan `--tw-leading`, sehingga H3 tidak mengikuti pembungkus (diukur di Chromium: 32px, bukan 30px).
 *
 * Kelas ditulis lengkap di bawah supaya terbaca pemindai Tailwind:
 * leading-[1.1] [&_*]:leading-[1.1] leading-tight [&_*]:leading-tight leading-normal [&_*]:leading-normal
 */
final class LineHeight
{
    /** Tipe yang memakai jarak baris tingkat blok. Paragraf tidak: ia sudah leading-relaxed sendiri. */
    public const TYPES = ['heading'];

    public const DEFAULT = 'sedang';

    public const CLASSES = [
        'rapat' => 'leading-[1.1] [&_*]:leading-[1.1]',
        'sedang' => 'leading-tight [&_*]:leading-tight',
        'longgar' => 'leading-normal [&_*]:leading-normal',
    ];

    /** Pilihan untuk panel (nilai => label). */
    public const OPTIONS = ['rapat' => 'Rapat', 'sedang' => 'Sedang', 'longgar' => 'Longgar'];

    public static function valid(mixed $value): string
    {
        return is_string($value) && isset(self::CLASSES[$value]) ? $value : self::DEFAULT;
    }

    public static function appliesTo(?string $type): bool
    {
        return in_array(str_replace('_', '-', strtolower((string) $type)), self::TYPES, true);
    }

    /** Kelas untuk pembungkus satu blok; '' bila bukan Judul. */
    public static function wrapperClass(array $block): string
    {
        if (!self::appliesTo($block['type'] ?? '')) {
            return '';
        }
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        return self::CLASSES[self::valid($data['line_height'] ?? null)];
    }
}
