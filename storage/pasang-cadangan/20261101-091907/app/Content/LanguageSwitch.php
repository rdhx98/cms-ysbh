<?php

namespace App\Content;

/**
 * Pengganti bahasa yang mudah dijangkau (rilis 104): pil bahasa MELAYANG (menempel di tepi layar) dan pil bahasa di header (bilah lengket
 * desktop dan bilah bawah layar kecil), dengan pengaturan di CMS. Tautan bahasa di footer selalu ada (tanpa pengaturan). Murni: tanpa basis
 * data dan tanpa Laravel (diuji tanpa lab).
 *
 * Penyimpanan: tabel site_settings, kunci "lang_switch" (CMS menulis lewat LanguageSwitchSettings, landing membaca lewat
 * PublicLookup::languageSwitch()). Belum pernah disimpan = bawaan di bawah (pil melayang dan pil header NYALA).
 *
 * Kelas Tailwind ditulis UTUH di berkas ini (tidak dirakit dari potongan) agar terpindai; nilai di luar daftar jatuh ke bawaan, jadi data
 * dari browser tidak bisa menyisipkan kelas sembarang.
 */
final class LanguageSwitch
{
    public const KEY = 'lang_switch';

    /** Letak pil melayang. Pada layar kecil letak bawah naik di atas bilah navigasi bawah (±72px). */
    public const POSITIONS = ['bottom-right' => 'Kanan bawah', 'bottom-left' => 'Kiri bawah', 'middle-right' => 'Kanan tengah'];

    /** Kapan pil melayang tampil: langsung, atau setelah pembaca menggulir sedikit (tidak menutupi bagian atas halaman). */
    public const APPEARS = ['scroll' => 'Setelah menggulir', 'always' => 'Selalu'];

    public const STYLES = ['glass' => 'Kaca buram', 'solid' => 'Solid (hijau)'];

    /** Berapa piksel digulir sebelum pil melayang muncul (mode "scroll"). */
    public const SCROLL_AFTER = 320;

    private const POSITION_CLASSES = [
        'bottom-right' => 'right-4 bottom-24 md:bottom-6',
        'bottom-left' => 'left-4 bottom-24 md:bottom-6',
        'middle-right' => 'right-3 top-1/2 -translate-y-1/2',
    ];

    /** Pembungkus pil: [melayang, header]. */
    private const SHELL = [
        'glass' => 'bg-white/70 backdrop-blur-xl backdrop-saturate-150 ring-1 ring-inset ring-forest/15 shadow-lg',
        'solid' => 'bg-forest shadow-lg ring-1 ring-white/20',
        'plain' => 'border border-forest',
    ];

    /** Bagian bahasa: [aktif, tidak aktif]. */
    private const SEGMENT = [
        'glass' => ['bg-forest text-forest-on', 'text-forest hover:bg-forest/10'],
        'solid' => ['bg-white text-forest', 'text-forest-on hover:bg-white/15'],
        'plain' => ['bg-forest text-forest-on', 'text-forest hover:bg-forest/10'],
    ];

    /** @return array{floating:bool,position:string,appear:string,style:string,mobile:bool,header:bool} */
    public static function defaults(): array
    {
        return ['floating' => true, 'position' => 'bottom-right', 'appear' => 'scroll', 'style' => 'glass', 'mobile' => false, 'header' => true];
    }

    /** Dari JSON tersimpan (atau larik): bawaan, lalu hanya nilai yang sah menimpanya. Tidak pernah melempar galat. */
    public static function resolve(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        $out = self::defaults();
        if (!is_array($raw)) {
            return $out;
        }
        foreach (['floating', 'mobile', 'header'] as $k) {
            if (array_key_exists($k, $raw) && is_bool($raw[$k])) {
                $out[$k] = $raw[$k];
            }
        }
        foreach (['position' => self::POSITIONS, 'appear' => self::APPEARS, 'style' => self::STYLES] as $k => $allowed) {
            if (isset($raw[$k]) && is_string($raw[$k]) && array_key_exists($raw[$k], $allowed)) {
                $out[$k] = $raw[$k];
            }
        }

        return $out;
    }

    /**
     * Masukan formulir -> data yang disimpan + daftar galat. Kotak centang = bool (teks "1"/"0"/"true"/"false" dan angka diterima);
     * pilihan di luar daftar ditolak dengan pesan (bukan diam-diam diganti), agar kesalahan terlihat.
     *
     * @return array{data:array<string,mixed>,errors:list<string>}
     */
    public static function sanitize(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $data = self::defaults();
        $errors = [];
        foreach (['floating' => 'Pil melayang', 'mobile' => 'Pil melayang di layar kecil', 'header' => 'Pil di header'] as $k => $name) {
            if (!array_key_exists($k, $input)) {
                continue;
            }
            $b = self::flag($input[$k]);
            if ($b === null) {
                $errors[] = "$name: pilih nyala atau mati.";
            } else {
                $data[$k] = $b;
            }
        }
        foreach (['position' => ['Letak', self::POSITIONS], 'appear' => ['Kapan tampil', self::APPEARS], 'style' => ['Gaya', self::STYLES]] as $k => [$name, $allowed]) {
            if (!array_key_exists($k, $input)) {
                continue;
            }
            if (is_string($input[$k]) && array_key_exists($input[$k], $allowed)) {
                $data[$k] = $input[$k];
            } else {
                $errors[] = "$name: pilihan tidak dikenal.";
            }
        }

        return ['data' => $data, 'errors' => $errors];
    }

    /** bool dari masukan formulir, atau null bila tidak dikenal. */
    public static function flag(mixed $v): ?bool
    {
        return match (true) {
            is_bool($v) => $v,
            $v === 1 || $v === '1' || $v === 'true' || $v === 'on' => true,
            $v === 0 || $v === '0' || $v === 'false' || $v === 'off' || $v === '' || $v === null => false,
            default => null,
        };
    }

    /** Kelas pembungkus pil melayang. */
    public static function floatClasses(array $s): string
    {
        $s = self::resolve($s);

        return 'fixed z-40 items-center gap-0.5 rounded-full p-1 text-xs font-bold '
            . ($s['mobile'] ? 'flex' : 'hidden md:flex') . ' '
            . self::POSITION_CLASSES[$s['position']] . ' '
            . self::SHELL[$s['style']];
    }

    /** Kelas pembungkus pil header (garis tipis hijau). */
    public static function headerClasses(): string
    {
        return 'inline-flex items-center gap-0.5 rounded-full p-0.5 text-xs font-bold ' . self::SHELL['plain'];
    }

    /** Kelas satu bagian bahasa. $variant: 'glass' | 'solid' | 'plain' (header). */
    public static function segmentClasses(string $variant, bool $active): string
    {
        $variant = isset(self::SEGMENT[$variant]) ? $variant : 'plain';

        return 'rounded-full px-2.5 py-1 leading-none transition-colors ' . self::SEGMENT[$variant][$active ? 0 : 1];
    }

    /** Pil melayang tampil langsung (tanpa menunggu gulir)? */
    public static function showsAtOnce(array $s): bool
    {
        return self::resolve($s)['appear'] === 'always';
    }
}
