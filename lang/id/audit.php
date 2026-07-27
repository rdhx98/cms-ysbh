<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa untuk Audit Trail (Riwayat Aktivitas)
    |--------------------------------------------------------------------------
    */

    'created'          => 'Artikel dibuat',
    'deleted'          => 'Artikel dihapus',
    'updated_general'  => 'Artikel diperbarui',

    // Status spesifik
    'status_draft'     => 'Artikel dikembalikan ke draft',
    'status_review'    => 'Artikel diajukan untuk review',
    'status_published' => 'Artikel ditayangkan',
    'status_scheduled' => 'Artikel dijadwalkan untuk tayang',
    'status_rejected'  => 'Artikel ditolak',
    'status_archived'  => 'Artikel diarsipkan',

    // Fallback jika ada status yang tidak dikenali
    'unknown'          => 'Aktivitas tidak dikenal',
];
