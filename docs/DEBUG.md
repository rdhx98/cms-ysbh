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

## 7. Pesan galat → penyebab
| Pesan | Penyebab |
|---|---|
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
