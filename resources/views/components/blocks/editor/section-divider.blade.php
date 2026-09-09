@props(['blockId', 'block'])

<div
  class="relative  border border-gray-200 shadow-sm transition-all duration-200  min-h-16 py-2 md:py-0 md:h-16 flex flex-wrap md:flex-nowrap items-center justify-between gap-x-4 gap-y-3 rounded-xl px-2">
  <div class="flex items-center gap-1">
    <div
      class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none cursor-pointer ">
      <x-dynamic-component component="lucide-circle-chevron-down"
        class="w-5 h-5 text-foresty transition-transform duration-200 -rotate-90" />
    </div>
    {{-- <div class="p-2 bg-sage-soft rounded-lg">
		</div> --}}
    <div class="flex items-center gap-1">
      <x-dynamic-component :component="'lucide-rows'" class="h-4 w-4 text-gray-400" stroke-width="2.5" />
      <div class="flex flex-col ">
        <span
          class="text-fluid-xxs font-extrabold text-gray-500 uppercase tracking-widest flex items-center gap-1.5 cursor-pointer select-none">Pengaturan
          Seksi</span>
        <p class="text-fluid-xxs text-slate-400">Blok di bawah batas ini akan dibungkus dengan gaya berikut:</p>
      </div>
    </div>
  </div>
  <div class="flex items-center gap-4 ">
    {{-- Pilih Warna Latar --}}
    <div class="flex flex-col gap-1">
      <label class="text-fluid-xxs font-bold text-foresty uppercase">Warna Latar</label>
      <select wire:model.live="content.{{ $blockId }}.data.background"
        class="text-xs bg-white text-foresty border-foresty rounded focus:ring-sage-soft py-1">
        <option value="bg-paper">Paper</option>
        <option value="bg-white">Putih</option>
        <option value="bg-gray-50">Abu-abu Terang</option>
        <option value="bg-foresty">Foresty (Hijau Gelap)</option>
        <option value="bg-mist">Mist (Abu Kebiruan)</option>
      </select>
    </div>

    {{-- Pilih Warna Teks --}}
    <div class="flex flex-col gap-1">
      <label class="text-fluid-xxs font-bold text-slate-400 uppercase">Warna Teks Utama</label>
      <select wire:model.live="content.{{ $blockId }}.data.text_color"
        class="text-xs bg-white text-foresty border-foresty rounded focus:ring-foresty py-1">
        <option value="text-gray-900">Gelap (Default)</option>
        <option value="text-white">Terang (Putih)</option>
      </select>
    </div>

    {{-- Pilih Padding --}}
    <div class="flex flex-col gap-1">
      <label class="text-fluid-xxs font-bold text-slate-400 uppercase">Jarak Luar (Padding)</label>
      <select wire:model.live="content.{{ $blockId }}.data.padding"
        class="text-xs bg-white text-foresty border-foresty rounded focus:ring-foresty py-1">
        <option value="py-8 sm:py-12">Sempit</option>
        <option value="py-16 sm:py-24">Sedang (Standar)</option>
        <option value="py-24 sm:py-[96px]">Lebar</option>
      </select>
    </div>
  </div>
</div>
