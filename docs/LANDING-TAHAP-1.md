# Landing tahap 1: halaman dan artikel dari CMS

Dasar: berkas landing yang Anda kirim (`web.php`, `app.css`, `head`, `header`, `footer`, layout `app`, `Navigation`, `Setting`, `User`, `AppServiceProvider`, `app.js`). Hasil tahap ini: halaman CMS tampil di `/{slug}`, artikel di `/artikel/{slug}`, indeks artikel di `/artikel`, menu bisa menunjuk halaman CMS, dan SEO dasar (judul, deskripsi, canonical, Open Graph). Halaman statis Anda (`/about`, `/programs/...`) tetap jalan.

> **Cara tercepat memasang semuanya: `docs/PASANG-LANDING.md`.** Satu perintah (`php tools\pasang-landing.php <landing> <cms>`, lalu `--apply`) menyalin 73 berkas, mencadangkan yang diganti, dan menjalankan pemeriksa. Bagian-bagian di bawah menjelaskan *apa* yang dipasang dan *mengapa*.

## Temuan pada berkas Anda
| # | Temuan | Dampak | Perbaikan |
|---|---|---|---|
| 1 | `header`: `route($link->route_name)` dan `routeIs($link->route_name)` | menu yang tidak punya rute bernama (halaman CMS) atau salah ketik **menjatuhkan seluruh situs** | `Navigation::href` dan `isCurrent()`; rute bernama **atau** kolom `url`; tidak pernah galat |
| 2 | `header`: `<header>` di dalam `<header>` | HTML tidak sah | inner diganti `<div>` |
| 3 | `x-cloak` dipakai 3 kali, tetapi `app.css` tidak punya `[x-cloak]` | menu mobile dan nav lengket **berkedip** sebelum Alpine siap | aturan `[x-cloak]` ditambahkan |
| 4 | kit memakai `charcoal` pada 27 kelas, tema landing tidak mendefinisikannya | pilihan warna "Charcoal" pada Akordion/Unduhan **tanpa gaya** | `--color-charcoal` ditambahkan |
| 5 | `head`: nilai `landing_bg_color` dari basis data langsung dicetak ke CSS | nilai berisi `;}` bisa menyisipkan CSS | hanya `#RRGGBB` diterima; gagal membaca = warna bawaan |
| 6 | `app.css` hanya memindai `../views` | kelas yang hanya ada di PHP kit (rasio, lebar carousel) bisa tidak terbentuk | `@source '../../app'` |
| 7 | rute `/articles` mengarah ke halaman kontak | placeholder | dialihkan 301 ke `/artikel` |
| 8 | halaman CMS membutuhkan renderer **tujuh tipe blok inti** dan `<x-table-of-contents>` yang tidak ada di kit | tipe tanpa renderer **dilewati diam-diam** di situs publik: halaman tampil tanpa judul dan paragraf | **salin dari CMS** lalu jalankan `tools\check-landing.php` (lihat bawah) |

## Dari CMS ke landing: yang disalin, dan yang jangan
**Sudah beres:** `config/cms.php` landing dibuat dari berkas CMS Anda apa adanya (`design`, `lucide`, `fonts` identik, diuji) + `public`, `reserved_slugs`, `articles_index_slug`, `sitemap_static`.

**Salin dari CMS (tujuh berkas)**, dari `resources/views/components/` CMS ke folder yang sama di landing:
```powershell
$cms = "C:\jalur\cms-ysbh"; $landing = "C:\jalur\landing-ysbh"
$render = "$landing\resources\views\components\blocks\render"
New-Item -ItemType Directory -Force $render | Out-Null
foreach ($t in 'heading','paragraph','eyebrow','image','card-builder','step-group','multi-columns') {
    Copy-Item "$cms\resources\views\components\blocks\render\$t.blade.php" $render
}
```
Hanya **tujuh renderer itu**. Daftar isi **tidak** disalin dari CMS (lihat di bawah). Renderer modul (akordion, tombol, callout, unduhan, video, galeri, artikel terbaru) datang dari kit lewat `tools\sync-landing.php`.

**`section-divider` TIDAK perlu disalin dan tidak punya renderer.** Ia bukan komponen yang dirender: mesin seksi (`SectionBuilder::group`) memakainya hanya untuk memecah halaman menjadi seksi (latar, padding, anchor). (Rilis 14 sampai 17 keliru meminta renderer-nya.)

**Daftar isi.** Ada dua bagian yang berbeda. (1) **Pembungkus** (kartu kaca melayang di kiri/kanan, sembunyi saat menabrak gambar layar penuh, hanya tampil di layar 2xl ke atas) dan **perakitan** daftar isi (judul dari heading/pemisah ber-anchor) ada di mesin render `page-preview.blade.php` Anda; terjemahannya di kit (`sections.blade.php`, `SectionBuilder`) dijaga `tests/section-parity-test.php`, yang memuat salinan apa adanya logika Anda sebagai acuan dan membandingkannya pada 6000 dokumen acak (identik; selisih sengaja hanya `dividerId` untuk kanvas). (2) **Isi daftar tautan di dalam kartu** adalah komponen `<x-table-of-contents>` (berkasnya `table-of-contents.blade.php`, yang Anda temukan). **Pakai versi di `landing-app/resources/views/components/table-of-contents.blade.php`**: itu berkas Anda dengan **tiga perbaikan terarah** (selisih 9 baris, akhir baris Windows dipertahankan), dan **salin juga ke CMS** karena versi di sana punya masalah yang sama:
1. **Teks `[cite: 1]` tampil** setelah setiap judul (baris `{{ $item['title'] }}[cite: 1]`; sisa salinan dari alat AI). Dihapus.
2. **Celah XSS.** Anchor disisipkan ke ekspresi Alpine dengan `@click="scrollTo('{{ $item['anchor'] }}')"` dan `:class="activeAnchor === '{{ $item['anchor'] }}' ..."`. `{{ }}` hanya meng-escape HTML; peramban mendekode `&#039;` kembali menjadi `'` sebelum Alpine mengeksekusi atribut, sehingga anchor `x');alert(1);('` menjalankan `alert(1)`. Anchor diisi lewat kolom teks bebas (placeholder "nama-anchor") tanpa validasi di server, jadi seorang penulis bisa menyimpannya, dan ia berjalan di peramban pengunjung (setelah terbit) atau admin (saat pratinjau). Diganti `@js($item['anchor'])`. Dibuktikan dengan **mengeksekusi** ekspresi yang sudah didekode di Node: komponen asli menjalankan kode penyerang; versi perbaikan 0 dari 16 payload (kutip, garis miring terbalik, baris baru, `U+2028`, `${}`, tanda petik balik, `</script>`), dan `scrollTo` menerima string utuh pada 16 dari 16.
3. **Judul kartu "Daftar Isi" selalu Indonesia**, padahal bahasa bawaan situs EN. Kini prop `label`; bawaannya mengikuti bahasa halaman (`id`: Daftar Isi, selain itu: Contents).
Lapis kedua: **anchor blok kini dinormalkan di server** (`BlockSanitizer::clean`, dipakai simpan, kanvas, dan publik): yang sah tidak diubah, "Beban Kasus" menjadi `beban-kasus`, dan `x');alert(1);('` menjadi `x-alert-1`. `tools\check-landing.php` kini juga menolak (GALAT) pola yang sama di tampilan mana pun: nilai `{{ }}` atau `{!! !!}` di dalam ekspresi Alpine, dan memberi PERINGATAN untuk teks `[cite: n]` yang tampil (variabel yang hanya pernah diisi konstanta string, seperti `$activeText` di `card-builder` Anda, tidak dituduh). Jadi bila versi lama dari CMS tersalin ke landing, pemeriksa akan menolaknya.

**Jangan disalin: komponen EDITOR.** Berkas bernama `heading.blade.php` dan kawan-kawannya ada di dua folder CMS: `components/blocks/render/` (tampilan **publik**, ini yang disalin) dan `components/blocks/` atau `blocks/editor/` (UI editor: memuat `x-blocks.editor.wrapper`, `blockId`, `activeLocales`).

**Lalu jalankan pemeriksa** (dari folder zip yang diekstrak):
```powershell
php tools\check-landing.php C:\jalur\landing-ysbh
```
Keluarannya `GALAT`/`PERINGATAN` per butir, dengan petunjuk perbaikan. Berkas renderer asli Anda sudah diuji lewat mesin seksi kit (rilis 18): lihat bagian berikut.

## Penyaring isi publik (rilis 18)
**Masalah.** Renderer inti Anda mencetak isi basis data apa adanya: `{!! $data['text'][$lang] !!}` (judul, paragraf), `href` dari kolom `url` (kartu, tombol kartu), `src` gambar, `style="color: ..."` (eyebrow), `style="grid-template-columns: ..."` (kartu dan kolom), dan `x-dynamic-component :component="'lucide-' . $icon"`. Tidak ada penyaringan di jalur simpan maupun publik. Diuji dengan **renderer asli Anda** dan satu dokumen berisi data jahat: tanpa penyaring, HTML akhir memuat 8 masalah keamanan (`<script>`, `<iframe>`, `onerror`, `onclick`, `javascript:` pada tautan dan gambar) dan 4 sisipan CSS (`position: fixed`); **satu nama ikon yang tidak sah membuat seluruh halaman gagal total (500)**. Dengan penyaring: nol.

**Cara kerjanya.**
- **Teks kaya (judul dan paragraf) disaring HANYA di jalur publik** (`BlockSanitizer::forPublic`, dipakai `PublicLookup::document` dan pratinjau tersimpan), **tidak saat simpan**: data editor tetap utuh; bila daftar putih terlalu ketat, tampilan publik yang terpengaruh, bukan data Anda.
- `RichText` adalah *serializer*: keluarannya hanya tag daftar-putih yang dibangun ulang dengan atribut ter-escape, ditambah teks ter-escape. Format editor Anda dipertahankan (tebal, miring, garis bawah, coret, warna, ukuran dan keluarga huruf, bobot, perataan, indentasi, "pill", daftar, daftar tugas, kutipan, blok kode, tautan, ikon SVG Eyebrow, tabel dan gambar artikel lama). Dibuang: `<script>`, `<style>`, `<iframe>`, `<object>`, formulir, semua atribut `on*`, **atribut Alpine (`x-*`, `@*`, `:*`) dan Livewire (`wire:*`)**, `id`, `javascript:`/`data:`/`vbscript:`, alamat `//host`, dan CSS di luar properti yang dikenal.
- **Tautan internal** `internal://page/{slug}` dan `internal://article/{slug}` (hasil dialog tautan TipTap; model `Page` lama Anda menyelesaikannya lewat `parsed_content`) kini diselesaikan lewat `config('cms.public')`, juga di kartu dan tombol kartu.
- **Tujuh blok inti** disaring secara struktural, non-destruktif (hanya kolom berisiko yang berubah): alamat (`url`), warna eyebrow, lebar kolom (kartu dan multi-kolom), nama ikon, dan daftar id anak. Kolom kelas CSS (margin, radius, dst.) tidak disentuh: ia dicetak ter-escape di dalam `class="..."` dan tidak bisa keluar dari atribut.

**Yang berubah di tampilan publik** (hasil penyaringan, bukan galat):
- Artikel **lama** yang berisi `<iframe>` (mis. sematan YouTube) kehilangan sematan itu di situs publik; isi basis data tetap utuh. Gunakan blok **Video** (tanpa permintaan pihak ketiga sebelum diklik).
- Ikon yang tidak sah pada eyebrow kembali ke `newspaper`, warna yang tidak sah ke `#e05a47`, lebar kolom yang tidak sah ke `1`.
- Ikon yang **sah tetapi tidak ada** di paket ikon (mis. salah ketik `heart-puls`) tetap membuat halaman gagal: penyaring hanya menolak *bentuk* nama yang tidak sah. Gunakan pemilih ikon editor.

## Slug yang dilarang, dan kepala daftar artikel (rilis 15)
**Masalah.** Rute statis (`/about`, `/contact`, `/programs`, ...) didaftarkan lebih dulu daripada `/{slug}`, jadi halaman CMS ber-slug yang sama **tersimpan, tampak online, tetapi tidak pernah terbuka**, tanpa galat apa pun. Editor kini **menolak** slug itu saat menyimpan halaman: `Slug (ID) "about" dipakai oleh alamat tetap situs, sehingga halaman ini tidak akan pernah terbuka. Pilih slug lain.` Berlaku untuk **halaman** saja; artikel ada di `/artikel/{slug}` dan tidak bertabrakan.

Dua sumber daftarnya: yang teknis ada di kode (`Slug::RESERVED`: `articles`, `storage`, `build`, `fonts`, `logo`, `up`, `livewire`, `sitemap`, `robots`, ...), dan rute statis **milik situs** di `config('cms.reserved_slugs')`. **Daftar kedua harus ditambahkan di `config/cms.php` CMS** (berkas Anda; contoh isi: `contoh-kode/config-slug-terlarang.php`). Tanpanya hanya daftar teknis yang berlaku. Setiap rute satu-segmen baru di `routes/web.php` harus ikut ditambahkan; `tools\check-landing.php` memberi PERINGATAN bila ada yang terlewat. **Saat sebuah halaman statis dipindahkan ke CMS (tahap 2), hapus rutenya dan slug-nya dari daftar ini**, kalau tidak editor tidak bisa memakai slug itu.

**Halaman yang sudah terlanjur bertabrakan** (dibuat sebelum penjaga ada): jalankan di CMS `php artisan cms:audit-slugs`. Ia hanya membaca, menampilkan ID, judul, bahasa, dan slug yang bermasalah, dan berakhir dengan kode 1 bila ada. Halaman itu baru bisa disimpan lagi setelah slug-nya diganti.

**Kepala daftar artikel.** Halaman `/artikel` adalah kode landing (data dinamis, berhalaman), tetapi kepalanya boleh diatur dari CMS. Buat halaman CMS ber-slug `artikel` (satu-satunya slug "terlarang" yang sengaja diizinkan; `articles_index_slug`), status online:
- **judul** halaman menjadi judul `<h1>` daftar; **judul SEO** dan **deskripsi SEO**-nya dipakai di `<title>` dan `<meta description>`;
- **blok-bloknya** tampil sebagai **pengantar**, hanya di halaman 1;
- **snippet penutup** (mis. ajakan donasi) tampil **sesudah** daftar dan penomoran, bukan di antara pengantar dan daftar.
Tanpa halaman itu atau bila offline: judul bawaan "Artikel" / "Articles" dan daftar polos. Halaman 2 dan seterusnya diberi nomor di judul dan `canonical` yang menunjuk dirinya sendiri (bukan halaman 1).

**Menu.** Item menu "Artikel" sebaiknya memakai kolom `url` = `/artikel`, bukan `route_name` = `articles`: dengan `url`, menu tetap tampil aktif di `/artikel/{slug}`.

## Peta situs dan robots.txt (rilis 17)
`GET /sitemap.xml` berisi setiap halaman CMS **online** dan artikel **terbit** (satu alamat per slug per bahasa: `/tentang-kami` dan `/about-us` adalah dua alamat), ditambah jalur statis dari `config('cms.sitemap_static')`. `lastmod` dari `updated_at` (artikel: `published_at` bila `updated_at` kosong). Alamat kembar digabung dengan `lastmod` terbaru; halaman CMS `artikel` (kepala daftar) dan jalur statis `/artikel` menjadi satu alamat. Halaman 2 dan seterusnya daftar artikel tidak dimasukkan (kanonik per halaman, bukan sasaran pencarian).

Aturan keras: alamat harus absolut `http(s)` tanpa spasi, kutip, `<`, `>`, `&`, atau query, dan **yang tidak sah dibuang, tidak diperbaiki**; maksimal 50.000 alamat; tanggal hanya `YYYY-MM-DD` yang sah. Keluarannya diuji selalu XML yang sah (termasuk 2500 dokumen dari masukan berbahaya, diurai oleh parser XML ketat pihak ketiga).

**Pasang:**
1. Sinkron kit ke landing (`tools\sync-landing.php --apply`): kini **45 berkas** (`Sitemap.php` baru).
2. Salin dari `landing-app/`: `app/Http/Controllers/SitemapController.php` (baru), `routes/web.php` (+ rute `/sitemap.xml`), `config/cms.php` (+ `sitemap_static`), `public/robots.txt` (ganti milik Laravel).
3. **Pastikan `APP_URL` di `.env` landing berisi alamat situs yang sebenarnya** (mis. `https://ysbh.org`, tanpa jalur). Peta situs memakainya untuk semua alamat; bila `APP_URL` masih `http://localhost`, peta situs akan berisi alamat localhost. Nilai yang bukan `skema://host` diganti alamat permintaan.
4. Ganti `ysbh.org` di `public/robots.txt` bila domainnya lain. Daftarkan `https://domain-anda/sitemap.xml` di Google Search Console (Sitemaps).

**`sitemap_static` harus sama persis dengan rute GET statis di `routes/web.php`** (kecuali pengalihan dan `/sitemap.xml`). Diperiksa `tests/landing-app-test.php` dan `tools\check-landing.php` (PERINGATAN untuk rute yang belum tercatat dan untuk alamat mati). Saat halaman statis dipindahkan ke CMS (tahap 2), hapus jalurnya dari daftar ini **dan** dari `reserved_slugs`: ia masuk dari basis data.

**Kegagalan.** Bila basis data tidak terbaca atau hasilnya kosong, balasannya **503**, bukan peta kosong berstatus 200: mesin pencari bisa menafsirkan peta kosong sebagai "semua halaman sudah dihapus". Respons di-cache publik 10 menit; halaman baru muncul paling lambat sesudah itu.

**Bahasa.** Peta situs belum memuat `hreflang`; itu bagian pengalih bahasa (tahap berikutnya).

## Catatan konfigurasi (dari berkas yang Anda kirim)
- `config/cms.php` → `public.cover`: **`/storage/articles/{file}`** (editor artikel menyimpan nama berkas saja dan membacanya dari `storage/articles/`). Bukan `/storage/posts/`.
- `APP_LOCALE=en` dan `APP_FALLBACK_LOCALE=en`: bagus. Tambahkan di `config/app.php` **CMS dan landing**: `'supported_locales' => ['en', 'id'],` (bahasa bawaan lebih dulu). Tanpa itu kit memakai `['id', 'en']`.
- `SESSION_DRIVER=database` dan `CACHE_STORE=database` berarti landing **menulis** ke basis data (`sessions`, `cache`). Maka pengguna basis data landing **tidak bisa baca-saja**; model landing tetap menolak menulis data konten (`ReadOnlyModel`), tetapi perlindungan tingkat basis data hanya berlaku bila Anda memakai `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`.
- `APP_URL=http://landing-ysbh.test` hanya untuk pengembangan; di produksi isi alamat sebenarnya (dipakai peta situs).
- `composer.json`: Laravel ^13.7, Livewire ^4.1 (komponen `⚡` didukung), `mallardduck/blade-lucide-icons`, `spatie/laravel-translatable`. Tidak ada `spatie/laravel-activitylog` di landing, dan model landing memang tidak memakainya.

## Pemasangan (urut)
1. **Sinkron kit ke landing** (dari folder zip): `php tools\sync-landing.php C:\jalur\landing` lalu `--apply`. 47 berkas (rilis 18: `RichText.php` dan `CoreBlocks.php` baru).
2. **Salin berkas di bawah ke landing** (folder `landing-app/` di zip; sama dengan yang ada di pesan):

| Berkas | Tujuan di landing | Status |
|---|---|---|
| `app/Models/ReadOnlyModel.php`, `Page.php`, `Post.php`, `Category.php`, `Snippet.php`, `Media.php` | `app/Models/` | **baru** |
| `app/Models/Navigation.php` | `app/Models/` | **ganti** (menambah `href` dan `isCurrent()`; selebihnya sama) |
| `resources/views/components/layouts/header.blade.php` | sama | **ganti** (3 penggantian terarah pada berkas Anda) |
| `resources/views/partials/head.blade.php` | sama | **ganti** (SEO; warna divalidasi; blok CSS lama yang dikomentari dihapus) |
| `resources/css/app.css` | sama | **ganti** (3 tambahan; isi lain tidak berubah) |
| `routes/web.php` | sama | **ganti** (rute lama utuh; `/articles` dialihkan; 3 rute baru di akhir) |
| `resources/views/components/⚡page-show.blade.php`, `⚡article-show.blade.php`, `⚡articles-index.blade.php` | sama | **baru** |
| `resources/views/errors/404.blade.php` | sama | **baru** |
| `config/cms.php` | `config/cms.php` | **baru**: salinan `config/cms.php` CMS Anda + `public`, `reserved_slugs`, `articles_index_slug`, `sitemap_static`. Bila landing sudah punya `config/cms.php`, tambahkan keempat kunci itu saja |
| `app/Http/Controllers/SitemapController.php`, `public/robots.txt` | sama | **baru** (rilis 17): peta situs dan robots.txt |
| `resources/views/components/table-of-contents.blade.php` | sama (**juga di CMS**) | **ganti/baru** (rilis 20): berkas Anda + 3 perbaikan (XSS, `[cite: 1]`, bahasa judul) |
| `resources/views/components/⚡articles-index.blade.php` | sama | **ganti** (rilis 15): kepala dari halaman CMS, pengantar, penutup, canonical per halaman |

3. **Sisi CMS (rilis 15)**: salin ke CMS `app/Content/Slug.php`, `ContentRules.php`, `SlugAudit.php`, `Rules/ReservedSlug.php`, dan `app/Console/Commands/AuditSlugs.php` (juga `PublicLookup.php`, yang sama dengan di landing). Tambahkan dua kunci dari `contoh-kode/config-slug-terlarang.php` ke `config/cms.php` CMS, lalu `php artisan optimize:clear` dan `php artisan cms:audit-slugs`.
4. `.env` landing: `CMS_PUBLIC_URL=` (kosong), `CMS_COVER_TEMPLATE=/storage/posts/{file}` (sesuaikan), dan `MEDIA_URL=https://ysbh.org/storage` (lihat `DUA-APLIKASI.md`). Bila memakai pengguna basis data baca-saja: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`.
5. Salin tujuh renderer dari CMS (bagian "Dari CMS ke landing"); daftar isi dari `landing-app/` (dan ke CMS), lalu `php tools\check-landing.php C:\jalur\landing`.
6. `php artisan view:clear`, `npm run build`, `php tests\blade-scan.php C:\jalur\landing`.

## Menyematkan blok di halaman Blade statis
Renderer blok bisa dipakai di halaman biasa tanpa CMS, mis. di beranda:
```blade
<x-blocks.render.latest-articles-builder :data="['limit' => '3', 'columns' => '3']" :lang="app()->getLocale()" />
```

## Rencana berikutnya
- **Tahap 2**: memindahkan halaman statis (tentang, kredibilitas, transparansi, dampak, program) ke halaman CMS satu per satu; menu dari `url`; footer dari data.
- **Tahap 3**: bahasa (pengalih ID/EN yang berfungsi, mengikuti slug saudara), peta situs, cache halaman, penyebaran di Hostinger.
- **Keputusan bahasa** yang saya perlukan sebelum tahap 3: slug berbeda per bahasa membuat URL berbeda per bahasa (`/tentang-kami` dan `/about-us`), yang baik untuk SEO. Pengalih bahasa lalu menuju slug saudaranya. Setuju?

## Pemeriksaan
`DEBUG.md` bagian **6l**. Pengujian: `tests/landing-app-test.php` (30), `tests/landing-nav-test.php` (25), kompilasi 18 tampilan landing di dua versi Laravel, dan render header (7).
