# Blok Artikel Terbaru

Artikel terbit dari basis data, dalam dua bagian: **Unggulan** (0-6 artikel dalam kisi bento yang dinamis) dan **Daftar** (kartu seragam: kolom atau baris, jumlah tetap atau berhalaman). Blok ini **dinamis**: ia tidak menyimpan artikel, melainkan membacanya saat halaman dirender, jadi artikel baru muncul sendiri. Modul: `app/Editor/Blocks/LatestArticlesBlock.php`. (Rilis 48 mengubahnya dari satu deretan kartu menjadi blok yang bisa difilter, disematkan, dan dihalamani. Blok lama tampil persis seperti sebelumnya.)

## Cara memakai
1. **+ Tambah blok → Artikel Terbaru**.
2. Tab **Blok**:
   - **Sumber artikel**: *Semua artikel*, atau *Kategori tertentu* lalu cari dan tambahkan beberapa kategori (maks. 20). Contoh: halaman **Penelitian** memilih hanya kategori Penelitian. Sumber kategori tanpa kategori terpilih = tidak ada artikel (bukan semua artikel).
   - **Artikel unggulan** 0-6: kisi di atas daftar. 0 = tanpa kisi (seperti blok lama).
   - **Sematkan artikel**: cari judul, tambahkan, geser urutannya dengan panah. Sematan tampil lebih dulu di kisi; kursi yang tersisa diisi artikel terbaru sesuai sumber blok. Sematan **tidak ikut saringan kategori**, tetapi harus terbit dan punya terjemahan di bahasa halaman (yang tidak memenuhi dilewati, kursinya diisi artikel terbaru).
   - **Balik kiri-kanan**: mencerminkan kisi (untuk 2-6 unggulan).
   - **Daftar di bawahnya**: *Kolom* (2/3/4) atau *Baris* (gambar di kiri).
   - **Penomoran**: *Jumlah tetap*, atau *Halaman berikutnya* (tombol Sebelumnya/angka/Berikutnya). **Jumlah artikel di daftar** = per halaman (3, 6, 9, 12).
   - Sakelar gambar sampul, kategori, tanggal, ringkasan; judul bagian; tautan "lihat semua".
3. Di **kanvas** yang tampil adalah artikel terbit sungguhan (atau keterangan bila belum ada). Tombol halaman di kanvas hanya tampilan, bukan tautan.

## Kisi unggulan (bento)
Pembagian petak menurut jumlah artikel (a = artikel pertama):
| Jumlah | Susunan |
|---|---|
| 1 | spanduk besar |
| 2 | satu baris: 1/3 + 2/3 |
| 3 | dua baris: satu petak 2/3 setinggi dua baris + dua petak 1/3 bertumpuk |
| 4 | pola Z: (2/3 + 1/3) setinggi 2/3, lalu (1/3 + 2/3) setinggi 1/3 |
| 5 | 3x3: a = baris 1-2 kolom 1-2, b = baris 1 kolom 3, c = baris 2-3 kolom 3, d dan e = dua sel sisa |
| 6 | "kincir angin": empat petak panjang mengelilingi dua petak teks (emas dan hijau, tanpa gambar) di tengah |
Sudut petak berselang-seling (bukan kotak seragam). Ringkasan hanya tampil di petak terbesar. Layar sempit (≤ 640 px): petak ditumpuk, yang pertama potret. Bila artikel yang tersedia kurang dari jumlah unggulan, kisi menyesuaikan jumlah yang ada.

## Penomoran
Tombol halaman adalah **tautan biasa** (`?hal1a2b3c=2#ula-hal1a2b3c`): bekerja tanpa JavaScript dan terbaca mesin pencari (`rel="prev"/"next"`). Nama parameter dibentuk dari id blok, jadi dua blok berhalaman di satu halaman tidak saling menimpa; parameter lain di alamat dipertahankan; halaman 1 tanpa parameter. Nomor tak sah jadi halaman 1, di atas batas jadi halaman terakhir. Hitungan halaman hanya untuk daftar (artikel unggulan dikurangkan dan tampil di semua halaman).
Catatan: `canonical` halaman tidak mengikuti parameter ini.

## Dari mana datanya
| Bagian kartu | Sumber |
|---|---|
| judul, tautan | `title` dan `slug` artikel menurut bahasa halaman; tautan dari templat `cms.public.article` |
| kategori | nama kategori artikel (`categories.name`) menurut bahasa |
| tanggal | `published_at`, "8 Okt 2026" (id) / "Oct 8, 2026" (en), dengan `<time datetime>` |
| ringkasan | `meta_description` bila ada; bila tidak, paragraf pertama isi (tanpa HTML, ±160 karakter) |
| sampul | kolom `featured_image` (ID media, atau nama berkas lama lewat templat `cms.public.cover`); tanpa sampul = latar gradien hijau |

## Aturan
- **Per bahasa:** blok di halaman EN hanya menampilkan artikel yang punya slug dan judul EN; tidak ada kartu yang menautkan ke 404.
- Hanya artikel **terbit**, paling baru dulu (tanggal sama: id terbesar dulu). Artikel tanpa judul dilewati.
- Di halaman artikel, **artikel yang sedang dibuka dikeluarkan** dari semua bagian, juga bila disematkan.
- Pembacaan basis data gagal: blok tidak dirender dan galatnya dilaporkan; halaman lain tetap tampil. Tanpa artikel: tidak dirender di situs (di kanvas ada keterangan yang membedakan "belum pilih kategori" dan "kategori belum punya artikel").
- Halaman **daftar artikel** (`⚡articles-index`) memberi data berhalaman sendiri lewat prop `feed`: jalur lama, hanya kartu; pengaturan unggulan dan penomoran blok diabaikan di sana.
- `definition()` tidak boleh mengakses basis data (dipanggil juga di landing). Pencarian kategori/artikel di inspektur dilakukan aksi server `searchLinkTargets` (jenis `category`, `article`).

## Keamanan
Semua teks di-escape. Pilihan disimpan sebagai `[{id, label}]`: hanya `id` (bilangan bulat positif, tanpa duplikat, batas jumlah) yang dipakai renderer, label hanya tampilan inspektur dan dibersihkan jadi teks polos. Kueri memakai id sebagai parameter terikat. Alamat artikel dan sampul dibentuk dari templat konfigurasi dengan pola ketat; data blok dibersihkan saat disimpan dan sekali lagi saat dirender. Gaya bento ada di `<style>` kecil bawaan blok (kelas `.ula-*`), tidak bergantung pada kelas Tailwind yang harus dikompilasi.

## Berkas
`app/Editor/Blocks/LatestArticlesBlock.php`, `app/Content/Blocks/ArticleCards.php` (kartu, kisi `bento()`, `picks()`, halaman; murni), `app/Content/PublicLookup.php` (`blockArticles()`; hanya membaca), `app/Livewire/Traits/SearchesLinkTargets.php` (jenis `category`), `app/Editor/Field.php` (`Field::picker`), `resources/views/components/editor/picker.blade.php` dan `field.blade.php`, `resources/js/editor.js` (`pickerField`, `isAny`), `resources/views/components/blocks/render/latest-articles-builder.blade.php`, tes: `tests/latest-articles-test.php`, `tests/article-cards-test.php`, `tests/latest-articles-render-test.php`, `tests/data-test.php`.
