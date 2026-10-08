# Perombakan UI editor: Fase 1 (registri + inspektur sekali-per-tipe)

> **Mulai dari `PASANG.md`**: daftar berkas yang harus disalin, tiga perubahan kecil (termasuk sprite ikon), dan arti pesan galat.

Menggantikan `blade-components` sebelumnya (kini di `opsional/editor-lama-elemen/`; v2 kompatibel dengan blade `element-*` yang sudah Anda pasang).

## Keputusan arsitektur
UI 4 area dari mockup menuntut satu aturan: **membaca dari state klien, menulis lewat `$wire.$set`, dan hanya operasi struktur yang ke server.**

| Hal | Cara |
|---|---|
| Fokus (blok/elemen mana yang dipilih) | Store Alpine `editor` (`base` path + `panel`). Pindah fokus = ganti satu string, tanpa request. |
| Properti | **Satu** panel per *tipe*, dirender sekali; path relatif terhadap `base` (`rel="data.style.size"`). |
| Nilai (gaya, teks) | `$wire.$set(path, nilai, live)`. `defer_style_sync` mengatur apakah tiap klik dikirim. |
| Tambah/pindah/hapus/duplikat | Aksi server di trait (sudah dipatch & diuji). |
| Bentuk kontrol | `App\Editor\BlockRegistry` (PHP). Menambah properti = satu baris `Field::...`. |

## Isi paket
- `app/Editor/`: `Options`, `Field`, `BlockType`, `BlockRegistry` (heading, paragraph, section-divider, dan elemen teks/ikon/inisial/foto profil/akordion)
- `resources/js/editor.js`: store `editor` + `wireField`
- `resources/views/components/editor/`: `segmented swatches select icon-picker icon-picker-modal i18n toggle text media rich` + `field` + `inspector`
- `tests/registry-audit.php`: bandingkan registri ↔ trait ↔ `config/cms.php`

## Pemasangan
1. Salin folder (`app/`, `resources/`, `tests/`).
2. `resources/js/app.js`:
   ```js
   import { registerEditor } from './editor'
   document.addEventListener('alpine:init', () => registerEditor(window.Alpine))
   ```
3. Di page-editor: `<x-editor.icon-picker-modal />` (sekali) dan `<x-editor.inspector :locales="$activeLocales" />` di panel kanan.
4. Fokus dari klik (contoh, di wrapper blok / baris elemen):
   ```blade
   x-on:click="$store.editor.focusBlock('{{ $blockId }}', '{{ $block['type'] }}')"
   x-on:click="$store.editor.focusElement('{{ $elPath }}', '{{ $el['elementType'] }}')"
   ```
5. `config/cms.php`: `'editor' => ['defer_style_sync' => false]`.

**Teks kaya (heading, paragraf) sengaja hanya-baca di inspektur** sampai Fase 3. `<x-editor.rich>` hanya menampilkan teks tanpa tag dan tidak menulis apa pun, supaya HTML Tiptap tidak rusak. Keduanya tetap diedit lewat editor lama.

## Hasil audit (`php tests/registry-audit.php app/Livewire/Traits/HasContentBlocks.php config/cms.php`)
Enam ketidakcocokan nyata antara yang ditulis trait dan yang bisa dipilih kontrol. Arah perbaikannya keputusan Anda:

| # | Temuan | Saran |
|---|---|---|
| 1 | `addBlock('section-divider')` menghasilkan data **kosong**: trait hanya punya kunci `"section_divider"`, sedangkan toolbar memanggil `section-divider`. | Pada `match`: `"section-divider", "section_divider" => [...]` |
| 2 | `addElementToColumn('profile_photo')` menghasilkan data **kosong**: trait hanya mengenal `"profile"` (kunci `image_url`, `name` yang tidak dipakai blade). | Tambah arm `profile_photo` dengan `content.url/alt`, `style.size/radius/border/border_color` |
| 3 | Akordion: trait menulis `title`/`body` dan tema `default`; blade memakai `question`/`answer` dan tema `foresty/coral/dark`. | Samakan trait ke blade |
| 4 | Teks: trait menulis font `font-sans`, tidak ada di `cms.fonts`. | Tambah `font-sans` ke `cms.fonts`, atau ubah default |
| 5 | Ikon & inisial: ukuran bawaan `w-10 h-10`, padahal opsinya `w-10 h-10 md:w-12 md:h-12`. | Ubah default trait |
| 6 | `avatar_border_colors` tidak punya `border-transparent` (dikomentari), padahal trait menulisnya. Registri menambahkannya sendiri. | Kembalikan di config |

Catatan: `text_transform` kini punya kontrol; `font_size`/`weight` (inisial) dan `margin` (ikon, akordion) ditulis trait tanpa kontrol.

Skrip keluar dengan kode 1 bila ada kesalahan, jadi bisa dijalankan di CI.

## Yang terbukti, dan yang belum
**Terbukti** (Blade sungguhan + Alpine + Tailwind v4, `$wire` di-stub): 72 pengujian inspektur + 66 pengujian komponen lulus, tanpa galat JS. Antara lain berpindah fokus tanpa `$set`, penulisan ke path elemen bersarang, `section_divider` dipetakan ke panel yang benar, kontrol kondisional (Mode Pill) bertukar di klien.

**Ukuran, 50 kartu (teks+ikon), headless Chromium di sandbox:**

| | elemen DOM | root Alpine | init Alpine |
|---|---|---|---|
| per-instance | 6.638 | 701 | 410 ms |
| inspektur sekali | 897 | 52 | 91 ms |

(Gzip memperkecil selisih byte jadi tidak berarti. Yang mahal adalah DOM dan inisialisasi, bukan transfer.)

**Belum terbukti: wajib dicek di Livewire asli**
1. `$wire.$get()` reaktif di Alpine (sorotan berpindah saat fokus berganti).
2. **`wire:ignore` tidak mencegah markup inspektur (~101 KB mentah) ikut terkirim di respons tiap commit.** Cek ukuran respons update di tab Network. Jika ikut terkirim: pindahkan ke `@island`, atau muat sekali lewat route statis.
3. `alpine:init` terpanggil sebelum Alpine memulai di Livewire 4 (jika tidak, daftarkan lewat `Livewire.hook`/urutan impor).
4. Isian teks menulis lewat `$set`, bukan `wire:model`: validasi Livewire per properti tetap berlaku, tetapi pesan error tidak otomatis tampil di bawah kolom.

## Fase berikutnya
2. **Shell**: outline + kanvas dirender klien dari `$wire.content`; layout responsif dari mockup.
3. **Tiptap tunggal** per bahasa di inspektur (menghapus ratusan instance), lalu `heading`/`paragraph` tidak lagi hanya-baca.
4. UI aksi struktur (tambah/pindah/duplikat/hapus) memakai trait yang sudah dipatch.
5. Migrasi blok lain (eyebrow, image, multi-columns, step-group) = menambah entri registri.
6. Hapus blade editor lama.
