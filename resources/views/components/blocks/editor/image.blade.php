@props([
    'blockId',
    'code',
    'block',
    'allContent' => []
])

@php
    // Mengambil URL dari data, jika ada
    $imageUrl = $block['data']['url'] ?? '';
@endphp

<div class="p-4 bg-white border border-gray-200 rounded-xl space-y-4 shadow-sm relative group"
     x-data="{
         isDragging: false,
         isUploading: false,
         errorMessage: '',
         
         // 1. Fungsi saat file dipilih lewat tombol
         fileChosen(event) {
             if (event.target.files.length > 0) {
                 this.uploadFile(event.target.files[0]);
             }
         },
         
         // 2. Fungsi saat file dilepas (drop)
         fileDropped(event) {
             this.isDragging = false;
             if (event.dataTransfer.files.length > 0) {
                 this.uploadFile(event.dataTransfer.files[0]);
             }
         },
         
         // 3. Mesin Utama Upload
         uploadFile(file) {
             if (!file) return;
             this.errorMessage = '';

             // A. Validasi Ekstensi/MIME Type
             const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
             if (!validTypes.includes(file.type)) {
                 this.errorMessage = 'Format tidak didukung. Harap gunakan JPG, PNG, WEBP, atau GIF.';
                 return;
             }

             // B. Validasi Ukuran (15MB = 15 * 1024 * 1024 byte)
             if (file.size > 15 * 1024 * 1024) {
                 this.errorMessage = 'Ukuran gambar terlalu besar. Maksimal 15MB.';
                 return;
             }

             this.isUploading = true;

             // Siapkan data untuk dikirim
             let formData = new FormData();
             formData.append('image', file);

             // C. Panggil Route Laravel Anda menggunakan Fetch API
             fetch('{{ route('editor.upload-image') }}', {
                 method: 'POST',
                 headers: {
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                 },
                 body: formData
             })
             .then(response => {
                 if (!response.ok) {
                     throw new Error('Gagal mengunggah gambar ke server.');
                 }
                 return response.json();
             })
             .then(data => {
                 if (data.url) {
                     // D. Sinkronisasi sukses! Beritahu Livewire untuk menyimpan URL
                     $wire.set('content.{{ $blockId }}.data.url', data.url);
                 }
             })
             .catch(error => {
                 this.errorMessage = 'Terjadi kesalahan sistem saat mengunggah.';
                 console.error(error);
             })
             .finally(() => {
                 this.isUploading = false;
                 // Bersihkan input file agar bisa upload gambar yang sama berulang kali jika perlu
                 $refs.fileInput.value = ''; 
             });
         }
     }">
    
    {{-- Header Blok --}}
    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            Blok Gambar
        </span>
    </div>

    <div class="space-y-4">
        
        {{-- Penampil Pesan Error --}}
        <div x-show="errorMessage" x-cloak class="px-3 py-2 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-lg">
            <span x-text="errorMessage"></span>
        </div>

        {{-- Input URL Manual (Tetap dipertahankan untuk gambar eksternal) --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1.5">URL / Tautan Gambar</label>
            <input type="text" 
                   wire:model.live.debounce.500ms="content.{{ $blockId }}.data.url" 
                   placeholder="Contoh: https://web.com/gambar.jpg"
                   class="w-full text-sm p-2.5 bg-gray-50 border border-gray-300 rounded-lg shadow-inner focus:ring-blue-500 focus:border-blue-500 transition-colors">
        </div>

        {{-- AREA DROPZONE & PRATINJAU GAMBAR --}}
        <div class="relative w-full rounded-lg overflow-hidden border-2 transition-all duration-200"
             :class="isDragging ? 'border-blue-500 bg-blue-50 shadow-md' : 'border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100'"
             @dragover.prevent="isDragging = true"
             @dragleave.prevent="isDragging = false"
             @drop.prevent="fileDropped($event)">

            {{-- Hidden File Input --}}
            <input type="file" x-ref="fileInput" class="hidden" accept="image/jpeg, image/png, image/webp, image/gif" @change="fileChosen">

            {{-- 🌟 Layar Loading (Muncul saat proses fetch berlangsung) --}}
            <div x-show="isUploading" x-cloak class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/90 backdrop-blur-sm">
                <svg class="animate-spin h-8 w-8 text-blue-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-xs font-bold text-gray-700 animate-pulse">Mengunggah & Mengompresi...</span>
            </div>

            {{-- 🌟 Overlay saat File Diseret --}}
            <div x-show="isDragging" x-cloak class="absolute inset-0 z-10 flex items-center justify-center bg-blue-50/90 pointer-events-none">
                <span class="text-sm font-bold text-blue-600">Lepaskan untuk Mengunggah</span>
            </div>

            {{-- 🌟 Kondisi JIKA Gambar Sudah Terisi --}}
            @if(!empty($imageUrl))
                <div class="relative flex flex-col items-center justify-center p-2 group/image min-h-[144px]">
                    <img src="{{ $imageUrl }}" alt="Pratinjau" class="max-h-56 object-contain rounded shadow-sm">
                    
                    {{-- Tombol Ganti Cepat (Tampil saat di-hover) --}}
                    <button type="button" @click="$refs.fileInput.click()" class="absolute inset-0 bg-black/60 text-white opacity-0 group-hover/image:opacity-100 transition-opacity flex items-center justify-center gap-2 font-semibold text-sm cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Ganti Gambar Baru
                    </button>
                </div>
                
            {{-- 🌟 Kondisi JIKA Gambar Masih Kosong --}}
            @else
                <div class="flex flex-col items-center justify-center h-36 cursor-pointer p-4 text-center" @click="$refs.fileInput.click()">
                    <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    <span class="text-sm text-gray-600 font-bold mb-1">Seret & lepas gambar ke sini</span>
                    <span class="text-[10px] text-gray-400">Maks. 15MB (JPG, PNG, WEBP, GIF)</span>
                    <button type="button" class="mt-3 px-4 py-1.5 text-xs font-semibold text-blue-600 border border-blue-200 bg-blue-50 rounded-lg shadow-sm hover:bg-blue-100 transition">
                        Atau Cari File
                    </button>
                </div>
            @endif
        </div>
        
    </div>
</div>