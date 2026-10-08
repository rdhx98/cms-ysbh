<?php

namespace App\Content\Blocks;

use App\Content\Links\LinkResolver;

/**
 * Menyiapkan butir galeri untuk tampilan: gambar dari ID media (model Media), teks alternatif, tautan, dan butir tak lengkap. Murni:
 * data berkas dilewatkan sebagai larik (hasil FileInfo::lookup) dan pemecah tautan sebagai objek, jadi seluruh aturan bisa diuji tanpa database.
 *
 * Di situs publik, butir tanpa gambar sah (belum dipilih, sudah dihapus, atau bukan berjenis gambar) DILEWATI. Di kanvas ($canvas)
 * tetap ditampilkan dan ditandai belum lengkap. Tautan yang gagal dipecah (mis. halaman belum online) TIDAK membuang gambarnya
 * (logo tetap tampil, hanya tanpa tautan); di kanvas ditandai.
 */
final class GalleryList
{
    /**
     * @param array $data  data blok yang SUDAH dibersihkan (GalleryBlock::sanitize)
     * @param array<int, array{url:string,name:string,mime:string,size:int}> $files hasil FileInfo::lookup
     * @return list<array{id:string,src:?string,alt:string,caption:string,href:?string,newTab:bool,incomplete:bool,linkIssue:bool,note:string}>
     */
    public static function prepare(array $data, array $files, LinkResolver $resolver, string $lang, bool $canvas = false): array
    {
        $out = [];
        foreach (array_values($data['items'] ?? []) as $item) {
            $mediaId = (int) ($item['image']['media_id'] ?? 0);
            $file = $mediaId > 0 ? ($files[$mediaId] ?? null) : null;
            $isImage = $file !== null && str_starts_with(strtolower($file['mime'] ?? ''), 'image/');
            if (!$isImage && !$canvas) {
                continue;
            }

            $link = is_array($item['link'] ?? null) ? $item['link'] : [];
            $hasLink = (string) ($link['ref'] ?? '') !== '' || ($link['media_id'] ?? null) !== null;
            $href = $hasLink && $isImage ? $resolver->url($link, $lang) : null;

            $out[] = [
                'id' => (string) ($item['id'] ?? ''),
                'src' => $isImage ? $file['url'] : null,
                'alt' => self::pick($item['title'] ?? [], $lang) ?: ($file ? self::fromFilename($file['name']) : ''),
                'caption' => self::pick($item['caption'] ?? [], $lang),
                'href' => $href,
                'newTab' => $href !== null && (bool) ($link['new_tab'] ?? false) && !preg_match('/^(tel:|mailto:|#)/', $href),
                'incomplete' => !$isImage,
                'linkIssue' => $hasLink && $isImage && $href === null,
                'note' => !$isImage ? ($mediaId > 0 ? ($file ? 'Berkas yang dipilih bukan gambar.' : 'Gambar tidak ditemukan (mungkin sudah dihapus).') : 'Gambar belum dipilih.') : '',
            ];
        }

        return $out;
    }

    /** "logo-mitra_utama.png" -> "logo mitra utama" (cadangan bila teks alternatif kosong, supaya gambar bertautan tetap punya nama). */
    public static function fromFilename(string $name): string
    {
        $stem = pathinfo($name, PATHINFO_FILENAME);
        $stem = trim((string) preg_replace('/[\s_.\-]+/u', ' ', $stem));

        return mb_substr($stem, 0, 80);
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
