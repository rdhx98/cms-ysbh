# Memperbarui CMS dari rilis 12 (cara paling mudah)

CMS Anda ada di **rilis 12**. Sejak itu ada perubahan yang juga mengenai CMS (bukan hanya landing): penjaga slug terlarang, penyaring HTML, peta situs, perbaikan keamanan daftar isi, dan beberapa penyesuaian. Satu perintah memperbaruinya; bawaannya hanya **memperlihatkan rencana**.

## Yang Anda perlukan
Dua folder: **kit** (zip yang sudah diekstrak) dan **cms** (proyek CMS, yang berisi file `artisan`). Alat ini hanya mengubah folder `cms`.

## Langkah 1: buka terminal di folder kit
```powershell
cd C:\kit\ysbh-cms-kit
```

## Langkah 2: lihat rencananya dulu (aman, tidak mengubah apa pun)
```powershell
php tools\pasang-cms.php "C:\proyek\cms-ysbh"
```

**Yang seharusnya Anda lihat jika CMS Anda memang rilis 12 yang utuh** (perkiraan dari riwayat rilis saya):

| Status | Berkas | Keterangan |
|---|---|---|
| **BARU** (6) | `app/Console/Commands/AuditSlugs.php` | perintah `php artisan cms:audit-slugs` |
| | `app/Content/Rules/ReservedSlug.php`, `app/Content/SlugAudit.php` | penjaga dan audit slug terlarang |
| | `app/Content/Blocks/RichText.php`, `app/Content/Blocks/CoreBlocks.php` | penyaring isi publik |
| | `app/Content/Sitemap.php` | peta situs |
| **GANTI** (7) | `app/Content/PublicLookup.php` | + daftar artikel, kepala daftar, peta situs, penyaringan |
| | `app/Content/Blocks/BlockSanitizer.php` | + penyaring tujuh blok inti, normalisasi anchor |
| | `app/Content/ContentRules.php`, `app/Content/Slug.php` | + penjaga slug terlarang |
| | `resources/views/components/content/body.blade.php` | memakai penyaring publik |
| | `resources/views/components/blocks/render/latest-articles-builder.blade.php` | hanya komentar |
| | `resources/views/components/table-of-contents.blade.php` | **perbaikan keamanan** (anchor di dalam ekspresi Alpine) dan teks `[cite: 1]` yang tampil |
| **SAMA** | sekitar 90 berkas | tidak disentuh |

Jika rencana Anda menampilkan **jauh lebih banyak GANTI** dari tujuh, artinya salah satu: (a) pemasangan rilis 12 Anda belum utuh, atau (b) ada berkas kit yang Anda sunting sendiri. Keduanya aman (berkas lama dicadangkan), tetapi **periksa daftarnya** sebelum lanjut.

Kalimat **"TIDAK ADA YANG DIUBAH. Ada masalah..."** berarti ada yang harus dibereskan dulu; pesannya menyebut apa.

## Langkah 3: pasang
```powershell
php tools\pasang-cms.php "C:\proyek\cms-ysbh" --apply
```
Alat ini mencadangkan setiap berkas yang diganti atau dihapus, menyalin berkas, lalu menjalankan pemindai direktif Blade pada CMS (menangkap bug `@context` pada JSON-LD). Bila ada satu masalah di tengah jalan, **tidak ada berkas yang berubah**.

## Langkah 4: pengaturan yang HARUS Anda isi sendiri
Alat ini tidak pernah mengubah `config`, `routes`, atau `.env` Anda.

1. **`config/cms.php` di CMS**: sejak rilis 24 kunci-kuncinya **per bahasa** (contoh lengkap: `contoh-kode/config-slug-terlarang.php`; arti dan alasan: `BAHASA.md`):
   ```php
   "default_locale" => "en",
   "reserved_slugs" => ["en" => [], "id" => []],
   "home_slug" => ["en" => "home", "id" => "beranda"],
   "articles_index_slug" => ["en" => "articles", "id" => "artikel"],
   "public" => [
       "base" => env("CMS_PUBLIC_URL", ""),   // https://ysbh.org
       "page" => ["en" => "/{slug}", "id" => "/id/{slug}"],
       "article" => ["en" => "/articles/{slug}", "id" => "/id/artikel/{slug}"],
       "home" => ["en" => "/", "id" => "/id"],
       "articles" => ["en" => "/articles", "id" => "/id/artikel"],
       "cover" => "/storage/posts/{file}",
   ],
   ```
   Bentuk lama (teks atau daftar biasa) masih diterima dan berlaku untuk semua bahasa, tetapi tautan ke halaman berbahasa Indonesia baru benar bila templat per bahasa diisi. **`reserved_slugs` KOSONG** sejak rilis 23: bila CMS Anda masih memuat enam slug lama (`about`, `contact`, `programs`, `credibility`, `transparancies`, `impact`), **hapus**; entri itu membuat editor menolak menyimpannya. `home_slug` adalah slug halaman beranda tiap bahasa (dilayani di `/` dan `/id`). Lihat `LANDING-RAMPING.md`.
2. **`config/app.php` di CMS**: `'supported_locales' => ['en', 'id'],` di dekat `'locale'`. `.env`: `APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en`.

## Langkah 5: perintah di folder CMS
```powershell
cd C:\proyek\cms-ysbh
php artisan optimize:clear
npm run build
php artisan cms:audit-slugs
php artisan cms:audit-translations
```
`cms:audit-translations` (rilis 24, hanya membaca) mendaftar halaman dan artikel yang belum atau setengah diterjemahkan (`BAHASA.md`). `cms:audit-slugs` menampilkan halaman lama yang slug-nya bertabrakan dengan alamat tetap situs (halaman itu tersimpan dan tampak online, tetapi tidak akan pernah terbuka di situs). Bila ada, ganti slug-nya di editor. **Tidak ada migrasi baru sejak rilis 12**, jadi `php artisan migrate` tidak diperlukan.

## Yang akan terasa berbeda di CMS
- Menyimpan **halaman** dengan slug teknis (`storage`, `build`, `articles`, `robots`, ...) atau slug yang kelak Anda catat di `reserved_slugs` ditolak dengan pesan yang jelas. Sejak rilis 23 `about`, `contact`, `programs`, dan seterusnya **boleh** dipakai (daftar kosong). Artikel tidak terkena. Slug kepala daftar artikel (`articles` di EN, `artikel` di ID) diizinkan di bahasanya. Di bahasa EN, slug `id` ditolak karena `/id` adalah beranda bahasa Indonesia.
- **Pratinjau tersimpan** menampilkan isi persis seperti situs publik, yaitu sesudah disaring: skrip, `<iframe>`, penangan `on*` dihilangkan, dan sematan `<iframe>` pada artikel lama tidak tampil (gunakan blok Video). Data di basis data **tidak berubah**.
- Anchor blok dinormalkan saat disimpan: "Beban Kasus" menjadi `beban-kasus`; anchor yang sudah sah tidak berubah.

## Membatalkan
Berkas lama ada di `cms\storage\pasang-cadangan\<tanggal-jam>\` dengan susunan folder yang sama. Salin kembali ke proyek CMS untuk membatalkan; berkas yang tadinya BARU tinggal dihapus.

## Yang dilindungi (tidak pernah ditimpa atau dihapus)
Model Anda (`Page`, `Post`, `Category`, `User`, `Setting`, `Navigation`, dst.), `app/Providers`, `app/Http`, `routes`, `config`, `bootstrap`, `public`, `storage`, `lang`, `database/seeders`, `database/factories`, `.env`, `vendor`, `node_modules`. Berkas buatan Anda sendiri yang tidak ada di kit (mis. renderer `heading`, `paragraph`, `eyebrow`, `image`, `card-builder`, `step-group`, `multi-columns` di folder `render`) juga tidak disentuh.

## Pesan yang mungkin muncul
| Pesan | Artinya |
|---|---|
| `Folder CMS bukan proyek Laravel (tidak ada artisan...)` | Alamat salah. Tunjuk folder utama CMS. |
| `Folder CMS tidak boleh sama dengan, atau berada di dalam, folder kit` | Anda menunjuk folder kit. Tunjuk proyek CMS. |
| `Tujuan berada di luar folder CMS` atau `tautan simbolik` | Ada folder/berkas di CMS yang berupa pintasan (symlink) ke tempat lain; alat menolak agar tidak menulis keluar dari CMS. |
| `kit memuat berkas dengan jalur yang DILINDUNGI` | Kit memuat berkas yang jalurnya sama dengan milik Anda. Ini tidak terjadi pada kit saat ini; bila muncul, kirimkan keluarannya kepada saya. |
| `'php' is not recognized` | PHP belum ada di PATH terminal. Buka terminal dari Laragon/XAMPP. |

## Setelah CMS dan landing diperbarui
Pasang juga landing: `docs/PASANG-LANDING.md`. Keduanya memakai kit yang sama, jadi berkas bersama dijamin identik.
