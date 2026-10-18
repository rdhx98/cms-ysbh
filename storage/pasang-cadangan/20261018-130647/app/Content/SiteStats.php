<?php

namespace App\Content;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Statistik situs untuk dashboard CMS (rilis 79). Hanya MEMBACA. Dua kelompok:
 *   - statistik KONTEN dari tabel posts/pages/article_reviews/users (tanpa pelacakan apa pun);
 *   - statistik KUNJUNGAN dari tabel site_visits yang ditulis landing lewat Visits (tanpa cookie, tanpa IP).
 * Setiap metode memakai nama tabel langsung (bukan model) supaya tidak bergantung pada model proyek, dan tahan terhadap tabel yang belum ada
 * (dashboard tetap tampil di proyek yang belum menjalankan migrasi: angka 0, bukan galat).
 *
 * $today dapat diberikan untuk pengujian; bawaannya hari ini menurut zona waktu aplikasi.
 */
final class SiteStats
{
    public const PERIODS = [7, 30, 90];

    public const ATTENTION_TABS = ['placeholders', 'translation'];
    public const KIND_LABELS = ['page' => 'Halaman', 'article' => 'Artikel', 'snippet' => 'Snippet'];
    public const PROBLEM_LABELS = [TranslationAudit::NOT_YET => 'Belum diterjemahkan', TranslationAudit::HALF => 'Terjemahan setengah jalan', TranslationAudit::CONTENT => 'Teks kosong di blok'];

    public const STATUS_LABELS = [
        ArticleWorkflow::DRAFT => 'Draf', ArticleWorkflow::REVIEW => 'Ditinjau', ArticleWorkflow::SCHEDULED => 'Terjadwal',
        ArticleWorkflow::PUBLISHED => 'Terbit', ArticleWorkflow::ARCHIVED => 'Arsip', ArticleWorkflow::REJECTED => 'Ditolak',
    ];

    // ---------------------------------------------------------------- kunjungan

    /**
     * Kunjungan dan pengunjung unik $days hari terakhir (termasuk hari ini), deret harian tanpa celah, dan periode sebelumnya sebagai pembanding.
     * "uniques" = jumlah pengunjung unik PER HARI dijumlahkan: orang yang datang dua hari berbeda dihitung dua kali (tidak ada cookie untuk mengenalinya).
     *
     * @return array{days:int,from:string,to:string,views:int,uniques:int,prev_views:int,prev_uniques:int,series:list<array{day:string,views:int,uniques:int}>,peak:array{day:string,views:int}|null}
     */
    public static function visits(int $days = 30, ?string $today = null): array
    {
        $days = max(1, min(366, $days));
        $to = Carbon::parse($today ?? Carbon::now()->toDateString())->startOfDay();
        $from = $to->copy()->subDays($days - 1);
        $prevFrom = $from->copy()->subDays($days);

        $rows = [];
        if (Schema::hasTable('site_visits')) {
            foreach (DB::table('site_visits')->where('kind', Visits::SITE)->whereBetween('day', [$prevFrom->toDateString(), $to->toDateString()])->get(['day', 'views', 'uniques']) as $r) {
                $rows[substr((string) $r->day, 0, 10)] = [(int) $r->views, (int) $r->uniques];
            }
        }

        $series = [];
        $views = $uniques = 0;
        $peak = null;
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            [$v, $u] = $rows[$key] ?? [0, 0];
            $series[] = ['day' => $key, 'views' => $v, 'uniques' => $u];
            $views += $v;
            $uniques += $u;
            if ($v > 0 && ($peak === null || $v > $peak['views'])) {
                $peak = ['day' => $key, 'views' => $v];
            }
        }
        $prevViews = $prevUniques = 0;
        for ($d = $prevFrom->copy(); $d->lt($from); $d->addDay()) {
            [$v, $u] = $rows[$d->toDateString()] ?? [0, 0];
            $prevViews += $v;
            $prevUniques += $u;
        }

        return ['days' => $days, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'views' => $views, 'uniques' => $uniques,
            'prev_views' => $prevViews, 'prev_uniques' => $prevUniques, 'series' => $series, 'peak' => $peak];
    }

    /**
     * Halaman/artikel terbanyak dibuka dalam $days hari. $onlyIds membatasi ke id tertentu (penulis: artikelnya sendiri).
     *
     * @param 'page'|'article' $kind
     * @param list<int>|null $onlyIds
     * @return list<array{id:int,views:int}>
     */
    public static function top(string $kind, int $days = 30, int $limit = 5, ?array $onlyIds = null, ?string $today = null): array
    {
        if (! in_array($kind, Visits::KINDS, true) || ! Schema::hasTable('site_visits') || $onlyIds === []) {
            return [];
        }
        $to = Carbon::parse($today ?? Carbon::now()->toDateString());
        $query = DB::table('site_visits')->where('kind', $kind)->whereBetween('day', [$to->copy()->subDays(max(1, $days) - 1)->toDateString(), $to->toDateString()]);
        if ($onlyIds !== null) {
            $query->whereIn('item_id', $onlyIds);
        }

        return $query->selectRaw('item_id, sum(views) as total')->groupBy('item_id')->orderByDesc('total')->orderBy('item_id')->limit(max(1, $limit))->get()
            ->map(fn ($r) => ['id' => (int) $r->item_id, 'views' => (int) $r->total])->all();
    }

    /** Kunjungan per bahasa (halaman + artikel) dalam $days hari, terbanyak dulu. @return array<string,int> */
    public static function byLocale(int $days = 30, ?string $today = null): array
    {
        if (! Schema::hasTable('site_visits')) {
            return [];
        }
        $to = Carbon::parse($today ?? Carbon::now()->toDateString());
        $out = [];
        $rows = DB::table('site_visits')->whereIn('kind', Visits::KINDS)->whereBetween('day', [$to->copy()->subDays(max(1, $days) - 1)->toDateString(), $to->toDateString()])
            ->selectRaw('locale, sum(views) as total')->groupBy('locale')->orderByDesc('total')->get();
        foreach ($rows as $r) {
            $out[(string) $r->locale] = (int) $r->total;
        }

        return $out;
    }

    /**
     * Jumlah kunjungan sekumpulan id dalam $days hari (penulis: artikelnya sendiri).
     *
     * @param 'page'|'article' $kind
     * @param list<int> $ids
     */
    public static function viewsOf(string $kind, array $ids, int $days = 30, ?string $today = null): int
    {
        $sum = 0;
        foreach (self::top($kind, $days, 100000, $ids, $today) as $r) {
            $sum += $r['views'];
        }

        return $sum;
    }

    /** Kunjungan seumur hidup satu artikel (untuk daftar artikel). */
    public static function articleTotal(int $id): int
    {
        return Schema::hasTable('site_visits') ? (int) DB::table('site_visits')->where('kind', 'article')->where('item_id', $id)->sum('views') : 0;
    }

    // ------------------------------------------------------------------- konten

    /**
     * Jumlah artikel per status (semua status ada, bernilai 0 bila kosong). $userId = hanya artikel milik penulis itu (penulis utama atau tambahan).
     *
     * @return array<string,int>
     */
    public static function articleStatuses(?int $userId = null): array
    {
        $out = array_fill_keys([ArticleWorkflow::DRAFT, ArticleWorkflow::REVIEW, ArticleWorkflow::SCHEDULED, ArticleWorkflow::PUBLISHED, ArticleWorkflow::ARCHIVED, ArticleWorkflow::REJECTED], 0);
        if (! Schema::hasTable('posts')) {
            return $out;
        }
        foreach (self::postsOf($userId)->selectRaw('status, count(*) as n')->groupBy('status')->get() as $r) {
            $out[(string) $r->status] = ($out[(string) $r->status] ?? 0) + (int) $r->n;
        }

        return $out;
    }

    /**
     * Artikel berstatus Ditinjau: jumlah dan yang paling lama menunggu (sejak pengajuan terakhirnya).
     *
     * @return array{count:int,oldest_days:int|null}
     */
    public static function awaitingReview(?int $userId = null, ?string $now = null): array
    {
        if (! Schema::hasTable('posts')) {
            return ['count' => 0, 'oldest_days' => null];
        }
        $posts = self::postsOf($userId)->where('status', ArticleWorkflow::REVIEW)->get(['id', 'updated_at']);
        if ($posts->isEmpty()) {
            return ['count' => 0, 'oldest_days' => null];
        }
        $since = [];
        if (Schema::hasTable('article_reviews')) {
            foreach (DB::table('article_reviews')->where('action', 'submit')->whereIn('post_id', $posts->pluck('id')->all())->selectRaw('post_id, max(created_at) as at')->groupBy('post_id')->get() as $r) {
                $since[(int) $r->post_id] = $r->at;
            }
        }
        $oldest = null;
        foreach ($posts as $p) {
            $at = $since[(int) $p->id] ?? $p->updated_at;
            if ($at !== null && ($oldest === null || (string) $at < $oldest)) {
                $oldest = (string) $at;
            }
        }
        $now = Carbon::parse($now ?? Carbon::now());

        return ['count' => $posts->count(), 'oldest_days' => $oldest === null ? null : max(0, (int) Carbon::parse($oldest)->startOfDay()->diffInDays($now->copy()->startOfDay()))];
    }

    /**
     * Artikel TERBIT (terbit, atau terjadwal yang waktunya sudah lewat) per bulan, $months bulan terakhir termasuk bulan ini, tanpa celah.
     *
     * @return list<array{month:string,count:int}>  month = "2026-10"
     */
    public static function publishedByMonth(int $months = 12, ?int $userId = null, ?string $today = null): array
    {
        $months = max(1, min(36, $months));
        $end = Carbon::parse($today ?? Carbon::now()->toDateString())->startOfMonth();
        $counts = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $counts[$end->copy()->subMonthsNoOverflow($i)->format('Y-m')] = 0;
        }
        if (Schema::hasTable('posts')) {
            $now = Carbon::parse($today ?? Carbon::now())->endOfDay()->toDateTimeString();
            $rows = self::postsOf($userId)->where(function ($w) use ($now) {
                $w->where('status', ArticleWorkflow::PUBLISHED)
                    ->orWhere(fn ($due) => $due->where('status', ArticleWorkflow::SCHEDULED)->whereNotNull('published_at')->where('published_at', '<=', $now));
            })->get(['published_at', 'created_at']);
            foreach ($rows as $r) {
                $at = $r->published_at ?? $r->created_at;
                $key = $at === null ? null : substr((string) $at, 0, 7);
                if ($key !== null && isset($counts[$key])) {
                    $counts[$key]++;
                }
            }
        }

        return array_map(fn ($m, $n) => ['month' => $m, 'count' => $n], array_keys($counts), array_values($counts));
    }

    /** Halaman online dan tidak online. @return array{online:int,offline:int} */
    public static function pages(): array
    {
        if (! Schema::hasTable('pages')) {
            return ['online' => 0, 'offline' => 0];
        }
        $online = (int) DB::table('pages')->where('status', PublicLookup::PAGE_STATUS)->count();

        return ['online' => $online, 'offline' => (int) DB::table('pages')->count() - $online];
    }

    /**
     * Terjemahan yang perlu perhatian di halaman online dan artikel terbit (aturan TranslationAudit, sama dengan cms:audit-translations).
     * errors = terjemahan setengah jalan atau blok kosong (tampil kosong di situs); info = record yang memang belum diterjemahkan.
     * Angka kartu dan DAFTAR (translationRecords) berasal dari satu sumber, jadi selalu sama.
     *
     * @param list<string> $locales
     * @return array{errors:int,info:int,records:int}
     */
    public static function translation(array $locales): array
    {
        $errors = $info = $records = 0;
        foreach (self::translationRecords($locales, $locales[0] ?? 'id') as $r) {
            $bad = 0;
            foreach ($r['issues'] as $i) {
                if ($i['severity'] === 'info') {
                    $info++;
                } else {
                    $errors++;
                    $bad++;
                }
            }
            $records += $bad > 0 ? 1 : 0;
        }

        return ['errors' => $errors, 'info' => $info, 'records' => $records];
    }

    /** Halaman online, artikel terbit, dan snippet online yang masih memuat [ISI-DULU] atau blok Gambar tanpa berkas (aturan cms:audit-placeholders). */
    public static function placeholders(): int
    {
        return count(self::placeholderRecords(['id', 'en'], 'id'));
    }

    /** Kolom yang diperiksa penanda, per jenis, beserta namanya untuk manusia. */
    public const PLACEHOLDER_COLUMNS = [
        'page' => ['title' => 'Judul', 'slug' => 'Slug', 'content' => 'Isi', 'meta_title' => 'Judul SEO', 'meta_description' => 'Deskripsi SEO'],
        'article' => ['title' => 'Judul', 'slug' => 'Slug', 'content' => 'Isi', 'meta_title' => 'Judul SEO', 'meta_description' => 'Deskripsi SEO'],
        'snippet' => ['title' => 'Judul', 'content' => 'Isi'],
    ];

    /** Label status untuk manusia: artikel memakai STATUS_LABELS, halaman/snippet Online/Offline. */
    public static function statusLabel(string $kind, string $status): string
    {
        if ($kind === 'article') {
            return self::STATUS_LABELS[$status] ?? $status;
        }

        return $status === PublicLookup::PAGE_STATUS ? 'Online' : ($status === 'offline' ? 'Offline' : $status);
    }

    /**
     * Dua DAFTAR "perlu perhatian" untuk komponen content.attention-list, dengan penyaringan yang sama untuk tampilan dan hitungan tab.
     * $all = termasuk yang belum tayang; $showInfo = ikut yang hanya "belum diterjemahkan"; $kind = '' (semua) | page | article | snippet.
     * counts tidak terpengaruh $kind (jumlah di label tab tetap utuh saat disaring per jenis).
     *
     * @param list<string> $locales
     * @return array{counts:array{placeholders:int,translation:int},rows:array{placeholders:list<array<string,mixed>>,translation:list<array<string,mixed>>}}
     */
    public static function attention(array $locales, string $locale, bool $all = false, bool $showInfo = false, string $kind = ''): array
    {
        $kind = array_key_exists($kind, self::KIND_LABELS) ? $kind : '';
        $ph = self::placeholderRecords($locales, $locale, $all);
        $tr = [];
        foreach (self::translationRecords($locales, $locale, $all) as $r) {
            if (! $showInfo) {
                $r['issues'] = array_values(array_filter($r['issues'], fn ($i) => $i['severity'] === 'error'));
            }
            if ($r['issues'] !== []) {
                $tr[] = $r;
            }
        }
        $only = fn (array $rows) => $kind === '' ? $rows : array_values(array_filter($rows, fn ($r) => $r['kind'] === $kind));

        return ['counts' => ['placeholders' => count($ph), 'translation' => count($tr)], 'rows' => ['placeholders' => $only($ph), 'translation' => $only($tr)]];
    }

    private const SOURCE_TABLE = ['page' => 'pages', 'article' => 'posts', 'snippet' => 'snippets'];

    /**
     * DAFTAR record yang masih memuat penanda [ISI-DULU] atau blok Gambar tanpa berkas: persis yang dihitung kartu "Penanda belum diisi".
     * $all = termasuk halaman offline, artikel bukan-terbit, dan snippet offline (daftar pekerjaan sebelum situs dibuka; sama dengan `--all` pada perintah).
     *
     * @param list<string> $locales
     * @return list<array{kind:string,id:int,title:string,status:string,columns:list<string>,empty_images:int}>
     *         columns = kolom bertanda (judul/slug/isi/judul SEO/deskripsi SEO), empty_images = jumlah blok Gambar tanpa berkas
     */
    public static function placeholderRecords(array $locales, string $locale, bool $all = false): array
    {
        $out = [];
        foreach (self::PLACEHOLDER_COLUMNS as $kind => $columns) {
            foreach (self::sourceRows($kind, $all, array_keys($columns)) as $row) {
                $marked = [];
                foreach ($columns as $c => $label) {
                    if (Placeholders::contains($row->{$c} ?? null)) {
                        $marked[] = $label;
                    }
                }
                $images = Placeholders::emptyImageCount($row->content ?? null);
                if ($marked !== [] || $images > 0) {
                    $out[] = ['kind' => $kind, 'id' => (int) $row->id, 'title' => self::displayTitle($row->title ?? null, $locale, $locales), 'status' => (string) $row->status, 'columns' => $marked, 'empty_images' => $images];
                }
            }
        }

        return $out;
    }

    /**
     * DAFTAR halaman/artikel dengan masalah terjemahan, dikelompokkan per record. severity 'error' = separuh atau teks kosong di blok (dihitung kartu);
     * 'info' = belum diterjemahkan sama sekali di bahasa itu (hanya keterangan di kartu). Record yang hanya punya 'info' tetap muncul agar bisa dipilih.
     *
     * @param list<string> $locales
     * @return list<array{kind:string,id:int,title:string,status:string,issues:list<array{locale:string,problem:string,severity:string,detail:string}>}>
     */
    public static function translationRecords(array $locales, string $locale, bool $all = false): array
    {
        $out = [];
        if (count($locales) < 2) {
            return $out;
        }
        foreach (['page', 'article'] as $kind) {
            foreach (self::sourceRows($kind, $all, ['title', 'slug', 'content']) as $row) {
                $issues = [];
                foreach (TranslationAudit::check([(array) $row], $locales) as $p) {
                    $issues[] = ['locale' => (string) $p['locale'], 'problem' => (string) $p['problem'], 'severity' => $p['problem'] === TranslationAudit::NOT_YET ? 'info' : 'error', 'detail' => (string) $p['detail']];
                }
                if ($issues !== []) {
                    $out[] = ['kind' => $kind, 'id' => (int) $row->id, 'title' => self::displayTitle($row->title ?? null, $locale, $locales), 'status' => (string) $row->status, 'issues' => $issues];
                }
            }
        }

        return $out;
    }

    /** Baris sumber: $all = semua; selain itu hanya yang tampil di situs (halaman/snippet online, artikel terbit atau terjadwal yang sudah lewat). @return iterable<object> */
    private static function sourceRows(string $kind, bool $all, array $columns): iterable
    {
        $table = self::SOURCE_TABLE[$kind] ?? null;
        if ($table === null || ! Schema::hasTable($table)) {
            return [];
        }
        $query = DB::table($table);
        if (! $all) {
            if ($kind === 'article') {
                $query->where(function ($w) {
                    $w->where('status', ArticleWorkflow::PUBLISHED)->orWhere(fn ($due) => $due->where('status', ArticleWorkflow::SCHEDULED)->whereNotNull('published_at')->where('published_at', '<=', Carbon::now()->toDateTimeString()));
                });
            } else {
                $query->where('status', PublicLookup::PAGE_STATUS);   // 'online' (halaman dan snippet)
            }
        }

        return $query->orderBy('id')->get(array_merge(['id', 'status'], $columns));
    }

    private static function displayTitle(mixed $rawTitle, string $locale, array $locales): string
    {
        $t = trim(Names::of($rawTitle, $locale, $locales));
        if ($t === '' && is_string($rawTitle)) {
            $t = trim(strip_tags($rawTitle));
        }

        return $t !== '' ? mb_substr($t, 0, 120) : '(tanpa judul)';
    }

    /**
     * Pengguna: total, aktif, tidak aktif, dan jumlah per peran (peran tanpa pengguna bernilai 0).
     *
     * @return array{total:int,active:int,inactive:int,roles:array<string,int>}
     */
    public static function users(): array
    {
        if (! Schema::hasTable('users')) {
            return ['total' => 0, 'active' => 0, 'inactive' => 0, 'roles' => []];
        }
        $total = (int) DB::table('users')->count();
        $active = Schema::hasColumn('users', 'active') ? (int) DB::table('users')->where('active', true)->count() : $total;
        $roles = [];
        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            foreach (DB::table('roles')->leftJoin('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')->selectRaw('roles.name as name, count(model_has_roles.model_id) as n')->groupBy('roles.name')->orderBy('roles.name')->get() as $r) {
                $roles[(string) $r->name] = (int) $r->n;
            }
        }

        return ['total' => $total, 'active' => $active, 'inactive' => $total - $active, 'roles' => $roles];
    }

    /** Id artikel milik penulis (utama atau tambahan). @return list<int> */
    public static function articleIdsOf(int $userId): array
    {
        return Schema::hasTable('posts') ? array_map('intval', self::postsOf($userId)->pluck('id')->all()) : [];
    }

    /**
     * Judul tampil untuk deretan id (satu query). Bahasa $locale dulu, lalu bahasa lain. Id yang sudah tidak ada = "(dihapus)".
     *
     * @param 'posts'|'pages' $table
     * @param list<int> $ids
     * @param list<string> $locales
     * @return array<int,string>
     */
    public static function titles(string $table, array $ids, string $locale, array $locales): array
    {
        $out = array_fill_keys($ids, '(dihapus)');
        if ($ids === [] || ! in_array($table, ['posts', 'pages'], true) || ! Schema::hasTable($table)) {
            return $out;
        }
        foreach (DB::table($table)->whereIn('id', $ids)->get(['id', 'title']) as $r) {
            $t = Names::of($r->title, $locale, $locales);
            $out[(int) $r->id] = $t !== '' ? $t : '(tanpa judul)';
        }

        return $out;
    }

    // ----------------------------------------------------------------- dashboard

    /**
     * Semua angka dashboard dalam satu larik siap tampil (komponen content.stats-panel hanya menggambar). Peninjau (admin/editor) melihat seluruh
     * situs; selain itu (penulis) hanya artikelnya sendiri dan tanpa angka kunjungan seluruh situs, pengguna, atau audit.
     *
     * @param list<string> $locales
     * @return array<string,mixed>
     */
    public static function dashboard(?int $userId, bool $reviewer, int $days, array $locales, string $locale, ?string $today = null, bool $cache = true): array
    {
        $days = in_array($days, self::PERIODS, true) ? $days : 30;
        $scope = $reviewer ? null : $userId;
        $own = $reviewer ? null : self::articleIdsOf((int) $userId);
        $statuses = self::articleStatuses($scope);
        $review = self::awaitingReview($scope);
        $monthly = self::publishedByMonth(12, $scope, $today);
        $thisMonth = $monthly[count($monthly) - 1]['count'] ?? 0;
        $pct = fn (int $now, int $before): ?int => $before > 0 ? (int) round(($now - $before) / $before * 100) : null;

        $statusRows = [];
        foreach (self::STATUS_LABELS as $key => $label) {
            $statusRows[] = ['key' => $key, 'label' => $label, 'count' => (int) ($statuses[$key] ?? 0)];
        }

        $out = ['scope' => $reviewer ? 'all' : 'own', 'days' => $days, 'statuses' => $statusRows, 'monthly' => $monthly, 'visits' => null,
            'tops' => ['article' => [], 'page' => []], 'locales' => [], 'translation' => null, 'placeholders' => null, 'users' => null, 'pages' => null];

        if ($reviewer) {
            $visits = self::visits($days, $today);
            $out['visits'] = $visits;
            $articleTop = self::top('article', $days, 5, null, $today);
            $pageTop = self::top('page', $days, 5, null, $today);
            $at = self::titles('posts', array_column($articleTop, 'id'), $locale, $locales);
            $pt = self::titles('pages', array_column($pageTop, 'id'), $locale, $locales);
            $out['tops'] = [
                'article' => array_map(fn ($r) => $r + ['title' => $at[$r['id']] ?? '(dihapus)'], $articleTop),
                'page' => array_map(fn ($r) => $r + ['title' => $pt[$r['id']] ?? '(dihapus)'], $pageTop),
            ];
            $byLocale = self::byLocale($days, $today);
            $sum = array_sum($byLocale);
            foreach ($byLocale as $code => $n) {
                $out['locales'][] = ['code' => $code, 'views' => $n, 'share' => $sum > 0 ? (int) round($n / $sum * 100) : 0];
            }
            $remember = static fn (string $key, callable $fn) => $cache && class_exists(\Illuminate\Support\Facades\Cache::class) ? \Illuminate\Support\Facades\Cache::remember($key, 600, $fn) : $fn();
            $out['translation'] = $remember('site-stats:translation:' . md5(implode(',', $locales)), fn () => self::translation($locales));
            $out['placeholders'] = $remember('site-stats:placeholders', fn () => self::placeholders());
            $out['users'] = self::users();
            $out['pages'] = self::pages();
            $out['cards'] = [
                ['key' => 'review', 'label' => 'Menunggu tinjauan', 'value' => $review['count'], 'hint' => $review['count'] > 0 && $review['oldest_days'] !== null ? 'terlama ' . $review['oldest_days'] . ' hari' : 'tidak ada antrean', 'tone' => $review['count'] > 0 ? 'warn' : 'ok', 'delta' => null],
                ['key' => 'month', 'label' => 'Terbit bulan ini', 'value' => $thisMonth, 'hint' => 'artikel', 'tone' => 'neutral', 'delta' => null],
                ['key' => 'views', 'label' => 'Kunjungan', 'value' => $visits['views'], 'hint' => $days . ' hari terakhir', 'tone' => 'neutral', 'delta' => $pct($visits['views'], $visits['prev_views'])],
                ['key' => 'uniques', 'label' => 'Pengunjung harian unik', 'value' => $visits['uniques'], 'hint' => 'dijumlahkan per hari', 'tone' => 'neutral', 'delta' => $pct($visits['uniques'], $visits['prev_uniques'])],
                ['key' => 'translation', 'label' => 'Terjemahan perlu perhatian', 'value' => $out['translation']['errors'], 'hint' => $out['translation']['info'] . ' belum diterjemahkan', 'tone' => $out['translation']['errors'] > 0 ? 'warn' : 'ok', 'delta' => null],
                ['key' => 'placeholders', 'label' => 'Penanda belum diisi', 'value' => $out['placeholders'], 'hint' => 'halaman, artikel, snippet', 'tone' => $out['placeholders'] > 0 ? 'warn' : 'ok', 'delta' => null],
                ['key' => 'users', 'label' => 'Pengguna aktif', 'value' => $out['users']['active'], 'hint' => 'dari ' . $out['users']['total'] . ' akun', 'tone' => 'neutral', 'delta' => null],
                ['key' => 'pages', 'label' => 'Halaman online', 'value' => $out['pages']['online'], 'hint' => $out['pages']['offline'] . ' offline', 'tone' => 'neutral', 'delta' => null],
            ];
        } else {
            $own = $own ?? [];
            $articleTop = self::top('article', $days, 5, $own, $today);
            $at = self::titles('posts', array_column($articleTop, 'id'), $locale, $locales);
            $out['tops']['article'] = array_map(fn ($r) => $r + ['title' => $at[$r['id']] ?? '(dihapus)'], $articleTop);
            $out['cards'] = [
                ['key' => 'draft', 'label' => 'Draf saya', 'value' => $statuses[ArticleWorkflow::DRAFT] ?? 0, 'hint' => 'artikel', 'tone' => 'neutral', 'delta' => null],
                ['key' => 'review', 'label' => 'Menunggu tinjauan', 'value' => $review['count'], 'hint' => $review['count'] > 0 && $review['oldest_days'] !== null ? 'terlama ' . $review['oldest_days'] . ' hari' : 'tidak ada', 'tone' => $review['count'] > 0 ? 'warn' : 'ok', 'delta' => null],
                ['key' => 'published', 'label' => 'Artikel saya terbit', 'value' => ($statuses[ArticleWorkflow::PUBLISHED] ?? 0) + ($statuses[ArticleWorkflow::SCHEDULED] ?? 0), 'hint' => 'termasuk terjadwal', 'tone' => 'neutral', 'delta' => null],
                ['key' => 'views', 'label' => 'Dibaca', 'value' => self::viewsOf('article', $own, $days, $today), 'hint' => $days . ' hari terakhir', 'tone' => 'neutral', 'delta' => null],
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------------ pembantu

    /** Query posts, dibatasi ke penulis bila $userId diberikan (user_id, atau tabel post_users bila ada). */
    private static function postsOf(?int $userId): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('posts');
        if ($userId === null) {
            return $q;
        }

        return $q->where(function ($w) use ($userId) {
            $w->where('user_id', $userId);
            if (Schema::hasTable('post_users')) {
                $w->orWhereIn('id', DB::table('post_users')->where('user_id', $userId)->select('post_id'));
            }
        });
    }
}
