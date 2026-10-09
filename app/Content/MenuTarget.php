<?php

namespace App\Content;

use App\Content\Links\LinkResolver;

/**
 * Tujuan satu baris menu (tabel `navigations`), sisi CMS (rilis 28). MURNI. Menjembatani formulir Menu Builder dan kolom `url`.
 *
 * Konvensi (docs/BAHASA.md, docs/LANDING-RAMPING.md): kolom `url` SATU isian untuk semua bahasa. Landing menerjemahkannya ke bahasa pembaca
 * (App\Content\NavLinks): "/" = beranda, "/articles" atau "/artikel" = daftar artikel, "/{slug}" = halaman dengan slug itu di bahasa mana pun.
 * Karena itu yang disimpan adalah slug SATU bahasa (bawaan), bukan alamat per bahasa. `route_name` TIDAK lagi diisi oleh formulir: rute statis lama
 * sudah dihapus (rilis 23); hanya `home` dan `articles` yang masih dikenali sebagai tujuan lama dan diubah menjadi "/" dan "/articles".
 *
 * Aturan keamanan sama dengan tombol (LinkResolver): hanya http(s), "/jalur", mailto:, tel:, dan #anchor; kolom `url` dibatasi 255 karakter.
 */
final class MenuTarget
{
    public const KINDS = ['home', 'articles', 'page', 'url', 'mailto', 'tel', 'anchor'];

    public const MAX_URL = 255;

    /** Nama rute lama yang masih ada dan dapat dipetakan ke tujuan baru. */
    private const ROUTE_KINDS = ['home' => 'home', 'articles' => 'articles'];

    /**
     * Dari pilihan formulir ke teks kolom `url`.
     *
     * @param string $articlesSlug slug daftar artikel bahasa bawaan (config('cms.articles_index_slug')), mis. "articles"
     * @return array{url:?string,error:?string}
     */
    public static function build(string $kind, string $ref, string $articlesSlug = 'articles'): array
    {
        $ref = trim($ref);
        $fail = fn (string $m): array => ['url' => null, 'error' => $m];

        $url = match ($kind) {
            'home' => '/',
            'articles' => Slug::isValid($articlesSlug) ? '/' . $articlesSlug : null,
            'page' => $ref === '' ? null : (Slug::isValid($ref) ? '/' . $ref : false),
            'url' => $ref === '' ? null : LinkResolver::safeUrl($ref),
            'mailto' => filter_var($ref, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $ref : null,
            'tel' => ($n = LinkResolver::phone($ref)) !== null ? 'tel:' . $n : null,
            'anchor' => ($a = LinkResolver::anchor($ref)) !== null ? '#' . $a : null,
            default => false,
        };

        if ($url === false) {
            return $fail($kind === 'page' ? 'Slug halaman tidak sah. Pilih halaman dari daftar.' : 'Jenis tujuan tidak dikenal.');
        }
        if ($url === null) {
            return $fail(match ($kind) {
                'page' => 'Pilih halaman tujuan.',
                'url' => $ref === '' ? 'Isi alamat tujuan.' : 'Alamat tidak aman atau tidak sah. Gunakan https://… atau jalur yang diawali satu "/" (tanpa spasi).',
                'mailto' => 'Alamat email tidak sah.',
                'tel' => 'Nomor telepon tidak sah (minimal 3 angka).',
                'anchor' => 'Anchor tidak sah (huruf di awal; huruf, angka, "-" dan "_" saja).',
                default => 'Tujuan tidak dapat dibentuk.',
            });
        }
        if (strlen($url) > self::MAX_URL) {
            return $fail('Alamat terlalu panjang (maksimal ' . self::MAX_URL . ' karakter).');
        }

        return ['url' => $url, 'error' => null];
    }

    /**
     * Dari kolom `url` / `route_name` sebuah baris ke pilihan formulir.
     * `legacy` terisi bila baris masih membawa nama rute yang sudah tidak ada (tujuan harus dipilih ulang); `kind` null = belum ada tujuan.
     *
     * @param string[] $indexSlugs slug daftar artikel SEMUA bahasa
     * @param string[] $locales
     * @return array{kind:?string,ref:string,legacy:string}
     */
    public static function parse(?string $url, ?string $routeName, array $indexSlugs, array $locales, string $default): array
    {
        $url = trim((string) $url);
        $route = trim((string) $routeName);
        $legacy = '';
        $fromRoute = null;

        if ($route !== '') {
            if (isset(self::ROUTE_KINDS[$route])) {
                $fromRoute = self::ROUTE_KINDS[$route];   // rute yang masih ada: setara dengan "/" atau daftar artikel
            } else {
                $legacy = $route;                         // rute lama yang sudah dihapus; url (bila ada) tetap dibaca di bawah
            }
        }
        if ($url === '') {
            return ['kind' => $fromRoute, 'ref' => '', 'legacy' => $legacy];
        }

        if ($url[0] === '#') {
            return ['kind' => 'anchor', 'ref' => ltrim($url, '#'), 'legacy' => $legacy];
        }
        if (stripos($url, 'mailto:') === 0) {
            return ['kind' => 'mailto', 'ref' => substr($url, 7), 'legacy' => $legacy];
        }
        if (stripos($url, 'tel:') === 0) {
            return ['kind' => 'tel', 'ref' => substr($url, 4), 'legacy' => $legacy];
        }
        if ($url[0] === '/' && !str_starts_with($url, '//') && !preg_match('/[?#\s]/', $url)) {
            $segments = array_values(array_filter(explode('/', $url), fn ($s) => $s !== ''));
            if ($segments !== [] && $segments[0] !== $default && in_array($segments[0], $locales, true)) {
                array_shift($segments);   // awalan bahasa ("/id/tentang-kami"): yang dimaksud halamannya
            }
            if ($segments === []) {
                return ['kind' => 'home', 'ref' => '', 'legacy' => $legacy];
            }
            if (count($segments) === 1 && Slug::isValid($segments[0])) {
                return in_array($segments[0], $indexSlugs, true)
                    ? ['kind' => 'articles', 'ref' => '', 'legacy' => $legacy]
                    : ['kind' => 'page', 'ref' => $segments[0], 'legacy' => $legacy];
            }
        }

        return ['kind' => 'url', 'ref' => $url, 'legacy' => $legacy];
    }

    /** Ringkasan untuk daftar menu. */
    public static function describe(?string $kind, string $ref, string $legacy = ''): string
    {
        $text = match ($kind) {
            'home' => 'Beranda',
            'articles' => 'Daftar artikel',
            'page' => 'Halaman: /' . $ref,
            'url' => 'URL: ' . $ref,
            'mailto' => 'Email: ' . $ref,
            'tel' => 'Telepon: ' . $ref,
            'anchor' => 'Anchor: #' . $ref,
            default => 'Belum ada tujuan',
        };

        return $legacy !== '' ? $text . " (rute lama \"$legacy\" sudah tidak ada)" : $text;
    }

    /**
     * Urutan baru setelah satu baris digeser. Posisi di luar jangkauan dijepit; ID yang tidak ada: urutan lama dikembalikan apa adanya.
     *
     * @param list<int> $ids urutan sekarang
     * @return list<int>
     */
    public static function reorder(array $ids, int $itemId, int $position): array
    {
        $ids = array_values(array_map('intval', $ids));
        if (!in_array($itemId, $ids, true)) {
            return $ids;
        }
        $rest = array_values(array_filter($ids, fn (int $id) => $id !== $itemId));
        array_splice($rest, max(0, min($position, count($rest))), 0, [$itemId]);

        return $rest;
    }
}
