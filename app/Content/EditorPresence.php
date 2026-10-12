<?php

namespace App\Content;

/**
 * "Sedang dibuka oleh ..." (rilis 50): siapa yang saat ini membuka sebuah record di editor. Tanpa websocket dan tanpa proses latar:
 * tab yang TERLIHAT mengirim denyut tiap 30 detik, dicatat di cache Laravel dengan masa berlaku; tab tersembunyi berhenti berdenyut dan
 * hilang sendiri setelah TTL. Hanya informasi, bukan kunci: tidak mencegah siapa pun menyimpan (konflik tetap ditangani deteksi konflik simpan).
 *
 * Satu kunci cache per record berisi peta {id pengguna => waktu denyut terakhir}. Dua denyut yang jatuh pada milidetik yang sama bisa saling
 * menimpa; akibatnya satu nama hilang paling lama sampai denyut berikutnya (<= 30 dtk). Dapat diterima untuk petunjuk.
 *
 * $cache cukup berperan sebagai Illuminate\Contracts\Cache\Repository: get($key), put($key, $value, $ttl).
 */
final class EditorPresence
{
    public const HEARTBEAT = 30;   // detik; dipakai browser
    public const TTL = 75;          // detik; 2,5 denyut: satu denyut terlewat tidak menghilangkan nama
    private const PREFIX = 'editor-presence:';

    public function __construct(private readonly object $cache)
    {
    }

    public static function make(): self
    {
        return new self(\Illuminate\Support\Facades\Cache::store());
    }

    /** Kunci aman: hanya jenis dikenal dan id numerik/string pendek. */
    public static function keyFor(string $type, int|string $recordId): ?string
    {
        $id = (string) $recordId;

        return in_array($type, ['page', 'article', 'snippet'], true) && preg_match('/^[A-Za-z0-9_-]{1,40}$/D', $id) ? self::PREFIX . $type . ':' . $id : null;
    }

    /**
     * Catat denyut pengguna ini dan kembalikan id pengguna LAIN yang masih aktif (terbaru dulu).
     *
     * @return list<string>
     */
    public function beat(string $type, int|string $recordId, int|string|null $userId, ?int $now = null): array
    {
        $key = self::keyFor($type, $recordId);
        if ($key === null || $userId === null || $userId === '') {
            return [];
        }
        $now ??= time();
        $map = $this->active($this->cache->get($key), $now);
        $map[(string) $userId] = $now;
        $this->cache->put($key, $map, self::TTL);

        unset($map[(string) $userId]);
        arsort($map);

        return array_map('strval', array_keys($map));
    }

    /** Pengguna lain yang aktif tanpa mencatat denyut (untuk tes/pembacaan). */
    public function others(string $type, int|string $recordId, int|string|null $userId, ?int $now = null): array
    {
        $key = self::keyFor($type, $recordId);
        if ($key === null) {
            return [];
        }
        $map = $this->active($this->cache->get($key), $now ?? time());
        unset($map[(string) $userId]);
        arsort($map);

        return array_map('strval', array_keys($map));
    }

    /** Buang entri kedaluwarsa dan nilai yang bukan angka (cache rusak/diubah pihak lain). @return array<string,int> */
    private function active(mixed $stored, int $now): array
    {
        $out = [];
        foreach (is_array($stored) ? $stored : [] as $uid => $at) {
            if (is_int($at) && $now - $at <= self::TTL && $at <= $now + 5) {
                $out[(string) $uid] = $at;
            }
        }

        return $out;
    }
}
