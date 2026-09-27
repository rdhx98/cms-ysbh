<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TALL Stack Card Builder Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini menyimpan seluruh daftar nilai Tailwind CSS untuk 
    | desain komponen. Tambahkan atau ubah kelas di sini jika Yayasan
    | meminta pembaruan warna atau gaya di masa mendatang.
    |
    */

    'design' => [
        // 1. Latar Belakang (Background)
        'backgrounds' => [
            [
                'name' => 'Putih',
                'value' => 'bg-white',
                'preview' => 'bg-white border border-gray-300',
            ],
            [
                'name' => 'Mist',
                'value' => 'bg-mist',
                'preview' => 'bg-gray-100 border border-gray-200',
            ],
            [
                'name' => 'Foresty',
                'value' => 'bg-foresty text-white',
                'preview' => 'bg-foresty',
            ],
            [
                'name' => 'Transparan',
                'value' => 'bg-transparent',
                'preview' => 'bg-white border border-gray-300',
                'is_transparent' => true,
            ],
        ],

        // 2A. Ketebalan Garis (Border Width)
        'border_widths' => [
            ['name' => 'Tanpa Garis', 'value' => 'border-0'],
            ['name' => 'Standar (1px)', 'value' => 'border'],
            ['name' => 'Tebal (2px)', 'value' => 'border-2'],
        ],

        // 2B. Tipe Garis (Border Style)
        'border_styles' => [
            ['name' => 'Solid (Lurus)', 'value' => 'border-solid'],
            ['name' => 'Dashed (Putus-putus)', 'value' => 'border-dashed'],
        ],

        // 2C. Warna Garis (Border Color)
        'border_colors' => [
            [
                'name' => 'Abu-abu',
                'value' => 'border-gray-200',
                'preview' => 'border-gray-200 bg-white',
            ],
            [
                'name' => 'Hijau Pudar',
                'value' => 'border-foresty/20',
                'preview' => 'border-foresty/20 bg-white',
            ],
            [
                'name' => 'Hijau Solid',
                'value' => 'border-foresty',
                'preview' => 'border-foresty bg-white',
            ],
            [
                'name' => 'Transparan',
                'value' => 'border-transparent',
                'preview' => 'border-gray-200 bg-gray-50',
                'is_transparent' => true,
            ],
        ],

        // 3. Sudut Kotak (Border Radius)
        'border_radiuses' => [
            ['name' => 'Siku (Tanpa Sudut)', 'value' => 'rounded-none'],
            ['name' => 'Agak Bulat', 'value' => 'rounded-[14px]'],
            ['name' => 'Sangat Bulat', 'value' => 'rounded-[28px]'],
        ],

        // 4. Perataan Vertikal (Vertical Alignment / Flex Items)
        'alignments' => [
            ['name' => 'Posisi Atas', 'value' => 'items-start'],
            ['name' => 'Posisi Tengah', 'value' => 'items-center'],
            ['name' => 'Posisi Bawah', 'value' => 'items-end'],
            ['name' => 'Sama Tinggi', 'value' => 'items-stretch'],
        ],

        // 5. Ruang Dalam (Padding)
        'paddings' => [
            ['name' => 'Kecil', 'value' => 'p-4'],
            ['name' => 'Besar', 'value' => 'p-6 md:p-8'],
            ['name' => 'Nol', 'value' => 'p-0'],
        ],
    ]
];