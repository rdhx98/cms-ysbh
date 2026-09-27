@php
  $iconBg = $style['bg'] ?? 'bg-goldy-soft';
  $iconColor = $style['color'] ?? 'text-foresty';
  // $iconSize = $style['size'] ?? 'w-10 h-10';
  $iconSize = $style['size'] ?? 'w-10 h-10 md:w-12 md:h-12';
  $iconRadius = $style['radius'] ?? 'rounded-[14px]';
@endphp
<div class="mb-2 flex items-center justify-between">
  <span class="bg-foresty rounded px-2 py-0.5 text-[10px] font-extrabold tracking-widest text-white uppercase">Ikon</span>
</div>

<div class="relative mb-2" x-data="{ openPicker: false, search: '' }">
  <button type="button" x-on:click="openPicker = !openPicker" class="hover:border-foresty flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs shadow-sm transition-colors focus:outline-none">
    <div class="flex items-center gap-2 truncate">
      <x-dynamic-component :component="'lucide-' . ($el['data']['content']['icon'] ?: 'box')" class="text-foresty h-5 w-5 shrink-0" stroke-width="2.5" />
      <span class="truncate font-mono text-[11px] font-bold text-gray-700 uppercase">{{ $el['data']['content']['icon'] ?: 'PILIH IKON...' }}</span>
    </div>
    <x-dynamic-component component="lucide-chevron-down" class="h-4 w-4 shrink-0 text-gray-400" />
  </button>

  <div x-show="openPicker" x-on:click.outside="openPicker = false" x-cloak class="absolute left-0 z-50 mt-1 flex w-full flex-col gap-2 rounded-xl border border-gray-200 bg-white p-3 shadow-xl sm:w-64">
    <div class="relative">
      <x-dynamic-component component="lucide-search" class="absolute top-2.5 left-3 h-4 w-4 text-gray-400" />
      <input type="text" x-model="search" placeholder="Cari ikon..." class="focus:ring-foresty focus:border-foresty w-full rounded-lg border border-gray-200 py-2 pr-2 pl-9 text-xs shadow-sm" />
    </div>
    <div class="grid max-h-48 scrollbar-thin grid-cols-5 gap-1.5 overflow-y-auto p-1">
      @foreach ($iconsList as $iconName)
        <button type="button" x-show="'{{ $iconName }}'.includes(search.toLowerCase())" x-on:click="$wire.set('{{ $elPath }}.data.content.icon', '{{ $iconName }}'); openPicker = false; search = '';" class="p-2.5 rounded-lg flex items-center justify-center transition-all duration-200 border {{ ($el['data']['content']['icon'] ?? '') === $iconName ? 'bg-sage-soft text-foresty border-foresty shadow-sm scale-110' : 'bg-gray-50 text-gray-400 border-transparent hover:border-foresty/50 hover:text-foresty' }}">
          <x-dynamic-component :component="'lucide-' . $iconName" class="h-5 w-5 shrink-0" stroke-width="2" />
        </button>
      @endforeach
    </div>
  </div>
</div>

<div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Latar Ikon</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Goldy" x-on:click="$wire.set('{{ $elPath }}.data.style.bg', 'bg-goldy-soft')" class="rounded p-1.5 transition-all outline-none {{ $iconBg === 'bg-goldy-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-[#fde68a] shadow-sm"></div>
      </button>
      <button type="button" title="Mist" x-on:click="$wire.set('{{ $elPath }}.data.style.bg', 'bg-mist')" class="rounded p-1.5 transition-all outline-none {{ $iconBg === 'bg-mist' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-gray-200 border border-gray-300 shadow-sm"></div>
      </button>
      <button type="button" title="Transparan" x-on:click="$wire.set('{{ $elPath }}.data.style.bg', 'bg-transparent')" class="rounded p-1.5 transition-all outline-none {{ $iconBg === 'bg-transparent' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="relative h-4 w-4 overflow-hidden rounded-full border border-gray-300 bg-white shadow-sm">
          <div class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"></div>
        </div>
      </button>
    </div>
  </div>

  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Warna Ikon</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Foresty" x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-foresty')" class="rounded p-1.5 transition-all outline-none {{ $iconColor === 'text-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-emerald-700 shadow-sm"></div>
      </button>
      <button type="button" title="Coral" x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-coral')" class="rounded p-1.5 transition-all outline-none {{ $iconColor === 'text-coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-orange-500 shadow-sm"></div>
      </button>
    </div>
  </div>

  {{-- <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Ukuran</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-10 h-10')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $iconSize === 'w-10 h-10' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Standar</button>
      <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-16 h-16')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $iconSize === 'w-16 h-16' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Besar</button>
    </div>
  </div> --}}
  {{-- Ukuran Ikon Responsif --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Ukuran</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Standar" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-10 h-10 md:w-12 md:h-12')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $iconSize === 'w-10 h-10 md:w-12 md:h-12' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-2.5 w-2.5 rounded-sm bg-current transition-all"></div>
      </button>
      <button type="button" title="Sedang" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-16 h-16 md:w-20 md:h-20')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $iconSize === 'w-16 h-16 md:w-20 md:h-20' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-3.5 w-3.5 rounded-sm bg-current transition-all"></div>
      </button>
      <button type="button" title="Besar" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-24 h-24 md:w-32 md:h-32')" class="text-gray-400 hover:text-gray-700 flex h-7 w-7 items-center justify-center rounded p-1 transition-all outline-none {{ $iconSize === 'w-24 h-24 md:w-32 md:h-32' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-sm bg-current transition-all"></div>
      </button>
    </div>
  </div>

  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Sudut Ikon</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Agak Bulat" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-[14px]')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $iconRadius === 'rounded-[14px]' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-md border-2 border-current"></div>
      </button>
      <button type="button" title="Lingkaran" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-full')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $iconRadius === 'rounded-full' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full border-2 border-current"></div>
      </button>
    </div>
  </div>
</div>