<?php

namespace App\Content;

/**
 * Nama untuk DITAMPILKAN dari kolom yang bentuknya bisa beragam: teks polos, string JSON, atau peta bahasa
 * ({"id": "Kesehatan", "en": "Health"}), seperti Category.name dan Tag.name (cast array).
 */
final class Names
{
    /** Bahasa yang diminta, lalu bahasa cadangan, lalu teks apa pun yang terisi; '' bila tidak ada. */
    public static function of(mixed $value, ?string $locale = null, array $fallbackLocales = ['id', 'en']): string
    {
        $value = LocaleMap::decode($value);

        if (is_string($value)) {
            return trim($value);
        }
        if (!is_array($value)) {
            return '';
        }

        foreach ([$locale, ...$fallbackLocales] as $l) {
            if ($l !== null && isset($value[$l]) && is_scalar($value[$l]) && trim((string) $value[$l]) !== '') {
                return trim((string) $value[$l]);
            }
        }
        foreach ($value as $v) {
            if (is_scalar($v) && trim((string) $v) !== '') {
                return trim((string) $v);
            }
        }

        return '';
    }

    /**
     * Teks MILIK bahasa itu saja, tanpa cadangan bahasa lain ('' bila kosong). Teks polos / bukan peta bahasa = ''. Dipakai situs publik,
     * di mana satu alamat = satu bahasa: isi bahasa lain tidak boleh bocor (slug EN yang menunjuk judul ID).
     */
    public static function exact(mixed $value, string $locale): string
    {
        $value = LocaleMap::decode($value);

        return is_array($value) && isset($value[$locale]) && is_scalar($value[$locale]) ? trim((string) $value[$locale]) : '';
    }
}
