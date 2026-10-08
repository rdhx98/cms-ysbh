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
        /** true bila dokumen ini DIBENTUK dari isi lama (HTML) dan belum pernah disimpan sebagai blok */
        public readonly bool $imported = false,
    ) {
    }

    /**
     * Menerima array, string JSON, JSON di dalam JSON, format blok lama, HTML lama dari editor artikel, atau sampah.
     *
     * HTML lama (artikel yang dibuat editor lama: satu string HTML, atau {"id": "<p>…", "en": "…"}) DIIMPOR sebagai satu
     * blok Paragraf, supaya membuka artikel lama di builder tidak menghasilkan dokumen kosong yang lalu menimpa isinya.
     *
     * @param string[] $locales bahasa aktif; yang pertama = bahasa utama untuk HTML satu bahasa
     */
    public static function fromRaw(mixed $raw, array $locales = ['id', 'en']): self
    {
        $raw = LocaleMap::decode($raw);

        // Teks/HTML polos (kolom lama bertipe string)
        if (is_string($raw)) {
            return trim($raw) === '' ? self::make([], [], []) : self::importHtml([($locales[0] ?? 'id') => $raw], $locales);
        }
        if (!is_array($raw)) {
            return self::make([], [], []);
        }

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

        // HTML per bahasa: {"id": "<p>…", "en": "<p>…"}
        if (self::isLocaleTextMap($raw)) {
            $texts = array_filter($raw, fn ($v) => is_string($v) && trim($v) !== '');
            return $texts === [] ? self::make([], [], []) : self::importHtml($texts, $locales);
        }

        // Format blok lama: { "id": [blok...] } / { "en": [blok...] } / daftar blok langsung
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

    /** {kode-bahasa: teks}: kunci seperti "id", "en", "pt-BR"; nilai string/null; bukan daftar. */
    private static function isLocaleTextMap(array $raw): bool
    {
        if ($raw === [] || array_is_list($raw)) {
            return false;
        }
        foreach ($raw as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-z]{2}(?:-[A-Za-z]{2})?$/D', $key) || !(is_string($value) || $value === null)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string,string> $texts bahasa => HTML */
    private static function importHtml(array $texts, array $locales): self
    {
        $text = array_fill_keys($locales, '');
        foreach ($texts as $locale => $html) {
            $text[$locale] = (string) $html; // bahasa di luar $locales ikut dibawa, supaya tidak ada yang hilang
        }

        $id = 'blk_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $block = ['id' => $id, 'type' => 'paragraph', 'data' => ['text' => $text, 'margin_bottom' => 'mb-4 md:mb-6']];

        return new self([$id => $block], [$id], self::DEFAULT_SETTINGS, imported: true);
    }

    public function toArray(): array
    {
        return ['blocks' => $this->blocks, 'order' => $this->order, 'settings' => $this->settings];
    }

    // ------------------------------------------------------------------ referensi ke snippet

    /** Pola kunci snippet yang sah: huruf kecil, angka, tanda hubung (mis. "hubungi-kami"). */
    public const KEY_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';

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
}
