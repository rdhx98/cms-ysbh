# Alur persetujuan artikel (rilis 38, aturan empat mata rilis 41)

Artikel **tidak bisa langsung tersimpan sebagai terbit**. Status tidak lagi dipilih dari daftar; ia berubah hanya lewat tombol alur di panel **Halaman → Status**, dan setiap tombol diperiksa lagi di server (bukan hanya disembunyikan).

## Siapa boleh apa

| | Penulis (bukan admin/editor) | Admin & Editor (peninjau) |
|---|---|---|
| Membuka artikel | hanya **miliknya sendiri** (milik orang lain: 403) | semua artikel |
| Menulis & menyimpan | hanya saat **Draf** | semua status kecuali **Ditolak** |
| Ajukan review (Draf → Ditinjau) | ya, artikel miliknya | ya |
| Tarik kembali pengajuan (Ditinjau → Draf) | ya, artikel miliknya | tidak (peninjau memakai "Kembalikan") |
| Terbitkan sekarang (Ditinjau → Terbit) | **tidak** | ya |
| Jadwalkan terbit (Ditinjau → Terjadwal) | **tidak** | ya, pilih tanggal dan jam |
| Kembalikan ke draf (Ditinjau → Draf) | – | ya, **alasan wajib** |
| Tolak total (Ditinjau → Ditolak) | – | ya, **alasan wajib**; **final** |

Tidak ada jalan pintas: **admin pun tidak bisa menerbitkan langsung dari Draf**. Draf hanya punya satu jalan keluar, yaitu diajukan, dan keputusan diambil dari status Ditinjau. Menyimpan isi tidak pernah mengubah status.

## Status dan perpindahannya
| Dari | Aksi | Ke | Oleh |
|---|---|---|---|
| Draf | Ajukan review | Ditinjau | penulis, peninjau |
| Ditinjau | Tarik kembali | Draf | penulis |
| Ditinjau | Terbitkan sekarang | Terbit | peninjau |
| Ditinjau | Jadwalkan terbit | Terjadwal | peninjau |
| Ditinjau | Kembalikan ke draf (alasan wajib) | Draf | peninjau |
| Ditinjau | Tolak (alasan wajib) | **Ditolak** (final) | peninjau |
| Terjadwal | Terbitkan sekarang / Ubah jadwal | Terbit / Terjadwal | peninjau |
| Terjadwal | Tarik ke draf | Draf | peninjau |
| Terbit | Tarik ke draf / Arsipkan | Draf / Arsip | peninjau |
| Arsip | Pulihkan ke draf | Draf | peninjau |

- **Ditolak = final.** Tidak ada tombol apa pun, isi tidak bisa diubah (admin pun tidak), dan alasan penolakan tetap tampil. Bila perlu, buat artikel baru.
- **Terbit atau terjadwal yang ditarik ke draf** harus melewati Ditinjau lagi sebelum bisa tampil kembali.
- **Arsip** hanya bisa dipulihkan ke Draf oleh admin/editor.

## Alasan dan catatan
- **Kembalikan** dan **Tolak** mewajibkan alasan (maksimal 500 karakter). Alasan tampil di kotak kuning (dikembalikan) atau merah (ditolak) pada panel penulis, dan tersimpan di **Riwayat tinjauan**.
- Ajukan, terbitkan, jadwalkan, dan tarik ke draf boleh membawa catatan (opsional).
- Riwayat (siapa, melakukan apa, kapan, catatannya, dan waktu jadwal) ada di panel yang sama; 12 entri terbaru.

## Terjadwal
- Pilih tanggal dan jam di zona waktu situs (`config('app.timezone')`, tertulis di formulir). Harus **di masa depan** dan paling jauh 3 tahun.
- Waktu disimpan di kolom `published_at` yang sudah ada.
- **Artikel terjadwal yang waktunya tiba langsung tampil di situs publik**, tanpa menunggu apa pun: daftar artikel, halaman artikel, tautan internal, dan peta situs menganggapnya terbit (`PublicLookup::visible`).
- Status di admin berubah dari Terjadwal menjadi Terbit lewat perintah `php artisan cms:publish-scheduled` (mencatat "terbit otomatis" di riwayat). Jalankan tiap menit. Di Laravel 13, tambahkan ke `routes/console.php`:
  ```php
  use Illuminate\Support\Facades\Schedule;
  Schedule::command('cms:publish-scheduled')->everyMinute();
  ```
  dan di Hostinger (hPanel → Cron Jobs) satu baris per menit: `php /home/USER/domains/cms.ysbh.org/public_html/artisan schedule:run` (sesuaikan jalur). **Perintah ini tidak wajib untuk ketepatan waktu tayang**, hanya merapikan status.

## Pasang
1. Salin berkas kit (CMS) dan jalankan **`php artisan migrate`**: migrasi `create_article_reviews_table` menambah tabel riwayat. Tabel `posts` **tidak diubah**. Tanpa tabel itu, tombol alur menolak bekerja (alasan tidak boleh hilang diam-diam) dan panel menampilkan peringatan.
2. **Landing**: `PublicLookup.php` dan `LinkResolver.php` berubah. Jalankan `php tools\sync-landing.php D:\Herd\landing-ysbh --apply`, supaya artikel terjadwal yang tiba tampil di situs.
3. Opsional: `config/cms.php` → `'article_self_review' => false` untuk **aturan empat mata** (lihat bagian di bawah; contoh di `contoh-kode/config-slug-terlarang.php`). Bawaan **true**, karena yayasan kecil sering hanya punya satu admin.
4. **Daftar artikel milik Anda** (`_article-index_blade.php`, tidak ada di kit): penulis hanya boleh melihat artikelnya sendiri. Builder sudah memberi 403 bila penulis membuka artikel orang lain, tetapi daftar sebaiknya disaring juga: `Post::when(! $user->hasRole(['admin','editor']), fn ($q) => $q->where('user_id', $user->id))`. Tombol **Hapus** di daftar lama sebaiknya hanya untuk Draf (dan Ditolak).

## Yang belum
- Pemberitahuan (email/lonceng) ke peninjau saat ada pengajuan, dan ke penulis saat dikembalikan/ditolak: belum. Untuk sekarang peninjau memfilter status **Ditinjau** di daftar.
- Alur **Halaman online/offline** ditunda sesuai permintaan: halaman dan snippet tetap memilih Offline/Online.
- Pratinjau artikel terjadwal sebelum waktunya: lewat pratinjau tersimpan di builder (belum ada tautan pratinjau publik).
- Peran: peninjau = `hasRole(['admin','editor'])` (Spatie), sama dengan sebelumnya. Peran lain dianggap penulis.

## Aturan empat mata (`cms.article_self_review`, rilis 41)
Pengaturan di `config/cms.php` (setelah mengubahnya: `php artisan config:clear`):

| Nilai | Artinya |
|---|---|
| `true` (bawaan) | admin/editor boleh memutuskan artikel yang ia tulis atau ajukan sendiri. Cocok bila hanya ada satu admin. |
| `false` | **akun yang menulis artikel, atau yang mengajukannya (pengajuan terakhir), tidak boleh memutuskannya**: Terbitkan, Jadwalkan, Kembalikan ke draf, dan Tolak hanya untuk admin/editor LAIN. |

- Berlaku untuk akun admin dengan semua role: yang dibandingkan adalah **akun**, bukan role.
- **Pengaju** dibaca di server dari riwayat (`article_reviews`, aksi `submit` terbaru), bukan dari kiriman browser. Admin yang mengajukan draf milik penulis tidak bisa lalu menyetujuinya sendiri.
- Pengaju yang bukan penulis boleh **Tarik kembali** pengajuannya sendiri (ia tidak boleh "Kembalikan ke draf", itu keputusan tinjauan). Bila artikel diajukan ulang oleh orang lain, pengaju terakhir yang berlaku.
- Tidak dibatasi: mengajukan, menarik, **Tarik ke draf / Arsipkan / Pulihkan** artikel yang sudah terbit, dan menyunting isi.
- Panel menjelaskannya: "Menunggu keputusan admin atau editor LAIN: artikel ini Anda tulis/ajukan, dan aturan empat mata tidak membolehkan Anda memutuskannya." Percobaan lewat permintaan buatan tangan ditolak server dengan pesan "Aturan empat mata: …".
- **Syarat praktis:** harus ada minimal DUA akun admin/editor. Dengan satu akun dan aturan ini menyala, artikelnya akan tertahan di Ditinjau.
- Artikel yang sudah berstatus Ditinjau tanpa riwayat pengajuan (data lama/diimpor): hanya aturan penulis yang berlaku, karena pengaju tidak diketahui.
