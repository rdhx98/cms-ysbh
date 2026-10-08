# Migrasi `page-editor` → `content.builder`

Dua pertanyaan Anda, dijawab dulu:

- **Judul sesuai rute + `wire:navigate`** → bagian 2 (jawaban ringkas: slot dinamis; `wire:navigate` mengganti `<title>` sendiri).
- **Cara memindahkan editor lama** → bagian 5 (tujuh tahap, masing-masing dengan gerbang uji).

Yang sudah diuji di sandbox: `ContentType`, `ContentDocument` (40 pengujian), dan ekspresi judul dinamis di browser.
**Yang belum**: komponen `⚡builder.blade.php` itu sendiri belum dijalankan di Livewire (sandbox saya tidak punya Livewire). Perlakukan sebagai rangka.

## 1. Rute

Rute Anda yang sekarang punya tiga masalah, dua di antaranya terverifikasi dari kode Laravel 12:

| Masalah | Penjelasan |
|---|---|
| `{post:slug}` tidak cocok | `slug` Anda JSON per bahasa. Binding yang di-scope oleh `{category}` memanggil `resolveRouteBindingQuery` milik **model anak** (`Post`), bukan `resolveRouteBinding`. Override di `Page` yang ada sekarang tidak berlaku untuk ini, dan `Post` tidak punya override sama sekali. |
| `{category}` mensyaratkan relasi | Binding ber-scope mencari relasi `Category::posts()` (nama diturunkan dari nama parameter). Bila tidak ada, galat. |
| `{page}` tanpa `:id` memakai slug | `getRouteKeyName()` di kedua model mengembalikan `slug`, jadi `{page}` (tanpa `:id`) mencari lewat slug. |

Selain itu slug bisa diubah di editor, sehingga URL edit yang memuat slug akan putus.

**Saran:** URL admin memakai ID, bukan slug, dan tanpa `{category}` (kategori adalah atribut artikel, bukan bagian alamat). Lihat `snippets/routes.php`.

Selama transisi, daftarkan builder dengan awalan `v2` (URL dan nama rute) supaya tidak bentrok dengan rute editor lama. `ContentType::fromRouteName()` mengabaikan awalan itu. Saat cutover, hapus awalan.

Bila Anda tetap ingin slug di URL, perbaikannya ada di **kedua model** (override `resolveRouteBindingQuery`, bukan `resolveRouteBinding`):

```php
public function resolveRouteBindingQuery($query, $value, $field = null)
{
    if (($field ?? $this->getRouteKeyName()) === 'slug') {
        return $query->where(function ($q) use ($value) {
            foreach (config('app.supported_locales', ['id', 'en']) as $locale) {
                $q->orWhere("slug->{$locale}", $value);
            }
        });
    }
    return parent::resolveRouteBindingQuery($query, $value, $field);
}
```

## 2. Judul per rute dan `wire:navigate`

Diverifikasi dari dokumentasi Livewire 4 (Pages dan Navigate):

1. **Layout** harus mencetak `$title` (`snippets/layout-title.blade.php`).
2. **`<x-slot:title>` ditulis di luar elemen akar** view komponen, dan view hanya boleh punya satu elemen akar.
3. Satu komponen melayani empat rute, jadi `#[Title('...')]` (statis) tidak cukup. Isi slot dihitung dari jenis dan mode:
   ```blade
   <x-slot:title>{{ $this->pageTitle }}</x-slot:title>
   ```
   `pageTitle` adalah properti `#[Computed]` yang memakai kunci terjemahan `ui.title.{jenis}.{create|edit}` (`snippets/lang-ui.php`). Isi slot adalah Blade biasa, jadi ekspresi dinamis boleh. Contoh dokumentasi hanya menunjukkan teks statis, jadi cek sekali di rute edit.
4. **`wire:navigate` tidak butuh apa-apa lagi.** Dokumentasi: setelah halaman tujuan diambil, Livewire mengganti URL, tag `<title>`, dan isi `<body>` dengan milik halaman baru. Selama server merender judul yang benar untuk rute tujuan, tab ikut benar.
5. **Setelah simpan pertama (buat → edit)**: ganti `history.replaceState` di editor lama dengan `$this->redirect(route(...), navigate: true)`. Dengan `replaceState`, URL berubah tetapi judul tab tetap "Buat Halaman" dan komponen masih berpikir ia mode buat. Harga yang dibayar: komponen dimuat ulang, jadi keadaan tampilan (collapse blok) kembali ke awal. Kirim **ID eksplisit** ke rute, karena `getRouteKey()` model Anda adalah slug.
6. **Judul yang mengikuti ketikan**: slot hanya dievaluasi saat halaman dirender penuh (muat awal dan navigasi), jadi memperbarui judul saat Anda mengetik butuh JavaScript. Itu dikerjakan oleh `x-effect` di `⚡builder.blade.php`. Hasil uji browser: awalnya "Buat Halaman — YSBH", saat mengetik "Tentang Kami" menjadi "Tentang Kami — YSBH", dan saat dikosongkan kembali ke judul server.
7. `page-preview` adalah halaman statis per rute: cukup `#[Title('Pratinjau Halaman')]`.

Satu tebakan saya, belum terverifikasi: di editor lama Anda mengganti `$title` menjadi `$page_title`, kemungkinan karena bentrok dengan `$title` milik slot layout. Rangka memakai `$titles` (jamak) supaya aman dan tidak spesifik halaman.

## 3. Arsitektur builder

```
rute (nama) ──► ContentType ──► model, parameter rute, status, kunci terjemahan
tabel content ─► ContentDocument ─► { blocks, order, settings }   (satu-satunya pembaca/penulis)
blok ──────────► HasContentBlocks (sudah dipatch & diuji) + registri/inspektur (`app/Editor`)
```

- **Tipe hanya ditentukan di `mount()`** dari nama rute, lalu disimpan di `#[Locked] public string $type`. Request update Livewire tidak lagi memakai rute aslinya, jadi `request()->route()` di sana bukan rute builder.
- **Record dari binding rute**, bukan pencarian manual empat skenario (ID, JSON, teks, `LIKE`) seperti `mount()` lama.
- Helper di view memakai `#[Computed]`, bukan method `public` (bisa dipanggil browser sebagai aksi) maupun `private` (kemungkinan tak terjangkau dari view).
- `ContentDocument::fromRaw()` menggantikan ±70 baris di `mount()` lama **dan** salinannya di `page-preview`, termasuk pembukaan JSON-di-dalam-JSON, format seeder lama, dan auto-migrasi `settings`. ID hantu di `order` kini dibuang.

## 4. Peta lama → baru

| `page-editor` lama | `content.builder` |
|---|---|
| `?Page $page` | `?Model $record` + `#[Locked] $type` |
| `$page_title` | `$titles` |
| `$slug`, `$meta_title`, `$meta_description` | sama (`localeMap()` menggantikan blok decode berulang) |
| `$content`, `$blockOrder`, `$settings` | sama (diisi dari `ContentDocument`) |
| `$status`, aturan `offline,online` | `ContentType::statuses()`; `?? 'draft'` yang keliru jadi `defaultStatus()` |
| `$isEditMode` | `$record->exists` |
| `mount($pageSlug)` (4 skenario) | binding rute + `ContentType` |
| `save($isPreview)` | `save()`; redirect ke rute edit dengan `navigate: true` |
| `saveAndPreview()` | tahap T3 (lihat catatan di bawah) |
| `handleMediaSelection` | dipertahankan, kini memvalidasi path dari browser |
| `searchInternalPages` | pindah ke trait (tahap T3) |
| `$layoutMode`, `$singleActiveLang`, `$splitLanguages` | `$store.editor.lang` (keadaan tampilan murni: tak perlu ke server) |
| sprite SVG di view | pindah ke layout |
| `Livewire.hook('commit')` | `Livewire.intercept($wire, …)` (tercakup per komponen; belum diverifikasi apakah dibersihkan otomatis) |

**Catatan pratinjau.** `saveAndPreview()` menyimpan ke record yang sedang online, sehingga setengah suntingan langsung tayang. Alternatif: simpan salinan kerja ke cache dengan token (`Cache::put("preview:$token", $dokumen, now()->addMinutes(30))`), dan buat `page-preview` membaca `?token=` bila ada. Itu juga yang membuat pratinjau artikel dan snippet mungkin.

## 5. Tahap migrasi

Prinsip: editor lama tetap hidup di rutenya sendiri sampai T7. **Jangan membuka record yang sama di dua editor** (penyimpanan terakhir yang menang).

**T1: Rute, rangka, judul.**
Salin `app/Content`, `⚡builder.blade.php`, `snippets/*`. Daftarkan rute `v2`. Pasang kunci terjemahan dan layout.
*Gerbang:* keempat rute membuka rangka; `<title>` benar di masing-masing; klik `wire:navigate` antar-rute (buat halaman → tulis artikel → edit halaman) mengganti judul dan jenis tanpa sisa; tombol Back tetap benar.

**T2: Paritas Page.**
Buka satu halaman nyata di v2, simpan **tanpa mengubah apa pun**, bandingkan JSON `content` sebelum dan sesudah.
*Gerbang:* JSON identik (kecuali ID hantu yang dibuang dan `settings.toc_position` yang diisi). Simpan halaman baru memindahkan Anda ke rute edit dengan judul benar.

**T3: Header, metadata, bahasa, pratinjau.**
Pindahkan tab Metadata/Konten, kontrol bahasa (ke `$store.editor.lang`), tombol pratinjau (berbasis token), dan `searchInternalPages` ke trait. Tambahkan `dirty` dan penjaga meninggalkan halaman (`snippets/app.js.snippet`).
*Gerbang:* pratinjau tidak mengubah record yang online; meninggalkan halaman dengan perubahan memunculkan konfirmasi.

**T4: Artikel.**
Isi `TODO` di `save()`: kategori, penulis, gambar unggulan (idealnya `featured_media_id`), jadwal terbit, tag, dan alur status enam langkah.
*Gerbang:* satu artikel nyata bisa dibuat, ditinjau, dijadwalkan, dan dibuka lagi di v2 dengan data utuh.

**T5: Snippet.**
Model, migrasi, dan penjaga hapus sudah ada dan teruji: lihat `SNIPPET.md`. Tersisa blok `snippet` di editor dan renderer.
*Gerbang:* satu snippet (mis. CTA donasi) dibuat, disisipkan di halaman, dan tampil di akhir halaman lain sebagai penutup.

**T6: Outline, kanvas, inspektur.**
Terapkan kit editor (`app/Editor` + `resources/views/components/editor`, Fase 2): outline dan kanvas dirender klien, inspektur memakai registri.
*Gerbang:* pada halaman 50 langkah, angka DOM dan Alpine jauh di bawah angka dasar di editor lama.

**T7: Cutover.**
Hapus awalan `v2`, arahkan daftar halaman/artikel ke builder, simpan editor lama dua sampai tiga minggu, lalu hapus.
*Gerbang:* tidak ada rute atau tautan yang masih menunjuk ke `page-editor`.

## 6. Risiko yang harus dicek di Livewire asli

1. **Satu komponen, empat rute, `wire:navigate`.** Pastikan berpindah antar-rute memuat ulang komponen (jenis dan judul berganti), bukan memakai ulang instance lama.
2. **Tombol Back/Forward.** Dokumentasi menyebut navigasi riwayat bisa memakai halaman dari cache (`context.cached`). Uji: edit, simpan, keluar ke daftar, tekan Back. Apakah editor menampilkan data terbaru atau yang lama?
3. **Listener di `document` menumpuk** antar-navigasi (peringatan di dokumentasi). `Livewire.hook('commit')` di `<script>` view dan JS `pageEditor` Anda termasuk. Daftarkan sekali di `app.js` atau pakai `data-navigate-once`.
4. **Store `editor` bertahan antar-navigasi.** Dibersihkan lewat `livewire:navigated` dan `x-init="$store.editor.clear()"`. Cek bahwa panel properti tidak membawa fokus dari halaman sebelumnya.
5. **`request()->route($param)` mengembalikan model** (bukan ID) di `mount()`. Cek di rute edit. Bila tidak, ganti dengan parameter bertipe di `mount(Page $page = null, ...)`.
6. **`new ($type->modelClass())()` untuk `Snippet`**: modelnya kini ada (`SNIPPET.md`); pastikan namespace `App\Models\Snippet` sesuai proyek.

## 7. Yang saya temukan di model Page/Post (untuk dikerjakan sambil jalan)

- `content` ditandai `Translatable` pada `Page` sementara isinya `{blocks, order, settings}`. Akibatnya, menetapkan array itu ke `$page->content` ditafsirkan sebagai terjemahan per bahasa, dan membaca `$page->content` memberi terjemahan locale aktif (kemungkinan kosong). Itu sebabnya `mount()` lama memakai `toArray()` dan `page-preview` memakai decode ganda. Keluarkan `content` dari `Translatable` dan normalkan data lama dengan satu migrasi. Sampai itu selesai, rangka memakai jalur `toArray()` yang sama dengan editor lama.
- `Post` memakai atribut `#[Translatable]` tanpa trait `HasTranslations` (tidak berfungsi), dan properti `$translateable` salah eja.
- `LogsActivity` pada `Page` tanpa `getActivitylogOptions()`; pada `Post`, log mencatat `content` utuh tiap simpan.
