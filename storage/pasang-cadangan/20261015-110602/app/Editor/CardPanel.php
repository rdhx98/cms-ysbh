<?php

namespace App\Editor;

/**
 * Bahan panel Kartu Builder (rilis 32), satu tempat untuk: kontrol tampilan kartu, kontrol kolom, jenis elemen yang bisa ditambah,
 * dan titik awal kartu. Bentuk data kartu (yang SUDAH ada, ditulis addCardItem() dan kawan-kawan di trait):
 *
 *   data.grid        {cols, margin_bottom}
 *   data.cards[]     {id, container{bg,padding,border_width,border_style,border_color,radius,shadow,hover,align_y,url,url_label},
 *                     layout{type:"row", children[] kolom}}
 *   kolom            {id, type:"column", width (1..12 | "auto"), align, children[] elemen}
 *   elemen           {id, type:"element", elementType, data{content, style}}   (panelnya: BlockRegistry::element($elementType))
 *   container.url    teks: "" | internal://page|article/{slug} | https://… | /jalur (pemilih: <x-editor.url-link>, rilis 39);
 *                    container.url_label = nama tujuan yang dipilih (hanya tampilan di panel)
 *
 * Pilihan diambil dari config('cms.design.*') yang SUDAH ada (card_paddings, card_bg_colors, border_*), supaya tampilan di panel baru
 * identik dengan editor lama.
 */
final class CardPanel
{
    /** Titik awal kartu (kunci = nama preset di trait: cardLayoutPresets()). */
    public const PRESETS = [
        'stack' => 'Tumpuk (1 kolom)',
        'icon-text' => 'Ikon + teks (2 kolom)',
        'document' => 'Dokumen (3 kolom)',
    ];

    /** Lebar kolom yang ditawarkan; tersimpan sebagai angka, atau "auto". */
    public const WIDTHS = [1, 2, 3, 4, 6, 'auto'];

    /** Batas jumlah di satu tingkat (aksi server menolak lebih dari ini). */
    public const MAX_CARDS = 24;
    public const MAX_COLUMNS = 6;
    public const MAX_ELEMENTS = 20;

    /** [{value,label,...}]: config memakai "name"; kontrol segmented memakai "label". */
    public static function labelled(array $list): array
    {
        return array_map(fn ($o) => ['label' => $o['name'] ?? ($o['label'] ?? $o['value'])] + $o, array_values($list));
    }

    /** Kontrol tampilan SATU kartu. Key relatif terhadap data.cards.{i}.container. */
    public static function containerFields(): array
    {
        $d = fn (string $k) => config('cms.design.' . $k, []);

        return [
            Field::swatches('bg', 'Warna Latar', $d('card_bg_colors'), 'bg-white', 'bg'),
            Field::segmented('padding', 'Ruang Dalam', self::labelled($d('card_paddings')), 'p-2 md:p-4'),
            Field::segmented('radius', 'Sudut', self::labelled($d('border_radiuses')), 'rounded-[14px]'),
            Field::segmented('border_width', 'Garis Tepi', self::labelled($d('border_widths')), 'border-0'),
            Field::segmented('border_style', 'Jenis Garis', self::labelled($d('border_styles')), 'border-solid'),
            Field::swatches('border_color', 'Warna Garis', $d('card_border_colors'), 'border-forest', 'preview'),
            Field::segmented('shadow', 'Bayangan', [
                'shadow-none' => 'Tanpa', 'shadow-sm' => 'Tipis', 'shadow-md' => 'Sedang', 'shadow-lg' => 'Tebal',
            ], 'shadow-sm'),
            Field::segmented('hover', 'Saat disorot', [
                '' => 'Diam', 'hover:-translate-y-1' => 'Naik', 'hover:shadow-lg' => 'Bayangan',
            ], 'hover:-translate-y-1'),
            Field::segmented('align_y', 'Rata Vertikal Kolom', self::labelled($d('alignments')), 'items-center'),
            Field::urlLink('url', 'Tautan seluruh kartu (opsional)'),
        ];
    }

    /** Kontrol SATU kolom. Key relatif terhadap data.cards.{i}.layout.children.{j}. */
    public static function columnFields(): array
    {
        return [
            Field::segmented('width', 'Lebar Relatif', array_map(fn ($w) => ['value' => $w, 'label' => $w === 'auto' ? 'Auto' : (string) $w], self::WIDTHS), 1),
            Field::segmented('align', 'Rata Vertikal Isi', self::labelled(config('cms.design.alignments', [])), 'items-start'),
        ];
    }

    /** Kontrol blok (di luar daftar kartu). */
    public static function gridFields(array $marginOptions, string $marginDefault): array
    {
        return [
            Field::segmented('data.grid.cols', 'Kartu per Baris', [1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6'], 1),
            Field::segmented('data.grid.margin_bottom', 'Jarak Bawah', $marginOptions, $marginDefault),
        ];
    }

    /**
     * Jenis elemen yang bisa ditambah ke kolom = elemen yang punya panel DAN nilai bawaan di registri.
     *
     * @return list<array{type:string,label:string,icon:string}>
     */
    public static function elementTypes(): array
    {
        $out = [];
        foreach (BlockRegistry::all() as $def) {
            if ($def->kind === 'element' && $def->defaults) {
                $out[] = ['type' => $def->type, 'label' => $def->label, 'icon' => $def->icon];
            }
        }

        return $out;
    }

    /** Label elemen untuk daftar di panel: jenis elemen → label (jenis tanpa panel tetap dapat label dari namanya). */
    public static function elementLabels(): array
    {
        $out = [];
        foreach (BlockRegistry::all() as $def) {
            if ($def->kind === 'element') {
                $out[$def->type] = ['label' => $def->label, 'icon' => $def->icon, 'panel' => true];
            }
        }

        return $out;
    }
}
