# Memasang berkas ke landing (cara paling mudah)

Ada **74 berkas** yang harus sampai ke proyek landing (dan 13 berkas prototipe lama yang dihapus, selalu dicadangkan dulu), tersebar di banyak folder. Jangan disalin satu per satu. Cukup **satu perintah**, yang awalnya hanya *memperlihatkan rencana* dan baru bekerja bila Anda menambahkan `--apply`.

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
Alat ini: (1) mencadangkan setiap berkas lama yang akan diganti atau dihapus, (2) menyalin semua berkas dan menghapus prototipe lama, (3) menjalankan pemeriksa otomatis dan menampilkan hasilnya. Bila ada satu masalah di tengah jalan, **tidak ada berkas yang berubah**.

## Langkah 4: perintah di folder landing
```powershell
cd C:\proyek\landing-ysbh
php artisan view:clear
npm run build
```

## Langkah 5: pengaturan yang harus Anda isi sendiri
1. `.env` landing: `APP_URL=` diisi alamat situs yang sebenarnya, misalnya `https://domain-anda.org` (tanpa `/` di belakang dan tanpa jalur). Dipakai peta situs **dan robots.txt**.
2. `config/app.php` landing **dan CMS**: tambahkan satu baris di dalam daftar, di dekat `'locale'`:
   ```php
   'supported_locales' => ['en', 'id'],
   ```
   Lalu di CMS dan landing: `php artisan config:clear`.
3. **Rilis 23**: buat halaman CMS beranda (slug `home`, online), perbarui baris menu yang memakai rute lama, dan sesuaikan `config/cms.php` CMS. Langkahnya di `LANDING-RAMPING.md`.

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
| `Berkas Anda masih memanggil rute yang DIHAPUS rilis 23` | Footer, menu, atau layout Anda memanggil `route('about')` (atau contact, programs, ...). Rute itu sudah tidak ada, jadi halaman yang memuatnya akan galat. Pesan menyebut berkas:baris dan penggantinya (`url('/about')`). Perbaiki lalu ulangi. `--abaikan-rute-hilang` melewati pemeriksaan ini (situs galat sampai tautannya diganti). |
| `H: jalur di landing-hapus.txt tidak sah` | Daftar hapus hanya menerima berkas di `resources/views/pages/` dan `public/robots.txt`. Jangan ubah `landing-hapus.txt`. |
| `Pemeriksa menemukan galat` | Baca baris `GALAT`. Pesannya menyebut berkas dan cara memperbaikinya. Kirimkan keluarannya kepada saya bila tidak yakin. |
| `'php' is not recognized` | PHP belum ada di PATH terminal. Buka terminal dari Laragon/XAMPP, atau pakai alamat lengkap php.exe. |

## Ringkasan: 77 berkas ke mana?
Semua alamat berikut relatif terhadap folder proyek landing.

**A. 49 berkas bersama dari kit** (dipakai CMS dan landing; daftar lengkap: `landing-files.txt`)
| Folder | Jumlah | Isi |
|---|---|---|
| `app/Content/Blocks/` | 15 | penyaring HTML dan blok, gaya blok |
| `app/Content/` | 11 | pencari halaman/artikel, peta situs, perakit seksi, slug, **bahasa (`Languages`) dan tautan menu per bahasa (`NavLinks`)** |
| `app/Content/Links/` | 1 | pembuat alamat tautan |
| `app/Editor/` dan `app/Editor/Blocks/` | 13 | definisi blok (tanpa tampilan editor) |
| `resources/views/components/blocks/render/` | 7 | tampilan publik blok modul (akordion, tombol, callout, unduhan, video, galeri, artikel terbaru) |
| `resources/views/components/content/` | 2 | mesin seksi dan isi artikel lama |

**B. 21 berkas khusus landing** (dari folder `landing-app` di kit)
| Berkas | Catatan |
|---|---|
| `routes/web.php` | **GANTI** (rilis 24): `/`, `/articles`, `/articles/{slug}`, `/id`, `/id/artikel`, `/id/artikel/{slug}`, `/id/{slug}`, `/{slug}` (paling akhir), peta situs, robots.txt |
| `resources/views/components/layouts/header.blade.php` | **GANTI**: menu memakai `href`/`isCurrent()`; pengalih bahasa memakai `lang-switch` |
| `resources/views/components/layouts/lang-switch.blade.php` | baru (rilis 24): tautan ke versi bahasa lain halaman ini |
| `resources/views/partials/head.blade.php` | **GANTI**: SEO, warna latar yang divalidasi, `hreflang` |
| `resources/css/app.css` | **GANTI**: warna `charcoal`, `[x-cloak]`, sumber kelas |
| `app/Models/Navigation.php` | **GANTI**: + `href` dan `isCurrent()`; tautan menu mengikuti bahasa pembaca |
| `config/cms.php` | baru atau **GANTI** (rilis 24: kunci per bahasa). Dicadangkan dulu; **samakan dengan config CMS** (`contoh-kode/config-dua-aplikasi.php`) |
| `app/Models/` (`Page`, `Post`, `Category`, `Snippet`, `Media`, `ReadOnlyModel`) | baru: model baca-saja |
| `app/Http/Controllers/SitemapController.php`, `RobotsController.php` | baru: peta situs dan robots.txt (alamat mengikuti `APP_URL`) |
| `resources/views/components/⚡page-show`, `⚡article-show`, `⚡articles-index` | baru: halaman CMS, artikel, daftar artikel |
| `resources/views/components/table-of-contents.blade.php` | baru: daftar isi (berkas Anda + perbaikan keamanan) |
| `resources/views/errors/404.blade.php`, `503.blade.php` | 404 dan "Situs sedang disiapkan", mengikuti bahasa alamat |

**H. 13 berkas yang DIHAPUS** (daftar: `landing-hapus.txt`; dicadangkan lebih dulu): 12 halaman Blade prototipe di `resources/views/pages/` dan `public/robots.txt`. Layout, header, footer, `x-cards.*`, aset, dan halaman lain yang tidak tercatat tidak disentuh.

**C. 7 berkas dari CMS Anda** (dari `resources\views\components\blocks\render\` di CMS)
`heading`, `paragraph`, `eyebrow`, `image`, `card-builder`, `step-group`, `multi-columns` (masing-masing `.blade.php`).

## Yang BELUM dicakup alat ini
- **CMS**: dipasang dengan alat terpisah, `tools\pasang-cms.php`; panduannya `docs/PASANG-CMS.md`. Pasang CMS dan landing dari kit yang sama agar berkas bersamanya identik.
- `.env` dan `config/app.php` (Langkah 5: `APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en`, `'supported_locales' => ['en', 'id']`), serta isi konten, menu (`navigations`), dan halaman CMS (beranda `home`/`beranda`, kepala daftar artikel `articles`/`artikel`).
- Rilis 24: **pasang CMS dan landing dari zip yang sama**, lalu samakan `config/cms.php` keduanya (kunci per bahasa, `docs/BAHASA.md`). Pemasang menimpa `config/cms.php` landing (dicadangkan); `config/cms.php` CMS tidak pernah disentuh.
