# Daftar cek saat debug `content.builder`

Centang berurutan. Setiap baris: **lakukan → hasil yang benar → bila salah, periksa**.
Kirimkan ke saya bagian "Bila masih macet" (di akhir) bersama hasil yang tidak cocok.

## 0. Persiapan (hindari mengejar hantu)
- [ ] `APP_DEBUG=true` di `.env` (panel debug editor hanya muncul dengan ini).
- [ ] `php artisan optimize:clear` dan `php artisan view:clear` (Blade lama tersimpan di cache).
- [ ] Vite berjalan (`npm run dev`), atau `npm run build` diulang setelah `editor.js` / kelas Tailwind baru. Muat ulang keras (Ctrl+Shift+R).
- [ ] DevTools terbuka: tab **Console** dan **Network** (centang *Preserve log*, filter `livewire`).
- [ ] Ekor log di terminal lain: `Get-Content .\storage\logs\laravel.log -Tail 40 -Wait` (PowerShell).

## 1. Dua menit pertama (di `/v2/page/create`)
Tempel di Console:

| Perintah | Hasil yang benar | Bila tidak |
|---|---|---|
| `Alpine.store('editor')` | objek `{id, panel, base, lang, ...}` | `editor.js` tidak terdaftar di `app.js` / belum di-build |
| `document.querySelectorAll('svg[data-icon-sprite] symbol').length` | **≥ 75** | **0** = sprite belum dipasang di layout → ikon outline dan menu kosong |
| `!!document.getElementById('icon-heading-1')` | `true` | ikon palet tidak ada di paket Lucide Anda (dilewati diam-diam) atau sprite tidak ada |
| `document.querySelectorAll('*').length` | catat angkanya | (angka dasar untuk perbandingan performa) |
| `document.querySelectorAll('[x-data]').length` | catat angkanya | (idem) |
| Panel debug pojok kanan bawah | `● debug editor: utuh` | merah = ada yatim/hantu: buka panelnya, lihat daftar |

> Hasil Anda: jumlah simbol = **0** dan `icon-heading-1` = `false`. Sprite belum ada di halaman. Perbaikannya: tempel `<x-editor.icon-sprite />` di layout yang dipakai builder (lihat `PASANG.md` bagian 2). Hitung ulang: harus ≥ 75, dan `icon-heading-1` harus `true`.

## 2. Outline (kiri)
Untuk setiap baris: hitung request di Network (endpoint update Livewire, `POST .../livewire.../update`).
Isi request: tab *Payload* → `components[0].calls[0].method` dan `params`.

| Lakukan | Request | Hasil yang benar |
|---|---|---|
| Klik baris blok | **0** | panel kanan berganti; baris tersorot |
| `+ Tambah blok` → Judul | **1** (`addBlockAt`, params `[null, null, "heading"]`) | blok baru muncul di bawah dan **langsung terfokus** |
| `+ Tambah blok` → Kolom, lalu `Tambah ke Kolom 1` → Paragraf | 1 (`addBlockAt`, params `["blk_…", "col_1_zone", "paragraph"]`) | anak masuk ke kolom 1 |
| Menu di dalam kolom | – | **tidak** menawarkan Kolom / Step / Pemisah Seksi / Kartu Builder |
| ↑ / ↓ | 1 (`moveBlock`) | bertukar dengan tetangga **di zonanya**; ujung = tombol nonaktif |
| ⋯ (rilis 43: sisip di atas/bawah, pindah, masuk/keluar) | lihat 6zi | `docs/OUTLINE.md` |
| Duplikat blok Kolom yang berisi anak | 1 (`duplicateBlock`) | salinan di bawahnya, anaknya ber-ID **baru** |
| Hapus blok berisi anak | konfirmasi dulu, lalu 1 (`removeBlock`) | semua anak ikut hilang; panel debug tetap "utuh" |

Bila Duplikat/Hapus meninggalkan yatim (panel debug merah): `HasContentBlocks` bukan versi patch.
Bila `moveBlock`/`addBlockAt` "tidak ditemukan": `ManagesBlockStructure` belum di-`use` di builder.

## 3. Inspektur (kanan) dan data
- [ ] Klik blok Judul → mengetik di kolom ID **tidak** membuat request selagi mengetik; mode live (bawaan) mengirim **satu** request ±0,8 dtk setelah berhenti.
- [ ] Label baris outline ikut berubah **seketika** saat mengetik judul (tanpa request).
- [ ] Klik H1 atau H3 pada blok Judul → buka **panel debug** (pojok kanan bawah, klik judulnya) dan lihat bagian `terfokus`: `data.level` harus berganti ke `h1`/`h3`. Blok lain tidak berubah. *(Tanpa konsol: panel debug menampilkan data blok yang sedang difokus.)*
- [ ] Mengetik `<b>x</b>` di kolom Judul tampil sebagai teks `<b>x</b>` (bukan tebal), tersimpan ter-escape (`&lt;b&gt;x&lt;/b&gt;`), dan kolom **tetap bisa diedit** (rilis 31: kini lewat Tiptap, lihat 6v).
- [ ] Kolom anchor: `Hello World!` → `hello-world`.
- [ ] Blok tipe `eyebrow` / `image` / `multi-columns`: panel kuning "belum punya panel" (wajar; belum didaftarkan).

## 4. Simpan, rute edit, dan keutuhan data
Tab **Halaman** (kolom kanan) berisi judul, slug, status, SEO. Memfokus blok memindahkan ke tab **Blok**.

### Cara memakai tinker (kalau belum pernah): 1 menit
Tinker adalah "layar perintah" untuk bertanya langsung ke aplikasi Anda. Tidak mengubah apa pun kecuali Anda menyuruhnya.
1. Buka terminal **di folder proyek** (VS Code: menu *Terminal → New Terminal*; pastikan jalurnya berakhir di nama proyek Anda).
2. Ketik `php artisan tinker` lalu Enter. Prompt berubah menjadi `>`.
3. Tempel **satu baris** di bawah ini lalu Enter. Hasilnya tampil tepat di bawahnya.
4. Ketik `exit` lalu Enter untuk keluar.

Satu baris yang berguna (ganti `5` dengan id halaman):
```php
\App\Models\Page::find(5)->only('title', 'slug', 'status', 'published_at')
```
Keutuhan isi halaman, **satu baris**:
```php
$d = \App\Content\ContentDocument::fromRaw(\App\Models\Page::find(5)->getRawOriginal('content')); [count($d->blocks), count($d->reachableIds()), array_values(array_diff(array_keys($d->blocks), $d->reachableIds()))]
```
Hasil benar: dua angka pertama **sama**, dan daftar terakhir **kosong** (`[]`) = tidak ada blok yatim.

**Tanpa tinker:** `php artisan model:show Page` (kolom, tipe, dan cast) atau aplikasi database Anda dengan
`SELECT id, status, published_at FROM pages ORDER BY id DESC LIMIT 5;`

### Halaman baru
- [ ] Ketik judul ID → slug ID terisi otomatis. Ketik langsung di kolom **slug**: spasi menjadi `-`, huruf kecil, aksen hilang (`Ibu & Anak` → `ibu-dan-anak`). `-` di ujung masih boleh saat mengetik, dan dirapikan saat Anda meninggalkan kolom.
- [ ] Ubah slug sendiri, lalu ketik judul lagi → slug **tidak** ditimpa. Kosongkan slug → otomatis aktif lagi.
- [ ] Simpan dengan judul kosong → banner merah dan panel pindah ke tab **Halaman**.
- [ ] Simpan lengkap → URL menjadi `/v2/page/edit/<id>`, header "Edit Halaman", judul tab "Ubah Halaman: …".
- [ ] Slug yang sama dengan halaman lain ditolak (per bahasa; bila kedua bahasa sama, kedua pesan muncul).
- [ ] `published_at` kosong saat Offline, terisi saat pertama Online, dan tidak bergeser saat disimpan lagi.

### Halaman yang sudah ada
- [ ] Judul/slug/status terisi. Mengetik judul **tidak** mengubah slug; peringatan kuning soal tautan lama tampil.
- [ ] Buka-simpan tanpa mengubah apa pun: kolom `content` tidak berubah (selain ID hantu dibuang dan `toc_position` terisi).

### Snippet (`/v2/snippet/make`)
- [ ] Nama, Kunci (`Hubungi Kami!` → `hubungi-kami`), catatan, status, penutup, urutan. Tanpa slug dan SEO.
- [ ] Urutan: kosongkan kolomnya → tersimpan `0` (bukan galat). Kunci ganda ditolak dengan pesan jelas.

### Artikel (`/v2/article/write`)
- [ ] Kategori: dropdown berisi kategori dari database; "— Pilih kategori —" = belum memilih.
- [ ] Tag: ketik lalu Enter atau koma; saran muncul dari tag yang ada; nama yang sama dengan tag lama (huruf besar/kecil tak berpengaruh) memakai tag lama, tidak menggandakan. Backspace pada kolom kosong menghapus tag terakhir; `×` menghapus tag tertentu. Minimal satu tag.
- [ ] Status untuk **penulis biasa**: hanya *Draf* dan *Ditinjau*. Untuk **admin/editor**: keenam status. (Dicek juga di server: memaksa `published` ditolak.)
- [ ] Simpan artikel baru → baris di `posts`, tag di `post_tags`, `featured_image` = `default.webp`, `user_id` = pengguna yang login, `published_at` terisi saat status *Terbit*.
- [ ] Tag baru yang diketik benar-benar dibuat di tabel `tags` (nama dan slug).
- [ ] **Buka artikel lama** (`/v2/article/edit/<id>` untuk artikel yang dibuat editor lama): banner kuning "Isi lama diimpor sebagai satu blok Paragraf"; isinya tampil utuh di blok itu. **Data di database tidak berubah** sampai Anda menekan Simpan.
- [ ] Setelah itu Simpan → isi menjadi dokumen blok. **Peringatan:** halaman publik artikel membaca `content` sebagai HTML; artikel yang disimpan dari builder baru tampil benar bila renderernya mendukung blok. Uji **hanya pada artikel percobaan**.

### Langkah terakhir yang membingungkan, dijelaskan ulang
*"Setelah redirect `navigate: true`, `Alpine.store('editor').tab` kembali `'page'` dan outline menampilkan blok yang tersimpan."*
Store `editor` hidup di **browser** dan bertahan saat `wire:navigate` berpindah halaman, tidak seperti komponennya yang dibuat baru. Tanpa pengaman, fokus blok dari halaman sebelumnya bisa ikut terbawa. Cara mencobanya:
1. Di halaman **baru**, tambahkan dua blok, lalu **klik salah satu** (panel kanan pindah ke tab *Blok*, blok tersorot).
2. Pindah ke tab *Halaman*, isi judul/slug, **klik sebuah blok lagi** (supaya fokus aktif), lalu tekan **Simpan**.
3. Setelah URL berubah ke `/v2/page/edit/<id>`, yang benar: panel kanan di tab **Halaman**, **tidak ada** blok tersorot, dan outline menampilkan **kedua blok** tadi.
4. Di Console: `Alpine.store('editor').tab` harus `'page'` dan `Alpine.store('editor').id` harus `null`.
Bila tab masih "Blok" atau ada blok tersorot, `x-init="$store.editor.clear()"` di akar builder tidak berjalan setelah navigasi: kirimkan hasilnya.

### Keutuhan di database
Pakai baris tinker di atas setelah beberapa kali menambah, memindah, menduplikat, dan menghapus blok, lalu Simpan.

## 5. Navigasi dan judul tab
- [ ] `/v2/page/create` → `/v2/article/write` lewat `wire:navigate`: judul tab, jenis, dan outline berganti, tanpa sisa dari halaman sebelumnya.
- [ ] Tombol Back/Forward: setelah edit → simpan → keluar → Back, editor menampilkan data **terbaru** (Livewire bisa memakai halaman dari cache).
- [ ] `Alpine.store('editor').panel` kembali `null` setelah pindah halaman.
- [ ] Listener tidak menumpuk: di Console Chrome `getEventListeners(document)['livewire:navigate']?.length` tetap **1** setelah beberapa kali pindah halaman.

## 6. Performa (bandingkan dengan angka dasar bagian 1)
- [ ] Ukuran respons satu request update (tab Network → *Size*) sesudah "Tambah blok". Bila ≥ ±100 KB, markup inspektur ikut terkirim tiap request → pindahkan ke `@island` atau muat sekali lewat route statis.
- [ ] Waktu dari klik "Tambah blok" sampai blok terlihat. Catat (konteks: shared hosting Hostinger bisa jauh lebih lambat dari Herd lokal).
- [ ] Tambah 50 blok ke Step Builder: jumlah elemen DOM dan `[x-data]` harus naik **linear kecil**, bukan ribuan per blok.

## 6b. Blok Tombol (di aplikasi sungguhan)
Sudah teruji di lab: kontrol, urutan, pemilih tautan, keamanan, dan tampilan. Yang **hanya** bisa dipastikan di Livewire dan layout Anda ada di daftar ini.

- [ ] `+ Tambah blok → Tombol`: blok muncul, tab *Blok* terbuka, daftar berisi satu tombol "(tanpa teks)". Di panel debug, `keutuhan` tetap "utuh".
- [ ] Buka tombol, ketik teks ID/EN: baris di daftar ikut berubah; mengetik lalu **langsung** menekan ↑/↓ tidak menukar atau menimpa teks tombol lain.
- [ ] **Halaman/Artikel**: ketik 2+ huruf judul → daftar hasil muncul. *(Ini memanggil `searchLinkTargets` lewat Livewire: bila daftar tetap kosong, buka Console dan Network; kirim isi responsnya.)* Pilih satu → muncul chip bernama; tombol hanya tampil di publik bila halamannya **online**.
- [ ] **Berkas**: "Pilih berkas" membuka File Manager; setelah memilih, nama berkas tampil. Di File Manager, berkas itu kini tercatat **Digunakan Di** halaman/artikel/snippet ini.
- [ ] **URL luar**: ketik `javascript:alert(1)` → peringatan kuning. Simpan, lalu cek kolom `content` di database: `ref` **kosong**.
- [ ] Simpan, muat ulang: semua tombol kembali sama, urutannya benar.
- [ ] Halaman publik: tombol tampil dengan warna/gaya yang dipilih. Tombol tanpa teks atau dengan tujuan offline **tidak** tampil. *(Bila blok tidak tampil sama sekali, periksa cara `page-preview` memanggil komponen render; lihat `docs/BLOK-TOMBOL.md`, bagian "Yang perlu diperhatikan".)*
- [ ] Ikon: pilih satu, "Posisi ikon" muncul; "Hapus ikon" mengosongkannya.

## 6c. Kanvas (di aplikasi sungguhan)
Sudah teruji di lab: pengelompokan seksi (3000 dokumen dibandingkan dengan logika asli), render, dan protokol pesan di browser. Yang **hanya** bisa dipastikan di Livewire dan layout Anda:

- [ ] Panel tengah menampilkan pratinjau (bukan petunjuk "Rute pratinjau belum dipasang"). Bila petunjuk itu muncul: rute `preview.frame` belum ada di grup `v2`.
- [ ] **Tanpa sidebar dan bilah atas admin di dalam kanvas.** Bingkai memakai layout polos `layouts.landing.index`. Bila sidebar/bilah atas admin muncul di dalam pratinjau ("editor di dalam editor"), bingkai memakai layout yang salah: `layouts.landing.dynamic-preview` **tanpa `?mode=raw`** memilih `layouts.app` (shell CMS). Periksa `#[Layout('layouts.landing.index')]` di `⚡canvas-frame.blade.php`.
- [ ] Status di bilah kanvas: "Memperbarui…" lalu "● Terkini". Di tab Network, **satu** permintaan `publishPreview` per jeda mengetik (bukan satu per huruf), dan responsnya **kecil** (hanya token).
- [ ] Isi bingkai tampil dengan gaya situs (font, warna). Bila polos, layout polos Anda tidak memuat CSS yang sama; kirim `layouts/landing/head.blade.php`.
- [ ] **Daftar isi (TOC)**: tidak tampil di panel biasa, **dan itu benar**: wadahnya `hidden 2xl:block`, hanya muncul bila lebar jendela ≥ 1536 px (sama seperti situs). Klik ikon **Layar lebar** (ikon sudut-sudut, paling kanan dari empat ukuran): bingkai berjendela 1600 px lalu diperkecil agar muat, dan TOC muncul bila halaman punya blok ber-anchor (judul atau pemisah seksi yang punya "ID Tautan").
- [ ] **Izin iframe (`X-Frame-Options` / `frame-ancestors`)**. Cara memeriksa: (1) bila pratinjau tampil di dalam panel, izinnya sudah benar dan butir ini lulus. (2) Bila panel putih/kosong: tekan F12, tab **Console**; pesan "Refused to display ... in a frame because it set 'X-Frame-Options' to 'deny'" memastikannya. (3) Untuk melihat headernya: tab **Network**, muat ulang, klik permintaan bertipe **document** bernama `<token>?lang=id`, lihat **Response Headers**. Yang boleh: `X-Frame-Options` tidak ada atau `SAMEORIGIN`; `Content-Security-Policy` tanpa `frame-ancestors` atau `frame-ancestors 'self'`. Yang salah: `DENY`.
- [ ] Ketik di kolom judul blok: pratinjau ikut berubah ±1 detik kemudian **tanpa berkedip dan tanpa melompat ke atas** (render ulang lewat `$wire.$refresh()`).
- [ ] Klik sebuah blok di pratinjau: blok itu terpilih di outline, tab pindah ke *Blok*, panel properti terbuka; garis putus-putus saat disorot. Klik tautan/tombol di dalam pratinjau **tidak** berpindah halaman.
- [ ] **Blok Tombol di kanvas**: tombol yang baru ditambah tampil **pudar dengan tepi putus-putus** (tanda belum lengkap: teks atau tujuan tautan kosong, atau tujuannya belum online/terbit); arahkan kursor untuk melihat penjelasannya. Setelah teks dan tautan terisi, tampil normal. Di **situs publik** tombol belum lengkap tetap **tidak tampil** (disengaja: tidak ada tombol mati).
- [ ] **Blok kosong**: blok yang tidak mencetak apa pun (mis. FAQ tanpa pertanyaan) tampil sebagai kotak "Blok ... masih kosong", sehingga tetap bisa diklik dan dipilih.
- [ ] Pilih blok dari outline: pratinjau menggulir ke blok itu dan memberinya garis hijau.
- [ ] Tombol EN/ID ("Lihat sebagai") mengganti bahasa pratinjau; tab header **ID** membuat pratinjau ikut ID; **Ganda** tidak mengubahnya.
- [ ] Ikon desktop/tablet/ponsel/lebar: lebar berubah; di lebar ponsel menu situs berubah menjadi versi ponsel (titik putus Tailwind ikut). Ukuran yang lebih lebar dari panel **diperkecil**, bukan terpotong.
- [ ] Ikon ↗: tab baru terbuka dengan halaman yang sama (layout polos, tanpa sidebar admin) dan tautan berfungsi.
- [ ] **Halaman online tidak berubah oleh pratinjau.** Langkah: (1) buka di builder sebuah halaman yang sudah online; (2) ubah teks sebuah judul, **jangan tekan Simpan**; (3) di tab lain buka alamat publik halaman itu: teks lama masih tampil; (4) di panel kanvas teks baru tampil; (5) bila ada aplikasi database, kolom `updated_at` halaman itu tidak berubah. Setelah tekan Simpan barulah situs berubah.
- [ ] Buka alamat bingkai (`/v2/preview/<token>`) dari akun lain atau tanpa masuk: 404 / pengalihan masuk.
- [ ] Cache: `CACHE_STORE` tidak boleh `array`; `database` (yang Anda pakai) atau `file` cukup. Bila pratinjau selalu "kedaluwarsa", periksa tabel `cache` ada (`php artisan cache:table` lalu `migrate`).

## 6d. Akordion / FAQ (di aplikasi sungguhan)
Teruji di lab: pembersih, teks → HTML (1500 teks acak), tampilan, perilaku `<details>` di Chromium, dan panel inspektur. Yang perlu dipastikan di aplikasi Anda:

- [ ] **Modul ditemukan**: di menu `+ Tambah blok` ada **Akordion / FAQ** (grup Konten), juga di menu di dalam kolom. Bila tidak ada: berkas harus persis `app/Editor/Blocks/AccordionBlock.php` dengan nama kelas `AccordionBlock`; lalu `composer dump-autoload` dan `php artisan optimize:clear`.
- [ ] Blok baru berisi satu pertanyaan kosong; tab Blok menampilkan "Pertanyaan (1)". Mengetik pertanyaan mengubah judul barisnya seketika.
- [ ] Jawaban: Enter membuat baris baru (kolom banyak-baris); simpan, muat ulang, baris barunya tetap ada.
- [ ] Di kanvas: semua pertanyaan **terbuka** dan jawabannya terlihat; mengklik blok memilihnya.
- [ ] Di halaman publik: pertanyaan tertutup; mengkliknya membuka dengan panah berputar; membuka yang lain menutup yang pertama (kecuali "Boleh membuka beberapa sekaligus" aktif).
  **Cara**: simpan halaman berisi blok FAQ, lalu buka `/v2/preview/page/<id>` (pratinjau tersimpan, rilis 6), atau `/page/preview/<id>?mode=raw` yang sudah ada, atau alamat publik halaman bila statusnya online.
- [ ] Papan ketik: Tab ke pertanyaan, Enter/Spasi membuka-tutup; ada cincin fokus.
- [ ] Ketik jawaban `usia <5 tahun & bayi` dan `<b>coba</b>`: tampil **apa adanya** di situs (bukan tebal, tidak terpotong).
- [ ] Ketik `https://ysbh.org.` di jawaban: menjadi tautan; titik di ujung kalimat **di luar** tautan.
- [ ] "Tandai untuk mesin pencari": lihat sumber halaman publik, ada `<script type="application/ld+json">` dengan `FAQPage`; tempel alamat halaman di _Rich Results Test_ Google untuk memeriksanya. Aktifkan hanya pada satu blok per halaman.
  **Cara**: buka halaman tersimpan seperti di atas, tekan Ctrl+U (lihat sumber), cari `ld+json`. Isinya harus diawali `{"@context":"https://schema.org","@type":"FAQPage",…`. **Bila Anda melihat `<?php`, `__contextArgs`, atau `context()->` di dalam blok itu, berkas `accordion-builder.blade.php` masih versi lama (sebelum rilis 9)**: Blade versi baru mengompilasi `@context` sebagai direktif. *Rich Results Test* Google **tidak bisa** membuka alamat lokal (`.test`, `localhost`): pakai tabnya **Kode** lalu tempel seluruh sumber halaman; setelah situs ada di server publik, tempel alamatnya.
- [ ] Kolom `content` di database tersimpan bersih: tidak ada gaya/warna di luar daftar, paling banyak 30 pertanyaan.

## 6e. Daftar unduhan (di aplikasi sungguhan)
Teruji di lab: pembersih, urut/kelompok, ukuran dan jenis berkas, tampilan, pencatatan "Digunakan Di", dan panel inspektur. Yang perlu dipastikan di aplikasi Anda:

- [ ] **Modul ditemukan**: di menu `+ Tambah blok` ada **Daftar Unduhan** (grup Konten), juga di dalam kolom.
- [ ] Blok baru berisi satu butir kosong; tab Blok menampilkan "Berkas (1)". Judul yang diketik muncul di baris butirnya.
- [ ] **File Manager**: tombol "Jelajahi File Manager" di dalam butir membuka File Manager. **Periksa jenis berkas yang ditawarkan**: harus semua berkas (PDF dan lainnya). Bila hanya gambar yang tampil, ganti `''` menjadi `'pdf'` di `app/Editor/Blocks/DownloadsBlock.php` (File Manager Anda mengenal filter `image` dan `pdf`; lihat `docs/JENIS-BERKAS.md`).
- [ ] Setelah memilih berkas, **nama berkas tampil di bawah tombol**, dan butir itu tidak lagi pudar di kanvas.
- [ ] Di kanvas: butir tanpa berkas/judul tampil **pudar bertepi putus-putus**; butir lengkap tampil dengan lencana jenis dan baris "tahun · jenis · ukuran".
- [ ] Di situs (atau `/v2/preview/page/<id>`): hanya butir lengkap; klik membuka/mengunduh berkas di tab baru; ukuran sesuai (mis. 1,2 MB).
- [ ] Atur Kelompokkan = Tahun: judul tahun menurun; Urutan = Judul A–Z mengurutkan wajar.
- [ ] Di File Manager, berkas yang dipakai blok ini tercatat di **Digunakan Di** (bekerja pada model yang memakai trait `SyncsMediaUsage`: Snippet di kit; Halaman/Artikel bila trait itu sudah Anda pasang di model tersebut) (halaman/artikel/snippet terkait). Hapus butirnya, simpan: pencatatan itu hilang.
- [ ] Pindahkan sebuah berkas ke Sampah di File Manager: butirnya **hilang dari situs** tanpa mengubah halaman.

## 6f. Pratinjau versi tersimpan (rilis 6)
- [ ] Rute `preview.record` terpasang (lihat `contoh-kode/routes.php`): `/v2/preview/page/<id>` menampilkan halaman tersimpan dengan pita kecil di pojok. Bila 404: rute belum ada di grup `v2`.
- [ ] Layout polos, **tanpa sidebar admin** (sama seperti kanvas). Bila sidebar muncul: periksa `#[Layout('layouts.landing.index')]` di `⚡record-preview.blade.php`.
- [ ] Halaman **offline** tetap bisa dibuka (pita menandai status "offline" berwarna kuning); halaman online bertanda hijau.
- [ ] `/v2/preview/article/<id>` menampilkan isi artikel; artikel lama (HTML) tampil sebagai satu blok teks, dan **kolom `content` di database tidak berubah**.
- [ ] `/v2/preview/snippet/<id>` menampilkan blok snippet.
- [ ] Tombol **database** di bilah kanvas: ada untuk record yang sudah tersimpan, **tidak ada** di halaman "Buat baru"; membuka tab baru yang sama dengan alamat di atas.
- [ ] Ubah sesuatu di builder **tanpa menyimpan**: tombol ↗ menampilkan perubahan, tombol database **tidak** (menampilkan yang tersimpan). Setelah Simpan, keduanya sama.
- [ ] Buka dari peramban yang belum masuk: pengalihan masuk / 403. Alamat ber-ID yang tidak ada: 404. `/v2/preview/user/1`: 404.
- [ ] Tautan **Edit** di pita membuka builder untuk record yang sama.

## 6g. Callout / Catatan (di aplikasi sungguhan)
Teruji di lab: pembersih, tampilan (jenis, gaya, ikon, aksi), pencatatan tautan aman, panel inspektur, dan jalur simpan/pratinjau. Yang perlu dipastikan di aplikasi Anda:

- [ ] **Modul ditemukan**: di menu `+ Tambah blok` ada **Callout / Catatan** (ikon megafon), juga di menu di dalam kolom.
- [ ] Blok baru kosong: di kanvas tampil kotak "masih kosong" (bisa dipilih); setelah mengisi judul atau isi, kotak berwarna tampil dengan ikon bawaan jenis.
- [ ] **Enam jenis × tiga gaya** tampil benar di situs (bukan hanya di kanvas): warnanya sama dengan yang di kanvas. Bila warna **polos/tanpa warna**, kelas Tailwind palet bawaan (`sky`, `emerald`, `amber`, `red`) tidak ada di build Anda: kirim berkas CSS/tema Tailwind.
- [ ] Gaya **Penuh + Peringatan**: teks gelap di atas kuning (bukan putih); gaya Penuh lain: teks putih.
- [ ] Isi: Enter membuat baris baru; `- ` menjadi daftar poin; `https://ysbh.org.` menjadi tautan dengan titik di luar. Ketik `usia <5 tahun`: tampil apa adanya.
- [ ] **Aksi "Hubungi 119"**: jenis Telepon, nilai `119`: di situs menjadi `tel:119` (di ponsel membuka penelepon). Nomor tiga angka sah sejak rilis 7.
- [ ] Aksi dengan **halaman offline** sebagai tujuan: di situs **tidak tampil**; di kanvas tampil pudar bertepi putus-putus.
- [ ] Pembaca layar atau mode kontras: kotak diumumkan sebagai catatan dengan nama jenisnya ("Peringatan: …").
- [ ] Ikon: "Tampilkan ikon" mati = tanpa ikon; pilih ikon lain (mis. **heart**) tampil di situs; "Hapus ikon" kembali ke ikon bawaan jenis.

## 6h. Video YouTube / Vimeo (di aplikasi sungguhan)
Teruji di lab: penguraian alamat (6000 alamat acak), tampilan, perilaku klik-untuk-memutar di browser (termasuk bukti tanpa permintaan jaringan sebelum klik), panel inspektur, dan jalur simpan. Yang perlu dipastikan di aplikasi Anda:

- [ ] **Modul ditemukan**: di menu `+ Tambah blok` ada **Video** (ikon video), juga di dalam kolom.
- [ ] Tempel `https://www.youtube.com/watch?v=…`, lalu `https://youtu.be/…`, lalu alamat Shorts: di kanvas tampil **fasad** (latar hijau, tombol putar, label "YouTube"). Tempel alamat salah (mis. situs lain): kotak penjelasan "hanya YouTube dan Vimeo".
- [ ] **Rasio tampil benar**: fasad berbentuk kotak 16:9 (bukan garis tipis tanpa tinggi). Bila tingginya nol, build Tailwind Anda tidak memindai folder `app/` (kelas rasio ada di `app/Content/Blocks/VideoStyle.php`): tambahkan `@source '../../app';` di CSS Anda.
- [ ] **Privasi**: di halaman publik (atau `/v2/preview/page/<id>`), buka DevTools → tab **Network**, muat ulang: **tidak ada** permintaan ke youtube, google, atau vimeo sebelum Anda mengklik. Tidak ada `<iframe>` di tab Elements.
- [ ] Klik fasad: video dimuat di tempat dan diputar. Di Network muncul `youtube-nocookie.com/embed/…`. Halaman tidak berpindah.
- [ ] Bila setelah klik kotak tetap kosong atau Console menyebut "Refused to frame": situs Anda memakai **Content-Security-Policy**; izinkan `frame-src https://www.youtube-nocookie.com https://player.vimeo.com`.
- [ ] Tab ke fasad lalu Enter: memutar; ada cincin fokus.
- [ ] Alamat dengan `?t=90`: video mulai di detik 90.
- [ ] Gambar sampul dari File Manager tampil di fasad; memilih berkas **PDF** sebagai sampul: diabaikan (kembali ke latar hijau).
- [ ] Rasio **9:16** + lebar **Sempit** untuk video vertikal (Shorts).
- [ ] Dua video di satu halaman: memutar yang kedua tidak memuat yang pertama.
- [ ] Alamat video privat/dihapus: pesan dari YouTube/Vimeo tampil di dalam kotak setelah klik (bukan galat CMS).

## 6i. Galeri / Logo (di aplikasi sungguhan)
Teruji di lab: pembersih, penyusun butir, tampilan (grid, carousel, logo, pembesar), perilaku di browser (jumlah gambar per layar di ponsel/tablet/desktop, panah, geser, papan ketik, berjalan otomatis, "kurangi gerakan", pembesar), panel inspektur, dan jalur simpan. Yang perlu dipastikan di aplikasi Anda:

- [ ] **Modul ditemukan**: di menu `+ Tambah blok` ada **Galeri / Logo**, juga di dalam kolom. (Ikon palet `images` bisa kosong bila paket Lucide Anda lama; itu tidak merusak apa pun.)
- [ ] **Jelajahi File Manager** di dalam butir menawarkan **hanya gambar**. Pilih gambar: di kanvas butir itu berhenti pudar dan gambarnya tampil.
- [ ] Grid Foto: 2 kolom di ponsel, 3 di tablet, sesuai pilihan di desktop (coba kolom 4).
- [ ] **Carousel**: ganti Tampilan ke Carousel dengan lebih banyak gambar daripada kolom. Panah **Sebelumnya/Berikutnya** muncul dan bergeser tepat satu gambar; di ponsel bisa digeser dengan jari dan berhenti rapi di gambar. Bila gambar bertumpuk atau lebarnya penuh: kelas `basis-[calc(…)]` tidak dihasilkan; tambahkan `@source '../../app';` di CSS Anda.
- [ ] Carousel dengan gambar yang muat semua (mis. 3 gambar, 4 kolom): panah **tidak tampil**.
- [ ] **Berjalan otomatis** (sakelar): bergeser sendiri; berhenti saat kursor di atas galeri; tombol **Jeda** berfungsi. Di Windows: Pengaturan → Aksesibilitas → Efek visual → matikan "Efek animasi", muat ulang: carousel **tidak** berjalan sendiri.
- [ ] **Logo mitra**: logo tampil utuh di kotak putih; "Logo hitam-putih" membuatnya abu-abu dan berwarna saat disorot; logo bertautan membuka situs mitra di tab baru.
- [ ] **Pembesar**: klik foto membuka tampilan besar (latar gelap) dengan keterangannya; panah kiri/kanan berpindah foto (melingkar); **Esc** menutup dan fokus kembali ke foto tadi; klik latar gelap menutup. Foto yang bertautan membuka tautannya, bukan pembesar.
- [ ] Tautan ke **halaman yang belum online**: logo tetap tampil (tanpa tautan); di kanvas ditandai.
- [ ] "Digunakan Di" di File Manager mencatat gambar galeri (butuh trait `SyncsMediaUsage` pada model Halaman/Artikel).
- [ ] Kecepatan: foto besar (≥ 2 MB) memperlambat halaman; unggah yang sudah dikecilkan.

## 6j. Dua aplikasi: alamat publik dan sinkron ke landing (rilis 11)
- [ ] **CMS: tautan ke halaman/artikel tidak lagi menimbulkan galat.** Di builder buat Tombol/Callout/Galeri dengan tautan jenis Halaman atau Artikel (yang online/terbit), lalu buka kanvas dan pratinjau tersimpan: tidak ada galat "Route [page.show] not defined". Tanpa konfigurasi `cms.public`, tautan itu hanya tidak tampil.
- [ ] Tambahkan `cms.public` (`contoh-kode/config-dua-aplikasi.php`) dan `CMS_PUBLIC_URL=https://…` di CMS. Arahkan kursor ke tombol bertautan halaman di pratinjau tersimpan: alamatnya **absolut ke domain landing** (`https://ysbh.org/…`), sesuai bahasa pratinjau.
- [ ] Halaman offline / artikel draf sebagai tujuan tautan: tombol itu tidak tampil di situs (di kanvas ditandai pudar), bukan galat.
- [ ] `php tools\sync-landing.php D:\Herd\landing-ysbh` (mode periksa) menampilkan berkas BARU pada landing yang belum disinkron; `--apply` menyalinnya; menjalankan periksa lagi menghasilkan "0 baru, 0 berbeda".
- [ ] Folder yang bukan proyek Laravel ditolak dengan pesan jelas.
- [ ] Di landing, `php artisan view:clear` lalu buka halaman yang berisi blok FAQ, Unduhan, Callout, Video, dan Galeri: semuanya tampil. Galat "Class … not found" = ada berkas yang belum tersinkron; jalankan sinkron lagi.
- [ ] Uji kontrak di landing: `route('article.show', 'x', absolute: false)` sama dengan templat `cms.public.article`.
- [ ] Gambar: unggah satu gambar lewat CMS; ia tampil di halaman landing dengan URL `https://ysbh.org/storage/…` (bukan subdomain CMS), dan tetap tampil bila subdomain CMS dimatikan sementara.

## 6k. Artikel Terbaru, snippet di situs publik, dan landing (rilis 12)
- [ ] **Modul ditemukan**: di menu `+ Tambah blok` ada **Artikel Terbaru** (ikon koran), juga di dalam kolom.
- [ ] Tambahkan blok itu pada sebuah halaman, atur jumlah 3: di **kanvas** tampil kartu artikel terbit sungguhan (judul, kategori, tanggal, ringkasan). Tidak ada artikel terbit: tampil keterangan "belum ada artikel berstatus terbit".
- [ ] **Sampul**: tanpa `cms.public.cover` di `config/cms.php`, kartu tampil dengan latar hijau (aman). Setelah mengisinya dengan templat yang benar (`/storage/posts/{file}` atau sesuai folder Anda), sampul artikel tampil; artikel bersampul `default.webp` tetap berlatar hijau, bukan gambar rusak.
- [ ] Klik kartu di situs menuju `…/artikel/<slug>` di landing (setelah rute `article.show` ada). Di CMS, tautan kartu absolut ke landing bila `CMS_PUBLIC_URL` diatur.
- [ ] Ringkasan: artikel dengan `meta_description` memakainya; tanpa itu, paragraf pertama (tanpa HTML). Artikel lama berisi HTML tetap punya ringkasan yang rapi (tanpa kata menempel).
- [ ] Di halaman **artikel**, "Artikel Terbaru" tidak memuat artikel yang sedang dibuka (hanya bila halaman membagikan `currentArticleId`, seperti contoh `article-show`).
- [ ] **Snippet penutup di situs**: buat satu snippet berstatus online dan bertanda "penutup" (mis. ajakan donasi); buka halaman publik (atau `/v2/preview/page/<id>`): snippet muncul di **akhir** halaman. Atur penutup halaman itu ke "tanpa penutup": hilang. Snippet yang offline tidak tampil. **Di rilis ≤ 11 snippet penutup tidak tampil di situs publik sama sekali.**
- [ ] Landing: `php tools\sync-landing.php D:\Herd\landing-ysbh` menampilkan tujuh berkas BARU dibanding rilis 11 (termasuk `PublicLookup.php`); `--apply` menyalinnya; di landing sediakan model `Snippet` dan `Category` (contoh baca-saja: `landing-app/app/Models/*.php`).
- [ ] Landing: model `Media` memakai `SoftDeletes`: hapus satu gambar di File Manager CMS (ke Sampah), muat ulang halaman publik: gambar itu tidak lagi tampil.
- [ ] Landing: `findPage` dan `findArticle` lewat contoh `page-show` / `article-show`: slug tak ada, halaman offline, dan artikel draf menghasilkan 404, bukan galat.
- [ ] Gambar lewat folder bersama (lihat `DUA-APLIKASI.md`): unggah satu gambar di CMS, buka `https://ysbh.org/storage/<path>`, dan pastikan berkas `.php` uji di folder itu **tidak** dijalankan.

## 6l. Landing tahap 1 (rilis 13)
> Sejak rilis 24 alamat `/artikel` dan `/articles` (301) sudah diganti: `/articles` (EN) dan `/id/artikel` (ID); slug `articles` kini boleh; semua kunci config per bahasa. Gunakan bagian 6q; butir di bawah yang menyebut alamat lama sudah usang.
- [ ] **Pemeriksa landing**: `php tools\check-landing.php D:\Herd\landing-ysbh` berakhir `==> 0 galat`. Setiap GALAT berisi berkas dan petunjuknya; PERINGATAN boleh ada tetapi sebaiknya dibereskan.
- [ ] **Menu tidak lagi menjatuhkan situs**: buat satu baris di tabel `navigations` dengan `route_name` salah ketik (mis. `tidak-ada`) dan `url` `/tentang-kami`: situs tetap tampil, menu menuju `/tentang-kami`. Baris tanpa keduanya menuju `#`.
- [ ] Menu bernama rute `programs` ikut **aktif** (bergaris) di halaman `/programs/malaria`.
- [ ] Menu berisi `#kontak`, `mailto:` atau `tel:` tidak pernah tampil sebagai menu aktif.
- [ ] **Berkedip**: muat ulang halaman dengan jaringan diperlambat (DevTools → Network → Slow 3G): menu mobile dan nav lengket tidak muncul sesaat sebelum menghilang.
- [ ] Pilihan warna **Charcoal** pada blok Akordion/Unduhan tampil berwarna (bukan polos).
- [ ] `/tentang-kami` (halaman CMS online) tampil dengan judul dan paragraf. **Judul/paragraf kosong = renderer `heading`/`paragraph` belum disalin dari CMS.**
- [ ] Halaman CMS offline, slug tidak ada, dan `/artikel/tidak-ada` menampilkan halaman 404 (bukan galat).
- [ ] `/artikel` menampilkan kartu artikel terbit (9 per halaman) dan penomoran; `/artikel?page=99` menampilkan 404; `/articles` dialihkan ke `/artikel`.
- [ ] Judul tab: halaman CMS berjudul "{judul} | {nama aplikasi}", beranda tetap "Sinar Bhakti Husada". Lihat sumber halaman (Ctrl+U): ada `<meta name="description">`, `rel="canonical"`, dan `og:title`.
- [ ] Ubah `landing_bg_color` di tabel `settings` menjadi `red;}body{display:none` : latar kembali ke bawaan `#FBF7EA` (nilai tak sah diabaikan). Lalu pulihkan.
- [ ] Halaman statis lama (`/about`, `/contact`, `/programs/...`) tetap jalan, dan `/{slug}` tidak menimpanya.

## 6m. Slug terlarang dan kepala daftar artikel (rilis 15)
> Sejak rilis 24 alamat `/artikel` dan `/articles` (301) sudah diganti: `/articles` (EN) dan `/id/artikel` (ID); slug `articles` kini boleh; semua kunci config per bahasa. Gunakan bagian 6q; butir di bawah yang menyebut alamat lama sudah usang.
- [ ] **CMS**: tambahkan `reserved_slugs` (rilis 23: `[]`), `home_slug`, dan `articles_index_slug` ke `config/cms.php` (lihat `contoh-kode/config-slug-terlarang.php`), lalu `php artisan optimize:clear`.
- [ ] Buat halaman baru ber-slug `about` (atau `contact`, `programs`): simpan **ditolak** dengan pesan "dipakai oleh alamat tetap situs". Slug biasa (`tentang-kami`) tersimpan.
- [ ] Slug terlarang di salah satu bahasa saja (mis. EN `contact`, ID `kontak`): ditolak untuk bahasa yang bermasalah, dengan nama "Slug (EN)".
- [ ] **Artikel** ber-slug `about` tetap boleh (alamatnya `/artikel/about`).
- [ ] Slug `artikel` untuk halaman **diizinkan**; slug `articles` ditolak.
- [ ] `php artisan cms:audit-slugs`: tanpa konflik menampilkan "Tidak ada halaman dengan slug terlarang." Buat konflik lewat basis data (ubah slug sebuah halaman menjadi `about` langsung di tabel): perintah menampilkannya dan berakhir dengan kode 1; mengedit halaman itu di editor menuntut penggantian slug.
- [ ] Buat halaman CMS ber-slug `artikel`, online, berjudul "Kabar Terbaru", dengan satu paragraf pengantar, dan snippet penutup bawaan aktif. Buka `/artikel`: judul "Kabar Terbaru", pengantar di bawahnya, **lalu** kartu artikel, **lalu** penomoran, **lalu** snippet penutup (bukan di antara pengantar dan daftar).
- [ ] `/artikel?page=2`: **tanpa** pengantar; judul tab bernomor "(halaman 2)"; sumber halaman (Ctrl+U) memuat `rel="canonical"` yang berakhir `?page=2`.
- [ ] Jadikan halaman `artikel` offline: `/artikel` kembali ke judul bawaan "Artikel" dan daftar polos, tanpa galat.
- [ ] Judul SEO dan deskripsi SEO halaman `artikel` muncul di `<title>` dan `<meta name="description">` `/artikel`.
- [ ] `php tools\check-landing.php D:\Herd\landing-ysbh`: tidak ada PERINGATAN tentang rute statis atau `articles_index_slug`. Tambahkan sementara `Route::view('/uji', ...)` ke `web.php` tanpa mencatatnya: pemeriksa memberi PERINGATAN; hapus kembali.

## 6n. Peta situs dan robots.txt (rilis 17)
> Sejak rilis 24 alamat `/artikel` dan `/articles` (301) sudah diganti: `/articles` (EN) dan `/id/artikel` (ID); slug `articles` kini boleh; semua kunci config per bahasa. Gunakan bagian 6q; butir di bawah yang menyebut alamat lama sudah usang.
- [ ] `.env` landing: `APP_URL=https://domain-anda` (tanpa jalur), lalu `php artisan config:clear`.
- [ ] Buka `/sitemap.xml`: tampil sebagai XML (bukan halaman 404/HTML), berisi `<loc>https://domain-anda/...` dengan **domain asli, bukan localhost**.
- [ ] Ada alamat untuk setiap halaman statis (`/`, `/about`, `/programs/malaria`, ...) dan `/artikel`.
- [ ] Sebuah halaman CMS online dengan slug berbeda per bahasa muncul dua kali (mis. `/tentang-kami` dan `/about-us`); halaman **offline** dan artikel **draf** tidak muncul.
- [ ] Halaman CMS `artikel` (kepala daftar) tidak membuat alamat `/artikel` ganda.
- [ ] Buat halaman CMS baru dan terbitkan: muncul di peta situs paling lambat 10 menit kemudian.
- [ ] Ubah `APP_URL` sementara menjadi `https://domain-anda/jalur` (tidak sah): peta situs tetap berisi alamat yang benar (memakai alamat permintaan), tidak kosong. Pulihkan.
- [ ] `/robots.txt` menampilkan baris `Sitemap: https://domain-anda/sitemap.xml` dan tidak berisi `Disallow: /`.
- [ ] Google Search Console, menu Sitemaps: kirim `sitemap.xml`; statusnya "Berhasil" dan jumlah halaman terbaca sama dengan yang Anda harapkan.
- [ ] `php tools\check-landing.php D:\Herd\landing-ysbh`: tidak ada PERINGATAN tentang `sitemap_static` atau robots.txt. Tambahkan sementara `Route::view('/uji', ...)` ke `web.php`: ada dua PERINGATAN (slug terlarang dan peta situs); hapus kembali.

## 6o. Penyaring isi publik (rilis 18)
Mulai dari sinkron ke landing (`tools\sync-landing.php --apply`, kini 47 berkas), salin tujuh renderer dan daftar isi dari CMS, lalu `php artisan view:clear`.
- [ ] `php tests\run-all.php` berakhir `==> N ok, 0 gagal` (sesudah `php tests\lab\setup.php`).
- [ ] Halaman CMS online dengan judul dan paragraf berformat (tebal, miring, warna, ukuran huruf, "pill", daftar, tautan): tampilannya di situs publik **sama** dengan pratinjau tersimpan di CMS.
- [ ] Di editor, buat tautan ke halaman lain lewat dialog "halaman internal", simpan, lalu klik tautan itu di situs publik: menuju alamat halaman itu (bukan `internal://...`).
- [ ] Di kartu atau tombol kartu, tautan internal juga mengarah ke alamat yang benar.
- [ ] Eyebrow dengan ikon dan warna pilihan editor tampil normal.
- [ ] **Uji penyaring** (hanya di lingkungan uji): ubah isi satu paragraf langsung di basis data menjadi `<p>a</p><script>alert(1)</script><img src=x onerror=alert(1)>`. Di situs publik: tidak ada dialog, tidak ada `<script>`/`onerror` di sumber halaman (Ctrl+U), teks "a" tetap. Di editor, isinya **tidak berubah** (penyaringan hanya pada tampilan). Pulihkan isinya.
- [ ] Ubah `icon` sebuah eyebrow di basis data menjadi `x::y`: situs publik **tetap tampil** (ikon `newspaper`), tidak 500. Pulihkan.
- [ ] Artikel lama yang berisi tabel: tabel tampil. Artikel lama dengan sematan YouTube (`<iframe>`): sematannya tidak tampil di situs publik (diketahui; pakai blok Video).
- [ ] Sumber halaman publik (Ctrl+U): tidak ada atribut `x-data`, `x-init`, `@click`, atau `wire:` yang berasal dari isi konten (yang berasal dari tata letak situs sendiri wajar).

## 6p. Daftar isi dan anchor (rilis 20)
- [ ] Salin `table-of-contents.blade.php` dari `landing-app/` ke **landing dan CMS**, lalu `php artisan view:clear` di keduanya.
- [ ] Halaman dengan beberapa heading ber-anchor, dibuka di layar lebar (>= 1536 px): kartu daftar isi tampil, **tanpa teks `[cite: 1]`** di akhir setiap judul.
- [ ] Klik sebuah judul di daftar isi: halaman menggulir ke heading itu, dan judul yang sedang dibaca ditandai tebal berwarna hijau saat digulir.
- [ ] Judul kartu: situs berbahasa Inggris menampilkan "Contents", situs berbahasa Indonesia "Daftar Isi".
- [ ] **Uji anchor jahat** (hanya di lingkungan uji): di editor, isi anchor sebuah heading dengan `x');alert(1);('` lalu simpan dan buka pratinjau tersimpan. Tidak ada dialog; anchor tersimpan menjadi `x-alert-1`. Isi `Beban Kasus`: menjadi `beban-kasus`. Anchor yang sudah sah (`beban-kasus`) tidak berubah.
- [ ] `php tools\check-landing.php D:\Herd\landing-ysbh`: bagian [5b] hijau. Salin sementara versi LAMA daftar isi dari CMS ke landing: pemeriksa memberi GALAT dengan petunjuk `@js`; pulihkan.

## 6q. Dua bahasa (rilis 24)
Pasang CMS dan landing dari zip yang sama, samakan `config/cms.php` (per bahasa; `BAHASA.md`), `php artisan optimize:clear` di keduanya, `npm run build`.
- [ ] `php tests\run-all.php --strict` berakhir `==> 33 ok, 0 gagal` (sesudah `php tests\lab\setup.php`).
- [ ] `php tools\check-landing.php D:\Herd\landing-ysbh`: tidak ada GALAT. Bagian bahasa memeriksa `public.page|article|home|articles`, `home_slug`, `articles_index_slug`, dan `default_locale`.
- [ ] Buat halaman **home** (slug EN `home`, ID `beranda`, judul di kedua bahasa, online). `/` menampilkannya dalam bahasa Inggris dan `/id` dalam bahasa Indonesia. Sebelum dibuat: kedua alamat menjawab 503, bukan 404.
- [ ] `/home` dan `/id/beranda` dialihkan 301 ke `/` dan `/id`.
- [ ] Halaman dengan slug `about-us` (EN) dan `tentang-kami` (ID): `/about-us` dan `/id/tentang-kami` tampil; `/tentang-kami` dialihkan 301 ke `/about-us`; `/id/about-us` dialihkan 301 ke `/id/tentang-kami`.
- [ ] Halaman yang hanya punya slug ID: `/id/<slug>` tampil; `/<slug>` dan `/id` tidak menampilkan salinannya (404 untuk bahasa EN).
- [ ] `/en/about-us` menjawab 404.
- [ ] Pengalih bahasa di header (desktop dan lembar menu ponsel) menuju versi bahasa lain **halaman yang sedang dibuka**; di halaman yang belum diterjemahkan menuju beranda bahasa itu. Tidak ada kedip atau pemuatan ulang tanpa tujuan (tautan biasa).
- [ ] Sumber halaman (Ctrl+U) di halaman dua bahasa: `<link rel="alternate" hreflang="en">`, `hreflang="id"`, dan `hreflang="x-default"` (= alamat EN), alamatnya saling timbal-balik. Halaman satu bahasa: tanpa tag hreflang.
- [ ] `<html lang>` mengikuti bahasa alamat (layout `app` milik Anda harus memakai `str_replace('_', '-', app()->getLocale())`; kit tidak menyentuh layout itu); tanggal artikel "Oct 8, 2026" di EN dan "8 Okt 2026" di ID.
- [ ] Menu: satu baris `navigations` dengan `url` = `/about-us` menuju `/about-us` di EN dan `/id/tentang-kami` di ID. Baris `url` = `/` menuju `/` dan `/id`. Baris `url` = `/articles` menuju daftar artikel bahasa pembaca. Menu aktif ditandai benar, termasuk di beranda.
- [ ] `/articles` dan `/id/artikel`: hanya artikel yang sudah diterjemahkan ke bahasa itu; penomoran (`?page=2`) konsisten; kartu tidak pernah menuju 404. Kepala daftar dari halaman CMS `articles` / `artikel`.
- [ ] Artikel `/articles/<slug-en>` dan `/id/artikel/<slug-id>` tampil; slug bahasa lain dialihkan 301; artikel yang belum diterjemahkan menjawab 404 di bahasa itu.
- [ ] Blok **Artikel Terbaru** di halaman EN: kartu menuju `/articles/...`; di halaman ID menuju `/id/artikel/...`.
- [ ] Tautan internal (`internal://page/...`) di teks Inggris menuju versi Inggris halaman itu; di teks Indonesia menuju `/id/...`.
- [ ] `/sitemap.xml`: setiap versi bahasa adalah satu `<loc>` (beranda `/` dan `/id`, bukan `/home`), ditambah `/articles` dan `/id/artikel`; halaman yang belum diterjemahkan tidak muncul di bahasa itu.
- [ ] Halaman 404 dan 503 mengikuti bahasa alamat (`/id/tidak-ada` berbahasa Indonesia; `/tidak-ada` berbahasa Inggris). Tombol "kembali ke beranda" menuju beranda bahasa itu.
- [ ] Aksi Livewire di halaman `/id/...` (mis. paginasi) tetap berbahasa Indonesia setelah permintaan pembaruan (bahasa dipulihkan dari properti terkunci).
- [ ] **CMS**: menyimpan halaman EN dengan slug `id` ditolak; slug `articles` diizinkan; `php artisan cms:audit-slugs` bersih; `php artisan cms:audit-translations` mendaftar halaman yang belum atau setengah diterjemahkan (kode keluar 1 untuk "separuh"/"isi").
- [ ] **CMS**: tombol/tautan ke halaman di blok menghasilkan alamat landing per bahasa (`https://ysbh.org/about-us` dan `https://ysbh.org/id/tentang-kami`), bukan alamat CMS.

## 6r. Halaman situs dan penanda (rilis 25, isi rilis 26)
- [ ] `php artisan migrate:status`: semuanya **Ran** (terutama `create_snippets_table`). Bila ada **Pending**, `php artisan migrate`, lalu lanjut.
- [ ] `php artisan cms:seed-pages` menampilkan tabel 12 halaman + snippet `legal-details`, tanpa membuat apa pun. Bila `reserved_slugs` masih berisi slug lama, perintah berhenti dengan daftar slug yang ditolak. Halaman yang sudah ada karena rilis 25 ditandai "ADA, dilewati".
- [ ] Bila halaman kerangka rilis 25 sudah ada (satu paragraf `[ISI-DULU]`): hapus ke-12 halaman itu di admin (semuanya offline dan kosong), lalu `php artisan cms:seed-pages --apply`. Bila belum ada: langsung `--apply`. Hasil: 12 halaman **offline** dan snippet `legal-details` offline. Bila ada galat per halaman (kolom wajib lain di tabel `pages`), kirimkan pesannya. Jalankan lagi: semuanya "ADA, dilewati".
- [ ] Di admin, halaman Malaria: judul "Malaria Program" (EN) / "Program Malaria" (ID), slug `malaria-program` / `program-malaria`, isi **banyak blok** (judul "Together Towards a Malaria-Free Papua" / "Bersama Mewujudkan Tanah Papua Bebas Malaria", paragraf, daftar, keterangan foto berpenanda). Ganti ke bahasa ID di editor: isi sejajar, tidak ada blok kosong.
- [ ] Pratinjau satu halaman program (`/v2/preview/page/<id>`): judul besar H1 hanya satu, daftar dan tabel tampil rapi, tautan di Beranda dan indeks program menuju halaman program yang benar di bahasa yang sama (EN ke `/malaria-program`, ID ke `/id/program-malaria`).
- [ ] Halaman Transparansi: di bagian bawah judul "Tax ID and bank details" / "NPWP dan rekening" lalu blok snippet. Snippet `legal-details`: dua baris (NPWP, rekening) berisi `[ISI-DULU]`, **tanpa angka**.
- [ ] Halaman Hubungi Kami: kode pos tertulis "`[ISI-DULU]` 99351 atau 99225" (belum diputuskan).
- [ ] `/malaria-program` dan `/id/program-malaria` di landing menjawab 404 selama offline.
- [ ] `php artisan cms:audit-placeholders`: "Tidak ada penanda" (semua offline); `--all` mendaftar 12 halaman + snippet (halaman Imunisasi, indeks Program, dan Transparansi: hanya snippet yang berpenanda). Jadikan satu halaman online tanpa menyelesaikan penandanya: perintah pertama melaporkannya dan berkode keluar 1. Kembalikan ke offline.
- [ ] `php artisan cms:audit-translations`: tidak melaporkan satu pun dari 12 halaman.

## 6s. Footer dan rute hilang (rilis 26)
- [ ] Salin `opsional/footer.blade.php` ke `resources/views/components/layouts/footer.blade.php` di landing (cadangkan footer lama). Buka `/` lalu `/id`: judul kolom "Programs / Foundation / Contact" dan "Program / Yayasan / Info Kontak"; teks hak cipta dan slogan mengikuti bahasa.
- [ ] Tautan kolom Program dan Yayasan menuju alamat bahasa yang sama (di `/id`: `/id/program-malaria`, `/id/tentang-kami`, ...). Selama halamannya offline, klik menjawab 404 (wajar); setelah online, terbuka. Tidak ada tautan `#`.
- [ ] Telepon (`tel:`), email (`mailto:contact@ysbh.org`), peta, dan Instagram `@ysbhusada` berfungsi. Ikon bulat "IG" tampil rapi. Beri tahu bila alamat Instagram-nya salah.
- [ ] `php tools\check-landing.php D:\Herd\landing-ysbh` tetap `==> 0 galat`. Pesan "rute dihapus" kini menyarankan `url('/about-us')`, `url('/malaria-program')`, dst. (bukan `/programs/kia`).

## 6t. Daftar isi: di tengah saat dimuat, menempel di atas saat digulir (rilis 29)
Salin `sections.blade.php` ke CMS **dan landing** (`php tools\sync-landing.php D:\Herd\landing-ysbh --apply`), lalu `php artisan view:clear` di keduanya. Uji di layar ≥ 1536 px (daftar isi tidak tampil di bawahnya). Muat ulang keras (Ctrl+F5).
- [ ] Halaman TANPA banner `w-screen` di blok pertama, dengan beberapa heading ber-anchor: saat dimuat kartu daftar isi berada di **tengah tinggi layar** (bukan di bawah navigasi), muncul dengan halus, tanpa melompat dari posisi lama.
- [ ] Gulir pelan ke bawah: kartu ikut naik bersama halaman sampai jaraknya sekitar 128 px dari atas layar, lalu **menempel** di sana. Gulir kembali ke atas: ia turun lagi ke tengah.
- [ ] Halaman dengan blok **gambar** `w-screen` sebagai blok PERTAMA: gambar memenuhi lebar layar, daftar isi **belum tampil**. Gulir: daftar isi muncul ketika tepi bawah gambar melewati tepi atas kartu (sekitar 128 px dari atas layar), lalu menandai judul yang dibaca. Gulir kembali ke atas: ia menyingkir lagi.
- [ ] Banner dengan gambar yang lambat dimuat (muat ulang tanpa cache): setelah gambar tampil, daftar isi tetap benar (tidak muncul di atas banner).
- [ ] Pindahkan gambar `w-screen` menjadi blok KEDUA (paragraf di depannya): daftar isi tampil di tengah dari awal dan hanya menyingkir saat berpapasan dengan gambar itu. Gambar `w-screen` di tengah halaman: menyingkir saat berpapasan, lalu kembali.
- [ ] Ubah lebar gambar pertama menjadi bukan `w-screen`: daftar isi tampil seperti halaman tanpa banner. Bila banner tidak pernah menyembunyikan kartu, periksa nilai `width` blok gambar Anda: harus tepat `w-screen`.
- [ ] Jendela dipendekkan (tinggi ~500 px): kartu tidak naik di atas 128 px dari atas layar dan tidak keluar dari layar.

## 6u. Menu Builder (rilis 28)
Salin isi `opsional/menu-builder.blade.php` menimpa berkas menu builder Anda, dan pasang `app/Content/MenuTarget.php` (pemasang CMS). `php artisan view:clear`. Panduan: `docs/MENU.md`.
- [ ] Formulir menampilkan satu kolom label per bahasa (EN dan ID, bahasa bawaan bertanda *), pilihan **Tujuan tautan**, dan tidak ada lagi kotak "Route Name".
- [ ] Tambah "About Us": Tujuan = Halaman CMS, ketik "tentang" atau "about": hasil pencarian muncul; pilih satu: tertulis `/slug`. Kotak **Yang dilihat pembaca** menampilkan alamat EN dan ID (`/about-us` dan `/id/tentang-kami`; absolut ke landing bila `CMS_PUBLIC_URL` diatur). Simpan: baris muncul dengan "Halaman: /about-us"; notifikasi tampil.
- [ ] Kosongkan label ID lalu simpan: label ID terisi sama dengan label EN. Kosongkan label EN: galat "Label bahasa bawaan (EN) wajib diisi".
- [ ] Tujuan URL khusus: `javascript:alert(1)`, `//evil.test`, `https://a b`, dan alamat > 255 karakter **ditolak** dengan pesan; `https://contoh.org/x` diterima. Email tidak sah, telepon `12`, anchor `1a` ditolak.
- [ ] Beranda menjadi `/`, Daftar artikel menjadi `/articles`. Di landing: menu itu menuju `/` dan `/articles` untuk EN, `/id` dan `/id/artikel` untuk ID.
- [ ] Halaman yang masih offline sebagai tujuan: baris diberi ⚠ "belum online atau tidak ditemukan". Jadikan online, muat ulang: ⚠ hilang.
- [ ] Baris lama ber-`route_name` `about`: tampil ⚠ "Tujuan perlu dipilih ulang" (atau "rute lama" bila `url` terisi). Edit: kotak kuning menyebut rutenya; pilih tujuan baru, simpan: ⚠ hilang dan kolom `route_name` di database kosong.
- [ ] Geser sebuah baris ke atas dan ke bawah, muat ulang: urutan tersimpan; kolom `order` di database berurutan 0, 1, 2, ... tanpa lubang.
- [ ] Hapus (dengan konfirmasi): baris hilang; menghapus baris yang sedang diedit mengosongkan formulir.
- [ ] Menu di landing mengikuti: label sesuai bahasa, tautan sesuai bahasa, baris "Tampilkan di Publik" dimatikan tidak muncul.
- [ ] Bila `x-sort` tidak bekerja (baris tidak bisa digeser): kirimkan pesan Console (F12); berkas lama Anda memakai fitur yang sama.

## 6v. Teks kaya: Tiptap tunggal di panel Properti (rilis 31)
Pasang dulu (lihat `PERUBAHAN.md` rilis 31): `rich-field.js` + `editor.js` baru + `app.js` + `mikro-tiptap.js` dan `toolbars.blade.php` proyek Anda. Lalu `npm run build`, `php artisan view:clear`, muat ulang keras (Ctrl+F5).
- [ ] Console (F12): `Alpine.data` tidak galat; tidak ada pesan `richField is not defined`.
- [ ] Tambah blok Paragraf, klik blok itu: panel **Properti** menampilkan kotak Tiptap per bahasa (ID dan EN) **dengan toolbar** (tab Teks/Format). Sebelum ada blok yang difokus: kotak abu-abu "Pilih sebuah blok…".
- [ ] Ketik di kolom ID: teks muncul di kanvas/outline; tidak ada request selagi mengetik, **satu** request ±0,8 dtk setelah berhenti. Panel debug → `terfokus`: `data.text.id` = `<p>…</p>`.
- [ ] Tebal, miring, warna, ukuran (Kecil…H1), rata teks, daftar, kutipan, tautan: tombol yang aktif menyala sesuai posisi kursor.
- [ ] **Tautan**: tab Halaman, ketik 2 huruf nama halaman, pilih: tersimpan `internal://page/{slug}` (lihat panel debug), TANPA `target`/`rel`. Tab Alamat: `ysbh.org` jadi `https://ysbh.org` (dengan `target="_blank"`); `javascript:alert(1)` ditolak (dialog tidak menutup).
- [ ] Blok Judul: toolbar **tanpa** rata teks, daftar, kutipan, kode; **Enter tidak membuat baris baru**; menempel dua paragraf tetap satu baris; mengosongkan judul menyimpan `""` (bukan `<p></p>`); `data.text.id` **tanpa** `<p>`.
- [ ] Pindah ke blok lain lalu tekan Ctrl+Z di kotak: **tidak** mengembalikan teks blok sebelumnya (riwayat per pemasangan).
- [ ] Pilih tampilan bahasa "ID" saja: hanya satu kotak; kembali "keduanya": dua kotak, isi utuh.
- [ ] Halaman hasil seed (mis. Beranda, blok "Program kami: …" dengan tautan internal): buka blok itu **tanpa mengetik**: panel debug menunjukkan data tidak berubah; Simpan tidak menghasilkan perubahan. Ketik satu huruf: tautan `internal://…` tetap ada, tebal tetap tebal, daftar tetap daftar.
- [ ] Blok yang isinya tabel (Kredibilitas → tabel mitra): kotak **hanya-baca** + catatan kuning + tombol **Sunting juga** (meminta konfirmasi). Ini disengaja: Tiptap proyek tidak punya tabel.
- [ ] Editor lama (halaman lama): tetap bekerja; tombol Tautan di toolbar tidak berubah.

## 6w. Panel Eyebrow, Gambar, Kartu Builder + jarak bawah (rilis 32)
Pasang dulu (lihat `PERUBAHAN.md` rilis 32). `npm run build`, `php artisan view:clear`, muat ulang keras. Landing: `sync-landing`, `view:clear`.
- [ ] Console (F12): tidak ada galat `cardPanel is not defined` / `Cannot read properties of undefined (reading 'parentId')`.
- [ ] Klik blok **Eyebrow**: panel Properti menampilkan Teks (ID/EN), Ikon, Warna, Jarak Bawah. Mengubah teks, ikon, warna tampil di kanvas; tidak ada lagi catatan kuning "Tipe ini belum punya panel".
- [ ] Klik blok **Gambar**: Pilih gambar (File Manager), Teks Alternatif, Keterangan, Lebar, Posisi, Sudut, Tinggi Maksimum, Pemotongan, Jarak Bawah. Setiap pilihan mengubah kanvas; **Layar penuh (banner)** pada gambar pertama halaman tetap memunculkan perilaku daftar isi rilis 29. Bandingkan dengan editor lama pada satu gambar yang sudah ada: bila ada pilihan lama yang tidak ada di panel baru, catat dan kirimkan.
- [ ] Klik blok **Kartu Builder**: Kartu per Baris dan Jarak Bawah, lalu daftar kartu. Kartu yang sudah ada tampil dengan judul teks pertamanya.
- [ ] `+ Tumpuk`, `+ Ikon + teks`, `+ Dokumen`: kartu baru muncul (terbuka) dengan 1, 2, 3 kolom; kanvas ikut berubah.
- [ ] Naik/Turun/Duplikat/Hapus kartu bekerja; hapus kartu berisi meminta konfirmasi, kartu kosong tidak. Muat ulang: urutan tersimpan.
- [ ] Buka sebuah kartu → "Tampilan kartu": latar, ruang dalam, sudut, garis, bayangan, saat disorot, rata vertikal, tautan. Setiap pilihan mengubah kartu itu saja (bukan kartu lain), dan **pilihan yang sudah dipakai kartu lama tampil terpilih** (bukan kosong).
- [ ] Kolom: `+ Kolom`, geser kiri/kanan, lebar relatif, hapus (konfirmasi bila berisi). Elemen: `+ Tambah elemen…` (Teks, Ikon, Inisial, Foto profil, Akordeon), naik/turun, hapus.
- [ ] Klik sebuah elemen: panelnya terbuka dengan tombol **← Kembali ke Kartu Builder**; ubah isinya; Kembali: kartu yang sama masih terbuka. Elemen baru dari panel memiliki setiap kontrol berpilihan terpilih (bukan kosong).
- [ ] Mengetik di elemen Teks, lalu langsung klik Kembali dan pindah blok: teksnya tersimpan di kartu yang benar (bukan kartu lain).
- [ ] **Jarak bawah**: di setiap blok dasar (Judul, Paragraf, Eyebrow, Gambar, Kartu Builder, Tombol, Kolom, Grup Langkah, Akordion, Callout, Video, Galeri, Unduhan, Artikel Terbaru) ada kontrol **Jarak Bawah**; Pemisah Seksi tidak. Pilih "tight" (mb-0) dan "besar": jarak ke blok berikutnya berubah di **kanvas dan di situs publik**.
- [ ] Judul/Paragraf/Eyebrow/Gambar/Kartu: bila memilih jarak tapi **tidak ada perubahan di kanvas**, renderer proyek Anda tidak mencetak `margin_bottom`: kirimkan berkas renderernya (atau keluarkan tipe itu dari `Spacing::OWN`).
- [ ] Tombol/Video/Galeri tanpa pengaturan: kini berjarak `mb-4 md:mb-6` ke blok berikutnya (sebelumnya dempet). Menyimpan dan memuat ulang mempertahankan pilihan.

## 6x. Toolbar Tiptap dua baris + rata teks Judul (rilis 33)
Pasang dulu (lihat `PERUBAHAN.md` rilis 33): salin `toolbars.blade.php` ke `resources/views/components/editor/`, lalu `npm run build`, `php artisan view:clear`, muat ulang keras. Landing: `sync-landing`, `view:clear`.
- [ ] Klik blok **Judul**: setiap kotak bahasa punya toolbar **dua baris**, tanpa tab Teks/Format. Baris 1: B I U, tiga tombol rata teks, tautan. Baris 2: jenis huruf, ukuran, palet, ⋯. Tidak ada latar sage/mist; hanya putih, abu, dan hijau forest.
- [ ] Judul: klik **rata tengah**: tombol menyala hijau, teks Judul di **kanvas** ikut ke tengah. Rata kanan, lalu kiri: kembali seperti semula. Muat ulang: pilihan tersimpan. HTML Judul tidak berubah (tetap tanpa `<p>`).
- [ ] Bila rata tengah **tidak tampak berubah di kanvas** padahal tombolnya menyala: renderer judul proyek memaksa rata teksnya sendiri; kirimkan berkasnya.
- [ ] Judul: daftar, kutipan, blok kode, jorok **tidak** muncul (satu baris). Menu **⋯** hanya berisi Ketebalan dan Coretan.
- [ ] Klik blok **Paragraf**: dua baris juga. Baris 1 menambah tombol daftar bullet/angka. Menu **⋯**: ketebalan, coretan, rata kiri-kanan, jorok, daftar tugas, kutipan, blok kode. Rata tengah pada satu paragraf hanya meratakan paragraf itu.
- [ ] Palet: panel putih berisi lima warna, "Bebas", dan "Kembalikan ke warna bawaan"; terbuka penuh (tidak terpotong), tertutup oleh Esc, klik di luar, atau memilih warna.
- [ ] Jenis huruf / ukuran / ketebalan: pilihan yang diubah berhuruf gelap tebal; kembali ke "bawaan" berhuruf abu. Teks tetap mengikuti pilihan setelah Simpan dan muat ulang.
- [ ] Editor lama (halaman lama): toolbar baru tampil dan tidak ada galat di console (rata teks per paragraf, daftar, kutipan tetap bekerja).
- [ ] Lebar panel kanan sempit (≤ 270 px): toolbar boleh turun ke tiga baris; di atas itu dua baris.

## 6y. Blok Tabel (rilis 34)
Pasang dulu (lihat `PERUBAHAN.md` rilis 34). `npm run build`, `php artisan view:clear`, muat ulang keras. Landing: `sync-landing`, `view:clear`.
- [ ] Console (F12): tidak ada galat `tablePanel is not defined`.
- [ ] **+ Tambah blok → Tabel**: blok muncul di kanvas sebagai kotak "Tabel masih kosong"; panel Properti menampilkan **Isi tabel (3 kolom, 2 baris)** di ATAS, lalu Judul tabel, Catatan, pilihan tampilan, Jarak Bawah.
- [ ] Ketik judul kolom dan beberapa sel (ID): kanvas langsung menampilkan tabel. Tekan **EN**, ketik teks Inggris: kanvas bahasa Inggris memakainya; bahasa ID tidak berubah.
- [ ] **+** di kanan judul menambah kolom, **+ Baris** menambah baris; geser kolom (panah) memindahkan judul DAN isi kolom itu; naik/turun baris; ikon rata teks mengganti kiri/tengah/kanan dan sel kolom itu ikut rata di kanvas.
- [ ] Hapus baris/kolom yang berisi: ada konfirmasi; yang kosong: tanpa konfirmasi. Kolom terakhir tidak bisa dihapus.
- [ ] Salin 3 kolom × 4 baris dari Excel/Google Sheets, klik sebuah sel kosong, Ctrl+V: terisi mulai dari sel itu. **Tempel dari spreadsheet** → tempel → "Baris pertama = judul kolom" → **Ganti isi tabel**: seluruh tabel diganti.
- [ ] Di sel: `**Total**` tampil tebal; `https://ysbh.org` menjadi tautan; `usia <5 tahun` tampil apa adanya; Enter di dalam sel menjadi baris baru.
- [ ] Pilihan tampilan: zebra, garis (antar baris / kotak / tanpa), kerapatan, warna baris judul, "Kolom pertama sebagai judul baris": semuanya terlihat di kanvas.
- [ ] **Situs publik**: tabel tampil sama; baris yang kosong di semua bahasa tidak ada; di HP (lebar ≤ 400 px) tabel 4 kolom bisa digeser dan muncul petunjuk "Geser ke samping"; di desktop petunjuk itu tidak muncul.
- [ ] Simpan, muat ulang: isi, urutan, rata kolom tersimpan. Pindah ke blok lain lalu kembali: kisi menampilkan isi yang sama.
- [ ] Tab dari papan tik ke bingkai tabel: bingkai bisa difokus (garis fokus tampak).

## 6z. Kolom dan Gambar (rilis 35)
Pasang kit + dua renderer (`opsional/render-kolom-gambar/INSTALL.md`). `npm run build`, `php artisan view:clear`, muat ulang keras. Landing: `sync-landing`, salin renderer, `view:clear`, `npm run build`.
- [ ] **Tambah blok → Kolom**: di kanvas langsung tampak dua kotak putus-putus "Kolom 1/2 masih kosong"; klik kotak itu memilih blok Kolom; panel kanan menampilkan Jumlah Kolom, **Rata Vertikal (terhadap kolom tertinggi)** dengan Atas / Tengah / Bawah / **Bagi rata**.
- [ ] Ubah jumlah kolom ke 3: muncul tiga kotak. Tambahkan blok ke Kolom 1 dan 2 dari panel kiri: kotak penanda hilang di kolom yang terisi.
- [ ] Klik sebuah blok DI DALAM kolom di kanvas: blok itu yang terpilih (bukan blok Kolom).
- [ ] Isi Kolom 1 dengan 3 paragraf dan Kolom 2 dengan 1 paragraf pendek. Kedua kolom setinggi yang terpanjang. Atas / Tengah / Bawah memindahkan paragraf pendek ke atas / tengah / bawah. Dengan 2 blok di Kolom 2, **Bagi rata** menaruh satu di atas dan satu di bawah.
- [ ] Gambar di Kolom 2 dengan Tinggi **Penuh** + Pemotongan "Penuhi (potong)": gambar setinggi Kolom 1, tidak gepeng.
- [ ] Tinggi 12 / 18 / 24 / 32: tinggi gambar berubah sesuai; "Tinggi Maksimum" tidak berpengaruh saat Tinggi bukan Auto. Tinggi Auto: sama seperti sebelumnya.
- [ ] Gambar tingkat atas (di luar Kolom) dengan Tinggi Penuh: tampil seperti Auto (wajar).
- [ ] Simpan, buka **situs publik**: tata letaknya sama dengan kanvas, tanpa bingkai putus-putus dan tanpa kotak "masih kosong". Di HP kolom bertumpuk seperti biasa.
- [ ] Halaman lama yang berisi Kolom/Gambar tampil sama seperti sebelum rilis ini.

## 6za. Galeri 1 kolom, Jarak Bawah di Kolom, editor Tiptap (rilis 36)
Pasang kit + `multi-columns.blade.php` baru. `npm run build`, `php artisan view:clear`, muat ulang keras. Landing: `sync-landing`, salin renderer, `view:clear`, `npm run build`.
- [ ] **Galeri**: Kolom (desktop) menawarkan 1 2 3 4 5 6. Pilih 1: satu gambar selebar blok (juga di HP). Tampilan carousel: satu gambar per layar.
- [ ] **Tombol di dalam Kolom**: ubah Jarak Bawah (tight → full): jarak ke blok di bawahnya dalam kolom yang sama ikut berubah, di kanvas dan di situs. Tombol di tingkat atas halaman: sama.
- [ ] Halaman lama yang sudah berisi Tombol/Video di dalam Kolom tampil sama seperti sebelumnya (selama Jarak Bawah-nya belum Anda ubah).
- [ ] **Judul**: beri sebagian teks ukuran **Raksasa H1**: di panel kanan huruf tidak terpecah (sekitar 28 px). Di kanvas dan situs ukurannya tetap besar sebenarnya.
- [ ] Beri sebagian teks warna **Putih** (atau Emas): di editor panel tampil putih di atas kotak abu gelap, terbaca. Hijau/koral/hitam tampil biasa. Simpan, muat ulang: warna dan ukuran di kanvas/situs tetap seperti dipilih (latar gelap hanya ada di panel).
- [ ] Console (F12): tidak ada galat saat membuka Judul/Paragraf (`@tiptap/pm`).

## 6zb. Tempat foto berupa blok Gambar (rilis 37)
Pasang kit (hanya kode PHP; tanpa `npm run build`). `php artisan optimize:clear`.
- [ ] Halaman sudah dibuat sebelumnya: `php artisan cms:convert-photos` menampilkan tabel (halaman dan jumlah foto); `--apply` mengganti. Jalankan lagi: "Tidak ada paragraf penanda foto yang perlu diganti".
- [ ] Buka halaman Malaria di editor: tempat foto kini blok **Gambar** kosong (kanvas: "Blok Gambar masih kosong"); klik memilihnya; panel kanan menampilkan **Keterangan Gambar** sudah terisi (ID dan EN).
- [ ] **Jelajahi File Manager** → pilih foto: tampil di kanvas dengan keterangan di bawahnya. Situs publik (halaman online) menampilkan foto itu.
- [ ] Halaman yang masih punya blok Gambar kosong: `php artisan cms:audit-placeholders --all` menyebutnya (kolom content). Gambar kosong pada halaman ONLINE tidak mencetak apa pun di situs.
- [ ] Setelah semua foto terisi dan teks lengkap, `cms:audit-placeholders` (tanpa `--all`) bersih untuk halaman itu.

## 6zc. Alur persetujuan artikel (rilis 38)
Pasang kit; **`php artisan migrate`** (tabel `article_reviews`); landing: `php tools\sync-landing.php D:\Herd\landing-ysbh --apply`; `php artisan optimize:clear`. Siapkan tiga akun: penulis (bukan admin/editor), editor, dan satu penulis lain. Rincian: `docs/ARTIKEL-ALUR.md`.
- [ ] **Penulis** buat artikel baru: di panel Halaman → Status hanya ada lencana **Draf** dan tombol **Ajukan review**; TIDAK ada daftar status. Simpan: tetap Draf.
- [ ] Penulis menekan **Ajukan review** (boleh isi catatan) pada artikel yang BELUM tersimpan: tersimpan lalu berstatus **Ditinjau**; halaman pindah ke mode edit.
- [ ] Penulis, artikel Ditinjau: tombol **Simpan** hilang diganti "Hanya baca", ada pita "Hanya baca…", hanya tombol **Tarik kembali ke draf**. Tarik kembali: Draf lagi, bisa diedit.
- [ ] Penulis LAIN membuka alamat edit artikel itu: halaman 403.
- [ ] **Editor** membuka artikel Ditinjau: tombol Terbitkan sekarang, Jadwalkan terbit, Kembalikan ke draf, Tolak. Tidak ada tombol Tarik kembali.
- [ ] **Kembalikan ke draf**: Konfirmasi tidak aktif sampai alasan diisi. Setelah dikembalikan, penulis melihat kotak kuning **Catatan peninjau** berisi alasan itu, dan bisa memperbaiki lalu mengajukan lagi.
- [ ] **Jadwalkan**: isi tanggal-jam di masa depan: status **Terjadwal**, tertulis "Terbit …". Artikel BELUM tampil di situs. Ubah jadwal ke lima menit lagi: begitu waktunya lewat, artikel tampil di daftar artikel dan alamatnya terbuka (tanpa cron). `php artisan cms:publish-scheduled` mengubah statusnya menjadi Terbit; riwayat mencatat "menerbitkan otomatis".
- [ ] Jadwal di masa lalu ditolak dengan pesan merah (toast). 
- [ ] **Tolak** dengan alasan: status **Ditolak**; semua tombol hilang, Simpan hilang, kotak merah **Alasan penolakan**. Buka lagi sebagai admin: tetap tidak bisa diubah.
- [ ] **Terbitkan sekarang**: Terbit, tampil di situs, `published_at` terisi.
- [ ] Artikel Terbit: penulis tidak melihat tombol apa pun dan tidak bisa menyimpan; editor melihat Tarik ke draf dan Arsipkan.
- [ ] **Riwayat tinjauan** memuat urutan (ajukan, kembalikan, ajukan, terbitkan…) lengkap dengan nama, waktu, catatan.
- [ ] Halaman dan snippet: Status tetap pilihan Offline/Online seperti sebelumnya.
- [ ] Peringatan "Riwayat belum aktif…" tampil bila `migrate` belum dijalankan; tombol alur menolak dengan pesan "Jalankan: php artisan migrate".

## 6zd. Pencarian tautan di kartu (rilis 39)
Pasang kit; `php artisan optimize:clear`, `php artisan view:clear`; bila memakai bundel: `npm run build`. Landing: `php tools\sync-landing.php D:\Herd\landing-ysbh --apply`.
- [ ] Kartu Builder → buka sebuah kartu → **Tampilan kartu**: paling bawah ada **Tautan seluruh kartu** berupa pilihan jenis (Tanpa tautan, Halaman, Artikel, URL luar / jalur), bukan kotak teks.
- [ ] Pilih **Halaman**, ketik 2 huruf judul: daftar tujuan muncul. Pilih satu: tampil kotak hijau berisi judulnya dengan tombol ×. Panel debug: `container.url` = `internal://page/{slug}`, `container.url_label` = judul.
- [ ] Klik ×: tujuan terlepas dan kolom pencarian kembali. Pilih **Artikel** dan ulangi: `internal://article/{slug}`.
- [ ] Simpan, buka situs publik: seluruh kartu bisa diklik ke halaman/artikel itu (renderer kartu proyek Anda harus mencetak `<a>`; lihat `contoh-kode/kartu-tautan.blade.php` bila tidak). Tujuan yang masih offline/draf: kartu tampil biasa (tidak jadi tautan).
- [ ] **URL luar / jalur**: ketik `https://ysbh.org/x` (tersimpan apa adanya), `/id/tentang` (sah); `javascript:alert(1)` memunculkan peringatan kuning dan setelah simpan menjadi kosong.
- [ ] Ganti jenis (Halaman → URL luar): tujuan lama dikosongkan. **Tanpa tautan**: `container.url` dan `url_label` kosong.
- [ ] Kartu lama berisi `internal://page/slug` tanpa label: tampil "Halaman: slug" dan tetap berfungsi; Simpan tanpa mengubah apa pun tidak mengubah data.
- [ ] Hasil pencarian tanpa slug tampil redup dengan tulisan "tanpa slug" dan tidak bisa dipilih.

## 6ze. Thumbnail artikel (rilis 40)
Pasang kit; `php artisan optimize:clear`, `php artisan view:clear`; bila memakai bundel: `npm run build`. Tidak ada migrasi.
- [ ] Artikel BARU tanpa gambar: panel Halaman → **Thumbnail** menampilkan "Belum ada thumbnail". Klik **Pilih dari gambar artikel**: pesan "Belum ada gambar di isi artikel" dengan tombol File Manager.
- [ ] Tambah blok **Gambar** (dan satu blok Galeri berisi 2 foto) TANPA menyimpan, buka lagi **Pilih dari gambar artikel**: gambar-gambar itu muncul sebagai kotak kecil (urut seperti di halaman). Pilih satu: pratinjau berganti dan tertulis nama berkas; panel menutup.
- [ ] Gambar yang sedang jadi thumbnail diberi cincin hijau di daftar. **Hapus**: kembali "Belum ada thumbnail".
- [ ] **File Manager**: hanya gambar yang bisa dipilih; setelah dipilih pratinjau berganti. Simpan, muat ulang: thumbnail yang sama. Di database `posts.featured_image` berisi angka (ID media); tanpa thumbnail: `default.webp`.
- [ ] Blok Unduhan berisi PDF dan tombol berkas TIDAK muncul sebagai pilihan thumbnail.
- [ ] Di File Manager, media yang dipakai sebagai thumbnail menunjukkan artikel itu di "Digunakan Di" dan tidak bisa dibuang ke Sampah (butuh `SyncsMediaUsage` di model `Post`).
- [ ] Hapus media thumbnail lewat basis data (uji saja): pratinjau berubah jadi "Gambar thumbnail tidak ditemukan"; Simpan mengosongkannya dengan toast.
- [ ] Penulis, artikel Ditinjau (terkunci): pratinjau tampil tetapi tidak ada tombol thumbnail.
- [ ] Artikel lama (nama berkas di `featured_image`): tampil "Thumbnail lama" + nama; Simpan tanpa mengubah tidak menimpanya; memilih yang baru menggantinya.
- [ ] Blok **Artikel terbaru** di halaman publik menampilkan thumbnail yang dipilih. Halaman publik artikel milik Anda: lihat `docs/ARTIKEL.md` bila gambar tidak muncul.

## 6zf. Aturan empat mata artikel (rilis 41)
Perlu DUA akun admin/editor (A dan B) dan satu penulis (W). `config/cms.php`: `'article_self_review' => false`, lalu `php artisan config:clear`.
- [ ] Sebagai **A**, buka draf milik **W**, **Ajukan review**. Panel: tidak ada Terbitkan/Jadwalkan/Kembalikan/Tolak; ada **Tarik kembali**; teks "Menunggu keputusan admin atau editor LAIN: artikel ini Anda ajukan…".
- [ ] Sebagai **B**, buka artikel itu: ke-empat tombol keputusan ada; teks "Menunggu keputusan Anda…". **Terbitkan sekarang** berhasil.
- [ ] Sebagai **A**, tulis artikel baru, ajukan: terhalang juga (teks "…Anda tulis…"); hanya B yang bisa memutuskan.
- [ ] Sebagai **A**, **Tarik kembali** pengajuan atas draf W: kembali Draf. Lalu **W** mengajukan lagi: sekarang **A boleh** memutuskan (pengaju terakhir = W).
- [ ] Coba kirim permintaan buatan tangan (Console: `$wire.workflowAction('publish')` sebagai A pada artikel yang diajukan A): toast merah "Aturan empat mata: …", status tidak berubah.
- [ ] Artikel Terbit: A tetap bisa **Tarik ke draf** dan **Arsipkan** (bukan keputusan tinjauan).
- [ ] Kembalikan saklar ke `true` (+ `config:clear`): A boleh memutuskan artikel yang ia ajukan; teks panel kembali "Menunggu keputusan Anda…".

## 6zg. Riwayat perubahan / audit trail (rilis 42)
Sebagai admin atau editor; `php artisan migrate` sudah jalan (tabel `activity_log`), model sudah memakai `getActivitylogOptions()` (`contoh-kode/audit-log-model.php`).
- [ ] Buka halaman/artikel/snippet yang sudah tersimpan: tab **Riwayat** ada di samping Halaman dan Blok. Di record BARU (belum tersimpan) dan sebagai penulis biasa: tab **tidak** ada.
- [ ] Ubah judul + simpan, buka Riwayat: entri "Diubah" paling atas, nama Anda, jam sekarang, "Judul  lama → baru". Ubah hanya isi blok: "Isi diubah" tanpa nilai.
- [ ] Artikel: ajukan lalu terbitkan lewat panel Status: tampil perubahan **Status** ("Draf → Ditinjau", "Ditinjau → Terbit") dengan pelaku masing-masing. Jalankan `php artisan cms:publish-scheduled` untuk artikel terjadwal: pelaku "Sistem".
- [ ] Record dengan banyak riwayat (>20): tombol **Muat lebih banyak** menambah tanpa mengulang entri; **Muat ulang** kembali ke 20 teratas.
- [ ] Riwayat kosong ("Belum ada riwayat…"): modelnya belum mencatat. Cek `getActivitylogOptions()` dan `config/activitylog.php` (`enabled`).
- [ ] Console: tidak ada galat merah saat tab dibuka. Network: satu permintaan Livewire per pembukaan tab; Response berisi `entries`.
- [ ] Penulis biasa mengirim `$wire.auditTrail(0)` dari Console: ditolak 403.

## 6zh. Artikel per kategori + menu (rilis 42)
- [ ] `php tools\check-landing.php D:\Herd\landing-ysbh`: tidak ada peringatan `public.category`.
- [ ] Landing: buka `/articles/category/{slug-kategori-EN}` dan `/id/artikel/kategori/{slug-kategori-ID}`: judul = nama kategori, hanya artikel kategori itu, "← Semua artikel" kembali ke indeks.
- [ ] `/id/artikel/kategori/{slug-EN}` (slug bahasa lain): **301** ke slug ID. Slug ngawur: 404. Kategori tanpa artikel: 200 "Belum ada artikel di kategori ini".
- [ ] Pengalih bahasa di header menuju halaman kategori yang sama di bahasa lain; sumber halaman memuat `hreflang` untuk bahasa yang punya slug.
- [ ] CMS → Menu: jenis **Artikel per kategori**, pilih kategori, "Yang dilihat pembaca" menunjukkan alamat EN dan ID. Simpan; menu di landing EN menuju `/articles/category/…`, di ID menuju `/id/artikel/kategori/…`.
- [ ] Hapus kategori (uji): baris menu bertanda "Kategori ini tidak ditemukan"; landing menampilkan menu dengan alamat yang diketik (404 saat diklik), bukan galat.

## 6zi. Outline: sisip di posisi, pindah, masuk/keluar kontainer (rilis 43)
Buka editor halaman dengan Network (filter `livewire`) dan Console terbuka. Siapkan: Judul, Kolom (2 kolom, kolom 1 berisi Paragraf), Paragraf B, Step, Pemisah Seksi.
- [ ] Arahkan kursor ke baris: ada tombol **⋯**. Klik: menu muncul dekat tombol, tidak terpotong sidebar; Escape dan klik di luar menutupnya. **0 request** saat membuka menu utama.
- [ ] Paragraf B → ⋯ → **Sisipkan di atas…** → Judul: **1** request `addBlockAt` dengan params `[null, null, "heading", <indeks>]`; Judul baru tepat di atas Paragraf B dan **terfokus**. Ulangi **di bawah…**.
- [ ] Paragraf di kolom 1 → ⋯ → Sisipkan di bawah: daftar jenis **tanpa** Pemisah Seksi / Kolom / Step; blok baru masuk kolom 1 tepat sesudahnya.
- [ ] Paragraf B (tepat di bawah Kolom) → ⋯ → **Masukkan ke Kolom di atas**: 1 request `indentBlock`; Paragraf B ada di kolom terakhir. ⋯ → **Keluarkan dari kontainer**: 1 request `outdentBlock`; kembali tepat sesudah Kolom. Isi teks tidak berubah, panel kanan tetap menunjuk blok yang sama.
- [ ] ⋯ → **Pindahkan ke…** pada Paragraf B: 1 request `moveTargets` (tanpa render ulang), daftar "Tingkat atas (sekarang)", "Kolom … › Kolom 1", "Kolom 2". Pilih Kolom 2: 1 request `moveBlockTo`; blok pindah **tanpa** harus dibuat ulang di kolom.
- [ ] Pemisah Seksi dan blok Kolom/Step: "Pindahkan ke…" hanya menawarkan Tingkat atas; "Masukkan…" tidak muncul.
- [ ] Kurangi jumlah kolom (3 → 2) saat kolom 3 berisi blok: zona "tersembunyi" tampil bertanda; blok di dalamnya bisa **Keluarkan**, tetapi zona itu tidak muncul sebagai tujuan.
- [ ] Pintasan: klik baris, **Alt+↑/↓** menggeser; **Alt+Shift+→/←** masuk/keluar kontainer; fokus keyboard tetap di baris itu setelah render ulang. Tab tetap berpindah fokus biasa. (Bila Alt+Shift mengganti bahasa papan ketik di Windows dan bentrok, pakai menu ⋯.)
- [ ] Simpan, muat ulang: struktur sama; pratinjau kanvas mengikuti urutan baru.
- [ ] Console: kirim `$wire.moveBlockTo('<id kolom>', '<id blok di dalamnya>', 'col_1_zone', 0)` (ke dirinya/turunannya) dan `$wire.moveBlockTo('<id pemisah>', '<id kolom>', 'col_1_zone')`: tidak ada perubahan, tidak ada galat merah.
- [ ] Panel debug (`APP_DEBUG=true`) tetap "utuh" (tanpa blok yatim) setelah semua langkah di atas.

## 6zj. Pencatatan riwayat Page / Post / Snippet (rilis 44)
Sebagai admin. Model `Page` dan `Post` sudah ditimpa dengan `contoh-kode/model/*`, `php artisan optimize:clear` sudah jalan.
- [ ] **Page**: ubah status Offline → Online + simpan. Tab **Riwayat**: entri "Diaktifkan (online)", nama Anda, jam sekarang, dan baris "Status  Offline → Online". Kembalikan ke offline: "Dinonaktifkan (offline)".
- [ ] **Page**: ganti slug + simpan: baris "Slug  lama → baru" (per bahasa). Ubah judul SEO: "Judul SEO  lama → baru".
- [ ] **Page**: ubah HANYA isi blok (mis. satu kata di paragraf) + simpan: satu entri **"Isi diubah"** dengan nama Anda dan tanpa nilai. Simpan lagi tanpa mengubah apa-apa: **tidak** ada entri baru.
- [ ] Cek langsung di database: `SELECT id, event, description, causer_id FROM activity_log ORDER BY id DESC LIMIT 5;` memuat baris `content_updated` / `content_updated` dan **tidak ada** `content` utuh di kolom `attribute_changes`/`properties` baris yang baru.
- [ ] **Artikel**: ajukan (Diajukan untuk ditinjau), setujui/terbitkan (Diterbitkan), tarik ke draf (Dikembalikan ke draf), arsipkan (Diarsipkan): tiap langkah satu entri dengan pelaku masing-masing dan "Status  lama → baru". Artikel terjadwal yang terbit lewat `php artisan cms:publish-scheduled`: pelaku "Sistem".
- [ ] **Artikel**: ganti kategori, penulis, thumbnail, waktu terbit: masing-masing tampil lama → baru dengan nama (bukan angka).
- [ ] **Snippet**: ubah status dan isi: entri "Diaktifkan/Dinonaktifkan" dan "Isi diubah". Hapus snippet yang tidak dipakai lalu pulihkan (bila ada fitur pulihkan): entri "Dihapus" dan "Dipulihkan".
- [ ] Riwayat artikel LAMA (sebelum rilis ini) tetap terbaca: baris dengan `status_published` kini bertuliskan "Diterbitkan"; baris yang dulu memuat `content` tampil "Isi diubah" (tanpa nilai).
- [ ] Simpan tetap berhasil walaupun tabel `activity_log` sedang bermasalah: matikan sementara tabelnya (di server uji) lalu simpan; halaman tersimpan, galat tercatat di `storage/logs/laravel.log`.
- [ ] Bila "Isi diubah" TIDAK muncul sama sekali padahal perubahan status muncul: periksa `storage/logs/laravel.log` (galat dari `activity()`); kirim pesan galatnya.

## 6zk. Alamat gambar tidak terpanggang host (rilis 45)
Berkas rilis 45 sudah ditimpa di CMS dan landing, `php artisan optimize:clear` sudah jalan di keduanya.
- [ ] **Gambar lama**: buka halaman publik yang memuat blok Gambar bertanda `http://landing-ysbh.test/storage/...` di JSON-nya. Lihat sumber halaman: `src` kini memakai `MEDIA_URL` lingkungan itu (di Hostinger: `http://<domain-sementara>/storage/...`), bukan `.test`.
- [ ] Ubah `MEDIA_URL` di `.env` landing, `php artisan config:clear`, muat ulang: `src` ikut berubah tanpa menyentuh data.
- [ ] **Editor**: buka halaman yang sama di CMS: gambar di kanvas tampil (alamat sesuai `MEDIA_URL` CMS). Simpan, lalu `SELECT content FROM pages WHERE id=…`: `url` di JSON kini alamat lingkungan CMS saat ini.
- [ ] **Pilih gambar baru** di blok Gambar: gambar, alt, dan `media_id` terisi; alt mengikuti alt di Media Manager (bila kosong, alt dari pemilih).
- [ ] Pilih gambar lewat Console dengan id yang tidak ada: `$wire.handleMediaSelection({componentId:'content.<id blok>.data', id:99999999, url:'http://jahat.test/x.png'})`: tidak ada perubahan, `url` kiriman tidak pernah tersimpan.
- [ ] Gambar dengan URL luar (tanpa `media_id`) tetap tampil apa adanya.
- [ ] Blok yang medianya sudah dihapus dari Media Manager: halaman tetap tampil (gambar rusak seperti sebelumnya, tanpa galat).
- [ ] Sisa data yang tak tersembuhkan: `SELECT id FROM pages WHERE content LIKE '%.test/storage%'` (juga `posts`, `snippets`); bila ada, kabari saya.

## 6zl. Kelola Kategori dan Tag dua bahasa (rilis 46)
`⚡taxonomy.blade.php` dan `Taxonomy.php` sudah disalin, panel contoh sudah dipasang, `php artisan view:clear` sudah jalan. Masuk sebagai admin.
- [ ] Buka panel **Kelola Kategori**: dua kotak EN dan ID, masing-masing dengan kolom slug. Ketik "Maternal & Child" di EN: slug EN terisi `maternal-dan-child` sambil mengetik; ketik slug sendiri lalu ubah nama: slug TIDAK ditimpa lagi.
- [ ] Simpan dengan EN dan ID terisi: muncul di daftar dengan dua nama dan dua slug; di database `categories.name` dan `.slug` berisi JSON `{"en":…,"id":…}`.
- [ ] Simpan dengan hanya ID terisi: ditolak, pesan di bawah kotak EN (bahasa bawaan wajib). Simpan dengan hanya EN: berhasil, baris daftar bertanda "belum diterjemahkan" untuk ID.
- [ ] Nama kembar (mis. "health" saat "Health" sudah ada) ditolak di bahasa yang sama; nama sama di dua bahasa pada satu kategori ("Malaria" / "Malaria") diterima.
- [ ] **Ubah**: tombol pensil mengisi form; slug tidak berubah saat nama diketik ulang; "Simpan perubahan" memperbarui baris (bukan membuat baru); "Batal ubah" mengosongkan form.
- [ ] **Hapus kategori yang dipakai artikel**: tombol nonaktif, tooltip menyebut jumlah artikel. Kategori kosong: klik hapus, muncul konfirmasi merah di baris itu; "Batal" menutupnya; "Ya, hapus" menghapus.
- [ ] **Kelola Tag**: sama; "#stunting" tersimpan sebagai "stunting". Hapus tag yang dipakai: konfirmasi menyebut "Dilepas dari N artikel"; sesudahnya artikel itu tidak lagi memuat tag tersebut.
- [ ] Pindah antara panel Kategori dan Tag: form dan daftar yang tampil sesuai (tidak membawa isian dari yang lain).
- [ ] Menu "Artikel per kategori" (Menu Builder) tetap mengenali kategori; kategori yang slug ID-nya dikosongkan: pratinjau menu menunjukkan "belum diterjemahkan" untuk ID (aturan KATEGORI.md).
- [ ] Sebagai penulis (bukan admin/editor): panel tidak bisa menyimpan/menghapus (notifikasi "tidak memiliki otorisasi"); membuat tag dari editor artikel tetap bisa.
- [ ] Console: kirim `$wire.delete(999999)` dari komponen yang terbuka: notifikasi "tidak ditemukan", tidak ada galat merah.
- [ ] `SELECT * FROM activity_log WHERE log_name='taxonomy_updates' ORDER BY id DESC LIMIT 3;` memuat baris dibuat/diubah/dihapus beserta pelakunya (bila Spatie terpasang).

## 6zm. Thumbnail di halaman artikel dan gambar berbagi (rilis 47)
`ArticleCards.php`, `⚡article-show.blade.php`, dan `head.blade.php` sudah disalin ke landing, `php artisan view:clear` sudah jalan. Siapkan dua artikel terbit: A dengan thumbnail (dipilih di editor), B tanpa thumbnail.
- [ ] Buka A (`/articles/…` dan `/id/artikel/…`): gambar lebar tampil di bawah judul dan tanggal, sebelum isi; sudut kiri-atas dan kanan-bawah membulat besar, dua sudut lain kecil; di ponsel gambar tetap proporsional (tidak melar, tidak menyundul tepi).
- [ ] Muat ulang dengan jaringan dilambatkan (DevTools → Network → Slow 3G): kotak gambar sudah menahan tempatnya, teks di bawahnya tidak melompat saat gambar tiba.
- [ ] Lihat sumber halaman A (Ctrl+U): `og:image` berupa alamat **mutlak** (`https://…`) ke berkas thumbnail yang benar, `og:type` = `article`; bila media punya teks alternatif, ada `og:image:alt`.
- [ ] Buka alamat `og:image` itu langsung di tab baru: gambar tampil (bukan 404 / bukan alamat `.test`).
- [ ] Buka B: tidak ada gambar dan tidak ada ruang kosong di bawah judul; `og:image` = `…/ysbh.png`.
- [ ] Ganti thumbnail A ke media lain di editor, simpan: halaman A menampilkan yang baru. Hapus media thumbnail itu di File Manager: halaman A tetap terbuka tanpa gambar (tidak ada gambar rusak, tidak ada galat 500), `og:image` kembali ke `ysbh.png`.
- [ ] Thumbnail berupa PDF (pilih manual lewat basis data) tidak ditampilkan.
- [ ] Artikel lama dengan `featured_image` = nama berkas (`cover-x.webp`): tampil bila berkas ada di folder templat `cms.public.cover`; bila berkasnya tidak ada, gambar tidak muncul tanpa mematahkan halaman (cek konsol: tidak ada galat selain 404 gambar itu).
- [ ] Facebook Sharing Debugger / opengraph.xyz dengan alamat A: judul, deskripsi, dan gambar muncul. (Hanya bisa di server yang dapat dijangkau dari internet, mis. domain sementara Hostinger.)

## 6zn. Blok Artikel Terbaru dinamis (rilis 48)
Berkas CMS dan landing sudah disalin, `npm run build` dan `php artisan view:clear` sudah jalan. Siapkan: kategori **Penelitian** dengan 8+ artikel terbit, kategori lain (mis. Berita) dengan beberapa artikel, satu artikel Penelitian tanpa terjemahan EN.
- [ ] Blok Artikel Terbaru yang lama (belum diubah) tampil sama seperti sebelumnya: semua artikel, 3 kartu kolom.
- [ ] Inspektur: Sumber = *Kategori tertentu* memunculkan pemilih kategori; *Semua artikel* menyembunyikannya. Ketik "pen": Penelitian muncul; pilih, tampil sebagai baris dengan tombol × dan panah. Kotak cari kosong yang difokus menampilkan daftar kategori.
- [ ] Kanvas dengan sumber kategori tanpa pilihan: keterangan "belum ada kategori yang dipilih". Pilih Penelitian: kartu Penelitian saja muncul, terbaru dulu. Tambah kategori kedua: artikelnya ikut, urut gabungan.
- [ ] Artikel unggulan 1 sampai 6, satu per satu: bentuk kisi sesuai (1 spanduk; 2 = 1/3 + 2/3; 3 = 2/3 + dua 1/3; 4 = pola Z; 5 = 3x3; 6 = kincir dengan dua petak teks emas dan hijau di tengah). Sakelar "Balik kiri-kanan" muncul mulai 2 dan mencerminkan kisi.
- [ ] Sematkan satu artikel Berita di blok Penelitian: tampil paling awal di kisi walau bukan kategori Penelitian; kursi sisa diisi artikel Penelitian terbaru. Geser urutan sematan dengan panah: urutan di kanvas ikut.
- [ ] Sematkan artikel yang masih draf atau belum ada terjemahan EN: di halaman EN ia dilewati dan kursinya diisi artikel terbaru (tidak ada kartu kosong).
- [ ] Daftar di bawah unggulan: pilih Kolom (jumlah kolom bisa diubah) dan Baris. Artikel unggulan tidak muncul dua kali.
- [ ] Penomoran = Halaman berikutnya, 3 per halaman: tombol "Berikutnya" di halaman publik menuju `?hal…=2#ula-hal…` dan layar kembali ke blok; halaman 2 punya "Sebelumnya"; halaman terakhir tanpa "Berikutnya"; `?hal…=999` membuka halaman terakhir, `?hal…=abc` halaman 1. Parameter lain di alamat (mis. `?utm_source=x`) tetap ada.
- [ ] Dua blok berhalaman di satu halaman: berpindah halaman di satu blok tidak menggeser blok lain.
- [ ] Ponsel (lebar 390): kisi menjadi tumpukan, petak pertama potret, dua petak teks berwarna tampil utuh; tidak ada gulir ke samping. Tablet (820): kisi tetap utuh.
- [ ] Buka sebuah artikel Penelitian yang memuat blok ini di kolom samping: artikel yang sedang dibuka tidak ikut tampil.
- [ ] Halaman daftar artikel (`/articles`, `/id/artikel`) tampil dan berhalaman seperti sebelumnya.
- [ ] Judul artikel berisi `<b>` atau `&`: tampil sebagai teks, bukan menjadi tebal.

## 7. Pesan galat → penyebab
| Pesan | Penyebab |
|---|---|
| sumber halaman memuat `<?php`, `__contextArgs`, atau `context()->`; JSON-LD kehilangan `"@context"` | `@context` (dan nama lain seperti `@use`, `@session`) di dalam `{{ }}`, `{!! !!}`, atau tanda kutip dikompilasi Blade sebagai **direktif**; Laravel menambah direktif tiap versi | letakkan teks itu di dalam `@php … @endphp` atau tulis `@@context`; jalankan `php tests\blade-scan.php <proyek>` (README langkah 5) |
| `HasContentBlocks belum di-patch ...` | trait masih versi asli: pasang patch |
| `Class "App\Editor\…" not found` | berkas di `app/Editor/` belum disalin (atau `composer dump-autoload`) |
| `Call to undefined method …addBlockAt()` | `ManagesBlockStructure` belum di-`use` di builder |
| Console: `wireField is not defined` | `editor.js` belum terdaftar / belum di-build |
| Console: `Cannot read properties of undefined (reading 'editor')` | `Alpine.store('editor')` belum ada: `registerEditor` belum dipanggil di `alpine:init` |
| Klik Simpan "tidak terjadi apa-apa" | validasi gagal: lihat banner merah, atau tab Payload/Response |
| `Unable to locate a class or view for component [editor.xxx]` | berkas Blade komponen itu belum disalin |
| Console: `cardPanel is not defined` | `editor.js` rilis 32 belum disalin / belum di-build |
| Console: `tablePanel is not defined` | `editor.js` rilis 34 belum disalin / belum di-build |
| Kisi tabel tidak tampil di panel Tabel | `table-grid.blade.php` atau `BlockType.php` / `inspector.blade.php` rilis 34 belum disalin |
| `Class "App\Content\Blocks\TableStyle" not found` (CMS atau landing) | `TableStyle.php` belum disalin; di landing jalankan `tools\sync-landing.php` |
| `Class "App\Content\Blocks\LayoutStyle" not found` (CMS atau landing) | `LayoutStyle.php` belum disalin; di landing jalankan `tools\sync-landing.php` |
| Rata Vertikal / Tinggi gambar tidak berpengaruh, kanvas Kolom tetap kosong tanpa kotak | renderer `multi-columns` / `image` rilis 35 belum disalin ke `resources/views/components/blocks/render/` |
| Build gagal: `Could not resolve "@tiptap/pm/state"` | paket `@tiptap/pm` belum terpasang: `npm i @tiptap/pm` (biasanya sudah ikut bersama ekstensi Tiptap) |
| Rata teks Judul menyala tapi kanvas tidak berubah | renderer judul memaksa `text-left`/`text-*` sendiri; kelas pembungkus kalah (lihat rilis 33) |
| `Unable to locate a class or view for component [lucide-…]` di toolbar | paket ikon `blade-lucide-icons` belum terpasang (toolbar memakai `lucide-bold`, `lucide-align-left`, `lucide-list-ordered`, …) |
| `Class "App\Content\Blocks\Spacing" not found` (di CMS atau landing) | `Spacing.php` belum disalin; di landing jalankan `tools\sync-landing.php` |
| `Declaration of … transition(…) must be compatible with Livewire\Component::transition(…)` | berkas builder rilis 38 pertama (method bernama `transition`); salin ulang `⚡builder.blade.php` dan `article-workflow.blade.php` terbaru (38.1), lalu `php artisan view:clear` |
| `Table "article_reviews" doesn't exist` / panel "Riwayat belum aktif" | migrasi rilis 38 belum dijalankan: `php artisan migrate` |
| Artikel terjadwal sudah lewat waktunya tetapi tidak tampil di situs | `PublicLookup.php` landing belum disinkron (`tools\sync-landing.php --apply`) |
| Penulis bisa membuka artikel orang lain dari daftar | daftar artikel lama belum menyaring `user_id` (builder tetap memberi 403 saat dibuka) |
| `Livewire\Exceptions\… multiple root elements` | view builder punya >1 elemen akar (slot judul harus di luar akar) |

## 8. Bila masih macet, kirimkan ke saya
1. Pesan galat **lengkap** (Console dan/atau `laravel.log`, tiga baris teratas dari stack trace).
2. Tab Network: *Payload* dari request yang bermasalah (bagian `calls`) dan ukuran responsnya.
3. Isi panel debug editor (salin teksnya).
4. `composer show livewire/livewire laravel/framework | findstr versions` dan `php -v`.
5. Hasil tinker keutuhan (bagian 4) bila datanya yang dicurigai.
