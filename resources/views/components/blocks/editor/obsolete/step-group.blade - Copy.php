@props(['blockId', 'code', 'block', 'allContent'])

@php
    $data = $block['data'] ?? [];
    $children = $data['children'] ?? [];
    
    // Nilai Default jika belum diatur
    if (!isset($data['orientation'])) $data['orientation'] = 'vertical';
    if (!isset($data['gap'])) $data['gap'] = 'gap-8';
    if (!isset($data['node_color'])) $data['node_color'] = 'bg-foresty text-white';
    if (!isset($data['line_color'])) $data['line_color'] = 'bg-foresty/30';
@endphp

<div x-data="{ showSettings: false }" class="bg-gray-50 border border-gray-200 rounded-xl p-4 sm:p-5 shadow-sm">
    
    <!-- ==========================================
         HEADER BLOK & TOMBOL PENGATURAN
    ========================================== -->
    <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-white rounded-lg shadow-sm border border-gray-200">
                <x-lucide-list-ordered class="w-5 h-5 text-foresty" />
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">Grup Langkah (Timeline)</h3>
                <p class="text-[10px] text-gray-500">Susun konten menjadi proses yang berurutan</p>
            </div>
        </div>
        
        <button type="button" @click="showSettings = !showSettings" class="text-xs flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors shadow-sm font-bold text-gray-600 outline-none focus:ring-2 focus:ring-foresty">
            <x-lucide-settings-2 class="w-3.5 h-3.5" />
            <span x-text="showSettings ? 'Tutup Pengaturan' : 'Pengaturan'"></span>
        </button>
    </div>

    <!-- ==========================================
         PANEL PENGATURAN (Tampil saat diklik)
    ========================================== -->
    <div x-show="showSettings" x-collapse x-cloak>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-white p-5 rounded-xl border border-gray-200 mb-6 shadow-inner">
            
            <!-- Pengaturan Orientasi -->
            <div>
                <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1.5">Orientasi Layout</label>
                <select wire:model.live="content.{{ $blockId }}.data.orientation" class="w-full text-xs font-medium border-gray-300 rounded-lg shadow-sm focus:ring-foresty focus:border-foresty bg-gray-50">
                    <option value="vertical">Vertikal (Atas ke Bawah)</option>
                    <option value="horizontal-top">Horizontal (Angka di Atas)</option>
                    <option value="horizontal-bottom">Horizontal (Angka di Bawah)</option>
                </select>
            </div>
            
            <!-- Pengaturan Jarak (Gap) -->
            <div>
                <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1.5">Jarak Antar Langkah</label>
                <select wire:model.live="content.{{ $blockId }}.data.gap" class="w-full text-xs font-medium border-gray-300 rounded-lg shadow-sm focus:ring-foresty focus:border-foresty bg-gray-50">
                    <option value="gap-4">Rapat (gap-4)</option>
                    <option value="gap-8">Sedang (gap-8)</option>
                    <option value="gap-12">Renggang (gap-12)</option>
                    <option value="gap-16">Sangat Renggang (gap-16)</option>
                </select>
            </div>
            
            <!-- Warna Lingkaran Angka -->
            <div>
                <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1.5">Warna Angka (Tailwind)</label>
                <input type="text" wire:model.live.debounce.500ms="content.{{ $blockId }}.data.node_color" placeholder="Contoh: bg-foresty text-white" class="w-full text-xs font-mono border-gray-300 rounded-lg shadow-sm focus:ring-foresty focus:border-foresty bg-gray-50">
            </div>
            
            <!-- Warna Garis Penghubung -->
            <div>
                <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1.5">Warna Garis (Tailwind)</label>
                <input type="text" wire:model.live.debounce.500ms="content.{{ $blockId }}.data.line_color" placeholder="Contoh: bg-foresty/30" class="w-full text-xs font-mono border-gray-300 rounded-lg shadow-sm focus:ring-foresty focus:border-foresty bg-gray-50">
            </div>

        </div>
    </div>

    <!-- ==========================================
         AREA RENDER ANAK (LANGKAH-LANGKAH)
    ========================================== -->
    <div class="relative pl-7 sm:pl-10 border-l-[3px] border-gray-200 ml-4 sm:ml-5 space-y-8">
        
        @foreach($children as $index => $childId)
            @php 
                $childBlock = $allContent[$childId] ?? null; 
            @endphp
            
            @if($childBlock)
                <div class="relative group/step">
                    
                    <!-- Lingkaran Indikator Angka (Simulasi UI Editor) -->
                    <div class="absolute -left-[42px] sm:-left-[58px] top-4 w-7 h-7 sm:w-9 sm:h-9 bg-white border-[3px] border-gray-200 rounded-full flex items-center justify-center text-xs font-extrabold text-gray-400 z-10 group-hover/step:border-foresty group-hover/step:text-foresty transition-colors shadow-sm">
                        {{ $index + 1 }}
                    </div>

                    <!-- Tombol Hapus Langkah -->
<button type="button" 
        wire:click="removeNestedBlock('{{ $blockId }}', 'children', '{{ $childId }}')" 
        class="absolute -right-3 -top-3 z-20 w-7 h-7 bg-white border border-gray-200 text-red-500 rounded-full flex items-center justify-center opacity-0 group-hover/step:opacity-100 transition-all hover:bg-red-500 hover:text-white hover:border-red-500 shadow-md" 
        title="Hapus Langkah Ini">
    <x-lucide-trash-2 class="w-3.5 h-3.5" />
</button>
                    
                    <!-- Tombol Hapus Langkah -->
                    {{-- <button type="button" 
                            wire:click="removeBlock('{{ $childId }}', '{{ $blockId }}')" 
                            class="absolute -right-3 -top-3 z-20 w-7 h-7 bg-white border border-gray-200 text-red-500 rounded-full flex items-center justify-center opacity-0 group-hover/step:opacity-100 transition-all hover:bg-red-500 hover:text-white hover:border-red-500 shadow-md" 
                            title="Hapus Langkah Ini">
                        <x-lucide-trash-2 class="w-3.5 h-3.5" />
                    </button> --}}

                    <!-- Render Pembungkus Komponen Anak -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-1 transition-all group-hover/step:border-foresty/50 group-hover/step:shadow-md">
                        @php
                            $childComponent = 'blocks.editor.' . str_replace('_', '-', $childBlock['type']);
                        @endphp
                        
                        {{-- Render langsung komponen anak (misal: card-builder atau paragraph) --}}
                        <x-dynamic-component 
                            :component="$childComponent" 
                            :block-id="$childId" 
                            :code="$code" 
                            :block="$childBlock" 
                            :all-content="$allContent" 
                        />
                    </div>

                </div>
            @endif
        @endforeach

        <!-- ==========================================
             TOMBOL TAMBAH LANGKAH BARU
        ========================================== -->
        <div class="pt-2">
            <!-- 
              Pastikan Anda menyesuaikan pemanggilan aksi Livewire di bawah ini.
              Jika Anda memakai addChildBlock, gunakan itu. 
              Sebagai contoh, kita tambahkan blok 'card-builder' ke dalam parent ini.
            -->
            <div class="pt-2">
    <!-- Parameter: ID Induk, Nama Zona Array, Tipe Blok Baru yang dimasukkan -->
    <button type="button" 
            wire:click="addChildBlock('{{ $blockId }}', 'children', 'card-builder')" 
            class="flex items-center gap-2 text-xs font-extrabold tracking-wide uppercase bg-white border-2 border-dashed border-gray-300 text-gray-400 hover:text-foresty hover:border-foresty hover:bg-foresty/5 px-4 py-3 rounded-xl transition-all shadow-sm w-full justify-center">
        <x-lucide-plus-circle class="w-4 h-4" />
        Tambah Langkah
    </button>
</div>
            {{-- <button type="button" 
                    wire:click="addChildBlock('{{ $blockId }}', 'card-builder')" 
                    class="flex items-center gap-2 text-xs font-extrabold tracking-wide uppercase bg-white border-2 border-dashed border-gray-300 text-gray-400 hover:text-foresty hover:border-foresty hover:bg-foresty/5 px-4 py-3 rounded-xl transition-all shadow-sm w-full justify-center">
                <x-lucide-plus-circle class="w-4 h-4" />
                Tambah Langkah
            </button> --}}
        </div>

    </div>
</div>