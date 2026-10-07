<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Folder itu virtual — cuma baris database, bukan direktori sungguhan
     * di disk. Ini keputusan sengaja untuk shared hosting Hostinger:
     * memindahkan file antar "folder" jadi UPDATE satu kolom, bukan
     * operasi rename/move di filesystem yang bisa kena masalah izin.
     */
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('media_folders')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_folders');
    }
};
