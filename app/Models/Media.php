<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    //
     protected $table = 'media';
 
    protected $fillable = [
        'folder_id', 'disk', 'path', 'original_name',
        'mime_type', 'size', 'width', 'height', 'uploaded_by',
    ];
 
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }
 
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
 
    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }
 
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
 
    /** URL publik berkas ini — dipakai untuk <img src>, bukan path disk mentah. */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
 
    /**
     * Dipakai tombol Hapus di panel detail: true kalau media ini masih
     * dirujuk minimal satu Halaman/Artikel. Cek ini WAJIB dipanggil
     * sebelum benar-benar menghapus baris + berkas fisiknya.
     */
    public function isInUse(): bool
    {
        return $this->usages()->exists();
    }
 
    /**
     * Daftar model (Halaman/Artikel) yang memakai media ini — untuk
     * mengisi panel "Digunakan Di". Tiap usage di-load beserta model
     * usable-nya (morphTo) supaya bisa ambil judul/link halamannya.
     */
    public function usedIn(): \Illuminate\Support\Collection
    {
        return $this->usages()->with('usable')->get()->map(
            fn (MediaUsage $u) => $u->usable
        )->filter();
    }
 
    /**
     * Hapus baris DAN berkas fisiknya di disk sekaligus. Menolak kalau
     * masih dipakai, kecuali $force=true (mis. dipanggil dari halaman
     * admin lain yang sudah menampilkan konfirmasi eksplisit).
     */
    public function deleteWithFile(bool $force = false): bool
    {
        if (! $force && $this->isInUse()) {
            return false;
        }
 
        Storage::disk($this->disk)->delete($this->path);
        return (bool) $this->delete();
    }
}
