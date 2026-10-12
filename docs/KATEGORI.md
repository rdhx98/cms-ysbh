# Artikel per kategori + tujuan menu "Artikel per kategori" (rilis 42)

Halaman publik yang memuat artikel **satu kategori**, dan tujuan menu untuk menautkannya.

| Bahasa | Alamat | Nama rute |
|---|---|---|
| EN (bawaan) | `/articles/category/{slug}` | `articles.category` |
| ID | `/id/artikel/kategori/{slug}` | `id.articles.category` |

`{slug}` = slug **kategori** (bukan artikel), **di bahasa alamat itu**. Rute memakai komponen `articles-index` yang sama dengan daftar semua artikel (kartu, penomoran `?page=2`, kepala SEO), dengan perbedaan:
- Judul (`<h1>`, `<title>`) = **nama kategori**; tautan "← Semua artikel" di atasnya. Pengantar halaman kepala (CMS) **tidak** tampil (itu pengantar semua artikel); **penutup** (mis. ajakan donasi) tetap.
- `hreflang`/pengalih bahasa menuju halaman kategori yang sama di bahasa lain (hanya bahasa yang punya slug kategori itu).
- Kategori tidak ada, atau belum punya slug di bahasa alamat → **404**. Slug bahasa lain → **301** ke slug bahasa alamat (satu kategori, satu alamat per bahasa; pembaca tidak dipindah ke bahasa lain). Kategori tanpa artikel → halaman tampil dengan "Belum ada artikel di kategori ini" (200).
- **Tidak masuk peta situs** (daftar yang isinya turunan indeks); `canonical` menunjuk dirinya per halaman.

## Dari mana slug kategori berasal (`categories.slug`)
Semua bentuk dibaca (`App\Content\CategoryLookup`):
| Kolom `slug` | Arti |
|---|---|
| peta bahasa `{"en":"malaria","id":"malaria"}` | satu slug per bahasa |
| teks polos `malaria` | satu slug untuk semua bahasa |
| kosong / kolom tidak ada | dibentuk dari **nama** di bahasa itu (`Imunisasi` → `imunisasi`, `&` → `dan`) |
Slug harus sah (huruf kecil, angka, tanda hubung). Kategori yang belum punya slug di suatu bahasa tidak punya halaman di bahasa itu.

## Menu: tujuan "Artikel per kategori"
Menu Builder (`opsional/menu-builder.blade.php`, salin menimpa berkas Anda) punya jenis tujuan baru **Artikel per kategori** dengan pilihan kategori dari daftar (bukan ketikan). Disimpan di kolom `url` sebagai **`/articles/category/{slug}`** (slug daftar artikel dan slug kategori bahasa bawaan; satu isian untuk semua bahasa, seperti tujuan lain) dan **diterjemahkan ke bahasa pembaca** oleh landing: pembaca ID mendapat `/id/artikel/kategori/{slug-id}`. Kategori yang belum diterjemahkan ke bahasa pembaca menuju versi bahasa lain yang ada (bukan menu mati). Pratinjau "Yang dilihat pembaca" menunjukkan alamat tiap bahasa; baris menu yang kategorinya hilang diberi tanda dan simpan menolak kategori yang tidak ada.

Menu "Artikel" dan "Kategori X" **sama-sama tampak aktif** di halaman kategori (menu aktif = jalur yang sama atau di bawahnya).

## Pasang
1. CMS dan landing: tambahkan ke `config/cms.php` → `public`:
   `'category' => ['en' => '/articles/category/{slug}', 'id' => '/id/artikel/kategori/{slug}']` (contoh: `contoh-kode/config-dua-aplikasi.php`; landing-app/config sudah memuatnya).
2. Landing: jalankan `php tools\sync-landing.php D:\Herd\landing-ysbh --apply` (berkas kit baru `CategoryLookup.php`, `PublicLookup`, `LinkResolver`, `NavLinks`), salin `landing-app/routes/web.php` (dua rute baru), `landing-app/app/Models/Navigation.php`, dan `landing-app/resources/views/components/⚡articles-index.blade.php`. `php artisan config:clear`, `view:clear`.
3. `php tools\check-landing.php D:\Herd\landing-ysbh`: memperingatkan bila `public.category` tidak ada atau tidak sama dengan rute.
4. CMS: salin `opsional/menu-builder.blade.php` (milik Anda; bila sudah Anda ubah, gabungkan tambahannya: `categoryOptions()`, pemeriksaan kategori di `save()`, penerjemah kategori di `preview()`, dan cabang `@elseif ($kind === 'category')`).

## Mengelola kategori (rilis 46)
Nama dan slug diisi per bahasa lewat panel `content.taxonomy`: lihat `KATEGORI-TAG.md`.

## Belum ada
- Kategori di peta situs; tautan dari label kategori di kartu artikel ke halaman kategori; saringan kategori pada blok **Artikel terbaru**; jenis tujuan `category` untuk **Tombol**. Mudah ditambahkan (`PublicLookup::latestArticles()` sudah menerima `$categoryId`).
