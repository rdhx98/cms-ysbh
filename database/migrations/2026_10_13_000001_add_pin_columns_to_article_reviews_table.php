<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pesan review yang menempel pada blok (rilis 50). Kolom ditambahkan ke `article_reviews` (hanya-tambah):
     *   block_id / block_type / block_excerpt : blok yang ditandai pesan dan potretan jenis + cuplikannya saat pesan dikirim (dibaca bila blok sudah dihapus);
     *   ref_id                                : untuk aksi `resolve` / `reopen`, id pesan yang ditandai selesai / dibuka lagi.
     * Aman dijalankan ulang (setiap kolom dicek). Pesan lama tidak berpenanda (semua kolom null).
     */
    public function up(): void
    {
        Schema::table('article_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('article_reviews', 'block_id')) {
                $table->string('block_id', 64)->nullable();
            }
            if (!Schema::hasColumn('article_reviews', 'block_type')) {
                $table->string('block_type', 40)->nullable();
            }
            if (!Schema::hasColumn('article_reviews', 'block_excerpt')) {
                $table->string('block_excerpt', 200)->nullable();
            }
            if (!Schema::hasColumn('article_reviews', 'ref_id')) {
                $table->unsignedBigInteger('ref_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Sengaja kosong: menghapus kolom akan membuang penanda dan tanda selesai yang sudah tercatat (riwayat hanya-tambah).
    }
};
