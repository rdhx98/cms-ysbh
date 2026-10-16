<?php

namespace App\Content;

use App\Content\Blocks\ArticleCards;
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

    /** Penanda: baca ulang kategori pada pemanggilan berikut (flush). */
    private static bool $categoriesStale = false;

    /** Penanda: baca ulang tag pada pemanggilan berikut (flush). */
    private static bool $tagsStale = false;

    /** Isi footer mentah dari tabel site_settings (rilis 55), diingat per permintaan. */
    private static ?string $footerRaw = null;

    private static bool $footerLoaded = false;

    /** Palet warna mentah dari tabel site_settings (rilis 57), diingat per permintaan. */
    private static ?string $paletteRaw = null;

    private static bool $paletteLoaded = false;

    public static function flush(): void
    {
        self::$siblings = [];
        self::$footerLoaded = false;
        self::$paletteLoaded = false;
        self::$categoriesStale = true;
        self::$tagsStale = true;
    }

    /**
     * Rilis 55: isi footer untuk bahasa $locale, siap tampil (SiteFooter::view). Sumber: tabel site_settings, kunci "footer", yang diisi
     * dari CMS; belum ada / tabel belum dibuat / data rusak = isi bawaan (sama dengan footer lama). TIDAK PERNAH melempar galat.
     */
    public static function footer(string $locale): array
    {
        try {
            $lang = Languages::fromConfig();
            $locales = $lang["locales"];
            $default = $lang["default"];
        } catch (\Throwable) {
            $locales = ["en", "id"];
            $default = "en";
        }
        try {
            if (!self::$footerLoaded) {
                $value = \Illuminate\Support\Facades\DB::table("site_settings")
                    ->where("key", SiteFooter::KEY)
                    ->value("value");
                self::$footerRaw = is_string($value) ? $value : null;
                self::$footerLoaded = true;
            }
            $data = SiteFooter::resolve(self::$footerRaw);
        } catch (\Throwable) {
            $data = SiteFooter::defaults(); // tabel belum ada (migrasi belum dijalankan) atau basis data bermasalah
            self::$footerLoaded = true;
            self::$footerRaw = null;
        }

        return SiteFooter::view(
            $data,
            $locale,
            $locales,
            $default,
            fn(string $url, string $l) => self::localizeUrl($url, $l),
        );
    }

    /**
     * Rilis 57: CSS variabel palet warna untuk <head> (":root{--pal-forest:#…;--pal-forest-on:#…;…}"), dari tabel site_settings kunci "palette".
     * Belum diatur / tabel belum ada / data rusak = warna bawaan (Palette::defaults). TIDAK PERNAH melempar galat; hanya hex sah yang dicetak.
     */
    public static function paletteCss(): string
    {
        return Palette::css(self::palette());
    }

    /** Rilis 58: palet lengkap (Palette::resolve) dari site_settings; tidak pernah melempar galat (gagal = warna bawaan). */
    public static function palette(): array
    {
        try {
            if (!self::$paletteLoaded) {
                $value = \Illuminate\Support\Facades\DB::table("site_settings")
                    ->where("key", Palette::KEY)
                    ->value("value");
                self::$paletteRaw = is_string($value) ? $value : null;
                self::$paletteLoaded = true;
            }
            $raw = self::$paletteRaw;
        } catch (\Throwable) {
            $raw = null; // tabel belum ada atau basis data bermasalah
            self::$paletteRaw = null;
            self::$paletteLoaded = true;
        }

        return Palette::resolve($raw);
    }

    /**
     * Tautan lokal satu isian ("/about-us") -> alamat versi bahasa $locale, aturan yang sama dengan menu (Navigation::localized). Tautan luar,
     * mailto:, tel:, #anchor tidak diubah. Bila basis data / konfigurasi bermasalah, mengembalikan $url apa adanya (tidak pernah galat).
     */
    public static function localizeUrl(string $url, string $locale): string
    {
        try {
            $lang = Languages::fromConfig();
            $setting = function_exists("config") ? config("cms.articles_index_slug") : null;

            return NavLinks::localize(
                $url,
                $locale,
                $lang["locales"],
                $lang["default"],
                array_map(fn(string $l) => Languages::indexSlug($setting, $l), $lang["locales"]),
                fn(string $slug, string $l) => self::sibling("page", $slug, $l, $lang["locales"], $lang["default"]),
                fn(string $slug, string $l) => LinkResolver::address("page", $slug, $l),
                fn(string $l) => LinkResolver::homeAddress($l),
                fn(string $l) => LinkResolver::indexAddress($l),
                function (string $slug, string $l) use ($lang) {
                    $hit = self::categorySibling($slug, $l, $lang["locales"], $lang["default"]);

                    return $hit === null ? null : LinkResolver::categoryAddress($hit["slug"], $hit["locale"]);
                },
            );
        } catch (\Throwable) {
            return $url;
        }
    }

    public const PAGE_STATUS = "online";
    public const ARTICLE_STATUS = "published";
    /** Artikel terjadwal yang waktunya (published_at) sudah tiba tampil seperti yang terbit (rilis 38). */
    public const ARTICLE_SCHEDULED = "scheduled";

    /**
     * Saring ke baris yang tampil di situs: halaman = status 'online'; artikel = 'published', ATAU 'scheduled' yang published_at-nya sudah lewat
     * (tanpa menunggu perintah cms:publish-scheduled berjalan).
     */
    public static function visible(
        \Illuminate\Database\Eloquent\Builder $query,
        string $status,
    ): \Illuminate\Database\Eloquent\Builder {
        if ($status !== self::ARTICLE_STATUS) {
            return $query->where("status", $status);
        }

        return $query->where(function ($w) {
            $w->where("status", self::ARTICLE_STATUS)->orWhere(
                fn($due) => $due
                    ->where("status", self::ARTICLE_SCHEDULED)
                    ->whereNotNull("published_at")
                    ->where("published_at", "<=", \Illuminate\Support\Carbon::now()),
            );
        });
    }

    /**
     * Halaman online untuk alamat "{bahasa}/{slug}". KETAT per bahasa (rilis 24): slug dicari HANYA di bahasa alamat itu.
     *   - cocok di bahasa itu                                   -> ['model', 'locale', 'redirect' => null]
     *   - cocok di bahasa LAIN, dan halaman itu punya slug sah di bahasa yang diminta -> 'redirect' = slug bahasa yang diminta
     *     (301 ke versi bahasa yang SAMA; pembaca tidak dipindah ke bahasa lain)
     *   - selain itu (tidak ada, offline, atau belum diterjemahkan)  -> null (404)
     *
     * @return array{model:object,locale:string,redirect:?string}|null
     */
    public static function findPage(string $slug, string $locale, array $locales = ["en", "id"]): ?array
    {
        return self::bySlug(\App\Models\Page::class, self::PAGE_STATUS, $slug, $locale, $locales);
    }

    /** @see findPage() artikel terbit dengan slug itu @return array{model:object,locale:string,redirect:?string}|null */
    public static function findArticle(string $slug, string $locale, array $locales = ["en", "id"]): ?array
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
            if (Names::exact($row->getRawOriginal("slug"), $locale) === $slug) {
                return ["model" => $row, "locale" => $locale, "redirect" => null];
            }
        }
        foreach ($rows as $row) {
            $own = Names::exact($row->getRawOriginal("slug"), $locale);
            if ($own !== "" && Slug::isValid($own)) {
                return ["model" => $row, "locale" => $locale, "redirect" => $own];
            }
        }

        return null;
    }

    /** Baris berstatus $status yang slug-nya $slug di SALAH SATU bahasa $locales (paling banyak 5, id naik). @return iterable<object> */
    private static function rowsWithSlug(string $model, string $status, string $slug, array $locales): iterable
    {
        $expression = JsonSql::locale(new $model()->getConnection()->getDriverName(), "slug") . " = ?";

        return self::visible($model::query(), $status)
            ->where(function ($where) use ($locales, $expression, $slug) {
                foreach ($locales as $candidate) {
                    $where->orWhereRaw($expression, [JsonSql::path($candidate), $slug]);
                }
            })
            ->orderBy("id")
            ->limit(5)
            ->get();
    }

    /**
     * Slug yang dipakai sebuah halaman/artikel di tiap bahasa, hanya yang SAH dan yang judulnya terisi (= sudah diterjemahkan). Bahan
     * hreflang dan sakelar bahasa. @return array<string,string> bahasa => slug
     */
    public static function alternates(object $model, array $locales): array
    {
        $out = [];
        foreach ($locales as $l) {
            $slug = Names::exact($model->getRawOriginal("slug"), $l);
            if ($slug !== "" && Slug::isValid($slug) && Names::exact($model->getRawOriginal("title"), $l) !== "") {
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
    public static function sibling(
        string $kind,
        string $slug,
        string $locale,
        array $locales,
        ?string $default = null,
    ): ?array {
        $key = "$kind|$slug|$locale";
        if (array_key_exists($key, self::$siblings)) {
            return self::$siblings[$key];
        }
        $model = $kind === "article" ? \App\Models\Post::class : \App\Models\Page::class;
        $status = $kind === "article" ? self::ARTICLE_STATUS : self::PAGE_STATUS;
        $found = null;
        if (strlen($slug) <= 190 && Slug::isValid($slug)) {
            $all = array_values(array_unique(array_merge([$locale], $locales)));
            $row = null;
            foreach (self::rowsWithSlug($model, $status, $slug, $all) as $candidate) {
                $row ??= $candidate;
                if (Names::exact($candidate->getRawOriginal("slug"), $locale) === $slug) {
                    $row = $candidate; // cocok di bahasa yang diminta: paling tepat
                    break;
                }
            }
            if ($row !== null) {
                foreach (
                    array_values(array_unique(array_merge([$locale], $default !== null ? [$default] : [], $all)))
                    as $l
                ) {
                    $own = Names::exact($row->getRawOriginal("slug"), $l);
                    if ($own !== "" && Slug::isValid($own)) {
                        $found = ["locale" => $l, "slug" => $own];
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

            return $hit === null ? null : LinkResolver::address($kind, $hit["slug"], $hit["locale"]);
        };
    }

    /**
     * Artikel terbit terbaru (paling baru dulu), sebagai nilai MENTAH kolom untuk App\Content\Blocks\ArticleCards::prepare().
     * $locale (rilis 24): hanya artikel yang punya slug DAN judul di bahasa itu (situs berbahasa itu tidak menampilkan kartu yang tautannya 404).
     *
     * $categoryId (rilis 42): hanya artikel kategori itu (null = semua).
     *
     * @return array{rows:list<array<string,mixed>>,categories:array<int,mixed>}
     */
    public static function latestArticles(
        int $limit,
        ?int $excludeId = null,
        ?string $locale = null,
        ?int $categoryId = null,
    ): array {
        $query = self::publishedArticles($locale, $categoryId)->limit(max(1, min(12, $limit)));
        if ($excludeId !== null) {
            $query->where("id", "!=", $excludeId);
        }

        return self::feed($query);
    }

    /**
     * Satu halaman daftar artikel terbit (paling baru dulu), untuk halaman indeks artikel. $locale dan $categoryId: lihat latestArticles().
     *
     * @return array{rows:list<array<string,mixed>>,categories:array<int,mixed>,total:int,page:int,perPage:int}
     */
    public static function articlesPage(
        int $page,
        int $perPage = 9,
        ?string $locale = null,
        ?int $categoryId = null,
    ): array {
        $perPage = max(1, min(24, $perPage));
        $page = max(1, $page);
        $total = self::inCategory(
            self::inLocale(self::visible(\App\Models\Post::query(), self::ARTICLE_STATUS), $locale),
            $categoryId,
        )->count();

        return self::feed(
            self::publishedArticles($locale, $categoryId)
                ->offset(($page - 1) * $perPage)
                ->limit($perPage),
        ) + ["total" => $total, "page" => $page, "perPage" => $perPage];
    }

    /**
     * Bahan blok Artikel Terbaru yang dinamis (rilis 48): bagian UNGGULAN (artikel yang disematkan di blok, lalu dilengkapi artikel terbaru
     * yang sesuai saringan blok sampai jumlah unggulan terpenuhi) dan DAFTAR di bawahnya (artikel sisanya, paling baru dulu).
     *
     * Opsi $o: locale ?string; source 'all'|'categories'; categoryIds list<int>; pinnedIds list<int>; featured 0-6; paging 'limit'|'pages';
     * perPage (limit: 1-12, pages: 3-24); page (mode pages; diratakan ke 1..jumlah halaman); excludeId (artikel yang sedang dibuka).
     *
     * - Sematan TIDAK ikut saringan kategori (keputusan produk), tetapi harus terbit dan punya terjemahan di $locale.
     * - Sumber 'categories' tanpa kategori satu pun = hasil kosong (`noCategories`), bukan semua artikel.
     * - Artikel unggulan tidak muncul lagi di daftar; $excludeId tidak muncul di mana pun.
     *
     * @param  array<string,mixed> $o
     * @return array{featured:list<array<string,mixed>>,rows:list<array<string,mixed>>,categories:array<int,mixed>,total:int,page:int,pages:int,perPage:int,noCategories:bool}
     */
    public static function blockArticles(array $o): array
    {
        $locale = isset($o["locale"]) && is_string($o["locale"]) && $o["locale"] !== "" ? $o["locale"] : null;
        $exclude = isset($o["excludeId"]) && (int) $o["excludeId"] > 0 ? (int) $o["excludeId"] : null;
        $paged = ($o["paging"] ?? "limit") === "pages";
        $perPage = $paged ? max(3, min(24, (int) ($o["perPage"] ?? 9))) : max(1, min(12, (int) ($o["perPage"] ?? 3)));
        $featuredCount = max(0, min(6, (int) ($o["featured"] ?? 0)));
        $ids = null;
        if (($o["source"] ?? "all") === "categories") {
            $ids = array_values(
                array_unique(
                    array_filter(array_map("intval", (array) ($o["categoryIds"] ?? [])), fn(int $id) => $id > 0),
                ),
            );
        }
        // Penyaring kategori dari alamat (rilis 52): SATU kategori, dipotongkan dengan pilihan blok. Saat menyaring, bagian unggulan dan sematan
        // tidak tampil (artikel unggulan dari kategori lain akan muncul di tengah daftar yang sudah disaring).
        $filterId = (int) ($o["filterCategoryId"] ?? 0);
        if ($filterId > 0) {
            $ids = $ids === null ? [$filterId] : array_values(array_intersect($ids, [$filterId]));
            $featuredCount = 0;
        }
        // Penyaring tag dari alamat (rilis 54): SATU tag, digabung (DAN) dengan kategori. Seperti kategori, unggulan dan sematan tidak tampil.
        $tagId = (int) ($o["filterTagId"] ?? 0);
        if ($tagId > 0) {
            $featuredCount = 0;
        }
        $empty = [
            "featured" => [],
            "rows" => [],
            "categories" => [],
            "total" => 0,
            "page" => 1,
            "pages" => 1,
            "perPage" => $perPage,
            "noCategories" => false,
        ];
        if ($ids === []) {
            return ["noCategories" => true] + $empty;
        }

        $base = function () use ($locale, $ids, $exclude, $tagId): \Illuminate\Database\Eloquent\Builder {
            $q = self::inCategories(self::publishedArticles($locale), $ids);
            if ($tagId > 0) {
                $q->whereIn("id", fn($sub) => $sub->select("post_id")->from("post_tags")->where("tag_id", $tagId));
            }

            return $exclude === null ? $q : $q->where("id", "!=", $exclude);
        };

        $featured = [];
        $categories = [];
        if ($featuredCount > 0) {
            $pinned = array_values(
                array_unique(
                    array_filter(
                        array_map("intval", (array) ($o["pinnedIds"] ?? [])),
                        fn(int $id) => $id > 0 && $id !== $exclude,
                    ),
                ),
            );
            if ($pinned) {
                $found = self::feed(
                    self::inLocale(self::visible(\App\Models\Post::query(), self::ARTICLE_STATUS), $locale)->whereIn(
                        "id",
                        $pinned,
                    ),
                );
                $byId = [];
                foreach ($found["rows"] as $row) {
                    $byId[(int) $row["id"]] = $row;
                }
                foreach ($pinned as $id) {
                    // urutan sesuai pilihan di inspektur
                    if (isset($byId[$id]) && count($featured) < $featuredCount) {
                        $featured[] = $byId[$id];
                    }
                }
                $categories += $found["categories"];
            }
            $need = $featuredCount - count($featured);
            if ($need > 0) {
                $query = $base()->limit($need);
                if ($featured) {
                    $query->whereNotIn("id", array_map(fn($r) => (int) $r["id"], $featured));
                }
                $fill = self::feed($query);
                $featured = array_merge($featured, $fill["rows"]);
                $categories += $fill["categories"];
            }
        }
        $featuredIds = array_map(fn($r) => (int) $r["id"], $featured);

        $listQuery = fn() => $featuredIds ? $base()->whereNotIn("id", $featuredIds) : $base();
        $total = 0;
        $page = 1;
        $pages = 1;
        if ($paged) {
            $total = (int) $listQuery()->count();
            $pages = max(1, (int) ceil($total / $perPage));
            $page = max(1, min($pages, (int) ($o["page"] ?? 1)));
            $list = self::feed(
                $listQuery()
                    ->offset(($page - 1) * $perPage)
                    ->limit($perPage),
            );
        } else {
            $list = self::feed($listQuery()->limit($perPage));
            $total = count($list["rows"]);
        }

        return [
            "featured" => $featured,
            "rows" => $list["rows"],
            "categories" => $categories + $list["categories"],
            "total" => $total,
            "page" => $page,
            "pages" => $pages,
            "perPage" => $perPage,
            "noCategories" => false,
        ];
    }

    /**
     * Daftar artikel SATU blok lengkap dengan penyaringnya (rilis 52). Satu pintu untuk renderer blok dan untuk pemeriksaan di halaman (SEO,
     * 404, 301): keduanya memanggil ini dengan masukan yang sama dan mendapat jawaban yang sama.
     *
     * @param array<string,mixed> $clean data blok yang sudah dibersihkan (LatestArticlesBlock::sanitize)
     * @param array<string,mixed> $query parameter alamat (request()->query())
     * @param string[] $locales
     * @return array{found:array<string,mixed>,chips:list<array<string,mixed>>,showFilter:bool,active:?array<string,mixed>,redirect:?string,pageParam:string,filterParam:string,requestedPage:?int,activeTag:?array{id:int,name:string,slug:string},tagRedirect:?string,tagParam:string}
     */
    public static function listing(
        array $clean,
        string $blockId,
        ?string $primaryId,
        string $lang,
        array $locales,
        array $query,
        ?int $excludeId = null,
    ): array {
        $pageParam = ArticleListing::pageParam($blockId, $primaryId);
        $filterParam = ArticleListing::filterParam($blockId, $primaryId);
        $pickIds =
            ($clean["source"] ?? "all") === "categories" ? ArticleCards::pickIds($clean["categories"] ?? []) : null;

        // Parameter ?category= berlaku untuk blok UTAMA walau chip-nya tidak ditampilkan (menu "Artikel per kategori" dan alamat lama
        // /articles/category/{slug} dialihkan ke sana); chip hanya tampil bila diaktifkan DAN ada dua kategori atau lebih.
        $chips = [];
        $active = null;
        $redirect = null;
        if ($pickIds !== [] && ($primaryId === $blockId || !empty($clean["show_filter"]))) {
            $chips = ArticleListing::chips(
                self::categoryRows(),
                self::categoryCounts($pickIds, $lang),
                $pickIds,
                $lang,
                $locales,
            );
            $picked = ArticleListing::pick($chips, $query[$filterParam] ?? null);
            $active = $picked["chip"];
            $redirect = $picked["redirect"];
        }
        $requested = ArticleListing::requestedPage($query[$pageParam] ?? null);

        // Penyaring tag (rilis 54): hanya blok UTAMA. Tag dicari di tabel tags menurut slug bahasa halaman; slug bahasa lain -> 301 ke slugnya
        // di bahasa halaman; tidak dikenal atau belum punya slug di bahasa ini -> diabaikan (tampil seperti tanpa saringan).
        $tagParam = ArticleListing::tagParam($blockId, $primaryId);
        $activeTag = null;
        $tagRedirect = null;
        $rawTag = $query[$tagParam] ?? null;
        if ($primaryId !== null && $primaryId === $blockId && is_string($rawTag) && $rawTag !== "") {
            $hit = self::findTag($rawTag, $lang, $locales);
            if ($hit !== null) {
                $activeTag = ["id" => $hit["id"], "name" => $hit["name"], "slug" => $hit["slug"]];
                $tagRedirect = $hit["redirect"];
            }
        }

        $found = self::blockArticles([
            "locale" => $lang,
            "source" => $clean["source"] ?? "all",
            "categoryIds" => $pickIds ?? [],
            "pinnedIds" => ArticleCards::pickIds($clean["pinned"] ?? []),
            "featured" => (int) ($clean["featured_count"] ?? 0),
            "paging" => $clean["paging"] ?? "limit",
            "perPage" => (int) ($clean["limit"] ?? 3),
            "page" => $requested ?? 1,
            "excludeId" => $excludeId,
            "filterCategoryId" => $active["id"] ?? 0,
            "filterTagId" => $activeTag["id"] ?? 0,
        ]);

        return [
            "found" => $found,
            "chips" => $chips,
            "showFilter" => !empty($clean["show_filter"]) && count($chips) >= 2,
            "active" => $active,
            "redirect" => $redirect,
            "pageParam" => $pageParam,
            "filterParam" => $filterParam,
            "requestedPage" => $requested,
            "activeTag" => $activeTag,
            "tagRedirect" => $tagRedirect,
            "tagParam" => $tagParam,
        ];
    }

    /**
     * Nama penulis artikel (rilis 53): penulis utama (`posts.user_id`) lalu penulis tambahan (`post_users`), dibaca dari `users.name`.
     * Hanya membaca; tabel yang tidak ada atau galat apa pun = daftar kosong (kepala artikel tampil tanpa nama, halaman tidak rusak).
     *
     * @return list<string>
     */
    public static function authorNames(int $postId, mixed $mainId): array
    {
        try {
            $extra = [];
            try {
                $extra = \Illuminate\Support\Facades\DB::table("post_users")
                    ->where("post_id", $postId)
                    ->pluck("user_id")
                    ->all();
            } catch (\Throwable) {
                $extra = []; // tabel penulis tambahan belum ada
            }
            $ids = array_values(
                array_unique(array_filter(array_map("intval", array_merge([$mainId], $extra)), fn(int $i) => $i > 0)),
            );
            if ($ids === []) {
                return [];
            }
            $names = \Illuminate\Support\Facades\DB::table("users")->whereIn("id", $ids)->pluck("name", "id")->all();

            return ArticleByline::names($mainId, $extra, $names);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Tag sebuah artikel untuk ditampilkan di $lang (rilis 54; aturan: ArticleTags::forArticle). Tabel tags atau post_tags yang tidak ada,
     * atau galat apa pun = daftar kosong (artikel tampil tanpa deretan tag).
     *
     * @param string[] $locales
     * @return list<array{id:int,name:string,slug:?string,slugs:array<string,string>}>
     */
    public static function articleTags(int $postId, string $lang, array $locales): array
    {
        try {
            $ids = \Illuminate\Support\Facades\DB::table("post_tags")
                ->where("post_id", $postId)
                ->pluck("tag_id")
                ->all();

            return $ids === [] ? [] : ArticleTags::forArticle(self::tagRows(), $ids, $lang, $locales);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Artikel terkait (rilis 54): artikel TERBIT lain di $lang yang berbagi tag dengan artikel $postId, paling banyak tag sama dulu, lalu
     * terbaru (ArticleTags::rank). Bentuk keluaran sama dengan blockArticles (rows + categories), siap untuk ArticleCards::prepare.
     * Tanpa tag, tanpa artikel lain, atau galat = rows kosong.
     *
     * @return array{rows:list<array<string,mixed>>,categories:array<int,mixed>}
     */
    public static function relatedArticles(int $postId, string $lang, int $limit = ArticleTags::RELATED_LIMIT): array
    {
        $none = ["rows" => [], "categories" => []];
        try {
            $tagIds = array_values(
                array_unique(
                    array_map(
                        "intval",
                        \Illuminate\Support\Facades\DB::table("post_tags")
                            ->where("post_id", $postId)
                            ->pluck("tag_id")
                            ->all(),
                    ),
                ),
            );
            if ($tagIds === []) {
                return $none;
            }
            $shared = \Illuminate\Support\Facades\DB::table("post_tags")
                ->selectRaw("post_id, COUNT(DISTINCT tag_id) as c")
                ->whereIn("tag_id", $tagIds)
                ->where("post_id", "!=", $postId)
                ->groupBy("post_id")
                ->orderByDesc("c")
                ->limit(ArticleTags::RELATED_CANDIDATES)
                ->pluck("c", "post_id")
                ->all();
            if ($shared === []) {
                return $none;
            }
            $dates = [];
            foreach (
                self::inLocale(self::visible(\App\Models\Post::query(), self::ARTICLE_STATUS), $lang)
                    ->whereIn("id", array_map("intval", array_keys($shared)))
                    ->toBase()
                    ->get(["id", "published_at"])
                as $r
            ) {
                $dates[(int) $r->id] = (string) $r->published_at;
            }
            $order = ArticleTags::rank($shared, $dates, max(1, min(12, $limit)));
            if ($order === []) {
                return $none;
            }
            $feed = self::feed(\App\Models\Post::query()->whereIn("id", $order));
            $byId = [];
            foreach ($feed["rows"] as $row) {
                $byId[(int) $row["id"]] = $row;
            }
            $rows = [];
            foreach ($order as $id) {
                if (isset($byId[$id])) {
                    $rows[] = $byId[$id];
                }
            }

            return ["rows" => $rows, "categories" => $feed["categories"]];
        } catch (\Throwable) {
            return $none;
        }
    }

    /**
     * Semua tag, nilai MENTAH kolom (tabel kecil: dibaca sekali per permintaan, dicocokkan di PHP seperti kategori). Kolom `slug` boleh tidak ada.
     *
     * @return list<array{id:mixed,name:mixed,slug:mixed}>
     */
    private static function tagRows(): array
    {
        static $rows = null;
        if ($rows !== null && !self::$tagsStale) {
            return $rows;
        }
        self::$tagsStale = false;
        try {
            try {
                $found = \App\Models\Tag::query()
                    ->orderBy("id")
                    ->limit(2000)
                    ->get(["id", "name", "slug"]);
            } catch (\Throwable) {
                $found = \App\Models\Tag::query()
                    ->orderBy("id")
                    ->limit(2000)
                    ->get(["id", "name"]);
            }
            $rows = [];
            foreach ($found as $tag) {
                $rows[] = [
                    "id" => $tag->getKey(),
                    "name" => $tag->getRawOriginal("name"),
                    "slug" => $tag->getRawOriginal("slug"),
                ];
            }
        } catch (\Throwable) {
            $rows = [];
        }

        return $rows;
    }

    /** Tag untuk alamat "?tag={slug}" (aturan sama dengan kategori: CategoryLookup::find). */
    public static function findTag(string $slug, string $locale, array $locales = ["en", "id"]): ?array
    {
        return CategoryLookup::find(self::tagRows(), $slug, $locale, $locales);
    }

    /**
     * Jumlah artikel terbit per kategori di $locale (null $ids = semua kategori). id kategori => jumlah.
     *
     * @param int[]|null $ids
     * @return array<int,int>
     */
    public static function categoryCounts(?array $ids, ?string $locale): array
    {
        try {
            $q = self::inLocale(self::visible(\App\Models\Post::query(), self::ARTICLE_STATUS), $locale)->whereNotNull(
                "category_id",
            );
            if ($ids !== null) {
                $q->whereIn("category_id", $ids ?: [0]);
            }
            $out = [];
            foreach ($q->toBase()->selectRaw("category_id, COUNT(*) as c")->groupBy("category_id")->get() as $r) {
                $out[(int) $r->category_id] = (int) $r->c;
            }

            return $out;
        } catch (\Throwable) {
            return [];
        }
    }

    /** Menyaring ke beberapa kategori (null = tanpa saringan; larik kosong = tidak ada hasil). */
    private static function inCategories(
        \Illuminate\Database\Eloquent\Builder $query,
        ?array $ids,
    ): \Illuminate\Database\Eloquent\Builder {
        return $ids === null ? $query : $query->whereIn("category_id", $ids);
    }

    /** Artikel terbit, paling baru dulu; urutan tetap pasti walau tanggalnya sama (id menurun). */
    private static function publishedArticles(
        ?string $locale = null,
        ?int $categoryId = null,
    ): \Illuminate\Database\Eloquent\Builder {
        return self::inCategory(
            self::inLocale(self::visible(\App\Models\Post::query(), self::ARTICLE_STATUS), $locale),
            $categoryId,
        )
            ->orderByDesc("published_at")
            ->orderByDesc("id");
    }

    /** Menyaring ke satu kategori (null = tanpa saringan). Id <= 0 = kategori yang mustahil ada: hasil kosong, bukan semua artikel. */
    private static function inCategory(
        \Illuminate\Database\Eloquent\Builder $query,
        ?int $categoryId,
    ): \Illuminate\Database\Eloquent\Builder {
        return $categoryId === null ? $query : $query->where("category_id", max(0, $categoryId));
    }

    /**
     * Kategori untuk halaman "artikel per kategori" (rilis 42). Tabel kecil: semua baris dibaca sekali per permintaan lalu dicocokkan di PHP
     * (App\Content\CategoryLookup), supaya bentuk kolom slug/name apa pun (peta bahasa, teks polos, kosong) terbaca sama. Kolom `slug` boleh
     * tidak ada: dibaca ulang tanpanya (slug dibentuk dari nama).
     *
     * @return list<array{id:mixed,name:mixed,slug:mixed}>
     */
    private static function categoryRows(): array
    {
        static $rows = null;
        if ($rows !== null && !self::$categoriesStale) {
            return $rows;
        }
        self::$categoriesStale = false;
        try {
            $query = \App\Models\Category::query()->orderBy("id")->limit(500);
            try {
                $found = $query->get(["id", "name", "slug"]);
            } catch (\Throwable) {
                $found = \App\Models\Category::query()
                    ->orderBy("id")
                    ->limit(500)
                    ->get(["id", "name"]);
            }
            $rows = [];
            foreach ($found as $category) {
                $rows[] = [
                    "id" => $category->getKey(),
                    "name" => $category->getRawOriginal("name"),
                    "slug" => $category->getRawOriginal("slug"),
                ];
            }
        } catch (\Throwable) {
            $rows = [];
        }

        return $rows;
    }

    /** Kategori untuk alamat "{bahasa}/…/{slug}" (aturan: CategoryLookup::find). */
    public static function findCategory(string $slug, string $locale, array $locales = ["en", "id"]): ?array
    {
        return CategoryLookup::find(self::categoryRows(), $slug, $locale, $locales);
    }

    /** Padanan slug kategori pada bahasa $locale untuk tautan menu (aturan: CategoryLookup::sibling). */
    public static function categorySibling(
        string $slug,
        string $locale,
        array $locales = ["en", "id"],
        ?string $default = null,
    ): ?array {
        return CategoryLookup::sibling(self::categoryRows(), $slug, $locale, $locales, $default);
    }

    /** Menyaring ke baris yang slug DAN judulnya terisi di $locale (null = tanpa saringan). */
    private static function inLocale(
        \Illuminate\Database\Eloquent\Builder $query,
        ?string $locale,
    ): \Illuminate\Database\Eloquent\Builder {
        if ($locale === null) {
            return $query;
        }
        $driver = $query->getModel()->getConnection()->getDriverName();
        foreach (["slug", "title"] as $column) {
            $e = JsonSql::locale($driver, $column);
            $query->whereRaw("{$e} IS NOT NULL AND {$e} <> ''", [JsonSql::path($locale), JsonSql::path($locale)]);
        }

        return $query;
    }

    /** @return array{rows:list<array<string,mixed>>,categories:array<int,mixed>} */
    private static function feed(\Illuminate\Database\Eloquent\Builder $query): array
    {
        $columns = [
            "id",
            "title",
            "slug",
            "content",
            "meta_description",
            "featured_image",
            "category_id",
            "published_at",
        ];
        $rows = [];
        foreach ($query->get($columns) as $post) {
            $row = [];
            foreach ($columns as $column) {
                $row[$column] = $post->getRawOriginal($column);
            }
            $rows[] = $row;
        }

        $categories = [];
        $ids = array_values(array_unique(array_filter(array_map(fn($r) => (int) $r["category_id"], $rows))));
        if ($ids) {
            foreach (
                \App\Models\Category::query()
                    ->whereIn("id", $ids)
                    ->get(["id", "name"])
                as $category
            ) {
                $categories[(int) $category->id] = $category->getRawOriginal("name");
            }
        }

        return ["rows" => $rows, "categories" => $categories];
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
    public static function sitemapEntries(array $locales = ["en", "id"], int $limit = 10000): array
    {
        $limit = max(1, min(Sitemap::MAX_URLS, $limit));
        $rows = [];
        $seen = [];
        $add = function (string $kind, mixed $rawSlug, mixed $rawTitle, ?string $lastmod) use (
            &$rows,
            &$seen,
            $locales,
        ): void {
            foreach ($locales as $locale) {
                $slug = Names::exact($rawSlug, $locale);
                if (
                    $slug === "" ||
                    !Slug::isValid($slug) ||
                    Names::exact($rawTitle, $locale) === "" ||
                    isset($seen["$kind:$locale:$slug"])
                ) {
                    continue;
                }
                $seen["$kind:$locale:$slug"] = true;
                $rows[] = ["kind" => $kind, "slug" => $slug, "locale" => $locale, "lastmod" => $lastmod];
            }
        };

        foreach (
            \App\Models\Page::query()
                ->where("status", self::PAGE_STATUS)
                ->orderBy("id")
                ->limit($limit)
                ->get(["id", "title", "slug", "updated_at"])
            as $page
        ) {
            $add(
                "page",
                $page->getRawOriginal("slug"),
                $page->getRawOriginal("title"),
                Sitemap::date($page->getRawOriginal("updated_at")),
            );
        }
        foreach (
            self::visible(\App\Models\Post::query(), self::ARTICLE_STATUS)
                ->orderBy("id")
                ->limit($limit)
                ->get(["id", "title", "slug", "published_at", "updated_at"])
            as $post
        ) {
            $add(
                "article",
                $post->getRawOriginal("slug"),
                $post->getRawOriginal("title"),
                Sitemap::date($post->getRawOriginal("updated_at")) ??
                    Sitemap::date($post->getRawOriginal("published_at")),
            );
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
    public static function articlesHeader(string $slug, string $locale, array $locales = ["en", "id"]): ?array
    {
        $found = self::findPage($slug, $locale, $locales);
        if ($found === null || $found["redirect"] !== null) {
            return null;
        }

        $page = $found["model"];
        $lang = $found["locale"];
        $doc = self::document($page->getRawOriginal("content"), $locales, true, $lang);
        $title = Names::of($page->getRawOriginal("title"), $lang);

        return [
            "locale" => $lang,
            "title" => $title,
            "metaTitle" => Names::of($page->getRawOriginal("meta_title"), $lang) ?: $title,
            "description" => Names::of($page->getRawOriginal("meta_description"), $lang),
            "intro" => [
                "blocks" => $doc["blocks"],
                "order" => array_slice($doc["order"], 0, $doc["closingFrom"]),
                "settings" => $doc["settings"],
            ],
            // penutup bukan bagian dari dokumen pengantar: tanpa daftar isi
            "closing" => [
                "blocks" => $doc["blocks"],
                "order" => array_slice($doc["order"], $doc["closingFrom"]),
                "settings" => [],
            ],
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
    public static function document(
        mixed $rawContent,
        array $locales = ["en", "id"],
        bool $withSnippets = true,
        ?string $lang = null,
    ): array {
        $doc = ContentDocument::fromRaw($rawContent, $locales);
        $blocks = $doc->blocks;
        $order = $doc->order;
        $closingFrom = count($order);

        if ($withSnippets) {
            $inline = $doc->snippetIds();
            $closing = ClosingPolicy::resolve(
                $doc->closingOverride(),
                self::closingDefaults(),
                self::availableByKey(),
                $inline,
            );
            $wanted = array_values(array_unique(array_merge($inline, $closing)));
            $snippets = $wanted
                ? \App\Models\Snippet::query()->online()->whereIn("id", $wanted)->get()->keyBy("id")
                : collect();

            $used = [];
            $expand = function (int $id) use ($snippets, $locales, &$blocks, &$used): array {
                if (isset($used[$id]) || !$snippets->has($id)) {
                    return [];
                }
                $used[$id] = true;
                $inner = ContentDocument::fromRaw($snippets[$id]->getRawOriginal("content"), $locales);
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
                if (
                    is_array($block) &&
                    str_replace("_", "-", strtolower((string) ($block["type"] ?? ""))) === "snippet"
                ) {
                    $sid = $block["data"]["snippet_id"] ?? null;
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

        $internal = $lang !== null ? self::internalLinks($lang, $locales, Languages::fromConfig()["default"]) : null;

        return [
            "blocks" => BlockSanitizer::forPublic($blocks, $locales, $internal),
            "order" => $order,
            "settings" => $doc->settings,
            "imported" => $doc->imported,
            "closingFrom" => $closingFrom,
        ];
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
            $data = is_array($blocks[$id]["data"] ?? null) ? $blocks[$id]["data"] : [];
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
        foreach (
            \App\Models\Snippet::query()
                ->closing()
                ->get(["id", "key"])
            as $s
        ) {
            $out[(string) $s->key] = (int) $s->id;
        }

        return $out;
    }

    /** @return array<string,int> kunci => id, semua snippet online */
    private static function availableByKey(): array
    {
        $out = [];
        foreach (
            \App\Models\Snippet::query()
                ->online()
                ->get(["id", "key"])
            as $s
        ) {
            $out[(string) $s->key] = (int) $s->id;
        }

        return $out;
    }
}
