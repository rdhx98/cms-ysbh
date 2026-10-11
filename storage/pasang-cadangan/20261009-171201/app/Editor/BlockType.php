<?php

namespace App\Editor;

final class BlockType
{
    /**
     * @param 'block'|'element' $kind  block = blok di pohon halaman; element = isi kartu (elementType)
     * @param Field[]           $fields
     */
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly string $icon,
        public readonly string $kind,
        public readonly array $fields,
        /** Nilai bawaan data blok baru (boleh memuat "@id"/"@locales", lihat Defaults). Kosong = pakai bawaan trait. */
        public readonly array $defaults = [],
    ) {
    }

    /** Kunci panel inspektur, mis. "block:heading" atau "element:text". */
    public function panelKey(): string
    {
        return $this->kind . ':' . $this->type;
    }

    /** @return Field[] */
    public function contentFields(): array
    {
        return array_values(array_filter($this->fields, fn (Field $f) => ! $f->isInline()));
    }

    /** @return Field[] */
    public function styleFields(): array
    {
        return array_values(array_filter($this->fields, fn (Field $f) => $f->isInline()));
    }
}
