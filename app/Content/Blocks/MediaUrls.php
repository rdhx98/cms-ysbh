<?php

namespace App\Content\Blocks;

/**
 * Alamat gambar/berkas yang tersimpan di JSON konten ({url, media_id}) TIDAK dipercaya: alamat itu "terpanggang" bersama host saat gambar dipilih
 * (mis. http://landing-ysbh.test/storage/...) sehingga rusak begitu domain/lingkungan berganti. Kelas ini membentuk ulang `url` dari `media_id`
 * lewat model Media (Storage::disk()->url() = MEDIA_URL lingkungan saat ini) SETIAP kali konten ditampilkan / dibuka di editor.
 *
 * Aturan (murni, kecuali fromModel):
 *   - hanya larik yang punya `media_id` bilangan bulat positif (int atau string angka) DAN kunci `url` yang disentuh;
 *   - `media_id` kosong/null, atau tanpa `media_id` (URL eksternal, tautan biasa) -> dibiarkan apa adanya;
 *   - media tidak ditemukan (terhapus), hasil pencarian bukan string / kosong, atau pencarian melempar galat -> url lama dibiarkan (tidak ada yang hilang);
 *   - idempoten; satu pencarian untuk seluruh ID unik (maks. 500).
 * Batas jujur: blok yang punya `url` tetapi tidak punya `media_id` sah tidak bisa disembuhkan (tidak ada rujukan ke Media).
 */
final class MediaUrls
{
    private const MAX_IDS = 500;

    /**
     * @param array<mixed> $blocks daftar blok (atau struktur apa pun yang memuatnya)
     * @param null|callable(int[]):array<int,string> $lookup id[] -> [id => url]; bawaan: fromModel()
     * @return array<mixed>
     */
    public static function resolve(array $blocks, ?callable $lookup = null): array
    {
        $ids = self::ids($blocks);
        if ($ids === []) {
            return $blocks;
        }

        try {
            $map = ($lookup ?? [self::class, 'fromModel'])($ids);
        } catch (\Throwable $e) {
            if (function_exists('report')) {
                report($e);
            }

            return $blocks;
        }
        if (!is_array($map) || $map === []) {
            return $blocks;
        }

        return self::walk($blocks, $map);
    }

    /**
     * ID media unik (terurut, maks. 500) dari semua larik yang memuat `url` dan `media_id` sah.
     *
     * @param array<mixed> $blocks
     * @return int[]
     */
    public static function ids(array $blocks): array
    {
        $found = [];
        self::collect($blocks, $found);
        $ids = array_keys($found);
        sort($ids);

        return array_slice($ids, 0, self::MAX_IDS);
    }

    /**
     * @param int[] $ids
     * @return array<int,string>
     */
    public static function fromModel(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $i) => $i > 0)));
        if ($ids === [] || !class_exists(\App\Models\Media::class)) {
            return [];
        }

        $out = [];
        foreach (\App\Models\Media::query()->whereIn('id', $ids)->get() as $m) {
            $out[(int) $m->id] = (string) $m->url();
        }

        return $out;
    }

    /** media_id sah -> int positif, selain itu null. */
    public static function validId(mixed $v): ?int
    {
        if (is_int($v)) {
            return $v > 0 ? $v : null;
        }
        if (is_string($v) && ctype_digit($v) && (int) $v > 0) {
            return (int) $v;
        }

        return null;
    }

    /** @param array<mixed> $node @param array<int,true> $found */
    private static function collect(array $node, array &$found): void
    {
        if (array_key_exists('url', $node) && ($id = self::validId($node['media_id'] ?? null)) !== null) {
            $found[$id] = true;
        }
        foreach ($node as $v) {
            if (is_array($v)) {
                self::collect($v, $found);
            }
        }
    }

    /** @param array<mixed> $node @param array<int,string> $map @return array<mixed> */
    private static function walk(array $node, array $map): array
    {
        if (array_key_exists('url', $node) && ($id = self::validId($node['media_id'] ?? null)) !== null) {
            $u = $map[$id] ?? null;
            if (is_string($u) && $u !== '') {
                $node['url'] = $u;
            }
        }
        foreach ($node as $k => $v) {
            if (is_array($v)) {
                $node[$k] = self::walk($v, $map);
            }
        }

        return $node;
    }
}
