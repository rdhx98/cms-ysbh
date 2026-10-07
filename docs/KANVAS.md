# Kanvas (pratinjau langsung)

Panel tengah builder. Menampilkan halaman/artikel/snippet **seperti di situs**, termasuk lebar tablet dan ponsel, dan **tanpa menyimpan**.

## Memasang (sekali)
1. Salin berkas baru (lihat `PERUBAHAN.md`, rilis 3).
2. Di grup rute `v2` (lihat `contoh-kode/routes.php`): `Route::livewire('/preview/{token}', 'content.canvas-frame')->name('preview.frame');`
3. Di `resources/js/app.js` (lihat `contoh-kode/app.js.snippet`): impor `registerCanvas` dan panggil di `alpine:init`.
4. `php artisan view:clear` dan `npm run build`.

Bila rute belum dipasang, panel menampilkan petunjuknya (bukan galat).

## Cara kerjanya
```
builder (admin)                                   bingkai ⚡canvas-frame (layout situs, di dalam <iframe>)
  isi berubah ─ jeda 0,7 dtk ─▶ publishPreview() ─▶ cache[token] (30 mnt, hanya pemilik)
                                       └─ postMessage 'canvas-refresh' ─▶ $wire.$refresh()  → render ulang dari cache
  klik blok di kanvas  ◀─ 'canvas-select' ────────── klik pada [data-block-id]
  fokus di outline/inspektur ─ 'canvas-focus' ───▶  sorot + gulir ke blok
  "Lihat sebagai ID/EN" ─ 'change-lang' ──────────▶  $wire.$set('lang', …)      (protokol sama dengan page-preview)
```
- **Kenapa iframe**: titik putus Tailwind mengikuti lebar *jendela*; hanya jendela sendiri yang membuat pratinjau ponsel akurat, dan CSS situs tidak bercampur dengan CSS admin.
- **Aman untuk data online**: pratinjau membaca cache, tidak pernah menulis ke `pages`/`posts`/`snippets`. Token 40 heksa acak; hanya pembuatnya yang bisa membukanya (pengguna lain mendapat 404); data dibersihkan (`BlockSanitizer`) sebelum dititipkan; batas 2 MB.
- **Pesan hanya antar jendela asal sama**: bingkai menolak pesan yang bukan dari induknya; induk menolak pesan yang bukan dari bingkainya, ID blok yang tak sah, dan ID yang tidak ada di dokumen. Jenis blok dibaca dari data, bukan dari pesan.
- **Bahasa**: tab "Ganda / ID / EN" di header hanya mengatur kolom isian. Pilihan "Lihat sebagai" di pratinjau terpisah; bila tab header diset ke satu bahasa, pratinjau ikut; "Ganda" membiarkannya.
- **Di tab baru** (tombol ↗): bingkai yang sama dibuka biasa, tautan dan tombol berfungsi normal. Di dalam kanvas, klik hanya **memilih** blok.

## Yang bergantung pada aplikasi Anda
`layouts.landing.dynamic-preview` (layout bingkai, sama dengan `page-preview`), komponen `<x-table-of-contents>`, dan komponen render `blocks.render.<tipe>` untuk setiap blok. Blok yang belum punya komponen render tampil sebagai kotak penanda di kanvas dan dilewati di situs (tidak menjatuhkan halaman).

## Batasan saat ini
- Memilih **elemen di dalam kartu** lewat klik belum ada (baru tingkat blok); gunakan outline untuk elemen.
- Animasi muncul bertahap dimatikan di kanvas (supaya tidak berulang tiap pembaruan).
- Artikel: kanvas menampilkan **isi** artikel; kerangka artikel (judul besar, kategori, tanggal) mengikuti halaman artikel publik Anda.
- Token kedaluwarsa setelah 30 menit tanpa perubahan; kanvas mengambilnya ulang otomatis.

## Memakai mesin yang sama di halaman Anda (opsional, kapan saja)
`page-preview` Anda **tidak diubah**. Bila ingin satu mesin saja, ganti blok `@php … $groupedSections …` + daftar isi + `@foreach ($groupedSections …)` di dalamnya dengan satu baris:
```blade
<x-content.sections :blocks="$allContent" :order="$rootOrder" :settings="$settings" :lang="$lang" />
```
Tampilannya sama; bedanya: `id=""` kosong tidak dicetak, `transition-delay` memakai gaya inline (kelas `delay-[…ms]` dinamis tidak pernah dibuat Tailwind), dan blok tanpa komponen render dilewati, bukan menjatuhkan halaman.
