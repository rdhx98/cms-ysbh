<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot polymorphic: satu tabel ini mencatat pemakaian media di
     * Halaman, Artikel, atau jenis konten lain mana pun yang nanti
     * memakai trait SyncsMediaUsage — tidak perlu tabel usage terpisah
     * per jenis konten.
     */
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
    }
};
