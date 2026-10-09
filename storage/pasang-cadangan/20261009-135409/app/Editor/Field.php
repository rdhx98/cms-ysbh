<?php

namespace App\Editor;

/**
 * Satu kontrol di inspektur. `key` adalah path RELATIF terhadap node yang sedang difokus,
 * mis. "data.style.size" -> content.{blockId}.data.style.size  (atau path elemen di dalam kartu).
 */
final class Field
{
    /** Tipe yang digambar di panel gaya (sebaris), sisanya satu baris penuh. */
    public const INLINE = ['segmented', 'swatches', 'select', 'icon', 'toggle'];

    public function __construct(
        public readonly string $type,
        public readonly string $key,
        public readonly string $label,
        public readonly array $options = [],
        public readonly mixed $default = null,
        /** [key, nilai]: kontrol hanya tampil bila nilai di `key` cocok (bool = truthy/falsy). */
        public readonly ?array $when = null,
        public readonly array $extra = [],
    ) {
    }

    public static function segmented(string $key, string $label, array $options, mixed $default = null, ?array $when = null, array $extra = []): self
    {
        return new self('segmented', $key, $label, $options, $default, $when, $extra);
    }

    /** $mode: bg | text | preview | hex (lihat <x-editor.swatches>) */
    public static function swatches(string $key, string $label, array $options, mixed $default = null, string $mode = 'bg', ?array $when = null): self
    {
        return new self('swatches', $key, $label, $options, $default, $when, ['mode' => $mode]);
    }

    public static function select(string $key, string $label, array $options, mixed $default = null, ?array $when = null, array $extra = []): self
    {
        return new self('select', $key, $label, $options, $default, $when, $extra);
    }

    public static function icon(string $key, string $label, string $default = 'box', bool $clearable = false): self
    {
        return new self('icon', $key, $label, [], $default, null, ['clearable' => $clearable]);
    }

    /**
     * Daftar item berulang (tombol, pertanyaan FAQ, ...). $fields = kontrol per item (key RELATIF terhadap item),
     * $itemDefaults = nilai satu item baru ("@id" = ID baru, "@locales" = peta bahasa kosong).
     *
     * @param Field[] $fields
     */
    public static function repeater(string $key, string $label, array $fields, array $itemDefaults, int $max = 12, string $itemLabel = 'Item'): self
    {
        return new self('repeater', $key, $label, [], null, null, ['fields' => $fields, 'defaults' => $itemDefaults, 'max' => $max, 'itemLabel' => $itemLabel]);
    }

    /** Tautan: jenis (halaman, artikel, berkas, URL, telepon, surel, anchor) + tujuannya. Nilai: {kind, ref, ref_label, media_id, url, new_tab}. */
    public static function link(string $key, string $label): self
    {
        return new self('link', $key, $label);
    }

    /** Teks per bahasa: key menunjuk ke objek {id, en}. */
    public static function i18n(string $key, string $label, bool $multi = false, int $rows = 3, ?array $when = null): self
    {
        return new self('i18n', $key, $label, [], null, $when, ['multi' => $multi, 'rows' => $rows]);
    }

    /**
     * Teks kaya (HTML dari Tiptap) per bahasa. SENGAJA belum bisa diedit di inspektur: <x-editor.rich>
     * hanya menampilkan teksnya sampai satu instance Tiptap dipasang (Fase 3), supaya HTML tidak rusak.
     */
    public static function rich(string $key, string $label, bool $multi = false): self
    {
        return new self('rich', $key, $label, [], null, null, ['multi' => $multi]);
    }

    public static function toggle(string $key, string $label, ?array $when = null): self
    {
        return new self('toggle', $key, $label, [], false, $when);
    }

    public static function text(string $key, string $label, array $extra = []): self
    {
        return new self('text', $key, $label, [], null, null, $extra);
    }

    /** Tombol File Manager; key menunjuk ke objek konten ({url, media_id, alt_text}). */
    public static function media(string $key, string $label, string $accept = 'image'): self
    {
        return new self('media', $key, $label, [], null, null, ['accept' => $accept]);
    }

    public function isInline(): bool
    {
        return in_array($this->type, self::INLINE, true);
    }

    /** Nilai-nilai yang sah untuk kontrol berpilihan; null untuk kontrol bebas. */
    public function allowedValues(): ?array
    {
        return in_array($this->type, ['segmented', 'swatches', 'select'], true)
            ? Options::values($this->options)
            : null;
    }
}
