<?php

namespace App\Content;

/**
 * Cuplikan teks sebuah blok (rilis 50), dipakai untuk "memotret" blok yang ditandai pesan review: bila blok itu kemudian dihapus, pesan
 * tetap menampilkan jenis dan cuplikan terakhirnya ("Blok sudah dihapus: Judul · “Selamat datang…”"). Murni, tanpa Laravel.
 *
 * Teks dicari pada kunci yang lazim di data blok (text, title, content, ...), kolom bahasa (peta {"id": "...", "en": "..."}) memilih
 * bahasa yang diminta lalu bahasa lain, HTML dibuang. Blok tanpa teks (gambar, pemisah) menghasilkan '' dan pemanggil memakai nama jenisnya.
 */
final class BlockExcerpt
{
    public const MAX = 80;

    /** Urutan = prioritas. */
    private const KEYS = ['text', 'title', 'heading', 'question', 'content', 'label', 'caption', 'name', 'description', 'answer'];

    /** @param string[] $langs bahasa yang diutamakan, mis. ['id', 'en'] */
    public static function of(array $block, array $langs = ['id', 'en'], int $max = self::MAX): string
    {
        $data = $block['data'] ?? null;
        if (!is_array($data)) {
            return '';
        }

        return self::truncate(self::find($data, $langs, 0), $max);
    }

    private static function find(array $data, array $langs, int $depth): string
    {
        if ($depth > 4) {
            return '';
        }
        foreach (self::KEYS as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $text = self::text($data[$key], $langs);
            if ($text !== '') {
                return $text;
            }
            if (is_array($data[$key]) && !self::isLocaleMap($data[$key])) {
                $inner = self::find($data[$key], $langs, $depth + 1);
                if ($inner !== '') {
                    return $inner;
                }
            }
        }
        // daftar berulang (kartu, akordeon, ...): item pertama yang punya teks
        foreach ($data as $value) {
            if (is_array($value) && !self::isLocaleMap($value)) {
                $inner = self::find($value, $langs, $depth + 1);
                if ($inner !== '') {
                    return $inner;
                }
            }
        }

        return '';
    }

    /** String biasa atau peta bahasa -> teks bersih; selain itu ''. */
    private static function text(mixed $value, array $langs): string
    {
        if (is_string($value)) {
            return self::clean($value);
        }
        if (is_array($value) && self::isLocaleMap($value)) {
            foreach ([...$langs, ...array_keys($value)] as $l) {
                if (isset($value[$l]) && is_string($value[$l]) && ($t = self::clean($value[$l])) !== '') {
                    return $t;
                }
            }
        }

        return '';
    }

    /** Peta bahasa = larik asosiatif yang semua nilainya string dan kuncinya kode bahasa (2-3 huruf, mis. id, en, zh-CN). */
    private static function isLocaleMap(array $value): bool
    {
        if ($value === []) {
            return false;
        }
        foreach ($value as $k => $v) {
            if (!is_string($k) || !preg_match('/^[a-z]{2,3}(-[A-Za-z]{2,4})?$/D', $k) || !is_string($v)) {
                return false;
            }
        }

        return true;
    }

    private static function clean(string $html): string
    {
        $text = strip_tags(preg_replace('/<\/(p|div|li|h[1-6]|br)>|<br\s*\/?>/i', ' ', $html) ?? $html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private static function truncate(string $text, int $max): string
    {
        return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max - 1)) . '…' : $text;
    }
}
