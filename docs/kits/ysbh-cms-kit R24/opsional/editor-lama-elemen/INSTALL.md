# Komponen editor: segmented, swatches, select, icon-picker

Salin isi folder `resources/` ke proyek Anda (nama file sama dengan yang lama, jadi akan menimpa).
Cadangkan atau commit dulu `element-*.blade.php` yang lama.

## 1. Pasang (4 langkah)

1. **Salin file.**
   - `components/editor/*` : komponen baru (segmented, swatches, select, icon-picker, icon-picker-modal)
   - `components/blocks/editor/card-builder/element-*.blade.php` : lima elemen yang sudah memakainya
2. **Tambah saklar di `config/cms.php`:**
   ```php
   'editor' => [
       'defer_style_sync' => false,   // false = perilaku lama (tiap klik = request). true = lihat bagian 2.
   ],
   ```
3. **Pasang modal ikon SEKALI** di `page-editor`, tepat di bawah `</svg>` sprite:
   ```blade
   <x-editor.icon-picker-modal />
   ```
   Sprite `#icon-{nama}` yang sudah ada dipakai apa adanya. Tidak ada yang perlu diubah di sana.
4. **Hapus pemilih lama.** Tidak ada lagi `x-teleport` + `@foreach ($iconsList ...)` di `element-icon`.
   Pemakai `$iconsList` lain (mis. `eyebrow`) bisa diganti `<x-editor.icon-picker :path="..." />`.

Tidak ada perubahan di PHP, trait, atau JavaScript bundle.

## 2. Saklar `defer_style_sync`

| Nilai | Efek |
|---|---|
| `false` (bawaan) | Sama seperti sekarang: tiap klik mengirim request. **Bedanya**, tombol langsung menyala di klien tanpa menunggu server. |
| `true` | Klik gaya tidak mengirim request; nilainya ikut terkirim bersama request berikutnya (mis. Simpan). Dokumentasi Livewire 4: `$set(name, value, live = true)`. |

Dengan `true`, pratinjau yang dirender server (mis. Live Preview Card Builder) tidak berubah sampai ada
request berikutnya. Pakai `true` hanya setelah pratinjau di editor mengikat kelas dari state klien.
Saklar ini sengaja satu tempat, supaya Anda bisa membandingkan rasa "snappy"-nya tanpa menyentuh blade.

## 3. Cek di aplikasi Anda (5 menit)

Komponen diuji di Blade sungguhan + Alpine + Tailwind v4, tetapi `$wire` di sana adalah stub. Cek ini di Livewire asli:

1. Klik swatch/segmented. **Tombol harus menyala seketika.** Kalau baru menyala setelah request selesai,
   `$wire.$get()` tidak reaktif di versi Anda. Kirimkan hasilnya, saya sesuaikan.
2. Set `defer_style_sync` ke `true`, klik beberapa warna. **Tab Network harus kosong**, lalu Simpan, dan nilai harus tersimpan.
3. Buka pemilih ikon dari dua elemen berbeda. Keduanya memakai modal yang sama dan mengubah elemennya masing-masing.
4. Bandingkan ukuran respons satu request pada halaman dengan banyak elemen ikon.

## 4. Perubahan tampilan yang disengaja
- Label kontrol seragam (`text-xxs`, abu tua). Sebelumnya `element-text` memakai label 9px abu muda.
- Swatch "Coral" kini benar-benar coral (sebelumnya oranye), begitu juga Foresty/Goldy/Sage.
- Pemilih font jadi `<select>` native: ringan dan memakai pemilih bawaan di ponsel, tetapi tidak lagi menampilkan
  tiap nama dengan fontnya di semua browser. Daftarnya dari `config('cms.fonts')`.
- Kontrol Teks/Pill bertukar di klien (tanpa request) saat "Mode Pill" dicentang.

## 5. Belum dikerjakan
- `heading`, `paragraph`, `section-divider`, `eyebrow`, `multi-columns` masih memakai pola lama (pola yang sama bisa langsung dipakai).
- Nilai bawaan masih tertulis di blade (`default="..."`), bukan satu sumber bersama dengan trait. Itu langkah 2 dari rencana.
  Contoh ketidakcocokan yang masih ada: trait mengisi font `font-sans`, sedangkan daftar `cms.fonts` tidak punya `font-sans`.
- Sprite SVG masih di dalam view Livewire. Sebaiknya dipindah ke layout.
