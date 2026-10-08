<?php

namespace App\Content;

/**
 * Membaca kolom "teks per bahasa" dari NILAI MENTAH database, apa pun bentuk historisnya:
 *   {"id":"Judul","en":"Title"}      format sekarang (JSON per bahasa)
 *   "\"Judul\""                       string JSON (kolom lama yang di-cast array saat menyimpan string)
 *   Judul                             teks polos dari editor lama (satu bahasa)
 * Teks polos ditempatkan pada bahasa pertama (utama); bahasa lain dibiarkan kosong untuk diisi.
 */
final class LocaleMap
{
    /** @return array<string,string> semua bahasa di $locales selalu ada (kosong = '') */
    public static function from(mixed $raw, array $locales): array
    {
        $out = array_fill_keys($locales, '');
        $value = self::decode($raw);

        if (is_array($value)) {
            foreach ($locales as $locale) {
                if (isset($value[$locale]) && is_scalar($value[$locale])) {
                    $out[$locale] = (string) $value[$locale];
                }
            }
        } elseif (is_string($value) && trim($value) !== '') {
            $out[$locales[0] ?? 'id'] = $value;
        }

        return $out;
    }

    /**
     * Membuka string JSON (hingga 3 lapis) HANYA bila tampak seperti JSON: diawali { [ " atau berupa "null".
     * Teks polos seperti "2026" atau "Tentang Kami" dibiarkan apa adanya.
     */
    public static function decode(mixed $raw): mixed
    {
        for ($i = 0; $i < 3 && is_string($raw); $i++) {
            $t = ltrim($raw);
            if ($t === '' || !(in_array($t[0], ['{', '[', '"'], true) || trim($t) === 'null')) {
                break;
            }
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
            $raw = $decoded;
        }

        return $raw;
    }
}
