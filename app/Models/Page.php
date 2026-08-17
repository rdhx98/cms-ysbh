<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;

use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;



#[Table('pages')]
#[Translatable('title', 'slug', 'content', 'meta_title', 'meta_description')]
#[Fillable('title', 'slug', 'content', 'status', 'meta_title','meta_description', 'published_at', 'created_at', 'updated_at')]

class Page extends Model
{
    use HasFactory, LogsActivity, HasTranslations;

    // Field yang boleh diisi massal
    // protected $fillable = ['title', 'slug', 'content', 'status', 'meta_title','meta_description', 'published_at', 'created_at', 'updated_at'];
    // public $translatable = ['title', 'content', 'meta_title', 'meta_description'];
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

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'online'     => 'bg-foresty-50 text-foresty-700 border-foresty-700 dark:bg-foresty-950 dark:text-foresty-300',
            'offline'    => 'bg-red-50 text-red-700 border-red-700 dark:bg-red-950 dark:text-red-300',
            default     => 'bg-zinc-50 text-zinc-700 border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300',
        };
    }
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'slug') {
            return $this->query()->where(function ($query) use ($value) {

                // Ambil semua bahasa yang didukung (id dan en)
                $locales = config('app.supported_locales', ['id', 'en']);

                // Cek ke semua bahasa JSON
                foreach ($locales as $locale) {
                    $query->orWhere("slug->{$locale}", $value);
                }

                // Cadangan jika berupa teks biasa
                $query->orWhere('slug', $value);

            })->firstOrFail();
        }

        return parent::resolveRouteBinding($value, $field);
    }
    // public function resolveRouteBinding($value, $field = null)
    // {
    //     if ($field === 'slug') {
    //         // Tambahkan ->query() sebelum ->where()
    //         return $this->query()->where(function ($query) use ($value) {

    //             $locales = config('app.supported_locales', ['id', 'en']);

    //             foreach ($locales as $locale) {
    //                 $query->orWhere("slug->{$locale}", $value);
    //             }

    //         })->firstOrFail();
    //     }

    //     return parent::resolveRouteBinding($value, $field);
    // }
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
