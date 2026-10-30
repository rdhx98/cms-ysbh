<?php

namespace App\Content\Blocks;

/**
 * Penyaring SVG untuk Hiasan latar (rilis 107). Murni (hanya ext-dom), tanpa Laravel.
 *
 * SVG dari File Manager dicetak SEBARIS di halaman supaya warnanya bisa diganti dari CMS. SVG sebaris berjalan di asal (origin) situs,
 * jadi berkasnya TIDAK dipercaya: yang dicetak adalah salinan BARU yang dirakit dari daftar putih (elemen, atribut, nilai), bukan berkas
 * asli yang dibersihkan sebagian. Apa pun yang tidak dikenal dibuang, bukan ditolak, kecuali berkas yang rusak / terlalu besar / tanpa
 * ukuran (hasilnya null dan hiasan tidak dicetak).
 *
 * Yang DIPERTAHANKAN: svg, g, defs, path, circle, ellipse, line, polyline, polygon, rect, linearGradient, radialGradient, stop, clipPath, mask;
 *   atribut geometri/cat/transformasi yang nilainya lolos pola ketat; gaya `style="..."` dan aturan `.kelas{...}` sederhana dari <style>
 *   (dipindah menjadi atribut, hanya properti dalam daftar).
 * Yang DIBUANG: script, foreignObject, image, use, symbol, pattern, text, a, animate*, set, filter, style (setelah dibaca), title/desc, komentar,
 *   instruksi pemrosesan, semua atribut on*, semua href selain "#id" di gradien, url(...) selain "#id", id/class asli, DOCTYPE.
 * Berkas dengan DOCTYPE berisi subset internal atau <!ENTITY> ditolak (serangan entitas).
 *
 * Mode warna (setelah dirakit): "asli" = warna berkas; "satu" = semua cat bukan-none -> slot A; "dua" = warna berbeda pertama (urutan
 * dokumen) -> A, sisanya -> B. Slot dicetak sebagai `fill:var(--orn-a)` / `var(--orn-b)` pada atribut style, nilainya diisi pembungkus.
 * Isi <defs>, <mask>, <clipPath>, dan gradien tidak diwarnai ulang (mask memakai kecerahan; gradien diratakan lewat cat bentuknya).
 */
final class SvgSanitizer
{
    public const MAX_BYTES = 262144;   // 256 KB
    public const MAX_NODES = 4000;
    public const MAX_DEPTH = 24;
    public const SVG_NS = 'http://www.w3.org/2000/svg';

    private const ELEMENTS = [
        'svg', 'g', 'defs', 'path', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'rect',
        'linearGradient', 'radialGradient', 'stop', 'clipPath', 'mask',
    ];
    private const SHAPES = ['path', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'rect'];
    private const SKIP_RECOLOR = ['defs', 'mask', 'clipPath', 'linearGradient', 'radialGradient', 'stop'];

    private const LENGTH_ATTRS = ['x', 'y', 'cx', 'cy', 'r', 'rx', 'ry', 'x1', 'y1', 'x2', 'y2', 'width', 'height', 'fx', 'fy',
        'stroke-width', 'stroke-dashoffset', 'stroke-miterlimit', 'offset'];
    private const PAINT_ATTRS = ['fill', 'stroke', 'stop-color'];
    private const OPACITY_ATTRS = ['opacity', 'fill-opacity', 'stroke-opacity', 'stop-opacity'];
    private const ENUMS = [
        'stroke-linecap' => ['butt', 'round', 'square'],
        'stroke-linejoin' => ['miter', 'round', 'bevel'],
        'fill-rule' => ['nonzero', 'evenodd'],
        'clip-rule' => ['nonzero', 'evenodd'],
        'gradientUnits' => ['userSpaceOnUse', 'objectBoundingBox'],
        'spreadMethod' => ['pad', 'reflect', 'repeat'],
        'clipPathUnits' => ['userSpaceOnUse', 'objectBoundingBox'],
        'maskUnits' => ['userSpaceOnUse', 'objectBoundingBox'],
        'maskContentUnits' => ['userSpaceOnUse', 'objectBoundingBox'],
    ];
    /** Properti CSS yang boleh datang dari style="" dan <style> (semuanya juga atribut presentasi yang sah). */
    private const CSS_PROPS = ['fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray',
        'stroke-dashoffset', 'fill-opacity', 'stroke-opacity', 'opacity', 'fill-rule', 'clip-rule', 'stop-color', 'stop-opacity', 'display',
        'clip-path', 'mask'];
    private const NUM = '[+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?';

    /**
     * @return array{svg:string, ratio:float, colors:int}|null  svg = penanda <svg ...> siap cetak; ratio = lebar/tinggi viewBox; colors = jumlah warna berbeda
     */
    public static function clean(string $raw, string $idPrefix = '', string $mode = 'asli'): ?array
    {
        if ($raw === '' || strlen($raw) > self::MAX_BYTES || !extension_loaded('dom')) {
            return null;
        }
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        if (preg_match('/<!ENTITY/i', $raw)) {
            return null;
        }
        $raw = preg_replace('/<!DOCTYPE[^>\[]*>/i', '', $raw) ?? $raw;
        if (stripos($raw, '<!DOCTYPE') !== false) {
            return null;   // masih ada DOCTYPE = berisi subset internal [...]
        }
        $idPrefix = preg_replace('/[^A-Za-z0-9_-]/', '', $idPrefix) ?? '';

        $previous = libxml_use_internal_errors(true);
        $src = new \DOMDocument();
        $loaded = $src->loadXML($raw, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $loaded ? $src->documentElement : null;
        if (!$root instanceof \DOMElement || $root->localName !== 'svg' || !in_array((string) $root->namespaceURI, ['', self::SVG_NS], true)) {
            return null;
        }

        $box = self::viewBox($root);
        if ($box === null) {
            return null;
        }

        // id asli -> id berawalan (awalan mencegah tabrakan antar hiasan dan dengan id halaman)
        $ids = [];
        foreach ((new \DOMXPath($src))->query('//@id') ?: [] as $a) {
            $v = trim((string) $a->nodeValue);
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_.:-]{0,63}$/D', $v) === 1) {
                $ids[$v] = $idPrefix . $v;
            }
        }
        $rules = self::cssRules($src);

        $out = new \DOMDocument('1.0', 'UTF-8');
        $count = 0;
        $svg = self::build($root, $out, $ids, $rules, 0, $count);
        if ($svg === null || $count > self::MAX_NODES) {
            return null;
        }
        $svg->setAttribute('viewBox', self::fmt($box[0]) . ' ' . self::fmt($box[1]) . ' ' . self::fmt($box[2]) . ' ' . self::fmt($box[3]));
        $svg->setAttribute('preserveAspectRatio', 'xMidYMid meet');
        $svg->setAttribute('focusable', 'false');
        $svg->setAttribute('aria-hidden', 'true');
        $out->appendChild($svg);

        $colors = 0;
        if (in_array($mode, ['satu', 'dua'], true)) {
            $slots = [];
            self::recolor($svg, $mode, null, null, $slots);
            $colors = count($slots);
        } else {
            $seen = [];
            self::countColors($svg, $seen);
            $colors = count($seen);
        }
        $markup = $out->saveXML($svg);
        if (!is_string($markup) || $markup === '') {
            return null;
        }

        return ['svg' => $markup, 'ratio' => $box[2] / $box[3], 'colors' => $colors];
    }

    // ---------------------------------------------------------------- perakitan

    private static function build(\DOMElement $el, \DOMDocument $out, array $ids, array $rules, int $depth, int &$count): ?\DOMElement
    {
        $name = $el->localName;
        if (!in_array($name, self::ELEMENTS, true) || ($name === 'svg' && $depth > 0) || $depth > self::MAX_DEPTH || ++$count > self::MAX_NODES) {
            return null;
        }
        $copy = $out->createElementNS(self::SVG_NS, $name);

        // 1) atribut langsung, 2) aturan .kelas dari <style>, 3) style="..." (urutan = prioritas naik)
        $attrs = [];
        foreach ($el->attributes ?? [] as $a) {
            if ($a->namespaceURI !== null && $a->namespaceURI !== '' && $a->localName !== 'href') {
                continue;   // xml:*, xlink:* selain href, atribut ber-namespace asing
            }
            $attrs[$a->localName === 'href' ? 'href' : $a->nodeName] = (string) $a->nodeValue;
        }
        $decl = [];
        foreach (preg_split('/\s+/', trim($attrs['class'] ?? '')) ?: [] as $cls) {
            foreach ($rules[$cls] ?? [] as $p => $v) {
                $decl[$p] = $v;
            }
        }
        foreach (self::declarations($attrs['style'] ?? '') as $p => $v) {
            $decl[$p] = $v;
        }
        foreach ($decl as $p => $v) {
            $attrs[$p] = $v;
        }

        if (($attrs['display'] ?? '') === 'none') {
            return null;
        }
        foreach ($attrs as $attr => $value) {
            if ($name === 'svg' && in_array($attr, ['width', 'height', 'x', 'y'], true)) {
                continue;   // ukuran ditentukan pembungkus; viewBox ditulis terpisah
            }
            $clean = self::attribute($attr, $value, $name, $ids);
            if ($clean !== null) {
                $copy->setAttribute($attr === 'href' ? 'href' : $attr, $clean);
            }
        }
        if (isset($attrs['id']) && isset($ids[trim($attrs['id'])])) {
            $copy->setAttribute('id', $ids[trim($attrs['id'])]);
        }

        foreach ($el->childNodes as $child) {
            if ($child instanceof \DOMElement && ($c = self::build($child, $out, $ids, $rules, $depth + 1, $count)) !== null) {
                $copy->appendChild($c);
            }
        }

        return $copy;
    }

    /** Satu atribut -> nilai bersih, atau null (dibuang). */
    private static function attribute(string $name, string $value, string $element, array $ids): ?string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 60000) {
            return null;
        }
        if (in_array($name, self::LENGTH_ATTRS, true)) {
            return preg_match('/^' . self::NUM . '(?:px|%)?$/D', $value) === 1 ? $value : null;
        }
        if (in_array($name, self::OPACITY_ATTRS, true)) {
            return preg_match('/^' . self::NUM . '$/D', $value) === 1 ? self::fmt(max(0.0, min(1.0, (float) $value))) : null;
        }
        if (in_array($name, self::PAINT_ATTRS, true)) {
            $p = self::paint($value, $ids);

            return $p === null ? null : $p['css'];
        }
        if (isset(self::ENUMS[$name])) {
            return in_array($value, self::ENUMS[$name], true) ? $value : null;
        }
        switch ($name) {
            case 'd':
                return preg_match('/^[MmLlHhVvCcSsQqTtAaZz0-9eE+\-.,\s]*$/D', $value) === 1 ? $value : null;
            case 'points':
                return preg_match('/^[0-9eE+\-.,\s]*$/D', $value) === 1 ? $value : null;
            case 'transform':
            case 'gradientTransform':
                return preg_match('/^(?:\s*(?:matrix|translate|scale|rotate|skewX|skewY)\s*\(\s*[0-9eE+\-.,\s]*\)\s*,?)*$/D', $value) === 1 ? $value : null;
            case 'stroke-dasharray':
                return $value === 'none' || preg_match('/^[0-9eE+\-.,\s%]*$/D', $value) === 1 ? $value : null;
            case 'clip-path':
            case 'mask':
                if ($value === 'none') {
                    return 'none';
                }

                return preg_match('/^url\(\s*#([A-Za-z_][A-Za-z0-9_.:-]{0,63})\s*\)$/D', $value, $m) === 1 && isset($ids[$m[1]]) ? 'url(#' . $ids[$m[1]] . ')' : null;
            case 'href':
                return in_array($element, ['linearGradient', 'radialGradient'], true) && preg_match('/^#([A-Za-z_][A-Za-z0-9_.:-]{0,63})$/D', $value, $m) === 1 && isset($ids[$m[1]])
                    ? '#' . $ids[$m[1]] : null;
        }

        return null;   // id, class, style, viewBox (raiz ditulis terpisah), on*, dan semua yang tidak dikenal
    }

    /**
     * Nilai cat -> ['kind' => none|color|url, 'css' => nilai siap cetak, 'key' => kunci perbandingan] atau null.
     */
    private static function paint(string $value, array $ids): ?array
    {
        $v = trim(preg_replace('/\s*!important\s*$/i', '', $value) ?? $value);
        $l = strtolower($v);
        if ($l === 'none' || $l === 'transparent') {
            return ['kind' => 'none', 'css' => 'none', 'key' => 'none'];
        }
        if ($l === 'currentcolor') {
            return ['kind' => 'color', 'css' => 'currentColor', 'key' => 'currentcolor'];
        }
        if (preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/D', $l, $m) === 1) {
            $hex = $m[1];
            if (strlen($hex) <= 4) {
                $hex = implode('', array_map(fn ($c) => $c . $c, str_split($hex)));
            }

            return ['kind' => 'color', 'css' => '#' . $hex, 'key' => '#' . $hex];
        }
        if (preg_match('/^(?:rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/-]+\)$/D', $l) === 1) {
            return ['kind' => 'color', 'css' => $l, 'key' => preg_replace('/\s+/', '', $l)];
        }
        if (preg_match('/^url\(\s*#([A-Za-z_][A-Za-z0-9_.:-]{0,63})\s*\)(?:\s+\S+)?$/D', $v, $m) === 1) {
            return isset($ids[$m[1]]) ? ['kind' => 'url', 'css' => 'url(#' . $ids[$m[1]] . ')', 'key' => 'url:' . $m[1]] : null;
        }
        if (preg_match('/^[a-z]{3,24}$/D', $l) === 1 && !in_array($l, ['inherit', 'initial', 'unset', 'revert'], true)) {
            return ['kind' => 'color', 'css' => $l, 'key' => $l];
        }

        return null;
    }

    // ---------------------------------------------------------------- CSS sederhana

    /** style="a:b;c:d" -> [properti => nilai] hanya untuk properti dalam daftar. */
    private static function declarations(string $css): array
    {
        $out = [];
        $css = preg_replace('~/\*.*?\*/~s', '', $css) ?? '';
        foreach (explode(';', $css) as $part) {
            if (!str_contains($part, ':')) {
                continue;
            }
            [$p, $v] = array_map('trim', explode(':', $part, 2));
            $p = strtolower($p);
            if (in_array($p, self::CSS_PROPS, true) && $v !== '') {
                $out[$p] = $v;
            }
        }

        return $out;
    }

    /** Semua <style>: aturan ".a, .b { ... }" -> [kelas => [properti => nilai]]. Selektor selain kelas sederhana diabaikan. */
    private static function cssRules(\DOMDocument $doc): array
    {
        $rules = [];
        foreach ($doc->getElementsByTagName('style') as $style) {
            $css = preg_replace('~/\*.*?\*/~s', '', (string) $style->textContent) ?? '';
            if (stripos($css, '@import') !== false) {
                continue;
            }
            if (preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $m, PREG_SET_ORDER) === false) {
                continue;
            }
            foreach ($m as [, $selectors, $body]) {
                $decl = self::declarations($body);
                foreach (explode(',', $selectors) as $sel) {
                    if (preg_match('/^\s*\.([A-Za-z_][A-Za-z0-9_-]*)\s*$/D', $sel, $c) === 1) {
                        $rules[$c[1]] ??= [];
                        foreach ($decl as $p => $v) {
                            $rules[$c[1]][$p] = $v;
                        }
                    }
                }
            }
        }

        return $rules;
    }

    // ---------------------------------------------------------------- viewBox

    /** @return array{0:float,1:float,2:float,3:float}|null */
    private static function viewBox(\DOMElement $root): ?array
    {
        $vb = trim($root->getAttribute('viewBox'));
        if ($vb !== '' && preg_match('/^' . self::NUM . '(?:[\s,]+' . self::NUM . '){3}$/D', $vb) === 1) {
            $n = array_map('floatval', preg_split('/[\s,]+/', $vb) ?: []);
            if ($n[2] > 0 && $n[3] > 0 && $n[2] <= 1000000 && $n[3] <= 1000000 && $n[2] / $n[3] >= 0.02 && $n[2] / $n[3] <= 50) {
                return [$n[0], $n[1], $n[2], $n[3]];
            }

            return null;
        }
        $w = trim($root->getAttribute('width'));
        $h = trim($root->getAttribute('height'));
        $num = '/^(' . self::NUM . ')(?:px)?$/D';
        if (preg_match($num, $w, $a) === 1 && preg_match($num, $h, $b) === 1 && (float) $a[1] > 0 && (float) $b[1] > 0) {
            $ratio = (float) $a[1] / (float) $b[1];

            return $ratio >= 0.02 && $ratio <= 50 ? [0.0, 0.0, (float) $a[1], (float) $b[1]] : null;
        }

        return null;
    }

    // ---------------------------------------------------------------- warna

    /** Menghitung warna berbeda pada cat bentuk (mode asli; hanya untuk informasi panel). */
    private static function countColors(\DOMElement $el, array &$seen, ?string $fill = null, ?string $stroke = null, bool $skip = false): void
    {
        $skip = $skip || in_array($el->localName, self::SKIP_RECOLOR, true);
        $fill = $el->hasAttribute('fill') ? $el->getAttribute('fill') : $fill;
        $stroke = $el->hasAttribute('stroke') ? $el->getAttribute('stroke') : $stroke;
        if (!$skip && in_array($el->localName, self::SHAPES, true)) {
            foreach ([$el->localName === 'line' ? 'none' : ($fill ?? '#000000'), $stroke ?? 'none'] as $v) {
                if (($key = self::keyOf($v)) !== 'none') {
                    $seen[$key] = true;
                }
            }
        }
        foreach ($el->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                self::countColors($child, $seen, $fill, $stroke, $skip);
            }
        }
    }

    /**
     * Mewarnai ulang bentuk: cat bukan-none -> slot A/B lewat atribut style (fill/stroke dipindah ke sana).
     *
     * @param array<string,string> $slots kunci warna -> 'a'|'b' (dibagi seluruh dokumen)
     */
    private static function recolor(\DOMElement $el, string $mode, ?string $fill, ?string $stroke, array &$slots, bool $skip = false): void
    {
        $skip = $skip || in_array($el->localName, self::SKIP_RECOLOR, true);
        $fill = $el->hasAttribute('fill') ? $el->getAttribute('fill') : $fill;
        $stroke = $el->hasAttribute('stroke') ? $el->getAttribute('stroke') : $stroke;
        if (!$skip && in_array($el->localName, self::SHAPES, true)) {
            $style = [];
            // <line> tidak punya area: cat isi bawaan (hitam) tidak boleh memakai slot warna
            foreach (['fill' => $el->localName === 'line' ? 'none' : ($fill ?? '#000000'), 'stroke' => $stroke ?? 'none'] as $prop => $value) {
                $key = self::keyOf($value);
                if ($key === 'none') {
                    $style[] = $prop . ':none';
                } else {
                    if (!isset($slots[$key])) {
                        $slots[$key] = $mode === 'satu' || $slots === [] ? 'a' : 'b';
                    }
                    $style[] = $prop . ':var(--orn-' . $slots[$key] . ')';
                }
                $el->removeAttribute($prop);
            }
            $el->removeAttribute('style');
            $el->setAttribute('style', implode(';', $style));
        }
        foreach ($el->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                self::recolor($child, $mode, $fill, $stroke, $slots, $skip);
            }
        }
    }

    private static function keyOf(string $value): string
    {
        $v = strtolower(trim($value));   // nilai sudah kanonik: keluaran paint() (heks penuh huruf kecil, url(#id), nama warna)

        return $v === '' ? 'none' : $v;
    }

    private static function fmt(float $n): string
    {
        $s = rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');

        return $s === '' || $s === '-0' ? '0' : $s;
    }
}
