<?php

namespace App\Livewire\Traits;

trait WithNotifications
{
    /**
     * Pemicu notifikasi untuk ditangkap oleh Alpine.js
     *
     * @param string $message Pesan notifikasi
     * @param string $type Jenis: 'info', 'success', 'warning', 'error'
     */
    public function notify(string $message, string $type = 'info')
    {
        // Di Livewire 3, named arguments otomatis menjadi properti di $event.detail Alpine
        $this->dispatch('tampilkan-notifikasi', message: $message, type: $type);
    }
}
