# Ringkasan debug yang tertunda (rilis 6 – 20)

Daftar lengkap tiap bagian ada di `DEBUG.md`; ini urutan dan butir **wajib** saja. Estimasi 30–40 menit dengan satu halaman uji.

## Status
| Bagian | Isi | Status |
|---|---|---|
| 6c | Kanvas | selesai (lulus; "editor di dalam editor" diperbaiki rilis 5) |
| 6d | Akordion / FAQ | selesai **kecuali JSON-LD** (diperbaiki rilis 9, cek ulang) |
| 6e | Daftar Unduhan (rilis 6) | belum |
| 6f | Pratinjau versi tersimpan (rilis 6) | belum |
| 6g | Callout (rilis 7) | belum |
| 6h | Video (rilis 8) | belum |
| 6i | Galeri / Logo (rilis 10) | belum |
| 6j | Dua aplikasi: alamat publik + sinkron landing (rilis 11) | belum |
| 6k | Artikel Terbaru, snippet penutup di situs, landing (rilis 12) | belum |
| 6l | Landing tahap 1: menu, halaman CMS, artikel, SEO (rilis 13) | belum |
| 6m | Slug terlarang dan kepala daftar artikel (rilis 15) | belum |
| 6n | Peta situs dan robots.txt (rilis 17) | belum |
| 6o | Penyaring isi publik: HTML, tautan internal, ikon (rilis 18) | belum |
| 6p | Daftar isi dan anchor (rilis 20) | belum |

## A. Pemasangan (sekali)
1. Rilis 6: salin 10 berkas **dan tambahkan satu baris rute** `preview.record` di grup `v2` (ada di `PERUBAHAN.md`). Tanpa baris itu tombol "pratinjau tersimpan" tidak muncul dan 6f gagal.
2. Rilis 7 (4 berkas), rilis 8 (4 berkas), rilis 9 (1 berkas: `accordion-builder.blade.php`). Urutan bebas; nama berkasnya tidak bentrok, kecuali rilis 9 yang menggantikan versi sebelumnya.
3. `php artisan optimize:clear`, `php artisan view:clear`.
4. **Pemindai direktif** (baru): `php tests\blade-scan.php C:\jalur\ke\proyek`. Harus berakhir `tidak ada tabrakan direktif`. Ia juga memeriksa Blade Anda sendiri (mis. JSON-LD di layout).

## B. Satu halaman uji
Buat halaman **"Uji Blok"** (boleh offline) berisi, berurutan:
1. **FAQ**: 3 pertanyaan; jawaban pertama `usia <5 tahun & bayi` lalu baris baru `https://ysbh.org.`; aktifkan "Tandai untuk mesin pencari".
2. **Daftar Unduhan**: 3 butir (satu PDF, satu non-PDF seperti Word/Excel, satu tanpa berkas), tahun berbeda, Kelompokkan = Tahun.
3. **Callout**: Peringatan + Lembut, isi `- demam tinggi` / `- kejang`, tautan aksi **"Hubungi 119"** (Telepon, `119`); satu lagi Bahaya + Penuh.
4. **Video**: alamat YouTube + judul; blok kedua Vimeo (opsional).
5. **Tombol**: "Hubungi 119" (Telepon, `119`) (regresi rilis 7).
6. **Galeri / Logo**: 4–6 gambar, Tampilan = Carousel, lalu satu blok Logo mitra (dua logo bertautan).

Simpan, lalu buka `/v2/preview/page/<id>` (atau tombol **database** di bilah kanvas).

## C. Butir wajib (urut)
| # | Periksa | Hasil yang benar |
|---|---|---|
| 1 | `/v2/preview/page/<id>` | halaman tampil dengan pita kecil di pojok, **tanpa sidebar admin**. 404 = rute belum ada |
| 2 | Ctrl+U, cari `ld+json` | diawali `{"@context":"https://schema.org","@type":"FAQPage"`. Ada `<?php`/`context()->` = rilis 9 belum terpasang |
| 3 | FAQ: `usia <5 tahun & bayi` | tampil apa adanya; `https://ysbh.org.` menjadi tautan, titik di luar |
| 4 | Daftar Unduhan, dari builder: **Jelajahi File Manager** | File Manager menampilkan **semua jenis berkas**. Bila hanya gambar: ubah `''` menjadi `'pdf'` di `DownloadsBlock.php` |
| 5 | Daftar Unduhan di situs | butir tanpa berkas **tidak tampil**; butir lengkap menampilkan "tahun · jenis · ukuran"; klik membuka berkas |
| 6 | **Bidang gambar lain** (gambar blok, foto profil) | File Manager tetap hanya menawarkan **gambar** (rilis 6 mengubah `media.blade.php`) |
| 7 | Callout | enam jenis berwarna benar (bukan polos), tombol "Hubungi 119" menjadi `tel:119`; Peringatan Penuh bertulisan gelap di atas kuning. Warna polos = Tailwind tidak memindai kelasnya: kirim CSS Anda |
| 8 | Tombol "Hubungi 119" | **tampil** (sebelum rilis 7 ditolak) |
| 9 | Video, DevTools → **Network** sebelum klik | **tidak ada** permintaan ke youtube/google/vimeo; tidak ada `<iframe>` |
| 10 | Video, klik fasad | video dimuat dan diputar di tempat; permintaan hanya ke `youtube-nocookie.com`. Kotak kosong/"Refused to frame" = CSP situs |
| 11 | Video, tinggi fasad | berbentuk kotak 16:9. Garis tipis = tambahkan `@source '../../app';` di CSS |
| 12 | Kanvas, ubah judul tanpa Simpan | tombol ↗ menampilkan perubahan; tombol **database** tidak |
| 13 | Galeri, Carousel | panah menggeser satu gambar; di ponsel bisa digeser jari; "Berjalan otomatis" berhenti saat disorot dan tidak berjalan bila efek animasi OS dimatikan; klik foto membuka pembesar (Esc menutup) |

## D. Sebaiknya
Sisa butir di `DEBUG.md` 6e–6h: papan ketik (Tab/Enter), `?t=90` pada video, sampul PDF diabaikan, rasio 9:16, dua video satu halaman, "Digunakan Di" di File Manager (butuh trait `SyncsMediaUsage` di model Halaman/Artikel), offline vs online pada pita pratinjau, dan akses tanpa masuk (403/pengalihan).

## E. Bila gagal, kirimkan
Nomor butir di atas, pesan galat di Console (F12) atau `storage/logs/laravel.log`, dan untuk masalah tampilan: potongan "sumber halaman" (Ctrl+U) di sekitar blok itu.
