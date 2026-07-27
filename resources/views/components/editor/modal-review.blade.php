{{-- MODAL REVIEW --}}
<div x-data="{ isReview: false }" @buka-modal-review.window="isReview = true" class="fixed inset-0 pointer-events-none z-[99]">
{{-- <div x-data="{ isReview: false }" @buka-modal-review.window="isReview = true" class="relative"> --}}

    <div x-show="isReview"  x-cloak  class="fixed inset-0 z-99 flex items-center pointer-events-none justify-center px-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">

        {{-- Latar Belakang Gelap (Backdrop) --}}
        <div x-show="isReview"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-zinc-900/40 backdrop-blur-sm transition-opacity pointer-events-auto"
                @click="isReview = false"></div>

        {{-- Panel Modal Utama --}}
        <div x-show="isReview"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-800 p-6 w-full max-w-md transform transition-all pointer-events-auto">

            {{-- Header Modal --}}
            <div class="flex items-center gap-4 mb-4">
                <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <x-dynamic-component :component="'lucide-send'" class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white select-none" id="modal-title">Ajukan Review?</h3>
                </div>
            </div>

            {{-- Deskripsi --}}
            <div class="mb-6 ml-14">
                <p class="text-sm text-zinc-600 dark:text-zinc-400 select-none">
                    Yakin ingin mengajukan naskah ini ke Editor?<br>Anda <b>tidak dapat mengubah isi dan metadata</b> dari artikel ini selama masa review berlangsung.
                </p>
            </div>

            {{-- Grup Tombol --}}
            <div class="flex items-center justify-end gap-3">
                <button type="button"
                        @click="isReview = false"
                        class="px-4 py-2 text-sm pointer-cursor font-semibold text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:text-zinc-300 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-xl transition-colors select-none cursor-pointer">
                    Batal
                </button>

                {{-- Eksekusi Livewire di sini --}}
                <button type="button"
                        @click="if(window.tiptapEditor) { $wire.submitForReview(window.tiptapEditor.getHTML()) }; isReview = false"
                        class="px-4 py-2 text-sm pointer-cursor font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-colors flex items-center gap-2 select-none cursor-pointer">
                    Ya, Ajukan
                </button>
            </div>
        </div>
    </div>


</div>
