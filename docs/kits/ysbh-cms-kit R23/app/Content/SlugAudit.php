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
     * @param array<int,mixed>                                  $configured config('cms.reserved_slugs')
     * @return list<array{id:mixed,title:string,locale:string,slug:string}>
     */
    public static function conflicts(iterable $rows, array $configured = [], ?string $allowed = null): array
    {
        $found = [];
        foreach ($rows as $row) {
            foreach (self::slugs($row['slug'] ?? null) as $locale => $slug) {
                if (Slug::isReserved($slug, $configured, $allowed)) {
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
