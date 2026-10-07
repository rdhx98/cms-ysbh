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
}
