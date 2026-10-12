<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penulis tambahan sebuah artikel (rilis 49 rev 3) memakai tabel pivot `post_users` yang SUDAH dikenal model Post proyek Anda
     * (`Post::authors()` = belongsToMany(User::class, 'post_users')). Penulis UTAMA tetap `posts.user_id` (pembuat artikel).
     *
     * Migrasi ini hanya membuat tabelnya BILA BELUM ADA. Tabel yang sudah ada (beserta isinya) tidak disentuh, dan `down()` tidak
     * menghapus apa pun, karena tabel itu bisa milik proyek Anda. Kit hanya memakai kolom `post_id` dan `user_id`.
     * Sengaja tanpa foreign key (tipe kolom id `posts`/`users` milik proyek tidak diketahui kit), seperti `article_reviews`.
     */
    public function up(): void
    {
        if (Schema::hasTable('post_users')) {
            return;
        }
        Schema::create('post_users', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->index();
            $table->unsignedBigInteger('user_id')->index();

            $table->unique(['post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        // sengaja kosong: `post_users` bisa sudah ada sebelum kit ini
    }
};
