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
            ['name' => 'Dashed', 'value' => 'border-dashed'],
            ['name' => 'Dotted', 'value' => 'border-dotted'],
            // ['name' => 'Double', 'value' => 'border-double'],
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
            ['name' => 'Siku', 'preview'=>'rounded-tl-none','value' => 'rounded-none'],
            ['name' => 'Agak Bulat', 'preview'=>'rounded-tl-md','value' => 'rounded-[14px]'],
            ['name' => 'Sangat Bulat', 'preview'=>'rounded-tl-[14px]','value' => 'rounded-[28px]'],
            // ['name' => 'Agak Bulat', 'preview'=>'rounded-','value' => 'rounded-md'],
            // ['name' => 'Sangat Bulat', 'preview'=>'rounded-','value' => 'rounded-lg'],
        ],

        // 4. Perataan Vertikal (Vertical Alignment / Flex Items)
        'alignments' => [
            ['name' => 'Posisi Atas', 'value' => 'items-start'],
            ['name' => 'Posisi Tengah', 'value' => 'items-center'],
            ['name' => 'Posisi Bawah', 'value' => 'items-end'],
            ['name' => 'Sama Tinggi', 'value' => 'items-stretch'],
        ],

        // 5. Ruang Dalam (Padding)
        'card_paddings' => [
          ['name' => 'Kecil', 'preview'=>'p-0.5', 'value' => 'p-2 md:p-4'],
          ['name' => 'Sedang', 'preview'=>'p-0.75', 'value' => 'p-4 md:p-6'],
            ['name' => 'Besar', 'preview'=>'p-1', 'value' => 'p-6 md:p-8'],
        ],
        'card_bg_colors'=> [
          ["name" => "White", "value" => "bg-white"],
          ["name" => "Forest", "value" => "bg-forest"],
          ["name" => "Mist", "value" => "bg-mist"],
          ["name" => "Coral", "value" => "bg-coral"],
          ["name" => "Aurum", "value" => "bg-aurum"],
          //  ["name" => "Charcoal", "value" => "bg-charcoal"],
          //  ["name" => "Paper", "value" => "bg-paper"],
          // ["name" => "Amber", "value" => "bg-amber"],
        ],
        'card_border_colors'=> [
          ["name" => "Transparan", "value" => "border-transparent", 'preview'=> 'bg-transparent'],
          ["name" => "White", "value" => "border-white", 'preview'=> 'bg-white'],
          ["name" => "Forest", "value" => "border-forest", 'preview'=> 'bg-forest'],
          ["name" => "Mist", "value" => "border-mist", 'preview'=> 'bg-mist'],
          ["name" => "Coral", "value" => "border-coral", 'preview'=> 'bg-coral'],
          ["name" => "Aurum", "value" => "border-aurum", 'preview'=> 'bg-aurum'],
          //  ["name" => "Charcoal", "value" => "border-charcoal", 'preview'=> 'bg-charcoal'],
          //  ["name" => "Paper", "value" => "border-paper", 'preview'=> 'bg-paper'],
          // ["name" => "Amber", "value" => "border-amber", 'preview'=> 'bg-amber'],
        ],
        'paddings' => [
          ['name' => 'Nol', 'value' => 'p-0'],
            ['name' => 'Kecil', 'value' => 'p-4'],
            ['name' => 'Besar', 'value' => 'p-6 md:p-8'],
        ],
        // 'margin_bottom' => [
        //     ['name' => 'zero', 'value' => 'mb-0'],
        //     ['name' => 'small', 'value' => 'mb-4'],
        //     ['name' => 'normal', 'value' => 'mb-8'],
        //     ['name' => 'wide', 'value' => 'mb-16'],
        //     ['name' => 'full', 'value' => 'mb-24'],
        // ],
        'margin_bottom' => [
            ['value' => 'mb-0',           'label' => 'tight',   'icon_mb' => 'mb-0',  'title' => 'tight',   'preview' => '0px'],
            ['value' => 'mb-4 md:mb-6',   'label' => 'small',   'icon_mb' => 'mb-4',  'title' => 'Small',   'preview' => '2px'],
            ['value' => 'mb-8 md:mb-10',  'label' => 'normal',  'icon_mb' => 'mb-6',  'title' => 'normal',  'preview' => '4px'],
            ['value' => 'mb-12 md:mb-16', 'label' => 'wide',    'icon_mb' => 'mb-16', 'title' => 'wide',    'preview' => '6px'],
            ['value' => 'mb-16 md:mb-24', 'label' => 'full',    'icon_mb' => 'mb-24', 'title' => 'full',    'preview' => '8px'],
        ],
        'margin_bottom_BAK' => [
            ['value' => 'mb-0',  'label' => 'tight',  'icon_mb' => 'mb-0',      'title' => 'tight'],
            ['value' => 'mb-4',  'label' => 'small',  'icon_mb' => 'mb-4',  'title' => 'Small'],
            ['value' => 'mb-8',  'label' => 'normal', 'icon_mb' => 'mb-6',      'title' => 'normal'],
            ['value' => 'mb-16', 'label' => 'wide',  'icon_mb' => 'mb-16',    'title' => 'wide'],
            ['value' => 'mb-24', 'label' => 'full',  'icon_mb' => 'mb-24',      'title' => 'full'],
        ],
      //  'section_bg_colors'=> [
       'eyebrow_colors'=> [
         ["name" => "Forest", "value" => "#064f3b"],
         ["name" => "Charcoal", "value" => "#1f2937"],
         ["name" => "Amber", "value" => "#d97706"],
         ["name" => "Coral", "value" => "#E42326"],
         ["name" => "Aurum", "value" => "#E5C423"],
         // ["name" => "Coral Dark", "value" => "#e05a47"],
          // ["name" => "Mist", "value" => "#E9F1EB"],
          // ["name" => "Sage Muted", "value" => "#4b5d53"],
          // ["name" => "Sage Soft", "value" => "#D2E7DF"],
          // ["name" => "Ocean Blue", "value" => "#0369a1"],
          // ["name" => "Rose", "value" => "#e11d48"],
          // ["name" => "Gold", "value" => "#EBCC26"],
        ],
       'bg_colors'=> [
         ["name" => "Charcoal", "value" => "bg-charcoal"],
         ["name" => "White", "value" => "bg-white"],
         ["name" => "Forest", "value" => "bg-forest"],
         ["name" => "Mist", "value" => "bg-mist"],
         ["name" => "Paper", "value" => "bg-paper"],
         ["name" => "Amber", "value" => "bg-amber"],
         ["name" => "Coral", "value" => "bg-coral"],
         ["name" => "Aurum", "value" => "bg-aurum"],
        ],
    ],
    'lucide' => [
        'activity', 
        'circle', 
        'crosshair',
        'calendar-range',
        'gap-vertical',
        'newspaper', 
        'bookmark', 
        'sparkles', 
        'tag', 
        'folder', 
        'flag', 
        'globe', 
        'heart', 
        'heart-pulse', 
        'star', 
        'shield', 
        'award', 
        'bell', 'briefcase', 'calendar', 'check-circle', 'compass', 'cpu', 
        'file-text', 'filter', 'gift', 'home', 'info', 'layers', 'life-buoy', 'lightbulb', 'link', 'lock', 'map', 
        'megaphone', 'message-square', 'mic', 'moon', 'package', 'paperclip', 'pen-tool', 'pie-chart', 'play', 
        'power', 'radio', 'rss', 'search', 'send', 'settings', 'share-2', 'shield-check', 'shopping-bag', 'shopping-cart', 
        'sliders', 'smile', 'speaker', 'sun', 'target', 'terminal', 'thumbs-up', 'wrench', 'trash-2', 'trending-up', 
        'triangle', 'truck', 'tv', 'user', 'users', 'video', 'volume-2', 'watch', 'zap', 'box', 'download', 'arrow-right'
    ],
    'fonts' => [
      'font-arial'    => 'Arial',
      'font-fraunces' => 'Fraunces',
      'font-times'    => 'Times New Roman',
      'font-roboto'   => 'Roboto',
      'font-jetbrains'=> 'JetBrains Mono',
      'font-opensans' => 'Open Sans',
      'font-jakarta'  => 'Plus Jakarta Sans',
    ],
];