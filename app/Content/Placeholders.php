<?php

namespace App\Content;

/**
 * Penanda "belum diisi" (rilis 25). Halaman dan snippet kerangka (cms:seed-pages) memuat TOKEN ini di tempat isi yang harus ditulis manusia
 * (isi program, NPWP, nomor rekening). Murni: perintah cms:audit-placeholders mencarinya di halaman online, artikel terbit, dan snippet online
 * supaya penanda tidak ikut terbit tanpa disadari.
 */
final class Placeholders
{
    public const TOKEN = '[ISI-DULU]';

    /** Apakah $value (teks polos, HTML, atau JSON mentah dari kolom; JSON mentah dicari apa adanya karena penandanya ASCII dan tag yang ter-escape tetap terbaca strip_tags) memuat penanda. Tag HTML dan entitas diabaikan ("[ISI-<b>DULU</b>]" tetap terdeteksi). */
    public static function contains(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                if (self::contains($v)) {
                    return true;
                }
            }

            return false;
        }
        if (!is_string($value) || $value === '') {
            return false;
        }

        return str_contains(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'), self::TOKEN);
    }

    /**
     * Kolom-kolom sebuah baris yang memuat penanda.
     *
     * @param array<string,mixed> $row nama kolom => nilai MENTAH
     * @return list<string>
     */
    public static function columns(array $row): array
    {
        $found = [];
        foreach ($row as $column => $value) {
            if (self::contains($value)) {
                $found[] = (string) $column;
            }
        }

        return $found;
    }
}
