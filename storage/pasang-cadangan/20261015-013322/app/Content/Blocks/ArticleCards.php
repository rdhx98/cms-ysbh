<?php

namespace App\Content\Blocks;

use App\Content\ContentDocument;
use App\Content\Names;

/**
 * Menyiapkan kartu "Artikel terbaru": judul, tautan, kategori, tanggal, ringkasan, dan sampul. Murni: baris artikel (nilai MENTAH kolom) dan
 * pembentuk alamat dilewatkan dari luar, jadi seluruh aturan bisa diuji tanpa basis data. Pencarian datanya ada di App\Content\PublicLookup.
 *
 * Sampul: kolom featured_image di proyek ini berisi NAMA BERKAS teks ("cover-abc.webp"); "default.webp" adalah nilai bawaan yang berarti
 * "belum ada sampul" (ContentWriter), jadi tidak ditampilkan sebagai gambar. Angka murni diperlakukan sebagai ID media (untuk pemilih sampul
 * dari File Manager di kemudian hari).
 */
final class ArticleCards
{
    /** Nilai bawaan kolom featured_image = "belum ada sampul". Harus sama dengan ContentWriter (dijaga oleh tests/article-cards-test.php). */
    public const DEFAULT_COVER = 'default.webp';

    private const GRID = [
        2 => 'grid grid-cols-1 gap-6 sm:grid-cols-2',
        3 => 'grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3',
        4 => 'grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4',
    ];

    public const LIMITS = ['3', '6', '9', '12'];
    public const COLUMNS = ['2', '3', '4'];

    private const MONTHS = [
        'id' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        'en' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    ];

    // ---- rilis 48: sumber, unggulan (bento), tampilan daftar, halaman ----
    public const SOURCES = ['all', 'categories'];
    public const FEATURED = ['0', '1', '2', '3', '4', '5', '6'];
    public const PAGINGS = ['limit', 'pages'];
    public const LIST_LAYOUTS = ['cols', 'rows'];
    public const MAX_CATEGORIES = 20;
    public const MAX_PINNED = 6;

    public static function source(mixed $v): string
    {
        return is_string($v) && in_array($v, self::SOURCES, true) ? $v : 'all';
    }

    /** Jumlah artikel unggulan, TEKS '0'..'6' ('0' = tanpa bagian unggulan). Angka dari inspektur lama diterima. */
    public static function featuredCount(mixed $v): string
    {
        $v = is_int($v) ? (string) $v : $v;

        return is_string($v) && in_array($v, self::FEATURED, true) ? $v : '0';
    }

    public static function paging(mixed $v): string
    {
        return is_string($v) && in_array($v, self::PAGINGS, true) ? $v : 'limit';
    }

    public static function listLayout(mixed $v): string
    {
        return is_string($v) && in_array($v, self::LIST_LAYOUTS, true) ? $v : 'cols';
    }

    /**
     * Daftar pilihan (kategori / artikel yang disematkan): [{id, label}] urut sesuai pilihan. Id harus bilangan bulat positif (angka murni atau
     * teks angka), tanpa duplikat, maksimum $max. `label` hanya untuk tampilan di inspektur (judul saat dipilih): teks polos satu baris, 120 karakter;
     * renderer TIDAK pernah memakainya (nama diambil lagi dari basis data). Nilai yang bukan larik -> daftar kosong.
     *
     * @return list<array{id:int,label:string}>
     */
    public static function picks(mixed $v, int $max): array
    {
        if (!is_array($v)) {
            return [];
        }
        $out = [];
        $seen = [];
        foreach ($v as $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : $item;
            if (!(is_int($id) || (is_string($id) && ctype_digit($id))) || (int) $id <= 0 || isset($seen[(int) $id])) {
                continue;
            }
            $label = is_array($item) && is_string($item['label'] ?? null) ? trim((string) preg_replace('/\s+/u', ' ', strip_tags($item['label']))) : '';
            $seen[(int) $id] = true;
            $out[] = ['id' => (int) $id, 'label' => mb_substr($label, 0, 120)];
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    /** @return list<int> */
    public static function pickIds(mixed $picks): array
    {
        return array_values(array_map(fn (array $p) => (int) $p['id'], is_array($picks) ? array_filter($picks, 'is_array') : []));
    }

    /**
     * Pembagian petak bagian unggulan (kisi bento) untuk $n artikel (1-6); null di luar itu. Huruf = urutan artikel (a = pertama).
     * `areas` = baris grid-template-areas (dibalik kiri-kanan bila $mirror), `tiles` = petak menurut urutan artikel dengan ukuran teks
     * (xl/l/m/s) dan, untuk petak tanpa gambar (pusat kincir 6 artikel), warnanya.
     *
     *   1 spanduk | 2 satu baris 1/3 + 2/3 | 3 dua baris: besar 2/3 (dua baris) + dua petak 1/3 | 4 pola Z (2/3+1/3 setinggi 2/3; 1/3+2/3 setinggi 1/3)
     *   5 tiga baris tiga kolom | 6 kincir angin: empat petak panjang mengelilingi dua petak teks di tengah
     *
     * @return array{n:int,cols:int,areas:list<string>,rows:?string,tiles:list<array{area:string,size:string,text:?string}>}|null
     */
    public static function bento(int $n, bool $mirror = false): ?array
    {
        $layouts = [
            1 => [['a'], null, ['a' => 'xl'], []],
            2 => [['a b b'], null, ['a' => 'l', 'b' => 'xl'], []],
            3 => [['a a b', 'a a c'], null, ['a' => 'xl', 'b' => 'm', 'c' => 'm'], []],
            4 => [['a a b', 'a a b', 'c d d'], null, ['a' => 'xl', 'b' => 'l', 'c' => 's', 'd' => 'm'], []],
            5 => [['a a b', 'a a c', 'd e c'], null, ['a' => 'xl', 'b' => 'm', 'c' => 'l', 'd' => 's', 'e' => 's'], []],
            6 => [['a a a a b b', 'a a a a b b', 'd d e e b b', 'd d f f b b', 'd d c c c c', 'd d c c c c'], '1fr 1fr 1.05fr 1.05fr 1fr 1fr',
                ['a' => 'l', 'b' => 'l', 'c' => 'l', 'd' => 'l', 'e' => 'm', 'f' => 'm'], ['e' => 'gold', 'f' => 'green']],
        ];
        if (!isset($layouts[$n])) {
            return null;
        }
        [$rows, $heights, $sizes, $text] = $layouts[$n];
        $areas = array_map(function (string $row) use ($mirror): string {
            $w = explode(' ', $row);

            return implode(' ', $mirror ? array_reverse($w) : $w);
        }, $rows);

        $tiles = [];
        foreach (array_slice(['a', 'b', 'c', 'd', 'e', 'f'], 0, $n) as $letter) {
            $tiles[] = ['area' => $letter, 'size' => $sizes[$letter], 'text' => $text[$letter] ?? null];
        }

        return ['n' => $n, 'cols' => count(explode(' ', $rows[0])), 'areas' => $areas, 'rows' => $heights, 'tiles' => $tiles];
    }

    /** Nama parameter alamat untuk halaman daftar SATU blok ("hal" + 6 huruf dari id blok), supaya dua blok di satu halaman tidak saling menimpa. */
    public static function pageParam(mixed $blockId): string
    {
        $id = is_scalar($blockId) ? trim((string) $blockId) : '';

        return $id === '' ? 'halaman' : 'hal' . substr(sha1($id), 0, 6);
    }

    /** Nomor halaman dari alamat: bilangan bulat 1..$pages; selain itu 1 (di atas batas: halaman terakhir). */
    public static function pageNumber(mixed $raw, int $pages): int
    {
        $pages = max(1, $pages);
        if (!(is_int($raw) || (is_string($raw) && ctype_digit($raw))) || (int) $raw < 1) {
            return 1;
        }

        return min((int) $raw, $pages);
    }

    /** Daftar tombol halaman: angka dan '…' ("1 … 4 [5] 6 … 20"). Satu halaman atau kurang -> kosong. @return list<int|string> */
    public static function pagerItems(int $page, int $pages): array
    {
        if ($pages <= 1) {
            return [];
        }
        $page = max(1, min($page, $pages));
        $want = array_unique(array_filter([1, $page - 1, $page, $page + 1, $pages], fn (int $n) => $n >= 1 && $n <= $pages));
        sort($want);
        $out = [];
        $prev = 0;
        foreach ($want as $n) {
            if ($n - $prev > 1) {
                $out[] = '…';
            }
            $out[] = $n;
            $prev = $n;
        }

        return $out;
    }

    public static function limit(mixed $v): string
    {
        $v = is_int($v) ? (string) $v : $v;

        return in_array($v, self::LIMITS, true) ? $v : '3';
    }

    public static function columns(mixed $v): string
    {
        $v = is_int($v) ? (string) $v : $v;

        return in_array($v, self::COLUMNS, true) ? $v : '3';
    }

    public static function grid(mixed $columns): string
    {
        return self::GRID[(int) self::columns($columns)];
    }

    /**
     * @param list<array{id?:mixed,title?:mixed,slug?:mixed,content?:mixed,meta_description?:mixed,featured_image?:mixed,category_id?:mixed,published_at?:mixed}> $rows nilai MENTAH kolom
     * @param array<int,mixed> $categoryNames id kategori => nilai mentah kolom name
     * @param callable(string):?string $articleUrl slug (sudah per bahasa) -> alamat artikel, atau null
     * @param callable(string):?string $coverFile  nama berkas sampul -> URL, atau null
     * @param callable(int):?string    $mediaUrl   id media -> URL gambar, atau null
     * @return list<array{id:int,title:string,url:?string,category:string,dateIso:string,dateLabel:string,excerpt:string,cover:?string}>
     */
    public static function prepare(array $rows, array $categoryNames, string $lang, callable $articleUrl, callable $coverFile, callable $mediaUrl, int $excerptMax = 160): array
    {
        $cards = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $title = Names::exact($row['title'] ?? null, $lang);   // satu alamat = satu bahasa: judul bahasa lain tidak dipinjam
            if ($title === '') {
                continue; // artikel tanpa judul (di bahasa ini) tidak ditampilkan
            }
            $slug = Names::exact($row['slug'] ?? null, $lang);
            [$iso, $label] = self::date($row['published_at'] ?? null, $lang);

            $cards[] = [
                'id' => (int) ($row['id'] ?? 0),
                'title' => $title,
                'url' => $slug !== '' ? $articleUrl($slug) : null,
                'category' => trim(Names::of($categoryNames[(int) ($row['category_id'] ?? 0)] ?? null, $lang)),
                'dateIso' => $iso,
                'dateLabel' => $label,
                'excerpt' => self::excerpt($row['meta_description'] ?? null, $row['content'] ?? null, $lang, $excerptMax),
                'cover' => self::cover($row['featured_image'] ?? null, $coverFile, $mediaUrl),
            ];
        }

        return $cards;
    }

    /** meta_description (bila ada) atau paragraf pertama isi artikel; teks biasa, dipotong di batas kata. */
    public static function excerpt(mixed $metaDescription, mixed $rawContent, string $lang, int $max = 160): string
    {
        $meta = self::plain(Names::exact($metaDescription, $lang));
        if ($meta !== '') {
            return self::truncate($meta, $max);
        }
        if ($rawContent === null || $rawContent === '' || $rawContent === []) {
            return '';
        }
        $doc = ContentDocument::fromRaw($rawContent, array_values(array_unique([$lang, 'en', 'id'])));
        foreach ($doc->order as $id) {
            $block = $doc->blocks[$id] ?? null;
            if (!is_array($block) || str_replace('_', '-', strtolower((string) ($block['type'] ?? ''))) !== 'paragraph') {
                continue;
            }
            $text = $block['data']['text'] ?? null;
            $plain = self::plain(is_array($text) ? (string) ($text[$lang] ?? '') : (string) $text);   // tanpa cadangan bahasa lain
            if ($plain !== '') {
                return self::truncate($plain, $max);
            }
        }

        return '';
    }

    /** @return array{0:string,1:string} [Y-m-d, label per bahasa], kosong bila tanggal tidak terbaca */
    public static function date(mixed $value, string $lang): array
    {
        if (!is_string($value) || trim($value) === '') {
            return ['', ''];
        }
        try {
            $d = new \DateTimeImmutable(trim($value));
        } catch (\Throwable) {
            return ['', ''];
        }
        $m = self::MONTHS[$lang === 'id' ? 'id' : 'en'][(int) $d->format('n') - 1];

        return [$d->format('Y-m-d'), $lang === 'id' ? $d->format('j') . " $m " . $d->format('Y') : "$m " . $d->format('j') . ', ' . $d->format('Y')];
    }

    /**
     * @param callable(string):?string $coverFile
     * @param callable(int):?string    $mediaUrl
     */
    public static function cover(mixed $value, callable $coverFile, callable $mediaUrl): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $v = trim((string) $value);
        if ($v === '' || strcasecmp($v, self::DEFAULT_COVER) === 0) {
            return null;
        }
        if (ctype_digit($v)) {
            return (int) $v > 0 ? $mediaUrl((int) $v) : null;
        }

        return $coverFile($v);
    }

    /**
     * Rilis 56: sembunyikan gambar utama di HALAMAN artikel (pengaturan dokumen `settings.hide_hero`, kotak centang di panel Halaman artikel;
     * tanpa migrasi). Thumbnail tetap dipakai untuk kartu artikel dan og:image; yang disembunyikan hanya gambar lebar di bawah judul, mis.
     * ketika thumbnail dipilih dari gambar yang sudah dipasang di isi artikel (kalau tidak, gambar itu tampil dua kali). Bawaan = TAMPIL.
     * Hanya nilai benar yang tegas menyembunyikan: true, 1, "1", "true", "on".
     */
    public static function hideHero(mixed $settings): bool
    {
        $v = is_array($settings) ? ($settings['hide_hero'] ?? false) : false;

        return $v === true || $v === 1 || (is_string($v) && in_array(strtolower(trim($v)), ['1', 'true', 'on'], true));
    }

    /**
     * Gambar utama halaman artikel (rilis 47): sumbernya kolom featured_image yang SAMA dengan kartu. Angka = ID media (harus gambar),
     * nama berkas lama = templat sampul. "default.webp"/kosong = tidak ada gambar (halaman tampil tanpa gambar, bukan gambar rusak).
     *
     * @param callable(string):?string $coverFile nama berkas -> URL, atau null
     * @param callable(int):?array{url:mixed,mime:mixed,alt:mixed} $mediaRow id media -> baris, atau null (tidak ada / sudah dihapus)
     * @return array{url:string,alt:string}|null alt kosong = gambar dekoratif (judul sudah ada tepat di atasnya)
     */
    public static function hero(mixed $featured, callable $coverFile, callable $mediaRow): ?array
    {
        if (!is_scalar($featured)) {
            return null;
        }
        $v = trim((string) $featured);
        if ($v === '' || strcasecmp($v, self::DEFAULT_COVER) === 0) {
            return null;
        }

        if (ctype_digit($v)) {
            $row = (int) $v > 0 ? $mediaRow((int) $v) : null;
            if (!is_array($row) || !is_string($row['url'] ?? null) || trim($row['url']) === '' || !str_starts_with(strtolower((string) ($row['mime'] ?? '')), 'image/')) {
                return null;
            }
            $alt = is_string($row['alt'] ?? null) ? trim((string) preg_replace('/\s+/u', ' ', $row['alt'])) : '';

            return ['url' => trim($row['url']), 'alt' => mb_substr($alt, 0, 255)];
        }

        $url = $coverFile($v);

        return is_string($url) && trim($url) !== '' ? ['url' => trim($url), 'alt' => ''] : null;
    }

    /**
     * Alamat gambar untuk og:image: HARUS mutlak (pengurai media sosial tidak mengikuti alamat relatif). URL mutlak http(s) dipertahankan,
     * "/jalur" ditempel ke $origin (skema + host, mis. "https://ysbh.org"); "//host/x" (protokol-relatif), skema lain, spasi, dan karakter
     * kontrol ditolak (null) sehingga pemanggil jatuh ke gambar bawaan.
     */
    public static function absoluteUrl(?string $url, string $origin): ?string
    {
        $u = trim((string) $url);
        if ($u === '' || preg_match('/[\s\x00-\x1f\x7f<>"\'\\\\]/', $u)) {
            return null;
        }
        if (preg_match('#^https?://[A-Za-z0-9.-]+(?::\d{1,5})?(?:/|$)#i', $u)) {
            return $u;
        }
        $origin = rtrim(trim($origin), '/');
        if (str_starts_with($u, '/') && !str_starts_with($u, '//') && preg_match('#^https?://[A-Za-z0-9.-]+(?::\d{1,5})?$#i', $origin)) {
            return $origin . $u;
        }

        return null;
    }

    private static function plain(string $html): string
    {
        // penutup blok dan <br> menjadi spasi: tanpa ini strip_tags menempelkan kata antar-blok ("Imunisasi" + "Dasar" -> "ImunisasiDasar")
        $html = (string) preg_replace('#</(?:p|h[1-6]|div|li|ul|ol|blockquote|tr|td|th|section|article)\s*>|<br\s*/?>#i', ' ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private static function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > $max * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " \t\n\r,;:.-") . '…';
    }
}
