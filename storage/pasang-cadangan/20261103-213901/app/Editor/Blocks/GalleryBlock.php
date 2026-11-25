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
            Field::toggle('data.card_border', 'Garis tepi kartu logo', ['data.mode', 'logos'], true),
            Field::toggle('data.grayscale', 'Logo hitam-putih (berwarna saat disorot)'),
            Field::toggle('data.hover', 'Efek saat disorot (warna kembali, membesar)', null, true),
            Field::segmented('data.hover_scale', 'Membesar saat disorot', ['none' => 'Tidak', 'sm' => 'Halus', 'md' => 'Sedang', 'lg' => 'Besar'], 'sm'),
            Field::segmented('data.animate', 'Animasi masuk', ['none' => 'Tanpa', 'fade' => 'Muncul', 'up' => 'Naik', 'zoom' => 'Zoom'], 'up'),
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
            'card_border' => true,
            'grayscale' => false,
            'hover' => true,
            'hover_scale' => 'sm',
            'animate' => 'up',
            'lightbox' => true,
            'autoplay' => false,
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

        return [
            'mode' => $mode,
            'layout' => GalleryStyle::layout($data['layout'] ?? null),
            'columns' => GalleryStyle::columns($data['columns'] ?? null),
            'ratio' => GalleryStyle::ratio($data['ratio'] ?? null),
            'follow' => (bool) ($data['follow'] ?? false),
            'height' => GalleryStyle::height($data['height'] ?? null),
            'card_bg' => GalleryStyle::cardBg($data['card_bg'] ?? null),
            'card_border' => (bool) ($data['card_border'] ?? true),
            'grayscale' => (bool) ($data['grayscale'] ?? false),
            'hover' => (bool) ($data['hover'] ?? true),
            'hover_scale' => GalleryStyle::scale($data['hover_scale'] ?? null, $mode),
            'animate' => GalleryStyle::animation($data['animate'] ?? null),
            'lightbox' => (bool) ($data['lightbox'] ?? true),
            'autoplay' => (bool) ($data['autoplay'] ?? false),
            'items' => $items,
        ];
    }
}
