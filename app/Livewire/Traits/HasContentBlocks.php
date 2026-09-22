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

    /**
     * OLD AF Menambahkan blok anak ke dalam zona milik induk (Kontainer)
     */
    // public function addChildBlock($parentId, $zone, $type = 'paragraph')
    // {
    //     // 1. Buat ID unik untuk anak baru
    //     $newChildId = 'blk_' . uniqid();

    //     // 2. Siapkan data default sejajar di root $this->content
    //     $this->content[$newChildId] = [
    //         'type' => $type,
    //         'data' => [
    //             'text' => ['id' => '', 'en' => '']
    //         ]
    //     ];

    //     // 3. Pastikan array zona tersedia di induk, lalu masukkan ID anak
    //     if (!isset($this->content[$parentId]['data'][$zone])) {
    //         $this->content[$parentId]['data'][$zone] = [];
    //     }
    //     $this->content[$parentId]['data'][$zone][] = $newChildId;
    //     $this->dispatch('block-added', id: $newChildId);
    // }

		public function addChildBlock($parentId, $zone, $type = 'paragraph')
    {
        // 1. Buat ID unik untuk anak baru
        $newChildId = 'blk_' . uniqid();

        // 🌟 PERBAIKAN: Ambil struktur data default yang benar dari match($type)
        // Pastikan Anda memanggil metode yang berisi kerangka match($type) Anda.
        // (Biasanya bernama getBlockDefaultData, getDefaultData, atau serupa yang Anda pakai di fungsi tambah blok utama)

        $defaultData = $this->getDefaultDataForType($type); // Sesuaikan nama fungsinya dengan milik Anda!

        // 2. Siapkan data default sejajar di root $this->content
        $this->content[$newChildId] = [
            'type' => $type,
            'data' => $defaultData // ✅ Sekarang data yang dimasukkan sudah komplit (ada align, buttons, dll)
        ];

        // 3. Pastikan array zona tersedia di induk, lalu masukkan ID anak
        if (!isset($this->content[$parentId]['data'][$zone])) {
            $this->content[$parentId]['data'][$zone] = [];
        }

        $this->content[$parentId]['data'][$zone][] = $newChildId;

        $this->dispatch('block-added', id: $newChildId);
    }

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
    public function removeNestedBlock(string $parentId, string $zone, string $childId)
    {
        // 1. Hapus ID anak dari zona induknya
        if (isset($this->content[$parentId]['data'][$zone])) {
            $this->content[$parentId]['data'][$zone] = array_values(array_diff(
                $this->content[$parentId]['data'][$zone],
                [$childId]
            ));
        }

        // 2. Hapus isi data blok anak itu sendiri dari memori global
        if (isset($this->content[$childId])) {
            unset($this->content[$childId]);
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
                'mobile_reverse' => 'false'
                // 'bg_color'   => 'transparent',
            ],
            'multi-columns' => [
              'col_count'      => 2, // Default saat pertama kali ditambahkan
              'mobile_reverse' => false,
							'align_x'        => 'items-start',   // 🌟 BARU
							'align_y'        => 'justify-start', // 🌟 BARU
							'gap'            => 'gap-2',           // 🌟 BARU

              // Siapkan 6 zona sekaligus (walau yang dirender nanti hanya sesuai col_count)
              'col_1_zone' => [],
              'col_2_zone' => [],
              'col_3_zone' => [],
              'col_4_zone' => [],
              'col_5_zone' => [],
              'col_6_zone' => [],
            ],
            'section_divider' => [
                'background' => 'bg-white',
                'text_color' => 'text-gray-900',
                'padding'    => 'py-16 sm:py-24',
            ],
						// 'card-builder' => [
            //     'template'  => '', // Kosong di awal agar klien memilih dulu
            //     'col_count' => 3,
            //     'items'     => [] // Akan diisi otomatis setelah template dipilih
            // ],
						'card-builder' => [
                'grid' => [
                    'cols' => 3,
                    'margin_bottom' => 'mb-8'
                ],
                'cards' => []
            ],
						// 'buttons' => [
						// 	[
						// 		'label' => ['id' => 'Donasi Sekarang', 'en' => 'Donate Now'],
						// 		'url' => '#donasi',
						// 		'style' => 'primary' // opsi: primary / outline
						// 	],
						// 		// ... bisa tambah tombol lagi
						// ],
						// 'badges' => [
						// 		[
						// 			'label' => ['id' => 'Ibu & Anak', 'en' => 'Mother & Child'],
						// 			'url' => '#program',
						// 			'icon' => 'heart-pulse', // Sesuaikan dengan nama ikon Lucide
						// 			'icon_bg' => 'bg-goldy-soft',
						// 			'icon_color' => '#064F3B',
						// 		],
						// 			// ... bisa tambah badge lagi
						// 	],
            'button-group' => [
              'align' => 'left', // 🌟 1. TAMBAHKAN BARIS INI DI SINI
              'buttons' => [
                [
                  'label' => ['id' => 'Donasi Sekarang', 'en' => 'Donate Now'],
                  'url' => '#donasi',
                  'style' => 'primary' // opsi: primary / secondary / outline / text
                ],
              ],
            ],

            'badge-group' => [
              'align' => 'left',
              'badges' => [
                [
                  'label' => ['id' => 'Ibu & Anak', 'en' => 'Mother & Child'],
                  'url' => '#program',
                  'icon' => 'heart-pulse',
                  'icon_bg' => 'bg-goldy-soft',
                  'icon_color' => '#064F3B'
                ],
              ],
            ],
						'stats-group' => [
                'align' => 'left',
                'stats' => [
                    [
                        'value' => ['id' => '2014', 'en' => '2014'],
                        'label' => ['id' => 'Tahun berdiri', 'en' => 'Founded'],
                    ],
                    [
                        'value' => ['id' => '76', 'en' => '76'],
                        'label' => ['id' => 'Desa dampingan', 'en' => 'Villages'],
                    ],
                    [
                        'value' => ['id' => '18', 'en' => '18'],
                        'label' => ['id' => 'Kabupaten tersebar', 'en' => 'Districts'],
                    ],
                ]
            ],
						'card-group' => [
                'col_count' => 3, // Pilihan: 1, 2, 3, atau 4 kolom
                'cards' => [
                    [
                        'icon'        => 'activity',
                        'icon_bg'     => 'bg-goldy-soft',
                        'icon_color'  => '#064F3B', // foresty
                        'eyebrow'     => ['id' => 'Kesehatan Ibu & Anak', 'en' => 'Maternal & Child Health'],
                        'title'       => ['id' => 'Mendampingi Sejak dalam Kandungan', 'en' => 'Supporting Since Pregnancy'],
                        'description' => ['id' => 'Pemeriksaan kehamilan berkala, pendampingan persalinan aman bersama bidan desa...', 'en' => 'Regular checkups, safe delivery...'],
                        'url'         => '',
                    ],
                    [
                        'icon'        => 'shield-check',
                        'icon_bg'     => 'bg-mist',
                        'icon_color'  => '#064F3B',
                        'eyebrow'     => ['id' => 'Imunisasi', 'en' => 'Immunization'],
                        'title'       => ['id' => 'Vaksin Lengkap, Sampai ke Pelosok', 'en' => 'Complete Vaccines to Remote Areas'],
                        'description' => ['id' => 'Menjangkau anak-anak di dusun terpencil dengan imunisasi dasar lengkap...', 'en' => 'Reaching children in remote villages...'],
                        'url'         => '',
                    ],
                    [
                        'icon'        => 'bug',
                        'icon_bg'     => 'bg-coral/20',
                        'icon_color'  => '#E06B5E', // coral
                        'eyebrow'     => ['id' => 'Penanganan Malaria', 'en' => 'Malaria Treatment'],
                        'title'       => ['id' => 'Memutus Rantai Penularan', 'en' => 'Breaking the Chain of Transmission'],
                        'description' => ['id' => 'Distribusi kelambu berinsektisida, tes cepat untuk deteksi dini...', 'en' => 'Distribution of insecticide-treated nets...'],
                        'url'         => '',
                    ],
                ]
            ],
						'testimonial-group' => [
                'col_count' => 3, // Pilihan: 1, 2, atau 3 kolom
                'testimonials' => [
                    [
                        'quote'  => ['id' => 'Kader posyandu di kampung kami jadi lebih percaya diri mendampingi ibu hamil sejak ada pelatihan rutin dari yayasan.', 'en' => 'Health cadres in our village are more confident...'],
                        'name'   => ['id' => 'Sri Wahyuni', 'en' => 'Sri Wahyuni'],
                        'role'   => ['id' => 'Kader Posyandu, Sikka · NTT', 'en' => 'Health Cadre, Sikka · NTT'],
												'theme' => 'theme-forest'
                    ],
                    [
                        'quote'  => ['id' => 'Anak saya sekarang lengkap imunisasinya. Petugas datang langsung ke dusun, kami tidak perlu jalan jauh lagi.', 'en' => 'My child is now fully immunized...'],
                        'name'   => ['id' => 'Fatimah', 'en' => 'Fatimah'],
                        'role'   => ['id' => 'Warga, Waepana · Manggarai', 'en' => 'Resident, Waepana · Manggarai'],
												'theme' => 'theme-forest'
                    ],
                    [
                        'quote'  => ['id' => 'Sejak dapat kelambu dan edukasi rutin, kasus malaria di dusun kami turun jauh dibanding tiga tahun lalu.', 'en' => 'Since receiving mosquito nets...'],
                        'name'   => ['id' => 'Yosef Bunga', 'en' => 'Yosef Bunga'],
                        'role'   => ['id' => 'Kepala Dusun, Sumba Timur', 'en' => 'Village Head, East Sumba'],
												'theme' => 'theme-forest'
                    ],
                ]
            ],


            'stats-grid' => [
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
            'dynamic-testimonials' => [
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
     * Menyimpan urutan baru blok anak setelah di drag-and-drop
     */
    public function reorderChildBlocks($parentId, $zone, $newOrderIds)
    {
        $this->content[$parentId]['data'][$zone] = $newOrderIds;
    }

    // Fungsi-fungsi manipulasi blok lainnya ditaruh di sini...

		// ==========================================
    // LOGIKA KHUSUS CARD BUILDER
    // ==========================================

    // OLD
    // public function addCardItem(String $blockId, String $blueprint)
    // {
    //     $newCard = [
    //         'id' => uniqid('card_'),
    //         'blueprint' => $blueprint,
    //         'container' => [
    //             'bg' => 'bg-white', 'padding' => 'p-5', 'border' => 'border border-gray-200', 'radius' => 'rounded-[18px]', 'shadow' => 'shadow-sm', 'hover' => 'hover:-translate-y-1', 'url' => ''
    //         ],
    //         'slots' => $blueprint === 'stack' ? ['main' => []] : ['left' => [], 'middle' => [], 'right' => []],
    //     ];

    //     if (!isset($this->content[$blockId]['data']['cards'])) {
    //         $this->content[$blockId]['data']['cards'] = [];
    //     }
    //     $this->content[$blockId]['data']['cards'][] = $newCard;
    // }

    public function removeCardItem(String $blockId, int $index)
    {
        if (isset($this->content[$blockId]['data']['cards'][$index])) {
            unset($this->content[$blockId]['data']['cards'][$index]);
            // Re-index array agar Blade tidak panik saat looping
            $this->content[$blockId]['data']['cards'] = array_values($this->content[$blockId]['data']['cards']);
        }
    }

    public function addCardElement(String $blockId, int $cardIndex, String $slotName, String $type)
    {
        $el = ['id' => uniqid('el_'), 'type' => $type, 'content' => [], 'style' => []];

        // Buat struktur bahasa kosong
        foreach ($this->activeLocales as $loc) { $el['content'][$loc] = ''; }

        if ($type === 'text') {
            $el['style'] = ['is_pill' => false, 'font' => 'font-sans', 'size' => 'text-[15px]', 'weight' => 'font-normal', 'color' => 'text-ink-soft', 'align' => 'text-left', 'margin' => 'mb-0', 'pill_bg' => 'bg-goldy-soft', 'pill_radius' => 'rounded-full'];
        } else {
            $el['content']['icon'] = 'box';
            $el['style'] = ['bg' => 'bg-goldy-soft', 'color' => 'text-foresty', 'radius' => 'rounded-[14px]', 'size' => 'w-[52px] h-[52px]', 'icon_size' => 'w-6 h-6', 'hover' => ''];
        }

        $this->content[$blockId]['data']['cards'][$cardIndex]['slots'][$slotName][] = $el;
    }

    public function removeCardElement(String $blockId, int $cardIndex, String $slotName, int $elIndex)
    {
        if (isset($this->content[$blockId]['data']['cards'][$cardIndex]['slots'][$slotName][$elIndex])) {
            unset($this->content[$blockId]['data']['cards'][$cardIndex]['slots'][$slotName][$elIndex]);
            $this->content[$blockId]['data']['cards'][$cardIndex]['slots'][$slotName] = array_values($this->content[$blockId]['data']['cards'][$cardIndex]['slots'][$slotName]);
        }
    }

  /*Below is NEW implementation*/

  /**
   * Tambahan untuk HasContentBlocks.php - model layout kartu row/column/element.
   *
   * addCardItem() yang lama diganti (parameter kedua sekarang nama PRESET,
   * bukan 'stack'/'media-object' langsung - lihat cardLayoutPresets()).
   * Method-method baru di bawahnya BOLEH ditambahkan berdampingan dengan
   * method lain yang sudah ada di trait Anda (removeCardItem, dst) - tidak
   * ada yang perlu dihapus selain addCardItem() versi lama.
   *
   * CATATAN PENTING mengikuti pelajaran dari sesi debugging sebelumnya:
   * setiap node BARU (column atau element) SELALU dibuatkan 'id' unik lewat
   * uniqid() saat dibuat - tidak pernah mengandalkan index posisi di array,
   * supaya wire:key di Blade nanti selalu punya sumber ID yang stabil.
   */

  /** Tiga titik awal cepat - lihat docs/card-layout-schema.md */
  protected function cardLayoutPresets(): array
{
    return [
        'stack' => [
            'type' => 'row',
            'children' => [
                ['id' => uniqid('col_'), 'type' => 'column', 'width' => 1, 'children' => []],
            ],
        ],
        'icon-text' => [
            'type' => 'row',
            'children' => [
                ['id' => uniqid('col_'), 'type' => 'column', 'width' => 1, 'children' => []],
                ['id' => uniqid('col_'), 'type' => 'column', 'width' => 3, 'children' => []],
            ],
        ],
        'document' => [
            'type' => 'row',
            'children' => [
                ['id' => uniqid('col_'), 'type' => 'column', 'width' => 1, 'children' => []],
                ['id' => uniqid('col_'), 'type' => 'column', 'width' => 2, 'children' => []],
                ['id' => uniqid('col_'), 'type' => 'column', 'width' => 1, 'children' => []],
            ],
        ],
    ];
}

/** Ganti addCardItem() lama dengan versi ini - parameter kedua sekarang
 *  nama preset ('stack' | 'icon-text' | 'document'), bukan blueprint. */
public function addCardItem(string $blockId, string $preset = 'stack')
{
    $presets = $this->cardLayoutPresets();
    $layout = $presets[$preset] ?? $presets['stack'];

    $newCard = [
        'id' => uniqid('card_'),
        'layout' => $layout,
        'container' => [
            'bg' => 'bg-white',
            'padding' => 'p-4',
            'border_width' => 'border-0',
            'border_style' => 'border-colid',
            'border_color' => 'border-gray-200',
            'radius' => 'rounded-[14px]',
            'shadow' => 'shadow-sm',
            'hover' => 'hover:-translate-y-1',
            'align_y' => 'items-start',
            'url' => ''
        ],
    ];


    if (!isset($this->content[$blockId]['data']['cards'])) {
        $this->content[$blockId]['data']['cards'] = [];
    }
    $this->content[$blockId]['data']['cards'][] = $newCard;
}

// ================= Kolom =================

public function addColumnToCard(string $blockId, int $cardIndex): void
{
    $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'][] = [
        'id' => uniqid('col_'),
        'type' => 'column',
        'width' => 1,
        'children' => [],
    ];
}

public function removeColumnFromCard(string $blockId, int $cardIndex, string $columnId): void
{
    $children = $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'] ?? [];
    $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'] =
        array_values(array_filter($children, fn ($col) => $col['id'] !== $columnId));
}

// public function updateColumnWidth(string $blockId, int $cardIndex, string $columnId, int $width): void
public function updateColumnWidth(string $blockId, int $cardIndex, string $columnId, string $width): void
{
    $children = $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'] ?? [];
    foreach ($children as $i => $col) {
        if ($col['id'] === $columnId) {
            // $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'][$i]['width'] = max(1, $width);
            $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'][$i]['width'] = $width === 'auto' ? 'auto' : max(1, (int)$width);
            break;
        }
    }
}

// ================= Elemen di dalam kolom =================
// Catatan: default 'data' di bawah ini MINIMAL dengan sengaja - saya belum
// melihat seluruh field yang dipakai editor elemen teks/ikon Anda saat ini.
// Sesuaikan array 'data' di bawah supaya field-nya cocok dengan yang sudah
// dibaca form edit elemen yang ada, jangan dibiarkan berbeda.

public function addElementToColumn(string $blockId, int $cardIndex, string $columnId, string $elementType): void
{
    $children = $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'] ?? [];
    foreach ($children as $i => $col) {
        if ($col['id'] === $columnId) {
            $newElement = [
                'id' => uniqid('el_'),
                'type' => 'element',
                'elementType' => $elementType,
                'data' => $elementType === 'text'
                    ? [
                        'content' => ['id' => '', 'en' => ''],
                        'style' => [
                            'is_pill' => false,
                            'font' => 'font-sans',
                            'size' => 'text-[15px]',
                            'weight' => 'font-normal',
                            'pill_bg' => 'bg-goldy-soft',
                            'pill_radius' => 'rounded-md',
                            'color' => 'text-ink-soft',
                            'margin' => 'mb-2',
                        ],
                    ]
                    : [
                        'content' => ['icon' => ''],
                        'style' => [
                            'bg' => 'bg-mist',
                            'color' => 'text-foresty',
                            'size' => 'w-10 h-10',
                            'radius' => 'rounded-[14px]',
                        ],
                    ],
            ];
            $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'][$i]['children'][] = $newElement;
            break;
        }
    }
}

public function removeElementFromColumn(string $blockId, int $cardIndex, string $columnId, string $elementId): void
{
    $children = $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'] ?? [];
    foreach ($children as $i => $col) {
        if ($col['id'] === $columnId) {
            $this->content[$blockId]['data']['cards'][$cardIndex]['layout']['children'][$i]['children'] =
                array_values(array_filter($col['children'] ?? [], fn ($el) => $el['id'] !== $elementId));
            break;
        }
    }
}

// ================= Migrasi format lama =================

/** Panggil ini sekali (mis. lewat perintah artisan, loop semua Post yang
 *  punya blok card-builder) untuk konversi kartu blueprint+slots lama ke
 *  format layout row/column/element baru. */
protected function migrateCardToLayoutModel(array $card): array
{
    if (isset($card['layout'])) {
        return $card; // sudah format baru, lewati
    }

    $blueprint = $card['blueprint'] ?? 'stack';
    $slots = $card['slots'] ?? [];

    if ($blueprint === 'stack') {
        $columns = [
            [
                'id' => uniqid('col_'),
                'type' => 'column',
                'width' => 1,
                'children' => $this->migrateElementsToNodes($slots['main'] ?? []),
            ],
        ];
    } else {
        $columns = [
            ['id' => uniqid('col_'), 'type' => 'column', 'width' => 1, 'children' => $this->migrateElementsToNodes($slots['left'] ?? [])],
            ['id' => uniqid('col_'), 'type' => 'column', 'width' => 2, 'children' => $this->migrateElementsToNodes($slots['middle'] ?? [])],
            ['id' => uniqid('col_'), 'type' => 'column', 'width' => 1, 'children' => $this->migrateElementsToNodes($slots['right'] ?? [])],
        ];
    }

    $card['layout'] = ['type' => 'row', 'children' => $columns];
    unset($card['blueprint'], $card['slots']);

    return $card;
}

protected function migrateElementsToNodes(array $elements): array
{
    return array_map(function ($el) {
        return [
            'id' => uniqid('el_'),
            'type' => 'element',
            'elementType' => $el['type'] ?? 'text',
            'data' => $el, // nilai lama disimpan APA ADANYA, tidak ada field yang dibuang
        ];
    }, $elements);
}
  // End of Claude implementation
}
