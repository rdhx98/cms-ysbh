# Usulan blok untuk CMS YSBH

Dasar pemilihan: (1) dipakai di halaman yang sudah Anda rancang (program, profil, transparansi laporan, mitra donatur, FAQ), (2) dikelola
editor non-teknis tanpa merusak desain, (3) tidak membuka celah keamanan (itu sebabnya **tidak ada blok HTML mentah**).

## Sudah ada, dipertahankan
Judul, Paragraf, Eyebrow, Gambar, Kartu Builder, Grup Langkah (timeline), Kolom, Pemisah Seksi. Rencana: Snippet (donasi, hubungi kami).

## Saran, berurutan
| # | Blok | Dipakai untuk | Catatan data |
|---|---|---|---|
| 1 ✅ | **Tombol** (*button-builder*, rilis 2; lihat `BLOK-TOMBOL.md`) | ajakan bertindak: Donasi, Unduh laporan, Hubungi kami | repeater; tautan ke halaman/artikel/berkas/URL/telepon/surel/anchor |
| 2 ✅ | **Akordion / FAQ** (rilis 4; lihat `BLOK-AKORDION.md`) | halaman FAQ; keterangan program | daftar pertanyaan-jawaban; saat ini hanya ada sebagai elemen di dalam kartu |
| 3 ✅ | **Daftar unduhan** (rilis 6; lihat `BLOK-UNDUHAN.md`) | Transparansi Laporan (PDF per tahun) | berkas dari file manager: judul, tahun, kategori; ukuran/jenis otomatis dari `media`; ikut "Digunakan Di" |
| 4 ✅ | **Callout** (rilis 7; lihat `BLOK-CALLOUT.md`) | peringatan/catatan penting di halaman kesehatan | nada: info / peringatan / sukses; ikon; teks |
| 5 ✅ | **Video (YouTube/Vimeo)** (rilis 8; lihat `BLOK-VIDEO.md`) | penjelasan program, testimoni video | YouTube/Vimeo, mode privasi, keterangan |
| 6 ✅ | **Artikel terbaru** (rilis 12; lihat `BLOK-ARTIKEL-TERBARU.md`) (dinamis) | beranda dan halaman program terisi otomatis | pilih kategori + jumlah; tanpa suntingan manual |
| 7 ✅ | **Galeri / logo mitra** (rilis 10; lihat `BLOK-GALERI.md`) / grid logo** | Mitra & Donatur | bisa tetap lewat Kartu Builder bila cukup |

**Tidak disarankan:** blok HTML/embed mentah (celah XSS), statistik (angka cukup di kartu), testimoni (sudah ditiadakan), tabel (tunda sampai Tiptap tunggal).

## Rancangan Tombol (untuk Anda setujui sebelum saya bangun)

```json
{
  "type": "button-builder",
  "data": {
    "align": "left | center | right",
    "stack_mobile": true,
    "buttons": [
      {
        "id": "btn_ab12cd34",
        "label": { "id": "Donasi Sekarang", "en": "Donate Now" },
        "link": { "kind": "page | article | url | file | tel | mailto | anchor", "ref": "…", "new_tab": false },
        "variant": "solid | outline | ghost",
        "color": "foresty | coral | aurum | charcoal",
        "size": "sm | md | lg",
        "icon": "heart", "icon_position": "left | right"
      }
    ]
  }
}
```
- **Tautan internal memakai ID**, bukan slug (`kind: page`, `ref: 12`), sehingga mengganti slug tidak mematahkan tombol. Di renderer diubah menjadi URL asli.
- **Kontrol baru yang dibutuhkan: *repeater*** (daftar item dengan tambah/hapus/urut, tiap item punya kontrol sendiri). Ini fondasi yang juga dipakai Akordion dan Daftar unduhan, jadi layak dibangun sekali dengan benar.
- Pemilih tautan memakai pencarian halaman/artikel yang sudah ada (`searchInternalPages`), plus pemilih berkas dari file manager.
- Warna dan gaya dibatasi pada token (bukan kelas Tailwind bebas), supaya desain tetap konsisten.

**Yang perlu Anda putuskan:** blok mana dari daftar di atas yang diambil (saran saya: 1–3 dulu), dan apakah rancangan Tombol sudah sesuai.
