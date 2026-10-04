@php
  $iconBg = $style["bg"] ?? "bg-goldy-soft";
  $iconColor = $style["color"] ?? "text-foresty";
  $iconSize = $style["size"] ?? "w-10 h-10 md:w-12 md:h-12";
  $iconRadius = $style["radius"] ?? "rounded-[14px]";
@endphp
{{-- <div class="mb-2 flex items-center justify-between">
  <span
    class="bg-foresty rounded px-2 py-0.5 text-[10px] font-extrabold tracking-widest text-white uppercase"
    >Ikon</span
  >
</div> --}}

<!-- OPSI IKON BAWAAN -->
<div
  class="flex flex-wrap items-start justify-start gap-4 rounded-lg border border-gray-100 bg-gray-50 p-2"
>
  <!-- 🌟 PEMILIH IKON TELEPORT (ANTI TERPOTONG) -->
  <div
    class="relative flex flex-col gap-1.5"
    x-data="{ openPicker: false, search: '' }"
  >
    <label
      {{-- class="text-xxs font-bold tracking-wide text-gray-700 uppercase" --}}
      class="text-xxs bg-foresty w-fit rounded px-2 py-0.5 font-bold text-white uppercase"
      >Ikon</label
    >

    <button
      type="button"
      x-on:click="openPicker = true"
      class="hover:border-foresty flex w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs shadow-sm transition-colors focus:outline-none"
    >
      <div class="flex items-center gap-2 truncate">
        <x-dynamic-component
          :component="'lucide-' . ($el['data']['content']['icon'] ?: 'box')"
          class="text-foresty h-4 w-4 shrink-0"
          stroke-width="2.5"
        />
        <span
          class="truncate font-mono text-[11px] font-bold text-gray-700 uppercase"
          >{{
            $el["data"]["content"]["icon"] ?:
              "PILIH IKON..."
          }}</span
        >
      </div>
      <x-dynamic-component
        component="lucide-search"
        class="h-4 w-4 shrink-0 text-gray-400"
      />
    </button>

    <template x-teleport="body">
      <div
        x-show="openPicker"
        x-cloak
        class="fixed inset-0 z-[10000] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
      >
        <div
          @click.away="openPicker = false"
          x-transition
          x-show="openPicker"
          class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
          <div class="border-b border-gray-100 bg-gray-50/80 p-4">
            <div class="mb-3 flex items-center justify-between">
              <h3
                class="text-sm font-extrabold tracking-widest text-gray-800 uppercase"
              >
                Pilih Ikon Kartu
              </h3>
              <button
                @click="openPicker = false"
                class="rounded-full bg-gray-200 p-1 text-gray-500 transition-colors outline-none hover:bg-red-100 hover:text-red-500"
              >
                <x-dynamic-component component="lucide-x" class="h-4 w-4" />
              </button>
            </div>
            <input
              type="text"
              x-model="search"
              placeholder="Cari nama ikon..."
              class="focus:border-forest w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm shadow-sm focus:ring-0"
            />
          </div>
          <div
            class="grid scrollbar-thin grid-cols-5 gap-2 overflow-y-auto p-4 sm:grid-cols-7"
          >
            @foreach ($iconsList as $iconName)
              <button
                type="button"
                x-show="'{{ $iconName }}'.includes(search.toLowerCase())"
                x-on:click="$wire.set('{{ $elPath }}.data.content.icon', '{{ $iconName }}'); openPicker = false; search = '';"
                class="flex aspect-square items-center justify-center rounded-xl outline-none transition-all hover:scale-110 hover:bg-sage-soft {{ ($el['data']['content']['icon'] ?? '') === $iconName ? 'bg-forest text-goldy shadow-md ring-2 ring-forest ring-offset-1' : 'bg-zinc-50 text-forest' }}"
                title="{{ $iconName }}"
              >
                <x-dynamic-component
                  :component="'lucide-' . $iconName"
                  class="h-5 w-5 shrink-0"
                  stroke-width="2"
                />
              </button>
            @endforeach
          </div>
        </div>
      </div>
    </template>
  </div>
  <!-- Copy seluruh isi elemen opsi gaya ikon di sini sama persis seperti aslinya -->
  <div class="flex flex-col gap-1.5">
    <span class="text-xxs rounded py-0.5 font-bold text-gray-700 uppercase"
      >Latar Ikon</span
    >
    <div
      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
    >
      <button
        type="button"
        title="Goldy"
        x-on:click="$wire.set('{{ $elPath }}.data.style.bg', 'bg-goldy-soft')"
        class="rounded p-1.5 transition-all outline-none {{ $iconBg === 'bg-goldy-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-full bg-[#fde68a] shadow-sm"></div></button
      ><button
        type="button"
        title="Mist"
        x-on:click="$wire.set('{{ $elPath }}.data.style.bg', 'bg-mist')"
        class="rounded p-1.5 transition-all outline-none {{ $iconBg === 'bg-mist' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div
          class="h-4 w-4 rounded-full border border-gray-300 bg-gray-200 shadow-sm"
        ></div></button
      ><button
        type="button"
        title="Transparan"
        x-on:click="$wire.set('{{ $elPath }}.data.style.bg', 'bg-transparent')"
        class="rounded p-1.5 transition-all outline-none {{ $iconBg === 'bg-transparent' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div
          class="relative h-4 w-4 overflow-hidden rounded-full border border-gray-300 bg-white shadow-sm"
        >
          <div
            class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"
          ></div>
        </div>
      </button>
    </div>
  </div>
  <div class="flex flex-col gap-1.5">
    <span class="text-xxs rounded py-0.5 font-bold text-gray-700 uppercase"
      >Warna Ikon</span
    >
    <div
      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
    >
      <button
        type="button"
        title="Foresty"
        x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-foresty')"
        class="rounded p-1.5 transition-all outline-none {{ $iconColor === 'text-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div
          class="h-4 w-4 rounded-full bg-emerald-700 shadow-sm"
        ></div></button
      ><button
        type="button"
        title="Coral"
        x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-coral')"
        class="rounded p-1.5 transition-all outline-none {{ $iconColor === 'text-coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-full bg-orange-500 shadow-sm"></div>
      </button>
    </div>
  </div>
  <div class="flex flex-col gap-1.5">
    <span class="text-xxs rounded py-0.5 font-bold text-gray-700 uppercase"
      >Ukuran</span
    >
    <div
      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
    >
      <button
        type="button"
        title="Standar"
        x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-10 h-10 md:w-12 md:h-12')"
        class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $iconSize === 'w-10 h-10 md:w-12 md:h-12' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div
          class="h-2.5 w-2.5 rounded-sm bg-current transition-all"
        ></div></button
      ><button
        type="button"
        title="Sedang"
        x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-16 h-16 md:w-20 md:h-20')"
        class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $iconSize === 'w-16 h-16 md:w-20 md:h-20' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div
          class="h-3.5 w-3.5 rounded-sm bg-current transition-all"
        ></div></button
      ><button
        type="button"
        title="Besar"
        x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-24 h-24 md:w-32 md:h-32')"
        class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $iconSize === 'w-24 h-24 md:w-32 md:h-32' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-sm bg-current transition-all"></div>
      </button>
    </div>
  </div>
  <div class="flex flex-col gap-1.5">
    <span class="text-xxs rounded py-0.5 font-bold text-gray-700 uppercase"
      >Sudut Ikon</span
    >
    <div
      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
    >
      <button
        type="button"
        title="Agak Bulat"
        x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-[14px]')"
        class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $iconRadius === 'rounded-[14px]' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-md border-2 border-current"></div></button
      ><button
        type="button"
        title="Lingkaran"
        x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-full')"
        class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $iconRadius === 'rounded-full' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-full border-2 border-current"></div>
      </button>
    </div>
  </div>
</div>
