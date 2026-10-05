@php
  $initials = $el["data"]["content"]["text"] ?? "AB";

  $bgColors = config("cms.design.mini_bg_colors");

  $textColors = config("cms.design.text_colors");
  $borderColors = config("cms.design.avatar_border_colors");
  $borders = [
    ["value" => "border-0", "name" => "0"],
    ["value" => "border", "name" => "1"],
    ["value" => "border-2", "name" => "2"],
    ["value" => "border-4", "name" => "4"],
  ];

  // Menggabungkan ukuran dimensi dengan ukuran teks (text-xl, text-3xl, dll) agar proporsional
  $avatarSize = $style["size"] ?? "w-16 h-16 md:w-20 md:h-20 text-xl md:text-2xl";
  $avatarRadius = $style["radius"] ?? "rounded-full";
  $avatarBgColor = $style["bg_color"] ?? "bg-emerald-700";
  $avatarTextColor = $style["text_color"] ?? "text-white";
  $avatarBorder = $style["border"] ?? "border-0";
  $avatarBorderColor = $style["border_color"] ?? "border-transparent";
@endphp
<div class="flex flex-col gap-3">
  {{-- Pengaturan Gaya Visual --}}
  <div
    class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3"
  >
    <div class="flex flex-col gap-1.5">
      <span class="text-xxs font-bold text-gray-700 uppercase">Inisial</span>
      <input
        type="text"
        maxlength="3"
        wire:model.live.debounce.300ms="{{ $elPath }}.data.content.text"
        placeholder="Contoh: JD"
        class="focus:ring-foresty focus:border-foresty w-22 rounded-md border border-gray-200 p-1.5 text-sm font-bold uppercase shadow-sm placeholder:text-xs"
      />
      {{-- <p class="text-xxs font-medium text-gray-700">Maksimal 3 huruf untuk tampilan terbaik.</p> --}}
    </div>
    {{-- Ukuran (Dilengkapi dengan skala font) --}}
    <div class="flex flex-col gap-1.5">
      <span class="text-xxs font-bold text-gray-700 uppercase">Ukuran</span>
      <div
        class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
      >
        <button
          type="button"
          title="Standar"
          x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-10 h-10 md:w-12 md:h-12 text-sm md:text-base')"
          class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $avatarSize === 'w-10 h-10 md:w-12 md:h-12 text-sm md:text-base' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <span class="text-[10px] font-bold">A</span>
        </button>
        <button
          type="button"
          title="Sedang"
          x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-16 h-16 md:w-20 md:h-20 text-xl md:text-2xl')"
          class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $avatarSize === 'w-16 h-16 md:w-20 md:h-20 text-xl md:text-2xl' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <span class="text-xs font-bold">A</span>
        </button>
        <button
          type="button"
          title="Besar"
          x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-24 h-24 md:w-32 md:h-32 text-3xl md:text-5xl')"
          class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $avatarSize === 'w-24 h-24 md:w-32 md:h-32 text-3xl md:text-5xl' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <span class="text-sm font-bold">A</span>
        </button>
        <button
          type="button"
          title="Paling Besar"
          x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-32 h-32 md:w-48 md:h-48 text-5xl md:text-7xl')"
          class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $avatarSize === 'w-32 h-32 md:w-48 md:h-48 text-5xl md:text-7xl' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <span class="text-base font-bold">A</span>
        </button>
      </div>
    </div>

    {{-- Bentuk --}}
    <div class="flex flex-col gap-1.5">
      <span class="text-xxs font-bold text-gray-700 uppercase">Bentuk</span>
      <div
        class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
      >
        <button
          type="button"
          title="Kotak"
          x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-md')"
          class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $avatarRadius === 'rounded-md' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div class="h-4 w-4 rounded-sm border-2 border-current"></div>
        </button>
        <button
          type="button"
          title="Agak Bulat"
          x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-[20px]')"
          class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $avatarRadius === 'rounded-[20px]' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div class="h-4 w-4 rounded-md border-2 border-current"></div>
        </button>
        <button
          type="button"
          title="Lingkaran"
          x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-full')"
          class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $avatarRadius === 'rounded-full' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div class="h-4 w-4 rounded-full border-2 border-current"></div>
        </button>
      </div>
    </div>

    {{-- BG COLOR --}}
    <div
      class="flex flex-col gap-1.5"
      x-data="{ localElBgColor: $wire.entangle('{{ $elPath }}.data.style.bg_color').live || 'bg-forest' }"
    >
      <label class="text-xxs font-bold text-gray-700 uppercase"
        >Warna Latar
      </label>
      <div class="flex flex-wrap gap-3 pb-6">
        @foreach ($bgColors as $item)
          <div class="group/btn relative flex flex-col items-center">
            <button
              type="button"
              x-on:click="localElBgColor='{{ $item['value'] }}'"
              class="border text-sm font-semibold flex justify-center items-center border-gray-200 hover:ring-forest {{ $item['value'] }} h-6 w-6 rounded-md shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none"
              x-bind:class="localElBgColor === '{{ strtolower($item['value']) }}' ?
              'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
            ></button>

            <!-- 2. Ubah 'group-hover' menjadi 'group-hover/btn' -->
            <span
              class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
              x-bind:class="localElBgColor === '{{ strtolower($item['value']) }}' ?
              'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
            >
              {{ $item["name"] }}
            </span>
          </div>
        @endforeach
      </div>
    </div>
    {{-- Text COLOR --}}
    <div
      class="flex flex-col gap-1.5"
      x-data="{ localElTextColor: $wire.entangle('{{ $elPath }}.data.style.text_color').live || 'text-charcoal' }"
    >
      <label class="text-xxs font-bold text-gray-700 uppercase"
        >Warna Teks</label
      >
      <div class="flex flex-wrap gap-3 pb-6">
        @foreach ($textColors as $item)
          <!-- 1. Gunakan 'group/btn' alih-alih 'group' biasa -->
          <div class="group/btn relative flex flex-col items-center">
            {{-- wire:model.live="content.{{ $blockId }}.data.background" --}}
            <button
              type="button"
              x-on:click="localElTextColor='{{ $item['value'] }}'"
              class="border text-sm font-semibold flex justify-center items-center border-gray-200 hover:ring-forest {{ $item['value'] }} {{ $item['preview'] }} h-6 w-6 rounded-md shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none"
              x-bind:class="localElTextColor === '{{ strtolower($item['value']) }}' ?
              'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
            >
              Aa
            </button>

            <!-- 2. Ubah 'group-hover' menjadi 'group-hover/btn' -->
            <span
              class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
              x-bind:class="localElTextColor === '{{ strtolower($item['value']) }}' ?
              'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
            >
              {{ $item["name"] }}
            </span>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Ketebalan Garis --}}
    <div
      class="flex flex-col gap-1.5"
      x-data="{ localElBorder: $wire.entangle('{{ $elPath }}.data.style.border').live || 'border-0' }"
    >
      <span class="text-xxs font-bold text-gray-700 uppercase">Garis Tepi</span>
      <div
        class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
      >
        @foreach ($borders as $item)
          <button
            type="button"
            x-on:click="localElBorder = '{{ $item['value'] }}'"
            class="rounded px-1.5 py-1 text-[10px] font-bold transition-all outline-none"
            {{-- {{ $avatarBorder === 'border-0' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}" --}}
            x-bind:class="localElBorder === '{{ $item['value'] }}' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' "
          >
            {{ $item["name"] }}
          </button>
        @endforeach
      </div>
    </div>

    {{-- BG COLOR --}}
    <div
      class="flex flex-col gap-1.5"
      x-data="{ localElBgColor: $wire.entangle('{{ $elPath }}.data.style.border_color').live || 'border-coral' }"
    >
      <label class="text-xxs font-bold text-gray-700 uppercase"
        >Warna Tepian
      </label>
      <div class="flex flex-wrap gap-3 pb-6">
        @foreach ($borderColors as $item)
          <div class="group/btn relative flex flex-col items-center">
            <button
              type="button"
              x-on:click="localElBgColor='{{ $item['value'] }}'"
              class="border text-sm font-semibold flex justify-center items-center border-gray-200 hover:ring-forest {{ $item['preview'] }} h-6 w-6 rounded-md shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none"
              x-bind:class="localElBgColor === '{{ strtolower($item['value']) }}' ?
              'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
            ></button>

            <!-- 2. Ubah 'group-hover' menjadi 'group-hover/btn' -->
            <span
              class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
              x-bind:class="localElBgColor === '{{ strtolower($item['value']) }}' ?
              'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
            >
              {{ $item["name"] }}
            </span>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Warna Garis --}}
    {{-- <div class="flex flex-col gap-1.5">
      <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
        >Warna Garis</span
      >
      <div
        class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
      >
        <button
          type="button"
          title="Transparan"
          x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-transparent')"
          class="rounded p-1.5 transition-all outline-none {{ $avatarBorderColor === 'border-transparent' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div
            class="relative h-4 w-4 overflow-hidden rounded-full border border-gray-300 bg-white shadow-sm"
          >
            <div
              class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"
            ></div>
          </div>
        </button>
        <button
          type="button"
          title="Foresty"
          x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-foresty')"
          class="rounded p-1.5 transition-all outline-none {{ $avatarBorderColor === 'border-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div class="h-4 w-4 rounded-full bg-emerald-700 shadow-sm"></div>
        </button>
        <button
          type="button"
          title="Coral"
          x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-coral')"
          class="rounded p-1.5 transition-all outline-none {{ $avatarBorderColor === 'border-coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div class="h-4 w-4 rounded-full bg-orange-500 shadow-sm"></div>
        </button>
        <button
          type="button"
          title="Abu-abu"
          x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-gray-200')"
          class="rounded p-1.5 transition-all outline-none {{ $avatarBorderColor === 'border-gray-200' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div
            class="h-4 w-4 rounded-full border border-gray-300 bg-gray-200 shadow-sm"
          ></div>
        </button>
      </div>
    </div> --}}
  </div>
  {{-- Area Input Data Inisial --}}
  {{-- <div class="flex-1 space-y-2">
    <input
      type="text"
      maxlength="3"
      wire:model.live.debounce.300ms="{{ $elPath }}.data.content.text"
      placeholder="Ketik Inisial (Contoh: JD)"
      class="focus:ring-foresty focus:border-foresty w-full rounded-lg border-gray-200 p-2 text-sm font-bold uppercase shadow-sm"
    />
    <p class="text-[10px] font-medium text-gray-400">Maksimal 3 huruf untuk tampilan terbaik.</p>
  </div> --}}
</div>
