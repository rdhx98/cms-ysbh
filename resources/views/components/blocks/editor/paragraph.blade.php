@props([
    'blockId',
    'code',
    'block',
    'allContent' => [] // Tambahkan fallback array kosong agar tidak error jika dipanggil di root
])

<div id="block-wrapper-{{ $blockId }}" class="space-y-2">
    {{-- Header Blok --}}
    <div class="flex items-center justify-between">
        <label class="block text-[10px] font-semibold text-gray-500 uppercase">Paragraf ({{ strtoupper($code) }})</label>

        {{-- Kontrol Margin & Padding --}}
        <div class="flex items-center gap-2">
            <div class="flex items-center border border-gray-200 rounded text-xs">
                <span class="px-2 text-gray-400">Jarak Atas</span>
                <select wire:model="content.{{ $blockId }}.data.spacing.mt" class="border-0 py-0.5 text-xs bg-gray-50 rounded-r focus:ring-0">
                    <option value="0px">0</option>
                    <option value="16px">Normal</option>
                    <option value="32px">Lebar</option>
                    <option value="64px">Sangat Lebar</option>
                </select>
            </div>
            <div class="flex items-center border border-gray-200 rounded text-xs">
                <span class="px-2 text-gray-400">Bawah</span>
                <select wire:model="content.{{ $blockId }}.data.spacing.mb" class="border-0 py-0.5 text-xs bg-gray-50 rounded-r focus:ring-0">
                    <option value="0px">0</option>
                    <option value="16px">Normal</option>
                    <option value="32px">Lebar</option>
                    <option value="64px">Sangat Lebar</option>
                </select>
            </div>
            {{-- Anda bisa menduplikasi select di atas untuk pt (padding top) dan pb (padding bottom) jika diperlukan --}}
        </div>
    </div>

    {{-- <label class="block text-[10px] font-semibold text-foresty uppercase">Teks Paragraf </label> --}}
    <x-tiptap :block-type="$block['type']"  wire:model="content.{{ $blockId }}.data.text.{{ $code }}" placeholder="Sweet Escape....."/>
</div>
