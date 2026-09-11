@props([
    'blockId',
    'code',
    'block',
    'allContent' => [], // Tambahkan fallback array kosong agar tidak error jika dipanggil di root
])

<div class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/heading" x-data="{
    isCollapsed: false,
    init() {
        // 1. Saat dirender ulang, periksa apakah blok ini punya ingatan status
        window.blockCollapseState = window.blockCollapseState || {};
        if (window.blockCollapseState['{{ $blockId }}'] !== undefined) {
            this.isCollapsed = window.blockCollapseState['{{ $blockId }}'];
        }

        // 2. Setiap kali status berubah, titipkan ingatannya ke memori peramban
        this.$watch('isCollapsed', (value) => {
            window.blockCollapseState['{{ $blockId }}'] = value;
        });
    }
}" @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
  @toggle-collapse-all.window="isCollapsed = $event.detail"
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }"
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }">

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
        <x-dynamic-component :component="'lucide-heading'" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>

      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center">
        Judul (Heading)
      </span>
    </div>
    {{-- RIGHT HEADER --}}
    {{-- Tambahkan flex-1 dan min-w-0 di sini agar ia berani mengambil sisa ruang tapi juga mau menyusut --}}
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0">

      {{-- 🌟 FITUR UX: Cuplikan Teks saat Runtuh (Terbatas & Memiliki Tooltip) --}}
      <div x-show="isCollapsed" x-cloak class="flex-1 min-w-0 px-2 sm:px-4 text-xs text-gray-400 font-medium" {{-- 💡 Tooltip Dinamis Alpine.js (Tidak akan mengubah layout/tinggi sama sekali) --}}
        :title="($wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || '').replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'">

        {{-- Jadikan span sebagai block dan berikan truncate untuk memotongnya menjadi 1 baris ketat --}}
        <span class="block truncate w-full text-right"
          x-text="($wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || '').replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'">
        </span>
      </div>

      {{-- Indikator Bahasa --}}
      {{-- Tambahkan shrink-0 agar kotak bahasa ini TIDAK IKUT tergencet saat teks di sebelahnya sangat panjang --}}
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
        {{ $code }}
      </span>
    </div>
  </div>

  {{-- Collapsed wrapper --}}
  <div x-show="!isCollapsed" x-collapse x-cloak class="p-4 space-y-4 bg-gray-50/50">
    <div class="flex justify-between items-center gap-2 ">
      <label class="block text-xs font-semibold text-gray-500 uppercase">Level Heading</label>
      <select wire:model="content.{{ $blockId }}.data.level" class="text-xs border border-gray-300 rounded-md py-0 px-2 bg-white text-gray-600 " @click.stop>
        <option value="h1">Heading 1</option>
        <option value="h2">Heading 2</option>
        <option value="h3">Heading 3</option>
      </select>
    </div>

    <x-tiptap :block-type="$block['type']" wire:model="content.{{ $blockId }}.data.text.{{ $code }}" placeholder="Blok Heading..." />

  </div>
</div>
