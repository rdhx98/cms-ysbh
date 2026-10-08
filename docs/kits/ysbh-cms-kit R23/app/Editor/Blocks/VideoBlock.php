<?php

namespace App\Editor\Blocks;

use App\Content\Blocks\BlockSanitizer;
use App\Content\Blocks\VideoStyle;
use App\Editor\BlockType;
use App\Editor\Field;

/**
 * Blok Video (tipe "video-builder"): sematan YouTube / Vimeo. TIDAK ada berkas video di server (hosting bersama tidak cocok untuk itu).
 * Tampilan publik: blocks/render/video-builder.blade.php, berupa "fasad klik-untuk-memutar": sebelum diklik tidak ada permintaan ke
 * pihak ketiga dan tidak ada iframe; setelah diklik baru iframe (domain mode privasi) dimuat. Alamat yang ditempel hanya dipakai untuk
 * mengambil ID (App\\Content\\Blocks\\VideoUrl); alamat yang dipasang di halaman selalu dibangun ulang dari ID itu.
 */
final class VideoBlock implements BlockModule
{
    public static function definition(): BlockType
    {
        return new BlockType('video-builder', 'Video', 'video', 'block', [
            Field::text('data.url', 'Alamat video (YouTube atau Vimeo)', ['placeholder' => 'https://www.youtube.com/watch?v=…', 'maxlength' => 300]),
            Field::i18n('data.title', 'Judul video (dibaca pembaca layar)'),
            Field::i18n('data.caption', 'Keterangan di bawah video (opsional)'),
            Field::media('data.poster', 'Pilih gambar sampul (opsional)', 'image'),
            Field::segmented('data.ratio', 'Rasio layar', ['16:9' => '16:9', '4:3' => '4:3', '1:1' => '1:1', '9:16' => '9:16', '21:9' => '21:9'], '16:9'),
            Field::segmented('data.max_width', 'Lebar', ['full' => 'Penuh', 'lg' => 'Sedang', 'md' => 'Sempit'], 'full'),
        ], defaults: [
            'url' => '',
            'title' => '@locales',
            'caption' => '@locales',
            'poster' => ['media_id' => null, 'url' => ''],
            'ratio' => '16:9',
            'max_width' => 'full',
        ]);
    }

    public static function placement(): array
    {
        return ['group' => 'Konten', 'root' => true, 'columns' => true];
    }

    public static function sanitize(array $data, array $locales): array
    {
        $poster = is_array($data['poster'] ?? null) ? $data['poster'] : [];
        $mediaId = $poster['media_id'] ?? null;
        $mediaId = (is_int($mediaId) || (is_string($mediaId) && ctype_digit($mediaId))) && (int) $mediaId > 0 ? (int) $mediaId : null;
        $url = is_scalar($data['url'] ?? null) ? trim(preg_replace('/[\x00-\x1f\x7f]/', '', (string) $data['url']) ?? '') : '';

        return [
            // apa yang diketik penulis (hanya untuk ditampilkan di inspektur); TIDAK pernah dipasang ke halaman
            'url' => mb_substr($url, 0, 300),
            'title' => BlockSanitizer::singleLines($data['title'] ?? [], $locales, 150),
            'caption' => BlockSanitizer::singleLines($data['caption'] ?? [], $locales, 300),
            'poster' => [
                'media_id' => $mediaId,
                'url' => $mediaId ? mb_substr(trim(preg_replace('/[\x00-\x1f\x7f]/', '', is_scalar($poster['url'] ?? null) ? (string) $poster['url'] : '') ?? ''), 0, 255) : '',
            ],
            'ratio' => VideoStyle::ratio($data['ratio'] ?? null),
            'max_width' => VideoStyle::width($data['max_width'] ?? null),
        ];
    }
}
