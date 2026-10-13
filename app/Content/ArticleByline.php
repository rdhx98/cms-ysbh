<?php

namespace App\Content;

/**
 * Baris penulis di kepala halaman artikel (rilis 53): "Ditulis oleh Nama" di samping tanggal. MURNI (tanpa basis data).
 *
 * Tampil atau tidak ditentukan per artikel lewat pengaturan dokumen `settings.show_author` (kotak centang di panel Halaman/Artikel di CMS;
 * disimpan di kolom `content` bersama blok, jadi TANPA migrasi). Tidak ada pengaturan = tidak tampil (artikel lama tidak berubah).
 * Penulis utama (`posts.user_id`) selalu di depan, lalu penulis tambahan (`post_users`); nama ganda dan nama kosong dibuang.
 */
final class ArticleByline
{
    public const MAX_AUTHORS = 10;
    public const MAX_NAME = 80;

    /** Hanya nilai benar yang tegas: true, 1, "1", "true", "on". Selain itu (kosong, "0", "false", larik, tidak ada) = tidak tampil. */
    public static function show(mixed $settings): bool
    {
        $v = is_array($settings) ? ($settings['show_author'] ?? false) : false;

        return $v === true || $v === 1 || (is_string($v) && in_array(strtolower(trim($v)), ['1', 'true', 'on'], true));
    }

    /**
     * Nama penulis berurutan: penulis utama dulu, lalu tambahan menurut urutan $extraIds.
     *
     * @param int|string|null      $mainId
     * @param iterable<mixed>      $extraIds
     * @param array<int|string,mixed> $nameById id pengguna => nama
     * @return list<string>
     */
    public static function names(mixed $mainId, iterable $extraIds, array $nameById): array
    {
        $ids = [];
        foreach (array_merge([$mainId], is_array($extraIds) ? $extraIds : iterator_to_array($extraIds, false)) as $id) {
            if ((is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0 && !in_array((int) $id, $ids, true)) {
                $ids[] = (int) $id;
            }
        }
        $out = [];
        foreach ($ids as $id) {
            $n = $nameById[$id] ?? null;
            $n = is_string($n) ? trim((string) preg_replace('/\s+/u', ' ', $n)) : '';
            if ($n === '' || in_array($n, $out, true)) {
                continue;
            }
            $out[] = mb_strlen($n) > self::MAX_NAME ? rtrim(mb_substr($n, 0, self::MAX_NAME - 1)) . '…' : $n;
            if (count($out) >= self::MAX_AUTHORS) {
                break;
            }
        }

        return $out;
    }

    /** Teks nama: "A", "A dan B" / "A and B", "A, B dan C" / "A, B and C". Kosong bila tidak ada nama. */
    public static function join(array $names, string $lang): string
    {
        $names = array_values(array_filter(array_map(fn ($n) => is_string($n) ? trim($n) : '', $names), fn ($n) => $n !== ''));
        $and = $lang === 'id' ? 'dan' : 'and';
        if (count($names) <= 1) {
            return $names[0] ?? '';
        }
        $last = array_pop($names);

        return implode(', ', $names) . ' ' . $and . ' ' . $last;
    }

    /** Awalan baris: "Ditulis oleh" (ID) / "Written by" (bahasa lain). */
    public static function label(string $lang): string
    {
        return $lang === 'id' ? 'Ditulis oleh' : 'Written by';
    }
}
