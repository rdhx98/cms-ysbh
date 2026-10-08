# Perubahan per rilis

## Rilis 25: kerangka halaman situs dan tempat NPWP/rekening

**Urutan rilis:** … → 24 → 25. Panduan: `docs/HALAMAN-SITUS.md`. **Hanya CMS** (landing tidak berubah; berkas bersama landing sama dengan rilis 24).

### Keputusan pengguna
Halaman program datar dengan pola "[nama program]-program" (EN: `malaria-program`, `immunization-program`, `maternal-child-health-program`, `tb-program`, `hiv-program`; indeks `programs`). ID memakai urutan Indonesia (`program-malaria`, ...). NPWP dan rekening: **hanya tempat**, tanpa nilai, menunggu keputusan yayasan.

### Baru
- `php artisan cms:seed-pages [--apply]`: membuat 12 halaman kerangka (beranda, tentang, indeks program, lima program, kredibilitas, dampak, transparansi, kontak), semuanya **offline**, judul dan paragraf penanda di EN dan ID; dan snippet `legal-details` (NPWP, rekening giro/bank) berisi penanda saja, disisipkan di halaman Transparansi. Bawaan hanya rencana; slug yang sudah dipakai dilewati; tidak pernah menimpa.
- `php artisan cms:audit-placeholders [--all]` (hanya membaca): halaman online, artikel terbit, dan snippet online yang masih memuat `[ISI-DULU]`; kode keluar 1 bila ada.
- `app/Content/SitePages.php` (daftar halaman, isi awal, pemeriksa slug) dan `app/Content/Placeholders.php`, keduanya murni. `tests/site-pages-test.php` (25 uji, termasuk: tidak ada angka di teks snippet).
- `docs/HALAMAN-SITUS.md`; `tools/pasang-cms.php` menyebut perintahnya sebagai langkah opsional.

### Yang harus Anda lakukan
Pasang CMS dari zip ini, pastikan `reserved_slugs` per bahasa sudah kosong (rilis 24), lalu `php artisan cms:seed-pages` (lihat rencana) dan `--apply`.

### Pembuktian
34 rangkaian lulus (sebelumnya 33), baru: `kerangka-halaman`. 17 mutan pada `SitePages`, `Placeholders`, dan kedua perintah semuanya terbunuh (satu mutan setara, cabang JSON di `Placeholders`, dihapus dari kode).
**Belum terbukti:** perintah `cms:seed-pages` dan `cms:audit-placeholders` tidak dijalankan di aplikasi sungguhan (hanya logika murni dan kontrak statis yang diuji); pembuatan `Page` bergantung pada tabel `pages` Anda (kolom wajib lain di luar `title`, `slug`, `status`, `content` tidak diketahui kit).

## Rilis 24: situs dua bahasa (EN tanpa awalan, ID di `/id`)

**Urutan rilis:** … → 23 → 24. Keputusan dan aturan: `docs/BAHASA.md` (disetujui pengguna). **Berubah di CMS juga:** banyak berkas bersama (bahasa, alamat, pencarian, peta situs, slug), jadi **pasang CMS dan landing dari zip yang sama**.

### Mengapa
Bahasa bawaan situs EN (donasi internasional), ID untuk pembaca lokal. Sebelumnya pengalih ID/EN di header hanya mainan Alpine dan pencarian halaman menerima slug bahasa mana pun (salinan ganda tanpa kendali).

### Aturan (satu alamat per bahasa)
EN tanpa awalan (`/`, `/about-us`, `/articles`, `/articles/{slug}`); ID dengan awalan (`/id`, `/id/tentang-kami`, `/id/artikel`, `/id/artikel/{slug}`). Slug berbeda per bahasa. Tanpa `?lang`, tanpa cookie, **tanpa pengalihan otomatis** dari `Accept-Language`/IP. Slug bahasa lain di alamat ini = 301 ke padanannya; belum diterjemahkan = 404.

### Baru
- `app/Content/Languages.php` (murni): bahasa dari alamat, `setting()` (teks atau peta per bahasa), alamat beranda dan slug bawaan per bahasa, `fromConfig()`, `forRequest()` (menetapkan `app()->setLocale` dari alamat; tanpa middleware global, `bootstrap/app.php` Anda tidak diubah).
- `app/Content/NavLinks.php` (murni): kolom `navigations.url` (satu isian) menuju halaman yang sama di bahasa pembaca.
- `app/Content/TranslationAudit.php` + `php artisan cms:audit-translations {--locale=} {--all} {--strict}` (hanya membaca): temuan `belum`, `separuh`, `isi`; kode keluar 1 untuk `separuh`/`isi`. `&nbsp;`, paragraf kosong, dan ruang lebar nol dihitung kosong.
- `landing-app/resources/views/components/layouts/lang-switch.blade.php`: pengalih bahasa = tautan biasa ke versi bahasa lain halaman ini (atau beranda bahasa itu).
- `tests/languages-test.php` (65 uji), `tests/translation-audit-test.php` (33 uji).

### Berubah
- **Rute landing** (`landing-app/routes/web.php`): `/`, `/articles`, `/articles/{slug}`, `/id`, `/id/artikel`, `/id/artikel/{slug}`, `/id/{slug}`, `/{slug}` (paling akhir). Nama: `home`/`id.home`, `articles`/`id.articles`, `article.show`/`id.article.show`, `page.show`/`id.page.show`. **Dihapus:** `/artikel` dan `/artikel/{slug}` (tanpa awalan) serta pengalihan `/articles` ke `/artikel`.
- **`config/cms.php` per bahasa** (teks lama tetap diterima): `default_locale`, `reserved_slugs`, `home_slug`, `articles_index_slug`, `public.page|article|home|articles`. Contoh: `contoh-kode/config-slug-terlarang.php`, `contoh-kode/config-dua-aplikasi.php`. Landing: `sitemap_static` = `["/articles", "/id/artikel"]`.
- `Slug::RESERVED` tidak lagi memuat `articles`; di bahasa bawaan kode bahasa lain (`id`) terlarang. `ReservedSlug`, `ContentRules`, `SlugAudit`, `cms:audit-slugs` memeriksa **per bahasa**.
- `LinkResolver`: templat alamat per bahasa, `homeAddress()`, `indexAddress()`, `pathUrl()`; rute cadangan `id.page.show`/`id.article.show`; `make()` memakai slug bahasa lain bila halaman belum diterjemahkan.
- `PublicLookup`: `findPage`/`findArticle` **ketat per bahasa** dan mengembalikan `['model','locale','redirect']`; + `alternates`, `alternateUrls`, `sibling` (diingat per permintaan; `flush()`), `internalLinks`; `document(..., $lang)` menyelesaikan `internal://` ke bahasa halaman; kartu, daftar, dan `articlesHeader` per bahasa; peta situs membawa bahasa tiap baris dan melewati judul yang belum diterjemahkan.
- `Sitemap::entries`: templat dan jalur beranda per bahasa. `Names::exact()`: hanya nilai bahasa itu. `ArticleCards`: tanpa cadangan lintas bahasa.
- Landing: `⚡page-show`, `⚡article-show`, `⚡articles-index` menetapkan bahasa dari alamat, 301 ke saudara, `hreflang` + `x-default` di `head`, pengalih di `header`, `Navigation::href` mengikuti bahasa pembaca, `404`/`503` mengikuti bahasa alamat, `SitemapController` memuat kedua bahasa.
- `tools/check-landing.php`: memeriksa config dan rute **per bahasa**, slug terlarang per bahasa, beranda tiap bahasa bukan jalur statis. `tools/pasang-cms.php` dan `tools/pasang-landing.php`: teks langkah berikutnya (config per bahasa, `optimize:clear`, audit terjemahan).
- Berkas bersama landing 47 → 49 (`Languages`, `NavLinks`); `landing-app/` 20 → 21 berkas.

### Yang harus Anda lakukan
1. Pasang CMS dan landing dari zip ini (`tools\pasang-cms.php`, `tools\pasang-landing.php`, lalu `--apply`). Pemasang landing menimpa `config/cms.php` landing (dicadangkan); `config/cms.php` **CMS tidak pernah disentuh**.
2. `config/cms.php` CMS: ganti ke bentuk per bahasa (`default_locale`, `reserved_slugs`, `home_slug`, `articles_index_slug`, `public.base|page|article|home|articles|cover`) seperti `contoh-kode/config-slug-terlarang.php`. `config/app.php` kedua aplikasi: `'supported_locales' => ['en', 'id']`. `.env`: `APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en`; landing `APP_URL=https://ysbh.org`; CMS `CMS_PUBLIC_URL=https://ysbh.org`.
3. `php artisan optimize:clear` di keduanya, `npm run build`.
4. Menu (`navigations`): baris yang `route_name`-nya rute yang dihapus: isi kolom `url` (mis. `/about-us`, `/articles`, `/`).
5. Buat halaman beranda: slug `home` (EN) dan `beranda` (ID) pada satu halaman; kepala daftar artikel: `articles` / `artikel`.
6. `php artisan cms:audit-slugs` dan `php artisan cms:audit-translations`; lalu `docs/DEBUG.md` bagian 6q.

### Pembuktian
33 rangkaian lulus (sebelumnya 31): + `bahasa (murni)` (65) dan `audit-terjemahan` (33). `data` 246, `slug-terlarang` 30, `landing-app` 67, `check-landing` 83, `pasang-landing` 65, `pasang-cms` 40, `landing-nav` 37, `sitemap (data)` 16, `kepala-artikel` 17. **Uji mutasi:** 62 mutan pada logika bahasa (Languages, Slug, NavLinks, LinkResolver, Sitemap, PublicLookup, TranslationAudit, ⚡page-show, head, 503, rute, config, check-landing), semuanya terbunuh; mutan yang setara (tidak mengubah perilaku) disederhanakan dari kodenya.
**Belum terbukti:** komponen `⚡` belum dijalankan di Livewire sungguhan (kontrak statis, kompilasi, dan logika murni yang dipakainya sudah diuji; `mount(?string $slug = null)` pada rute tanpa parameter dan `hydrate()` perlu dicoba di peramban: `docs/DEBUG.md` 6q). Pemasang diuji di Linux, belum di Windows. Hostinger belum diuji (apakah CMS dapat menulis ke folder storage landing). Terjemahan Inggris seluruh halaman belum ada (menunggu penulis dan peninjau).

## Rilis 23: landing ramping, semua isi situs dari CMS

**Urutan rilis:** … → 22 → 23. Panduan: `docs/LANDING-RAMPING.md`. **Berubah di CMS juga:** hanya dua berkas bersama, `app/Content/Links/LinkResolver.php` dan `app/Content/Sitemap.php` (pasang CMS lagi dari zip ini).

### Mengapa
Pengguna menyatakan semua halaman landing (beranda, tentang, kredibilitas, dampak, indeks program) hanyalah prototipe dari awal percakapan, bahwa yang dipertahankan hanya layout aplikasi, dan bahwa halaman program dibuat dengan model `Page`. Maka rute dan halaman Blade statis dihapus; seluruh isi situs dari CMS. Konsekuensinya, tiga halaman CMS yang selama ini tersembunyi di balik rute statis (slug bertabrakan, ditemukan `cms:audit-slugs`) kembali bisa dibuka.

### Baru dan berubah
- `landing-app/routes/web.php`: tinggal `/` (beranda), `/sitemap.xml`, `/robots.txt`, `/articles` (301), `/artikel`, `/artikel/{slug}`, `/{slug}`. Tanpa `Route::view`. Nama rute yang hilang: `about`, `contact`, `programs`, `programs-*`, `credibility`, `transparancies`, `impact`.
- **Beranda dari CMS.** `⚡page-show` menerima `$slug` opsional; rute `/` tanpa parameter memuat halaman ber-slug `config('cms.home_slug')` (`home`). `/home` dialihkan 301 ke `/` (sebelum membaca basis data). Beranda belum dibuat atau offline = **503** "Situs sedang disiapkan" dengan `Retry-After` (bukan 404, agar situs tidak dianggap hilang). `LinkResolver::homeUrl` (baru, murni) dan `LinkResolver::address` menjadikan tautan internal ke halaman itu `/`; `Sitemap::entries(..., $homeSlug)` memasukkannya sebagai `{base}/`, bukan `{base}/home` (yang hanya mengalihkan).
- Judul beranda ditentukan penuh oleh editor (judul SEO halaman), tanpa akhiran " | nama situs".
- **`/robots.txt` dinamis** (`RobotsController`, `RobotsController::body` murni): alamat peta situs mengikuti `APP_URL`. Berkas statis `public/robots.txt` dihapus dari kit (berkas lama menulis `https://ysbh.org/sitemap.xml` tetap; itu cocok dengan domain produksi, tetapi tidak mengikuti lokal `landing-ysbh.test` atau domain lain, dan berkas statis juga akan menutupi rute ini, jadi pemasang menghapusnya dengan cadangan).
- `errors/404` dua bahasa (sebelumnya hanya Indonesia, padahal bahasa bawaan situs Inggris); `errors/503` baru, dua bahasa.
- `config/cms.php` landing: `reserved_slugs` **kosong**; `+ home_slug`; `sitemap_static` = `["/artikel"]`. `contoh-kode/config-slug-terlarang.php` mengikuti (tiga kunci).
- **`tools/pasang-landing.php`**: kelompok baru **H** (hapus) dari `landing-hapus.txt`: 12 halaman Blade prototipe dan `public/robots.txt`, selalu dicadangkan lebih dulu. Hanya jalur di `resources/views/pages/` dan `public/robots.txt` yang diterima; tautan simbolik, folder, bentrok dengan berkas yang dipasang, dan jalur tidak aman ditolak **sebelum menulis**. Pemeriksaan baru `tools/rute-hilang.php`: bila berkas Anda yang lain (footer, menu, layout) masih memanggil `route('about')` dan sejenisnya, pemasangan **ditolak sebelum mengubah apa pun**, dengan daftar berkas:baris dan penggantinya (`url('/about')`). `--abaikan-rute-hilang` melewatinya. Berkas yang akan diganti atau dihapus pemasang tidak dihitung; komentar dan nama lain (`about-us`, `routeIs('about')`) tidak dituduh.
- `tools/check-landing.php`: beranda (`/` → `page-show`) bukan jalur statis; `public/robots.txt` yang masih ada padahal rute `/robots.txt` ada = GALAT; seksi baru 3d (route() ke rute yang dihapus = GALAT dengan berkas:baris); `home_slug` tidak sah = PERINGATAN.
- Sisa dari awal sandbox: `tests/landing-app-test.php` mewajibkan `/mnt/user-data/uploads/cms.php` (berkas Anda) dan jatuh bila tidak ada; kini dibandingkan hanya bila berkas itu ada.
- `MANIFEST.sha256`: hash dihitung dengan akhir baris dinormalkan ke LF, sama seperti fungsi PowerShell di README (sebelumnya satu berkas ber-CRLF, daftar isi, dilaporkan "BEDA" padahal sama).

### Alamat yang dipakai yayasan
Lokal `cms-ysbh.test` dan `landing-ysbh.test`; produksi landing `ysbh.org` dan CMS `cms.ysbh.org` (panduan sebelumnya menyebut `kelola.ysbh.org`; sudah diganti). Tabel `.env` lokal dan produksi ada di `docs/DUA-APLIKASI.md`.

### Yang harus Anda lakukan
Lihat `docs/LANDING-RAMPING.md`: pasang CMS dan landing dari zip ini; di config CMS kosongkan `reserved_slugs` dan tambahkan `home_slug`; perbaiki baris menu yang memakai rute lama (isi kolom `url`); buat halaman beranda berslug `home`; isi `APP_URL`.

### Pembuktian
31 rangkaian lulus (sebelumnya 29), dua baru: `tests/beranda-test.php` (39 uji termasuk 4000 kombinasi acak) dan `tests/rute-hilang-test.php` (52 uji). `tests/pasang-landing-test.php` 34 → 65, `tests/check-landing-test.php` 60 → 73, `tests/landing-app-test.php` 61 → 62. **Uji mutasi:** 44 perusakan kode yang disengaja (satu per satu; pengaman H, pemindai, beranda, robots, pengalihan, 503, penerusan `home_slug` ke peta situs) semuanya tertangkap. Tiga yang sempat lolos (`home_slug` tidak diteruskan ke pengendali peta situs; dua cabang pesan rencana saat hanya ada penghapusan) ditambahi uji sampai tertangkap.
**Belum terbukti:** komponen `⚡page-show` dengan `mount(?string $slug = null)` pada rute tanpa parameter belum dijalankan di Livewire sungguhan (hanya kontrak statis, kompilasi, dan logika murni yang diuji); `abort(503)` dari `mount` memakai jalur yang sama dengan 404 yang sudah ada. Pemasang diuji di Linux, bukan Windows.

## Rilis 22: pemasang CMS (`tools/pasang-cms.php`) untuk CMS yang masih di rilis 12

**Urutan rilis:** … → 21 → 22. **Tidak ada kode aplikasi yang berubah** (app, resources, database, landing-app, landing-files.txt, HAPUS.txt identik dengan rilis 21, diperiksa byte demi byte). Panduan: `docs/PASANG-CMS.md`.

### Mengapa
Pengguna menyatakan CMS-nya masih di rilis 12, sedangkan pemasang rilis 21 hanya untuk landing. Sembilan rilis sesudahnya mengubah berkas yang juga dipakai CMS. Memasangnya dengan tangan (102 berkas kit di tiga folder, ditambah daftar isi perbaikan dan daftar penghapusan) rawan terlewat.

### Baru
`php tools\pasang-cms.php <cms>` (melihat rencana) dan `... --apply` (memasang): semua berkas kit di `app/`, `resources/`, `database/` (102), daftar isi perbaikan, dan berkas di `HAPUS.txt` bila ada di CMS. Tidak memasang `landing-app/`, `tests/`, `tools/`, `docs/`, `contoh-kode/`, `patch/`, `opsional/`.
- Pengaman seperti `pasang-landing.php`: bawaannya hanya melihat; semua pemeriksaan sebelum menulis (satu masalah = tidak ada yang berubah); cadangan setiap berkas yang diganti **dan yang dihapus** ke `storage/pasang-cadangan/<tanggal-jam>/` sebelum mengubah apa pun; berkas yang sama tidak disentuh; tautan simbolik dan tujuan di luar CMS ditolak.
- **Pengaman khusus CMS: jalur milik pengguna tidak pernah ditimpa atau dihapus**, walaupun suatu hari kit memuat jalur yang sama: model (`Page`, `Post`, `Category`, `User`, `Setting`, `Navigation`, `Tag`, `PlainTag`), `app/Providers`, `app/Http`, `routes`, `config`, `bootstrap`, `public`, `storage`, `lang`, `database/seeders`, `database/factories`, `.env`, `vendor`, `node_modules`. Jalur ini juga ditolak di `HAPUS.txt`. Saat ini kit tidak bertabrakan dengan satu pun (diperiksa: `app/Models` kit berisi `Media`, `MediaFolder`, `MediaUsage`, `Snippet`, `SnippetUsage`).
- Menjalankan pemindai direktif Blade pada CMS sesudahnya dan mencetak langkah yang harus dilakukan sendiri (dua kunci `config/cms.php`, `supported_locales`, `optimize:clear`, `npm run build`, `cms:audit-slugs`). Alat tidak mengubah config, rute, atau `.env`.

### Perkiraan perubahan dari rilis 12 (dijelaskan di `docs/PASANG-CMS.md`)
6 berkas baru (`AuditSlugs`, `ReservedSlug`, `SlugAudit`, `RichText`, `CoreBlocks`, `Sitemap`), 7 diganti (`PublicLookup`, `BlockSanitizer`, `ContentRules`, `Slug`, `body.blade`, `latest-articles-builder`, `table-of-contents`), sekitar 90 sama. **Tidak ada migrasi baru sejak rilis 12.** (Perhitungan dari folder rilis tersimpan sempat menandai `BlockSanitizer`, `ContentRules`, `Slug`, dan `body.blade` sebagai "baru" karena folder rilis 6 sampai 12 hanya memuat perubahan per rilis, bukan keadaan penuh; daftar di atas sudah dikoreksi dengan pengetahuan riwayat.)

### Pembuktian
`tests/pasang-cms-test.php`: 40 uji pada CMS tiruan, termasuk CMS yang **tertinggal** (6 berkas versi lama, 4 berkas hilang, daftar isi versi lama, dan 2 sisa yang harus dihapus): rencana 4 baru, 7 diganti, 2 dihapus; sesudah `--apply`, sembilan berkas lama ada di cadangan dengan isi aslinya dan semua berkas kit identik dengan sumbernya; berkas milik pengguna (model, rute, config, `.env`, `vendor`, renderer `heading`/`paragraph` buatan sendiri, seeder) tidak berubah. Perusakan: 16 dari 16 terdeteksi. **Dua mutasi sempat selamat karena pengujian saya sendiri:** blok uji tautan simbolik **diam-diam dilewati** dengan pesan yang keliru ("sistem ini tidak mengizinkan tautan simbolik"), padahal penyebabnya folder `app/` sudah ada sebagai folder biasa di fixture. Kini kemampuan tautan diperiksa terpisah, dan bila mampu tetapi pembuatan tautan gagal, uji **gagal keras** (tidak dilewati).

### Berubah
`tests/run-all.php` (29 rangkaian), `docs/PENGUJIAN.md`, `docs/PASANG-LANDING.md` (penunjuk ke panduan CMS).

## Rilis 21: satu perintah untuk memasang semua berkas landing (`tools/pasang-landing.php`)

**Urutan rilis:** … → 20 → 21. **Tidak ada kode aplikasi yang berubah** (app, resources, landing-app, landing-files.txt identik dengan rilis 20, diperiksa byte demi byte). Panduan: `docs/PASANG-LANDING.md`.

### Mengapa
Pengguna melaporkan tidak tahu cara memakai alat-alat kit. Memang: ada tiga alat terpisah (`sync-landing`, `check-landing`, `landing-files`), `sync-landing` hanya mencakup 47 dari **73** berkas yang harus sampai ke landing dan menimpa tanpa cadangan, dan 26 berkas sisanya (19 dari `landing-app/`, 7 dari CMS) harus disalin dengan tangan ke banyak folder bersarang.

### Baru
`php tools\pasang-landing.php <landing> <cms>` (melihat rencana) dan `... --apply` (memasang): satu perintah untuk ketiga kelompok (A: 47 berkas kit; B: 19 berkas `landing-app/`; C: 7 renderer dari CMS).
- **Bawaannya hanya melihat.** Tidak ada yang ditulis tanpa `--apply`.
- **Tidak pernah setengah jadi:** seluruh pemeriksaan dilakukan sebelum menulis; satu masalah = tidak ada yang berubah. Termasuk: folder landing/CMS yang bukan proyek Laravel, landing sama dengan CMS atau dengan/di dalam folder kit, renderer CMS yang hilang atau kosong atau ternyata **komponen editor**, jalur tidak aman di daftar berkas (`..`, absolut, `C:`, garis miring terbalik), dan **tautan simbolik** di dalam landing yang menunjuk ke luar (sebelumnya pagar ini baru berjalan di tengah penulisan, sehingga sebagian berkas sudah tertulis; dipindah ke pemeriksaan awal setelah uji perusakan menunjukkan celahnya).
- **Selalu mencadangkan** setiap berkas yang akan diganti ke `storage/pasang-cadangan/<tanggal-jam>/` (jalur yang sama), sebelum mengganti apa pun; bila folder cadangan tidak bisa dibuat, tidak ada yang diganti. Berkas yang sama tidak disentuh (idempoten).
- **Tidak pernah menyentuh** `.env`, `vendor`, `node_modules`, `database`, atau berkas landing lain di luar daftar; CMS hanya dibaca.
- Menjalankan `tools/check-landing.php` otomatis sesudahnya dan mencetak langkah berikutnya.
- Pesan dalam bahasa Indonesia sederhana. Pada pemasangan ulang yang tidak mengubah apa pun, tidak mencetak "SELESAI" (sempat mencetak "SELESAI: 0 berkas dipasang" dan diperbaiki).

### Pembuktian
`tests/pasang-landing-test.php`: 34 uji pada proyek landing dan CMS tiruan di folder sementara, memakai tujuh renderer asli yang diunggah pengguna sebagai CMS, termasuk: rencana tidak menulis; setiap berkas di tujuan identik dengan sumbernya; `.env`/vendor/node_modules/database/model `User` tetap utuh; pemasangan ulang tidak mengubah apa pun; cadangan berisi isi lama dengan jalur sama; kegagalan membuat folder cadangan tidak mengganti apa pun; setiap penolakan tidak mengubah landing. Perusakan: 16 dari 16 terdeteksi. **Putaran pertama 3 mutasi selamat** karena pengujian saya sendiri terlalu longgar: tiga penjaga dilindungi oleh penjaga lain yang kebetulan menolak dengan kode yang sama (folder kit tidak punya `artisan` sehingga ditolak sebagai "bukan Laravel" sebelum penjaga "sama dengan kit" teruji; jalur `../evil.php` tidak ada sehingga ditolak sebagai "tidak ada" sebelum penjaga jalur teruji). Pengujian ditulis ulang agar setiap penjaga diuji sendirian dengan memeriksa pesan yang spesifik, dengan berkas yang benar-benar ada di jalur berbahaya.

### Berubah
`tests/run-all.php` (28 rangkaian), `docs/PENGUJIAN.md`, `docs/LANDING-TAHAP-1.md` (penunjuk ke panduan baru).

## Rilis 20: daftar isi dari CMS Anda (tiga perbaikan, termasuk celah XSS) dan normalisasi anchor

**Urutan rilis:** … → 19 → 20. Panduan: `docs/LANDING-TAHAP-1.md` bagian "Daftar isi". Pemeriksaan: `docs/DEBUG.md` 6p.

### Temuan pada `table-of-contents.blade.php` (berkas yang Anda temukan)
1. **Teks `[cite: 1]` tampil** setelah setiap judul di daftar isi (`{{ $item['title'] }}[cite: 1]`; sisa salinan dari alat AI).
2. **Celah XSS.** `@click="scrollTo('{{ $item['anchor'] }}')"` dan `:class="activeAnchor === '{{ $item['anchor'] }}' ..."` menyisipkan anchor ke ekspresi Alpine. `{{ }}` hanya meng-escape HTML dan peramban mendekodenya kembali sebelum Alpine mengeksekusi, jadi apostrof di anchor keluar dari literal string. Anchor diisi lewat kolom teks bebas **tanpa validasi di server** (di editor, trait, maupun kit). **Dibuktikan dengan eksekusi** (ekspresi yang sudah didekode dijalankan di Node dengan semantik Alpine): `x');alert(1);('` menjalankan `alert(1)` pada komponen asli; 3 payload lain menyebabkan galat sintaks yang mematikan klik.
3. **Judul kartu selalu "Daftar Isi"**, padahal bahasa bawaan situs EN.

### Perbaikan (dua lapis; masing-masing cukup sendirian)
- **Komponen**: `landing-app/resources/views/components/table-of-contents.blade.php` adalah berkas Anda dengan penggantian terarah (selisih 9 baris; akhir baris Windows dipertahankan): `@js($item['anchor'])` di dua tempat, `[cite: 1]` dihapus, dan prop `label` (bawaan: `id` -> "Daftar Isi", selain itu "Contents"). **Salin juga ke CMS.** Hasil dieksekusi di Node: 16 payload (kutip, garis miring terbalik, baris baru, `U+2028`, `${}`, tanda petik balik, `</script>`): 0 eksekusi, `scrollTo` menerima string utuh 16 dari 16, kelas aktif/tak aktif benar 16 dari 16, 0 galat sintaks.
- **Server**: `BlockSanitizer::anchor()` dipanggil di `clean()` (jalur simpan, kanvas, dan publik). Anchor sah (`^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$`) tidak diubah; selain itu dinormalkan seperti slug ("Beban Kasus" -> `beban-kasus`, `x');alert(1);('` -> `x-alert-1`); bukan teks atau hasilnya kosong -> "". Jalur kanvas ikut terlindungi (sebelumnya seorang penulis bisa menyerang admin yang membuka pratinjau drafnya).

Ujung ke ujung (anchor jahat -> `forPublic` -> mesin seksi kit -> HTML -> Node): data mentah + komponen asli = **kode penyerang jalan**; komponen perbaikan saja = 0; penyaring server saja = 0; keduanya = 0.

### Pemeriksa landing
`tools/check-landing.php` bagian **[5b]**: nilai `{{ }}` atau `{!! !!}` di dalam ekspresi Alpine (`x-*`, `@*`, `:*`) adalah **GALAT** (gunakan `@js()`), dan teks `[cite: n]` yang tampil (di luar komentar Blade) adalah PERINGATAN. Bila versi LAMA daftar isi dari CMS tersalin ke landing, pemeriksa menolaknya (di baris sebenarnya 65 dan 67).

**Positif palsu yang saya sendiri perbaiki.** Versi pertama pemeriksa ini menandai `card-builder.blade.php` **Anda** (`x-bind:class="open ? '{{ $activeText }}' : ..."`), padahal `$activeText` berasal dari `match ($theme) { "coral" => "...", default => "..." }`: konstanta tetap yang tidak bisa dipengaruhi penyerang, jadi di berkas Anda itu **bukan celah**. (Pencarian `grep` saya sebelumnya tidak menemukannya karena polanya berbeda.) Pemeriksa kini mengenali variabel yang HANYA pernah diisi konstanta string (`$x = "teks";` atau `match` yang semua cabangnya literal) dan melewatinya; variabel dari data, cabang `match` berisi variabel, penugasan campuran, variabel yang tidak diisi di berkas itu (prop), indeks larik, dan `{!! !!}` selalu ditandai. Nomor baris pada laporan juga sempat menunjuk baris yang salah (komentar Blade multibaris menggesernya); kini baris sebenarnya.

### Baru, dihapus, berubah
Baru: `landing-app/resources/views/components/table-of-contents.blade.php`. Dihapus: `landing-app/_sementara/table-of-contents.blade.php` (stand-in saya). Berubah: `BlockSanitizer.php` (+ `anchor()`), `tools/check-landing.php`, `tools/landing-files.php` (tanpa `_sementara`), `tests/core-blocks-test.php` (147 uji; +28), `tests/check-landing-test.php` (60; +16), `tests/landing-app-test.php` (49; +5), dan dokumen. **Tidak ada rangkaian baru; `landing-files.txt` tetap 47 berkas.**

### Pembuktian
Perusakan: 5 dari 5 pada normalisasi anchor (terlalu lemah dan terlalu agresif, termasuk "anchor sah ikut diubah"), 12 dari 12 pada pemeriksa Alpine/cite/konstanta (dua mutasi selamat pada putaran pertama, karena tidak ada skenario untuk `{!! !!}` bersama `{{ konstan }}` dan karena celah itu baru terlihat setelah penjaga konstanta ditambahkan; keduanya ditambahi skenario dan terdeteksi). Harness Node saya sendiri sempat salah dua kali (membungkus ekspresi dalam `return (...)` padahal Alpine mengeksekusinya sebagai pernyataan, dan indeks argumen `node -e`) dan hasil "tidak ada eksekusi" dari alat yang salah tidak dipakai; kriteria kelas aktif juga sempat salah dan dikoreksi sebelum angka dilaporkan.

## Rilis 19: paritas mesin seksi dengan mesin render asli, dan penjelasan daftar isi

**Urutan rilis:** … → 18 → 19. **Tidak ada kode aplikasi yang berubah** (app, resources, tools, landing-app, landing-files.txt identik dengan rilis 18, diperiksa byte demi byte); hanya satu pengujian baru dan dokumen.

### Temuan
Potongan mesin render yang Anda tempel (`page-preview.blade.php`) memuat **pembungkus** daftar isi (kartu kaca, sembunyi saat menabrak gambar layar penuh) dan **perakitan** daftar isi, tetapi hanya **memanggil** `<x-table-of-contents :items="$tocItems" />` dua kali; komponen itu sendiri tidak didefinisikan di potongan itu maupun di berkas mana pun yang pernah diunggah. Pembungkus dan perakitan sudah ada di kit; yang belum terlihat adalah isi daftar tautan di dalam kartu.

### Baru
`tests/section-parity-test.php`: memuat **salinan apa adanya** logika pengelompokan seksi dan perakitan daftar isi dari mesin render Anda sebagai acuan, lalu membandingkannya dengan `SectionBuilder` pada 6000 dokumen acak (teks per bahasa, bahasa tak tersedia, string polos, null, id hantu, urutan diacak, kedua penulisan tipe pemisah, semua posisi daftar isi). **Identik.** Satu-satunya selisih yang disengaja: kunci `dividerId` pada setiap seksi (perluasan kit untuk kanvas). Secara terpisah dibuktikan bahwa JavaScript `checkOverlap` setara (363 karakter kanonik sama persis) dan penanda pembungkus sama. Bila `SectionBuilder` suatu hari menyimpang dari mesin asli, pengujian ini gagal.

Uji perusakan: 7 dari 7 penyimpangan pada `SectionBuilder` terdeteksi (cadangan bahasa, posisi daftar isi bawaan, padding dan warna teks seksi awal, warna teks dan latar bawaan pemisah). **Dua mutasi pertama saya tidak sah** (mengenai komentar dokumen, bukan kode), dan satu lagi selamat karena data acak saya tidak pernah menaruh kunci `en` sebelum `id`; keduanya diperbaiki sebelum angka ini dilaporkan.

### Berubah
`tests/run-all.php` (27 rangkaian), `docs/PENGUJIAN.md`, `docs/LANDING-TAHAP-1.md` (bagian daftar isi: dua bagian yang berbeda, dan cara mencari komponennya termasuk bentuk berkelas).

## Rilis 18: penyaring isi publik (teks kaya, tautan internal, tujuh blok inti) dan koreksi atas berkas yang Anda kirim

**Urutan rilis:** … → 17 → 18. Panduan: `docs/LANDING-TAHAP-1.md` bagian "Penyaring isi publik", dan `docs/BAHASA.md` (keputusan bahasa). Pemeriksaan: `docs/DEBUG.md` 6o. Sinkron ke landing sekarang **47 berkas** (`RichText.php` dan `CoreBlocks.php` baru).

### Temuan (dari renderer asli yang Anda unggah)
1. **Tidak ada penyaringan untuk tujuh blok inti.** `BlockSanitizer::forType()` mengembalikan data apa adanya untuk semua tipe selain tombol dan modul. Renderer Anda mencetak: `{!! $data['text'][$lang] !!}` (judul, paragraf), `href` dari kolom `url` (kartu, tombol kartu), `src` gambar, `style="color: ..."` (eyebrow), `style="grid-template-columns: ..."` (kartu, multi-kolom), dan `'lucide-' . $icon`. Diuji dengan **renderer asli Anda** dan satu dokumen berisi data jahat: tanpa penyaring HTML akhir memuat **8 masalah keamanan** (`<script>`, `<iframe>`, `onerror`, `onclick`, `javascript:` pada tautan dan gambar) dan **4 sisipan CSS** (`position: fixed`); satu **nama ikon tidak sah membuat seluruh halaman gagal total** (500). Dengan penyaring: nol.
2. **Tautan internal tidak terselesaikan.** `internal://page/{slug}` dan `internal://article/{slug}` (hasil dialog tautan TipTap) hanya diselesaikan oleh `Page::getParsedContentAttribute` lama Anda, yang tidak dilewati pipeline kit. Di landing tautan itu tidak bisa diklik.
3. **`section-divider` bukan komponen.** Mesin pratinjau lama Anda memakainya hanya untuk memecah seksi. Rilis 14 sampai 17 keliru meminta renderer-nya dan pemeriksa landing menuntutnya (GALAT palsu). Dikoreksi: 14 tipe diperiksa, bukan 15.
4. **Templat sampul `/storage/posts/{file}` keliru**: editor artikel menyimpan nama berkas dan membacanya dari `storage/articles/`. Dikoreksi menjadi `/storage/articles/{file}`.
5. **Desain daftar artikel Anda lebih kaya** dari `/artikel` yang dibangun rilis 13 (filter kategori, artikel unggulan, lencana kategori, penulis, "muat lebih banyak"). Belum dikerjakan; dicatat untuk tahap 2.

### Baru
| Berkas | Isi |
|---|---|
| `app/Content/Blocks/RichText.php` | penyaring HTML teks kaya: *serializer* (tag daftar-putih dibangun ulang + teks ter-escape), tanpa ekstensi DOM; atribut daftar-putih (tanpa `on*`, `x-*`, `@*`, `:*`, `wire:*`, `id`); URL (http, https, mailto, tel, anchor, jalur; `//host` dan `\` ditolak); `style` per properti dan per nilai; SVG hanya bentuk ikon; `internal://` diselesaikan; idempoten; kedalaman dan ukuran dibatasi |
| `app/Content/Blocks/CoreBlocks.php` | penyaring struktural tujuh blok inti, **non-destruktif** (hanya kolom berisiko: alamat, warna eyebrow, lebar kolom, ikon, id anak) |
| `tests/rich-text-test.php` | 79 uji: 22 pola editor dipertahankan, 91 vektor serangan, uji acak 6000, kinerja; setiap keluaran diaudit oleh pemeriksa yang ditulis terpisah dari kode yang diuji |
| `tests/core-blocks-test.php` | 119 uji |
| `tests/public-sanitize-test.php` | 14 uji berbasis data: halaman berbahaya dan artikel lama (HTML mentah) lewat `PublicLookup::document()` |
| `docs/BAHASA.md` | rekomendasi bahasa: `/` (EN) dan `/id`, slug per bahasa, bukan `?lang` |

### Berubah
| Berkas | Perubahan |
|---|---|
| `app/Content/Blocks/BlockSanitizer.php` | `forType()` memasang `CoreBlocks`; **`forPublic()` baru**: `clean()` + HTML teks kaya disaring + tautan internal diselesaikan. HTML **tidak** disaring saat simpan (data editor utuh) |
| `app/Content/PublicLookup.php`, `resources/.../content/body.blade.php` | jalur publik memakai `forPublic()` (pratinjau tersimpan ikut, karena memakai `document()`) |
| `landing-app/config/cms.php` | templat sampul `/storage/articles/{file}` |
| `tools/check-landing.php` | `section-divider` tidak lagi diminta |
| `landing-files.txt` | 45 -> 47 berkas |
| pengujian | `check-landing-test` (+2 skenario), `landing-app-test`, `run-all` (26 rangkaian). **Sembilan pengujian murni lama** (`accordion`, `article-cards`, `button`, `callout`, `canvas`, `downloads`, `gallery`, `latest-articles`, `video`) memuat kelas kit dengan daftar `require_once` tulisan tangan; `RichText.php` dan `CoreBlocks.php` ditambahkan ke daftar itu. Tiga di antaranya (`accordion`, `button`, `canvas`) **gagal** setelah `BlockSanitizer` dirujuk ke `CoreBlocks` dan tertangkap oleh pelari penuh dari ekstraksi bersih; enam lainnya lolos hanya kebetulan (tidak memanggil jalur itu). Kode aplikasi tidak terpengaruh (aplikasi memakai autoload Composer) |

### Yang berubah di tampilan publik
Sematan `<iframe>` (mis. YouTube) pada artikel **lama** tidak tampil lagi (isi basis data utuh; gunakan blok Video). Ikon/warna/lebar yang tidak sah kembali ke bawaan. Ikon yang sah bentuknya tetapi **tidak ada** di paket ikon tetap membuat halaman gagal (penyaring hanya menolak bentuk nama yang tidak sah).

### Pembuktian
Perusakan terhadap kode: 10 dari 11 terdeteksi pada `RichText` (yang selamat setara: validator per-properti sudah menolak `url(`/`expression` sebelum filter larangan umum berjalan, jadi filter itu lapisan kedua), 11 dari 11 pada `CoreBlocks`, 4 dari 4 pada pemasangan. Verifikasi **independen** dengan parser HTML Python: 4113 keluaran (22 pola editor, 91 serangan, 4000 acak) tanpa satu pelanggaran daftar-putih, dan 22 pola editor utuh secara struktur. Berkas renderer asli Anda diuji lewat mesin seksi kit: 16 dari 16 pemeriksaan isi lolos.

## Rilis 17: peta situs (`/sitemap.xml`) dan `robots.txt`

**Urutan rilis:** … → 16 → 17. Panduan: `docs/LANDING-TAHAP-1.md` bagian "Peta situs dan robots.txt". Pemeriksaan: `docs/DEBUG.md` 6n. Sinkron ke landing sekarang **45 berkas** (`Sitemap.php` baru).

### Baru
`GET /sitemap.xml`: setiap halaman CMS online dan artikel terbit, satu alamat per slug per bahasa (slug berbeda = alamat berbeda), ditambah jalur statis dari `config('cms.sitemap_static')`. `lastmod` dari `updated_at` (artikel: `published_at` bila kosong). Alamat kembar digabung dengan `lastmod` terbaru. **Alamat yang tidak sah dibuang, tidak diperbaiki**; maksimal 50.000; tanggal hanya `YYYY-MM-DD` yang sah. Bila basis data gagal dibaca atau hasilnya kosong, balasannya **503**, bukan peta kosong 200 (mesin pencari bisa menafsirkannya sebagai "semua halaman dihapus"). **Pastikan `APP_URL` landing berisi alamat sebenarnya**; bila tidak berbentuk `skema://host`, dipakai alamat permintaan. Belum ada `hreflang` (bagian pengalih bahasa).

| Berkas | Isi |
|---|---|
| `app/Content/Sitemap.php` | murni: `entries()` (baris + jalur statis + alamat dasar -> alamat tervalidasi), `normalize()`, `xml()`, `date()`; ikut ke landing |
| `landing-app/app/Http/Controllers/SitemapController.php` | penyambung tipis: baca, panggil `Sitemap`, 503 saat gagal, `application/xml`, cache publik 10 menit |
| `landing-app/public/robots.txt` | `Sitemap: https://ysbh.org/sitemap.xml` (ganti bila domain lain) |
| `tests/sitemap-test.php`, `tests/sitemap-entries-test.php` | murni (22 uji) dan berbasis data (13 uji) |

### Berubah (9 berkas)
| Berkas | Perubahan |
|---|---|
| `app/Content/PublicLookup.php` | + `sitemapEntries()` (slug per bahasa; baris lama teks polos dibaca; slug tak sah dilewati) |
| `landing-app/routes/web.php` | + rute `GET /sitemap.xml` sebelum `/{slug}` |
| `landing-app/config/cms.php` | + `sitemap_static` (jalur statis; harus sama dengan rute GET statis) |
| `tools/landing-files.php`, `landing-files.txt` | penelusuran kini juga membaca KODE `landing-app/` (pengendali, model, komponen) dan menyertakan kelas `App\Content\*` yang dirujuknya: diturunkan, bukan ditulis tangan. 44 -> 45 berkas |
| `tools/check-landing.php` | + bagian 3c (rute `/sitemap.xml`, pengendalinya, `sitemap_static` vs rute, `robots.txt`); pemeriksaan slug terlarang hanya menyangkut segmen yang sah sebagai slug (`sitemap.xml` bertitik tidak mungkin menjadi slug, jadi tidak lagi dituduh) |
| `tests/landing-app-test.php`, `tests/check-landing-test.php`, `tests/run-all.php` | kontrak peta situs (+5), skenario pemeriksa (+7), dua rangkaian baru terdaftar (23 rangkaian) |

### Pengujian
Keluaran diuji selalu XML yang sah: pemeriksa bentuk yang ketat di dalam uji (tidak butuh ekstensi XML, yang tidak ada di semua PHP), 4500 gugus acak dari masukan berbahaya (karakter kendali, Unicode, skema, `]]>`, `<!--`), dan secara **independen** 2500 dokumen diurai parser XML ketat Python (`ElementTree`): semuanya sah, 7818 `<url>`, tidak ada alamat atau tanggal aneh. Perusakan terhadap kode: 7 dari 8 terdeteksi pada `Sitemap` (yang selamat, tanpa escape XML, setara karena validasi alamat sudah menyingkirkan karakter istimewa; escape dipertahankan sebagai lapisan kedua), 7 dari 7 pada sumber data, 4 dari 4 pada pemeriksa.

## Rilis 16: lab pengujian mandiri dan penjalan tunggal

**Urutan rilis:** … → 15 → 16. **Tidak ada kode aplikasi yang berubah** (app, resources, tools, landing-app identik dengan rilis 15, diperiksa byte demi byte); yang berubah hanya pengujian dan dokumen. Panduan: `docs/PENGUJIAN.md`.

### Koreksi atas klaim di rilis 13 sampai 15
Catatan rilis sebelumnya menyebut pengujian berbasis data "ikut zip dan bisa dijalankan". **Itu keliru**: pengujian itu butuh lab (`bootstrap.php`, `support.php`, `stubs.php`, serta pustaka Laravel dan Carbon) yang tidak ikut kit. Hanya pengujian murni yang bisa Anda jalankan. Suite data terbesar (227 uji) bahkan hanya ada di lab saya. Rilis ini memperbaikinya.

Ditemukan juga: lab saya memuat kelas `App\*` dari **folder kerja saya**, bukan dari folder tempat pengujian dijalankan. Karena manifes membuktikan isi zip identik dengan folder kerja, hasil sebelumnya tetap sahih, tetapi celahnya kini ditutup.

### Baru
| Berkas | Isi |
|---|---|
| `tests/lab/setup.php` | mengunduh Laravel 13.30.0 dan Carbon 3.10.0 ke `tests/lab/_src`. Mengekstrak lewat zip, atau phar + zlib, atau `tar` (urut); nama berkas unduhan unik per pustaka (PharData menyimpan arsip menurut nama berkas: sebelumnya unduhan kedua mengekstrak ulang arsip pertama) |
| `tests/lab/bootstrap.php`, `support.php`, `stubs.php` | lab Eloquent + SQLite + validator; kelas `App\*` dimuat dari folder kit ini; tanpa jalur tertanam |
| `tests/lab/README.md` | pemakaian |
| `tests/run-all.php` | menjalankan 21 rangkaian, ringkasan per baris, kode keluar 0/1/2; `--only=kata`, `--strict`; pengujian yang butuh lab DILEWATI dengan keterangan bila lab belum disiapkan; filter tanpa kecocokan = galat (bukan hijau palsu) |
| `tests/data-test.php` | suite data (227 uji), kini portabel dan ikut zip |
| `docs/PENGUJIAN.md` | cara menjalankan dan apa yang tidak dicakup |

### Berubah
`tests/landing-models-test.php` kini menguji model `landing-app/app/Models` yang sebenarnya (rilis 15). Pengujian rilis 15 yang baru: `slug-reserved-test.php`, `articles-header-test.php`, dan perluasan `check-landing-test.php` serta `landing-app-test.php`.

### Pembuktian
Dari salinan bersih: `setup.php` dari nol 3 kali berhasil (jalur tar juga diuji); `run-all.php` 21 ok, 0 gagal, 0 dilewati; kode di salinan dirusak sengaja, hanya di salinan: pengujian gagal dan kode keluar 1 sementara folder kerja tidak tersentuh.

## Rilis 15: penjaga slug terlarang, dan kepala daftar artikel dari halaman CMS

**Urutan rilis:** … → 14 → 15. Panduan: `docs/LANDING-TAHAP-1.md` bagian "Slug yang dilarang, dan kepala daftar artikel". Pemeriksaan: `docs/DEBUG.md` 6m.

### Yang diperbaiki: halaman CMS yang tidak pernah bisa dibuka
Rute statis (`/about`, `/contact`, ...) didaftarkan lebih dulu daripada `/{slug}`. Halaman CMS ber-slug yang sama **tersimpan, tampak online, tetapi tidak pernah terbuka**, tanpa galat apa pun, dan kit tidak punya penjaga. Kini editor menolak slug itu saat menyimpan **halaman** (artikel tidak terkena: alamatnya `/artikel/{slug}`), per bahasa, dengan pesan `Slug (ID) "about" dipakai oleh alamat tetap situs, ...`. Dua sumber daftar: yang teknis di kode (`Slug::RESERVED`), dan rute statis situs di `config('cms.reserved_slugs')` (**tambahkan ke `config/cms.php` CMS**; contoh: `contoh-kode/config-slug-terlarang.php`). `tools/check-landing.php` kini memberi PERINGATAN bila ada rute statis satu-segmen di `routes/web.php` yang belum tercatat. `php artisan cms:audit-slugs` menemukan halaman lama yang sudah terlanjur bertabrakan.

### Baru: kepala daftar artikel dari halaman CMS
Halaman CMS online ber-slug `artikel` (`config('cms.articles_index_slug')`; satu-satunya slug "terlarang" yang sengaja diizinkan) menyumbang judul, judul/deskripsi SEO, blok **pengantar** (hanya halaman 1), dan snippet **penutup** yang tampil **sesudah** daftar. Daftar dan penomorannya tetap kode landing. Tanpa halaman itu: judul bawaan dan daftar polos. Halaman 2+ kini punya `canonical` yang menunjuk dirinya sendiri (sebelumnya semua halaman menyatakan dirinya sama dengan halaman 1) dan judul bernomor.
`PublicLookup::document()` sekarang juga mengembalikan `closingFrom` (indeks tempat snippet penutup mulai): tanpa ini penutup akan tampil di antara pengantar dan daftar.

### Baru (4 berkas)
| Berkas | Isi |
|---|---|
| `app/Content/Rules/ReservedSlug.php` | aturan validasi (hanya CMS) |
| `app/Content/SlugAudit.php` | pencari halaman lama yang bertabrakan (murni; hanya CMS) |
| `app/Console/Commands/AuditSlugs.php` | `php artisan cms:audit-slugs` (hanya CMS; hanya membaca; kode keluar 1 bila ada konflik) |
| `contoh-kode/config-slug-terlarang.php` | dua kunci yang ditambahkan ke `config/cms.php` CMS |

### Berubah (7 berkas)
| Berkas | Perubahan |
|---|---|
| `app/Content/Slug.php` | + `RESERVED`, `reserved()`, `isReserved()` (murni; ikut ke landing) |
| `app/Content/ContentRules.php` | halaman memakai `ReservedSlug` pada setiap slug bahasa; membaca `cms.reserved_slugs` dan `cms.articles_index_slug` tanpa galat bila config belum ada |
| `app/Content/PublicLookup.php` | + `articlesHeader()`; `document()` + kunci `closingFrom` (kunci lama tidak berubah) |
| `resources/views/components/blocks/render/latest-articles-builder.blade.php` | hanya komentar (prop `feed` kini dipakai daftar artikel, bukan hanya pengujian) |
| `landing-app/resources/views/components/⚡articles-index.blade.php` | kepala dari halaman CMS, pengantar, penutup sesudah daftar, canonical dan judul per halaman |
| `landing-app/config/cms.php` | + `reserved_slugs`, `articles_index_slug` |
| `tools/check-landing.php` | + pemeriksaan rute statis vs slug terlarang dan kecocokan slug kepala |

### Dihapus dari kit (4 berkas; tidak ada di proyek Anda)
`contoh-kode/landing/models.php`, `⚡page-show.blade.php`, `⚡article-show.blade.php`, dan `contoh-kode/landing-routes.php`. Rilis 13 menyisakan **dua sumber** untuk model, komponen, dan rute landing (contoh lama yang diuji, dan `landing-app/` yang Anda pasang); contoh lama sudah menyimpang (masih memakai `$pageTitle`, padahal `head` membaca `$title`). Satu sumber kini: `landing-app/`. `tests/landing-models-test.php` menguji model `landing-app/app/Models` yang sebenarnya.

### Pengujian
Baru: penjaga slug 26 (validator sungguhan + SQLite; 10 perusakan terdeteksi), kepala artikel 17 (model landing yang sebenarnya; 6 perusakan), pemeriksa landing 35 (+7), kontrak landing 39 (+7), render templat daftar artikel 8 dengan Blade sungguhan (urutan judul, pengantar, kartu, penomoran, penutup; 3 perusakan). Seluruh rangkaian ulang di Laravel 12.0.0 dan 12.69.3. **Komponen Livewire (`⚡`) tetap hanya dikompilasi dan di-lint dan templatnya dirender dengan data tiruan; belum dijalankan di Livewire sungguhan.**

## Rilis 14: config landing dari berkas CMS Anda, dan pemeriksa landing

**Urutan rilis:** … → 13 → 14. Hanya sisi **landing** yang berubah; tidak ada berkas CMS yang berubah.

### Temuan
Berkas `heading`, `paragraph`, `eyebrow`, `image`, `card-builder`, `step-group`, `multi-columns`, dan `section-divider` yang pernah Anda kirim adalah **komponen EDITOR** (`x-blocks.editor.wrapper`, `blockId`, `activeLocales`), bukan renderer publik. **Jangan disalin ke landing.** Renderer publik ada di `resources/views/components/blocks/render/` di CMS (mesin pratinjau lama Anda merender lewat `blocks.render.<tipe>`); yang itu yang disalin. Kit tidak memuat delapan renderer itu, jadi tidak bisa saya tulis atau uji sendiri.

### Berubah (2 berkas, folder `landing-app/`)
| Berkas | Perubahan |
|---|---|
| `config/cms.php` | kini **salinan `config/cms.php` CMS Anda apa adanya** (`design` 18 daftar, `lucide` 75 ikon, `fonts`: identik, diuji) + blok `public`. Sebelumnya hanya `lucide` kosong dan `public`; renderer sangat mungkin membaca `design` |
| `resources/css/app.css` | + `@source '../../config'` (daftar `design` berisi kelas Tailwind yang dipakai renderer) |

### Baru (1 berkas)
| Berkas | Isi |
|---|---|
| `tools/check-landing.php` | pemeriksa proyek landing setelah penyalinan: renderer untuk setiap tipe blok (15), tidak ada ciri komponen editor, kunci `config('cms...')`, komponen `<x-...>` dan model yang dipakai tersedia, tabrakan direktif Blade, pengaturan Tailwind, sinkron dengan kit. Hanya membaca; kode keluar 0/1/2 |

### Dokumen dan pengujian
`docs/LANDING-TAHAP-1.md` ditulis ulang pada bagian "Dari CMS ke landing" (perintah PowerShell untuk menyalin sembilan berkas, tabel `render/` lawan komponen editor, cara memakai pemeriksa). `DEBUG.md` 6l + butir pemeriksa. Pengujian baru: pemeriksa 28 (proyek lengkap + 20 skenario rusak, enam perusakan alat terdeteksi), kontrak landing 32 (kini mengevaluasi config dan membandingkan templat dengan jalur rute).

## Rilis 13: landing tahap 1 (halaman, artikel, indeks, menu, SEO) dan perbaikan di berkas landing Anda

**Urutan rilis:** … → 12 → 13. Panduan lengkap dan daftar berkas yang saya butuhkan dari Anda: `docs/LANDING-TAHAP-1.md`. Folder **`landing-app/`** berisi berkas untuk proyek **landing** (strukturnya sama dengan proyek landing); hanya `app/Content/PublicLookup.php` yang untuk CMS dan landing (disinkronkan ke landing oleh `tools\sync-landing.php`).

### Temuan pada berkas landing Anda (diperbaiki)
1. `header`: `route($link->route_name)` menjatuhkan seluruh situs bila menu tidak punya rute bernama (mis. halaman CMS) atau salah ketik. Kini `Navigation::href` (rute bernama **atau** kolom `url`, tidak pernah galat) dan `isCurrent()` (turunan rute dan jalur ikut aktif; anchor/surel/telepon tidak pernah aktif).
2. `header`: `<header>` bersarang diganti `<div>`.
3. `[x-cloak]` dipakai tetapi tidak didefinisikan di CSS (menu mobile dan nav lengket berkedip): ditambahkan.
4. Warna `charcoal` dipakai 27 kelas kit tetapi tidak ada di tema: ditambahkan.
5. `head`: warna dari basis data dicetak ke CSS tanpa validasi: hanya `#RRGGBB` diterima.
6. `app.css` tidak memindai `app/` (kelas di PHP kit): `@source '../../app'`.
7. `/articles` (placeholder ke halaman kontak) dialihkan 301 ke `/artikel`.
8. **Renderer delapan tipe blok bawaan (judul, paragraf, eyebrow, gambar, kartu, langkah, kolom, pemisah) dan `<x-table-of-contents>` ada di aplikasi CMS, bukan di kit.** Tanpanya halaman CMS di landing tampil tanpa judul dan paragraf (tipe tanpa renderer dilewati diam-diam). **Salin dari CMS.**

### Berubah (1 berkas aplikasi; ikut ke landing)
| Berkas | Perubahan |
|---|---|
| `app/Content/PublicLookup.php` | + `articlesPage()` (daftar artikel berhalaman untuk `/artikel`); `latestArticles()` memakai pembantu yang sama (perilaku tidak berubah) |

### Baru: folder `landing-app/` (17 berkas)
| Berkas | Keterangan |
|---|---|
| `routes/web.php` | rute lama utuh; `/articles` dialihkan; **`/artikel`, `/artikel/{slug}`, dan `/{slug}` (paling akhir)** |
| `resources/views/components/layouts/header.blade.php` | berkas Anda dengan 3 penggantian terarah |
| `resources/views/partials/head.blade.php` | SEO (deskripsi, canonical, Open Graph), warna divalidasi |
| `resources/css/app.css` | berkas Anda + 3 tambahan |
| `app/Models/Navigation.php` | berkas Anda + `href` dan `isCurrent()` |
| `app/Models/ReadOnlyModel.php`, `Page.php`, `Post.php`, `Category.php`, `Snippet.php`, `Media.php` | model baca-saja (landing tidak menulis; `Media` memakai `SoftDeletes`) |
| `resources/views/components/⚡page-show.blade.php`, `⚡article-show.blade.php`, `⚡articles-index.blade.php` | halaman CMS, artikel, dan indeks artikel (Livewire) |
| `resources/views/errors/404.blade.php` | halaman 404 |
| `config/cms.php` | `lucide` (isi dari CMS) dan templat `public` (harus sama dengan rute) |
| `_sementara/table-of-contents.blade.php` | pengganti SEMENTARA sampai komponen asli dari CMS dikirim |

### Pemeriksaan
`docs/DEBUG.md` bagian **6l**. Pengujian baru: kontrak landing 30 (rute vs templat alamat, urutan rute, perbaikan di berkas Anda, keamanan komponen), navigasi 25, render header 7 (gagal pada header lama Anda), kompilasi 18 tampilan landing di Laravel 12.0.0 dan 12.69.3, data 227. **Komponen Livewire (`⚡`) hanya dikompilasi dan di-lint, belum dijalankan di Livewire sungguhan.**

## Rilis 12: blok Artikel Terbaru, snippet di situs publik, dan perlengkapan landing

**Urutan rilis:** … → 11 → 12. Salin berkas aplikasi di bawah ke CMS **dan sinkronkan ke landing** (`php tools\sync-landing.php <folder-landing> --apply`; daftarnya kini 44 berkas), lalu `php artisan optimize:clear`. Tidak ada rute, `app.js`, migrasi, atau `npm run build`.

### Yang diperbaiki: snippet tidak pernah tampil di situs publik
Kit mendefinisikan model Snippet dan aturan snippet penutup (`ClosingPolicy`), tetapi **tidak ada kode render publik yang memakainya**. Snippet penutup (bertanda `is_closing`) tidak akan tampil di landing, dan blok `snippet` tidak diperluas. Kini `PublicLookup::document()` merakit isi halaman + blok `snippet` di tempatnya + snippet penutup menurut `ClosingPolicy` (termasuk penggantian per halaman), dan membersihkan datanya. **Pratinjau tersimpan CMS memakai perakit yang sama**, jadi sama dengan yang dilihat pengunjung (isi sebuah snippet tidak mendapat penutup). Batasan: hanya blok `snippet` tingkat atas yang diperluas; satu snippet tampil sekali per halaman.

### Baru: blok Artikel Terbaru
Kartu artikel terbit terbaru (sampul, kategori, tanggal, judul, ringkasan) + tautan "lihat semua". Dinamis: dibaca dari basis data saat dirender; bila membaca gagal blok tidak dirender dan galat dilaporkan. Sampul: kolom `featured_image` berisi **nama berkas**; `default.webp` = belum ada sampul. **Tambahkan `cms.public.cover`** (mis. `'/storage/posts/{file}'`, sesuaikan folder Anda) agar sampul tampil; tanpa itu kartu tampil tanpa gambar. Panduan: `docs/BLOK-ARTIKEL-TERBARU.md`.

### Baru (4 berkas aplikasi; ikut ke landing)
| Berkas | Isi |
|---|---|
| `app/Content/PublicLookup.php` | pencarian halaman/artikel dari slug JSON (hanya membaca), artikel terbaru, perakitan dokumen publik dengan snippet |
| `app/Content/Blocks/ArticleCards.php` | kartu artikel: ringkasan, tanggal per bahasa, sampul, tautan (murni, diuji) |
| `app/Editor/Blocks/LatestArticlesBlock.php` | blok Artikel Terbaru (modul) |
| `resources/views/components/blocks/render/latest-articles-builder.blade.php` | tampilan publik |

### Berubah (2 berkas aplikasi)
| Berkas | Perubahan |
|---|---|
| `app/Content/Links/LinkResolver.php` | `address()` publik; `publicUrl()` menerima templat `{file}` (sampul) di samping `{slug}`; `make()` memakainya. Perilaku `{slug}` tidak berubah |
| `app/Content/SavedPreview.php` | memakai `PublicLookup::document()` (snippet sisipan dan penutup ikut untuk halaman dan artikel) |

### Contoh dan dokumen (tidak dipasang otomatis)
| Berkas | Isi |
|---|---|
| `contoh-kode/landing/models.php` | model baca-saja untuk landing (`Page`, `Post`, `Category`, `Snippet`, `Media` dengan `SoftDeletes`), menolak penulisan; **diuji terhadap kode kit** |
| `contoh-kode/landing/⚡page-show.blade.php`, `⚡article-show.blade.php` | komponen Livewire halaman dan artikel publik. **Dikompilasi dan di-lint, belum dijalankan di Livewire sungguhan** |
| `contoh-kode/config-dua-aplikasi.php` | + kunci `cms.public.cover` |
| `docs/DUA-APLIKASI.md` | ditulis ulang: gambar lewat folder bersama (rinci), hasil verifikasi hPanel dan **koreksi** (subdomain sebagai situs mandiri; `public_html` tetap; pengguna basis data baca-saja), model dan snippet di landing |
| `landing-files.txt` | 37 → 44 berkas (otomatis; `tools/landing-files.php --check` menjaganya) |

### Pemeriksaan
`docs/DEBUG.md` bagian **6k**. Pengujian baru: data 222 (PublicLookup, perakitan snippet, SavedPreview, blok), kartu 31, blok 15, alamat publik 23, paket dan alat sinkron 22, model landing 16, render 22, inspektur 14. Seluruh pengujian render publik (251) lulus hanya dengan hasil salinan alat sinkron, di Laravel 12.69.3.

## Rilis 11: dua aplikasi (CMS dan landing), alamat publik, dan paket berkas landing

**Urutan rilis:** … → 10 → 11. Rilis ini mengganti **1 berkas aplikasi** (`LinkResolver.php`) dan menambah **alat dan dokumen** untuk memisahkan CMS dan landing. Setelah menyalin: `php artisan optimize:clear`. Tidak ada rute, `app.js`, migrasi, atau `npm run build`.

### Perbaikan: tautan ke halaman/artikel melempar galat di CMS
`LinkResolver::make()` memanggil `route('page.show')` dan `route('article.show')` di aplikasi tempatnya berjalan. Pada dua aplikasi, rute itu hanya ada di **landing**; di CMS (kanvas dan pratinjau tersimpan) tautan jenis Halaman/Artikel melempar "route not found". Kini:
1. alamat dibentuk dari **templat konfigurasi** `config('cms.public')` (`base`, `page`, `article`); di CMS menghasilkan alamat **absolut ke landing**;
2. bila konfigurasi tidak ada, memakai rute bernama bila ada di aplikasi itu;
3. bila tidak ada pula, hasilnya null (tautan tidak dirender), **tidak pernah galat**.

Pembangun alamatnya ketat: templat harus diawali satu `/`, tepat satu `{slug}`; `//` (protokol-relatif), skema, spasi, dan kutip ditolak; alamat dasar hanya skema+host(+port), tanpa jalur; slug di-encode. Diuji dengan uji acak 4000 kombinasi.

### Baru (di zip; bukan berkas yang dipasang ke proyek)
| Berkas | Isi |
|---|---|
| `landing-files.txt` | 37 berkas kit yang dibutuhkan landing; **tanpa kode admin** (dibuat otomatis, diuji) |
| `tools/landing-files.php` | menelusuri ketergantungan renderer publik; `--write`/`--check`; mencetak kebutuhan landing |
| `tools/sync-landing.php` | menyalin berkas itu ke proyek landing (bawaan: hanya memeriksa; `--apply` menyalin; menolak folder bukan Laravel dan jalur tidak aman) |
| `docs/DUA-APLIKASI.md` | arsitektur dua aplikasi, kontrak alamat, gambar, Hostinger, apa yang terjadi saat salah satu mati |
| `contoh-kode/config-dua-aplikasi.php` | `cms.public`, disk `public`, dan `.env` untuk kedua aplikasi |
| `contoh-kode/landing-routes.php` | rute `page.show`/`article.show` di landing dan uji kontraknya |
| `tests/landing-pack-test.php`, `tests/public-url-test.php` | 22 dan 23 pemeriksaan |

### Berubah (1 berkas aplikasi)
| Berkas | Perubahan |
|---|---|
| `app/Content/Links/LinkResolver.php` | alamat publik dari templat konfigurasi; tidak melempar galat bila rute tidak ada. **Salin juga ke landing** (ada di `landing-files.txt`; alat sinkron melakukannya) |

### Pemeriksaan
`docs/DEBUG.md` bagian **6j**. Seluruh pengujian render publik (227 pemeriksaan) dijalankan di atas folder yang hanya berisi hasil salinan alat sinkron, di Laravel 12.69.3.

## Rilis 10: blok Galeri / Logo (grid dan carousel)

**Urutan rilis:** … → 9 → 10. Rilis 10 hanya **menambah 4 berkas baru, tidak mengubah berkas lama mana pun**, dan tidak tumpang tindih dengan rilis sebelumnya. Salin lalu `php artisan optimize:clear`. Tidak ada rute, `app.js`, migrasi, atau JavaScript baru (`npm run build` tidak perlu).

### Yang perlu diketahui
- **Dua jenis**: **Foto** (galeri kegiatan; keterangan, pembesar) dan **Logo mitra** (kotak putih, logo utuh, hitam-putih opsional, tautan ke situs mitra). **Dua tampilan**: **Grid** dan **Carousel** (scroll-snap, panah, geser sentuh; berjalan otomatis opsional).
- **Berjalan otomatis aksesibel**: berhenti saat disorot/difokus, ada tombol Jeda, dan tidak berjalan bila pengguna memilih "kurangi gerakan" di sistem operasinya.
- **Kelas lebar carousel** (`basis-[calc(…)]`, `aspect-[4/3]`, dst.) ada di berkas PHP `GalleryStyle.php`. Tailwind v4 memindai seluruh proyek secara bawaan; bila CSS Anda membatasi pemindaian, tambahkan `@source '../../app';`. Gejalanya: gambar carousel bertumpuk atau selebar penuh.
- **Ukuran gambar**: File Manager menyajikan berkas aslinya. Unggah foto yang sudah dikecilkan (sekitar 1600 px, di bawah 300 KB) dan logo di bawah 100 KB.
- Memilih gambar masih **satu per satu** (butuh dukungan pilih-banyak di File Manager untuk lebih cepat).

### Baru (4 berkas, tidak menimpa apa pun)
| Berkas | Isi |
|---|---|
| `app/Content/Blocks/GalleryList.php` | menyiapkan butir: gambar dari Media, teks alternatif, tautan, butir tak lengkap |
| `app/Content/Blocks/GalleryStyle.php` | gaya, kolom, lebar butir carousel, petak foto/logo (kelas dari daftar tetap) |
| `app/Editor/Blocks/GalleryBlock.php` | **blok Galeri / Logo** (modul): definisi, penempatan, pembersih |
| `resources/views/components/blocks/render/gallery-builder.blade.php` | tampilan **publik**: grid, carousel (scroll-snap, panah, berjalan otomatis opsional), pembesar foto (`<dialog>`) |

### Pemeriksaan
`docs/DEBUG.md` bagian **6i**; panduan `docs/BLOK-GALERI.md`; `tests/gallery-test.php` (34 pemeriksaan) ada di zip. Seluruh pengujian Blade dijalankan di Laravel 12.69.3 dan kompilasi di 12.0.0 juga; pemindai direktif (`tests/blade-scan.php`) bersih.

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
