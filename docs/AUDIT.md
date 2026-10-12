# Riwayat perubahan / audit trail (rilis 42; pencatatan model rilis 44)

Tab ketiga di panel kanan editor: **Halaman · Blok · Riwayat**. Untuk **Page, Artikel (Post), dan Snippet** yang sudah tersimpan. Hanya **admin dan editor** yang melihatnya (penulis biasa tidak; server juga menolak panggilannya).

## Apa yang tampil
Per entri: **jenis** (Dibuat · Diubah · Dihapus · Dipulihkan, atau kejadian kustom), **siapa** (nama pengguna; "Sistem" bila tanpa pengguna, mis. perintah terbit terjadwal), **kapan**, dan **kolom yang berubah** dengan nilai lama → baru (judul, slug, status, kategori, penulis, waktu terbit, thumbnail, kunci snippet, dst., label Indonesia). Terbaru dulu, 20 per muatan, tombol **Muat lebih banyak**. Dimuat setiap tab dibuka (tombol **Muat ulang** tersedia).

Aturan tampilan (`App\Content\AuditTrail`):
- **Isi (`content`) hanya ditulis "Isi diubah"**, tanpa nilai. Dokumen blok bukan untuk dibaca sebagai teks mentah di panel samping, dan tidak seharusnya disimpan penuh ke log.
- Kata sandi, token, dan cap waktu rutin (`created_at`, `updated_at`, `deleted_at`) **tidak pernah** ditampilkan, walau terlanjur masuk log.
- Judul per bahasa tampil "ID: … · EN: …"; ID kategori/penulis diganti nama; status memakai label ("Ditinjau", "Terbit"); nilai dipendekkan 80 karakter, tag HTML dibuang. Semua nilai ditampilkan sebagai **teks** (tidak ada HTML hidup).
- Lebih dari 8 perubahan dalam satu entri: 8 pertama + "+ N perubahan lainnya".

## Syarat: modelnya harus MENCATAT (rilis 44: sudah disiapkan)
Panel hanya **membaca** tabel `activity_log` (spatie/laravel-activitylog). Yang menulis adalah model. Rilis 44 menyediakan model siap timpa:

| Model | Berkas | Dicatat |
|---|---|---|
| **Page** (milik Anda) | `contoh-kode/model/Page.php` | dibuat, diubah, dihapus; judul, **slug** (alamat halaman), **status online/offline**, judul/deskripsi SEO, waktu terbit; **isi diubah** |
| **Post / artikel** (milik Anda) | `contoh-kode/model/Post.php` | semua di atas + kategori, penulis, thumbnail; **setiap pergantian status** (diajukan, diterbitkan, dijadwalkan, ditolak, dikembalikan, diarsipkan) |
| **Snippet** (ikut kit) | `app/Models/Snippet.php` | kunci, judul, status, penutup halaman, urutan, catatan admin; **isi diubah**; dihapus dan **dipulihkan** |

`Page.php` dan `Post.php` adalah **model Anda yang sudah digabung** (isinya sama, hanya bagian pencatatan yang berubah; lihat `diff`): periksa lalu timpa. Semuanya memakai trait baru `app/Models/Concerns/RecordsChanges.php`.

**Yang dikerjakan trait:**
1. **"Isi diubah" tanpa menyimpan isi.** `content` tetap tidak masuk `logOnly` (dokumen blok besar). Tetapi simpan yang hanya mengubah isi tidak akan meninggalkan jejak, jadi trait menambah **satu baris `content_updated`** (siapa, kapan) setiap kali `content` benar-benar berubah. Simpan ulang tanpa perubahan tidak mencatat apa-apa.
2. **Deskripsi status.** Bila `status` berubah, deskripsi baris = `status_<nilai>` (`status_published`, `status_online`, ...). Panel menampilkannya sebagai "Diterbitkan", "Diaktifkan (online)", "Dinonaktifkan (offline)", "Diajukan untuk ditinjau", "Dikembalikan ke draf", "Dijadwalkan terbit", "Ditolak", "Diarsipkan"; lama → baru tetap tampil di bawahnya. Kode untuk artikel sama dengan yang sudah Anda pakai di `Post`, jadi riwayat lama ikut terbaca. `getDescriptionForEvent` lama di `Post` (yang menulis `updated_general`/`unknown`) digantikan; panel juga mengabaikan dua deskripsi itu pada baris lama.
3. **Pencatatan tidak pernah menggagalkan simpan**: galat pada log dilaporkan (`report()`) dan diabaikan; tanpa paket Spatie, trait diam.

Pelaku = pengguna yang login. Terbit terjadwal (`cms:publish-scheduled`) dan perintah konsol: "Sistem".

**Nama log:** `Post` tetap `article_updates` (riwayat lama tetap satu rangkaian), `Snippet` `snippet_updates`, `Page` baru `page_updates`. Panel mencari berdasarkan record, bukan nama log.

**Riwayat lama tidak dihapus.** Baris lama `Post` yang memuat `content` utuh tetap tampil sebagai "Isi diubah" (nilai tidak ditampilkan); tabelnya tetap besar sampai Anda membersihkannya (`activitylog:clean`). **Yang sebelumnya tidak tercatat (Page) tidak bisa dipulihkan.**

**Diketahui tidak tercatat:**
- Perubahan lewat kueri massal (`Page::query()->update(...)`, `DB::table(...)`): tidak memicu event model, sama seperti paket Spatie.
- Penulis tambahan (`post_users`) dan tag (`post_tags`): tabel pivot tidak dicatat paket. Perlu pencatatan manual di tempat `sync()` dipanggil bila diperlukan.
- Catatan alasan peninjau artikel tetap di `article_reviews` (panel Status), bukan di riwayat ini.
- Riwayat record yang **sudah dihapus**: barisnya (dengan nilai terakhir) tetap di `activity_log`, tetapi panel hanya terbuka untuk record yang ada. Belum ada halaman riwayat global.

## Dua versi paket
- **v5** (yang dipakai `Snippet` di kit: `Spatie\Activitylog\Support\LogOptions`): perubahan di kolom `attribute_changes`.
- **v4 ke bawah**: perubahan di kolom `properties`.
Panel membaca **keduanya** (v5 didahulukan), jadi tidak perlu mengubah apa pun saat memperbarui paket. Impor `LogOptions`/`LogsActivity` di contoh ditulis untuk v5; di v4 lihat komentar di berkas contoh.

## Keamanan
- `auditTrail(int $offset)` di builder: `#[Renderless]`, memeriksa login dan peran admin/editor, record sudah tersimpan. **Subjek selalu `$this->record`** (jenis dari `getMorphClass()` dan nama kelas untuk log lama sebelum morph map; id dari record); browser hanya mengirim `offset`.
- Tidak ada yang ditulis ke `activity_log` oleh panel ini.
- Bila tabel belum ada / tidak terbaca: panel menulis "Tabel riwayat belum ada atau tidak terbaca. Jalankan `php artisan migrate`" (bukan galat).

## Belum ada
- Halaman riwayat global (semua record, semua pengguna, penyaring) dan tombol **pulihkan nilai lama**. Yang ada hanya panel per record.
- Pelaku selain pengguna (mis. model lain) tampil "#id".
