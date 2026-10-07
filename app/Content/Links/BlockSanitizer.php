<?php

namespace App\Content\Blocks;

use App\Content\Links\LinkResolver;

/**
 * Membersihkan data blok SEBELUM disimpan. Inspektur hanya menulis nilai yang sah, tetapi permintaan Livewire bisa dibuat tangan,
 * dan data ini nantinya dicetak sebagai kelas CSS dan atribut href di halaman publik. Prinsip: nilai tak sah diganti bawaan
 * (bukan ditolak), sehingga penyimpanan sah tidak pernah gagal, dan data berbahaya tidak pernah tersimpan.
 */
final class BlockSanitizer
{
    /** @param array<string,array> $blocks id => blok */
    public static function clean(array $blocks, array $locales = ['id', 'en']): array
    {
        foreach ($blocks as $id => $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = str_replace('_', '-', strtolower((string) ($block['type'] ?? '')));
            if ($type === 'button-builder') {
                $blocks[$id]['data'] = self::buttonBuilder(is_array($block['data'] ?? null) ? $block['data'] : [], $locales);
            }
        }

        return $blocks;
    }

    public static function buttonBuilder(array $data, array $locales = ['id', 'en']): array
    {
        $buttons = [];
        foreach (array_slice(array_values(array_filter($data['buttons'] ?? [], 'is_array')), 0, 12) as $b) {
            $link = is_array($b['link'] ?? null) ? $b['link'] : [];
            $kind = in_array($link['kind'] ?? null, LinkResolver::KINDS, true) ? $link['kind'] : 'url';

            $buttons[] = [
                'id' => is_string($b['id'] ?? null) && preg_match('/^[A-Za-z0-9_-]{1,40}$/D', $b['id']) ? $b['id'] : 'itm_' . substr(bin2hex(random_bytes(5)), 0, 8),
                'label' => self::locales($b['label'] ?? [], $locales, 60),
                'link' => self::link($kind, $link),
                'variant' => in_array($b['variant'] ?? null, ButtonStyle::VARIANTS, true) ? $b['variant'] : 'solid',
                'color' => in_array($b['color'] ?? null, ButtonStyle::COLORS, true) ? $b['color'] : 'foresty',
                'size' => in_array($b['size'] ?? null, ButtonStyle::SIZES, true) ? $b['size'] : 'md',
                'icon' => is_string($b['icon'] ?? null) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $b['icon']) && strlen($b['icon']) <= 40 ? $b['icon'] : '',
                'icon_position' => ($b['icon_position'] ?? null) === 'right' ? 'right' : 'left',
            ];
        }

        return [
            'align' => in_array($data['align'] ?? null, ButtonStyle::ALIGNS, true) ? $data['align'] : 'left',
            'stack_mobile' => (bool) ($data['stack_mobile'] ?? true),
            'buttons' => $buttons,
        ];
    }

    /** Hanya bidang yang sesuai jenisnya yang disimpan; sisanya dikosongkan (mis. berpindah dari "berkas" ke "URL" tidak membawa media_id). */
    private static function link(string $kind, array $link): array
    {
        $ref = is_scalar($link['ref'] ?? null) ? trim((string) $link['ref']) : '';
        $out = ['kind' => $kind, 'ref' => '', 'ref_label' => '', 'media_id' => null, 'url' => '', 'new_tab' => (bool) ($link['new_tab'] ?? false)];

        switch ($kind) {
            case 'page':
            case 'article':
                $id = LinkResolver::positiveInt($ref);
                $out['ref'] = $id ?: '';
                $out['ref_label'] = $id ? self::text($link['ref_label'] ?? '', 120) : '';
                break;
            case 'file':
                $id = LinkResolver::positiveInt($link['media_id'] ?? null);
                $out['media_id'] = $id ?: null;
                $out['url'] = $id ? self::text($link['url'] ?? '', 255) : ''; // hanya untuk tampilan nama berkas; tidak pernah dipakai sebagai href
                break;
            case 'url':
                $out['ref'] = LinkResolver::safeUrl($ref) ?? '';
                break;
            case 'tel':
                $out['ref'] = LinkResolver::phone($ref) ?? '';
                $out['new_tab'] = false;
                break;
            case 'mailto':
                $out['ref'] = filter_var($ref, FILTER_VALIDATE_EMAIL) ? $ref : '';
                $out['new_tab'] = false;
                break;
            case 'anchor':
                $out['ref'] = LinkResolver::anchor($ref) ?? '';
                $out['new_tab'] = false;
                break;
        }

        return $out;
    }

    private static function locales(mixed $value, array $locales, int $max): array
    {
        $value = is_array($value) ? $value : [];
        $out = [];
        foreach ($locales as $l) {
            $out[$l] = self::text($value[$l] ?? '', $max);
        }

        return $out;
    }

    /** Teks polos: tag dibuang, spasi dirapikan, dipotong. (Dicetak dengan {{ }} di renderer, jadi escape tetap berlapis.) */
    private static function text(mixed $value, int $max): string
    {
        $s = is_scalar($value) ? trim(strip_tags((string) $value)) : '';

        return mb_substr(preg_replace('/\s+/u', ' ', $s) ?? '', 0, $max);
    }
}
