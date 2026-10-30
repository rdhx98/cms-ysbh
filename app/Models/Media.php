<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    /** Berkas di Sampah dibuang permanen setelah sekian hari (tampilan Sampah dan cms:purge-media-trash memakai angka ini). */
    public const TRASH_DAYS = 30;

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
        return $this->usages()->exists() || $this->usedBySiteProfile() || $this->usedByAnnouncement();
    }

    /**
     * Rilis 83: logo / favicon / gambar berbagi di Pengaturan > Profil & SEO menyimpan id media di site_settings (bukan di media_usages,
     * karena bukan Halaman/Artikel), jadi dicek terpisah. Tidak pernah galat: gagal membaca = dianggap tidak dipakai profil.
     */
    public function usedBySiteProfile(): bool
    {
        return class_exists(\App\Content\ProfileSettings::class) && in_array((int) $this->id, \App\Content\ProfileSettings::mediaIds(), true);
    }

    /**
     * Rilis 85: gambar popup pengumuman menyimpan id media di site_settings (kunci "announcement"), bukan di media_usages. Tidak pernah galat.
     */
    public function usedByAnnouncement(): bool
    {
        return class_exists(\App\Content\AnnouncementSettings::class) && in_array((int) $this->id, \App\Content\AnnouncementSettings::mediaIds(), true);
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
        // Folder asal yang sudah dihapus: kolom folder_id sudah dikosongkan oleh basis data (nullOnDelete), jadi berkas kembali ke Folder Utama.
        return (bool) $this->restore();
    }

    /**
     * Sisa hari sebelum berkas di Sampah dibuang permanen (0 = sudah waktunya). Null bila berkas tidak ada di Sampah.
     */
    public function daysUntilPurge(int $days = self::TRASH_DAYS): ?int
    {
        if ($this->deleted_at === null) {
            return null;
        }
        $left = (int) ceil(($this->deleted_at->copy()->addDays($days)->getTimestamp() - Carbon::now()->getTimestamp()) / 86400);

        return max(0, $left);
    }

    /** Berkas di Sampah yang sudah lewat batas hari. */
    public function scopeExpiredTrash(Builder $query, int $days = self::TRASH_DAYS): Builder
    {
        return $query->onlyTrashed()->where('deleted_at', '<=', Carbon::now()->subDays($days));
    }

    /**
     * Buang permanen semua berkas di Sampah yang sudah lewat batas hari. Berkas yang ternyata dipakai lagi dilewati (tidak dibuang).
     * Dipanggil saat Sampah dibuka di File Manager dan oleh `php artisan cms:purge-media-trash`. Mengembalikan jumlah per hasil.
     *
     * @return array{purged: int, skipped: int}
     */
    public static function purgeExpiredTrash(int $days = self::TRASH_DAYS, bool $dryRun = false): array
    {
        $purged = $skipped = 0;
        static::expiredTrash($days)->eachById(function (Media $media) use (&$purged, &$skipped, $dryRun) {
            if ($media->isInUse()) {
                $skipped++;

                return;
            }
            if ($dryRun || $media->forceDeleteWithFile()) {
                $purged++;
            } else {
                $skipped++;
            }
        });

        return ['purged' => $purged, 'skipped' => $skipped];
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
