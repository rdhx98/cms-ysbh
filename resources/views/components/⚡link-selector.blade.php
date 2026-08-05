<?php

use Livewire\Component;
use App\Models\Post;
use App\Models\Page;

new class extends Component
{
    public $searchQuery = '';
    public $searchResults = [];

    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) < 2) { // 👈 Ubah ke 2 huruf
            $this->searchResults = [];
            return;
        }

        // Ubah input menjadi huruf kecil semua agar aman
        $search = '%' . strtolower($this->searchQuery) . '%';

        // Gunakan LOWER(title) untuk mencocokkan secara mentah tanpa peduli besar/kecil huruf
        $articles = Post::whereRaw('LOWER(title) LIKE ?', [$search])
            ->select('id', 'title', 'slug')
            ->take(5)
            ->get()
            ->map(fn($item) => ['title' => $item->title, 'slug' => $item->slug, 'type' => 'post']);

        $pages = Page::whereRaw('LOWER(title) LIKE ?', [$search])
            ->select('id', 'title', 'slug')
            ->take(5)
            ->get()
            ->map(fn($item) => ['title' => $item->title, 'slug' => $item->slug, 'type' => 'page']);

        $this->searchResults = collect($articles)->merge($pages)->toArray();
    }
};
?>

<!-- Kita hapus x-data lokal karena kita akan menggunakan linkInputUrl dari tiptap-editor.js -->
<div x-show="isLinkOpen" 
     style="display: none;" 
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <!-- Backdrop Gelap -->
    <div x-show="isLinkOpen" @click="isLinkOpen = false; @this.set('searchQuery', '');" x-transition.opacity class="fixed inset-0 bg-zinc-900/40 backdrop-blur-sm"></div>

    <!-- Panel Modal -->
    <div x-show="isLinkOpen" x-transition.scale class="relative bg-white dark:bg-zinc-900 rounded-xl shadow-2xl w-full max-w-lg border border-zinc-200 dark:border-zinc-800 overflow-hidden z-10 flex flex-col">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50">
            <h3 class="font-bold text-forest text-lg">Sisipkan Tautan</h3>
        </div>

        <!-- Konten -->
        <div class="p-6 space-y-5 bg-zinc-100 dark:bg-zinc-900">
            
            <!-- 1. Teks Tautan -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Teks Tautan</label>
                <input type="text" x-model="linkInputText" placeholder="Contoh: Baca selengkapnya..." class=" p-2 w-full border-zinc-300 rounded-lg focus:ring-forest bg-white dark:bg-zinc-800">
            </div>

            <!-- 2. Input URL Manual -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">URL / Alamat Web</label>
                <input type="text" x-model="linkInputUrl" placeholder="https://..." class="p-2 w-full border-zinc-300 text-blue-600 font-mono rounded-lg focus:ring-forest bg-white dark:bg-zinc-800">
            </div>

            <div class="relative pt-5 border-t border-zinc-200 dark:border-zinc-800">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Atau Cari Halaman Internal</label>
                <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Ketik judul artikel/halaman..." class="p-2 w-full border-zinc-300 rounded-lg focus:ring-forest bg-white dark:bg-zinc-800">

                <!-- Indikator Loading Livewire -->
                <div wire:loading wire:target="searchQuery" class="absolute right-3 top-12 text-forest">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>

                <!-- Hasil Pencarian -->
                @if(count($searchResults) > 0)
                    <div class="absolute z-20 w-full mt-2 bg-white dark:bg-zinc-800 rounded-lg shadow-xl border border-zinc-200 dark:border-zinc-700 max-h-60 overflow-y-auto">
                        @foreach($searchResults as $result)
                            
                            <!-- Saat diklik, URL otomatis masuk ke kolom Manual URL -->
                            <button type="button"
                                @click="
                                    linkInputUrl = 'internal://{{ $result['type'] }}/{{ $result['slug'] }}';
                                    
                                    if (linkInputText.trim() === '') {
                                        linkInputText = '{{ addslashes($result['title']) }}';
                                    }
                                    
                                    @this.set('searchQuery', '');
                                "
                                class="w-full text-left px-4 py-3 hover:bg-sage-soft dark:hover:bg-zinc-700 transition-colors flex items-center gap-3 border-b border-zinc-50 dark:border-zinc-700/50 last:border-0">

                                <span class="text-[10px] uppercase font-bold bg-zinc-200 text-zinc-600 px-2 py-0.5 rounded-full shrink-0">
                                    {{ $result['type'] }}
                                </span>
                                <span class="text-sm font-medium truncate text-zinc-800 dark:text-zinc-200">{{ $result['title'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        <!-- Footer Modal -->
        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex justify-end gap-3 bg-white dark:bg-zinc-900">
            <button type="button" @click="isLinkOpen = false; @this.set('searchQuery', '');" class="px-5 py-2 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors font-medium">
                Batal
            </button>
            
            <!-- Tombol Eksekusi -->
            <button type="button" 
                :disabled="linkInputUrl.trim() === ''"
                :class="linkInputUrl.trim() === '' ? 'opacity-50 cursor-not-allowed' : 'hover:bg-forest/90'"
                @click="
                    let url = linkInputUrl.trim();
                    let text = linkInputText.trim() === '' ? url : linkInputText;

                    if (url === '') return;

                    if (!/^https?:\/\//i.test(url) && !/^mailto:/i.test(url) && !/^tel:/i.test(url) && !/^internal:\/\//i.test(url) && !/^#/.test(url) && !/^\//.test(url)) {
                        url = 'https://' + url;
                    }

                    if (hasSelection) {
                        // TAHAP 1: Jadikan teks yang diblok sebagai link
                        window.tiptapEditor.chain()
                            .focus()
                            .extendMarkRange('link')
                            .setLink({ href: url, target: '_blank' })
                            .run();
                            
                        // TAHAP 2: Ambil posisi di ujung teks tersebut
                        let endPosition = window.tiptapEditor.state.selection.to;
                        
                        // TAHAP 3: Taruh kursor di ujung, matikan mode link, dan beri spasi murni
                        window.tiptapEditor.chain()
                            .setTextSelection(endPosition)
                            .unsetLink()
                            .insertContent(' ')
                            .run();
                            
                    } else {
                        // Jika kursor kosong, kita langsung sisipkan Array berisi 2 objek
                        window.tiptapEditor.chain()
                            .focus()
                            .insertContent([
                                {
                                    type: 'text',
                                    text: text,
                                    marks: [{ type: 'link', attrs: { href: url, target: '_blank' } }] // 👈 Objek 1: Membawa link
                                },
                                {
                                    type: 'text',
                                    text: ' ' // 👈 Objek 2: Hanya spasi kosong murni (tanpa mark/link)
                                }
                            ])
                            .run();
                    }

                    // Reset semua state dan tutup modal
                    isLinkOpen = false;
                    linkInputText = '';
                    linkInputUrl = '';
                    $wire.set('searchQuery', '');
                "
                class="px-5 py-2 bg-forest text-white rounded-lg font-bold transition-colors shadow-sm">
                Sisipkan Tautan
            </button>
        </div>
    </div>
</div>

{{-- 
<div x-show="isLinkOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">

    <!-- Backdrop Gelap (Klik di luar untuk menutup) -->
    <div x-show="isLinkOpen" @click="isLinkOpen = false; @this.set('searchQuery', '');" x-transition.opacity class="fixed inset-0 bg-zinc-900/40 backdrop-blur-sm"></div>

    <!-- Panel Modal -->
    <div x-show="isLinkOpen" x-transition.scale class="relative bg-white dark:bg-zinc-900 rounded-xl shadow-2xl w-full max-w-lg border border-zinc-200 dark:border-zinc-800 overflow-hidden z-10 flex flex-col">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50">
            <h3 class="font-bold text-forest text-lg">Sisipkan Tautan Internal</h3>
        </div>

        <!-- Konten -->
        <div class="p-6 space-y-4 bg-zinc-100 dark:bg-zinc-900">

            <!-- Input Default (Untuk Teks yang Ditampilkan) -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Teks Tautan</label>
                <input type="text" x-model="linkInputText" placeholder="Contoh: Baca selengkapnya..." class="p-2 w-full border-zinc-300 rounded-lg focus:ring-forest bg-white dark:bg-zinc-800">
            </div>

            <!-- Input Pencarian (Livewire) -->
            <div class="relative">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Cari Target (Artikel / Halaman)</label>
                <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Ketik judul minimal 3 huruf..." class="p-2 w-full border-zinc-300 rounded-lg focus:ring-forest bg-white dark:bg-zinc-800">

                <div wire:loading wire:target="searchQuery" class="absolute right-3 top-9 text-forest">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <!-- Hasil Pencarian -->
                @if(count($searchResults) > 0)
                    <div class="absolute z-20 w-full mt-2 bg-white dark:bg-zinc-800 rounded-lg shadow-xl border border-zinc-200 dark:border-zinc-700 max-h-60 overflow-y-auto">
                        @foreach($searchResults as $result)
                            <!-- <button type="button"
                                @click="
                                    let internalUrl = 'internal://{{ $result['type'] }}/{{ $result['slug'] }}';

                                    if (hasSelection) {
                                        window.tiptapEditor.chain()
                                            .focus()
                                            .extendMarkRange('link')
                                            .setLink({ href: internalUrl, target: '_blank' })
                                            .run();
                                    } else {
                                        window.tiptapEditor.chain()
                                            .focus()
                                            .insertContent({
                                                type: 'text',
                                                text: linkInputText,
                                                marks: [{ type: 'link', attrs: { href: internalUrl, target: '_blank' } }]
                                            })
                                            .insertContent(' ') // Beri spasi setelahnya
                                            .run();
                                    }
                                    isLinkOpen = false;
                                    linkInputText = '';
                                    $wire.set('searchQuery', '');
                                "
                                class="w-full text-left px-4 py-3 hover:bg-sage-soft dark:hover:bg-zinc-700 transition-colors flex items-center gap-3 border-b border-zinc-50 dark:border-zinc-700/50 last:border-0">

                                <span class="text-[10px] uppercase font-bold bg-zinc-200 text-zinc-600 px-2 py-0.5 rounded-full shrink-0">
                                    {{ $result['type'] }}
                                </span>
                                <span class="text-sm font-medium truncate text-zinc-800 dark:text-zinc-200">{{ $result['title'] }}</span>
                            </button> -->
                            <button type="button"
                                @click="
                                    let internalUrl = 'internal://{{ $result['type'] }}/{{ $result['slug'] }}';
                                    
                                    // 🌟 PERBAIKAN: Jika input teks kosong, gunakan judul dari database secara otomatis
                                    let finalLinkText = linkInputText.trim() === '' ? '{{ addslashes($result['title']) }}' : linkInputText;
                                    
                                    if (hasSelection) {
                                        window.tiptapEditor.chain()
                                            .focus()
                                            .extendMarkRange('link')
                                            .setLink({ href: internalUrl, target: '_blank' })
                                            .run();
                                    } else {
                                        window.tiptapEditor.chain()
                                            .focus()
                                            .insertContent({
                                                type: 'text',
                                                text: finalLinkText, // 👈 Gunakan variabel yang sudah dijamin tidak kosong
                                                marks: [{ type: 'link', attrs: { href: internalUrl, target: '_blank' } }]
                                            })
                                            .insertContent(' ')
                                            .run();
                                    }
                                    
                                    isLinkOpen = false;
                                    linkInputText = '';
                                    $wire.set('searchQuery', '');
                                "
                                class="w-full text-left px-4 py-3 hover:bg-sage-soft dark:hover:bg-zinc-700 transition-colors flex items-center gap-3 border-b border-zinc-50 dark:border-zinc-700/50 last:border-0">

                                <span class="text-[10px] uppercase font-bold bg-zinc-200 text-zinc-600 px-2 py-0.5 rounded-full shrink-0">
                                    {{ $result['type'] }}
                                </span>
                                <span class="text-sm font-medium truncate text-zinc-800 dark:text-zinc-200">{{ $result['title'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex justify-end gap-3 bg-white dark:bg-zinc-900">
            <button type="button" @click="isLinkOpen = false; @this.set('searchQuery', '');" class="select-none cursor-pointer px-5 py-2 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors font-medium">
                Batal
            </button>
        </div>
    </div>
</div> --}}
