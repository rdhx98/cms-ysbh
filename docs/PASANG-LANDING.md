# Memasang berkas ke landing (cara paling mudah)

Ada **73 berkas** yang harus sampai ke proyek landing, tersebar di banyak folder. Jangan disalin satu per satu. Cukup **satu perintah**, yang awalnya hanya *memperlihatkan rencana* dan baru bekerja bila Anda menambahkan `--apply`.

## Yang Anda perlukan
Tiga folder (cukup tahu alamatnya):
| | Contoh | Isinya |
|---|---|---|
| **kit** | `C:\kit\ysbh-cms-kit` | zip kit yang sudah diekstrak (folder yang berisi `tools`, `tests`, `docs`) |
| **landing** | `C:\proyek\landing-ysbh` | proyek landing Anda (ada file `artisan`) |
| **cms** | `C:\proyek\cms-ysbh` | proyek CMS Anda (ada file `artisan`); hanya **dibaca**, tidak diubah |

## Langkah 1: buka terminal di folder kit
Di File Explorer, masuk ke folder kit, klik kolom alamat, ketik `powershell`, tekan Enter. Atau di PowerShell:
```powershell
cd C:\kit\ysbh-cms-kit
```

## Langkah 2: lihat rencananya dulu (aman, tidak mengubah apa pun)
Ganti dua alamat sesuai milik Anda. Bila alamat memuat spasi, biarkan tanda kutipnya.
```powershell
php tools\pasang-landing.php "C:\proyek\landing-ysbh" "C:\proyek\cms-ysbh"
```
Keluarannya daftar berkas **BARU** dan **GANTI**, dan jumlahnya. Yang perlu Anda baca adalah baris **GANTI**: itu berkas yang sudah ada di landing dan **berbeda** dari versi kit. Bila ada berkas GANTI yang Anda ubah sendiri dengan sengaja, catat dulu; versi lama Anda akan dicadangkan, tetapi Anda perlu tahu bahwa ia akan diganti.

Bila ada kalimat **"TIDAK ADA YANG DIUBAH. Ada masalah..."**, bacalah masalahnya (lihat bagian *Pesan yang mungkin muncul*), perbaiki, dan ulangi langkah ini.

## Langkah 3: pasang
Perintah yang sama, ditambah `--apply`:
```powershell
php tools\pasang-landing.php "C:\proyek\landing-ysbh" "C:\proyek\cms-ysbh" --apply
```
Alat ini: (1) mencadangkan setiap berkas lama yang akan diganti, (2) menyalin semua berkas, (3) menjalankan pemeriksa otomatis dan menampilkan hasilnya. Bila ada satu masalah di tengah jalan, **tidak ada berkas yang berubah**.

## Langkah 4: perintah di folder landing
```powershell
cd C:\proyek\landing-ysbh
php artisan view:clear
npm run build
```

## Langkah 5: dua pengaturan yang harus Anda isi sendiri
1. `.env` landing: `APP_URL=` diisi alamat situs yang sebenarnya, misalnya `https://ysbh.org` (tanpa `/` di belakang dan tanpa jalur). Dipakai peta situs.
2. `config/app.php` landing **dan CMS**: tambahkan satu baris di dalam daftar, di dekat `'locale'`:
   ```php
   'supported_locales' => ['en', 'id'],
   ```
   Lalu di CMS dan landing: `php artisan config:clear`.

Alat ini **tidak pernah** menyentuh `.env`, `vendor`, `node_modules`, atau `database`.

## Membatalkan
Berkas lama yang diganti ada di `landing\storage\pasang-cadangan\<tanggal-jam>\`, dengan susunan folder yang sama seperti di proyek. Untuk membatalkan, salin isinya kembali ke proyek landing. Berkas yang tadinya belum ada (BARU) tinggal dihapus.

## Pesan yang mungkin muncul
| Pesan | Artinya, dan yang harus dilakukan |
|---|---|
| `Folder landing bukan proyek Laravel (tidak ada artisan...)` | Alamat salah. Tunjuk folder utama proyek, yang berisi file `artisan`. |
| `... adalah komponen EDITOR, bukan tampilan publik` | Di CMS Anda ada dua berkas bernama sama. Yang dibutuhkan ada di `resources\views\components\blocks\render\`, bukan di `blocks\` atau `blocks\editor\`. |
| `tidak ada di CMS: resources/views/components/blocks/render/...` | Alamat CMS salah, atau berkas itu memang tidak ada di folder `render`. Katakan pada saya berkas mana. |
| `Folder landing tidak boleh sama dengan ... folder kit` | Anda menunjuk folder kit sebagai landing. Tunjuk proyek landing yang sebenarnya. |
| `Tujuan berada di luar folder landing` | Ada folder di dalam landing yang berupa pintasan (symlink) ke tempat lain. Pasang secara manual atau hapus pintasannya. |
| `Pemeriksa menemukan galat` | Baca baris `GALAT`. Pesannya menyebut berkas dan cara memperbaikinya. Kirimkan keluarannya kepada saya bila tidak yakin. |
| `'php' is not recognized` | PHP belum ada di PATH terminal. Buka terminal dari Laragon/XAMPP, atau pakai alamat lengkap php.exe. |

## Ringkasan: 73 berkas ke mana?
Semua alamat berikut relatif terhadap folder proyek landing.

**A. 47 berkas bersama dari kit** (dipakai CMS dan landing; daftar lengkap: `landing-files.txt`)
| Folder | Jumlah | Isi |
|---|---|---|
| `app/Content/Blocks/` | 15 | penyaring HTML dan blok, gaya blok |
| `app/Content/` | 9 | pencari halaman/artikel, peta situs, perakit seksi, slug |
| `app/Content/Links/` | 1 | pembuat alamat tautan |
| `app/Editor/` dan `app/Editor/Blocks/` | 13 | definisi blok (tanpa tampilan editor) |
| `resources/views/components/blocks/render/` | 7 | tampilan publik blok modul (akordion, tombol, callout, unduhan, video, galeri, artikel terbaru) |
| `resources/views/components/content/` | 2 | mesin seksi dan isi artikel lama |

**B. 19 berkas khusus landing** (dari folder `landing-app` di kit)
| Berkas | Catatan |
|---|---|
| `routes/web.php` | **GANTI**: rute lama utuh + rute halaman CMS, artikel, peta situs |
| `resources/views/components/layouts/header.blade.php` | **GANTI**: menu memakai `href`/`isCurrent()` |
| `resources/views/partials/head.blade.php` | **GANTI**: SEO dan warna latar yang divalidasi |
| `resources/css/app.css` | **GANTI**: warna `charcoal`, `[x-cloak]`, sumber kelas |
| `app/Models/Navigation.php` | **GANTI**: + `href` dan `isCurrent()` |
| `config/cms.php` | baru atau **GANTI**: salinan config CMS + 4 kunci landing |
| `app/Models/` (`Page`, `Post`, `Category`, `Snippet`, `Media`, `ReadOnlyModel`) | baru: model baca-saja |
| `app/Http/Controllers/SitemapController.php`, `public/robots.txt` | baru: peta situs |
| `resources/views/components/⚡page-show`, `⚡article-show`, `⚡articles-index` | baru: halaman CMS, artikel, daftar artikel |
| `resources/views/components/table-of-contents.blade.php` | baru: daftar isi (berkas Anda + perbaikan keamanan) |
| `resources/views/errors/404.blade.php` | baru: halaman 404 |

**C. 7 berkas dari CMS Anda** (dari `resources\views\components\blocks\render\` di CMS)
`heading`, `paragraph`, `eyebrow`, `image`, `card-builder`, `step-group`, `multi-columns` (masing-masing `.blade.php`).

## Yang BELUM dicakup alat ini
- **CMS**: dipasang dengan alat terpisah, `tools\pasang-cms.php`; panduannya `docs/PASANG-CMS.md`. Pasang CMS dan landing dari kit yang sama agar berkas bersamanya identik.
- `.env` dan `config/app.php` (Langkah 5), serta isi konten, menu (`navigations`), dan halaman CMS (mis. halaman berslug `artikel`).
