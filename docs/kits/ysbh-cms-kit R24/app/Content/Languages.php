<?php

namespace App\Content;

/**
 * Bahasa situs publik (rilis 24; keputusan lengkap di docs/BAHASA.md). MURNI kecuali fromConfig().
 *
 *   - Bahasa ditentukan HANYA oleh alamat: bahasa bawaan (EN) tanpa awalan ("/about-us"), bahasa lain dengan awalan kodenya ("/id/tentang-kami").
 *     Tidak ada ?lang, tidak ada cookie, tidak ada pengalihan otomatis dari Accept-Language/IP.
 *   - Banyak kunci config('cms.*') boleh berupa TEKS (berlaku untuk semua bahasa, bentuk lama) atau PETA bahasa (['en' => …, 'id' => …]).
 */
final class Languages
{
    /** Kode bahasa yang diterima: huruf kecil 2-3 huruf, boleh dengan wilayah ("pt-br"). */
    private const CODE = '/^[a-z]{2,3}(?:-[a-z]{2,4})?$/D';

    /**
     * Daftar kode bahasa yang bersih (urutan dipertahankan, tanpa duplikat). Bukan larik / tidak ada yang sah = $fallback.
     *
     * @param  string[] $fallback
     * @return string[]
     */
    public static function codes(mixed $configured, array $fallback = ['en', 'id']): array
    {
        $out = [];
        foreach (is_array($configured) ? $configured : [] as $code) {
            if (is_string($code) && preg_match(self::CODE, $code) && !in_array($code, $out, true)) {
                $out[] = $code;
            }
        }

        return $out !== [] ? $out : $fallback;
    }

    /** Bahasa bawaan: $configured bila termasuk $locales, jika tidak bahasa pertama di $locales. */
    public static function defaultOf(mixed $configured, array $locales): string
    {
        return is_string($configured) && in_array($configured, $locales, true) ? $configured : (string) ($locales[0] ?? 'en');
    }

    /**
     * Bahasa sebuah alamat: segmen pertama yang sama dengan kode bahasa NON-bawaan = bahasa itu ("id/tentang-kami" -> id);
     * selain itu bahasa bawaan. Bahasa bawaan sengaja tidak punya awalan ("/en/..." bukan alamat yang sah, jadi bahasa bawaan -> 404 di rute).
     *
     * @param string[] $locales
     */
    public static function fromPath(string $path, array $locales, string $default): string
    {
        $first = explode('/', trim($path, '/'), 2)[0];

        return in_array($first, $locales, true) ? $first : $default;   // $first === $default ("/en/...") tetap bahasa bawaan; hasilnya sama
    }

    /** "" untuk bahasa bawaan, "/id" untuk bahasa lain. */
    public static function prefix(string $locale, string $default): string
    {
        return $locale === '' || $locale === $default ? '' : '/' . $locale;
    }

    /**
     * Nilai config untuk satu bahasa. Teks = berlaku untuk semua bahasa (bentuk lama); peta = nilai bahasa itu, atau $default bila tidak ada.
     * Nilai bukan teks / kosong = $default.
     */
    public static function setting(mixed $setting, string $locale, string $default = ''): string
    {
        if (is_array($setting)) {
            $setting = $setting[$locale] ?? null;
        }

        return is_string($setting) && trim($setting) !== '' ? trim($setting) : $default;
    }

    /** Jalur beranda satu bahasa: dari config bila ada, jika tidak "/" (bahasa bawaan) atau "/{kode}". */
    public static function homePath(mixed $setting, string $locale, string $default): string
    {
        $configured = self::setting($setting, $locale);
        if ($configured !== '') {
            return $configured;
        }

        return self::prefix($locale, $default) ?: '/';
    }

    /** Slug halaman CMS yang menjadi kepala daftar artikel, per bahasa. Tidak diatur = bawaan: "artikel" (id), "articles" (lainnya). */
    public static function indexSlug(mixed $setting, string $locale): string
    {
        return self::setting($setting, $locale, $locale === 'id' ? 'artikel' : 'articles');
    }

    /** Slug halaman CMS yang menjadi beranda, per bahasa. Tidak diatur = "beranda" (id) atau "home" (lainnya). */
    public static function homeSlug(mixed $setting, string $locale): string
    {
        return self::setting($setting, $locale, $locale === 'id' ? 'beranda' : 'home');
    }

    /** Peta bahasa (kunci teks), bukan daftar. */
    public static function isMap(mixed $value): bool
    {
        if (!is_array($value) || $value === []) {
            return false;
        }
        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Bahasa dari config aplikasi INI: config('app.supported_locales') dan bahasa bawaan config('cms.default_locale') (bila tidak ada:
     * config('app.fallback_locale'); config('app.locale') SENGAJA tidak dipakai karena app()->setLocale() mengubahnya per permintaan).
     * Di luar Laravel (pengujian murni) = ['en', 'id'] / 'en'.
     *
     * @return array{locales:string[],default:string}
     */
    public static function fromConfig(): array
    {
        $locales = ['en', 'id'];
        $default = null;
        try {
            if (function_exists('config')) {
                $locales = self::codes(config('app.supported_locales'), $locales);
                $default = config('cms.default_locale') ?? config('app.fallback_locale');
            }
        } catch (\Throwable) {
            // di luar aplikasi: pakai bawaan
        }

        return ['locales' => $locales, 'default' => self::defaultOf($default, $locales)];
    }

    /**
     * Menetapkan bahasa permintaan ini DARI ALAMATNYA dan mengembalikannya (app()->setLocale). Dipanggil paling awal di mount() tiap komponen
     * halaman dan di tampilan galat (404/503), sebelum tata letak dirender, supaya menu, tanggal, dan teks mengikuti bahasa alamat.
     * Tanpa middleware global: bootstrap/app.php milik pengguna dan tidak diubah kit.
     */
    public static function forRequest(): string
    {
        $cfg = self::fromConfig();
        $lang = self::fromPath(request()->path(), $cfg['locales'], $cfg['default']);
        app()->setLocale($lang);

        return $lang;
    }
}
