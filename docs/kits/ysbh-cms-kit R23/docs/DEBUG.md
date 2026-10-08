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
| Duplikat blok Kolom yang berisi anak | 1 (`duplicateBlock`) | salinan di bawahnya, anaknya ber-ID **baru** |
| Hapus blok berisi anak | konfirmasi dulu, lalu 1 (`removeBlock`) | semua anak ikut hilang; panel debug tetap "utuh" |

Bila Duplikat/Hapus meninggalkan yatim (panel debug merah): `HasContentBlocks` bukan versi patch.
Bila `moveBlock`/`addBlockAt` "tidak ditemukan": `ManagesBlockStructure` belum di-`use` di builder.

## 3. Inspektur (kanan) dan data
- [ ] Klik blok Judul → mengetik di kolom ID **tidak** membuat request selagi mengetik; mode live (bawaan) mengirim **satu** request ±0,8 dtk setelah berhenti.
- [ ] Label baris outline ikut berubah **seketika** saat mengetik judul (tanpa request).
- [ ] Klik H1 atau H3 pada blok Judul → buka **panel debug** (pojok kanan bawah, klik judulnya) dan lihat bagian `terfokus`: `data.level` harus berganti ke `h1`/`h3`. Blok lain tidak berubah. *(Tanpa konsol: panel debug menampilkan data blok yang sedang difokus.)*
- [ ] Mengetik `<b>x</b>` di kolom Judul tersimpan sebagai `&lt;b&gt;x&lt;/b&gt;` dan kolom **tetap bisa diedit**.
- [ ] *(Tunda sampai rute edit bisa membuka halaman lama.)* Buka halaman yang dibuat editor lama; bila judulnya berformat (tebal/miring), kolom Judul tampil **hanya-baca** dengan catatan kuning "Teks berformat masih diedit lewat editor lama". Halaman baru dari builder tidak bisa menghasilkan ini, jadi tidak bisa diuji sekarang.
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
- [ ] `php tools\sync-landing.php C:\jalur\landing` (mode periksa) menampilkan berkas BARU pada landing yang belum disinkron; `--apply` menyalinnya; menjalankan periksa lagi menghasilkan "0 baru, 0 berbeda".
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
- [ ] Landing: `php tools\sync-landing.php C:\jalur\landing` menampilkan tujuh berkas BARU dibanding rilis 11 (termasuk `PublicLookup.php`); `--apply` menyalinnya; di landing sediakan model `Snippet` dan `Category` (contoh baca-saja: `landing-app/app/Models/*.php`).
- [ ] Landing: model `Media` memakai `SoftDeletes`: hapus satu gambar di File Manager CMS (ke Sampah), muat ulang halaman publik: gambar itu tidak lagi tampil.
- [ ] Landing: `findPage` dan `findArticle` lewat contoh `page-show` / `article-show`: slug tak ada, halaman offline, dan artikel draf menghasilkan 404, bukan galat.
- [ ] Gambar lewat folder bersama (lihat `DUA-APLIKASI.md`): unggah satu gambar di CMS, buka `https://ysbh.org/storage/<path>`, dan pastikan berkas `.php` uji di folder itu **tidak** dijalankan.

## 6l. Landing tahap 1 (rilis 13)
- [ ] **Pemeriksa landing**: `php tools\check-landing.php C:\jalur\landing-ysbh` berakhir `==> 0 galat`. Setiap GALAT berisi berkas dan petunjuknya; PERINGATAN boleh ada tetapi sebaiknya dibereskan.
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
- [ ] `php tools\check-landing.php C:\jalur\landing`: tidak ada PERINGATAN tentang rute statis atau `articles_index_slug`. Tambahkan sementara `Route::view('/uji', ...)` ke `web.php` tanpa mencatatnya: pemeriksa memberi PERINGATAN; hapus kembali.

## 6n. Peta situs dan robots.txt (rilis 17)
- [ ] `.env` landing: `APP_URL=https://domain-anda` (tanpa jalur), lalu `php artisan config:clear`.
- [ ] Buka `/sitemap.xml`: tampil sebagai XML (bukan halaman 404/HTML), berisi `<loc>https://domain-anda/...` dengan **domain asli, bukan localhost**.
- [ ] Ada alamat untuk setiap halaman statis (`/`, `/about`, `/programs/malaria`, ...) dan `/artikel`.
- [ ] Sebuah halaman CMS online dengan slug berbeda per bahasa muncul dua kali (mis. `/tentang-kami` dan `/about-us`); halaman **offline** dan artikel **draf** tidak muncul.
- [ ] Halaman CMS `artikel` (kepala daftar) tidak membuat alamat `/artikel` ganda.
- [ ] Buat halaman CMS baru dan terbitkan: muncul di peta situs paling lambat 10 menit kemudian.
- [ ] Ubah `APP_URL` sementara menjadi `https://domain-anda/jalur` (tidak sah): peta situs tetap berisi alamat yang benar (memakai alamat permintaan), tidak kosong. Pulihkan.
- [ ] `/robots.txt` menampilkan baris `Sitemap: https://domain-anda/sitemap.xml` dan tidak berisi `Disallow: /`.
- [ ] Google Search Console, menu Sitemaps: kirim `sitemap.xml`; statusnya "Berhasil" dan jumlah halaman terbaca sama dengan yang Anda harapkan.
- [ ] `php tools\check-landing.php C:\jalur\landing`: tidak ada PERINGATAN tentang `sitemap_static` atau robots.txt. Tambahkan sementara `Route::view('/uji', ...)` ke `web.php`: ada dua PERINGATAN (slug terlarang dan peta situs); hapus kembali.

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
- [ ] `php tools\check-landing.php C:\jalur\landing`: bagian [5b] hijau. Salin sementara versi LAMA daftar isi dari CMS ke landing: pemeriksa memberi GALAT dengan petunjuk `@js`; pulihkan.

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
| `Livewire\Exceptions\… multiple root elements` | view builder punya >1 elemen akar (slot judul harus di luar akar) |

## 8. Bila masih macet, kirimkan ke saya
1. Pesan galat **lengkap** (Console dan/atau `laravel.log`, tiga baris teratas dari stack trace).
2. Tab Network: *Payload* dari request yang bermasalah (bagian `calls`) dan ukuran responsnya.
3. Isi panel debug editor (salin teksnya).
4. `composer show livewire/livewire laravel/framework | findstr versions` dan `php -v`.
5. Hasil tinker keutuhan (bagian 4) bila datanya yang dicurigai.
