<?php

namespace App\Editor\Blocks;

use App\Content\Blocks\BlockSanitizer;
use App\Content\Blocks\SimpleTextStyle;
use App\Editor\BlockType;
use App\Editor\Field;

/**
 * Blok Paragraf Biasa (tipe "simple-text", rilis 92): teks tanpa editor Tiptap, untuk penulis awam.
 * Isi = teks biasa per bahasa (baris kosong = paragraf baru, baris "- " = daftar, https://… = tautan; lihat FaqText).
 * Ukuran, rata, dan warna diatur SATU kali untuk semua bahasa (bukan per bahasa seperti Tiptap).
 * Tampilan publik: resources/views/components/blocks/render/simple-text.blade.php. Tidak ada HTML dari penulis yang lolos.
 */
final class SimpleTextBlock implements BlockModule
{
    public static function definition(): BlockType
    {
        return new BlockType('simple-text', 'Paragraf Biasa', 'file-text', 'block', [
            Field::i18n('data.text', 'Isi', true, 8),
            Field::segmented('data.size', 'Ukuran huruf', ['sm' => 'Kecil', 'md' => 'Normal', 'lg' => 'Besar', 'xl' => 'Sangat besar'], 'md'),
            Field::segmented('data.align', 'Rata teks', ['left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan'], 'left'),
            Field::segmented('data.color', 'Warna teks', [
                ['value' => '', 'label' => 'Ikuti seksi'],
                ['value' => 'muted', 'label' => 'Lembut'],
                ['value' => 'forest', 'label' => 'Hijau'],
                ['value' => 'coral', 'label' => 'Coral'],
            ], ''),
        ], defaults: [
            'text' => '@locales',
            'size' => 'md',
            'align' => 'left',
            'color' => '',
        ]);
    }

    public static function placement(): array
    {
        return ['group' => 'Konten', 'root' => true, 'columns' => true];
    }

    public static function sanitize(array $data, array $locales): array
    {
        return [
            'text' => BlockSanitizer::multiLines($data['text'] ?? [], $locales, 10000),
            'size' => SimpleTextStyle::size($data['size'] ?? null),
            'align' => SimpleTextStyle::align($data['align'] ?? null),
            'color' => SimpleTextStyle::color($data['color'] ?? null),
        ];
    }
}
