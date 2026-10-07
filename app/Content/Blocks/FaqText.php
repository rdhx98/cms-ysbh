<?php

namespace App\Content\Blocks;

/**
 * Teks jawaban FAQ (teks BIASA) -> HTML aman. Semua karakter di-escape DULU, lalu hanya struktur yang dikenal yang ditambahkan:
 *   baris kosong     -> paragraf baru
 *   baris baru       -> <br>
 *   baris "- ", "* ", "• "  -> daftar poin
 *   https://… http://…      -> tautan (hanya http/https; tanda baca di ujung tidak ikut)
 * Tidak ada HTML dari penulis yang pernah lolos: "usia <5 tahun" tampil apa adanya, "<script>" tampil sebagai teks.
 */
final class FaqText
{
    public static function toHtml(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            return '';
        }

        $html = '';
        foreach (preg_split('/\n{2,}/', $text) ?: [] as $chunk) {
            $paragraph = [];
            $items = [];
            $flushParagraph = function () use (&$paragraph, &$html) {
                if ($paragraph) {
                    $html .= '<p>' . implode('<br>', $paragraph) . '</p>';
                    $paragraph = [];
                }
            };
            $flushList = function () use (&$items, &$html) {
                if ($items) {
                    $html .= '<ul>' . implode('', array_map(fn ($i) => "<li>{$i}</li>", $items)) . '</ul>';
                    $items = [];
                }
            };

            foreach (explode("\n", $chunk) as $line) {
                if (preg_match('/^\s*[-*•]\s+(\S.*)$/u', $line, $m)) {
                    $flushParagraph();
                    $items[] = self::inline($m[1]);
                } elseif (trim($line) !== '') {
                    $flushList();
                    $paragraph[] = self::inline(trim($line));
                }
            }
            $flushParagraph();
            $flushList();
        }

        return $html;
    }

    /** Escape lalu tautan; urutan ini yang menjamin tidak ada HTML dari penulis. */
    private static function inline(string $line): string
    {
        $escaped = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return preg_replace_callback(
            '~https?://(?:(?!&quot;|&#039;|&lt;|&gt;)[^\s<])+~i',
            function (array $m): string {
                $url = $m[0];
                $trail = '';
                // tanda baca di ujung kalimat bukan bagian dari alamat (kecuali ';' penutup entitas seperti &amp;)
                while ($url !== '' && preg_match('/[.,;:!?)\]]$/', $url) && !preg_match('/&(?:[a-z]+|#\d+);$/i', $url)) {
                    $trail = $url[-1] . $trail;
                    $url = substr($url, 0, -1);
                }
                if (!preg_match('~^https?://[^\s/]+~i', $url)) {
                    return $m[0];
                }

                return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>' . $trail;
            },
            $escaped,
        ) ?? $escaped;
    }
}