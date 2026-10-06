@php
  $isPill = $style["is_pill"] ?? false;
  $size = $style["size"] ?? "text-[13px]";
  $weight = $style["weight"] ?? "font-normal";
  $pillBg = $style["pill_bg"] ?? "bg-goldy-soft";
  $pillRadius = $style["pill_radius"] ?? "rounded-md";
  $textColor = $style["color"] ?? "text-ink-soft";
  $margin = $style["margin"] ?? "mb-0";
  $font = $style["font"] ?? "font-fraunces";
  $fontOptions = [
    "font-arial" => "Arial",
    "font-fraunces" => "Fraunces",
    "font-times" => "Times New Roman",
    "font-roboto" => "Roboto",
    "font-jetbrains" => "JetBrains Mono",
    "font-opensans" => "Open Sans",
    "font-jakarta" => "Plus Jakarta Sans",
  ];
  $currentFontName = $fontOptions[$font] ?? "Fraunces";
@endphp

<div class="mb-4 flex items-center justify-between">
  <span
    class="rounded px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-widest {{ $isPill ? 'bg-goldy-soft text-goldy-dark' : 'bg-foresty/10 text-foresty' }}"
    >{{
      $isPill
        ? "Lencana (Pill)"
        : "Teks"
    }}</span
  >
  <label class="flex cursor-pointer items-center gap-1.5">
    <input
      type="checkbox"
      wire:model.live="{{ $elPath }}.data.style.is_pill"
      class="text-foresty focus:ring-foresty h-3.5 w-3.5 rounded border-gray-300"
    />
    <span class="text-[10px] font-bold text-gray-500 uppercase">Mode Pill</span>
  </label>
</div>

<!-- 🌟 KUNCI: Loop Multi-Bahasa untuk Teks -->
<div
  class="mb-4 grid gap-4"
  :class="effectiveLayout === 'single'
    ? 'grid-cols-1'
    : splitLanguages.length >= 3
      ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
      : 'grid-cols-1 md:grid-cols-2'"
>
  @foreach ($activeLocales as $lang)
    <div
      wire:key="text-{{ $elPath }}-{{ $lang }}"
      x-show="effectiveLayout === 'single' ? singleActiveLang === '{{ $lang }}' : splitLanguages.includes('{{ $lang }}')"
      x-cloak
    >
      <div class="mb-1.5 flex items-center gap-2">
        <span
          class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase shadow-sm"
          >{{ $lang }}</span
        >
      </div>
      <textarea
        rows="2"
        wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.{{ $lang }}"
        placeholder="Ketik isi teks di sini..."
        class="focus:border-foresty focus:ring-foresty w-full resize-none rounded-lg border-gray-200 p-2 text-sm font-semibold shadow-sm transition-colors"
      ></textarea>
    </div>
  @endforeach
</div>

<div
  class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3"
>
  @if (!$isPill)
    <!-- Font Picker Teleport -->
    <div class="flex flex-col gap-1.5">
      <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
        >Tipe Font</span
      >
      <div class="relative w-36" x-data="{ openFont: false }">
        <button
          type="button"
          x-on:click="openFont = true"
          class="hover:border-foresty flex w-full items-center justify-between rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-[11px] shadow-sm transition-colors focus:outline-none"
        >
          <span
            class="truncate"
            style="font-family: '{{ $currentFontName }}', sans-serif;"
            >{{ $currentFontName }}</span
          >
          <x-dynamic-component
            component="lucide-chevron-down"
            class="h-3 w-3 shrink-0 text-gray-400"
          />
        </button>

        <template x-teleport="body">
          <div
            x-show="openFont"
            x-cloak
            class="fixed inset-0 z-[10000] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
          >
            <div
              @click.away="openFont = false"
              x-transition
              x-show="openFont"
              class="w-full max-w-xs overflow-hidden rounded-xl bg-white shadow-2xl"
            >
              <div
                class="flex items-center justify-between border-b border-gray-100 bg-gray-50 p-3"
              >
                <span
                  class="text-xs font-bold tracking-widest text-gray-600 uppercase"
                  >Pilih Font Teks</span
                >
                <button
                  @click="openFont = false"
                  class="rounded-full bg-gray-200 p-1 text-gray-500 outline-none hover:bg-red-100 hover:text-red-500"
                >
                  <x-dynamic-component
                    component="lucide-x"
                    class="h-3.5 w-3.5"
                  />
                </button>
              </div>
              <div class="max-h-64 scrollbar-thin overflow-y-auto p-2">
                @foreach ($fontOptions as $fClass => $fName)
                  <button
                    type="button"
                    x-on:click="$wire.set('{{ $elPath }}.data.style.font', '{{ $fClass }}'); openFont = false"
                    class="group mb-1 flex w-full items-center rounded-md px-3 py-2 text-left transition-colors {{ $font === $fClass ? 'bg-sage-soft font-bold text-foresty' : 'text-gray-600 hover:bg-gray-100' }}"
                  >
                    <span
                      class="origin-left truncate text-[13px] transition-transform group-hover:scale-105"
                      style="font-family: '{{ $fName }}', sans-serif;"
                      >{{ $fName }}</span
                    >
                    @if ($font === $fClass)
                      <x-dynamic-component
                        component="lucide-check"
                        class="text-foresty ml-auto h-3 w-3 shrink-0"
                        stroke-width="3"
                      />
                    @endif
                  </button>
                @endforeach
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <div class="flex flex-col gap-1.5">
      <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
        >Ketebalan</span
      >
      <div
        class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
      >
        <button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.weight', 'font-normal')"
          class="rounded px-2.5 py-1 text-[10px] font-bold outline-none transition-all {{ $weight === 'font-normal' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Reguler</button
        ><button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.weight', 'font-semibold')"
          class="rounded px-2.5 py-1 text-[10px] font-bold outline-none transition-all {{ $weight === 'font-semibold' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Semi Bold
        </button>
      </div>
    </div>
    <div class="flex flex-col gap-1.5">
      <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
        >Ukuran</span
      >
      <div
        class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
      >
        <button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'text-[13px]')"
          class="rounded px-2.5 py-1 text-[10px] font-bold outline-none transition-all {{ $size === 'text-[13px]' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Kecil</button
        ><button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'text-[15px]')"
          class="rounded px-2.5 py-1 text-[10px] font-bold outline-none transition-all {{ $size === 'text-[15px]' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Normal</button
        ><button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'text-[21px]')"
          class="rounded px-2.5 py-1 text-[10px] font-bold outline-none transition-all {{ $size === 'text-[21px]' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Besar (H3)
        </button>
      </div>
    </div>
  @else
    <div class="flex flex-col gap-1.5">
      <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
        >Warna Latar Pill</span
      >
      <div
        class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
      >
        <button
          type="button"
          title="Goldy"
          x-on:click="$wire.set('{{ $elPath }}.data.style.pill_bg', 'bg-goldy-soft')"
          class="rounded p-1.5 outline-none transition-all {{ $pillBg === 'bg-goldy-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div
            class="h-4 w-4 rounded-full bg-[#fde68a] shadow-sm"
          ></div></button
        ><button
          type="button"
          title="Mist"
          x-on:click="$wire.set('{{ $elPath }}.data.style.pill_bg', 'bg-mist')"
          class="rounded p-1.5 outline-none transition-all {{ $pillBg === 'bg-mist' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div
            class="h-4 w-4 rounded-full border border-gray-300 bg-gray-200 shadow-sm"
          ></div>
        </button>
        <button
          type="button"
          title="Sage"
          x-on:click="$wire.set('{{ $elPath }}.data.style.pill_bg', 'bg-sage-soft')"
          class="rounded p-1.5 outline-none transition-all {{ $pillBg === 'bg-sage-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div class="h-4 w-4 rounded-full bg-[#dcfce7] shadow-sm"></div>
        </button>
      </div>
    </div>
    <div class="flex flex-col gap-1.5">
      <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
        >Bentuk Pill</span
      >
      <div
        class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
      >
        <button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.pill_radius', 'rounded-md')"
          class="rounded px-2.5 py-1 text-[10px] font-bold outline-none transition-all {{ $pillRadius === 'rounded-md' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Bulat Sedikit</button
        ><button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.pill_radius', 'rounded-full')"
          class="rounded px-2.5 py-1 text-[10px] font-bold outline-none transition-all {{ $pillRadius === 'rounded-full' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Lingkaran
        </button>
      </div>
    </div>
  @endif

  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
      >Warna Teks</span
    >
    <div
      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
    >
      <button
        type="button"
        title="Abu Gelap"
        x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-ink-soft')"
        class="rounded p-1.5 outline-none transition-all {{ $textColor === 'text-ink-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-full bg-gray-700 shadow-sm"></div></button
      ><button
        type="button"
        title="Foresty"
        x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-foresty')"
        class="rounded p-1.5 outline-none transition-all {{ $textColor === 'text-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="bg-foresty h-4 w-4 rounded-full shadow-sm"></div>
      </button>
      <button
        type="button"
        title="Coral"
        x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-coral')"
        class="rounded p-1.5 outline-none transition-all {{ $textColor === 'text-coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="bg-coral h-4 w-4 rounded-full shadow-sm"></div>
      </button>
      <button
        type="button"
        title="Aurum"
        x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-aurum')"
        class="rounded p-1.5 outline-none transition-all {{ $textColor === 'text-aurum' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="bg-aurum h-4 w-4 rounded-full shadow-sm"></div>
      </button>
    </div>
  </div>
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
      >Jarak Bawah</span
    >
    <div
      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
    >
      <button
        type="button"
        x-on:click="$wire.set('{{ $elPath }}.data.style.margin', 'mb-0')"
        class="rounded px-2.5 py-1 text-[10px] font-bold outline-none transition-all {{ $margin === 'mb-0' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700' }}"
      >
        0px</button
      ><button
        type="button"
        x-on:click="$wire.set('{{ $elPath }}.data.style.margin', 'mb-2')"
        class="rounded px-2.5 py-1 text-[10px] font-bold outline-none transition-all {{ $margin === 'mb-2' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700' }}"
      >
        Kecil</button
      ><button
        type="button"
        x-on:click="$wire.set('{{ $elPath }}.data.style.margin', 'mb-4')"
        class="rounded px-2.5 py-1 text-[10px] font-bold outline-none transition-all {{ $margin === 'mb-4' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700' }}"
      >
        Sedang
      </button>
    </div>
  </div>
</div>
