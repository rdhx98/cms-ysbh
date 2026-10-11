<?php

namespace App\Editor\Blocks;

use App\Content\Blocks\ArticleCards;
use App\Content\Blocks\BlockSanitizer;
use App\Editor\BlockType;
use App\Editor\Field;

/**
 * Blok Artikel Terbaru (tipe "latest-articles-builder"): kartu artikel terbit terbaru (sampul, kategori, tanggal, judul, ringkasan) dengan
 * tautan opsional "lihat semua". BLOK DINAMIS: isinya dibaca dari basis data saat halaman dirender (App\Content\PublicLookup), jadi data blok
 * hanya menyimpan pengaturan tampilan. Tampilan publik: blocks/render/latest-articles-builder.blade.php.
 *
 * definition() TIDAK boleh menjalankan kueri basis data (dipanggil juga di landing lewat Modules), karena itu tidak ada filter kategori
 * dengan daftar dinamis di inspektur.
 */
final class LatestArticlesBlock implements BlockModule
{
    private const LINK_DEFAULT = ['kind' => 'url', 'ref' => '', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false];

    public static function definition(): BlockType
    {
        return new BlockType('latest-articles-builder', 'Artikel Terbaru', 'newspaper', 'block', [
            Field::i18n('data.title', 'Judul bagian (opsional)'),
            // bentuk DAFTAR: nilai tetap teks ('6'); bentuk peta membuat PHP mengubah kunci menjadi angka
            Field::segmented('data.limit', 'Jumlah artikel', ['3', '6', '9', '12'], '3'),
            Field::segmented('data.columns', 'Kolom (desktop)', ['2', '3', '4'], '3'),
            Field::toggle('data.show_image', 'Tampilkan gambar sampul'),
            Field::toggle('data.show_category', 'Tampilkan kategori'),
            Field::toggle('data.show_date', 'Tampilkan tanggal'),
            Field::toggle('data.show_excerpt', 'Tampilkan ringkasan'),
            Field::i18n('data.all_label', 'Teks tautan "lihat semua" (opsional)'),
            Field::link('data.all_link', 'Tujuan "lihat semua" (mis. halaman daftar artikel)'),
        ], defaults: [
            'title' => '@locales',
            'limit' => '3',
            'columns' => '3',
            'show_image' => true,
            'show_category' => true,
            'show_date' => true,
            'show_excerpt' => true,
            'all_label' => '@locales',
            'all_link' => self::LINK_DEFAULT,
        ]);
    }

    public static function placement(): array
    {
        return ['group' => 'Konten', 'root' => true, 'columns' => true];
    }

    public static function sanitize(array $data, array $locales): array
    {
        return [
            'title' => BlockSanitizer::singleLines($data['title'] ?? [], $locales, 120),
            'limit' => ArticleCards::limit($data['limit'] ?? null),
            'columns' => ArticleCards::columns($data['columns'] ?? null),
            'show_image' => (bool) ($data['show_image'] ?? true),
            'show_category' => (bool) ($data['show_category'] ?? true),
            'show_date' => (bool) ($data['show_date'] ?? true),
            'show_excerpt' => (bool) ($data['show_excerpt'] ?? true),
            'all_label' => BlockSanitizer::singleLines($data['all_label'] ?? [], $locales, 60),
            // tautan dibersihkan dengan aturan yang SAMA dengan tombol
            'all_link' => BlockSanitizer::buttonBuilder(['buttons' => [['link' => is_array($data['all_link'] ?? null) ? $data['all_link'] : []]]], $locales)['buttons'][0]['link'],
        ];
    }
}
