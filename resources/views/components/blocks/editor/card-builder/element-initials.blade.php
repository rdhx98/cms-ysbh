@php
  $initials = $el["data"]["content"]["text"] ?? "AB";

  // Menggabungkan ukuran dimensi dengan ukuran teks (text-xl, text-3xl, dll) agar proporsional
  $avatarSize = $style["size"] ?? "w-16 h-16 md:w-20 md:h-20 text-xl md:text-2xl";
  $avatarRadius = $style["radius"] ?? "rounded-full";
  $avatarBgColor = $style["bg_color"] ?? "bg-emerald-700";
  $avatarTextColor = $style["text_color"] ?? "text-white";
  $avatarBorder = $style["border"] ?? "border-0";
  $avatarBorderColor = $style["border_color"] ?? "border-transparent";
@endphp

<div class="mb-3 flex items-center justify-between">
  <span
    class="rounded bg-teal-100 px-2 py-0.5 text-[10px] font-extrabold tracking-widest text-teal-700 uppercase"
  >
    Inisial Nama
  </span>
</div>

{{-- Area Input Data Inisial --}}
<div class="mb-3 flex items-start gap-3">
  <div class="flex-1 space-y-2">
    <input
      type="text"
      maxlength="3"
      wire:model.live.debounce.300ms="{{ $elPath }}.data.content.text"
      placeholder="Ketik Inisial (Contoh: JD)"
      class="focus:ring-foresty focus:border-foresty w-full rounded-lg border-gray-200 p-2 text-sm font-bold uppercase shadow-sm"
    />
    <p class="text-[10px] font-medium text-gray-400">Maksimal 3 huruf untuk tampilan terbaik.</p>
  </div>
</div>

{{-- Pengaturan Gaya Visual --}}
<div
  class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3"
>
  {{-- Ukuran (Dilengkapi dengan skala font) --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
      >Ukuran</span
    >
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
    <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
      >Bentuk</span
    >
    <div
      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
    >
      <button
        type="button"
        title="Kotak"
        x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-md')"
        class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $avatarRadius === 'rounded-md' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-md border-2 border-current"></div>
      </button>
      <button
        type="button"
        title="Agak Bulat"
        x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-[20px]')"
        class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $avatarRadius === 'rounded-[20px]' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-[8px] border-2 border-current"></div>
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

  {{-- Warna Latar Belakang --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
      >Latar Latar</span
    >
    <div
      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
    >
      <button
        type="button"
        title="Foresty"
        x-on:click="$wire.set('{{ $elPath }}.data.style.bg_color', 'bg-emerald-700')"
        class="rounded p-1.5 transition-all outline-none {{ $avatarBgColor === 'bg-emerald-700' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-full bg-emerald-700 shadow-sm"></div>
      </button>
      <button
        type="button"
        title="Coral"
        x-on:click="$wire.set('{{ $elPath }}.data.style.bg_color', 'bg-orange-500')"
        class="rounded p-1.5 transition-all outline-none {{ $avatarBgColor === 'bg-orange-500' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-full bg-orange-500 shadow-sm"></div>
      </button>
      <button
        type="button"
        title="Abu-abu"
        x-on:click="$wire.set('{{ $elPath }}.data.style.bg_color', 'bg-gray-200')"
        class="rounded p-1.5 transition-all outline-none {{ $avatarBgColor === 'bg-gray-200' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div
          class="h-4 w-4 rounded-full border border-gray-300 bg-gray-200 shadow-sm"
        ></div>
      </button>
      <button
        type="button"
        title="Indigo"
        x-on:click="$wire.set('{{ $elPath }}.data.style.bg_color', 'bg-indigo-600')"
        class="rounded p-1.5 transition-all outline-none {{ $avatarBgColor === 'bg-indigo-600' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div class="h-4 w-4 rounded-full bg-indigo-600 shadow-sm"></div>
      </button>
    </div>
  </div>

  {{-- Warna Teks --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
      >Warna Teks</span
    >
    <div
      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
    >
      <button
        type="button"
        title="Putih"
        x-on:click="$wire.set('{{ $elPath }}.data.style.text_color', 'text-white')"
        class="rounded p-1.5 transition-all outline-none {{ $avatarTextColor === 'text-white' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div
          class="flex h-4 w-4 items-center justify-center rounded-full border border-gray-300 bg-white text-[8px] font-bold text-gray-400 shadow-sm"
        >
          A
        </div>
      </button>
      <button
        type="button"
        title="Hitam"
        x-on:click="$wire.set('{{ $elPath }}.data.style.text_color', 'text-gray-900')"
        class="rounded p-1.5 transition-all outline-none {{ $avatarTextColor === 'text-gray-900' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div
          class="flex h-4 w-4 items-center justify-center rounded-full bg-gray-900 text-[8px] font-bold text-white shadow-sm"
        >
          A
        </div>
      </button>
      <button
        type="button"
        title="Foresty"
        x-on:click="$wire.set('{{ $elPath }}.data.style.text_color', 'text-foresty')"
        class="rounded p-1.5 transition-all outline-none {{ $avatarTextColor === 'text-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
      >
        <div
          class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-700 text-[8px] font-bold text-white shadow-sm"
        >
          A
        </div>
      </button>
    </div>
  </div>

  {{-- Ketebalan Garis --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
      >Garis Tepi</span
    >
    <div
      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
    >
      <button
        type="button"
        x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-0')"
        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $avatarBorder === 'border-0' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
      >
        Tanpa Garis
      </button>
      <button
        type="button"
        x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-2')"
        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $avatarBorder === 'border-2' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
      >
        Tipis (2px)
      </button>
      <button
        type="button"
        x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-4')"
        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $avatarBorder === 'border-4' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
      >
        Tebal (4px)
      </button>
    </div>
  </div>

  {{-- Warna Garis --}}
  <div class="flex flex-col gap-1.5">
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
  </div>
</div>
