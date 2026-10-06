@props ([
  "model", // Wajib: Alamat properti Livewire, cth: 'content.blk_123.data.icon'
  "label" => "Ikon Lucide" // Opsional
])

@php
  $iconsList = config("icons.lucide");
@endphp

<div
  class="flex flex-col gap-1.5"
  x-data="{
    openPicker: false,
    searchQuery: '',
    {{-- 🌟 Mengikat data langsung ke properti model yang dilempar dari luar --}}
    {{-- localIcon: $wire.entangle('{{ $model }}').live --}}
}"
>
  <label
    class="text-foresty text-[10px] font-bold uppercase"
    >{{ $label }}</label
  >

  <div class="relative">
    {{-- Tombol Pemicu Picker --}}
    <button
      type="button"
      @click="openPicker = !openPicker"
      class="hover:border-foresty flex w-full items-center justify-between rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs shadow-sm transition-all duration-200 focus:outline-none"
    >
      <div class="flex items-center gap-2 truncate">
        <span
          class="text-foresty flex h-4 w-4 shrink-0 items-center justify-center"
        >
          {{-- Render Ikon Terpilih --}}
          @foreach ($iconsList as $icon)
            <span
              x-show="localIcon === '{{ $icon }}'"
              x-cloak
              style="display: none"
            >
              <x-dynamic-component
                :component="'lucide-' . $icon"
                class="h-4 w-4"
                stroke-width="2.5"
              />
            </span>
          @endforeach
          {{-- Jika Kosong (Fallback) --}}
          <span x-show="!localIcon" x-cloak>
            <x-dynamic-component
              component="lucide-check-circle"
              class="h-4 w-4 text-gray-400"
              stroke-width="2.5"
            />
          </span>
        </span>
        <span
          class="truncate font-mono text-[10px] text-gray-600 uppercase"
          x-text="localIcon || 'Pilih Ikon'"
        ></span>
      </div>

      <x-dynamic-component
        component="lucide-chevron-down"
        class="h-3.5 w-3.5 shrink-0 text-gray-400"
      />
    </button>

    {{-- Pop-up Daftar Ikon --}}
    <div
      x-show="openPicker"
      @click.outside="openPicker = false"
      x-cloak
      style="display: none"
      class="absolute left-0 z-50 mt-1 flex w-56 flex-col gap-2 rounded-xl border border-gray-200 bg-white p-2.5 shadow-xl"
    >
      {{-- Input Pencarian --}}
      <div class="relative">
        <x-dynamic-component
          component="lucide-search"
          class="absolute top-2 left-2.5 h-3.5 w-3.5 text-gray-400"
        />
        <input
          type="text"
          x-model="searchQuery"
          placeholder="Cari ikon..."
          class="focus:ring-foresty focus:border-foresty w-full rounded-lg border border-gray-200 py-1.5 pr-2 pl-8 text-xs shadow-sm transition-colors"
        />
      </div>

      {{-- Grid Ikon --}}
      <div
        class="grid max-h-48 scrollbar-thin grid-cols-5 gap-1.5 overflow-y-auto p-1"
      >
        @foreach ($iconsList as $icon)
          <button
            type="button"
            x-show="'{{ $icon }}'.includes(searchQuery.toLowerCase())"
            @click="localIcon = '{{ $icon }}'; openPicker = false; searchQuery = ''"
            class="flex items-center justify-center rounded-lg p-2 transition-all duration-200"
            :class="localIcon === '{{ $icon }}' ? 'bg-sage-soft text-foresty border border-foresty shadow-sm scale-105' : 'bg-gray-50 text-gray-500 border border-transparent hover:border-foresty/50 hover:text-foresty'"
            title="{{ $icon }}"
          >
            <span class="flex h-4 w-4 items-center justify-center">
              <x-dynamic-component
                :component="'lucide-' . $icon"
                class="h-4 w-4 shrink-0"
                stroke-width="2"
              />
            </span>
          </button>
        @endforeach
      </div>
    </div>
  </div>
</div>
