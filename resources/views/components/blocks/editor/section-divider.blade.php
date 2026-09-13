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
        <x-dynamic-component :component="'lucide-between-horizontal-start'" class="h-4 w-4 text-forest" stroke-width="2.5" />
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
        <span class="block truncate w-full text-right text-xs" {{-- x-text="($wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || '').replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'" --}}>
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
  <div x-show="!isCollapsed" x-collapse x-cloak class="p-4 space-y-5 bg-white rounded-b-xl">

    <div class="w-full border-b border-gray-200 pb-2">
      <label class="block text-xs font-semibold text-gray-500 uppercase">Blok di bawah batas ini akan dibungkus dengan gaya berikut:</label>
    </div>

    {{-- 🌟 WADAH RESPONSIF FLEX-WRAP: Berbaris sejajar, turun jika sempit --}}
    <div class="flex flex-wrap items-start gap-x-12 gap-y-6">

      {{-- 🎨 PILIHAN WARNA LATAR (Color Swatches) --}}
      <div class="flex flex-col gap-2">
        <label class="text-xs font-bold text-foresty uppercase">Warna Latar</label>
        <div class="flex flex-wrap gap-4 mt-1">

          @php
            $bgOptions = [
                ['value' => 'bg-white', 'label' => 'Putih', 'colorClass' => 'bg-white'],
                ['value' => 'bg-paper', 'label' => 'Paper', 'colorClass' => 'bg-paper'],
                ['value' => 'bg-coral', 'label' => 'Koral', 'colorClass' => 'bg-coral'],
                ['value' => 'bg-foresty', 'label' => 'Hutan', 'colorClass' => 'bg-foresty'],
                ['value' => 'bg-sage-soft', 'label' => 'Ijo Sage', 'colorClass' => 'bg-sage-soft'],
            ];
          @endphp

          @foreach ($bgOptions as $bg)
            <label class="cursor-pointer flex flex-col items-center gap-1.5 group">
              <input type="radio" wire:model.live="content.{{ $blockId }}.data.background" value="{{ $bg['value'] }}" class="sr-only peer">

              <div
                class="w-8 h-8 rounded-md {{ $bg['colorClass'] }} border border-gray-200 shadow-sm 
                        peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-foresty 
                        group-hover:scale-110 transition-all duration-200">
              </div>

              <span class="text-[10px] text-gray-500 font-medium peer-checked:text-foresty peer-checked:font-bold transition-colors">
                {{ $bg['label'] }}
              </span>
            </label>
          @endforeach

        </div>
      </div>

      {{-- 📝 PILIHAN WARNA TEKS UTAMA (Typography Swatches) --}}
      <div class="flex flex-col gap-2">
        <label class="text-xs font-bold text-foresty uppercase">Warna Teks Utama</label>
        <div class="flex flex-wrap gap-4 mt-1">

          {{-- Opsi Teks Gelap --}}
          <label class="cursor-pointer flex flex-col items-center gap-1.5 group">
            <input type="radio" wire:model.live="content.{{ $blockId }}.data.text_color" value="text-gray-900" class="sr-only peer">
            <div
              class="w-8 h-8 rounded-md bg-gray-900 border border-gray-200 shadow-sm flex items-center justify-center
                        peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-foresty 
                        group-hover:scale-110 transition-all duration-200">
              <span class="text-white font-serif text-sm font-bold">Aa</span>
            </div>
            <span class="text-[10px] text-gray-500 font-medium peer-checked:text-foresty peer-checked:font-bold">Gelap</span>
          </label>

          {{-- Opsi Teks Terang --}}
          <label class="cursor-pointer flex flex-col items-center gap-1.5 group">
            <input type="radio" wire:model.live="content.{{ $blockId }}.data.text_color" value="text-white" class="sr-only peer">
            <div
              class="w-8 h-8 rounded-md bg-white border border-gray-200 shadow-sm flex items-center justify-center
                        peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-foresty 
                        group-hover:scale-110 transition-all duration-200">
              <span class="text-gray-900 font-serif text-sm font-bold">Aa</span>
            </div>
            <span class="text-[10px] text-gray-500 font-medium peer-checked:text-foresty peer-checked:font-bold">Terang</span>
          </label>

        </div>
      </div>

      {{-- 📏 Pilihan Padding --}}
      <div class="flex flex-col gap-1 border-gray-100">
        <label class="text-xs font-bold text-foresty uppercase">Jarak Luar (Padding)</label>
        <select wire:model.live="content.{{ $blockId }}.data.padding" class="text-xs bg-white text-foresty border-gray-200 rounded focus:ring-foresty py-1.5 shadow-sm max-w-[250px]">
          <option value="py-8 sm:py-12">Sempit (Compact)</option>
          <option value="py-16 sm:py-24">Sedang (Standar)</option>
          <option value="py-24 sm:py-[96px]">Lebar (Spacious)</option>
        </select>
      </div>

    </div>


  </div>

</div>
