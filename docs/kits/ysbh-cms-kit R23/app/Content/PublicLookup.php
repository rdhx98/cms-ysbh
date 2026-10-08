<?php

namespace App\Content;

use App\Content\Blocks\BlockSanitizer;

/**
 * Akses data untuk SITUS PUBLIK (landing): mencari halaman/artikel dari slug, daftar artikel terbaru, dan merakit dokumen yang tampil
 * (isi sendiri + snippet yang disisipkan + snippet penutup). Hanya MEMBACA; tidak pernah menulis ke basis data.
 *
 * Memerlukan model aplikasi: App\Models\Page, Post, Snippet, Category. Kolom slug/judul berbentuk JSON per bahasa ({"id":"…","en":"…"}),
 * dibaca lewat JsonSql: baris lama berisi teks biasa (bukan JSON) tidak membuat kueri galat, nilainya dianggap kosong.
 */
final class PublicLookup
{
    public const PAGE_STATUS = 'online';
    public const ARTICLE_STATUS = 'published';

    /** @return array{model:object,locale:string}|null halaman online dengan slug itu (bahasa yang diminta dulu, lalu bahasa lain) */
    public static function findPage(string $slug, string $locale, array $locales = ['id', 'en']): ?array
    {
        return self::bySlug(\App\Models\Page::class, self::PAGE_STATUS, $slug, $locale, $locales);
    }

    /** @return array{model:object,locale:string}|null artikel terbit dengan slug itu */
    public static function findArticle(string $slug, string $locale, array $locales = ['id', 'en']): ?array
    {
        return self::bySlug(\App\Models\Post::class, self::ARTICLE_STATUS, $slug, $locale, $locales);
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     * @return array{model:object,locale:string}|null
     */
    private static function bySlug(string $model, string $status, string $slug, string $locale, array $locales): ?array
    {
        if (strlen($slug) > 190 || !Slug::isValid($slug)) {
            return null; // bukan slug sah (atau mustahil ada): tidak perlu menyentuh basis data
        }
        $driver = (new $model())->getConnection()->getDriverName();
        $expression = JsonSql::locale($driver, 'slug') . ' = ?';

        foreach (array_values(array_unique(array_merge([$locale], $locales))) as $candidate) {
            $found = $model::query()->where('status', $status)->whereRaw($expression, [JsonSql::path($candidate), $slug])->first();
            if ($found) {
                return ['model' => $found, 'locale' => $candidate];
            }
        }

        return null;
    }

    /**
     * Artikel terbit terbaru (paling baru dulu), sebagai nilai MENTAH kolom untuk App\Content\Blocks\ArticleCards::prepare().
     *
     * @return array{rows:list<array<string,mixed>>,categories:array<int,mixed>}
     */
    public static function latestArticles(int $limit, ?int $excludeId = null): array
    {
        $query = self::publishedArticles()->limit(max(1, min(12, $limit)));
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return self::feed($query);
    }

    /**
     * Satu halaman daftar artikel terbit (paling baru dulu), untuk halaman indeks artikel.
     *
     * @return array{rows:list<array<string,mixed>>,categories:array<int,mixed>,total:int,page:int,perPage:int}
     */
    public static function articlesPage(int $page, int $perPage = 9): array
    {
        $perPage = max(1, min(24, $perPage));
        $page = max(1, $page);
        $total = \App\Models\Post::query()->where('status', self::ARTICLE_STATUS)->count();

        return self::feed(self::publishedArticles()->offset(($page - 1) * $perPage)->limit($perPage)) + ['total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    /** Artikel terbit, paling baru dulu; urutan tetap pasti walau tanggalnya sama (id menurun). */
    private static function publishedArticles(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Post::query()->where('status', self::ARTICLE_STATUS)->orderByDesc('published_at')->orderByDesc('id');
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
     * Bahan peta situs: setiap halaman ONLINE dan artikel TERBIT, satu baris per slug per bahasa (slug berbeda = alamat berbeda).
     * Hanya slug yang sah (Slug::isValid); slug teks polos pada baris lama dibaca sebagai satu slug. Nilai mentah dibaca langsung dari kolom,
     * sehingga baris lama yang kolomnya bukan JSON tidak menjatuhkan peta situs. Urutan tetap (id naik) dan dibatasi $limit per jenis.
     *
     * @param  string[] $locales
     * @return list<array{kind:string,slug:string,lastmod:?string}>
     */
    public static function sitemapEntries(array $locales = ['id', 'en'], int $limit = 10000): array
    {
        $limit = max(1, min(Sitemap::MAX_URLS, $limit));
        $rows = [];
        $seen = [];
        $add = function (string $kind, mixed $rawSlug, ?string $lastmod) use (&$rows, &$seen, $locales): void {
            $slugs = [];
            $decoded = is_array($rawSlug) ? $rawSlug : (is_string($rawSlug) ? json_decode($rawSlug, true) : null);
            if (is_array($decoded)) {
                foreach ($locales as $locale) {
                    $v = $decoded[$locale] ?? null;
                    if (is_string($v)) {
                        $slugs[] = $v;
                    }
                }
            } elseif (is_string($rawSlug)) {
                $slugs[] = $rawSlug;   // baris lama: teks polos
            }
            foreach ($slugs as $slug) {
                if (Slug::isValid($slug) && !isset($seen[$kind . ':' . $slug])) {
                    $seen[$kind . ':' . $slug] = true;
                    $rows[] = ['kind' => $kind, 'slug' => $slug, 'lastmod' => $lastmod];
                }
            }
        };

        foreach (\App\Models\Page::query()->where('status', self::PAGE_STATUS)->orderBy('id')->limit($limit)->get(['id', 'slug', 'updated_at']) as $page) {
            $add('page', $page->getRawOriginal('slug'), Sitemap::date($page->getRawOriginal('updated_at')));
        }
        foreach (\App\Models\Post::query()->where('status', self::ARTICLE_STATUS)->orderBy('id')->limit($limit)->get(['id', 'slug', 'published_at', 'updated_at']) as $post) {
            $add('article', $post->getRawOriginal('slug'), Sitemap::date($post->getRawOriginal('updated_at')) ?? Sitemap::date($post->getRawOriginal('published_at')));
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
     * @return array{locale:string,title:string,metaTitle:string,description:string,intro:array{blocks:array,order:array,settings:array},closing:array{blocks:array,order:array,settings:array}}|null
     */
    public static function articlesHeader(string $slug, string $locale, array $locales = ['id', 'en']): ?array
    {
        $found = self::findPage($slug, $locale, $locales);
        if ($found === null) {
            return null;
        }

        $page = $found['model'];
        $lang = $found['locale'];
        $doc = self::document($page->getRawOriginal('content'), $locales);
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
     * Batasan: hanya blok `snippet` di tingkat atas yang diperluas; satu snippet tampil paling banyak sekali per halaman.
     *
     * `closingFrom` = indeks di `order` tempat snippet PENUTUP mulai (= jumlah order bila tidak ada), supaya halaman yang menyela isinya dengan
     * hal lain (daftar artikel) bisa menaruh penutup SESUDAH sela itu, bukan sebelumnya.
     *
     * @return array{blocks:array,order:array,settings:array,imported:bool,closingFrom:int}
     */
    public static function document(mixed $rawContent, array $locales = ['id', 'en'], bool $withSnippets = true): array
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

        return ['blocks' => BlockSanitizer::forPublic($blocks, $locales), 'order' => $order, 'settings' => $doc->settings, 'imported' => $doc->imported, 'closingFrom' => $closingFrom];
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
