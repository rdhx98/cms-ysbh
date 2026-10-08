# CMS dan landing sebagai dua aplikasi terpisah

> Membangun sisi landing: lihat `LANDING-TAHAP-1.md` (berkas siap pakai ada di folder `landing-app/`).

Pilihan yayasan: **dua aplikasi Laravel yang berdiri sendiri**, supaya bila satu bermasalah yang lain tetap beroperasi.

| | **CMS** (`cms.ysbh.org`) | **Landing** (`ysbh.org`) |
|---|---|---|
| Fungsi | admin: menulis dan mengubah konten | situs publik: menampilkan konten |
| Rute publik `page.show` / `article.show` | **tidak punya** | **milik landing** |
| Rute `article.edit`, builder, pratinjau, File Manager | milik CMS | tidak punya |
| Menulis ke basis data / berkas | ya | tidak (hanya membaca) |

**Jadi `article.show` ada di landing.** CMS tidak membutuhkannya; CMS hanya perlu *mengetahui pola alamatnya* untuk membuat tautan di pratinjau (lihat "Kontrak alamat").

## Apa yang dibagi, dan apa yang terjadi saat ada yang mati
Kedua aplikasi memakai **satu basis data** dan **satu folder berkas unggahan**; kode render disalin ke landing.

| Kejadian | Landing | CMS |
|---|---|---|
| CMS rusak / diperbarui / dimatikan | **normal** (membaca basis data dan berkas) | tidak tersedia |
| Landing rusak / diperbarui | tidak tersedia | **normal**; pratinjau tersimpan tetap jalan (CMS merender sendiri); tautan di dalamnya menuju landing |
| Basis data mati | mati | mati (satu titik kegagalan bersama) |
| Akun hosting mati / kehabisan sumber daya | mati | mati (satu akun Hostinger berbagi batas proses) |

Pemisahan penuh dari kegagalan hosting dan basis data memerlukan akun/basis data terpisah dengan sinkronisasi data; itu di luar rencana ini. Yang dijamin: **kerusakan atau pembaruan kode CMS tidak menjatuhkan situs publik**, dan **landing tidak memuat kode admin** (diuji).

## Kontrak alamat (harus sama di kedua aplikasi)
Konten menyimpan tautan sebagai **ID** (`{"kind":"article","ref":9}`); alamatnya dibentuk saat dirender oleh `LinkResolver`. Agar di CMS dan di landing menghasilkan alamat landing yang sama, kedua aplikasi memakai **templat jalur yang sama** di `config('cms.public')`:

```php
'public' => [
    'base'    => env('CMS_PUBLIC_URL', ''),   // CMS: https://ysbh.org  |  landing: kosong
    'page'    => '/{slug}',
    'article' => '/artikel/{slug}',
    'cover'   => '/storage/posts/{file}',   // nama berkas sampul artikel -> alamat gambar (blok Artikel Terbaru)
],
```
- **CMS**: `CMS_PUBLIC_URL=https://ysbh.org` → tautan menjadi `https://ysbh.org/artikel/imunisasi-dasar` (absolut, menuju landing).
- **Landing**: `base` kosong → `/artikel/imunisasi-dasar`. Rute `page.show` dan `article.show` di landing harus berjalan di jalur yang sama (contoh dan uji kontrak: `landing-app/routes/web.php`).
- Bila `cms.public.*` tidak diatur, `LinkResolver` memakai rute bernama `page.show`/`article.show` **bila ada di aplikasi itu**, dan bila tidak ada, tautan tidak dirender. Tidak ada lagi galat "route not found" di CMS (bug di rilis ≤ 10).
- Perubahan pola alamat = ubah templat di KEDUA aplikasi **dan** rute di landing, bersamaan.

## Alamat: lokal (Herd) dan produksi (Hostinger)
| | Lokal | Produksi |
|---|---|---|
| Landing | `http://landing-ysbh.test` | `https://ysbh.org` |
| CMS | `http://cms-ysbh.test` | `https://cms.ysbh.org` |

`.env` per aplikasi:
| | Lokal | Produksi |
|---|---|---|
| Landing `APP_URL` | `http://landing-ysbh.test` | `https://ysbh.org` |
| CMS `APP_URL` | `http://cms-ysbh.test` | `https://cms.ysbh.org` |
| CMS `CMS_PUBLIC_URL` | `http://landing-ysbh.test` | `https://ysbh.org` |
| Landing `CMS_PUBLIC_URL` | kosong | kosong |
| `MEDIA_URL` (kedua aplikasi) | `http://landing-ysbh.test/storage` | `https://ysbh.org/storage` |
| CMS `MEDIA_ROOT` | folder `public/storage` milik landing (jalur lengkapnya sesuai komputer Anda; harus folder biasa, bukan hasil `storage:link`) | `/home/uXXXXXXXX/domains/ysbh.org/public_html/storage` |

`APP_URL` landing dipakai peta situs dan `robots.txt`, jadi di produksi harus `https://ysbh.org` persis (tanpa `/` di belakang dan tanpa jalur). Sebelum rilis 23 `robots.txt` menulis domain tetap; sekarang mengikuti `APP_URL` di lokal maupun produksi.
Bila lokal Anda lebih mudah memakai `MEDIA_URL=http://cms-ysbh.test/storage` di kedua aplikasi (cara C di bawah), itu cukup untuk pengembangan; produksi tetap memakai cara yang disarankan.

## Gambar dan berkas, secara rinci
**Cara kerjanya.** Setiap unggahan punya satu baris di tabel `media` (kolom `disk` dan `path`, mis. `public` dan `galeri/a.jpg`). Alamat gambar dibentuk `Storage::disk('public')->url($path)`: **`url` disk + path**. Kedua aplikasi membaca baris yang sama, tetapi alamatnya ditentukan oleh konfigurasi disk di masing-masing; hasilnya sama hanya bila `MEDIA_URL` sama. Ada dua hal yang terpisah: **di mana berkas ditulis** (`root`) dan **alamat publiknya** (`url`).

**Yang disarankan:** CMS menulis ke folder yang **dilayani landing**.
| | CMS (`.env`) | Landing (`.env`) |
|---|---|---|
| `MEDIA_ROOT` | `/home/u12345678/domains/ysbh.org/public_html/storage` | tidak perlu |
| `MEDIA_URL` | `https://ysbh.org/storage` | `https://ysbh.org/storage` |

Jalur `…/domains/<domain>/public_html` adalah format yang disebut panduan Hostinger; jalur persis akun Anda ada di hPanel → Files → FTP Accounts. Hasilnya: peramban meminta `https://ysbh.org/storage/galeri/a.jpg`, dan web server landing menyajikannya **langsung dari folder itu, tanpa PHP**. Karena itu gambar tetap tampil walau CMS mati, tidak perlu `storage:link` (Hostinger menonaktifkan `symlink()`), dan halaman publik tidak menampilkan nama host admin.

**Langkah (sekali):**
1. File Manager (domain utama) → buat folder `storage` di dalam folder yang dilayani landing.
2. Atur `.env` seperti tabel, dan disk `public` di `config/filesystems.php` memakai `MEDIA_ROOT`/`MEDIA_URL` (`contoh-kode/config-dua-aplikasi.php`). `php artisan config:clear`.
3. Salin unggahan lama dari `storage/app/public` CMS ke folder baru dengan struktur yang sama. Baris `media.path` **tidak perlu diubah**.
4. **Uji:** unggah satu gambar lewat File Manager CMS, lalu buka `https://ysbh.org/storage/<path>`.
5. **Keamanan:** folder ini bisa dibuka publik. Letakkan `.htaccess` di dalamnya agar berkas PHP tidak pernah dijalankan:
   ```apache
   <FilesMatch "\.(php|phtml|phar|php[0-9])$">
       Require all denied
   </FilesMatch>
   ```
   (Periksa bahwa aturan ini bekerja di akun Anda: simpan `x.php` uji di folder itu dan pastikan membukanya ditolak, lalu hapus.)

**Bila unggahan gagal** (pesan "failed to open stream", "Permission denied", atau "open_basedir restriction in effect"): PHP CMS tidak diizinkan menulis ke folder situs lain. Saya tidak menemukan keterangan resmi Hostinger tentang `open_basedir` untuk kasus ini; umumnya dibatasi ke folder akun, sehingga situs-situs dalam **satu akun** saling terjangkau, tetapi itu harus diuji (langkah 4). Alternatifnya:
- **B**: biarkan `MEDIA_ROOT` di folder CMS (`storage/app/public`), lalu di landing buat tautan simbolik lewat SSH: `ln -s <folder-CMS>/storage/app/public <folder-landing>/storage`. Fungsi PHP `symlink()` dinonaktifkan, tetapi `ln -s` di SSH dilaporkan berhasil oleh pengguna; belum saya verifikasi untuk akun Anda, termasuk apakah web server mau mengikuti tautan lintas situs. Gambar tetap tampil walau aplikasi CMS mati, karena berkasnya di disk.
- **C** (terakhir): `MEDIA_URL=https://cms.ysbh.org/storage` di kedua aplikasi. Berfungsi, tetapi gambar halaman publik dimuat dari subdomain admin: ikut mati bila subdomain itu dimatikan atau diproteksi, dan nama host admin terlihat di halaman publik.

## Memeriksa di hPanel (hasil verifikasi dan koreksi)
**1. Subdomain: pakai "situs mandiri", bukan "folder kustom".** Artikel resmi Hostinger (diperbarui September 2026) menyebut dua cara:
- **Cara 1 (disarankan): subdomain sebagai situs mandiri** (hPanel → Websites → tambah website → isi `cms.ysbh.org`). Hasilnya situs sendiri dengan pengaturan sendiri: inilah pemisahan yang Anda inginkan (dua situs dalam satu akun).
- **Cara 2: subdomain sebagai bagian situs yang ada** (subfolder di dalam folder situs utama; di sinilah folder bawaan bisa diubah). **Jangan** dipakai: CMS akan hidup di dalam folder landing, sehingga tidak terpisah.

*Koreksi atas saran saya sebelumnya:* saya menyebut opsi "folder kustom" untuk mengarahkan subdomain ke folder `public`. Opsi itu hanya ada pada Cara 2, dan Hostinger juga menyatakan folder utama (`public_html`) **tidak bisa diubah** pada paket web hosting. Maka **kedua aplikasi** (CMS dan landing) harus mengatasi `public_html` versus `public` Laravel dengan salah satu:
- **Salin**: isi `public/` ke `public_html`, aplikasi diletakkan di luar `public_html`, dan jalur di `index.php` disesuaikan. Sederhana, tetapi diulang tiap deploy yang mengubah `public/`.
- **Tautan simbolik (SSH)**: hapus `public_html`, lalu `ln -s public public_html` di folder situs. Dilaporkan berhasil oleh dua sumber komunitas (bukan dokumentasi resmi); uji dulu, dan bila berfungsi ini lebih bersih karena `public/` tidak disalin.

**2. Pengguna basis data baca-saja: bisa, dengan satu hal yang harus Anda periksa.** Terverifikasi (panduan resmi, hPanel): Websites → Manage → Databases → Management → daftar "MySQL Databases and Users" → titik tiga → **Change Permissions** → hilangkan centang selain `SELECT` → Update. Bawaannya semua hak aktif, dan Hostinger sendiri menyebut contoh "membuat basis data baca-saja".
- **Yang belum pasti**: apakah hPanel mengizinkan **pengguna kedua** pada basis data yang sama. Bila tidak, **jangan** mengubah izin pengguna yang dipakai CMS: CMS ikut menjadi baca-saja dan rusak. Landing tetap aman karena model-modelnya menolak menulis (`landing-app/app/Models/*.php`).
- Bila pengguna baca-saja dipakai, `.env` landing harus bebas dari basis data untuk sesi dan cache: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`. Dengan `database`, menulis ke tabel sesi/cache akan ditolak. Jangan menjalankan `php artisan migrate` di landing (tabel milik CMS).

## Model, Snippet, dan rute di landing
Landing menyediakan sendiri model berikut. Model **baca-saja** yang sudah diuji terhadap kode kit ada di `landing-app/app/Models/` (satu berkas per model; pasang apa adanya).

| Model | Dipakai untuk | Catatan |
|---|---|---|
| `Page` | halaman publik dari slug | `status` `online`; isi dibaca **mentah** |
| `Post` | artikel publik, Artikel Terbaru | `status` `published`; kolom `published_at`, `featured_image`, `category_id` |
| `Category` | nama kategori di kartu artikel | |
| `Snippet` | snippet penutup dan sisipan | scope `online()` dan `closing()` |
| `Media` | alamat gambar dan berkas | **wajib `SoftDeletes`**: tanpa itu berkas yang sudah dihapus di File Manager tetap tampil |

**Snippet.** Ada dua pemakaian: (a) **snippet penutup**, yaitu snippet bertanda `is_closing` yang otomatis tampil di akhir halaman (bisa diganti per halaman lewat pengaturan "penutup"), dan (b) blok `snippet` yang menyisipkan satu snippet di tempatnya. *Temuan:* di rilis ≤ 11 **tidak ada satu pun kode render publik yang memakai snippet**, jadi snippet penutup tidak akan tampil di landing. Rilis 12 menambahkan `PublicLookup::document()` yang merakit isi halaman + snippet sisipan + snippet penutup (aturan `ClosingPolicy`) dan membersihkan datanya; pratinjau tersimpan CMS memakai perakit yang sama, jadi sama dengan yang dilihat pengunjung. Batasan: hanya blok `snippet` di tingkat atas yang diperluas; satu snippet tampil sekali per halaman; snippet yang offline diabaikan. Perubahan snippet langsung berlaku di semua halaman (dibaca saat render); bila landing menyimpan cache halaman penuh, beri masa berlaku pendek.

**Rute dan komponen halaman.** Berkas siap pasang ada di `landing-app/` (lihat `LANDING-TAHAP-1.md`): rute di `landing-app/routes/web.php` dan komponen Livewire `⚡page-show`, `⚡article-show`, `⚡articles-index` di `landing-app/resources/views/components/`. Semuanya memakai `PublicLookup`: slug JSON dicari di bahasa aktif lalu bahasa lain (dan bahasa tempat cocok menjadi bahasa halaman), slug tak sah ditolak sebelum menyentuh basis data, dan isi dirakit bersama snippet. Rute `/{slug}` menangkap SEMUA jalur satu segmen, jadi harus **paling akhir**, dan rute statis satu-segmen harus dicatat di `config('cms.reserved_slugs')` (lihat `LANDING-TAHAP-1.md`).

Uji kontrak yang menjaga templat alamat dan rute tidak pernah berbeda (taruh di landing, mis. `tests/Feature/UrlContractTest.php`):
```php
expect(route('article.show', 'contoh', absolute: false))->toBe(str_replace('{slug}', 'contoh', config('cms.public.article')));
expect(route('page.show', 'contoh', absolute: false))->toBe(str_replace('{slug}', 'contoh', config('cms.public.page')));
```

## Kode render untuk landing
Renderer publik (mesin seksi, renderer blok, pencarian dan perakitan di `PublicLookup`, serta kelas pendukungnya) ada di kit dan harus ada **juga di landing**. Daftarnya otomatis dan terbukti: `landing-files.txt` (**44 berkas**, tanpa kode admin; seluruh pengujian render publik lulus hanya dengan berkas itu).

Setiap rilis kit (dijalankan dari folder zip yang diekstrak):
```powershell
php tools\sync-landing.php C:\jalur\landing            # PERIKSA: berkas baru/berbeda (tidak mengubah apa pun)
php tools\sync-landing.php C:\jalur\landing --apply    # salin yang baru/berbeda
```
lalu di landing `php artisan view:clear` dan `php tests\blade-scan.php C:\jalur\landing`. Alat ini menolak folder yang bukan proyek Laravel, menolak jalur tidak aman, dan tidak pernah menyentuh berkas landing di luar daftar. Daftar kebutuhan landing (model, komponen, konfigurasi) dicetak oleh `php tools\landing-files.php`.

**Yang harus disediakan landing sendiri** (bukan bagian kit): lima model (bagian di atas), komponen `<x-table-of-contents>`, renderer blok milik Anda (heading, paragraf, gambar, eyebrow, kartu, langkah, kolom, ...), konfigurasi `cms.lucide`, `app.supported_locales`, dan `cms.public` (`base`, `page`, `article`, `cover`), layout yang memuat Alpine (Livewire sudah memuatnya; Video, Galeri, dan FAQ memakainya), dan rute.

## Pemasangan di Hostinger
- **CMS di subdomain**: buat sebagai **situs mandiri** (lihat "Memeriksa di hPanel"). DNS otomatis bila nameserver domain memakai Hostinger.
- **Landing di domain utama** dan **CMS**: `public_html` tidak bisa diganti, jadi salin isi `public/` atau buat tautan simbolik lewat SSH (kedua cara di atas).
- `.env` terpisah per aplikasi (`APP_KEY` berbeda, `APP_DEBUG=false`); `APP_URL` masing-masing. Jangan atur `SESSION_DOMAIN` agar cookie sesi admin tetap milik host admin saja.
- CMS: tambahkan `X-Robots-Tag: noindex` dan `robots.txt` `Disallow: /` agar admin dan pratinjau tidak terindeks.
- `npm run build` di komputer sendiri, unggah `public/build`. Composer dan artisan lewat SSH (paket Premium dan Business menyediakan SSH).
- Landing: simpan hasil render halaman publik di cache (`Cache::remember` dengan masa berlaku pendek, karena snippet dan artikel terbaru dibaca saat render) dan pakai cache berkas, bukan basis data.

## Yang tidak berubah
Blok, pembersih, inspektur, dan kanvas CMS tidak berubah. Semua blok baru tetap "dua berkas baru"; setelah rilis, jalankan sinkron ke landing.
