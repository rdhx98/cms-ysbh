# Blok Video (YouTube / Vimeo)

Menyematkan video dari **YouTube** atau **Vimeo**. Tidak ada berkas video di server (hosting bersama tidak cocok untuk itu; lihat `JENIS-BERKAS.md`). Blok ini adalah modul (`app/Editor/Blocks/VideoBlock.php`).

## Cara memakai
1. **+ Tambah blok → Video** (juga di dalam kolom, bukan di dalam Grup Langkah).
2. Tab **Blok**: tempel **Alamat video** (dari bilah alamat peramban atau tombol Bagikan), isi **Judul video** (dibaca pembaca layar dan jadi nama tombol putar), dan **Keterangan** (opsional, tampil di bawah video).
3. Pilih **Rasio layar** dan **Lebar**. Gambar sampul (dari File Manager) bersifat opsional.

## Alamat yang dikenali
| Penyedia | Contoh |
|---|---|
| YouTube | `https://www.youtube.com/watch?v=ID`, `https://youtu.be/ID`, `…/shorts/ID`, `…/embed/ID`, `…/live/ID` |
| Vimeo | `https://vimeo.com/123456789`, `https://vimeo.com/123456789/abcdef1234` (video tak terdaftar), `https://player.vimeo.com/video/123456789` |
| jam mulai | tambahkan `?t=90`, `?t=1m30s` (YouTube) atau `#t=30s` (Vimeo) |

Tidak didukung: daftar putar (playlist), kanal, dan penyedia lain. Alamat yang tidak dikenali ditolak: di kanvas tampil penjelasan, di situs blok tidak ditampilkan.

## Perilaku di situs: "klik untuk memutar"
- **Sebelum diklik tidak ada iframe dan tidak ada permintaan ke pihak ketiga**: tidak ada cookie atau pelacak YouTube/Google/Vimeo, dan halaman lebih ringan. Fasad berupa gambar sampul (atau latar hijau bila tidak ada) dengan tombol putar dan nama penyedia.
- **Setelah diklik**, video dimuat di tempat dan langsung diputar. YouTube memakai domain mode privasi `youtube-nocookie.com` (tanpa video terkait), Vimeo memakai `dnt=1`.
- **Tanpa JavaScript**, fasad adalah tautan biasa ke halaman video di penyedia.
- Bisa dioperasikan dengan papan ketik (Tab, lalu Enter); tombol bernama "Putar video: {judul}".
- Gambar sampul tidak diambil otomatis dari YouTube (itu permintaan ke Google saat halaman dimuat); pilih sendiri dari File Manager bila mau.
- Di **kanvas editor** video tidak diputar (klik memilih blok); yang tampil hanya fasadnya.

## Keamanan
- **Alamat yang Anda tempel tidak pernah dipasang ke halaman.** Ia hanya dibaca untuk mengambil ID video (pola ketat: 11 karakter YouTube, 5–12 angka Vimeo). Alamat iframe dan tautan **dibangun ulang** dari konstanta dan ID itu. Karena itu `javascript:`, host serupa (`youtube.com.evil.test`, `youtube.com@evil.test`), port, dan sisipan HTML tidak punya jalan masuk.
- Sematan hanya dari `www.youtube-nocookie.com` dan `player.vimeo.com`; atribut `allow` dibatasi (`autoplay; encrypted-media; picture-in-picture; fullscreen`), `referrerpolicy="strict-origin-when-cross-origin"`.
- Judul dan keterangan di-escape. Gambar sampul hanya dari model Media dan hanya bila berjenis gambar.
- Diuji dengan 6000 alamat acak dan 3000 masukan biner: hasil yang diterima selalu berpola aman.

## Bila video tidak muncul setelah diklik
- Situs Anda mungkin memakai **Content-Security-Policy** dengan `frame-src`/`default-src`. Tambahkan `https://www.youtube-nocookie.com https://player.vimeo.com`.
- Video bersifat privat, dihapus, atau pemiliknya melarang penyematan: YouTube/Vimeo sendiri yang menampilkan pesannya di dalam kotak.

## Bentuk data
```json
{ "type": "video-builder", "data": {
  "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=90",
  "title": {"id": "…", "en": "…"}, "caption": {"id": "…", "en": "…"},
  "poster": {"media_id": 7, "url": "…/sampul.jpg"},
  "ratio": "16:9 | 4:3 | 1:1 | 9:16 | 21:9", "max_width": "full | lg | md" } }
```
`url` disimpan apa adanya (hanya untuk ditampilkan di inspektur) dan divalidasi ulang setiap kali dirender.

## Berkas
`app/Editor/Blocks/VideoBlock.php`, `app/Content/Blocks/VideoUrl.php` (penguraian alamat), `VideoStyle.php` (rasio dan lebar), `resources/views/components/blocks/render/video-builder.blade.php`, `tests/video-test.php`.
