<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengaturan situs berbentuk kunci -> JSON (rilis 55). Saat ini satu baris: key "footer" (isi kaki halaman, App\Content\SiteFooter).
     * Dibaca landing (hanya membaca) dan ditulis CMS. Tabel baru: tidak menyentuh tabel yang ada.
     */
    public function up(): void
    {
        if (Schema::hasTable('site_settings')) {
            return;
        }
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->json('value');
            $table->unsignedBigInteger('updated_by')->nullable();   // tanpa kunci asing: pengaturan tidak boleh ikut hilang bila akun dihapus
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
