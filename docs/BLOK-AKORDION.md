# Blok Akordion / FAQ

Daftar pertanyaan yang jawabannya terbuka-tutup. Dipakai untuk halaman FAQ dan keterangan program. Blok ini adalah **modul pertama** (`app/Editor/Blocks/AccordionBlock.php`); lihat `RESEP-BLOK.md`.

## Cara memakai
1. **+ Tambah blok → Akordion / FAQ** (juga tersedia di dalam kolom, bukan di dalam Grup Langkah).
2. Tab **Blok**: klik sebuah pertanyaan untuk membukanya, isi **Pertanyaan** (satu baris) dan **Jawaban** (banyak baris), per bahasa. Tombol ↑ ↓ mengurutkan, juga ada duplikat dan hapus. Maksimal 30 pertanyaan.
3. Atur **Gaya** (Kotak/Garis), **Warna aksen**, **Penanda** (panah atau plus/minus), dan tiga pilihan di bawahnya.

## Menulis jawaban (teks biasa)
| Ketik | Hasil |
|---|---|
| baris kosong | paragraf baru |
| baris baru | pindah baris (`<br>`) |
| `- ` / `* ` / `• ` di awal baris | daftar poin |
| `https://…` | tautan otomatis (buka di tab baru) |

Contoh:
```
Gejala malaria:
- demam
- menggigil

Segera ke puskesmas: https://ysbh.org/layanan.
```
Tidak ada HTML. Apa pun yang Anda ketik **ditampilkan apa adanya** (`<b>x</b>` tampil sebagai teks `<b>x</b>`), jadi tulisan seperti "usia <5 tahun" aman. Format tebal/miring/tautan bernama menunggu editor teks berformat (tahap Tiptap).

## Pilihan blok
| Pilihan | Arti |
|---|---|
| Buka pertanyaan pertama | pertanyaan pertama terbuka saat halaman dimuat |
| Boleh membuka beberapa sekaligus | bila mati (bawaan), membuka satu menutup yang lain |
| Tandai untuk mesin pencari (FAQPage) | menambahkan data terstruktur schema.org. **Aktifkan pada satu blok per halaman.** Catatan: Google membatasi tampilan FAQ khusus di hasil pencarian untuk situs pemerintah dan kesehatan, jadi hasilnya tidak dijamin muncul |

## Perilaku di situs
- Memakai `<details>`/`<summary>` bawaan peramban: **jalan tanpa JavaScript**, bisa dioperasikan dengan papan ketik (Enter/Spasi) dan pembaca layar, dan isi jawaban ada di HTML.
- "Satu terbuka sekali waktu" memakai atribut `name` pada `<details>` (Chrome/Edge 120+, Safari 17.2+, Firefox 130+). Di peramban lebih lama tetap berfungsi, hanya saja beberapa pertanyaan bisa terbuka bersamaan.
- **Di kanvas editor semua pertanyaan terbuka** supaya jawabannya terlihat (klik di kanvas memilih blok, bukan membuka-tutup).
- Pertanyaan tanpa teks dilewati. Bahasa yang kosong jatuh ke bahasa Indonesia, lalu Inggris.

## Keamanan
Pertanyaan dan jawaban selalu di-escape; hanya struktur dari daftar tetap (`p, br, ul, li, a`) yang ditambahkan, dan tautan hanya `http(s)`. Kelas CSS berasal dari daftar tetap (`AccordionStyle`). Data dibersihkan saat disimpan, saat titipan pratinjau, dan sekali lagi saat dirender. Blok `schema` memakai `JSON_HEX_TAG`, sehingga `</script>` di dalam teks tidak bisa menutup blok data terstruktur.

## Bentuk data
```json
{ "type": "accordion-builder", "data": {
  "style": "boxed | lines", "color": "foresty | coral | aurum | charcoal", "marker": "chevron | plus",
  "first_open": false, "allow_multiple": false, "schema": false,
  "items": [ { "id": "itm_ab12cd34", "question": {"id": "…", "en": "…"}, "answer": {"id": "…", "en": "…"} } ] } }
```

## Berkas
`app/Editor/Blocks/AccordionBlock.php` (definisi, penempatan, pembersih), `app/Content/Blocks/FaqText.php` (teks → HTML aman), `AccordionStyle.php` (kelas), `resources/views/components/blocks/render/accordion-builder.blade.php` (tampilan), `tests/accordion-test.php`.
