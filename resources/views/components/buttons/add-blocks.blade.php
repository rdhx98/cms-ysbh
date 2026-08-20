@props([
    'command', 
    'icon' => null,
    'label' => null,
    'mode' => 'icon-label', // Pilihan: 'icon-only', 'icon-label', 'icon-hover'
])

<button type="button" 
    @click="{{ $command }}"
    {{ $attributes->merge([
        // Padding disesuaikan (px-2.5) agar terlihat seimbang baik saat ada teks maupun hanya ikon
        'class' => 'group p-1.5 px-2.5 min-h-[2.25rem] h-9 transition-all duration-300 ease-out rounded-lg flex items-center justify-center text-sm font-medium cursor-pointer hover:bg-sage-soft hover:text-foresty bg-white border border-gray-300 text-gray-700 hover:shadow-sm overflow-hidden select-none',
    ]) }}>

    {{-- Efek Pop/Membesar saat di-hover --}}
    @if ($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="h-4 w-4 shrink-0 transition-transform duration-300 ease-out group-hover:scale-110" stroke-width="2.5" />
    @endif

    @if ($label)
        @if ($mode === 'icon-only')
            {{-- Mode 1: Hanya Ikon (Teks disembunyikan untuk pembaca layar) --}}
            <span class="sr-only">{{ $label }}</span>

        @elseif ($mode === 'icon-hover')
            {{-- Mode 2: Teks tersembunyi, lalu meluncur keluar saat di-hover --}}
            <span class="overflow-hidden whitespace-nowrap opacity-0 max-w-0 -translate-x-3 transition-all duration-300 ease-out group-hover:translate-x-0 group-hover:max-w-[150px] group-hover:opacity-100 group-hover:ml-2">
                {{ $label }}
            </span>

        @else
            {{-- Mode 3 (Default): Ikon dan Teks tampil bersamaan --}}
            <span class="ml-2 whitespace-nowrap">{{ $label }}</span>
        @endif
    @endif

    {{ $slot }}
</button>