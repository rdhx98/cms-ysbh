<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends ReadOnlyModel
{
    use SoftDeletes;   // WAJIB: tanpa ini berkas yang sudah dihapus dari File Manager tetap tampil di situs

    protected $table = 'media';

    /** URL publik berkas. Disk 'public' dan MEDIA_URL harus sama dengan CMS (lihat contoh-kode/config-dua-aplikasi.php). */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
