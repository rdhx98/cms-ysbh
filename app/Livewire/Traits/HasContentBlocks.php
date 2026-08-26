<?php
namespace App\Livewire\Traits;

use Illuminate\Support\Str;

trait HasContentBlocks
{
    public function addBlockWithOrder($type, $orderedIds = []) {
        if (!empty($orderedIds)) {
            $this->updateBlockOrder($orderedIds);
        }
        $this->addBlock($type);
    }

    public function addBlock(String $type) {
        $id = 'blk_' . Str::random(8);
        $this->content[$id] = [
            'id' => $id,
            'type' => $type,
            'data' => $this->getDefaultDataForType($type)
        ];

        $this->blockOrder[] = $id; // Letakkan di paling bawah
        $this->dispatch('block-added', id: $id);
    }

    // public function removeBlock(String $blockId) {
    //     unset($this->content[$blockId]);
    //     $this->blockOrder = array_values(array_filter($this->blockOrder, fn($id) => $id !== $blockId));
    // }
    public function removeBlock(String $blockId) {
        // 1. Hapus dari gudang data utama
        unset($this->content[$blockId]);

        // 2. Bersihkan dari urutan terluar (Root)
        $this->blockOrder = array_values(array_filter($this->blockOrder, fn($id) => $id !== $blockId));

        // 3. 🌟 PEMBERSIHAN MENDALAM: Hapus ID hantu dari dalam SEMUA kolom/zona
        foreach ($this->content as $parentId => $blockData) {
            // Bersihkan zona kiri jika ada
            if (isset($blockData['data']['left_zone'])) {
                $this->content[$parentId]['data']['left_zone'] = array_values(array_filter($blockData['data']['left_zone'], fn($id) => $id !== $blockId));
            }
            // Bersihkan zona kanan jika ada
            if (isset($blockData['data']['right_zone'])) {
                $this->content[$parentId]['data']['right_zone'] = array_values(array_filter($blockData['data']['right_zone'], fn($id) => $id !== $blockId));
            }
        }
    }

    public function duplicateBlock(String $id){
        if (isset($this->content[$id])) {
            $newId = 'blk_' . uniqid();
            $duplicatedBlock = $this->content[$id];
            $duplicatedBlock['id'] = $newId;

            // Simpan konten kloningannya
            $this->content[$newId] = $duplicatedBlock;

            // Sisipkan ke urutan tepat di bawah blok aslinya
            $index = array_search($id, $this->blockOrder);
            if ($index !== false) {
                array_splice($this->blockOrder, $index + 1, 0, [$newId]);
            } else {
                $this->blockOrder[] = $newId;
            }
        }
    }


    // public function updateBlockOrder(Array $orderedIds = []) {
    //     if (is_string($orderedIds)) {
    //         $orderedIds = json_decode($orderedIds, true) ?? [];
    //     }

    //     if (is_array($orderedIds) && !empty($orderedIds)) {
    //         // Ambil ID yang valid saja dari hasil drag-and-drop
    //         $validIds = array_filter($orderedIds, fn($id) => isset($this->content[$id]));

    //         // 🌟 PERBAIKAN KRUSIAL:
    //         // Amankan sisa blok DARI URUTAN ROOT SEBELUMNYA, BUKAN DARI SELURUH KONTEN.
    //         // Ini mencegah blok anak yang ada di dalam kolom ikut terseret ke luar.
    //         $missingIds = array_diff($this->blockOrder, $validIds);

    //         $this->blockOrder = array_values(array_merge($validIds, $missingIds));
    //     }
    // }

    public function updateBlockOrder(Array $orderedIds = []) {
        if (is_string($orderedIds)) {
            $orderedIds = json_decode($orderedIds, true) ?? [];
        }

        if (is_array($orderedIds) && !empty($orderedIds)) {

            // 🌟 PERBAIKAN BUG: FILTER SUPER KETAT
            // Alih-alih mengecek isset($this->content[$id]), kita HANYA menerima
            // ID yang memang sebelumnya sudah terdaftar di $this->blockOrder (root).
            $validIds = array_filter($orderedIds, function($id) {
                return in_array($id, $this->blockOrder);
            });

            // Amankan sisa blok root jika ada yang terlewat oleh frontend
            $missingIds = array_diff($this->blockOrder, $validIds);

            // Gabungkan urutan baru yang valid dengan blok yang tersisa
            $this->blockOrder = array_values(array_merge($validIds, $missingIds));
        }
    }

    private function getDefaultDataForType(String $type) {
        $emptyLocales = [];
        foreach ($this->activeLocales as $locale) {
            $emptyLocales[$locale] = '';
        }

        return match($type) {
            'heading'    => [
                'text' => $emptyLocales,
                'level' => 'h2'],
            'paragraph'  => [
                'text' => $emptyLocales
                ],
            'image'      => ['url' => ''],
            'media_text' => ['image_url' => '', 'image_position' => 'left', 'text' => $emptyLocales],
            // 'columns'    => [
            //     'col_left'  => $emptyLocales,
            //     'col_right' => $emptyLocales,
            // ],
            // 🌟 BLOK STATS GRID DENGAN PROPERTI WARNA
            'columns'    => [
                'left_zone'  => [],
                'right_zone' => [],
                'bg_color'   => 'transparent',
            ],
            'stats_grid' => [
                'columns'      => 4,
                'color_title'  => '#eab308', // Default: Kuning (seperti gambar)
                'color_desc'   => '#ffffff', // Default: Putih
                'color_border' => '#4b5563', // Default: Abu-abu
                'items' => [
                    ['title' => $emptyLocales, 'description' => $emptyLocales],
                    ['title' => $emptyLocales, 'description' => $emptyLocales],
                    ['title' => $emptyLocales, 'description' => $emptyLocales],
                    ['title' => $emptyLocales, 'description' => $emptyLocales],
                ]
            ],
            // BLOK TESTIMONI DINAMIS (Dari Database)
            'dynamic_testimonials' => [
                'tagline'      => $emptyLocales,
                'title'        => $emptyLocales,
                'limit'        => 5,         // Berapa maksimal kartu yang ditarik dari DB
                'order_by'     => 'latest',  // Pilihan: 'latest' (terbaru) atau 'random' (acak)
            ],

            default => []
        };
    }
    // 🌟 FUNGSI UNTUK MENAMBAH KOTAK INFO
    public function addStatItem(String $blockId)
    {
        $emptyLocales = array_fill_keys($this->activeLocales, '');

        // Batasi maksimal 6 kotak
        if (isset($this->content[$blockId]['data']['items']) && count($this->content[$blockId]['data']['items']) < 6) {
            $this->content[$blockId]['data']['items'][] = [
                'title' => $emptyLocales,
                'description' => $emptyLocales,
            ];
        }
    }

    // 🌟 FUNGSI UNTUK MENGHAPUS KOTAK INFO
    public function removeStatItem(String $blockId, int $itemIndex)
    {
        if (isset($this->content[$blockId]['data']['items'][$itemIndex])) {
            unset($this->content[$blockId]['data']['items'][$itemIndex]);
            // Re-index array agar tidak error di foreach Blade
            $this->content[$blockId]['data']['items'] = array_values($this->content[$blockId]['data']['items']);
        }
    }
    public function addArrayItem(String $blockId, String $arrayKey)
    {
        // Struktur kosong default untuk tombol/pill
        $newItem = [];
        foreach ($this->activeLocales as $locale) {
            $newItem['text'][$locale] = '';
        }

        if ($arrayKey === 'buttons') {
            $newItem['url'] = '#';
            $newItem['link_type'] = 'internal';
            $newItem['style'] = 'solid_coral';
        } elseif ($arrayKey === 'pills') {
            $newItem['url'] = '#';
            $newItem['icon'] = 'activity';
            $newItem['bg_color'] = '#fef3c7'; // default goldy-soft
        }

        // Tambahkan ke array
        $this->content[$blockId]['data'][$arrayKey][] = $newItem;
    }

    public function removeArrayItem(String $blockId, String $arrayKey, int $index)
    {
        if (isset($this->content[$blockId]['data'][$arrayKey][$index])) {
            unset($this->content[$blockId]['data'][$arrayKey][$index]);
            // Re-index array agar tidak loncat
            $this->content[$blockId]['data'][$arrayKey] = array_values($this->content[$blockId]['data'][$arrayKey]);
        }
    }
    /**
     * Menambahkan blok anak ke dalam zona milik induk (Kontainer)
     */
    public function addChildBlock($parentId, $zone, $type = 'paragraph')
    {
        // 1. Buat ID unik untuk anak baru
        $newChildId = 'blk_' . uniqid();

        // 2. Siapkan data default sejajar di root $this->content
        $this->content[$newChildId] = [
            'type' => $type,
            'data' => [
                'text' => ['id' => '', 'en' => '']
            ]
        ];

        // 3. Pastikan array zona tersedia di induk, lalu masukkan ID anak
        if (!isset($this->content[$parentId]['data'][$zone])) {
            $this->content[$parentId]['data'][$zone] = [];
        }
        $this->content[$parentId]['data'][$zone][] = $newChildId;
    }

    /**
     * Menyimpan urutan baru blok anak setelah di drag-and-drop
     */
    public function reorderChildBlocks($parentId, $zone, $newOrderIds)
    {
        $this->content[$parentId]['data'][$zone] = $newOrderIds;
    }

    // Fungsi-fungsi manipulasi blok lainnya ditaruh di sini...
}
