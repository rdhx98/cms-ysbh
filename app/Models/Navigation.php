<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Navigation extends Model
{
    //
    use HasTranslations; // 🌟 2. Aktifkan trait di sini
    
    protected $fillable = ['label', 'route_name', 'url', 'order', 'is_active'];
    public $translatable = ['label'];

    protected $casts = [  
        'label' => 'array',
        'published_at'     => 'datetime', 
    ];
}
