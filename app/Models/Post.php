<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
// HAPUS BARIS LAMA INI:
// use Spatie\Activitylog\Traits\LogsActivity;

// GANTI MENJADI ALAMAT YANG BARU:
// Ganti sesuai namespace yang tertera di file LogOptions.php
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

use Spatie\Translatable\Attributes\Translatable;

#[Table('posts')]
#[Fillable('user_id','category_id','title','slug','content','featured_image', 'meta_title','meta_description','status', 'created_at','updated_at','published_at',)]
#[Translatable('title','slug','content', 'meta_title','meta_description')]
class Post extends Model
{
    use HasFactory, LogsActivity;

    // Field yang boleh diisi massal
    // protected $fillable = ['user_id','category_id','title','slug','content','featured_image', 'meta_title','meta_description','status', 'created_at','updated_at','published_at',];
    protected $translateable = ['user_id','category_id','title','slug','content','featured_image', 'meta_title','meta_description','status', 'created_at','updated_at','published_at',];
    // ['draft', 'review', 'published', 'scheduled', 'archived', 'rejected']
    // 1. BUKU ATURAN
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'content', 'status'])
            ->logOnlyDirty()
            ->useLogName('article_updates');
    }

    // 2. PEMBERI LABEL PINTAR
    public function getDescriptionForEvent(string $eventName): string
    {
        if ($eventName === 'updated') {
        if (array_key_exists('status', $this->getChanges())) {
            return match($this->status) {
                'review' => 'status_review',
                'draft' => 'status_draft',
                'published' => 'status_published',
                'scheduled' => 'status_scheduled',
                'rejected' => 'status_rejected',
                'archived' => 'status_archived',
                default => 'updated_general',
            };
        }
        return 'updated_general';
    }

        return match ($eventName) {
            'created' => 'created',
            'deleted' => 'deleted',
            default => "unknown",
        };
    }

    // Mengubah string tanggal menjadi objek Carbon/Datetime secara otomatis
    protected $casts = [  
        'title'            => 'array',
        'slug'             => 'array',
        'content'          => 'array',
        'meta_title'       => 'array',
        'meta_description' => 'array',
        'published_at' => 'datetime', 
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Relasi: Post ini dimiliki oleh satu Kategori (belongsTo).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi: Post ini ditulis oleh satu User (Penulis).
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

        // Menghubungkan ke banyak Penulis
    public function authors()
    {
        return $this->belongsToMany(User::class, 'post_users');
    }

    // Menghubungkan ke banyak Tag
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'draft'     => 'bg-violet-50 text-violet-700 border-violet-700 dark:bg-violet-950 dark:text-violet-300',
            'review'    => 'bg-yellow-50 text-yellow-700 border-yellow-700 dark:bg-yellow-950 dark:text-yellow-300',
            'published' => 'bg-green-50 text-green-700 border-green-700 dark:bg-green-950 dark:text-green-300',
            'scheduled' => 'bg-orange-50 text-orange-700 border-orange-700 dark:bg-orange-950 dark:text-orange-300',
            'archived'  => 'bg-blue-50 text-blue-700 border-blue-700 dark:bg-blue-950 dark:text-blue-300',
            'rejected'  => 'bg-red-50 text-red-700 border-red-700 dark:bg-red-950 dark:text-red-300',
            default     => 'bg-violet-50 text-violet-700 border-violet-700 dark:bg-violet-950 dark:text-violet-300',
        };
    }
    public function getParsedContentAttribute()
    {
        $content = $this->content;

        // Pola Regex baru: Mencari href="internal://tipe/slug"
        $pattern = '/href="internal:\/\/(page|article)\/([^"]+)"/';

        // Terjemahkan URL internal menjadi URL asli Laravel
        return preg_replace_callback($pattern, function ($matches) {
            $type = $matches[1]; // Hasil: 'page' atau 'article'
            $slug = $matches[2]; // Hasil: 'transparansi'

            try {
                // Hasilkan URL valid berbasis route Laravel Anda
                $url = $type === 'article'
                    ? route('article.show', $slug)
                    : route('page.show', $slug);

                // Ganti pseudo-URL dengan URL yang sebenarnya
                return 'href="' . $url . '"';
            } catch (\Exception $e) {
                // Jika route tidak ditemukan, kembalikan ke # agar tidak crash
                return 'href="#"'; 
            }

        }, $content);
    }
}
