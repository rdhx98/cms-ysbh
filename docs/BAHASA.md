# Bahasa: keputusan dan rencana (tahap 3)

**Situasi.** Bahasa bawaan situs `en` (target utama yayasan: donasi internasional); `id` untuk pembaca lokal. Pengalih "ID/EN" di header saat ini hanya mainan Alpine: tidak mengganti apa pun. Halaman statis (beranda, tentang, program) berisi teks Indonesia yang ditulis langsung di Blade, tanpa terjemahan.

## Rekomendasi
**Satu alamat per bahasa: bahasa bawaan (EN) tanpa awalan, bahasa lain dengan awalan `/id`, dan slug berbeda per bahasa. Bukan parameter `?lang`.**

| | EN (bawaan) | ID |
|---|---|---|
| Beranda | `/` | `/id` |
| Halaman CMS | `/about-us` | `/id/tentang-kami` |
| Artikel | `/articles/{slug-en}` | `/id/artikel/{slug-id}` |
| Daftar artikel | `/articles` | `/id/artikel` |

**Mengapa bukan `?lang=`.**
1. Satu alamat melayani dua bahasa. Mesin pencari memperlakukan parameter sebagai salinan halaman yang sama dan biasanya mengindeks satu bahasa saja; Google menyarankan alamat terpisah per bahasa. Untuk donor internasional yang datang dari pencarian, itu paling merugikan.
2. Tautan yang dibagikan tidak membawa bahasa kecuali parameternya ikut; cache (CDN, peramban) harus dikunci ke parameter itu.
3. Peta situs dan `hreflang` tidak bisa menunjuk dua bahasa di satu alamat.

**Mengapa bukan slug saja (tanpa awalan).** Halaman statis dan beranda tidak punya slug yang bisa membawa bahasa. Slug yang sama di dua bahasa (mis. `artikel`) tidak bisa menentukan bahasa. Dan hari ini, halaman bisa dibuka lewat slug bahasa lain secara diam-diam, yaitu salinan ganda yang tidak terkendali. Awalan menyelesaikan ketiganya: **bahasa ditentukan oleh alamat, titik.**

**Bahasa tidak ditebak.** Tanpa pengalihan otomatis dari `Accept-Language`/IP dan tanpa cookie yang mengubah isi satu alamat (merusak cache dan pengindeksan). Pengunjung memilih lewat pengalih; paling jauh sebuah *spanduk* ("Baca dalam Bahasa Indonesia?") yang tidak mengalihkan sendiri.

## Konsekuensi di kode (yang akan dikerjakan di tahap 3)
1. **Middleware bahasa**: bahasa dari awalan alamat (`/id` -> `id`, selain itu bawaan).
2. **`PublicLookup::findPage/findArticle` hanya mencari di bahasa alamat itu.** Bila slug hanya ada di bahasa lain: pengalihan 301 ke alamat kanoniknya (bukan menayangkan salinan). Kini keduanya mencari di semua bahasa.
3. **Templat alamat per bahasa** di `config('cms.public')` (`page`, `article`), dipakai `LinkResolver`, peta situs, dan tautan internal di teks kaya.
4. **Rute statis berkelompok** dua kali (tanpa awalan dan `id`), dengan nama rute berbahasa; menu (`Navigation`) memakai label per bahasa yang sudah ada.
5. **Pengalih bahasa di header** menuju alamat saudara: halaman CMS -> slug bahasa lain; halaman statis -> tambah/buang `/id`; artikel -> slug bahasa lain; bila tidak ada terjemahannya, pengalih menuju beranda bahasa itu (atau disembunyikan).
6. **`hreflang`** di `<head>` (`en`, `id`, `x-default` = EN) dan di peta situs.
7. **Slug terlarang** berubah: kata `articles` (kini pengalihan ke `/artikel`) menjadi alamat sungguhan.

## Yang harus Anda ketahui sebelum menyetujui
- **Isi blok tidak punya cadangan bahasa.** Renderer Anda mencetak `$data['text'][$lang] ?? ''`: blok yang belum diterjemahkan ke EN tampil **kosong** di situs EN (bukan teks Indonesia). Judul halaman wajib di semua bahasa (aturan CMS), tetapi isi blok tidak. Karena EN adalah bahasa bawaan, **setiap halaman harus punya isi EN sebelum diterbitkan**. Perintah audit terjemahan (daftar halaman dengan blok tanpa teks EN) bisa saya buat.
- **Halaman statis perlu diterjemahkan.** Beranda dan halaman program berisi teks Indonesia di Blade. Ada dua jalan: pindahkan ke halaman CMS (bilingual), atau ke berkas terjemahan Laravel (`lang/en`, `lang/id`) bila tata letaknya khas (beranda). Lihat rencana tahap 2.
- Alamat lama tanpa awalan yang kini Indonesia (mis. `/tentang-kami`) perlu dialihkan 301 bila sudah pernah diindeks. Situs belum terbit, jadi biayanya nol **sekarang**.
