<?php

namespace App\Content\Blocks;

/**
 * Penyaring HTML teks kaya (keluaran TipTap) untuk SITUS PUBLIK. Murni PHP: tanpa ekstensi DOM, tanpa Laravel.
 *
 * Prinsip: SERIALIZER, bukan pembersih. Keluaran hanya terdiri dari (a) tag daftar-putih yang DIBANGUN ULANG dengan atribut daftar-putih
 * yang nilainya di-escape, dan (b) teks yang di-escape. Tidak ada potongan masukan yang disalin mentah, sehingga perilaku penguraian
 * peramban atas masukan yang cacat tidak bisa dimanfaatkan. Tag/atribut yang tidak dikenal DIBUANG (tag dilepas, isinya dipertahankan),
 * kecuali tag berbahaya yang dibuang BESERTA isinya (script, style, iframe, ...).
 *
 * Atribut daftar-putih ketat: tidak ada on*, x-* / @* / :* (Alpine), wire:* (Livewire), id, atau name. Tautan hanya http(s), mailto, tel,
 * #anchor, dan jalur relatif; `internal://page|article/{slug}` (tautan dari dialog TipTap) diselesaikan lewat $internal. `style` disaring
 * per properti dan per nilai (tanpa url(), expression, backslash). SVG hanya himpunan bentuk ikon (tanpa script/use/foreignObject).
 *
 * Idempoten: clean(clean(x)) === clean(x).
 */
final class RichText
{
    public const MAX_INPUT = 1_000_000;

    private const MAX_DEPTH = 40;
    private const MAX_ATTRS = 24;

    /** Tag HTML yang dipertahankan. */
    private const HTML = [
        'p', 'br', 'hr', 'div', 'span', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins', 'mark', 'small', 'sub', 'sup',
        'code', 'pre', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'a', 'img', 'figure', 'figcaption',
        'label', 'input', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',   // tabel: lazim di artikel lama (HTML warisan)
    ];
    /** Himpunan bentuk SVG untuk ikon (simpul "Eyebrow" editor). */
    private const SVG = ['svg', 'g', 'path', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'rect'];
    private const VOID = ['br', 'hr', 'img', 'input'];
    /** Dibuang BESERTA isinya: isinya diperlakukan khusus oleh peramban atau berisi kode. */
    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'noscript', 'template', 'textarea', 'title',
        'xmp', 'plaintext', 'noembed', 'noframes', 'math', 'foreignobject', 'head', 'select', 'button', 'audio', 'video', 'canvas',
    ];

    private const GLOBAL_ATTRS = ['class', 'style', 'title', 'dir'];
    private const SVG_ATTRS = [
        'viewbox' => 'viewBox', 'width' => 'width', 'height' => 'height', 'fill' => 'fill', 'stroke' => 'stroke', 'stroke-width' => 'stroke-width',
        'stroke-linecap' => 'stroke-linecap', 'stroke-linejoin' => 'stroke-linejoin', 'd' => 'd', 'cx' => 'cx', 'cy' => 'cy', 'r' => 'r', 'rx' => 'rx',
        'ry' => 'ry', 'x' => 'x', 'y' => 'y', 'x1' => 'x1', 'y1' => 'y1', 'x2' => 'x2', 'y2' => 'y2', 'points' => 'points', 'transform' => 'transform',
        'opacity' => 'opacity', 'fill-rule' => 'fill-rule', 'clip-rule' => 'clip-rule', 'focusable' => 'focusable',
    ];

    private const TAG = '~\G<([A-Za-z][A-Za-z0-9:-]*+)((?>\s++[^\s"\'<>/=\x00-\x1f]++(?>\s*+=\s*+(?>"[^"]*+"|\'[^\']*+\'|[^\s"\'=<>`]++))?)*+)\s*+(/?)>~';
    private const ATTR = '~([^\s"\'<>/=\x00-\x1f]++)(?>\s*+=\s*+(?>"([^"]*+)"|\'([^\']*+)\'|([^\s"\'=<>`]++)))?~';

    /** @var array<string,string> properti CSS yang boleh => jenis nilai */
    private const CSS = [
        'color' => 'color', 'background-color' => 'color', 'border-color' => 'color',
        'font-size' => 'size', 'font-family' => 'family', 'font-weight' => 'weight', 'font-style' => 'fstyle',
        'text-align' => 'align', 'text-decoration' => 'decoration', 'text-decoration-line' => 'decoration', 'text-indent' => 'lengths',
        'line-height' => 'lengthornum', 'letter-spacing' => 'lengths', 'border-radius' => 'lengths',
        'padding' => 'lengths', 'padding-top' => 'lengths', 'padding-right' => 'lengths', 'padding-bottom' => 'lengths', 'padding-left' => 'lengths',
        'margin' => 'lengths', 'margin-top' => 'lengths', 'margin-right' => 'lengths', 'margin-bottom' => 'lengths', 'margin-left' => 'lengths',
        'border' => 'border', 'border-width' => 'lengths', 'border-style' => 'bstyle', 'display' => 'display', 'vertical-align' => 'valign', 'opacity' => 'opacity',
    ];

    /**
     * @param callable(string,string):?string|null $internal ("page"|"article", slug) => alamat publik, atau null bila tidak bisa; null = tautan internal dibuang
     */
    public static function clean(mixed $html, ?callable $internal = null): string
    {
        if (!is_string($html) || $html === '') {
            return '';
        }
        if (strlen($html) > self::MAX_INPUT) {
            $html = substr($html, 0, self::MAX_INPUT);
        }
        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = (string) mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        }

        $out = '';
        $stack = [];   // tag terbuka yang dikeluarkan: nama
        $pos = 0;
        $n = strlen($html);
        $loops = 0;

        while ($pos < $n) {
            if (++$loops > 400000) {
                break; // pengaman terakhir
            }
            $lt = strpos($html, '<', $pos);
            if ($lt === false) {
                $out .= self::text(substr($html, $pos));
                break;
            }
            if ($lt > $pos) {
                $out .= self::text(substr($html, $pos, $lt - $pos));
            }
            if (substr_compare($html, '<!--', $lt, 4) === 0) {
                $end = strpos($html, '-->', $lt + 4);
                $pos = $end === false ? $n : $end + 3;
                continue;
            }
            if (isset($html[$lt + 1]) && ($html[$lt + 1] === '!' || $html[$lt + 1] === '?')) {
                $end = strpos($html, '>', $lt);
                $pos = $end === false ? $n : $end + 1;
                continue;
            }
            if (preg_match('~\G</([A-Za-z][A-Za-z0-9:-]*+)\s*+>~', $html, $m, 0, $lt) === 1) {
                $pos = $lt + strlen($m[0]);
                $name = strtolower($m[1]);
                $idx = array_search($name, array_reverse($stack, true), true);
                if ($idx !== false) {
                    while (count($stack) > $idx) {
                        $out .= '</' . array_pop($stack) . '>';
                    }
                }
                continue;
            }
            if (preg_match(self::TAG, $html, $m, 0, $lt) === 1) {
                $pos = $lt + strlen($m[0]);
                $name = strtolower($m[1]);
                $selfClose = $m[3] === '/';
                if (in_array($name, self::DROP_WITH_CONTENT, true)) {
                    if (!$selfClose && preg_match('~</' . preg_quote($name, '~') . '\b[^>]*+>~i', $html, $e, PREG_OFFSET_CAPTURE, $pos) === 1) {
                        $pos = $e[0][1] + strlen($e[0][0]);
                    } elseif (!$selfClose) {
                        $pos = $n;
                    }
                    continue;
                }
                $isSvg = in_array($name, self::SVG, true);
                if (!$isSvg && !in_array($name, self::HTML, true)) {
                    continue; // tag tak dikenal: dilepas, isinya tetap diproses
                }
                $attrs = self::attributes($name, $m[2], $internal);
                if ($name === 'a' && !isset($attrs['href'])) {
                    continue; // tautan tanpa tujuan yang sah: lepas tag, pertahankan teks
                }
                if ($name === 'input' && ($attrs['type'] ?? '') !== 'checkbox') {
                    continue;
                }
                if (count($stack) >= self::MAX_DEPTH && !in_array($name, self::VOID, true)) {
                    continue;
                }
                $out .= '<' . $name . self::render($attrs) . '>';
                if (in_array($name, self::VOID, true)) {
                    continue;
                }
                if ($selfClose) {
                    $out .= '</' . $name . '>';
                } else {
                    $stack[] = $name;
                }
                continue;
            }
            $out .= '&lt;'; // "<" yang bukan tag yang utuh: teks
            $pos = $lt + 1;
        }
        while ($stack) {
            $out .= '</' . array_pop($stack) . '>';
        }

        return $out;
    }

    /** Teks: entitas diuraikan lalu di-escape ulang (tidak ada penggandaan "&amp;amp;"); karakter kendali dibuang. */
    private static function text(string $s): string
    {
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/(?![\t\n\r])\p{Cc}/u', '', $s) ?? '';

        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @param array<string,string> $attrs */
    private static function render(array $attrs): string
    {
        $o = '';
        foreach ($attrs as $k => $v) {
            $o .= $v === '' && in_array($k, ['checked', 'disabled'], true) ? ' ' . $k : ' ' . $k . '="' . htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return $o;
    }

    /** @return array<string,string> atribut yang LOLOS (nama kanonik => nilai mentah belum di-escape) */
    private static function attributes(string $tag, string $raw, ?callable $internal): array
    {
        $found = [];
        if ($raw !== '' && preg_match_all(self::ATTR, $raw, $mm, PREG_SET_ORDER) !== false) {
            foreach (array_slice($mm, 0, self::MAX_ATTRS) as $a) {
                $name = strtolower($a[1]);
                if (isset($found[$name])) {
                    continue; // peramban memakai yang pertama
                }
                $val = ($a[4] ?? '') !== '' ? $a[4] : (($a[3] ?? '') !== '' ? $a[3] : ($a[2] ?? ''));
                $val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $found[$name] = (string) preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', '', $val);
            }
        }

        $out = [];
        $svg = in_array($tag, self::SVG, true);
        foreach ($found as $name => $val) {
            if ($svg) {
                if (isset(self::SVG_ATTRS[$name]) && self::svgValue($name, $val)) {
                    $out[self::SVG_ATTRS[$name]] = $val;
                } elseif ($name === 'class' && self::className($val) !== '') {
                    $out['class'] = self::className($val);
                } elseif ($name === 'aria-hidden' && in_array($val, ['true', 'false'], true)) {
                    $out['aria-hidden'] = $val;
                }
                continue;
            }
            if (in_array($name, self::GLOBAL_ATTRS, true)) {
                if ($name === 'class') {
                    if (($c = self::className($val)) !== '') { $out['class'] = $c; }
                } elseif ($name === 'style') {
                    if (($s = self::style($val)) !== '') { $out['style'] = $s; }
                } elseif ($name === 'dir') {
                    if (in_array(strtolower($val), ['ltr', 'rtl', 'auto'], true)) { $out['dir'] = strtolower($val); }
                } else {
                    $out[$name] = mb_substr($val, 0, 300);
                }
                continue;
            }
            if (preg_match('/^data-[a-z0-9]+(?:-[a-z0-9]+)*$/D', $name) && strlen($name) <= 40) {
                $out[$name] = mb_substr($val, 0, 200);
                continue;
            }
            if (preg_match('/^aria-[a-z]+$/D', $name) && strlen($name) <= 30) {
                $out[$name] = mb_substr($val, 0, 200);
                continue;
            }
            if ($tag === 'a') {
                if ($name === 'href' && ($u = self::url($val, $internal)) !== null) { $out['href'] = $u; }
                elseif ($name === 'target' && in_array(strtolower($val), ['_blank', '_self'], true)) { $out['target'] = strtolower($val); }
                elseif ($name === 'rel') {
                    $tokens = array_values(array_intersect(preg_split('/\s+/', strtolower(trim($val))) ?: [], ['noopener', 'noreferrer', 'nofollow', 'ugc', 'sponsored']));
                    if ($tokens) { $out['rel'] = implode(' ', array_unique($tokens)); }
                }
            } elseif ($tag === 'img') {
                if ($name === 'src' && ($u = self::url($val, null, false)) !== null) { $out['src'] = $u; }
                elseif ($name === 'alt') { $out['alt'] = mb_substr($val, 0, 300); }
                elseif (in_array($name, ['width', 'height'], true) && preg_match('/^\d{1,4}$/D', $val)) { $out[$name] = $val; }
                elseif ($name === 'loading' && in_array($val, ['lazy', 'eager'], true)) { $out['loading'] = $val; }
            } elseif ($tag === 'input') {
                if ($name === 'type' && strtolower($val) === 'checkbox') { $out['type'] = 'checkbox'; }
                elseif ($name === 'checked') { $out['checked'] = ''; }
            } elseif ($tag === 'ol' && $name === 'start' && preg_match('/^\d{1,5}$/D', $val)) {
                $out['start'] = $val;
            } elseif (in_array($tag, ['td', 'th'], true) && in_array($name, ['colspan', 'rowspan'], true) && preg_match('/^[1-9]\d{0,2}$/D', $val)) {
                $out[$name] = $val;
            }
        }
        if ($tag === 'a' && ($out['target'] ?? '') === '_blank') {
            $out['rel'] = implode(' ', array_unique(array_merge(['noopener', 'noreferrer'], explode(' ', $out['rel'] ?? ''))));
            $out['rel'] = trim($out['rel']);
        }
        if ($tag === 'input') {
            $out['disabled'] = ''; // pengunjung tidak boleh mengubah daftar tugas
        }
        if ($tag === 'img' && !isset($out['loading'])) {
            $out['loading'] = 'lazy';
        }

        return $out;
    }

    /** Alamat yang aman, atau null. $internal hanya untuk tautan (bukan gambar). */
    public static function url(string $value, ?callable $internal = null, bool $allowSchemes = true): ?string
    {
        $v = trim($value);
        if ($v === '' || strlen($v) > 2000 || str_contains($v, '\\')) {
            return null;
        }
        if (preg_match('~^internal://(page|article)/([a-z0-9]+(?:-[a-z0-9]+)*)$~D', $v, $m)) {
            if ($internal === null || $allowSchemes === false) {
                return null;
            }
            $u = $internal($m[1], $m[2]);

            return is_string($u) && $u !== '' && self::url($u, null) !== null ? $u : null;
        }
        $norm = preg_replace('/[\x00-\x20\x7f]+/', '', $v) ?? '';   // peramban membuang spasi/tab/baris baru di dalam skema
        if ($norm === '') {
            return null;
        }
        if (preg_match('/^([A-Za-z][A-Za-z0-9+.\-]*):/', $norm, $s)) {
            $scheme = strtolower($s[1]);
            if (!in_array($scheme, $allowSchemes ? ['http', 'https', 'mailto', 'tel'] : ['http', 'https'], true)) {
                return null;
            }
            if (in_array($scheme, ['http', 'https'], true) && !preg_match('~^https?://[^/?#\s]+~i', $norm)) {
                return null;
            }
        } elseif (str_starts_with($norm, '//')) {
            return null; // protokol-relatif: menuju host lain
        } elseif (!$allowSchemes && $norm[0] === '#') {
            return null;
        }

        return str_replace(' ', '%20', preg_replace('/[\x00-\x1f\x7f]/', '', $v) ?? '');
    }

    private static function className(string $v): string
    {
        $v = trim((string) preg_replace('/[^\x20-\x7e]/', '', $v));

        return substr(preg_replace('/\s+/', ' ', $v) ?? '', 0, 400);
    }

    private static function svgValue(string $name, string $v): bool
    {
        if (strlen($v) > 4000) {
            return false;
        }
        return match ($name) {
            'fill', 'stroke' => preg_match('/^(?:none|currentColor|#[0-9a-fA-F]{3,8}|[a-zA-Z]{3,20}|rgba?\([0-9 ,.%]+\))$/D', $v) === 1,
            'stroke-linecap' => in_array($v, ['butt', 'round', 'square'], true),
            'stroke-linejoin' => in_array($v, ['miter', 'round', 'bevel'], true),
            'fill-rule', 'clip-rule' => in_array($v, ['nonzero', 'evenodd'], true),
            'focusable' => in_array($v, ['true', 'false'], true),
            'd' => preg_match('/^[MmLlHhVvCcSsQqTtAaZz0-9eE+\-., \t\n\r]*$/D', $v) === 1,
            'transform' => preg_match('/^[A-Za-z0-9 ,.\-()]*$/D', $v) === 1,
            default => preg_match('/^[0-9eE+\-., %a-zA-Z]*$/D', $v) === 1,
        };
    }

    /** "prop: nilai; ..." -> hanya deklarasi yang lolos, dalam bentuk kanonik. */
    private static function style(string $v): string
    {
        if (strlen($v) > 1500) {
            return '';
        }
        $out = [];
        foreach (explode(';', $v) as $decl) {
            $decl = trim($decl);
            if ($decl === '' || !str_contains($decl, ':')) {
                continue;
            }
            [$prop, $val] = array_map('trim', explode(':', $decl, 2));
            $prop = strtolower($prop);
            if (!isset(self::CSS[$prop]) || $val === '' || preg_match('/[\\\\<>{}@]|\/\*|\*\/|url\s*\(|expression|javascript|image-set|var\s*\(|!important/i', $val)) {
                continue;
            }
            if (self::cssValue(self::CSS[$prop], $val)) {
                $out[$prop] = preg_replace('/\s+/', ' ', $val);
            }
        }
        $s = '';
        foreach ($out as $p => $val) {
            $s .= $p . ': ' . $val . '; ';
        }

        return trim($s);
    }

    private static function cssValue(string $kind, string $v): bool
    {
        $len = '-?\d{1,4}(?:\.\d{1,3})?(?:px|em|rem|%|pt|ex|ch|vw|vh)?';
        $color = '(?:#[0-9a-fA-F]{3,8}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*[01]?(?:\.\d+)?\s*)?\)|hsla?\(\s*\d{1,3}(?:deg)?\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*(?:,\s*[01]?(?:\.\d+)?\s*)?\)|[a-zA-Z]{3,20})';
        return match ($kind) {
            'color' => preg_match('/^' . $color . '$/D', $v) === 1,
            'size' => preg_match('/^(?:' . $len . '|xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger)$/D', $v) === 1,
            'family' => strlen($v) <= 200 && preg_match('/^[A-Za-z0-9 ,\'"_\-]+$/D', $v) === 1,
            'weight' => preg_match('/^(?:normal|bold|bolder|lighter|[1-9]00)$/D', $v) === 1,
            'fstyle' => in_array($v, ['normal', 'italic', 'oblique'], true),
            'align' => in_array($v, ['left', 'right', 'center', 'justify', 'start', 'end'], true),
            'decoration' => preg_match('/^(?:(?:none|underline|line-through|overline)(?:\s+|$))+$/D', $v . ' ') === 1,
            'lengths' => preg_match('/^(?:' . $len . '|auto)(?:\s+(?:' . $len . '|auto)){0,3}$/D', $v) === 1,
            'lengthornum' => preg_match('/^(?:normal|' . $len . ')$/D', $v) === 1,
            'bstyle' => in_array($v, ['none', 'solid', 'dashed', 'dotted', 'double'], true),
            'display' => in_array($v, ['inline', 'inline-block', 'block', 'inline-flex', 'flex'], true),
            'valign' => preg_match('/^(?:baseline|middle|top|bottom|sub|super|text-top|text-bottom|' . $len . ')$/D', $v) === 1,
            'opacity' => preg_match('/^(?:0|1|0?\.\d{1,3})$/D', $v) === 1,
            'border' => (function () use ($v, $len, $color): bool {
                preg_match_all('/(?:rgba?|hsla?)\([^)]*\)|[^\s]+/', $v, $tm);   // fungsi warna berisi spasi: satu token
                $tokens = $tm[0];
                if (count($tokens) < 1 || count($tokens) > 3) {
                    return false;
                }
                foreach ($tokens as $t) {
                    if (!(preg_match('/^' . $len . '$/D', $t) || in_array($t, ['none', 'solid', 'dashed', 'dotted', 'double', 'transparent'], true) || preg_match('/^' . $color . '$/D', $t))) {
                        return false;
                    }
                }
                return true;
            })(),
            default => false,
        };
    }
}
