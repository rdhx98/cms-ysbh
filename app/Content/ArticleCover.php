<?php

namespace App\Content;

/**
 * Thumbnail (sampul) artikel, rilis 40. Murni: tanpa Livewire/Eloquent; pencarian ke database dititipkan lewat callable.
 *
 * Penyimpanan: kolom `posts.featured_image` (teks, wajib terisi). Tiga kemungkinan nilai:
 *   "default.webp"  = belum ada sampul (nilai bawaan ContentWriter)
 *   "12" (angka)    = ID media dari File Manager  <- yang ditulis pemilih baru; sudah dibaca ArticleCards::cover()
 *   "cover-x.webp"  = nama berkas lama (storage/articles/…); dipertahankan selama tidak diganti
 *
 * Sumber pilihan: semua gambar yang dipasang di isi artikel, urut sesuai kemunculan di halaman: kunci `media_id` / `media_ids` di
 * blok mana pun (Gambar, Galeri, poster Video, foto profil di Kartu Builder, ...) dan <img src> di teks kaya. Berkas yang bukan gambar
 * (mis. PDF di blok Unduhan) disaring server lewat callable, dan subpohon `link` / `file` dilewati (itu tautan/unduhan, bukan gambar yang dipasang).
 */
final class ArticleCover
{
    public const DEFAULT = 'default.webp';

    /** Batas pilihan yang ditawarkan (panel kecil; sisanya lewat File Manager). */
    public const MAX_CHOICES = 24;

    /** Subpohon yang bukan "gambar yang dipasang". */
    private const SKIP_KEYS = ['link', 'file'];

    /**
     * Nilai kolom -> bentuk yang mudah dipakai panel.
     *
     * @return array{kind:'none'|'media'|'legacy',id:int,name:string}
     */
    public static function parse(mixed $raw): array
    {
        $v = is_scalar($raw) ? trim((string) $raw) : '';
        if ($v === '' || strcasecmp($v, self::DEFAULT) === 0) {
            return ['kind' => 'none', 'id' => 0, 'name' => ''];
        }
        if (ctype_digit($v)) {
            return (int) $v > 0 ? ['kind' => 'media', 'id' => (int) $v, 'name' => ''] : ['kind' => 'none', 'id' => 0, 'name' => ''];
        }

        return ['kind' => 'legacy', 'id' => 0, 'name' => $v];
    }

    /** Nilai state editor dari kolom: "" = belum ada sampul. */
    public static function stateFromColumn(mixed $raw): string
    {
        $p = self::parse($raw);

        return match ($p['kind']) {
            'media' => (string) $p['id'],
            'legacy' => $p['name'],
            default => '',
        };
    }

    /** Nilai kolom dari state editor (sesudah normalize()): kosong -> bawaan. */
    public static function columnFromState(string $state): string
    {
        return $state === '' ? self::DEFAULT : $state;
    }

    /**
     * Nilai dari browser tidak dipercaya. Yang lolos: "" (hapus), ID media gambar yang ADA, atau nama berkas lama yang sama persis
     * dengan nilai tersimpan (artinya tidak diubah). Selain itu dibuang menjadi "".
     *
     * @param callable(int):bool $isImage apakah ID itu media gambar yang ada
     */
    public static function normalize(mixed $client, mixed $storedRaw, callable $isImage): string
    {
        $v = is_scalar($client) ? trim((string) $client) : '';
        if ($v === '' || strcasecmp($v, self::DEFAULT) === 0) {
            return '';
        }
        if (ctype_digit($v)) {
            $id = (int) $v;

            return $id > 0 && $isImage($id) ? (string) $id : '';
        }
        $stored = self::parse($storedRaw);

        return $stored['kind'] === 'legacy' && $stored['name'] === $v ? $v : '';
    }

    /**
     * Rujukan gambar di isi artikel menurut urutan kemunculan: ID media dan alamat <img src>.
     * Blok tingkat atas mengikuti $order (yang tidak ada di $order ikut di belakang), isi bersarang mengikuti urutan larik.
     *
     * @param array<string,mixed> $blocks id => blok
     * @param list<string>        $order
     * @return array{ids:list<int>,srcs:list<string>} tanpa duplikat
     */
    public static function references(array $blocks, array $order): array
    {
        $ids = [];
        $srcs = [];
        $seen = [];
        $sequence = [];
        foreach ($order as $id) {
            if (is_string($id) && isset($blocks[$id]) && !isset($seen[$id])) {
                $seen[$id] = true;
                $sequence[] = $blocks[$id];
            }
        }
        foreach ($blocks as $id => $block) {
            if (!isset($seen[$id])) {
                $sequence[] = $block;
            }
        }

        foreach ($sequence as $block) {
            self::walk($block, $ids, $srcs, 0);
        }

        return ['ids' => array_values(array_unique($ids)), 'srcs' => array_values(array_unique($srcs))];
    }

    private static function walk(mixed $node, array &$ids, array &$srcs, int $depth): void
    {
        if ($depth > 24) {
            return;
        }
        if (is_string($node)) {
            if (str_contains($node, '<img')) {
                foreach (self::imgSources($node) as $s) {
                    $srcs[] = $s;
                }
            }

            return;
        }
        if (!is_array($node)) {
            return;
        }
        foreach ($node as $key => $value) {
            if (is_string($key) && in_array($key, self::SKIP_KEYS, true)) {
                continue;
            }
            if ($key === 'media_id' && (is_int($value) || (is_string($value) && ctype_digit($value))) && (int) $value > 0) {
                $ids[] = (int) $value;
                continue;
            }
            if ($key === 'media_ids' && is_array($value)) {
                foreach ($value as $v) {
                    if ((is_int($v) || (is_string($v) && ctype_digit($v))) && (int) $v > 0) {
                        $ids[] = (int) $v;
                    }
                }
                continue;
            }
            self::walk($value, $ids, $srcs, $depth + 1);
        }
    }

    /** @return list<string> nilai src dari tag <img> di HTML teks kaya */
    public static function imgSources(string $html): array
    {
        if (!preg_match_all('/<img\b[^>]*?\bsrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $html, $m)) {
            return [];
        }
        $out = [];
        foreach ($m[1] as $i => $a) {
            $src = trim(html_entity_decode($a !== '' ? $a : ($m[2][$i] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($src !== '' && !str_starts_with($src, 'data:')) {
                $out[] = $src;
            }
        }

        return $out;
    }

    /**
     * Alamat <img src> -> kandidat path berkas di disk media (tanpa host, tanpa "/storage/" di depan, tanpa query/anchor).
     * Hanya untuk dicocokkan ke kolom media.path; hasilnya tidak pernah dipakai sebagai alamat.
     */
    public static function pathOf(string $src): ?string
    {
        $p = preg_replace('~^https?://[^/]+~i', '', trim($src)) ?? '';
        $p = preg_replace('~[?#].*$~', '', $p) ?? '';
        $p = ltrim(rawurldecode($p), '/');
        $p = preg_replace('~^storage/~', '', $p) ?? '';

        return $p !== '' && !str_contains($p, '..') && strlen($p) <= 255 ? $p : null;
    }

    /**
     * Pilihan akhir: referensi + data media dari database, urut kemunculan, hanya gambar, maks. MAX_CHOICES.
     *
     * @param array{ids:list<int>,srcs:list<string>} $refs hasil references()
     * @param callable(list<int>):list<array{id:int,url:string,name:string,alt:string,image:bool}> $byIds
     * @param callable(list<string>):list<array{id:int,url:string,name:string,alt:string,image:bool,path:string}> $byPaths
     * @return list<array{id:int,url:string,name:string,alt:string}>
     */
    public static function choices(array $refs, callable $byIds, callable $byPaths, int $max = self::MAX_CHOICES): array
    {
        $found = [];
        foreach ($refs['ids'] !== [] ? $byIds($refs['ids']) : [] as $row) {
            $found['id:' . $row['id']] = $row;
        }
        $paths = array_values(array_filter(array_map([self::class, 'pathOf'], $refs['srcs'])));
        $byPath = [];
        foreach ($paths !== [] ? $byPaths(array_values(array_unique($paths))) : [] as $row) {
            $byPath[$row['path']] = $row;
        }

        $out = [];
        $seen = [];
        $push = function (?array $row) use (&$out, &$seen) {
            if ($row && $row['image'] && !isset($seen[$row['id']])) {
                $seen[$row['id']] = true;
                $out[] = ['id' => (int) $row['id'], 'url' => (string) $row['url'], 'name' => (string) $row['name'], 'alt' => (string) $row['alt']];
            }
        };
        // ID lebih dulu (urut referensi), lalu gambar teks kaya menurut urutan src; urutan antar-kedua-sumber mengikuti daftar masing-masing
        foreach ($refs['ids'] as $id) {
            $push($found['id:' . $id] ?? null);
        }
        foreach ($refs['srcs'] as $src) {
            $p = self::pathOf($src);
            $push($p !== null ? ($byPath[$p] ?? null) : null);
        }

        return array_slice($out, 0, $max);
    }
}
