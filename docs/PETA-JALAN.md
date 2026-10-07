# Peta jalan: dari sini sampai cutover

Urutan menurut apa yang **membuka** hal berikutnya. Tiap tahap punya gerbang: jangan lanjut sebelum lulus.

| # | Tahap | Kenapa sekarang | Gerbang |
|---|---|---|---|
| A ✅ | **Pengaturan halaman + Simpan end-to-end** *(selesai di kode; menunggu uji Anda)* (judul, slug otomatis, status, SEO; snippet: key, deskripsi, penutup, urutan) | tanpa ini **tidak ada yang bisa disimpan**: validasi menuntut judul dan slug yang belum punya kolom | simpan halaman baru → pindah ke rute edit; JSON di database utuh (tinker bagian 4 DEBUG.md); halaman lama dibuka-simpan tanpa perubahan = JSON identik |
| B | **Daftarkan tipe blok yang tersisa**: eyebrow, image, lalu panel **card-builder** (kartu, kolom, elemen) | Kolom dan Grup Langkah sudah terdaftar; Judul/Paragraf/Pemisah juga | `registry-audit` bersih untuk tiap tipe; setiap kontrol menulis ke path yang benar |
| B2 | **Tujuh blok baru**, berurutan. ✅ *repeater* dan **Tombol** (rilis 2). Berikutnya: **Akordion/FAQ** → **Daftar unduhan** → **Callout** → **Video** → **Galeri/logo mitra** → **Artikel terbaru** (dinamis). Resep: `docs/RESEP-BLOK.md` | semua dipakai di halaman yang sudah dirancang; repeater dan pemilih tautan kini siap dipakai ulang | tiap blok: registri, pembersih, render publik, pemeriksaan |
| C | **Kanvas = pratinjau** (ekstrak komponen "seksi" dari `page-preview`; pratinjau memakai token, bukan menimpa record online) | tengah layar masih placeholder; komponen seksi juga dibutuhkan snippet | pratinjau tidak mengubah record yang online; kanvas = tampilan publik |
| D | **Tiptap tunggal** (satu instance per bahasa di inspektur) + **sanitizer server** | teks berformat masih hanya-baca; sanitizer wajib sebelum situs dibuka ke publik | HTML Tiptap lama tidak berubah setelah dibuka-simpan |
| E | **Artikel** (kategori, tag, penulis, gambar unggulan, jadwal, alur status) dan **Snippet** (blok `snippet`, penutup halaman) | melengkapi tiga jenis konten | satu artikel dibuat→ditinjau→dijadwalkan; satu CTA tampil sebagai penutup |
| F | **Polesan**: penjaga perubahan belum tersimpan, cache Back/Forward, tata letak responsif (ponsel/tablet), drag-and-drop (opsional) | kenyamanan, bukan penghalang | uji manual di tablet dan ponsel |
| G | **Cutover**: hapus awalan `v2`, arahkan daftar ke builder, hapus editor lama setelah 2–3 minggu stabil | | tidak ada rute atau tautan yang menunjuk ke `page-editor` |

**Yang saya butuhkan dari Anda per tahap**
- A: konfirmasi kolom `pages` yang ada (saya sudah punya `Page.php`), dan apakah slug harus unik per bahasa.
- B: tidak ada yang perlu dikirim (blade `eyebrow`, `image`, `multi-columns`, `step-group` sudah saya terima).
- B2: pilihan blok dari `USULAN-BLOK.md`.
- A (artikel): **sudah ada** (kategori, tag, status menurut peran). Yang saya perlukan sekarang: hasil `php artisan model:show Post` dan satu contoh baris `posts` (lihat pesan terakhir), untuk memastikan tipe kolom `title`/`slug`/`content`.
- Tahap artikel berikutnya: gambar sampul, tombol "Ajukan tinjauan", renderer publik artikel berbasis blok (lihat `ARTIKEL.md`).
- E: sama seperti di atas.
