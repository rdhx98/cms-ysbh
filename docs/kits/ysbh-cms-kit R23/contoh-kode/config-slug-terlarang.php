<?php

/**
 * TAMBAHKAN tiga kunci ini ke config/cms.php di aplikasi CMS (di samping 'design', 'lucide', 'fonts'). Aplikasi landing sudah
 * memilikinya di landing-app/config/cms.php. Nilainya harus sama di kedua aplikasi.
 *
 * Tanpa 'reserved_slugs', editor hanya menolak slug teknis bawaan (Slug::RESERVED).
 */
return [
    // Rute STATIS satu-segmen di routes/web.php landing. Sejak rilis 23 landing tidak punya halaman statis, jadi KOSONG.
    // Bila Anda masih punya entri lama (about, contact, programs, credibility, transparancies, impact), HAPUS: halaman CMS ber-slug itu
    // sekarang dilayani landing, dan entri lama justru membuat editor menolak menyimpannya.
    'reserved_slugs' => [],

    // Halaman CMS ber-slug ini adalah BERANDA, dilayani di "/". Tautan internal ke halaman itu (mis. di pratinjau CMS) menuju "/".
    'home_slug' => 'home',

    // Halaman CMS ber-slug ini menjadi kepala daftar artikel (/artikel); boleh dipakai walau alamatnya rute tetap.
    'articles_index_slug' => 'artikel',
];
