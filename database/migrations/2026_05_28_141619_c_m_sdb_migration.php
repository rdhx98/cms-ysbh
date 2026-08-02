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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relasi ke Penulis
            $table->foreignId('category_id')->constrained()->onDelete('cascade'); // Relasi ke Kategori
            $table->string('title')->unique();
            $table->string('slug')->unique();
            $table->longText('content');
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->string('featured_image')->nullable(); // Jalur file di FTP Hostinger
            $table->enum('status', ['draft', 'review', 'published', 'scheduled', 'archived', 'rejected'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('post_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
        });

        Schema::create('post_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Anda bisa tambah kolom 'is_primary' jika ingin menandai penulis utama
            $table->boolean('is_primary')->default(false);
        });

        // Schema::create('pages', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('title')->unique();
        //     $table->string('slug')->unique();
        //     $table->longText('content');
        //     $table->enum('status', ['online', 'offline'])->default('offline');
        //     $table->json('meta_title')->nullable();
        //     $table->json('meta_description')->nullable();
        //     $table->timestamp('published_at')->nullable();
        //     $table->timestamps();
        // });
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('slug')->unique();
            $table->json('content')->nullable();
            $table->enum('status', ['online', 'offline'])->default('offline');
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->enum('type', ['page_link', 'custom_url'])->default('custom_url');
            $table->boolean('editable')->default(true);
            $table->bigInteger('page_id')->nullable();
            $table->string('url')->nullable();
            $table->bigInteger('parent_id')->nullable();
            $table->integer('order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('tags');
        Schema::dropIfExists('post_categories');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_tags');
        Schema::dropIfExists('post_users');
    }
};
