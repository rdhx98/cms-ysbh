@props(['blockId', 'block', 'code'])

@php
  $buttons = $block['data']['buttons'] ?? [];

  $stylesList = [
      ['value' => 'primary', 'label' => 'Utama', 'desc' => 'Hijau Solid', 'class' => 'bg-foresty text-white'],
      ['value' => 'secondary', 'label' => 'Sekunder', 'desc' => 'Kuning Solid', 'class' => 'bg-goldy text-foresty'],
      ['value' => 'outline', 'label' => 'Garis Tepi', 'desc' => 'Transparan', 'class' => 'bg-white border-2 border-foresty text-foresty'],
      ['value' => 'text', 'label' => 'Teks Saja', 'desc' => 'Dengan Panah', 'class' => 'bg-gray-100 text-foresty'],
  ];
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/section" {{-- 🌟 TAMBAHKAN: activeTab: 0 --}} x-data="{
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
}"
  @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail" @toggle-collapse-all.window="isCollapsed = $event.detail"
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }"
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }"
  @sync-active-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail">

  <!-- Header Editor -->
  <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-all duration-200 group-hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">
    <div class="flex items-center gap-2">
      <button type="button" x-on:click="isCollapsed = !isCollapsed;  $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed) "
        class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none">
        <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
      </button>
      <div class="p-1 bg-sage-soft rounded-md">
        <x-dynamic-component :component="'lucide-plus-square'" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center">
        Grup Tombol CTA
      </span>
    </div>
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0">
      {{-- 🌟 Lencana Penghitung Otomatis --}}
      <span x-show="isCollapsed" x-cloak class="bg-white border border-gray-200 shadow-sm text-gray-500 px-1.5 py-0.5 rounded-md text-[9px] font-bold tracking-normal"
        x-text="($wire.get('content.{{ $blockId }}.data.buttons') || []).length + ' Tombol'">
      </span>
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
        {{ $code }}
      </span>
    </div>
  </div>

  {{-- BODIES (Repeater Area) --}}
  <div x-show="!isCollapsed" x-collapse x-cloak class="p-4 space-y-5 bg-white rounded-b-xl">

    {{-- Pengaturan Tata Letak Blok (Alignment) --}}
    <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
      <label class="text-[10px] font-bold text-gray-400 uppercase">Posisi Tombol:</label>
      <div class="flex items-center gap-1.5 bg-gray-50 p-1 rounded-lg border border-gray-200 shadow-inner">
        <button type="button" @click="localAlign = 'left'" class="px-3 py-1 text-xs font-bold rounded-md transition-all flex items-center gap-1.5"
          :class="localAlign === 'left' ? 'bg-white shadow text-foresty border border-gray-200/50' : 'text-gray-400 hover:text-foresty hover:bg-gray-200/50'">
          <x-dynamic-component component="lucide-align-left" class="w-3.5 h-3.5" /> Kiri
        </button>
        <button type="button" @click="localAlign = 'center'" class="px-3 py-1 text-xs font-bold rounded-md transition-all flex items-center gap-1.5"
          :class="localAlign === 'center' ? 'bg-white shadow text-foresty border border-gray-200/50' : 'text-gray-400 hover:text-foresty hover:bg-gray-200/50'">
          <x-dynamic-component component="lucide-align-center" class="w-3.5 h-3.5" /> Tengah
        </button>
        <button type="button" @click="localAlign = 'right'" class="px-3 py-1 text-xs font-bold rounded-md transition-all flex items-center gap-1.5"
          :class="localAlign === 'right' ? 'bg-white shadow text-foresty border border-gray-200/50' : 'text-gray-400 hover:text-foresty hover:bg-gray-200/50'">
          <x-dynamic-component component="lucide-align-right" class="w-3.5 h-3.5" /> Kanan
        </button>
      </div>
    </div>

    {{-- 🌟 TAB NAVIGASI TOMBOL 🌟 --}}
    @if (count($buttons) > 0)
      <div class="flex flex-wrap gap-2">
        @foreach ($buttons as $index => $btn)
          <button type="button" x-on:click="activeTab = {{ $index }}; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', {{ $index }})"
            :class="activeTab === {{ $index }} ? 'bg-foresty text-white shadow-md border-transparent' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 hover:border-foresty/50'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-all flex items-center gap-2">

            {{-- Nama Tab (Realtime dari input atau default) --}}
            <span x-text="$wire.get('content.{{ $blockId }}.data.buttons.{{ $index }}.label.{{ $code }}') || 'Tombol {{ $index + 1 }}'" class="max-w-[100px] truncate"></span>

            {{-- Tombol Hapus (Silang) di dalam Tab --}}
            <div
              x-onclick.stop="$wire.set('content.{{ $blockId }}.data.buttons', $wire.get('content.{{ $blockId }}.data.buttons').filter((_, i) => i !== {{ $index }})); activeTab = 0; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', 0);"
              class="p-0.5 rounded hover:bg-red-500 hover:text-white transition-colors ml-1" :class="activeTab === {{ $index }} ? 'text-white/60 hover:bg-white/20' : 'text-gray-400'">
              <x-dynamic-component component="lucide-x" class="w-3 h-3" />
            </div>
          </button>
        @endforeach
      </div>
    @endif

    {{-- AREA KONTEN TAB --}}
    <div class="min-h-[200px]">
      @foreach ($buttons as $index => $btn)
        {{-- 🌟 x-show mengikat ke activeTab --}}
        <div x-show="activeTab === {{ $index }}" x-cloak wire:key="button-{{ $blockId }}-{{ $index }}" class="p-4 border border-gray-100 bg-gray-50 rounded-xl" x-data="{ localStyle: $wire.entangle('content.{{ $blockId }}.data.buttons.{{ $index }}.style').live }">

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-5">

            {{-- KOLOM KIRI: Teks & Tautan --}}
            <div class="space-y-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Label Tombol</label>
                <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.buttons.{{ $index }}.label.{{ $code }}" placeholder="Misal: Hubungi Kami"
                  class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Tautan (URL)</label>
                <div class="flex items-center gap-1">
                  <input type="text" wire:model.live="content.{{ $blockId }}.data.buttons.{{ $index }}.url" placeholder="#section atau https://..."
                    class="w-full text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
                  <button type="button" @click="$dispatch('buka-modal-link', { target: 'content.{{ $blockId }}.data.buttons.{{ $index }}.url' })"
                    class="p-1.5 border border-gray-200 bg-white hover:bg-sage-soft text-gray-400 hover:text-foresty rounded-md shadow-sm transition-colors cursor-pointer shrink-0">
                    <x-dynamic-component component="lucide-search" class="w-4 h-4" stroke-width="2.5" />
                  </button>
                </div>
              </div>
            </div>

            {{-- KOLOM KANAN: Visual --}}
            <div class="space-y-2">
              <label class="text-[10px] font-bold text-foresty uppercase">Gaya Visual Tombol</label>
              <div class="grid grid-cols-2 gap-2">
                @foreach ($stylesList as $style)
                  <button type="button" @click="localStyle = '{{ $style['value'] }}'" class="flex flex-col items-start p-2 rounded-lg border transition-all text-left bg-white"
                    :class="localStyle === '{{ $style['value'] }}' ? 'ring-2 ring-foresty ring-offset-1 border-transparent shadow-sm' : 'border-gray-200 hover:border-foresty/50'">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="w-3 h-3 rounded-full {{ $style['class'] }} {{ $style['value'] === 'outline' ? 'border' : '' }}"></span>
                      <span class="text-[11px] font-bold text-gray-700 leading-none">{{ $style['label'] }}</span>
                    </div>
                    <span class="text-[9px] text-gray-400 pl-5">{{ $style['desc'] }}</span>
                  </button>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      @endforeach

      @if (count($buttons) === 0)
        <div class="p-8 border-2 border-dashed border-gray-200 rounded-xl text-center flex flex-col items-center justify-center bg-gray-50">
          <x-dynamic-component component="lucide-mouse-pointer-click" class="w-8 h-8 text-gray-300 mb-2" />
          <p class="text-xs font-bold text-gray-400 uppercase">Belum ada tombol</p>
          <p class="text-[10px] text-gray-400 mt-1">Klik tombol tambah di bawah untuk membuat CTA.</p>
        </div>
      @endif
    </div>

    {{-- 🌟 PRATINJAU GABUNGAN 🌟 --}}
    <div class="pt-5 border-t border-dashed border-gray-200">
      <span class="block text-[10px] font-bold text-gray-400 uppercase mb-3">Pratinjau Grup Tombol:</span>

      <div class="flex flex-wrap gap-4 transition-all duration-300"
        :class="{
            'justify-start': localAlign === 'left',
            'justify-center': localAlign === 'center',
            'justify-end': localAlign === 'right'
        }">

        @foreach ($buttons as $index => $btn)
          <div x-data="{
              style: $wire.entangle('content.{{ $blockId }}.data.buttons.{{ $index }}.style').live,
              label: $wire.entangle('content.{{ $blockId }}.data.buttons.{{ $index }}.label.{{ $code }}').live
          }" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-full font-bold text-[15px] transition-all duration-300 pointer-events-none"
            :class="{
                'bg-foresty text-white shadow-[0_8px_20px_-6px_rgba(6,79,59,0.5)]': style === 'primary',
                'bg-goldy text-foresty shadow-[0_8px_20px_-6px_rgba(235,204,38,0.5)]': style === 'secondary',
                'bg-white border-2 border-foresty text-foresty shadow-sm': style === 'outline',
                'bg-transparent text-foresty p-0 !px-0': style === 'text'
            }">
            <span x-text="label || 'Ketik label...'"></span>
            <span x-show="style === 'text' || style === 'outline'" x-cloak>
              <x-dynamic-component component="lucide-arrow-right" class="w-4 h-4" stroke-width="2.5" />
            </span>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Tombol Tambah --}}
    <button type="button"
      x-on:click="
        let arr = $wire.get('content.{{ $blockId }}.data.buttons') || [];
        arr.push({ label: { id: '', en: '' }, url: '#', style: 'primary' });
        $wire.set('content.{{ $blockId }}.data.buttons', arr);
        {{-- 🌟 PERBAIKAN: Dispatch juga saat tab otomatis fokus ke anak baru --}}
        setTimeout(() => {
            activeTab = arr.length - 1;
            $dispatch('sync-active-tab-{{ strtolower($blockId) }}', activeTab);
        }, 100);
    "
      class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50 hover:bg-sage-soft">
      <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" />
      Tambah Tombol
    </button>

  </div>
</div>
