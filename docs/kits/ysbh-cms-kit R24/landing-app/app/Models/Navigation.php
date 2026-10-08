<?php

namespace App\Models;

use App\Content\Languages;
use App\Content\Links\LinkResolver;
use App\Content\NavLinks;
use App\Content\PublicLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Spatie\Translatable\HasTranslations;

class Navigation extends Model
{
    //
    use HasTranslations; // 🌟 2. Aktifkan trait di sini

    protected $fillable = ['label', 'route_name', 'url', 'order', 'is_active'];
    public $translatable = ['label'];

    protected $casts = [
        'label' => 'array',
        'published_at'     => 'datetime',
    ];

    /**
     * Tujuan menu. Urutan: (1) rute bernama (route_name) bila ada di aplikasi ini dan tidak butuh parameter;
     * (2) kolom `url` (halaman CMS, mis. "/tentang-kami"), hanya bila aman: http(s), /jalur, mailto:, tel:, #anchor (aturan LinkResolver);
     * (3) "#". Tidak pernah melempar galat: rute yang hilang atau salah ketik tidak menjatuhkan seluruh situs.
     */
    public function getHrefAttribute(): string
    {
        $name = trim((string) $this->route_name);
        if ($name !== '') {
            try {
                $effective = $this->effectiveRoute($name);
                if ($effective !== null) {
                    return route($effective);
                }
            } catch (\Throwable) {
                // rute butuh parameter, atau router tidak siap: lanjut ke url
            }
        }

        $url = trim((string) $this->url);
        if ($url !== '') {
            // awalan menentukan jenis tautan di pemecah: mailto:, tel:, #anchor punya aturan sendiri; selebihnya http(s) atau /jalur
            $kind = 'url';
            $ref = $url;
            if (str_starts_with($url, 'mailto:')) {
                [$kind, $ref] = ['mailto', substr($url, 7)];
            } elseif (str_starts_with($url, 'tel:')) {
                [$kind, $ref] = ['tel', substr($url, 4)];
            } elseif (str_starts_with($url, '#')) {
                [$kind, $ref] = ['anchor', substr($url, 1)];
            }
            $safe = LinkResolver::make()->url(['kind' => $kind, 'ref' => $ref], app()->getLocale());
            if ($safe !== null) {
                return $kind === 'url' ? self::localized($safe) : $safe;
            }
        }

        return '#';
    }

    /**
     * Rilis 24: kolom `url` satu teks untuk semua bahasa, jadi jalur lokal ("/about-us", "/tentang-kami", "/") diarahkan ke versi bahasa
     * pembaca (App\Content\NavLinks). Alamat luar (http…), anchor, mailto, dan tel tidak diubah.
     */
    private static function localized(string $url): string
    {
        try {
            $lang = Languages::fromConfig();
            $locale = app()->getLocale();
            $setting = config('cms.articles_index_slug');

            return NavLinks::localize(
                $url,
                $locale,
                $lang['locales'],
                $lang['default'],
                array_map(fn (string $l) => Languages::indexSlug($setting, $l), $lang['locales']),
                fn (string $slug, string $l) => PublicLookup::sibling('page', $slug, $l, $lang['locales'], $lang['default']),
                fn (string $slug, string $l) => LinkResolver::address('page', $slug, $l),
                fn (string $l) => LinkResolver::homeAddress($l),
                fn (string $l) => LinkResolver::indexAddress($l),
            );
        } catch (\Throwable) {
            return $url;   // basis data belum siap atau konfigurasi rusak: menu tetap tampil dengan alamat yang diketik, tidak menjatuhkan situs
        }
    }

    /** Rilis 24: untuk bahasa selain bawaan, rute berawalan bahasa ("id.articles") didahulukan atas rute bawaan ("articles"). */
    private function effectiveRoute(string $name): ?string
    {
        $lang = Languages::fromConfig();
        $locale = app()->getLocale();
        if ($locale !== $lang['default'] && Route::has($locale . '.' . $name)) {
            return $locale . '.' . $name;
        }

        return Route::has($name) ? $name : null;
    }

    /** Menu yang sedang dibuka. Rute bernama: rute itu atau turunannya ("programs" aktif di "programs-malaria"). Url: jalur yang sama atau di bawahnya. */
    public function isCurrent(): bool
    {
        $name = trim((string) $this->route_name);
        try {
            if ($name !== '' && ($effective = $this->effectiveRoute($name)) !== null) {
                return request()->routeIs($effective, $effective . '-*');
            }
        } catch (\Throwable) {
            return false;
        }

        $href = $this->href;
        if (str_starts_with($href, '#') || preg_match('/^(?:mailto|tel):/i', $href)) {
            return false; // anchor, surel, dan telepon bukan halaman: tidak pernah menjadi menu aktif
        }
        $host = parse_url($href, PHP_URL_HOST);
        if ($host !== null && $host !== false && strcasecmp($host, request()->getHost()) !== 0) {
            return false;
        }
        $path = rtrim((string) parse_url($href, PHP_URL_PATH), '/');
        $current = '/' . trim(request()->path(), '/');
        // Beranda tiap bahasa ("/" dan "/id") hanya aktif di dirinya sendiri: "/id" adalah awalan SEMUA halaman bahasa Indonesia
        $homes = array_map(fn (string $l) => rtrim((string) parse_url((string) LinkResolver::homeAddress($l), PHP_URL_PATH), '/'), Languages::fromConfig()['locales']);
        if ($path === '' || in_array($path, $homes, true)) {
            return $current === ($path === '' ? '/' : $path);
        }

        return $current === $path || str_starts_with($current, $path . '/');
    }
}
