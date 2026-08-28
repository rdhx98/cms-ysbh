@props([
    'blockId',
    'code',
    'block',
    'allContent'
])

@php
    $leftZone = $block['data']['left_zone'] ?? [];
    $rightZone = $block['data']['right_zone'] ?? [];
@endphp

{{-- 🌟 STATE ALPINE DENGAN PENDENGAR SINKRONISASI ANTI-GAGAL --}}
<div x-data="{
        activeTab: 'left',
        isCollapsed : false,
        updateZoneOrder(evt, zone) {
            let order = Array.from(evt.to.children).map(el => el.getAttribute('data-id')).filter(Boolean);
            $wire.reorderChildBlocks('{{ $blockId }}', zone, order);
        }
    }"
    {{-- Nama sinyal dicetak huruf kecil murni oleh server (PHP) --}}
    @toggle-collapse-all.window="isCollapsed = $event.detail"
    @sync-columns-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail"
    class="is-nested-container bg-white border border-gray-300 rounded-xl shadow-sm relative">

    {{-- HEADER & PENGATURAN BLOK --}}
    <div class="bg-gray-50 border-b border-gray-200" :class="isCollapsed ? 'rounded-b-xl' : ''">

        <div class="px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                
                {{-- 🌟 3. Tombol Buka/Tutup Lokal --}}
                <button type="button" @click="isCollapsed = !isCollapsed" 
                        class="p-1 hover:bg-gray-200 rounded text-gray-500 transition-colors focus:outline-none"
                        title="Tutup/Buka Blok Ini">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="isCollapsed ? '-rotate-90' : 'rotate-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <span class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest flex items-center gap-1.5 cursor-pointer select-none" @click="isCollapsed = !isCollapsed">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                    Seksi: 2 Kolom
                </span>
            </div>

            <span class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                Seksi: 2 Kolom
            </span>

            <div class="flex items-center gap-1">
                {{-- PEMILIH WARNA LATAR --}}
                <div class="flex items-center gap-2" x-data="{ openColorMenu: false, bgColor: @entangle('content.'.$blockId.'.data.bg_color') }">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Warna Latar:</label>
                    <div class="relative flex items-center border-l border-zinc-200 pl-2 ml-1">
                        <button type="button" @click="openColorMenu = !openColorMenu" class="flex items-center gap-2 p-1.5 h-9 transition rounded cursor-pointer hover:bg-zinc-200 text-gray-700 bg-zinc-50 shadow-sm border border-transparent">
                            <x-dynamic-component component="lucide-palette" class="h-4 w-4" stroke-width="2.5" />
                            <div class="w-6 h-6 rounded border border-zinc-300 shadow-inner transition-colors" :style="bgColor && bgColor !== 'transparent' ? { backgroundColor: bgColor } : { backgroundColor: '#ffffff', backgroundImage: 'repeating-linear-gradient(45deg, #e5e7eb 25%, transparent 25%, transparent 75%, #e5e7eb 75%, #e5e7eb)', backgroundPosition: '0 0, 4px 4px', backgroundSize: '8px 8px' }"></div>
                        </button>
                        <div x-show="openColorMenu" @click.away="openColorMenu = false" style="display: none;" x-cloak class="absolute top-full right-0 mt-1 bg-white border border-zinc-200 shadow-lg rounded-xl p-3 z-[99] flex flex-col gap-3 w-48">
                            <div>
                                <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-2 block">Warna Brand</span>
                                <div class="flex gap-2">
                                    <button type="button" @click="bgColor = '#000000'; openColorMenu = false" class="w-6 h-6 rounded-full bg-[#000000] shadow-lg"></button>
                                    <button type="button" @click="bgColor = '#FFFFFF'; openColorMenu = false" class="w-6 h-6 border border-gray-200 rounded-full bg-[#FFFFFF] shadow-lg"></button>
                                    <button type="button" @click="bgColor = '#e9f1eb'; openColorMenu = false" class="w-6 h-6 rounded-full bg-[#e9f1eb] shadow-lg"></button>
                                    <button type="button" @click="bgColor = '#064f3b'; openColorMenu = false" class="w-6 h-6 rounded-full bg-[#064f3b] shadow-lg"></button>
                                    <button type="button" @click="bgColor = '#fbf7ea'; openColorMenu = false" class="w-6 h-6 rounded-full bg-[#fbf7ea] shadow-lg"></button>
                                    <button type="button" @click="bgColor = '#e42326'; openColorMenu = false" class="w-6 h-6 rounded-full bg-[#e42326] shadow-lg"></button>
                                </div>
                            </div>
                            <hr class="border-zinc-100">
                            <div>
                                <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-2 block">Warna Bebas</span>
                                <input type="color" x-model="bgColor" class="w-full h-8 p-0 border-0 rounded cursor-pointer bg-transparent">
                            </div>
                            <hr class="border-zinc-100">
                            <button type="button" @click="bgColor = 'transparent'; openColorMenu = false" class="flex items-center gap-2 px-2 py-2 -mx-1 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">
                                <x-dynamic-component component="lucide-eraser" class="h-4 w-4" stroke-width="2.5" />
                                <span>Transparan</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- KONTROL URUTAN HP --}}
                <div class="flex items-center gap-2 border-l border-gray-200 pl-3 ml-2">
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Urutan HP:</span>
                        <div class="relative group/tooltip flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-gray-400 cursor-help hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="absolute bottom-full right-0 md:left-1/2 md:-translate-x-1/2 mb-2 w-48 opacity-0 invisible group-hover/tooltip:opacity-100 group-hover/tooltip:visible transition-all duration-200 z-[100] pointer-events-none">
                                <div class="bg-gray-800 text-white text-[10px] leading-relaxed p-2.5 rounded-lg shadow-xl text-center relative">
                                    Mengatur susunan saat dibaca di HP.
                                    <div class="mt-1.5 pt-1.5 border-t border-gray-600 text-left space-y-1">
                                        <p><strong class="text-blue-300">Kiri di Atas:</strong> Kolom kiri tampil lebih dulu.</p>
                                        <p><strong class="text-blue-300">Kanan di Atas:</strong> Kolom kanan naik ke atas.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" wire:click="$toggle('content.{{ $blockId }}.data.mobile_reverse')" class="flex items-center gap-1.5 px-2 py-1.5 bg-white border border-gray-200 rounded-md text-[10px] font-bold shadow-sm hover:bg-gray-50 focus:outline-none">
                        @if($block['data']['mobile_reverse'] ?? false)
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                            <span class="text-blue-600">Kanan di Atas</span>
                        @else
                            <svg class="w-3.5 h-3.5 text-foresty" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            <span class="text-gray-600">Kiri di Atas</span>
                        @endif
                    </button>
                </div>
            </div>
        </div>

        {{-- 🌟 NAVIGASI TAB --}}
        <div x-show="layoutMode === 'split'" x-cloak class="flex px-4 space-x-2 relative top-[1px]">
            
            <button @click="activeTab = 'left'; $dispatch('sync-columns-tab-{{ strtolower($blockId) }}', 'left')"
                    type="button"
                    :class="activeTab === 'left' ? 'bg-white text-blue-600 border-gray-200 shadow-sm' : 'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-200/50 border-transparent'"
                    class="px-5 py-2 text-xs font-bold transition-all border-t border-l border-r rounded-t-lg flex items-center gap-2">
                Kolom Kiri
                <span class="bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded text-[9px]">{{ count($leftZone) }}</span>
            </button>

            <button @click="activeTab = 'right'; $dispatch('sync-columns-tab-{{ strtolower($blockId) }}', 'right')"
                    type="button"
                    :class="activeTab === 'right' ? 'bg-white text-blue-600 border-gray-200 shadow-sm' : 'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-200/50 border-transparent'"
                    class="px-5 py-2 text-xs font-bold transition-all border-t border-l border-r rounded-t-lg flex items-center gap-2">
                Kolom Kanan
                <span class="bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded text-[9px]">{{ count($rightZone) }}</span>
            </button>
            
        </div>

    </div>

    {{-- AREA KONTEN --}}
    <div x-show="!isCollapsed" x-collapse x-cloak :class="layoutMode === 'single' ? 'grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200' : 'block'" class="bg-white">

        {{-- ================= KONTEN KIRI ================= --}}
        <div x-show="layoutMode === 'single' || activeTab === 'left'" class="flex flex-col h-full">
            
            <div x-show="layoutMode === 'single'" x-cloak class="px-4 py-2 bg-gray-100/50 border-b border-gray-200">
                <span class="text-[10px] font-bold text-gray-400 uppercase">Kolom Kiri</span>
            </div>

            <div x-sort
                 x-sort:config="{
                     group: 'left_zone_{{ $blockId }}',
                     animation: 150,
                     handle: '.child-drag-handle',
                     onEnd: (evt) => updateZoneOrder(evt, 'left_zone')
                 }"
                 class="flex-1 p-4 space-y-4 min-h-[150px] bg-gray-50/20"
            >
                @foreach($leftZone as $childId)
                    @if(isset($allContent[$childId]))
                        @php $childBlock = $allContent[$childId]; @endphp
                        <div data-id="{{ $childId }}" x-sort:item="'{{ $childId }}'" wire:key="child-{{ $childId }}" class="relative group rounded-lg hover:ring-2 hover:ring-blue-100 transition-all bg-white shadow-sm border border-gray-200">
                            
                            <!-- Drag Handle -->
                            <div class="child-drag-handle absolute -left-2 top-3 opacity-0 group-hover:opacity-100 cursor-move text-gray-300 hover:text-blue-500 z-10 bg-white rounded-full p-0.5 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"></path></svg>
                            </div>

                            <!-- 🌟 TOMBOL HAPUS BLOK ANAK (MUNCUL SAAT HOVER) -->
                            <div class="absolute -top-2.5 -right-2.5 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                                <button type="button" 
                                        wire:click="removeNestedBlock('{{ $blockId }}', 'left_zone', '{{ $childId }}')" 
                                        class="p-1 bg-red-100 text-red-600 rounded-full hover:bg-red-200 border border-red-200 shadow-sm" title="Hapus Blok Ini">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <div class="p-0">
                                <x-dynamic-component :component="'blocks.editor.' . str_replace('_', '-', $childBlock['type'])" :block-id="$childId" :code="$code" :block="$childBlock" :all-content="$allContent" />
                            </div>
                        </div>
                    @endif
                @endforeach
                {{-- Below is BAK code --}}
                {{-- @foreach($leftZone as $childId)
                    @if(isset($allContent[$childId]))
                        @php $childBlock = $allContent[$childId]; @endphp
                        <div data-id="{{ $childId }}" x-sort:item="'{{ $childId }}'" wire:key="child-{{ $childId }}" class="relative group rounded-lg hover:ring-2 hover:ring-blue-100 transition-all bg-white shadow-sm border border-gray-200">
                            <div class="child-drag-handle absolute -left-2 top-3 opacity-0 group-hover:opacity-100 cursor-move text-gray-300 hover:text-blue-500 z-10 bg-white rounded-full p-0.5 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"></path></svg>
                            </div>
                            <div class="p-0">
                                <x-dynamic-component :component="'blocks.editor.' . str_replace('_', '-', $childBlock['type'])" :block-id="$childId" :code="$code" :block="$childBlock" :all-content="$allContent" />
                            </div>
                        </div>
                    @endif
                @endforeach --}}
            </div>

            <div class="p-4 pt-0 mt-auto">
                <div x-data="{ openDropdown: false }" class="relative">
                    <button @click="openDropdown = !openDropdown" @click.outside="openDropdown = false" type="button" class="w-full py-2 flex items-center justify-center gap-2 border border-dashed border-gray-300 text-xs font-bold text-gray-400 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 rounded-lg transition-colors">
                        + Tambah Konten Kiri
                    </button>
                    <div x-show="openDropdown" x-cloak class="absolute z-50 bottom-full mb-1 left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden">
                        <div class="p-1 flex flex-col gap-0.5">
                            <button type="button" wire:click="addChildBlock('{{ $blockId }}', 'left_zone', 'heading'); openDropdown = false" class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Judul (Heading)</button>
                            <button type="button" wire:click="addChildBlock('{{ $blockId }}', 'left_zone', 'paragraph'); openDropdown = false" class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Paragraf</button>
                            <button type="button" wire:click="addChildBlock('{{ $blockId }}', 'left_zone', 'image'); openDropdown = false" class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Gambar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= KONTEN KANAN ================= --}}
        <div x-show="layoutMode === 'single' || activeTab === 'right'" class="flex flex-col h-full" style="display: none;">
            
            <div x-show="layoutMode === 'single'" x-cloak class="px-4 py-2 bg-gray-100/50 border-b border-gray-200">
                <span class="text-[10px] font-bold text-gray-400 uppercase">Kolom Kanan</span>
            </div>

            <div x-sort
                 x-sort:config="{
                     group: 'right_zone_{{ $blockId }}',
                     animation: 150,
                     handle: '.child-drag-handle',
                     onEnd: (evt) => updateZoneOrder(evt, 'right_zone')
                 }"
                 class="flex-1 p-4 space-y-4 min-h-[150px] bg-gray-50/20"
            >

                @foreach($rightZone as $childId)
                    @if(isset($allContent[$childId]))
                        @php $childBlock = $allContent[$childId]; @endphp
                        <div data-id="{{ $childId }}" x-sort:item="'{{ $childId }}'" wire:key="child-{{ $childId }}" class="relative group rounded-lg hover:ring-2 hover:ring-blue-100 transition-all bg-white shadow-sm border border-gray-200">
                            
                            <!-- Drag Handle -->
                            <div class="child-drag-handle absolute -left-2 top-3 opacity-0 group-hover:opacity-100 cursor-move text-gray-300 hover:text-blue-500 z-10 bg-white rounded-full p-0.5 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"></path></svg>
                            </div>

                            <!-- 🌟 TOMBOL HAPUS BLOK ANAK (MUNCUL SAAT HOVER) -->
                            <div class="absolute -top-2.5 -right-2.5 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                                <button type="button" 
                                        wire:click="removeNestedBlock('{{ $blockId }}', 'left_zone', '{{ $childId }}')" 
                                        class="p-1 bg-red-100 text-red-600 rounded-full hover:bg-red-200 border border-red-200 shadow-sm" title="Hapus Blok Ini">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <div class="p-0">
                                <x-dynamic-component :component="'blocks.editor.' . str_replace('_', '-', $childBlock['type'])" :block-id="$childId" :code="$code" :block="$childBlock" :all-content="$allContent" />
                            </div>
                        </div>
                    @endif
                @endforeach
                {{-- Below is BAK code --}}
                {{-- @foreach($rightZone as $childId)
                    @if(isset($allContent[$childId]))
                        @php $childBlock = $allContent[$childId]; @endphp
                        <div data-id="{{ $childId }}" x-sort:item="'{{ $childId }}'" wire:key="child-{{ $childId }}" class="relative group rounded-lg hover:ring-2 hover:ring-blue-100 transition-all bg-white shadow-sm border border-gray-200">
                            <div class="child-drag-handle absolute -left-2 top-3 opacity-0 group-hover:opacity-100 cursor-move text-gray-300 hover:text-blue-500 z-10 bg-white rounded-full p-0.5 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"></path></svg>
                            </div>
                            <div class="p-0">
                                <x-dynamic-component :component="'blocks.editor.' . str_replace('_', '-', $childBlock['type'])" :block-id="$childId" :code="$code" :block="$childBlock" :all-content="$allContent" />
                            </div>
                        </div>
                    @endif
                @endforeach --}}
            </div>

            <div class="p-4 pt-0 mt-auto">
                <div x-data="{ openDropdown: false }" class="relative">
                    <button @click="openDropdown = !openDropdown" @click.outside="openDropdown = false" type="button" class="w-full py-2 flex items-center justify-center gap-2 border border-dashed border-gray-300 text-xs font-bold text-gray-400 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 rounded-lg transition-colors">
                        + Tambah Konten Kanan
                    </button>
                    <div x-show="openDropdown" x-cloak class="absolute z-50 bottom-full mb-1 left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden">
                        <div class="p-1 flex flex-col gap-0.5">
                            <button type="button" wire:click="addChildBlock('{{ $blockId }}', 'right_zone', 'heading'); openDropdown = false" class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Judul (Heading)</button>
                            <button type="button" wire:click="addChildBlock('{{ $blockId }}', 'right_zone', 'paragraph'); openDropdown = false" class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Paragraf</button>
                            <button type="button" wire:click="addChildBlock('{{ $blockId }}', 'right_zone', 'image'); openDropdown = false" class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Gambar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>