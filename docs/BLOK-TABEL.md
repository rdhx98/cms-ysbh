# Blok Tabel

Tabel data (jadwal, daftar mitra, rincian dana, tarif, ...) dengan judul kolom, baris, dan sel per bahasa. Blok ini adalah modul (`app/Editor/Blocks/TableBlock.php`), ditambahkan seperti blok lain.

## Cara memakai
1. **+ Tambah blok → Tabel** (tingkat atas, juga di dalam kolom). Blok baru berisi 3 kolom × 2 baris kosong.
2. Tab **Blok**. Bagian paling atas adalah **kisi isi**:
   - **ID / EN**: kisi menampilkan SATU bahasa pada satu waktu. Isi bahasa lain dengan menekan tombolnya (mengikuti pilihan bahasa di bilah atas bila hanya satu bahasa).
   - Baris atas kisi = **judul kolom**. Di bawah tiap judul: geser kiri/kanan, **rata teks kolom** (klik untuk berganti kiri → tengah → kanan), hapus kolom. Tombol **+** di ujung kanan menambah kolom.
   - Kiri tiap baris: nomor, naik/turun, hapus. **+ Baris** menambah baris di bawah.
   - Kisi digulir mendatar bila kolomnya banyak.
3. **Judul tabel** dan **Catatan/sumber** (opsional, per bahasa) tampil di atas dan di bawah tabel.
4. Pilih tampilan: baris judul, kolom pertama sebagai judul baris, zebra, garis, kerapatan, warna baris judul, jarak bawah.

## Tempel dari Excel / Google Sheets
- **Ke sebuah sel:** salin beberapa sel di spreadsheet, klik sebuah sel di kisi, tempel (Ctrl+V). Isinya mengisi mulai dari sel itu; kolom dan baris bertambah bila perlu (batas 12 × 100; bila terpotong ada catatan kuning).
- **Ganti seluruh tabel:** **Tempel dari spreadsheet** → tempel → centang "Baris pertama = judul kolom" bila perlu → **Ganti isi tabel** (meminta konfirmasi bila tabel sudah berisi). Hanya bahasa yang sedang dipilih yang terisi; isi bahasa lain dikosongkan.
- Sel berkutip dengan baris baru di dalamnya (hasil salinan Sheets) terbaca sebagai satu sel.

## Menulis isi sel (teks biasa)
Tidak ada HTML. Apa pun yang diketik tampil apa adanya ("usia <5 tahun" aman).
| Tulis | Tampil |
|---|---|
| baris baru (Enter) | baris baru di dalam sel |
| `**Total**` | **Total** (tebal; berguna untuk baris jumlah) |
| `https://ysbh.org/program` | tautan (hanya http/https; tanda baca di ujung tidak ikut) |

## Pilihan
| | Pilihan |
|---|---|
| **Baris judul kolom** | tampil/sembunyi. Bila semua judul kolom kosong, baris judul tidak dicetak |
| **Kolom pertama sebagai judul baris** | sel kolom pertama tebal dan ditandai `scope="row"` untuk pembaca layar |
| **Zebra** | baris genap berlatar abu muda |
| **Garis** | Antar baris (garis tipis), Kotak (garis di tiap sel), Tanpa (tanpa bingkai) |
| **Kerapatan** | Rapat, Normal, Lega |
| **Warna baris judul** | Foresty, Coral, Aurum, Charcoal, Abu muda |

## Aturan tampilan
| Situasi | Situs | Kanvas |
|---|---|---|
| tabel tanpa teks sama sekali (hanya blok baru) | tidak dirender | "Tabel masih kosong" (bisa dipilih) |
| baris kosong di SEMUA bahasa | **tidak** dirender | tampil (agar baris baru terlihat) |
| sel/judul kosong di bahasa halaman | jatuh ke ID, lalu EN | sama |
| tabel lebih lebar dari layar | digulir mendatar di dalam bingkainya + petunjuk "Geser ke samping" (hanya bila memang meluap) | sama |

Lebar minimum tabel = 6 rem per kolom, jadi 4 kolom di HP menggulir sedikit, bukan menciut sampai tak terbaca. Bingkai bisa difokus dengan papan tik (Tab) dan diberi nama (judul tabel, atau "Tabel") untuk pembaca layar; `<th scope="col">` untuk judul kolom.

## Batas
12 kolom, 100 baris, 600 karakter per sel, 120 per judul kolom. Pembersih memotong yang berlebih; kolom dan sel tetap sejajar (sel berlebih dibuang, sel yang kurang diisi kosong).

## Keamanan
- Semua teks di-escape; hanya `<br>`, `<strong>`, dan `<a href="https://…" rel="noopener noreferrer" target="_blank">` yang ditambahkan, dari potongan yang di-escape SENDIRI-SENDIRI (`**` tidak bisa menyisip ke dalam alamat).
- Kelas CSS dari daftar tetap (`TableStyle`); nilai lain jatuh ke bawaan. Data dibersihkan saat disimpan, pada pratinjau, dan sekali lagi saat dirender.

## Yang sengaja tidak ada (bisa ditambah bila perlu)
Gabung sel (rowspan/colspan), tinggi baris tetap, baris judul yang menempel saat gulir vertikal, urut/cari oleh pengunjung, impor CSV dengan pemisah koma (Excel/Sheets menyalin dengan tab, itu yang didukung), dan **mengubah tabel HTML lama di dalam paragraf menjadi blok ini** (tabel di dalam paragraf tetap hanya-baca di editor; bisa dibuatkan konverter bila diperlukan).

## Bentuk data
```json
{ "type": "table-builder", "data": {
  "caption": {"id": "…", "en": "…"}, "note": {"id": "…", "en": "…"},
  "header": true, "first_col_header": false, "striped": true,
  "border": "rows | grid | none", "density": "compact | normal | relaxed",
  "color": "foresty | coral | aurum | charcoal | light",
  "columns": [ { "id": "itm_…", "label": {"id": "…", "en": "…"}, "align": "left | center | right" } ],
  "rows":    [ { "id": "itm_…", "cells": [ {"id": "…", "en": "…"}, … ] } ] } }
```
`cells` SEJAJAR dengan `columns` (indeks yang sama).

## Berkas
`app/Editor/Blocks/TableBlock.php`, `app/Content/Blocks/TableStyle.php`, `resources/views/components/blocks/render/table-builder.blade.php`, `resources/views/components/editor/table-grid.blade.php`, `resources/js/editor.js` (`tablePanel`, `tableOps`, `parseTsv`), `tests/table-test.php`. Mengubah: `app/Editor/BlockType.php` (`componentFirst`), `inspector.blade.php`, `FaqText.php` (`line()`).
