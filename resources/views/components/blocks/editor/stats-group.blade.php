@props(['blockId', 'block', 'code'])

@php
  $stats = $block['data']['stats'] ?? [];
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/section" x-data="{
    isCollapsed: false,
    activeTab: 0,
    localAlign: $wire.entangle('content.{{ $blockId }}.data.align').live || 'left',
    init() {
        window.blockCollapseState = window.blockCollapseState || {};
        if (window.blockCollapseState['{{ $blockId }}'] !== undefined) {
            this.isCollapsed = window.blockCollapseState['{{ $blockId }}'];
        }
        this.$watch('isCollapsed', (value) => {
            window.blockCollapseState['{{ $blockId }}'] = value;
        });
    }
}" @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
  @toggle-collapse-all.window="isCollapsed = $event.detail"
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }"
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }"
  @sync-active-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail">

  <!-- Header Editor -->
  <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-all duration-200 group-hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">
    <div class="flex items-center gap-2">
      <button type="button" @click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
        class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none">
        <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
      </button>
      <div class="p-1 bg-sage-soft rounded-md">
        <x-dynamic-component component="lucide-trending-up" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center gap-2">
        Grup Statistik
        {{-- Lencana Penghitung --}}
        <span class="bg-white border border-gray-200 shadow-sm text-gray-500 px-1.5 py-0.5 rounded-md text-[9px] font-bold tracking-normal"
          x-text="($wire.get('content.{{ $blockId }}.data.stats') || []).length + ' Item'">
        </span>
      </span>
    </div>
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0">
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
        {{ $code }}
      </span>
    </div>
  </div>

  {{-- Repeater Area --}}
  <div x-show="!isCollapsed" x-collapse x-cloak class="p-4 space-y-5 bg-white rounded-b-xl">

    {{-- Pengaturan Tata Letak Blok --}}
    <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
      <label class="text-[10px] font-bold text-gray-400 uppercase">Posisi Teks:</label>
      <div class="flex items-center gap-1.5 bg-gray-50 p-1 rounded-lg border border-gray-200 shadow-inner">
        <button type="button" @click="localAlign = 'left'" class="px-3 py-1 text-xs font-bold rounded-md transition-all duration-300 flex items-center gap-1.5"
          :class="localAlign === 'left' ? 'bg-white shadow text-foresty border border-gray-200/50' : 'text-gray-400 hover:text-foresty hover:bg-gray-200/50'">
          <x-dynamic-component component="lucide-align-left" class="w-3.5 h-3.5" /> Kiri
        </button>
        <button type="button" @click="localAlign = 'center'" class="px-3 py-1 text-xs font-bold rounded-md transition-all duration-300 flex items-center gap-1.5"
          :class="localAlign === 'center' ? 'bg-white shadow text-foresty border border-gray-200/50' : 'text-gray-400 hover:text-foresty hover:bg-gray-200/50'">
          <x-dynamic-component component="lucide-align-center" class="w-3.5 h-3.5" /> Tengah
        </button>
        <button type="button" @click="localAlign = 'right'" class="px-3 py-1 text-xs font-bold rounded-md transition-all duration-300 flex items-center gap-1.5"
          :class="localAlign === 'right' ? 'bg-white shadow text-foresty border border-gray-200/50' : 'text-gray-400 hover:text-foresty hover:bg-gray-200/50'">
          <x-dynamic-component component="lucide-align-right" class="w-3.5 h-3.5" /> Kanan
        </button>
      </div>
    </div>

    {{-- TAB NAVIGASI STATISTIK --}}
    @if (count($stats) > 0)
      <div class="flex flex-wrap gap-2">
        @foreach ($stats as $index => $stat)
          <button type="button" @click="activeTab = {{ $index }}; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', {{ $index }})"
            :class="activeTab === {{ $index }} ? 'bg-foresty text-white shadow-md border-transparent' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 hover:border-foresty/50'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-all duration-300 flex items-center gap-2">

            <span x-text="$wire.get('content.{{ $blockId }}.data.stats.{{ $index }}.value.{{ $code }}') || 'Stat {{ $index + 1 }}'" class="max-w-[80px] truncate"></span>

            <div
              @click.stop="$wire.set('content.{{ $blockId }}.data.stats', $wire.get('content.{{ $blockId }}.data.stats').filter((_, i) => i !== {{ $index }})); activeTab = 0; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', 0);"
              class="p-0.5 rounded hover:bg-red-500 hover:text-white transition-colors ml-1" :class="activeTab === {{ $index }} ? 'text-white/60 hover:bg-white/20' : 'text-gray-400'">
              <x-dynamic-component component="lucide-x" class="w-3 h-3" />
            </div>
          </button>
        @endforeach
      </div>
    @endif

    {{-- AREA KONTEN TAB --}}
    <div class="min-h-[120px]">
      @foreach ($stats as $index => $stat)
        <div x-show="activeTab === {{ $index }}" x-cloak wire:key="stat-tab-{{ $blockId }}-{{ $index }}" class="p-4 border border-gray-100 bg-gray-50 rounded-xl">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Input Nilai/Angka --}}
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-foresty uppercase">Angka (Value)</label>
              <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.stats.{{ $index }}.value.{{ $code }}" placeholder="Misal: 2014, 76, 10M+"
                class="text-xs font-bold border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm transition-colors">
            </div>
            {{-- Input Label/Keterangan --}}
            <div class="flex flex-col gap-1.5 md:border-l border-gray-200 md:pl-6">
              <label class="text-[10px] font-bold text-foresty uppercase">Keterangan (Label)</label>
              <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.stats.{{ $index }}.label.{{ $code }}" placeholder="Misal: Desa dampingan"
                class="text-xs border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm transition-colors">
            </div>
          </div>
        </div>
      @endforeach

      @if (count($stats) === 0)
        <div class="p-8 border-2 border-dashed border-gray-200 rounded-xl text-center flex flex-col items-center justify-center bg-gray-50">
          <x-dynamic-component component="lucide-bar-chart-2" class="w-8 h-8 text-gray-300 mb-2" />
          <p class="text-xs font-bold text-gray-400 uppercase">Belum ada statistik</p>
          <p class="text-[10px] text-gray-400 mt-1">Tambahkan angka pencapaian untuk membangun kepercayaan.</p>
        </div>
      @endif
    </div>

    {{-- PRATINJAU GABUNGAN --}}
    <div class="pt-5 border-t border-dashed border-gray-200">
      <span class="block text-[10px] font-bold text-gray-400 uppercase mb-3">Pratinjau Grup Statistik:</span>

      {{-- Pembungkus utama persis seperti HTML HTML Anda --}}
      <div class="flex gap-7 mt-4 flex-wrap transition-all duration-500 ease-out"
        :class="{
            'justify-start': localAlign === 'left',
            'justify-center text-center': localAlign === 'center',
            'justify-end text-right': localAlign === 'right'
        }">

        @foreach ($stats as $index => $stat)
          <div x-data="{
              val: $wire.entangle('content.{{ $blockId }}.data.stats.{{ $index }}.value.{{ $code }}').live,
              lbl: $wire.entangle('content.{{ $blockId }}.data.stats.{{ $index }}.label.{{ $code }}').live
          }" class="transition-all duration-300">
            {{-- Typography dari template Tailwind Anda --}}
            <b class="block font-display text-[26px] text-foresty" x-text="val || '0'"></b>
            <span class="text-[13px] text-ink-soft" x-text="lbl || 'Teks keterangan...'"></span>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Tombol Tambah --}}
    <button type="button"
      @click="
        let arr = $wire.get('content.{{ $blockId }}.data.stats') || [];
        arr.push({ value: { id: '', en: '' }, label: { id: '', en: '' } });
        $wire.set('content.{{ $blockId }}.data.stats', arr);
        setTimeout(() => {
            activeTab = arr.length - 1;
            $dispatch('sync-active-tab-{{ strtolower($blockId) }}', activeTab);
        }, 100);
    "
      class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50 hover:bg-sage-soft">
      <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" />
      Tambah Statistik
    </button>

  </div>
</div>
