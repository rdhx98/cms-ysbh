<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\EditorImageUploadController;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;


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

    Route::livewire('/article', 'article-index')->name('article.index');
    Route::livewire('/article/write', 'article-editor')->name('article.write');
    Route::livewire('/article/edit/{category}/{post:slug}', 'article-editor')->name('article.edit');
    Route::livewire('/article/preview/{category}/{post:slug}', 'article-preview')->name('article.preview');

    Route::post('/editor/upload-image', [EditorImageUploadController::class, 'store'])->name('editor.upload-image');

    Route::livewire('/page', 'page-index')->name('page.index');
    Route::livewire('/page/create', 'page-editor')->name('page.create');
    Route::livewire('/page/edit/{page:slug}', 'page-editor')->name('page.edit');
    Route::livewire('/page/preview/{page:slug}', 'page-preview')->name('page.preview');

    Route::livewire('/block', 'block-index')->name('block.index');

    // Route::livewire('/user/edit/{user:handle}', 'user-edit')->name('user.edit');
    Route::livewire('/user', 'user-index')->name('user.index');
    Route::livewire('/user/create', 'user-detail')->name('user.create');
    Route::livewire('/user/detail/{user:handle}', 'user-detail')->name('user.detail');

    Route::livewire('/preferences', 'settings-manager')->name('settings.manager');

    Route::livewire('/documentation', 'json-viewer')->name('documentation');

});

Route::get('/language/{locale}', function ($locale) {
    // Daftar bahasa yang diizinkan (mencegah error jika user manipulasi URL)
    if (! in_array($locale, ['en', 'id'])) {
        abort(400);
    }

    // Simpan ke session
    Session::put('locale', $locale);

    // Kembalikan user ke halaman sebelumnya
    return Redirect::back();
})->name('language.switch');

require __DIR__.'/settings.php';
