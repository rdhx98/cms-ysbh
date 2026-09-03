@props([
    'blockId',
    'code',
    'block',
    'allContent' => []
])

@php
    $imageUrl = $block['data']['url'] ?? '';
@endphp

<div 
    {{-- id="block-wrapper-{{ $blockId }}"   --}}
    {{-- class="p-4 bg-white border border-gray-200 rounded-xl space-y-4 shadow-sm relative group" --}}
    class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 relative group space-y-2"
    x-data="{
         isDragging: false,
         isUploading: false,
         errorMessage: '',
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
        },
         
         // FUNGSI CERDAS: Menangkap Gambar ATAU Teks dari Paste
         handlePaste(event) {
             let cbData = event.clipboardData || window.clipboardData;
             if (!cbData || !cbData.items) return;

             for (let i = 0; i < cbData.items.length; i++) {
                 let item = cbData.items[i];
                 
                 // Jika yang ditempel adalah file gambar
                 if (item.type.indexOf('image') !== -1) {
                     event.preventDefault(); // Hentikan teks masuk ke input
                     event.stopPropagation();
                     
                     let file = item.getAsFile();
                     this.uploadFile(file); // Unggah gambar
                     return; // Hentikan proses
                 }
             }
             // Jika proses sampai di sini (bukan gambar), biarkan peramban 
             // menempelkan teks tautan secara normal ke dalam kolom input.
         },
         
         fileChosen(event) {
             if (event.target.files.length > 0) this.uploadFile(event.target.files[0]);
         },
         
         fileDropped(event) {
             this.isDragging = false;
             if (event.dataTransfer.files.length > 0) this.uploadFile(event.dataTransfer.files[0]);
         },
         
         uploadFile(file) {
             if (!file) return;
             this.errorMessage = '';

             const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
             if (!validTypes.includes(file.type)) {
                 this.errorMessage = 'Format tidak didukung. Harap gunakan JPG, PNG, WEBP, atau GIF.';
                 return;
             }

             if (file.size > 15 * 1024 * 1024) {
                 this.errorMessage = 'Ukuran gambar maksimal 15MB.';
                 return;
             }

             this.isUploading = true;

             let formData = new FormData();
             formData.append('image', file);

             fetch('{{ route('editor.upload-image') }}', {
                 method: 'POST',
                 headers: {
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                 },
                 body: formData
             })
             .then(response => {
                 if (!response.ok) throw new Error('Gagal mengunggah gambar.');
                 return response.json();
             })
             .then(data => {
                 if (data.url) $wire.set('content.{{ $blockId }}.data.url', data.url);
             })
             .catch(error => {
                 this.errorMessage = 'Terjadi kesalahan sistem saat mengunggah.';
                 console.error(error);
             })
             .finally(() => {
                 this.isUploading = false;
                 if(this.$refs.fileInput) this.$refs.fileInput.value = ''; 
             });
         }
     }"
    @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
    @toggle-collapse-all.window="isCollapsed = $event.detail"
    @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }">
    
    {{-- Header Blok & Kontrol Padding --}}
    <div 
        class="flex items-center justify-between px-4 py-3 bg-gray-50/80 cursor-pointer select-none transition-colors hover:bg-gray-100"
        :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'"
        @click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)">
        
        <div class="flex items-center gap-2">
            {{-- Ikon Panah (Berputar saat diklik) --}}
            <svg class="w-4 h-4 text-gray-400 group-hover/heading:text-blue-500 transition-transform duration-200"
                 :class="isCollapsed ? '-rotate-90' : 'rotate-0'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>

            {{-- Label Identitas --}}
            <x-dynamic-component :component="'lucide-image'" class="h-4 w-4 text-gray-400" stroke-width="2.5" />
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                {{-- <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> --}}
                Blok Gambar
            </span>

        </div>
        
        
        <div class="flex items-center gap-2" >
            {{-- Jika input caption HANYA teks biasa (bukan TipTap) --}}
            <div x-show="isCollapsed" x-cloak class="flex-1 px-4 truncate text-xs text-gray-400 text-right font-medium">
                <span x-text="$wire.get('content.{{ $blockId }}.data.caption.{{ $code }}') || 'Tanpa Keterangan...'"></span>
            </div>
            {{-- PADDING CONTROL --}}
            <div x-show="!isCollapsed" class="flex items-center gap-4 px-4 py-0" @click.stop>
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Jarak Atas:</label>
                    <select wire:model.live="content.{{ $blockId }}.data.padding_top" class="text-xs font-medium text-gray-600 border-gray-300 rounded py-1 pl-2 pr-6 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Default</option>
                        <option value="pt-0">0 (Nihil)</option>
                        <option value="pt-4">Kecil</option>
                        <option value="pt-8">Sedang</option>
                        <option value="pt-16">Besar</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Jarak Bawah:</label>
                    <select wire:model.live="content.{{ $blockId }}.data.padding_bottom" class="text-xs font-medium text-gray-600 border-gray-300 rounded py-1 pl-2 pr-6 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Default</option>
                        <option value="pb-0">0 (Nihil)</option>
                        <option value="pb-4">Kecil</option>
                        <option value="pb-8">Sedang</option>
                        <option value="pb-16">Besar</option>
                    </select>
                </div>
            </div>
            <span class="text-[10px] font-bold text-foresty uppercase ml-2 bg-sage-soft px-1.5 py-0.5 rounded shadow-sm">{{ $code }}</span>
        </div>

    </div>

    <!-- BODY AREA -->
    <div x-show="!isCollapsed" x-collapse x-cloak class="space-y-4 p-2">
        
        <div x-show="errorMessage" x-cloak class="px-3 py-2 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-lg">
            <span x-text="errorMessage"></span>
        </div>

        {{-- 🌟 INPUT URL (Pusat Penerima Paste Teks & Gambar) --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1.5 flex justify-between items-center">
                <span>URL Tautan / Tempel Gambar</span>
                <span class="text-[10px] text-gray-400 font-normal">Tekan Ctrl+V di sini</span>
            </label>
            <input type="text" 
                   wire:model.live.debounce.500ms="content.{{ $blockId }}.data.url" 
                   @keydown.stop 
                   @paste.stop="handlePaste($event)"
                   placeholder="Atau masukkan tautan gambar (https://...)"
                   class="w-full text-sm p-2.5 bg-gray-50 border border-gray-300 rounded-lg shadow-inner focus:ring-blue-500 focus:border-blue-500 transition-colors">
        </div>

        {{-- AREA DROPZONE --}}
        <div class="relative w-full rounded-lg overflow-hidden border-2 transition-all duration-200"
             :class="isDragging ? 'border-blue-500 bg-blue-50 shadow-md' : 'border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100'"
             @dragover.prevent="isDragging = true"
             @dragleave.prevent="isDragging = false"
             @drop.prevent="fileDropped($event)">

            <input type="file" x-ref="fileInput" class="hidden" accept="image/jpeg, image/png, image/webp, image/gif" @change="fileChosen">

            <div x-show="isUploading" x-cloak class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/90 backdrop-blur-sm">
                <svg class="animate-spin h-8 w-8 text-blue-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-xs font-bold text-gray-700 animate-pulse">Mengunggah & Mengompresi...</span>
            </div>

            <div x-show="isDragging" x-cloak class="absolute inset-0 z-10 flex items-center justify-center bg-blue-50/90 pointer-events-none">
                <span class="text-sm font-bold text-blue-600">Lepaskan untuk Mengunggah</span>
            </div>

            @if(!empty($imageUrl))
                <div class="relative flex flex-col items-center justify-center p-2 group/image min-h-[144px]">
                    <img src="{{ $imageUrl }}" alt="Pratinjau" class="max-h-56 object-contain rounded shadow-sm">
                    
                    <button type="button" @click="$refs.fileInput.click()" class="absolute inset-0 bg-black/60 text-white opacity-0 group-hover/image:opacity-100 transition-opacity flex items-center justify-center gap-2 font-semibold text-sm cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Ganti Gambar Baru
                    </button>
                </div>
            @else
                <div class="flex flex-col items-center justify-center h-36 cursor-pointer p-4 text-center" @click="$refs.fileInput.click()">
                    <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    <span class="text-sm text-gray-600 font-bold mb-1">Seret gambar, klik untuk cari</span>
                    <span class="text-[10px] text-gray-400">Atau paste gambar di kolom URL. Maks. 15MB</span>
                </div>
            @endif
        </div>

        {{-- INPUT CAPTION --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1.5 flex justify-between items-center">
                <span>Keterangan Gambar (Caption)</span>
                <span class="text-[10px] font-bold text-blue-500 uppercase">{{ $code }}</span>
            </label>
            <input type="text" 
                   wire:model.live.debounce.500ms="content.{{ $blockId }}.data.caption.{{ $code }}" 
                   @keydown.stop 
                   @paste.stop
                   placeholder="Tulis keterangan gambar di sini..."
                   class="w-full text-sm p-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors">
        </div>
        
    </div>

</div>