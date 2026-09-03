@props([
    'blockId',
    'code',
    'block' => [],
    'allContent' => []
])

@php
    $iconsList = [
        'newspaper', 'bookmark', 'sparkles', 'tag', 'folder', 
        'flag', 'globe', 'heart', 'star', 'shield', 
        'award', 'bell', 'briefcase', 'calendar', 'check-circle', 
        'compass', 'cpu', 'file-text', 'filter', 'gift', 
        'home', 'info', 'layers', 'life-buoy', 'lightbulb', 
        'link', 'lock', 'map', 'megaphone', 'message-square', 
        'mic', 'moon', 'package', 'paperclip', 'pen-tool', 
        'pie-chart', 'play', 'power', 'radio', 'rss', 
        'search', 'send', 'settings', 'share-2', 'shield-check', 
        'shopping-bag', 'shopping-cart', 'sliders', 'smile', 'speaker', 
        'sun', 'target', 'terminal', 'thumbs-up', 'wrench', 
        'trash-2', 'trending-up', 'triangle', 'truck', 'tv', 
        'user', 'users', 'video', 'volume-2', 'watch', 'zap'
    ];

    $colorsList = [
        ['name' => 'Coral Dark', 'value' => '#e05a47'],
        ['name' => 'Forest Green', 'value' => '#064f3b'],
        ['name' => 'Sage Muted', 'value' => '#4b5d53'],
        ['name' => 'Charcoal', 'value' => '#1f2937'],
        ['name' => 'Ocean Blue', 'value' => '#0369a1'],
        ['name' => 'Amber', 'value' => '#d97706'],
        ['name' => 'Purple', 'value' => '#7c3aed'],
        ['name' => 'Rose', 'value' => '#e11d48']
    ];
@endphp

<div 
    class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/eyebrow space-y-2"
    x-data="{ 
        isCollapsed: false,
        init() {
            window.blockCollapseState = window.blockCollapseState || {};
            if (window.blockCollapseState['{{ $blockId }}'] !== undefined) {
                this.isCollapsed = window.blockCollapseState['{{ $blockId }}'];
            }
            this.$watch('isCollapsed', (value) => {
                window.blockCollapseState['{{ $blockId }}'] = value;
            });
        }
    }" 
    @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
    @toggle-collapse-all.window="isCollapsed = $event.detail"
    @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }">

    <!-- ==================== HEADER BLOK ==================== -->
    <div 
        class="flex items-center justify-between px-4 py-3 bg-gray-50/80 cursor-pointer select-none transition-colors hover:bg-gray-100"
        :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'"
        @click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)">

        <div class="flex items-center gap-2">
            <x-dynamic-component 
                :component="'lucide-circle-chevron-down'" 
                class="h-4 w-4 text-sage-soft transition-transform duration-200" 
                ::class="isCollapsed ? '-rotate-90' : 'rotate-0'" 
                stroke-width="2.5" 
            />
            <span class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest flex items-center gap-1.5">
                <x-dynamic-component :component="'lucide-bookmark'" class="h-4 w-4 text-gray-400" stroke-width="2.5" />
                Eyebrow / Kategori
            </span>
        </div>
        
        <div class="flex items-center gap-2" @click.stop>
            <div x-show="isCollapsed" x-cloak class="flex-1 px-4 truncate text-xs text-gray-400 text-right font-medium">
                <span x-text="$wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || 'Kosong...'"></span>
            </div>
            <span class="text-[10px] font-bold text-forest uppercase ml-2 bg-sage-soft px-1.5 py-0.5 rounded shadow-sm">{{ $code }}</span>
        </div>
    </div>

    <!-- ==================== KONTEN EDITOR ==================== -->
    {{-- 🌟 PERBAIKAN: Listener window menggunakan strtolower() agar tidak miss dengan dispatch --}}
    <div x-show="!isCollapsed" x-collapse x-cloak class="p-4 space-y-4 bg-gray-50/50 rounded-b-xl"
         x-data="{ 
             localIcon: '{{ $block['data']['icon'] ?? 'newspaper' }}',
             localColor: '{{ $block['data']['color'] ?? '#e05a47' }}',
             
             setIcon(val) {
                 this.localIcon = val;
                 $wire.set('content.{{ $blockId }}.data.icon', val); 
                 // 🌟 PERBAIKAN: Gunakan strtolower() saat dispatch
                 $dispatch('sync-global-icon-{{ strtolower($blockId) }}', val); 
             },
             
             setColor(val) {
                 this.localColor = val;
                 $wire.set('content.{{ $blockId }}.data.color', val);
                 // 🌟 PERBAIKAN: Gunakan strtolower() saat dispatch
                 $dispatch('sync-global-color-{{ strtolower($blockId) }}', val);
             }
         }"
         @sync-global-icon-{{ strtolower($blockId) }}.window="localIcon = $event.detail"
         @sync-global-color-{{ strtolower($blockId) }}.window="localColor = $event.detail">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <!-- 1. ICON PICKER GLOBAL -->
            <div class="md:col-span-1" x-data="{ openPicker: false, searchQuery: '' }">
                <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Ikon Global</label>
                
                <div class="relative">
                    <button type="button" @click="openPicker = !openPicker" 
                        class="w-full flex items-center justify-between border border-zinc-200 bg-white text-zinc-700 rounded-md py-2 px-3 text-xs shadow-sm hover:bg-zinc-50 transition-colors focus:outline-none">
                        <div class="flex items-center gap-2 truncate">
                            <span class="w-4 h-4 shrink-0 flex items-center justify-center">
                                @foreach($iconsList as $icon)
                                    <span x-show="localIcon === '{{ $icon }}'" x-cloak style="display: none;">
                                        <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4" />
                                    </span>
                                @endforeach
                                <span x-show="!localIcon" x-cloak>
                                    <x-dynamic-component component="lucide-newspaper" class="w-4 h-4" />
                                </span>
                            </span>
                            <span class="truncate uppercase font-mono text-[11px]" x-text="localIcon || 'Pilih Ikon'"></span>
                        </div>
                        <x-dynamic-component :component="'lucide-chevron-down'" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                    </button>

                    <div x-show="openPicker" @click.away="openPicker = false" x-cloak style="display: none;"
                        class="absolute left-0 mt-1 w-64 bg-white border border-zinc-200 rounded-xl shadow-xl p-3 z-50 flex flex-col gap-2">
                        
                        <input type="text" x-model="searchQuery" placeholder="Cari ikon..."
                            class="w-full text-xs border border-zinc-200 rounded-lg px-2.5 py-1.5 focus:ring-0 focus:border-forest" />

                        <div class="grid grid-cols-5 gap-1.5 max-h-48 overflow-y-auto p-1 scrollbar-thin">
                            @foreach($iconsList as $icon)
                                <button type="button" 
                                    x-show="'{{ $icon }}'.includes(searchQuery.toLowerCase())"
                                    @click="setIcon('{{ $icon }}'); openPicker = false; searchQuery = ''"
                                    class="p-2 rounded-lg flex items-center justify-center hover:bg-sage-soft text-zinc-600 transition-colors"
                                    :class="localIcon === '{{ $icon }}' ? 'bg-forest text-white' : 'bg-zinc-50'"
                                    title="{{ $icon }}">
                                    <span class="w-4 h-4 flex items-center justify-center">
                                        <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 shrink-0" />
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. COLOR PICKER GLOBAL -->
            <div class="md:col-span-1" x-data="{ openColor: false }">
                <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1">Warna Global</label>
                
                <div class="relative">
                    <button type="button" @click="openColor = !openColor" 
                        class="w-full flex items-center justify-between border border-zinc-200 bg-white text-zinc-700 rounded-md py-2 px-3 text-xs shadow-sm hover:bg-zinc-50 transition-colors focus:outline-none">
                        <div class="flex items-center gap-2 truncate">
                            <span class="w-3.5 h-3.5 rounded-full border border-gray-200 shrink-0" 
                                  :style="`background-color: ${localColor || '#e05a47'}`"></span>
                            
                            <span class="truncate uppercase font-mono text-[11px]">
                                @foreach($colorsList as $color)
                                    <span x-show="localColor === '{{ $color['value'] }}'" x-cloak style="display: none;">{{ $color['name'] }}</span>
                                @endforeach
                                <span x-show="!localColor" x-cloak>Pilih Warna</span>
                            </span>
                        </div>
                        <x-dynamic-component :component="'lucide-chevron-down'" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                    </button>

                    <div x-show="openColor" @click.away="openColor = false" x-cloak style="display: none;"
                        class="absolute left-0 mt-1 w-48 bg-white border border-zinc-200 rounded-xl shadow-xl p-2 z-50 flex flex-col gap-1">
                        
                        @foreach($colorsList as $color)
                            <button type="button" 
                                @click="setColor('{{ $color['value'] }}'); openColor = false"
                                class="flex items-center gap-2 w-full p-2 text-xs text-left rounded-lg hover:bg-zinc-50 transition-colors"
                                :class="localColor === '{{ $color['value'] }}' ? 'bg-zinc-100 font-semibold' : ''">
                                <span class="w-3.5 h-3.5 rounded-full border border-gray-200 shadow-sm" style="background-color: {{ $color['value'] }}"></span>
                                <span class="text-zinc-700">{{ $color['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 3. INPUT TEKS LOKAL (Terikat pada Bahasa $code) -->
            <div class="md:col-span-2">
                <label class="block text-[10px] font-semibold text-forest uppercase mb-1">Teks Eyebrow ({{ strtoupper($code) }})</label>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="content.{{ $blockId }}.data.text.{{ $code }}"
                    placeholder="Contoh: ARTIKEL & CERITA LAPANGAN"
                    class="w-full text-xs border border-zinc-200 bg-white text-zinc-700 rounded-md py-2 px-3 focus:ring-0 focus:border-forest transition-colors shadow-sm"
                />
            </div>

        </div>

        <!-- ==================== LIVE PREVIEW ==================== -->
        <div class="pt-3 border-t border-dashed border-gray-200">
            <span class="block text-[10px] font-semibold text-gray-400 uppercase mb-2">Pratinjau Langsung:</span>
            
            <div class="px-4 py-3 bg-white border border-gray-200 rounded-lg shadow-2xs inline-flex items-center">
                <span class="inline-flex items-center gap-2.5 font-['Instrument_Sans',sans-serif] text-[11px] md:text-[13px] font-bold tracking-[0.16em] uppercase transition-colors"
                      :style="`color: ${localColor || '#e05a47'}`">
                    
                    <span class="w-4 h-4 shrink-0 flex items-center justify-center">
                        @foreach($iconsList as $icon)
                            <span x-show="localIcon === '{{ $icon }}'" x-cloak style="display: none;">
                                <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 shrink-0" stroke-width="2" />
                            </span>
                        @endforeach
                        <span x-show="!localIcon" x-cloak>
                            <x-dynamic-component component="lucide-newspaper" class="w-4 h-4 shrink-0" stroke-width="2" />
                        </span>
                    </span>

                    {{-- Teks Preview tetap mengambil dari Livewire karena teks berbeda tiap bahasa --}}
                    <span x-text="$wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || 'TULIS TEKS EYEBROW DI ATAS...'"></span>
                </span>
            </div>
        </div>

    </div>
</div>