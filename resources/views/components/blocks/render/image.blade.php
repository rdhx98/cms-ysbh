@props([
    'data',
    'lang',
    'allContent' => []
])

@php
    $imageUrl = $data['url'] ?? '';
    $caption = $data['caption'][$lang] ?? '';
    
    // Ambil kelas padding yang dipilih, atau kosongkan jika menggunakan default
    $paddingTop = $data['padding_top'] ?? '';
    $paddingBottom = $data['padding_bottom'] ?? '';
@endphp

@if(!empty($imageUrl))
    <figure class="w-full {{ $paddingTop }} {{ $paddingBottom }}">
        <img src="{{ $imageUrl }}" alt="{{ $caption ?: 'Gambar' }}" class="w-full h-auto rounded-lg shadow-sm">
        
        @if(!empty($caption))
            <figcaption class="mt-3 text-center text-sm text-gray-500 italic">
                {{ $caption }}
            </figcaption>
        @endif
    </figure>
@endif

{{-- @if(!empty($data['url']))
    <div class="w-full my-6 flex flex-col justify-center">
        <img src="{{ $data['url'] }}" 
             alt="Visual konten" 
             loading="lazy" 
             class="w-full h-auto rounded-xl shadow-md border border-gray-100 object-cover" 
        />
    </div>
@endif --}}