@props(['blockId', 'code', 'block', 'allContent' => []])

@php
  $imageUrl = $block['data']['url'] ?? '';
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/image" x-data="{
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

  <!-- HEADER BLOK -->
  <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-colors group-hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">
    <div class="flex items-center gap-2">
      <button type="button" x-on:click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)" class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none cursor-pointer">
        <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
      </button>
      <div class="p-1 bg-sage-soft rounded-md">
        <x-dynamic-component :component="'lucide-image'" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center">Gambar</span>
    </div>
    
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0">
      <div x-show="isCollapsed" x-cloak class="flex-1 min-w-0 px-2 sm:px-4 text-xs text-gray-400 font-medium">
        <span class="block truncate w-full text-right" x-text="'{{ $imageUrl ? 'Gambar Terpilih' : 'Belum Ada Gambar' }}'"></span>
      </div>
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">{{ $code }}</span>
    </div>
  </div>

  <!-- BODY AREA -->
  <div x-show="!isCollapsed" x-collapse x-cloak class="space-y-4 p-2">
    <div class="flex justify-between items-center gap-2">
      <label class="block text-xs font-semibold text-gray-500 uppercase">Padding</label>
      <div class="flex items-center gap-4 px-4 py-0" @click.stop>
        <div class="flex items-center gap-2">
          <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Atas:</label>
          <select wire:model.live="content.{{ $blockId }}.data.padding_top" class="text-xs font-medium text-gray-600 border-gray-300 rounded py-1 shadow-sm">
            <option value="">Default</option>
            <option value="pt-0">0</option><option value="pt-4">Kecil</option><option value="pt-8">Sedang</option><option value="pt-16">Besar</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Bawah:</label>
          <select wire:model.live="content.{{ $blockId }}.data.padding_bottom" class="text-xs font-medium text-gray-600 border-gray-300 rounded py-1 shadow-sm">
            <option value="">Default</option>
            <option value="pb-0">0</option><option value="pb-4">Kecil</option><option value="pb-8">Sedang</option><option value="pb-16">Besar</option>
          </select>
        </div>
      </div>
    </div>

    {{-- INPUT URL MANUAL --}}
    <div>
      <label class="block text-xs font-medium text-gray-600 mb-1.5 flex justify-between items-center">
        <span>URL Gambar Eksternal</span>
        <span class="text-[10px] text-gray-400 font-normal">Opsional (Ambil dari web lain)</span>
      </label>
      <input type="text" 
        wire:model.live.debounce.500ms="content.{{ $blockId }}.data.url" 
        x-on:input="$wire.set('content.{{ $blockId }}.data.media_id', null)"
        placeholder="https://..."
        class="w-full text-sm p-2.5 bg-gray-50 border border-gray-300 rounded-lg shadow-inner focus:ring-foresty focus:border-foresty transition-colors">
    </div>

    {{-- 🌟 AREA PEMANGGIL FILE MANAGER 🌟 --}}
    <div class="relative w-full rounded-lg overflow-hidden border-2 border-dashed border-gray-300 bg-gray-50">
      @if (!empty($imageUrl))
        <div class="relative flex flex-col items-center justify-center p-4 min-h-[144px] bg-gray-100">
          <img src="{{ $imageUrl }}" alt="Pratinjau" class="max-h-56 object-contain rounded shadow-sm">
          
          <div class="absolute inset-0 bg-black/50 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
            <button type="button" 
                x-on:click="$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: 'content.{{ $blockId }}.data' })"
                class="bg-foresty text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md hover:bg-emerald-700 transition-colors flex items-center gap-2">
              <x-dynamic-component component="lucide-image" class="w-4 h-4" /> Ganti Gambar
            </button>
            <button type="button"
                x-on:click="$wire.set('content.{{ $blockId }}.data.url', null); $wire.set('content.{{ $blockId }}.data.media_id', null);"
                class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md hover:bg-red-700 transition-colors">
              Hapus
            </button>
          </div>
        </div>
      @else
        <div class="flex flex-col items-center justify-center py-10 px-4 text-center">
          <x-dynamic-component component="lucide-folder-open" class="w-12 h-12 text-gray-300 mb-3" />
          <p class="text-sm font-bold text-gray-600 mb-4">Belum ada gambar yang dipilih</p>
          <button type="button" 
              x-on:click="$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: 'content.{{ $blockId }}.data' })"
              class="bg-foresty text-white px-5 py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-emerald-800 transition-colors flex items-center gap-2">
            <x-dynamic-component component="lucide-search" class="w-4 h-4" /> Buka File Manager
          </button>
        </div>
      @endif
    </div>

    {{-- INPUT CAPTION --}}
    <div>
      <label class="block text-xs font-medium text-gray-600 mb-1.5">Keterangan Gambar (Caption)</label>
      <input type="text" wire:model.live.debounce.500ms="content.{{ $blockId }}.data.caption.{{ $code }}" placeholder="Tulis keterangan..."
        class="w-full text-sm p-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-foresty focus:border-foresty transition-colors">
    </div>
  </div>
</div>