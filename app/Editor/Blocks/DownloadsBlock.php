<?php

namespace App\Editor\Blocks;

use App\Content\Blocks\BlockSanitizer;
use App\Content\Blocks\DownloadsStyle;
use App\Editor\BlockType;
use App\Editor\Field;

/**
 * Blok Daftar Unduhan (tipe "downloads-builder"): berkas dari File Manager dengan judul, tahun, dan kategori; ukuran dan jenis berkas
 * diambil OTOMATIS dari model Media. Dipakai mis. untuk Transparansi Laporan. Tampilan publik: blocks/render/downloads-builder.blade.php.
 * Tautan unduhan SELALU dibentuk dari media_id (App\Content\Blocks\FileInfo::lookup); url dalam data hanya untuk tampilan nama di inspektur.
 */
final class DownloadsBlock implements BlockModule
{
    public const MAX_ITEMS = 60;

    private const FILE_DEFAULT = ['media_id' => null, 'url' => ''];

    private const ITEM_DEFAULTS = ['id' => '@id', 'title' => '@locales', 'year' => '', 'category' => '@locales', 'file' => self::FILE_DEFAULT];

    public static function definition(): BlockType
    {
        $dot = fn (string $value, string $title, string $bg) => ['value' => $value, 'title' => $title, 'dot' => $bg, 'dot_ring' => false];

        return new BlockType('downloads-builder', 'Daftar Unduhan', 'download', 'block', [
            Field::repeater('data.items', 'Berkas', [
                Field::i18n('title', 'Judul'),
                Field::text('year', 'Tahun', ['placeholder' => '2025', 'maxlength' => 4]),
                Field::i18n('category', 'Kategori (opsional)'),
                // '' = tanpa filter jenis berkas (nilai allowedFileType untuk dokumen tidak diketahui, jadi tidak ditebak)
                Field::media('file', 'Pilih berkas dari File Manager', ''),
            ], self::ITEM_DEFAULTS, self::MAX_ITEMS, 'berkas'),
            Field::segmented('data.layout', 'Tampilan', ['list' => 'Daftar', 'cards' => 'Kartu'], 'list'),
            Field::segmented('data.group_by', 'Kelompokkan', ['none' => 'Tidak', 'year' => 'Tahun', 'category' => 'Kategori'], 'none'),
            Field::segmented('data.sort', 'Urutan', ['manual' => 'Manual', 'year_desc' => 'Tahun terbaru', 'title_asc' => 'Judul A–Z'], 'manual'),
            Field::segmented('data.color', 'Warna aksen', [
                $dot('foresty', 'Foresty', 'bg-foresty'),
                $dot('coral', 'Coral', 'bg-coral'),
                $dot('aurum', 'Aurum', 'bg-aurum'),
                $dot('charcoal', 'Charcoal', 'bg-charcoal'),
            ], 'foresty'),
            Field::toggle('data.show_meta', 'Tampilkan tahun, jenis, dan ukuran berkas'),
            Field::toggle('data.new_tab', 'Buka di tab baru'),
        ], defaults: [
            'layout' => 'list',
            'group_by' => 'none',
            'sort' => 'manual',
            'color' => 'foresty',
            'show_meta' => true,
            'new_tab' => true,
            'items' => [self::ITEM_DEFAULTS],
        ]);
    }

    public static function placement(): array
    {
        return ['group' => 'Konten', 'root' => true, 'columns' => true];
    }

    public static function sanitize(array $data, array $locales): array
    {
        $items = [];
        foreach (array_slice(array_values(array_filter($data['items'] ?? [], 'is_array')), 0, self::MAX_ITEMS) as $item) {
            $file = is_array($item['file'] ?? null) ? $item['file'] : [];
            $mediaId = $file['media_id'] ?? null;
            $mediaId = (is_int($mediaId) || (is_string($mediaId) && ctype_digit($mediaId))) && (int) $mediaId > 0 ? (int) $mediaId : null;
            $year = is_scalar($item['year'] ?? null) ? trim((string) $item['year']) : '';

            $items[] = [
                'id' => BlockSanitizer::itemId($item['id'] ?? null),
                'title' => BlockSanitizer::singleLines($item['title'] ?? [], $locales, 200),
                'year' => preg_match('/^\d{4}$/D', $year) && (int) $year >= 1900 && (int) $year <= 2100 ? $year : '',
                'category' => BlockSanitizer::singleLines($item['category'] ?? [], $locales, 80),
                'file' => [
                    'media_id' => $mediaId,
                    // hanya untuk menampilkan nama berkas di inspektur; TIDAK pernah menjadi tautan
                    'url' => $mediaId ? mb_substr(trim(preg_replace('/[\x00-\x1f\x7f]/', '', is_scalar($file['url'] ?? null) ? (string) $file['url'] : '') ?? ''), 0, 255) : '',
                ],
            ];
        }

        return [
            'layout' => DownloadsStyle::layout($data['layout'] ?? null),
            'group_by' => in_array($data['group_by'] ?? null, ['none', 'year', 'category'], true) ? $data['group_by'] : 'none',
            'sort' => in_array($data['sort'] ?? null, ['manual', 'year_desc', 'title_asc'], true) ? $data['sort'] : 'manual',
            'color' => DownloadsStyle::color($data['color'] ?? null),
            'show_meta' => (bool) ($data['show_meta'] ?? true),
            'new_tab' => (bool) ($data['new_tab'] ?? true),
            'items' => $items,
        ];
    }
}
