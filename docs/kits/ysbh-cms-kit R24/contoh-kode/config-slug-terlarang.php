<?php

/**
 * TAMBAHKAN kunci-kunci ini ke config/cms.php di aplikasi CMS (di samping 'design', 'lucide', 'fonts'; 'public' digabung dengan yang sudah
 * ada). Aplikasi landing sudah memilikinya di landing-app/config/cms.php. Nilainya harus SAMA di kedua aplikasi (kecuali public.base).
 * Rilis 24 (dua bahasa): hampir semua kunci kini PETA bahasa. Alasan dan aturan alamat: docs/BAHASA.md.
 *
 * Tanpa 'reserved_slugs', editor hanya menolak slug teknis bawaan (Slug::RESERVED) dan kode bahasa lain ("id") di bahasa bawaan.
 */
return [
    // Bahasa bawaan: alamatnya TANPA awalan ("/about-us"). Bahasa lain memakai awalan kodenya ("/id/tentang-kami").
    // Daftar bahasa tetap di config/app.php: 'supported_locales' => ['en', 'id'].
    'default_locale' => 'en',

    // Rute STATIS satu-segmen di routes/web.php landing, per bahasa. Landing tidak punya halaman statis, jadi KOSONG.
    // Entri lama (about, contact, programs, credibility, transparancies, impact) HARUS dihapus: halaman CMS ber-slug itu
    // sekarang dilayani landing, dan entri lama justru membuat editor menolak menyimpannya.
    'reserved_slugs' => [
        'en' => [],
        'id' => [],
    ],

    // Halaman CMS ber-slug ini adalah BERANDA bahasa itu: dilayani di "/" (en) atau "/id" (id). Biasanya SATU halaman dengan slug
    // "home" (en) dan "beranda" (id). Tautan internal ke halaman itu (mis. di pratinjau CMS) menuju jalur beranda.
    'home_slug' => [
        'en' => 'home',
        'id' => 'beranda',
    ],

    // Halaman CMS ber-slug ini menjadi kepala daftar artikel (/articles, /id/artikel); boleh dipakai walau alamatnya rute tetap.
    'articles_index_slug' => [
        'en' => 'articles',
        'id' => 'artikel',
    ],

    // Gabungkan dengan 'public' yang sudah ada (base dan cover tetap seperti di contoh-kode/config-dua-aplikasi.php).
    // Templat HARUS sama dengan rute di routes/web.php landing.
    'public' => [
        // 'base' => env('CMS_PUBLIC_URL', ''),   // CMS: 'https://ysbh.org'
        'page' => ['en' => '/{slug}', 'id' => '/id/{slug}'],
        'article' => ['en' => '/articles/{slug}', 'id' => '/id/artikel/{slug}'],
        'home' => ['en' => '/', 'id' => '/id'],
        'articles' => ['en' => '/articles', 'id' => '/id/artikel'],
    ],
];
