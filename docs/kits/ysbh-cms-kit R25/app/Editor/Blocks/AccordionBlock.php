<?php

namespace App\Editor\Blocks;

use App\Content\Blocks\AccordionStyle;
use App\Content\Blocks\BlockSanitizer;
use App\Editor\BlockType;
use App\Editor\Field;

/**
 * Blok Akordion / FAQ (tipe "accordion-builder"). Tampilan publik: resources/views/components/blocks/render/accordion-builder.blade.php.
 * Jawaban = teks biasa (paragraf, daftar "- ", tautan https://), di-escape saat dicetak: lihat App\Content\Blocks\FaqText.
 */
final class AccordionBlock implements BlockModule
{
    public const MAX_ITEMS = 30;

    private const ITEM_DEFAULTS = ['id' => '@id', 'question' => '@locales', 'answer' => '@locales'];

    public static function definition(): BlockType
    {
        $dot = fn (string $value, string $title, string $bg) => ['value' => $value, 'title' => $title, 'dot' => $bg, 'dot_ring' => false];

        return new BlockType('accordion-builder', 'Akordion / FAQ', 'message-circle-question', 'block', [
            Field::repeater('data.items', 'Pertanyaan', [
                Field::i18n('question', 'Pertanyaan'),
                Field::i18n('answer', 'Jawaban', true, 5),
            ], self::ITEM_DEFAULTS, self::MAX_ITEMS, 'pertanyaan'),
            Field::segmented('data.style', 'Gaya', ['boxed' => 'Kotak', 'lines' => 'Garis'], 'boxed'),
            Field::segmented('data.color', 'Warna aksen', [
                $dot('foresty', 'Foresty', 'bg-foresty'),
                $dot('coral', 'Coral', 'bg-coral'),
                $dot('aurum', 'Aurum', 'bg-aurum'),
                $dot('charcoal', 'Charcoal', 'bg-charcoal'),
            ], 'foresty'),
            Field::segmented('data.marker', 'Penanda', ['chevron' => 'Panah', 'plus' => 'Plus / minus'], 'chevron'),
            Field::toggle('data.first_open', 'Buka pertanyaan pertama'),
            Field::toggle('data.allow_multiple', 'Boleh membuka beberapa sekaligus'),
            Field::toggle('data.schema', 'Tandai untuk mesin pencari (FAQPage). Aktifkan pada SATU blok per halaman'),
        ], defaults: [
            'style' => 'boxed',
            'color' => 'foresty',
            'marker' => 'chevron',
            'first_open' => false,
            'allow_multiple' => false,
            'schema' => false,
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
            $items[] = [
                'id' => BlockSanitizer::itemId($item['id'] ?? null),
                'question' => BlockSanitizer::singleLines($item['question'] ?? [], $locales, 300),
                'answer' => BlockSanitizer::multiLines($item['answer'] ?? [], $locales, 5000),
            ];
        }

        return [
            'style' => AccordionStyle::style($data['style'] ?? null),
            'color' => AccordionStyle::color($data['color'] ?? null),
            'marker' => in_array($data['marker'] ?? null, AccordionStyle::MARKERS, true) ? $data['marker'] : 'chevron',
            'first_open' => (bool) ($data['first_open'] ?? false),
            'allow_multiple' => (bool) ($data['allow_multiple'] ?? false),
            'schema' => (bool) ($data['schema'] ?? false),
            'items' => $items,
        ];
    }
}
