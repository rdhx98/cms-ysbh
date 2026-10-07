<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'folder_id', 'disk', 'path', 'original_name', 'alt_text',
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
     * sebelum benar-benar mengosongkan dari Sampah (forceDeleteWithFile).
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
     * "Hapus" dari sudut pandang pengguna — sebetulnya cuma soft-delete
     * (isi deleted_at). Berkas fisik di disk TIDAK disentuh sama sekali
     * di sini; baris tetap ada supaya bisa dipulihkan dari Sampah.
     *
     * Tetap menolak kalau media masih dirujuk Halaman/Artikel — sengaja
     * dipertahankan di titik ini (bukan cuma nanti saat kosongkan
     * Sampah) supaya admin langsung tahu saat itu juga, bukan baru sadar
     * 30 hari kemudian waktu Sampah otomatis terkosongkan.
     */
    public function moveToTrash(bool $force = false): bool
    {
        if (! $force && $this->isInUse()) {
            return false;
        }

        return (bool) $this->delete(); // soft-delete, berkat trait SoftDeletes
    }

    /**
     * Pulihkan dari Sampah — bawaan trait SoftDeletes (restore()) sudah
     * cukup untuk ini, method ini cuma alias supaya nama di kode
     * pemanggil terasa konsisten dengan moveToTrash().
     */
    public function restoreFromTrash(): bool
    {
        return (bool) $this->restore();
    }

    /**
     * Kosongkan permanen — dipanggil dari view Sampah, atau otomatis
     * lewat scheduled command untuk baris yang sudah lebih dari 30 hari
     * di Sampah (lihat catatan di README). Ini satu-satunya titik yang
     * benar-benar membuang berkas fisik dari disk.
     */
    public function forceDeleteWithFile(): bool
    {
        if ($this->isInUse()) {
            return false;
        }

        Storage::disk($this->disk)->delete($this->path);
        return (bool) $this->forceDelete();
    }

    /**
     * Deteksi duplikat ringan untuk peringatan saat unggah — dicocokkan
     * lewat ukuran berkas + dimensi (bukan hash konten, supaya tidak
     * membebani proses upload di shared hosting). Ini heuristik, bukan
     * jaminan mutlak sama persis — cukup untuk memberi peringatan
     * "kemungkinan sudah ada", keputusan akhir tetap di tangan admin.
     */
    public static function findPotentialDuplicate(int $size, ?int $width, ?int $height, ?int $folderId = null): ?self
    {
        return static::query()
            ->where('size', $size)
            ->when($width, fn ($q) => $q->where('width', $width))
            ->when($height, fn ($q) => $q->where('height', $height))
            ->when($folderId, fn ($q) => $q->where('folder_id', $folderId))
            ->first();
    }
}
