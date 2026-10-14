<?php

namespace App\Content;

use Illuminate\Support\Facades\DB;

/**
 * Penyimpanan palet warna di sisi CMS (rilis 57): tabel site_settings (rilis 55), kunci "palette". Aturan isi ada di Palette (murni, diuji).
 * TIDAK ada di landing (landing membaca lewat PublicLookup::paletteCss).
 */
final class PaletteSettings
{
    public const TABLE = 'site_settings';

    public static function exists(): bool
    {
        return DB::table(self::TABLE)->where('key', Palette::KEY)->exists();
    }

    /** Palet saat ini: yang tersimpan, atau bawaan bila belum pernah disimpan / rusak. */
    public static function load(): array
    {
        return Palette::resolve(DB::table(self::TABLE)->where('key', Palette::KEY)->value('value'));
    }

    /** Simpan data yang SUDAH lolos Palette::sanitize. */
    public static function save(array $data, ?int $userId = null): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $now = \Illuminate\Support\Carbon::now();
        $query = DB::table(self::TABLE)->where('key', Palette::KEY);
        if ($query->exists()) {
            $query->update(['value' => $json, 'updated_by' => $userId, 'updated_at' => $now]);
        } else {
            DB::table(self::TABLE)->insert(['key' => Palette::KEY, 'value' => $json, 'updated_by' => $userId, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    /** Hapus palet tersimpan: situs kembali ke warna bawaan. */
    public static function reset(): void
    {
        DB::table(self::TABLE)->where('key', Palette::KEY)->delete();
    }
}
