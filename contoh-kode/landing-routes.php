<?php
/**
 * Rute di aplikasi LANDING (situs publik). Contoh; sesuaikan dengan struktur Anda. Lihat docs/DUA-APLIKASI.md.
 *
 * Templat jalur di config('cms.public') harus sama dengan jalur rute-rute ini:
 *   page.show    <->  cms.public.page     = '/{slug}'
 *   article.show <->  cms.public.article  = '/artikel/{slug}'
 *
 * Rute 'page.show' ('/{slug}') menangkap SEMUA jalur satu segmen: daftarkan PALING AKHIR.
 */

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

// Artikel: /artikel/{slug}. Pencarian slug berbentuk JSON per bahasa (lihat App\Content\JsonSql); isi lewat <x-content.sections> / <x-content.body>.
Route::livewire('/artikel/{slug}', 'article-show')->name('article.show');   // milik landing, bukan bagian kit

// Halaman: /{slug}  (paling akhir)
Route::livewire('/{slug}', 'page-show')->name('page.show')->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');

/*
 * Uji kontrak (taruh di landing, mis. tests/Feature/UrlContractTest.php) agar templat dan rute tidak pernah berbeda:
 *
 *   expect(route('article.show', 'contoh', absolute: false))->toBe(str_replace('{slug}', 'contoh', config('cms.public.article')));
 *   expect(route('page.show', 'contoh', absolute: false))->toBe(str_replace('{slug}', 'contoh', config('cms.public.page')));
 */
