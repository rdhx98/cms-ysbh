# Blok Artikel Terbaru

Kartu artikel terbit terbaru: gambar sampul, kategori, tanggal, judul, dan ringkasan, dengan tautan opsional "lihat semua". Blok ini **dinamis**: ia tidak menyimpan artikel, melainkan membacanya dari basis data saat halaman dirender, jadi artikel baru muncul sendiri. Modul: `app/Editor/Blocks/LatestArticlesBlock.php`.

## Cara memakai
1. **+ Tambah blok → Artikel Terbaru**.
2. Tab **Blok**: judul bagian (opsional), **Jumlah artikel** (3, 6, 9, 12), **Kolom** (2, 3, 4), dan sakelar untuk gambar sampul, kategori, tanggal, ringkasan. "Lihat semua": isi teksnya lalu pilih tujuan (mis. halaman daftar artikel).
3. Di **kanvas** yang tampil adalah artikel terbit sungguhan dari basis data (atau keterangan bila belum ada).

## Dari mana datanya
| Bagian kartu | Sumber |
|---|---|
| judul, tautan | `title` dan `slug` artikel menurut bahasa halaman (bahasa lain bila kosong); tautan dari templat `cms.public.article` |
| kategori | nama kategori artikel (`categories.name`) menurut bahasa |
| tanggal | `published_at`, ditulis "8 Okt 2026" (id) / "Oct 8, 2026" (en), dengan `<time datetime>` |
| ringkasan | `meta_description` bila ada; bila tidak, paragraf pertama isi artikel (tanpa HTML, dipotong di batas kata, ±160 karakter). Artikel lama berisi HTML tetap bisa diringkas |
| sampul | kolom `featured_image` (lihat di bawah) |

## Gambar sampul
Kolom `featured_image` di proyek ini berisi **nama berkas** (`cover-abc.webp`); `default.webp` adalah nilai bawaan yang berarti "belum ada sampul", jadi kartu menampilkan latar hijau (bukan gambar rusak). Agar nama berkas menjadi alamat gambar, tambahkan templat di `config/cms.php`:
```php
'public' => [
    'base' => env('CMS_PUBLIC_URL', ''),
    'article' => ['en' => '/articles/{slug}', 'id' => '/id/artikel/{slug}'],   // rilis 24: per bahasa
    'cover' => '/storage/posts/{file}',   // sesuaikan dengan folder tempat sampul artikel disimpan
],
```
Tanpa `cover`, kartu tidak menampilkan gambar (aman, tidak menebak). Nilai berupa **angka murni** diperlakukan sebagai ID media (untuk pemilih sampul dari File Manager di kemudian hari). Nama berkas yang berisi `..`, garis miring ganda, atau karakter kontrol ditolak.

## Aturan
- **Rilis 24: per bahasa.** Blok di halaman EN hanya menampilkan artikel yang punya slug dan judul EN, dan tautannya menuju `/articles/{slug-en}`; di halaman ID menuju `/id/artikel/{slug-id}`. Tidak ada kartu yang menautkan ke 404.
- Hanya artikel berstatus **terbit** yang tampil, paling baru dulu. Artikel tanpa judul dilewati. Artikel tanpa slug tampil tanpa tautan.
- Di halaman artikel, **artikel yang sedang dibuka dikeluarkan** dari daftar (contoh `article-show` membagikan `currentArticleId`).
- Bila pembacaan basis data gagal, blok tidak dirender dan galatnya dilaporkan; halaman lain tetap tampil.
- Kartu seluruhnya bisa diklik (satu tautan pada judul yang diperluas), dengan teks "Baca selengkapnya" untuk pembaca layar.
- Tidak ada filter kategori: definisi blok tidak boleh mengakses basis data (dipanggil juga di landing), dan inspektur belum mendukung pilihan dinamis.

## Keamanan
Semua teks di-escape. Ringkasan berupa **teks** (entitas didekode setelah tag dilepas), bukan HTML, dan renderer mencetaknya dengan escape; pengujian memeriksa muatan `&lt;script&gt;`. Alamat artikel dan sampul dibentuk dari templat konfigurasi dengan pola ketat; data blok dibersihkan saat disimpan dan sekali lagi saat dirender.

## Berkas
`app/Editor/Blocks/LatestArticlesBlock.php`, `app/Content/Blocks/ArticleCards.php` (kartu, ringkasan, tanggal, sampul; murni), `app/Content/PublicLookup.php` (kueri; hanya membaca), `resources/views/components/blocks/render/latest-articles-builder.blade.php`, `tests/latest-articles-test.php`, `tests/article-cards-test.php`.
