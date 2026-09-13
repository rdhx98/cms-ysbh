@props(['blockId', 'block', 'code'])

@php
  // Ambil data array buttons dari property $block, defaultkan array kosong jika belum ada
  $buttons = $block['data']['buttons'] ?? [];
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/section" x-data="{
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
}" @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
  @toggle-collapse-all.window="isCollapsed = $event.detail"
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }"
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }">

  <!-- Header -->
  <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-all duration-200 group-hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">

    {{-- HEADER KIRI --}}
    <div class="flex items-center gap-2">
      <button type="button" x-on:click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
        class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none cursor-pointer">
        <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
      </button>

      <div class="p-1 bg-sage-soft rounded-md">
        <x-dynamic-component :component="'lucide-mouse-pointer-click'" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center">
        Grup Tombol (CTA)
      </span>
    </div>

    {{-- RIGHT HEADER --}}
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0">
      <div x-show="isCollapsed" x-cloak class="flex-1 min-w-0 px-2 sm:px-4 text-xs text-gray-400 font-medium text-right">
        <span class="block truncate w-full" x-text="($wire.get('content.{{ $blockId }}.data.buttons') || []).length + ' Tombol Terdaftar'"></span>
      </div>
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
        {{ $code }}
      </span>
    </div>
  </div>

  {{-- BODIES (Repeater Area) --}}
  <div x-show="!isCollapsed" x-collapse x-cloak class="p-4 space-y-4 bg-white rounded-b-xl">

    @foreach ($buttons as $index => $btn)
      <div wire:key="btn-{{ $blockId }}-{{ $index }}" class="p-4 border border-gray-100 bg-gray-50 rounded-xl relative group/item">

        {{-- Tombol Hapus Baris --}}
        <button type="button" @click="$wire.set('content.{{ $blockId }}.data.buttons', $wire.get('content.{{ $blockId }}.data.buttons').filter((_, i) => i !== {{ $index }}))"
          class="absolute top-3 right-3 p-1.5 bg-white text-gray-400 hover:text-coral hover:bg-[#FBE6E6] rounded-md shadow-sm border border-gray-200 transition-colors">
          <x-dynamic-component component="lucide-trash-2" class="w-4 h-4" />
        </button>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          {{-- Input Label --}}
          <div class="flex flex-col gap-1">
            <label class="text-[10px] font-bold text-foresty uppercase tracking-wide">Label Tombol</label>
            <input type="text" wire:model.live="content.{{ $blockId }}.data.buttons.{{ $index }}.label.{{ $code }}" placeholder="Misal: Donasi Sekarang"
              class="text-xs border-gray-200 focus:border-foresty focus:ring-foresty rounded-md w-full py-1.5">
          </div>

          {{-- Input URL --}}
          <div class="flex flex-col gap-1">
            <label class="text-[10px] font-bold text-foresty uppercase tracking-wide">Tautan (URL)</label>
            <input type="text" wire:model.live="content.{{ $blockId }}.data.buttons.{{ $index }}.url" placeholder="Misal: #donasi atau /kontak"
              class="text-xs border-gray-200 focus:border-foresty focus:ring-foresty rounded-md w-full py-1.5">
          </div>

          {{-- Pilih Gaya --}}
          <div class="flex flex-col gap-1 md:col-span-2">
            <label class="text-[10px] font-bold text-foresty uppercase tracking-wide">Gaya Tombol</label>
            <select wire:model.live="content.{{ $blockId }}.data.buttons.{{ $index }}.style" class="text-xs border-gray-200 focus:border-foresty focus:ring-foresty rounded-md w-full py-1.5 bg-white">
              <option value="primary">Aksen Utama (Warna Koral Penuh)</option>
              <option value="outline">Garis Tepi (Teks Foresty, Transparan)</option>
            </select>
          </div>
        </div>
      </div>
    @endforeach

    {{-- Tombol Tambah Item (Inject ke Alpine/Livewire) --}}
    <button type="button"
      @click="
        let arr = $wire.get('content.{{ $blockId }}.data.buttons') || [];
        arr.push({ label: { id: '', en: '' }, url: '#', style: 'primary' });
        $wire.set('content.{{ $blockId }}.data.buttons', arr);
    "
      class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50 hover:bg-sage-soft">
      <x-dynamic-component component="lucide-plus" class="w-4 h-4" />
      Tambah Tombol
    </button>

  </div>
</div>
