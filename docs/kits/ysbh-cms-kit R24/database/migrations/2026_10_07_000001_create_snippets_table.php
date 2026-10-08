<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snippet = potongan konten yang dipakai berulang (CTA donasi, Hubungi Kami, dst).
     * Bentuk kolomnya mengikuti Page: title JSON per bahasa, content {blocks, order, settings}, status offline/online.
     * Bedanya: tanpa slug & metadata SEO (bukan halaman), dan dikenali lewat `key` yang stabil.
     */
    public function up(): void
    {
        Schema::create('snippets', function (Blueprint $table) {
            $table->id();

            // Pengenal stabil untuk kode dan override halaman (settings.closing). Tidak diterjemahkan, tidak berubah.
            $table->string('key', 80)->unique();

            // Nama di admin per bahasa: {"id": "...", "en": "..."}  (sama bentuknya dengan pages.title)
            $table->json('title');
            $table->text('description')->nullable(); // catatan admin: kapan snippet ini dipakai

            // {blocks, order, settings} — dibaca/ditulis lewat App\Content\ContentDocument
            $table->json('content');

            $table->string('status', 20)->default('offline');

            // Tampil otomatis di akhir halaman (urut sort_order). Halaman bisa menimpanya lewat settings.closing.
            $table->boolean('is_closing')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Kueri penyusun halaman: "snippet penutup yang online, berurutan"
            $table->index(['is_closing', 'status', 'sort_order']);
        });

        // Siapa memakai snippet apa. Bentuknya sama dengan media_usages, supaya panel "Digunakan Di"
        // dan penjaga hapus bekerja serupa untuk media dan snippet.
        Schema::create('snippet_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snippet_id')->constrained('snippets')->cascadeOnDelete();
            $table->morphs('usable'); // usable_type, usable_id  (page | post | ...)
            $table->timestamps();

            $table->unique(['snippet_id', 'usable_type', 'usable_id'], 'snippet_usage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snippet_usages');
        Schema::dropIfExists('snippets');
    }
};
