<?php

namespace App\Http\Controllers;

use App\Content\Languages;
use App\Content\PublicLookup;
use App\Content\Sitemap;
use Illuminate\Http\Response;

/**
 * GET /sitemap.xml  (rute 'sitemap'; didaftarkan di routes/web.php SEBELUM '/{slug}').
 *
 * Isi: setiap halaman CMS online dan artikel terbit (satu alamat per slug per bahasa, dengan templat alamat bahasa itu) + jalur statis
 * dari config('cms.sitemap_static'). Hanya versi yang sudah diterjemahkan (slug dan judul terisi) yang masuk.
 * Semua logika ada di App\Content\Sitemap dan PublicLookup::sitemapEntries (diuji); ini hanya penyambung.
 *
 * Alamat dasar = APP_URL (harus skema://host saja, mis. https://ysbh.org). Bila gagal membaca basis data, atau hasilnya kosong, dibalas
 * 503, BUKAN peta kosong berstatus 200: mesin pencari bisa menafsirkan peta kosong sebagai "semua halaman sudah dihapus".
 */
final class SitemapController
{
    public function __invoke(): Response
    {
        try {
            $rows = PublicLookup::sitemapEntries(Languages::fromConfig()['locales']);
        } catch (\Throwable $e) {
            report($e);
            abort(503, 'Peta situs belum tersedia.');
        }

        $entries = Sitemap::entries($rows, (array) config('cms.sitemap_static', []), self::base(), config('cms.public.page'), config('cms.public.article'), config('cms.home_slug'), config('cms.public.home'));
        if ($entries === []) {
            report(new \RuntimeException('Peta situs kosong: periksa APP_URL, config cms.public, dan cms.sitemap_static.'));
            abort(503, 'Peta situs belum tersedia.');
        }

        return response(Sitemap::xml($entries), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=600',
        ]);
    }

    /** APP_URL bila berbentuk skema://host[:port] saja; selain itu alamat permintaan ini. */
    private static function base(): string
    {
        $configured = rtrim((string) config('app.url'), '/');

        return preg_match('#^https?://[A-Za-z0-9.-]+(?::\\d{1,5})?$#D', $configured) ? $configured : request()->getSchemeAndHttpHost();
    }
}
