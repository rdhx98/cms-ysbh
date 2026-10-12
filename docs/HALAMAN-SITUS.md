# Halaman situs (rilis 25 kerangka, rilis 26 isi)

Semua isi situs adalah halaman CMS (`LANDING-RAMPING.md`). Rilis 25 menambah perintah yang membuat halaman-halaman yang direncanakan, lengkap dengan slug dua bahasa, dalam keadaan **offline**. **Rilis 26 mengisinya dengan draf teks dua bahasa** (EN dan ID) yang disusun dari profil yayasan (PDF Maret 2026), presentasi pengalaman kerja 2014–2026, dan lima dokumen program; teksnya ada di `app/Content/SiteText.php`. Draf ini bukan teks final: **tinjau, lengkapi, ganti foto dan penanda, baru jadikan online.**

## Alamat halaman
Halaman program **datar** (satu segmen, cocok dengan rute `/{slug}`), dengan pola yang diputuskan pengguna: **`[nama program]-program`** di EN. Di ID memakai urutan bahasa Indonesia, `program-[nama]`. (Bila ingin pola EN juga di ID, mis. `malaria-program`, ubah daftar di `app/Content/SitePages.php` sebelum menjalankan perintah; setelah halaman terbit, ganti slug berarti alamat lama mati.)

| Halaman | EN | ID |
|---|---|---|
| Beranda | `/` (slug `home`) | `/id` (slug `beranda`) |
| Tentang | `/about-us` | `/id/tentang-kami` |
| Indeks program | `/programs` | `/id/program` |
| Malaria | `/malaria-program` | `/id/program-malaria` |
| Imunisasi | `/immunization-program` | `/id/program-imunisasi` |
| Kesehatan Ibu dan Anak | `/maternal-child-health-program` | `/id/program-kesehatan-ibu-anak` |
| Tuberkulosis (TB/TBC) | `/tb-program` | `/id/program-tbc` |
| HIV | `/hiv-program` | `/id/program-hiv` |
| Kredibilitas | `/credibility` | `/id/kredibilitas` |
| Dampak | `/impact` | `/id/dampak` |
| Transparansi | `/transparency` | `/id/transparansi` |
| Hubungi kami | `/contact` | `/id/kontak` |

Daftar ini adalah usulan awal: hapus baris yang tidak dipakai langsung di editor (halaman offline tidak tampil di mana pun), atau ubah `SitePages::pages()` sebelum menjalankan perintah.

## Membuat halaman
```powershell
php artisan cms:seed-pages            # hanya melihat rencana
php artisan cms:seed-pages --apply    # membuat
```
- Semua halaman dibuat **offline**. Halaman yang slug-nya sudah dipakai (bahasa mana pun) **dilewati**, tidak pernah ditimpa; aman dijalankan ulang.
- Setiap halaman terdiri dari blok **Judul** (satu H1, lalu H2/H3) dan blok **Paragraf** (daftar dan tabel tetap berupa HTML-nya sendiri), diberi id `b01`, `b02`, ... Teks EN dan ID **sejajar** blok demi blok, jadi `cms:audit-translations` tidak melaporkan apa pun.
- **Yang sengaja belum diputuskan tetap berpenanda `[ISI-DULU]`**, sehingga `cms:audit-placeholders` menolak halaman itu terbit sampai Anda menyelesaikannya:
  - **Foto** (20 tempat di halaman program; rilis 37): setiap foto adalah **blok Gambar kosong** yang keterangannya (EN dan ID, lengkap dengan tanggal) sudah terisi. Anda tinggal membuka blok itu di editor dan memilih berkasnya (panel kanan, **Jelajahi File Manager**). Selama belum ada berkas, blok tidak tampil di situs, dan `cms:audit-placeholders` menolak halaman itu terbit. Satu keterangan (HIV, Enarotali) sengaja masih memuat `[ISI-DULU]` pada bulan tanggalnya yang belum diketahui: ganti di kolom Keterangan.
  - **Beranda**: ajakan donasi (menunggu keputusan yayasan tentang cara berdonasi).
  - **Tentang kami**: pengurus saat ini dan persetujuan tiap orang untuk ditampilkan.
  - **Kesehatan Ibu dan Anak**: dokumen sumber menyebut empat komponen tetapi hanya membahas dua (Perawatan Bayi Baru Lahir, Kesehatan Anak); tiga komponen lain perlu teks.
  - **Kredibilitas**: audit yang lebih baru, bila ada. **Dampak**: hasil 2016 ke atas beserta sumbernya.
  - **Hubungi kami**: kode pos (99351 menurut PDF, 99225 menurut kartu kontak dan Google Maps; pilih satu) dan, bila yayasan mau, formulir, jam kerja, dan peta.
- Halaman **Imunisasi**, **Program (indeks)**, dan **Transparansi** tidak berpenanda di teksnya sendiri, tetapi Transparansi menyisipkan snippet NPWP/rekening yang berpenanda (bagian di bawah).
- Beranda dan indeks program menaut ke lima halaman program; tautan memakai `internal://page/<slug>` dan diselesaikan ke alamat bahasa halaman yang dibuka.
- Yang **sengaja tidak ada** di teks: NPWP, nomor rekening, nomor telepon atau email staf, domain lama, dan nilai kontrak. Satu-satunya telepon adalah nomor resmi yayasan; satu-satunya email `contact@ysbh.org`. Nomor SK Kemenkumham boleh tampil (bukan data sensitif).
- Perintah berhenti sebelum menulis bila `config/cms.php` menolak sebuah slug (mis. `reserved_slugs` masih berisi enam slug lama; kosongkan dulu, lihat `PASANG-CMS.md`).
- Pembuatan halaman memakai model `Page` Anda; kolom lain yang wajib diisi di tabel `pages` Anda (bila ada) akan menggagalkan halaman itu dengan pesan galat per halaman, tanpa menyentuh yang lain. **Belum dicoba pada tabel `pages` Anda**; laporkan pesannya bila ada.

## Halaman yang sudah dibuat sebelum rilis 37
Tempat foto di halaman yang sudah dibuat rilis 25/26 masih paragraf `[ISI-DULU] <em>Photo: ...</em>`. Ubah semuanya sekaligus (aman, hanya melihat tanpa `--apply`):
```powershell
php artisan cms:convert-photos            # rencana: halaman mana dan berapa foto
php artisan cms:convert-photos --apply    # mengganti paragraf penanda dengan blok Gambar kosong berketerangan
```
Hanya paragraf yang masih berupa penanda foto utuh yang diganti (id dan urutan blok sama). Yang sudah Anda sunting, status halaman, judul, slug, dan blok lain tidak disentuh; aman dijalankan ulang.

## Mengisi kekurangan langsung di CMS
Semua isi di atas adalah halaman dan snippet biasa: lengkapi langsung di editor CMS, tidak perlu menjalankan perintah apa pun. Teks `[ISI-DULU]` diganti lewat panel Properti blok Paragraf/Judul; foto lewat blok Gambar; NPWP dan rekening di Snippet `legal-details`.

## NPWP dan rekening: hanya tempatnya
Informasi ini sensitif dan harus dibahas dengan yayasan lebih dulu, jadi kit **tidak memuat nilai apa pun**. Perintah membuat snippet **`legal-details`** (offline) dengan dua baris, "NPWP" dan "Rekening giro/bank", masing-masing berisi `[ISI-DULU]` di EN dan ID. Halaman **Transparansi** menyisipkan snippet itu lewat blok snippet.

Setelah yayasan memutuskan apa yang boleh ditampilkan: ganti penanda di snippet itu (Snippet di admin), jadikan snippet online, lalu halaman Transparansi online. Snippet yang offline tidak tampil di halaman mana pun, jadi nilainya tidak bisa bocor lebih dulu.

## Footer
`opsional/footer.blade.php` adalah kaki halaman dua bahasa dengan tautan sungguhan ke halaman-halaman di atas (menggantikan footer ber-`href="#"`). Berkas ini milik Anda: salin ke `resources/views/components/layouts/footer.blade.php` di landing. Tautan ke halaman yang masih offline menjawab 404 sampai halamannya online. Hanya Instagram `@ysbhusada` yang diketahui; tautan sosial lain ditambah setelah alamatnya pasti.

## Penanda tidak boleh terbit
```powershell
php artisan cms:audit-placeholders          # halaman online / artikel terbit / snippet online yang masih memuat [ISI-DULU] atau blok Gambar tanpa berkas; kode keluar 1 bila ada
php artisan cms:audit-placeholders --all    # daftar pekerjaan: termasuk yang offline
```
Jalankan sebelum menjadikan halaman online. Perintah ini hanya membaca.

## Urutan kerja yang disarankan
1. `cms:seed-pages --apply`. Bila Anda sudah menjalankannya di rilis 25, halaman kerangka itu (satu paragraf penanda) **dilewati, tidak ditimpa**: untuk memakai teks rilis 26, hapus halaman-halaman kerangka itu di admin (semuanya offline dan kosong), lalu jalankan lagi.
2. Tinjau tiap halaman (dua bahasa), lengkapi penanda dan foto, jadikan online satu per satu, jalankan `cms:audit-translations` dan `cms:audit-placeholders`. Teks bahasa Inggris disusun dari bahan Indonesia dan sebagian dari dokumen berbahasa Inggris: sebaiknya ditinjau penutur bahasa Inggris sebelum terbit.
3. Menu (`navigations`): kolom `url` `/about-us`, `/programs`, `/transparency`, `/articles`, `/contact` (satu isian untuk dua bahasa, `BAHASA.md`).
4. NPWP/rekening menunggu keputusan yayasan (bagian di atas).
