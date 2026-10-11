<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat alur persetujuan artikel (rilis 38): siapa mengajukan, menerbitkan, menjadwalkan, mengembalikan atau menolak, kapan, dan
     * dengan catatan apa. Catatan "kembalikan ke draf" dan "tolak" adalah cara penulis tahu APA yang harus diperbaiki.
     * Tabel baru; tabel `posts` tidak diubah. Sengaja tanpa foreign key (tipe kolom id `posts`/`users` milik proyek tidak diketahui kit).
     */
    public function up(): void
    {
        Schema::create('article_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();   // null = sistem (terbit otomatis oleh jadwal)
            $table->string('action', 20);                         // submit|recall|publish|schedule|return|reject|withdraw|archive|restore|auto-publish
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->text('note')->nullable();
            $table->timestamp('scheduled_for')->nullable();      // aksi "schedule": waktu terbit yang dipilih
            $table->timestamp('created_at')->nullable();

            $table->index(['post_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_reviews');
    }
};
