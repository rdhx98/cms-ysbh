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
            $title = trim(Names::of($row['title'] ?? null, $lang));
            if ($title === '') {
                continue; // artikel tanpa judul tidak ditampilkan
            }
            $slug = trim(Names::of($row['slug'] ?? null, $lang));
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
        $meta = self::plain(Names::of($metaDescription, $lang));
        if ($meta !== '') {
            return self::truncate($meta, $max);
        }
        if ($rawContent === null || $rawContent === '' || $rawContent === []) {
            return '';
        }
        $doc = ContentDocument::fromRaw($rawContent, array_values(array_unique([$lang, 'id', 'en'])));
        foreach ($doc->order as $id) {
            $block = $doc->blocks[$id] ?? null;
            if (!is_array($block) || str_replace('_', '-', strtolower((string) ($block['type'] ?? ''))) !== 'paragraph') {
                continue;
            }
            $text = $block['data']['text'] ?? null;
            $plain = self::plain(is_array($text) ? ($text[$lang] ?? $text['id'] ?? $text['en'] ?? '') : (string) $text);
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
