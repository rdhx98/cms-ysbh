@props([
    'data' => [], // Menerima :data="$block['data']" dari parent
    'lang' => app()->getLocale() // Menerima :lang="$lang" dari parent
])

@php
    // Ambil teks sesuai bahasa ($lang)
    // Karena dari induk sudah dikirim $block['data'], kita cukup panggil $data
    $text = $data['text'][$lang] ?? ''; 
    
    // Ikon dan Warna bersifat Global
    $icon = $data['icon'] ?? 'newspaper'; 
    $color = $data['color'] ?? '#e05a47'; 
@endphp

@if($text)
    <div class="mb-4">
        <span class="inline-flex items-center gap-2.5 font-['Instrument_Sans',sans-serif] text-[11px] md:text-[13px] font-bold tracking-[0.16em] uppercase" 
              style="color: {{ $color }};">
            <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 shrink-0" stroke-width="2" />
            {{ $text }}
        </span>
    </div>
@endif