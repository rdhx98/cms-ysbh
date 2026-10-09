<?php

namespace App\Content\Links;

use App\Content\Languages;
use Closure;

/**
 * Tautan blok ({kind, ref, media_id, new_tab}) -> URL AMAN, atau null bila tidak sah / tidak boleh ditampilkan.
 * Murni: pencarian halaman/artikel/berkas dilewatkan sebagai fungsi, jadi seluruh aturan keamanan bisa diuji tanpa database.
 *
 * Aturan keamanan (tidak boleh dilonggarkan): hanya http(s), mailto, tel, jalur relatif satu garis miring, dan #anchor.
 * "javascript:", "data:", "vbscript:", "//host" (protokol-relatif), spasi/kontrol di URL -> null. Tombol tanpa URL sah TIDAK dirender.
 */
final class LinkResolver
{
    public const KINDS = ['page', 'article', 'file', 'url', 'tel', 'mailto', 'anchor'];

    /**
     * @param Closure(int,string):?string $page    id, bahasa -> URL halaman online, atau null
     * @param Closure(int,string):?string $article id, bahasa -> URL artikel terbit, atau null
     * @param Closure(int):?string        $file    id media -> URL berkas, atau null
     */
    public function __construct(
        private readonly Closure $page,
        private readonly Closure $article,
        private readonly Closure $file,
    ) {
    }

    /**
     * Alamat publik dari TEMPLAT jalur (mis. '/artikel/{slug}') + alamat dasar situs publik (mis. 'https://ysbh.org').
     * Murni dan ketat: templat harus diawali satu '/', hanya berisi karakter jalur dan tepat SATU penanda ('{slug}' atau '{file}');
     * alamat dasar hanya skema+host(+port), tanpa jalur. Nilai di-encode (untuk '{file}' per segmen, garis miring dipertahankan,
     * "." / ".." ditolak). Tidak sah -> null (tidak dirender), bukan galat.
     *
     * Dipakai di KEDUA aplikasi dengan templat yang SAMA: landing (situs publik) dan CMS (admin), supaya tautan di pratinjau CMS menuju
     * alamat landing yang benar walau CMS tidak punya rute page.show / article.show.
     */
    public static function publicUrl(string $value, mixed $template, mixed $base = '', string $token = '{slug}'): ?string
    {
        if ($value === '' || !in_array($token, ['{slug}', '{file}'], true) || !is_string($template)
            || !preg_match('#^/[A-Za-z0-9/_.\-{}]*$#D', $template) || substr_count($template, $token) !== 1 || str_starts_with($template, '//')) {
            return null; // '//' = protokol-relatif: akan menuju host lain
        }
        if ($base !== null && !is_string($base)) {
            return null; // konfigurasi rusak (mis. larik): jangan diam-diam dianggap kosong
        }
        $base = rtrim(trim((string) $base), '/');
        if ($base !== '' && !preg_match('#^https?://[A-Za-z0-9.-]+(?::\d{1,5})?$#D', $base)) {
            return null;
        }
        if ($token === '{file}') {
            $segments = explode('/', $value);
            foreach ($segments as $seg) {
                if ($seg === '' || $seg === '.' || $seg === '..' || preg_match('/[\x00-\x1f\x7f\\\\]/', $seg)) {
                    return null;
                }
            }
            $encoded = implode('/', array_map('rawurlencode', $segments));
        } else {
            $encoded = rawurlencode($value);
        }

        return $base . str_replace($token, $encoded, $template);
    }

    /**
     * Alamat BERANDA: bila $slug sama dengan slug beranda ($homeSlug, config('cms.home_slug') untuk bahasa itu), alamatnya adalah alamat dasar +
     * $homePath ("/" untuk bahasa bawaan, "/id" untuk bahasa lain). Selain itu null. Murni. Slug beranda kosong, bukan teks, atau tidak sah =
     * tidak ada beranda (null); alamat dasar atau jalur beranda rusak = null (konfigurasi rusak tidak diam-diam dianggap kosong).
     * Di CMS kuncinya boleh tidak ada: tautan ke halaman itu tetap "/{slug}", yang di landing dialihkan 301 ke beranda.
     */
    public static function homeUrl(string $slug, mixed $homeSlug, mixed $base = '', mixed $homePath = '/'): ?string
    {
        if (!is_string($homeSlug) || $homeSlug === '' || $slug === '' || $slug !== $homeSlug || !\App\Content\Slug::isValid($homeSlug)) {
            return null;
        }

        return self::pathUrl($homePath, $base);
    }

    /**
     * Alamat dasar + jalur tetap ("/", "/id", "/articles", "/id/artikel"). Jalur: diawali satu "/", segmen huruf/angka/_/- saja, tanpa "//" dan
     * tanpa garis miring di ujung (kecuali "/" sendiri). Rusak = null. Murni.
     */
    public static function pathUrl(mixed $path, mixed $base = ''): ?string
    {
        if (!is_string($path) || !preg_match('#^/(?:[A-Za-z0-9_\-]+(?:/[A-Za-z0-9_\-]+)*)?$#D', $path)) {
            return null;
        }
        if ($base !== null && !is_string($base)) {
            return null;
        }
        $base = rtrim(trim((string) $base), '/');
        if ($base !== '' && !preg_match('#^https?://[A-Za-z0-9.-]+(?::\d{1,5})?$#D', $base)) {
            return null;
        }

        return $base . $path;
    }

    /**
     * Alamat publik halaman ('page') atau artikel ('article') untuk $slug pada bahasa $locale (null = bahasa permintaan ini):
     * (1) config('cms.public.page' | 'cms.public.article') = templat jalur + config('cms.public.base'); bila tidak diatur, (2) rute bernama
     * page.show / article.show ("id.page.show" untuk bahasa selain bawaan) di aplikasi INI; bila tidak ada pula, (3) null.
     * Templat boleh TEKS (semua bahasa) atau PETA bahasa (['en' => '/{slug}', 'id' => '/id/{slug}']). Beranda: slug beranda bahasa itu
     * (config('cms.home_slug'), teks atau peta) dilayani di jalur beranda (config('cms.public.home'), bawaan "/" atau "/{kode}").
     * TIDAK PERNAH melempar galat bila rute tidak ada (mis. di CMS yang tidak punya rute publik).
     */
    public static function address(string $kind, string $slug, ?string $locale = null): ?string
    {
        $locale ??= self::currentLocale();
        $lang = Languages::fromConfig();
        $setting = fn (string $key) => function_exists('config') ? config('cms.public.' . $key) : null;
        if ($kind === 'page' && function_exists('config')) {
            $home = self::homeUrl($slug, Languages::setting(config('cms.home_slug'), $locale), $setting('base') ?? '', Languages::homePath($setting('home'), $locale, $lang['default']));
            if ($home !== null) {
                return $home;   // halaman beranda dilayani di "/" (atau "/id"), bukan di "/{slug}" (yang hanya mengalihkan)
            }
        }
        $template = Languages::setting($setting($kind), $locale);
        if ($template !== '') {
            return self::publicUrl($slug, $template, $setting('base') ?? '');
        }
        $name = ($locale !== '' && $locale !== $lang['default'] ? $locale . '.' : '') . ($kind === 'page' ? 'page.show' : 'article.show');
        try {
            return function_exists('app') && app('router')->has($name) ? route($name, $slug) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Alamat BERANDA bahasa $locale (config('cms.public.home'); bawaan "/" atau "/{kode}"), atau null bila alamat dasar rusak. */
    public static function homeAddress(?string $locale = null): ?string
    {
        $locale ??= self::currentLocale();

        return self::pathUrl(Languages::homePath(self::config('cms.public.home'), $locale, Languages::fromConfig()['default']), self::config('cms.public.base') ?? '');
    }

    /** Alamat DAFTAR ARTIKEL bahasa $locale (config('cms.public.articles'), teks atau peta), atau null bila tidak diatur / rusak. */
    public static function indexAddress(?string $locale = null): ?string
    {
        $locale ??= self::currentLocale();
        $path = Languages::setting(self::config('cms.public.articles'), $locale);

        return self::pathUrl($path, self::config('cms.public.base') ?? '');
    }

    private static function config(string $key): mixed
    {
        try {
            return function_exists('config') ? config($key) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Bahasa permintaan ini ('' bila tidak ada aplikasi, mis. pengujian murni). */
    private static function currentLocale(): string
    {
        try {
            return function_exists('app') ? (string) app()->getLocale() : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Resolver sungguhan: model Page/Post/Media; alamat halaman/artikel lewat address(). Hasil diingat per permintaan.
     * Tujuan tanpa slug pada bahasa yang diminta tidak dibuang: tautan memakai slug (dan awalan) bahasa lain yang ada, urut bahasa bawaan
     * dulu. Tautan yang membawa pembaca ke bahasa lain lebih baik daripada tombol yang hilang atau mati.
     */
    public static function make(): self
    {
        $addressOf = function (object $model, string $kind, string $locale): ?string {
            $lang = Languages::fromConfig();
            $order = array_values(array_unique(array_merge([$locale], [$lang['default']], $lang['locales'])));
            foreach ($order as $l) {
                $slug = \App\Content\Names::exact($model->getRawOriginal('slug'), $l);
                if ($slug !== '' && ($url = self::address($kind, $slug, $l)) !== null) {
                    return $url;
                }
            }

            return null;
        };

        return new self(
            page: function (int $id, string $locale) use ($addressOf) {
                static $memo = [];
                return $memo["$id:$locale"] ??= (function () use ($id, $locale, $addressOf) {
                    $page = \App\Models\Page::query()->find($id);
                    return $page && $page->status === 'online' ? $addressOf($page, 'page', $locale) : null;
                })();
            },
            article: function (int $id, string $locale) use ($addressOf) {
                static $memo = [];
                return $memo["$id:$locale"] ??= (function () use ($id, $locale, $addressOf) {
                    $post = \App\Models\Post::query()->find($id);
                    return $post && $post->status === 'published' ? $addressOf($post, 'article', $locale) : null;
                })();
            },
            file: function (int $id) {
                static $memo = [];
                return $memo[$id] ??= \App\Models\Media::query()->find($id)?->url();
            },
        );
    }

    /** @param array{kind?:mixed,ref?:mixed,media_id?:mixed} $link */
    public function url(array $link, string $locale = 'id'): ?string
    {
        $kind = $link['kind'] ?? 'url';
        $ref = is_scalar($link['ref'] ?? null) ? trim((string) $link['ref']) : '';

        return match ($kind) {
            'page' => ($id = self::positiveInt($ref)) ? ($this->page)($id, $locale) : null,
            'article' => ($id = self::positiveInt($ref)) ? ($this->article)($id, $locale) : null,
            // berkas: HANYA media_id yang dipercaya (url yang dikirim browser diabaikan)
            'file' => ($id = self::positiveInt($link['media_id'] ?? null)) ? ($this->file)($id) : null,
            'url' => self::safeUrl($ref),
            'tel' => ($n = self::phone($ref)) !== null ? 'tel:' . $n : null,
            'mailto' => filter_var($ref, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $ref : null,
            'anchor' => ($a = self::anchor($ref)) !== null ? '#' . $a : null,
            default => null,
        };
    }

    /** http(s) mutlak atau jalur relatif "/…" (bukan "//host"); tanpa spasi/kontrol. null bila tidak aman. */
    public static function safeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            return null;
        }
        if (preg_match('#^https?://[^/\s]+#i', $url) && filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        if (preg_match('#^/(?!/)#', $url)) {
            return $url;
        }

        return null;
    }

    public static function phone(string $value): ?string
    {
        $n = preg_replace('/[^0-9+]/', '', $value) ?? '';
        $n = ($n !== '' && $n[0] === '+' ? '+' : '') . str_replace('+', '', $n);

        // minimal 3 angka: nomor darurat (119, 112, 110) sah dan sangat mungkin dipakai di situs kesehatan
        return strlen(str_replace('+', '', $n)) >= 3 && strlen($n) <= 20 ? $n : null;
    }

    public static function anchor(string $value): ?string
    {
        $a = ltrim(trim($value), '#');

        return $a !== '' && preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,79}$/D', $a) ? $a : null;
    }

    public static function positiveInt(mixed $value): int
    {
        return (is_int($value) || (is_string($value) && ctype_digit($value))) && (int) $value > 0 ? (int) $value : 0;
    }
}
