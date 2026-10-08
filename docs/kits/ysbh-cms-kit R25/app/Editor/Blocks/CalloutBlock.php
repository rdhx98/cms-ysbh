<?php

namespace App\Editor\Blocks;

use App\Content\Blocks\BlockSanitizer;
use App\Content\Blocks\CalloutStyle;
use App\Editor\BlockType;
use App\Editor\Field;

/**
 * Blok Callout / Catatan penting (tipe "callout-builder"): kotak berwarna untuk informasi, saran, peringatan, atau bahaya (mis. peringatan
 * medis), dengan judul opsional, isi teks biasa (paragraf, daftar "- ", tautan https://), ikon, dan satu tautan aksi (mis. "Hubungi 119").
 * Tampilan publik: blocks/render/callout-builder.blade.php. Isi memakai pengubah teks yang sama dengan FAQ (FaqText), jadi HTML dari
 * penulis tidak pernah lolos. Tautan aksi melewati aturan keamanan yang SAMA dengan tombol (hanya http(s), mailto, tel, /jalur, #anchor).
 */
final class CalloutBlock implements BlockModule
{
    private const LINK_DEFAULT = ['kind' => 'url', 'ref' => '', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false];

    public static function definition(): BlockType
    {
        return new BlockType('callout-builder', 'Callout / Catatan', 'megaphone', 'block', [
            Field::i18n('data.title', 'Judul (opsional)'),
            Field::i18n('data.body', 'Isi', true, 5),
            Field::i18n('data.action.label', 'Teks tautan (opsional)'),
            Field::link('data.action.link', 'Tujuan tautan'),
            Field::toggle('data.action.link.new_tab', 'Buka tautan di tab baru'),
            Field::segmented('data.tone', 'Jenis', [
                'info' => 'Info', 'success' => 'Sukses', 'warning' => 'Peringatan', 'danger' => 'Bahaya', 'neutral' => 'Catatan', 'brand' => 'Merek',
            ], 'info'),
            Field::segmented('data.style', 'Gaya', ['soft' => 'Lembut', 'outline' => 'Garis', 'solid' => 'Penuh'], 'soft'),
            Field::toggle('data.show_icon', 'Tampilkan ikon'),
            // kosong = ikon bawaan sesuai jenis
            Field::icon('data.icon', 'Ikon pilihan (kosong = bawaan sesuai jenis)', '', true),
        ], defaults: [
            'tone' => 'info',
            'style' => 'soft',
            'show_icon' => true,
            'icon' => '',
            'title' => '@locales',
            'body' => '@locales',
            'action' => ['label' => '@locales', 'link' => self::LINK_DEFAULT],
        ]);
    }

    public static function placement(): array
    {
        return ['group' => 'Konten', 'root' => true, 'columns' => true];
    }

    public static function sanitize(array $data, array $locales): array
    {
        $action = is_array($data['action'] ?? null) ? $data['action'] : [];
        // tautan aksi dibersihkan dengan aturan yang SAMA dengan tombol (satu-satunya tempat aturan itu didefinisikan)
        $link = BlockSanitizer::buttonBuilder(['buttons' => [['link' => is_array($action['link'] ?? null) ? $action['link'] : []]]], $locales)['buttons'][0]['link'];

        return [
            'tone' => CalloutStyle::tone($data['tone'] ?? null),
            'style' => CalloutStyle::style($data['style'] ?? null),
            'show_icon' => (bool) ($data['show_icon'] ?? true),
            'icon' => is_string($data['icon'] ?? null) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $data['icon']) && strlen($data['icon']) <= 40 ? $data['icon'] : '',
            'title' => BlockSanitizer::singleLines($data['title'] ?? [], $locales, 150),
            'body' => BlockSanitizer::multiLines($data['body'] ?? [], $locales, 3000),
            'action' => ['label' => BlockSanitizer::singleLines($action['label'] ?? [], $locales, 60), 'link' => $link],
        ];
    }
}
