# Bahasa situs (rilis 24): EN tanpa awalan, ID di `/id`

**Status: dikerjakan di rilis 24** (keputusan disetujui pengguna). Bahasa bawaan **EN** (target utama yayasan: donasi internasional); **ID** untuk pembaca lokal. Bahasa ditentukan **hanya oleh alamat**.

## Aturan
| | EN (bawaan) | ID |
|---|---|---|
| Beranda | `/` | `/id` |
| Halaman CMS | `/about-us` | `/id/tentang-kami` |
| Artikel | `/articles/{slug-en}` | `/id/artikel/{slug-id}` |
| Daftar artikel | `/articles` | `/id/artikel` |

- Slug **berbeda per bahasa** (kolom `slug` halaman dan artikel sudah per bahasa).
- Tidak ada `?lang`, tidak ada cookie bahasa, **tidak ada pengalihan otomatis** dari `Accept-Language` atau IP.
- Pengalih bahasa di header adalah **tautan biasa** ke versi bahasa lain halaman yang sedang dibuka (bukan tombol Alpine). Bila halaman itu belum punya versi bahasa lain, pengalih menuju beranda bahasa itu.
- `<head>` memuat `hreflang` `en`, `id`, dan `x-default` (= EN), hanya bila halaman punya dua bahasa atau lebih; peta situs memuat setiap versi bahasa sebagai alamatnya sendiri.

## Apa yang terjadi di setiap alamat
| Kasus | Hasil |
|---|---|
| `/about-us` ada dan berjudul EN | halaman tampil |
| `/tentang-kami` (slug ID) dibuka di alamat EN | **301** ke `/about-us` (satu halaman, satu alamat) |
| `/id/about-us` (slug EN) dibuka di alamat ID | **301** ke `/id/tentang-kami` |
| halaman belum diterjemahkan ke bahasa alamat itu (slug kosong di bahasa itu) | **404**, bukan salinan bahasa lain |
| `/en/...` | 404 (bahasa bawaan tidak punya awalan) |
| `/home`, `/id/beranda` | 301 ke `/` dan `/id` |
| beranda bahasa itu belum dibuat atau offline | 503 "situs sedang disiapkan" (bukan 404) |

## Pengaturan (`config/cms.php`, SAMA di CMS dan landing)
Contoh lengkap: `contoh-kode/config-slug-terlarang.php` dan `contoh-kode/config-dua-aplikasi.php`. Banyak kunci boleh berupa **teks** (berlaku untuk semua bahasa, bentuk lama) atau **peta bahasa**:

```php
'default_locale'      => 'en',
'reserved_slugs'      => ['en' => [], 'id' => []],      // atau daftar biasa, atau kunci '*' untuk semua bahasa
'home_slug'           => ['en' => 'home', 'id' => 'beranda'],
'articles_index_slug' => ['en' => 'articles', 'id' => 'artikel'],
'sitemap_static'      => ['/articles', '/id/artikel'],  // hanya landing
'public' => [
    'base'     => env('CMS_PUBLIC_URL', ''),            // CMS: https://ysbh.org | landing: kosong
    'page'     => ['en' => '/{slug}',          'id' => '/id/{slug}'],
    'article'  => ['en' => '/articles/{slug}', 'id' => '/id/artikel/{slug}'],
    'home'     => ['en' => '/',                'id' => '/id'],
    'articles' => ['en' => '/articles',        'id' => '/id/artikel'],
    'cover'    => '/storage/posts/{file}',
],
```
`config/app.php` (kedua aplikasi): `'supported_locales' => ['en', 'id']`. `.env`: `APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en`.

**Slug terlarang per bahasa.** `articles` tidak lagi terlarang bawaan (alamat `/articles` milik halaman kepala daftar). Di bahasa bawaan, kode bahasa lain (`id`) terlarang sebagai slug karena `/id` adalah beranda bahasa itu. Daftar `reserved_slugs` per bahasa; `cms:audit-slugs` memeriksa per bahasa.

## Halaman statis tidak ada lagi
Beranda, tentang, program, dan lainnya adalah **halaman CMS** (lihat `LANDING-RAMPING.md`). Terjemahan adalah isi halaman itu sendiri, bukan berkas `lang/`. Konsekuensinya: **blok yang belum diterjemahkan tampil kosong** di bahasa itu (renderer tidak menyalin teks bahasa lain). Karena EN adalah bahasa bawaan, setiap halaman harus punya isi EN sebelum diterbitkan.

## Audit terjemahan
```powershell
php artisan cms:audit-translations           # halaman online dan artikel terbit
php artisan cms:audit-translations --all     # termasuk draf dan offline
php artisan cms:audit-translations --locale=en
php artisan cms:audit-translations --strict  # kode keluar 1 juga untuk "belum"
```
Perintah ini **hanya membaca**. Tiga jenis temuan:
- **belum**: slug dan judul kosong di satu bahasa padahal bahasa lain terisi. Informasi: halaman tidak ada di situs bahasa itu (404, tidak masuk peta situs).
- **separuh**: slug terisi tetapi judul kosong, atau sebaliknya. Masalah: slug kosong = halaman 404 di bahasa itu; judul kosong = halaman tetap terbuka lewat slug-nya tetapi tanpa judul, dan tidak masuk hreflang, peta situs, atau daftar artikel.
- **isi**: slug dan judul terisi, tetapi ada teks blok yang kosong di bahasa itu padahal terisi di bahasa lain. Masalah: halaman tampil dengan lubang.

Kode keluar 1 bila ada **separuh** atau **isi**. `&nbsp;` dan paragraf kosong dihitung kosong; gambar tidak.

## Tautan menu dan tautan internal
- Kolom `url` tabel `navigations` **bukan per bahasa**. Satu isian (`/about-us` atau `/tentang-kami`) menuju halaman yang sama di **bahasa pembaca**: `/` menjadi beranda bahasa pembaca, slug halaman menjadi slug halaman itu di bahasa pembaca, `/articles` atau `/artikel` menjadi daftar artikel bahasa pembaca. Tautan eksternal, `mailto:`, `tel:`, `#anchor`, dan jalur bersegmen banyak tidak diubah. Halaman yang belum diterjemahkan menuju versi bahasa yang ada.
- Tautan `internal://page/{slug}` di teks kaya diselesaikan ke versi halaman itu **dalam bahasa halaman yang sedang tampil**: tautan di teks Inggris menuju versi Inggris.
- Daftar dan kartu artikel hanya memuat artikel yang sudah diterjemahkan ke bahasa itu (kartu tidak pernah menautkan ke 404).

## Menambah bahasa
1. Tambahkan kode ke `supported_locales` (kedua aplikasi).
2. Di `config/cms.php` tambahkan kunci bahasa itu pada `home_slug`, `articles_index_slug`, `public.page|article|home|articles`, dan (bila perlu) `reserved_slugs`.
3. Salin blok rute `/id` di `landing-app/routes/web.php` dengan kode baru (rute ditulis satu per satu supaya dapat diperiksa `tools/check-landing.php`), sebelum `/{slug}`.
4. Jalankan `php tools/check-landing.php <landing>`: ia memeriksa setiap bahasa.

## Belum dikerjakan
- Spanduk "Baca dalam Bahasa Indonesia?" (tanpa pengalihan otomatis): ditunda.
- Alamat lama tanpa awalan yang kini berbahasa Indonesia perlu dialihkan 301 **bila sudah pernah diindeks**. Situs belum terbit, jadi tidak ada yang dibuat.
- Terjemahan Inggris dari seluruh halaman menunggu penulis dan peninjau.
