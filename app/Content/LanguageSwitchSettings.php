<?php

namespace App\Content;

use Illuminate\Support\Facades\DB;

/**
 * Penyimpanan pengaturan pengganti bahasa di sisi CMS (rilis 104): tabel site_settings, kunci "lang_switch". Aturan isi ada di
 * LanguageSwitch (murni, diuji); kelas ini hanya membaca dan menulis. Landing membaca lewat PublicLookup::languageSwitch().
 */
final class LanguageSwitchSettings
{
    public const TABLE = 'site_settings';

    public static function exists(): bool
    {
        return DB::table(self::TABLE)->where('key', LanguageSwitch::KEY)->exists();
    }

    /** Isi saat ini: yang tersimpan, atau bawaan bila belum pernah disimpan / rusak. */
    public static function load(): array
    {
        return LanguageSwitch::resolve(DB::table(self::TABLE)->where('key', LanguageSwitch::KEY)->value('value'));
    }

    /** Simpan data yang SUDAH lolos LanguageSwitch::sanitize. */
    public static function save(array $data, ?int $userId = null): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $now = \Illuminate\Support\Carbon::now();
        if (DB::table(self::TABLE)->where('key', LanguageSwitch::KEY)->exists()) {
            DB::table(self::TABLE)->where('key', LanguageSwitch::KEY)->update(['value' => $json, 'updated_by' => $userId, 'updated_at' => $now]);
        } else {
            DB::table(self::TABLE)->insert(['key' => LanguageSwitch::KEY, 'value' => $json, 'updated_by' => $userId, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    /** Hapus isi tersimpan: kembali ke bawaan (pil nyala). */
    public static function reset(): void
    {
        DB::table(self::TABLE)->where('key', LanguageSwitch::KEY)->delete();
    }
}
