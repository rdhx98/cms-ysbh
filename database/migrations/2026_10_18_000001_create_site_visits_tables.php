<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Statistik kunjungan situs publik (rilis 79). Tanpa cookie, tanpa menyimpan alamat IP, tanpa pihak ketiga.
     *
     * site_visits   : hitungan HARIAN. Baris kind='site' = jumlah kunjungan dan pengunjung unik seluruh situs pada hari itu;
     *                 baris kind='page'|'article' = jumlah kunjungan per halaman/artikel per bahasa (item_id = id record).
     * site_visitors : penanda "pengunjung ini sudah dihitung hari ini" = HASH satu arah (IP+browser+garam harian) yang tidak bisa
     *                 dikembalikan menjadi IP dan tidak bisa dihubungkan antarhari. Baris lebih tua dari 2 hari dihapus otomatis.
     * Tabel baru; tidak ada tabel yang ada diubah. Sengaja tanpa foreign key (id `posts`/`pages` milik proyek, dan baris tetap berguna
     * sebagai angka sejarah walau artikelnya dihapus).
     */
    public function up(): void
    {
        if (! Schema::hasTable('site_visits')) {
            Schema::create('site_visits', function (Blueprint $table) {
                $table->date('day');
                $table->string('kind', 10);                           // site | page | article
                $table->unsignedBigInteger('item_id')->default(0);   // 0 untuk baris 'site'
                $table->string('locale', 8)->default('');            // kosong untuk baris 'site'
                $table->unsignedInteger('views')->default(0);
                $table->unsignedInteger('uniques')->default(0);      // hanya terisi pada baris 'site'
                $table->primary(['day', 'kind', 'item_id', 'locale']);
                $table->index(['kind', 'item_id', 'day']);
            });
        }
        if (! Schema::hasTable('site_visitors')) {
            Schema::create('site_visitors', function (Blueprint $table) {
                $table->date('day');
                $table->char('hash', 32);
                $table->primary(['day', 'hash']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visitors');
        Schema::dropIfExists('site_visits');
    }
};
