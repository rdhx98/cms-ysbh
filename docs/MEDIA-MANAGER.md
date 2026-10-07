# Media Manager Backend — Pemasangan

7 file ini melengkapi UI file manager yang sudah dibuat sebelumnya
(`file-manager.html`) dengan lapisan data & logika di baliknya.
Dirancang khusus untuk keterbatasan Hostinger Shared/Bisnis — folder
virtual (bukan direktori fisik), thumbnail dibuat sinkron saat upload
(tidak butuh queue worker), dan tracking "Digunakan Di" otomatis.

## 1. Salin file ke proyek Anda

```
migrations/*.php   → database/migrations/
models/*.php       → app/Models/
traits/*.php       → app/Traits/
```

## 2. Jalankan migrasi

```bash
php artisan migrate
```

Tiga tabel baru: `media_folders`, `media`, `media_usages`. Tidak
menyentuh tabel `pages`/`posts` yang sudah ada — media_usages berdiri
sendiri sebagai pivot polymorphic.

## 3. Tempel trait ke model konten Anda

Di **setiap** model yang menyimpan blok (kemungkinan `Page`, dan
`Post`/`Artikel` kalau terpisah):

```php
use App\Traits\SyncsMediaUsage;

class Page extends Model
{
    use SyncsMediaUsage;

    protected $casts = ['content' => 'array']; // kalau belum ada
}
```

Kalau kolom konten Anda bukan bernama `content`, override satu method:

```php
protected function mediaContentColumn(): string
{
    return 'body'; // atau nama kolom Anda
}
```

**Tidak ada langkah lain.** Trait ini otomatis menyambung ke event
`saved` model — begitu Halaman/Artikel disimpan, sinkronisasi jalan
sendiri. Anda tidak perlu memanggil `syncMediaUsage()` manual di
mana pun.

## 4. Ubah blok bertipe gambar supaya menyimpan `media_id`

Ini satu-satunya perubahan pada kode editor blok Anda yang sekarang
ada. Field gambar yang sebelumnya menyimpan URL mentah:

```php
// SEBELUM
'data' => ['src' => 'https://.../foto.jpg']

// SESUDAH
'data' => ['media_id' => 42]
```

Lalu saat render ke publik, ambil URL-nya lewat model:

```blade
@php $media = \App\Models\Media::find($block['data']['media_id']); @endphp
<img src="{{ $media?->url() }}" alt="...">
```

Kalau ada blok yang sudah punya banyak gambar sekaligus (mis. blok
Galeri Foto dari pustaka blok kemarin), pakai `media_ids` (jamak,
array) — trait ini sudah otomatis mengenali keduanya, sedalam apa pun
nesting-nya di struktur blok Anda.

## 5. Migrasi data lama (kalau sudah ada gambar tersimpan sebagai URL)

Trait tidak bisa melacak usage untuk gambar yang masih tersimpan
sebagai URL mentah, karena tidak tahu itu file media mana. Kalau
sudah ada Halaman/Artikel terlanjur pakai URL langsung, perlu skrip
migrasi satu kali: untuk tiap URL yang ditemukan, cek apakah filenya
sudah ada baris `media`-nya (kalau belum, buat baru dari file yang
sudah ada di disk), lalu ganti field blok dari `src` ke `media_id`.
Ini best dikerjakan sebagai Artisan command terpisah — beri tahu saya
kalau Anda mau saya buatkan sekalian, saya perlu tahu dulu kira-kira
berapa banyak Halaman/Artikel yang sudah terlanjur begini.

## 6. Sebelum menghapus media, cek `isInUse()`

Di komponen Livewire file manager Anda:

```php
public function deleteMedia(int $mediaId): void
{
    $media = Media::findOrFail($mediaId);

    if ($media->isInUse()) {
        // Tampilkan panel "Digunakan Di" — JANGAN hapus.
        // $media->usedIn() mengembalikan koleksi model Page/Post
        // yang masih memakainya.
        return;
    }

    $media->deleteWithFile();
}
```

## Catatan Hostinger yang perlu diperiksa sebelum upload pertama

- **PHP upload limit**: hPanel → Advanced → PHP Configuration → naikkan
  `upload_max_filesize` dan `post_max_size` (default sering cuma 2-8MB).
- **Ekstensi GD**: cek di layar yang sama, pastikan `gd` aktif — dipakai
  Intervention Image untuk baca `width`/`height` saat upload.
- **`storage:link`**: jalankan sekali lewat Terminal hPanel kalau
  tersedia (Advanced → SSH Access), atau lewat fitur "Run PHP Script"
  kalau paket Anda tidak punya akses shell sama sekali.

## Perbaikan (Oktober 2026) — `SyncsMediaUsage`
Dua bug ditemukan dan diperbaiki saat menguji kit Snippet:
1. Gambar pada halaman/artikel yang baru **dibuat** tidak tercatat (`wasChanged()` bernilai false setelah INSERT). Kini memakai event `created` + `updated`.
2. Pada model dengan accessor terjemahan (Page), trait membaca `content` lewat accessor sehingga sinkronisasi bisa menghapus semua catatan. Kini membaca atribut mentah.

Sinkron ulang data yang ada sekali: `Page::query()->each(fn ($m) => $m->syncMediaUsage());` (idem `Post`).
