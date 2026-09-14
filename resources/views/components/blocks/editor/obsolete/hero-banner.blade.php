@props([
    'blockId',
    'code',
    'block',
    'allContent' => [] // Tambahkan fallback array kosong agar tidak error jika dipanggil di root
])

<div 
    {{-- id="block-wrapper-{{ $blockId }}"  --}}
    class="space-y-6 bg-gray-50 p-5 rounded-xl border border-gray-200">
    <div class="border-b border-gray-200 pb-2 flex justify-between items-center">
        <span class="text-xs font-bold text-gray-500 uppercase">Pengaturan Hero Banner</span>

        <button wire:click="removeBlock('{{ $blockId }}')" type="button" class="text-red-500 hover:bg-red-50 p-1.5 rounded-md transition-colors" title="Hapus Blok">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- KOLOM KIRI: TEKS UTAMA --}}
        <div class="space-y-4">
            <h4 class="font-bold text-sm text-foresty border-b pb-1">1. Konten Teks</h4>

            <div>
                <label class="block text-[10px] text-gray-400 font-semibold mb-1">TAGLINE (Ikon & Teks)</label>
                <div class="flex gap-2 items-start">
                    <div class="w-1/3 shrink-0">
                        <x-editor.icon-picker wire:model="content.{{ $blockId }}.data.tagline_icon" />
                    </div>
                    <div class="flex-1">
                        <input type="text" wire:model="content.{{ $blockId }}.data.tagline_text.{{ $code }}" placeholder="Misal: Yayasan Kesehatan..." class="w-full text-sm border-gray-300 rounded-md py-1.5 focus:ring-foresty focus:border-foresty">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] text-gray-400 font-semibold mb-1">JUDUL UTAMA (Gunakan tag &lt;em&gt; untuk cetak miring/warna berbeda)</label>
                <textarea wire:model="content.{{ $blockId }}.data.title.{{ $code }}" rows="3" placeholder="Menyalakan <em>Sinar Sehat</em>..." class="w-full text-sm border-gray-300 rounded-md focus:ring-foresty focus:border-foresty"></textarea>
            </div>

            <div>
                <label class="block text-[10px] text-gray-400 font-semibold mb-1">DESKRIPSI</label>
                <textarea wire:model="content.{{ $blockId }}.data.description.{{ $code }}" rows="4" placeholder="Kami mendampingi ibu hamil..." class="w-full text-sm border-gray-300 rounded-md focus:ring-foresty focus:border-foresty"></textarea>
            </div>
        </div>

        {{-- KOLOM KANAN: MEDIA & TATA LETAK --}}
        <div class="space-y-4">
            <h4 class="font-bold text-sm text-foresty border-b pb-1">2. Media (Sebelah Teks)</h4>

            <div class="flex gap-4 items-start">
                <div class="w-1/3 shrink-0">
                    <label class="block text-[10px] text-gray-400 font-semibold mb-1">TIPE MEDIA</label>
                    <select wire:model="content.{{ $blockId }}.data.media_type" class="w-full text-sm border-gray-300 rounded-md py-1.5 focus:ring-foresty focus:border-foresty">
                        <option value="svg">Kode SVG</option>
                        <option value="image">URL Gambar</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] text-gray-400 font-semibold mb-1">KODE SVG / URL GAMBAR</label>
                    <textarea wire:model="content.{{ $blockId }}.data.media_content" rows="4" class="w-full text-sm border-gray-300 rounded-md focus:ring-foresty focus:border-foresty font-mono text-xs"></textarea>
                </div>
            </div>

            <h4 class="font-bold text-sm text-foresty border-b pb-1 mt-6">3. Pengaturan Tata Letak</h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] text-gray-400 font-semibold mb-1">POSISI GAMBAR/MEDIA</label>
                    <select wire:model="content.{{ $blockId }}.data.layout_image_position" class="w-full text-sm border-gray-300 rounded-md py-1.5 focus:ring-foresty focus:border-foresty">
                        <option value="right">Di Kanan (Default)</option>
                        <option value="left">Di Kiri</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] text-gray-400 font-semibold mb-1">POSISI BARIS TOMBOL</label>
                    <select wire:model="content.{{ $blockId }}.data.layout_button_order" class="w-full text-sm border-gray-300 rounded-md py-1.5 focus:ring-foresty focus:border-foresty">
                        <option value="bottom">Bawah Paragraf (Default)</option>
                        <option value="top">Atas Paragraf</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- FULL WIDTH: TOMBOL & PILL --}}
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-8 border-t border-gray-200 pt-6 mt-2">

            {{-- AREA TOMBOL UTAMA --}}
            <div>
                <div class="flex justify-between items-center border-b pb-1 mb-3">
                    <h4 class="font-bold text-sm text-foresty">4. Tombol Aksi</h4>
                    <button wire:click="addArrayItem('{{ $blockId }}', 'buttons')" type="button" class="text-[10px] bg-white border border-gray-300 px-2 py-1 rounded hover:bg-gray-50 font-bold">+ TAMBAH TOMBOL</button>
                </div>

                <div class="space-y-3">
                    @forelse($block['data']['buttons'] ?? [] as $index => $btn)
                        <div class="p-3 bg-white border border-dashed border-gray-300 rounded-lg relative group">
                            <button wire:click="removeArrayItem('{{ $blockId }}', 'buttons', {{ $index }})" type="button" class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus Tombol">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[8px] text-gray-400 font-bold mb-1">TEKS TOMBOL</label>
                                    <input type="text" wire:model="content.{{ $blockId }}.data.buttons.{{ $index }}.text.{{ $code }}" class="w-full text-xs border-gray-300 rounded-md py-1.5 focus:ring-foresty">
                                </div>
                                <div>
                                    <label class="block text-[8px] text-gray-400 font-bold mb-1">GAYA TOMBOL</label>
                                    <select wire:model="content.{{ $blockId }}.data.buttons.{{ $index }}.style" class="w-full text-xs border-gray-300 rounded-md py-1.5 focus:ring-foresty">
                                        <option value="solid_coral">Solid Merah</option>
                                        <option value="outline_foresty">Garis Hijau</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[8px] text-gray-400 font-bold mb-1">TAUTAN (LINK)</label>
                                    <x-editor.link-picker typeModel="content.{{ $blockId }}.data.buttons.{{ $index }}.link_type" urlModel="content.{{ $blockId }}.data.buttons.{{ $index }}.url" />
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic text-center py-2">Belum ada tombol.</p>
                    @endforelse
                </div>
            </div>

            {{-- AREA PILL (BADGE KECIL) --}}
            <div>
                <div class="flex justify-between items-center border-b pb-1 mb-3">
                    <h4 class="font-bold text-sm text-foresty">5. Tanda Pengenal (Pill)</h4>
                    <button wire:click="addArrayItem('{{ $blockId }}', 'pills')" type="button" class="text-[10px] bg-white border border-gray-300 px-2 py-1 rounded hover:bg-gray-50 font-bold">+ TAMBAH PILL</button>
                </div>

                <div class="space-y-3">
                    @forelse($block['data']['pills'] ?? [] as $index => $pill)
                        <div class="p-3 bg-white border border-dashed border-gray-300 rounded-lg relative group">
                            <button wire:click="removeArrayItem('{{ $blockId }}', 'pills', {{ $index }})" type="button" class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus Pill">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>

                            <div class="grid grid-cols-[1fr_2fr] gap-3 mb-3">
                                <div>
                                    <label class="block text-[8px] text-gray-400 font-bold mb-1">IKON</label>
                                    <x-editor.icon-picker wire:model="content.{{ $blockId }}.data.pills.{{ $index }}.icon" />
                                </div>
                                <div>
                                    <label class="block text-[8px] text-gray-400 font-bold mb-1">TEKS PILL</label>
                                    <input type="text" wire:model="content.{{ $blockId }}.data.pills.{{ $index }}.text.{{ $code }}" class="w-full text-xs border-gray-300 rounded-md py-1.5 focus:ring-foresty">
                                </div>
                            </div>
                            <div class="grid grid-cols-[1fr_2fr] gap-3">
                                <div>
                                    <label class="block text-[8px] text-gray-400 font-bold mb-1">WARNA LATAR IKON</label>
                                    <input type="color" wire:model="content.{{ $blockId }}.data.pills.{{ $index }}.bg_color" class="w-full h-7 p-0 border-0 rounded-md cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-[8px] text-gray-400 font-bold mb-1">TAUTAN (OPSIONAL)</label>
                                    <input type="text" wire:model="content.{{ $blockId }}.data.pills.{{ $index }}.url" placeholder="#program atau /url" class="w-full text-xs border-gray-300 rounded-md py-1.5 focus:ring-foresty">
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic text-center py-2">Belum ada pill.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
