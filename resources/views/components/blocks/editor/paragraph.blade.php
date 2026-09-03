@props([
    'blockId',
    'code',
    'block',
    'allContent' => [] // Tambahkan fallback array kosong agar tidak error jika dipanggil di root
])

<div 
    {{-- id="block-wrapper-{{ $blockId }}"  --}}
    class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/heading space-y-2"
    x-data="{ 
        isCollapsed: false,
        init() {
            // 1. Saat dirender ulang, periksa apakah blok ini punya ingatan status
            window.blockCollapseState = window.blockCollapseState || {};
            if (window.blockCollapseState['{{ $blockId }}'] !== undefined) {
                this.isCollapsed = window.blockCollapseState['{{ $blockId }}'];
            }

            // 2. Setiap kali status berubah, titipkan ingatannya ke memori peramban
            this.$watch('isCollapsed', (value) => {
                window.blockCollapseState['{{ $blockId }}'] = value;
            });
        }
    }" 
    @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
    @toggle-collapse-all.window="isCollapsed = $event.detail"
    @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }">
    
    <!-- Header -->
    <div @click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
        class="flex items-center justify-between px-4 py-3 bg-gray-50/80 cursor-pointer select-none transition-colors hover:bg-gray-100"
        :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">
        <x-dynamic-component :component="'lucide-text-align-start'" class="h-4 w-4 text-gray-400" stroke-width="2.5" />
        <label class="block text-[10px] font-semibold text-gray-500 uppercase">Paragraf ({{ strtoupper($code) }})</label>

        {{-- Kontrol Margin & Padding --}}
        <div class="flex items-center gap-2" @click.stop>
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
    <div x-show="!isCollapsed" x-collapse x-cloak class=""p-4 space-y-4">
        <x-tiptap :block-type="$block['type']"  wire:model="content.{{ $blockId }}.data.text.{{ $code }}" placeholder="Blok Paragraf..."/>
    </div>
</div>
