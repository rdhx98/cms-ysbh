<?php

namespace App\Editor;

use App\Content\Blocks\LayoutStyle;
use App\Content\Blocks\Spacing;

/**
 * Registri tipe blok/elemen: satu-satunya tempat yang menyatakan "properti apa saja yang bisa diubah,
 * pilihannya apa, dan bentuknya di data". Menggambarkan data yang SUDAH ADA (content.{id}.data.*),
 * bukan format baru, sehingga halaman lama tetap terbaca.
 *
 * Inspektur (<x-editor.inspector>) dan audit konsistensi (tests/registry-audit.php) membaca dari sini.
 */
final class BlockRegistry
{
    /** @var array<string, BlockType>|null */
    private static ?array $all = null;

    /** @return array<string, BlockType> dikunci oleh panelKey() */
    public static function all(): array
    {
        return self::$all ??= self::build();
    }

    /** "section_divider" dan "section-divider" dianggap tipe yang sama. */
    public static function canonical(string $type): string
    {
        return str_replace('_', '-', strtolower($type));
    }

    public static function block(string $type): ?BlockType
    {
        return self::all()['block:' . self::canonical($type)] ?? null;
    }

    public static function element(string $type): ?BlockType
    {
        return self::all()['element:' . $type] ?? null;
    }

    public static function reset(): void
    {
        self::$all = null;
    }

    // ------------------------------------------------------------------ opsi bersama

    private static function marginBottom(): array
    {
        return array_map(
            fn ($m) => ['value' => $m['value'], 'label' => $m['label'] ?? ($m['name'] ?? $m['value'])],
            config('cms.design.margin_bottom', []),
        );
    }

    private static function dot(string $value, string $title, string $dot, bool $ring = false): array
    {
        return ['value' => $value, 'title' => $title, 'dot' => $dot, 'dot_ring' => $ring];
    }

    private static function sizeSquares(array $map): array
    {
        $out = [];
        foreach ($map as $value => [$title, $square]) {
            $out[] = ['value' => $value, 'title' => $title, 'square' => $square];
        }
        return $out;
    }

    private static function shapes(array $map): array
    {
        $out = [];
        foreach ($map as $value => [$title, $shape]) {
            $out[] = ['value' => $value, 'title' => $title, 'shape' => $shape];
        }
        return $out;
    }

    // ------------------------------------------------------------------ definisi

    private static function build(): array
    {
        $isPill = ['data.style.is_pill', true];
        $notPill = ['data.style.is_pill', false];

        $defs = [
            // ========================= BLOK =========================
            new BlockType('heading', 'Judul', 'heading', 'block', [
                Field::rich('data.text', 'Teks Judul', align: true),
                Field::segmented('data.level', 'Level', ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3'], 'h2'),
                Field::segmented('data.margin_bottom', 'Jarak Bawah', self::marginBottom(), 'mb-4 md:mb-6'),
            ]),

            new BlockType('paragraph', 'Paragraf', 'text-align-start', 'block', [
                Field::rich('data.text', 'Paragraf', multi: true),
                Field::segmented('data.margin_bottom', 'Jarak Bawah', self::marginBottom(), 'mb-4 md:mb-6'),
            ]),

            new BlockType('section-divider', 'Pemisah Seksi', 'between-horizontal-start', 'block', [
                Field::swatches('data.background', 'Warna Latar Seksi', config('cms.design.bg_colors', []), 'bg-paper', 'bg'),
                Field::segmented('data.text_color', 'Warna Teks', [
                    self::dot('text-charcoal', 'Gelap', 'bg-charcoal'),
                    self::dot('text-white', 'Terang', 'bg-white', true),
                ], 'text-charcoal'),
                Field::segmented('data.padding', 'Padding Atas & Bawah', [
                    ['value' => 'py-8 sm:py-12', 'label' => 'Rapat'],
                    ['value' => 'py-16 sm:py-24', 'label' => 'Normal'],
                    ['value' => 'py-24 sm:py-[96px]', 'label' => 'Lebar'],
                ], 'py-16 sm:py-24'),
            ]),

            // Kolom. Tinggi baris grid = kolom TERTINGGI; kolom lain diregangkan sebesar itu, lalu isinya disejajarkan pada sumbu Y
            // (Atas / Tengah / Bawah / Bagi rata). Nilai bawaan ada di sini (rilis 35): blok baru langsung punya enam zona kosong,
            // sehingga kanvas bisa menggambar kolom kosongnya (sebelumnya memakai bawaan trait lama yang memberi gap-2).
            new BlockType('multi-columns', 'Kolom', 'columns-4', 'block', [
                // live=true: jumlah kolom mengubah zona di outline, yang dirender server — harus langsung dikirim
                Field::segmented('data.col_count', 'Jumlah Kolom', [
                    ['value' => 2, 'label' => '2'], ['value' => 3, 'label' => '3'], ['value' => 4, 'label' => '4'],
                    ['value' => 5, 'label' => '5'], ['value' => 6, 'label' => '6'],
                ], 2, null, ['live' => true]),
                Field::toggle('data.mobile_reverse', 'Kolom kanan di atas (urutan HP)', ['data.col_count', 2]),
                Field::segmented('data.align_y', 'Rata Vertikal (terhadap kolom tertinggi)', LayoutStyle::ALIGN_Y, LayoutStyle::ALIGN_Y_DEFAULT),
                Field::segmented('data.align_x', 'Rata Horizontal', ['items-start' => 'Kiri', 'items-center' => 'Tengah', 'items-end' => 'Kanan'], 'items-start'),
                // Kelas gap dipasang renderer pada tiap kolom: ini jarak antar BLOK di dalam satu kolom (jarak antar kolom tetap)
                Field::segmented('data.gap', 'Jarak Antar Blok di Kolom', ['gap-0' => '0px', 'gap-4' => '16px', 'gap-8' => '32px'], 'gap-4'),
            ], defaults: [
                'col_count' => 2,
                'mobile_reverse' => false,
                'align_x' => 'items-start',
                'align_y' => LayoutStyle::ALIGN_Y_DEFAULT,
                'gap' => 'gap-4',
                'col_1_zone' => [], 'col_2_zone' => [], 'col_3_zone' => [], 'col_4_zone' => [], 'col_5_zone' => [], 'col_6_zone' => [],
            ]),

            new BlockType('step-group', 'Grup Langkah (Timeline)', 'list-ordered', 'block', [
                Field::text('data.node_color', 'Warna Angka (kelas Tailwind)', ['placeholder' => 'bg-foresty text-white']),
                Field::text('data.line_color', 'Warna Garis (kelas Tailwind)', ['placeholder' => 'bg-foresty/30']),
                Field::segmented('data.orientation', 'Orientasi', ['vertical' => 'Vertikal', 'horizontal-top' => 'Horiz. Atas', 'horizontal-bottom' => 'Horiz. Bawah'], 'vertical'),
                Field::segmented('data.gap', 'Jarak', ['gap-4' => 'Rapat', 'gap-8' => 'Sedang', 'gap-12' => 'Renggang', 'gap-16' => 'Jauh'], 'gap-8'),
            ]),

            // Eyebrow: label kecil berikon di atas judul. Data: text{id,en}, icon, color (hex), margin_bottom. Renderer: bawaan editor lama.
            new BlockType('eyebrow', 'Eyebrow', 'crosshair', 'block', [
                Field::i18n('data.text', 'Teks Eyebrow'),
                Field::icon('data.icon', 'Ikon', 'newspaper'),
                Field::swatches('data.color', 'Warna', config('cms.design.eyebrow_colors', []), '#E42326', 'hex'),
                Field::segmented('data.margin_bottom', 'Jarak Bawah', self::marginBottom(), Spacing::DEFAULT),
            ]),

            // Gambar. Berkas dipilih lewat File Manager (menulis data.url, data.media_id, data.alt_text). Pilihan tata letak adalah kelas Tailwind
            // yang dicetak renderer gambar; "w-screen" = banner layar penuh (dikenali daftar isi di sections.blade.php).
            new BlockType('image', 'Gambar', 'image-plus', 'block', [
                Field::media('data', 'Pilih gambar dari File Manager'),
                Field::text('data.alt_text', 'Teks Alternatif (SEO dan pembaca layar)', ['placeholder' => 'Jelaskan isi gambar']),
                Field::i18n('data.caption', 'Keterangan Gambar (opsional)'),
                Field::segmented('data.width', 'Lebar', [
                    'w-full' => 'Penuh', 'w-screen' => 'Layar penuh (banner)', 'w-3/4' => '3/4', 'w-1/2' => '1/2', 'w-1/3' => '1/3',
                ], 'w-full'),
                Field::segmented('data.align', 'Posisi', ['mr-auto' => 'Kiri', 'mx-auto' => 'Tengah', 'ml-auto' => 'Kanan'], 'mx-auto'),
                Field::segmented('data.radius', 'Sudut', [
                    'rounded-none' => 'Siku', 'rounded-lg' => 'Halus', 'rounded-2xl' => 'Bulat', 'rounded-3xl' => 'Sangat bulat',
                ], 'rounded-none'),
                // Tinggi (rilis 35): Otomatis = mengikuti rasio gambar; Penuh = mengisi tinggi kolom (di dalam Kolom yang diregangkan ke
                // kolom tertinggi); angka = tinggi tetap, gambar dipotong/diletakkan sesuai "Pemotongan".
                Field::segmented('data.height', 'Tinggi (angka = rem)', LayoutStyle::IMAGE_HEIGHT, LayoutStyle::IMAGE_HEIGHT_DEFAULT),
                Field::segmented('data.max_height', 'Tinggi Maksimum (bila Tinggi = Auto)', [
                    'max-h-none' => 'Bebas', 'max-h-64' => '16rem', 'max-h-96' => '24rem', 'max-h-[32rem]' => '32rem', 'max-h-[40rem]' => '40rem',
                ], 'max-h-none'),
                Field::segmented('data.object_fit', 'Pemotongan', ['object-cover' => 'Penuhi (potong)', 'object-contain' => 'Utuh'], 'object-cover'),
                Field::segmented('data.margin_bottom', 'Jarak Bawah', self::marginBottom(), Spacing::DEFAULT),
            ]),

            // Kartu Builder: kartu → kolom → elemen. Kontrol blok di sini; daftar kartu, kolom, dan elemen digambar <x-editor.cards>
            // (komponen tambahan), yang memanggil aksi trait (addCardItem, addColumnToCard, ...) dan membuka panel elemen (element:*) bila diklik.
            new BlockType('card-builder', 'Kartu Builder', 'playing-cards-fan', 'block',
                CardPanel::gridFields(self::marginBottom(), Spacing::DEFAULT),
                defaults: [],
                component: 'editor.cards',
            ),

            // Tombol / ajakan bertindak. Tautan internal memakai ID (bukan slug) supaya mengganti slug tidak mematahkan tombol.
            new BlockType('button-builder', 'Tombol', 'mouse-pointer-click', 'block', [
                Field::repeater('data.buttons', 'Tombol', [
                    Field::i18n('label', 'Teks Tombol'),
                    Field::link('link', 'Tautan'),
                    Field::toggle('link.new_tab', 'Buka di tab baru'),
                    Field::segmented('variant', 'Gaya', ['solid' => 'Isi', 'outline' => 'Garis', 'ghost' => 'Teks'], 'solid'),
                    Field::segmented('color', 'Warna', [
                        self::dot('foresty', 'Foresty', 'bg-foresty'),
                        self::dot('coral', 'Coral', 'bg-coral'),
                        self::dot('aurum', 'Aurum', 'bg-aurum'),
                        self::dot('charcoal', 'Charcoal', 'bg-charcoal'),
                    ], 'foresty'),
                    Field::segmented('size', 'Ukuran', ['sm' => 'S', 'md' => 'M', 'lg' => 'L'], 'md'),
                    Field::icon('icon', 'Ikon (opsional)', '', true),
                    Field::segmented('icon_position', 'Posisi ikon', ['left' => 'Kiri', 'right' => 'Kanan'], 'left', ['icon', true]),
                ], [
                    'id' => '@id',
                    'label' => '@locales', // kosong: tombol tanpa teks tidak dirender di halaman publik (tidak ada isi palsu)
                    'link' => ['kind' => 'url', 'ref' => '', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false],
                    'variant' => 'solid', 'color' => 'foresty', 'size' => 'md', 'icon' => '', 'icon_position' => 'left',
                ], 12, 'tombol'),
                Field::segmented('data.align', 'Perataan', ['left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan'], 'left'),
                Field::toggle('data.stack_mobile', 'Tumpuk ke bawah di layar kecil'),
            ], defaults: [
                'align' => 'left',
                'stack_mobile' => true,
                'buttons' => [[
                    'id' => '@id',
                    'label' => '@locales',
                    'link' => ['kind' => 'url', 'ref' => '', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false],
                    'variant' => 'solid', 'color' => 'foresty', 'size' => 'md', 'icon' => '', 'icon_position' => 'left',
                ]],
            ]),

            // ========================= ELEMEN KARTU =========================
            new BlockType('text', 'Teks', 'square-dashed-text', 'element', [
                Field::i18n('data.content', 'Teks', multi: true, rows: 2),
                Field::toggle('data.style.is_pill', 'Mode Pill'),
                Field::select('data.style.font', 'Tipe Font', config('cms.fonts', []), 'font-fraunces', $notPill, ['font' => true]),
                Field::segmented('data.style.weight', 'Ketebalan', ['font-normal' => 'Reguler', 'font-semibold' => 'Semi Bold'], 'font-normal', $notPill),
                Field::segmented('data.style.size', 'Ukuran', ['text-[13px]' => 'Kecil', 'text-[15px]' => 'Normal', 'text-[21px]' => 'Besar (H3)'], 'text-[13px]', $notPill),
                Field::segmented('data.style.text_transform', 'Huruf', config('cms.design.text_transform', []), 'normal-case', $notPill),
                Field::segmented('data.style.pill_bg', 'Warna Latar Pill', [
                    self::dot('bg-goldy-soft', 'Goldy', 'bg-goldy-soft', true),
                    self::dot('bg-mist', 'Mist', 'bg-mist', true),
                    self::dot('bg-sage-soft', 'Sage', 'bg-sage-soft', true),
                ], 'bg-goldy-soft', $isPill),
                Field::segmented('data.style.pill_radius', 'Bentuk Pill', ['rounded-md' => 'Bulat Sedikit', 'rounded-full' => 'Lingkaran'], 'rounded-md', $isPill),
                Field::segmented('data.style.color', 'Warna Teks', [
                    self::dot('text-ink-soft', 'Abu Gelap', 'bg-ink-soft'),
                    self::dot('text-foresty', 'Foresty', 'bg-foresty'),
                    self::dot('text-coral', 'Coral', 'bg-coral'),
                    self::dot('text-aurum', 'Aurum', 'bg-aurum'),
                ], 'text-ink-soft'),
                Field::segmented('data.style.margin', 'Jarak Bawah', ['mb-0' => '0px', 'mb-2' => 'Kecil', 'mb-4' => 'Sedang'], 'mb-0'),
            ], defaults: [
                'content' => '@locales',
                'style' => [
                    'is_pill' => false, 'font' => 'font-jakarta', 'weight' => 'font-normal', 'size' => 'text-[15px]', 'text_transform' => 'normal-case',
                    'pill_bg' => 'bg-goldy-soft', 'pill_radius' => 'rounded-md', 'color' => 'text-ink-soft', 'margin' => 'mb-2',
                ],
            ]),

            new BlockType('icon', 'Ikon', 'shapes', 'element', [
                Field::icon('data.content.icon', 'Ikon', 'box'),
                Field::segmented('data.style.bg', 'Latar Ikon', [
                    self::dot('bg-goldy-soft', 'Goldy', 'bg-goldy-soft', true),
                    self::dot('bg-mist', 'Mist', 'bg-mist', true),
                    ['value' => 'bg-transparent', 'title' => 'Transparan', 'slash' => true],
                ], 'bg-goldy-soft'),
                Field::segmented('data.style.color', 'Warna Ikon', [
                    self::dot('text-foresty', 'Foresty', 'bg-foresty'),
                    self::dot('text-coral', 'Coral', 'bg-coral'),
                ], 'text-foresty'),
                Field::segmented('data.style.size', 'Ukuran', self::sizeSquares([
                    'w-10 h-10 md:w-12 md:h-12' => ['Standar', 'h-2.5 w-2.5'],
                    'w-16 h-16 md:w-20 md:h-20' => ['Sedang', 'h-3.5 w-3.5'],
                    'w-24 h-24 md:w-32 md:h-32' => ['Besar', 'h-4 w-4'],
                ]), 'w-10 h-10 md:w-12 md:h-12', null, ['compact' => true]),
                Field::segmented('data.style.radius', 'Sudut Ikon', self::shapes([
                    'rounded-[14px]' => ['Agak Bulat', 'rounded-md'],
                    'rounded-full' => ['Lingkaran', 'rounded-full'],
                ]), 'rounded-[14px]', null, ['compact' => true]),
            ], defaults: [
                'content' => ['icon' => 'box'],
                'style' => ['bg' => 'bg-goldy-soft', 'color' => 'text-foresty', 'size' => 'w-10 h-10 md:w-12 md:h-12', 'radius' => 'rounded-[14px]'],
            ]),

            new BlockType('initials', 'Inisial Nama', 'a-large-small', 'element', [
                Field::text('data.content.text', 'Inisial', ['maxlength' => 3, 'placeholder' => 'Contoh: JD', 'upper' => true]),
                Field::segmented('data.style.size', 'Ukuran', [
                    ['value' => 'w-10 h-10 md:w-12 md:h-12 text-sm md:text-base', 'title' => 'Standar', 'label' => 'A'],
                    ['value' => 'w-16 h-16 md:w-20 md:h-20 text-xl md:text-2xl', 'title' => 'Sedang', 'label' => 'A'],
                    ['value' => 'w-24 h-24 md:w-32 md:h-32 text-3xl md:text-5xl', 'title' => 'Besar', 'label' => 'A'],
                    ['value' => 'w-32 h-32 md:w-48 md:h-48 text-5xl md:text-7xl', 'title' => 'Paling Besar', 'label' => 'A'],
                ], 'w-16 h-16 md:w-20 md:h-20 text-xl md:text-2xl'),
                Field::segmented('data.style.radius', 'Bentuk', self::shapes([
                    'rounded-md' => ['Kotak', 'rounded-sm'],
                    'rounded-[20px]' => ['Agak Bulat', 'rounded-md'],
                    'rounded-full' => ['Lingkaran', 'rounded-full'],
                ]), 'rounded-full', null, ['compact' => true]),
                Field::swatches('data.style.bg_color', 'Warna Latar', config('cms.design.mini_bg_colors', []), 'bg-forest', 'bg'),
                Field::swatches('data.style.text_color', 'Warna Teks', config('cms.design.text_colors', []), 'text-white', 'text'),
                Field::segmented('data.style.border', 'Garis Tepi', ['border-0' => '0', 'border' => '1', 'border-2' => '2', 'border-4' => '4'], 'border-0'),
                // Trait menulis 'border-transparent' sebagai nilai bawaan, tetapi opsi itu dikomentari di
                // cms.design.avatar_border_colors — ditambahkan di sini supaya nilai bawaan bisa dipilih.
                Field::swatches('data.style.border_color', 'Warna Tepian', array_merge([
                    ['name' => 'Transparan', 'value' => 'border-transparent', 'preview' => 'bg-transparent', 'is_transparent' => true],
                ], config('cms.design.avatar_border_colors', [])), 'border-transparent', 'preview'),
            ], defaults: [
                'content' => ['text' => ''],
                'style' => [
                    'size' => 'w-16 h-16 md:w-20 md:h-20 text-xl md:text-2xl', 'radius' => 'rounded-full', 'bg_color' => 'bg-forest',
                    'text_color' => 'text-white', 'border' => 'border-0', 'border_color' => 'border-transparent',
                ],
            ]),

            new BlockType('profile_photo', 'Foto Profil', 'image', 'element', [
                Field::media('data.content', 'Foto'),
                Field::text('data.content.alt', 'Teks Alternatif', ['placeholder' => 'Teks Alternatif (Untuk SEO & Tunanetra)']),
                Field::segmented('data.style.size', 'Ukuran', self::sizeSquares([
                    'w-10 h-10 md:w-12 md:h-12' => ['Standar', 'h-2.5 w-2.5'],
                    'w-16 h-16 md:w-20 md:h-20' => ['Sedang', 'h-3.5 w-3.5'],
                    'w-24 h-24 md:w-32 md:h-32' => ['Besar', 'h-4 w-4'],
                    'w-32 h-32 md:w-48 md:h-48' => ['Paling Besar', 'h-5 w-5'],
                ]), 'w-16 h-16 md:w-20 md:h-20', null, ['compact' => true]),
                Field::segmented('data.style.radius', 'Bentuk', self::shapes([
                    'rounded-md' => ['Kotak', 'rounded-md'],
                    'rounded-[20px]' => ['Agak Bulat', 'rounded-[8px]'],
                    'rounded-full' => ['Lingkaran', 'rounded-full'],
                ]), 'rounded-full', null, ['compact' => true]),
                Field::segmented('data.style.border', 'Garis Tepi', ['border-0' => 'Tanpa Garis', 'border-2' => 'Tipis (2px)', 'border-4' => 'Tebal (4px)'], 'border-0'),
                Field::segmented('data.style.border_color', 'Warna Garis', [
                    ['value' => 'border-transparent', 'title' => 'Transparan', 'slash' => true],
                    self::dot('border-foresty', 'Foresty', 'bg-foresty'),
                    self::dot('border-coral', 'Coral', 'bg-coral'),
                    self::dot('border-gray-200', 'Abu-abu', 'bg-gray-200', true),
                ], 'border-transparent'),
            ], defaults: [
                'content' => ['url' => '', 'media_id' => null, 'alt' => ''],
                'style' => ['size' => 'w-16 h-16 md:w-20 md:h-20', 'radius' => 'rounded-full', 'border' => 'border-0', 'border_color' => 'border-transparent'],
            ]),

            new BlockType('accordion', 'FAQ / Akordion', 'list-chevrons-up-down', 'element', [
                Field::i18n('data.content.question', 'Pertanyaan'),
                Field::i18n('data.content.answer', 'Jawaban', multi: true, rows: 3),
                Field::segmented('data.style.theme', 'Warna Aksen Teks & Ikon', [
                    self::dot('foresty', 'Foresty', 'bg-foresty'),
                    self::dot('coral', 'Coral', 'bg-coral'),
                    self::dot('dark', 'Gelap', 'bg-gray-800'),
                ], 'foresty'),
            ], defaults: [
                'content' => ['question' => '@locales', 'answer' => '@locales'],
                'style' => ['theme' => 'foresty'],
            ]),
        ];

        $all = [];
        foreach ($defs as $def) {
            $all[$def->panelKey()] = $def;
        }

        // Blok modul (app/Editor/Blocks/*Block.php): ditambahkan otomatis; tidak boleh menimpa tipe bawaan.
        foreach (class_exists(Modules::class) ? Modules::all() : [] as $module) {
            $def = $module::definition();
            $all[$def->panelKey()] ??= $def;
        }

        // Jarak bawah untuk SEMUA blok tingkat atas (rilis 32). Blok yang sudah punya kontrolnya sendiri (judul, paragraf, eyebrow, gambar:
        // data.margin_bottom; kartu: data.grid.margin_bottom) dibiarkan. Pemisah seksi tidak punya margin (ia mengatur padding seksi).
        foreach ($all as $key => $def) {
            if ($def->kind === 'block' && $def->type !== 'section-divider' && ! $def->hasField('data.margin_bottom', 'data.grid.margin_bottom')) {
                $all[$key] = $def->withFields([Field::segmented('data.margin_bottom', 'Jarak Bawah', self::marginBottom(), Spacing::DEFAULT)]);
            }
        }

        return $all;
    }
}
