<?php

namespace App\Livewire\Traits;

use App\Editor\BlockPalette;
use App\Editor\BlockRegistry;
use App\Editor\CardPanel;
use App\Editor\Defaults;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;

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
     * dan $type yang diizinkan di kontainer itu. $index (rilis 43) = posisi di daftar tujuan (0 = paling atas); kosong atau di luar
     * jangkauan = paling bawah.
     */
    public function addBlockAt(?string $parentId, ?string $zone, string $type, ?int $index = null): void
    {
        $type = BlockPalette::canonical($type);

        if ($parentId === null || $parentId === '') {
            if (!BlockPalette::allowedAtRoot($type)) {
                return;
            }
            $this->structureInsert(null, null, $type, $index);
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

        if (!$this->structureZoneUsable($parent, $zone)) {
            return;
        }

        $this->structureInsert($parentId, $zone, $type, $index);
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

    // ------------------------------------------------------------------ Kartu Builder (rilis 32)
    // Tambah/hapus/duplikat kartu, kolom, dan geser kolom/elemen memakai method HasContentBlocks yang SUDAH ada (addCardItem,
    // duplicateCardItem, removeCardItem, addColumnToCard, removeColumnFromCard, moveCardColumn, moveCardElement,
    // removeElementFromColumn). Dua di bawah ini baru: menambah elemen dengan nilai bawaan dari REGISTRI (sesuai pilihan kontrol di
    // panel; addElementToColumn() di trait menulis nilai yang tidak ada di pilihan, temuan audit #2-#5) dan menggeser kartu.

    /**
     * Tambah kartu ke blok Kartu Builder dari sebuah templat (rilis 67). $preset: stack | icon-text | document (preset trait HasContentBlocks, lewat
     * addCardItem) atau blank = kartu tanpa kolom (rangka kosong; kolom ditambah sendiri di panel). Templat tak dikenal = stack. Batas jumlah kartu
     * (CardPanel::MAX_CARDS) ditegakkan di sini, bukan hanya di tombol.
     */
    public function addCardWithPreset(string $blockId, string $preset = 'stack'): void
    {
        $block = $this->content[$blockId] ?? null;
        if (!is_array($block) || BlockPalette::canonical($block['type'] ?? '') !== 'card-builder') {
            return;
        }
        $before = $block['data']['cards'] ?? [];
        if (!is_array($before) || count($before) >= CardPanel::MAX_CARDS) {
            return;
        }
        $preset = array_key_exists($preset, CardPanel::PRESETS) ? $preset : 'stack';

        $this->addCardItem($blockId, $preset === 'blank' ? 'stack' : $preset);

        $cards = $this->content[$blockId]['data']['cards'] ?? [];
        if ($preset === 'blank' && is_array($cards) && count($cards) === count($before) + 1) {
            $last = array_key_last($cards);
            $this->content[$blockId]['data']['cards'][$last]['layout']['children'] = [];
        }
    }

    /** Tambah elemen ke kolom (id kolom) pada kartu ke-$cardIndex. Jenis harus punya panel dan nilai bawaan di registri. */
    public function addCardElementAt(string $blockId, int $cardIndex, string $columnId, string $elementType): void
    {
        $block = $this->content[$blockId] ?? null;
        if (!is_array($block) || BlockPalette::canonical($block['type'] ?? '') !== 'card-builder') {
            return;
        }
        $definition = BlockRegistry::element($elementType);
        if (!$definition || !$definition->defaults) {
            return;
        }
        $columns = $block['data']['cards'][$cardIndex]['layout']['children'] ?? null;
        if (!is_array($columns)) {
            return;
        }
        foreach ($columns as $c => $column) {
            if (!is_array($column) || ($column['id'] ?? null) !== $columnId) {
                continue;
            }
            $elements = is_array($column['children'] ?? null) ? $column['children'] : [];
            if (count($elements) >= CardPanel::MAX_ELEMENTS) {
                return;
            }
            $elements[] = [
                'id' => 'el_' . Str::random(10),
                'type' => 'element',
                'elementType' => $definition->type,
                'data' => Defaults::materialize($definition->defaults, $this->activeLocales ?: ['id', 'en']),
            ];
            $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'][$c]['children'] = array_values($elements);

            return;
        }
    }

    /** Geser kartu satu posisi di dalam blok kartu. $delta: -1 ke awal, +1 ke akhir. */
    public function moveCard(string $blockId, int $cardIndex, int $delta): void
    {
        $block = $this->content[$blockId] ?? null;
        if (!is_array($block) || BlockPalette::canonical($block['type'] ?? '') !== 'card-builder' || !in_array($delta, [-1, 1], true)) {
            return;
        }
        $cards = $block['data']['cards'] ?? null;
        $j = $cardIndex + $delta;
        if (!is_array($cards) || !array_key_exists($cardIndex, $cards) || !array_key_exists($j, $cards)) {
            return;
        }
        [$cards[$cardIndex], $cards[$j]] = [$cards[$j], $cards[$cardIndex]];
        $this->content[$blockId]['data']['cards'] = array_values($cards);
    }

    private function structureInsert(?string $parentId, ?string $zone, string $type, ?int $index = null): void
    {
        do {
            $id = 'blk_' . Str::random(8);
        } while (isset($this->content[$id]));

        // getDefaultDataForType() memakai kunci "section_divider" (garis bawah) sedangkan tipe di editor memakai
        // "section-divider": tanpa ini blok pemisah seksi baru tersimpan dengan data kosong (temuan audit #1).
        // Tipe yang punya nilai bawaan di REGISTRI (mis. tombol) memakainya; tipe lama memakai bawaan trait.
        $definition = BlockRegistry::block($type);
        if ($definition && $definition->defaults) {
            $data = Defaults::materialize($definition->defaults, $this->activeLocales ?: ['id', 'en']);
        } else {
            $data = $this->getDefaultDataForType($type);
            if ($data === []) {
                $data = $this->getDefaultDataForType(str_replace('-', '_', $type));
            }
        }

        $this->content[$id] = ['id' => $id, 'type' => $type, 'data' => $data];

        if ($parentId === null) {
            $this->blockOrder = self::structureSplice($this->blockOrder, $id, $index);
        } else {
            $this->content[$parentId]['data'][$zone] = self::structureSplice($this->content[$parentId]['data'][$zone] ?? [], $id, $index);
        }

        // Outline memfokuskan blok baru lewat event ini
        $this->dispatch('block-added', id: $id);
    }

    // ------------------------------------------------------------------ pindah lintas level (rilis 43)

    /**
     * Pindahkan blok ke daftar lain: tingkat atas ($parentId kosong) atau zona kontainer, pada posisi $index (posisi AKHIR di daftar tujuan,
     * sesudah blok dilepas dari daftar asal; kosong atau di luar jangkauan = paling bawah). Semua argumen datang dari browser:
     * blok harus ada; tujuan harus kontainer yang ada dengan zona sah yang TIDAK tersembunyi dan jenis anak yang diizinkan
     * (BlockPalette::allowsChild), atau tingkat atas untuk jenis yang boleh di sana (allowedAtRoot); tidak boleh ke dalam dirinya sendiri
     * atau turunannya. Hanya referensi yang berpindah; isi blok tidak disentuh.
     *
     * @return bool true bila dipindah (atau sudah di tempat itu)
     */
    public function moveBlockTo(string $id, ?string $parentId, ?string $zone, ?int $index = null): bool
    {
        $block = $this->content[$id] ?? null;
        if (!is_array($block) || !$this->structureLocate($id)) {
            return false;
        }
        $type = BlockPalette::canonical((string) ($block['type'] ?? ''));
        $parentId = ($parentId === null || $parentId === '') ? null : $parentId;

        if ($parentId === null) {
            if (!BlockPalette::allowedAtRoot($type)) {
                return false;
            }
            $zone = null;
        } else {
            $parent = $this->content[$parentId] ?? null;
            if (!is_array($parent) || !BlockPalette::isContainer((string) ($parent['type'] ?? ''))
                || !$this->structureZoneUsable($parent, (string) $zone) || !BlockPalette::allowsChild((string) $parent['type'], $type)) {
                return false;
            }
            if ($parentId === $id || in_array($parentId, $this->structureDescendants($id), true)) {
                return false;   // ke dalam dirinya sendiri / turunannya: pohon akan terputus dari akar
            }
        }

        // lepas dari daftar asal
        [$fromParent, $fromZone] = $this->structureLocate($id);
        if ($fromParent === null) {
            $this->blockOrder = array_values(array_filter($this->blockOrder, fn ($x) => $x !== $id));
        } else {
            $this->content[$fromParent]['data'][$fromZone] = array_values(array_filter($this->content[$fromParent]['data'][$fromZone] ?? [], fn ($x) => $x !== $id));
        }

        // pasang di daftar tujuan
        if ($parentId === null) {
            $this->blockOrder = self::structureSplice($this->blockOrder, $id, $index);
        } else {
            $this->content[$parentId]['data'][$zone] = self::structureSplice($this->content[$parentId]['data'][$zone] ?? [], $id, $index);
        }

        return true;
    }

    /** Keluarkan blok dari kontainernya: ia menjadi blok tingkat atas tepat SESUDAH kontainer itu. Blok yang sudah di tingkat atas: tidak berubah. */
    public function outdentBlock(string $id): void
    {
        $where = $this->structureLocate($id);
        if ($where === null || $where[0] === null) {
            return;
        }
        $grand = $this->structureLocate($where[0]);   // kontainer itu sendiri: di tingkat atas, atau (kelak) di zona lain
        if ($grand === null) {
            return;
        }
        $list = $grand[0] === null ? $this->blockOrder : ($this->content[$grand[0]]['data'][$grand[1]] ?? []);
        $at = array_search($where[0], $list, true);

        $this->moveBlockTo($id, $grand[0], $grand[1], $at === false ? null : $at + 1);
    }

    /** Masukkan blok ke blok KONTAINER tepat di atasnya (saudara sebelumnya): ke zona terakhir yang tampil, paling bawah. Bukan kontainer / jenis tak cocok: tidak berubah. */
    public function indentBlock(string $id): void
    {
        $where = $this->structureLocate($id);
        if ($where === null) {
            return;
        }
        $list = $where[0] === null ? $this->blockOrder : ($this->content[$where[0]]['data'][$where[1]] ?? []);
        $at = array_search($id, $list, true);
        $prev = ($at !== false && $at > 0) ? ($list[$at - 1] ?? null) : null;
        $prevBlock = is_string($prev) ? ($this->content[$prev] ?? null) : null;
        if (!is_array($prevBlock) || !BlockPalette::isContainer((string) ($prevBlock['type'] ?? ''))) {
            return;
        }
        $zones = array_values(array_filter(BlockPalette::zonesFor($prevBlock), fn ($z) => !$z['hidden']));
        if ($zones === []) {
            return;
        }

        $this->moveBlockTo($id, $prev, $zones[count($zones) - 1]['key'], null);
    }

    /**
     * Tujuan yang SAH untuk blok ini (menu "Pindahkan ke…"): tingkat atas dan setiap zona kontainer yang menerima jenisnya, urutan dokumen.
     * Dihitung saat menu dibuka (#[Renderless]), bukan untuk setiap baris outline.
     *
     * @return list<array{parent:?string,zone:?string,label:string,current:bool}>
     */
    #[Renderless]
    public function moveTargets(string $id): array
    {
        $block = $this->content[$id] ?? null;
        $here = $this->structureLocate($id);
        if (!is_array($block) || $here === null) {
            return [];
        }
        $type = BlockPalette::canonical((string) ($block['type'] ?? ''));
        $out = [];
        if (BlockPalette::allowedAtRoot($type)) {
            $out[] = ['parent' => null, 'zone' => null, 'label' => 'Tingkat atas', 'current' => $here[0] === null];
        }

        $blocked = [$id, ...$this->structureDescendants($id)];
        $count = [];
        foreach ($this->structureWalk() as $cid) {
            $container = $this->content[$cid] ?? null;
            if (!is_array($container) || !BlockPalette::isContainer((string) ($container['type'] ?? ''))) {
                continue;
            }
            $ctype = BlockPalette::canonical((string) $container['type']);
            $n = $count[$ctype] = ($count[$ctype] ?? 0) + 1;
            if (in_array($cid, $blocked, true) || !BlockPalette::allowsChild($ctype, $type)) {
                continue;
            }
            $zones = array_values(array_filter(BlockPalette::zonesFor($container), fn ($z) => !$z['hidden']));
            foreach ($zones as $z) {
                $out[] = [
                    'parent' => $cid,
                    'zone' => $z['key'],
                    'label' => BlockPalette::label($ctype) . ' #' . $n . (count($zones) > 1 ? ' › ' . $z['label'] : ''),
                    'current' => $here[0] === $cid && $here[1] === $z['key'],
                ];
            }
        }

        return $out;
    }

    /** Zona ada di kontainer, dan tidak tersembunyi (melebihi jumlah kolom). */
    private function structureZoneUsable(array $parent, ?string $zone): bool
    {
        foreach (BlockPalette::zonesFor($parent) as $z) {
            if ($z['key'] === $zone) {
                return !$z['hidden'];
            }
        }

        return false;
    }

    /** Sisipkan $id ke $list pada $index (null / di luar jangkauan = paling bawah). */
    private static function structureSplice(array $list, string $id, ?int $index): array
    {
        $list = array_values($list);
        $at = $index === null ? count($list) : max(0, min($index, count($list)));
        array_splice($list, $at, 0, [$id]);

        return $list;
    }

    /** Letak blok: [null, null] di tingkat atas, [idInduk, zona] di dalam kontainer, null bila tidak terpasang di pohon. @return array{0:?string,1:?string}|null */
    private function structureLocate(string $id): ?array
    {
        if (in_array($id, $this->blockOrder, true)) {
            return [null, null];
        }

        return $this->structureParentOf($id);
    }

    /** Semua turunan blok (anak, cucu, ...), tahan siklus. @return list<string> */
    private function structureDescendants(string $id): array
    {
        $seen = [];
        $stack = [$id];
        while ($stack) {
            $cur = array_pop($stack);
            foreach ($this->content[$cur]['data'] ?? [] as $key => $value) {
                if (!is_array($value) || !($key === 'children' || str_ends_with((string) $key, '_zone'))) {
                    continue;
                }
                foreach ($value as $child) {
                    if (is_string($child) && !isset($seen[$child]) && $child !== $id) {
                        $seen[$child] = true;
                        $stack[] = $child;
                    }
                }
            }
        }

        return array_keys($seen);
    }

    /** Id semua blok dalam urutan dokumen (tingkat atas, lalu isi tiap zona), tahan siklus. @return list<string> */
    private function structureWalk(): array
    {
        $out = [];
        $seen = [];
        $visit = function (string $id) use (&$visit, &$out, &$seen): void {
            if (isset($seen[$id]) || !isset($this->content[$id])) {
                return;
            }
            $seen[$id] = true;
            $out[] = $id;
            foreach ($this->content[$id]['data'] ?? [] as $key => $value) {
                if (is_array($value) && ($key === 'children' || str_ends_with((string) $key, '_zone'))) {
                    foreach ($value as $child) {
                        if (is_string($child)) {
                            $visit($child);
                        }
                    }
                }
            }
        };
        foreach ($this->blockOrder as $id) {
            if (is_string($id)) {
                $visit($id);
            }
        }

        return $out;
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
