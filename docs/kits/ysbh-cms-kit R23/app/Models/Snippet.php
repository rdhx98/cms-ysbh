<?php

namespace App\Models;

use App\Content\ContentDocument;
use App\Content\ContentType;
use App\Traits\SyncsMediaUsage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * Potongan konten yang dipakai berulang (CTA donasi, Hubungi Kami, ...).
 *
 *  - Disisipkan di halaman/artikel sebagai blok `snippet` (data.snippet_id) -> selalu menampilkan versi terbaru.
 *  - Bisa ditandai `is_closing`: tampil otomatis di akhir halaman; halaman menimpanya lewat settings.closing
 *    (lihat App\Content\ClosingPolicy).
 *  - Tidak boleh memuat snippet lain (mencegah rekursi) dan tidak bisa dihapus selama masih dipakai.
 */
#[Table('snippets')]
#[Translatable('title')]
#[Fillable('key', 'title', 'description', 'content', 'status', 'is_closing', 'sort_order', 'created_by', 'updated_by')]
class Snippet extends Model
{
    use HasFactory, HasTranslations, LogsActivity, SoftDeletes;
    // Gambar di dalam snippet ikut tercatat di "Digunakan Di" milik file manager.
    use SyncsMediaUsage;

    protected $casts = [
        'title'      => 'array',
        'content'    => 'array',
        'is_closing' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'status'     => 'offline',
        'is_closing' => false,
        'sort_order' => 0,
        'content'    => '{"blocks":[],"order":[],"settings":[]}',
    ];

    // ------------------------------------------------------------------ penjaga di tingkat model

    protected static function booted(): void
    {
        static::saving(function (Snippet $snippet) {
            $snippet->key = static::normalizeKey((string) $snippet->key);

            if ($snippet->key === '' || !preg_match(ContentDocument::KEY_PATTERN, $snippet->key)) {
                throw new \InvalidArgumentException('Snippet butuh key yang sah (huruf kecil, angka, tanda hubung).');
            }
            if (!in_array($snippet->status, ContentType::Snippet->statuses(), true)) {
                throw new \InvalidArgumentException("Status snippet tidak sah: {$snippet->status}");
            }
            if ($snippet->document()->referencesSnippets()) {
                throw new \InvalidArgumentException('Snippet tidak boleh memuat snippet lain.');
            }
        });

        // Mengembalikan false membatalkan penghapusan. Berlaku untuk delete() maupun forceDelete().
        static::deleting(function (Snippet $snippet) {
            if ($snippet->isInUse()) {
                return false;
            }
        });
    }

    /** "Hubungi Kami!" -> "hubungi-kami". */
    public static function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9]+/', '-', $key) ?? '';

        return trim($key, '-');
    }

    // ------------------------------------------------------------------ isi

    /** Pembaca tunggal `content`; memakai atribut mentah supaya tidak terpengaruh trait terjemahan. */
    public function document(): ContentDocument
    {
        return ContentDocument::fromRaw($this->getAttributes()['content'] ?? null);
    }

    /** Nama untuk daftar admin: bahasa yang diminta, lalu bahasa lain yang terisi, lalu key. */
    public function label(?string $locale = null): string
    {
        $titles = $this->getTranslations('title');
        foreach ([$locale ?? app()->getLocale(), ...array_keys($titles)] as $l) {
            if (filled($titles[$l] ?? null)) {
                return $titles[$l];
            }
        }

        return (string) $this->key;
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    // ------------------------------------------------------------------ kueri

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', 'online');
    }

    /** Snippet yang tampil otomatis di akhir halaman, berurutan. */
    public function scopeClosing(Builder $query): Builder
    {
        return $query->online()->where('is_closing', true)->orderBy('sort_order')->orderBy('id');
    }

    /** @return Collection<string, static> snippet online dikunci oleh `key` (untuk ClosingPolicy). */
    public static function onlineByKey(): Collection
    {
        return static::query()->online()->get()->keyBy('key');
    }

    // ------------------------------------------------------------------ pemakaian

    public function usages(): HasMany
    {
        return $this->hasMany(SnippetUsage::class);
    }

    /** Halaman/artikel yang memakai snippet ini — isi panel "Digunakan Di". */
    public function usedIn(): Collection
    {
        return $this->usages()->with('usable')->get()->map(fn (SnippetUsage $u) => $u->usable)->filter()->values();
    }

    /**
     * Dipakai = ada halaman/artikel yang menyisipkannya, ATAU ia penutup yang online (muncul di semua halaman).
     * Snippet penutup yang offline tidak tampil di mana pun, jadi boleh dihapus.
     */
    public function isInUse(): bool
    {
        return ($this->is_closing && $this->isOnline()) || $this->usages()->exists();
    }

    // ------------------------------------------------------------------ relasi & log

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** `content` sengaja tidak dicatat: ukurannya besar dan berubah pada tiap simpan. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'title', 'status', 'is_closing', 'sort_order'])
            ->logOnlyDirty()
            ->useLogName('snippet_updates');
    }
}
