<?php

namespace App\Editor;

/**
 * Nilai bawaan blok di registri boleh memuat penanda yang diisi saat blok/item DIBUAT:
 *   "@id"      -> ID acak baru (tiap item daftar berulang butuh ID sendiri)
 *   "@locales" -> peta bahasa kosong untuk bahasa aktif ({"id":"","en":""})
 * Padanan JavaScript-nya ada di editor.js (materialize) untuk item yang ditambah dari inspektur.
 */
final class Defaults
{
    public static function materialize(array $data, array $locales = ['id', 'en']): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[$key] = match (true) {
                $value === '@id' => 'itm_' . substr(bin2hex(random_bytes(5)), 0, 8),
                $value === '@locales' => array_fill_keys($locales, ''),
                is_array($value) => self::materialize($value, $locales),
                default => $value,
            };
        }

        return $out;
    }
}
