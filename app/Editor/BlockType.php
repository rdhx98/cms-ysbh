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
        /** Komponen Blade tambahan di bawah kontrol blok ini (mis. "editor.cards": daftar kartu → kolom → elemen). null = tidak ada. */
        public readonly ?string $component = null,
        /** true = komponen tambahan tampil DI ATAS kontrol blok (mis. kisi isi tabel), bukan di bawahnya. */
        public readonly bool $componentFirst = false,
    ) {
    }

    /** Salinan dengan kontrol tambahan di akhir daftar (BlockType tak bisa diubah). */
    public function withFields(array $extra): self
    {
        return new self($this->type, $this->label, $this->icon, $this->kind, [...$this->fields, ...$extra], $this->defaults, $this->component, $this->componentFirst);
    }

    /** Apakah ada kontrol dengan salah satu key ini. */
    public function hasField(string ...$keys): bool
    {
        foreach ($this->fields as $f) {
            if (in_array($f->key, $keys, true)) {
                return true;
            }
        }

        return false;
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
