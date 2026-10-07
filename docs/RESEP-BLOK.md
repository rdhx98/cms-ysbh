# Resep menambah blok baru

Urutan yang sama dipakai untuk Akordion, Daftar unduhan, Callout, Video, dst. Tiap langkah punya pemeriksaan; blok Tombol adalah contohnya.

1. **Registri**: `app/Editor/BlockRegistry.php`. Tambah `new BlockType('nama', 'Label', 'ikon', 'block', [Field…], defaults: […])`.
   - Daftar berulang: `Field::repeater(kunci, label, [Field…], itemDefaults, maks, 'kata benda')`. Tautan: `Field::link(...)`.
   - `defaults` boleh memuat `"@id"` (ID baru) dan `"@locales"` (peta bahasa kosong). **Jangan** mengisi teks palsu.
2. **Palet**: `app/Editor/BlockPalette.php`. Tambah ke `TYPES` (label, ikon, grup) dan, bila boleh berada di dalam kolom, ke `COLUMN_CHILDREN`.
3. **Pembersih data**: bila blok menyimpan **URL, kelas CSS, atau teks yang dicetak**, tambahkan fungsi di `BlockSanitizer` dan daftarkan di `clean()`. Prinsip: nilai tak sah diganti bawaan, tidak ditolak.
4. **Tampilan publik**: `resources/views/components/blocks/render/<nama>.blade.php`. Bersihkan data lagi di sini; cetak dengan `{{ }}`; kelas CSS dari daftar tetap.
5. **Pemeriksaan**: salin pola `tests/button-test.php` (registri ↔ bawaan ↔ pembersih harus sepakat) dan uji render dengan data berbahaya.

## Jebakan yang sudah pernah terjadi (jangan ulangi)
| Jebakan | Akibat | Pencegah |
|---|---|---|
| komentar `//` di baris `@props([...])` yang berisi prop lain | sisa prop **tertelan**, variabel tak terdefinisi | jangan beri komentar di baris props |
| ternary bersarang tanpa kurung (`a ? b : c ?: d`) | **galat fatal PHP 8**, halaman publik mati | `php compile_all.php` mengompilasi semua Blade lalu me-lint hasilnya (`php -l` pada `.blade.php` **tidak** menangkapnya) |
| `$a + compact('x')` untuk menimpa kunci | kunci kiri menang; penyaringan tidak berlaku | `array_replace` |
| path kontrol berbasis indeks + penulisan tertunda | nilai lama menimpa item lain / item hantu | `typeAt` kini membaca nilai **saat menembak** dan membatalkan bila path hilang |
| `{...objek}` untuk memperluas data Alpine | getter berubah menjadi nilai beku | `Object.defineProperties(..., getOwnPropertyDescriptors(...))` (`extend`) |
| memercayai `url` dari browser untuk berkas | tautan sisipan | hanya `media_id`, URL dari model `Media` |
| regex validasi berjangkar `$` tanpa modifier `D` | baris baru di ujung (`"abc\n"`) **lolos** | selalu `/…$/D` untuk token, slug, kunci, id |
| memanggil komponen render yang belum ada (`x-dynamic-component`) | seluruh halaman publik jatuh karena SATU blok | `x-content.sections` memeriksa `view()->exists(...)` dan melewati/menandai blok itu |

