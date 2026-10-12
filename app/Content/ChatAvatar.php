<?php

namespace App\Content;

/**
 * Foto profil / inisial pengirim di percakapan tab Review (rilis 51). Murni: tidak menyentuh basis data.
 *
 * Foto dicari di model pengguna proyek (kolom bawaan Laravel tidak punya foto), berurutan:
 *   1. metode reviewAvatarUrl() pada model User, bila Anda membuatnya (jalan pintas untuk skema khusus);
 *   2. atribut: avatar_url, profile_photo_url, avatar, profile_photo_path, photo_url, photo, picture, image.
 * Nilai yang diterima: alamat http(s), alamat dari akar situs ("/storage/..."), atau jalur relatif di disk "public".
 * Alamat bawaan layanan avatar pihak ketiga (ui-avatars.com, gravatar) diabaikan: tanpa foto sungguhan, tampil inisial berwarna.
 * Bila gambar gagal dimuat, browser membuang <img> dan inisial di bawahnya tampil (diatur di komponen chat-avatar).
 */
final class ChatAvatar
{
    public const TONES = 8;

    private const COLUMNS = ['avatar_url', 'profile_photo_url', 'avatar', 'profile_photo_path', 'photo_url', 'photo', 'picture', 'image'];
    private const IGNORED_HOSTS = ['ui-avatars.com', 'gravatar.com', 'secure.gravatar.com', 'www.gravatar.com'];

    /** "Wati Sari" -> "WS"; "Budi" -> "BU"; kosong -> "?" */
    public static function initials(string $name): string
    {
        $name = trim(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $name) ?? '');
        if ($name === '') {
            return '?';
        }
        $parts = preg_split('/\s+/u', $name) ?: [];
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 2));
    }

    /** Indeks warna 0..TONES-1, tetap untuk nama yang sama di semua perangkat. */
    public static function tone(string $name): int
    {
        return (int) (crc32(mb_strtolower(trim($name))) % self::TONES);
    }

    /** Alamat foto, atau null bila pengguna tidak punya (atau nilainya tidak aman/tidak dikenal). */
    public static function url(?object $user): ?string
    {
        if ($user === null) {
            return null;
        }
        try {
            $raw = method_exists($user, 'reviewAvatarUrl') ? $user->reviewAvatarUrl() : null;
            if (!is_string($raw) || trim($raw) === '') {
                $raw = null;
                foreach (self::COLUMNS as $col) {
                    $v = method_exists($user, 'getAttribute') ? $user->getAttribute($col) : ($user->{$col} ?? null);
                    if (is_string($v) && trim($v) !== '') {
                        $v = self::normalize(trim($v));
                        if ($v !== null) {
                            $raw = $v;
                            break;
                        }
                    }
                }

                return $raw;
            }

            return self::normalize(trim($raw));
        } catch (\Throwable) {
            return null;
        }
    }

    /** Menolak skema selain http(s), alamat protokol-relatif, dan layanan avatar bawaan; jalur relatif -> /storage/... */
    public static function normalize(string $v): ?string
    {
        if ($v === '' || preg_match('/[\x00-\x1f\s"\'<>]/', $v)) {
            return null;
        }
        if (preg_match('#^https?://#i', $v)) {
            $host = strtolower((string) parse_url($v, PHP_URL_HOST));
            foreach (self::IGNORED_HOSTS as $h) {
                if ($host === $h || str_ends_with($host, '.' . $h)) {
                    return null;
                }
            }

            return $v;
        }
        if (str_starts_with($v, '//') || preg_match('#^[a-z][a-z0-9+.\-]*:#i', $v) || str_contains($v, '..')) {
            return null;
        }
        if (str_starts_with($v, '/')) {
            return $v;
        }

        return '/storage/' . ltrim(preg_replace('#^(public|storage)/#', '', $v) ?? $v, '/');
    }

    /** Data siap-tampil untuk komponen chat-avatar. */
    public static function make(string $name, ?object $user): array
    {
        return ['url' => self::url($user), 'initials' => self::initials($name), 'tone' => self::tone($name)];
    }
}
