<?php

namespace App\Livewire\Traits;

trait WithNotifications
{
    /**
     * Otomatis dijalankan oleh Livewire saat komponen di-load (Lifecycle Hook)
     */
    public function mountWithNotifications()
    {
        // 1. Baca session dari fungsi notifyFlash() kita sendiri
        if (session()->has('notify_message')) {
            $this->notify(
                session()->get('notify_message'),
                session()->get('notify_type', 'info')
            );
        }

        // 2. Baca juga format flash session standar Laravel (opsional tapi berguna)
        $standardTypes = ['success', 'error', 'info', 'warning'];
        foreach ($standardTypes as $type) {
            if (session()->has($type)) {
                $this->notify(session()->get($type), $type);
            }
        }
    }
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
    /**
     * Pemicu notifikasi khusus untuk Pindah Halaman (Redirect)
     */
    public function notifyFlash(string $message, string $type = 'info')
    {
        session()->flash('notify_message', $message);
        session()->flash('notify_type', $type);
    }
}
