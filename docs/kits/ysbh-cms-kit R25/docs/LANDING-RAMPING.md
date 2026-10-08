# Landing ramping (rilis 23, dua bahasa sejak rilis 24): semua isi situs dari CMS

Sejak rilis 23, **landing tidak punya halaman statis lagi**. Beranda, tentang, kontak, program, kredibilitas, dampak, transparansi, dan halaman lain adalah **halaman CMS biasa** (model `Page`), diedit di editor halaman. Yang dipertahankan dari landing lama hanya **layout aplikasi** (layout `app`, header, footer, `head`, `app.css`).

## Rute yang ada (rilis 24: dua bahasa, lihat `BAHASA.md`)
| Alamat EN (bawaan) | Alamat ID | Isi |
|---|---|---|
| `/` | `/id` | halaman CMS ber-slug beranda bahasa itu (`config('cms.home_slug')`: `home` / `beranda`); belum dibuat atau offline = 503 "Situs sedang disiapkan" |
| `/{slug}` | `/id/{slug}` | halaman CMS lain; slug tidak ada, offline, atau belum diterjemahkan = 404; slug bahasa lain = 301 ke padanannya |
| `/articles`, `/articles/{slug}` | `/id/artikel`, `/id/artikel/{slug}` | daftar artikel (kepalanya dari halaman CMS ber-slug `articles` / `artikel`) dan satu artikel |
| `/sitemap.xml`, `/robots.txt` | (sama) | peta situs (memuat kedua bahasa) dan robots.txt; alamatnya mengikuti `APP_URL` |
| `/home` | `/id/beranda` | dialihkan 301 ke `/` dan `/id` (satu halaman, satu alamat) |

Nama rute: `home`, `articles`, `article.show`, `page.show`, `id.home`, `id.articles`, `id.article.show`, `id.page.show`, `sitemap`, `robots`. **Rute lama dihapus sejak rilis 23:** `about`, `contact`, `programs`, `programs-malaria`, `programs-imunisasi`, `programs-kia`, `programs-tbc`, `programs-hiv`, `credibility`, `transparancies`, `impact`. Rilis 24 juga menghapus alamat `/artikel` tanpa awalan dan pengalihan `/articles` ke `/artikel` (kini `/articles` adalah daftar artikel EN).

## Yang harus Anda lakukan (urut)
1. **Pasang kit di CMS dan landing dari zip yang sama** (`tools\pasang-cms.php`, `tools\pasang-landing.php`; panduan `PASANG-CMS.md`, `PASANG-LANDING.md`). Rilis 24 mengubah banyak berkas bersama (bahasa, alamat, pencarian, peta situs); itu sebabnya kedua aplikasi dipasang dari zip yang sama.
2. **Pemasang landing akan menolak** (tanpa mengubah apa pun) bila berkas Anda yang lain, misalnya footer atau layout, masih memanggil `route('about')` dan sejenisnya. Pesannya menyebut berkas:baris dan penggantinya, mis. `route('about')` menjadi `url('/about')`. Perbaiki, lalu jalankan ulang.
3. **Config CMS** (`config/cms.php`), **per bahasa sejak rilis 24** (salin dari `contoh-kode/config-slug-terlarang.php`): `default_locale`, `reserved_slugs` (kosong per bahasa; hapus enam slug lama), `home_slug`, `articles_index_slug`, dan `public.*`. Lalu `php artisan optimize:clear`, `php artisan cms:audit-slugs` (harus bersih), dan `php artisan cms:audit-translations`.
4. **Menu (tabel `navigations`)**: baris yang `route_name`-nya salah satu rute lama kini jatuh ke kolom `url`. Isi `url` dengan alamat halaman CMS-nya (mis. `/about-us`; satu isian untuk dua bahasa, lihat `BAHASA.md`). Baris yang `url`-nya kosong tampil sebagai `#`.
5. **Buat halaman beranda** di editor: satu halaman dengan slug `home` (EN) dan `beranda` (ID), judul di kedua bahasa, status **online**. Sampai itu ada, `/` dan `/id` menampilkan "Situs sedang disiapkan" (503), bukan 404.
6. `.env`: landing `APP_URL=https://ysbh.org` (lokal `http://landing-ysbh.test`); CMS `CMS_PUBLIC_URL` sama dengan alamat landing. Peta situs dan robots.txt memakai `APP_URL`. Tabel lengkap lokal dan produksi: `DUA-APLIKASI.md`.

## Yang dilakukan pemasang landing
- Menghapus 12 halaman Blade prototipe (`resources/views/pages/*`) dan `public/robots.txt` (berkas statis menutupi rute `/robots.txt`), **setelah mencadangkannya** ke `storage/pasang-cadangan/<tanggal-jam>/`. Daftarnya `landing-hapus.txt`; hanya jalur di `resources/views/pages/` dan `public/robots.txt` yang diterima.
- Tidak menghapus layout, header, footer, komponen kartu (`x-cards.*`), aset, `.env`, atau halaman lain di `pages/` yang tidak tercatat.

## Bila ingin kembali
Salin isi folder cadangan ke proyek landing dan pasang `routes/web.php` lama dari cadangan yang sama.

## Belum termasuk
Isi halaman (termasuk terjemahannya). Kerangka halaman dan tempat NPWP/rekening kosong: `HALAMAN-SITUS.md` (rilis 25); nilainya menunggu keputusan yayasan, tidak ada nilai apa pun di kit ini.
