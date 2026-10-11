<?php

namespace App\Content;

/**
 * Kategori artikel untuk halaman publik "artikel per kategori" (rilis 42). MURNI: baris kategori masuk sebagai larik, jadi seluruh aturan
 * diuji tanpa basis data. Tabel `categories` milik aplikasi Anda; bentuk kolomnya beragam, dan semuanya dibaca:
 *
 *   slug   {"en":"malaria","id":"malaria"}  peta per bahasa (satu alamat per bahasa, seperti halaman dan artikel)
 *          "malaria"                         teks polos: SATU slug untuk semua bahasa
 *          kosong / kolom tidak ada          slug dibentuk dari NAMA di bahasa itu (Slug::make)
 *   name   peta bahasa atau teks polos (lihat Names)
 *
 * Aturan sama dengan halaman (PublicLookup::findPage): alamat KETAT per bahasa. Slug bahasa lain -> 301 ke slug kategori itu di bahasa alamat;
 * kategori yang belum punya slug di bahasa alamat -> tidak ditemukan (404), bukan tampil dengan slug bahasa lain.
 */
final class CategoryLookup
{
    /** Kata segmen alamat kategori per bahasa: "/articles/category/{slug}" dan "/id/artikel/kategori/{slug}". Dibaca menu (NavLinks, MenuTarget). */
    public const SEGMENTS = ['category', 'kategori'];

    /**
     * Slug SAH kategori di tiap bahasa (hanya yang bisa dibentuk).
     *
     * @param array{id?:mixed,name?:mixed,slug?:mixed} $row nilai MENTAH kolom
     * @param string[] $locales
     * @return array<string,string> bahasa => slug
     */
    public static function slugs(array $row, array $locales): array
    {
        $raw = LocaleMap::decode($row['slug'] ?? null);
        $out = [];
        foreach ($locales as $locale) {
            if (is_array($raw)) {
                $slug = Names::exact($raw, $locale);
            } elseif (is_string($raw) && trim($raw) !== '') {
                $slug = trim($raw);   // teks polos: satu slug untuk semua bahasa
            } else {
                $slug = Slug::make(Names::exact($row['name'] ?? null, $locale));   // tanpa kolom slug: dari nama bahasa itu
            }
            if ($slug !== '' && strlen($slug) <= 190 && Slug::isValid($slug)) {
                $out[$locale] = $slug;
            }
        }

        return $out;
    }

    /** Nama tampil di $locale; bila kosong, bahasa bawaan lalu bahasa lain (kategori tanpa terjemahan tetap punya judul). */
    public static function name(array $row, string $locale, array $locales = []): string
    {
        return Names::of($row['name'] ?? null, $locale, array_values(array_unique($locales)));
    }

    /**
     * Kategori untuk alamat "{bahasa}/…/{slug}".
     *   cocok di bahasa itu                                    -> ['redirect' => null]
     *   cocok di bahasa LAIN dan punya slug di bahasa alamat   -> 'redirect' = slug bahasa alamat (301 ke versi bahasa yang SAMA)
     *   selain itu                                             -> null (404)
     *
     * @param iterable<array{id?:mixed,name?:mixed,slug?:mixed}> $rows
     * @param string[] $locales
     * @return array{id:int,name:string,slug:string,slugs:array<string,string>,redirect:?string}|null
     */
    public static function find(iterable $rows, string $slug, string $locale, array $locales): ?array
    {
        if ($slug === '' || strlen($slug) > 190 || !Slug::isValid($slug)) {
            return null;
        }
        $all = array_values(array_unique(array_merge([$locale], $locales)));
        $other = null;
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $slugs = self::slugs($row, $all);
            if (($slugs[$locale] ?? null) === $slug) {
                return self::result($row, $id, $slugs, $locale, $all, null);
            }
            if ($other === null && isset($slugs[$locale]) && in_array($slug, $slugs, true)) {
                $other = [$row, $id, $slugs];
            }
        }

        return $other === null ? null : self::result($other[0], $other[1], $other[2], $locale, $all, $other[2][$locale]);
    }

    /**
     * Padanan sebuah slug kategori pada bahasa $locale, untuk tautan menu (satu isian untuk semua bahasa): slug di $locale, atau (belum
     * punya) di bahasa lain yang ada, bahasa bawaan dulu. null = kategori tidak ada.
     *
     * @param iterable<array{id?:mixed,name?:mixed,slug?:mixed}> $rows
     * @param string[] $locales
     * @return array{locale:string,slug:string}|null
     */
    public static function sibling(iterable $rows, string $slug, string $locale, array $locales, ?string $default = null): ?array
    {
        if ($slug === '' || strlen($slug) > 190 || !Slug::isValid($slug)) {
            return null;
        }
        $all = array_values(array_unique(array_merge([$locale], $default !== null ? [$default] : [], $locales)));
        foreach ($rows as $row) {
            $slugs = self::slugs($row, $all);
            if (!in_array($slug, $slugs, true)) {
                continue;
            }
            foreach ($all as $l) {
                if (isset($slugs[$l])) {
                    return ['locale' => $l, 'slug' => $slugs[$l]];
                }
            }
        }

        return null;
    }

    /** @return array{id:int,name:string,slug:string,slugs:array<string,string>,redirect:?string} */
    private static function result(array $row, int $id, array $slugs, string $locale, array $locales, ?string $redirect): array
    {
        return ['id' => $id, 'name' => self::name($row, $locale, $locales), 'slug' => $slugs[$locale], 'slugs' => $slugs, 'redirect' => $redirect];
    }
}
