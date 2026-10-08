# Jenis berkas di File Manager (`allowedFileType`)

## Keadaan sekarang
File Manager menerima dua nilai filter lewat event `openFileManager`: **`image`** dan **`pdf`**. Kit memakainya begini:

| Pemakai | Nilai | Catatan |
|---|---|---|
| bidang gambar (foto profil, gambar blok, dst.) | `image` (bawaan `Field::media`) | tidak berubah |
| Daftar Unduhan | **tanpa filter** (`Field::media(..., '')`) | kunci `allowedFileType` tidak dikirim |
| pemilih berkas di tautan (jenis "Berkas") | tanpa filter | idem |

**Bila saat menguji Daftar Unduhan File Manager hanya menampilkan gambar** (artinya tanpa kunci itu File Manager jatuh ke `image`): ubah satu kata di `app/Editor/Blocks/DownloadsBlock.php`, `Field::media('file', '…', '')` menjadi `Field::media('file', '…', 'pdf')`. Itu cukup untuk laporan PDF, dan tidak mengubah berkas lain.

## Apakah perlu menambah `audio` dan `video`?
**Keputusan (2026-10-07): video TIDAK disimpan di server; hanya sematan YouTube/Vimeo (blok Video, rilis 8, `BLOK-VIDEO.md`).** `audio` belum dibutuhkan; ditunda sampai ada blok yang memakainya. Alasannya: Alasannya:
1. **Tidak ada blok yang memakainya.** Rencana blok **Video** adalah menyematkan YouTube/Vimeo (hanya alamat), bukan mengunggah berkas video. Callout, Galeri, dan Artikel terbaru tidak butuh audio/video.
2. **Hosting bersama bukan tempat yang baik untuk video.** Video makin besar dan memakan bandwidth; tidak ada transkode, streaming adaptif, atau CDN. Satu video 50 MB yang ditonton ratusan kali bisa menghabiskan kuota. Audio (mis. spot radio, rekaman penyuluhan) jauh lebih ringan dan lebih masuk akal.
3. **Menambah filter saja tidak cukup.** Filter di pemilih hanya sebagian kecil pekerjaan; unggahan juga harus mengizinkan jenis itu (validasi mime/ekstensi), batas ukuran PHP (`upload_max_filesize`, `post_max_size`) harus cukup, dan daftar berkas butuh ikon/pratinjau untuk jenis itu.

## Kapan sebaiknya ditambahkan
- **`audio`**: bila Anda ingin blok **Audio** (pemutar `<audio>` untuk rekaman penyuluhan/podcast). Ringan dan bermanfaat; saya sarankan sebelum video.
- **`video`**: hanya bila ada klip pendek yang memang harus di server sendiri (mis. ≤ 20 MB, tanpa YouTube). Selain itu, tetap sematkan dari YouTube/Vimeo.

## Yang saya perlukan bila Anda memutuskan menambahkannya
Kode komponen `file-manager` (bagian yang menerima `allowedFileType` dan menyaring daftar) dan aturan unggahannya (validasi jenis dan ukuran). Tanpa itu saya tidak akan menebak nama nilai atau cara menyaringnya; blok barunya sendiri kecil (modul + tampilan), dan memakai `Field::media($key, $label, 'audio')`.
