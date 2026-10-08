<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dua tambahan menyusul ide UI/UX putaran kedua:
     * - alt_text: wajib diisi untuk aksesibilitas & SEO gambar,
     *   khususnya untuk situs yayasan yang publik.
     * - deleted_at: dasar fitur Sampah — file "dihapus" dari sisi
     *   pengguna cuma disembunyikan (soft-delete), berkas fisiknya
     *   TIDAK langsung dibuang dari disk. Baru benar-benar dihapus
     *   (dan berkasnya ikut dibuang) saat "Kosongkan Sampah" atau
     *   lewat pembersihan otomatis 30 hari (lihat catatan di README).
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('alt_text')->nullable()->after('original_name');
            $table->softDeletes(); // kolom deleted_at, nullable
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('alt_text');
            $table->dropSoftDeletes();
        });
    }
};
