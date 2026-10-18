<?php

namespace App\Content;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pencacah kunjungan situs publik (rilis 79). Dipakai LANDING (menulis); CMS membacanya lewat SiteStats.
 *
 * Prinsip: tanpa cookie, tanpa menyimpan IP, tanpa pihak ketiga, tidak pernah mengganggu halaman.
 *   - Satu kunjungan = satu permintaan GET halaman/artikel yang berhasil dirender (bukan bot, bukan prefetch).
 *   - Pengunjung unik per HARI = hash satu arah dari IP + browser + garam yang berganti tiap hari (kunci aplikasi + tanggal). IP tidak
 *     pernah disimpan; hash tidak bisa dihubungkan antarhari; baris hash lebih tua dari 2 hari dihapus.
 *   - Angka disimpan agregat per hari (tabel site_visits), jadi ukuran data kecil walau trafik besar.
 *   - Penulisan dilakukan SETELAH respons terkirim (terminating) dan semua galat ditelan: gagal menghitung tidak pernah menjatuhkan halaman.
 *   - Dimatikan lewat config('cms.stats.enabled') = false.
 */
final class Visits
{
    public const SITE = 'site';
    public const KINDS = ['page', 'article'];

    /** Browser tanpa manusia: perayap, alat pantau, pustaka HTTP, pratinjau tautan. UA kosong dianggap bot. */
    private const BOT = '~bot|crawl|spider|slurp|archiver|fetch|monitor|uptime|probe|scan|headless|lighthouse|pagespeed|gtmetrix|facebookexternalhit|embedly|whatsapp|telegram|curl|wget|python|java/|go-http|okhttp|libwww|httpclient|axios|node-fetch|php/~i';

    public static function enabled(): bool
    {
        return (bool) (function_exists('config') ? config('cms.stats.enabled', true) : true);
    }

    public static function isBot(?string $userAgent): bool
    {
        $ua = trim((string) $userAgent);

        return $ua === '' || preg_match(self::BOT, $ua) === 1;
    }

    /** Hanya GET oleh manusia dan bukan prefetch/prerender yang dihitung. */
    public static function countable(string $method, ?string $userAgent, ?string $purpose = null): bool
    {
        if (strtoupper($method) !== 'GET' || self::isBot($userAgent)) {
            return false;
        }

        return ! in_array(strtolower(trim((string) $purpose)), ['prefetch', 'prerender', 'preview'], true);
    }

    /** Hash harian satu arah (32 heks). Hari berbeda = hash berbeda untuk pengunjung yang sama. */
    public static function visitorHash(string $ip, string $userAgent, string $day, string $secret): string
    {
        return substr(hash_hmac('sha256', $ip . '|' . mb_substr($userAgent, 0, 300), $secret . '|' . $day), 0, 32);
    }

    /**
     * Dipanggil ⚡page-show / ⚡article-show setelah halaman dipastikan ada. Mencatat SETELAH respons terkirim.
     *
     * @param 'page'|'article' $kind
     */
    public static function track(string $kind, int $itemId, string $locale): void
    {
        try {
            if (! self::enabled()) {
                return;
            }
            $req = request();
            $args = [
                $kind, $itemId, $locale, (string) $req->ip(), (string) $req->userAgent(), $req->method(),
                (string) ($req->header('Sec-Purpose') ?: $req->header('Purpose') ?: $req->header('X-Purpose') ?: $req->header('X-Moz')),
            ];
            $run = static fn () => self::record(...$args);
            app()->terminating($run);
        } catch (\Throwable $e) {
            self::report($e);   // pencacah tidak pernah boleh menjatuhkan halaman
        }
    }

    /**
     * Mencatat satu kunjungan. Mengembalikan true bila dihitung. Tidak melempar galat.
     *
     * @param 'page'|'article' $kind
     */
    public static function record(
        string $kind,
        int $itemId,
        string $locale,
        string $ip,
        string $userAgent,
        string $method = 'GET',
        ?string $purpose = null,
        ?string $day = null,
        ?string $secret = null,
    ): bool {
        if (! in_array($kind, self::KINDS, true) || $itemId < 1 || ! self::countable($method, $userAgent, $purpose)) {
            return false;
        }
        $locale = substr((string) preg_replace('/[^a-z0-9-]/i', '', $locale), 0, 8);

        try {
            $day ??= Carbon::now()->toDateString();
            $secret ??= (string) (function_exists('config') ? config('app.key') : '');
            $hash = self::visitorHash($ip, $userAgent, $day, $secret);

            $isNew = DB::table('site_visitors')->insertOrIgnore(['day' => $day, 'hash' => $hash]) === 1;
            self::bump($day, self::SITE, 0, '', 1, $isNew ? 1 : 0);
            self::bump($day, $kind, $itemId, $locale, 1, 0);

            if (random_int(1, 50) === 1) {   // pembersihan hash lama tanpa cron (Hostinger berbagi)
                self::purge($day);
            }

            return true;
        } catch (\Throwable $e) {
            self::report($e);

            return false;
        }
    }

    /**
     * Rilis 81: kegagalan mencatat tidak lagi senyap. Satu baris peringatan di storage/logs per 5 menit (tabel belum ada, izin DB, sintaks upsert).
     * Tidak pernah melempar galat.
     */
    private static function report(\Throwable $e): void
    {
        try {
            if (! class_exists(\Illuminate\Support\Facades\Log::class) || ! function_exists('app')) {
                return;
            }
            $once = class_exists(\Illuminate\Support\Facades\Cache::class) ? \Illuminate\Support\Facades\Cache::add('visits:fail-logged', 1, 300) : true;
            if ($once) {
                \Illuminate\Support\Facades\Log::warning('Pencacah kunjungan gagal mencatat: ' . $e->getMessage());
            }
        } catch (\Throwable) {
            // diam
        }
    }

    /** Menghapus penanda pengunjung yang lebih tua dari 2 hari sebelum $today. Mengembalikan jumlah baris terhapus. */
    public static function purge(string $today): int
    {
        return (int) DB::table('site_visitors')->where('day', '<', Carbon::parse($today)->subDays(2)->toDateString())->delete();
    }

    /** Menambah views/uniques satu baris harian (membuatnya bila belum ada). Kolom diberi nama tabel supaya tidak ambigu di MySQL/MariaDB/SQLite. */
    private static function bump(string $day, string $kind, int $itemId, string $locale, int $views, int $uniques): void
    {
        DB::table('site_visits')->upsert(
            [['day' => $day, 'kind' => $kind, 'item_id' => $itemId, 'locale' => $locale, 'views' => $views, 'uniques' => $uniques]],
            ['day', 'kind', 'item_id', 'locale'],
            ['views' => DB::raw('site_visits.views + ' . (int) $views), 'uniques' => DB::raw('site_visits.uniques + ' . (int) $uniques)],
        );
    }
}
