<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute landing (rilis 23: ramping)
|--------------------------------------------------------------------------
| SEMUA isi situs publik berasal dari CMS (model Page, Post, Snippet). Tidak ada lagi halaman statis (Blade) yang dirutekan satu per satu:
| beranda, tentang, kontak, program, kredibilitas, dampak, transparansi, dan seterusnya adalah halaman CMS biasa di '/{slug}'.
|
| URUTAN PENTING: rute yang lebih khusus lebih dulu, '/{slug}' PALING AKHIR.
| Templat jalur harus sama dengan config('cms.public'): page '/{slug}', article '/artikel/{slug}' (lihat config/cms.php).
*/

// Beranda: halaman CMS ber-slug config('cms.home_slug') (bawaan "home"). Belum dibuat = balasan 503 "situs sedang disiapkan", bukan 404.
Route::livewire('/', 'page-show')->name('home');

// Peta situs dan robots.txt: satu segmen tetapi bertitik, jadi bukan slug yang sah dan tidak pernah bertabrakan dengan halaman CMS.
Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');
Route::get('/robots.txt', \App\Http\Controllers\RobotsController::class)->name('robots');

// Alamat lama (placeholder rilis awal) dialihkan ke indeks artikel.
Route::redirect('/articles', '/artikel', 301);

Route::livewire('/artikel', 'articles-index')->name('articles');
Route::livewire('/artikel/{slug}', 'article-show')->name('article.show');
Route::livewire('/{slug}', 'page-show')->name('page.show')->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
