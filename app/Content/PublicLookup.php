<?php

namespace App\Content;

use App\Content\Blocks\BlockSanitizer;
use App\Content\Links\LinkResolver;

/**
 * Akses data untuk SITUS PUBLIK (landing): mencari halaman/artikel dari slug, daftar artikel terbaru, dan merakit dokumen yang tampil
 * (isi sendiri + snippet yang disisipkan + snippet penutup). Hanya MEMBACA; tidak pernah menulis ke basis data.
 *
 * Memerlukan model aplikasi: App\Models\Page, Post, Snippet, Category. Kolom slug/judul berbentuk JSON per bahasa ({"id":"…","en":"…"}),
 * dibaca lewat JsonSql: baris lama berisi teks biasa (bukan JSON) tidak membuat kueri galat, nilainya dianggap kosong.
 */
final class PublicLookup
{
    /** Ingatan sibling() per permintaan (kind|slug|bahasa => hasil). Kosongkan dengan flush() (pengujian, proses panjang). @var array<string,?array> */
    private static array $siblings = [];

    public static function flush(): void
    {
        self::$siblings = [];
    }

    public const PAGE_STATUS = 'online';
    public const ARTICLE_STATUS = 'published';

    /**
     * Halaman online untuk alamat "{bahasa}/{slug}". KETAT per bahasa (rilis 24): slug dicari HANYA di bahasa alamat itu.
     *   - cocok di bahasa itu                                   -> ['model', 'locale', 'redirect' => null]
     *   - cocok di bahasa LAIN, dan halaman itu punya slug sah di bahasa yang diminta -> 'redirect' = slug bahasa yang diminta
     *     (301 ke versi bahasa yang SAMA; pembaca tidak dipindah ke bahasa lain)
     *   - selain itu (tidak ada, offline, atau belum diterjemahkan)  -> null (404)
     *
     * @return array{model:object,locale:string,redirect:?string}|null
     */
    public static function findPage(string $slug, string $locale, array $locales = ['en', 'id']): ?array
    {
        return self::bySlug(\App\Models\Page::class, self::PAGE_STATUS, $slug, $locale, $locales);
    }

    /** @see findPage() artikel terbit dengan slug itu @return array{model:object,locale:string,redirect:?string}|null */
    public static function findArticle(string $slug, string $locale, array $locales = ['en', 'id']): ?array
    {
        return self::bySlug(\App\Models\Post::class, self::ARTICLE_STATUS, $slug, $locale, $locales);
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @return array{model:object,locale:string,redirect:?string}|null
     */
    private static function bySlug(string $model, string $status, string $slug, string $locale, array $locales): ?array
    {
        if (strlen($slug) > 190 || !Slug::isValid($slug)) {
            return null; // bukan slug sah (atau mustahil ada): tidak perlu menyentuh basis data
        }
        $all = array_values(array_unique(array_merge([$locale], $locales)));
        $rows = self::rowsWithSlug($model, $status, $slug, $all);

        foreach ($rows as $row) {
            if (Names::exact($row->getRawOriginal('slug'), $locale) === $slug) {
                return ['model' => $row, 'locale' => $locale, 'redirect' => null];
            }
        }
        foreach ($rows as $row) {
            $own = Names::exact($row->getRawOriginal('slug'), $locale);
            if ($own !== '' && Slug::isValid($own)) {
                return ['model' => $row, 'locale' => $locale, 'redirect' => $own];
            }
        }

        return null;
    }

    /** Baris berstatus $status yang slug-nya $slug di SALAH SATU bahasa $locales (paling banyak 5, id naik). @return iterable<object> */
    private static function rowsWithSlug(string $model, string $status, string $slug, array $locales): iterable
    {
        $expression = JsonSql::locale((new $model())->getConnection()->getDriverName(), 'slug') . ' = ?';

        return $model::query()->where('status', $status)
            ->where(function ($where) use ($locales, $expression, $slug) {
                foreach ($locales as $candidate) {
                    $where->orWhereRaw($expression, [JsonSql::path($candidate), $slug]);
                }
            })
            ->orderBy('id')->limit(5)->get();
    }

    /**
     * Slug yang dipakai sebuah halaman/artikel di tiap bahasa, hanya yang SAH dan yang judulnya terisi (= sudah diterjemahkan). Bahan
     * hreflang dan sakelar bahasa. @return array<string,string> bahasa => slug
     */
    public static function alternates(object $model, array $locales): array
    {
        $out = [];
        foreach ($locales as $l) {
            $slug = Names::exact($model->getRawOriginal('slug'), $l);
            if ($slug !== '' && Slug::isValid($slug) && Names::exact($model->getRawOriginal('title'), $l) !== '') {
                $out[$l] = $slug;
            }
        }

        return $out;
    }

    /**
     * Alamat tiap versi bahasa sebuah halaman/artikel (bahan hreflang dan sakelar bahasa), hanya yang bisa dibentuk.
     *
     * @param 'page'|'article' $kind
     * @return array<string,string> bahasa => alamat (jalur, atau absolut bila config('cms.public.base') diisi)
     */
    public static function alternateUrls(string $kind, object $model, array $locales): array
    {
        $out = [];
        foreach (self::alternates($model, $locales) as $l => $slug) {
            $url = LinkResolver::address($kind, $slug, $l);
            if ($url !== null) {
                $out[$l] = $url;
            }
        }

        return $out;
    }

    /**
     * Padanan sebuah slug pada bahasa $locale: untuk tautan menu (kolom `url`, satu teks untuk semua bahasa) dan tautan internal://.
     * Slug dicari di SEMUA bahasa; hasilnya slug halaman itu di $locale, atau (belum diterjemahkan) di bahasa lain yang ada, bahasa bawaan dulu.
     * Diingat per permintaan. @param 'page'|'article' $kind  @return array{locale:string,slug:string}|null  null = tidak ada / tidak online
     */
    public static function sibling(string $kind, string $slug, string $locale, array $locales, ?string $default = null): ?array
    {
        $key = "$kind|$slug|$locale";
        if (array_key_exists($key, self::$siblings)) {
            return self::$siblings[$key];
        }
        $model = $kind === 'article' ? \App\Models\Post::class : \App\Models\Page::class;
        $status = $kind === 'article' ? self::ARTICLE_STATUS : self::PAGE_STATUS;
        $found = null;
        if (strlen($slug) <= 190 && Slug::isValid($slug)) {
            $all = array_values(array_unique(array_merge([$locale], $locales)));
            $row = null;
            foreach (self::rowsWithSlug($model, $status, $slug, $all) as $candidate) {
                $row ??= $candidate;
                if (Names::exact($candidate->getRawOriginal('slug'), $locale) === $slug) {
                    $row = $candidate;   // cocok di bahasa yang diminta: paling tepat
                    break;
                }
            }
            if ($row !== null) {
                foreach (array_values(array_unique(array_merge([$locale], $default !== null ? [$default] : [], $all))) as $l) {
                    $own = Names::exact($row->getRawOriginal('slug'), $l);
                    if ($own !== '' && Slug::isValid($own)) {
                        $found = ['locale' => $l, 'slug' => $own];
                        break;
                    }
                }
            }
        }

        return self::$siblings[$key] = $found;
    }

    /**
     * Pemecah tautan internal://page|article/{slug} untuk dokumen berbahasa $lang: slug yang diketik editor (bahasa mana pun) diganti slug
     * halaman itu di $lang, jadi tautan di teks Inggris menuju versi Inggris. Tujuan yang tidak online/terbit: null (tautan dibuang).
     *
     * @return callable(string,string):?string
     */
    public static function internalLinks(string $lang, array $locales, ?string $default = null): callable
    {
        return function (string $kind, string $slug) use ($lang, $locales, $default): ?string {
            $hit = self::sibling($kind, $slug, $lang, $locales, $default);

            return $hit === null ? null : LinkResolver::address($kind, $hit['slug'], $hit['locale']);
        };
    }

    /**
     * Artikel terbit terbaru (paling baru dulu), sebagai nilai MENTAH kolom untuk App\Content\Blocks\ArticleCards::prepare().
     * $locale (rilis 24): hanya artikel yang punya slug DAN judul di bahasa itu (situs berbahasa itu tidak menampilkan kartu yang tautannya 404).
     *
     * @return array{rows:list<array<string,mixed>>,categories:array<int,mixed>}
     */
    public static function latestArticles(int $limit, ?int $excludeId = null, ?string $locale = null): array
    {
        $query = self::publishedArticles($locale)->limit(max(1, min(12, $limit)));
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return self::feed($query);
    }

    /**
     * Satu halaman daftar artikel terbit (paling baru dulu), untuk halaman indeks artikel. $locale: lihat latestArticles().
     *
     * @return array{rows:list<array<string,mixed>>,categories:array<int,mixed>,total:int,page:int,perPage:int}
     */
    public static function articlesPage(int $page, int $perPage = 9, ?string $locale = null): array
    {
        $perPage = max(1, min(24, $perPage));
        $page = max(1, $page);
        $total = self::inLocale(\App\Models\Post::query()->where('status', self::ARTICLE_STATUS), $locale)->count();

        return self::feed(self::publishedArticles($locale)->offset(($page - 1) * $perPage)->limit($perPage)) + ['total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    /** Artikel terbit, paling baru dulu; urutan tetap pasti walau tanggalnya sama (id menurun). */
    private static function publishedArticles(?string $locale = null): \Illuminate\Database\Eloquent\Builder
    {
        return self::inLocale(\App\Models\Post::query()->where('status', self::ARTICLE_STATUS), $locale)->orderByDesc('published_at')->orderByDesc('id');
    }

    /** Menyaring ke baris yang slug DAN judulnya terisi di $locale (null = tanpa saringan). */
    private static function inLocale(\Illuminate\Database\Eloquent\Builder $query, ?string $locale): \Illuminate\Database\Eloquent\Builder
    {
        if ($locale === null) {
            return $query;
        }
        $driver = $query->getModel()->getConnection()->getDriverName();
        foreach (['slug', 'title'] as $column) {
            $e = JsonSql::locale($driver, $column);
            $query->whereRaw("{$e} IS NOT NULL AND {$e} <> ''", [JsonSql::path($locale), JsonSql::path($locale)]);
        }

        return $query;
    }

    /** @return array{rows:list<array<string,mixed>>,categories:array<int,mixed>} */
    private static function feed(\Illuminate\Database\Eloquent\Builder $query): array
    {
        $columns = ['id', 'title', 'slug', 'content', 'meta_description', 'featured_image', 'category_id', 'published_at'];
        $rows = [];
        foreach ($query->get($columns) as $post) {
            $row = [];
            foreach ($columns as $column) {
                $row[$column] = $post->getRawOriginal($column);
            }
            $rows[] = $row;
        }

        $categories = [];
        $ids = array_values(array_unique(array_filter(array_map(fn ($r) => (int) $r['category_id'], $rows))));
        if ($ids) {
            foreach (\App\Models\Category::query()->whereIn('id', $ids)->get(['id', 'name']) as $category) {
                $categories[(int) $category->id] = $category->getRawOriginal('name');
            }
        }

        return ['rows' => $rows, 'categories' => $categories];
    }

    /**
     * Bahan peta situs: setiap halaman ONLINE dan artikel TERBIT, satu baris per slug per bahasa (slug berbeda = alamat berbeda), bertanda
     * bahasanya (templat alamat berbeda per bahasa). Hanya slug yang sah (Slug::isValid) yang judulnya terisi di bahasa itu (sudah diterjemahkan);
     * baris lama berisi teks polos dilewati (tidak pernah bisa dibuka di situs, lihat bySlug). Nilai mentah dibaca langsung dari kolom, sehingga
     * baris lama yang kolomnya bukan JSON tidak menjatuhkan peta situs. Urutan tetap (id naik) dan dibatasi $limit per jenis.
     *
     * @param  string[] $locales
     * @return list<array{kind:string,slug:string,locale:string,lastmod:?string}>
     */
    public static function sitemapEntries(array $locales = ['en', 'id'], int $limit = 10000): array
    {
        $limit = max(1, min(Sitemap::MAX_URLS, $limit));
        $rows = [];
        $seen = [];
        $add = function (string $kind, mixed $rawSlug, mixed $rawTitle, ?string $lastmod) use (&$rows, &$seen, $locales): void {
            foreach ($locales as $locale) {
                $slug = Names::exact($rawSlug, $locale);
                if ($slug === '' || !Slug::isValid($slug) || Names::exact($rawTitle, $locale) === '' || isset($seen["$kind:$locale:$slug"])) {
                    continue;
                }
                $seen["$kind:$locale:$slug"] = true;
                $rows[] = ['kind' => $kind, 'slug' => $slug, 'locale' => $locale, 'lastmod' => $lastmod];
            }
        };

        foreach (\App\Models\Page::query()->where('status', self::PAGE_STATUS)->orderBy('id')->limit($limit)->get(['id', 'title', 'slug', 'updated_at']) as $page) {
            $add('page', $page->getRawOriginal('slug'), $page->getRawOriginal('title'), Sitemap::date($page->getRawOriginal('updated_at')));
        }
        foreach (\App\Models\Post::query()->where('status', self::ARTICLE_STATUS)->orderBy('id')->limit($limit)->get(['id', 'title', 'slug', 'published_at', 'updated_at']) as $post) {
            $add('article', $post->getRawOriginal('slug'), $post->getRawOriginal('title'), Sitemap::date($post->getRawOriginal('updated_at')) ?? Sitemap::date($post->getRawOriginal('published_at')));
        }

        return $rows;
    }

    /**
     * Kepala halaman DAFTAR ARTIKEL: halaman CMS ber-slug tertentu (bawaan "artikel", config('cms.articles_index_slug')) yang menyumbang
     * judul, deskripsi SEO, blok pengantar, dan snippet penutup. Daftarnya sendiri tetap kode landing (berhalaman).
     *
     * Penutup (mis. ajakan donasi) dipisah dari pengantar karena harus tampil SESUDAH daftar. Keduanya memakai `blocks` yang sama;
     * yang membedakan hanya `order`. Mengembalikan null bila halamannya tidak ada, offline, atau slug tidak sah: daftar tampil polos.
     *
     * KETAT per bahasa: slug kepala hanya dicari di bahasa $locale (tidak ada pengalihan: bila belum diterjemahkan, daftar tampil polos).
     *
     * @return array{locale:string,title:string,metaTitle:string,description:string,intro:array{blocks:array,order:array,settings:array},closing:array{blocks:array,order:array,settings:array}}|null
     */
    public static function articlesHeader(string $slug, string $locale, array $locales = ['en', 'id']): ?array
    {
        $found = self::findPage($slug, $locale, $locales);
        if ($found === null || $found['redirect'] !== null) {
            return null;
        }

        $page = $found['model'];
        $lang = $found['locale'];
        $doc = self::document($page->getRawOriginal('content'), $locales, true, $lang);
        $title = Names::of($page->getRawOriginal('title'), $lang);

        return [
            'locale' => $lang,
            'title' => $title,
            'metaTitle' => Names::of($page->getRawOriginal('meta_title'), $lang) ?: $title,
            'description' => Names::of($page->getRawOriginal('meta_description'), $lang),
            'intro' => ['blocks' => $doc['blocks'], 'order' => array_slice($doc['order'], 0, $doc['closingFrom']), 'settings' => $doc['settings']],
            // penutup bukan bagian dari dokumen pengantar: tanpa daftar isi
            'closing' => ['blocks' => $doc['blocks'], 'order' => array_slice($doc['order'], $doc['closingFrom']), 'settings' => []],
        ];
    }

    /**
     * Dokumen yang tampil untuk sebuah halaman/artikel: isi sendiri, blok `snippet` (data.snippet_id) diperluas di tempatnya, lalu snippet
     * PENUTUP di akhir menurut ClosingPolicy (bawaan + penggantian settings.closing). Hasil dibersihkan (BlockSanitizer) dan siap
     * diberikan ke <x-content.sections>.
     *
     * $lang (rilis 24): bahasa halaman. Tautan internal://page|article/{slug} di seluruh dokumen diselesaikan ke versi bahasa itu (internalLinks);
     * null = pemecah bawaan BlockSanitizer (bahasa permintaan ini).
     *
     * Batasan: hanya blok `snippet` di tingkat atas yang diperluas; satu snippet tampil paling banyak sekali per halaman.
     *
     * `closingFrom` = indeks di `order` tempat snippet PENUTUP mulai (= jumlah order bila tidak ada), supaya halaman yang menyela isinya dengan
     * hal lain (daftar artikel) bisa menaruh penutup SESUDAH sela itu, bukan sebelumnya.
     *
     * @return array{blocks:array,order:array,settings:array,imported:bool,closingFrom:int}
     */
    public static function document(mixed $rawContent, array $locales = ['en', 'id'], bool $withSnippets = true, ?string $lang = null): array
    {
        $doc = ContentDocument::fromRaw($rawContent, $locales);
        $blocks = $doc->blocks;
        $order = $doc->order;
        $closingFrom = count($order);

        if ($withSnippets) {
            $inline = $doc->snippetIds();
            $closing = ClosingPolicy::resolve($doc->closingOverride(), self::closingDefaults(), self::availableByKey(), $inline);
            $wanted = array_values(array_unique(array_merge($inline, $closing)));
            $snippets = $wanted ? \App\Models\Snippet::query()->online()->whereIn('id', $wanted)->get()->keyBy('id') : collect();

            $used = [];
            $expand = function (int $id) use ($snippets, $locales, &$blocks, &$used): array {
                if (isset($used[$id]) || !$snippets->has($id)) {
                    return [];
                }
                $used[$id] = true;
                $inner = ContentDocument::fromRaw($snippets[$id]->getRawOriginal('content'), $locales);
                $ids = [];
                foreach ($inner->order as $blockId) {
                    if (isset($blocks[$blockId]) || !isset($inner->blocks[$blockId])) {
                        continue; // ID sama dengan blok halaman (praktis tak mungkin): lewati
                    }
                    foreach (self::reach($inner->blocks, $blockId) as $childId => $child) {
                        if (!isset($blocks[$childId])) {
                            $blocks[$childId] = $child;
                        }
                    }
                    $ids[] = $blockId;
                }

                return $ids;
            };

            $final = [];
            foreach ($order as $blockId) {
                $block = $blocks[$blockId] ?? null;
                if (is_array($block) && str_replace('_', '-', strtolower((string) ($block['type'] ?? ''))) === 'snippet') {
                    $sid = $block['data']['snippet_id'] ?? null;
                    if ((is_int($sid) || (is_string($sid) && ctype_digit($sid))) && (int) $sid > 0) {
                        array_push($final, ...$expand((int) $sid)); // blok pembungkusnya sendiri dibuang
                    }
                    unset($blocks[$blockId]);
                    continue;
                }
                $final[] = $blockId;
            }
            $closingFrom = count($final);
            foreach ($closing as $sid) {
                array_push($final, ...$expand($sid));
            }
            $order = $final;
        }

        $internal = $lang !== null ? self::internalLinks($lang, $locales, Languages::fromConfig()['default']) : null;

        return ['blocks' => BlockSanitizer::forPublic($blocks, $locales, $internal), 'order' => $order, 'settings' => $doc->settings, 'imported' => $doc->imported, 'closingFrom' => $closingFrom];
    }

    /** Blok $rootId beserta semua anaknya (zona kolom / langkah), menurut kunci id. @return array<string,array> */
    private static function reach(array $blocks, string $rootId): array
    {
        $out = [];
        $stack = [$rootId];
        while ($stack) {
            $id = array_pop($stack);
            if (isset($out[$id]) || !isset($blocks[$id]) || !is_array($blocks[$id])) {
                continue;
            }
            $out[$id] = $blocks[$id];
            $data = is_array($blocks[$id]['data'] ?? null) ? $blocks[$id]['data'] : [];
            array_walk_recursive($data, function ($value) use (&$stack, $blocks) {
                if (is_string($value) && isset($blocks[$value])) {
                    $stack[] = $value;
                }
            });
        }

        return $out;
    }

    /** @return array<string,int> kunci => id, snippet penutup online, berurutan */
    private static function closingDefaults(): array
    {
        $out = [];
        foreach (\App\Models\Snippet::query()->closing()->get(['id', 'key']) as $s) {
            $out[(string) $s->key] = (int) $s->id;
        }

        return $out;
    }

    /** @return array<string,int> kunci => id, semua snippet online */
    private static function availableByKey(): array
    {
        $out = [];
        foreach (\App\Models\Snippet::query()->online()->get(['id', 'key']) as $s) {
            $out[(string) $s->key] = (int) $s->id;
        }

        return $out;
    }
}
