@php
  $imgUrl = $el['data']['content']['url'] ?? '';
  $imgAlt = $el['data']['content']['alt'] ?? '';
  $imgSize = $style['size'] ?? 'w-16 h-16 md:w-20 md:h-20'; // Sedang sebagai default
  $imgRadius = $style['radius'] ?? 'rounded-full';
  $imgBorder = $style['border'] ?? 'border-0';
  $imgBorderColor = $style['border_color'] ?? 'border-transparent';
@endphp
<div class="mb-3 flex items-center justify-between">
  <span class="bg-indigo-100 text-indigo-700 rounded px-2 py-0.5 text-[10px] font-extrabold tracking-widest uppercase">Foto Profil</span>
</div>

{{-- Area Pratinjau & Input Data --}}
<div class="mb-3 flex items-start gap-3">

  {{-- Input URL & Alt Text --}}
  <div class="flex-1 space-y-2">

    {{-- <button wire:click="$dispatch('openFileManager', { targetEvent: 'photoSelected', targetComponentId: 'form1', allowedFileType: 'image' })">
    Pilih Foto Profil
</button> --}}
{{-- targetEvent: 'mediaSelectedForCard',  --}}
{{-- targetComponentId: '{{ $blockId }}-{{ $elPath }}'  --}}
    {{-- Tombol Buka File Manager --}}
    <button
      type="button"
      x-on:click="$dispatch('openFileManager', {
          targetEvent: 'mediaSelected',
          targetComponentId: '{{ $elPath }}.data.content',
          allowedFileType: 'image'
      })"
      class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold shadow-sm transition-colors hover:border-foresty hover:bg-sage-soft hover:text-foresty text-gray-600"
    >
      <x-dynamic-component component="lucide-folder-search" class="h-4 w-4 shrink-0" />
      <span>Jelajahi File Manager</span>
    </button>
    <input
      type="text"
      wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.alt"
      placeholder="Teks Alternatif (Untuk SEO & Tunanetra)"
      class="focus:ring-foresty focus:border-foresty w-full rounded-lg border-gray-200 p-2 text-xs shadow-sm"
    />
  </div>
</div>

{{-- Pengaturan Gaya Visual --}}
<div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
  {{-- Ukuran --}}
  {{-- <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Ukuran</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-12 h-12')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgSize === 'w-12 h-12' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Kecil</button>
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-20 h-20')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgSize === 'w-20 h-20' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Sedang</button>
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-32 h-32')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgSize === 'w-32 h-32' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Besar</button>
    </div>
  </div> --}}
  {{-- Ukuran Foto Profil Responsif --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Ukuran</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Standar" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-10 h-10 md:w-12 md:h-12')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $imgSize === 'w-10 h-10 md:w-12 md:h-12' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-2.5 w-2.5 rounded-sm bg-current transition-all"></div>
      </button>
      <button type="button" title="Sedang" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-16 h-16 md:w-20 md:h-20')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $imgSize === 'w-16 h-16 md:w-20 md:h-20' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-3.5 w-3.5 rounded-sm bg-current transition-all"></div>
      </button>
      <button type="button" title="Besar" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-24 h-24 md:w-32 md:h-32')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $imgSize === 'w-24 h-24 md:w-32 md:h-32' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-sm bg-current transition-all"></div>
      </button>
      <button type="button" title="Paling Besar" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-32 h-32 md:w-48 md:h-48')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $imgSize === 'w-32 h-32 md:w-48 md:h-48' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-5 w-5 rounded-sm bg-current transition-all"></div>
      </button>
    </div>
  </div>

  {{-- Bentuk --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Bentuk</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Kotak" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-md')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $imgRadius === 'rounded-md' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-md border-2 border-current"></div>
      </button>
      <button type="button" title="Agak Bulat" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-[20px]')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $imgRadius === 'rounded-[20px]' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-[8px] border-2 border-current"></div>
      </button>
      <button type="button" title="Lingkaran" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-full')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $imgRadius === 'rounded-full' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full border-2 border-current"></div>
      </button>
    </div>
  </div>

  {{-- Ketebalan Garis --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Garis Tepi</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-0')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgBorder === 'border-0' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tanpa Garis</button>
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-2')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgBorder === 'border-2' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tipis (2px)</button>
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-4')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgBorder === 'border-4' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tebal (4px)</button>
    </div>
  </div>

  {{-- Warna Garis --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Warna Garis</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Transparan" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-transparent')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-transparent' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="relative h-4 w-4 overflow-hidden rounded-full border border-gray-300 bg-white shadow-sm">
          <div class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"></div>
        </div>
      </button>
      <button type="button" title="Foresty" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-foresty')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-emerald-700 shadow-sm"></div>
      </button>
      <button type="button" title="Coral" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-coral')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-orange-500 shadow-sm"></div>
      </button>
      <button type="button" title="Abu-abu" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-gray-200')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-gray-200' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-gray-200 border border-gray-300 shadow-sm"></div>
      </button>
    </div>
  </div>
</div>
