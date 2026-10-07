<?php

namespace App\Content;

/**
 * Pembuat slug. Aturannya sama dengan yang di JavaScript (editor.js: slugify) supaya hasil di browser dan server identik.
 * "Pelayanan Kesehatan Ibu & Anak!" -> "pelayanan-kesehatan-ibu-dan-anak"
 */
final class Slug
{
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** Huruf beraksen Latin yang lazim di nama/istilah Indonesia dan Inggris. */
    private const MAP = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ā' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ō' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ū' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y', 'ß' => 'ss',
    ];

    public static function make(string $text): string
    {
        $s = mb_strtolower(trim($text));
        $s = strtr($s, self::MAP);
        $s = str_replace('&', ' dan ', $s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';

        return trim($s, '-');
    }

    public static function isValid(string $slug): bool
    {
        return (bool) preg_match(self::PATTERN, $slug);
    }
}
