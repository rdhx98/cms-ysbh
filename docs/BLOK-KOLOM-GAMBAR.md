# Blok Kolom dan Gambar: rata vertikal dan tinggi (rilis 35)

## Kolom: patokan kolom tertinggi
Baris grid setinggi **kolom terpanjang**. Setiap kolom diregangkan sebesar itu, lalu isinya disejajarkan pada sumbu Y (kontrol **Rata Vertikal**):

| Pilihan | Kelas | Hasil |
|---|---|---|
| Atas | `justify-start` | isi menempel ke atas kolom |
| Tengah | `justify-center` | isi di tengah tinggi kolom |
| Bawah | `justify-end` | isi menempel ke bawah kolom |
| Bagi rata | `justify-between` | blok pertama di atas, terakhir di bawah, sisa ruang dibagi sama di antara blok |

Catatan: kolom berisi satu blok dengan **Bagi rata** tampil seperti **Atas**. **Rata Horizontal** (kiri/tengah/kanan) menata lebar blok di dalam kolom, bukan tinggi. **Jarak Antar Blok di Kolom** adalah jarak antar blok dalam satu kolom (jarak antar kolom tetap).

## Gambar: kontrol Tinggi
| Pilihan | Kelas | Hasil |
|---|---|---|
| Auto | `h-auto` | mengikuti rasio gambar; "Tinggi Maksimum" berlaku |
| Penuh | `h-full` | mengisi tinggi tempatnya. Di dalam Kolom: setinggi kolom (tertinggi) |
| 12 / 18 / 24 / 32 | `h-48` `h-72` `h-96` `h-[32rem]` | tinggi tetap (rem); gambar dipotong atau diletakkan utuh sesuai **Pemotongan** |

Pasangan yang berguna: gambar **Penuh** + Pemotongan **Penuhi (potong)** di kolom yang bersebelahan dengan teks panjang: gambar mengisi persis setinggi teks. Bila gambar berada di kolom yang sama dengan blok lain, ia mengisi sisa tinggi kolom.
Di luar Kolom, **Penuh** sama dengan Auto (tidak ada tinggi untuk diisi).

## Jarak Bawah blok di dalam Kolom (rilis 36)
Blok tingkat atas mendapat margin dari pembungkus halaman; anak Kolom tidak. Renderer Kolom mencetak Jarak Bawah **yang dipilih** pada anak (Tombol, Video, Galeri, Callout, Tabel, dst.). Judul, paragraf, eyebrow, gambar, dan kartu mengurus margin sendiri.

## Kanvas
Blok Kolom baru menampilkan kotak "Kolom N masih kosong" per kolom dan bingkai putus-putus; klik sebuah blok di dalam kolom langsung memilih blok itu. Tidak ada penanda ini di situs publik.

## Pemasangan
Renderer milik proyek berubah: lihat `opsional/render-kolom-gambar/INSTALL.md`. Kelas Tailwind ada utuh di `app/Content/Blocks/LayoutStyle.php` (CSS landing harus memindai `app/`: `@source '../../app'`; sudah dicek `tools\check-landing.php`).
