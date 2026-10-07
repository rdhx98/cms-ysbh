# Artikel di builder: apa yang sudah dan belum

Dasar: `_article-editor_blade.php` dan `_article-index_blade.php` (editor dan daftar artikel lama).

## Keputusan data yang perlu Anda ketahui
- **Format isi berubah.** Editor lama menyimpan isi artikel sebagai **satu HTML** (Tiptap). Builder menyimpan **dokumen blok** seperti halaman.
  Artikel lama yang dibuka di builder diimpor sebagai **satu blok Paragraf berisi HTML aslinya** (gambar dan kelas utuh), dan data di database
  tidak berubah sampai Anda menekan Simpan. Pembacaan memakai **nilai mentah** kolom, karena membaca lewat cast `array` pada kolom berisi HTML menghasilkan `null`
  (dan menyimpannya akan menghapus isi artikel).
- **Halaman publik artikel** (`article.show`) harus mendukung blok sebelum artikel yang disimpan dari builder dipakai sungguhan.
- **Kategori dan tag** = `name` dan `slug` berupa string biasa (bukan terjemahan JSON), seperti yang terbaca di berkas Anda.
- **Judul dan slug** artikel memakai per-bahasa (JSON) seperti halaman. Bila kolom `posts.title`/`slug` Anda masih string, nilai lama dibaca sebagai bahasa pertama (aman),
  dan menyimpan dari builder menulis JSON.

## Peta fitur editor artikel lama
| Fitur lama | Builder |
|---|---|
| judul, slug (otomatis dari judul) | ✅ per bahasa; slug otomatis hanya untuk artikel baru |
| unik judul/slug | ✅ slug unik per bahasa (tahan baris lama berisi slug polos) |
| kategori (wajib) | ✅ dropdown |
| tag (minimal satu, boleh buat baru) | ✅ input tag; nama sama dengan tag lama tidak menggandakan |
| status draft → review, hanya penulis | ✅ penulis biasa: draf/ditinjau; admin/editor: semua. Dicek di server |
| isi (Tiptap satu kolom) | ✅ sebagai blok; teks berformat masih hanya-baca sampai Tiptap tunggal (Fase 3) |
| **gambar sampul** (unggah / pilih dari gambar isi) | ❌ belum. Artikel baru memakai `default.webp`; nilai lama tidak ditimpa |
| **tombol "Ajukan tinjauan"** (simpan + status review + kembali ke daftar) | ❌ belum. Status *Ditinjau* bisa dipilih manual |
| simpan gambar base64 ke storage | ❌ tidak diperlukan: gambar lewat file manager |
| hapus berkas gambar yang dibuang dari isi | ❌ digantikan pelacakan "Digunakan Di" di file manager |
| riwayat perubahan (audit trail) | ❌ belum ditampilkan (Post sudah memakai `LogsActivity`) |
| tanggal dibuat dapat diubah | ❌ belum |
| penulis lebih dari satu (`authors`) | ❌ belum; `user_id` = pengguna yang membuat, tidak tertimpa saat orang lain menyimpan |
| pratinjau artikel (`article.preview`) | ❌ belum |
