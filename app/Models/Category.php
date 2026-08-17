<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('categories')]
class Category extends Model
{
    //
    //
    use HasFactory;

    // Field yang boleh diisi
    protected $fillable = ['name', 'slug'];
    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
    ];

    /**
     * Relasi: Satu kategori memiliki banyak Post (hasMany).
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
