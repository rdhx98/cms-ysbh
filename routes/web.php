<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\EditorImageUploadController;


// Route::view('/', 'welcome')->name('home');
// 1. Logika untuk Halaman Utama "/" CMS
// Route::get('/', function () {
//     if (Auth::check()) {
//         return redirect()->route('dashboard');
//     }
//     return redirect()->route('login');
// });
// Auth Route (Hanya bisa diakses jika belum login / Guest)
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    // --- RUTE REGISTER CMS (Pindahan dari domain utama) ---
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

});


Route::get('/', function () {

    return redirect('/login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/dashboard', 'dashboard')->name('dashboard');
    Route::livewire('/article', 'article')->name('article.index');
    Route::livewire('/article/write', 'article.editor')->name('article.editor');
    Route::livewire('/documentation', 'documentation')->name('documentation');

    // --- ROUTE UNTUK ARTIKEL ---
    // 1. Create: Membuat artikel baru (TIDAK ADA parameter)
    // Route::livewire('/article/write', 'article.editor')->name('article.editor');

    // 2. Edit: Menyunting artikel lama (MENGGUNAKAN parameter {post} dan komponen editor yang sama)
    Route::livewire('/article/{post}/edit', 'article.editor')->name('article.edit');

    // 3. Preview: Melihat artikel baca-saja (MENGGUNAKAN parameter {post} dan komponen preview baru)
    Route::livewire('/article/{post}/preview', 'article.preview')->name('article.preview');

    Route::post('/editor/upload-image', [EditorImageUploadController::class, 'store'])
    // ->middleware(['auth']) // sesuaikan dengan middleware yang dipakai route artikel Anda
    ->name('editor.upload-image');

});

require __DIR__.'/settings.php';
