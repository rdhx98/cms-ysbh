<?php

namespace App\Content;

/**
 * Halaman situs (rilis 25, isi rilis 26): daftar halaman yang direncanakan beserta slug dua bahasanya, dan isi awal dari SiteText (draf dua bahasa
 * yang disetujui; yang belum diputuskan tetap berpenanda Placeholders::TOKEN). MURNI. Dipakai perintah cms:seed-pages. Semua halaman dibuat
 * OFFLINE; ditinjau dan dijadikan online oleh manusia di editor (docs/HALAMAN-SITUS.md).
 *
 * Aturan nama halaman program (diputuskan pengguna): "[nama program]-program" di EN (malaria-program); di ID "program-[nama]" (program-malaria).
 * Halaman program datar (satu segmen), bukan /programs/malaria, supaya cocok dengan satu rute '/{slug}'.
 */
final class SitePages
{
    public const SNIPPET_KEY = 'legal-details';

    /**
     * @param array<string,string> $homeSlugs slug beranda per bahasa (config('cms.home_slug'))
     * @return list<array{key:string,title:array<string,string>,slug:array<string,string>,snippet?:string}>
     */
    public static function pages(array $homeSlugs = ['en' => 'home', 'id' => 'beranda']): array
    {
        return [
            ['key' => 'home', 'title' => ['en' => 'Home', 'id' => 'Beranda'], 'slug' => ['en' => $homeSlugs['en'] ?? 'home', 'id' => $homeSlugs['id'] ?? 'beranda']],
            ['key' => 'about', 'title' => ['en' => 'About Us', 'id' => 'Tentang Kami'], 'slug' => ['en' => 'about-us', 'id' => 'tentang-kami']],
            ['key' => 'programs', 'title' => ['en' => 'Our Programs', 'id' => 'Program Kami'], 'slug' => ['en' => 'programs', 'id' => 'program']],
            ['key' => 'malaria', 'title' => ['en' => 'Malaria Program', 'id' => 'Program Malaria'], 'slug' => ['en' => 'malaria-program', 'id' => 'program-malaria']],
            ['key' => 'immunization', 'title' => ['en' => 'Immunization Program', 'id' => 'Program Imunisasi'], 'slug' => ['en' => 'immunization-program', 'id' => 'program-imunisasi']],
            ['key' => 'maternal-child-health', 'title' => ['en' => 'Maternal and Child Health Program', 'id' => 'Program Kesehatan Ibu dan Anak'], 'slug' => ['en' => 'maternal-child-health-program', 'id' => 'program-kesehatan-ibu-anak']],
            ['key' => 'tb', 'title' => ['en' => 'Tuberculosis (TB) Program', 'id' => 'Program Tuberkulosis (TBC)'], 'slug' => ['en' => 'tb-program', 'id' => 'program-tbc']],
            ['key' => 'hiv', 'title' => ['en' => 'HIV Program', 'id' => 'Program HIV'], 'slug' => ['en' => 'hiv-program', 'id' => 'program-hiv']],
            ['key' => 'credibility', 'title' => ['en' => 'Credibility', 'id' => 'Kredibilitas'], 'slug' => ['en' => 'credibility', 'id' => 'kredibilitas']],
            ['key' => 'impact', 'title' => ['en' => 'Our Impact', 'id' => 'Dampak Kami'], 'slug' => ['en' => 'impact', 'id' => 'dampak']],
            ['key' => 'transparency', 'title' => ['en' => 'Transparency', 'id' => 'Transparansi'], 'slug' => ['en' => 'transparency', 'id' => 'transparansi'], 'snippet' => self::SNIPPET_KEY],
            ['key' => 'contact', 'title' => ['en' => 'Contact Us', 'id' => 'Hubungi Kami'], 'slug' => ['en' => 'contact', 'id' => 'kontak']],
        ];
    }

    /**
     * Snippet NPWP dan rekening: TEMPATNYA saja. Nilainya sengaja tidak ada di kit; menunggu keputusan yayasan.
     *
     * @return array{key:string,title:array<string,string>,description:string,text:array<string,list<string>>}
     */
    public static function legalSnippet(): array
    {
        $t = Placeholders::TOKEN;

        return [
            'key' => self::SNIPPET_KEY,
            'title' => ['en' => 'Legal and bank details', 'id' => 'Data hukum dan rekening'],
            'description' => 'Tempat NPWP dan rekening (giro/bank) yayasan. Isi hanya setelah yayasan memutuskan apa yang boleh ditampilkan. Selama masih memuat ' . $t . ', jangan dijadikan online (cms:audit-placeholders).',
            'text' => [
                'en' => ["Tax ID (NPWP): $t", "Bank / giro account: $t"],
                'id' => ["NPWP: $t", "Rekening giro/bank: $t"],
            ],
        ];
    }

    /**
     * Dokumen isi awal sebuah halaman. Bila SiteText punya teks untuk halaman itu (rilis 26): satu blok per butir (judul h1/h2/h3 sebagai blok
     * 'heading', selain itu blok 'paragraph'; daftar dan tabel tetap HTML mereka sendiri, tidak dibungkus <p>), id b01, b02, ...
     * Bila tidak ada teks: satu paragraf penanda seperti rilis 25. Blok snippet ditambahkan di akhir bila halaman memuatnya dan $snippetId ada.
     *
     * @param array{key:string,title:array<string,string>,slug:array<string,string>,snippet?:string} $page
     * @param string[] $locales
     * @return array{blocks:array<string,array<string,mixed>>,order:list<string>,settings:array<mixed>}
     */
    public static function content(array $page, array $locales, ?int $snippetId = null): array
    {
        $blocks = [];
        $order = [];
        foreach (SiteText::items((string) ($page['key'] ?? '')) as $i => [$kind, $texts]) {
            $id = sprintf('b%02d', $i + 1);
            $text = [];
            foreach ($locales as $l) {
                $t = (string) ($texts[$l] ?? '');
                $text[$l] = $t === '' || $kind !== 'p' || preg_match('/^<(ul|ol|table)\b/', $t) ? $t : '<p>' . $t . '</p>';
            }
            $data = ['text' => $text];
            if ($kind !== 'p') {
                $data['level'] = $kind;
            }
            $block = ['id' => $id, 'type' => $kind === 'p' ? 'paragraph' : 'heading', 'data' => $data];
            $blocks[$id] = self::photoToImage($block, $locales) ?? $block; // rilis 37: tempat foto = blok Gambar kosong (bukan paragraf penanda)
            $order[] = $id;
        }
        if ($blocks === []) {
            $text = [];
            foreach ($locales as $l) {
                $title = (string) ($page['title'][$l] ?? '');
                $text[$l] = $title === '' ? '' : ($l === 'id'
                    ? '<p>' . Placeholders::TOKEN . ' Tulis isi halaman "' . $title . '" di sini. Halaman ini offline sampai selesai ditulis.</p>'
                    : '<p>' . Placeholders::TOKEN . ' Write the page "' . $title . '" here. This page stays offline until it is written.</p>');
            }
            $blocks['p1'] = ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => $text]];
            $order[] = 'p1';
        }
        if (isset($page['snippet']) && $snippetId !== null) {
            $blocks['sn1'] = ['id' => 'sn1', 'type' => 'snippet', 'data' => ['snippet_id' => $snippetId]];
            $order[] = 'sn1';
        }

        return ['blocks' => $blocks, 'order' => $order, 'settings' => []];
    }

    /**
     * Paragraf penanda foto ("[ISI-DULU] <em>Photo|Foto: keterangan</em>", rilis 25/26) -> blok Gambar KOSONG (rilis 37): url kosong, keterangan
     * dua bahasa terisi (tanpa awalan "Foto:" dan tanpa tag), tata letak bawaan yang sah di registri. Penulis tinggal memilih berkas di panel
     * Properti (Jelajahi File Manager). Gambar tanpa berkas tidak dicetak di situs (renderer) dan dilaporkan cms:audit-placeholders.
     * Mengembalikan null bila blok BUKAN paragraf penanda foto (tidak disentuh). Setiap bahasa yang berisi teks harus berpola foto; murni.
     *
     * @param array<string,mixed> $block
     * @param string[] $locales
     * @return array<string,mixed>|null
     */
    public static function photoToImage(array $block, array $locales): ?array
    {
        if (($block['type'] ?? '') !== 'paragraph' || !is_array($block['data']['text'] ?? null)) {
            return null;
        }
        $caption = [];
        $n = 0;
        foreach ($locales as $l) {
            $t = trim((string) ($block['data']['text'][$l] ?? ''));
            if ($t === '') {
                $caption[$l] = '';
                continue;
            }
            if (!preg_match('~^(?:<p>)?\s*' . preg_quote(Placeholders::TOKEN, '~') . '\s*<em>\s*(?:Photo|Foto)\s*:\s*(.*?)\s*</em>\s*(?:</p>)?$~su', $t, $m)) {
                return null;
            }
            $caption[$l] = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $n++;
        }
        if ($n === 0) {
            return null;
        }

        return ['id' => (string) ($block['id'] ?? ''), 'type' => 'image', 'data' => [
            'url' => '', 'alt_text' => '', 'caption' => $caption,
            'width' => 'w-full', 'align' => 'mx-auto', 'radius' => 'rounded-lg', 'height' => 'h-auto', 'max_height' => 'max-h-[32rem]', 'object_fit' => 'object-cover',
            'margin_bottom' => Blocks\Spacing::DEFAULT,
        ]];
    }

    /**
     * Ganti paragraf penanda foto di dokumen yang SUDAH dibuat (cms:convert-photos). Hanya blok yang masih berupa paragraf penanda
     * utuh yang diganti (id dan urutan sama); blok yang sudah Anda sunting tidak disentuh.
     *
     * @param array<string,mixed> $doc dokumen {blocks, order, settings}
     * @param string[] $locales
     * @return array{0:array<string,mixed>,1:int} [dokumen baru, jumlah diganti]
     */
    public static function convertPhotos(array $doc, array $locales): array
    {
        $n = 0;
        foreach (is_array($doc['blocks'] ?? null) ? $doc['blocks'] : [] as $id => $block) {
            if (is_array($block) && ($img = self::photoToImage($block + ['id' => (string) $id], $locales)) !== null) {
                $doc['blocks'][$id] = $img;
                $n++;
            }
        }

        return [$doc, $n];
    }

    /** Dokumen isi snippet NPWP/rekening: satu paragraf per baris. */
    public static function snippetContent(array $locales): array
    {
        $s = self::legalSnippet();
        $blocks = [];
        $order = [];
        $n = max(array_map('count', $s['text']));
        for ($i = 0; $i < $n; $i++) {
            $id = 'p' . ($i + 1);
            $text = [];
            foreach ($locales as $l) {
                $text[$l] = isset($s['text'][$l][$i]) ? '<p>' . $s['text'][$l][$i] . '</p>' : '';
            }
            $blocks[$id] = ['id' => $id, 'type' => 'paragraph', 'data' => ['text' => $text]];
            $order[] = $id;
        }

        return ['blocks' => $blocks, 'order' => $order, 'settings' => []];
    }

    /**
     * Masalah pada daftar halaman: judul kosong, slug tidak sah/terlarang (per bahasa), atau slug kembar dalam satu bahasa.
     *
     * @param list<array{key:string,title:array<string,string>,slug:array<string,string>}> $pages
     * @param string[] $locales
     * @param mixed $reservedConfig config('cms.reserved_slugs')
     * @param mixed $indexSlugs config('cms.articles_index_slug')
     * @return list<string>
     */
    public static function problems(array $pages, array $locales, string $default, mixed $reservedConfig = [], mixed $indexSlugs = null): array
    {
        $out = [];
        $seen = [];
        $reserved = is_array($reservedConfig) ? $reservedConfig : [];
        foreach ($pages as $p) {
            foreach ($locales as $l) {
                $slug = (string) ($p['slug'][$l] ?? '');
                $title = (string) ($p['title'][$l] ?? '');
                if ($title === '' || $slug === '') {
                    $out[] = "{$p['key']} ($l): judul atau slug kosong";
                    continue;
                }
                if (!Slug::isValid($slug)) {
                    $out[] = "{$p['key']} ($l): slug \"$slug\" tidak sah";
                } elseif (Slug::isReserved($slug, $reserved, Languages::indexSlug($indexSlugs, $l), $l, $locales, $default)) {
                    $out[] = "{$p['key']} ($l): slug \"$slug\" terlarang";
                }
                if (isset($seen["$l|$slug"])) {
                    $out[] = "{$p['key']} ($l): slug \"$slug\" kembar dengan {$seen["$l|$slug"]}";
                }
                $seen["$l|$slug"] = $p['key'];
            }
        }

        return $out;
    }
}
