<?php

namespace App\Content;

/**
 * Pengumuman situs (rilis 85): banner (pita di atas halaman) dan popup (jendela di tengah layar), dengan jadwal tayang, tombol tautan,
 * gambar (popup), dan aturan seberapa sering tampil. Murni: tanpa basis data dan tanpa Laravel (diuji tanpa lab).
 *
 * Penyimpanan: tabel site_settings, kunci "announcement" (CMS menulis lewat AnnouncementSettings, landing membaca lewat
 * PublicLookup::announcement()). Bentuk data: {"timezone":"Asia/Jayapura","items":[ {...}, ... ]}, maksimal MAX_ITEMS pengumuman.
 *
 * Aturan tampil: yang dicentang aktif, sedang dalam jadwal (zona waktu pada data, bukan zona server), dan sesuai lingkup halaman
 * ("semua halaman" atau "beranda saja"). Dari yang lolos, yang URUTANNYA paling atas dalam daftar menang: SATU banner dan SATU popup
 * sekaligus, tidak lebih (pengunjung tidak ditumpuk jendela).
 *
 * Gambar disimpan sebagai ID media, bukan alamat (alamat dibentuk saat dibaca; tahan ganti domain).
 * Teks polos saja: tanpa HTML. Tautan memakai aturan menu/footer (MenuTarget) dan diarahkan ke bahasa pembaca.
 */
final class Announcement
{
    public const KEY = 'announcement';
    public const MAX_ITEMS = 5;
    public const MAX_TITLE = 80;
    public const MAX_BANNER = 160;
    public const MAX_POPUP = 500;
    public const MAX_BUTTON = 30;
    public const MAX_DELAY = 30;
    public const DEFAULT_TZ = 'Asia/Jayapura';

    public const TYPES = ['banner', 'popup'];
    public const TONES = ['info', 'penting', 'sorotan'];
    public const SCOPES = ['all', 'home'];
    public const FREQUENCIES = ['always', 'session', 'day', 'once'];
    /** Zona waktu yang ditawarkan di editor (Indonesia). */
    public const TIMEZONES = ['Asia/Jayapura' => 'WIT (Jayapura)', 'Asia/Makassar' => 'WITA (Makassar)', 'Asia/Jakarta' => 'WIB (Jakarta)'];

    /** Kelas Tailwind per nada (pasangan warna palet + teks di atasnya; kelas ditulis UTUH agar ikut terpindai). */
    public const TONE_CLASSES = [
        'info' => 'bg-forest text-forest-on',
        'penting' => 'bg-coral text-coral-on',
        'sorotan' => 'bg-aurum text-aurum-on',
    ];

    /** Slot palet tiap nada (untuk pratinjau di editor: warna dibaca dari Palette, bukan dari kelas Tailwind landing). */
    public const TONE_SLOTS = ['info' => 'forest', 'penting' => 'coral', 'sorotan' => 'aurum'];

    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** @return array{timezone:string,items:list<array<string,mixed>>} */
    public static function defaults(): array
    {
        return ['timezone' => self::DEFAULT_TZ, 'items' => []];
    }

    /** ID baru: "ann_" + 8 heksadesimal. */
    public static function newId(): string
    {
        return 'ann_' . bin2hex(random_bytes(4));
    }

    public static function validId(mixed $id): bool
    {
        return is_string($id) && preg_match('/^ann_[a-f0-9]{8}$/D', $id) === 1;
    }

    /**
     * Pengumuman kosong yang siap diisi.
     *
     * @param string[] $locales
     * @return array<string,mixed>
     */
    public static function blank(array $locales, string $default, ?string $id = null): array
    {
        $all = array_values(array_unique([$default, ...$locales]));
        $map = array_fill_keys($all, '');

        return [
            'id' => $id !== null && self::validId($id) ? $id : self::newId(),
            'enabled' => false,
            'type' => 'banner',
            'tone' => 'info',
            'title' => $map,
            'message' => $map,
            'button' => $map,
            'url' => '',
            'new_tab' => false,
            'image_media' => null,
            'starts_at' => '',
            'ends_at' => '',
            'scope' => 'all',
            'frequency' => 'session',
            'delay' => 2,
        ];
    }

    public static function maxMessage(string $type): int
    {
        return $type === 'popup' ? self::MAX_POPUP : self::MAX_BANNER;
    }

    /** Zona waktu sah (nama IANA yang dikenal PHP) atau bawaan. */
    public static function timezone(mixed $tz): string
    {
        $tz = is_string($tz) ? trim($tz) : '';

        return $tz !== '' && in_array($tz, \DateTimeZone::listIdentifiers(), true) ? $tz : self::DEFAULT_TZ;
    }

    /** Teks polos beberapa baris: tanpa tag HTML dan karakter kontrol (kecuali baris baru), baris kosong berurutan dipadatkan. */
    public static function multiline(mixed $value): string
    {
        $t = is_scalar($value) ? (string) $value : '';
        $t = strip_tags(str_replace(["\r\n", "\r"], "\n", $t));
        $t = preg_replace('/[\x00-\x09\x0b-\x1f\x7f]+/u', ' ', $t) ?? '';
        $t = preg_replace('/[ \t\x{00a0}]+/u', ' ', $t) ?? '';
        $t = preg_replace('/ ?\n ?/u', "\n", $t) ?? '';
        $t = preg_replace('/\n{3,}/', "\n\n", $t) ?? '';

        return trim($t);
    }

    /**
     * Waktu "Y-m-d\TH:i" (juga "Y-m-d H:i" dan "Y-m-d") -> teks baku "Y-m-d\TH:i", atau '' bila kosong, atau null bila tidak sah.
     */
    public static function dateText(mixed $value): ?string
    {
        $t = is_scalar($value) ? trim((string) $value) : '';
        if ($t === '') {
            return '';
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::\d{2})?)?$/D', $t, $m)) {
            return null;
        }
        [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        $h = isset($m[4]) ? (int) $m[4] : 0;
        $mi = isset($m[5]) ? (int) $m[5] : 0;
        if (!checkdate($mo, $d, $y) || $h > 23 || $mi > 59 || $y < 2000 || $y > 2100) {
            return null;
        }

        return sprintf('%04d-%02d-%02dT%02d:%02d', $y, $mo, $d, $h, $mi);
    }

    /** Teks baku -> cap waktu UNIX di zona $tz, atau null. */
    public static function timestamp(string $text, string $tz): ?int
    {
        if ($text === '' || self::dateText($text) !== $text) {
            return null;
        }
        try {
            return (new \DateTimeImmutable($text, new \DateTimeZone($tz)))->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Data tersimpan (JSON teks atau larik) -> data lengkap. Longgar: tipe salah diabaikan, bukan galat. Pengumuman tanpa id sah diberi id
     * turunan dari urutannya (stabil selama data tidak diubah), supaya landing tetap bisa menandai "sudah ditutup".
     *
     * @return array{timezone:string,items:list<array<string,mixed>>}
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
        $out['timezone'] = self::timezone($raw['timezone'] ?? null);
        $items = is_array($raw['items'] ?? null) ? array_values($raw['items']) : [];
        foreach (array_slice($items, 0, self::MAX_ITEMS) as $i => $item) {
            if (is_array($item)) {
                $out['items'][] = self::normalizeItem($item, $i);
            }
        }

        return $out;
    }

    /** @return array<string,mixed> */
    private static function normalizeItem(array $item, int $position): array
    {
        $b = self::blank([], 'id');
        $type = is_string($item['type'] ?? null) && in_array($item['type'], self::TYPES, true) ? $item['type'] : 'banner';
        $maps = fn (string $k): array => is_array($item[$k] ?? null) ? $item[$k] : [];

        return [
            'id' => self::validId($item['id'] ?? null) ? $item['id'] : 'ann_' . substr(md5('pos' . $position), 0, 8),
            'enabled' => ($item['enabled'] ?? false) === true,
            'type' => $type,
            'tone' => is_string($item['tone'] ?? null) && in_array($item['tone'], self::TONES, true) ? $item['tone'] : 'info',
            'title' => $maps('title'),
            'message' => $maps('message'),
            'button' => $maps('button'),
            'url' => is_string($item['url'] ?? null) ? trim($item['url']) : '',
            'new_tab' => ($item['new_tab'] ?? false) === true,
            'image_media' => SiteProfile::mediaId($item['image_media'] ?? null),
            'starts_at' => self::dateText($item['starts_at'] ?? '') ?? '',
            'ends_at' => self::dateText($item['ends_at'] ?? '') ?? '',
            'scope' => is_string($item['scope'] ?? null) && in_array($item['scope'], self::SCOPES, true) ? $item['scope'] : 'all',
            'frequency' => is_string($item['frequency'] ?? null) && in_array($item['frequency'], self::FREQUENCIES, true) ? $item['frequency'] : $b['frequency'],
            'delay' => is_numeric($item['delay'] ?? null) ? max(0, min(self::MAX_DELAY, (int) $item['delay'])) : $b['delay'],
        ];
    }

    /**
     * Masukan editor -> data bersih + daftar galat. Galat dikumpulkan semuanya; simpan hanya bila `errors` kosong.
     *
     * @param string[] $locales
     * @param string[] $indexSlugs slug daftar artikel semua bahasa (untuk MenuTarget)
     * @return array{data:array<string,mixed>,errors:list<string>}
     */
    public static function sanitize(mixed $raw, array $locales, string $default, array $indexSlugs = ['articles'], string $articlesSlug = 'articles'): array
    {
        $raw = is_array($raw) ? $raw : [];
        $errors = [];
        $all = array_values(array_unique([$default, ...$locales]));
        $tz = self::timezone($raw['timezone'] ?? null);

        $items = is_array($raw['items'] ?? null) ? array_values($raw['items']) : [];
        if (count($items) > self::MAX_ITEMS) {
            $errors[] = 'Maksimal ' . self::MAX_ITEMS . ' pengumuman.';
            $items = array_slice($items, 0, self::MAX_ITEMS);
        }

        $clean = [];
        $seen = [];
        foreach ($items as $n => $item) {
            $item = is_array($item) ? $item : [];
            $where = 'Pengumuman ' . ($n + 1);
            $type = is_string($item['type'] ?? null) && in_array($item['type'], self::TYPES, true) ? $item['type'] : null;
            if ($type === null) {
                $errors[] = "$where: jenis harus banner atau popup.";
                $type = 'banner';
            }
            $enabled = filter_var($item['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $map = function (mixed $value, int $max, string $what, bool $multi = false) use ($all, $where, &$errors): array {
                $value = is_array($value) ? $value : [];
                $out = [];
                foreach ($all as $l) {
                    $t = $multi ? self::multiline($value[$l] ?? '') : SiteFooter::plain($value[$l] ?? '');
                    if (mb_strlen($t) > $max) {
                        $errors[] = "$where, $what (" . strtoupper($l) . ") terlalu panjang: maksimal $max karakter.";
                        $t = mb_substr($t, 0, $max);
                    }
                    $out[$l] = $t;
                }

                return $out;
            };

            $title = $map($item['title'] ?? [], self::MAX_TITLE, 'judul');
            $message = $map($item['message'] ?? [], self::maxMessage($type), 'isi', $type === 'popup');
            $button = $map($item['button'] ?? [], self::MAX_BUTTON, 'teks tombol');
            if ($type === 'banner') {
                $title = array_fill_keys($all, '');   // banner satu baris: tanpa judul
            }

            if ($message[$default] === '') {
                $errors[] = "$where: isi pengumuman bahasa bawaan (" . strtoupper($default) . ') wajib diisi.';
            }

            $url = trim(is_scalar($item['url'] ?? null) ? (string) $item['url'] : '');
            if ($url !== '') {
                $built = SiteFooter::target($url, $locales, $default, $indexSlugs, $articlesSlug);
                if ($built['error'] !== null) {
                    $errors[] = "$where, tombol: " . $built['error'];
                } else {
                    $url = (string) $built['url'];
                }
                if ($button[$default] === '') {
                    $errors[] = "$where: tautan terisi, jadi isi teks tombol bahasa bawaan (" . strtoupper($default) . ').';
                }
            } elseif (array_filter($button, fn ($t) => $t !== '') !== []) {
                $errors[] = "$where: teks tombol terisi tetapi tautannya kosong.";
            }

            $starts = self::dateText($item['starts_at'] ?? '');
            $ends = self::dateText($item['ends_at'] ?? '');
            if ($starts === null) {
                $errors[] = "$where: tanggal mulai tidak sah.";
                $starts = '';
            }
            if ($ends === null) {
                $errors[] = "$where: tanggal selesai tidak sah.";
                $ends = '';
            }
            if ($starts !== '' && $ends !== '' && self::timestamp($ends, $tz) <= self::timestamp($starts, $tz)) {
                $errors[] = "$where: tanggal selesai harus sesudah tanggal mulai.";
            }

            $tone = is_string($item['tone'] ?? null) && in_array($item['tone'], self::TONES, true) ? $item['tone'] : 'info';
            $scope = is_string($item['scope'] ?? null) && in_array($item['scope'], self::SCOPES, true) ? $item['scope'] : 'all';
            $frequency = is_string($item['frequency'] ?? null) && in_array($item['frequency'], self::FREQUENCIES, true) ? $item['frequency'] : 'session';

            $delay = $item['delay'] ?? 2;
            if (!is_numeric($delay) || (int) $delay < 0 || (int) $delay > self::MAX_DELAY) {
                $errors[] = "$where: jeda tampil popup antara 0 dan " . self::MAX_DELAY . ' detik.';
                $delay = 2;
            }

            $id = self::validId($item['id'] ?? null) && !isset($seen[$item['id']]) ? $item['id'] : self::newId();
            $seen[$id] = true;

            $clean[] = [
                'id' => $id,
                'enabled' => $enabled,
                'type' => $type,
                'tone' => $tone,
                'title' => $title,
                'message' => $message,
                'button' => $button,
                'url' => $url,
                'new_tab' => filter_var($item['new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'image_media' => $type === 'popup' ? SiteProfile::mediaId($item['image_media'] ?? null) : null,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'scope' => $scope,
                'frequency' => $frequency,
                'delay' => (int) $delay,
            ];
        }

        return ['data' => ['timezone' => $tz, 'items' => $clean], 'errors' => $errors];
    }

    /**
     * Data untuk editor: tiap peta memuat SEMUA bahasa (bahasa bawaan dulu).
     *
     * @param string[] $locales
     * @return array{timezone:string,items:list<array<string,mixed>>}
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
        $items = [];
        foreach (is_array($data['items'] ?? null) ? array_values($data['items']) : [] as $i => $item) {
            if (!is_array($item)) {
                continue;
            }
            $n = self::normalizeItem($item, $i);
            foreach (['title', 'message', 'button'] as $k) {
                $n[$k] = $map($n[$k]);
            }
            $items[] = $n;
        }

        return ['timezone' => self::timezone($data['timezone'] ?? null), 'items' => $items];
    }

    /**
     * Status satu pengumuman pada saat $now (cap UNIX): 'off' (tidak dicentang), 'scheduled' (belum mulai), 'expired' (sudah lewat), 'live'.
     */
    public static function status(array $item, int $now, string $tz): string
    {
        if (empty($item['enabled'])) {
            return 'off';
        }
        $starts = self::timestamp((string) ($item['starts_at'] ?? ''), $tz);
        $ends = self::timestamp((string) ($item['ends_at'] ?? ''), $tz);
        if ($starts !== null && $now < $starts) {
            return 'scheduled';
        }
        if ($ends !== null && $now >= $ends) {
            return 'expired';
        }

        return 'live';
    }

    /** Teks baku "Y-m-d\TH:i" -> "25 Okt 2026, 08.00" (bahasa Indonesia); '' bila kosong atau tidak sah. */
    public static function dateLabel(string $text): string
    {
        if ($text === '' || self::dateText($text) !== $text) {
            return '';
        }
        [$date, $time] = explode('T', $text);
        [$y, $m, $d] = array_map('intval', explode('-', $date));

        return $d . ' ' . self::MONTHS[$m - 1] . ' ' . $y . ', ' . str_replace(':', '.', $time);
    }

    /**
     * Satu kalimat tentang jadwal pengumuman SEKARANG, untuk ringkasan di editor (rilis 87), mis. "Tayang sekarang sampai 30 Okt 2026, 23.59."
     */
    public static function summary(array $item, int $now, string $tz): string
    {
        $from = self::dateLabel((string) ($item['starts_at'] ?? ''));
        $to = self::dateLabel((string) ($item['ends_at'] ?? ''));

        return match (self::status($item, $now, $tz)) {
            'off' => 'Nonaktif, tidak tampil di situs.',
            'scheduled' => 'Mulai tayang ' . $from . ($to !== '' ? ', selesai ' . $to : ', tanpa batas akhir') . '.',
            'expired' => 'Sudah berakhir pada ' . $to . '.',
            default => $to !== '' ? 'Tayang sekarang sampai ' . $to . '.' : 'Tayang sekarang, tanpa batas akhir.',
        };
    }

    /**
     * Pengumuman yang tampil: satu banner dan satu popup (yang paling atas dalam daftar dan lolos aturan), atau null.
     *
     * @return array{banner:?array<string,mixed>,popup:?array<string,mixed>}
     */
    public static function pickActive(array $data, int $now, bool $isHome): array
    {
        $tz = self::timezone($data['timezone'] ?? null);
        $out = ['banner' => null, 'popup' => null];
        foreach (is_array($data['items'] ?? null) ? array_values($data['items']) : [] as $i => $item) {
            if (!is_array($item)) {
                continue;
            }
            $item = self::normalizeItem($item, $i);
            if (self::status($item, $now, $tz) !== 'live' || ($item['scope'] === 'home' && !$isHome)) {
                continue;
            }
            if ($out[$item['type']] === null) {
                $out[$item['type']] = $item;
            }
        }

        return $out;
    }

    /**
     * Sidik isi yang tampil: berubah bila teks / tautan / gambar / nada diubah, sehingga pengunjung yang sudah menutup versi lama
     * melihat versi baru. Jadwal dan lingkup tidak ikut (memperpanjang masa tayang tidak memunculkan ulang).
     */
    public static function revision(array $item): string
    {
        $core = [$item['type'] ?? '', $item['tone'] ?? '', $item['title'] ?? [], $item['message'] ?? [], $item['button'] ?? [], $item['url'] ?? '', $item['image_media'] ?? null];

        return substr(md5((string) json_encode($core, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)), 0, 8);
    }

    /**
     * Warna latar dan teks satu nada sebagai gaya langsung (rilis 89). Memakai variabel palet (--pal-{slot}, --pal-{slot}-on) dengan cadangan
     * warna bawaan, jadi teks tetap terbaca di latar hijau walau kelas Tailwind seperti text-forest-on belum ada di CSS landing.
     */
    public static function toneStyle(string $tone): string
    {
        $slot = self::TONE_SLOTS[$tone] ?? 'forest';
        $d = Palette::defaults()[$slot];

        return 'background-color:var(--pal-' . $slot . ',' . $d['hex'] . ');color:var(--pal-' . $slot . '-on,' . Palette::on($d) . ')';
    }

    /**
     * Tampilan untuk landing pada satu bahasa. Pengumuman tanpa isi di bahasa itu (dan bahasa bawaan) dilewati. Tidak pernah melempar galat.
     *
     * @param string[]                         $locales
     * @param array<int,string>                $mediaUrls id media => alamat
     * @param callable(string,string):string   $localize (url tautan, bahasa) -> alamat bahasa itu
     * @return array{banner:?array<string,mixed>,popup:?array<string,mixed>}
     */
    public static function view(array $data, string $locale, array $locales, string $default, array $mediaUrls, callable $localize, int $now, bool $isHome): array
    {
        $text = fn (mixed $map): string => SiteFooter::pick($map, $locale, $default, $locales);
        $active = self::pickActive($data, $now, $isHome);
        $out = ['banner' => null, 'popup' => null];
        foreach ($active as $type => $item) {
            if ($item === null) {
                continue;
            }
            $message = $text($item['message']);
            if ($message === '') {
                // sengaja tidak jatuh ke pengumuman berikutnya: yang di atas sudah dipilih admin sebagai pemenang
                continue;
            }
            $label = $text($item['button']);
            $url = (string) $item['url'];
            $href = '';
            if ($url !== '' && $label !== '') {
                $href = SiteFooter::safeHref((string) $localize($url, $locale));
                if ($href === '#') {
                    $href = '';
                }
            }
            $imageId = $item['image_media'];
            $image = $type === 'popup' && $imageId !== null ? trim((string) ($mediaUrls[$imageId] ?? '')) : '';
            $out[$type] = [
                'id' => $item['id'],
                'key' => $item['id'] . '.' . self::revision($item),
                'type' => $type,
                'tone' => $item['tone'],
                'class' => self::TONE_CLASSES[$item['tone']] ?? self::TONE_CLASSES['info'],
                'style' => self::toneStyle($item['tone']),
                'title' => $type === 'popup' ? $text($item['title']) : '',
                'message' => $message,
                'button' => $href !== '' ? $label : '',
                'href' => $href,
                'new_tab' => $href !== '' && !empty($item['new_tab']),
                'image' => $image,
                'frequency' => $item['frequency'],
                'delay' => (int) $item['delay'],
            ];
        }

        return $out;
    }

    /** Semua ID media yang dirujuk (unik, terurut). @return list<int> */
    public static function mediaIds(array $data): array
    {
        $ids = [];
        foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item) {
            $id = is_array($item) ? SiteProfile::mediaId($item['image_media'] ?? null) : null;
            if ($id !== null) {
                $ids[$id] = $id;
            }
        }
        sort($ids);

        return array_values($ids);
    }
}
