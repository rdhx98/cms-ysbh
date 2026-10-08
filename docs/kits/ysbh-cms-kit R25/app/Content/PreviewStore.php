<?php

namespace App\Content;

use App\Content\Blocks\BlockSanitizer;

/**
 * Penyimpanan sementara untuk PRATINJAU kanvas: isi yang BELUM disimpan dititipkan di cache dengan token acak, lalu dibaca oleh
 * halaman bingkai kanvas. Tidak menyentuh record mana pun (halaman yang sedang online tidak berubah).
 *
 *  - token 40 heksadesimal acak; hanya bentuk itu yang diterima (tidak ada injeksi kunci cache);
 *  - hanya PEMILIK yang bisa membaca (pengguna yang membuatnya); pengguna lain mendapat null;
 *  - kedaluwarsa 30 menit sejak pembaruan terakhir; tiap publish memperpanjang;
 *  - data dibersihkan (BlockSanitizer) SEBELUM dititipkan: pratinjau tidak pernah merender data yang tidak akan lolos saat disimpan;
 *  - batas ukuran 2 MB.
 *
 * $cache cukup berperan sebagai Illuminate\Contracts\Cache\Repository: get($key), put($key, $value, $ttl), forget($key).
 */
final class PreviewStore
{
    public const TTL = 1800;
    public const MAX_BYTES = 2_000_000;
    private const PREFIX = 'content-preview:';

    public function __construct(private readonly object $cache)
    {
    }

    public static function make(): self
    {
        return new self(\Illuminate\Support\Facades\Cache::store());
    }

    public static function validToken(?string $token): bool
    {
        return is_string($token) && (bool) preg_match('/^[a-f0-9]{40}$/D', $token);
    }

    /**
     * @param string[] $locales
     * @return array{token:string,rev:int}
     */
    public function publish(string $type, int|string|null $userId, ?string $token, array $blocks, array $order, array $settings, array $locales, array $titles = []): array
    {
        $doc = ContentDocument::fromRaw([
            'blocks' => BlockSanitizer::clean($blocks, $locales),
            'order' => $order,
            'settings' => $settings,
        ], $locales)->toArray();

        // memakai token yang sama hanya bila MILIK pengguna ini; selain itu dibuat baru
        $existing = $this->get($token, $userId);
        $token = $existing ? (string) $token : bin2hex(random_bytes(20));
        $rev = ($existing['rev'] ?? 0) + 1;

        $payload = [
            'type' => $type,
            'user' => $userId === null ? null : (string) $userId,
            'rev' => $rev,
            'blocks' => $doc['blocks'],
            'order' => $doc['order'],
            'settings' => $doc['settings'],
            'titles' => $titles,
            'locales' => $locales,
        ];

        if (strlen((string) json_encode($payload)) > self::MAX_BYTES) {
            throw new \LengthException('Isi terlalu besar untuk pratinjau.');
        }

        $this->cache->put(self::key($token), $payload, self::TTL);

        return ['token' => $token, 'rev' => $rev];
    }

    /** Muatan bila token sah DAN milik $userId; selain itu null. Pengguna tanpa id (belum masuk) tidak pernah boleh membaca. */
    public function get(?string $token, int|string|null $userId): ?array
    {
        if (!self::validToken($token) || $userId === null) {
            return null;
        }
        $payload = $this->cache->get(self::key($token));

        return is_array($payload) && ($payload['user'] ?? null) === (string) $userId ? $payload : null;
    }

    public function forget(?string $token): void
    {
        if (self::validToken($token)) {
            $this->cache->forget(self::key($token));
        }
    }

    private static function key(string $token): string
    {
        return self::PREFIX . $token;
    }
}
