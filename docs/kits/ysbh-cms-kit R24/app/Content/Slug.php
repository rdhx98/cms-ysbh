<?php

namespace App\Content;

/**
 * Pembuat slug. Aturannya sama dengan yang di JavaScript (editor.js: slugify) supaya hasil di browser dan server identik.
 * "Pelayanan Kesehatan Ibu & Anak!" -> "pelayanan-kesehatan-ibu-dan-anak"
 */
final class Slug
{
    // D: '$' hanya cocok di AKHIR teks (tanpa D, "slug\n" dengan baris baru di ujung lolos)
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';

    /** Huruf beraksen Latin yang lazim di nama/istilah Indonesia dan Inggris. */
    private const MAP = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ā' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ō' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ū' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y', 'ß' => 'ss',
    ];

    public static function make(string $text): string
    {
        $s = mb_strtolower(trim($text));
        $s = strtr($s, self::MAP);
        $s = str_replace('&', ' dan ', $s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';

        return trim($s, '-');
    }

    public static function isValid(string $slug): bool
    {
        return (bool) preg_match(self::PATTERN, $slug);
    }

    /**
     * Slug yang TIDAK BOLEH dipakai halaman CMS karena alamatnya sudah dimiliki sesuatu yang didaftarkan lebih dulu di landing (rute statis,
     * rute teknis, atau folder publik). Halaman CMS ber-slug ini tersimpan dan tampak "online", tetapi tidak pernah bisa dibuka.
     * Rute statis milik situs dicatat di config('cms.reserved_slugs'); daftar ini hanya yang bersifat teknis.
     * Sejak rilis 24 "articles" TIDAK lagi di sini: itu kepala daftar artikel bahasa Inggris (config('cms.articles_index_slug')), dan boleh
     * dipakai halaman CMS (halaman itu justru yang menyumbang judul/pengantarnya).
     */
    public const RESERVED = ['storage', 'build', 'fonts', 'logo', 'up', 'livewire', 'login', 'logout', 'admin', 'api', 'robots', 'sitemap', 'favicon', 'preview', 'v2'];

    /**
     * Daftar slug terlarang yang berlaku, huruf kecil, tanpa duplikat.
     *
     * @param  array<int|string,mixed> $configured config('cms.reserved_slugs'): DAFTAR (berlaku untuk semua bahasa) atau PETA bahasa
     *                                  (['en' => ['…'], 'id' => ['…'], '*' => ['…']]; '*' = semua bahasa). Nilai bukan teks diabaikan.
     * @param  string|null $allowed     slug kepala daftar artikel BAHASA ITU (mis. "artikel"): dikeluarkan, alamatnya memang milik halaman CMS itu
     * @param  string|null $locale      bahasa slug yang diperiksa; null = gabungan semua bahasa (tanpa pengecualian per bahasa)
     * @param  string[]    $locales     semua bahasa situs
     * @param  string|null $default     bahasa bawaan. Di bahasa bawaan, KODE bahasa lain ("id") terlarang: "/id" adalah beranda bahasa itu
     * @return string[]
     */
    public static function reserved(array $configured = [], ?string $allowed = null, ?string $locale = null, array $locales = [], ?string $default = null): array
    {
        $list = self::RESERVED;
        if (Languages::isMap($configured)) {
            foreach ($configured as $key => $values) {
                if (is_array($values) && ($locale === null || $key === '*' || $key === $locale)) {
                    $list = array_merge($list, $values);
                }
            }
        } else {
            $list = array_merge($list, $configured);
        }
        if ($default !== null && ($locale === null || $locale === $default)) {
            $list = array_merge($list, array_diff($locales, [$default]));
        }

        $all = [];
        foreach ($list as $slug) {
            if (is_string($slug) && trim($slug) !== '') {
                $all[strtolower(trim($slug))] = true;
            }
        }
        if ($allowed !== null) {
            unset($all[strtolower(trim($allowed))]);
        }

        return array_keys($all);
    }

    /** @see reserved() */
    public static function isReserved(string $slug, array $configured = [], ?string $allowed = null, ?string $locale = null, array $locales = [], ?string $default = null): bool
    {
        return in_array(strtolower(trim($slug)), self::reserved($configured, $allowed, $locale, $locales, $default), true);
    }
}
