# Blok Galeri / Logo

Kumpulan gambar dari File Manager, ditampilkan sebagai **grid** atau **carousel**, dengan dua jenis tampilan: **Foto** (galeri kegiatan) dan **Logo mitra**. Blok ini adalah modul (`app/Editor/Blocks/GalleryBlock.php`).

## Cara memakai
1. **+ Tambah blok → Galeri / Logo** (juga di dalam kolom, bukan di dalam Grup Langkah).
2. Tab **Blok**: **+ Tambah gambar**, buka butirnya, lalu **Jelajahi File Manager** dan pilih gambar (hanya gambar yang ditawarkan). Isi **Nama gambar / teks alternatif** (dibaca pembaca layar; juga jadi judul baris), dan, bila perlu, **Keterangan** dan **Tautan**. Maksimal 60 gambar; ↑ ↓ mengurutkan.
3. Atur **Jenis**, **Tampilan**, **Kolom**, **Rasio foto**, dan tiga sakelar.

## Pilihan
| Pilihan | Arti |
|---|---|
| **Jenis: Foto** | petak berpotongan mengisi rasio (1:1, 4:3, 3:2, 16:9); keterangan tampil di bawah foto; foto bisa diperbesar |
| **Jenis: Logo mitra** | kotak putih berasio 3:2, logo tampil **utuh**; tanpa keterangan dan tanpa pembesar |
| **Tampilan: Grid** | semua gambar tertata; 2 kolom di ponsel, 3 di tablet, sesuai pilihan di desktop (2–6) |
| **Tampilan: Carousel** | satu baris yang bisa digeser: 2 gambar per layar di ponsel, paling banyak 3 di tablet, sesuai pilihan di desktop; panah, geser sentuh, papan ketik |
| **Logo hitam-putih** | logo tampil abu-abu dan agak pudar, berwarna penuh saat disorot atau difokus |
| **Foto bisa diperbesar** | klik membuka pembesar (hanya jenis Foto); panah kiri/kanan berpindah foto, Esc menutup |
| **Carousel berjalan otomatis** | maju satu gambar tiap ±4 detik (lihat aturan di bawah) |

Pilihan yang tidak relevan diabaikan (mis. "Rasio foto" untuk Logo mitra, "berjalan otomatis" untuk Grid).

## Aturan penting
- **Berjalan otomatis dibuat aksesibel**: berhenti saat kursor di atas galeri atau saat ada yang difokus, ada tombol **Jeda/Putar**, dan **tidak dijalankan sama sekali** bila pengguna memilih "kurangi gerakan" di sistem operasinya. Kembali ke awal setelah gambar terakhir. Tidak berjalan di kanvas editor.
- **Tautan**: gambar boleh bertaut (mis. situs mitra). Aturan sama dengan tombol (hanya `http(s)`, `mailto`, `tel`, jalur, anchor; halaman/artikel hanya bila online/terbit). **Tautan yang gagal tidak membuang gambarnya**: logo tetap tampil, hanya tanpa tautan; di kanvas ditandai. Foto yang bertaut membuka tautannya, **bukan** pembesar.
- **Teks alternatif**: bila dikosongkan, dipakai nama berkas yang dirapikan ("logo-mitra_utama.png" → "logo mitra utama") agar gambar bertautan tetap punya nama. Sebaiknya tetap diisi.
- Gambar yang dihapus dari File Manager atau yang bukan berjenis gambar **dilewati di situs**; di kanvas tampil pudar bertepi putus-putus dengan penjelasan.
- **Tanpa JavaScript**: grid tetap tampil; carousel tetap bisa digeser (scroll-snap) tanpa panah; foto menjadi tautan ke berkas gambarnya.

## Ukuran gambar
File Manager Anda menyajikan **berkas aslinya** (tidak ada pembuatan gambar kecil). Unggah foto galeri yang sudah dikecilkan (sekitar 1600 px sisi panjang, di bawah 300 KB) dan logo di bawah 100 KB. Gambar dimuat malas (`loading="lazy"`), tetapi 30 foto berukuran 5 MB tetap membebani pengunjung.

## Keamanan
Gambar **selalu** dibentuk dari `media_id` lewat model Media (hanya berjenis `image/*`); alamat di data hanya untuk tampilan di inspektur. Teks di-escape; semua kelas dari daftar tetap; data dibersihkan saat disimpan, saat titipan pratinjau, dan sekali lagi saat dirender. Pembesar memakai elemen `<dialog>` bawaan peramban (fokus terkunci, Esc menutup).

## Yang belum
Memilih **banyak gambar sekaligus** dari File Manager (sekarang satu per satu; butuh dukungan pilih-banyak di File Manager Anda). Keterangan per foto di dalam pembesar sudah ada; tidak ada pengelompokan atau penyaringan oleh pengunjung.

## Bentuk data
```json
{ "type": "gallery-builder", "data": {
  "mode": "photos | logos", "layout": "grid | carousel", "columns": "2..6 (teks)", "ratio": "1:1 | 4:3 | 3:2 | 16:9",
  "grayscale": false, "lightbox": true, "autoplay": false,
  "items": [ { "id": "itm_ab12cd34", "title": {"id": "…", "en": "…"}, "caption": {"id": "…", "en": "…"},
               "image": {"media_id": 7, "url": "…/foto.jpg"}, "link": {"kind": "url", "ref": "https://…", "new_tab": true} } ] } }
```

## Berkas
`app/Editor/Blocks/GalleryBlock.php`, `app/Content/Blocks/GalleryStyle.php` (gaya, kolom, lebar carousel), `GalleryList.php` (butir, alt, tautan), `resources/views/components/blocks/render/gallery-builder.blade.php`, `tests/gallery-test.php`.
