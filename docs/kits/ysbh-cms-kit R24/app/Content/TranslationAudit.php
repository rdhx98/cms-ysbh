<?php

namespace App\Content;

/**
 * Audit terjemahan (rilis 24): mencari halaman/artikel yang BELUM LENGKAP di sebuah bahasa. Murni (tanpa basis data); pembacanya adalah
 * perintah `php artisan cms:audit-translations`.
 *
 * Mengapa perlu: satu alamat = satu bahasa. Renderer mencetak teks bahasa halaman saja, jadi blok yang hanya terisi bahasa Indonesia
 * tampil KOSONG di situs Inggris, tanpa galat apa pun. Audit ini menemukannya sebelum pengunjung.
 *
 * Per record dan per bahasa L (dibandingkan dengan bahasa lain yang terisi):
 *   - 'belum'   slug DAN judul L kosong: record tidak ada di situs berbahasa L (404; sakelar bahasa menuju beranda). Hanya informasi.
 *   - 'separuh' slug ATAU judul L terisi, yang lain kosong: terjemahan setengah jalan (tidak masuk peta situs dan hreflang). GALAT.
 *   - 'isi'     slug dan judul terisi, tetapi ada teks di blok yang kosong di L padahal terisi di bahasa lain: blok kosong di situs. GALAT.
 * Teks di blok dikenali sebagai PETA BAHASA: larik ber-kunci kode bahasa, minimal dua kunci, semua kunci termasuk daftar bahasa, nilai teks.
 * Teks kosong = tanpa isi sesudah tag dibuang dan spasi dipangkas ("<p></p>" kosong; "<img>" tetap dihitung berisi).
 */
final class TranslationAudit
{
    public const NOT_YET = 'belum';
    public const HALF = 'separuh';
    public const CONTENT = 'isi';

    /**
     * @param iterable<array{id:mixed,title:mixed,slug:mixed,content?:mixed}> $rows nilai MENTAH kolom
     * @param string[] $locales
     * @return list<array{id:mixed,title:string,locale:string,problem:string,detail:string}>
     */
    public static function check(iterable $rows, array $locales): array
    {
        $out = [];
        foreach ($rows as $row) {
            $title = self::label($row['title'] ?? null, $locales);
            foreach ($locales as $locale) {
                $hasSlug = Names::exact($row['slug'] ?? null, $locale) !== '';
                $hasTitle = Names::exact($row['title'] ?? null, $locale) !== '';
                $others = array_values(array_diff($locales, [$locale]));
                $otherFilled = false;
                foreach ($others as $o) {
                    $otherFilled = $otherFilled || Names::exact($row['slug'] ?? null, $o) !== '' || Names::exact($row['title'] ?? null, $o) !== '';
                }
                if (!$hasSlug && !$hasTitle) {
                    if ($otherFilled) {
                        $out[] = ['id' => $row['id'] ?? null, 'title' => $title, 'locale' => $locale, 'problem' => self::NOT_YET, 'detail' => 'slug dan judul kosong: tidak ada di situs berbahasa ' . $locale];
                    }
                    continue;
                }
                if ($hasSlug !== $hasTitle) {
                    $out[] = ['id' => $row['id'] ?? null, 'title' => $title, 'locale' => $locale, 'problem' => self::HALF, 'detail' => $hasSlug ? 'slug terisi tetapi judul kosong' : 'judul terisi tetapi slug kosong'];
                    continue;
                }
                $gaps = self::gaps($row['content'] ?? null, $locale, $locales);
                if ($gaps !== []) {
                    $shown = array_slice($gaps, 0, 3);
                    $out[] = ['id' => $row['id'] ?? null, 'title' => $title, 'locale' => $locale, 'problem' => self::CONTENT, 'detail' => count($gaps) . ' teks kosong di blok: ' . implode(', ', $shown) . (count($gaps) > 3 ? ', …' : '')];
                }
            }
        }

        return $out;
    }

    /**
     * Blok-blok yang punya teks kosong di $locale padahal terisi di bahasa lain.
     *
     * @param string[] $locales
     * @return list<string> "tipe id" per teks kosong (tipe blok bila diketahui)
     */
    public static function gaps(mixed $rawContent, string $locale, array $locales): array
    {
        $doc = LocaleMap::decode($rawContent);
        $blocks = is_array($doc) && is_array($doc['blocks'] ?? null) ? $doc['blocks'] : [];
        $found = [];
        foreach ($blocks as $id => $block) {
            if (!is_array($block)) {
                continue;
            }
            $label = trim((string) ($block['type'] ?? '?') . ' ' . (string) $id);
            $n = self::count($block['data'] ?? null, $locale, $locales);
            for ($i = 0; $i < $n; $i++) {
                $found[] = $label;
            }
        }

        return $found;
    }

    /** Jumlah peta bahasa dalam $node (rekursif) yang kosong di $locale tetapi terisi di bahasa lain. */
    private static function count(mixed $node, string $locale, array $locales, int $depth = 0): int
    {
        if (!is_array($node) || $depth > 12) {
            return 0;
        }
        if (self::isLocaleMap($node, $locales)) {
            $mine = self::filled($node[$locale] ?? null);
            if ($mine) {
                return 0;
            }
            foreach ($node as $l => $v) {
                if ($l !== $locale && self::filled($v)) {
                    return 1;
                }
            }

            return 0;
        }
        $total = 0;
        foreach ($node as $child) {
            $total += self::count($child, $locale, $locales, $depth + 1);
        }

        return $total;
    }

    /** Larik ber-kunci kode bahasa (semua kunci termasuk $locales, minimal dua kunci), nilai teks atau null. */
    private static function isLocaleMap(array $node, array $locales): bool
    {
        if (count($node) < 2) {
            return false;
        }
        foreach ($node as $key => $value) {
            if (!is_string($key) || !in_array($key, $locales, true) || !(is_string($value) || $value === null)) {
                return false;
            }
        }

        return true;
    }

    private static function filled(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        if (stripos($value, '<img') !== false) {
            return true;
        }

        // &nbsp; menjadi U+00A0 yang tidak dipangkas trim(); dengan /u, \s mencakupnya (UCP). Ruang lebar nol dan BOM juga dianggap kosong.
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/^[\s\x{200B}\x{FEFF}]+|[\s\x{200B}\x{FEFF}]+$/u', '', $text) !== '';
    }

    private static function label(mixed $rawTitle, array $locales): string
    {
        foreach ($locales as $l) {
            $t = Names::exact($rawTitle, $l);
            if ($t !== '') {
                return $t;
            }
        }

        return Names::of($rawTitle);
    }
}
