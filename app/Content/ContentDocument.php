<?php

namespace App\Content;

/**
 * Isi sebuah konten: { blocks: {id => blok}, order: [id root], settings: {...} }.
 * Satu-satunya pembaca/penulis kolom `content` — dipakai builder DAN page-preview, menggantikan
 * logika decode/migrasi yang sebelumnya disalin di mount() editor lama dan di page-preview.
 */
final class ContentDocument
{
    public const DEFAULT_SETTINGS = ['toc_position' => 'right'];

    public function __construct(
        public readonly array $blocks = [],
        public readonly array $order = [],
        public readonly array $settings = self::DEFAULT_SETTINGS,
    ) {
    }

    /** Menerima array, string JSON, JSON di dalam JSON, format lama, atau sampah (-> dokumen kosong). */
    public static function fromRaw(mixed $raw): self
    {
        $raw = self::decode($raw);

        // Format sekarang
        if (isset($raw['blocks'], $raw['order']) && is_array($raw['blocks']) && is_array($raw['order'])) {
            $blocks = $raw['blocks'];
            $settings = is_array($raw['settings'] ?? null) ? $raw['settings'] : [];

            // Auto-migrasi: 'settings' pernah terselip di dalam 'blocks' (bug lama)
            if (isset($blocks['settings']) && is_array($blocks['settings'])) {
                $settings = $blocks['settings'];
                unset($blocks['settings']);
            }

            return self::make($blocks, $raw['order'], $settings);
        }

        // Format lama: { "id": [blok...] } / { "en": [blok...] } / daftar blok langsung
        foreach (['id', 'en'] as $locale) {
            if (isset($raw[$locale]) && is_array($raw[$locale]) && isset($raw[$locale][0]['type'])) {
                $raw = $raw[$locale];
                break;
            }
        }

        $blocks = [];
        $order = [];
        foreach ($raw as $block) {
            if (is_array($block) && isset($block['type'])) {
                $id = $block['id'] ?? 'blk_' . substr(bin2hex(random_bytes(6)), 0, 8);
                $block['id'] = $id;
                $blocks[$id] = $block;
                $order[] = $id;
            }
        }

        return self::make($blocks, $order, []);
    }

    public function toArray(): array
    {
        return ['blocks' => $this->blocks, 'order' => $this->order, 'settings' => $this->settings];
    }

    // ------------------------------------------------------------------ referensi ke snippet

    /** Pola kunci snippet yang sah: huruf kecil, angka, tanda hubung (mis. "hubungi-kami"). */
    public const KEY_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * ID blok yang benar-benar tersambung ke halaman: dari `order`, lalu turun lewat zona anak
     * (`children`, `*_zone`). Blok yatim/hantu tidak dihitung.
     *
     * @return string[]
     */
    public function reachableIds(): array
    {
        $seen = [];
        $stack = array_reverse($this->order);

        while ($stack) {
            $id = array_pop($stack);
            if (!is_string($id) || isset($seen[$id]) || !isset($this->blocks[$id]) || !is_array($this->blocks[$id])) {
                continue;
            }
            $seen[$id] = true;

            // Kumpulkan anak sesuai urutan dokumen (zona demi zona), lalu dorong terbalik agar pop() menghasilkan urutan itu
            $children = [];
            foreach (($this->blocks[$id]['data'] ?? []) as $key => $value) {
                if (is_array($value) && ($key === 'children' || str_ends_with((string) $key, '_zone'))) {
                    array_push($children, ...array_values($value));
                }
            }
            foreach (array_reverse($children) as $childId) {
                $stack[] = $childId;
            }
        }

        return array_keys($seen);
    }

    /**
     * ID snippet yang disisipkan sebagai blok `snippet` (data.snippet_id), di kedalaman mana pun.
     *
     * @return int[]
     */
    public function snippetIds(): array
    {
        $ids = [];
        foreach ($this->reachableIds() as $blockId) {
            $block = $this->blocks[$blockId];
            if (str_replace('_', '-', strtolower((string) ($block['type'] ?? ''))) !== 'snippet') {
                continue;
            }
            $raw = $block['data']['snippet_id'] ?? null;
            if ((is_int($raw) || (is_string($raw) && ctype_digit($raw))) && (int) $raw > 0) {
                $ids[] = (int) $raw;
            }
        }

        return array_values(array_unique($ids));
    }

    public function referencesSnippets(): bool
    {
        return $this->snippetIds() !== [];
    }

    /**
     * Penggantian snippet penutup untuk halaman ini (settings.closing):
     *   tidak ada / "default"  -> null  (ikuti snippet penutup bawaan)
     *   "none" / false / []    -> []    (tanpa snippet penutup)
     *   ["donasi", "kontak"]   -> daftar kunci yang dipakai menggantikan bawaan
     *
     * @return string[]|null
     */
    public function closingOverride(): ?array
    {
        $value = $this->settings['closing'] ?? null;

        if ($value === null || $value === 'default') {
            return null;
        }
        if ($value === 'none' || $value === false || $value === []) {
            return [];
        }
        if (!is_array($value)) {
            return null;
        }

        $keys = [];
        foreach ($value as $key) {
            if (is_string($key) && preg_match(self::KEY_PATTERN, $key)) {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    private static function make(array $blocks, array $order, array $settings): self
    {
        // Urutan hanya boleh memuat ID yang benar-benar ada (ID hantu merusak render)
        $order = array_values(array_filter($order, fn ($id) => is_string($id) && isset($blocks[$id])));

        return new self($blocks, $order, $settings + self::DEFAULT_SETTINGS);
    }

    private static function decode(mixed $raw): array
    {
        // `content` pernah tersimpan sebagai string JSON di dalam JSON, jadi dibuka berulang (maks 3x)
        for ($i = 0; $i < 3 && is_string($raw); $i++) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? $raw : [];
    }
}