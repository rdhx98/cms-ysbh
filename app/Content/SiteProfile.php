<?php

namespace App\Content;

/**
 * Profil situs & SEO (rilis 83): nama situs, slogan, deskripsi bawaan, logo / favicon / gambar berbagi (Open Graph), akun X (Twitter),
 * penanda noindex, dan JSON-LD Organization. Murni: tanpa basis data dan tanpa Laravel (diuji tanpa lab).
 *
 * Penyimpanan: tabel site_settings, kunci "profile" (CMS menulis lewat ProfileSettings, landing membaca lewat PublicLookup::profile()).
 * KONTAK dan SOSIAL tidak diulang di sini: sudah ada di footer (SiteFooter), JSON-LD memakai data itu.
 *
 * Gambar disimpan sebagai ID media (bilangan bulat), BUKAN alamat: alamat dibentuk saat dibaca lewat model Media (MEDIA_URL lingkungan saat ini),
 * jadi tidak rusak saat domain / lingkungan berganti.
 *
 * noindex: nilai tersimpan hanya bisa MENAMBAH larangan. Di luar lingkungan production selalu noindex (salinan staging / lokal tidak boleh
 * terindeks walau data produksi tersalin ke sana). Belum pernah disimpan = mengikuti kebiasaan lama (production: boleh diindeks).
 */
final class SiteProfile
{
    public const KEY = 'profile';
    public const MAX_NAME = 80;
    public const MAX_TAGLINE = 120;
    public const MAX_DESCRIPTION = 300;
    public const MAX_ALT = 125;

    private const KEYS = ['name', 'tagline', 'description', 'logo_media', 'favicon_media', 'og_media', 'og_alt', 'twitter', 'noindex', 'jsonld'];

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        return [
            'name' => '',
            'tagline' => [],
            'description' => [],
            'logo_media' => null,
            'favicon_media' => null,
            'og_media' => null,
            'og_alt' => [],
            'twitter' => '',
            'noindex' => false,
            'jsonld' => true,
        ];
    }

    /**
     * Data tersimpan (JSON teks atau larik) -> data lengkap. Kunci yang ada dihormati; sisanya bawaan; tipe salah diabaikan (bukan galat).
     *
     * @return array<string,mixed>
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
            if (!array_key_exists($key, $raw)) {
                continue;
            }
            $v = $raw[$key];
            switch ($key) {
                case 'name':
                case 'twitter':
                    if (is_string($v)) {
                        $out[$key] = $v;
                    }
                    break;
                case 'tagline':
                case 'description':
                case 'og_alt':
                    if (is_array($v)) {
                        $out[$key] = $v;
                    }
                    break;
                case 'logo_media':
                case 'favicon_media':
                case 'og_media':
                    $out[$key] = self::mediaId($v);
                    break;
                default:   // noindex, jsonld
                    if (is_bool($v)) {
                        $out[$key] = $v;
                    }
            }
        }

        return $out;
    }

    /** ID media sah (bilangan bulat > 0, int atau teks angka) atau null. */
    public static function mediaId(mixed $v): ?int
    {
        if (is_int($v)) {
            return $v > 0 ? $v : null;
        }
        if (is_string($v) && preg_match('/^[1-9]\d{0,17}$/D', trim($v))) {
            return (int) trim($v);
        }

        return null;
    }

    /** Teks satu baris tanpa tag (aturan yang sama dengan footer). */
    public static function plain(mixed $value): string
    {
        return SiteFooter::plain($value);
    }

    /** Akun X: "@nama" atau "nama" (1-15 huruf/angka/garis bawah) -> "@nama"; kosong -> ""; selain itu null (tidak sah). */
    public static function twitterHandle(mixed $value): ?string
    {
        $t = is_scalar($value) ? trim((string) $value) : '';
        if ($t === '') {
            return '';
        }
        $t = str_starts_with($t, '@') ? substr($t, 1) : $t;   // satu "@" di depan saja

        return preg_match('/^[A-Za-z0-9_]{1,15}$/D', $t) ? '@' . $t : null;
    }

    /**
     * Masukan editor -> data bersih + daftar galat. Bahasa bawaan wajib untuk nama situs; teks bahasa lain boleh kosong.
     *
     * @param array<int,string> $locales
     * @return array{data:array<string,mixed>,errors:list<string>}
     */
    public static function sanitize(mixed $raw, array $locales, string $default): array
    {
        $raw = is_array($raw) ? $raw : [];
        $errors = [];
        $all = array_values(array_unique([$default, ...$locales]));

        $map = function (mixed $value, int $max, string $what) use ($all, &$errors): array {
            $value = is_array($value) ? $value : [];
            $out = [];
            foreach ($all as $l) {
                $t = self::plain($value[$l] ?? '');
                if (mb_strlen($t) > $max) {
                    $errors[] = "$what (" . strtoupper($l) . ") terlalu panjang: maksimal $max karakter.";
                    $t = mb_substr($t, 0, $max);
                }
                $out[$l] = $t;
            }

            return $out;
        };

        $name = self::plain($raw['name'] ?? '');
        if ($name === '') {
            $errors[] = 'Nama situs wajib diisi.';
        } elseif (mb_strlen($name) > self::MAX_NAME) {
            $errors[] = 'Nama situs terlalu panjang: maksimal ' . self::MAX_NAME . ' karakter.';
            $name = mb_substr($name, 0, self::MAX_NAME);
        }

        $twitter = self::twitterHandle($raw['twitter'] ?? '');
        if ($twitter === null) {
            $errors[] = 'Akun X tidak sah: gunakan 1-15 huruf, angka, atau garis bawah (contoh @ysbh).';
            $twitter = '';
        }

        $data = [
            'name' => $name,
            'tagline' => $map($raw['tagline'] ?? [], self::MAX_TAGLINE, 'Slogan'),
            'description' => $map($raw['description'] ?? [], self::MAX_DESCRIPTION, 'Deskripsi'),
            'logo_media' => self::mediaId($raw['logo_media'] ?? null),
            'favicon_media' => self::mediaId($raw['favicon_media'] ?? null),
            'og_media' => self::mediaId($raw['og_media'] ?? null),
            'og_alt' => $map($raw['og_alt'] ?? [], self::MAX_ALT, 'Teks pengganti gambar berbagi'),
            'twitter' => $twitter,
            'noindex' => filter_var($raw['noindex'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'jsonld' => filter_var($raw['jsonld'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];

        return ['data' => $data, 'errors' => $errors];
    }

    /**
     * Data untuk editor: setiap bahasa pasti punya kunci (bahasa bawaan lebih dulu).
     *
     * @param array<int,string> $locales
     * @return array<string,mixed>
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
        $data = array_replace(self::defaults(), $data);

        return [
            'name' => (string) $data['name'],
            'tagline' => $map($data['tagline']),
            'description' => $map($data['description']),
            'logo_media' => $data['logo_media'],
            'favicon_media' => $data['favicon_media'],
            'og_media' => $data['og_media'],
            'og_alt' => $map($data['og_alt']),
            'twitter' => (string) $data['twitter'],
            'noindex' => (bool) $data['noindex'],
            'jsonld' => (bool) $data['jsonld'],
        ];
    }

    /**
     * Apakah situs harus melarang pengindeksan. Di luar production SELALU ya (dan alasannya dikembalikan oleh reason()).
     */
    public static function isNoindex(array $data, bool $production): bool
    {
        return !$production || !empty($data['noindex']);
    }

    /** Alasan noindex, untuk spanduk editor: 'environment' | 'setting' | null (boleh diindeks). */
    public static function noindexReason(array $data, bool $production): ?string
    {
        if (!$production) {
            return 'environment';
        }

        return !empty($data['noindex']) ? 'setting' : null;
    }

    /**
     * Tampilan untuk landing: teks sudah dipilih per bahasa, gambar sudah berupa alamat (dari $mediaUrls: id => alamat), kosong bila tak ada.
     *
     * @param array<int,string> $locales
     * @param array<int,string> $mediaUrls id media => alamat
     * @return array{name:string,tagline:string,description:string,logo:string,favicon:string,og_image:string,og_alt:string,twitter:string,noindex:bool,jsonld:bool}
     */
    public static function view(array $data, string $locale, array $locales, string $default, array $mediaUrls, bool $production): array
    {
        $data = array_replace(self::defaults(), $data);
        $text = fn (mixed $map): string => SiteFooter::pick($map, $locale, $default, $locales);
        $url = function (mixed $id) use ($mediaUrls): string {
            $id = self::mediaId($id);
            $u = $id !== null ? ($mediaUrls[$id] ?? '') : '';

            return is_string($u) ? trim($u) : '';
        };

        return [
            'name' => self::plain($data['name']),
            'tagline' => $text($data['tagline']),
            'description' => $text($data['description']),
            'logo' => $url($data['logo_media']),
            'favicon' => $url($data['favicon_media']),
            'og_image' => $url($data['og_media']),
            'og_alt' => $text($data['og_alt']),
            'twitter' => self::twitterHandle($data['twitter']) ?? '',
            'noindex' => self::isNoindex($data, $production),
            'jsonld' => !empty($data['jsonld']),
        ];
    }

    /** Semua ID media yang dirujuk profil (unik, terurut). @return list<int> */
    public static function mediaIds(array $data): array
    {
        $ids = [];
        foreach (['logo_media', 'favicon_media', 'og_media'] as $k) {
            $id = self::mediaId($data[$k] ?? null);
            if ($id !== null) {
                $ids[$id] = $id;
            }
        }
        sort($ids);

        return array_values($ids);
    }

    /** Alamat mutlak http(s) tanpa spasi / kutip / tanda kurung sudut; selain itu null. */
    public static function absoluteUrl(mixed $url): ?string
    {
        $u = is_string($url) ? trim($url) : '';
        if (!preg_match('#^https?://[A-Za-z0-9.-]+(?::\d{1,5})?(?:/[^\s"\'<>\\\\]*)?$#D', $u)) {
            return null;
        }

        return $u;
    }

    /**
     * JSON-LD Organization (teks JSON siap dicetak di <script type="application/ld+json">) dari profil + footer. Kosong ("") bila dimatikan
     * atau tanpa nama / alamat dasar sah. Karakter < > & dan U+2028/2029 di-escape supaya aman dicetak mentah di dalam <script>.
     *
     * @param array $profile hasil view()
     * @param array $footer  hasil SiteFooter::view() (contacts: type, text, href)
     */
    public static function jsonLd(array $profile, array $footer, string $base): string
    {
        $base = rtrim(trim($base), '/');
        if (empty($profile['jsonld']) || ($profile['name'] ?? '') === '' || self::absoluteUrl($base) === null) {
            return '';
        }
        $ld = ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $profile['name'], 'url' => $base . '/'];
        if (($profile['description'] ?? '') !== '') {
            $ld['description'] = $profile['description'];
        }
        if (self::absoluteUrl($profile['logo'] ?? '') !== null) {
            $ld['logo'] = $profile['logo'];
        }
        $same = [];
        foreach (is_array($footer['contacts'] ?? null) ? $footer['contacts'] : [] as $c) {
            $type = $c['type'] ?? '';
            $href = (string) ($c['href'] ?? '');
            if ($type === 'email' && str_starts_with($href, 'mailto:')) {
                $ld['email'] = substr($href, 7);
            } elseif ($type === 'phone' && str_starts_with($href, 'tel:')) {
                $ld['telephone'] = substr($href, 4);
            } elseif ($type === 'address' && !empty($c['text'])) {
                $ld['address'] = (string) $c['text'];
            } elseif ($type === 'social' && ($u = self::absoluteUrl($href)) !== null) {
                $same[$u] = $u;
            }
        }
        if ($same !== []) {
            $ld['sameAs'] = array_values($same);
        }

        return (string) json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
