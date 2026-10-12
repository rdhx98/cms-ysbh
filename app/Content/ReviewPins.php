<?php

namespace App\Content;

/**
 * Pesan review yang menempel pada BLOK (rilis 50). Murni, tanpa Laravel.
 *
 * Penanda = id blok (bukan koordinat), jadi memindahkan blok membawa penandanya; menduplikat blok memberi id baru dan tidak membawa pesan.
 * Saat pesan dikirim, jenis dan cuplikan blok DIPOTRET di server (kolom block_type / block_excerpt); bila blok dihapus kemudian, pesan
 * tetap bisa dibaca dengan potretan itu ("Blok sudah dihapus"). Pesan hanya bisa menempel pada blok (id yang ada di isi artikel), tidak pada koordinat bebas.
 *
 * "Selesai" adalah baris riwayat tersendiri (aksi resolve / reopen dengan ref_id = id pesan): riwayat hanya bertambah, keadaan sekarang = peristiwa terakhir.
 */
final class ReviewPins
{
    private const ID = '/^[A-Za-z0-9_-]{1,64}$/D';

    /**
     * Periksa blok yang dituju sebuah pesan terhadap isi artikel SAAT INI (di server) dan potret jenis + cuplikannya.
     *
     * @param array<string,mixed> $content peta id => blok ({id, type, data}) seperti properti `content` builder
     * @param string[] $langs
     * @return array{ok:bool,error:?string,block_id:?string,block_type:?string,block_excerpt:?string}
     */
    public static function anchor(?string $blockId, array $content, array $langs = ['id', 'en']): array
    {
        $fail = fn (string $e) => ['ok' => false, 'error' => $e, 'block_id' => null, 'block_type' => null, 'block_excerpt' => null];
        if ($blockId === null || $blockId === '') {
            return ['ok' => true, 'error' => null, 'block_id' => null, 'block_type' => null, 'block_excerpt' => null];
        }
        if (!preg_match(self::ID, $blockId) || !isset($content[$blockId]) || !is_array($content[$blockId])) {
            return $fail('Blok yang ditandai tidak ditemukan di artikel ini.');
        }
        $block = $content[$blockId];
        $type = is_string($block['type'] ?? null) ? substr(preg_replace('/[^a-z0-9-]/', '', strtolower($block['type'])) ?? '', 0, 40) : '';
        if ($type === '') {
            return $fail('Blok yang ditandai tidak ditemukan di artikel ini.');
        }

        return ['ok' => true, 'error' => null, 'block_id' => $blockId, 'block_type' => $type, 'block_excerpt' => BlockExcerpt::of($block, $langs, 160)];
    }

    /**
     * Keadaan semua pesan berpenanda dari seluruh riwayat (urut id naik: pesan, lalu resolve/reopen-nya).
     *
     * @param list<array<string,mixed>> $rows baris article_reviews: id, action, user_id, note, block_id, block_type, block_excerpt, ref_id, created_at
     * @param string[] $liveBlockIds id blok yang ada di isi artikel sekarang
     * @return array{pins:array<int,array<string,mixed>>,pending:int,blocks:array<string,array{open:int,total:int}>}
     */
    public static function summarize(array $rows, array $liveBlockIds): array
    {
        $live = array_flip($liveBlockIds);
        $pins = [];
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            $action = (string) ($r['action'] ?? '');
            $blockId = $r['block_id'] ?? null;
            if ($action === 'note' && is_string($blockId) && $blockId !== '') {
                $pins[$id] = [
                    'id' => $id, 'blockId' => $blockId, 'type' => (string) ($r['block_type'] ?? ''), 'excerpt' => (string) ($r['block_excerpt'] ?? ''),
                    'note' => (string) ($r['note'] ?? ''), 'gone' => !isset($live[$blockId]),
                    'resolved' => false, 'resolvedBy' => null, 'resolvedAt' => null,
                ];
            } elseif (in_array($action, ['resolve', 'reopen'], true)) {
                $ref = (int) ($r['ref_id'] ?? 0);
                if (isset($pins[$ref])) {
                    $done = $action === 'resolve';
                    $pins[$ref]['resolved'] = $done;
                    $pins[$ref]['resolvedBy'] = $done ? ($r['user_id'] ?? null) : null;
                    $pins[$ref]['resolvedAt'] = $done ? ($r['created_at'] ?? null) : null;
                }
            }
        }

        $pending = 0;
        $blocks = [];
        foreach ($pins as $p) {
            if (!$p['resolved']) {
                $pending++;
            }
            if (!$p['gone']) {
                $blocks[$p['blockId']] ??= ['open' => 0, 'total' => 0];
                $blocks[$p['blockId']]['total']++;
                if (!$p['resolved']) {
                    $blocks[$p['blockId']]['open']++;
                }
            }
        }

        return ['pins' => $pins, 'pending' => $pending, 'blocks' => $blocks];
    }

    /** Kalimat peringatan sebelum menerbitkan; '' bila tidak ada yang tertunda. Tidak memblokir: hanya mengingatkan. */
    public static function warning(int $pending): string
    {
        return $pending > 0 ? 'Masih ada ' . $pending . ' pesan berpenanda yang belum selesai.' : '';
    }
}
