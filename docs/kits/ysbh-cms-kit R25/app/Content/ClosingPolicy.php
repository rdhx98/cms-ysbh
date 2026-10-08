<?php

namespace App\Content;

/**
 * Snippet mana yang ditambahkan di AKHIR halaman. Murni logika: penyusun halaman memberi datanya.
 *
 * Aturan:
 *   - bawaan   : semua snippet "penutup" yang online, urut sort_order
 *   - override : settings.closing pada halaman menggantikan bawaan sepenuhnya
 *                (null = ikuti bawaan, [] = tanpa penutup, ["donasi"] = hanya itu)
 *   - snippet yang sudah disisipkan manual di halaman TIDAK ditambahkan lagi di akhir
 */
final class ClosingPolicy
{
    /**
     * @param string[]|null     $override     ContentDocument::closingOverride()
     * @param array<string,int> $defaults     kunci => id snippet penutup bawaan, sudah berurutan
     * @param array<string,int> $available    kunci => id semua snippet online (untuk override yang menyebut snippet non-penutup)
     * @param int[]             $includedIds  ContentDocument::snippetIds()
     * @return int[]                          id snippet yang ditambahkan di akhir halaman, berurutan, tanpa duplikat
     */
    public static function resolve(?array $override, array $defaults, array $available, array $includedIds = []): array
    {
        $map = $override === null ? $defaults : $available;
        $keys = $override ?? array_keys($defaults);

        $ids = [];
        foreach ($keys as $key) {
            $id = $map[$key] ?? null;
            if ($id !== null && !in_array($id, $ids, true) && !in_array($id, $includedIds, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
