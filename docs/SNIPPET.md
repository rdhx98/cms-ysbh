# Model Snippet

Potongan konten yang dipakai berulang (CTA donasi, Hubungi Kami). Diuji dengan **Eloquent sungguhan + SQLite** (Laravel 13.30, Carbon 3): 65 pengujian lulus, ditambah 43 pengujian `ContentType`/`ContentDocument`. Paket Spatie di-stub di lab (antarmuka yang dipakai model saja), jadi perilaku internal Spatie tidak ikut teruji.

## 1. Mengikuti desain yang ada

| | `pages` | `snippets` | Catatan |
|---|---|---|---|
| gaya model | `#[Table] #[Fillable] #[Translatable]`, `HasTranslations`, `LogsActivity` | sama | `#[Fillable]` dengan beberapa string butuh Laravel 13.x yang cukup baru (di 13.2.0 hanya menerima satu array); proyek Anda jelas sudah memakainya |
| `title` | JSON per bahasa | JSON per bahasa | label di admin |
| `content` | `{blocks, order, settings}` | sama | dibaca/ditulis hanya lewat `ContentDocument`; **tidak** ditandai Translatable |
| `status` | `offline`/`online` | sama | dari `ContentType::Snippet->statuses()` |
| `slug`, `meta_*` | ada | **tidak ada** | snippet bukan halaman, tak punya URL |
| `key` | – | unik, mis. `donasi` | pengenal stabil (kode, override halaman); tidak diterjemahkan |
| `is_closing`, `sort_order` | – | ada | tampil otomatis di akhir halaman |
| `description` | – | ada | catatan admin |
| SoftDeletes | tidak | ya | dengan penjaga: tidak bisa dihapus selagi dipakai |

**Tertaut vs salinan** diputuskan di titik penyisipan, bukan di kolom: blok `snippet` (`data.snippet_id`) = tertaut (selalu versi terbaru); "Salin ke halaman" = menyalin blok-bloknya (seperti preset). Karena itu tidak ada kolom `synced`.

## 2. Aturan yang ditegakkan model (diuji)

- `key` dinormalkan saat simpan (`"Hubungi Kami!"` → `hubungi-kami`); kosong atau tidak sah ditolak; unik di database.
- Status di luar `offline`/`online` ditolak.
- **Snippet tidak boleh memuat snippet lain** (mencegah rekursi).
- **Tidak bisa dihapus selagi dipakai**: `delete()` dan `forceDelete()` mengembalikan `false`. "Dipakai" berarti ada halaman/artikel yang menyisipkannya, atau ia penutup yang online. Penutup yang offline boleh dihapus.
- Gambar di dalam snippet ikut tercatat di "Digunakan Di" milik file manager (lewat `SyncsMediaUsage`).

## 3. Pemasangan

1. Salin `app/`, `database/`, dan jalankan `php artisan migrate` (tabel `snippets`, `snippet_usages`).
2. Daftarkan morph map: `snippets/morph-map.php`. Pakai `morphMap()`, bukan `enforceMorphMap()` (lihat komentar di file).
3. Tempel trait ke **Page dan Post**: `use \App\Traits\SyncsSnippetUsage;`. Trait membaca atribut mentah, jadi aman terhadap accessor terjemahan; hook `restored` hanya dipasang bila modelnya memakai `SoftDeletes` (Page dan Post tidak).
4. **Ganti `SyncsMediaUsage.php`** dengan versi di zip (`app/Traits/SyncsMediaUsage.php`) (lihat bagian 4).
5. Rangka `⚡builder.blade.php` sudah memahami snippet: tanpa slug/meta, dengan `key`, `description`, `is_closing`, `sort_order`.

## 4. Dua bug di kit file manager yang saya temukan saat menguji ini (perlu Anda ketahui)

Keduanya ada di `SyncsMediaUsage.php` yang saya kirim sebelumnya, dan **sudah diperbaiki di berkas itu**:

1. **Gambar pada halaman/artikel yang baru dibuat tidak tercatat.** Trait memakai `wasChanged()` di event `saved`, tetapi setelah INSERT `wasChanged()` bernilai false (saya dulu menulis sebaliknya tanpa mengujinya). Kini memakai event `created` dan `updated`.
2. **Pada model yang `content`-nya Translatable (Page), sinkronisasi bisa menghapus semua catatan.** Trait membaca `$this->content` lewat accessor, yang mengembalikan terjemahan locale aktif. Kini membaca atribut mentah. Diuji dengan model yang accessor-nya sengaja mengembalikan `null`.

Data yang sudah ada belum tentu benar. Jalankan sekali (`php artisan tinker`) setelah mengganti trait:

```php
\App\Models\Page::query()->each(fn ($m) => $m->syncMediaUsage());
\App\Models\Post::query()->each(fn ($m) => $m->syncMediaUsage());
```

## 5. Memakai di renderer

```php
$doc        = ContentDocument::fromRaw($page->getAttributes()['content']);
$closingIds = ClosingPolicy::resolve(
    $doc->closingOverride(),                          // settings.closing: null | [] | ['donasi', ...]
    Snippet::closing()->pluck('id', 'key')->all(),    // penutup bawaan, berurutan
    Snippet::onlineByKey()->map->id->all(),           // semua yang online (untuk override)
    $doc->snippetIds(),                               // yang sudah disisipkan manual tidak diulang
);

// Satu kueri untuk semua snippet yang dibutuhkan halaman (hindari N+1)
$snippets = Snippet::online()->whereIn('id', [...$doc->snippetIds(), ...$closingIds])->get()->keyBy('id');
```

Blok `snippet` di tengah halaman: `$snippets[$block['data']['snippet_id']]?->document()`. Penutup: render `$snippets[$id]->document()` berurutan setelah blok terakhir.

## 6. Belum dikerjakan

- **Renderer.** Pengelompokan seksi (pemisah seksi + latar) masih inline di `page-preview`. Snippet bisa berisi pemisah seksinya sendiri, jadi logikanya perlu diekstrak ke satu komponen yang dipakai halaman dan snippet.
- **Blok `snippet` di editor.** Perlu entri registri dan tipe kontrol pemilih record (`Field::record`). Registri sebaiknya melarang blok ini di dalam snippet (model sudah menolaknya, tapi UI sebaiknya tidak menawarkannya).
- **Cache halaman publik.** Mengubah snippet harus membatalkan cache halaman yang memakainya: `$snippet->usedIn()` memberi daftarnya. Penutup bawaan mengenai semua halaman.
- **Policy/otorisasi** dan factory/seeder.
