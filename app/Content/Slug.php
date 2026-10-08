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
     * Slug yang TIDAK BOLEH dipakai halaman CMS karena alamat "/{slug}"-nya sudah dimiliki sesuatu yang didaftarkan lebih dulu di
     * landing (rute statis, rute teknis, atau folder publik). Halaman CMS ber-slug ini tersimpan dan tampak "online", tetapi tidak
     * pernah bisa dibuka. Rute statis milik situs dicatat di config('cms.reserved_slugs'); daftar ini hanya yang bersifat teknis.
     */
    public const RESERVED = ['articles', 'storage', 'build', 'fonts', 'logo', 'up', 'livewire', 'login', 'logout', 'admin', 'api', 'robots', 'sitemap', 'favicon', 'preview', 'v2'];

    /**
     * Daftar slug terlarang yang berlaku: bawaan + tambahan dari config, huruf kecil, tanpa duplikat. $allowed (mis. slug kepala
     * daftar artikel, "artikel") dikeluarkan: alamatnya memang milik halaman CMS itu.
     *
     * @param  array<int,mixed> $configured tambahan dari config('cms.reserved_slugs'); nilai bukan teks diabaikan
     * @return string[]
     */
    public static function reserved(array $configured = [], ?string $allowed = null): array
    {
        $all = [];
        foreach (array_merge(self::RESERVED, $configured) as $slug) {
            if (is_string($slug) && trim($slug) !== '') {
                $all[strtolower(trim($slug))] = true;
            }
        }
        if ($allowed !== null) {
            unset($all[strtolower(trim($allowed))]);
        }

        return array_keys($all);
    }

    public static function isReserved(string $slug, array $configured = [], ?string $allowed = null): bool
    {
        return in_array(strtolower(trim($slug)), self::reserved($configured, $allowed), true);
    }
}
