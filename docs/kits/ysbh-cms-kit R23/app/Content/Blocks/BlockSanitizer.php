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
            // anchor tingkat blok (kolom teks bebas di editor, tanpa validasi server): dicetak ke id="..." dan ke ekspresi JS daftar isi
            if (array_key_exists('anchor', $block)) {
                $anchor = self::anchor($block['anchor']);
                if ($anchor !== $block['anchor']) {
                    $blocks[$id]['anchor'] = $anchor;
                }
            }
            $type = str_replace('_', '-', strtolower((string) ($block['type'] ?? '')));
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $clean = self::forType($type, $data, $locales);
            if ($clean !== $data || $type === 'button-builder') {
                $blocks[$id]['data'] = $clean;
            }
        }

        return $blocks;
    }

    /**
     * Pembersih untuk SATU jenis blok: Tombol (bawaan) atau modul di app/Editor/Blocks. Jenis lain dikembalikan apa adanya.
     * Komponen tampilan publik memanggil ini lagi (jaring kedua).
     */
    public static function forType(string $type, array $data, array $locales = ['id', 'en']): array
    {
        $type = str_replace('_', '-', strtolower($type));
        if ($type === 'button-builder') {
            return self::buttonBuilder($data, $locales);
        }
        if (class_exists(\App\Editor\Modules::class) && ($module = \App\Editor\Modules::for($type))) {
            return $module::sanitize($data, $locales);
        }
        if (CoreBlocks::handles($type)) {
            return CoreBlocks::clean($type, $data);   // tujuh blok inti: hanya kolom berisiko; non-destruktif
        }

        return $data;
    }

    /**
     * Untuk SITUS PUBLIK dan pratinjau tersimpan: clean() + (a) HTML teks kaya heading/paragraph disaring RichText, dan (b) alamat
     * internal://page|article/{slug} (tautan dari dialog TipTap, juga di kartu dan gambar) diselesaikan menjadi alamat publik.
     * HTML TIDAK disaring saat SIMPAN (data editor tetap utuh); hanya saat ditampilkan kepada pengunjung.
     *
     * @param array<string,array> $blocks id => blok
     */
    public static function forPublic(array $blocks, array $locales = ['id', 'en'], ?callable $internal = null): array
    {
        $internal ??= fn (string $kind, string $slug): ?string => LinkResolver::address($kind, $slug);
        $blocks = self::clean($blocks, $locales);
        foreach ($blocks as $id => $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = str_replace('_', '-', strtolower((string) ($block['type'] ?? '')));
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            if ($type === 'heading' || $type === 'paragraph') {
                if (isset($data['text']) && is_array($data['text'])) {
                    foreach ($data['text'] as $loc => $html) {
                        $data['text'][$loc] = RichText::clean($html, $internal);
                    }
                    $blocks[$id]['data'] = $data;
                } elseif (isset($data['text']) && is_string($data['text'])) {
                    $blocks[$id]['data']['text'] = RichText::clean($data['text'], $internal);
                }
            } elseif (CoreBlocks::handles($type)) {
                $resolved = CoreBlocks::clean($type, $data, $internal);
                if ($resolved !== $data) {
                    $blocks[$id]['data'] = $resolved;
                }
            }
        }

        return $blocks;
    }

    /**
     * Anchor blok: yang sudah sah (huruf/angka/garis bawah/tanda hubung, maksimal 64, diawali huruf atau angka) TIDAK diubah; yang lain
     * dinormalkan seperti slug ("Beban Kasus" -> "beban-kasus", "x');alert(1);('" -> "x-alert-1"); bukan teks/angka atau hasilnya kosong -> "".
     */
    public static function anchor(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }
        if (!is_string($value)) {
            return '';
        }
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/D', $value)) {
            return $value;
        }
        $s = strtolower(trim($value));
        $s = preg_replace('/[^a-z0-9_]+/', '-', $s) ?? '';
        $s = trim(substr($s, 0, 64), '-_');

        return $s;
    }

    /** ID item daftar berulang: dipertahankan bila sah, jika tidak dibuat baru. */
    public static function itemId(mixed $id): string
    {
        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,40}$/D', $id)
            ? $id
            : 'itm_' . substr(bin2hex(random_bytes(5)), 0, 8);
    }

    /** Teks SATU baris per bahasa, TANPA membuang tag: dicetak lewat {{ }}, sehingga "usia <5 tahun" tidak terpotong. */
    public static function singleLines(mixed $value, array $locales, int $max): array
    {
        $value = is_array($value) ? $value : [];
        $out = [];
        foreach ($locales as $l) {
            $s = is_scalar($value[$l] ?? null) ? (string) $value[$l] : '';
            $out[$l] = mb_substr(trim(preg_replace('/[\s\x00-\x1f]+/u', ' ', $s) ?? ''), 0, $max);
        }

        return $out;
    }

    /** Teks BANYAK baris per bahasa (baris baru dipertahankan, maksimal dua berurutan), TANPA membuang tag. */
    public static function multiLines(mixed $value, array $locales, int $max): array
    {
        $value = is_array($value) ? $value : [];
        $out = [];
        foreach ($locales as $l) {
            $s = is_scalar($value[$l] ?? null) ? (string) $value[$l] : '';
            $s = str_replace(["\r\n", "\r"], "\n", $s);
            $s = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', '', $s) ?? '';
            $s = preg_replace('/\n{3,}/', "\n\n", trim($s)) ?? '';
            $out[$l] = mb_substr($s, 0, $max);
        }

        return $out;
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
