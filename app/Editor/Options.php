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
    public static function normalize(array $options): array
    {
        $items = [];
        $isList = array_is_list($options);

        foreach ($options as $key => $opt) {
            if (is_array($opt)) {
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
