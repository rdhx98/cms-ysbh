<?php

namespace App\Content;

use Illuminate\Support\Facades\DB;

/**
 * Penyimpanan pengumuman di sisi CMS (rilis 85): tabel site_settings, kunci "announcement". Aturan isi ada di Announcement (murni, diuji);
 * kelas ini hanya membaca dan menulis. Landing membaca lewat PublicLookup::announcement().
 */
final class AnnouncementSettings
{
    public const TABLE = 'site_settings';

    public static function exists(): bool
    {
        return DB::table(self::TABLE)->where('key', Announcement::KEY)->exists();
    }

    /** Isi saat ini: yang tersimpan, atau bawaan (tanpa pengumuman) bila belum pernah disimpan / rusak. */
    public static function load(): array
    {
        return Announcement::resolve(DB::table(self::TABLE)->where('key', Announcement::KEY)->value('value'));
    }

    /** Simpan data yang SUDAH lolos Announcement::sanitize. */
    public static function save(array $data, ?int $userId = null): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $now = \Illuminate\Support\Carbon::now();
        if (DB::table(self::TABLE)->where('key', Announcement::KEY)->exists()) {
            DB::table(self::TABLE)->where('key', Announcement::KEY)->update(['value' => $json, 'updated_by' => $userId, 'updated_at' => $now]);
        } else {
            DB::table(self::TABLE)->insert(['key' => Announcement::KEY, 'value' => $json, 'updated_by' => $userId, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    /** Hapus semua pengumuman tersimpan. */
    public static function reset(): void
    {
        DB::table(self::TABLE)->where('key', Announcement::KEY)->delete();
    }

    /** ID media yang dirujuk pengumuman tersimpan (untuk Media::isInUse). Tidak pernah galat. @return list<int> */
    public static function mediaIds(): array
    {
        try {
            return Announcement::mediaIds(self::load());
        } catch (\Throwable) {
            return [];
        }
    }
}
