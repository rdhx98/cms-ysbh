<?php

namespace App\Content;

use App\Content\Links\LinkResolver;

/**
 * Isi kaki halaman (footer) situs publik yang diatur dari CMS (rilis 55). MURNI: tanpa basis data, tanpa Laravel.
 * Tata letak tetap milik berkas Blade landing (opsional/footer.blade.php); yang berubah hanya ISINYA.
 *
 * Bentuk data (disimpan sebagai JSON di tabel site_settings, kunci "footer"). Teks yang diterjemahkan = peta bahasa {"id": "…", "en": "…"}:
 *   description       paragraf di bawah nama yayasan
 *   columns[2]        {heading: peta, links: [{label: peta, url: "/about-us", new_tab: bool}]}   (maks. 8 tautan per kolom)
 *   contact_heading   judul kolom kontak
 *   contacts[]        {type: phone|address|email|social, text: peta, url: "https://…", badge: "IG"}   (maks. 8)
 *                       phone/email: hanya `text` (nomor / surel); alamat tel:/mailto: dibentuk sendiri dan SAMA di semua bahasa
 *                       address/social: `text` per bahasa + `url` http(s) opsional; social memakai `badge` (maks. 4 huruf) sebagai ikon
 *   copyright         "© {year} Yayasan …"  ({year} diganti tahun berjalan)
 *   tagline           slogan di kanan bawah
 *
 * `url` tautan = SATU isian untuk semua bahasa, sama seperti menu (NavLinks): "/about-us" diarahkan ke versi bahasa pembaca saat tampil.
 * Berbeda dengan menu: kolom ini disimpan apa adanya setelah divalidasi MenuTarget (jalur "/", http(s), mailto:, tel:, #anchor).
 */
final class SiteFooter
{
    public const KEY = 'footer';
    public const COLUMNS = 2;
    public const MAX_LINKS = 8;
    public const MAX_CONTACTS = 8;
    public const CONTACT_TYPES = ['phone', 'address', 'email', 'social'];
    public const MAX_DESCRIPTION = 400;
    public const MAX_TEXT = 160;
    public const MAX_LABEL = 80;
    public const MAX_BADGE = 4;

    private const KEYS = ['description', 'columns', 'contact_heading', 'contacts', 'copyright', 'tagline'];

    private static function m(string $id, string $en): array
    {
        return ['id' => $id, 'en' => $en];
    }

    /** Isi bawaan = isi footer lama yang tertulis di Blade (dipakai sebelum ada yang disimpan dari CMS, dan bila data rusak). */
    public static function defaults(): array
    {
        $link = fn (string $id, string $en, string $url): array => ['label' => self::m($id, $en), 'url' => $url, 'new_tab' => false];

        return [
            'description' => self::m(
                'Yayasan kesehatan yang mendampingi ibu, anak, dan komunitas di wilayah terpencil Indonesia sejak 2014.',
                'A health foundation supporting mothers, children and communities in remote areas of Indonesia since 2014.',
            ),
            'columns' => [
                [
                    'heading' => self::m('Program', 'Programs'),
                    'links' => [
                        $link('Kesehatan Ibu & Anak', 'Maternal and Child Health', '/maternal-child-health-program'),
                        $link('Malaria', 'Malaria', '/malaria-program'),
                        $link('Imunisasi', 'Immunization', '/immunization-program'),
                        $link('Tuberkulosis (TBC)', 'Tuberculosis (TB)', '/tb-program'),
                        $link('HIV', 'HIV', '/hiv-program'),
                    ],
                ],
                [
                    'heading' => self::m('Yayasan', 'Foundation'),
                    'links' => [
                        $link('Tentang Kami', 'About Us', '/about-us'),
                        $link('Kredibilitas', 'Credibility', '/credibility'),
                        $link('Dampak Kami', 'Our Impact', '/impact'),
                        $link('Transparansi', 'Transparency', '/transparency'),
                        $link('Hubungi Kami', 'Contact Us', '/contact'),
                    ],
                ],
            ],
            'contact_heading' => self::m('Info Kontak', 'Contact'),
            'contacts' => [
                ['type' => 'phone', 'text' => self::m('+62 823 8229 5759', '+62 823 8229 5759'), 'url' => '', 'badge' => ''],
                ['type' => 'address', 'text' => self::m('BTN Umum Blok I No. 155, Jayapura', 'BTN Umum Blok I No. 155, Jayapura'), 'url' => 'https://maps.app.goo.gl/TMx2eBRxoCpo5gkv9', 'badge' => ''],
                ['type' => 'email', 'text' => self::m('contact@ysbh.org', 'contact@ysbh.org'), 'url' => '', 'badge' => ''],
                ['type' => 'social', 'text' => self::m('@ysbhusada', '@ysbhusada'), 'url' => 'https://www.instagram.com/ysbhusada/', 'badge' => 'IG'],
            ],
            'copyright' => self::m('© {year} Yayasan Sinar Bhakti Husada', '© {year} Yayasan Sinar Bhakti Husada'),
            'tagline' => self::m('Dibuat dengan bhakti, untuk sinar yang terus menyala.', 'Made with devotion, so the light keeps shining.'),
        ];
    }

    /**
     * Data tersimpan (teks JSON atau larik) -> data lengkap. Kunci yang tidak ada memakai isi bawaan; JSON rusak / bukan larik = semua bawaan.
     * Kunci yang ADA dihormati apa adanya (kolom dikosongkan sengaja tetap kosong).
     */
    public static function resolve(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        $out = self::defaults();
        if (!is_array($raw)) {
            return $out;
        }
        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $raw) && is_array($raw[$key])) {
                $out[$key] = $raw[$key];
            }
        }

        return $out;
    }

    /**
     * Teks untuk bahasa pembaca: bahasanya sendiri, lalu bahasa bawaan, lalu bahasa lain yang terisi ('' bila semuanya kosong).
     *
     * @param string[] $locales
     */
    public static function pick(mixed $map, string $locale, string $default, array $locales): string
    {
        return Names::of($map, $locale, array_values(array_unique([$default, ...$locales])));
    }

    /** Teks polos satu baris: tanpa tag HTML dan karakter kontrol, spasi berurutan dipadatkan. */
    public static function plain(mixed $value): string
    {
        $text = is_scalar($value) ? (string) $value : '';
        $text = strip_tags($text);
        $text = preg_replace('/[\x00-\x1f\x7f]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    /** Alamat aman untuk href: jalur "/", #anchor, http(s), mailto:, tel:. Selain itu "#". Dipakai juga saat tampil (data bisa diubah di luar CMS). */
    public static function safeHref(string $href): string
    {
        $href = trim($href);
        if ($href === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $href)) {
            return '#';
        }

        return preg_match('#^(?:https?://[^/]|/(?!/)|\#|mailto:|tel:)#i', $href) ? $href : '#';
    }

    /**
     * Data dari formulir -> data yang boleh disimpan. Galat dikumpulkan (tidak berhenti di yang pertama); simpan hanya bila `errors` kosong.
     *
     * @param string[] $locales
     * @param string[] $indexSlugs slug daftar artikel semua bahasa (untuk MenuTarget)
     * @return array{data:array,errors:list<string>}
     */
    public static function sanitize(mixed $raw, array $locales, string $default, array $indexSlugs = ['articles'], string $articlesSlug = 'articles'): array
    {
        $raw = is_array($raw) ? $raw : [];
        $all = array_values(array_unique([$default, ...$locales]));
        $errors = [];

        $map = function (mixed $value, int $max, string $what, bool $required = false) use ($all, $default, &$errors): array {
            $value = is_array($value) ? $value : [];
            $out = [];
            foreach ($all as $l) {
                $text = self::plain($value[$l] ?? '');
                if (mb_strlen($text) > $max) {
                    $errors[] = "$what (" . strtoupper($l) . "): terlalu panjang (maksimal $max karakter).";
                    $text = mb_substr($text, 0, $max);
                }
                $out[$l] = $text;
            }
            if ($required && $out[$default] === '') {
                $errors[] = "$what: isi bahasa bawaan (" . strtoupper($default) . ').';
            }

            return $out;
        };
        $any = fn (array $m): bool => array_filter($m, fn ($t) => $t !== '') !== [];

        $columns = [];
        $rawColumns = is_array($raw['columns'] ?? null) ? array_values($raw['columns']) : [];
        for ($c = 0; $c < self::COLUMNS; $c++) {
            $col = is_array($rawColumns[$c] ?? null) ? $rawColumns[$c] : [];
            $n = $c + 1;
            $links = [];
            $rawLinks = is_array($col['links'] ?? null) ? array_values($col['links']) : [];
            if (count($rawLinks) > self::MAX_LINKS) {
                $errors[] = "Kolom $n: maksimal " . self::MAX_LINKS . ' tautan.';
                $rawLinks = array_slice($rawLinks, 0, self::MAX_LINKS);
            }
            foreach ($rawLinks as $i => $link) {
                $link = is_array($link) ? $link : [];
                $where = "Kolom $n, tautan " . ($i + 1);
                $label = $map($link['label'] ?? [], self::MAX_LABEL, "$where, label");
                $url = trim(is_scalar($link['url'] ?? null) ? (string) $link['url'] : '');
                if (!$any($label) && $url === '') {
                    continue;   // baris kosong dilewati
                }
                if ($label[$default] === '') {
                    $errors[] = "$where: isi label bahasa bawaan (" . strtoupper($default) . ').';
                }
                $clean = null;
                if ($url === '') {
                    $errors[] = "$where: pilih atau isi tujuan.";
                } else {
                    $built = self::target($url, $locales, $default, $indexSlugs, $articlesSlug);
                    if ($built['error'] !== null) {
                        $errors[] = "$where: " . $built['error'];
                    }
                    $clean = $built['url'];
                }
                $links[] = ['label' => $label, 'url' => $clean ?? $url, 'new_tab' => !empty($link['new_tab'])];
            }
            $columns[] = ['heading' => $map($col['heading'] ?? [], self::MAX_TEXT, "Kolom $n, judul"), 'links' => $links];
        }

        $contacts = [];
        $rawContacts = is_array($raw['contacts'] ?? null) ? array_values($raw['contacts']) : [];
        if (count($rawContacts) > self::MAX_CONTACTS) {
            $errors[] = 'Kontak: maksimal ' . self::MAX_CONTACTS . ' baris.';
            $rawContacts = array_slice($rawContacts, 0, self::MAX_CONTACTS);
        }
        foreach ($rawContacts as $i => $contact) {
            $contact = is_array($contact) ? $contact : [];
            $where = 'Kontak ' . ($i + 1);
            $type = is_scalar($contact['type'] ?? null) ? (string) $contact['type'] : '';
            $text = $map($contact['text'] ?? [], self::MAX_TEXT, "$where, teks");
            $url = trim(is_scalar($contact['url'] ?? null) ? (string) $contact['url'] : '');
            $badge = self::plain($contact['badge'] ?? '');
            if (!$any($text) && $url === '' && $badge === '') {
                continue;
            }
            if (!in_array($type, self::CONTACT_TYPES, true)) {
                $errors[] = "$where: jenis kontak tidak dikenal.";
                continue;
            }
            if ($text[$default] === '') {
                $errors[] = "$where: isi teks bahasa bawaan (" . strtoupper($default) . ').';
            }
            if (in_array($type, ['phone', 'email'], true)) {
                // nomor dan surel sama di semua bahasa: nilai bahasa bawaan disalin ke semuanya
                $value = $text[$default] !== '' ? $text[$default] : (array_values(array_filter($text, fn ($t) => $t !== ''))[0] ?? '');
                $text = array_fill_keys($all, $value);
                if ($value !== '' && $type === 'phone' && LinkResolver::phone($value) === null) {
                    $errors[] = "$where: nomor telepon tidak sah (minimal 3 angka).";
                }
                if ($value !== '' && $type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "$where: alamat email tidak sah.";
                }
                $url = '';
                $badge = '';
            } else {
                if ($url !== '' && (!preg_match('#^https?://#i', $url) || LinkResolver::safeUrl($url) === null)) {
                    $errors[] = "$where: tautan harus diawali https:// (atau dikosongkan).";
                }
                if ($type === 'address') {
                    $badge = '';
                } elseif (mb_strlen($badge) > self::MAX_BADGE) {
                    $errors[] = "$where: ikon teks maksimal " . self::MAX_BADGE . ' huruf.';
                    $badge = mb_substr($badge, 0, self::MAX_BADGE);
                }
            }
            $contacts[] = ['type' => $type, 'text' => $text, 'url' => $url, 'badge' => $badge];
        }

        $data = [
            'description' => $map($raw['description'] ?? [], self::MAX_DESCRIPTION, 'Deskripsi'),
            'columns' => $columns,
            'contact_heading' => $map($raw['contact_heading'] ?? [], self::MAX_TEXT, 'Judul kontak'),
            'contacts' => $contacts,
            'copyright' => $map($raw['copyright'] ?? [], self::MAX_TEXT, 'Hak cipta'),
            'tagline' => $map($raw['tagline'] ?? [], self::MAX_TEXT, 'Slogan'),
        ];

        return ['data' => $data, 'errors' => $errors];
    }

    /**
     * Tujuan tautan -> teks `url` yang disimpan, dengan aturan menu (MenuTarget). "/id/tentang-kami" disimpan "/tentang-kami".
     *
     * @return array{url:?string,error:?string}
     */
    public static function target(string $url, array $locales, string $default, array $indexSlugs, string $articlesSlug = 'articles'): array
    {
        $t = MenuTarget::parse($url, null, $indexSlugs, $locales, $default);

        return MenuTarget::build($t['kind'] ?? 'url', $t['ref'], $articlesSlug);
    }

    /**
     * Data (hasil resolve / sanitize) -> bentuk untuk formulir: semua peta memuat SEMUA bahasa, dan selalu dua kolom.
     *
     * @param string[] $locales
     */
    public static function forEditor(array $data, array $locales, string $default): array
    {
        $all = array_values(array_unique([$default, ...$locales]));
        $map = function (mixed $value) use ($all): array {
            $value = is_array($value) ? $value : [];
            $out = [];
            foreach ($all as $l) {
                $out[$l] = is_scalar($value[$l] ?? null) ? (string) $value[$l] : '';
            }

            return $out;
        };

        $columns = [];
        $rawColumns = is_array($data['columns'] ?? null) ? array_values($data['columns']) : [];
        for ($c = 0; $c < self::COLUMNS; $c++) {
            $col = is_array($rawColumns[$c] ?? null) ? $rawColumns[$c] : [];
            $links = [];
            foreach (is_array($col['links'] ?? null) ? array_values($col['links']) : [] as $link) {
                $link = is_array($link) ? $link : [];
                $links[] = ['label' => $map($link['label'] ?? []), 'url' => is_scalar($link['url'] ?? null) ? (string) $link['url'] : '', 'new_tab' => !empty($link['new_tab'])];
            }
            $columns[] = ['heading' => $map($col['heading'] ?? []), 'links' => $links];
        }
        $contacts = [];
        foreach (is_array($data['contacts'] ?? null) ? array_values($data['contacts']) : [] as $contact) {
            $contact = is_array($contact) ? $contact : [];
            $type = is_scalar($contact['type'] ?? null) ? (string) $contact['type'] : 'address';
            $contacts[] = [
                'type' => in_array($type, self::CONTACT_TYPES, true) ? $type : 'address',
                'text' => $map($contact['text'] ?? []),
                'url' => is_scalar($contact['url'] ?? null) ? (string) $contact['url'] : '',
                'badge' => is_scalar($contact['badge'] ?? null) ? (string) $contact['badge'] : '',
            ];
        }

        return [
            'description' => $map($data['description'] ?? []),
            'columns' => $columns,
            'contact_heading' => $map($data['contact_heading'] ?? []),
            'contacts' => $contacts,
            'copyright' => $map($data['copyright'] ?? []),
            'tagline' => $map($data['tagline'] ?? []),
        ];
    }

    /**
     * Data -> model tampil untuk satu bahasa. Tidak pernah melempar galat: bentuk yang rusak dilewati.
     *
     * @param string[]                   $locales
     * @param callable(string,string):string $localize (url tautan, bahasa) -> alamat bahasa itu (NavLinks lewat PublicLookup::localizeUrl)
     * @return array{description:string,columns:list<array{heading:string,links:list<array{label:string,href:string,new_tab:bool}>}>,contact_heading:string,contacts:list<array{type:string,text:string,href:?string,badge:string,external:bool}>,copyright:string,tagline:string}
     */
    public static function view(array $data, string $locale, array $locales, string $default, callable $localize, ?int $year = null): array
    {
        $text = fn (mixed $map): string => self::pick($map, $locale, $default, $locales);

        $columns = [];
        $rawColumns = is_array($data['columns'] ?? null) ? array_values($data['columns']) : [];
        foreach (array_slice($rawColumns, 0, self::COLUMNS) as $col) {
            $col = is_array($col) ? $col : [];
            $links = [];
            foreach (is_array($col['links'] ?? null) ? array_values($col['links']) : [] as $link) {
                $link = is_array($link) ? $link : [];
                $label = $text($link['label'] ?? []);
                if ($label === '') {
                    continue;
                }
                $url = is_scalar($link['url'] ?? null) ? trim((string) $link['url']) : '';
                $href = $url === '' ? '#' : self::safeHref($localize($url, $locale));
                $links[] = ['label' => $label, 'href' => $href, 'new_tab' => !empty($link['new_tab'])];
            }
            if ($links !== []) {
                $columns[] = ['heading' => $text($col['heading'] ?? []), 'links' => $links];
            }
        }

        $contacts = [];
        foreach (is_array($data['contacts'] ?? null) ? array_values($data['contacts']) : [] as $contact) {
            $contact = is_array($contact) ? $contact : [];
            $type = is_scalar($contact['type'] ?? null) ? (string) $contact['type'] : '';
            $label = $text($contact['text'] ?? []);
            if ($label === '' || !in_array($type, self::CONTACT_TYPES, true)) {
                continue;
            }
            $url = is_scalar($contact['url'] ?? null) ? trim((string) $contact['url']) : '';
            $href = match ($type) {
                'phone' => ($n = LinkResolver::phone($label)) !== null ? 'tel:' . $n : null,
                'email' => filter_var($label, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $label : null,
                default => preg_match('#^https?://#i', $url) && LinkResolver::safeUrl($url) !== null ? $url : null,
            };
            $badge = $type === 'social' ? self::plain($contact['badge'] ?? '') : '';
            if ($type === 'social' && $badge === '') {
                $badge = mb_strtoupper(mb_substr(ltrim($label, '@'), 0, 2));   // tanpa ikon teks: dua huruf pertama
            }
            $contacts[] = ['type' => $type, 'text' => $label, 'href' => $href, 'badge' => mb_substr($badge, 0, self::MAX_BADGE), 'external' => in_array($type, ['address', 'social'], true) && $href !== null];
        }

        $copyright = str_replace('{year}', (string) ($year ?? (int) date('Y')), $text($data['copyright'] ?? []));

        return [
            'description' => $text($data['description'] ?? []),
            'columns' => $columns,
            'contact_heading' => $text($data['contact_heading'] ?? []),
            'contacts' => $contacts,
            'copyright' => $copyright,
            'tagline' => $text($data['tagline'] ?? []),
        ];
    }
}
