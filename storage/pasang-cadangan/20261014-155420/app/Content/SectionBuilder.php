<?php

namespace App\Content;

/**
 * Mengelompokkan blok menjadi SEKSI dan merakit daftar isi (TOC). Ekstraksi persis dari mesin render page-preview:
 * satu tempat untuk halaman publik, pratinjau, kanvas editor, dan isi artikel/snippet (diuji terhadap salinan logika aslinya).
 *
 * Aturan (tidak boleh berubah diam-diam):
 *  - blok bertipe section_divider (juga ditulis section-divider) TIDAK dirender sendiri; ia menutup seksi berjalan dan membuka seksi baru
 *    dengan latar/warna teks/padding/anchor dari datanya;
 *  - seksi tanpa blok dibuang; seksi awal (sebelum divider pertama) bawaan: bg-paper, text-charcoal, py-16 sm:py-24;
 *  - blok ber-anchor yang berjudul (heading: data.text; divider: data.title) masuk daftar isi;
 *  - ID di urutan yang tidak punya blok dilewati.
 */
final class SectionBuilder
{
    public const DEFAULT_SECTION = [
        'bgClass' => 'bg-paper',
        'textClass' => 'text-charcoal',
        'padding' => 'py-16 sm:py-24',
        'anchor' => '',
    ];

    /**
     * @param array<string,array> $blocks id => blok
     * @param string[]            $order  urutan ID tingkat atas
     * @return array{sections: list<array{bgClass:string,textClass:string,padding:string,anchor:string,dividerId:?string,blocks:list<string>}>, toc: list<array{title:string,anchor:string}>, tocPosition:string}
     */
    public static function group(array $blocks, array $order, array $settings, string $lang): array
    {
        $sections = [];
        $toc = [];
        $tocPosition = $settings['toc_position'] ?? 'right';

        $current = self::DEFAULT_SECTION + ['dividerId' => null, 'blocks' => []];

        foreach ($order as $blockId) {
            if (!isset($blocks[$blockId]) || !is_array($blocks[$blockId])) {
                continue;
            }
            $block = $blocks[$blockId];
            $type = str_replace('-', '_', (string) ($block['type'] ?? ''));

            if (!empty($block['anchor'])) {
                $title = '';
                if ($type === 'heading') {
                    $title = strip_tags(self::text($block['data']['text'] ?? '', $lang));
                } elseif ($type === 'section_divider') {
                    $title = strip_tags(self::text($block['data']['title'] ?? '', $lang));
                }
                if (empty($title) && !empty($block['data']['title'])) {
                    $title = strip_tags(self::text($block['data']['title'], $lang));
                }
                if (!empty(trim($title))) {
                    $toc[] = ['title' => trim($title), 'anchor' => $block['anchor']];
                }
            }

            if ($type === 'section_divider') {
                if (count($current['blocks']) > 0) {
                    $sections[] = $current;
                }
                $current = [
                    'bgClass' => $block['data']['background'] ?? 'bg-paper',
                    'textClass' => $block['data']['text_color'] ?? 'text-gray-900',
                    'padding' => $block['data']['padding'] ?? 'py-16 sm:py-24',
                    'anchor' => $block['anchor'] ?? '',
                    'dividerId' => (string) $blockId,
                    'blocks' => [],
                ];
                continue;
            }

            $current['blocks'][] = $blockId;
        }

        if (count($current['blocks']) > 0) {
            $sections[] = $current;
        }

        return ['sections' => $sections, 'toc' => $toc, 'tocPosition' => $tocPosition];
    }

    /**
     * Banner atas (rilis 27): ID blok PERTAMA yang dirender di halaman (blok pertama seksi pertama yang punya blok; pemisah seksi tidak dihitung)
     * bila blok itu gambar berlebar penuh (type image, data.width "w-screen"); selain itu null. Daftar isi menunggu sampai banner ini terlewati.
     * Hanya blok gambar: blok layar penuh lain (video, kartu) tetap memakai aturan tumpang tindih biasa.
     *
     * @param list<array{blocks:list<string>}> $sections hasil group()['sections']
     * @param array<string,array>              $blocks   id => blok
     */
    public static function topBanner(array $sections, array $blocks): ?string
    {
        foreach ($sections as $section) {
            $first = $section['blocks'][0] ?? null;
            if ($first === null) {
                continue;
            }
            $block = $blocks[$first] ?? null;
            $data = is_array($block) && is_array($block['data'] ?? null) ? $block['data'] : [];

            return is_array($block) && str_replace('_', '-', strtolower((string) ($block['type'] ?? ''))) === 'image' && ($data['width'] ?? null) === 'w-screen'
                ? (string) $first
                : null;
        }

        return null;
    }

    /** Teks dari bidang yang bisa berupa string atau peta bahasa: bahasa diminta, lalu id, lalu elemen pertama. */
    private static function text(mixed $field, string $lang): string
    {
        if (is_array($field)) {
            $value = $field[$lang] ?? ($field['id'] ?? (reset($field) ?: ''));
        } else {
            $value = $field ?? '';
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
