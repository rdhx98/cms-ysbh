# Blok Callout / Catatan penting

Kotak berwarna untuk informasi, saran, peringatan, atau bahaya (mis. peringatan medis di halaman kesehatan), dengan judul opsional, isi, ikon, dan satu tautan aksi. Blok ini adalah modul (`app/Editor/Blocks/CalloutBlock.php`).

## Cara memakai
1. **+ Tambah blok → Callout / Catatan** (juga di dalam kolom, bukan di dalam Grup Langkah).
2. Tab **Blok**: isi **Judul** (opsional), **Isi** (banyak baris), per bahasa. Blok tanpa judul **dan** tanpa isi tidak ditampilkan di situs.
3. **Tautan aksi** (opsional): isi **Teks tautan** lalu pilih **Tujuan** (halaman, artikel, berkas, URL, telepon, surel, anchor), misalnya `Hubungi 119` → Telepon → `119`.
4. Pilih **Jenis**, **Gaya**, dan ikon.

## Pilihan
| | Pilihan |
|---|---|
| **Jenis** | Info (biru), Sukses (hijau), Peringatan (kuning), Bahaya (merah), Catatan (abu-abu), Merek (hijau Foresty) |
| **Gaya** | Lembut (latar muda + garis aksen kiri), Garis (latar putih + bingkai berwarna), Penuh (latar pekat, teks putih; peringatan memakai teks gelap di atas kuning demi keterbacaan) |
| **Ikon** | tiap jenis punya ikon bawaan sendiri (tidak bergantung pada daftar ikon aplikasi). Matikan lewat "Tampilkan ikon", atau pilih ikon lain; "Hapus ikon" mengembalikan ke ikon bawaan jenis |

## Menulis isi (teks biasa)
Sama dengan jawaban FAQ: baris kosong = paragraf baru, `- ` di awal baris = daftar poin, `https://…` = tautan otomatis. Tidak ada HTML; apa pun yang diketik tampil apa adanya ("usia <5 tahun" aman).

## Aksesibilitas
Kotak memakai `<aside role="note">` dengan nama jenis ("Peringatan", "Bahaya", …) untuk pembaca layar, sehingga makna tidak hanya bergantung pada warna dan ikon. Pasangan warna teks/latar dipilih agar kontrasnya memadai.

## Aturan tampilan
| Situasi | Situs | Kanvas |
|---|---|---|
| judul dan isi kosong | tidak dirender | kotak "masih kosong" (bisa dipilih) |
| aksi: teks ada, tujuan sah | tautan tampil | tampil |
| aksi: teks ada, tujuan kosong / halaman belum online / `javascript:` | **tidak tampil** | tampil **pudar bertepi putus-putus** |
| aksi: teks kosong | tidak tampil | tidak tampil |

## Keamanan
- Judul dan isi di-escape; hanya struktur dari daftar tetap (`p, br, ul, li, a`) yang ditambahkan, tautan hanya `http(s)`.
- Tautan aksi memakai aturan **yang sama persis** dengan tombol (`BlockSanitizer`): hanya `http(s)`, `mailto`, `tel`, `/jalur`, `#anchor`; `tel:` dan `mailto:` tidak membuka tab baru.
- Kelas CSS dari daftar tetap; data dibersihkan saat disimpan, saat titipan pratinjau, saat pratinjau tersimpan, dan sekali lagi saat dirender.

## Yang sengaja tidak ada (bisa ditambah bila perlu)
Callout yang dapat ditutup pengunjung (butuh penyimpanan di peramban), yang dapat dilipat, warna kustom di luar enam jenis, dan lebih dari satu tautan aksi (untuk beberapa tombol, taruh blok **Tombol** di bawahnya).

## Bentuk data
```json
{ "type": "callout-builder", "data": {
  "tone": "info | success | warning | danger | neutral | brand", "style": "soft | outline | solid",
  "show_icon": true, "icon": "",
  "title": {"id": "…", "en": "…"}, "body": {"id": "…", "en": "…"},
  "action": { "label": {"id": "…", "en": "…"}, "link": { "kind": "tel", "ref": "119", "ref_label": "", "media_id": null, "url": "", "new_tab": false } } } }
```

## Berkas
`app/Editor/Blocks/CalloutBlock.php`, `app/Content/Blocks/CalloutStyle.php`, `resources/views/components/blocks/render/callout-builder.blade.php`, `tests/callout-test.php`.
