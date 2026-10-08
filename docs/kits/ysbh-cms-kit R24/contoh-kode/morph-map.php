<?php
// app/Providers/AppServiceProvider.php -> boot()
// Sebelum tabel snippet_usages / media_usages terisi: simpan nama pendek ('page') alih-alih nama kelas penuh
// di kolom usable_type, supaya pemindahan/penggantian nama kelas tidak membuat baris yatim.
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::morphMap([
    'page'    => \App\Models\Page::class,
    'post'    => \App\Models\Post::class,
    'snippet' => \App\Models\Snippet::class,
]);

// Sengaja morphMap(), BUKAN enforceMorphMap(): versi "enforce" melempar galat untuk model mana pun yang dipakai
// di relasi morph tetapi tidak terdaftar — termasuk 'causer' (User) milik Spatie Activitylog dan model lain.
// Baris lama yang menyimpan nama kelas penuh tetap terbaca.
