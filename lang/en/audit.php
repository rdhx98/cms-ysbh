<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa untuk Audit Trail (Riwayat Aktivitas)
    |--------------------------------------------------------------------------
    */

    'created'          => 'Article created',
    'deleted'          => 'Article deleted',
    'updated_general'  => 'Article updated',

    // Status spesifik
    'status_draft'     => 'Article returned to draft',
    'status_review'    => 'Article submitted for review',
    'status_published' => 'Article published',
    'status_scheduled' => 'Article scheduled to be published',
    'status_rejected'  => 'Article rejected',
    'status_archived'  => 'Article scheduled to be archived',

    // Fallback jika ada status yang tidak dikenali
    'unknown'          => 'Unknown activity',
];
