<?php
// routes/web.php — builder di samping editor lama (versi yang Anda pakai).
// Builder membaca NAMA rute, bukan URL: "snippet/make" dengan nama "snippet.create" aman.
// Awalan "v2." dipertahankan saat redirect setelah simpan pertama (ContentType::routeNameFor):
//   v2.page.create -> v2.page.edit,  v2.snippet.create -> v2.snippet.edit
// Saat cutover: hapus ->prefix('v2') dan ->name('v2.'), dan hapus rute editor lama.

Route::middleware(['auth'])->prefix('v2')->name('v2.')->group(function () {
    Route::livewire('/page/create',         'content.builder')->name('page.create');
    Route::livewire('/page/edit/{page:id}', 'content.builder')->name('page.edit');

    Route::livewire('/article/write',          'content.builder')->name('article.write');
    Route::livewire('/article/edit/{post:id}', 'content.builder')->name('article.edit');

    // Bingkai pratinjau kanvas (rilis 3): dimuat di <iframe> oleh builder. HARUS di dalam grup ber-auth ini.
    Route::livewire('/preview/{token}', 'content.canvas-frame')->name('preview.frame');

    // Pratinjau versi TERSIMPAN dari database (halaman/artikel/snippet), rilis 6. Dua segmen, jadi tidak bentrok dengan {token} di atas.
    Route::livewire('/preview/{type}/{id}', 'content.record-preview')->name('preview.record')->whereIn('type', ['page', 'article', 'snippet'])->whereNumber('id');

    Route::livewire('/snippet/make',              'content.builder')->name('snippet.create');
    Route::livewire('/snippet/edit/{snippet:id}', 'content.builder')->name('snippet.edit');
});

// Tidak berubah. (Punya #[Title] sendiri.)
Route::livewire('/page/preview/{pageSlug}', 'page-preview')->name('page.preview');
