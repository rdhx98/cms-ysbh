@props([
    'blockId',
    'code',
    'block',
    'allContent' => [] // Tambahkan fallback array kosong agar tidak error jika dipanggil di root
])

<div id="block-wrapper-{{ $blockId }}" class="space-y-4 bg-gray-50 p-4 rounded-xl border border-gray-200">

    <div class="flex flex-wrap items-center justify-between border-b border-gray-200 pb-3 gap-4">
        <span class="text-xs font-bold text-gray-500 uppercase">Grid Info / Statistik</span>

        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-3 bg-white px-3 py-1 rounded-md border border-gray-200 shadow-sm">
                <div class="flex items-center gap-1.5" title="Warna Angka Utama">
                    <span class="text-[10px] font-bold text-gray-400">ANGKA</span>
                    <input type="color" wire:model="content.{{ $blockId }}.data.color_title" class="w-5 h-5 p-0 border-0 rounded cursor-pointer bg-transparent">
                </div>
                <div class="flex items-center gap-1.5" title="Warna Teks Deskripsi">
                    <span class="text-[10px] font-bold text-gray-400">TEKS</span>
                    <input type="color" wire:model="content.{{ $blockId }}.data.color_desc" class="w-5 h-5 p-0 border-0 rounded cursor-pointer bg-transparent">
                </div>
                <div class="flex items-center gap-1.5" title="Warna Garis Kotak">
                    <span class="text-[10px] font-bold text-gray-400">GARIS</span>
                    <input type="color" wire:model="content.{{ $blockId }}.data.color_border" class="w-5 h-5 p-0 border-0 rounded cursor-pointer bg-transparent">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500">Baris:</span>
                <select wire:model="content.{{ $blockId }}.data.columns" class="text-xs border-gray-300 rounded-md py-1 shadow-sm">
                    <option value="2">2 Kolom</option>
                    <option value="3">3 Kolom</option>
                    <option value="4">4 Kolom</option>
                    <option value="5">5 Kolom</option>
                    <option value="6">6 Kolom</option>
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($block['data']['items'] as $itemIndex => $item)
            <div class="p-4 bg-white border border-dashed border-gray-300 rounded-lg relative group">

                <button wire:click="removeStatItem('{{ $blockId }}', {{ $itemIndex }})" type="button" class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus Kotak">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <div class="space-y-3">
                    <div>
                        <label class="block text-[10px] text-gray-400 font-semibold mb-1">ANGKA UTAMA / JUDUL</label>
                        <input type="text" wire:model="content.{{ $blockId }}.data.items.{{ $itemIndex }}.title.{{ $code }}" placeholder="Misal: 7 Tahun" class="w-full text-lg font-bold text-center border-gray-300 rounded-md shadow-sm py-1">
                    </div>
                    <div>
                        <label class="block text-[10px] text-gray-400 font-semibold mb-1">DESKRIPSI SINGKAT</label>
                        <textarea wire:model="content.{{ $blockId }}.data.items.{{ $itemIndex }}.description.{{ $code }}" rows="2" placeholder="Teks kecil di bawahnya..." class="w-full text-xs text-center border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(count($block['data']['items']) < 6)
        <button wire:click="addStatItem('{{ $blockId }}')" type="button" class="w-full py-2 border-2 border-dashed border-gray-300 text-gray-500 text-xs font-bold rounded-lg hover:bg-white hover:text-foersty transition shadow-sm">
            + Tambah Kotak Info
        </button>
    @endif
</div>
