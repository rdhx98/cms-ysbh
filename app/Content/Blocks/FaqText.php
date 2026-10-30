<?php

namespace App\Content\Blocks;

/**
 * Teks jawaban FAQ (teks BIASA) -> HTML aman. Semua karakter di-escape DULU, lalu hanya struktur yang dikenal yang ditambahkan:
 *   baris kosong     -> paragraf baru
 *   baris baru       -> <br>
 *   baris "- ", "* ", "• "  -> daftar poin
 *   [Judul](alamat), https://…, www.…  -> tautan (hanya http, https, mailto, tel; tanda baca di ujung tidak ikut)
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

    /** Satu baris teks biasa -> HTML aman (escape, lalu tautan https). Dipakai sel tabel (TableStyle::cell). */
    public static function line(string $text): string
    {
        return self::inline($text);
    }

    /**
     * Escape dulu, baru tautan; urutan ini yang menjamin tidak ada HTML dari penulis. Rilis 95: tiga bentuk tautan, SATU kali lewat
     * (jadi teks di dalam [judul] tidak ditautkan lagi dan tidak pernah ada <a> di dalam <a>):
     *   [Judul tautan](alamat)   alamat = https://… | http://… | www.… (otomatis https) | mailto:nama@situs.id | tel:+62967…
     *   https://… / http://…     alamat polos
     *   www.situs.id             alamat polos tanpa skema (otomatis https)
     * Skema lain (javascript:, data:, ftp:, file:, …) TIDAK pernah menjadi tautan: tulisannya tampil apa adanya sebagai teks.
     */
    private static function inline(string $line): string
    {
        $escaped = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $stop = '(?!&quot;|&#039;|&lt;|&gt;)';

        return preg_replace_callback(
            '~\[([^\[\]\n]{1,200})\]\(([^\s()]{1,500})\)'
            . '|https?://(?:' . $stop . '[^\s<])+'
            . '|(?<![\w./@:%-])www\.(?:' . $stop . '[^\s<])+~iu',
            function (array $m): string {
                // [judul](alamat)
                if (isset($m[2]) && $m[2] !== '') {
                    $href = self::target($m[2]);
                    if ($href === null) {
                        return $m[0];   // alamat tak diizinkan: tampil sebagai teks biasa
                    }
                    $label = trim($m[1]);

                    return self::anchor($href, $label !== '' ? $label : $m[2]);
                }
                // alamat polos: tanda baca di ujung kalimat bukan bagian dari alamat (kecuali ';' penutup entitas seperti &amp;)
                $url = $m[0];
                $trail = '';
                while ($url !== '' && preg_match('/[.,;:!?)\]]$/', $url) && !preg_match('/&(?:[a-z]+|#\d+);$/i', $url)) {
                    $trail = $url[-1] . $trail;
                    $url = substr($url, 0, -1);
                }
                $href = self::target($url);
                if ($href === null) {
                    return $m[0];
                }

                return self::anchor($href, $url) . $trail;
            },
            $escaped,
        ) ?? $escaped;
    }

    /**
     * Alamat (SUDAH di-escape) -> href yang boleh dicetak, atau null. Hanya http, https, mailto, dan tel.
     * Entitas kutip/kurung sudut di dalam alamat ditolak, jadi atribut href tidak mungkin "pecah".
     */
    private static function target(string $t): ?string
    {
        if (preg_match('/&quot;|&#039;|&lt;|&gt;/', $t)) {
            return null;
        }
        if (preg_match('~^https?://[^\s/?#]+~i', $t)) {
            return $t;
        }
        if (preg_match('~^www\.[a-z0-9-]+(?:\.[a-z0-9-]+)+(?:[/?#]\S*)?$~i', $t)) {
            return 'https://' . $t;
        }
        if (preg_match('~^mailto:[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$~', $t)) {
            return $t;
        }
        if (preg_match('~^tel:\+?[0-9][0-9.-]{2,30}$~', $t)) {
            return $t;
        }

        return null;
    }

    /** $href sudah divalidasi oleh target(); $label sudah di-escape. http(s) dibuka di tab baru, mailto/tel tidak. */
    private static function anchor(string $href, string $label): string
    {
        $external = stripos($href, 'http') === 0;

        return '<a href="' . $href . '"' . ($external ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . $label . '</a>';
    }
}
