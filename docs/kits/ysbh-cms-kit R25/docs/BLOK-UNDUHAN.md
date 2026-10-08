# Blok Daftar Unduhan

Berkas dari File Manager yang bisa diunduh pengunjung, dengan judul, tahun, dan kategori. Dibuat untuk **Transparansi Laporan**. Ukuran dan jenis berkas (PDF, Word, Excel, …) **diambil otomatis** dari File Manager, jadi tidak perlu diketik. Blok ini adalah modul (`app/Editor/Blocks/DownloadsBlock.php`).

## Cara memakai
1. **+ Tambah blok → Daftar Unduhan** (juga di dalam kolom, bukan di dalam Grup Langkah).
2. Tab **Blok**: **+ Tambah berkas**, buka butirnya, isi **Judul** (per bahasa), **Tahun** (4 angka), **Kategori** (opsional, per bahasa), lalu **Jelajahi File Manager** dan pilih berkas. Nama berkas yang terpilih tampil di bawah tombol. Maksimal 60 butir; ↑ ↓ mengurutkan.
3. Di bawah daftar: **Tampilan** (Daftar / Kartu), **Kelompokkan** (Tidak / Tahun / Kategori), **Urutan** (Manual / Tahun terbaru / Judul A–Z), **Warna aksen**, dan dua sakelar (tampilkan tahun-jenis-ukuran; buka di tab baru).

## Aturan tampilan
| Situasi | Hasil |
|---|---|
| butir lengkap | judul, lencana jenis (PDF/Word/Excel/…), baris "tahun · jenis · ukuran", ikon unduh |
| **di situs**: butir tanpa judul, tanpa berkas terpilih, atau berkasnya sudah dihapus | **dilewati** (tidak ada tautan mati) |
| **di kanvas**: butir seperti itu | tampil **pudar bertepi putus-putus**, dengan penjelasan di title ("Berkas belum dipilih", "Berkas tidak ditemukan", "Judul kosong") |
| Kelompokkan: Tahun | tahun terbaru di atas; butir tanpa tahun di bawah ("Tanpa tahun") |
| Kelompokkan: Kategori | urutan kemunculan; tanpa kategori di bawah ("Lainnya") |
| Urutan: Judul A–Z | tanpa peduli huruf besar; angka wajar ("Laporan 2" sebelum "Laporan 10") |

Berkas yang dihapus dari File Manager (ke Sampah) otomatis hilang dari situs; tidak perlu mengubah halaman. Di File Manager, setiap berkas tercatat di **Digunakan Di** (otomatis, lewat `media_id`).

## Keamanan
- **Tautan unduhan selalu dibentuk dari `media_id`** lewat model Media. Alamat yang ada di data (yang dikirim browser) hanya dipakai untuk menampilkan nama berkas di inspektur, dan tidak pernah menjadi `href`.
- Judul dan kategori di-escape; kelas CSS dari daftar tetap; data dibersihkan saat disimpan, saat titipan pratinjau, dan sekali lagi saat dirender.
- Tautan dibuka di tab baru dengan `rel="noopener noreferrer"` (bisa dimatikan).

## Yang belum
- Pemilih berkas dikirim **tanpa filter jenis**. File Manager Anda hanya mengenal filter `image` dan `pdf`; bila tanpa filter ia hanya menampilkan gambar, ganti `''` menjadi `'pdf'` di `DownloadsBlock.php` (satu kata). Lihat `JENIS-BERKAS.md`.
- Belum ada pencarian/penyaringan oleh pengunjung di halaman (bisa ditambah bila daftarnya panjang).

## Bentuk data
```json
{ "type": "downloads-builder", "data": {
  "layout": "list | cards", "group_by": "none | year | category", "sort": "manual | year_desc | title_asc",
  "color": "foresty | coral | aurum | charcoal", "show_meta": true, "new_tab": true,
  "items": [ { "id": "itm_ab12cd34", "title": {"id": "…", "en": "…"}, "year": "2025", "category": {"id": "…", "en": "…"},
               "file": { "media_id": 7, "url": "…/lap-2025.pdf" } } ] } }
```

## Berkas
`app/Editor/Blocks/DownloadsBlock.php`, `app/Content/Blocks/FileInfo.php` (ukuran/jenis/lookup Media), `DownloadList.php` (urut/kelompok), `DownloadsStyle.php`, `resources/views/components/blocks/render/downloads-builder.blade.php`, `resources/views/components/editor/media.blade.php` (kini bisa tanpa filter dan menampilkan nama berkas), `tests/downloads-test.php`.
