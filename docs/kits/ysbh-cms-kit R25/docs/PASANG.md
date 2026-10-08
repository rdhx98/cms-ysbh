# Memasang inspektur, outline, dan pemilih ikon

> Kini satu zip (`ysbh-cms-kit`): lihat `README.md` di akar zip untuk cara memasang. Debug: `docs/DEBUG.md`. Urutan pengerjaan berikutnya: `docs/PETA-JALAN.md`.

Kit editor dan kit konten kini **satu pohon folder**. Salin **semuanya**: komponen saling bergantung.

## 1. Berkas

| Dari zip | Ke proyek | Isi |
|---|---|---|
| `app/Editor/*.php` (**5**) | `app/Editor/` | `Options`, `Field`, `BlockType`, `BlockRegistry`, **`BlockPalette`** (tipe blok yang bisa ditambah, zona kontainer, tipe anak yang diizinkan) |
| `resources/js/editor.js` | `resources/js/editor.js` | store `editor`, `wireField`, dan `outline` |
| `resources/views/components/editor/*.blade.php` (**17**) | `resources/views/components/editor/` | `inspector`, `icon-picker-modal`, `icon-sprite`, `outline`, `outline-node`, `add-menu`, `debug` (hanya tampil bila `APP_DEBUG=true`), `field`, `segmented`, `swatches`, `select`, `icon-picker`, `i18n`, `toggle`, `text`, `media`, `rich` |
| `app/Livewire/Traits/ManagesBlockStructure.php` | `app/Livewire/Traits/` | aksi `addBlockAt()` dan `moveBlock()` |

**Hapus** `outline-sementara.blade.php` bila sudah pernah Anda salin: digantikan `outline`.

## 2. Tiga hal yang harus benar

1. **`HasContentBlocks` harus versi patch** (punya `deleteBlockTree` dan `cloneBlockTree`). Tombol Hapus dan Duplikat di outline
   memakainya. Versi asli menyisakan anak yatim saat menghapus kontainer dan membuat salinan berbagi anak dengan aslinya.
   Builder sekarang berhenti dengan `LogicException` bila trait belum dipatch, supaya tidak merusak data diam-diam.
2. **Sprite di layout**: `<x-editor.icon-sprite />` (sekali). **Cara menemukan layoutnya**: layout yang dipakai builder adalah berkas yang memuat `{{ $slot }}` dan `@livewireScripts`
   (cari: `Get-ChildItem resources\views -Recurse | Select-String '\$slot'`; bawaan Livewire 4: `resources/views/layouts/app.blade.php`). Tempel tepat setelah `<body>`. Periksa:
   `document.querySelectorAll('svg[data-icon-sprite] symbol').length` harus ≥ 75. Sprite kini juga memuat ikon palet (`heading-1`, `columns-4`, dst.).
   Nama ikon yang tidak ada di paket Lucide Anda dilewati, tidak membuat layout error; ikonnya hanya tampil kosong.
3. **`resources/js/app.js`**: `registerEditor` dipanggil di `alpine:init` (sudah Anda lakukan).

## 3. Kunci bahasa (header masih menampilkan `ui.header.page.create`)

Tambahkan isi `snippets/lang-ui.php` ke `lang/id/ui.php` (dan `lang/en/ui.php`). Sementara belum, builder memakai teks cadangan
("Halaman Baru", "Edit Artikel", dst.), jadi kunci mentah tidak lagi tampil.

## 4. Cara mencoba

| Lakukan | Hasil yang benar |
|---|---|
| Klik baris blok di outline | panel properti muncul, **tanpa request** (tab Network kosong) |
| `+ Tambah blok` → Judul | **1** request `addBlockAt`; blok baru muncul dan **langsung terfokus** |
| `+ Tambah ke Kolom 2` pada blok Kolom | anak masuk ke kolom itu; menu tidak menawarkan Kolom/Step/Pemisah/Kartu Builder |
| ↑ ↓ pada baris | blok bertukar tempat dengan tetangganya (di zonanya sendiri) |
| Duplikat blok Kolom yang berisi anak | salinan punya anak dengan ID baru |
| Hapus blok yang berisi anak | konfirmasi menyebut jumlah blok di dalamnya |
| Ketik di kolom teks Judul yang kosong | label baris di outline ikut berubah seketika |

## 5. Palet blok (keputusan)

Grup tombol, lencana, statistik, kartu, dan testimoni **ditiadakan** dari menu. Yang bisa ditambah: Judul, Paragraf, Eyebrow, Gambar, Kartu Builder, Grup Langkah, Kolom, Pemisah Seksi.
- Kolom menerima: Judul, Paragraf, Eyebrow, Gambar, Kartu Builder.
- Grup Langkah menerima **hanya** Kartu Builder (tombol "Tambah langkah" langsung, tanpa menu), sesuai blade step-group.
- Blok lama bertipe yang ditiadakan tetap terbaca di outline (label dari nama tipe) dan tetap dirender halaman publik.
- Panel properti tersedia: Judul, Paragraf, Pemisah Seksi, **Kolom**, **Grup Langkah**, dan semua elemen kartu.

## 5b. Yang sengaja dibatasi

- **Teks Judul/Paragraf**: kosong atau polos = bisa diisi (yang diketik di-escape: `<` `>` `&`). Berisi HTML dari Tiptap = **hanya-baca**,
  dengan catatan kuning. Pemformatan tidak mungkin rusak lewat kotak ini. Tiptap tunggal untuk teks berformat = Fase 3.
- **Tipe tanpa panel** (eyebrow, image, multi-columns, step-group, ...) menampilkan catatan kuning di inspektur: daftarkan di
  `BlockRegistry` satu per satu.
- **Kanvas** (tengah) masih placeholder.

## 6. Asumsi yang perlu Anda cek

- **Isi step-group** = tipe kolom + `card-builder` (`BlockPalette::STEP_CHILDREN`). Blade step-group tidak ada di berkas yang saya terima.
- **Zona kolom** = `col_1_zone…col_N_zone` dengan `N = data.col_count` (maks 6), dan step-group = `children`, dibaca dari trait dan blade lama Anda.
- Tidak ada drag-and-drop; urutan lewat tombol ↑ ↓.

## 7. Pesan galat yang mungkin muncul

| Pesan | Penyebab |
|---|---|
| `HasContentBlocks belum di-patch ...` | trait di proyek masih versi asli: pasang patch |
| `Call to undefined method ...addBlockAt()` | `ManagesBlockStructure` belum di-`use` di builder, atau berkas belum disalin |
| `Class "App\Editor\BlockPalette" not found` | `BlockPalette.php` belum disalin ke `app/Editor/` |
| Blok baru tidak terfokus otomatis | event `block-added` tidak sampai ke outline (cek di Console: `window.addEventListener('block-added', console.log)`) |
| Ikon baris kosong | sprite belum dipasang di layout, atau nama ikon tak ada di Lucide Anda |
