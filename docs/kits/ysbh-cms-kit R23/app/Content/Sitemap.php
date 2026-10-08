<?php

namespace App\Content;

use App\Content\Links\LinkResolver;

/**
 * Peta situs (sitemap.xml). Murni: tanpa basis data dan tanpa Laravel; pengendali di landing hanya menyambungkannya.
 *
 *   rows (PublicLookup::sitemapEntries) + jalur statis + alamat dasar  ->  entries()  ->  xml()
 *
 * Aturan keras (peta situs yang rusak membuat mesin pencari mengabaikan SELURUH berkas):
 *  - alamat HARUS absolut http(s), tanpa spasi/kutip/tanda kurung sudut/& dan tanpa query; yang tidak sah DIBUANG, bukan diperbaiki;
 *  - alamat kembar digabung (lastmod terbaru dipertahankan); maksimal 50.000 alamat (batas protokol);
 *  - lastmod hanya tanggal sah YYYY-MM-DD, selain itu dihilangkan;
 *  - semua teks di-escape XML.
 */
final class Sitemap
{
    public const MAX_URLS = 50000;

    private const LOC = '#^https?://[A-Za-z0-9.-]+(?::\d{1,5})?(?:/[A-Za-z0-9._~%/\-]*)?$#D';

    /**
     * @param list<array{kind:string,slug:string,lastmod:?string}> $rows nilai dari PublicLookup::sitemapEntries()
     * @param array<int,mixed>                                     $staticPaths jalur statis ("/artikel", ...) dari config('cms.sitemap_static')
     * @param mixed                                                $homeSlug slug halaman beranda (config('cms.home_slug')): halaman itu masuk sebagai "{base}/", bukan "{base}/{slug}"
     * @return list<array{loc:string,lastmod:?string}>                  sudah divalidasi dan tanpa duplikat; [] bila alamat dasar tidak sah
     */
    public static function entries(array $rows, array $staticPaths, string $base, mixed $pageTemplate, mixed $articleTemplate, mixed $homeSlug = null): array
    {
        $base = rtrim(trim($base), '/');
        if (!preg_match('#^https?://[A-Za-z0-9.-]+(?::\d{1,5})?$#D', $base)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $template = ($row['kind'] ?? '') === 'article' ? $articleTemplate : $pageTemplate;
            $slug = (string) ($row['slug'] ?? '');
            $loc = ($row['kind'] ?? '') === 'page' ? LinkResolver::homeUrl($slug, $homeSlug, $base) : null;
            $loc ??= LinkResolver::publicUrl($slug, $template, $base);
            if ($loc !== null) {
                $out[] = ['loc' => $loc, 'lastmod' => $row['lastmod'] ?? null];
            }
        }
        foreach ($staticPaths as $path) {
            if (is_string($path) && preg_match('#^/[A-Za-z0-9/_.~\-]*$#D', $path) && !str_starts_with($path, '//')) {
                $out[] = ['loc' => $base . $path, 'lastmod' => null];
            }
        }

        return self::normalize($out);
    }

    /**
     * Membuang entri tidak sah, menggabungkan alamat kembar (lastmod terbaru), dan memotong di MAX_URLS. Urutan kemunculan pertama dipertahankan.
     *
     * @param  array<int,mixed> $entries
     * @return list<array{loc:string,lastmod:?string}>
     */
    public static function normalize(array $entries, int $max = self::MAX_URLS): array
    {
        $byLoc = [];
        foreach ($entries as $e) {
            $loc = is_array($e) ? ($e['loc'] ?? null) : null;
            if (!is_string($loc) || strlen($loc) > 2048 || !preg_match(self::LOC, $loc)) {
                continue;
            }
            $mod = self::date($e['lastmod'] ?? null);
            if (!isset($byLoc[$loc])) {
                $byLoc[$loc] = ['loc' => $loc, 'lastmod' => $mod];
            } elseif ($mod !== null && ($byLoc[$loc]['lastmod'] === null || $mod > $byLoc[$loc]['lastmod'])) {
                $byLoc[$loc]['lastmod'] = $mod;
            }
        }

        return array_slice(array_values($byLoc), 0, max(0, $max));
    }

    /** @param array<int,mixed> $entries */
    public static function xml(array $entries, int $max = self::MAX_URLS): string
    {
        $esc = fn (string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (self::normalize($entries, $max) as $e) {
            $xml .= '  <url><loc>' . $esc($e['loc']) . '</loc>' . ($e['lastmod'] !== null ? '<lastmod>' . $esc($e['lastmod']) . '</lastmod>' : '') . "</url>\n";
        }

        return $xml . "</urlset>\n";
    }

    /** "2026-10-08 13:00:00" atau "2026-10-08" -> "2026-10-08"; tanggal mustahil (2026-13-45) atau bentuk lain -> null. */
    public static function date(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:Z|[+-]\d{2}:?\d{2})?)?$/D', trim($value), $m)) {
            return null;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? "{$m[1]}-{$m[2]}-{$m[3]}" : null;
    }
}
