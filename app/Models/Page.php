<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Table('pages')]
#[Translatable('title', 'slug', 'content', 'meta_title', 'meta_description')]
class Page extends Model
{
    use HasFactory, HasTranslations;

    // Field yang boleh diisi massal
    protected $fillable = ['title', 'slug', 'content', 'status', 'meta_title','meta_description', 'published_at', 'created_at', 'updated_at'];
    // public $translatable = ['title', 'content', 'meta_title', 'meta_description'];
    protected $casts = [  'published_at' => 'datetime', ];

    // public function getRouteKeyName()
    // {
    //     return 'slug';
    // }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'online'     => 'bg-foresty-50 text-foresty-700 border-foresty-700 dark:bg-foresty-950 dark:text-foresty-300',
            'offline'    => 'bg-red-50 text-red-700 border-red-700 dark:bg-red-950 dark:text-red-300',
            default     => 'bg-zinc-50 text-zinc-700 border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300',
        };
    }
    // public function resolveRouteBinding($value, $field = null)
    // {
    //     if ($field === 'slug') {
    //         return $this->where(function ($query) use ($value) {

    //             // Ambil daftar bahasa dari config/app.php
    //             // Fallback ke ['id', 'en'] jika config belum diset
    //             $locales = config('app.supported_locales', ['id', 'en']);

    //             // Lakukan pencarian ke semua bahasa secara dinamis
    //             foreach ($locales as $locale) {
    //                 $query->orWhere("slug->{$locale}", $value);
    //             }

    //         })->firstOrFail();
    //     }

    //     return parent::resolveRouteBinding($value, $field);
    // }
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'slug') {
            // Tambahkan ->query() sebelum ->where()
            return $this->query()->where(function ($query) use ($value) {

                $locales = config('app.supported_locales', ['id', 'en']);

                foreach ($locales as $locale) {
                    $query->orWhere("slug->{$locale}", $value);
                }

            })->firstOrFail();
        }

        return parent::resolveRouteBinding($value, $field);
    }
}
