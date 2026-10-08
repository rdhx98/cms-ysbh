<?php

namespace App\Content\Blocks;

/**
 * Menyiapkan daftar unduhan untuk tampilan: memilih teks sesuai bahasa, menyusun berkas dari ID media, mengurutkan, dan mengelompokkan.
 * Murni: data berkas (url, nama, mime, ukuran) dilewatkan sebagai larik, jadi seluruh aturan bisa diuji tanpa database.
 *
 * Di situs publik, butir tanpa judul atau tanpa berkas (belum dipilih / sudah dihapus) DILEWATI. Di kanvas ($canvas) tetap ditampilkan
 * dan ditandai belum lengkap, supaya bisa dilihat saat disusun.
 */
final class DownloadList
{
    /**
     * @param array $data  data blok yang SUDAH dibersihkan (DownloadsBlock::sanitize)
     * @param array<int, array{url:string,name:string,mime:string,size:int}> $files hasil FileInfo::lookup
     * @return array{groups: list<array{heading:?string,items:list<array>}>, count:int}
     */
    public static function prepare(array $data, array $files, string $lang, bool $canvas = false): array
    {
        $items = [];
        foreach (array_values($data['items'] ?? []) as $index => $item) {
            $title = self::pick($item['title'] ?? [], $lang);
            $mediaId = (int) ($item['file']['media_id'] ?? 0);
            $file = $mediaId > 0 ? ($files[$mediaId] ?? null) : null;
            $incomplete = $title === '' || $file === null;
            if ($incomplete && !$canvas) {
                continue;
            }

            $type = $file ? FileInfo::type($file['name'], $file['mime']) : ['label' => '', 'kind' => 'file'];
            $items[] = [
                'index' => $index,
                'id' => (string) ($item['id'] ?? ''),
                'title' => $title !== '' ? $title : '(tanpa judul)',
                'year' => ($item['year'] ?? '') !== '' ? (int) $item['year'] : null,
                'category' => self::pick($item['category'] ?? [], $lang),
                'url' => $file['url'] ?? null,
                'type' => $type['label'],
                'kind' => $type['kind'],
                'size' => $file ? FileInfo::size($file['size'], $lang) : '',
                'incomplete' => $incomplete,
                'note' => $file === null ? ($mediaId > 0 ? 'Berkas tidak ditemukan (mungkin sudah dihapus).' : 'Berkas belum dipilih.') : ($title === '' ? 'Judul kosong.' : ''),
            ];
        }

        $items = self::sort($items, (string) ($data['sort'] ?? 'manual'));

        return ['groups' => self::group($items, (string) ($data['group_by'] ?? 'none'), $lang), 'count' => count($items)];
    }

    private static function sort(array $items, string $mode): array
    {
        if ($mode === 'year_desc') {
            usort($items, fn ($a, $b) => [$b['year'] ?? -1, $a['index']] <=> [$a['year'] ?? -1, $b['index']]);
        } elseif ($mode === 'title_asc') {
            usort($items, fn ($a, $b) => strnatcasecmp($a['title'], $b['title']) ?: $a['index'] <=> $b['index']);
        }

        return $items;
    }

    /** @return list<array{heading:?string,items:list<array>}> */
    private static function group(array $items, string $by, string $lang): array
    {
        if (!$items) {
            return [];
        }
        if ($by !== 'year' && $by !== 'category') {
            return [['heading' => null, 'items' => $items]];
        }

        $other = $by === 'year' ? ($lang === 'id' ? 'Tanpa tahun' : 'No year') : ($lang === 'id' ? 'Lainnya' : 'Other');
        $groups = [];
        foreach ($items as $item) {
            $key = $by === 'year' ? ($item['year'] !== null ? (string) $item['year'] : '') : $item['category'];
            $groups[$key]['items'][] = $item;
        }
        if ($by === 'year') {
            // tahun terbaru di atas; "tanpa tahun" paling bawah
            uksort($groups, fn ($a, $b) => ($b === '' ? -1 : (int) $b) <=> ($a === '' ? -1 : (int) $a));
        } elseif (isset($groups[''])) {
            $none = $groups['']; unset($groups['']); $groups[''] = $none; // "Lainnya" paling bawah; kategori lain mengikuti urutan kemunculan
        }

        $out = [];
        foreach ($groups as $key => $g) {
            $out[] = ['heading' => $key === '' ? $other : (string) $key, 'items' => $g['items']];
        }

        return $out;
    }

    private static function pick(array $byLocale, string $lang): string
    {
        foreach ([$lang, 'id', 'en'] as $l) {
            if (($byLocale[$l] ?? '') !== '') {
                return (string) $byLocale[$l];
            }
        }

        return '';
    }
}
