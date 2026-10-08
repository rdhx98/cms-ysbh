<?php

namespace App\Content;

/**
 * Tautan menu (kolom `url` tabel navigations) di situs dua bahasa. Kolom `url` BUKAN per bahasa, jadi satu isian ("/about-us" atau "/tentang-kami")
 * harus menuju versi bahasa PEMBACA. MURNI: pencarian halaman dan pembentuk alamat dilewatkan sebagai fungsi, sehingga dapat diuji tanpa database.
 *
 * Aturan (hanya jalur lokal satu segmen; sisanya TIDAK diubah):
 *   "/"                          -> beranda bahasa pembaca
 *   "/id" (awalan bahasa saja)   -> beranda bahasa pembaca
 *   "/articles", "/artikel"      -> daftar artikel bahasa pembaca (slug kepala daftar bahasa mana pun)
 *   "/{slug}" atau "/id/{slug}"  -> halaman dengan slug itu di bahasa mana pun, dialamatkan pada bahasa pembaca (bahasa lain bila belum diterjemahkan)
 *   selain itu (http(s), mailto:, tel:, #anchor, jalur bersegmen banyak, halaman tak dikenal) -> apa adanya
 * Query (?x) dan fragmen (#x) dipertahankan.
 */
final class NavLinks
{
    /**
     * @param string[]                                          $locales
     * @param string[]                                          $indexSlugs  slug kepala daftar artikel semua bahasa
     * @param callable(string,string):?array{locale:string,slug:string} $sibling     (slug, bahasa) -> padanan halaman, atau null
     * @param callable(string,string):?string                   $pageAddress (slug, bahasa) -> alamat halaman, atau null
     * @param callable(string):?string                          $homeAddress (bahasa) -> alamat beranda
     * @param callable(string):?string                          $indexAddress (bahasa) -> alamat daftar artikel
     */
    public static function localize(string $url, string $locale, array $locales, string $default, array $indexSlugs, callable $sibling, callable $pageAddress, callable $homeAddress, callable $indexAddress): string
    {
        if ($url === '' || $url[0] !== '/' || str_starts_with($url, '//')) {
            return $url;
        }
        $suffix = '';
        if (($cut = strcspn($url, '?#')) < strlen($url)) {
            $suffix = substr($url, $cut);
            $url = substr($url, 0, $cut);
        }

        $segments = array_values(array_filter(explode('/', $url), fn ($s) => $s !== ''));
        if ($segments !== [] && $segments[0] !== $default && in_array($segments[0], $locales, true)) {
            array_shift($segments);   // awalan bahasa tertulis: yang dimaksud halamannya, bukan bahasanya
        }

        if ($segments === []) {
            $home = $homeAddress($locale);

            return $home !== null ? $home . $suffix : $url . $suffix;
        }
        if (count($segments) !== 1 || !Slug::isValid($segments[0])) {
            return $url . $suffix;
        }

        $slug = $segments[0];
        if (in_array($slug, $indexSlugs, true)) {
            $index = $indexAddress($locale);
            if ($index !== null) {
                return $index . $suffix;
            }
        }
        $hit = $sibling($slug, $locale);
        $address = $hit !== null ? $pageAddress($hit['slug'], $hit['locale']) : null;

        return ($address ?? $url) . $suffix;
    }
}
