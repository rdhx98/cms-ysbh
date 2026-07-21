<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;


#[Table('posts')]
class Post extends Model
{
    use HasFactory;

    // Field yang boleh diisi massal
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'content',
        'featured_image',
        'status',
        'created_at',
        'updated_at',
        'published_at',
    ];

    // Mengubah string tanggal menjadi objek Carbon/Datetime secara otomatis
    protected $casts = [
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
}
