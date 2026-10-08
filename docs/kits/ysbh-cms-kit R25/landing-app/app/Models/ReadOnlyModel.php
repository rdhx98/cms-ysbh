<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ReadOnlyModel extends Model
{
    // $guarded dikosongkan agar SEMUA upaya menulis sampai ke peristiwa di bawah dan mendapat pesan yang jelas (bukan galat isian massal)
    protected $guarded = [];

    protected static function booted(): void
    {
        foreach (['saving', 'deleting', 'restoring', 'forceDeleting'] as $event) {
            static::registerModelEvent($event, fn () => throw new \LogicException('Landing hanya membaca; ubah data lewat CMS.'));
        }
    }
}
