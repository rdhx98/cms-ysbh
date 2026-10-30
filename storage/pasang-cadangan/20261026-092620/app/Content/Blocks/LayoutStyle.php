<?php

namespace App\Content\Blocks;

/**
 * Pilihan tata letak blok Kolom dan Gambar (rilis 35) -> kelas Tailwind. Murni, tanpa Laravel.
 *
 * Satu-satunya sumber daftar kelas: registri (panel), renderer proyek (multi-columns, image), dan pemindai Tailwind (semua kelas
 * ditulis UTUH di sini, jadi terbentuk di CSS situs publik). Nilai di luar daftar jatuh ke bawaan; data dari browser tidak bisa
 * menyisipkan kelas sembarang lewat dua kunci ini.
 *
 * KOLOM  : grid baris tunggal; tinggi baris = kolom TERTINGGI (semua kolom diregangkan sebesar itu), lalu isi tiap kolom
 *          disejajarkan pada sumbu Y terhadap tinggi tersebut: atas / tengah / bawah / bagi rata (jarak sama di antara blok).
 * GAMBAR : tinggi tetap atau "penuh" (mengisi sisa tinggi kolom yang diregangkan tadi).
 */
final class LayoutStyle
{
    public const ALIGN_Y_DEFAULT = 'justify-start';

    /** Rata vertikal isi kolom (flex-col: justify-* = sumbu Y). */
    public const ALIGN_Y = [
        'justify-start' => 'Atas',
        'justify-center' => 'Tengah',
        'justify-end' => 'Bawah',
        'justify-between' => 'Bagi rata',
    ];

    public const IMAGE_HEIGHT_DEFAULT = 'h-auto';

    /** Tinggi gambar (label angka = rem; ringkas agar muat satu baris di panel). h-full = penuh (mengisi tinggi kolom); sisanya tinggi tetap dengan gambar dipotong/diletakkan sesuai "Pemotongan". */
    public const IMAGE_HEIGHT = [
        'h-auto' => 'Auto',
        'h-full' => 'Penuh',
        'h-48' => '12',
        'h-72' => '18',
        'h-96' => '24',
        'h-[32rem]' => '32',
    ];

    /** Kelas rata vertikal yang sah; selain itu bawaan (atas). */
    public static function alignY(mixed $value): string
    {
        return is_string($value) && isset(self::ALIGN_Y[$value]) ? $value : self::ALIGN_Y_DEFAULT;
    }

    /** Kelas tinggi gambar yang sah; selain itu otomatis. */
    public static function imageHeight(mixed $value): string
    {
        return is_string($value) && isset(self::IMAGE_HEIGHT[$value]) ? $value : self::IMAGE_HEIGHT_DEFAULT;
    }

    /**
     * Kelas <img>. Tinggi tetap & penuh memerlukan gambar yang mengisi kotaknya (w-full + h-*); object-fit dari "Pemotongan"
     * dicetak renderer. Penuh: gambar menyerap sisa tinggi figure (flex-1, min-h-0 agar bisa menyusut di kolom).
     */
    public static function imageClass(mixed $height): string
    {
        return match (self::imageHeight($height)) {
            'h-auto' => 'h-auto',
            'h-full' => 'h-full min-h-0 flex-1',
            default => self::imageHeight($height),
        };
    }

    /** Kelas <figure>: Penuh membuat figure mengisi pembungkusnya. */
    public static function figureClass(mixed $height): string
    {
        return self::imageHeight($height) === 'h-full' ? 'h-full min-h-0' : '';
    }

    /**
     * Pembungkus anak di dalam kolom (rilis 35):
     *  - anak berisi gambar Penuh harus ikut meregang (flex-1) agar gambar punya tinggi untuk diisi;
     *  - Jarak Bawah (data.margin_bottom) anak dicetak di sini: blok tingkat atas mendapatkannya dari pembungkus di sections.blade.php,
     *    tetapi anak di dalam kolom tidak melewati pembungkus itu, sehingga kontrol Jarak Bawah Tombol, Video, Galeri, dst. tidak berpengaruh.
     *    Hanya nilai yang DIPILIH penulis (ada di data dan sah) yang dicetak, supaya halaman lama tidak bergeser. Jenis yang mencetak margin
     *    sendiri (judul, paragraf, eyebrow, gambar, kartu: lihat Spacing::OWN) tidak digandakan.
     *
     * @param list<string> $allowedMargins nilai di cms.design.margin_bottom; kosong = cukup berbentuk sah
     */
    public static function columnChildClass(array $childBlock, array $allowedMargins = []): string
    {
        $type = str_replace('_', '-', (string) ($childBlock['type'] ?? ''));
        $data = is_array($childBlock['data'] ?? null) ? $childBlock['data'] : [];
        $cls = $type === 'image' && self::imageHeight($data['height'] ?? null) === 'h-full' ? 'w-full flex-1 min-h-0' : 'w-full';

        if (!Spacing::owns($type) && ($mb = Spacing::valid($data['margin_bottom'] ?? null)) !== null && ($allowedMargins === [] || in_array($mb, $allowedMargins, true))) {
            $cls .= ' ' . $mb;
        }

        return $cls;
    }
}
