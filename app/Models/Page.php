<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('pages')]
class Page extends Model
{
    use HasFactory;

    // Field yang boleh diisi massal
    protected $fillable = ['title', 'slug', 'content', 'status', 'meta_title','meta_description', 'published_at', 'created_at', 'updated_at'];
    public $translatable = ['title', 'content', 'meta_title', 'meta_description'];
    protected $casts = [  'published_at' => 'datetime', ];

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
