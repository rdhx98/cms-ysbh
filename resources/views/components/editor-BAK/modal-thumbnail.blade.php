{{-- THuMBNAIL SELECTOR MODAL --}}
<div x-data="{ isOpen: false }" @buka-featured-modal.window="isOpen = true" class="fixed inset-0 pointer-events-none z-[99]">
{{-- <div x-data="{ isOpen: false }" @buka-featured-modal.window="isOpen = true" class="relative"> --}}

    {{-- ========================================================================= --}}
    {{-- 💻 LAYOUT MODAL THUMBNAIL: DESKTOP (md:flex)                              --}}
    {{-- ========================================================================= --}}
    <div x-show="isOpen"
        class="hidden md:flex fixed inset-0 z-999 items-center justify-center overflow-x-hidden overflow-y-auto"
        style="display: none;">

        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="isOpen = false"></div>

        {{-- THUMBNAIL MODAL AREA --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-800 w-full max-w-3xl max-h-[85vh] flex flex-col z-10 overflow-hidden pointer-events-auto">
            {{-- Header Modal --}}
            <div
                class="p-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between bg-white dark:bg-gray-900">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Pilih Gambar Sampul</h3>
                <button type="button" @click="isOpen = false"
                    class="text-gray-400 hover:text-red-500 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- Area Konten Gambar --}}
            <div
                class="p-6 overflow-y-auto grid grid-cols-3 gap-4 bg-gray-50 dark:bg-gray-950 flex-1 min-h-62.5">
                {{-- KOTAK PREVIEW / UNGGAH UTAMA DESKTOP --}}
                <div
                    class="relative aspect-video rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 flex flex-col items-center justify-center text-center overflow-hidden group">
                    <input type="file" wire:model="photo"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                        accept="image/*" />

                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                    @elseif ($selected_image_url)
                        <img src="{{ $selected_image_url }}"
                            class="w-full h-full object-cover opacity-90 group-hover:opacity-50 transition-opacity">
                        <div
                            class="absolute inset-0 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                            <x-dynamic-component :component="'lucide-upload-cloud'"
                                class="h-6 w-6 text-zinc-700 dark:text-zinc-300 drop-shadow-sm" />
                            <span
                                class="block text-[11px] font-bold text-zinc-700 dark:text-zinc-300 mt-1">Ganti
                                Berkas</span>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center pointer-events-none p-2">
                            <x-dynamic-component :component="'lucide-camera'" class="h-5 w-5 text-zinc-400 mb-1" />
                            <span
                                class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300">Klik
                                / Seret Foto</span>
                        </div>
                    @endif

                    <div wire:loading wire:target="photo"
                        class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center z-20">
                        <span class="text-xs font-bold text-blue-600 animate-pulse">Memproses...</span>
                    </div>
                </div>

                {{-- LOOP GAMBAR DARI EDITOR --}}
                @foreach ($extracted_images as $imgUrl)
                    <div wire:click="selectImageFromEditor('{{ $imgUrl }}')"
                        class="relative aspect-video rounded-xl overflow-hidden cursor-pointer border-2 {{ $selected_image_url === $imgUrl ? 'border-blue-600 ring-4 ring-blue-500/20' : 'border-transparent hover:border-zinc-400' }} transition-all">
                        <img src="{{ $imgUrl }}" class="w-full h-full object-cover">

                        @if ($selected_image_url === $imgUrl)
                            <div
                                class="absolute top-1 right-1 bg-blue-600 text-white rounded-full p-0.5 shadow-md animate-scale-in">
                                <x-dynamic-component :component="'lucide-check'" class="h-3 w-3"
                                    stroke-width="3" />
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Footer Modal --}}
            <div
                class="p-4 border-t border-zinc-100 dark:border-zinc-800 flex justify-end bg-gray-50 dark:bg-gray-900">
                <button type="button" @click="isOpen = false"
                    class="bg-forest hover:bg-forest text-white px-5 py-2 rounded-xl text-xs font-semibold shadow-sm transition cursor-pointer">
                    Selesai
                </button>
            </div>
        </div>
    </div>


    {{-- ========================================================================= --}}
    {{-- 📱 LAYOUT MODAL THUMBNAIL: MOBILE (md:hidden)                              --}}
    {{-- ========================================================================= --}}
    <div x-show="isOpen" class="flex md:hidden fixed inset-0 z-999 items-end justify-center"
        style="display: none;">

        {{-- Animasi Overlay: Fade In / Fade Out --}}
        <div x-show="isOpen" x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="isOpen = false"></div>

            {{-- Animasi Laci: Meluncur dari bawah (translate-y-full) ke atas (translate-y-0) --}}
        <div x-show="isOpen" x-transition:enter="transition ease-out duration-300 transform pointer-events-auto"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="bg-white dark:bg-gray-900 w-full rounded-t-2xl max-h-[80vh] flex flex-col z-10 overflow-hidden shadow-2xl relative">

            {{-- Handle Drag Modal Mobile --}}
            <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-700 rounded-full mx-auto my-3"
                @click="isOpen = false"></div>
            <div class="p-4 overflow-y-auto space-y-4 bg-gray-50 dark:bg-gray-950 flex-1">
                {{-- KOTAK PREVIEW / UNGGAH UTAMA MOBILE --}}
                <div
                    class="relative w-full aspect-video rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-700 bg-white dark:bg-gray-900 flex flex-col items-center justify-center overflow-hidden transition-all">
                    <input type="file" wire:model="photo"
                        class="absolute inset-0 w-full h-full opacity-0 z-10 cursor-pointer"
                        accept="image/*" />

                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                    @elseif ($selected_image_url)
                        <img src="{{ $selected_image_url }}" class="w-full h-full object-cover">
                        <div
                            class="absolute bottom-2 right-2 bg-black/60 text-white text-[10px] px-2 py-1 rounded backdrop-blur-xs pointer-events-none">
                            Ketuk untuk mengganti
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center pointer-events-none">
                            <x-dynamic-component :component="'lucide-camera'" class="h-6 w-6 text-zinc-400 mb-1" />
                            <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">Unggah /
                                Ambil Foto</span>
                        </div>
                    @endif

                    <div wire:loading wire:target="photo"
                        class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center z-20">
                        <span class="text-xs font-bold text-blue-600 animate-pulse">Memproses...</span>
                    </div>
                </div>

                {{-- DAFTAR PILIHAN GAMBAR UTUK MOBILE (YANG TADI HILANG) --}}
                @if (count($extracted_images) > 0)
                    <div class="pt-2">
                        <h4
                            class="text-xs font-bold text-zinc-400 dark:text-zinc-500 mb-2 tracking-wide uppercase">
                            Pilih Gambar Dari Artikel:</h4>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($extracted_images as $imgUrl)
                                <div wire:click="selectImageFromEditor('{{ $imgUrl }}')"
                                    class="relative w-full aspect-video rounded-xl overflow-hidden cursor-pointer border-2 {{ $selected_image_url === $imgUrl ? 'border-blue-600 ring-2 ring-blue-500/10' : 'border-transparent' }} transition-all">
                                    <img src="{{ $imgUrl }}" class="w-full h-full object-cover">

                                    @if ($selected_image_url === $imgUrl)
                                        <div
                                            class="absolute top-1 right-1 bg-blue-600 text-white rounded-full p-0.5 shadow">
                                            <x-dynamic-component :component="'lucide-check'" class="h-3 w-3"
                                                stroke-width="3" />
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Tombol Aksi Mobile --}}
            <div
                class="p-4 bg-white dark:bg-gray-900 border-t border-zinc-100 dark:border-zinc-800 shadow-inner">
                <button type="button" @click="isOpen = false"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-xs font-bold text-center transition-colors cursor-pointer shadow-sm">
                    Terapkan Sampul
                </button>
            </div>
        </div>
    </div>
</div>
