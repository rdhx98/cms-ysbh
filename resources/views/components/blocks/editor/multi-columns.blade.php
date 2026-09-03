@props([
    'blockId',
    'code',
    'block',
    'allContent'
])

@php
    $colCount = (int)($block['data']['col_count'] ?? 2);
    
    // Kumpulkan semua ID anak dari zona yang aktif untuk fitur "Runtuhkan Anak"
    $allChildren = [];
    for($i = 1; $i <= $colCount; $i++) {
        $allChildren = array_merge($allChildren, $block['data']["col_{$i}_zone"] ?? []);
    }

    // Kelas grid untuk editor saat mode layoutMode === 'single' (semua kolom dijejerkan)
    $editorGridClass = match($colCount) {
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
        5 => 'md:grid-cols-5',
        6 => 'md:grid-cols-6',
        default => 'md:grid-cols-2'
    };
@endphp

{{-- 🌟 STATE ALPINE LENGKAP (Termasuk layoutMode & Sinkronisasi) --}}
<div 
    x-data="{
        activeTab: 'col_1_zone',
        isCollapsed : false,
        layoutMode: 'split', // Memastikan tampilan tab (split) berfungsi default
        init() {
            window.blockCollapseState = window.blockCollapseState || {};
            if (window.blockCollapseState['{{ $blockId }}'] !== undefined) {
                this.isCollapsed = window.blockCollapseState['{{ $blockId }}'];
            }
            this.$watch('isCollapsed', (value) => {
                window.blockCollapseState['{{ $blockId }}'] = value;
            });
            
            // Jaring pengaman: Jika jumlah kolom dikurangi, pastikan tab aktif kembali ke 1
            this.$watch('$wire.content.{{ $blockId }}.data.col_count', (value) => {
                let maxTab = parseInt(value);
                let currentTabNum = parseInt(this.activeTab.replace('col_', '').replace('_zone', ''));
                if (currentTabNum > maxTab) {
                    this.activeTab = 'col_1_zone';
                }
            });
        },
        updateZoneOrder(evt, zone) {
            let order = Array.from(evt.to.children).map(el => el.getAttribute('data-id')).filter(Boolean);
            $wire.reorderChildBlocks('{{ $blockId }}', zone, order);
        }
    }"
    @toggle-collapse-all.window="isCollapsed = $event.detail"
    @sync-columns-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail"
    @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
    class="is-nested-container bg-white border border-gray-300 rounded-xl shadow-sm relative">

    {{-- HEADER & PENGATURAN BLOK --}}
    <div class="bg-gray-50 border-b border-gray-200" :class="isCollapsed ? 'rounded-b-xl' : ''">

        <div class="px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                {{-- Tombol Buka/Tutup --}}
                <button type="button" @click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)" 
                        class="p-1 hover:bg-gray-200 rounded text-gray-500 transition-colors focus:outline-none">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="isCollapsed ? '-rotate-90' : 'rotate-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <span class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest flex items-center gap-1.5 cursor-pointer select-none" 
                      @click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)">
                    <x-dynamic-component component="lucide-layout-template" class="w-4 h-4 text-gray-400" />
                    Blok Kolom Multi
                </span>

                <button type="button"
                        @click.stop="isCollapsed = false; $dispatch('sync-collapse-{{ strtolower($blockId) }}', false); $dispatch('force-collapse-children', {{ json_encode($allChildren) }});"
                        class="ml-2 flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-200 rounded text-[9px] font-bold shadow-sm hover:bg-blue-100 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    Atur Susunan
                </button>
            </div>

            <div class="flex items-center gap-4">
                {{-- KONTROL JUMLAH KOLOM --}}
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Jumlah Kolom:</label>
                    <select wire:model.live="content.{{ $blockId }}.data.col_count" class="text-xs font-bold text-blue-600 border-gray-300 rounded py-1 pl-2 pr-6 shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                        <option value="2">2 Kolom</option>
                        <option value="3">3 Kolom</option>
                        <option value="4">4 Kolom</option>
                        <option value="5">5 Kolom</option>
                        <option value="6">6 Kolom</option>
                    </select>
                </div>

                {{-- 🌟 KONTROL URUTAN HP (Hanya muncul jika 2 Kolom) --}}
                @if($colCount === 2)
                    <div class="flex items-center gap-2 border-l border-gray-200 pl-4">
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Urutan HP:</span>
                            <div class="relative group/tooltip flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-gray-400 cursor-help hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div class="absolute bottom-full right-0 md:left-1/2 md:-translate-x-1/2 mb-2 w-48 opacity-0 invisible group-hover/tooltip:opacity-100 group-hover/tooltip:visible transition-all duration-200 z-[100] pointer-events-none">
                                    <div class="bg-gray-800 text-white text-[10px] leading-relaxed p-2.5 rounded-lg shadow-xl text-center relative">
                                        Mengatur susunan saat dibaca di HP.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" wire:click="$toggle('content.{{ $blockId }}.data.mobile_reverse')" class="flex items-center gap-1.5 px-2 py-1 bg-white border border-gray-200 rounded-md text-[10px] font-bold shadow-sm hover:bg-gray-50 focus:outline-none">
                            @if($block['data']['mobile_reverse'] ?? false)
                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                                <span class="text-blue-600">Kanan di Atas</span>
                            @else
                                <svg class="w-3.5 h-3.5 text-foresty" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                <span class="text-gray-600">Kiri di Atas</span>
                            @endif
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- 🌟 NAVIGASI TAB DINAMIS DENGAN SINKRONISASI --}}
        <div x-show="layoutMode === 'split'" x-cloak class="flex px-4 space-x-1 relative top-[1px] overflow-x-auto scrollbar-hide">
            @for($i = 1; $i <= $colCount; $i++)
                @php $zoneKey = "col_{$i}_zone"; @endphp
                <button @click="activeTab = '{{ $zoneKey }}'; $dispatch('sync-columns-tab-{{ strtolower($blockId) }}', '{{ $zoneKey }}')"
                        type="button"
                        :class="activeTab === '{{ $zoneKey }}' ? 'bg-white text-blue-600 border-gray-200 shadow-sm' : 'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-200/50 border-transparent'"
                        class="px-5 py-2 text-xs font-bold transition-all border-t border-l border-r rounded-t-lg flex items-center gap-2 shrink-0">
                    Kolom {{ $i }}
                    <span class="bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded text-[9px]">
                        {{ count($block['data'][$zoneKey] ?? []) }}
                    </span>
                </button>
            @endfor
        </div>
    </div>

    {{-- 🌟 AREA KONTEN DROPZONE (Mendukung layoutMode Single/Split) --}}
    <div x-show="!isCollapsed" x-collapse x-cloak :class="layoutMode === 'single' ? 'grid grid-cols-1 {{ $editorGridClass }} divide-y md:divide-y-0 md:divide-x divide-gray-200' : 'block'" class="bg-white rounded-b-xl">
        @for($i = 1; $i <= $colCount; $i++)
            @php 
                $zoneKey = "col_{$i}_zone"; 
                $zoneBlocks = $block['data'][$zoneKey] ?? [];
            @endphp
            
            <div x-show="layoutMode === 'single' || activeTab === '{{ $zoneKey }}'" class="flex flex-col h-full" style="display: none;">
                
                {{-- Penanda kolom saat mode 'single' (semua dijajarkan) --}}
                <div x-show="layoutMode === 'single'" x-cloak class="px-4 py-2 bg-gray-100/50 border-b border-gray-200">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Kolom {{ $i }}</span>
                </div>

                {{-- Alpine Sortable Dropzone --}}
                <div x-sort
                     x-sort:config="{ group: '{{ $zoneKey }}_{{ $blockId }}', animation: 150, handle: '.child-drag-handle', onEnd: (evt) => updateZoneOrder(evt, '{{ $zoneKey }}') }"
                     class="flex-1 p-4 space-y-4 min-h-[150px] bg-gray-50/20">
                    
                    @foreach($zoneBlocks as $childId)
                        @if(isset($allContent[$childId]))
                            @php $childBlock = $allContent[$childId]; @endphp
                            
                            {{-- Wrapper Mikro Blok --}}
                            <div id="block-wrapper-{{ $childId }}" data-id="{{ $childId }}" x-sort:item="'{{ $childId }}'" wire:key="child-{{ $childId }}" class="relative group rounded-lg hover:ring-2 hover:ring-blue-100 transition-all bg-white shadow-sm border border-gray-200">
                                <!-- Drag Handle -->
                                <div class="child-drag-handle absolute -left-2 top-3 opacity-0 group-hover:opacity-100 cursor-move text-gray-300 hover:text-blue-500 z-10 bg-white rounded-full p-0.5 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"></path></svg>
                                </div>
                                <!-- Tombol Hapus -->
                                <div class="absolute -top-2.5 -right-2.5 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                                    <button type="button" wire:click="removeNestedBlock('{{ $blockId }}', '{{ $zoneKey }}', '{{ $childId }}')" class="p-1 bg-red-100 text-red-600 rounded-full hover:bg-red-200 border border-red-200 shadow-sm" title="Hapus Blok Ini">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                
                                {{-- Render Komponen Editor --}}
                                <div class="p-0">
                                    <x-dynamic-component :component="'blocks.editor.' . str_replace('_', '-', $childBlock['type'])" :block-id="$childId" :code="$code" :block="$childBlock" :all-content="$allContent" />
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Menu Tambah Blok Anak --}}
                <div class="p-4 pt-0 mt-auto border-t border-gray-100">
                    <div x-data="{ openDropdown: false }" class="relative mt-4">
                        <button @click="openDropdown = !openDropdown" @click.outside="openDropdown = false" type="button" class="w-full py-2 flex items-center justify-center gap-2 border border-dashed border-gray-300 text-xs font-bold text-gray-400 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 rounded-lg transition-colors">
                            + Tambah Konten ke Kolom {{ $i }}
                        </button>
                        <div x-show="openDropdown" x-cloak class="absolute z-50 bottom-full mb-1 left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden">
                            <div class="p-1 flex flex-col gap-0.5">
                                <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', 'heading'); openDropdown = false" class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Judul (Heading)</button>
                                <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', 'paragraph'); openDropdown = false" class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Paragraf</button>
                                <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', 'eyebrow'); openDropdown = false" class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Eyebrow</button>
                                <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', 'image'); openDropdown = false" class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Gambar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>