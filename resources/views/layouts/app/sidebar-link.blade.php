@props([
    'route' => '#',       // URL atau nama route tujuan
    'active' => false,    // Menentukan apakah rute ini sedang aktif
    'icon' => null        // Menerima nama icon atau dibiarkan kosong jika pakai slot
])

<a href="{{ $route }}"
   wire:navigate
   {{ $attributes->merge([
       'class' => 'flex items-center rounded-xl group  whitespace-nowrap transition-all duration-300 easew-full-in-out h-9 ' .
        ($active
            ? 'bg-forest text-white font-semibold shadow-sm '
            : 'text-forest hover:bg-forest/80 hover:text-white ')
   ]) }}
   :class="isExpanded ? 'w-full' : 'w-9'"
>
    <div class="flex items-center justify-center w-9 h-9 shrink-0 transition-transform duration-200 group-hover:scale-105 {{ $active ? 'text-aurum' : 'text-forest group-hover:text-aurum' }}">
        @if($icon)
            <x-dynamic-component
                :component="'lucide-' . $icon"
                class="h-4 w-4"
                stroke-width="2"
            />
        @else
            {{ $iconSlot ?? '' }}
        @endif
    </div>

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

