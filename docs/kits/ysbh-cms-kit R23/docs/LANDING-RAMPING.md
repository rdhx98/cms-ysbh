# Landing ramping (rilis 23): semua isi situs dari CMS

Sejak rilis 23, **landing tidak punya halaman statis lagi**. Beranda, tentang, kontak, program, kredibilitas, dampak, transparansi, dan halaman lain adalah **halaman CMS biasa** (model `Page`), diedit di editor halaman. Yang dipertahankan dari landing lama hanya **layout aplikasi** (layout `app`, header, footer, `head`, `app.css`).

## Rute yang tersisa
| Alamat | Isi |
|---|---|
| `/` | halaman CMS ber-slug `home` (`config('cms.home_slug')`); belum dibuat atau offline = halaman 503 "Situs sedang disiapkan" |
| `/{slug}` | halaman CMS lain; slug tidak ada atau offline = 404 |
| `/artikel`, `/artikel/{slug}` | daftar artikel (kepalanya dari halaman CMS ber-slug `artikel`) dan satu artikel |
| `/sitemap.xml`, `/robots.txt` | peta situs dan robots.txt; alamatnya mengikuti `APP_URL` (tidak ada domain yang tertulis di kode) |
| `/articles` | dialihkan 301 ke `/artikel` |
| `/home` | dialihkan 301 ke `/` (satu halaman, satu alamat) |

Nama rute yang masih ada: `home`, `articles`, `article.show`, `page.show`, `sitemap`, `robots`. **Rute lama dihapus:** `about`, `contact`, `programs`, `programs-malaria`, `programs-imunisasi`, `programs-kia`, `programs-tbc`, `programs-hiv`, `credibility`, `transparancies`, `impact`.

## Yang harus Anda lakukan (urut)
1. **Pasang kit di CMS dan landing dari zip yang sama** (`tools\pasang-cms.php`, `tools\pasang-landing.php`; panduan `PASANG-CMS.md`, `PASANG-LANDING.md`). Dua berkas bersama berubah di rilis ini: `LinkResolver.php` dan `Sitemap.php`.
2. **Pemasang landing akan menolak** (tanpa mengubah apa pun) bila berkas Anda yang lain, misalnya footer atau layout, masih memanggil `route('about')` dan sejenisnya. Pesannya menyebut berkas:baris dan penggantinya, mis. `route('about')` menjadi `url('/about')`. Perbaiki, lalu jalankan ulang.
3. **Config CMS** (`config/cms.php`): ubah `reserved_slugs` menjadi `[]` (hapus enam slug lama, bila tidak halaman CMS ber-slug itu tetap ditolak) dan tambahkan `"home_slug" => "home"`. Lalu `php artisan optimize:clear` dan `php artisan cms:audit-slugs` (harus bersih).
4. **Menu (tabel `navigations`)**: baris yang `route_name`-nya salah satu rute lama kini jatuh ke kolom `url`. Isi `url` dengan alamat halaman CMS-nya (mis. `/about`). Baris yang `url`-nya kosong tampil sebagai `#`.
5. **Buat halaman beranda** di editor: slug `home` (EN) dan, bila perlu, slug bahasa Indonesia (mis. `beranda`), status **online**. Sampai itu ada, `/` menampilkan "Situs sedang disiapkan" (503), bukan 404.
6. `.env`: landing `APP_URL=https://ysbh.org` (lokal `http://landing-ysbh.test`); CMS `CMS_PUBLIC_URL` sama dengan alamat landing. Peta situs dan robots.txt memakai `APP_URL`. Tabel lengkap lokal dan produksi: `DUA-APLIKASI.md`.

## Yang dilakukan pemasang landing
- Menghapus 12 halaman Blade prototipe (`resources/views/pages/*`) dan `public/robots.txt` (berkas statis menutupi rute `/robots.txt`), **setelah mencadangkannya** ke `storage/pasang-cadangan/<tanggal-jam>/`. Daftarnya `landing-hapus.txt`; hanya jalur di `resources/views/pages/` dan `public/robots.txt` yang diterima.
- Tidak menghapus layout, header, footer, komponen kartu (`x-cards.*`), aset, `.env`, atau halaman lain di `pages/` yang tidak tercatat.

## Bila ingin kembali
Salin isi folder cadangan ke proyek landing dan pasang `routes/web.php` lama dari cadangan yang sama.

## Belum termasuk
Pengalih bahasa (`/` untuk EN dan `/id` untuk ID, lihat `BAHASA.md`), isi halaman, dan tempat NPWP/rekening (isi menunggu keputusan yayasan; tidak ada nilai apa pun di kit ini).
