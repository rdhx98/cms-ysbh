<?php

namespace App\Content\Blocks;

/**
 * Penyaring STRUKTURAL untuk tujuh blok inti (heading, paragraph, eyebrow, image, card-builder, step-group, multi-columns).
 *
 * Renderer publik tipe-tipe ini mencetak data apa adanya. Yang berbahaya hanya empat jenis kolom, dan HANYA itu yang disentuh:
 *   - ALAMAT yang masuk ke href / src                      (javascript: dan skema lain)
 *   - nilai yang masuk ke atribut style="..."              (warna eyebrow, lebar kolom kartu dan kolom): penyisipan properti CSS
 *   - NAMA IKON yang menjadi nama komponen ("lucide-<nama>")
 *   - daftar id anak (kolom, langkah)
 * Kolom kelas CSS (margin, lebar, radius, ...) dicetak lewat {{ }} di dalam class="...": ter-escape, tidak bisa keluar dari atribut;
 * TIDAK disentuh. Teks kaya (heading, paragraph) disaring di jalur PUBLIK oleh RichText, bukan di sini.
 *
 * NON-DESTRUKTIF: hanya kolom yang tidak sah yang diganti (bawaan renderer); kolom lain dan urutannya dipertahankan persis. Murni, tanpa Laravel.
 */
final class CoreBlocks
{
    public const TYPES = ['heading', 'paragraph', 'eyebrow', 'image', 'card-builder', 'step-group', 'multi-columns'];

    private const EYEBROW_COLOR = '#e05a47';
    private const ID = '/^[A-Za-z0-9_-]{1,64}$/D';

    public static function handles(string $type): bool
    {
        return in_array(str_replace('_', '-', strtolower($type)), self::TYPES, true);
    }

    /**
     * @param callable(string,string):?string|null $internal bila diberikan (jalur PUBLIK), alamat internal://page|article/{slug}
     *                                                        diselesaikan menjadi alamat publik; bila null (jalur SIMPAN), dipertahankan apa adanya
     */
    public static function clean(string $type, array $data, ?callable $internal = null): array
    {
        return match (str_replace('_', '-', strtolower($type))) {
            'eyebrow' => self::eyebrow($data),
            'image' => self::image($data),
            'card-builder' => self::cardBuilder($data, $internal),
            'step-group' => self::stepGroup($data),
            'multi-columns' => self::multiColumns($data),
            default => $data,
        };
    }

    /**
     * Karakter yang tidak pernah sah dalam nilai URL mentah (tanda kutip, kurung sudut, tanda petik balik) dan entitas HTML ("&#x09;", "&amp;"):
     * renderer meng-escape-nya, tetapi keselamatan tidak boleh bergantung pada renderer yang suatu hari bisa berubah.
     */
    private static function unsafeChars(string $v): bool
    {
        return preg_match('/[<>"\'`]|&#?[A-Za-z0-9]+;/', $v) === 1;
    }

    /** Alamat aman untuk href, atau "". Jalur simpan mempertahankan internal://; jalur publik menyelesaikannya. */
    public static function link(mixed $value, ?callable $internal = null): string
    {
        if (!is_string($value)) {
            return '';
        }
        $v = trim($value);
        if ($v === '' || self::unsafeChars($v)) {
            return '';
        }
        if (preg_match('~^internal://(page|article)/([a-z0-9]+(?:-[a-z0-9]+)*)$~D', $v)) {
            if ($internal === null) {
                return $v;
            }
            $r = RichText::url($v, $internal);

            return $r ?? '';
        }

        return RichText::url($v, null) ?? '';
    }

    /** Alamat gambar: hanya http(s) atau jalur relatif. */
    public static function imageUrl(mixed $value): string
    {
        return is_string($value) && !self::unsafeChars($value) ? (RichText::url(trim($value), null, false) ?? '') : '';
    }

    public static function icon(mixed $value, string $default = ''): string
    {
        return is_string($value) && strlen($value) <= 40 && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value) ? $value : $default;
    }

    public static function color(mixed $value): string
    {
        return is_string($value) && preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/D', $value) ? $value : self::EYEBROW_COLOR;
    }

    /** Lebar kolom grid: "auto" atau bilangan (0, 12]. Tipe asli dipertahankan; selain itu 1. */
    public static function fraction(mixed $value): int|float|string
    {
        if ($value === 'auto') {
            return 'auto';
        }
        if ((is_int($value) || is_float($value) || (is_string($value) && preg_match('/^\d{1,2}(?:\.\d{1,2})?$/D', $value))) && (float) $value > 0 && (float) $value <= 12) {
            return $value;
        }

        return is_string($value) ? '1' : 1;
    }

    /** @return list<string> */
    private static function ids(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, fn ($v) => is_string($v) && preg_match(self::ID, $v) === 1)) : [];
    }

    private static function eyebrow(array $d): array
    {
        if (array_key_exists('icon', $d)) {
            $d['icon'] = self::icon($d['icon'], 'newspaper');
        }
        if (array_key_exists('color', $d)) {
            $d['color'] = self::color($d['color']);
        }

        return $d;
    }

    private static function image(array $d): array
    {
        if (array_key_exists('url', $d)) {
            $d['url'] = self::imageUrl($d['url']);
        }

        return $d;
    }

    private static function stepGroup(array $d): array
    {
        if (array_key_exists('children', $d) && is_array($d['children'])) {
            $d['children'] = self::ids($d['children']);
        }

        return $d;
    }

    private static function multiColumns(array $d): array
    {
        if (array_key_exists('col_count', $d)) {
            $v = $d['col_count'];
            $n = is_numeric($v) ? max(1, min(6, (int) $v)) : 2;
            if (!is_scalar($v) || (string) $n !== (string) $v) {
                $d['col_count'] = $n;   // hanya bila tidak sah atau di luar jangkauan: "3" yang sah tidak diubah menjadi 3
            }
        }
        for ($i = 1; $i <= 6; $i++) {
            if (array_key_exists("col_{$i}_zone_width", $d)) {
                $d["col_{$i}_zone_width"] = self::fraction($d["col_{$i}_zone_width"]);
            }
            if (array_key_exists("col_{$i}_zone", $d) && is_array($d["col_{$i}_zone"])) {
                $d["col_{$i}_zone"] = self::ids($d["col_{$i}_zone"]);
            }
        }

        return $d;
    }

    private static function cardBuilder(array $d, ?callable $internal): array
    {
        if (isset($d['grid']) && is_array($d['grid']) && array_key_exists('cols', $d['grid'])) {
            $v = $d['grid']['cols'];
            $c = is_numeric($v) ? max(1, min(6, (int) $v)) : 3;
            if (!is_scalar($v) || (string) $c !== (string) $v) {
                $d['grid']['cols'] = $c;
            }
        }
        if (!isset($d['cards']) || !is_array($d['cards'])) {
            return $d;
        }
        foreach ($d['cards'] as $i => $card) {
            if (!is_array($card)) {
                continue;
            }
            if (isset($card['container']) && is_array($card['container']) && array_key_exists('url', $card['container'])) {
                $d['cards'][$i]['container']['url'] = self::link($card['container']['url'], $internal);
            }
            if (!isset($card['layout']['children']) || !is_array($card['layout']['children'])) {
                continue;
            }
            foreach ($card['layout']['children'] as $c => $col) {
                if (!is_array($col)) {
                    continue;
                }
                if (array_key_exists('width', $col)) {
                    $d['cards'][$i]['layout']['children'][$c]['width'] = self::fraction($col['width']);
                }
                foreach (is_array($col['children'] ?? null) ? $col['children'] : [] as $e => $el) {
                    if (!is_array($el) || !isset($el['data']['content']) || !is_array($el['data']['content'])) {
                        continue;
                    }
                    $type = $el['elementType'] ?? 'text';
                    if ($type === 'icon' && array_key_exists('icon', $el['data']['content'])) {
                        $d['cards'][$i]['layout']['children'][$c]['children'][$e]['data']['content']['icon'] = self::icon($el['data']['content']['icon']);
                    }
                    if ($type === 'button' && array_key_exists('url', $el['data']['content'])) {
                        $d['cards'][$i]['layout']['children'][$c]['children'][$e]['data']['content']['url'] = self::link($el['data']['content']['url'], $internal) ?: '#';
                    }
                }
            }
        }

        return $d;
    }
}
