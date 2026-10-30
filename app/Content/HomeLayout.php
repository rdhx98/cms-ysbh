<?php

namespace App\Content;

use App\Content\Blocks\BlockSanitizer;
use App\Editor\BlockRegistry;
use App\Editor\Defaults;

/**
 * Isi beranda dari blok (rilis 90). MURNI: tanpa basis data. Dipakai perintah `cms:seed-home` (docs/BERANDA.md).
 *
 * Menyusun satu dokumen {blocks, order, settings} dua bahasa (EN, ID) dari blok yang sudah ada di CMS: Pemisah Seksi (latar), Kolom, Judul,
 * Paragraf, Eyebrow, Gambar, Tombol, Kartu Builder, Grup Langkah, Artikel Terbaru. Semua teks berasal dari draf yang sudah disetujui
 * (SiteText: beranda, program, dampak, donasi, malaria); tidak ada fakta baru. Angka di bagian "Apa yang berubah" hanya angka yang sudah
 * tertulis di halaman Dampak (2014, lima bidang program, 16 kabupaten, di atas 80%) dengan tahun dan sumbernya.
 *
 * Urutan seksi: Hero | Mitra | Program | Kisah lapangan | Angka | Cara donasi | Artikel terbaru | Penutup.
 *
 * Yang SENGAJA kosong: foto (dua blok Gambar tanpa berkas: foto hero dan foto kisah lapangan). Gambar tanpa berkas tidak dicetak di situs dan
 * dilaporkan `cms:audit-placeholders`, jadi beranda tidak terbit dengan tempat foto kosong tanpa sepengetahuan Anda.
 *
 * Tautan: kartu program dan tautan teks memakai internal://page/{slug EN} (diselesaikan ke bahasa pembaca). Tombol menyimpan ID halaman, jadi
 * butuh pemetaan kunci halaman -> ID ($pageId); halaman yang belum ada membuat tombolnya tanpa tautan (tidak tampil di situs).
 */
final class HomeLayout
{
    /** Kunci halaman SitePages yang menjadi tujuan tombol. */
    public const BUTTON_TARGETS = ['donation', 'programs', 'malaria', 'transparency', 'contact'];

    /** Kunci blok yang menjadi tempat foto (Gambar tanpa berkas). */
    public const PHOTO_IDS = ['hero_photo', 'story_photo'];

    /** Slot palet program, urutan kartu. */
    private const PROGRAMS = [
        ['malaria', 'malaria-program', 'target'],
        ['imunisasi', 'immunization-program', 'shield-check'],
        ['kia', 'maternal-child-health-program', 'heart'],
        ['tbc', 'tb-program', 'activity'],
        ['hiv', 'hiv-program', 'heart-pulse'],
    ];

    /** @var array<string,array<string,mixed>> */
    private array $blocks = [];

    /** @var list<string> */
    private array $order = [];

    /** @param string[] $locales */
    private function __construct(private array $locales, private $pageId)
    {
    }

    /**
     * @param string[] $locales
     * @param callable(string):?int $pageId kunci halaman SitePages ('donation', ...) -> ID halaman, atau null bila belum ada
     * @return array{blocks:array<string,array<string,mixed>>,order:list<string>,settings:array<mixed>}
     */
    public static function content(array $locales, callable $pageId): array
    {
        $b = new self($locales, $pageId);
        $b->hero();
        $b->partners();
        $b->programs();
        $b->story();
        $b->numbers();
        $b->steps();
        $b->news();
        $b->closing();

        // Dibersihkan modul/pembersih blok yang sama dengan jalur simpan, supaya yang tersimpan sudah berbentuk sah.
        foreach ($b->blocks as $id => $block) {
            $b->blocks[$id]['data'] = BlockSanitizer::forType((string) $block['type'], (array) $block['data'], $locales);
        }

        return ['blocks' => $b->blocks, 'order' => $b->order, 'settings' => []];
    }

    // ------------------------------------------------------------------ bagian halaman

    private function hero(): void
    {
        $this->divider('sec_hero', 'bg-paper', 'py-16 sm:py-24');
        $this->add('hero_eyebrow', 'eyebrow', ['text' => $this->t('Papua · since 2014', 'Papua · sejak 2014'), 'icon' => 'map', 'color' => 'var(--color-coral)', 'margin_bottom' => 'mb-4 md:mb-6'], false);
        $this->add('hero_title', 'heading', ['text' => $this->t('Stronger health systems for the people of Papua', 'Sistem kesehatan yang lebih kuat bagi masyarakat Papua'), 'level' => 'h1', 'margin_bottom' => 'mb-4 md:mb-6'], false);
        $this->add('hero_lead', 'paragraph', ['text' => $this->p(
            'Yayasan Sinar Bhakti Husada (YSBH) is a Papua-based health foundation. We work alongside government and international partners to strengthen health services, empower communities, and improve quality of life, especially in remote areas.',
            'Yayasan Sinar Bhakti Husada (YSBH) adalah yayasan kesehatan di Papua. Kami bekerja bersama pemerintah dan mitra internasional untuk memperkuat layanan kesehatan, memberdayakan masyarakat, dan meningkatkan kualitas hidup, terutama di wilayah terpencil.'
        ), 'margin_bottom' => 'mb-8 md:mb-10'], false);
        $this->add('hero_cta', 'button-builder', ['align' => 'left', 'stack_mobile' => true, 'buttons' => [
            $this->button('hero_donate', $this->t('Donate', 'Donasi'), 'donation', 'solid', 'coral', 'lg', 'heart'),
            $this->button('hero_work', $this->t('See our work', 'Lihat kerja kami'), 'programs', 'outline', 'foresty', 'lg'),
        ], 'margin_bottom' => 'mb-0'], false);
        $this->add('hero_photo', 'image', $this->photo($this->t('', ''), 'rounded-2xl', 'max-h-[40rem]'), false);
        $this->columns('hero_cols', [['hero_eyebrow', 'hero_title', 'hero_lead', 'hero_cta'], ['hero_photo']], [3, 2], 'justify-center');
    }

    private function partners(): void
    {
        $this->divider('sec_partners', 'bg-mist', 'py-8 sm:py-12');
        $this->add('partners_text', 'paragraph', ['text' => $this->p(
            '<strong>Trusted by partners since 2014:</strong> UNICEF · USAID (through Pact and FHI 360) · World Health Organization (WHO) · provincial and district health offices in Papua',
            '<strong>Dipercaya mitra sejak 2014:</strong> UNICEF · USAID (melalui Pact dan FHI 360) · World Health Organization (WHO) · dinas kesehatan provinsi dan kabupaten di Papua',
            true
        ), 'margin_bottom' => 'mb-4 md:mb-6']);
        $this->add('partners_link', 'paragraph', ['text' => $this->p(
            '<a href="internal://page/credibility">See our track record →</a>',
            '<a href="internal://page/kredibilitas">Lihat rekam jejak kami →</a>',
            true
        ), 'margin_bottom' => 'mb-0']);
    }

    private function programs(): void
    {
        $this->divider('sec_programs', 'bg-paper', 'py-16 sm:py-24');
        $this->add('programs_title', 'heading', ['text' => $this->t('Five program areas', 'Lima bidang program'), 'level' => 'h2', 'align' => 'center', 'margin_bottom' => 'mb-4 md:mb-6']);
        $this->add('programs_intro', 'paragraph', ['text' => $this->p(
            'Our programs are built around what local health systems need. We work with district and provincial health offices so that results last beyond the funding period.',
            'Program kami disusun berdasarkan kebutuhan sistem kesehatan setempat. Kami bekerja bersama dinas kesehatan kabupaten dan provinsi agar hasilnya bertahan setelah masa pendanaan berakhir.',
            true
        ), 'margin_bottom' => 'mb-8 md:mb-10']);

        $texts = [
            'malaria' => [$this->t('Malaria', 'Malaria'), $this->t('Malaria control and elimination, including integrated services for pregnant women and children, and insecticide-resistance surveillance.', 'Pengendalian dan eliminasi malaria, termasuk layanan terpadu bagi ibu hamil dan anak, serta surveilans resistensi insektisida.')],
            'imunisasi' => [$this->t('Immunization', 'Imunisasi'), $this->t('Support for routine immunization and the elimination of maternal and neonatal tetanus.', 'Dukungan imunisasi rutin dan eliminasi tetanus maternal dan neonatal.')],
            'kia' => [$this->t('Maternal and Child Health', 'Kesehatan Ibu dan Anak'), $this->t('Better care for mothers, newborns and young children, and stunting reduction.', 'Layanan yang lebih baik bagi ibu, bayi baru lahir, dan balita, serta penurunan stunting.')],
            'tbc' => [$this->t('Tuberculosis (TB)', 'Tuberkulosis (TBC)'), $this->t('Strengthening TB control in health services.', 'Penguatan pengendalian TBC di layanan kesehatan.')],
            'hiv' => [$this->t('HIV', 'HIV'), $this->t('Support to health offices for HIV services and data.', 'Dukungan kepada dinas kesehatan untuk layanan dan data HIV.')],
        ];
        $more = $this->t('Learn more →', 'Selengkapnya →');
        $cards = [];
        foreach (self::PROGRAMS as [$slot, $slug, $icon]) {
            $cards[] = $this->card('prog_' . $slot, [
                $this->icon('prog_' . $slot . '_i', $icon, 'bg-' . $slot, 'text-' . $slot . '-on'),
                $this->text('prog_' . $slot . '_t', $texts[$slot][0], 'text-[21px]', 'font-bold', 'text-charcoal', 'mb-2', 'font-fraunces'),
                $this->text('prog_' . $slot . '_b', $texts[$slot][1], 'text-[15px]', 'font-normal', 'text-ink-soft', 'mb-3'),
                $this->text('prog_' . $slot . '_m', $more, 'text-[13px]', 'font-bold', 'text-charcoal', 'mb-0'),
            ], 'bg-' . $slot . '-soft', 'internal://page/' . $slug);
        }
        $cards[] = $this->card('prog_all', [
            $this->icon('prog_all_i', 'arrow-right', 'bg-aurum', 'text-aurum-on'),
            $this->text('prog_all_t', $this->t('All programs', 'Semua program'), 'text-[21px]', 'font-bold', 'text-white', 'mb-2', 'font-fraunces'),
            $this->text('prog_all_b', $this->t('See how we plan and support health programs with district and provincial health offices.', 'Lihat cara kami merencanakan dan mendukung program kesehatan bersama dinas kesehatan kabupaten dan provinsi.'), 'text-[15px]', 'font-normal', 'text-white', 'mb-3'),
            $this->text('prog_all_m', $this->t('View programs →', 'Lihat program →'), 'text-[13px]', 'font-bold', 'text-aurum', 'mb-0'),
        ], 'bg-forest', 'internal://page/programs');
        $this->add('programs_cards', 'card-builder', ['grid' => ['cols' => 3, 'margin_bottom' => 'mb-0'], 'cards' => $cards]);
    }

    private function story(): void
    {
        $this->divider('sec_story', 'bg-sage-soft', 'py-16 sm:py-24');
        $this->add('story_photo', 'image', $this->photo($this->t(
            'On-the-job training in Paniai Regency, Central Papua Province, for the malaria program.',
            'Pelatihan di tempat kerja (OJT) di Kabupaten Paniai, Provinsi Papua Tengah, untuk program malaria.'
        ), 'rounded-2xl', 'max-h-[32rem]'), false);
        $this->add('story_title', 'heading', ['text' => $this->t('A legacy that must end', 'Warisan yang Perlu Diakhiri'), 'level' => 'h2', 'margin_bottom' => 'mb-4 md:mb-6'], false);
        $this->add('story_text', 'paragraph', ['text' => $this->p(
            'The Papua region, with six autonomous provinces and 42 regencies, accounts for 90% of malaria cases in Indonesia. For many communities, malaria has become a disease passed from one generation to the next. Yet no child should grow up facing repeated illness from malaria, and no family should accept a preventable disease as inevitable.',
            'Tanah Papua, dengan 6 provinsi mandiri dan 42 kabupaten, menyumbang 90% kasus malaria di Indonesia. Bagi sebagian masyarakat, malaria telah menjadi penyakit yang diwariskan dari satu generasi ke generasi berikutnya. Padahal tidak ada anak yang seharusnya tumbuh dengan risiko sakit berulang akibat malaria, dan tidak ada keluarga yang seharusnya menerima penyakit yang dapat dicegah sebagai hal yang wajar.'
        ), 'margin_bottom' => 'mb-8 md:mb-10'], false);
        $this->add('story_cta', 'button-builder', ['align' => 'left', 'stack_mobile' => true, 'buttons' => [
            $this->button('story_more', $this->t('About our malaria program', 'Tentang program malaria kami'), 'malaria', 'outline', 'foresty', 'md'),
        ], 'margin_bottom' => 'mb-0'], false);
        $this->columns('story_cols', [['story_photo'], ['story_title', 'story_text', 'story_cta']], [2, 3], 'justify-center');
    }

    private function numbers(): void
    {
        $this->divider('sec_numbers', 'bg-forest', 'py-16 sm:py-24');
        $this->add('numbers_title', 'heading', ['text' => $this->t('What our work has changed', 'Apa yang berubah berkat kerja kami'), 'level' => 'h2', 'align' => 'center', 'margin_bottom' => 'mb-4 md:mb-6']);
        $this->add('numbers_intro', 'paragraph', ['text' => $this->p(
            'Figures from results reported by our programs, with the year and source.',
            'Angka dari hasil yang dilaporkan program kami, beserta tahun dan sumbernya.',
            true
        ), 'margin_bottom' => 'mb-8 md:mb-10']);

        $facts = [
            ['2014', $this->t('YSBH was founded in Papua.', 'YSBH berdiri di Papua.')],
            ['5', $this->t('program areas: malaria, immunization, maternal and child health, TB and HIV.', 'bidang program: malaria, imunisasi, kesehatan ibu dan anak, TBC dan HIV.')],
            ['16', $this->t('malaria-endemic districts reached by the mass bed-net campaign week (2014, with UNICEF).', 'kabupaten endemis malaria terjangkau Pekan Kelambu Massal (2014, bersama UNICEF).')],
            ['>80%', $this->t('of health centers took part in Integrated Micro-Planning in UNICEF-supported areas (2015).', 'puskesmas mengikuti Integrated Micro-Planning di wilayah binaan UNICEF (2015).')],
        ];
        $cards = [];
        foreach ($facts as $i => [$figure, $label]) {
            $cards[] = $this->card('fact_' . ($i + 1), [
                $this->text('fact_' . ($i + 1) . '_n', ['en' => $figure, 'id' => $figure], 'text-[28px]', 'font-bold', 'text-aurum', 'mb-2', 'font-fraunces', 'text-center'),
                $this->text('fact_' . ($i + 1) . '_l', $label, 'text-[15px]', 'font-normal', 'text-white', 'mb-0', 'font-jakarta', 'text-center'),
            ], 'bg-transparent', '', 'border', 'border-aurum', 'shadow-none', '');
        }
        $this->add('numbers_cards', 'card-builder', ['grid' => ['cols' => 4, 'margin_bottom' => 'mb-8 md:mb-10'], 'cards' => $cards]);
        $this->add('numbers_link', 'paragraph', ['text' => $this->p(
            '<a href="internal://page/impact">See all results →</a>',
            '<a href="internal://page/dampak">Lihat semua hasil →</a>',
            true
        ), 'margin_bottom' => 'mb-0']);
    }

    private function steps(): void
    {
        $this->divider('sec_steps', 'bg-paper', 'py-16 sm:py-24');
        $this->add('steps_title', 'heading', ['text' => $this->t('How donating works', 'Cara kerja donasi'), 'level' => 'h2', 'align' => 'center', 'margin_bottom' => 'mb-4 md:mb-6']);
        $this->add('steps_intro', 'paragraph', ['text' => $this->p(
            'From your transfer to our reports, in four steps.',
            'Dari transfer Anda sampai laporan kami, dalam empat langkah.',
            true
        ), 'margin_bottom' => 'mb-8 md:mb-10']);

        $steps = [
            [$this->t('Transfer', 'Transfer'), $this->t('Send your donation to the foundation\'s official bank account. Only that account is official; we do not ask for donations through personal numbers.', 'Kirim donasi ke rekening bank resmi yayasan. Hanya rekening itu yang resmi; kami tidak meminta donasi lewat nomor pribadi.')],
            [$this->t('Confirm', 'Konfirmasi'), $this->t('Tell us by WhatsApp or email your name or organization, the amount, the date and what the donation is for. A proof of transfer helps us verify it.', 'Beri tahu kami lewat WhatsApp atau email: nama atau lembaga, nominal, tanggal, dan peruntukan donasi. Bukti transfer membantu proses verifikasi.')],
            [$this->t('We verify', 'Kami verifikasi'), $this->t('Authorized staff verify the funds. Then we send a confirmation of receipt and a thank-you message by WhatsApp or email.', 'Petugas yang berwenang memverifikasi dana. Setelah itu kami mengirim konfirmasi penerimaan dan ucapan terima kasih lewat WhatsApp atau email.')],
            [$this->t('Recorded and reported', 'Dicatat dan dilaporkan'), $this->t('Every donation is recorded by purpose, either a specific program or a general donation for the foundation\'s priority needs. Donor names are not shown on this website.', 'Setiap donasi dicatat sesuai peruntukannya, baik untuk program tertentu maupun donasi umum untuk kebutuhan prioritas yayasan. Nama donatur tidak ditampilkan di situs ini.')],
        ];
        $children = [];
        foreach ($steps as $i => [$title, $body]) {
            $n = $i + 1;
            $id = 'step_' . $n;
            $this->add($id, 'card-builder', ['grid' => ['cols' => 1, 'margin_bottom' => 'mb-0'], 'cards' => [
                $this->card('step_card_' . $n, [
                    $this->text($id . '_t', $title, 'text-[18px]', 'font-bold', 'text-charcoal', 'mb-2', 'font-fraunces'),
                    $this->text($id . '_b', $body, 'text-[15px]', 'font-normal', 'text-ink-soft', 'mb-0'),
                ], 'bg-white', '', 'border-0', 'border-forest', 'shadow-sm', ''),
            ]], false);
            $children[] = $id;
        }
        $this->add('steps_group', 'step-group', ['orientation' => 'horizontal', 'children' => $children, 'margin_bottom' => 'mb-8 md:mb-10'] + $this->base('step-group'));
        $this->add('steps_cta', 'button-builder', ['align' => 'center', 'stack_mobile' => true, 'buttons' => [
            $this->button('steps_donate', $this->t('Donation details', 'Rincian donasi'), 'donation', 'solid', 'foresty', 'md'),
            $this->button('steps_report', $this->t('How we report', 'Cara kami melaporkan'), 'transparency', 'outline', 'foresty', 'md'),
        ], 'margin_bottom' => 'mb-0']);
    }

    private function news(): void
    {
        $this->divider('sec_news', 'bg-mist', 'py-16 sm:py-24');
        $this->add('news_latest', 'latest-articles-builder', [
            'title' => $this->t('Latest from YSBH', 'Terbaru dari YSBH'),
            'source' => 'all', 'featured_count' => '1', 'list_layout' => 'cols', 'columns' => '3', 'limit' => '3',
            'show_image' => true, 'show_category' => true, 'show_date' => true, 'show_excerpt' => true, 'margin_bottom' => 'mb-0',
        ]);
    }

    private function closing(): void
    {
        $this->divider('sec_closing', 'bg-forest', 'py-16 sm:py-24');
        $this->add('closing_title', 'heading', ['text' => $this->t('Join us in strengthening health in Papua', 'Bergabunglah memperkuat kesehatan di Papua'), 'level' => 'h2', 'align' => 'center', 'margin_bottom' => 'mb-4 md:mb-6']);
        $this->add('closing_text', 'paragraph', ['text' => $this->p(
            'Your donation helps mothers, children and families in areas with limited access to health services.',
            'Donasi Anda membantu ibu, anak, dan keluarga di wilayah dengan keterbatasan akses layanan kesehatan.',
            true
        ), 'margin_bottom' => 'mb-8 md:mb-10']);
        $this->add('closing_cta', 'button-builder', ['align' => 'center', 'stack_mobile' => true, 'buttons' => [
            $this->button('closing_donate', $this->t('Donate', 'Donasi'), 'donation', 'solid', 'aurum', 'lg', 'heart'),
            $this->button('closing_partner', $this->t('Partner with us', 'Bermitra dengan kami'), 'contact', 'outline', 'aurum', 'lg'),
        ], 'margin_bottom' => 'mb-0']);
    }

    // ------------------------------------------------------------------ bahan blok

    private function add(string $id, string $type, array $data, bool $root = true): void
    {
        $this->blocks[$id] = ['id' => $id, 'type' => $type, 'data' => $data];
        if ($root) {
            $this->order[] = $id;
        }
    }

    /** Nilai bawaan registri untuk tipe blok (kunci yang tidak saya isi tetap berbentuk sah). */
    private function base(string $type): array
    {
        return Defaults::materialize(BlockRegistry::block($type)?->defaults ?? [], $this->locales);
    }

    private function divider(string $id, string $background, string $padding): void
    {
        $this->add($id, 'section-divider', ['background' => $background, 'text_color' => 'text-auto', 'padding' => $padding]);
    }

    /**
     * Blok Kolom (jumlah kolom = jumlah daftar anak). Anak-anak sudah ditambahkan lewat add(..., false); di sini hanya dirangkai.
     *
     * @param list<list<string>> $zones id anak per kolom
     * @param list<int> $widths lebar relatif tiap kolom
     */
    private function columns(string $id, array $zones, array $widths, string $alignY): void
    {
        $data = $this->base('multi-columns');
        $data['col_count'] = count($zones);
        $data['col_ratio'] = \App\Content\Blocks\LayoutStyle::ratioFor(array_map(fn ($i) => $widths[$i] ?? 1, array_keys($zones)));   // panel menampilkan pilihan yang benar
        $data['align_y'] = $alignY;
        $data['gap'] = 'gap-4';
        foreach ($zones as $i => $ids) {
            $data['col_' . ($i + 1) . '_zone'] = $ids;
            $data['col_' . ($i + 1) . '_zone_width'] = $widths[$i] ?? 1;
        }
        $data['margin_bottom'] = 'mb-0';
        $this->add($id, 'multi-columns', $data);
    }

    /** Kartu Builder: satu kartu satu kolom; elemen sudah berbentuk {id,type,elementType,data}. */
    private function card(string $id, array $elements, string $bg, string $url, string $borderWidth = 'border-0', string $borderColor = 'border-forest', string $shadow = 'shadow-sm', string $hover = 'hover:-translate-y-1'): array
    {
        return [
            'id' => $id,
            'layout' => ['type' => 'row', 'children' => [['id' => $id . '_col', 'type' => 'column', 'width' => 1, 'align' => 'items-start', 'children' => $elements]]],
            'container' => [
                'bg' => $bg, 'padding' => 'p-6 md:p-8', 'border_width' => $borderWidth, 'border_style' => 'border-solid', 'border_color' => $borderColor,
                'radius' => 'rounded-[14px]', 'shadow' => $shadow, 'hover' => $hover, 'align_y' => 'items-start', 'url' => $url, 'url_label' => '',
            ],
        ];
    }

    /** Elemen Teks kartu. */
    private function text(string $id, array $content, string $size, string $weight, string $color, string $margin, string $font = 'font-jakarta', string $align = ''): array
    {
        return ['id' => $id, 'type' => 'element', 'elementType' => 'text', 'data' => ['content' => $content, 'style' => [
            'is_pill' => false, 'font' => $font, 'weight' => $weight, 'size' => $size, 'text_transform' => 'normal-case', 'effects' => [], 'align' => $align,
            'pill_bg' => 'bg-aurum-soft', 'pill_radius' => 'rounded-md', 'color' => $color, 'margin' => $margin,
        ]]];
    }

    /** Elemen Ikon kartu. */
    private function icon(string $id, string $icon, string $bg, string $color): array
    {
        return ['id' => $id, 'type' => 'element', 'elementType' => 'icon', 'data' => ['content' => ['icon' => $icon], 'style' => [
            'bg' => $bg, 'color' => $color, 'size' => 'w-10 h-10 md:w-12 md:h-12', 'radius' => 'rounded-full', 'margin' => 'mb-3',
        ]]];
    }

    /** Satu tombol Blok Tombol. Halaman yang belum ada = tombol tanpa tautan (tidak tampil di situs publik). */
    private function button(string $id, array $label, string $target, string $variant, string $color, string $size, string $icon = ''): array
    {
        $pageId = ($this->pageId)($target);
        $link = ['kind' => 'page', 'ref' => $pageId === null ? '' : $pageId, 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => false];

        return ['id' => $id, 'label' => $label, 'link' => $link, 'variant' => $variant, 'color' => $color, 'size' => $size, 'icon' => $icon, 'icon_position' => 'left'];
    }

    /** Blok Gambar KOSONG (tempat foto): tanpa berkas, keterangan bila ada. Bentuk sama dengan SitePages::photoToImage. */
    private function photo(array $caption, string $radius, string $maxHeight): array
    {
        return [
            'url' => '', 'media_id' => null, 'alt_text' => '', 'caption' => $caption,
            'width' => 'w-full', 'align' => 'mx-auto', 'radius' => $radius, 'height' => 'h-auto', 'max_height' => $maxHeight, 'object_fit' => 'object-cover',
            'margin_bottom' => 'mb-0',
        ];
    }

    // ------------------------------------------------------------------ teks dua bahasa

    /** Peta bahasa => teks polos; bahasa selain EN/ID kosong (CMS memakai bahasa bawaan). */
    private function t(string $en, string $id): array
    {
        $out = [];
        foreach ($this->locales as $l) {
            $out[$l] = $l === 'en' ? $en : ($l === 'id' ? $id : '');
        }

        return $out;
    }

    /** Peta bahasa => paragraf HTML (<p>…</p>); $center = rata tengah. */
    private function p(string $en, string $id, bool $center = false): array
    {
        $open = $center ? '<p style="text-align:center">' : '<p>';
        $out = [];
        foreach ($this->t($en, $id) as $l => $text) {
            $out[$l] = $text === '' ? '' : (str_starts_with($text, '<p') ? $text : $open . $text . '</p>');
        }
        // tautan yang dibungkus tag <a> saja sudah berupa isi paragraf: tetap dibungkus <p> supaya rata tengah berlaku
        return $out;
    }

    // ------------------------------------------------------------------ keputusan perintah

    /**
     * Beranda lama masih BELUM disentuh (sama persis dengan benih rilis 26) sehingga aman diganti tanpa --replace.
     *
     * @param array<string,mixed> $existing isi (content) halaman beranda sekarang
     * @param array<string,mixed> $seed     isi benih lama, SitePages::content(...)
     */
    public static function isUntouched(array $existing, array $seed): bool
    {
        $norm = fn (array $d) => json_encode([$d['blocks'] ?? [], $d['order'] ?? []], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        return ($existing['blocks'] ?? []) !== [] && $norm($existing) === $norm($seed);
    }

    /** Beranda ini sudah berupa susunan rilis 90 (ada blok `hero_cols`). */
    public static function isCurrent(array $existing): bool
    {
        return isset($existing['blocks']['hero_cols']) && isset($existing['blocks']['sec_closing']);
    }
}
