<?php

namespace App\Content\Links;

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
     * Alamat publik halaman ('page') atau artikel ('article') untuk $slug: (1) config('cms.public.page' | 'cms.public.article') = templat jalur
     * + config('cms.public.base'); bila tidak diatur, (2) rute bernama page.show / article.show di aplikasi INI; bila tidak ada pula, (3) null.
     * TIDAK PERNAH melempar galat bila rute tidak ada (mis. di CMS yang tidak punya rute publik).
     */
    public static function address(string $kind, string $slug): ?string
    {
        $setting = fn (string $key) => function_exists('config') ? config('cms.public.' . $key) : null;
        $template = $setting($kind);
        if (is_string($template) && $template !== '') {
            return self::publicUrl($slug, $template, $setting('base') ?? '');
        }
        $name = $kind === 'page' ? 'page.show' : 'article.show';
        try {
            return function_exists('app') && app('router')->has($name) ? route($name, $slug) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolver sungguhan: model Page/Post/Media; alamat halaman/artikel lewat address(). Hasil diingat per permintaan.
     */
    public static function make(): self
    {
        $slugOf = fn ($model, string $locale) => \App\Content\Names::of($model->getRawOriginal('slug'), $locale);
        $addressOf = fn (string $kind, string $slug): ?string => self::address($kind, $slug);

        return new self(
            page: function (int $id, string $locale) use ($slugOf, $addressOf) {
                static $memo = [];
                return $memo["$id:$locale"] ??= (function () use ($id, $locale, $slugOf, $addressOf) {
                    $page = \App\Models\Page::query()->find($id);
                    $slug = $page && $page->status === 'online' ? $slugOf($page, $locale) : '';
                    return $slug !== '' ? $addressOf('page', $slug) : null;
                })();
            },
            article: function (int $id, string $locale) use ($slugOf, $addressOf) {
                static $memo = [];
                return $memo["$id:$locale"] ??= (function () use ($id, $locale, $slugOf, $addressOf) {
                    $post = \App\Models\Post::query()->find($id);
                    $slug = $post && $post->status === 'published' ? $slugOf($post, $locale) : '';
                    return $slug !== '' ? $addressOf('article', $slug) : null;
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
