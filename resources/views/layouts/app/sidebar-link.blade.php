@props([
    'route' => '#',       
    'active' => false,    
    'icon' => null,       
    'activeIcon' => null, 
    'iconSize' => '4',    
])

@php
    // Cek apakah admin memberikan 2 ikon yang berbeda
    $hasDistinctActiveIcon = $activeIcon && $activeIcon !== $icon;
@endphp

<a href="{{ $route }}"
   wire:navigate
   {{ $attributes->merge([
       'class' => 'flex items-center rounded-xl group whitespace-nowrap transition-all duration-300 ease-in-out h-9 ' .
        ($active
            ? 'bg-forest text-white font-semibold shadow-sm '
            : 'text-forest hover:bg-forest/80 hover:text-white ')
   ]) }}
   :class="isExpanded ? 'w-full' : 'w-9'"
>
    <!-- 🌟 KOTAK IKON (Relatif agar ikon di dalamnya bisa absolute/bertumpuk) -->
    <div class="relative flex items-center justify-center w-9 h-9 shrink-0 transition-transform duration-300 group-hover:scale-110 {{ $active ? 'text-aurum' : 'text-forest group-hover:text-aurum' }}">
        
        @if($icon)
            @if($hasDistinctActiveIcon)
                <!-- 1. IKON BAWAAN (Akan mengecil & memudar saat aktif) -->
                <x-dynamic-component
                    :component="'lucide-' . $icon"
                    class="absolute h-{{ $iconSize }} w-{{ $iconSize }} transition-all duration-300 ease-in-out {{ $active ? 'opacity-0 scale-50 -rotate-12' : 'opacity-100 scale-100 rotate-0' }}"
                    stroke-width="2"
                />
                
                <!-- 2. IKON AKTIF (Akan membesar & muncul saat aktif) -->
                <x-dynamic-component
                    :component="'lucide-' . $activeIcon"
                    class="absolute h-{{ $iconSize }} w-{{ $iconSize }} transition-all duration-300 ease-in-out {{ $active ? 'opacity-100 scale-100 rotate-0' : 'opacity-0 scale-50 rotate-12' }}"
                    stroke-width="2.5"
                />
            @else
                <!-- 3. JIKA HANYA 1 IKON (Gunakan animasi biasa tanpa tumpuk) -->
                <x-dynamic-component
                    :component="'lucide-' . $icon"
                    class="h-{{ $iconSize }} w-{{ $iconSize }} transition-all duration-300 ease-in-out"
                    stroke-width="{{ $active ? '2.5' : '2' }}"
                />
            @endif
        @else
            {{ $iconSlot ?? '' }}
        @endif
    </div>

    <!-- Teks Link -->
    <span x-show="isExpanded"
          x-transition:enter="transition ease-out duration-200 delay-150"
          x-transition:enter-start="opacity-0 translate-x-[-10px]"
          x-transition:enter-end="opacity-100 translate-x-0"
          x-transition:leave="transition ease-in duration-100"
          x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0"
          class="text-xs md:text-sm font-medium tracking-wide">
        {{ $title ?? $slot }}
    </span>
</a>