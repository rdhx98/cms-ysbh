<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Pelengkap spatie/laravel-activitylog untuk Page, Post (artikel), dan Snippet (rilis 44). Dipakai BERSAMA `LogsActivity`.
 *
 * Dua hal yang tidak bisa dilakukan opsi `logOnly()` saja:
 *
 *  1. "Isi diubah" TANPA menyimpan isi. `content` sengaja tidak masuk `logOnly` (dokumen blok besar; menyimpannya penuh pada tiap simpan
 *     membengkakkan tabel). Akibatnya, simpan yang hanya mengubah isi tidak meninggalkan jejak sama sekali (dontSubmitEmptyLogs). Trait ini
 *     menambah SATU baris log `content_updated` (siapa, kapan) setiap kali `content` benar-benar berubah, tanpa nilai lama/baru.
 *
 *  2. Label perubahan status. Bila `status` berubah, deskripsi baris log menjadi `status_<nilai>` (mis. `status_published`, `status_online`)
 *     yang dikenali panel Riwayat (App\Content\AuditTrail::DESCRIPTIONS) sebagai "Diterbitkan", "Diaktifkan (online)", dst.
 *     Kolom `status` sendiri tetap tercatat lama → baru oleh `logOnly(['status', …])`.
 *
 * Pelaku = pengguna yang login (diambil Spatie). Perintah terjadwal / konsol: tanpa pelaku, panel menulis "Sistem".
 * Mencatat TIDAK PERNAH menggagalkan simpan: galat pada log dilaporkan (report) dan diabaikan.
 */
trait RecordsChanges
{
    /** Nama kejadian dan deskripsi untuk baris "isi diubah". Dikenali App\Content\AuditTrail. */
    public const CONTENT_EVENT = 'content_updated';

    public static function bootRecordsChanges(): void
    {
        static::updated(function (Model $model): void {
            if ($model->wasChanged('content')) {
                $model->recordContentEdit();
            }
        });
    }

    /**
     * Deskripsi baris log. Perubahan status diberi `status_<nilai>`; selain itu nama kejadian ("created", "updated", "deleted", "restored")
     * agar panel tidak menampilkan keterangan ganda.
     *
     * Dipanggil dari `getDescriptionForEvent()` MILIK MODEL (tiga baris, lihat Page/Post/Snippet). Sengaja tidak bernama
     * `getDescriptionForEvent` di sini: trait LogsActivity milik paket Spatie bisa saja sudah punya method itu, dan dua trait dengan
     * method sama menyebabkan galat fatal; method milik kelas selalu menang tanpa konflik.
     */
    public function describeEvent(string $eventName): string
    {
        if ($eventName === 'updated' && $this->wasChanged('status')) {
            $status = strtolower((string) preg_replace('/[^A-Za-z0-9_]+/', '_', (string) $this->status));
            if ($status !== '') {
                return 'status_' . $status;
            }
        }

        return $eventName;
    }

    /** Satu baris log "isi diubah" untuk record ini. Tanpa paket Spatie atau saat pencatatan dimatikan: tidak melakukan apa-apa. */
    protected function recordContentEdit(): void
    {
        if (!function_exists('activity')) {
            return;
        }

        try {
            $name = null;
            if (method_exists($this, 'getActivitylogOptions')) {
                $options = $this->getActivitylogOptions();
                $name = property_exists($options, 'logName') ? $options->logName : null;
            }

            activity($name)
                ->performedOn($this)
                ->event(self::CONTENT_EVENT)
                ->log(self::CONTENT_EVENT);
        } catch (\Throwable $e) {
            if (function_exists('report')) {
                report($e);
            }
        }
    }
}
