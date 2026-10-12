# Kelola Kategori dan Tag dua bahasa (rilis 46)

Masalah di panel lama: satu kolom "Nama Kategori Baru" / "Nama Tag Baru" yang dikirim ke `createCategory($nama)` / `createTag($nama)`. Satu nama itu masuk ke semua bahasa (atau, bila kolomnya per bahasa, hanya satu bahasa yang terisi), dan daftar menampilkan `$i->name` yang sebenarnya larik per bahasa. Sekarang tiap kategori/tag diisi **per bahasa**.

## Yang berubah
| Berkas | Isi |
|---|---|
| `app/Content/Taxonomy.php` | semua aturan (diuji): validasi, slug, kembar per bahasa, simpan, hapus, daftar + jumlah pemakai |
| `resources/views/components/content/⚡taxonomy.blade.php` | komponen `content.taxonomy` (form per bahasa + daftar + ubah/hapus) |
| `contoh-kode/panel-kategori-tag.blade.php` | pengganti blok panel yang Anda komentari di daftar halaman/artikel |
| `tests/taxonomy-test.php` | 64 pemeriksaan (SQLite + Blade sungguhan) |

## Aturan
- **Bahasa bawaan wajib** diisi (EN). Bahasa lain boleh kosong: nama tampil memakai nama bahasa bawaan, dan baris daftar bertanda *belum diterjemahkan*.
- Kategori yang kosong di suatu bahasa **tidak punya slug dan halaman di bahasa itu** (aturan lama `docs/KATEGORI.md`, tidak berubah).
- **Slug otomatis** dari nama bahasa itu ("Maternal & Child" -> `maternal-dan-child`), boleh diubah (diketik bebas, dinormalkan). Slug dan nama harus **unik per bahasa** (tanpa pandang huruf besar); nama yang sama di EN dan ID pada item yang sama boleh ("Malaria").
- Tag: awalan `#` dibuang ("#stunting" -> "stunting"). Maksimal 100 karakter (kategori) / 60 (tag).
- Bahasa mengikuti `config('app.supported_locales')`: menambah bahasa ketiga menambah satu baris isian, tanpa mengubah kode.
- **Hapus**: kategori yang dipakai artikel ditolak (pesan memuat jumlahnya; tombolnya nonaktif); tag dilepas dari artikelnya lebih dulu (konfirmasi menyebut jumlahnya). Konfirmasi ada di dalam komponen, bukan modal induk.
- Hanya **admin dan editor** (`hasRole(['admin','editor'])`, sama dengan kode Anda). Dicatat di `activity_log` (nama log `taxonomy_updates`) bila Spatie terpasang.

## Bentuk kolom
| Kolom | Perilaku |
|---|---|
| `name` ber-cast `array`/`json` (bentuk yang dipakai kit) | peta bahasa; kunci bahasa lain yang sudah ada di data (mis. `fr`) **tidak dihapus** |
| `name` teks polos | hanya bahasa bawaan tersimpan; kolom bahasa lain dinonaktifkan dengan penjelasan (tambahkan `'name' => 'array'` di `$casts`) |
| `slug` peta bahasa / string JSON / teks polos / **tidak ada kolomnya** | ditulis mengikuti bentuknya; tanpa kolom `slug` bagian slug tidak tampil (alamat dibentuk dari nama oleh `CategoryLookup`) |
Data lama dibaca apa adanya: nama teks polos ditempatkan di bahasa bawaan; slug teks polos dianggap berlaku untuk semua bahasa (jadi ikut dihitung saat memeriksa slug kembar).

## Pasang (CMS saja; tidak ada migrasi, tidak ada berkas landing)
1. Salin `app/Content/Taxonomy.php` dan `resources/views/components/content/⚡taxonomy.blade.php`.
2. Di komponen induk (daftar artikel Anda): hapus `createCategory`, `createTag`, `deleteCategory`, `deleteTag`, `categoryList`, `tagList`, dan `newItemName`; tempel blok dari `contoh-kode/panel-kategori-tag.blade.php` menggantikan panel yang dikomentari (`activeSubPanel = 'categories'` / `'tags'` tetap dipakai tombol Anda).
3. `php artisan view:clear`. Alpine, Tailwind, `flux:icon`, `lucide-save`, dan trait `WithNotifications` sudah ada di proyek Anda.

## Yang BELUM berubah (disengaja)
- **Tag baru dari editor artikel** (`x-editor.tags`, `TagResolver`): satu nama yang diketik masih disalin ke semua bahasa (penulis tidak perlu mengurus terjemahan saat menulis). Admin/editor menerjemahkannya di panel ini.
- Halaman publik belum menampilkan tag; slug tag per bahasa disiapkan untuk itu.

## Batas yang jujur
- Komponen Livewire tidak dijalankan di Livewire sungguhan di sini. Diuji: seluruh logika dengan database SQLite, templat dirender dengan Blade sungguhan (tanpa Livewire: `$this->…` diganti variabel), dan kontrak sumber. Alpine (isi slug otomatis, konfirmasi hapus) diuji lewat DEBUG 6zl.
- Pengisian slug otomatis di browser memakai aturan yang sama dengan `Slug::make` untuk huruf Latin biasa; huruf beraksen langka bisa berbeda, dan server selalu menormalkan ulang saat menyimpan.
- Mengganti slug kategori mengubah alamat `/articles/category/{slug}`; tautan lama (mis. dari menu) menuju 404/301 mengikuti aturan `KATEGORI.md`.
