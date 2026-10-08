<?php

namespace App\Content\Blocks;

/**
 * Penguraian alamat video YouTube / Vimeo. Murni.
 *
 * PRINSIP KEAMANAN: alamat yang ditempel penulis TIDAK PERNAH dipasang ke halaman. Ia hanya dibaca untuk mengambil ID (dan jam mulai);
 * alamat sematan dan alamat tonton selalu DIBANGUN ULANG dari konstanta + ID yang lolos pola ketat. Hanya host YouTube dan Vimeo yang
 * dikenal; skema selain http/https, nama pengguna/kata sandi, port, dan host serupa ("youtube.com.evil.test") ditolak.
 * Sematan memakai domain mode privasi (youtube-nocookie.com; Vimeo dengan dnt=1).
 */
final class VideoUrl
{
    private const YT_HOSTS = ['youtube.com', 'youtube-nocookie.com', 'youtu.be'];
    private const VIMEO_HOSTS = ['vimeo.com', 'player.vimeo.com'];
    private const MAX_START = 86399;

    /**
     * @return array{provider:'youtube'|'vimeo',id:string,hash:?string,start:int}|null  null bila bukan alamat YouTube/Vimeo yang didukung
     */
    public static function parse(mixed $input): ?array
    {
        if (!is_string($input)) {
            return null;
        }
        $url = trim($input);
        if ($url === '' || strlen($url) > 300 || preg_match('/[\x00-\x20\x7f"<>\\\\^`{|}]/', $url)) {
            return null;
        }
        if (!preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            if (str_starts_with($url, '/')) {
                return null; // //host atau /jalur
            }
            $url = 'https://' . $url;
        }

        $p = parse_url($url);
        if ($p === false || !isset($p['host']) || !in_array(strtolower($p['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }
        if (isset($p['user']) || isset($p['pass']) || isset($p['port'])) {
            return null;
        }

        $host = (string) preg_replace('/^(?:www|m)\./', '', strtolower($p['host']));
        $path = $p['path'] ?? '';
        parse_str($p['query'] ?? '', $query);
        parse_str($p['fragment'] ?? '', $fragment);
        $start = self::seconds($query['t'] ?? $query['start'] ?? $fragment['t'] ?? null);

        if (in_array($host, self::YT_HOSTS, true)) {
            $id = null;
            if ($host === 'youtu.be') {
                $id = preg_match('#^/([A-Za-z0-9_-]{11})/?$#D', $path, $m) ? $m[1] : null;
            } elseif ($path === '/watch' || $path === '/watch/') {
                $v = $query['v'] ?? null;
                $id = is_string($v) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $v) ? $v : null;
            } elseif (preg_match('#^/(?:embed|shorts|live|v)/([A-Za-z0-9_-]{11})/?$#D', $path, $m)) {
                $id = $m[1];
            }

            return $id === null ? null : ['provider' => 'youtube', 'id' => $id, 'hash' => null, 'start' => $start];
        }

        if (in_array($host, self::VIMEO_HOSTS, true)) {
            $id = $hash = null;
            if ($host === 'vimeo.com' && preg_match('#^/(\d{5,12})(?:/([a-f0-9]{6,20}))?/?$#D', $path, $m)) {
                $id = $m[1];
                $hash = $m[2] ?? null;
            } elseif ($host === 'player.vimeo.com' && preg_match('#^/video/(\d{5,12})/?$#D', $path, $m)) {
                $id = $m[1];
            }
            if ($id === null) {
                return null;
            }
            $h = $query['h'] ?? null;
            $hash ??= is_string($h) && preg_match('/^[a-f0-9]{6,20}$/D', $h) ? $h : null;

            return ['provider' => 'vimeo', 'id' => $id, 'hash' => $hash, 'start' => $start];
        }

        return null;
    }

    /** Alamat sematan (iframe src), dibangun dari komponen yang divalidasi ulang. Kosong bila komponen tidak sah. */
    public static function embed(array $v, bool $autoplay = true): string
    {
        if (!self::valid($v)) {
            return '';
        }
        $start = (int) $v['start'];

        if ($v['provider'] === 'youtube') {
            $qs = array_filter([$autoplay ? 'autoplay=1' : null, 'rel=0', 'modestbranding=1', 'playsinline=1', $start > 0 ? 'start=' . $start : null]);

            return 'https://www.youtube-nocookie.com/embed/' . $v['id'] . '?' . implode('&', $qs);
        }

        $qs = array_filter([$autoplay ? 'autoplay=1' : null, 'dnt=1', $v['hash'] ? 'h=' . $v['hash'] : null]);

        return 'https://player.vimeo.com/video/' . $v['id'] . '?' . implode('&', $qs) . ($start > 0 ? '#t=' . $start . 's' : '');
    }

    /** Alamat halaman video di penyedia (untuk tautan cadangan tanpa JavaScript). Dibangun dari komponen yang divalidasi ulang. */
    public static function watch(array $v): string
    {
        if (!self::valid($v)) {
            return '';
        }
        $start = (int) $v['start'];

        if ($v['provider'] === 'youtube') {
            return 'https://www.youtube.com/watch?v=' . $v['id'] . ($start > 0 ? '&t=' . $start . 's' : '');
        }

        return 'https://vimeo.com/' . $v['id'] . ($v['hash'] ? '/' . $v['hash'] : '') . ($start > 0 ? '#t=' . $start . 's' : '');
    }

    public static function providerLabel(string $provider): string
    {
        return $provider === 'vimeo' ? 'Vimeo' : 'YouTube';
    }

    private static function valid(array $v): bool
    {
        $start = $v['start'] ?? 0;

        return ($v['provider'] ?? null) === 'youtube'
            ? is_string($v['id'] ?? null) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $v['id']) === 1 && is_int($start) && $start >= 0 && $start <= self::MAX_START
            : (($v['provider'] ?? null) === 'vimeo'
                && is_string($v['id'] ?? null) && preg_match('/^\d{5,12}$/D', $v['id']) === 1
                && (($v['hash'] ?? null) === null || (is_string($v['hash']) && preg_match('/^[a-f0-9]{6,20}$/D', $v['hash']) === 1))
                && is_int($start) && $start >= 0 && $start <= self::MAX_START);
    }

    /** "90", "90s", "1m30s", "1h2m3s" -> detik; selain itu atau di atas 24 jam -> 0. */
    private static function seconds(mixed $v): int
    {
        if (!is_string($v)) {
            return 0;
        }
        $v = strtolower(trim($v));
        $total = 0;
        if (preg_match('/^(\d{1,6})s?$/D', $v, $m)) {
            $total = (int) $m[1];
        } elseif ($v !== '' && preg_match('/^(?:(\d{1,3})h)?(?:(\d{1,4})m)?(?:(\d{1,6})s)?$/D', $v, $m)) {
            $total = (int) ($m[1] ?? 0) * 3600 + (int) ($m[2] ?? 0) * 60 + (int) ($m[3] ?? 0);
        }

        return $total > 0 && $total <= self::MAX_START ? $total : 0;
    }
}
