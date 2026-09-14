@props([
    'model', // Wajib: Alamat properti Livewire, cth: 'content.blk_123.data.icon'
    'label' => 'Ikon Lucide' // Opsional
])

@php
    // Daftar ikon diekstrak secara terpusat di sini
    $iconsList = [
        'newspaper', 'bookmark', 'sparkles', 'tag', 'folder', 'flag', 'globe', 'heart', 'star', 'shield', 'award', 'bell', 'briefcase', 'calendar', 'check-circle', 'compass', 'cpu', 'file-text', 'filter', 'gift', 'home', 'info', 'layers', 'life-buoy', 'lightbulb', 'link', 'lock', 'map', 'megaphone', 'message-square', 'mic', 'moon', 'package', 'paperclip', 'pen-tool', 'pie-chart', 'play', 'power', 'radio', 'rss', 'search', 'send', 'settings', 'share-2', 'shield-check', 'shopping-bag', 'shopping-cart', 'sliders', 'smile', 'speaker', 'sun', 'target', 'terminal', 'thumbs-up', 'wrench', 'trash-2', 'trending-up', 'triangle', 'truck', 'tv', 'user', 'users', 'video', 'volume-2', 'watch', 'zap',
    ];
@endphp

<div class="flex flex-col gap-1.5"
     x-data="{
        openPicker: false,
        searchQuery: '',
        {{-- 🌟 Mengikat data langsung ke properti model yang dilempar dari luar --}}
        localIcon: $wire.entangle('{{ $model }}').live
     }">

    <label class="text-[10px] font-bold text-foresty uppercase">{{ $label }}</label>

    <div class="relative">
        {{-- Tombol Pemicu Picker --}}
        <button type="button" @click="openPicker = !openPicker"
            class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-md py-1.5 px-3 text-xs shadow-sm hover:border-foresty focus:outline-none transition-all duration-200">

            <div class="flex items-center gap-2 truncate">
                <span class="w-4 h-4 shrink-0 flex items-center justify-center text-foresty">
                    {{-- Render Ikon Terpilih --}}
                    @foreach ($iconsList as $icon)
                        <span x-show="localIcon === '{{ $icon }}'" x-cloak style="display: none;">
                            <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4" stroke-width="2.5" />
                        </span>
                    @endforeach
                    {{-- Jika Kosong (Fallback) --}}
                    <span x-show="!localIcon" x-cloak>
                        <x-dynamic-component component="lucide-check-circle" class="w-4 h-4 text-gray-400" stroke-width="2.5" />
                    </span>
                </span>
                <span class="truncate uppercase font-mono text-[10px] text-gray-600" x-text="localIcon || 'Pilih Ikon'"></span>
            </div>

            <x-dynamic-component component="lucide-chevron-down" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
        </button>

        {{-- Pop-up Daftar Ikon --}}
        <div x-show="openPicker" @click.outside="openPicker = false" x-cloak style="display: none;"
             class="absolute left-0 mt-1 w-56 bg-white border border-gray-200 rounded-xl shadow-xl p-2.5 z-50 flex flex-col gap-2">

            {{-- Input Pencarian --}}
            <div class="relative">
                <x-dynamic-component component="lucide-search" class="w-3.5 h-3.5 absolute left-2.5 top-2 text-gray-400" />
                <input type="text" x-model="searchQuery" placeholder="Cari ikon..."
                       class="w-full text-xs border border-gray-200 rounded-lg pl-8 pr-2 py-1.5 focus:ring-foresty focus:border-foresty shadow-sm transition-colors" />
            </div>

            {{-- Grid Ikon --}}
            <div class="grid grid-cols-5 gap-1.5 max-h-48 overflow-y-auto p-1 scrollbar-thin">
                @foreach ($iconsList as $icon)
                    <button type="button"
                        x-show="'{{ $icon }}'.includes(searchQuery.toLowerCase())"
                        @click="localIcon = '{{ $icon }}'; openPicker = false; searchQuery = ''"
                        class="p-2 rounded-lg flex items-center justify-center transition-all duration-200"
                        :class="localIcon === '{{ $icon }}' ? 'bg-sage-soft text-foresty border border-foresty shadow-sm scale-105' : 'bg-gray-50 text-gray-500 border border-transparent hover:border-foresty/50 hover:text-foresty'"
                        title="{{ $icon }}">
                        <span class="w-4 h-4 flex items-center justify-center">
                            <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 shrink-0" stroke-width="2" />
                        </span>
                    </button>
                @endforeach
            </div>

        </div>
    </div>
</div>
