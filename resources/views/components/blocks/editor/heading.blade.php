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

    <!-- HEADER -->
    <div 
        class="flex items-center justify-between px-4 py-3 bg-gray-50/80 cursor-pointer select-none transition-colors hover:bg-gray-100"
        :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'"
        @click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)">

        <div class="flex items-center gap-2">
            {{-- Ikon Panah (Berputar saat diklik) --}}
            {{-- <svg class="w-4 h-4 text-gray-400 group-hover/heading:text-blue-500 transition-transform duration-200"
                 :class="isCollapsed ? '-rotate-90' : 'rotate-0'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg> --}}
            <x-dynamic-component 
                :component="'lucide-circle-chevron-down'" 
                class="h-4 w-4 text-gray-400 transition-transform duration-200" 
                ::class="isCollapsed ? '-rotate-90' : 'rotate-0'" 
                stroke-width="2.5" 
            />

            {{-- Label Identitas --}}
            <span class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest flex items-center gap-1.5">
                {{-- <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg> --}}
                <x-dynamic-component :component="'lucide-heading'" class="h-4 w-4 text-gray-400" stroke-width="2.5" />
                Judul (Heading)
            </span>
        </div>
        <div class="flex items-center gap-2" >
            {{-- Ini membantu staf mengenali blok mana yang sedang mereka seret --}}
            {{-- 🌟 FITUR UX: Cuplikan Teks saat Runtuh (Bersih dari HTML) --}}
            <div x-show="isCollapsed" x-cloak class="flex-1 px-4 truncate text-xs text-gray-400 text-right font-medium">
                <span x-text="($wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || '').replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'"></span>
            </div>
            <select wire:model="content.{{ $blockId }}.data.level" class="text-xs border border-gray-300 rounded-md py-0 px-2 bg-white text-gray-600 " @click.stop>
                <option value="h1">Heading 1</option>
                <option value="h2">Heading 2</option>
                <option value="h3">Heading 3</option>
            </select>
            {{-- Indikator Bahasa --}}
            <span class="text-[10px] font-bold text-foresty uppercase ml-2 bg-sage-soft px-1.5 py-0.5 rounded shadow-sm">{{ $code }}</span>
        </div>
    </div>

    {{-- Collapsed wrapper --}}
    <div x-show="!isCollapsed" x-collapse x-cloak class="p-2 space-y-4 rounded-lg border border-gray-200 bg-gray-50/50">
        {{-- <div class="flex items-center justify-between"  @click.stop>
            <label class="block text-[10px] font-semibold text-foresty uppercase">Judul / Heading ({{ strtoupper($code) }})</label>

            <!-- Opsional: Jika Anda ingin mempertahankan pilihan Level H2 / H3 -->

        {{-- <div class="flex items-center justify-between"  @click.stop>
            <label class="block text-[10px] font-semibold text-foresty uppercase">Judul / Heading ({{ strtoupper($code) }})</label>

            <!-- Opsional: Jika Anda ingin mempertahankan pilihan Level H2 / H3 -->
            
        </div> --}}

        {{-- Mengganti input text lama dengan Tiptap --}}
        <x-tiptap 
            :block-type="$block['type']" 
            wire:model="content.{{ $blockId }}.data.text.{{ $code }}"  
            placeholder="Blok Heading..." />

    </div>
</div>