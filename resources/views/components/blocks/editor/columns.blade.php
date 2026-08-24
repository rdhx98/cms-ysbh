@props([
    'blockId',
    'code',
    'block',
    'allContent' // Diterima dari parent untuk mencari data anak
])

@php
    $leftZone = $block['data']['left_zone'] ?? [];
    $rightZone = $block['data']['right_zone'] ?? [];
@endphp

<div class="p-4 bg-gray-50 border border-gray-200 rounded-xl space-y-4 shadow-sm">

    {{-- Header Kontainer --}}
    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
        <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">📦 Kontainer: 2 Kolom</span>
        {{-- Di sini nanti Anda bisa menambahkan select pengaturan spacing/background kontainer --}}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- ================= ZONA KIRI ================= --}}
        <div class="space-y-2">
            <div class="text-[10px] font-bold text-gray-400 uppercase text-center bg-gray-200 py-1 rounded-md">Kolom Kiri</div>

            {{-- Area Sortable Kiri --}}
            <div x-data="{
                    initSortable() {
                        new Sortable(this.$refs.leftZone, {
                            group: 'left_zone_{{ $blockId }}', // Kunci khusus agar tidak bisa keluar
                            animation: 150,
                            handle: '.child-drag-handle',
                            onEnd: (evt) => {
                                let order = Array.from(evt.to.children).map(el => el.getAttribute('data-id')).filter(Boolean);
                                $wire.reorderChildBlocks('{{ $blockId }}', 'left_zone', order);
                            }
                        });
                    }
                }"
                x-init="initSortable"
                x-ref="leftZone"
                class="min-h-[100px] border-2 border-dashed border-gray-200 bg-white rounded-lg p-3 space-y-3"
            >
                @foreach($leftZone as $childId)
                    @if(isset($allContent[$childId]))
                        @php $childBlock = $allContent[$childId]; @endphp

                        {{-- Wrapper Anak --}}
                        <div data-id="{{ $childId }}" class="relative group">

                            {{-- Handle Drag Anak --}}
                            <div class="child-drag-handle absolute -left-3 top-2 opacity-0 group-hover:opacity-100 cursor-move text-gray-400 z-10 bg-white rounded-full shadow p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                            </div>

                            {{-- Render Blok Anak Secara Dinamis --}}
                            <x-dynamic-component
                                :component="'blocks.editor.' . str_replace('_', '-', $childBlock['type'])"
                                :block-id="$childId"
                                :code="$code"
                                :block="$childBlock"
                                :all-content="$allContent"
                            />
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Tombol Tambah Anak Kiri --}}
            <button type="button" wire:click="addChildBlock('{{ $blockId }}', 'left_zone', 'paragraph')" class="w-full py-2 border border-dashed border-gray-300 text-xs font-medium text-gray-500 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 rounded-lg transition-colors">
                + Tambah Blok (Kiri)
            </button>
        </div>


        {{-- ================= ZONA KANAN ================= --}}
        <div class="space-y-2">
            <div class="text-[10px] font-bold text-gray-400 uppercase text-center bg-gray-200 py-1 rounded-md">Kolom Kanan</div>

            {{-- Area Sortable Kanan --}}
            <div x-data="{
                    initSortable() {
                        new Sortable(this.$refs.rightZone, {
                            group: 'right_zone_{{ $blockId }}', // Grup kanan
                            animation: 150,
                            handle: '.child-drag-handle',
                            onEnd: (evt) => {
                                let order = Array.from(evt.to.children).map(el => el.getAttribute('data-id')).filter(Boolean);
                                $wire.reorderChildBlocks('{{ $blockId }}', 'right_zone', order);
                            }
                        });
                    }
                }"
                x-init="initSortable"
                x-ref="rightZone"
                class="min-h-[100px] border-2 border-dashed border-gray-200 bg-white rounded-lg p-3 space-y-3"
            >
                @foreach($rightZone as $childId)
                    @if(isset($allContent[$childId]))
                        @php $childBlock = $allContent[$childId]; @endphp
                        <div data-id="{{ $childId }}" class="relative group">
                            <div class="child-drag-handle absolute -left-3 top-2 opacity-0 group-hover:opacity-100 cursor-move text-gray-400 z-10 bg-white rounded-full shadow p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                            </div>
                            <x-dynamic-component
                                :component="'blocks.editor.' . str_replace('_', '-', $childBlock['type'])"
                                :block-id="$childId"
                                :code="$code"
                                :block="$childBlock"
                                :all-content="$allContent"
                            />
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Tombol Tambah Anak Kanan --}}
            <button type="button" wire:click="addChildBlock('{{ $blockId }}', 'right_zone', 'paragraph')" class="w-full py-2 border border-dashed border-gray-300 text-xs font-medium text-gray-500 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 rounded-lg transition-colors">
                + Tambah Blok (Kanan)
            </button>
        </div>

    </div>
</div>
