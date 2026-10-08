<?php
// lang/id/ui.php — tambahkan (dan padanannya di lang/en/ui.php)
return [
    // <title> tab browser. :title hanya dipakai pada mode edit.
    'title' => [
        'page'    => ['create' => 'Buat Halaman',  'edit' => 'Ubah Halaman: :title'],
        'article' => ['create' => 'Tulis Artikel', 'edit' => 'Ubah Artikel: :title'],
        'snippet' => ['create' => 'Buat Snippet',  'edit' => 'Ubah Snippet: :title'],
    ],

    // Judul di header editor. Sekarang tampil mentah sebagai "ui.header.write_page" karena kuncinya tidak ada.
    'header' => [
        'page'    => ['create' => 'Halaman Baru',  'edit' => 'Edit Halaman'],
        'article' => ['create' => 'Artikel Baru',  'edit' => 'Edit Artikel'],
        'snippet' => ['create' => 'Snippet Baru',  'edit' => 'Edit Snippet'],
    ],
];
