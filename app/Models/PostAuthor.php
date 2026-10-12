<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Penulis tambahan sebuah artikel (rilis 49 rev 3): baris tabel pivot `post_users` (yang sama dengan `Post::authors()` di proyek Anda).
 * Penulis utama = `posts.user_id`, bukan baris di sini. Hanya kolom `post_id` dan `user_id` yang dipakai (tabel Anda boleh punya kolom lain,
 * termasuk tanpa kolom id); karena itu tanpa cap waktu. Penambahan/pelepasan dicatat di `article_reviews` (aksi `author-add` / `author-remove`).
 */
class PostAuthor extends Model
{
    public $timestamps = false;

    protected $table = 'post_users';

    protected $guarded = [];
}
