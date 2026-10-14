<?php

namespace App\Content;

/**
 * Palet warna situs yang diatur dari CMS (rilis 57). MURNI: tanpa basis data, tanpa Laravel.
 *
 * Palet = deretan SLOT bernama (forest, aurum, coral, ...) ditambah slot kustom (custom-1 ... custom-8). Tiap slot: label, hex, dan
 * warna teks di atasnya ("on"): kosong/null = OTOMATIS (putih atau gelap, mana yang kontrasnya lebih tinggi), atau hex pilihan sendiri.
 *
 * Cara kerja di situs: hex dicetak sebagai variabel CSS (:root{--pal-forest:#064F3B; --pal-forest-on:#FFFFFF; ...}) dan blok @theme Tailwind
 * (themeBlock()) mengarahkan kelas yang SUDAH ADA (bg-forest, text-coral, border-aurum/20, ...) ke variabel itu. Nama lama yang dilebur
 * tetap berlaku sebagai alias (foresty -> forest, goldy -> aurum, misty -> mist, goldy-soft -> aurum-soft), jadi isi konten lama tidak
 * perlu dimigrasi. Kelas pasangan teks: text-{slot}-on.
 */
final class Palette
{
    public const KEY = 'palette';
    public const CUSTOM = 8;
    public const MAX_LABEL = 30;
    public const ON_LIGHT = '#FFFFFF';
    public const ON_DARK = '#1F2937';
    /** Batas kontras teks biasa (WCAG AA). */
    public const MIN_CONTRAST = 4.5;

    /** key => [label, hex bawaan (= warna situs sebelum rilis ini), alias nama lama] */
    private const BASE = [
        'forest' => ['Hijau Forest', '#064F3B', ['foresty']],
        'aurum' => ['Emas Aurum', '#E5C423', ['goldy']],
        'aurum-soft' => ['Emas Muda', '#F7EBAF', ['goldy-soft']],
        'coral' => ['Koral', '#E42326', []],
        'charcoal' => ['Charcoal', '#1F2937', []],
        'ink-soft' => ['Teks Lembut', '#4B5D53', []],
        'paper' => ['Kertas', '#FBF7EA', []],
        'mist' => ['Mist', '#E9F1EB', ['misty']],
        'sage-soft' => ['Sage Muda', '#D2E7DF', []],
        'amber' => ['Amber', '#D97706', []],
        // Rilis 60: warna program, selaras dengan palet yayasan. Tiap program: warna utama + warna muda (latar label/kartu; teks gelap terbaca, kontras > 10).
        // Utama dipilih agar teks di atasnya (putih/gelap, otomatis) terbaca >= 4,5 dan tiap program tetap berbeda untuk buta warna merah-hijau.
        'kia' => ['KIA', '#E0679A', []],
        'kia-soft' => ['KIA Muda', '#FCDCEC', []],
        'imunisasi' => ['Imunisasi', '#1A4793', []],
        'imunisasi-soft' => ['Imunisasi Muda', '#DBE9FA', []],
        'malaria' => ['Malaria', '#A347C2', []],
        'malaria-soft' => ['Malaria Muda', '#EBE0F8', []],
        'hiv' => ['HIV', '#A3153A', []],
        'hiv-soft' => ['HIV Muda', '#FBD1CB', []],
        'tbc' => ['TBC', '#57A630', []],
        'tbc-soft' => ['TBC Muda', '#DFF2D4', []],
    ];

    /** @return list<string> kunci slot berurutan: bernama dulu, lalu custom-1..custom-8 */
    public static function keys(): array
    {
        $keys = array_keys(self::BASE);
        for ($i = 1; $i <= self::CUSTOM; $i++) {
            $keys[] = 'custom-' . $i;
        }

        return $keys;
    }

    public static function isCustom(string $key): bool
    {
        return str_starts_with($key, 'custom-');
    }

    /** @return array<string,array{label:string,hex:string,on:?string}> */
    public static function defaults(): array
    {
        $out = [];
        foreach (self::BASE as $key => [$label, $hex]) {
            $out[$key] = ['label' => $label, 'hex' => $hex, 'on' => null];
        }
        for ($i = 1; $i <= self::CUSTOM; $i++) {
            $out['custom-' . $i] = ['label' => 'Kustom ' . $i, 'hex' => '', 'on' => null];
        }

        return $out;
    }

    /** "#abc" / "#AABBCC" -> "#AABBCC"; selain itu null. */
    public static function hex(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $v = strtoupper(trim($value));
        if (preg_match('/^#([0-9A-F])([0-9A-F])([0-9A-F])$/D', $v, $m)) {
            return '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
        }

        return preg_match('/^#[0-9A-F]{6}$/D', $v) === 1 ? $v : null;
    }

    private static function plain(mixed $value): string
    {
        $text = is_scalar($value) ? strip_tags((string) $value) : '';
        $text = preg_replace('/[\x00-\x1f\x7f]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /** Luminansi relatif WCAG 0..1. $hex harus sudah sah (#RRGGBB). */
    public static function luminance(string $hex): float
    {
        $hex = self::hex($hex) ?? '#000000';
        $c = [];
        foreach ([1, 3, 5] as $i) {
            $v = hexdec(substr($hex, $i, 2)) / 255;
            $c[] = $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    /** Rasio kontras WCAG 1..21 antara dua warna. */
    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** Warna teks otomatis di atas $hex: putih atau gelap, mana yang kontrasnya lebih tinggi. */
    public static function autoOn(string $hex): string
    {
        return self::contrast($hex, self::ON_LIGHT) >= self::contrast($hex, self::ON_DARK) ? self::ON_LIGHT : self::ON_DARK;
    }

    /** Warna teks yang berlaku untuk satu slot: pilihan manual bila sah, kalau tidak otomatis. */
    public static function on(array $slot): string
    {
        $hex = self::hex($slot['hex'] ?? '') ?? '#000000';

        return self::hex($slot['on'] ?? null) ?? self::autoOn($hex);
    }

    /**
     * Data tersimpan (teks JSON atau larik) -> palet lengkap. Slot/kolom yang tidak ada atau tidak sah memakai bawaan; kunci asing dibuang.
     *
     * @return array<string,array{label:string,hex:string,on:?string}>
     */
    public static function resolve(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        $out = self::defaults();
        if (!is_array($raw)) {
            return $out;
        }
        foreach ($out as $key => $slot) {
            $row = $raw[$key] ?? null;
            if (!is_array($row)) {
                continue;
            }
            $label = self::plain($row['label'] ?? '');
            $hex = self::hex($row['hex'] ?? null);
            if ($hex === null) {
                // kosong sah hanya untuk slot kustom (= tidak dipakai); selain itu bawaan
                $hex = self::isCustom($key) && trim((string) (is_scalar($row['hex'] ?? null) ? $row['hex'] : '')) === '' && array_key_exists('hex', $row) ? '' : $slot['hex'];
            }
            $out[$key] = [
                'label' => $label !== '' && mb_strlen($label) <= self::MAX_LABEL ? $label : $slot['label'],
                'hex' => $hex,
                'on' => self::hex($row['on'] ?? null),
            ];
        }

        return $out;
    }

    /**
     * Data dari formulir -> data yang boleh disimpan. Galat memblokir simpan; peringatan (kontras rendah) tidak.
     *
     * @return array{data:array<string,array{label:string,hex:string,on:?string}>,errors:list<string>,warnings:list<string>}
     */
    public static function sanitize(mixed $raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $errors = [];
        $warnings = [];
        $data = [];
        foreach (self::defaults() as $key => $default) {
            $row = is_array($raw[$key] ?? null) ? $raw[$key] : [];
            $name = $default['label'];
            $label = self::plain($row['label'] ?? '');
            if ($label === '') {
                $label = $default['label'];
            } elseif (mb_strlen($label) > self::MAX_LABEL) {
                $errors[] = "Slot $key: nama terlalu panjang (maksimal " . self::MAX_LABEL . ' huruf).';
                $label = mb_substr($label, 0, self::MAX_LABEL);
            }
            $rawHex = is_scalar($row['hex'] ?? null) ? trim((string) $row['hex']) : '';
            $hex = self::hex($rawHex);
            if ($hex === null) {
                if ($rawHex === '' && self::isCustom($key)) {
                    $hex = '';   // slot kustom boleh kosong = tidak dipakai
                } else {
                    $errors[] = "$label ($key): warna harus berbentuk #RRGGBB.";
                    $hex = $default['hex'];
                }
            }
            $rawOn = is_scalar($row['on'] ?? null) ? trim((string) $row['on']) : '';
            $on = self::hex($rawOn);
            if ($rawOn !== '' && $on === null) {
                $errors[] = "$label ($key): warna teks harus berbentuk #RRGGBB (atau Otomatis).";
            }
            $data[$key] = ['label' => $label, 'hex' => $hex, 'on' => $on];
            if ($hex !== '') {
                $ratio = self::contrast($hex, self::on($data[$key]));
                if ($ratio < self::MIN_CONTRAST) {
                    $warnings[] = "$label ($key): teks di atas warna ini kurang terbaca (kontras " . number_format($ratio, 1) . ', disarankan minimal ' . self::MIN_CONTRAST . ').';
                }
            }
        }

        return ['data' => $data, 'errors' => $errors, 'warnings' => $warnings];
    }

    /** Bentuk formulir: tiap slot punya `auto` (true = warna teks otomatis) dan `on` (hex manual, '' bila otomatis). */
    public static function forEditor(array $palette): array
    {
        $out = [];
        foreach (self::resolve($palette) as $key => $slot) {
            $out[$key] = ['label' => $slot['label'], 'hex' => $slot['hex'], 'auto' => $slot['on'] === null, 'on' => $slot['on'] ?? ''];
        }

        return $out;
    }

    /** Kebalikan forEditor: bentuk formulir -> larik untuk sanitize(). */
    public static function fromEditor(mixed $form): array
    {
        $out = [];
        foreach (is_array($form) ? $form : [] as $key => $slot) {
            if (!is_array($slot)) {
                continue;
            }
            $auto = !isset($slot['auto']) || (bool) $slot['auto'];
            $out[$key] = ['label' => $slot['label'] ?? '', 'hex' => $slot['hex'] ?? '', 'on' => $auto ? null : ($slot['on'] ?? '')];
        }

        return $out;
    }

    /**
     * CSS variabel untuk <head> situs: ":root{--pal-forest:#064F3B;--pal-forest-on:#FFFFFF;...}". Hanya hex yang sah yang pernah dicetak
     * (aman dicetak mentah). Slot kosong tidak dicetak, sehingga cadangan di themeBlock() yang berlaku.
     */
    public static function css(array $palette): string
    {
        $vars = '';
        foreach (self::resolve($palette) as $key => $slot) {
            if ($slot['hex'] === '') {
                continue;
            }
            $vars .= "--pal-$key:{$slot['hex']};--pal-$key-on:" . self::on($slot) . ';';
        }

        return ':root{' . $vars . '}';
    }

    /**
     * Blok @theme untuk berkas CSS Tailwind (landing; CMS di tahap berikutnya). Mengarahkan kelas lama ke variabel palet, dengan cadangan
     * warna bawaan bila variabel belum dicetak. Dihasilkan oleh `php tools/palette-theme.php`.
     */
    public static function themeBlock(): string
    {
        $lines = ["@theme static {"];
        foreach (self::defaults() as $key => $slot) {
            $fallback = $slot['hex'] !== '' ? $slot['hex'] : 'transparent';
            $onFallback = $slot['hex'] !== '' ? self::autoOn($slot['hex']) : self::ON_LIGHT;
            $lines[] = "    --color-$key: var(--pal-$key, $fallback);";
            $lines[] = "    --color-$key-on: var(--pal-$key-on, $onFallback);";
            foreach (self::BASE[$key][2] ?? [] as $alias) {
                $lines[] = "    --color-$alias: var(--pal-$key, $fallback);   /* nama lama, dilebur ke $key */";
            }
        }
        $lines[] = '}';

        return implode("\n", $lines) . "\n";
    }

    /**
     * Rilis 58: @source inline(...) untuk Tailwind. Kelas seperti bg-custom-3 / text-aurum-soft / border-sage-soft dibentuk oleh PHP, jadi
     * pemindai Tailwind tidak pernah melihatnya tertulis; tanpa baris ini kelasnya tidak dibuat dan warna tidak muncul. Taruh di LUAR @theme.
     */
    public static function sourceBlock(): string
    {
        $keys = implode(',', self::keys());

        return "@source inline(\"{bg,text,border}-{{$keys}}\");\n@source inline(\"text-{{$keys}}-on\");\n";
    }

    /**
     * Rilis 58: opsi untuk kontrol swatches, dibuat dari palet. Bentuknya sama dengan config('cms.design.*') (name, value, preview).
     *
     * @param 'bg'|'text'|'border'|'hex' $kind  bg = bg-{slot} | text = text-{slot} (preview: latar berkontras terbaik) | border = border-{slot} | hex = nilai #RRGGBB
     * @param list<string>               $hide  slot yang disembunyikan dari picker ini
     * @param list<string>               $extra pilihan tetap di luar palet: 'white', 'transparent'
     * @return list<array<string,mixed>>
     */
    public static function swatchOptions(string $kind, mixed $palette, array $hide = [], array $extra = []): array
    {
        $palette = self::resolve($palette);
        $out = [];

        if (in_array('transparent', $extra, true) && $kind === 'border') {
            $out[] = ['name' => 'Transparan', 'value' => 'border-transparent', 'preview' => 'bg-transparent', 'is_transparent' => true];
        }
        if (in_array('white', $extra, true) && in_array($kind, ['bg', 'border'], true)) {
            $out[] = $kind === 'bg'
                ? ['name' => 'Putih', 'value' => 'bg-white', 'hex' => '#FFFFFF']
                : ['name' => 'Putih', 'value' => 'border-white', 'preview' => 'bg-white', 'hex' => '#FFFFFF'];
        }

        foreach ($palette as $key => $slot) {
            if (($slot['hex'] ?? '') === '' || in_array($key, $hide, true)) {
                continue;   // slot kustom kosong = belum dipakai; yang disembunyikan tidak ditampilkan
            }
            $name = $slot['label'] !== '' ? $slot['label'] : $key;
            // 'hex' = warna slot saat ini, untuk wajah swatch di admin (admin tidak mencetak palet, jadi kelas bg-{slot} di sana bukan warna palet)
            $out[] = match ($kind) {
                'text' => ['name' => $name, 'value' => "text-$key", 'preview' => ($pv = self::previewBg($slot['hex'], $palette)), 'hex' => $slot['hex'], 'preview_hex' => self::previewHex($pv, $palette)],
                'border' => ['name' => $name, 'value' => "border-$key", 'preview' => "bg-$key", 'hex' => $slot['hex']],
                'hex' => ['name' => $name, 'value' => "var(--color-$key)", 'hex' => $slot['hex']],
                default => ['name' => $name, 'value' => "bg-$key", 'hex' => $slot['hex']],
            };
        }

        if (in_array('white', $extra, true) && $kind === 'text') {
            $out[] = ['name' => 'Putih', 'value' => 'text-white', 'preview' => 'bg-charcoal', 'hex' => '#FFFFFF', 'preview_hex' => self::hex($palette['charcoal']['hex'] ?? '') ?? '#1F2937'];
        }

        return $out;
    }

    private static function previewHex(string $class, array $palette): string
    {
        $key = substr($class, 3);

        return $key === 'white' ? '#FFFFFF' : (self::hex($palette[$key]['hex'] ?? '') ?? '#FFFFFF');
    }

    /** Kelas latar untuk memperagakan teks berwarna $hex: slot paper/charcoal/forest atau putih, mana yang paling kontras. */
    private static function previewBg(string $hex, array $palette): string
    {
        $best = 'bg-white';
        $bestRatio = self::contrast($hex, '#FFFFFF');
        foreach (['paper', 'charcoal', 'forest'] as $key) {
            $bg = self::hex($palette[$key]['hex'] ?? '');
            if ($bg !== null && ($ratio = self::contrast($hex, $bg)) > $bestRatio) {
                $best = "bg-$key";
                $bestRatio = $ratio;
            }
        }

        return $best;
    }

    /**
     * Rilis 59: nama warna (kunci slot ATAU nama lama: "forest", "foresty", "custom-3") -> kunci slot; selain itu null.
     */
    public static function slotFor(mixed $name): ?string
    {
        if (!is_string($name)) {
            return null;
        }
        if (in_array($name, self::keys(), true)) {
            return $name;
        }
        foreach (self::BASE as $key => $row) {
            if (in_array($name, $row[2], true)) {
                return $key;
            }
        }

        return null;
    }

    /** "var(--color-forest)" / "var(--color-foresty)" -> "var(--color-forest)" (nama lama dinormalkan); selain itu null. Aman dicetak ke style. */
    public static function colorVar(mixed $value): ?string
    {
        if (!is_string($value) || preg_match('/^var\(--color-([a-z0-9-]{1,20})\)$/D', $value, $m) !== 1) {
            return null;
        }
        $slot = self::slotFor($m[1]);

        return $slot === null ? null : "var(--color-$slot)";
    }

    /**
     * Kelas warna teks yang terbaca di atas kelas latar $bgClasses ("bg-forest", "bg-foresty text-white", "bg-custom-3").
     * Latar slot -> "text-{slot}-on"; putih, transparan, berbayang (bg-x/50), kelas selain palet -> "text-charcoal" (seperti sebelum rilis 59).
     */
    public static function autoText(mixed $bgClasses): string
    {
        foreach (preg_split('/\s+/', is_string($bgClasses) ? trim($bgClasses) : '') ?: [] as $token) {
            if (preg_match('/^bg-([a-z0-9-]+)$/D', $token, $m) === 1 && ($slot = self::slotFor($m[1])) !== null) {
                return "text-$slot-on";
            }
        }

        return 'text-charcoal';
    }

    /** Semua nama warna Tailwind yang diatur palet (slot + alias), untuk menghapus definisi lama di CSS. @return list<string> */
    public static function names(): array
    {
        $names = [];
        foreach (array_keys(self::defaults()) as $key) {
            $names[] = $key;
            foreach (self::BASE[$key][2] ?? [] as $alias) {
                $names[] = $alias;
            }
        }

        return $names;
    }
}
