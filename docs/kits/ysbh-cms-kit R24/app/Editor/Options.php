<?php

namespace App\Editor;

/**
 * Satu-satunya tempat yang menormalkan daftar opsi kontrol (dipakai komponen Blade DAN audit).
 *
 *   ['font-normal' => 'Reguler']                         -> [['value'=>'font-normal','label'=>'Reguler']]
 *   ['a', 'b']                                           -> [['value'=>'a','label'=>'a'], ...]
 *   [['value'=>'x','name'=>'X','preview'=>'bg-x'], ...]  -> dibiarkan, 'value' dijamin ada
 */
final class Options
{
    /** Kunci yang menandakan sebuah larik adalah DEFINISI opsi (bukan peta bahasa). */
    private const OPTION_KEYS = ['value', 'label', 'name', 'title', 'icon', 'html', 'dot', 'dot_ring', 'slash', 'square', 'shape', 'preview', 'is_transparent'];

    public static function normalize(array $options): array
    {
        $items = [];
        $isList = array_is_list($options);

        foreach ($options as $key => $opt) {
            // Larik tanpa satu pun kunci definisi opsi adalah PETA BAHASA ({"id": "Kesehatan", "en": "Health"}), mis. nama
            // kategori ber-cast array: itu label, bukan definisi. Tanpa ini labelnya hilang dan yang tampil hanya id.
            if (is_array($opt) && array_intersect(self::OPTION_KEYS, array_keys($opt)) === []) {
                $items[] = ['value' => $key, 'label' => \App\Content\Names::of($opt, function_exists('app') ? app()->getLocale() : null)];
            } elseif (is_array($opt)) {
                $items[] = $opt + ['value' => $key];
            } elseif ($isList) {
                $items[] = ['value' => $opt, 'label' => $opt];
            } else {
                $items[] = ['value' => $key, 'label' => $opt];
            }
        }

        return $items;
    }

    /** Hanya nilai yang boleh tersimpan. Dipakai audit untuk memeriksa default. */
    public static function values(array $options): array
    {
        return array_map(fn ($i) => $i['value'], self::normalize($options));
    }
}
