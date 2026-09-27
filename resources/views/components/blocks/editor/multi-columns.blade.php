@props(['blockId', 'code', 'block', 'allContent'])

@php
  $colCount = (int) ($block['data']['col_count'] ?? 2);

  // Kumpulkan semua ID anak dari zona yang aktif untuk fitur "Runtuhkan Anak"
  $allChildren = [];
  for ($i = 1; $i <= $colCount; $i++) {
      $allChildren = array_merge($allChildren, $block['data']["col_{$i}_zone"] ?? []);
  }

  // Kelas grid untuk editor saat mode layoutMode === 'single' (semua kolom dijejerkan)
  // 🌟 Diperbarui: Kini grid utamanya tidak lagi statis 'grid-cols-2', melainkan diatur
  // secara dinamis via inline style berdasarkan proporsi lebar (width) masing-masing kolom.
@endphp

{{-- 🌟 STATE ALPINE LENGKAP --}}
<div class="is-nested-container bg-white border border-gray-300 rounded-xl shadow-sm relative" x-data="{
    isCollapsed: false,
    childrenCollapsed: false,
    activeTab: 'col_1_zone',
    layoutMode: 'split', 
    
    init() {
        window.blockCollapseState = window.blockCollapseState || {};
        if (window.blockCollapseState['{{ $blockId }}'] !== undefined) {
            this.isCollapsed = window.blockCollapseState['{{ $blockId }}'];
        }

        this.$watch('isCollapsed', (value) => {
            window.blockCollapseState['{{ $blockId }}'] = value;
        });

        this.$watch(
            () => $wire.content?.['{{ $blockId }}']?.data?.col_count,
            (value) => {
                if (value === undefined || value === null) return;
                let maxTab = parseInt(value);
                let currentTabNum = parseInt(this.activeTab.replace('col_', '').replace('_zone', ''));
                if (currentTabNum > maxTab) {
                    this.activeTab = 'col_1_zone';
                }
            }
        );
    },
    updateZoneOrder(evt, zone) {
        let order = Array.from(evt.to.children).map(el => el.getAttribute('data-id')).filter(Boolean);
        $wire.reorderChildBlocks('{{ $blockId }}', zone, order);
    }
}" @toggle-collapse-all.window="isCollapsed = $event.detail"
  @sync-columns-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail" 
  @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail">

  <!-- HEADER UTAMA BLOK -->
  <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-colors group-hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">
    {{-- LEFT HEADER --}}
    <div class="flex items-center gap-2">
      <button type="button" x-on:click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)" class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none cursor-pointer">
        <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
      </button>
      <div class="p-1 bg-sage-soft rounded-md">
        <x-dynamic-component :component="'lucide-layout-template'" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center">
        Kolom Multi
      </span>
    </div>
    
    {{-- RIGHT HEADER --}}
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0 transition-all duration-200">
      <div x-show="isCollapsed" x-cloak class="flex-1 min-w-0 px-2 sm:px-4 text-xs text-gray-400 font-medium" title="{{ $colCount }} Kolom berisi total {{ count($allChildren) }} konten">
        <span class="block truncate w-full text-right">
          <span class="font-bold text-gray-500">{{ $colCount }}</span> Kolom &bull;
          <span class="font-bold text-gray-500">{{ count($allChildren) }}</span> Blok
        </span>
      </div>
      <button type="button" x-data="{ childrenCollapsed: false }" x-cloak
        @click.stop="
          if (isCollapsed) {
              isCollapsed = false;
              $dispatch('sync-collapse-{{ strtolower($blockId) }}', false);
              childrenCollapsed = true;
              $dispatch('force-collapse-children', {{ json_encode($allChildren) }});
          } else {
              childrenCollapsed = !childrenCollapsed;
              $dispatch(childrenCollapsed ? 'force-collapse-children' : 'force-expand-children', {{ json_encode($allChildren) }});
          }
        "
        class="flex items-center gap-1 px-2 py-0.5 bg-sage-soft/50 text-forest border border-sage-soft/50 rounded text-[9px] font-bold shadow-sm hover:bg-sage-soft transition-colors shrink-0"
        :title="isCollapsed ? 'Buka kolom dan atur susunan blok di dalamnya' : (childrenCollapsed ? 'Buka kembali semua isi kolom' : 'Ciutkan semua isi kolom untuk menyusun urutan')">
        
        <x-dynamic-component x-show="isCollapsed" component="lucide-list-tree" class="w-3 h-3" stroke-width="3" />
        <x-dynamic-component x-show="!isCollapsed && !childrenCollapsed" component="lucide-chevrons-down-up" class="w-3 h-3" stroke-width="3" />
        <x-dynamic-component x-show="!isCollapsed && childrenCollapsed" component="lucide-chevrons-up-down" class="w-3 h-3" stroke-width="3" />
        <span x-text="isCollapsed ? 'Atur Blok' : (childrenCollapsed ? 'Buka Isi' : 'Ciutkan Isi')"></span>
      </button>
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
        {{ $code }}
      </span>
    </div>
  </div>


  {{-- 🌟 AREA KONTEN (PENGATURAN & DROPZONE) 🌟 --}}
  <div x-show="!isCollapsed" x-collapse x-cloak class="bg-gray-100 rounded-b-xl pb-4">

    {{-- HEADER KONTROL --}}
    <div class="flex flex-col gap-4 p-4 border-b border-gray-200">
      {{-- BARIS 1: Kontrol Dasar --}}
      <div class="flex items-center justify-between">
        <label class="block text-xs font-semibold text-gray-500 uppercase">Kontrol Dasar</label>
        <div class="flex items-center gap-4">

          {{-- KONTROL URUTAN HP --}}
          @if ($colCount === 2)
            <div class="flex items-center gap-2 border-r border-gray-200 pr-4">
              <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Urutan HP:</span>
              <button type="button" wire:click="$toggle('content.{{ $blockId }}.data.mobile_reverse')"
                class="flex items-center gap-1.5 px-2 py-1 bg-white border border-gray-200 rounded-md text-[10px] font-bold shadow-sm hover:bg-gray-50 focus:outline-none">
                @if ($block['data']['mobile_reverse'] ?? false)
                  <x-dynamic-component component="lucide-arrow-up-down" class="w-3.5 h-3.5 text-blue-500" />
                  <span class="text-blue-600">Kanan di Atas</span>
                @else
                  <x-dynamic-component component="lucide-arrow-down-up" class="w-3.5 h-3.5 text-foresty" />
                  <span class="text-gray-600">Kiri di Atas</span>
                @endif
              </button>
            </div>
          @endif

          {{-- KONTROL JUMLAH KOLOM --}}
          <div class="flex items-center gap-2">
            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Jumlah Kolom:</label>
            <select wire:model.live="content.{{ $blockId }}.data.col_count" class="text-xs font-bold text-forest border-gray-300 rounded py-1 pl-2 pr-6 shadow-sm focus:ring-forest focus:border-forest bg-white outline-none cursor-pointer">
              <option value="2">2 Kolom</option>
              <option value="3">3 Kolom</option>
              <option value="4">4 Kolom</option>
              <option value="5">5 Kolom</option>
              <option value="6">6 Kolom</option>
            </select>
          </div>
        </div>
      </div>

      {{-- BARIS 2: Tata Letak & Spasi Internal --}}
      <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-200 shadow-inner" x-data="{
          localAlignX: $wire.entangle('content.{{ $blockId }}.data.align_x').live || 'items-start',
          localAlignY: $wire.entangle('content.{{ $blockId }}.data.align_y').live || 'justify-start',
          localGap: $wire.entangle('content.{{ $blockId }}.data.gap').live || 'gap-4'
      }">
        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-3">Tata Letak & Spasi Kolom</label>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
          {{-- 1. Sumbu X --}}
          <div class="flex flex-col gap-1.5">
            <span class="text-[9px] font-bold text-foresty uppercase tracking-wider">Perataan X (Kiri-Kanan)</span>
            <div class="flex items-center gap-1 bg-gray-200/50 p-1 rounded-lg border border-gray-200/50">
              <button type="button" @click="localAlignX = 'items-start'" class="flex-1 justify-center px-2 py-1.5 text-[10px] font-bold rounded-md transition-all duration-300 flex items-center gap-1.5" :class="localAlignX === 'items-start' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'">
                <x-dynamic-component component="lucide-align-left" class="w-3.5 h-3.5" /> Kiri
              </button>
              <button type="button" @click="localAlignX = 'items-center'" class="flex-1 justify-center px-2 py-1.5 text-[10px] font-bold rounded-md transition-all duration-300 flex items-center gap-1.5" :class="localAlignX === 'items-center' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'">
                <x-dynamic-component component="lucide-align-center" class="w-3.5 h-3.5" /> Tengah
              </button>
              <button type="button" @click="localAlignX = 'items-end'" class="flex-1 justify-center px-2 py-1.5 text-[10px] font-bold rounded-md transition-all duration-300 flex items-center gap-1.5" :class="localAlignX === 'items-end' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'">
                <x-dynamic-component component="lucide-align-right" class="w-3.5 h-3.5" /> Kanan
              </button>
            </div>
          </div>

          {{-- 2. Sumbu Y --}}
          <div class="flex flex-col gap-1.5">
            <span class="text-[9px] font-bold text-foresty uppercase tracking-wider">Perataan Y (Atas-Bawah)</span>
            <div class="flex flex-wrap items-center gap-1 bg-gray-200/50 p-1 rounded-lg border border-gray-200/50">
              <button type="button" @click="localAlignY = 'justify-start'" class="flex-1 justify-center px-2 py-1.5 text-[10px] font-bold rounded-md transition-all duration-300 flex items-center gap-1" :class="localAlignY === 'justify-start' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'">
                <x-dynamic-component component="lucide-align-vertical-justify-start" class="w-3.5 h-3.5" /> Atas
              </button>
              <button type="button" @click="localAlignY = 'justify-center'" class="flex-1 justify-center px-2 py-1.5 text-[10px] font-bold rounded-md transition-all duration-300 flex items-center gap-1" :class="localAlignY === 'justify-center' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'">
                <x-dynamic-component component="lucide-align-vertical-justify-center" class="w-3.5 h-3.5" /> Tgh
              </button>
              <button type="button" @click="localAlignY = 'justify-end'" class="flex-1 justify-center px-2 py-1.5 text-[10px] font-bold rounded-md transition-all duration-300 flex items-center gap-1" :class="localAlignY === 'justify-end' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'">
                <x-dynamic-component component="lucide-align-vertical-justify-end" class="w-3.5 h-3.5" /> Bwh
              </button>
            </div>
          </div>

          {{-- 3. Jarak Antar Blok --}}
          <div class="flex flex-col gap-1.5">
            <span class="text-[9px] font-bold text-foresty uppercase tracking-wider">Jarak Antar Blok</span>
            <div class="flex items-center gap-1 bg-gray-200/50 p-1 rounded-lg border border-gray-200/50">
              <button type="button" @click="localGap = 'gap-0'" class="flex-1 justify-center px-1 py-1.5 text-[10px] font-bold rounded-md transition-all duration-300 flex items-center gap-1" :class="localGap === 'gap-0' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'">0px</button>
              <button type="button" @click="localGap = 'gap-4'" class="flex-1 justify-center px-1 py-1.5 text-[10px] font-bold rounded-md transition-all duration-300 flex items-center gap-1" :class="localGap === 'gap-4' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'">16px</button>
              <button type="button" @click="localGap = 'gap-8'" class="flex-1 justify-center px-1 py-1.5 text-[10px] font-bold rounded-md transition-all duration-300 flex items-center gap-1" :class="localGap === 'gap-8' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'">32px</button>
            </div>
          </div>
        </div>
      </div>
    </div>


    <!-- 🌟 NAVIGASI TAB KOLOM DINAMIS (Hanya muncul jika mode Split) 🌟 -->
    <div x-show="layoutMode === 'split'" x-cloak class="flex px-4 pt-4 space-x-1 relative overflow-x-auto scrollbar-hide bg-gray-100">
      @for ($i = 1; $i <= $colCount; $i++)
        @php $zoneKey = "col_{$i}_zone"; @endphp
        <button type="button" x-on:click="activeTab = '{{ $zoneKey }}'; $dispatch('sync-columns-tab-{{ strtolower($blockId) }}', '{{ $zoneKey }}')"
          :class="activeTab === '{{ $zoneKey }}' ? 'bg-white text-foresty border-gray-200 shadow-sm border-b-white z-10 -mb-px' : 'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-200/50 border-transparent border-b-gray-200'"
          class="px-5 py-2 text-xs font-bold transition-all border-t border-l border-r rounded-t-lg flex items-center gap-2 shrink-0 outline-none">
          Kolom {{ $i }}
          <span class="bg-sage-soft text-foresty px-1.5 py-0.5 rounded text-[9px]">{{ count($block['data'][$zoneKey] ?? []) }}</span>
        </button>
      @endfor
    </div>

    <!-- 🌟 GRID UTAMA & DROPZONE 🌟 -->
    <!-- Jika mode Single: Semua kolom dirender berurutan dengan CSS Grid -->
    <!-- Jika mode Split: Hanya kolom aktif yang ditampilkan -->
    <div :class="layoutMode === 'single' ? 'grid grid-cols-1 md:grid-cols-{{ $colCount }} divide-y md:divide-y-0 md:divide-x divide-gray-200' : 'block px-4 pt-0'">
        
      @for ($i = 1; $i <= $colCount; $i++)
        @php
          $zoneKey = "col_{$i}_zone";
          $zoneBlocks = $block['data'][$zoneKey] ?? [];
          // Baca nilai width dari JSON, default 'auto'
          $colWidth = $block['data']["{$zoneKey}_width"] ?? 'auto'; 
        @endphp

        <div x-show="layoutMode === 'single' || activeTab === '{{ $zoneKey }}'" class="flex flex-col h-full bg-white border-gray-200 relative" 
             :class="layoutMode === 'split' ? 'border rounded-xl shadow-sm' : ''"
             style="display: none;">

          <!-- 🌟 FITUR BARU: KONTROL LEBAR KOLOM (MUNCUL DI ATAS SETIAP KOLOM) 🌟 -->
          <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-2" :class="layoutMode === 'split' ? 'rounded-t-xl' : ''">
            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest shrink-0">
                <span x-show="layoutMode === 'single'">Kolom {{ $i }} • </span>Lebar:
            </span>
            
            <!-- Segmented Control Width -->
            <div class="flex items-center rounded-md bg-gray-200 transition-all duration-200 p-0.5 shadow-inner">
                <button type="button" 
                    wire:click="$set('content.{{ $blockId }}.data.{{ $zoneKey }}_width', 'auto')" 
                    class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $colWidth === 'auto' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    Auto
                </button>
                <button type="button" 
                    wire:click="$set('content.{{ $blockId }}.data.{{ $zoneKey }}_width', '1')" 
                    class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $colWidth == '1' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    1fr
                </button>
                <button type="button" 
                    wire:click="$set('content.{{ $blockId }}.data.{{ $zoneKey }}_width', '2')" 
                    class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $colWidth == '2' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    2fr
                </button>
                <button type="button" 
                    wire:click="$set('content.{{ $blockId }}.data.{{ $zoneKey }}_width', '3')" 
                    class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $colWidth == '3' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    3fr
                </button>
            </div>
          </div>

          {{-- Alpine Sortable Dropzone --}}
          <div x-sort x-sort:config="{ group: '{{ $zoneKey }}_{{ $blockId }}', animation: 150, handle: '.child-drag-handle', onEnd: (evt) => updateZoneOrder(evt, '{{ $zoneKey }}') }"
            class="flex-1 p-4 space-y-4 min-h-[160px] bg-white transition-colors">

            @foreach ($zoneBlocks as $childId)
              @if (isset($allContent[$childId]))
                @php $childBlock = $allContent[$childId]; @endphp

                {{-- Wrapper Mikro Blok --}}
                <div id="block-wrapper-{{ $childId }}" data-id="{{ $childId }}" x-sort:item="'{{ $childId }}'" wire:key="child-{{ $childId }}"
                  class="relative group rounded-xl hover:ring-2 hover:ring-sage-soft transition-all bg-white shadow-sm border border-gray-200">
                  
                  <!-- Drag Handle -->
                  <div class="child-drag-handle absolute -left-2 top-3 opacity-0 group-hover:opacity-100 cursor-move text-gray-300 hover:text-blue-500 z-10 bg-white rounded-full p-0.5 shadow-sm">
                    <x-dynamic-component component="lucide-grip-vertical" class="w-4 h-4" />
                  </div>
                  
                  <!-- Tombol Hapus -->
                  <div class="absolute -top-2.5 -right-2.5 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                    <button type="button" wire:click="removeNestedBlock('{{ $blockId }}', '{{ $zoneKey }}', '{{ $childId }}')"
                      class="p-1 bg-red-100 text-red-600 rounded-full hover:bg-red-200 border border-red-200 shadow-sm outline-none cursor-pointer" title="Hapus Blok Ini">
                      <x-dynamic-component component="lucide-x" class="w-3 h-3" stroke-width="2.5" />
                    </button>
                  </div>

                  {{-- Render Komponen Editor Internal --}}
                  <div class="p-0">
                    <x-dynamic-component :component="'blocks.editor.' . str_replace('_', '-', $childBlock['type'])" :block-id="$childId" :code="$code" :block="$childBlock" :all-content="$allContent" />
                  </div>
                </div>
              @endif
            @endforeach
          </div>

          {{-- Menu Tambah Blok Anak (Ditampilkan di bawah setiap dropzone) --}}
          <div class="p-4 pt-0 mt-auto bg-white" :class="layoutMode === 'split' ? 'rounded-b-xl' : ''">
            <div x-data="{ openDropdown: false }" class="relative mt-2 border-t border-gray-100 pt-3">
              <button @click="openDropdown = !openDropdown" @click.outside="openDropdown = false" type="button"
                class="w-full py-2 flex items-center justify-center gap-2 border border-dashed border-gray-300 text-xs font-bold text-gray-400 hover:text-foresty hover:border-foresty hover:bg-sage-soft rounded-lg transition-colors outline-none cursor-pointer">
                <x-dynamic-component component="lucide-plus" class="w-3.5 h-3.5" /> Tambah Blok ke Kolom {{ $i }}
              </button>
              
              <!-- Pop-up Daftar Komponen -->
              <div x-show="openDropdown" x-cloak class="absolute z-50 bottom-full mb-2 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                <div class="p-1.5 grid grid-cols-2 gap-1 max-h-[300px] overflow-y-auto scrollbar-thin">
                    @php
                        $availableBlocks = [
                            ['type' => 'heading', 'icon' => 'heading-1', 'label' => 'Judul (Heading)'],
                            ['type' => 'paragraph', 'icon' => 'align-left', 'label' => 'Paragraf'],
                            ['type' => 'eyebrow', 'icon' => 'minus', 'label' => 'Eyebrow'],
                            ['type' => 'image', 'icon' => 'image', 'label' => 'Gambar'],
                            ['type' => 'button-group', 'icon' => 'mouse-pointer-click', 'label' => 'Grup Tombol'],
                            ['type' => 'badge-group', 'icon' => 'tag', 'label' => 'Lencana'],
                            ['type' => 'stats-group', 'icon' => 'bar-chart-2', 'label' => 'Statistik'],
                            ['type' => 'card-group', 'icon' => 'layout-grid', 'label' => 'Grup Kartu'],
                            ['type' => 'testimonial-group', 'icon' => 'message-square-quote', 'label' => 'Testimoni'],
                            ['type' => 'card-builder', 'icon' => 'layout-panel-top', 'label' => 'Card Builder'],
                        ];
                    @endphp
                    @foreach($availableBlocks as $b)
                        <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', '{{ $b['type'] }}'); openDropdown = false"
                          class="flex items-center gap-2 px-2 py-2 text-[11px] font-semibold text-gray-600 hover:bg-sage-soft hover:text-foresty rounded-md transition text-left outline-none cursor-pointer">
                          <x-dynamic-component component="lucide-{{ $b['icon'] }}" class="w-3.5 h-3.5 shrink-0" /> 
                          <span class="truncate">{{ $b['label'] }}</span>
                        </button>
                    @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      @endfor
    </div>
  </div>
</div>