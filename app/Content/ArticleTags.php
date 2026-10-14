<?php

namespace App\Content;

/**
 * Tag artikel di situs publik (rilis 54). MURNI (tanpa basis data): baris tag dan id tag artikel masuk sebagai larik; kuerinya ada di
 * PublicLookup (articleTags, relatedArticles, listing).
 *
 * Tiga pemakaian:
 *   1. Deretan tag di bawah isi artikel; tiap tag menaut ke "{daftar artikel}?tag={slug}" (LinkResolver::tagAddress).
 *   2. Penyaring ?tag=slug pada blok Artikel Terbaru UTAMA di halaman daftar (ArticleListing::TAG, PublicLookup::listing).
 *   3. "Artikel terkait" di bawah artikel: artikel lain yang berbagi tag, paling banyak tag sama dulu, lalu terbaru.
 *
 * Tabel `tags` memakai bentuk kolom yang sama dengan kategori (name/slug peta bahasa, teks polos, atau tanpa slug), jadi pembacaan slug dan
 * nama memakai CategoryLookup. Tag yang namanya belum diterjemahkan tetap tampil dengan nama bahasa bawaan (tag baru dari editor memang
 * menyalin satu nama ke semua bahasa).
 */
final class ArticleTags
{
    /** Tag yang ditampilkan per artikel (batas wajar; artikel nyata hanya punya beberapa). */
    public const MAX_SHOWN = 20;
    /** Jumlah artikel terkait. */
    public const RELATED_LIMIT = 3;
    /** Kandidat artikel terkait yang diperiksa (yang paling banyak berbagi tag lebih dulu). */
    public const RELATED_CANDIDATES = 500;

    /**
     * Artikel terkait disembunyikan? Pengaturan dokumen `settings.hide_related` (kotak centang "Sembunyikan artikel terkait" di CMS; disimpan
     * di kolom content, tanpa migrasi). Bawaan = TAMPIL. Hanya nilai benar yang tegas menyembunyikan: true, 1, "1", "true", "on".
     */
    public static function hideRelated(mixed $settings): bool
    {
        $v = is_array($settings) ? ($settings['hide_related'] ?? false) : false;

        return $v === true || $v === 1 || (is_string($v) && in_array(strtolower(trim($v)), ['1', 'true', 'on'], true));
    }

    /**
     * Tag sebuah artikel untuk ditampilkan di $lang: urut abjad nama (tidak peka huruf besar), tanpa ganda, maksimal MAX_SHOWN.
     * `slug` null = tag belum punya alamat di bahasa itu (tampil sebagai label tanpa tautan).
     *
     * @param iterable<array{id?:mixed,name?:mixed,slug?:mixed}> $rows nilai MENTAH kolom semua tag
     * @param iterable<mixed> $tagIds id tag artikel itu
     * @param string[] $locales
     * @return list<array{id:int,name:string,slug:?string,slugs:array<string,string>}>
     */
    public static function forArticle(iterable $rows, iterable $tagIds, string $lang, array $locales): array
    {
        $want = [];
        foreach ($tagIds as $id) {
            if ((is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0) {
                $want[(int) $id] = true;
            }
        }
        if ($want === []) {
            return [];
        }
        $all = array_values(array_unique(array_merge([$lang], $locales)));
        $out = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || !isset($want[$id]) || isset($out[$id])) {
                continue;
            }
            $name = CategoryLookup::name($row, $lang, $all);
            if ($name === '') {
                continue;   // tag tanpa nama sama sekali tidak ditampilkan
            }
            $slugs = CategoryLookup::slugs($row, $all);
            $out[$id] = ['id' => $id, 'name' => $name, 'slug' => $slugs[$lang] ?? null, 'slugs' => $slugs];
        }
        $list = array_values($out);
        usort($list, fn (array $a, array $b) => [mb_strtolower($a['name']), $a['id']] <=> [mb_strtolower($b['name']), $b['id']]);

        return array_slice($list, 0, self::MAX_SHOWN);
    }

    /**
     * Urutan artikel terkait: paling banyak tag yang sama dulu; sama banyak, yang lebih baru dulu (tanggal terbit, teks yang bisa dibandingkan);
     * sama lagi, id yang lebih besar dulu. Hasil: id artikel, paling banyak $limit.
     *
     * @param array<int|string,int|string> $shared id artikel => jumlah tag yang sama
     * @param array<int|string,mixed> $dates id artikel => tanggal terbit (hanya artikel yang BOLEH ditampilkan ada di sini)
     * @return list<int>
     */
    public static function rank(array $shared, array $dates, int $limit = self::RELATED_LIMIT): array
    {
        $rows = [];
        foreach ($shared as $id => $count) {
            $id = (int) $id;
            if ($id <= 0 || (int) $count <= 0 || !array_key_exists($id, $dates)) {
                continue;
            }
            $rows[] = [$id, (int) $count, is_scalar($dates[$id]) ? (string) $dates[$id] : ''];
        }
        usort($rows, fn (array $a, array $b) => [$b[1], $b[2], $b[0]] <=> [$a[1], $a[2], $a[0]]);

        return array_map(fn (array $r) => $r[0], array_slice($rows, 0, max(0, $limit)));
    }

    /** Judul bagian artikel terkait. */
    public static function relatedHeading(string $lang): string
    {
        return $lang === 'id' ? 'Artikel terkait' : 'Related articles';
    }

    /** Nama deretan tag untuk pembaca layar. */
    public static function listLabel(string $lang): string
    {
        return $lang === 'id' ? 'Tag artikel' : 'Article tags';
    }

    /** Keterangan di daftar artikel yang disaring tag. */
    public static function filterLabel(string $lang): string
    {
        return $lang === 'id' ? 'Artikel bertag' : 'Articles tagged';
    }

    public static function clearLabel(string $lang): string
    {
        return $lang === 'id' ? 'Hapus penyaring' : 'Clear filter';
    }

    public static function emptyLabel(string $lang): string
    {
        return $lang === 'id' ? 'Belum ada artikel dengan tag ini.' : 'No articles with this tag yet.';
    }

    /** Akhiran judul halaman daftar yang disaring tag (" — Tag: nama"). */
    public static function titleSuffix(string $name): string
    {
        return ' — Tag: ' . $name;
    }
}
