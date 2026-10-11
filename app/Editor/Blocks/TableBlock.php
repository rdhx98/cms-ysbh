<?php

namespace App\Editor\Blocks;

use App\Content\Blocks\BlockSanitizer;
use App\Content\Blocks\TableStyle;
use App\Editor\BlockType;
use App\Editor\Field;

/**
 * Blok Tabel (tipe "table-builder", rilis 34): tabel data (jadwal, daftar mitra, rincian dana, ...) dengan judul kolom, baris, dan sel
 * per bahasa. Sel = teks biasa (di-escape; baris baru, **tebal**, dan tautan https saja yang diberi struktur: App\Content\Blocks\TableStyle).
 * Kisi isi diedit di <x-editor.table-grid> (resources/js/editor.js: tablePanel); pilihan tampilan memakai kontrol biasa.
 * Tampilan publik: blocks/render/table-builder.blade.php (bisa digeser mendatar di layar kecil).
 *
 * Bentuk data: columns[] {id, label{id,en}, align}; rows[] {id, cells[] (SEJAJAR dengan columns, tiap sel peta bahasa)}.
 * Pembersih menjaga keduanya sejajar: sel berlebih dibuang, sel yang kurang diisi kosong.
 */
final class TableBlock implements BlockModule
{
    public const MAX_COLUMNS = TableStyle::MAX_COLUMNS;
    public const MAX_ROWS = TableStyle::MAX_ROWS;

    public static function definition(): BlockType
    {
        $dot = fn (string $value, string $title, string $bg, bool $ring = false) => ['value' => $value, 'title' => $title, 'dot' => $bg, 'dot_ring' => $ring];

        return new BlockType('table-builder', 'Tabel', 'table', 'block', [
            Field::i18n('data.caption', 'Judul tabel (opsional)'),
            Field::i18n('data.note', 'Catatan atau sumber di bawah tabel (opsional)'),
            Field::toggle('data.header', 'Baris judul kolom'),
            Field::toggle('data.first_col_header', 'Kolom pertama sebagai judul baris (tebal)'),
            Field::toggle('data.striped', 'Baris berselang-seling (zebra)'),
            Field::segmented('data.border', 'Garis', ['rows' => 'Antar baris', 'grid' => 'Kotak', 'none' => 'Tanpa'], 'rows'),
            Field::segmented('data.density', 'Kerapatan', ['compact' => 'Rapat', 'normal' => 'Normal', 'relaxed' => 'Lega'], 'normal'),
            Field::segmented('data.color', 'Warna baris judul', [
                $dot('foresty', 'Foresty', 'bg-foresty'),
                $dot('coral', 'Coral', 'bg-coral'),
                $dot('aurum', 'Aurum', 'bg-aurum'),
                $dot('charcoal', 'Charcoal', 'bg-charcoal'),
                $dot('light', 'Abu muda', 'bg-gray-100', true),
            ], 'foresty', ['data.header', true]),
        ], defaults: [
            'caption' => '@locales',
            'note' => '@locales',
            'header' => true,
            'first_col_header' => false,
            'striped' => true,
            'border' => 'rows',
            'density' => 'normal',
            'color' => 'foresty',
            'columns' => [
                ['id' => '@id', 'label' => '@locales', 'align' => 'left'],
                ['id' => '@id', 'label' => '@locales', 'align' => 'left'],
                ['id' => '@id', 'label' => '@locales', 'align' => 'left'],
            ],
            'rows' => [
                ['id' => '@id', 'cells' => ['@locales', '@locales', '@locales']],
                ['id' => '@id', 'cells' => ['@locales', '@locales', '@locales']],
            ],
        ], component: 'editor.table-grid', componentFirst: true);
    }

    public static function placement(): array
    {
        return ['group' => 'Konten', 'root' => true, 'columns' => true];
    }

    public static function sanitize(array $data, array $locales): array
    {
        $columns = [];
        foreach (array_slice(array_values(array_filter(is_array($data['columns'] ?? null) ? $data['columns'] : [], 'is_array')), 0, self::MAX_COLUMNS) as $c) {
            $columns[] = [
                'id' => BlockSanitizer::itemId($c['id'] ?? null),
                'label' => BlockSanitizer::singleLines($c['label'] ?? [], $locales, 120),
                'align' => TableStyle::alignOf($c['align'] ?? null),
            ];
        }

        $width = count($columns);
        $rows = [];
        foreach (array_slice(array_values(array_filter(is_array($data['rows'] ?? null) ? $data['rows'] : [], 'is_array')), 0, self::MAX_ROWS) as $r) {
            $source = is_array($r['cells'] ?? null) ? array_values($r['cells']) : [];
            $cells = [];
            for ($i = 0; $i < $width; $i++) {
                $cells[] = BlockSanitizer::multiLines($source[$i] ?? [], $locales, TableStyle::MAX_CELL);
            }
            $rows[] = ['id' => BlockSanitizer::itemId($r['id'] ?? null), 'cells' => $cells];
        }

        return [
            'caption' => BlockSanitizer::singleLines($data['caption'] ?? [], $locales, 200),
            'note' => BlockSanitizer::singleLines($data['note'] ?? [], $locales, 400),
            'header' => (bool) ($data['header'] ?? true),
            'first_col_header' => (bool) ($data['first_col_header'] ?? false),
            'striped' => (bool) ($data['striped'] ?? true),
            'border' => TableStyle::border($data['border'] ?? null),
            'density' => TableStyle::density($data['density'] ?? null),
            'color' => TableStyle::color($data['color'] ?? null),
            'columns' => $columns,
            'rows' => $rows,
        ];
    }
}
