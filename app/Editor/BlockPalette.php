<?php

namespace App\Editor;

/**
 * Daftar tipe blok yang bisa ditambahkan, dan aturan "kontainer" (zona + tipe anak yang diizinkan).
 * Menggantikan tiga daftar yang tersebar di editor lama: toolbar bawah page-editor, $availableBlocks di multi-columns,
 * dan pengetahuan tentang nama zona (col_N_zone, children) yang tertanam di trait.
 *
 * Murni data & fungsi: tidak menyentuh model, mudah diubah.
 */
final class BlockPalette
{
    /**
     * type => [label, ikon Lucide, grup, tampil di menu tingkat atas?]
     * Urutan = urutan di menu. Grup lama (tombol, lencana, statistik, kartu, testimoni) sengaja ditiadakan:
     * kartu dibuat lewat card-builder. Blok lama bertipe itu yang sudah ada di halaman tetap terbaca (label dari nama tipe).
     */
    private const BUILTIN = [
        'heading'         => ['Judul', 'heading-1', 'Konten', true],
        'paragraph'       => ['Paragraf', 'align-left', 'Konten', true],
        'eyebrow'         => ['Eyebrow', 'crosshair', 'Konten', true],
        'image'           => ['Gambar', 'image-plus', 'Konten', true],
        'button-builder'  => ['Tombol', 'mouse-pointer-click', 'Konten', true],
        'card-builder'    => ['Kartu Builder', 'playing-cards-fan', 'Konten', true],
        'step-group'      => ['Grup Langkah', 'list-ordered', 'Layout', true],
        'multi-columns'   => ['Kolom', 'columns-4', 'Layout', true],
        'section-divider' => ['Pemisah Seksi', 'between-horizontal-start', 'Layout', true],
    ];

    /** Tipe yang boleh berada di dalam kolom. (card-builder ditambahkan: dua kolom kartu adalah pemakaian yang wajar.) */
    private const BUILTIN_COLUMN_CHILDREN = ['heading', 'paragraph', 'eyebrow', 'image', 'button-builder', 'card-builder'];

    /** Isi step-group: HANYA card-builder, satu per langkah (dari blade step-group: "Tambah Langkah Baru"). */
    private const STEP_CHILDREN = ['card-builder'];

    public const MAX_COLUMNS = 6;

    /** @var array<string, array{0:string,1:string,2:string,3:bool}>|null bawaan + modul (app/Editor/Blocks) */
    private static ?array $types = null;
    /** @var string[]|null */
    private static ?array $columnChildren = null;

    /** Tipe bawaan, lalu tipe dari modul (muncul di akhir grupnya). */
    private static function types(): array
    {
        if (self::$types !== null) {
            return self::$types;
        }
        $types = self::BUILTIN;
        foreach (class_exists(Modules::class) ? Modules::all() : [] as $module) {
            $def = $module::definition();
            $place = $module::placement();
            $types[BlockRegistry::canonical($def->type)] ??= [$def->label, $def->icon, $place['group'] ?? 'Konten', (bool) ($place['root'] ?? true)];
        }

        return self::$types = $types;
    }

    private static function columnChildren(): array
    {
        if (self::$columnChildren !== null) {
            return self::$columnChildren;
        }
        $children = self::BUILTIN_COLUMN_CHILDREN;
        foreach (class_exists(Modules::class) ? Modules::all() : [] as $type => $module) {
            if (!empty($module::placement()['columns'])) {
                $children[] = $type;
            }
        }

        return self::$columnChildren = array_values(array_unique($children));
    }

    /** Untuk pengujian: lupakan hasil penemuan modul. */
    public static function reset(): void
    {
        self::$types = self::$columnChildren = null;
    }

    public static function canonical(?string $type): string
    {
        return str_replace('_', '-', strtolower((string) $type));
    }

    public static function has(string $type): bool
    {
        return isset(self::types()[self::canonical($type)]);
    }

    public static function label(string $type): string
    {
        $type = self::canonical($type);
        return self::types()[$type][0] ?? ucfirst(str_replace('-', ' ', $type));
    }

    public static function icon(string $type): string
    {
        return self::types()[self::canonical($type)][1] ?? 'box';
    }

    /** Semua nama ikon yang dipakai palet (sprite harus memuatnya). */
    public static function icons(): array
    {
        return array_values(array_unique(array_column(self::types(), 1)));
    }

    /** @return list<array{type:string,label:string,icon:string,group:string}> */
    public static function rootTypes(): array
    {
        return self::describe(array_keys(array_filter(self::types(), fn ($t) => $t[3])));
    }

    /** @return list<array{type:string,label:string,icon:string,group:string}> tipe yang boleh menjadi anak kontainer */
    public static function childTypes(string $containerType): array
    {
        return self::describe(match (self::canonical($containerType)) {
            'multi-columns' => self::columnChildren(),
            'step-group' => self::STEP_CHILDREN,
            default => [],
        });
    }

    public static function allowsChild(string $containerType, string $childType): bool
    {
        return in_array(self::canonical($childType), array_column(self::childTypes($containerType), 'type'), true);
    }

    public static function allowedAtRoot(string $type): bool
    {
        return in_array(self::canonical($type), array_column(self::rootTypes(), 'type'), true);
    }

    public static function isContainer(string $type): bool
    {
        return in_array(self::canonical($type), ['multi-columns', 'step-group'], true);
    }

    /**
     * Zona sebuah kontainer. Kolom di atas `col_count` yang masih berisi tetap ditampilkan (hidden = true), karena
     * mengurangi jumlah kolom di editor lama menyisakan konten tersembunyi.
     *
     * @return list<array{key:string,label:string,hidden:bool}>
     */
    public static function zonesFor(array $block): array
    {
        $data = $block['data'] ?? [];

        switch (self::canonical($block['type'] ?? '')) {
            case 'step-group':
                return [['key' => 'children', 'label' => 'Langkah', 'hidden' => false]];

            case 'multi-columns':
                $count = max(1, min(self::MAX_COLUMNS, (int) ($data['col_count'] ?? 2)));
                $zones = [];
                for ($i = 1; $i <= self::MAX_COLUMNS; $i++) {
                    $key = "col_{$i}_zone";
                    $filled = !empty($data[$key]) && is_array($data[$key]);
                    if ($i <= $count || $filled) {
                        $zones[] = ['key' => $key, 'label' => "Kolom {$i}", 'hidden' => $i > $count];
                    }
                }
                return $zones;
        }

        return [];
    }

    private static function describe(array $types): array
    {
        return array_map(fn (string $type) => [
            'type' => $type,
            'label' => self::types()[$type][0],
            'icon' => self::types()[$type][1],
            'group' => self::types()[$type][2],
        ], array_values($types));
    }
}