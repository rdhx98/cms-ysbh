<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="bg-white rounded-lg w-full h-full md:max-w-none flex flex-col justify-center items-center p-4 flex-1 grow">
    <x-slot:title>{{ __('Arcticles') }}</x-slot:title>
    {{-- We must ship. - Taylor Otwell --}}

    <div class="" x-data="{ activeSubPanel: 'none' }">
        {{-- REVISI: Menggunakan flex-col untuk mobile agar menumpuk, dan lg:flex-row untuk desktop --}}
        <div class="flex flex-col lg:flex-row gap-4 items-start w-full">

            <!-- MAIN UX (KONTAINER UTAMA) -->
            {{-- REVISI: Menggunakan x-bind:class agar dinamis mengikuti status activeSubPanel tanpa bentrok dengan PHP --}}
            <div
                x-bind:class="activeSubPanel !== 'none' ? 'w-full lg:w-2/3 transition-all duration-300' : 'w-full transition-all duration-300'"
                class="bg-sbh-yellow/10 py-2 px-4 rounded-2xl"
            >
                {{-- REVISI: Merapikan header fleksibel --}}
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-2 py-4 gap-3">
                    <b class="text-lg text-zinc-800 dark:text-zinc-100">{{ __('Index') }}</b>

                    <!-- Group Tombol Navigasi/Aksi -->
                    <div class="flex flex-wrap items-center gap-2 self-end sm:self-auto">

                        <!-- Tombol Kategori (Mengubah state ke 'categories') -->
                        <button
                            @click="activeSubPanel = (activeSubPanel === 'categories' ? 'none' : 'categories')"
                            x-bind:class="activeSubPanel === 'categories' ? 'bg-forest text-white' : 'bg-sage-soft text-forest'"
                            class="rounded-xl h-8 flex justify-center items-center [&_svg]:text-sbh-yellow px-3 gap-2 text-sm font-medium cursor-pointer hover:text-white hover:bg-green-700 transition-colors"
                        >
                            {{ __('Category') }}
                            <flux:icon variant="outline" icon="rectangle-group" class="size-4!" />
                        </button>

                        <!-- Tombol Tags (Mengubah state ke 'tags') -->
                        <button
                            @click="activeSubPanel = (activeSubPanel === 'tags' ? 'none' : 'tags')"
                            x-bind:class="activeSubPanel === 'tags' ? 'bg-forest text-white' : 'bg-sage-soft text-forest'"
                            class="rounded-xl h-8 flex justify-center items-center [&_svg]:text-sbh-yellow text-accent-foreground px-3 gap-2 text-sm font-medium cursor-pointer hover:text-white hover:bg-green-700 transition-colors"
                        >
                            {{ __('Tags') }}
                            <flux:icon variant="outline" icon="tag" class="size-4!" />
                        </button>

                        <!-- Tombol Create (Membuka halaman baru) -->
                        <a
                            href="{{ route('article.editor') }}"
                            {{-- :current="request()->routeIs('article-editor')" --}}

                            wire:navigate
                            class="cursor-pointer rounded-xl h-8 bg-sage-soft flex justify-center items-center [&_svg]:text-sbh-yellow text-forest px-3 gap-2 text-sm font-medium hover:text-white hover:bg-green-700 transition-colors"
                        >
                            {{ __('Write') }}
                            <flux:icon variant="outline" icon="pencil-square" class="size-4!" />
                        </a>
                    </div>
                </div>

                <!-- BARIS FILTER SEDERHANA-->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white dark:bg-zinc-900 p-3 mb-2 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <!-- 1. Input Pencarian -->
                    <div class="md:col-span-2 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                            <flux:icon variant="outline" icon="magnifying-glass" class="w-4 h-4" />
                        </div>
                        <input
                            type="text"
                            placeholder="Cari judul artikel..."
                            class="w-full pl-9 pr-4 py-2 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-sbh-green focus:border-transparent text-zinc-700 dark:text-zinc-300"
                        >
                    </div>

                    <!-- 2. Dropdown Kategori -->
                    <div>
                        <select class="w-full px-3 py-2 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-sbh-green text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <option value="">Semua Kategori</option>
                            <option value="program-kerja">Program Kerja</option>
                            <option value="berita">Berita & Kegiatan</option>
                            <option value="cerita-yayasan">Cerita Inspiratif</option>
                            <option value="pengumuman">Pengumuman</option>
                        </select>
                    </div>

                    <!-- 3. Dropdown Status -->
                    <div>
                        <select class="w-full px-3 py-2 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-sbh-green text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <option value="">Semua Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Diarsipkan</option>
                        </select>
                    </div>
                </div>

                <!-- TABEL UTAMA -->
                {{-- REVISI: Penambahan komputasi max-height responsif agar di mobile tidak meluber ke bawah --}}
                <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-450px)] lg:max-h-[calc(100vh-280px)] rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-sbh-green text-xs text-white dark:bg-green-950 dark:text-green-300">
                            <tr>
                                <th class="sticky top-0 z-10 lg:z-20 bg-sbh-green dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">Judul Artikel</th>
                                <th class="sticky top-0 z-10 lg:z-20 bg-sbh-green dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">Kategori</th>
                                <th class="sticky top-0 z-10 lg:z-20 bg-sbh-green dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">Status</th>
                                <th class="sticky top-0 z-10 lg:z-20 bg-sbh-green dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider text-center">Kelola</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                            @foreach (range(1, 25) as $i)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                    <td class="px-4 py-3.5 text-sm">
                                        <div class="font-medium text-zinc-900 dark:text-white">Sosialisasi Kesehatan Stunting</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Penulis: Admin SBH</div>
                                    </td>
                                    <td class="px-4 py-3.5 text-sm">Program Kerja</td>
                                    <td class="px-4 py-3.5 text-sm">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300">
                                            Published
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-sm">
                                        <div class="flex justify-center items-center gap-2">
                                            <div class="p-1.5 rounded-xl bg-sbh-green text-white dark:bg-green-950 relative z-0 cursor-pointer hover:bg-green-600 transition-colors">
                                                <flux:icon variant="outline" icon="pencil" class="size-3.5!" />
                                            </div>
                                            <div class="p-1.5 rounded-xl bg-sbh-red text-white dark:bg-red-950 relative z-0 cursor-pointer hover:bg-red-600 transition-colors">
                                                <flux:icon variant="outline" icon="trash" class="size-3.5!" />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECONDARY UX (KONTAINER KEDUA - PANEL/POPUP) -->
            <div x-show="activeSubPanel !== 'none'" class="contents">

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
                        bg-white lg:bg-sbh-yellow/10 dark:bg-zinc-900 p-5 border border-zinc-200 dark:border-zinc-700 shadow-xl lg:shadow-sm space-y-4"
                >
                    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-700 rounded-full mx-auto lg:hidden mb-2"></div>

                    <!-- Header Kontainer Kedua -->
                    <div class="flex justify-between items-center border-b border-zinc-200 dark:border-zinc-800 pb-2">
                        <div class="flex items-center gap-2 text-sbh-green">
                            {{-- REVISI: Mengubah dari :icon menjadi x-bind:icon --}}
                            <flux:icon.folder x-show="activeSubPanel === 'categories'" variant="solid" class="size-4" />
                            <flux:icon.tag x-show="activeSubPanel === 'tags'" variant="solid" class="size-4" />
                            <h3 class="font-bold text-zinc-900 dark:text-white text-md" x-text="activeSubPanel === 'categories' ? 'Kelola Kategori' : 'Kelola Tag'"></h3>
                        </div>
                        <button @click="activeSubPanel = 'none'" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                            <flux:icon variant="outline" icon="x-mark" class="size-4" />
                        </button>
                    </div>

                    <!-- KONTEN DINAMIS -->
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-zinc-500" x-text="activeSubPanel === 'categories' ? 'Nama Kategori Baru' : 'Nama Tag Baru'"></label>
                            <div class="flex gap-2">
                                <input type="text" x-bind:placeholder="activeSubPanel === 'categories' ? 'Misal: Berita Internal' : 'Misal: #stunting'" class="flex-1 px-3 py-2 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-sbh-green text-zinc-700 dark:text-zinc-300">
                                <button class="px-4 py-2 bg-sbh-green text-white text-sm font-medium rounded-lg hover:bg-green-700">Simpan</button>
                            </div>
                        </div>

                        <div class="max-h-[300px] lg:max-h-[250px] overflow-y-auto border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 p-2 divide-y divide-zinc-200 dark:divide-zinc-700">
                            <template x-if="activeSubPanel === 'categories'">
                                <div class="space-y-1">
                                    @foreach (range(1, 25) as $i)
                                    <div class="flex justify-between items-center py-2.5 px-2 text-sm">
                                        <span class="text-zinc-700 dark:text-zinc-300 font-medium">Program Kerja</span>
                                        <button class="text-zinc-400 hover:text-sbh-red"><flux:icon variant="outline" icon="trash" class="size-4" /></button>
                                    </div>
                                    @endforeach
                                    <div class="flex justify-between items-center py-2.5 px-2 text-sm">
                                        <span class="text-zinc-700 dark:text-zinc-300 font-medium">Cerita Inspiratif</span>
                                        <button class="text-zinc-400 hover:text-sbh-red"><flux:icon variant="outline" icon="trash" class="size-4" /></button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="activeSubPanel === 'tags'">
                                <div class="space-y-1">
                                    <div class="flex justify-between items-center py-2.5 px-2 text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400 font-medium">#kesehatan</span>
                                        <button class="text-zinc-400 hover:text-sbh-red"><flux:icon variant="outline" icon="trash" class="size-4" /></button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- <div class="bg-sbh-yellow/10 py-2 px-4 rounded-2xl">
        <div class="flex justify-between items-center py-2 ">
            Daftar Artikel
            <div class="rounded-xl w-8 h-8 bg-sbh-green flex justify-center items-center [&_svg]:text-sbh-yellow">
                <flux:icon variant="solid" icon="plus" class="" />
            </div>
        </div>

        <!-- BARIS FILTER SEDERHANA-->
        <!-- Menggunakan grid agar rapi di mobile (1 kolom) dan desktop (3 kolom + otomatis) -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white dark:bg-zinc-900 p-3 mb-2 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">

            <!-- 1. Input Pencarian -->
            <div class="md:col-span-2 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                    <flux:icon variant="outline" icon="magnifying-glass" class="w-4 h-4" />
                </div>
                <input
                    type="text"
                    placeholder="Cari judul artikel..."
                    class="w-full pl-9 pr-4 py-2 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-sbh-green focus:border-transparent text-zinc-700 dark:text-zinc-300"
                >
            </div>

            <!-- 2. Dropdown Kategori -->
            <div>
                <select class="w-full px-3 py-2 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-sbh-green text-zinc-700 dark:text-zinc-300 cursor-pointer">
                    <option value="">Semua Kategori</option>
                    <option value="program-kerja">Program Kerja</option>
                    <option value="berita">Berita & Kegiatan</option>
                    <option value="cerita-yayasan">Cerita Inspiratif</option>
                    <option value="pengumuman">Pengumuman</option>
                </select>
            </div>

            <!-- 3. Dropdown Status -->
            <div>
                <select class="w-full px-3 py-2 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-sbh-green text-zinc-700 dark:text-zinc-300 cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Diarsipkan</option>
                </select>
            </div>

        </div>

        <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-280px)] rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left border-collapse">
                <!-- HEADER TABEL -->
                <thead class="bg-sbh-green text-xs text-white dark:bg-green-950 dark:text-green-300">
                    <tr>
                        <th class="sticky top-0 lg:z-20 bg-sbh-green dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">Judul Artikel</th>
                        <th class="sticky top-0 lg:z-20 bg-sbh-green dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">Kategori</th>
                        <th class="sticky top-0 lg:z-20 bg-sbh-green dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider">Status</th>
                        <th class="sticky top-0 lg:z-20 bg-sbh-green dark:bg-green-950 px-4 py-3 font-semibold uppercase tracking-wider text-center">Kelola</th>
                    </tr>
                </thead>

                <!-- ISI TABEL -->
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                    @foreach (range(1, 10) as $i)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <td class="px-4 py-3.5 text-sm">
                                <div class="font-medium text-zinc-900 dark:text-white">Sosialisasi Kesehatan Stunting</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Penulis: Admin SBH</div>
                            </td>
                            <td class="px-4 py-3.5 text-sm">Program Kerja</td>
                            <td class="px-4 py-3.5 text-sm">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300">
                                    Published
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-sm">
                                <div class="flex justify-center items-center gap-2">
                                    <div class="p-1 rounded-xl bg-sbh-green text-white dark:bg-green-950 dark:text-sbh-gold relative cursor-pointer">
                                        <flux:icon variant="outline" icon="pencil" class="" />
                                    </div>
                                    <div class="p-1 rounded-xl bg-sbh-red text-white dark:bg-red-950 dark:text-sbh-gold relative cursor-pointer">
                                        <flux:icon variant="outline" icon="trash" class="" />
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div> --}}
</div>
