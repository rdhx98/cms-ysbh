<?php

/**
 * CONTOH model BACA-SAJA untuk aplikasi LANDING. Satu berkas ini berisi kelima kelas hanya agar mudah dibaca: di proyek landing,
 * pisahkan menjadi app/Models/Page.php, Post.php, Category.php, Snippet.php, Media.php (dan ReadOnlyModel.php), semuanya namespace App\Models.
 * Tabelnya milik CMS (landing tidak punya migrasi dan tidak pernah menjalankan `php artisan migrate`).
 *
 * Kontrak yang dipakai kode kit (App\Content\PublicLookup, FileInfo, LinkResolver):
 *   Page      status 'online'; kolom slug JSON per bahasa; content dibaca MENTAH (getRawOriginal), jadi tanpa cast `content`
 *   Post      status 'published'; kolom yang dibaca: id, title, slug, content, meta_description, featured_image, category_id, published_at
 *   Category  name JSON per bahasa
 *   Snippet   scope online() dan closing() (urut sort_order lalu id), kolom key, status, is_closing, sort_order, content
 *   Media     SoftDeletes (berkas yang dihapus dari File Manager TIDAK boleh tampil di situs) dan url() ke disk 'public'
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/** Lapis kode: landing tidak boleh menulis. (Lapis lain: pengguna basis data dengan hak SELECT saja, lihat docs/DUA-APLIKASI.md.) */
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

class Page extends ReadOnlyModel
{
    protected $table = 'pages';
}

class Post extends ReadOnlyModel
{
    protected $table = 'posts';
}

class Category extends ReadOnlyModel
{
    protected $table = 'categories';
    public $timestamps = false;
}

class Snippet extends ReadOnlyModel
{
    protected $table = 'snippets';

    protected function casts(): array
    {
        return ['is_closing' => 'boolean', 'sort_order' => 'integer'];
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', 'online');
    }

    /** Snippet yang tampil otomatis di akhir halaman, berurutan. */
    public function scopeClosing(Builder $query): Builder
    {
        return $query->online()->where('is_closing', true)->orderBy('sort_order')->orderBy('id');
    }
}

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
