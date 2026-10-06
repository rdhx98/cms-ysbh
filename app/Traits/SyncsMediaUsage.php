<?php

namespace App\Traits;

use App\Models\Media;
use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Tempel trait ini ke model konten mana pun yang menyimpan blok dalam
 * kolom JSON (Page, Post/Artikel, dst) supaya YSBH tahu persis "gambar
 * ini dipakai di halaman/artikel mana" — dasar panel "Digunakan Di"
 * di file manager, dan penjaga sebelum media dihapus.
 *
 * Cara pakai — cukup dua baris di model Anda:
 *
 *   class Page extends Model
 *   {
 *       use \App\Traits\SyncsMediaUsage;
 *       // ...
 *   }
 *
 * Lalu panggil $page->syncMediaUsage() di titik yang sama persis
 * setelah Anda menyimpan kolom content (akhir method save() Livewire
 * Anda, atau lewat model event 'created' dan 'updated' — lihat bootSyncsMediaUsage()
 * di bawah, sudah otomatis terpasang, tidak perlu dipanggil manual
 * kecuali Anda menonaktifkan auto-hook-nya).
 */
trait SyncsMediaUsage
{
    /**
     * Nama kolom yang menyimpan struktur blok JSON. Override method ini
     * di model Anda kalau nama kolomnya bukan 'content', mis:
     *
     *   protected function mediaContentColumn(): string { return 'body'; }
     */
    protected function mediaContentColumn(): string
    {
        return 'content';
    }

    /**
     * Auto-hook: setiap kali model ini disimpan (create maupun update),
     * sinkronisasi usage jalan otomatis. Ini yang membuat fitur ini
     * tidak bisa "lupa dipanggil" — bukan langkah manual terpisah yang
     * gampang terlewat saat menambah jenis konten baru di masa depan.
     */
    protected static function bootSyncsMediaUsage(): void
    {
        // PERBAIKAN: sebelumnya memakai event 'saved' + wasChanged(). Setelah INSERT, wasChanged() bernilai false,
        // jadi gambar pada halaman/artikel yang baru DIBUAT tidak pernah tercatat. 'created' menangani pembuatan,
        // 'updated' hanya jalan bila kolom konten benar-benar berubah (hemat kueri di shared hosting).
        static::created(fn ($model) => $model->syncMediaUsage());

        static::updated(function ($model) {
            if ($model->wasChanged($model->mediaContentColumn())) {
                $model->syncMediaUsage();
            }
        });
    }

    public function mediaUsages(): MorphMany
    {
        return $this->morphMany(MediaUsage::class, 'usable');
    }

    /**
     * Jalankan sinkronisasi: baca ulang kolom konten, susuri seluruh
     * pohon blok (berapa pun dalamnya nesting-nya — row/column/element,
     * card-builder, atau bentuk blok apa pun yang ditambahkan nanti),
     * kumpulkan semua media_id yang benar-benar dirujuk saat ini, lalu
     * samakan tabel media_usages supaya persis mencerminkan itu.
     */
    public function syncMediaUsage(): void
    {
        $column = $this->mediaContentColumn();

        // Baca atribut MENTAH, bukan $this->{$column}: pada model dengan trait terjemahan (Page menandai `content`
        // sebagai Translatable), accessor mengembalikan terjemahan locale aktif — kosong untuk isi {blocks, order, ...} —
        // sehingga sinkronisasi akan menganggap tidak ada gambar dan menghapus semua catatan pemakaian.
        // String JSON dibuka berulang (maks 3x) karena `content` pernah tersimpan sebagai JSON di dalam JSON.
        $content = $this->getAttributes()[$column] ?? null;
        for ($i = 0; $i < 3 && is_string($content); $i++) {
            $content = json_decode($content, true);
        }
        $content = is_array($content) ? $content : [];

        $currentIds = $this->extractMediaIds($content ?? []);
        $currentIds = array_values(array_unique(array_filter($currentIds)));

        $existingIds = $this->mediaUsages()->pluck('media_id')->all();

        $toAdd = array_diff($currentIds, $existingIds);
        $toRemove = array_diff($existingIds, $currentIds);

        foreach ($toAdd as $mediaId) {
            // firstOrCreate, bukan create polos — jaga-jaga kalau
            // syncMediaUsage() sampai terpanggil dua kali beruntun
            // (mis. saved event + panggilan manual), constraint unik
            // di migrasi tetap jadi pengaman lapis kedua.
            $this->mediaUsages()->firstOrCreate(['media_id' => $mediaId]);
        }

        if (! empty($toRemove)) {
            $this->mediaUsages()->whereIn('media_id', $toRemove)->delete();
        }
    }

    /**
     * Menyusuri struktur bersarang apa pun (array asosiatif atau
     * berindeks, campur keduanya, sedalam apa pun) dan mengumpulkan
     * setiap nilai yang ditemukan di bawah kunci 'media_id' (satu
     * gambar) atau 'media_ids' (array — untuk blok Galeri Foto atau
     * blok masa depan yang merujuk banyak media sekaligus).
     *
     * Sengaja tidak di-hardcode ke bentuk blok tertentu (row/column,
     * card-builder, dst) — supaya kalau nanti Anda menambah jenis blok
     * baru yang menyimpan gambar, trait ini tetap menemukannya tanpa
     * perlu diperbarui, selama field-nya tetap dinamai media_id(s).
     */
    protected function extractMediaIds(mixed $node): array
    {
        $ids = [];

        if (! is_array($node)) {
            return $ids;
        }

        foreach ($node as $key => $value) {
            if ($key === 'media_id' && (is_int($value) || is_string($value)) && $value !== '') {
                $ids[] = (int) $value;
                continue;
            }

            if ($key === 'media_ids' && is_array($value)) {
                foreach ($value as $v) {
                    if (is_int($v) || is_string($v)) {
                        $ids[] = (int) $v;
                    }
                }
                continue;
            }

            if (is_array($value)) {
                $ids = array_merge($ids, $this->extractMediaIds($value));
            }
        }

        return $ids;
    }

    /**
     * Dipanggil dari Media::isInUse() lewat relasi terbalik — daftar
     * judul konten yang memakai media tertentu, dipakai mengisi panel
     * "Digunakan Di". Override method ini kalau model Anda tidak punya
     * kolom 'title' (mis. pakai 'page_title' seperti yang sempat
     * terlihat di data Anda sebelumnya).
     */
    public function mediaUsageLabel(): string
    {
        return $this->title ?? $this->page_title ?? "#{$this->id}";
    }
}