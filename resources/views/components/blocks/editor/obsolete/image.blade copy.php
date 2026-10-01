@props (["blockId", "code", "block", "allContent" => []])

@php
  $imageUrl = $block["data"]["url"] ?? "";
@endphp

<div
  class="group/image rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200"
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
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }"
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }"
>
  <!-- HEADER BLOK -->
  <div
    class="flex cursor-pointer items-center justify-between bg-gray-100 p-2 transition-colors select-none group-hover:bg-white"
    :class="isCollapsed
      ? 'rounded-xl'
      : 'rounded-t-xl border-b border-gray-200'"
  >
    <div class="flex items-center gap-2">
      <button
        type="button"
        x-on:click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
        class="hover:bg-sage-soft text-foresty cursor-pointer rounded-full p-1 transition-all duration-200 focus:outline-none"
      >
        <x-dynamic-component
          component="lucide-circle-chevron-down"
          class="text-foresty h-5 w-5 transition-transform duration-200"
          x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'"
        />
      </button>
      <div class="bg-sage-soft rounded-md p-1">
        <x-dynamic-component
          :component="'lucide-image'"
          class="text-forest h-4 w-4"
          stroke-width="2.5"
        />
      </div>
      <span
        class="flex items-center text-xs font-extrabold tracking-widest text-gray-500 uppercase"
        >Gambar</span
      >
    </div>

    <div class="flex min-w-0 flex-1 items-center justify-end gap-2">
      <div
        x-show="isCollapsed"
        x-cloak
        class="min-w-0 flex-1 px-2 text-xs font-medium text-gray-400 sm:px-4"
      >
        <span
          class="block w-full truncate text-right"
          x-text="'{{ $imageUrl ? 'Gambar Terpilih' : 'Belum Ada Gambar' }}'"
        ></span>
      </div>
      <span
        class="text-foresty bg-sage-soft shrink-0 rounded px-1.5 py-0.5 text-xs font-bold uppercase shadow-sm"
        >{{ $lang }}</span
      >
    </div>
  </div>

  <!-- BODY AREA -->
  <div x-show="!isCollapsed" x-collapse x-cloak class="space-y-4 p-2">
    <div class="flex items-center justify-between gap-2">
      <label class="block text-xs font-semibold text-gray-500 uppercase"
        >Padding</label
      >
      <div class="flex items-center gap-4 px-4 py-0" @click.stop>
        <div class="flex items-center gap-2">
          <label
            class="text-[10px] font-bold tracking-wide text-gray-400 uppercase"
            >Atas:</label
          >
          <select
            wire:model.live="content.{{ $blockId }}.data.padding_top"
            class="rounded border-gray-300 py-1 text-xs font-medium text-gray-600 shadow-sm"
          >
            <option value="">Default</option>
            <option value="pt-0">0</option>
            <option value="pt-4">Kecil</option>
            <option value="pt-8">Sedang</option>
            <option value="pt-16">Besar</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <label
            class="text-[10px] font-bold tracking-wide text-gray-400 uppercase"
            >Bawah:</label
          >
          <select
            wire:model.live="content.{{ $blockId }}.data.padding_bottom"
            class="rounded border-gray-300 py-1 text-xs font-medium text-gray-600 shadow-sm"
          >
            <option value="">Default</option>
            <option value="pb-0">0</option>
            <option value="pb-4">Kecil</option>
            <option value="pb-8">Sedang</option>
            <option value="pb-16">Besar</option>
          </select>
        </div>
      </div>
    </div>

    {{-- INPUT URL MANUAL --}}
    <div>
      <label
        class="mb-1.5 block flex items-center justify-between text-xs font-medium text-gray-600"
      >
        <span>URL Gambar Eksternal</span>
        <span class="text-[10px] font-normal text-gray-400"
          >Opsional (Ambil dari web lain)</span
        >
      </label>
      <input
        type="text"
        wire:model.live.debounce.500ms="content.{{ $blockId }}.data.url"
        x-on:input="$wire.set('content.{{ $blockId }}.data.media_id', null)"
        placeholder="https://..."
        class="focus:ring-foresty focus:border-foresty w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm shadow-inner transition-colors"
      />
    </div>

    {{-- 🌟 AREA PEMANGGIL FILE MANAGER 🌟 --}}
    <div
      class="relative w-full overflow-hidden rounded-lg border-2 border-dashed border-gray-300 bg-gray-50"
    >
      @if (!empty($imageUrl))
        <div
          class="relative flex min-h-[144px] flex-col items-center justify-center bg-gray-100 p-4"
        >
          <img
            src="{{ $imageUrl }}"
            alt="Pratinjau"
            class="max-h-56 rounded object-contain shadow-sm"
          />

          <div
            class="absolute inset-0 flex items-center justify-center gap-3 bg-black/50 opacity-0 transition-opacity hover:opacity-100"
          >
            <button
              type="button"
              x-on:click="$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: 'content.{{ $blockId }}.data' })"
              class="bg-foresty flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-bold text-white shadow-md transition-colors hover:bg-emerald-700"
            >
              <x-dynamic-component component="lucide-image" class="h-4 w-4" />
              Ganti Gambar
            </button>
            <button
              type="button"
              x-on:click="$wire.set('content.{{ $blockId }}.data.url', null); $wire.set('content.{{ $blockId }}.data.media_id', null);"
              class="rounded-lg bg-red-500 px-4 py-2 text-sm font-bold text-white shadow-md transition-colors hover:bg-red-700"
            >
              Hapus
            </button>
          </div>
        </div>
      @else
        <div
          class="flex flex-col items-center justify-center px-4 py-10 text-center"
        >
          <x-dynamic-component
            component="lucide-folder-open"
            class="mb-3 h-12 w-12 text-gray-300"
          />
          <p class="mb-4 text-sm font-bold text-gray-600">Belum ada gambar yang dipilih</p>
          <button
            type="button"
            x-on:click="$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: 'content.{{ $blockId }}.data' })"
            class="bg-foresty flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-bold text-white shadow-md transition-colors hover:bg-emerald-800"
          >
            <x-dynamic-component component="lucide-search" class="h-4 w-4" />
            Buka File Manager
          </button>
        </div>
      @endif
    </div>

    {{-- INPUT CAPTION --}}
    <div>
      <label class="mb-1.5 block text-xs font-medium text-gray-600"
        >Keterangan Gambar (Caption)</label
      >
      <input
        type="text"
        wire:model.live.debounce.500ms="content.{{ $blockId }}.data.caption.{{ $lang }}"
        placeholder="Tulis keterangan..."
        class="focus:ring-foresty focus:border-foresty w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm shadow-sm transition-colors"
      />
    </div>
  </div>
</div>
