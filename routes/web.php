<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

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
    Route::livewire('/article', 'article')->name('article');
    Route::livewire('/documentation', 'documentation')->name('documentation');
});

require __DIR__.'/settings.php';
