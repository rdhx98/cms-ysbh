<?php

use Livewire\Component;
use App\Models\Page;

use App\Livewire\Traits\WithNotifications;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

new class extends Component
{
    //

    Use WithNotifications;

    public string $orderDirection;
    public string $orderColumn;

    public function mount() {
        $this->orderDirection = "desc";
        $this->orderColumn = "created_at";
    }

    // #[Url(as: 'status')]
    #[Url]
    public $statusFilter = '';

    #[Url]
    public $titleSearch = '';

    #[Computed]
    public function pages()
    {
        // Mulai dari query builder kosong
        $query = Page::query();

        // 1. Filter Pencarian Judul (Sesuai bahasa yang aktif)
        // if ($this->titleSearch) {
        //     // Ambil kode bahasa saat ini (misal: 'id' atau 'en')
        //     $locale = app()->getLocale();

        //     // Cari hanya di dalam JSON key bahasa tersebut
        //     $query->where("title->{$locale}", 'like', '%' . $this->titleSearch . '%');
        // }
        // 1. Filter Pencarian Judul
        if ($this->titleSearch) {
            $locale = app()->getLocale();

            // Ubah input user menjadi huruf kecil
            $searchTerm = strtolower($this->titleSearch);

            // Gunakan whereRaw dengan JSON_EXTRACT eksplisit
            $query->whereRaw(
                "LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.{$locale}'))) LIKE ?",
                ['%' . $searchTerm . '%']
            );
        }

        // 2. Filter Status
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Eksekusi query
        return $query->get();
    }
};
?>

<x-slot:title>{{ __('ui.header.page') }}</x-slot:title>
<x-main-wrapper>
    <div x-data="{ activeSubPanel: 'none', showDeleteModal: false, deleteType: '', deleteId: null, newItemName: '' }" class="flex flex-col lg:flex-row gap-4 items-start w-full">
        <!-- MAIN UX (KONTAINER UTAMA) -->
        <div x-bind:class="activeSubPanel !== 'none' ? 'w-full lg:w-2/3 transition-all duration-300' : 'w-full transition-all duration-300'" class="min-w-0">
            {{-- HEADER --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-2 pb-4 gap-3">
                <span class="text-2xl font-bold text-foresty dark:text-zinc-100">{{ __('Index') }}</span>

                <!-- Group Tombol Navigasi/Aksi -->
                <div class="flex flex-wrap items-center gap-2 self-end sm:self-auto">

                    <!-- Kode Tombol Anda -->
                    <a href="{{ route('page.menu-builder') }}" wire:navigate class="group inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer overflow-hidden">
                        <x-dynamic-component :component="'lucide-panels-top-left'" class="h-5 w-5 origin-bottom-left group-hover:animate-stroke" stroke-width="2"  />
                        {{ __('Menu Builder') }}
                    </a>
                    <a href="{{ route('page.create') }}" wire:navigate class="group inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer overflow-hidden">
                        <x-dynamic-component :component="'lucide-panels-top-left'" class="h-5 w-5 origin-bottom-left group-hover:animate-stroke" stroke-width="2"  />
                        {{ __('Create') }}
                    </a>
                </div>
            </div>

            <!-- BARIS FILTER SEDERHANA-->
            <div x-data="{statusSelected: @entangle('statusFilter').live }" class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white dark:bg-zinc-900 px-2 py-4 mb-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <!-- 1. Input Pencarian -->
                <div class="md:col-span-3 col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                        <flux:icon variant="outline" icon="magnifying-glass" class="w-4 h-4" />
                    </div>
                    <input
                        type="text"
                        placeholder="Cari halaman..."
                        class="w-full pl-9 pr-4 py-2 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-sbh-green focus:border-transparent text-zinc-700 dark:text-zinc-300"
                        wire:model.live="titleSearch"
                    >
                </div>
                <div class=" md:col-span-1 col-span-2 flex flex-col md:flex-row gap-2">

                    <button
                    type="button"
                    x-on:click="statusSelected = (statusSelected === 'online' ? '' : 'online');"
                    x-bind:class="statusSelected === 'online' ? 'bg-misty text-foresty' : 'bg-zinc-50 text-forest '"
                    class="w-full select-none cursor-pointer p-2 text-sm rounded-full focus:outline-none border border-zinc-200 hover:bg-misty ">
                        Online
                    </button>
                    <button
                    x-on:click="statusSelected = (statusSelected === 'offline' ? '' : 'offline');"
                    x-bind:class="statusSelected === 'offline' ? 'bg-misty text-foresty' : 'bg-zinc-50 text-forest '"
                    class="w-full select-none cursor-pointer p-2 text-sm rounded-full focus:outline-none border border-zinc-200 hover:bg-misty ">
                        Offline
                    </button>
                </div>

            </div>


            {{-- REVISI: Penambahan komputasi max-height responsif agar di mobile tidak meluber ke bawah --}}
            <!-- TABEL UTAMA -->
            <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-450px)] lg:max-h-[calc(100vh-280px)] rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm w-full max-w-screen">
                <table class="w-full min-w-max text-left border-collapse">
                    <thead class="bg-misty text-xs text-foresty dark:bg-green-950 dark:text-green-300">
                        <tr>
                            <!-- Header Judul -->
                            <th class="sticky top-0 z-10 lg:z-20 px-4 py-3 font-semibold uppercase bg-misty tracking-wider border-r-2 border-sage-soft ">
                                <button wire:click="sortBy('title')" class="flex items-center gap-2 w-full uppercase tracking-wider font-semibold cursor-pointer hover:text-zinc-200 transition-colors">
                                    Judul Halaman
                                    @if($orderColumn === 'title')
                                        <flux:icon variant="solid" icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                    @endif
                                </button>
                            </th>
                            <!-- PATH -->
                            <th class="sticky top-0 z-10 lg:z-20 px-4 py-3 font-semibold uppercase bg-misty tracking-wider select-none border-r-2 border-sage-soft">
                                {{-- <button wire:click="sortBy('title')" class="flex justify-center items-center gap-2 w-full uppercase tracking-wider font-semibold cursor-pointer hover:text-zinc-200 transition-colors"> --}}
                                    Path
                                    {{-- @if($orderColumn === 'title')
                                        <flux:icon variant="solid" icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                    @endif --}}
                                {{-- </button> --}}
                            </th>

                            <!-- Tanggal -->
                            <th class="sticky top-0 z-10 lg:z-20 px-4 py-3 font-semibold uppercase bg-misty tracking-wider border-r-2 border-sage-soft whitespace-nowrap">
                                <button wire:click="sortBy('created_at')"
                                class="flex justify-center items-center w-full gap-2 uppercase tracking-wider font-semibold cursor-pointer hover:text-zinc-200 transition-colors">
                                    Tanggal & Waktu dibuat
                                    {{-- @if($orderColumn === 'created_at')
                                        <flux:icon variant="solid" icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                    @endif --}}
                                </button>
                            </th>

                            <!-- STATUS -->
                            <th class="sticky top-0 z-10 lg:z-20 px-4 py-3 font-semibold uppercase bg-misty tracking-wider border-r-2 border-sage-soft">
                                <button wire:click="sortBy('status')" class="flex justify-center items-center gap-2 w-full uppercase tracking-wider font-semibold cursor-pointer hover:text-zinc-200 transition-colors">
                                    Status
                                    @if($orderColumn === 'status')
                                        <flux:icon variant="solid" icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                    @endif
                                </button>
                            </th>

                            <!-- Header Kelola (Tidak perlu sorting) -->
                            <th class="sticky top-0 z-10 lg:z-20 px-4 py-3 font-semibold uppercase bg-misty tracking-wider text-center select-none">
                                Kelola
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">

                        @forelse ($this->pages as $page)
                            @foreach (range(1, 1) as $i)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                    <!-- TITLE -->
                                    <td class="w-[30%] px-4 py-3.5 text-sm">
                                        <div class="font-medium text-zinc-900 dark:text-white">{{ $page->getTranslation('title', 'id') }}</div>
                                        {{-- <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $article->author->name }}</div> --}}
                                    </td>

                                    <!-- PATH -->
                                    <td class="w-[30%] px-4 py-0 text-sm h-full align-middle">
                                        <div class="flex gap-2 items-center justify-start h-full min-h-14">
                                            /{{ $page->slug }}
                                            {{-- <div class="px-2 py-0.5 rounded text-xs font-medium bg-sage-soft text-foresty dark:bg-slate-800 dark:text-slate-300"> {{ $article->created_at->format('D, d/m/y') }} </div> --}}
                                            {{-- <div class="px-2 py-0.5 rounded text-xs font-medium bg-sage-soft text-foresty dark:bg-slate-800 dark:text-slate-300"> {{ $article->created_at->format('H:i') }} </div> --}}
                                        </div>
                                    </td>

                                    <!-- DATE -->
                                    <td class="px-4 py-3.5 text-sm">
                                        {{ $page->created_at }}
                                    </td>

                                    <!-- STATUS -->
                                    <td class="px-2 py-3.5 text-sm text-center">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full border-2 {{ $page->status_color ?? '' }}">
                                            {{ ucfirst($page->status) }}
                                        </span>
                                    </td>

                                    {{-- BUTTONS  --}}
                                    <td class="px-4 py-3.5 text-sm">
                                        <div class="flex justify-center items-center gap-2">
                                            @php
                                                $rawSlug = $page->slug;
                                                // Coba jadikan array jika memungkinkan
                                                $slugData = is_string($rawSlug) ? json_decode($rawSlug, true) : $rawSlug;
                                                
                                                // Cadangan paling aman (gunakan ID)
                                                $slugCantik = $page->id; 

                                                // Jika berhasil menjadi array JSON
                                                if (is_array($slugData) && !empty($slugData)) {
                                                    $slugCantik = $slugData[app()->getLocale()] ?? $slugData['id'] ?? $slugData['en'] ?? $page->id;
                                                } 
                                                // Jika slug di database ternyata cuma teks biasa (bukan JSON)
                                                elseif (is_string($slugData) && !empty(trim($slugData))) {
                                                    $slugCantik = $slugData;
                                                } 
                                                elseif (is_string($rawSlug) && !empty(trim($rawSlug))) {
                                                    $slugCantik = $rawSlug;
                                                }
                                            @endphp

                                            <a wire:navigate href="{{ route('page.edit', ['pageSlug' => $slugCantik]) }}" class="group p-1.5 rounded-md text-white bg-forest/90 dark:bg-forest/80 relative cursor-pointer hover:bg-forest/70 transition-colors flex items-center justify-center">
                                                <flux:icon variant="solid" icon="pencil" class="size-3.5!" />
                                                <span class="z-30 absolute bottom-full left-0 mb-2 w-max px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity duration-200 shadow-lg dark:bg-gray-100 dark:text-gray-900">
                                                    Sunting

                                                    <svg class="absolute text-gray-900 dark:text-gray-100 h-2 w-4 left-2 top-full" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                                                        <polygon class="fill-current" points="0,0 127.5,127.5 255,0" />
                                                    </svg>
                                                </span>
                                            </a>

                                            <a wire:navigate href="{{ route('page.preview', ['pageSlug' => $slugCantik]) }}" class="group p-1.5 rounded-md bg-slate-600 text-white dark:bg-slate-800 relative cursor-pointer hover:bg-slate-700 transition-colors flex items-center justify-center">
                                                <flux:icon variant="solid" icon="eye" class="size-3.5!" />
                                                <span class="z-30 absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity duration-200 shadow-lg dark:bg-gray-100 dark:text-gray-900">
                                                    Pratinjau
                                                    <svg class="absolute text-gray-900 dark:text-gray-100 h-2 w-full left-0 top-full" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                                                        <polygon class="fill-current" points="0,0 127.5,127.5 255,0" />
                                                    </svg>
                                                </span>
                                            </a>

                                            {{-- <button
                                                @click="deleteType = 'article'; deleteId = {{ $article->id }}; showDeleteModal = true"
                                                type="button" class="group p-1.5 rounded-md bg-red-600 text-white dark:bg-red-800 relative cursor-pointer hover:bg-red-700 transition-colors flex items-center justify-center">
                                                <flux:icon variant="solid" icon="trash" class="size-3.5!" />

                                                <span class="z-30 absolute bottom-full right-0 mb-2 w-max px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity duration-200 shadow-lg dark:bg-gray-100 dark:text-gray-900">
                                                    Hapus

                                                    <svg class="absolute text-gray-900 dark:text-gray-100 h-2 w-4 right-2 top-full" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                                                        <polygon class="fill-current" points="0,0 127.5,127.5 255,0" />
                                                    </svg>
                                                </span>
                                            </button> --}}

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            {{-- INI AKAN MUNCUL JIKA TIDAK ADA DATA ARTIKEL --}}
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <flux:icon variant="outline" icon="document-text" class="size-8 text-zinc-400 mb-2" />
                                        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Tidak ada halaman yang ditemukan.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECONDARY UX (KONTAINER KEDUA - PANEL/POPUP) -->
        {{-- <div x-show="activeSubPanel !== 'none'" class="contents">

            <!-- 1. BACKDROP HITAM MOBILE -->
            <div
                x-show="activeSubPanel !== 'none'"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="activeSubPanel = 'none'"
                class="fixed inset-0 bg-black/40 z-40 lg:hidden"
            ></div>

            <!-- 2. BADAN PANEL KEDUA -->
            <div
                x-show="activeSubPanel !== 'none'"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-4 opacity-0 lg:opacity-100"
                x-transition:enter-end="translate-y-0 lg:translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 lg:translate-x-0 opacity-100"
                x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-4 opacity-0 lg:opacity-100"

                class="fixed bottom-0 left-0 right-0 rounded-t-3xl z-50 max-h-[80vh] overflow-y-auto
                    lg:relative lg:bottom-auto lg:left-auto lg:right-auto lg:rounded-2xl lg:z-10 lg:w-1/3 lg:max-h-none
                    bg-white lg:bg-sbh-yellow/10 dark:bg-zinc-900 p-5 border border-zinc-200 dark:border-zinc-700 shadow-xl lg:shadow-sm space-y-4" >
                <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-700 rounded-full mx-auto lg:hidden mb-2"></div>

                <!-- Header Kontainer Kedua -->
                <div class="flex justify-between items-center border-b border-zinc-200 dark:border-zinc-800 pb-2">
                    <div class="flex items-center gap-2 text-sbh-green">
                        <!-- REVISI: Mengubah dari :icon menjadi x-bind:icon -->
                        <flux:icon.folder x-show="activeSubPanel === 'categories'" variant="solid" class="size-4" />
                        <flux:icon.tag x-show="activeSubPanel === 'tags'" variant="solid" class="size-4" />
                        <h3 class="font-bold text-zinc-900 dark:text-white text-md" x-text="activeSubPanel === 'categories' ? 'Kelola Kategori' : 'Kelola Tag'"></h3>
                    </div>
                    <button @click="activeSubPanel = 'none'" class="text-red-500 dark:hover:text-zinc-200 p-2 hover:bg-red-600 hover:text-white transition-colors cursor-pointer rounded-lg">
                        <flux:icon variant="outline" icon="x-mark" class="size-6" />
                    </button>
                </div>

                <!-- KONTEN DINAMIS -->
                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-zinc-500" x-text="activeSubPanel === 'categories' ? 'Nama Kategori Baru' : 'Nama Tag Baru'"></label>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                x-model="newItemName"
                                @keyup.enter="
                                    if(newItemName.trim() !== '') {
                                        activeSubPanel === 'categories' ? $wire.createCategory(newItemName) : $wire.createTag(newItemName);
                                        newItemName = ''; // Kosongkan input setelah submit
                                    }
                                "
                                x-bind:placeholder="activeSubPanel === 'categories' ? 'Misal: Berita Internal' : 'Misal: #stunting'"
                                class="flex-1 px-3 py-2 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-sbh-green text-zinc-700 dark:text-zinc-300">
                            <!-- CREATE BUTTON -->
                            <button
                                x-data="{  isAnimating: false,  playAnim() { this.isAnimating = false;  this.$nextTick(() => { this.isAnimating = true; setTimeout(() => this.isAnimating = false, 500); }); }}"
                                x-on:click="
                                    playAnim();
                                    if(newItemName.trim() !== '') {
                                        activeSubPanel === 'categories' ? $wire.createCategory(newItemName) : $wire.createTag(newItemName);
                                        newItemName = ''; // Kosongkan input setelah submit
                                    }"
                                x-on:mouseenter="playAnim()"
                                class="p-2 bg-forest text-white text-sm font-medium rounded-lg hover:bg-green-700 cursor-pointer transition-colors flex items-center gap-1">
                                <x-dynamic-component :component="'lucide-save'" class="h-5 w-5 origin-bottom-center group-hover:animate-save" stroke-width="2" x-bind:class="isAnimating ? 'animate-save' : ''" />
                            </button>
                        </div>
                    </div>
                    <div class="max-h-75 lg:max-h-62.5 overflow-y-auto border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 p-2 divide-y divide-zinc-200 dark:divide-zinc-700">
                        <template x-if="activeSubPanel === 'categories'">
                            <div class="space-y-1">
                                @foreach ($this->categoryList as $i)
                                    <div class="flex justify-between items-center py-2.5 px-2 text-sm hover:bg-forest hover:text-white rounded-lg transition-colors">
                                        <span class="font-medium">{{ $i->name }}</span>
                                        <!-- <button
                                            x-on:click="deleteType = 'category'; deleteId = {{ $i->id }}; showDeleteModal = true"
                                            class="text-white bg-red-500 p-2 rounded-md cursor-pointer">
                                            <flux:icon variant="outline" icon="trash" class="size-4" />
                                        </button> -->
                                        <button
                                            x-data="{  isAnimating: false,  playAnim() { this.isAnimating = false;  this.$nextTick(() => { this.isAnimating = true; setTimeout(() => this.isAnimating = false, 500); }); }}"
                                            x-on:click=" playAnim(); deleteType = 'category'; deleteId = {{ $i->id }}; showDeleteModal = true "
                                            x-on:mouseenter="playAnim()"
                                            class="p-2 text-white bg-red-500 rounded-md cursor-pointer">
                                            <x-dynamic-component :component="'lucide-trash-2'" class="h-5 w-5 origin-center group-hover:animate-trash" stroke-width="2" x-bind:class="isAnimating ? 'animate-trash' : ''" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </template>

                        <template x-if="activeSubPanel === 'tags'">
                            <div class="space-y-1">
                                @foreach ($this->tagList as $i)
                                    <div class="flex justify-between items-center py-2.5 px-2 text-sm hover:bg-forest hover:text-white rounded-lg transition-colors">
                                        <span class="font-medium">{{ $i->name }}</span>
                                        <!-- <button
                                            @click="deleteType = 'tag'; deleteId = {{ $i->id }}; showDeleteModal = true"
                                            class="text-white bg-red-500 p-2 rounded-md cursor-pointer">
                                            <flux:icon variant="outline" icon="trash" class="size-4" />
                                        </button> -->
                                        <button
                                            x-data="{  isAnimating: false,  playAnim() { this.isAnimating = false;  this.$nextTick(() => { this.isAnimating = true; setTimeout(() => this.isAnimating = false, 500); }); }}"
                                            x-on:click=" playAnim(); deleteType = 'tag'; deleteId = {{ $i->id }}; showDeleteModal = true "
                                            x-on:mouseenter="playAnim()"
                                            class="p-2 text-white bg-red-500 rounded-md cursor-pointer">
                                            <x-dynamic-component :component="'lucide-trash-2'" class="h-5 w-5 origin-center group-hover:animate-trash" stroke-width="2" x-bind:class="isAnimating ? 'animate-trash' : ''" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div> --}}

        <!-- DELETE CONFIRMATION MODAL -->
        <div
            x-show="showDeleteModal"
            class="relative z-99"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
            x-cloak >
            <div
                x-show="showDeleteModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm transition-opacity"
            ></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        x-show="showDeleteModal"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        @click.away="showDeleteModal = false"
                        class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 px-4 pb-4 pt-5 text-left shadow-xl transition-all w-full max-w-sm sm:my-8 sm:p-6"
                    >
                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <flux:icon variant="outline" icon="exclamation-triangle" class="h-6 w-6 text-terracotta dark:text-red-400" />
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-base font-bold leading-6 text-zinc-900 dark:text-white" id="modal-title">
                                    Hapus Artikel
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        Apakah Anda yakin ingin menghapus artikel ini? Data yang sudah dihapus tidak dapat dikembalikan.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 flex flex-col sm:flex-row-reverse gap-3">
                            <button
                                type="button"
                                class="inline-flex cursor-pointer w-full justify-center rounded-xl bg-sage-soft px-3 py-2 text-sm font-semibold text-forest shadow-sm hover:bg-red-600 hover:text-white transition-colors sm:w-auto"
                                @click="
                                    ({
                                        'article': () => $wire.deleteArticle(deleteId),
                                        'category': () => $wire.deleteCategory(deleteId),
                                        'tag': () => $wire.deleteTag(deleteId)
                                    })[deleteType]();

                                    showDeleteModal = false;
                                ">
                                Ya, Hapus
                            </button>
                            <button
                                type="button"

                                @click = "deleteId = null; deleteType = ''; showDeleteModal = false"
                                class="inline-flex cursor-pointer w-full justify-center rounded-xl bg-white dark:bg-zinc-800 px-3 py-2 text-sm font-semibold text-zinc-900 dark:text-zinc-300 shadow-sm ring-1 ring-inset ring-zinc-300 dark:ring-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors sm:w-auto">
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-main-wrapper>

