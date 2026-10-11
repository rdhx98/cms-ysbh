<?php

namespace App\Editor\Blocks;

use App\Content\Blocks\ArticleCards;
use App\Content\Blocks\BlockSanitizer;
use App\Editor\BlockType;
use App\Editor\Field;

/**
 * Blok Artikel Terbaru (tipe "latest-articles-builder"): artikel terbit dari basis data, dalam dua bagian — UNGGULAN (0-6 artikel, kisi bento;
 * yang disematkan di blok lebih dulu, sisanya dilengkapi artikel terbaru yang sesuai saringan) dan DAFTAR (kartu seragam: kolom atau baris,
 * jumlah tetap atau berhalaman dengan tombol "Halaman berikutnya"). Sumber: semua artikel atau beberapa kategori (rilis 48).
 *
 * BLOK DINAMIS: isinya dibaca dari basis data saat halaman dirender (App\Content\PublicLookup::blockArticles); data blok hanya menyimpan
 * pengaturan + pilihan id (label di pilihan hanya tampilan inspektur). Tampilan publik: blocks/render/latest-articles-builder.blade.php.
 * Blok lama (tanpa kolom baru) tampil persis seperti sebelumnya: semua artikel, tanpa unggulan, jumlah tetap, kolom.
 *
 * definition() TIDAK boleh menjalankan kueri basis data (dipanggil juga di landing lewat Modules); pencarian kategori/artikel di inspektur
 * dilakukan aksi server searchLinkTargets (trait SearchesLinkTargets di aplikasi CMS).
 */
final class LatestArticlesBlock implements BlockModule
{
    private const LINK_DEFAULT = ['kind' => 'url', 'ref' => '', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false];

    public static function definition(): BlockType
    {
        $featured = ['1', '2', '3', '4', '5', '6'];

        return new BlockType('latest-articles-builder', 'Artikel Terbaru', 'newspaper', 'block', [
            Field::i18n('data.title', 'Judul bagian (opsional)'),
            Field::segmented('data.source', 'Sumber artikel', ['all' => 'Semua artikel', 'categories' => 'Kategori tertentu'], 'all'),
            Field::picker('data.categories', 'Kategori yang dimuat', 'category', 20, ['data.source', 'categories'], 'Cari kategori…', 'Artikel dari kategori mana pun di daftar ini dimuat. Kosong = tidak ada artikel.'),
            // bentuk DAFTAR: nilai tetap teks ('6'); bentuk peta membuat PHP mengubah kunci menjadi angka
            Field::segmented('data.featured_count', 'Artikel unggulan (kisi di atas)', ['0', '1', '2', '3', '4', '5', '6'], '0'),
            Field::picker('data.pinned', 'Sematkan artikel (tampil paling awal)', 'article', 6, ['data.featured_count', $featured], 'Cari judul artikel…', 'Sematan tidak ikut saringan kategori. Kursi unggulan yang tersisa diisi artikel terbaru yang sesuai sumber di atas.'),
            Field::toggle('data.mirror', 'Balik kiri-kanan (kisi unggulan)', ['data.featured_count', ['2', '3', '4', '5', '6']]),
            Field::segmented('data.list_layout', 'Daftar di bawahnya', ['cols' => 'Kolom', 'rows' => 'Baris'], 'cols'),
            Field::segmented('data.columns', 'Kolom (desktop)', ['2', '3', '4'], '3', ['data.list_layout', 'cols']),
            Field::segmented('data.paging', 'Penomoran', ['limit' => 'Jumlah tetap', 'pages' => 'Halaman berikutnya'], 'limit'),
            Field::segmented('data.limit', 'Jumlah artikel di daftar (per halaman)', ['3', '6', '9', '12'], '3'),
            Field::toggle('data.show_image', 'Tampilkan gambar sampul'),
            Field::toggle('data.show_category', 'Tampilkan kategori'),
            Field::toggle('data.show_date', 'Tampilkan tanggal'),
            Field::toggle('data.show_excerpt', 'Tampilkan ringkasan'),
            Field::i18n('data.all_label', 'Teks tautan "lihat semua" (opsional)'),
            Field::link('data.all_link', 'Tujuan "lihat semua" (mis. halaman daftar artikel)'),
        ], defaults: [
            'title' => '@locales',
            'source' => 'all',
            'categories' => [],
            'featured_count' => '0',
            'pinned' => [],
            'mirror' => false,
            'list_layout' => 'cols',
            'columns' => '3',
            'paging' => 'limit',
            'limit' => '3',
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
            'source' => ArticleCards::source($data['source'] ?? null),
            'categories' => ArticleCards::picks($data['categories'] ?? null, ArticleCards::MAX_CATEGORIES),
            'featured_count' => ArticleCards::featuredCount($data['featured_count'] ?? null),
            'pinned' => ArticleCards::picks($data['pinned'] ?? null, ArticleCards::MAX_PINNED),
            'mirror' => (bool) ($data['mirror'] ?? false),
            'list_layout' => ArticleCards::listLayout($data['list_layout'] ?? null),
            'columns' => ArticleCards::columns($data['columns'] ?? null),
            'paging' => ArticleCards::paging($data['paging'] ?? null),
            'limit' => ArticleCards::limit($data['limit'] ?? null),
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
