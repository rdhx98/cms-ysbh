@props ([
  "blockId",
  "block",
  "activeLocales" => [],
  "allContent" => [],
])

@php
  $colorsList = config("cms.design.bg_colors", []);
  // $iconsList = config("cms.lucide", []);
  // $marginsList = config("cms.design.margin_bottom", []);
@endphp

<x-blocks.editor.wrapper :block-id="$blockId" :block="$block">
  <x-slot:title>
    <div
      class="bg-sage-soft text-foresty flex h-5 w-5 items-center justify-center rounded-sm shadow-sm"
    >
      <x-dynamic-component
        component="lucide-between-horizontal-start"
        class="h-3.5 w-3.5"
        stroke-width="2.5"
      />
    </div>

    Pemisah Seksi
  </x-slot:title>

  <x-slot:snippet>
    <label class="text-xxs block font-semibold text-gray-500 uppercase"
      >Blok di bawah batas ini akan dibungkus dengan gaya berikut:
    </label>
  </x-slot:snippet>

  <x-slot:settings>
    <div
      x-show="isCollapsed"
      x-transition.opacity.duration.300ms
      x-cloak
      class="flex items-center justify-center gap-1"
    >
      {{-- latar --}}
      <div
        class="flex shrink-0 items-center gap-1.5 sm:border-r sm:border-gray-200 sm:pr-3"
        title="Warna Latar Seksi Ini"
      >
        <span
          class="hidden text-[9px] font-extrabold tracking-wider text-gray-400 uppercase sm:block"
        >
          Latar
        </span>
        <!-- Bungkus Cincin (Ring) agar warna putih tetap terlihat -->
        <div
          class="flex items-center justify-center rounded-full bg-white p-[3px] shadow-sm ring-1 ring-gray-400"
        >
          <div
            class="h-3.5 w-3.5 rounded-md border border-black/5 transition-colors duration-300"
            {{-- Mengambil nilai warna background secara langsung dan reaktif dari Livewire --}}
            x-bind:class="$wire.get('content.{{ $blockId }}.data.background') || 'bg-white'"
          ></div>
        </div>
      </div>

      <div class="h-3.5 w-px bg-gray-200"></div>

      {{-- warna teks --}}
      <div
        title="Warna Teks Utama"
        class="flex h-4 w-4 items-center justify-center rounded-[3px] border border-gray-200 transition-colors"
        x-bind:class="$wire.get('content.{{ $blockId }}.data.text_color') === 'text-white' ? 'bg-white' : 'bg-gray-900'"
      >
        <span
          class="font-serif text-[8px] leading-none font-bold"
          x-bind:class="$wire.get('content.{{ $blockId }}.data.text_color') === 'text-white' ? 'text-gray-900' : 'text-white'"
        >
          Aa
        </span>
      </div>

      <!-- Garis Pemisah Vertikal -->
      <div class="h-3.5 w-px bg-gray-200"></div>

      {{-- padding --}}
      <div
        title="Jarak Padding"
        class="flex items-center justify-center text-gray-500"
      >
        <!-- Sempit (4 Baris) -->
        <div
          x-show="$wire.get('content.{{ $blockId }}.data.padding') === 'py-8 sm:py-12'"
          x-cloak
        >
          <x-dynamic-component component="lucide-rows-4" class="h-3.5 w-3.5" />
        </div>
        <!-- Sedang / Default (3 Baris) -->
        <div
          x-show="!$wire.get('content.{{ $blockId }}.data.padding') || $wire.get('content.{{ $blockId }}.data.padding') === 'py-16 sm:py-24'"
          x-cloak
        >
          <x-dynamic-component component="lucide-rows-3" class="h-3.5 w-3.5" />
        </div>
        <!-- Lebar (2 Baris) -->
        <div
          x-show="$wire.get('content.{{ $blockId }}.data.padding') === 'py-24 sm:py-[96px]'"
          x-cloak
        >
          <x-dynamic-component component="lucide-rows-2" class="h-3.5 w-3.5" />
        </div>
      </div>
    </div>
  </x-slot:settings>

  <!-- CONTROLS -->
  <div
    class="m-2 flex flex-wrap items-start justify-start gap-4 rounded-lg border border-gray-100 bg-gray-50/50 p-3 shadow-inner"
  >
    <!-- TEMPLATE COLOR PICKER GLOBAL (Grup Tombol Warna) -->
    <div class="flex flex-col gap-1.5">
      <label class="text-xxs font-bold text-gray-700 uppercase">Warna</label>
      <div class="flex flex-wrap gap-3 pb-6">
        @foreach ($colorsList as $color)
          <!-- 1. Gunakan 'group/btn' alih-alih 'group' biasa -->
          <div class="group/btn relative flex flex-col items-center">
            {{-- wire:model.live="content.{{ $blockId }}.data.background" --}}
            <button
              type="button"
              x-on:click="$wire.set('content.{{ $blockId }}.data.background', '{{ $color['value'] }}')"
              class="border border-gray-200 hover:ring-forest {{ $color['value'] }} h-6 w-6 rounded-md shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none"
              x-bind:class="($wire.content['{{ $blockId }}']?.data?.background ?? 'bg-paper').toLowerCase() === '{{ strtolower($color['value']) }}' ? 
          'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
            ></button>

            <!-- 2. Ubah 'group-hover' menjadi 'group-hover/btn' -->
            <span
              class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
              x-bind:class="($wire.content['{{ $blockId }}']?.data?.background ?? 'bg-paper').toLowerCase() === '{{ strtolower($color['value']) }}' ? 
          'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
            >
              {{ $color["name"] }}
            </span>
          </div>
        @endforeach
      </div>
    </div>

    <!-- TEXT COLOR -->
    <div class="flex flex-col gap-1.5">
      <label class="text-xxs font-bold text-gray-700 uppercase"
        >Warna Teks</label
      >
      <div class="flex flex-wrap gap-3 pb-6">
        <div class="group/btn relative flex flex-col items-center">
          {{-- wire:model.live="content.{{ $blockId }}.data.background" --}}
          <button
            type="button"
            x-on:click="$wire.set('content.{{ $blockId }}.data.text_color', 'text-charcoal')"
            class="hover:ring-forest flex h-6 w-6 items-center justify-center rounded-md border border-gray-200 bg-white text-white shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none"
            x-bind:class="($wire.content['{{ $blockId }}']?.data?.text_color ?? 'text-charcoal').toLowerCase() === 'text-charcoal' ? 
          'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
          >
            <span class="text-charcoal font-serif text-sm font-bold">Aa</span>
          </button>

          <!-- 2. Ubah 'group-hover' menjadi 'group-hover/btn' -->
          <span
            class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
            x-bind:class="($wire.content['{{ $blockId }}']?.data?.text_color ?? 'text-charcoal').toLowerCase() === 'text-charcoal' ? 
          'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
          >
            Gelap
          </span>
        </div>
        <div class="group/btn relative flex flex-col items-center">
          {{-- wire:model.live="content.{{ $blockId }}.data.background" --}}
          <button
            type="button"
            x-on:click="$wire.set('content.{{ $blockId }}.data.text_color', 'text-white')"
            class="hover:ring-forest bg-charcoal flex h-6 w-6 items-center justify-center rounded-md border border-gray-200 text-white shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none"
            x-bind:class="($wire.content['{{ $blockId }}']?.data?.text_color ?? 'text-charcoal').toLowerCase() === 'text-white' ? 
          'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
          >
            <span class="font-serif text-sm font-bold text-white">Aa</span>
          </button>

          <!-- 2. Ubah 'group-hover' menjadi 'group-hover/btn' -->
          <span
            class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
            x-bind:class="($wire.content['{{ $blockId }}']?.data?.text_color ?? 'text-charcoal').toLowerCase() === 'text-white' ? 
          'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
          >
            Gelap
          </span>
        </div>
      </div>
    </div>

    <!-- PADDINGS -->
    <div class="flex flex-col gap-1.5">
      <label class="text-xxs font-bold text-gray-700 uppercase">
        Jarak Luar (Padding)
      </label>

      <div
        class="inline-flex w-fit items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-100/80 p-0.75 shadow-inner"
      >
        {{-- Opsi 1: Sempit --}}
        <label class="group relative cursor-pointer">
          <input
            type="radio"
            wire:model.live="content.{{ $blockId }}.data.padding"
            value="py-8 sm:py-12"
            class="peer sr-only"
          />
          <div
            class="peer-checked:text-foresty text-xxs flex items-center gap-1 rounded-sm px-1 py-0.75 font-bold text-gray-400 transition-all duration-200 peer-checked:bg-white peer-checked:shadow-sm hover:text-gray-600"
          >
            <x-dynamic-component
              component="lucide-rows-4"
              class="h-4 w-4 transition-transform group-hover:scale-110"
            />
            Tight
          </div>
        </label>

        {{-- Opsi 2: Sedang --}}
        <label class="group relative cursor-pointer">
          <input
            type="radio"
            wire:model.live="content.{{ $blockId }}.data.padding"
            value="py-16 sm:py-24"
            class="peer sr-only"
          />
          <div
            class="peer-checked:text-foresty text-xxs flex items-center gap-1 rounded-sm px-1 py-0.75 font-bold text-gray-400 transition-all duration-200 peer-checked:bg-white peer-checked:shadow-sm hover:text-gray-600"
          >
            <x-dynamic-component
              component="lucide-rows-3"
              class="h-4 w-4 transition-transform group-hover:scale-110"
            />
            Normal
          </div>
        </label>

        {{-- Opsi 3: Lebar --}}
        <label class="group relative cursor-pointer">
          <input
            type="radio"
            wire:model.live="content.{{ $blockId }}.data.padding"
            value="py-24 sm:py-[96px]"
            class="peer sr-only"
          />
          <div
            class="peer-checked:text-foresty text-xxs flex items-center gap-1 rounded-sm px-1 py-0.75 font-bold text-gray-400 transition-all duration-200 peer-checked:bg-white peer-checked:shadow-sm hover:text-gray-600"
          >
            <x-dynamic-component
              component="lucide-rows-2"
              class="h-4 w-4 transition-transform group-hover:scale-110"
            />
            Wide
          </div>
        </label>
      </div>
    </div>
  </div>

  {{-- BODIES --}}
  {{-- <div class="space-y-2 rounded-b-xl bg-white p-4">
    {{-- 🌟 WADAH RESPONSIF FLEX-WRAP: Berbaris sejajar, turun jika sempit --}
    <div class="flex flex-wrap items-start gap-x-12 gap-y-3">
      {{-- 🎨 PILIHAN WARNA LATAR (Color Swatches) --}
      <div class="flex flex-col gap-1.5">
        <label class="text-foresty text-xs font-bold uppercase"
          >Warna Latar</label
        >
        <div class="mt-1 flex flex-wrap gap-4">
          @php
            $bgOptions = [
              ["value" => "bg-white", "label" => "Putih", "colorClass" => "bg-white"],
              ["value" => "bg-paper", "label" => "Paper", "colorClass" => "bg-paper"],
              ["value" => "bg-coral", "label" => "Koral", "colorClass" => "bg-coral"],
              ["value" => "bg-foresty", "label" => "Hutan", "colorClass" => "bg-foresty"],
              ["value" => "bg-mist", "label" => "Kabut", "colorClass" => "bg-mist"],
              [
                "value" => "bg-sage-soft",
                "label" => "Ijo Sage",
                "colorClass" => "bg-sage-soft",
              ],
            ];
          @endphp

          @foreach ($bgOptions as $bg)
            <label
              class="group flex cursor-pointer flex-col items-center gap-1.5"
            >
              <input
                type="radio"
                wire:model.live="content.{{ $blockId }}.data.background"
                value="{{ $bg['value'] }}"
                class="peer sr-only"
              />

              <div
                class="w-6 h-6 rounded-md {{ $bg['colorClass'] }} border border-gray-200 shadow-sm 
                        peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-foresty 
                        hover:scale-115 transition-all duration-200"
              ></div>

              <span
                class="peer-checked:text-foresty text-[10px] font-medium text-gray-500 transition-colors peer-checked:font-bold"
              >
                {{ $bg["label"] }}
              </span>
            </label>
          @endforeach
        </div>
      </div>

      {{-- 📝 PILIHAN WARNA TEKS UTAMA (Typography Swatches) --}
      <div class="flex flex-col gap-1.5">
        <label class="text-foresty text-xs font-bold uppercase"
          >Warna Teks Utama</label
        >
        <div class="mt-1 flex flex-wrap gap-4">
          {{-- Opsi Teks Gelap --}
  <label class="group flex cursor-pointer flex-col items-center gap-1.5">
    <input
      type="radio"
      wire:model.live="content.{{ $blockId }}.data.text_color"
      value="text-gray-900"
      class="peer sr-only"
    />
    <div
      class="peer-checked:ring-foresty flex h-6 w-6 items-center justify-center rounded-md border border-gray-200 bg-gray-900 shadow-sm transition-all duration-200 group-hover:scale-110 peer-checked:ring-2 peer-checked:ring-offset-2"
    >
      <span class="font-serif text-sm font-bold text-white">Aa</span>
    </div>
    <span
      class="peer-checked:text-foresty text-[10px] font-medium text-gray-500 peer-checked:font-bold"
      >Gelap</span
    >
  </label>

  {{-- Opsi Teks Terang --}
  <label class="group flex cursor-pointer flex-col items-center gap-1.5">
    <input
      type="radio"
      wire:model.live="content.{{ $blockId }}.data.text_color"
      value="text-white"
      class="peer sr-only"
    />
    <div
      class="peer-checked:ring-foresty flex h-6 w-6 items-center justify-center rounded-md border border-gray-200 bg-white shadow-sm transition-all duration-200 group-hover:scale-110 peer-checked:ring-2 peer-checked:ring-offset-2"
    >
      <span class="font-serif text-sm font-bold text-gray-900">Aa</span>
    </div>
    <span
      class="peer-checked:text-foresty text-[10px] font-medium text-gray-500 peer-checked:font-bold"
      >Terang</span
    >
  </label>
  </div>
  </div>

  {{-- 📏 Pilihan Padding --}}
  {{-- <div class="flex flex-col gap-1 border-gray-100">
        <label class="text-foresty text-xs font-bold uppercase"
          >Jarak Luar (Padding)</label
        >
        <select
          wire:model.live="content.{{ $blockId }}.data.padding"
          class="text-foresty focus:ring-foresty max-w-[250px] rounded border-gray-200 bg-white py-1.5 text-xs shadow-sm"
        >
          <option value="py-8 sm:py-12">Sempit (Compact)</option>
          <option value="py-16 sm:py-24">Sedang (Standar)</option>
          <option value="py-24 sm:py-[96px]">Lebar (Spacious)</option>
        </select>
      </div> --}}
  {{-- 📏 Pilihan Padding (Segmented Control) --}
      <div class="flex flex-col gap-2">
        <label class="text-foresty text-xs font-bold uppercase">
          Jarak Luar (Padding)
        </label>

        <div
          class="inline-flex w-fit items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-100/80 p-0.5 shadow-inner"
        >
          {{-- Opsi 1: Sempit --}
          <label class="group relative cursor-pointer">
            <input
              type="radio"
              wire:model.live="content.{{ $blockId }}.data.padding"
              value="py-8 sm:py-12"
              class="peer sr-only"
            />
            <div
              class="peer-checked:text-foresty text-xxs flex items-center gap-1.5 rounded-md px-3 py-1.5 font-bold text-gray-400 transition-all duration-200 peer-checked:bg-white peer-checked:shadow-sm hover:text-gray-600"
            >
              <x-dynamic-component
                component="lucide-rows-4"
                class="h-4 w-4 transition-transform group-hover:scale-110"
              />
              SEMPIT
            </div>
          </label>

          {{-- Opsi 2: Sedang --}
          <label class="group relative cursor-pointer">
            <input
              type="radio"
              wire:model.live="content.{{ $blockId }}.data.padding"
              value="py-16 sm:py-24"
              class="peer sr-only"
            />
            <div
              class="peer-checked:text-foresty text-xxs flex items-center gap-1.5 rounded-md px-3 py-1.5 font-bold text-gray-400 transition-all duration-200 peer-checked:bg-white peer-checked:shadow-sm hover:text-gray-600"
            >
              <x-dynamic-component
                component="lucide-rows-3"
                class="h-4 w-4 transition-transform group-hover:scale-110"
              />
              SEDANG
            </div>
          </label>

          {{-- Opsi 3: Lebar --}
          <label class="group relative cursor-pointer">
            <input
              type="radio"
              wire:model.live="content.{{ $blockId }}.data.padding"
              value="py-24 sm:py-[96px]"
              class="peer sr-only"
            />
            <div
              class="peer-checked:text-foresty text-xxs flex items-center gap-1.5 rounded-md px-3 py-1.5 font-bold text-gray-400 transition-all duration-200 peer-checked:bg-white peer-checked:shadow-sm hover:text-gray-600"
            >
              <x-dynamic-component
                component="lucide-rows-2"
                class="h-4 w-4 transition-transform group-hover:scale-110"
              />
              LEBAR
            </div>
          </label>
        </div>
      </div>
    </div>
  </div> --}}
</x-blocks.editor.wrapper>
