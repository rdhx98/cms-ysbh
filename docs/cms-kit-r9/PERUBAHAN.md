# Perubahan per rilis

## Rilis 9: perbaikan JSON-LD FAQ (`@context`) dan pemindai tabrakan direktif

**Urutan rilis:** … → 8 → 9. Hanya **1 berkas yang diganti** (di bawah), tanpa berkas baru. Salin lalu `php artisan view:clear`. Tidak ada rute, `app.js`, migrasi, atau `npm run build`.

### Bug: JSON-LD FAQPage kehilangan `"@context"`
Dilaporkan dari mode dev: blok `application/ld+json` berisi `{"<?php $__contextArgs = []; if (context()->has(...`. Penyebab: Blade memproses direktif (`@nama`) **sebelum** ekspresi echo, dan Laravel 12 versi terbaru (diuji 12.69.3) memiliki direktif **`@context`**. Teks `'@context'` di dalam `{!! json_encode([...]) !!}` pun dikompilasi sebagai direktif, sehingga properti `@context` hilang dan Google tidak bisa membaca data terstruktur itu. Perbaikan: JSON-LD disusun di dalam blok `@php … @endphp` (tidak diproses sebagai direktif) lalu dicetak sebagai variabel.

Mengapa lolos pengujian: lab saya memakai Laravel **12.0.0**, yang belum punya direktif itu, dan pengujian lama hanya memeriksa sebagian kunci JSON-LD. Kini seluruh pengujian Blade dijalankan di **Laravel 12.69.3**, pengujian JSON-LD membandingkan kumpulan kunci persis, dan ada pemindai statis baru.

### Berubah (1 berkas)
| Berkas | Perubahan |
|---|---|
| `resources/views/components/blocks/render/accordion-builder.blade.php` | JSON-LD disusun di dalam `@php … @endphp`; hanya itu |

### Baru di zip (bukan berkas instal)
- `tests/blade-scan.php`: memindai **seluruh** berkas Blade sebuah proyek untuk teks `@nama` (di dalam `{{ }}`, `{!! !!}`, atau tanda kutip) yang sama dengan nama direktif Laravel. Jalankan: `php tests\blade-scan.php C:\jalur\ke\proyek` (README langkah 5). Ini juga memeriksa Blade Anda sendiri; bila layout Anda punya JSON-LD (`"@context"`) di dalam `{!! !!}`, pemindai akan menandainya.
- `docs/DEBUG-RINGKAS.md`: ringkasan debug 6c–6h dengan satu halaman uji.

### Pemeriksaan
`docs/DEBUG.md` bagian 6d (butir JSON-LD diperbarui) dan tabel galat (baris baru).

## Rilis 8: blok Video (YouTube / Vimeo)

**Urutan rilis:** … → 7 → 8. Rilis 8 hanya **menambah 4 berkas baru, tidak mengubah berkas lama mana pun**, dan tidak tumpang tindih dengan rilis sebelumnya. Salin lalu `php artisan optimize:clear`. Tidak ada rute, `app.js`, migrasi, atau JavaScript baru (`npm run build` tidak perlu).

### Yang perlu diketahui
- **Tanpa video di server**: hanya sematan YouTube/Vimeo (keputusan 2026-10-07; lihat `docs/JENIS-BERKAS.md`).
- **Privasi dan kecepatan**: sebelum diklik tidak ada iframe dan tidak ada permintaan ke pihak ketiga; setelah diklik, video dimuat dari domain mode privasi (`youtube-nocookie.com`, Vimeo `dnt=1`). Tanpa JavaScript, fasad menjadi tautan ke halaman video.
- **Kelas rasio layar** (`aspect-video`, dst.) ada di berkas PHP `VideoStyle.php`. Tailwind v4 memindai seluruh proyek secara bawaan; bila CSS Anda membatasi pemindaian, tambahkan `@source '../../app';`. Gejalanya: fasad video berupa garis tipis tanpa tinggi.
- Bila situs Anda memakai **Content-Security-Policy**, izinkan `frame-src https://www.youtube-nocookie.com https://player.vimeo.com`.

### Baru (4 berkas, tidak menimpa apa pun)
| Berkas | Isi |
|---|---|
| `app/Content/Blocks/VideoStyle.php` | rasio layar dan lebar → kelas Tailwind (daftar tetap) |
| `app/Content/Blocks/VideoUrl.php` | penguraian alamat YouTube/Vimeo → ID; alamat sematan dan tonton DIBANGUN ULANG dari ID (alamat penulis tidak pernah dipasang) |
| `app/Editor/Blocks/VideoBlock.php` | **blok Video** (modul): definisi, penempatan, pembersih |
| `resources/views/components/blocks/render/video-builder.blade.php` | tampilan **publik**: fasad klik-untuk-memutar, iframe hanya setelah klik |

### Pemeriksaan
`docs/DEBUG.md` bagian **6h**; panduan `docs/BLOK-VIDEO.md`; `tests/video-test.php` (101 pemeriksaan, termasuk uji acak 6000 alamat) ada di zip.

## Rilis 7: blok Callout, dan perbaikan nomor darurat

**Urutan rilis:** … → 6 → 7. Rilis 7 tidak tumpang tindih dengan berkas rilis 6, jadi bisa dipasang kapan saja setelah atau bersamaan dengan rilis 6. Salin 4 berkas di bawah lalu `php artisan optimize:clear`. Tidak ada rute, `app.js`, migrasi, atau JavaScript baru (`npm run build` tidak perlu).

### Perbaikan: nomor darurat 119 ditolak
Tautan telepon (blok Tombol sejak rilis 2) menolak nomor kurang dari 5 angka, sehingga **119, 112, dan 110 ditolak** dan tombol "Hubungi 119" tidak akan pernah tampil. Kini minimal 3 angka. Ditemukan saat menguji Callout dengan contoh nyata ("Hubungi 119"). Bila ada tombol telepon bernomor tiga angka yang sudah Anda buat, simpan ulang halamannya setelah memasang rilis ini.

### Baru (3 berkas, tidak menimpa apa pun)
| Berkas | Isi |
|---|---|
| `app/Content/Blocks/CalloutStyle.php` | peta gaya jenis × gaya → kelas Tailwind, ikon bawaan per jenis (SVG sendiri), nama jenis untuk pembaca layar |
| `app/Editor/Blocks/CalloutBlock.php` | **blok Callout / Catatan** (modul): definisi, penempatan, pembersih |
| `resources/views/components/blocks/render/callout-builder.blade.php` | tampilan **publik** Callout |

### Berubah (1 berkas)
| Berkas | Perubahan |
|---|---|
| `app/Content/Links/LinkResolver.php` | **nomor telepon minimal 3 angka** (sebelumnya 5): nomor darurat 119 / 112 / 110 kini sah. Memengaruhi blok Tombol juga |

### Pemeriksaan
`docs/DEBUG.md` bagian **6g**; panduan `docs/BLOK-CALLOUT.md`. Tentang filter jenis berkas (`image`, `pdf`, dan apakah perlu `audio`/`video`): `docs/JENIS-BERKAS.md`.

## Rilis 6: blok Daftar Unduhan, dan pratinjau versi tersimpan

**Urutan rilis:** … → 5 → 6. Berkas bernama sama di beberapa rilis: pakai yang terbaru. Salin berkas di bawah, tambahkan **satu baris rute**, lalu `php artisan optimize:clear`. Tidak ada `app.js`, migrasi, atau perubahan JavaScript baru, jadi `npm run build` tidak perlu.

### Satu baris rute (di dalam grup `v2` yang sudah ada)
```php
Route::livewire('/preview/{type}/{id}', 'content.record-preview')->name('preview.record')->whereIn('type', ['page', 'article', 'snippet'])->whereNumber('id');
```

### Baru (7 berkas, tidak menimpa apa pun)
| Berkas | Isi |
|---|---|
| `app/Content/Blocks/DownloadList.php` | memilih bahasa, mengurutkan, dan mengelompokkan butir; butir tak lengkap |
| `app/Content/Blocks/DownloadsStyle.php` | peta gaya → kelas Tailwind (daftar tetap) |
| `app/Content/Blocks/FileInfo.php` | ukuran ("1,2 MB") dan jenis berkas (PDF/Word/Excel/…); pencarian berkas dari model Media dalam satu query |
| `app/Content/SavedPreview.php` | bahan pratinjau untuk record yang SUDAH tersimpan (nilai mentah database, dibersihkan) |
| `app/Editor/Blocks/DownloadsBlock.php` | **blok Daftar Unduhan** (modul): definisi, penempatan, pembersih |
| `resources/views/components/blocks/render/downloads-builder.blade.php` | tampilan **publik** Daftar Unduhan |
| `resources/views/components/content/⚡record-preview.blade.php` | halaman **pratinjau tersimpan**: `/v2/preview/{page|article|snippet}/{id}` |

### Berubah (3 berkas)
| Berkas | Perubahan |
|---|---|
| `resources/views/components/content/canvas.blade.php` | + tombol "pratinjau versi tersimpan" (hanya untuk record yang sudah tersimpan) |
| `resources/views/components/content/⚡builder.blade.php` | + `savedPreviewUrl()` dan meneruskannya ke kanvas |
| `resources/views/components/editor/media.blade.php` | `accept=""` = pemilih TANPA filter jenis dan menampilkan nama berkas yang dipilih. Bidang gambar lama (`accept` bawaan `image`) **tidak berubah** |

### Bila Anda sudah mengubah `⚡builder.blade.php` sendiri
Jangan ditimpa; ada dua sisipan: (1) tambahkan metode `savedPreviewUrl()` (salin dari berkas rilis ini; letaknya tepat di atas `previewFrameUrl()`), (2) ganti `<x-content.canvas :frame-url="$this->previewFrameUrl" :locales="$activeLocales" />` dengan versi yang menambahkan `:saved-url="$this->savedPreviewUrl"`.

### Jawaban atas "bagaimana mengakses pratinjau penuh dari model tersimpan"
- **Halaman**: `/page/preview/<id>` (rute Anda yang sudah ada; `?mode=raw` untuk layout polos).
- **Halaman, artikel, snippet**: `/v2/preview/page/<id>`, `/v2/preview/article/<id>`, `/v2/preview/snippet/<id>` (baru, memakai mesin yang sama dengan kanvas). Di builder, tombol **database** di bilah kanvas membukanya untuk record yang sedang diedit. Lihat `docs/KANVAS.md`.

### Pemeriksaan
`docs/DEBUG.md` bagian **6e** (Daftar unduhan) dan **6f** (pratinjau tersimpan); dua butir 6d yang belum tercentang kini punya langkah ("Cara").

## Rilis 5: perbaikan dari uji kanvas (layout bingkai, tombol, TOC)

**Urutan rilis:** 3 → 4 → 5. Berkas bernama sama di beberapa rilis: **pakai yang terbaru**. Rilis 5 hanya **mengganti 5 berkas, tanpa berkas baru**, lalu `php artisan view:clear` dan `npm run build` (canvas.js berubah). Tidak ada rute, `app.js`, atau migrasi baru.

### Yang diperbaiki
1. **Editor di dalam editor.** Bingkai kanvas memakai `layouts.landing.dynamic-preview`, yang tanpa `?mode=raw` memilih `layouts.app` (sidebar, bilah atas, File Manager). Kini bingkai memakai `layouts.landing.index` (polos). Rute pratinjau penuh Anda tidak berubah.
2. **Blok Tombol tidak muncul di kanvas.** Renderer melewati tombol tanpa teks atau tanpa tautan sah (benar untuk situs publik). Di kanvas, tombol yang baru ditambah memang belum punya tautan, jadi tidak tampak. Sekarang di kanvas tombol belum lengkap **tampil pudar dengan tepi putus-putus** (dengan penjelasan di title). Situs publik tidak berubah.
3. **Blok yang tidak mencetak apa pun** (mis. FAQ tanpa pertanyaan) kini tampil sebagai kotak "masih kosong" di kanvas, sehingga tetap bisa diklik.
4. **TOC tidak tampil di kanvas.** Itu perilaku situs: wadahnya `hidden 2xl:block` (hanya ≥ 1536 px). Kini ada ukuran **Layar lebar** (1600 px, diperkecil agar muat) untuk melihatnya. Efek samping yang baik: tablet (820 px) di panel yang lebih sempit tidak lagi terpotong, melainkan diperkecil.

### Berubah (5 berkas, tidak ada yang baru)
| Berkas | Perubahan |
|---|---|
| `resources/js/canvas.js` | ukuran **Layar lebar** (1600 px); ukuran yang melebihi panel diperkecil, bukan terpotong |
| `resources/views/components/blocks/render/button-builder.blade.php` | di kanvas, tombol yang belum lengkap tetap tampak (pudar, tepi putus-putus); situs publik tidak berubah |
| `resources/views/components/content/canvas.blade.php` | tombol ukuran keempat; iframe diperkecil mengikuti lebar panel |
| `resources/views/components/content/sections.blade.php` | di kanvas, blok yang tidak mencetak apa pun diberi kotak "masih kosong" agar tetap bisa dipilih |
| `resources/views/components/content/⚡canvas-frame.blade.php` | **layout bingkai: `layouts.landing.index`** (polos). Menghilangkan sidebar/bilah atas admin di dalam kanvas ("editor di dalam editor") |

Hanya `canvas.js` dan `canvas.blade.php` yang saling bergantung (ukuran baru); tiga berkas lain berdiri sendiri.

### Pemeriksaan
`docs/DEBUG.md` bagian **6c** (diperbarui, termasuk cara memeriksa izin iframe dan cara memastikan halaman online tidak berubah).

## Rilis 4: blok Akordion / FAQ, dan blok menjadi MODUL

**Cara memasang:** salin berkas di bawah, lalu `php artisan optimize:clear` dan `npm run build` (editor.js berubah). Tidak ada rute atau `app.js` baru, tidak ada migrasi. **Bila rilis 3 belum selesai Anda salin, pasang rilis 4 saja**: `sections.blade.php` dan `BlockSanitizer.php` di sini sudah memuat semua isi versi rilis 3.

> Pesan chat memuat hanya berkas baru/berubah (identik bayt demi bayt dengan zip ini). Hanya **5 berkas lama** yang diganti, dan itu **sekali ini saja**: sesudahnya, blok baru tidak mengubah berkas lama lagi.

### Mengapa blok menjadi modul
Sebelumnya menambah satu blok berarti mengubah tiga berkas lama (registri, palet, pembersih). Sekarang cukup **dua berkas baru**: satu kelas modul di `app/Editor/Blocks/` dan satu tampilan publik. Registri, menu tambah blok, dan pembersih data menemukannya otomatis. Lima blok berikutnya (Daftar unduhan, Callout, Video, Galeri, Artikel terbaru) tinggal berkas baru. Lihat `docs/RESEP-BLOK.md`.

### Baru (6 berkas, tidak menimpa apa pun)
| Berkas | Isi |
|---|---|
| `app/Content/Blocks/AccordionStyle.php` | peta gaya Akordion → kelas Tailwind (daftar tetap) |
| `app/Content/Blocks/FaqText.php` | teks jawaban → HTML aman (paragraf, daftar, tautan https) |
| `app/Editor/Blocks/AccordionBlock.php` | **blok Akordion / FAQ** (modul pertama) |
| `app/Editor/Blocks/BlockModule.php` | kontrak sebuah blok sebagai modul (definisi, penempatan, pembersih) |
| `app/Editor/Modules.php` | penemu modul: setiap `app/Editor/Blocks/*Block.php` otomatis terdaftar |
| `resources/views/components/blocks/render/accordion-builder.blade.php` | tampilan **publik** Akordion (`<details>`, tanpa JavaScript) |

### Berubah (5 berkas)
| Berkas | Perubahan |
|---|---|
| `app/Content/Blocks/BlockSanitizer.php` | `forType()` dan `clean()` memanggil pembersih modul; + helper `itemId`, `singleLines`, `multiLines` (**memperbarui versi rilis 3**) |
| `app/Editor/BlockPalette.php` | daftar tipe = bawaan + modul (menu atas dan menu di dalam kolom) |
| `app/Editor/BlockRegistry.php` | + menggabungkan blok modul (3 baris; tipe bawaan tidak berubah) |
| `resources/js/editor.js` | ringkasan baris daftar berulang memakai `label`/`question`/`title`/`name` |
| `resources/views/components/content/sections.blade.php` | + 1 baris: membagikan `$canvasMode` ke komponen render (**memperbarui versi rilis 3**) |

Perilaku semua tipe bawaan **tidak berubah** (urutan menu, ikon, zona kolom, pembersih Tombol), dan itu diuji ulang. Hanya satu efek yang terlihat: menu tambah blok kini memuat **Akordion / FAQ** di akhir grup Konten.

### Bila Anda sudah mengubah `BlockRegistry.php`, `BlockPalette.php`, atau `BlockSanitizer.php`
Jangan ditimpa. Sambungannya kecil:
- `BlockRegistry::build()`, sebelum `return $all;`: `foreach (class_exists(Modules::class) ? Modules::all() : [] as $module) { $def = $module::definition(); $all[$def->panelKey()] ??= $def; }`
- `BlockPalette`: ganti konstanta `TYPES` dengan `types()` (bawaan + modul) dan `COLUMN_CHILDREN` dengan `columnChildren()`; salin dari berkas rilis ini.
- `BlockSanitizer::clean()`: panggil `forType()` (salin dari berkas rilis ini).

### Pemeriksaan
`docs/DEBUG.md` bagian **6d**; panduan `docs/BLOK-AKORDION.md`. `tests/accordion-test.php` (52 pemeriksaan) ada di zip.

## Rilis 3: kanvas (pratinjau langsung), tab bahasa, pencarian tautan internal

**Cara memasang:** (1) salin berkas di tabel di bawah, (2) tambahkan **dua** potongan kecil (rute dan `app.js`), (3) `php artisan view:clear` dan `npm run build`. Tidak ada migrasi.

> **Cara membaca rilis ini.** Pesan chat memuat **hanya berkas baru dan yang berubah** (sama persis, bayt demi bayt, dengan isi zip rilis ini). Zip = arsip lengkap. Sebagian besar berkas di bawah **baru**, jadi tidak ada yang tertimpa; hanya 6 berkas lama yang diganti.

### Dua potongan yang ditambahkan sendiri
```php
// routes/web.php, DI DALAM grup v2 yang sudah ada:
Route::livewire('/preview/{token}', 'content.canvas-frame')->name('preview.frame');
```
```js
// resources/js/app.js: ganti baris registerEditor yang ada dengan:
import { registerEditor } from './editor'
import { registerCanvas } from './canvas'
document.addEventListener('alpine:init', () => { registerEditor(window.Alpine); registerCanvas(window.Alpine) })
```

### Baru (9 berkas, tidak menimpa apa pun)
| Berkas | Isi |
|---|---|
| `app/Content/PreviewStore.php` | titipan pratinjau di cache: token 40 heksa, hanya pemilik, 30 menit, data dibersihkan, batas 2 MB |
| `app/Content/SectionBuilder.php` | pengelompokan seksi + daftar isi, ekstraksi persis dari `page-preview` (diuji terhadap logika aslinya) |
| `app/Livewire/Traits/SearchesInternalPages.php` | `searchInternalPages($keyword)` dari page-editor lama, dipindahkan ke trait (tahan baris lama, slug per bahasa) |
| `resources/js/canvas.js` | logika panel kanvas (Alpine `canvasPane`): titip isi, bahasa, ukuran, pilih blok |
| `resources/views/components/content/body.blade.php` | `<x-content.body>`: pengganti `{!! $article->content !!}` (blok atau HTML lama) |
| `resources/views/components/content/canvas.blade.php` | panel tengah builder: bilah alat + iframe |
| `resources/views/components/content/sections.blade.php` | mesin render seksi + daftar isi (mode publik dan kanvas) |
| `resources/views/components/content/⚡canvas-frame.blade.php` | halaman bingkai di dalam iframe (Livewire); protokol pesan dengan editor |
| `resources/views/components/editor/lang-tabs.blade.php` | tab bahasa kolom isian: Ganda / ID / EN |

### Berubah (6 berkas)
| Berkas | Perubahan |
|---|---|
| `app/Content/Blocks/BlockSanitizer.php` | regex id/ikon: tambah modifier `D` |
| `app/Content/ContentDocument.php` | regex kunci snippet dan peta bahasa: tambah modifier `D` |
| `app/Content/JsonSql.php` | regex nama kolom: tambah modifier `D` |
| `app/Content/Links/LinkResolver.php` | regex anchor: tambah modifier `D` |
| `app/Content/Slug.php` | `PATTERN` : tambah modifier `D` |
| `resources/views/components/content/⚡builder.blade.php` | tab bahasa, kanvas, aksi `publishPreview`, `previewFrameUrl`, trait `SearchesInternalPages` (lihat "Bila Anda sudah mengubah builder") |

Lima berkas teratas hanya menambah modifier `D` pada pola regex. Alasannya: tanpa `D`, tanda `$` juga cocok sebelum **baris baru di ujung teks**, sehingga `"slug\n"` atau token `"…\n"` lolos validasi dari permintaan yang dibuat tangan. Nilai sah tidak terpengaruh. Aman disalin kapan saja.

### Bila Anda sudah mengubah `⚡builder.blade.php` sendiri
Jangan ditimpa; ada lima sisipan:
1. di bagian `use` atas: `use App\Content\PreviewStore;`, `use App\Livewire\Traits\SearchesInternalPages;`, `use Livewire\Attributes\Renderless;`
2. di kelas, bersama trait lain: `use SearchesInternalPages;`
3. ganti komentar `// TODO (Fase E): …` dengan dua metode `publishPreview()` dan `previewFrameUrl()` (salin dari berkas rilis ini)
4. di `<header>`, ganti komentar `{{-- TODO: tab bahasa … --}}` dengan `<x-editor.lang-tabs :locales="$activeLocales" />`
5. ganti `<section class="overflow-y-auto p-4">Kanvas</section>` dengan `<x-content.canvas :frame-url="$this->previewFrameUrl" :locales="$activeLocales" />`

### Dua baris di halaman artikel publik Anda (perlu, bukan opsional)
Lihat `docs/ARTIKEL.md`, bagian terakhir: `{!! $article->content !!}` dan `{!! $article->title !!}` harus diganti, atau artikel dari builder menghasilkan "Array to string conversion".

### Pemeriksaan
`docs/DEBUG.md` bagian **6c**; panduan lengkap di `docs/KANVAS.md`.

## Rilis 2: daftar berulang (repeater) dan blok Tombol

**Cara memasang:** salin isi `app/`, `database/`, `resources/` (Replace), lalu `php artisan view:clear` dan `npm run build` (editor.js berubah). Tidak ada berkas yang perlu dihapus dan tidak ada migrasi baru.

### Baru (10 berkas)
| Berkas | Isi |
|---|---|
| `app/Content/Blocks/BlockSanitizer.php` | pembersih data blok (Tombol): kelas, tautan, panjang, jumlah |
| `app/Content/Blocks/ButtonStyle.php` | peta gaya tombol → kelas Tailwind (36 kombinasi, daftar tetap) |
| `app/Content/JsonSql.php` | SQL kolom JSON per bahasa yang tahan baris lama (dipakai pencarian dan aturan unik) |
| `app/Content/Links/LinkResolver.php` | tautan → URL aman (http/https, mailto, tel, /jalur, #anchor; `javascript:` dkk ditolak) |
| `app/Editor/Defaults.php` | penanda `@id` / `@locales` pada nilai bawaan blok |
| `app/Editor/Rel.php` | path dinamis berbasis indeks untuk kontrol di dalam daftar berulang |
| `app/Livewire/Traits/SearchesLinkTargets.php` | aksi `searchLinkTargets()` untuk pemilih tautan |
| `resources/views/components/blocks/render/button-builder.blade.php` | tampilan **publik** blok Tombol |
| `resources/views/components/editor/link.blade.php` | kontrol pemilih tautan |
| `resources/views/components/editor/repeater.blade.php` | kontrol daftar berulang (tambah/hapus/duplikat/urut) |

### Berubah (19 berkas)
| Berkas | Perubahan |
|---|---|
| `app/Content/ContentWriter.php` | membersihkan data blok sebelum disimpan (`BlockSanitizer`); menerima bahasa aktif |
| `app/Content/Rules/UniqueLocaleValue.php` | memakai `JsonSql` (perilaku sama) |
| `app/Editor/BlockPalette.php` | + Tombol (menu atas dan di dalam kolom) |
| `app/Editor/BlockRegistry.php` | + definisi blok Tombol |
| `app/Editor/BlockType.php` | + `defaults` (nilai bawaan dari registri) |
| `app/Editor/Field.php` | + `repeater`, `link`; `icon` bisa dikosongkan |
| `app/Livewire/Traits/ManagesBlockStructure.php` | blok baru memakai nilai bawaan **registri** bila ada (tidak perlu mengubah trait lama) |
| `resources/js/editor.js` | + `repeater`, `linkField`; **perbaikan balapan** penulisan tertunda (lihat di bawah); `wireField` bisa diperluas |
| `resources/views/components/content/⚡builder.blade.php` | `use SearchesLinkTargets`; mengirim bahasa aktif ke penulis |
| `resources/views/components/editor/field.blade.php` | + tipe `repeater` dan `link`; path dinamis di dalam item |
| `resources/views/components/editor/i18n.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/icon-picker.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/media.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/rich.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/segmented.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/select.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/swatches.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/text.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/toggle.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |

### Bila Anda sudah mengubah `⚡builder.blade.php` sendiri
Jangan ditimpa. Cukup tiga tambahan kecil:
1. di bagian `use` atas: `use App\Livewire\Traits\SearchesLinkTargets;`
2. di dalam kelas, di samping `use ManagesBlockStructure;`: `use SearchesLinkTargets;`
3. di `save()`, di dalam larik `ContentWriter::fill(...)`, tambahkan baris `'locales' => $this->activeLocales,`

### Yang perlu diperiksa di aplikasi Anda
`docs/DEBUG.md` bagian **6b**. Hal yang hanya bisa dipastikan di Livewire dan layout Anda: pencarian halaman/artikel (`$wire.searchLinkTargets`), File Manager untuk tombol berkas, dan cara `page-preview` memanggil komponen render publik.

### Catatan teknis
- **Perbaikan balapan di `editor.js` (`typeAt`).** Penulisan "live" yang tertunda 0,8 detik menimpa **nilai lama** ke **path lama**. Di inspektur biasa path-nya tetap, jadi aman. Di dalam daftar berulang path berbasis indeks: mengetik lalu langsung menaikkan item, atau menghapus item di atasnya, menimpa teks item lain atau membuat item hantu. Kini yang dikirim adalah nilai yang **sekarang** ada di path itu, dan dibatalkan bila path hilang. Teruji dengan dua skenario nyata.
- Semua kontrol (`segmented`, `i18n`, `toggle`, dst.) kini menerima `rel-expr`. Perilaku di inspektur tidak berubah.
- Pemeriksaan baru yang berguna untuk Anda sendiri: setelah mengubah Blade, jalankan di PowerShell
  `php artisan view:cache` lalu `Get-ChildItem storage\framework\views\*.php | ForEach-Object { $r = php -l $_.FullName 2>&1; if ($LASTEXITCODE -ne 0) { $r } }`.
  `php -l` langsung pada berkas `.blade.php` **tidak** menangkap galat yang baru muncul setelah Blade dikompilasi (mis. ternary bersarang tanpa kurung).

## Rilis 1 — 2026-10-07 (penggabungan dua kit + perbaikan dari uji Anda)

**Penggabungan:** `editor-kit`, `content-builder`, `media-manager-backend`, `blade-components`, dan `patch-has-content-blocks` kini satu pohon folder Laravel dalam satu zip.
Berkas lepas dan zip lama sudah dihapus dari folder output.

### Berkas yang BERUBAH dibanding lampiran terakhir (copy yang ini bila hanya ingin memperbarui)
| Berkas | Perubahan |
|---|---|
| `app/Content/Names.php` | **baru**: nama kategori/tag yang berupa JSON per bahasa → teks (bahasa aktif → id → en → apa pun) |
| `app/Content/TagResolver.php` | **ditulis ulang**: tag ber-JSON per bahasa dikenali; tag baru dibuat **per bahasa** (`{"id":"X","en":"X"}`, slug sama), bukan string JSON `"X"`; mencocokkan nama bahasa mana pun atau slug |
| `app/Content/Rules/UniqueLocaleValue.php` | **menggantikan** `UniqueSlug.php` (**hapus yang lama**): kini juga untuk judul artikel (`posts.title` punya indeks unik) |
| `app/Content/ContentRules.php` | judul artikel unik per bahasa; memakai `UniqueLocaleValue` |
| `app/Content/ContentWriter.php` | `syncRelations()` menerima bahasa aktif |
| `app/Editor/Options.php` | nama berupa peta bahasa tidak lagi dianggap definisi opsi. **Perbaikan dropdown kategori yang hanya menampilkan id** |
| `resources/js/editor.js` | `fitViewport`: tinggi editor = sisa tinggi layar (tidak ada bilah gulir di halaman) |
| `resources/views/components/content/⚡builder.blade.php` | `categoryOptions`/`tagOptions` memakai `Names` (menggantikan perbaikan Anda; hasilnya sama, ditambah pengurutan); tinggi mengikuti layar; pelanggaran indeks unik jadi pesan di tab Halaman, bukan galat 500 |

### Berkas yang HARUS dihapus
`app/Content/Rules/UniqueSlug.php` (digantikan `UniqueLocaleValue.php`), `resources/views/components/editor/outline-sementara.blade.php` (bila masih ada).

### Catatan
- **Kategori dan tag memakai JSON per bahasa** (cast `array`), bukan string. Rilis sebelumnya salah menduga.
- Tag yang Anda buat dari builder sebelum rilis ini tersimpan sebagai string JSON (`"Malaria"`). Tetap terbaca dan dikenali sebagai tag lama; boleh dibiarkan.
- `tests/content-test.php` bertambah pemeriksaan `Names`.
