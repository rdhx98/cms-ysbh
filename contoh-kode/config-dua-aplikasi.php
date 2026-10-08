<?php
/**
 * Konfigurasi untuk CMS (kelola.ysbh.org) dan landing (ysbh.org) sebagai DUA aplikasi terpisah. Lihat docs/DUA-APLIKASI.md.
 * Salin bagian yang relevan ke berkas config Anda; ini bukan berkas yang dipasang otomatis.
 */

// ========== config/cms.php  (di KEDUA aplikasi; templat HARUS sama dengan rute di landing) ==========
return [
    // ... kunci cms yang sudah ada (lucide, design, fonts, ...) ...

    'public' => [
        // Alamat situs publik. CMS: 'https://ysbh.org'  |  landing: kosong (jalur relatif)
        'base' => env('CMS_PUBLIC_URL', ''),
        // Templat jalur. HARUS sama persis dengan rute page.show dan article.show di landing.
        'page' => '/{slug}',
        'article' => '/artikel/{slug}',
        // Nama berkas sampul artikel (kolom featured_image, mis. cover-abc.webp) -> alamat gambar. Sesuaikan dengan folder sampul artikel Anda.
        'cover' => '/storage/posts/{file}',
    ],
];

// ========== config/filesystems.php  (di KEDUA aplikasi; hanya bagian disk 'public') ==========
// Berkas yang diunggah lewat CMS disimpan di folder yang DILAYANI LANDING, supaya gambar tetap tampil walau CMS mati,
// dan halaman publik tidak memuat gambar dari subdomain admin.
//
//   'public' => [
//       'driver' => 'local',
//       'root' => env('MEDIA_ROOT', storage_path('app/public')),
//       'url' => env('MEDIA_URL', env('APP_URL') . '/storage'),
//       'visibility' => 'public',
//       'throw' => false,
//   ],
//
// .env CMS:      MEDIA_ROOT=/home/uXXXXXXXX/domains/ysbh.org/public_html/storage     (jalur: hPanel > Files > FTP Accounts)
//                MEDIA_URL=https://ysbh.org/storage
//                CMS_PUBLIC_URL=https://ysbh.org
// .env landing:  MEDIA_URL=https://ysbh.org/storage
//                (MEDIA_ROOT tidak perlu: landing hanya membaca; folder itu berada di public_html-nya sendiri, jadi tanpa storage:link)
