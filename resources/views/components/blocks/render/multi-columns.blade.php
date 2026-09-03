@props(['data', 'lang', 'allContent'])

@php
    // Konversi jumlah kolom dari Editor
    $colCount = (int)($data['col_count'] ?? 2);
    
    // Pemetaan responsif Tailwind untuk Grid
    $gridClass = match($colCount) {
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-2 lg:grid-cols-4',
        5 => 'md:grid-cols-3 lg:grid-cols-5',
        6 => 'md:grid-cols-3 lg:grid-cols-6',
        default => 'grid-cols-1 md:grid-cols-2'
    };
    
    $isReverseMobile = $data['mobile_reverse'] ?? false;
@endphp

<div class="grid grid-cols-1 gap-6 {{ $gridClass }} w-full my-6">
    
    @for($i = 1; $i <= $colCount; $i++)
        @php 
            // 🌟 Animasi Domino: Kolom 1=0ms, Kolom 2=150ms, Kolom 3=300ms, dst.
            $delay = ($i - 1) * 150; 
            $zoneKey = "col_{$i}_zone";

            // Logika Reverse Mobile (Hanya masuk akal digunakan jika col_count = 2)
            $orderClass = '';
            if ($isReverseMobile && $colCount === 2) {
                $orderClass = ($i === 1) ? 'order-2 md:order-1' : 'order-1 md:order-2';
            }
        @endphp

        {{-- Wadah Kolom dengan Animasi Reveal --}}
        <div class="flex flex-col gap-4 {{ $orderClass }} reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none delay-[{{ $delay }}ms]">
            
            @if(isset($data[$zoneKey]))
                @foreach($data[$zoneKey] as $childId)
                    @if(isset($allContent[$childId]))
                        @php 
                            $childBlock = $allContent[$childId];
                            $component = 'blocks.render.' . str_replace('_', '-', $childBlock['type']);
                        @endphp
                        
                        {{-- Render Mikro Blok (Judul, Teks, dll) --}}
                        <x-dynamic-component 
                            :component="$component" 
                            :data="$childBlock['data']" 
                            :lang="$lang" 
                            :all-content="$allContent" 
                        />
                    @endif
                @endforeach
            @endif

        </div>
    @endfor
</div>