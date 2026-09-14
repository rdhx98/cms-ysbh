@props(['blockId', 'code', 'block', 'allContent'])

@php
  $colCount = (int) ($block['data']['col_count'] ?? 2);

  // Kumpulkan semua ID anak dari zona yang aktif untuk fitur "Runtuhkan Anak"
  $allChildren = [];
  for ($i = 1; $i <= $colCount; $i++) {
      $allChildren = array_merge($allChildren, $block['data']["col_{$i}_zone"] ?? []);
  }

  // Kelas grid untuk editor saat mode layoutMode === 'single' (semua kolom dijejerkan)
  $editorGridClass = match ($colCount) {
      2 => 'md:grid-cols-2',
      3 => 'md:grid-cols-3',
      4 => 'md:grid-cols-4',
      5 => 'md:grid-cols-5',
      6 => 'md:grid-cols-6',
      default => 'md:grid-cols-2',
  };
@endphp
{{--

				// Jaring pengaman: Jika jumlah kolom dikurangi, pastikan tab aktif kembali ke 1
        this.$watch('$wire.content.{{ $blockId }}.data.col_count', (value) => {
            let maxTab = parseInt(value);
            let currentTabNum = parseInt(this.activeTab.replace('col_', '').replace('_zone', ''));
            if (currentTabNum > maxTab) {
                this.activeTab = 'col_1_zone';
            }
        }); --}}
{{-- 🌟 STATE ALPINE LENGKAP (Termasuk layoutMode & Sinkronisasi) --}}
<div class="is-nested-container bg-white border border-gray-300 rounded-xl shadow-sm relative" x-data="{
    isCollapsed: false,
    childrenCollapsed: false,
    activeTab: 'col_1_zone',
    layoutMode: 'split', // Memastikan tampilan tab (split) berfungsi default
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
                // Hentikan eksekusi jika value kosong/undefined (misal saat blok lain dihapus)
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

  <!-- HEADER -->
  <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-colors group-hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">
    {{-- LEFT HEADER --}}
    <div class="flex items-center gap-2">
      {{-- Tombol Collapse --}}
      <button type="button" x-on:click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
        class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none cursor-pointer">
        <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
      </button>

      {{-- Label Identitas --}}

      <div class="p-1 bg-sage-soft rounded-md">
        <x-dynamic-component :component="'lucide-layout-template'" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>

      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center">
        Kolom Multi
      </span>
    </div>
    {{-- RIGHT HEADER --}}
    {{-- Tambahkan flex-1 dan min-w-0 di sini agar ia berani mengambil sisa ruang tapi juga mau menyusut --}}
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0 transition-all duration-200">
      {{-- 🌟 FITUR UX: Informasi Jumlah Kolom & Anak saat Runtuh --}}
      <div x-show="isCollapsed" x-cloak class="flex-1 min-w-0 px-2 sm:px-4 text-xs text-gray-400 font-medium" title="{{ $colCount }} Kolom berisi total {{ count($allChildren) }} konten">

        {{-- Menampilkan rekapitulasi data dengan format rapi --}}
        <span class="block truncate w-full text-right">
          <span class="font-bold text-gray-500">{{ $colCount }}</span> Kolom &bull;
          <span class="font-bold text-gray-500">{{ count($allChildren) }}</span> Blok
        </span>
      </div>
      <button type="button" x-data="{ childrenCollapsed: false }" x-cloak
        @click.stop="
          if (isCollapsed) {
              // Skenario 1: Makro sedang runtuh. Buka makro, dan siapkan anak dalam mode ringkas (runtuh)
              isCollapsed = false;
              $dispatch('sync-collapse-{{ strtolower($blockId) }}', false);
              childrenCollapsed = true;
              $dispatch('force-collapse-children', {{ json_encode($allChildren) }});
          } else {
              // Skenario 2: Makro sudah terbuka. Berfungsi sebagai toggle normal
              childrenCollapsed = !childrenCollapsed;
              $dispatch(childrenCollapsed ? 'force-collapse-children' : 'force-expand-children', {{ json_encode($allChildren) }});
          }
        "
        class="flex items-center gap-1 px-2 py-0.5 bg-sage-soft/50 text-forest border border-sage-soft/50 rounded text-[9px] font-bold shadow-sm hover:bg-sage-soft transition-colors shrink-0"
        :title="isCollapsed ? 'Buka kolom dan atur susunan blok di dalamnya' : (childrenCollapsed ? 'Buka kembali semua isi kolom' : 'Ciutkan semua isi kolom untuk menyusun urutan')">

        {{-- Ikon berubah dinamis dalam 3 state --}}
        <x-dynamic-component x-show="isCollapsed" component="lucide-list-tree" class="w-3 h-3" stroke-width="3" />
        <x-dynamic-component x-show="!isCollapsed && !childrenCollapsed" component="lucide-chevrons-down-up" class="w-3 h-3" stroke-width="3" />
        <x-dynamic-component x-show="!isCollapsed && childrenCollapsed" component="lucide-chevrons-up-down" class="w-3 h-3" stroke-width="3" />

        {{-- Teks berubah dinamis dalam 3 state --}}
        <span x-text="isCollapsed ? 'Atur Blok' : (childrenCollapsed ? 'Buka Isi' : 'Ciutkan Isi')"></span>
      </button>
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
        {{ $code }}
      </span>
    </div>
  </div>


  {{-- 🌟 AREA KONTEN DROPZONE (Mendukung layoutMode Single/Split) --}}
  <div x-show="!isCollapsed" x-collapse x-cloak :class="layoutMode === 'single' ?
      'grid grid-cols-1 {{ $editorGridClass }} divide-y md:divide-y-0 md:divide-x divide-gray-200' : 'block'"
    class="bg-gray-100 rounded-b-xl">
    <div class="justify-between flex items-center p-4">
      <label class="block text-xs font-semibold text-gray-500 uppercase">Kontrol</label>
      <!-- 🌟 BAGIAN KANAN: Kontrol Jumlah Kolom & Urutan HP (Hanya untuk 2 Kolom) -->
      <div class="flex items-center gap-4">
        {{-- 🌟 KONTROL URUTAN HP (Hanya muncul jika 2 Kolom) --}}
        @if ($colCount === 2)
          <div class="flex items-center gap-2 border-l border-gray-200 pl-4">
            <div class="flex items-center gap-1">
              <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Urutan HP:</span>
              <div class="relative group/tooltip flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-gray-400 cursor-help hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                  </path>
                </svg>
                <div
                  class="absolute bottom-full right-0 md:left-1/2 md:-translate-x-1/2 mb-2 w-48 opacity-0 invisible group-hover/tooltip:opacity-100 group-hover/tooltip:visible transition-all duration-200 z-[100] pointer-events-none">
                  <div class="bg-gray-800 text-white text-[10px] leading-relaxed p-2.5 rounded-lg shadow-xl text-center relative">
                    Mengatur susunan saat dibaca di HP.
                  </div>
                </div>
              </div>
            </div>

            <button type="button" wire:click="$toggle('content.{{ $blockId }}.data.mobile_reverse')"
              class="flex items-center gap-1.5 px-2 py-1 bg-white border border-gray-200 rounded-md text-[10px] font-bold shadow-sm hover:bg-gray-50 focus:outline-none">
              @if ($block['data']['mobile_reverse'] ?? false)
                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                </svg>
                <span class="text-blue-600">Kanan di Atas</span>
              @else
                <svg class="w-3.5 h-3.5 text-foresty" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
                <span class="text-gray-600">Kiri di Atas</span>
              @endif
            </button>
          </div>
        @endif

        {{-- KONTROL JUMLAH KOLOM --}}
        <div class="flex items-center gap-2">
          <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Jumlah Kolom:</label>
          <select wire:model.live="content.{{ $blockId }}.data.col_count" class="text-xs font-bold text-forest border-gray-300 rounded py-1 pl-2 pr-6 shadow-sm focus:ring-forest focus:border-forest bg-white">
            <option value="2">2 Kolom</option>
            <option value="3">3 Kolom</option>
            <option value="4">4 Kolom</option>
            <option value="5">5 Kolom</option>
            <option value="6">6 Kolom</option>
          </select>
        </div>
      </div>
    </div>

    <!-- 🌟 NAVIGASI TAB DINAMIS DENGAN SINKRONISASI -->
    <div x-show="layoutMode === 'split'" x-cloak class="flex px-4 space-x-1 relative overflow-x-auto scrollbar-hide bg-gray-100 ">
      @for ($i = 1; $i <= $colCount; $i++)
        @php $zoneKey = "col_{$i}_zone"; @endphp
        <button type="button" x-on:click="activeTab = '{{ $zoneKey }}'; $dispatch('sync-columns-tab-{{ strtolower($blockId) }}', '{{ $zoneKey }}')"
          :class="activeTab === '{{ $zoneKey }}' ? 'bg-white text-forest border-gray-200 shadow-sm' :
              'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-200/50 border-transparent'"
          class="px-5 py-2 text-xs font-bold transition-all border-t border-l border-r rounded-t-lg flex items-center gap-2 shrink-0">
          Kolom {{ $i }}
          <span class="bg-sage-soft text-foresty px-1.5 py-0.5 rounded text-[9px]">
            {{ count($block['data'][$zoneKey] ?? []) }}
          </span>
        </button>
      @endfor
    </div>

    @for ($i = 1; $i <= $colCount; $i++)
      @php
        $zoneKey = "col_{$i}_zone";
        $zoneBlocks = $block['data'][$zoneKey] ?? [];
      @endphp

      <div x-show="layoutMode === 'single' || activeTab === '{{ $zoneKey }}'" class="flex flex-col h-full" style="display: none;">

        {{-- Penanda kolom saat mode 'single' (semua dijajarkan) --}}
        <div x-show="layoutMode === 'single'" x-cloak class="px-4 py-2 bg-gray-100/50 border-b border-gray-200">
          <span class="text-xs font-bold text-gray-400 uppercase">Kolom {{ $i }}</span>
        </div>

        {{-- Alpine Sortable Dropzone --}}
        <div x-sort x-sort:config="{ group: '{{ $zoneKey }}_{{ $blockId }}', animation: 150, handle: '.child-drag-handle', onEnd: (evt) => updateZoneOrder(evt, '{{ $zoneKey }}') }"
          class="flex-1 p-4 space-y-4 min-h-40 bg-white border-t-2 border border-gray-200 rounded-t-xl transition-colors ">

          @foreach ($zoneBlocks as $childId)
            @if (isset($allContent[$childId]))
              @php $childBlock = $allContent[$childId]; @endphp

              {{-- Wrapper Mikro Blok --}}
              <div id="block-wrapper-{{ $childId }}" data-id="{{ $childId }}" x-sort:item="'{{ $childId }}'" wire:key="child-{{ $childId }}"
                class="relative group rounded-xl hover:ring-2 hover:ring-sage-soft transition-all bg-white shadow-sm border border-gray-200">
                <!-- Drag Handle -->
                <div class="child-drag-handle absolute -left-2 top-3 opacity-0 group-hover:opacity-100 cursor-move text-gray-300 hover:text-blue-500 z-10 bg-white rounded-full p-0.5 shadow-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"></path>
                  </svg>
                </div>
                <!-- Tombol Hapus -->
                <div class="absolute -top-2.5 -right-2.5 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                  <button type="button" wire:click="removeNestedBlock('{{ $blockId }}', '{{ $zoneKey }}', '{{ $childId }}')"
                    class="p-1 bg-red-100 text-red-600 rounded-full hover:bg-red-200 border border-red-200 shadow-sm" title="Hapus Blok Ini">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
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
            <button @click="openDropdown = !openDropdown" @click.outside="openDropdown = false" type="button"
              class="w-full py-2 flex items-center justify-center gap-2 border border-dashed border-gray-300 text-xs font-bold text-gray-400 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 rounded-lg transition-colors">
              + Tambah Konten ke Kolom {{ $i }}
            </button>
            <div x-show="openDropdown" x-cloak class="absolute z-50 bottom-full mb-1 left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden">
              <div class="p-1 flex flex-col gap-0.5">
                <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', 'heading'); openDropdown = false"
                  class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Judul
                  (Heading)</button>
                <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', 'paragraph'); openDropdown = false"
                  class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Paragraf</button>
                <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', 'eyebrow'); openDropdown = false"
                  class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Eyebrow</button>
                <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', 'image'); openDropdown = false"
                  class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Gambar</button>
                <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', 'button-group'); openDropdown = false"
                  class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Grup Tombol</button>
                <button type="button" wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', 'badge-group'); openDropdown = false"
                  class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-blue-50 rounded-md transition text-left">Grup Lencana</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endfor
  </div>
</div>
