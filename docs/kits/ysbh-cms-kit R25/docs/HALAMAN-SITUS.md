# Kerangka halaman situs (rilis 25)

Semua isi situs adalah halaman CMS (`LANDING-RAMPING.md`). Rilis 25 menambah **kerangka**: satu perintah membuat halaman-halaman yang direncanakan, lengkap dengan slug dua bahasa, dalam keadaan **offline** dan berisi penanda `[ISI-DULU]`. Isi sebenarnya ditulis manusia di editor.

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

## Membuat kerangka
```powershell
php artisan cms:seed-pages            # hanya melihat rencana
php artisan cms:seed-pages --apply    # membuat
```
- Semua halaman dibuat **offline**. Halaman yang slug-nya sudah dipakai (bahasa mana pun) **dilewati**, tidak pernah ditimpa; aman dijalankan ulang.
- Judul dan paragraf penanda ada di **kedua bahasa**, jadi `cms:audit-translations` tidak melaporkan apa pun sampai Anda mulai menulis.
- Perintah berhenti sebelum menulis bila `config/cms.php` menolak sebuah slug (mis. `reserved_slugs` masih berisi enam slug lama; kosongkan dulu, lihat `PASANG-CMS.md`).
- Pembuatan halaman memakai model `Page` Anda; kolom lain yang wajib diisi di tabel `pages` Anda (bila ada) akan menggagalkan halaman itu dengan pesan galat per halaman, tanpa menyentuh yang lain. **Belum dicoba pada tabel `pages` Anda**; laporkan pesannya bila ada.

## NPWP dan rekening: hanya tempatnya
Informasi ini sensitif dan harus dibahas dengan yayasan lebih dulu, jadi kit **tidak memuat nilai apa pun**. Perintah membuat snippet **`legal-details`** (offline) dengan dua baris, "NPWP" dan "Rekening giro/bank", masing-masing berisi `[ISI-DULU]` di EN dan ID. Halaman **Transparansi** menyisipkan snippet itu lewat blok snippet.

Setelah yayasan memutuskan apa yang boleh ditampilkan: ganti penanda di snippet itu (Snippet di admin), jadikan snippet online, lalu halaman Transparansi online. Snippet yang offline tidak tampil di halaman mana pun, jadi nilainya tidak bisa bocor lebih dulu.

## Penanda tidak boleh terbit
```powershell
php artisan cms:audit-placeholders          # halaman online / artikel terbit / snippet online yang masih memuat [ISI-DULU]; kode keluar 1 bila ada
php artisan cms:audit-placeholders --all    # daftar pekerjaan: termasuk yang offline
```
Jalankan sebelum menjadikan halaman online. Perintah ini hanya membaca.

## Urutan kerja yang disarankan
1. `cms:seed-pages --apply`.
2. Tulis halaman satu per satu (isi dua bahasa), jadikan online, jalankan `cms:audit-translations` dan `cms:audit-placeholders`.
3. Menu (`navigations`): kolom `url` `/about-us`, `/programs`, `/transparency`, `/articles`, `/contact` (satu isian untuk dua bahasa, `BAHASA.md`).
4. NPWP/rekening menunggu keputusan yayasan (bagian di atas).
