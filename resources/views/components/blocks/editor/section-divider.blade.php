@props(['blockId', 'block', 'code'])

<div class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/section" x-data="{
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

  <!-- Header -->
  <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-all duration-200 group-hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">
    {{-- HEADER KIRI --}}
    <div class="flex items-center gap-2">
      {{-- Tombol Collapse --}}
      <button type="button" x-on:click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
        class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none cursor-pointer">
        <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
      </button>

      {{-- Label Identitas --}}
      <div class="p-1 bg-sage-soft rounded-md">
        <x-dynamic-component :component="'lucide-text-align-start'" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center">
        Pemisah Seksi
      </span>
    </div>
    {{-- RIGHT HEADER --}}
    {{-- Tambahkan flex-1 dan min-w-0 di sini agar ia berani mengambil sisa ruang tapi juga mau menyusut --}}
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0">

      {{-- 🌟 FITUR UX: Cuplikan Teks saat Runtuh (Terbatas & Memiliki Tooltip) --}}
      <div x-show="isCollapsed" x-cloak class="flex-1 min-w-0 px-2 sm:px-4 text-xs text-gray-400 font-medium" {{-- 💡 Tooltip Dinamis Alpine.js (Tidak akan mengubah layout/tinggi sama sekali) --}} {{-- :title="($wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || '').replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'" --}}>
        {{-- Jadikan span sebagai block dan berikan truncate untuk memotongnya menjadi 1 baris ketat --}}
        <span class="block truncate w-full text-right" {{-- x-text="($wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || '').replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'" --}}>
          Blok di bawah batas ini akan dibungkus dengan gaya berikut:
        </span>
      </div>

      {{-- Indikator Bahasa --}}
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
        {{ $code }}
      </span>
    </div>
  </div>


  {{-- BODIES --}}
  <div x-show="!isCollapsed" x-collapse x-cloak class="p-4 space-y-4 bg-white rounded-b-xl">
    {{-- Pilih Warna Latar --}}
    <label class="text-xs font-bold text-foresty uppercase border-b border-forest mb-2 w-full">Blok di bawah batas ini akan dibungkus dengan gaya berikut:</label>
    <div class="flex flex-col gap-1">
      <label class="text-xs font-bold text-foresty uppercase">Warna Latar</label>
      <select wire:model.live="content.{{ $blockId }}.data.background" class="text-xs bg-white text-foresty border-foresty rounded focus:ring-sage-soft py-1">
        <option value="bg-paper">Paper</option>
        <option value="bg-white">Putih</option>
        <option value="bg-gray-50">Abu-abu Terang</option>
        <option value="bg-foresty">Foresty (Hijau Gelap)</option>
        <option value="bg-mist">Mist (Abu Kebiruan)</option>
      </select>
    </div>

    {{-- Pilih Warna Teks --}}
    <div class="flex flex-col gap-1">
      <label class="text-fluid-xxs font-bold text-slate-400 uppercase">Warna Teks Utama</label>
      <select wire:model.live="content.{{ $blockId }}.data.text_color" class="text-xs bg-white text-foresty border-foresty rounded focus:ring-foresty py-1">
        <option value="text-gray-900">Gelap (Default)</option>
        <option value="text-white">Terang (Putih)</option>
      </select>
    </div>

    {{-- Pilih Padding --}}
    <div class="flex flex-col gap-1">
      <label class="text-fluid-xxs font-bold text-slate-400 uppercase">Jarak Luar (Padding)</label>
      <select wire:model.live="content.{{ $blockId }}.data.padding" class="text-xs bg-white text-foresty border-foresty rounded focus:ring-foresty py-1">
        <option value="py-8 sm:py-12">Sempit</option>
        <option value="py-16 sm:py-24">Sedang (Standar)</option>
        <option value="py-24 sm:py-[96px]">Lebar</option>
      </select>
    </div>
  </div>
</div>
