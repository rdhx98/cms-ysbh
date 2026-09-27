<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('media_folders')
                ->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('media', function (Blueprint $table) {
            $table->id();
 
            $table->foreignId('folder_id')
                ->nullable()
                ->constrained('media_folders')
                ->nullOnDelete();
 
            // 'disk' disimpan eksplisit (bukan hardcode 'public') supaya
            // gampang pindah ke disk S3-compatible (mis. Cloudflare R2)
            // nanti tanpa migrasi ulang skema kalau butuh, tanpa mengubah
            // baris yang sudah ada.
            $table->string('disk')->default('public');
 
            // Nama file di disk SELALU di-generate (UUID/random), tidak
            // pernah dipakai nama asli dari pengguna — mencegah tabrakan
            // nama dan celah path traversal. Nama asli tetap disimpan
            // terpisah untuk ditampilkan di UI.
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // dalam bytes
 
            // Nullable: dokumen (PDF) tidak punya dimensi
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
 
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
 
            $table->timestamps();
 
            $table->index('folder_id');
        });
         Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->morphs('usable'); // usable_type, usable_id
            $table->timestamps();
 
            // Satu media cuma boleh tercatat SEKALI per konten yang sama —
            // sync() di trait bergantung pada constraint ini supaya tidak
            // ada baris dobel tiap kali Halaman disimpan ulang.
            $table->unique(['media_id', 'usable_type', 'usable_id'], 'media_usage_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('media_folders');
        Schema::dropIfExists('media');
        Schema::dropIfExists('media_usages');
    }
};
