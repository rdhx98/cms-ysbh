@props ([
  "blockId",
  "block",
  "activeLocales" => [],
  "allContent" => [],
])

@php
  $colorsList = config("cms.design.bg_colors", []);
  $paddingOptions=[
    ['label'=>'tight','value'=> 'py-8 sm:py-12', 'preview'=> 'lucide-rows-4'],
    ['label'=>'normal','value'=> 'py-16 sm:py-24', 'preview'=> 'lucide-rows-3'],
    ['label'=>'wide','value'=> 'py-24 sm:py-[96px]', 'preview'=> 'lucide-rows-2'],
  ]
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


    <!-- MARGIN CONTROL -->
      <div
        class="flex flex-col gap-1.5"
        {{-- x-data="{ localPadding: $wire.entangle('content.{{ $blockId }}.data.padding').live || 'py-16 sm:py-24' }" --}}
        x-data="{ localPadding: @entangle('content.'.$blockId.'.data.padding').live }"
      >
      {{-- content.{{ $blockId }}.data.padding --}}
        <label class="text-xxs font-bold text-gray-700 uppercase"
          >Padding atas & bawah</label
        >
        <div
          class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
        >
          @foreach ($paddingOptions as $item)
            <button
              type="button"
              x-on:click="localPadding = '{{ $item['value'] }}'"
              class="group text-xxs flex items-center gap-1 rounded px-1.5 py-1 font-bold transition-all outline-none"
              {{-- class="group flex items-center gap-1 rounded px-1.5 py-1 transition-all outline-none" --}}
              x-bind:class="(localPadding || 'py-16 sm:py-24') === '{{ $item['value'] }}' ? 'bg-white shadow-sm text-forest' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
              title="{{ $item['name'] ?? $item['label'] }}"
            >
            <x-dynamic-component
              component="{{ $item['preview'] ?? 'lucide-rows-4' }}"
              class="h-4 w-4 transition-transform group-hover:scale-110"
            />

              <!-- Label Teks -->
              <span
                x-bind:class="(localPadding || 'py-16 sm:py-24') === '{{ $item['value'] }}' ? 'text-forest' : 'text-gray-400/70'"
                class="text-xxs font-bold uppercase"
                >{{
                  $item["name"] ??
                    $item["label"]
                }}</span
              >
            </button>
          @endforeach
        </div>
      </div>
  </div>


</x-blocks.editor.wrapper>
