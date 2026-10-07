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

    /** Resolver sungguhan: model Page/Post/Media dan rute publik (page.show, article.show). Hasil diingat per permintaan. */
    public static function make(): self
    {
        $slugOf = fn ($model, string $locale) => \App\Content\Names::of($model->getRawOriginal('slug'), $locale);

        return new self(
            page: function (int $id, string $locale) use ($slugOf) {
                static $memo = [];
                return $memo["$id:$locale"] ??= (function () use ($id, $locale, $slugOf) {
                    $page = \App\Models\Page::query()->find($id);
                    $slug = $page && $page->status === 'online' ? $slugOf($page, $locale) : '';
                    return $slug !== '' ? route('page.show', $slug) : null;
                })();
            },
            article: function (int $id, string $locale) use ($slugOf) {
                static $memo = [];
                return $memo["$id:$locale"] ??= (function () use ($id, $locale, $slugOf) {
                    $post = \App\Models\Post::query()->find($id);
                    $slug = $post && $post->status === 'published' ? $slugOf($post, $locale) : '';
                    return $slug !== '' ? route('article.show', $slug) : null;
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

        return strlen(str_replace('+', '', $n)) >= 5 && strlen($n) <= 20 ? $n : null;
    }

    public static function anchor(string $value): ?string
    {
        $a = ltrim(trim($value), '#');

        return $a !== '' && preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,79}$/', $a) ? $a : null;
    }

    public static function positiveInt(mixed $value): int
    {
        return (is_int($value) || (is_string($value) && ctype_digit($value))) && (int) $value > 0 ? (int) $value : 0;
    }
}
