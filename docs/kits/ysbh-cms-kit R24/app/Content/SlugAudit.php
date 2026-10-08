<?php

namespace App\Content;

/**
 * Mencari halaman yang SUDAH tersimpan dengan slug terlarang (dibuat sebelum penjaga ada): tampak online di CMS, tidak pernah terbuka
 * di situs. Murni (tanpa basis data); pembacanya adalah perintah `php artisan cms:audit-slugs`.
 */
final class SlugAudit
{
    /**
     * @param iterable<array{id:mixed,title:mixed,slug:mixed}> $rows nilai MENTAH kolom (JSON per bahasa, atau teks polos pada baris lama)
     * @param array<int|string,mixed> $configured  config('cms.reserved_slugs') (daftar, atau peta bahasa)
     * @param mixed    $indexSlugs config('cms.articles_index_slug') (teks, atau peta bahasa; null = tidak ada pengecualian)
     * @param string[] $locales    semua bahasa situs
     * @return list<array{id:mixed,title:string,locale:string,slug:string}>
     */
    public static function conflicts(iterable $rows, array $configured = [], mixed $indexSlugs = null, array $locales = [], ?string $default = null): array
    {
        $found = [];
        foreach ($rows as $row) {
            foreach (self::slugs($row['slug'] ?? null) as $locale => $slug) {
                $loc = $locale === '*' ? null : (string) $locale;   // baris lama teks polos tidak punya bahasa
                $allowed = $indexSlugs === null ? null : ($loc === null ? (is_string($indexSlugs) ? $indexSlugs : null) : Languages::indexSlug($indexSlugs, $loc));
                if (Slug::isReserved($slug, $configured, $allowed, $loc, $locales, $default)) {
                    $found[] = ['id' => $row['id'] ?? null, 'title' => self::title($row['title'] ?? null), 'locale' => (string) $locale, 'slug' => $slug];
                }
            }
        }

        return $found;
    }

    /** @return array<string,string> bahasa => slug; baris lama berisi teks polos dibaca sebagai satu slug tanpa bahasa ("*") */
    private static function slugs(mixed $raw): array
    {
        if (is_array($raw)) {
            $decoded = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return ['*' => $raw];
            }
        } else {
            return [];
        }

        $out = [];
        foreach ($decoded as $locale => $slug) {
            if (is_string($slug) && trim($slug) !== '') {
                $out[(string) $locale] = $slug;
            }
        }

        return $out;
    }

    private static function title(mixed $raw): string
    {
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (is_array($decoded)) {
            foreach ($decoded as $v) {
                if (is_string($v) && trim($v) !== '') {
                    return $v;
                }
            }

            return '';
        }

        return is_string($raw) ? $raw : '';
    }
}
