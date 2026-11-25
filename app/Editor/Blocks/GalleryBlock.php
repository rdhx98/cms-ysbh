<?php

namespace App\Editor\Blocks;

use App\Content\Blocks\BlockSanitizer;
use App\Content\Blocks\GalleryStyle;
use App\Editor\BlockType;
use App\Editor\Field;
use App\Editor\PaletteOptions;

/**
 * Blok Galeri / Logo (tipe "gallery-builder"): kumpulan gambar dari File Manager, ditampilkan sebagai grid atau carousel, dengan dua
 * pratinjau bawaan: "Foto" (petak berpotongan, keterangan, dan pembesar/lightbox) dan "Logo mitra" (kotak putih, gambar utuh, opsi
 * hitam-putih sampai disorot). Tiap gambar boleh bertaut (mis. situs mitra). Tampilan publik: blocks/render/gallery-builder.blade.php.
 * Gambar SELALU dibentuk dari media_id lewat model Media; url dalam data hanya untuk tampilan di inspektur. Tautan memakai aturan yang
 * sama dengan tombol.
 */
final class GalleryBlock implements BlockModule
{
    public const MAX_ITEMS = 60;

    private const LINK_DEFAULT = ['kind' => 'url', 'ref' => '', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false];
    private const ITEM_DEFAULTS = ['id' => '@id', 'title' => '@locales', 'caption' => '@locales', 'image' => ['media_id' => null, 'url' => ''], 'link' => self::LINK_DEFAULT];

    public static function definition(): BlockType
    {
        return new BlockType('gallery-builder', 'Galeri / Logo', 'images', 'block', [
            Field::repeater('data.items', 'Gambar', [
                Field::media('image', 'Pilih gambar', 'image'),
                Field::i18n('title', 'Nama gambar / teks alternatif'),
                Field::i18n('caption', 'Keterangan di bawah gambar (opsional)'),
                Field::link('link', 'Tautan (opsional, mis. situs mitra)'),
                Field::toggle('link.new_tab', 'Buka di tab baru'),
            ], self::ITEM_DEFAULTS, self::MAX_ITEMS, 'gambar'),
            Field::segmented('data.mode', 'Jenis', ['photos' => 'Foto', 'logos' => 'Logo mitra'], 'photos'),
            Field::segmented('data.layout', 'Tampilan', ['grid' => 'Grid', 'carousel' => 'Carousel'], 'grid'),
            Field::toggle('data.follow', 'Lebar kartu mengikuti gambar'),
            Field::segmented('data.height', 'Tinggi kartu', ['sm' => 'Kecil', 'md' => 'Sedang', 'lg' => 'Besar'], 'md', ['data.follow', true]),
            // bentuk DAFTAR: nilai tetap teks ('5'); bentuk peta ['5' => '5'] membuat PHP mengubah kunci menjadi angka dan inspektur menulis 5
            Field::segmented('data.columns', 'Kolom (desktop)', ['1', '2', '3', '4', '5', '6'], '3', ['data.follow', false]),
            Field::segmented('data.ratio', 'Rasio foto', ['1:1' => '1:1', '4:3' => '4:3', '3:2' => '3:2', '16:9' => '16:9'], '4:3', ['data.follow', false])->withAnd(['data.mode', 'photos']),
            Field::swatches('data.card_bg', 'Warna latar kartu', PaletteOptions::colors('card_bg_colors'), 'bg-white', 'bg'),
            Field::swatches('data.border_color', 'Warna garis tepi kartu logo', self::borderOptions(), 'border-gray-200', 'preview', ['data.mode', 'logos']),
            Field::toggle('data.grayscale', 'Hitam-putih, berwarna saat disorot', ['data.mode', 'logos']),
            Field::segmented('data.hover_scale', 'Membesar saat disorot', ['none' => 'Tidak', 'sm' => 'Halus', 'md' => 'Sedang', 'lg' => 'Besar'], 'sm'),
            Field::segmented('data.animate', 'Animasi masuk', ['none' => 'Tanpa', 'fade' => 'Muncul', 'up' => 'Naik', 'zoom' => 'Zoom'], 'up'),
            // Keterangan di bawah gambar (rilis 119). Kuncinya "hide_caption" (bukan "show_caption"): blok lama tidak punya kunci ini, dan syarat `when` booleans
            // membaca nilai mentah (kosong = salah), jadi pengatur gaya tetap tampil untuk blok lama yang keterangannya memang tampil.
            Field::toggle('data.hide_caption', 'Sembunyikan keterangan gambar'),
            Field::toggle('data.caption_bold', 'Keterangan tebal', ['data.hide_caption', false]),
            Field::toggle('data.caption_italic', 'Keterangan miring', ['data.hide_caption', false]),
            Field::toggle('data.caption_underline', 'Keterangan bergaris bawah', ['data.hide_caption', false]),
            Field::swatches('data.caption_color', 'Warna keterangan', self::captionColorOptions(), 'text-gray-600', 'text', ['data.hide_caption', false])->withAnd(['data.caption_overlay', false]),
            // Rilis 120: batas baris dan keterangan yang menimpa gambar saat disorot (teksnya putih di atas bilah gelap, jadi pilihan warna disembunyikan).
            Field::segmented('data.caption_lines', 'Batas baris keterangan', ['none' => 'Semua', 'l1' => '1 baris', 'l2' => '2 baris', 'l3' => '3 baris'], 'none', ['data.hide_caption', false]),
            Field::toggle('data.caption_overlay', 'Keterangan menimpa gambar, muncul saat disorot', ['data.hide_caption', false]),
            Field::toggle('data.lightbox', 'Foto bisa diperbesar saat diklik'),
            Field::toggle('data.autoplay', 'Carousel berjalan otomatis'),
        ], defaults: [
            'mode' => 'photos',
            'layout' => 'grid',
            'columns' => '3',
            'ratio' => '4:3',
            'follow' => false,
            'height' => 'md',
            'card_bg' => 'bg-white',
            'border_color' => 'border-gray-200',
            'grayscale' => false,
            'hover_scale' => 'sm',
            'animate' => 'up',
            'hide_caption' => false,
            'caption_bold' => false,
            'caption_italic' => false,
            'caption_underline' => false,
            'caption_color' => 'text-gray-600',
            'caption_lines' => 'none',
            'caption_overlay' => false,
            'lightbox' => true,
            'autoplay' => false,
            'items' => [self::ITEM_DEFAULTS],
        ]);
    }

    /** Pilihan warna garis: abu-abu terang bawaan, lalu palet situs; "Transparan" diberi nama yang lebih jelas. */
    private static function borderOptions(): array
    {
        $options = array_map(
            fn (array $o) => ($o['value'] ?? '') === 'border-transparent' ? ['name' => 'Tanpa garis'] + $o : $o,
            PaletteOptions::colors('card_border_colors'),
        );

        return array_merge([['name' => 'Abu-abu terang (bawaan)', 'value' => 'border-gray-200', 'preview' => 'bg-gray-200', 'hex' => '#E5E7EB', 'group' => 'Dasar']], $options);
    }

    /** Pilihan warna keterangan: abu-abu bawaan, lalu putih dan palet situs. */
    private static function captionColorOptions(): array
    {
        return array_merge([['name' => 'Abu-abu (bawaan)', 'value' => 'text-gray-600', 'preview' => 'bg-white', 'hex' => '#4B5563', 'preview_hex' => '#FFFFFF', 'group' => 'Dasar']], PaletteOptions::colors('text_colors'));
    }

    public static function placement(): array
    {
        return ['group' => 'Konten', 'root' => true, 'columns' => true];
    }

    public static function sanitize(array $data, array $locales): array
    {
        $items = [];
        foreach (array_slice(array_values(array_filter($data['items'] ?? [], 'is_array')), 0, self::MAX_ITEMS) as $item) {
            $image = is_array($item['image'] ?? null) ? $item['image'] : [];
            $mediaId = $image['media_id'] ?? null;
            $mediaId = (is_int($mediaId) || (is_string($mediaId) && ctype_digit($mediaId))) && (int) $mediaId > 0 ? (int) $mediaId : null;

            $items[] = [
                'id' => BlockSanitizer::itemId($item['id'] ?? null),
                'title' => BlockSanitizer::singleLines($item['title'] ?? [], $locales, 150),
                'caption' => BlockSanitizer::singleLines($item['caption'] ?? [], $locales, 300),
                'image' => [
                    'media_id' => $mediaId,
                    // hanya untuk menampilkan nama berkas di inspektur; TIDAK pernah menjadi alamat gambar
                    'url' => $mediaId ? mb_substr(trim(preg_replace('/[\x00-\x1f\x7f]/', '', is_scalar($image['url'] ?? null) ? (string) $image['url'] : '') ?? ''), 0, 255) : '',
                ],
                // tautan dibersihkan dengan aturan yang SAMA dengan tombol
                'link' => BlockSanitizer::buttonBuilder(['buttons' => [['link' => is_array($item['link'] ?? null) ? $item['link'] : []]]], $locales)['buttons'][0]['link'],
            ];
        }

        $mode = GalleryStyle::mode($data['mode'] ?? null);
        // Rilis 113 sempat punya sakelar induk 'hover'; rilis 115 menghapusnya. Yang sempat mematikannya: efek membesar ikut mati (kunci lamanya dibuang).
        $legacyOff = array_key_exists('hover', $data) && !$data['hover'];
        // Rilis 116: sakelar card_border (rilis 113) diganti warna dari palet; yang sempat mematikannya menjadi "Tanpa garis" (border-transparent).
        $border = GalleryStyle::borderColor($data['border_color'] ?? (array_key_exists('card_border', $data) && !$data['card_border'] ? 'border-transparent' : null));

        return [
            'mode' => $mode,
            'layout' => GalleryStyle::layout($data['layout'] ?? null),
            'columns' => GalleryStyle::columns($data['columns'] ?? null),
            'ratio' => GalleryStyle::ratio($data['ratio'] ?? null),
            'follow' => (bool) ($data['follow'] ?? false),
            'height' => GalleryStyle::height($data['height'] ?? null),
            'card_bg' => GalleryStyle::cardBg($data['card_bg'] ?? null),
            'border_color' => $border,
            'grayscale' => (bool) ($data['grayscale'] ?? false),
            'hover_scale' => GalleryStyle::scale($legacyOff ? 'none' : ($data['hover_scale'] ?? null), $mode),
            'animate' => GalleryStyle::animation($data['animate'] ?? null),
            'hide_caption' => (bool) ($data['hide_caption'] ?? false),
            'caption_bold' => (bool) ($data['caption_bold'] ?? false),
            'caption_italic' => (bool) ($data['caption_italic'] ?? false),
            'caption_underline' => (bool) ($data['caption_underline'] ?? false),
            'caption_color' => GalleryStyle::captionColor($data['caption_color'] ?? null),
            'caption_lines' => GalleryStyle::captionLines($data['caption_lines'] ?? null),
            'caption_overlay' => (bool) ($data['caption_overlay'] ?? false),
            'lightbox' => (bool) ($data['lightbox'] ?? true),
            'autoplay' => (bool) ($data['autoplay'] ?? false),
            'items' => $items,
        ];
    }
}
