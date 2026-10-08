<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute landing (rilis 24: dua bahasa)
|--------------------------------------------------------------------------
| SEMUA isi situs publik berasal dari CMS (model Page, Post, Snippet). Tidak ada halaman statis (Blade) yang dirutekan satu per satu.
|
| BAHASA ditentukan HANYA oleh alamat (docs/BAHASA.md): bahasa bawaan (EN) tanpa awalan, bahasa lain dengan awalan kodenya ("/id").
| Tidak ada ?lang, tidak ada cookie, tidak ada pengalihan otomatis dari Accept-Language atau IP. Slug berbeda per bahasa.
| Rute ditulis satu per satu (bukan Route::prefix/group) supaya tools/check-landing.php dan tests dapat membacanya.
|
| URUTAN PENTING: rute yang lebih khusus lebih dulu, '/{slug}' PALING AKHIR. Rute '/id/...' harus sebelum '/{slug}' (kalau tidak, "id" dianggap slug).
| Templat jalur harus sama dengan config('cms.public') (lihat config/cms.php): page, article, home, articles per bahasa.
| Menambah bahasa: salin blok "id" di bawah dengan kode barunya, tambahkan ke 'supported_locales', dan ke config('cms.*') per bahasa.
*/

// ---- Bahasa bawaan (EN), tanpa awalan ----
// Beranda: halaman CMS ber-slug config('cms.home_slug.en') (bawaan "home"). Belum dibuat = balasan 503 "situs sedang disiapkan", bukan 404.
Route::livewire('/', 'page-show')->name('home');

// Peta situs dan robots.txt: satu segmen tetapi bertitik, jadi bukan slug yang sah dan tidak pernah bertabrakan dengan halaman CMS.
Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');
Route::get('/robots.txt', \App\Http\Controllers\RobotsController::class)->name('robots');

Route::livewire('/articles', 'articles-index')->name('articles');
Route::livewire('/articles/{slug}', 'article-show')->name('article.show');

// ---- Bahasa Indonesia, awalan /id ----
Route::livewire('/id', 'page-show')->name('id.home');
Route::livewire('/id/artikel', 'articles-index')->name('id.articles');
Route::livewire('/id/artikel/{slug}', 'article-show')->name('id.article.show');
Route::livewire('/id/{slug}', 'page-show')->name('id.page.show')->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');

// ---- Halaman CMS bahasa bawaan: PALING AKHIR ----
Route::livewire('/{slug}', 'page-show')->name('page.show')->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
