@props([
'blockId',
'block'
])

<div class="relative bg-gray-100 hover:bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 flex items-center justify-between px-4 py-3">
  <div class="flex items-center gap-3">
    <div class="p-2 bg-sage-soft rounded-lg">
      <x-dynamic-component :component="'lucide-rows'" class="h-4 w-4 text-foresty" stroke-width="2.5" />
      {{-- <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg> --}}
    </div>
    {{-- square-dashed-top-solid --}}
    <div>
      <h4 class="text-xs font-bold text-foresty uppercase tracking-wider">Pengaturan Seksi</h4>
      <p class="text-[10px] text-slate-400">Blok di bawah batas ini akan dibungkus dengan gaya berikut:</p>
    </div>
  </div>

  <div class="flex items-center gap-4 ">
    {{-- Pilih Warna Latar --}}
    <div class="flex flex-col gap-1">
      <label class="text-[9px] font-bold text-foresty uppercase">Warna Latar</label>
      <select wire:model.live="content.{{ $blockId }}.data.background" class="text-xs bg-white text-foresty border-foresty rounded focus:ring-sage-soft py-1">
        <option value="bg-white">Putih</option>
        <option value="bg-gray-50">Abu-abu Terang</option>
        <option value="bg-foresty">Foresty (Hijau Gelap)</option>
        <option value="bg-mist">Mist (Abu Kebiruan)</option>
      </select>
    </div>

    {{-- Pilih Warna Teks --}}
    <div class="flex flex-col gap-1">
      <label class="text-[9px] font-bold text-slate-400 uppercase">Warna Teks Utama</label>
      <select wire:model.live="content.{{ $blockId }}.data.text_color" class="text-xs bg-white text-foresty border-foresty rounded focus:ring-foresty py-1">
        <option value="text-gray-900">Gelap (Default)</option>
        <option value="text-white">Terang (Putih)</option>
      </select>
    </div>

    {{-- Pilih Padding --}}
    <div class="flex flex-col gap-1">
      <label class="text-[9px] font-bold text-slate-400 uppercase">Jarak Luar (Padding)</label>
      <select wire:model.live="content.{{ $blockId }}.data.padding" class="text-xs bg-white text-foresty border-foresty rounded focus:ring-foresty py-1">
        <option value="py-8 sm:py-12">Sempit</option>
        <option value="py-16 sm:py-24">Sedang (Standar)</option>
        <option value="py-24 sm:py-[96px]">Lebar</option>
      </select>
    </div>
  </div>
</div>
