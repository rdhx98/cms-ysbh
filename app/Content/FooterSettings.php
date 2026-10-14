<?php

namespace App\Content;

use Illuminate\Support\Facades\DB;

/**
 * Penyimpanan isi footer di sisi CMS (rilis 55): tabel site_settings, kunci "footer". Aturan isi ada di SiteFooter (murni, diuji);
 * kelas ini hanya membaca dan menulis. TIDAK ada di landing (landing membaca lewat PublicLookup::footer).
 */
final class FooterSettings
{
    public const TABLE = 'site_settings';

    /** Apakah ada isi tersimpan (false = masih memakai isi bawaan). */
    public static function exists(): bool
    {
        return DB::table(self::TABLE)->where('key', SiteFooter::KEY)->exists();
    }

    /** Isi saat ini: yang tersimpan, atau isi bawaan bila belum pernah disimpan / rusak. */
    public static function load(): array
    {
        return SiteFooter::resolve(DB::table(self::TABLE)->where('key', SiteFooter::KEY)->value('value'));
    }

    /** Simpan data yang SUDAH lolos SiteFooter::sanitize. */
    public static function save(array $data, ?int $userId = null): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $now = \Illuminate\Support\Carbon::now();
        $found = DB::table(self::TABLE)->where('key', SiteFooter::KEY)->exists();
        if ($found) {
            DB::table(self::TABLE)->where('key', SiteFooter::KEY)->update(['value' => $json, 'updated_by' => $userId, 'updated_at' => $now]);
        } else {
            DB::table(self::TABLE)->insert(['key' => SiteFooter::KEY, 'value' => $json, 'updated_by' => $userId, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    /** Hapus isi tersimpan: situs kembali memakai isi bawaan. */
    public static function reset(): void
    {
        DB::table(self::TABLE)->where('key', SiteFooter::KEY)->delete();
    }
}
