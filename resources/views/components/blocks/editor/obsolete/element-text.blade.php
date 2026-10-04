@php
  $isPill = $style["is_pill"] ?? false;
  $size = $style["size"] ?? "text-[13px]";
  $weight = $style["weight"] ?? "font-normal";
  $pillBg = $style["pill_bg"] ?? "bg-goldy-soft";
  $pillRadius = $style["pill_radius"] ?? "rounded-md";
  $textColor = $style["color"] ?? "text-ink-soft";
  $margin = $style["margin"] ?? "mb-0";
  // 🌟 MAPPING DAFTAR FONT YANG DIIZINKAN KE KELAS TAILWIND
  $font = $style["font"] ?? "font-fraunces";
  $fontOptions = config("cms.fonts", []);
  $borderStyles = config("cms.design.border_styles", []);
  // $fontOptions = [
  //     'font-arial'    => 'Arial',
  //     'font-fraunces' => 'Fraunces',
  //     'font-times'    => 'Times New Roman',
  //     'font-roboto'   => 'Roboto',
  //     'font-jetbrains'=> 'JetBrains Mono',
  //     'font-opensans' => 'Open Sans',
  //     'font-jakarta'  => 'Plus Jakarta Sans',
  // ];
  $currentFontName = $fontOptions[$font] ?? "Fraunces";
@endphp
<!-- <div class="mb-2 flex items-center justify-between">
  <span
    class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded {/{ $isPill ? 'bg-goldy-soft text-goldy-dark' : 'bg-foresty/10 text-foresty' }}"
    >{/{
      $isPill
        ? "Lencana (Pill)"
        : "Teks"
    }}</span
  >
  <label class="flex cursor-pointer items-center gap-1.5">
    <input
      type="checkbox"
      wire:model.live="{/{ $elPath }}.data.style.is_pill"
      class="text-foresty focus:ring-foresty h-3.5 w-3.5 rounded border-gray-300"
    />
    <span class="text-[10px] font-bold text-gray-500 uppercase">Mode Pill</span>
  </label>
</div> -->
KONTOl
<textarea
  rows="2"
  wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.{{ $code }}"
  placeholder="Ketik isi teks di sini..."
  class="focus:ring-foresty mb-2 w-full resize-none rounded-lg border-gray-200 p-2 text-sm font-semibold shadow-sm"
></textarea>

<div
  class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3"
>
  @if (!$isPill)
    {{-- Font & Weight --}}
    {{-- <div class="flex flex-col gap-1.5">
      <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Tipe Font</span>
      <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
        <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.font', 'font-sans')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $font === 'font-sans' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Sistem</button>
        <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.font', 'font-display')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $font === 'font-display' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Display</button>
      </div>
    </div> --}}
    {{-- Font Custom Dropdown --}}
    <div class="flex flex-col gap-1.5">
      <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
        >Tipe Font</span
      >

      <div class="relative w-36" x-data="{ openFont: false }">
        {{-- Tombol Utama --}}
        <button
          type="button"
          x-on:click="openFont = !openFont"
          class="hover:border-foresty flex w-full items-center justify-between rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-[11px] shadow-sm transition-colors focus:outline-none"
        >
          {{-- Teks tombol utama menggunakan inline-style agar persis dengan font terpilih --}}
          <span
            class="truncate"
            style="font-family: '{{ $currentFontName }}', sans-serif;"
          >
            {{ $currentFontName }}
          </span>
          <x-dynamic-component
            component="lucide-chevron-down"
            class="h-3 w-3 shrink-0 text-gray-400"
          />
        </button>

        {{-- Menu Melayang (Dropdown) --}}
        <div
          x-show="openFont"
          x-on:click.outside="openFont = false"
          x-cloak
          class="absolute left-0 z-50 mt-1 max-h-48 w-full scrollbar-thin overflow-y-auto rounded-md border border-gray-200 bg-white p-1 shadow-lg"
        >
          @foreach ($fontOptions as $fClass => $fName)
            <button
              type="button"
              x-on:click="$wire.set('{{ $elPath }}.data.style.font', '{{ $fClass }}'); openFont = false"
              class="group flex w-full items-center rounded-sm px-2 py-1.5 text-left transition-colors {{ $font === $fClass ? 'bg-sage-soft text-foresty font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
            >
              {{-- Teks menu menggunakan inline-style agar admin langsung melihat pratinjau bentuk asli font-nya --}}
              <span
                class="origin-left truncate text-[11px] transition-transform group-hover:scale-105"
                style="font-family: '{{ $fName }}', sans-serif;"
              >
                {{ $fName }}
              </span>

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
          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $weight === 'font-normal' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Reguler
        </button>
        <button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.weight', 'font-semibold')"
          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $weight === 'font-semibold' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
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
          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $size === 'text-[13px]' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Kecil
        </button>
        <button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'text-[15px]')"
          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $size === 'text-[15px]' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Normal
        </button>
        <button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'text-[21px]')"
          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $size === 'text-[21px]' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Besar (H3)
        </button>
      </div>
    </div>
  @else
    {{-- Mode Pill Options --}}
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
          class="rounded p-1.5 transition-all outline-none {{ $pillBg === 'bg-goldy-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div class="h-4 w-4 rounded-full bg-[#fde68a] shadow-sm"></div>
        </button>
        <button
          type="button"
          title="Mist"
          x-on:click="$wire.set('{{ $elPath }}.data.style.pill_bg', 'bg-mist')"
          class="rounded p-1.5 transition-all outline-none {{ $pillBg === 'bg-mist' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
        >
          <div
            class="h-4 w-4 rounded-full border border-gray-300 bg-gray-200 shadow-sm"
          ></div>
        </button>
        <button
          type="button"
          title="Sage"
          x-on:click="$wire.set('{{ $elPath }}.data.style.pill_bg', 'bg-sage-soft')"
          class="rounded p-1.5 transition-all outline-none {{ $pillBg === 'bg-sage-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
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
          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $pillRadius === 'rounded-md' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Bulat Sedikit
        </button>
        <button
          type="button"
          x-on:click="$wire.set('{{ $elPath }}.data.style.pill_radius', 'rounded-full')"
          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $pillRadius === 'rounded-full' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
        >
          Lingkaran
        </button>
      </div>
    </div>
  @endif

  {{-- Shared Options (Color & Margin) --}}
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
        class="rounded p-1.5 transition-all outline-none {{ $textColor === 'text-ink-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-full bg-gray-700 shadow-sm"></div>
      </button>
      <button
        type="button"
        title="Foresty"
        x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-foresty')"
        class="rounded p-1.5 transition-all outline-none {{ $textColor === 'text-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="bg-foresty h-4 w-4 rounded-full shadow-sm"></div>
      </button>
      <button
        type="button"
        title="Coral"
        x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-coral')"
        class="rounded p-1.5 transition-all outline-none {{ $textColor === 'text-coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="bg-coral h-4 w-4 rounded-full shadow-sm"></div>
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
        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $margin === 'mb-0' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
      >
        0px
      </button>
      <button
        type="button"
        x-on:click="$wire.set('{{ $elPath }}.data.style.margin', 'mb-2')"
        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $margin === 'mb-2' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
      >
        Kecil
      </button>
      <button
        type="button"
        x-on:click="$wire.set('{{ $elPath }}.data.style.margin', 'mb-4')"
        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $margin === 'mb-4' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
      >
        Sedang
      </button>
    </div>
  </div>
</div>
