<?php

namespace App\Content;

use App\Content\Blocks\ArticleCards;

/**
 * Daftar artikel di halaman CMS (rilis 52): alamat penomoran dan penyaring kategori untuk blok "Artikel Terbaru". MURNI (tanpa basis data):
 * baris kategori dan jumlah artikelnya masuk sebagai larik; pencarian datanya ada di PublicLookup::listing.
 *
 * Model: HALAMAN adalah kurator (judul, pengantar, SEO, penutup), BLOK menarik artikelnya, CHIP menyaring di halaman yang sama.
 *   ?page=N          penomoran. Hanya blok UTAMA di halaman (blok daftar pertama yang berhalaman atau bersaringan, urutan dokumen) memakai nama
 *                    "page" dan "category"; blok daftar lain di halaman yang sama memakai nama berakhiran kode blok, supaya tidak saling menimpa.
 *   ?category=slug   menyaring ke SATU kategori. Yang boleh disaring = kategori pilihan blok (sumber "Kategori tertentu") atau semua kategori
 *                    yang punya artikel di bahasa halaman (sumber "Semua"). Selain itu diabaikan (tampil seperti "Semua"), kecuali slug kategori
 *                    bahasa lain yang sah: itu dialihkan 301 ke slug bahasa halaman (aturan yang sama dengan halaman dan artikel).
 */
final class ArticleListing
{
    public const PAGE = 'page';
    public const FILTER = 'category';
    public const TYPE = 'latest-articles-builder';

    /**
     * Id blok UTAMA: blok Artikel Terbaru pertama (urutan dokumen, hanya yang tersambung ke halaman) yang berhalaman atau punya penyaring.
     *
     * @param array<string,mixed> $blocks
     * @param array<int,mixed> $order
     */
    public static function primaryId(array $blocks, array $order): ?string
    {
        foreach (ContentDocument::walk($blocks, $order) as $id) {
            $b = $blocks[$id] ?? null;
            if (!is_array($b) || ($b['type'] ?? null) !== self::TYPE) {
                continue;
            }
            $d = is_array($b['data'] ?? null) ? $b['data'] : [];
            if (ArticleCards::paging($d['paging'] ?? null) === 'pages' || ($d['show_filter'] ?? false)) {
                return (string) $id;
            }
        }

        return null;
    }

    /** Nama parameter nomor halaman: "page" untuk blok utama, selain itu "hal" + kode blok (ArticleCards::pageParam). */
    public static function pageParam(mixed $blockId, ?string $primaryId): string
    {
        return $primaryId !== null && is_scalar($blockId) && (string) $blockId === $primaryId ? self::PAGE : ArticleCards::pageParam($blockId);
    }

    /** Nama parameter penyaring: "category" untuk blok utama, selain itu "kat" + kode blok. */
    public static function filterParam(mixed $blockId, ?string $primaryId): string
    {
        if ($primaryId !== null && is_scalar($blockId) && (string) $blockId === $primaryId) {
            return self::FILTER;
        }
        $id = is_scalar($blockId) ? trim((string) $blockId) : '';

        return $id === '' ? 'kategori' : 'kat' . substr(sha1($id), 0, 6);
    }

    /**
     * Nomor halaman yang DIMINTA di alamat: bilangan bulat >= 1 (tanpa batas atas), selain itu null (dianggap halaman 1).
     * Dipakai untuk membalas 404 pada halaman di luar jangkauan.
     */
    public static function requestedPage(mixed $raw): ?int
    {
        if (!(is_int($raw) || (is_string($raw) && ctype_digit($raw))) || (int) $raw < 1) {
            return null;
        }

        return min((int) $raw, 1000000);
    }

    /**
     * Chip penyaring: kategori yang boleh dipilih, berurutan.
     *   - $pickIds null  = semua kategori yang punya artikel (urut id)
     *   - $pickIds larik = hanya kategori pilihan blok, urut sesuai pilihan
     * Kategori tanpa slug di $lang, atau tanpa artikel terbit di bahasa itu ($counts[id] kosong), disembunyikan: chip tidak boleh membuka
     * daftar kosong.
     *
     * @param iterable<array{id?:mixed,name?:mixed,slug?:mixed}> $rows nilai MENTAH kolom
     * @param array<int,int> $counts id kategori => jumlah artikel terbit di $lang
     * @param int[]|null $pickIds
     * @param string[] $locales
     * @return list<array{id:int,slug:string,slugs:array<string,string>,name:string,count:int}>
     */
    public static function chips(iterable $rows, array $counts, ?array $pickIds, string $lang, array $locales): array
    {
        $all = array_values(array_unique(array_merge([$lang], $locales)));
        $byId = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || isset($byId[$id])) {
                continue;
            }
            $slugs = CategoryLookup::slugs($row, $all);
            $count = (int) ($counts[$id] ?? 0);
            if (!isset($slugs[$lang]) || $count <= 0) {
                continue;
            }
            $byId[$id] = ['id' => $id, 'slug' => $slugs[$lang], 'slugs' => $slugs, 'name' => CategoryLookup::name($row, $lang, $all), 'count' => $count];
        }
        if ($pickIds === null) {
            ksort($byId);

            return array_values($byId);
        }
        $out = [];
        $used = [];
        foreach ($pickIds as $id) {
            $id = (int) $id;
            if (isset($byId[$id]) && !isset($used[$id])) {
                $used[$id] = true;
                $out[] = $byId[$id];
            }
        }

        return $out;
    }

    /**
     * Kategori yang dipilih dari alamat.
     *   slug di bahasa halaman, termasuk dalam chip  -> ['chip' => chip, 'redirect' => null]
     *   slug bahasa lain milik chip                  -> ['chip' => chip, 'redirect' => slug bahasa halaman] (301)
     *   selain itu (kosong, tidak dikenal, di luar pilihan, bukan teks) -> ['chip' => null, 'redirect' => null] (diabaikan: tampil "Semua")
     *
     * @param list<array{id:int,slug:string,slugs:array<string,string>,name:string,count:int}> $chips
     * @return array{chip:?array,redirect:?string}
     */
    public static function pick(array $chips, mixed $raw): array
    {
        if (!is_string($raw) || $raw === '' || strlen($raw) > 190) {
            return ['chip' => null, 'redirect' => null];
        }
        foreach ($chips as $chip) {
            if ($chip['slug'] === $raw) {
                return ['chip' => $chip, 'redirect' => null];
            }
        }
        foreach ($chips as $chip) {
            if (in_array($raw, $chip['slugs'], true)) {
                return ['chip' => $chip, 'redirect' => $chip['slug']];
            }
        }

        return ['chip' => null, 'redirect' => null];
    }

    /**
     * Alamat relatif ("?a=1&b=2") dari parameter alamat sekarang ($query) dengan perubahan ($set: nama => nilai; null = buang).
     * Nomor halaman 1 dibuang (halaman 1 = tanpa parameter). Kunci larik di $query dilewati (hanya skalar yang dipertahankan).
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $set
     */
    public static function href(array $query, array $set, string $anchor = '', string $base = ''): string
    {
        $q = [];
        foreach ($query as $k => $v) {
            if (is_string($k) && is_scalar($v) && !array_key_exists($k, $set)) {
                $q[$k] = (string) $v;
            }
        }
        foreach ($set as $k => $v) {
            if ($v === null || ($k === self::PAGE && (int) $v <= 1) || (str_starts_with($k, 'hal') && (int) $v <= 1)) {
                continue;
            }
            $q[$k] = (string) $v;
        }
        $qs = http_build_query($q);

        return ($qs === '' ? ($base === '' ? '?' : $base) : $base . '?' . $qs) . $anchor;
    }
}
