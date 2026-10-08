<?php

namespace App\Traits;

use App\Content\ContentDocument;
use App\Models\Snippet;
use App\Models\SnippetUsage;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tempel ke model berisi blok (Page, Post). Mencatat snippet apa yang dipakainya, sehingga Snippet tahu
 * "Digunakan Di" dan menolak dihapus selagi masih dipakai. Padanan SyncsMediaUsage untuk snippet.
 *
 * Yang dihitung sebagai pemakaian:
 *   1. blok `snippet` (data.snippet_id) yang tersambung ke halaman
 *   2. snippet yang disebut settings.closing (halaman memilih penutupnya sendiri)
 * Snippet penutup BAWAAN (is_closing) tidak dicatat per halaman: ia dihitung lewat Snippet::isInUse().
 */
trait SyncsSnippetUsage
{
    protected static function bootSyncsSnippetUsage(): void
    {
        // Bukan 'saved' + wasChanged(): setelah INSERT, wasChanged() bernilai false (lihat SyncsMediaUsage).
        static::created(fn ($model) => $model->syncSnippetUsage());

        static::updated(function ($model) {
            if ($model->wasChanged('content')) {
                $model->syncSnippetUsage();
            }
        });

        static::deleted(fn ($model) => $model->snippetUsages()->delete());

        // `restored` hanya ada pada model ber-SoftDeletes. Page dan Post saat ini tidak memakainya, dan memanggilnya
        // tanpa trait itu membuat model gagal ter-boot.
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn ($model) => $model->syncSnippetUsage());
        }
    }

    public function snippetUsages(): MorphMany
    {
        return $this->morphMany(SnippetUsage::class, 'usable');
    }

    public function syncSnippetUsage(): void
    {
        // Atribut mentah: aman terhadap accessor terjemahan pada `content`
        $doc = ContentDocument::fromRaw($this->getAttributes()['content'] ?? null);

        $wanted = $doc->snippetIds();
        if ($keys = $doc->closingOverride()) {
            $wanted = array_merge($wanted, Snippet::withTrashed()->whereIn('key', $keys)->pluck('id')->all());
        }

        // Hanya ID yang benar-benar ada (blok bisa menunjuk snippet yang sudah dihapus permanen)
        $wanted = $wanted ? Snippet::withTrashed()->whereIn('id', array_unique($wanted))->pluck('id')->all() : [];
        $current = $this->snippetUsages()->pluck('snippet_id')->all();

        foreach (array_diff($wanted, $current) as $id) {
            $this->snippetUsages()->create(['snippet_id' => $id]);
        }
        if ($stale = array_diff($current, $wanted)) {
            $this->snippetUsages()->whereIn('snippet_id', $stale)->delete();
        }
    }
}
