<?php

/**
 * TAMBAHKAN dua kunci ini ke config/cms.php di aplikasi CMS (di samping 'design', 'lucide', 'fonts'). Aplikasi landing sudah
 * memilikinya di landing-app/config/cms.php. Daftar harus sama di kedua aplikasi.
 *
 * Tanpa 'reserved_slugs', editor hanya menolak slug teknis bawaan (Slug::RESERVED); rute statis situs (/about, /contact, ...) belum terjaga.
 */
return [
    // Rute STATIS satu-segmen di routes/web.php landing. Halaman CMS ber-slug ini tidak akan pernah terbuka, jadi editor menolaknya.
    'reserved_slugs' => ['about', 'contact', 'programs', 'credibility', 'transparancies', 'impact'],

    // Halaman CMS ber-slug ini menjadi kepala daftar artikel (/artikel); boleh dipakai walau alamatnya rute tetap.
    'articles_index_slug' => 'artikel',
];
