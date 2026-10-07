# Perubahan per rilis

## Rilis 2: daftar berulang (repeater) dan blok Tombol

**Cara memasang:** salin isi `app/`, `database/`, `resources/` (Replace), lalu `php artisan view:clear` dan `npm run build` (editor.js berubah). Tidak ada berkas yang perlu dihapus dan tidak ada migrasi baru.

### Baru (10 berkas)
| Berkas | Isi |
|---|---|
| `app/Content/Blocks/BlockSanitizer.php` | pembersih data blok (Tombol): kelas, tautan, panjang, jumlah |
| `app/Content/Blocks/ButtonStyle.php` | peta gaya tombol → kelas Tailwind (36 kombinasi, daftar tetap) |
| `app/Content/JsonSql.php` | SQL kolom JSON per bahasa yang tahan baris lama (dipakai pencarian dan aturan unik) |
| `app/Content/Links/LinkResolver.php` | tautan → URL aman (http/https, mailto, tel, /jalur, #anchor; `javascript:` dkk ditolak) |
| `app/Editor/Defaults.php` | penanda `@id` / `@locales` pada nilai bawaan blok |
| `app/Editor/Rel.php` | path dinamis berbasis indeks untuk kontrol di dalam daftar berulang |
| `app/Livewire/Traits/SearchesLinkTargets.php` | aksi `searchLinkTargets()` untuk pemilih tautan |
| `resources/views/components/blocks/render/button-builder.blade.php` | tampilan **publik** blok Tombol |
| `resources/views/components/editor/link.blade.php` | kontrol pemilih tautan |
| `resources/views/components/editor/repeater.blade.php` | kontrol daftar berulang (tambah/hapus/duplikat/urut) |

### Berubah (19 berkas)
| Berkas | Perubahan |
|---|---|
| `app/Content/ContentWriter.php` | membersihkan data blok sebelum disimpan (`BlockSanitizer`); menerima bahasa aktif |
| `app/Content/Rules/UniqueLocaleValue.php` | memakai `JsonSql` (perilaku sama) |
| `app/Editor/BlockPalette.php` | + Tombol (menu atas dan di dalam kolom) |
| `app/Editor/BlockRegistry.php` | + definisi blok Tombol |
| `app/Editor/BlockType.php` | + `defaults` (nilai bawaan dari registri) |
| `app/Editor/Field.php` | + `repeater`, `link`; `icon` bisa dikosongkan |
| `app/Livewire/Traits/ManagesBlockStructure.php` | blok baru memakai nilai bawaan **registri** bila ada (tidak perlu mengubah trait lama) |
| `resources/js/editor.js` | + `repeater`, `linkField`; **perbaikan balapan** penulisan tertunda (lihat di bawah); `wireField` bisa diperluas |
| `resources/views/components/content/⚡builder.blade.php` | `use SearchesLinkTargets`; mengirim bahasa aktif ke penulis |
| `resources/views/components/editor/field.blade.php` | + tipe `repeater` dan `link`; path dinamis di dalam item |
| `resources/views/components/editor/i18n.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/icon-picker.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/media.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/rich.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/segmented.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/select.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/swatches.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/text.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |
| `resources/views/components/editor/toggle.blade.php` | menerima path dinamis (`rel-expr`) agar bisa dipakai di dalam daftar berulang |

### Bila Anda sudah mengubah `⚡builder.blade.php` sendiri
Jangan ditimpa. Cukup tiga tambahan kecil:
1. di bagian `use` atas: `use App\Livewire\Traits\SearchesLinkTargets;`
2. di dalam kelas, di samping `use ManagesBlockStructure;`: `use SearchesLinkTargets;`
3. di `save()`, di dalam larik `ContentWriter::fill(...)`, tambahkan baris `'locales' => $this->activeLocales,`

### Yang perlu diperiksa di aplikasi Anda
`docs/DEBUG.md` bagian **6b**. Hal yang hanya bisa dipastikan di Livewire dan layout Anda: pencarian halaman/artikel (`$wire.searchLinkTargets`), File Manager untuk tombol berkas, dan cara `page-preview` memanggil komponen render publik.

### Catatan teknis
- **Perbaikan balapan di `editor.js` (`typeAt`).** Penulisan "live" yang tertunda 0,8 detik menimpa **nilai lama** ke **path lama**. Di inspektur biasa path-nya tetap, jadi aman. Di dalam daftar berulang path berbasis indeks: mengetik lalu langsung menaikkan item, atau menghapus item di atasnya, menimpa teks item lain atau membuat item hantu. Kini yang dikirim adalah nilai yang **sekarang** ada di path itu, dan dibatalkan bila path hilang. Teruji dengan dua skenario nyata.
- Semua kontrol (`segmented`, `i18n`, `toggle`, dst.) kini menerima `rel-expr`. Perilaku di inspektur tidak berubah.
- Pemeriksaan baru yang berguna untuk Anda sendiri: setelah mengubah Blade, jalankan di PowerShell
  `php artisan view:cache` lalu `Get-ChildItem storage\framework\views\*.php | ForEach-Object { $r = php -l $_.FullName 2>&1; if ($LASTEXITCODE -ne 0) { $r } }`.
  `php -l` langsung pada berkas `.blade.php` **tidak** menangkap galat yang baru muncul setelah Blade dikompilasi (mis. ternary bersarang tanpa kurung).

## Rilis 1 — 2026-10-07 (penggabungan dua kit + perbaikan dari uji Anda)

**Penggabungan:** `editor-kit`, `content-builder`, `media-manager-backend`, `blade-components`, dan `patch-has-content-blocks` kini satu pohon folder Laravel dalam satu zip.
Berkas lepas dan zip lama sudah dihapus dari folder output.

### Berkas yang BERUBAH dibanding lampiran terakhir (copy yang ini bila hanya ingin memperbarui)
| Berkas | Perubahan |
|---|---|
| `app/Content/Names.php` | **baru**: nama kategori/tag yang berupa JSON per bahasa → teks (bahasa aktif → id → en → apa pun) |
| `app/Content/TagResolver.php` | **ditulis ulang**: tag ber-JSON per bahasa dikenali; tag baru dibuat **per bahasa** (`{"id":"X","en":"X"}`, slug sama), bukan string JSON `"X"`; mencocokkan nama bahasa mana pun atau slug |
| `app/Content/Rules/UniqueLocaleValue.php` | **menggantikan** `UniqueSlug.php` (**hapus yang lama**): kini juga untuk judul artikel (`posts.title` punya indeks unik) |
| `app/Content/ContentRules.php` | judul artikel unik per bahasa; memakai `UniqueLocaleValue` |
| `app/Content/ContentWriter.php` | `syncRelations()` menerima bahasa aktif |
| `app/Editor/Options.php` | nama berupa peta bahasa tidak lagi dianggap definisi opsi. **Perbaikan dropdown kategori yang hanya menampilkan id** |
| `resources/js/editor.js` | `fitViewport`: tinggi editor = sisa tinggi layar (tidak ada bilah gulir di halaman) |
| `resources/views/components/content/⚡builder.blade.php` | `categoryOptions`/`tagOptions` memakai `Names` (menggantikan perbaikan Anda; hasilnya sama, ditambah pengurutan); tinggi mengikuti layar; pelanggaran indeks unik jadi pesan di tab Halaman, bukan galat 500 |

### Berkas yang HARUS dihapus
`app/Content/Rules/UniqueSlug.php` (digantikan `UniqueLocaleValue.php`), `resources/views/components/editor/outline-sementara.blade.php` (bila masih ada).

### Catatan
- **Kategori dan tag memakai JSON per bahasa** (cast `array`), bukan string. Rilis sebelumnya salah menduga.
- Tag yang Anda buat dari builder sebelum rilis ini tersimpan sebagai string JSON (`"Malaria"`). Tetap terbaca dan dikenali sebagai tag lama; boleh dibiarkan.
- `tests/content-test.php` bertambah pemeriksaan `Names`.
