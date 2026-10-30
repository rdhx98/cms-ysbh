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
 * BAYANGAN (rilis 100): satu sakelar nyala/mati untuk semua gambar (blok Gambar dan elemen Gambar di Kartu Builder); lihat imageShadow().
 * LEBAR KOLOM (rilis 100): pola siap pakai per jumlah kolom (data.col_ratio) atau "Kustom" (data.col_N_zone_width, bagian 1 sampai 12);
 *          columnTemplate() mengubahnya menjadi grid-template-columns.
 *
 * Kelas yang harus terbaca pemindai Tailwind (ditulis utuh): shadow-sm md:shadow-md flex h-3 w-7 gap-px rounded-sm bg-current
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
        // Rilis 106: jarak antar baris Judul (anak di kolom tidak melewati pembungkus sections.blade.php)
        if (LineHeight::appliesTo($type)) {
            $cls .= ' ' . LineHeight::wrapperClass(['type' => $type, 'data' => $data]);
        }

        return $cls;
    }

    // ------------------------------------------------------------------------------------------------------------------------
    // Bayangan gambar (rilis 100)
    // ------------------------------------------------------------------------------------------------------------------------

    /** Bayangan gambar saat menyala: sama dengan tampilan blok Gambar sebelum rilis 100 (halus di HP, sedang di layar lebar). */
    public const IMAGE_SHADOW = 'shadow-sm md:shadow-md';

    /**
     * Nilai sakelar dari data (bisa true/false, 1/0, "1"/"0", "true"/"false") -> bool. Kunci belum ada (null) atau bentuk tak dikenal
     * memakai $default, sehingga data lama tidak berubah tampilannya.
     */
    public static function flag(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return $value != 0;
        }
        if (is_string($value)) {
            $v = strtolower(trim($value));
            if (in_array($v, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }
            if (in_array($v, ['0', 'false', 'off', 'no', ''], true)) {
                return false;
            }
        }

        return $default;
    }

    /**
     * Kelas bayangan untuk gambar. $default = nilai bila penulis belum memilih: blok Gambar mandiri NYALA (dulu selalu berbayangan),
     * elemen Gambar di kartu MATI (dulu tanpa bayangan). Hanya dua string tetap yang pernah dicetak.
     */
    public static function imageShadow(mixed $value, bool $default = true): string
    {
        return self::flag($value, $default) ? self::IMAGE_SHADOW : '';
    }

    // ------------------------------------------------------------------------------------------------------------------------
    // Lebar kolom (rilis 100)
    // ------------------------------------------------------------------------------------------------------------------------

    public const RATIO_EQUAL = 'equal';
    public const RATIO_CUSTOM = 'custom';

    /** Pola siap pakai (selain sama rata) per jumlah kolom. Angka = perbandingan lebar kolom kiri ke kanan. */
    public const COLUMN_PRESETS = [
        2 => ['1:2', '2:1', '1:3', '3:1'],
        3 => ['1:2:1', '2:1:1', '1:1:2'],
    ];

    /** Pilihan "Kustom": bagian per kolom 1 sampai 6 (data lama boleh sampai 12; lihat CoreBlocks::fraction). */
    public const CUSTOM_PARTS = [1, 2, 3, 4, 5, 6];

    /** Kolom yang lebih sempit dari ini (persen) diberi peringatan di mode Kustom. */
    public const NARROW_PERCENT = 15;

    /** Jumlah kolom yang sah (1 sampai 6, bawaan 2). */
    public static function columnCount(mixed $value): int
    {
        return is_numeric($value) ? max(1, min(6, (int) $value)) : 2;
    }

    /** @return list<string> nilai col_ratio yang sah untuk jumlah kolom ini: equal, pola, custom */
    public static function ratioValues(int $count): array
    {
        return [self::RATIO_EQUAL, ...(self::COLUMN_PRESETS[$count] ?? []), self::RATIO_CUSTOM];
    }

    /**
     * Bagian lebar tiap kolom: angka (int|float) atau 'auto' (hanya dari data lama).
     *  - col_ratio pola yang sah untuk jumlah kolom ini -> pola itu; 'equal' -> sama rata
     *  - 'custom' atau belum ada (data lama, beranda bawaan) -> col_N_zone_width tiap kolom (tak sah = 1)
     *  - pola yang tidak cocok dengan jumlah kolom (mis. sisa "1:2" saat kolom jadi 3) -> sama rata
     *
     * @return list<int|float|string>
     */
    public static function columnShares(array $data): array
    {
        $n = self::columnCount($data['col_count'] ?? null);
        $ratio = $data['col_ratio'] ?? null;

        if (is_string($ratio) && in_array($ratio, self::COLUMN_PRESETS[$n] ?? [], true)) {
            return array_map('intval', explode(':', $ratio));
        }
        if ($ratio === null || $ratio === self::RATIO_CUSTOM) {
            $shares = [];
            for ($i = 1; $i <= $n; $i++) {
                $shares[] = self::share($data["col_{$i}_zone_width"] ?? null);
            }

            return $shares;
        }

        return array_fill(0, $n, 1);
    }

    /** Satu bagian lebar: 'auto', atau angka 0 sampai 12 (bukan nol); selain itu 1. */
    public static function share(mixed $value): int|float|string
    {
        if ($value === 'auto') {
            return 'auto';
        }
        if ((is_int($value) || is_float($value) || (is_string($value) && preg_match('/^\d{1,2}(?:\.\d{1,2})?$/D', $value) === 1)) && (float) $value > 0 && (float) $value <= 12) {
            return $value + 0;   // "2" -> 2, "1.5" -> 1.5
        }

        return 1;
    }

    /**
     * Nilai grid-template-columns (mulai layar md ke atas): "minmax(0,1fr) minmax(0,2fr)". minmax(0, ...) menjaga rasio tetap tepat
     * walau isi kolom lebar (kata panjang, tabel); di bawah md kolom menumpuk (kelas grid-cols-1 renderer).
     */
    public static function columnTemplate(array $data): string
    {
        return implode(' ', array_map(
            fn ($s) => $s === 'auto' ? 'auto' : 'minmax(0,' . rtrim(rtrim(number_format((float) $s, 2, '.', ''), '0'), '.') . 'fr)',
            self::columnShares($data),
        ));
    }

    /** Persen tiap kolom (bulat) untuk keterangan panel; kolom 'auto' dihitung 0 dan tidak ikut penyebut. @return list<int> */
    public static function columnPercents(array $data): array
    {
        $shares = self::columnShares($data);
        $sum = array_sum(array_map(fn ($s) => $s === 'auto' ? 0 : (float) $s, $shares));

        return array_map(fn ($s) => $s === 'auto' || $sum <= 0 ? 0 : (int) round((float) $s / $sum * 100), $shares);
    }

    /**
     * Pola dari bagian lebar (dipakai beranda bawaan agar panel menampilkan pilihan yang benar): [3, 3] -> equal, [1, 2] -> "1:2",
     * [3, 2] (bukan pola siap pakai) -> custom.
     *
     * @param list<int|float> $widths
     */
    public static function ratioFor(array $widths): string
    {
        $n = count($widths);
        if ($n < 2) {
            return self::RATIO_EQUAL;
        }
        foreach ($widths as $w) {
            if (!is_int($w) || $w < 1) {
                return self::RATIO_CUSTOM;   // pecahan atau tak sah: tidak dicocokkan ke pola
            }
        }
        $g = array_reduce($widths, fn ($c, $x) => $c === 0 ? $x : self::gcd($c, $x), 0);
        $reduced = array_map(fn ($x) => intdiv($x, $g), $widths);
        if (count(array_unique($reduced)) === 1) {
            return self::RATIO_EQUAL;
        }
        $key = implode(':', $reduced);

        return in_array($key, self::COLUMN_PRESETS[$n] ?? [], true) ? $key : self::RATIO_CUSTOM;
    }

    private static function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : self::gcd($b, $a % $b);
    }

    /**
     * Pilihan segmented "Lebar Kolom" untuk panel: batang mini sebanding dengan pola (html), keterangan persen (title). Kustom = ikon.
     *
     * @return list<array<string, mixed>>
     */
    public static function ratioOptions(int $count): array
    {
        $out = [];
        foreach (self::ratioValues($count) as $value) {
            if ($value === self::RATIO_CUSTOM) {
                // ikon sakelar geser sebaris (tanpa paket ikon, jadi tampil juga di lab pengujian)
                $out[] = ['value' => $value, 'html' => '<svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/></svg>', 'title' => 'Kustom: atur angka tiap kolom'];
                continue;
            }
            $parts = $value === self::RATIO_EQUAL ? array_fill(0, $count, 1) : array_map('intval', explode(':', $value));
            $sum = array_sum($parts);
            $bars = implode('', array_map(fn ($p) => '<i class="rounded-sm bg-current" style="flex:' . $p . ' 1 0%"></i>', $parts));
            $out[] = [
                'value' => $value,
                'html' => '<span class="flex h-3 w-7 gap-px" aria-hidden="true">' . $bars . '</span>',
                'title' => ($value === self::RATIO_EQUAL ? 'Sama rata: ' : '') . implode(' · ', array_map(fn ($p) => round($p / $sum * 100) . '%', $parts)),
            ];
        }

        return $out;
    }

    /** Pilihan Kustom per kolom: 1 sampai 6 bagian. */
    public static function partOptions(): array
    {
        return array_map(fn ($n) => ['value' => $n, 'label' => (string) $n], self::CUSTOM_PARTS);
    }
}
