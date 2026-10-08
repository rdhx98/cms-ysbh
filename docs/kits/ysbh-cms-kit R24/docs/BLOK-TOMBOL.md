# Blok Tombol

Ajakan bertindak: Donasi, Unduh laporan, Hubungi kami. Satu blok berisi 1 sampai 12 tombol.

## Cara memakai (di editor)
1. **+ Tambah blok → Tombol** (juga tersedia di dalam kolom). Blok baru berisi satu tombol kosong.
2. Di tab **Blok**: klik sebuah tombol di daftar untuk membukanya. Tombol bisa dinaik/diturunkan (↑ ↓), diduplikat, atau dihapus.
3. Isi **Teks Tombol** (per bahasa), pilih **jenis tautan**, lalu atur gaya, warna, ukuran, dan ikon (opsional).
4. **Perataan** dan **Tumpuk ke bawah di layar kecil** ada di bawah daftar (berlaku untuk seluruh blok).

## Jenis tautan
| Jenis | Disimpan sebagai | Catatan |
|---|---|---|
| Halaman, Artikel | **ID** (bukan slug) + nama untuk tampilan | mengganti slug **tidak mematahkan** tombol. Tombol hanya tampil bila tujuannya **online / terbit** |
| Berkas | `media_id` dari File Manager | otomatis tercatat di "Digunakan Di" milik berkas itu |
| URL luar | teks | hanya `https://…`, `http://…`, atau jalur `/halaman`. Selain itu dikosongkan saat disimpan |
| Telepon | nomor | dinormalkan: `+62 812-3456-7890` menjadi `tel:+6281234567890`; minimal 3 angka (nomor darurat **119 / 112 / 110** sah; sebelum rilis 7 minimalnya 5) |
| Surel | alamat | harus alamat surel sah |
| Anchor | `nama-bagian` | menuju blok yang memiliki **ID Tautan (Anchor)** yang sama di halaman itu |

"Buka di tab baru" berlaku untuk halaman, artikel, berkas, dan URL luar (selalu dengan `rel="noopener noreferrer"`).

## Aturan keamanan (ditegakkan di SERVER, bukan hanya di browser)
- Hanya `http`, `https`, `mailto`, `tel`, jalur `/…`, dan `#anchor` yang pernah menjadi `href`. **`javascript:`, `data:`, `vbscript:`, `//host`** dan URL berisi spasi/kontrol **ditolak**.
- Warna, gaya, dan ukuran dipetakan ke **kelas Tailwind dari daftar tetap** (`app/Content/Blocks/ButtonStyle.php`); nilai di luar daftar jatuh ke bawaan.
- Data dibersihkan **dua kali**: saat disimpan (`BlockSanitizer` dipanggil `ContentWriter`) dan saat dirender (jaring kedua, karena data bisa berasal dari impor atau edit langsung di database).
- Tautan berkas hanya mempercayai `media_id`; `url` yang dikirim browser tidak pernah dipakai sebagai `href`.
- Tombol **tanpa teks** atau **tanpa tautan sah** tidak dirender di halaman publik (tidak ada tombol mati).
- Ikon hanya yang ada di `config('cms.lucide')` (daftar yang sama dengan pemilih ikon).

## Bentuk data
```json
{
  "type": "button-builder",
  "data": {
    "align": "left | center | right",
    "stack_mobile": true,
    "buttons": [{
      "id": "itm_ab12cd34",
      "label": { "id": "Donasi Sekarang", "en": "Donate Now" },
      "link": { "kind": "page|article|file|url|tel|mailto|anchor", "ref": "5", "ref_label": "Tentang Kami", "media_id": null, "url": "", "new_tab": false },
      "variant": "solid | outline | ghost",
      "color": "foresty | coral | aurum | charcoal",
      "size": "sm | md | lg",
      "icon": "heart", "icon_position": "left | right"
    }]
  }
}
```

## Berkas yang terlibat
| Berkas | Peran |
|---|---|
| `app/Editor/BlockRegistry.php` | definisi blok: kontrol di inspektur + nilai bawaan |
| `resources/views/components/editor/repeater.blade.php`, `link.blade.php` | kontrol daftar berulang dan pemilih tautan (dipakai blok lain nanti) |
| `app/Content/Links/LinkResolver.php` | tautan → URL aman |
| `app/Content/Blocks/BlockSanitizer.php`, `ButtonStyle.php` | pembersih data dan peta kelas |
| `app/Livewire/Traits/SearchesLinkTargets.php` | aksi pencarian halaman/artikel untuk pemilih tautan |
| `resources/views/components/blocks/render/button-builder.blade.php` | tampilan **publik** |
| `tests/button-test.php` | 75 pemeriksaan logika dan keamanan |

## Yang perlu diperhatikan
- **Tailwind**: semua kelas ada utuh di `ButtonStyle.php`. Tailwind v4 memindai `app/` otomatis; bila Anda memakai `@source` manual, tambahkan `app/Content/Blocks`.
- **Halaman publik**: komponen render dipanggil sebagai `blocks.render.button-builder` dengan props `block`, `data`, `lang`, `allContent`. Bila `page-preview` Anda memanggil komponen render dengan cara lain, sesuaikan di satu tempat itu.
- **Belum ada**: pratinjau tombol di kanvas (tahap kanvas), dan pelacakan klik.
