{{-- EDITOR COMPONENTS CONTAINER INIT OLD--}}
@props(['editable' => true])
<div class="transition-colors duration-300 ease-in-out w-full flex-1 flex flex-col min-h-0">

    @include('components.editor.toolbars')
    @include('components.editor.bubble-image')
    @include('components.editor.bubble-text')

    {{-- EDITOR's AREA --}}
    <div wire:ignore wire:key="tiptap-editor-shell" class="flex-1 relative w-full h-full flex flex-col overflow-hidden bg-paper dark:bg-zinc-950" >
        {{-- 🌌 ZONA DROP OVERLAY GLOBAL (SUNTIKAN PASIF SAH) --}}
        <div @dragenter.window.prevent="isUploading || isLinkOpen || (window.tiptapEditor && window.tiptapEditor.isActive('mediaPlaceholder')) ? (isLocalDrag = false) : (isLocalDrag = $event.dataTransfer.types.includes('Files'))" @dragover.window.prevent="isUploading || isLinkOpen || (window.tiptapEditor && window.tiptapEditor.isActive('mediaPlaceholder')) ? (isLocalDrag = false) : (isLocalDrag = $event.dataTransfer.types.includes('Files'))" @dragleave.window.prevent="event.clientX === 0 && event.clientY === 0 ? isLocalDrag = false : null" @drop.window.prevent="isLocalDrag = false; handleMultipleImageUpload($event.dataTransfer.files);" x-show="isLocalDrag" x-transition class="absolute inset-0 z-50 bg-sage-soft/90 backdrop-blur-sm p-4 flex items-center justify-center" style="display: none;">
            <div class="border-2 border-dashed border-forest dark:border-amber-500 rounded-xl w-full h-full flex flex-col items-center justify-center gap-3 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-xs shadow-inner">
                <div class="p-4 bg-white dark:bg-zinc-800 rounded-full shadow-md text-forest dark:text-amber-500 animate-bounce">
                    <x-dynamic-component :component="'lucide-image-plus'" class="h-8 w-8" stroke-width="2" />
                </div>
                <div class="text-center">
                    <h3 class="text-sm font-bold text-forest dark:text-zinc-100 tracking-wide">Lepaskan
                        Gambar di Sini</h3>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Berkas otomatis diunggah ke
                        server Yayasan SBH</p>
                </div>
            </div>
        </div>

        {{-- MODAL INPUT LINK: Sekarang posisinya mutlak di tengah atas AREA TEKS saja --}}
        <div x-show="isLinkOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 -translate-y-2 scale-95" @click.away="isLinkOpen = false; clearLinkInputs();" {{-- 💡 KUNCI POSISI: top-4 membuat modal berjarak sedikit dari batas bawah toolbar --}} class="absolute left-1/2 top-4 -translate-x-1/2 w-80 bg-white p-4 rounded-md shadow-2xl ring-1 ring-black ring-opacity-5 z-40 border border-zinc-200" style="display: none;">
            <div class="flex flex-col gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Teks Tautan (Title)</label>
                    <input type="text" x-model="linkInputText" placeholder="Masukkan teks tampilan..."
                        class="w-full text-sm px-2 py-1.5 border rounded focus:outline-none focus:border-emerald-500 bg-white text-zinc-800"
                        @keydown.enter.prevent="submitLink(); isLinkOpen = false;" />
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">URL Tujuan</label>
                    <input type="text" x-model="linkInputUrl" placeholder="https://example.com"
                        class="w-full text-sm px-2 py-1.5 border rounded focus:outline-none focus:border-emerald-500 bg-white text-zinc-800"
                        @keydown.enter.prevent="submitLink(); isLinkOpen = false;" />
                </div>

                <div class="flex justify-end gap-2 text-xs pt-1 border-t border-gray-100">
                    <template x-if="checkButtonActive('link', {}, 'default')">
                        <button type="button" @click="unsetLink(); isLinkOpen = false;"
                            class="text-red-500 px-2 py-1 hover:underline mr-auto cursor-pointer">
                            Copot Link
                        </button>
                    </template>
                    <button type="button" @click="isLinkOpen = false; clearLinkInputs();"
                        class="text-gray-500 px-3 py-1 hover:bg-gray-100 rounded cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="submitLink(); isLinkOpen = false;"
                        class="bg-emerald-600 text-white px-3 py-1 rounded hover:bg-emerald-700 font-medium cursor-pointer">
                        Terapkan
                    </button>
                </div>
            </div>
        </div>

        {{-- UPLOADING IMAGES INDICATOR wire:loading --}}
        <div x-show="isUploading" x-transition class="absolute left-1/2 top-4 -translate-x-1/2 z-40"
            style="display: none;">

            {{-- Box styling dibuat senada dengan komponen Sage-Soft & Amber Alert --}}
            <div
                class="bg-amber-50 dark:bg-zinc-800 border border-amber-200 dark:border-amber-900/50 px-4 py-2 rounded-full shadow-lg flex items-center gap-2 select-none">
                {{-- Efek putaran kustom CSS internal --}}
                <svg class="animate-spin h-4 w-4 text-amber-600" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span
                    class="text-xs text-amber-700 dark:text-amber-400 font-semibold animate-pulse tracking-wide">
                    ⏳ Mengunggah berkas gambar...
                </span>
            </div>
        </div>

        {{-- AREA WRAPPER TEXT UTAMA TIPTAP --}}
        <div id="editor-scroll-container" class="flex-1 w-full h-full overflow-y-auto overflow-x-hidden scroll-smooth" @scroll="document.querySelector('.drag-handle')?.classList.add('hide')" onclick="document.querySelector('.ProseMirror')?.focus()" >
            <div class="w-full py-8 flex flex-col min-h-[75vh]">
                {{-- 1. PEMBUNGKUS LUAR (Dibiarkan diubah oleh Livewire untuk menampilkan warna merah jika error) --}}
                <div class="relative w-full flex-1 rounded-xl transition-all" wire:key="tiptap-parent-container">
                    {{-- 2. PELINDUNG EDITOR (Area ini di-skip oleh Livewire agar Tiptap tidak terhapus) --}}
                    <div wire:ignore wire:key="tiptap-instance-permanen">
                        <div id="editor" x-ref="editorElement" class="w-full h-full focus:outline-none" data-editable="{{ $editable ? 'true' : 'false' }}">
                        </div>
                    </div>
                </div>
                {{-- 3. PESAN ERROR KONTEN --}}
                @error('content')
                    <div class="mt-4 text-center" wire:key="tiptap-error-container">
                        <span class="bg-red-100 text-red-600 px-4 py-1.5 rounded-full text-sm font-bold shadow-sm">
                            {{ $message }}
                        </span>
                    </div>
                @enderror
            </div>
        </div>

        {{-- 🌟 WORD COUNTER MELAYANG (FLOATING) --}}
        {{-- <div class="fixed bottom-6 right-8 pointer-events-none z-50">
            <div class="bg-sage-soft dark:bg-zinc-800/90 backdrop-blur-sm border border-zinc-200 dark:border-zinc-700 text-foresty dark:text-zinc-400 text-[10px] md:text-xs px-2.5 py-1 rounded-md shadow-sm font-medium tracking-wide">
                <span x-text="`${wordCount} kata`"></span>
            </div>
        </div> --}}
        <!-- 🌟 WORD COUNTER KEMBALI KE POSISI FLOATING DI DALAM AREA EDITOR 🌟 -->
        <div class="absolute bottom-6 right-8 pointer-events-none z-40">
            <div class="bg-sage-soft dark:bg-zinc-800/90 backdrop-blur-sm border border-zinc-200 dark:border-zinc-700 text-foresty dark:text-zinc-400 text-[10px] md:text-xs px-2.5 py-1 rounded-md shadow-sm font-medium tracking-wide">
                <span x-text="`${wordCount} kata`"></span>
            </div>
        </div>

    </div>
    <input type="file" x-ref="fileInput" accept="image/*" multiple class="hidden" @change="handleMultipleImageUpload($event.target.files); $event.target.value = ''" />
</div>

