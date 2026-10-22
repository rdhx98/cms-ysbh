<?php

namespace App\Content;

use Illuminate\Support\Facades\DB;

/**
 * Penyimpanan profil situs di sisi CMS (rilis 83): tabel site_settings, kunci "profile". Aturan isi ada di SiteProfile (murni, diuji);
 * kelas ini hanya membaca dan menulis. Landing membaca lewat PublicLookup::profile().
 */
final class ProfileSettings
{
    public const TABLE = 'site_settings';

    public static function exists(): bool
    {
        return DB::table(self::TABLE)->where('key', SiteProfile::KEY)->exists();
    }

    /** Isi saat ini: yang tersimpan, atau bawaan bila belum pernah disimpan / rusak. */
    public static function load(): array
    {
        return SiteProfile::resolve(DB::table(self::TABLE)->where('key', SiteProfile::KEY)->value('value'));
    }

    /** Simpan data yang SUDAH lolos SiteProfile::sanitize. */
    public static function save(array $data, ?int $userId = null): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $now = \Illuminate\Support\Carbon::now();
        if (DB::table(self::TABLE)->where('key', SiteProfile::KEY)->exists()) {
            DB::table(self::TABLE)->where('key', SiteProfile::KEY)->update(['value' => $json, 'updated_by' => $userId, 'updated_at' => $now]);
        } else {
            DB::table(self::TABLE)->insert(['key' => SiteProfile::KEY, 'value' => $json, 'updated_by' => $userId, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    /** Hapus isi tersimpan: situs kembali ke perilaku lama (tanpa profil). */
    public static function reset(): void
    {
        DB::table(self::TABLE)->where('key', SiteProfile::KEY)->delete();
    }

    /** ID media yang dirujuk profil tersimpan (untuk Media::isInUse). Tidak pernah galat. @return list<int> */
    public static function mediaIds(): array
    {
        try {
            return SiteProfile::mediaIds(self::load());
        } catch (\Throwable) {
            return [];
        }
    }
}
