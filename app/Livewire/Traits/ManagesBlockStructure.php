<?php

namespace App\Livewire\Traits;

use App\Editor\BlockPalette;
use Illuminate\Support\Str;

/**
 * Aksi struktur untuk outline: tambah di mana saja (dengan whitelist) dan pindah naik/turun.
 * Dipakai bersama HasContentBlocks (hapus & duplikat tetap memakai removeBlock()/duplicateBlock() miliknya,
 * yang HARUS versi patch: menghapus/menyalin seluruh turunan).
 *
 * Semua method publik di sini bisa dipanggil dari browser, jadi setiap argumen divalidasi.
 */
trait ManagesBlockStructure
{
    /**
     * Tambah blok baru. $parentId null = tingkat atas; selain itu harus kontainer yang ada, dengan $zone yang sah
     * dan $type yang diizinkan di kontainer itu.
     */
    public function addBlockAt(?string $parentId, ?string $zone, string $type): void
    {
        $type = BlockPalette::canonical($type);

        if ($parentId === null || $parentId === '') {
            if (!BlockPalette::allowedAtRoot($type)) {
                return;
            }
            $this->structureInsert(null, null, $type);
            return;
        }

        $parent = $this->content[$parentId] ?? null;
        if (!is_array($parent) || !BlockPalette::isContainer($parent['type'] ?? '')) {
            return;
        }
        if (!in_array($zone, array_column(BlockPalette::zonesFor($parent), 'key'), true)) {
            return;
        }
        if (!BlockPalette::allowsChild($parent['type'], $type)) {
            return;
        }

        $this->structureInsert($parentId, $zone, $type);
    }

    /** Geser blok satu posisi dalam daftarnya (tingkat atas atau zona induk). $delta: -1 naik, +1 turun. */
    public function moveBlock(string $id, int $delta): void
    {
        if (!isset($this->content[$id]) || !in_array($delta, [-1, 1], true)) {
            return;
        }

        if ($parent = $this->structureParentOf($id)) {
            [$parentId, $zone] = $parent;
            $list = $this->content[$parentId]['data'][$zone];
        } else {
            $parentId = $zone = null;
            $list = $this->blockOrder;
        }

        $i = array_search($id, $list, true);
        $j = $i === false ? false : $i + $delta;
        if ($i === false || $j < 0 || $j >= count($list)) {
            return;
        }

        [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
        $list = array_values($list);

        if ($parentId === null) {
            $this->blockOrder = $list;
        } else {
            $this->content[$parentId]['data'][$zone] = $list;
        }
    }

    private function structureInsert(?string $parentId, ?string $zone, string $type): void
    {
        do {
            $id = 'blk_' . Str::random(8);
        } while (isset($this->content[$id]));

        // getDefaultDataForType() memakai kunci "section_divider" (garis bawah) sedangkan tipe di editor memakai
        // "section-divider": tanpa ini blok pemisah seksi baru tersimpan dengan data kosong (temuan audit #1).
        $data = $this->getDefaultDataForType($type);
        if ($data === []) {
            $data = $this->getDefaultDataForType(str_replace('-', '_', $type));
        }

        $this->content[$id] = ['id' => $id, 'type' => $type, 'data' => $data];

        if ($parentId === null) {
            $this->blockOrder[] = $id;
        } else {
            $this->content[$parentId]['data'][$zone] = array_values([...($this->content[$parentId]['data'][$zone] ?? []), $id]);
        }

        // Outline memfokuskan blok baru lewat event ini
        $this->dispatch('block-added', id: $id);
    }

    /** @return array{0:string,1:string}|null [idInduk, zona] bila blok berada di dalam kontainer */
    private function structureParentOf(string $childId): ?array
    {
        foreach ($this->content as $parentId => $block) {
            foreach ($block['data'] ?? [] as $key => $value) {
                if (is_array($value) && ($key === 'children' || str_ends_with((string) $key, '_zone')) && in_array($childId, $value, true)) {
                    return [(string) $parentId, (string) $key];
                }
            }
        }
        return null;
    }
}