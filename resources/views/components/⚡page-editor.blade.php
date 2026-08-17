<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Livewire\Traits\WithNotifications;

use App\Models\Page;
use Spatie\Activitylog\Models\Activity;

// #[Translatable('title', 'slug', 'content', 'meta_title', 'meta_description')]
// #[Fillable('title', 'slug', 'content', 'status', 'meta_title','meta_description', 'published_at', 'created_at', 'updated_at')]

new class extends Component
{
    use WithFileUploads;
    use WithNotifications;

    public ?Page $page;

    public array $title =[];
    public array $slug = [];
    public array $content = [];
    public array $meta_title = [];
    public array $meta_description = [];

    public  $status;
    public string $created_at;

    public bool $isPage = true;

    public ?int $page_id = null;

    public array $activeLocales = [];

    public function mount (?Page $page = null)
    {
        $this->page = $page ?? new Page();

        // 1. Cek bahasa apa saja yang sudah disimpan di database untuk halaman ini
        $existingLocales = array_keys($this->page->getTranslations('title') ?: []);
        
        // 2. Jika artikel baru, jadikan 'id' sebagai bahasa default pertama
        if (empty($existingLocales)) {
            $existingLocales = ['id'];
        }
        
        $existingLocales = array_keys($this->page->getTranslations('title') ?: []);

        $this->activeLocales = empty($existingLocales) ? ['id'] : $existingLocales;

        // 🌟 2. Muat data SEMUA kolom untuk setiap bahasa yang aktif
        foreach ($this->activeLocales as $locale) {
            $this->title[$locale]            = $this->page->getTranslation('title', $locale, false) ?: '';
            $this->slug[$locale]             = $this->page->getTranslation('slug', $locale, false) ?: '';
            $this->content[$locale]          = $this->page->getTranslation('content', $locale, false) ?: '';
            $this->meta_title[$locale]       = $this->page->getTranslation('meta_title', $locale, false) ?: '';
            $this->meta_description[$locale] = $this->page->getTranslation('meta_description', $locale, false) ?: '';
        }

        $this->status = $this->page->status;

    }

    public function addLanguage($localeCode)
    {
        if (!in_array($localeCode, $this->activeLocales)) {
            $this->activeLocales[] = $localeCode;
            
            // Siapkan wadah kosong agar tidak error
            $this->title[$localeCode] = '';
            $this->content[$localeCode] = '';
        }
    }

    public function getAuditTrailProperty()
    {
        if (!isset($this->page_id)) {
            return collect(); // Jika artikel baru/belum disave, kosongkan
        }

        return Activity::forSubject(\App\Models\Page::find($this->page_id))
            ->with('causer') // Ambil data siapa yang melakukan aksi
            ->latest()
            ->get();
    }

    protected function rules() 
    {
        $rules = [
            'status' => 'required|in:online,offline',
        ];
        
        // Asumsi Anda memiliki $this->activeLocales di komponen Livewire Anda
        // Atau bisa panggil dari config: config('app.supported_locales', ['id', 'en'])
        foreach ($this->activeLocales as $locale) {
            
            // 1. Judul (Biasanya tidak wajib unik, tapi jika Anda ingin unik, gunakan format ini)
            $rules["title.{$locale}"] = [
                'required', 
                'string',
                'min:3'
            ];

            // 2. Slug (WAJIB UNIK berdasarkan bahasanya)
            // Perhatikan bagian "slug->{$locale}"
            $rules["slug.{$locale}"] = [
                'required', 
                'string',
                // Ganti 'posts' dengan nama tabel artikel Anda yang sebenarnya!
                Rule::unique('posts', "slug->{$locale}")->ignore($this->article_id)
            ];

            // 3. Konten Utama
            $rules["content.{$locale}"] = 'required|min:5';

            // 4. SEO Meta Data (Ditambahkan batas maksimal agar bagus untuk SEO)
            $rules["meta_title.{$locale}"] = 'required|min:5|max:60'; 
            $rules["meta_description.{$locale}"] = 'required|min:5|max:160';
        }

        return $rules;
    }
    // 'title', 'slug', 'content', 'status', 'meta_title','meta_description',
    // protected function rules() {
    //     return [
    //         'title.*'             => ['required', Rule::unique('pages')->ignore($this->page_id)],
    //         'slug.*'              => ['required', Rule::unique('pages')->ignore($this->page_id)],
    //         'content.*'           => 'required|min:5',
    //         'meta_title.*'        => 'required|min:5',
    //         'meta_description.*'  => 'required|min:5',
    //     ];
    // }
        // 'category_id'       => 'required|numeric',
        // 'tags'              => 'required|array|min:1',
        // 'featured_image'    => 'required',
    protected function messages() 
    {
        $messages = [
            'status.required' => __('Status publikasi wajib dipilih.'),
            'status.in'       => __('Status publikasi tidak valid.'),
        ];

        foreach ($this->activeLocales as $locale) {
            $lang = strtoupper($locale);
            
            // Pesan Kustom Multi-bahasa
            $messages["title.{$locale}.required"] = __("Judul ($lang) wajib diisi.");
            $messages["title.{$locale}.min"]      = __("Judul ($lang) minimal 3 karakter.");
            $messages["content.{$locale}.required"] = __("Isi konten ($lang) tidak boleh kosong.");
        }

        return $messages;
    }
    // protected function messages() {
    //     return [
    //         // Format: 'nama_variabel.nama_rule' => 'Pesan kustom'

    //         'title.required' => __('Judul artikel tidak boleh dikosongkan.'),
    //         'title.unique'   => __('Judul ini sudah dipakai di artikel lain. Silakan buat yang berbeda.'),

    //         'category_id.required' => __('Anda belum memilih kategori artikel.'),

    //         'content.required' => __('Isi tulisan tidak boleh kosong.'),
    //         'content.min'      => __('Tulisan terlalu pendek, minimal 5 karakter.'),

    //         'tags.required' => __('Anda wajib menambahkan minimal satu tag.'),
    //         'tags.min'      => __('Minimal pilih satu tag dari daftar.'),

    //         'featured_image.required' => __('Tolong tentukan gambar sampul untuk artikel ini.'),
    //     ];
    // }

    public function savePage($content)
    {
        $this->content = $content;

        $validatedData = $this->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,' . $this->page_id,
            'content' => 'nullable|string',
            'status' => 'required|in:online,offline',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'created_at' => 'nullable|date',
        ]);

        if ($this->page_id) {
            // Update existing page
            $page = Page::findOrFail($this->page_id);
            $page->update($validatedData);
        } else {
            // Create new page
            $page = Page::create($validatedData);
            $this->page_id = $page->id; // Set the page_id for future updates
        }

        $this->notify(__('ui.notification.page_saved'), 'success',);
    }
};
?>

{{-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi --}}

<x-slot:title>{{ __('ui.header.write_page') }}</x-slot:title>

<div class="w-full md:h-[calc(100vh-4rem)] flex-1 min-h-0 gap-2 overflow-hidden flex flex-col pt-2 md:pt-0 ">
    <form 
        {{-- class="w-full rounded-xl overflow-hidden shadow-sm transition-all duration-300 ease-in-out" --}}
        class="flex-1 min-h-0 h-full flex flex-col w-full gap-2 relative"
        x-init="isPage = @json($isPage)"
        wire:submit="save" @submit.capture="flushEditorSync()"
        @buka-modal-link.window=" isLinkOpen = true; linkInputText = $event.detail.text || '';"
        x-data="{
            viewMode: 'single', //'single' atau 'split'
            activeLang: '{{ $activeLocales[0] ?? 'id' }}', 
            isMetaOpen: false,
            isAuditOpen: false,
            title: @entangle('title'), 
            createdAt: @entangle('created_at') ,
            isLinkOpen: false,
            linkInputText: '',
            linkInputUrl: ''
        }"
        >

        {{-- HEADER, META, BUTTONS, TOOLBARS --}}
        <div class="flex-none w-full bg-white rounded-xl z-40">
            {{-- META's --}}
            <div class="w-full pt-4 pb-3 px-4 md:px-4 flex flex-col md:flex-row md:items-center justify-between gap-4">

                {{-- INPUT JUDUL DINAMIS --}}
                <div class="relative flex flex-row justify-start items-center w-full gap-4 max-w-4xl">
                    <input type="text" 
                        x-model="title[activeLang]" 
                        placeholder="{{ __('ui.page.title') }}" 
                        class="w-full p-2.5 text-sm md:text-md font-bold bg-transparent outline-none focus:ring-0 border-0 border-b-2 border-zinc-200 focus:border-zinc-400 dark:border-zinc-800 dark:focus:border-zinc-600 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-600 transition-colors" />
                </div>

                {{-- BUTTONS --}}
                <div class="flex items-center justify-end md:justify-between gap-2 shrink-0 md:ml-4">
                    {{-- @php $canEdit =  in_array($status, ['draft', null]); @endphp --}}

                    {{-- @if(empty($article_id) || $status === 'draft') --}}
                        {{-- COVER MODAL BUTTON --}}
                        {{-- <button type="button" wire:click="scanEditorImages" @click="$dispatch('buka-featured-modal')"
                            class="group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none"
                            title="{{ __('ui.tip.cover') }}">
                            <x-dynamic-component :component="'lucide-image'" class="h-5 w-5 origin-center group-hover:animate-blocks" stroke-width="2" />
                            <span class="hidden 2xl:inline">{{ __('ui.button.cover') }}</span>
                        </button> --}}

                        {{-- META MODAL BUTTON --}}
                        <button type="button" @click="isMetaOpen = true"
                            class="relative group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none"
                            title="{{ __('ui.tip.properties') }}">

                            <x-dynamic-component :component="'lucide-file-sliders'" class="h-5 w-5 origin-center group-hover:animate-tag" stroke-width="2" />
                            <span class="hidden 2xl:inline">{{ __('ui.button.properties') }}</span>

                            {{-- 🚨 INDIKATOR DOT MERAH ERROR 🚨 --}}
                            {{-- @if($errors->hasAny(['category_id', 'tags']))
                                <!-- Wrapper absolute diletakkan di luar jangkauan flex -->
                                <span class="absolute -top-1.5 -right-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-zinc-900 z-10">
                                    !
                                    <!-- Efek ping/denyut opsional agar lebih menarik perhatian -->
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                </span>
                            @endif --}}
                        </button>

                        @if(!empty($article_id) && $status === 'draft')
                            {{-- REVIEW MODAL BUTTON --}}
                            <button type="button"
                                x-on:click="$dispatch('buka-modal-review')"
                                wire:loading.attr="disabled"
                                title="{{ __('ui.tip.review') }}"
                                class="group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none">

                                <span class="flex items-center justify-center gap-2" wire:loading.remove wire:target="submitForReview">
                                    <x-dynamic-component :component="'lucide-send'" class="h-5 w-5 origin-center group-hover:animate-save" stroke-width="2" />
                                    <span class="hidden 2xl:block"> {{ __('ui.button.review') }} </span>
                                    {{-- <span class="md:hidden"> Ajukan </span> --}}
                                </span>

                                <div wire:loading.flex wire:target="submitForReview" class="flex-row items-center justify-center gap-2">
                                    <span>{{ __('ui.button.sending') }}...</span>
                                </div>
                            </button>
                        @endif

                        {{-- SAVE BUTTON --}}
                        <button 
                            type="button"
                            x-on:click="if(window.tiptapEditor) { $wire.savePage(window.tiptapEditor.getHTML()) }"
                            wire:loading.attr="disabled"
                            :title="$wire.article_id ? '{{ __('ui.tip.save') }}' : '{{ __('ui.tip.create') }}'"
                            class="group inline-flex items-center gap-2 p-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none">

                            <!-- PENTING: Ubah wire:target menjadi saveArticle -->
                            <span class="flex items-center justify-center gap-2" >
                                <x-dynamic-component :component="'lucide-save'" class="h-5 w-5 origin-center group-hover:animate-save" stroke-width="2" />
                                <span wire:loading.remove wire:target="saveArticle" class="hidden 2xl:block" x-text="$wire.article_id ? '{{ __('ui.button.save') }}' : '{{ __('ui.button.create') }}'"></span>
                            </span>

                            <div  class="hidden 2xl:block flex-row items-center justify-center gap-2">
                                <span wire:loading.flex wire:target="savePage">{{ __('ui.button.saving') }}...</span>
                            </div>
                        </button>
                    {{-- @endif --}}


                    <!-- 🎛️ TOMBOL TOGGLE PANEL AUDIT (Desktop & Mobile) -->
                    <button type="button" @click="isAuditOpen = !isAuditOpen"
                        class="group inline-flex items-center gap-2 p-2 text-sm font-semibold border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm cursor-pointer select-none"
                        :title="isAuditOpen ? '{{ __('ui.tip.audit_trail_close') }}' : '{{ __('ui.tip.audit_trail_open') }}'"
                        x-bind:class="isAuditOpen ? 'bg-foresty text-goldy' : 'bg-white text-zinc-600'" >

                        {{-- 🔥 Ganti class menjadi group-hover:animate-tick --}}
                        <x-dynamic-component :component="'lucide-clock-fading'" class="h-5 w-5 origin-center group-hover:animate-tick" stroke-width="2" />

                        <span class="hidden 2xl:inline" x-text="isAuditOpen ? '{{ __('ui.button.close') }}' : '{{ __('ui.button.audit_trail') }}'"></span>
                    </button>

                </div>

            </div>
        </div>
        
        <!-- WORKSPACE -->
        <div class="flex-1 flex flex-row min-h-0 h-full w-full gap-4 relative">
    
            {{-- 🟢 KOTAK UTAMA (KIRI): Selalu muncul (Lebar penuh atau 50% saat Split) --}}
            <div class="flex-1 flex flex-col min-h-0 h-full bg-white dark:bg-zinc-950 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden relative">
                
                {{-- Pembungkus Editor Kiri --}}
                <div class="flex-1 flex flex-col min-h-0 h-full w-full relative">
                    
                    {{-- Looping semua bahasa, tapi yang ditampilkan HANYA yang sesuai dengan 'activeLang' (diatur oleh FAB Widget) --}}
                    @foreach ($activeLocales as $locale)
                        <div x-show="activeLang === '{{ $locale }}'" x-cloak class="flex-1 flex flex-col min-h-0 h-full w-full absolute inset-0">
                            
                            {{-- Panggil Komponen TipTap --}}
                            <x-editor 
                                :locale="$locale" 
                                content-model="content.{{ $locale }}" 
                                title-model="title.{{ $locale }}" 
                            />

                        </div>
                    @endforeach
                    
                </div>
            </div>

            {{-- 🔵 KOTAK KEDUA (KANAN): Hanya Muncul Saat Split Mode Diaktifkan --}}
            <div x-show="viewMode === 'split'" x-cloak 
                class="flex-1 flex flex-col min-h-0 h-full bg-white dark:bg-zinc-950 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden relative">
                
                {{-- Header Kotak Kanan (Pilih Bahasa Target & Tombol Salin) --}}
                <div class="flex-none shrink-0 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800/50 flex justify-between items-center z-10">
                    
                    {{-- Dropdown Pilih Bahasa Kanan --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wide">Terjemahkan ke:</span>
                        <select x-model="rightLang" class="text-xs font-bold text-blue-800 dark:text-blue-300 bg-transparent border-0 py-0 pl-1 pr-6 focus:ring-0 cursor-pointer uppercase outline-none">
                            @foreach($activeLocales as $loc)
                                <option value="{{ $loc }}">{{ $loc }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    {{-- Tombol Salin (Menyalin dari kotak kiri/activeLang ke kotak kanan/rightLang) --}}
                    <button type="button" 
                        @click="$wire.copyFromDefault(rightLang, activeLang)" 
                        class="text-xs font-bold bg-blue-600 text-white px-3 py-1.5 rounded-md hover:bg-blue-700 transition-colors flex items-center gap-1.5 cursor-pointer shadow-sm">
                        <x-dynamic-component component="lucide-copy" class="w-3.5 h-3.5" stroke-width="2.5" /> 
                        Salin Layout
                    </button>
                </div>

                {{-- Input Judul Khusus Kotak Kanan --}}
                <div class="flex-none shrink-0 px-4 py-3 border-b border-zinc-100 dark:border-zinc-800 z-10 bg-zinc-50/50 dark:bg-zinc-900/30">
                    <input type="text" 
                        x-model="title[rightLang]" 
                        placeholder="Terjemahkan judul di sini..." 
                        class="w-full text-lg font-bold bg-transparent border-0 p-0 focus:ring-0 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-600">
                </div>

                {{-- Pembungkus Editor Kanan --}}
                <div class="flex-1 flex flex-col min-h-0 h-full w-full relative">
                    
                    {{-- Looping semua bahasa, tapi yang ditampilkan HANYA yang sesuai dengan 'rightLang' --}}
                    @foreach ($activeLocales as $locale)
                        <div x-show="rightLang === '{{ $locale }}'" x-cloak class="flex-1 flex flex-col min-h-0 h-full w-full absolute inset-0">
                            
                            {{-- Panggil Komponen TipTap --}}
                            <x-editor 
                                :locale="$locale" 
                                content-model="content.{{ $locale }}" 
                                title-model="title.{{ $locale }}" 
                            />

                        </div>
                    @endforeach
                    
                </div>
            </div>
        </div>

        <!-- <div class="flex-1 flex flex-row min-h-0 h-full w-full gap-4 relative">
            <div class="flex-1 flex flex-col min-h-0 overflow-hidden relative bg-white  rounded-2xl dark:bg-zinc-950">
                @/foreach ($activeLocales as $locale)
                    {{-- 🌟 UBAH CLASS DI SINI JUGA: Tambahkan flex flex-col flex-1 min-h-0 --}}
                    <div x-show="activeLang === '{/{ $locale }}'" x-cloak class="w-full h-full flex flex-col flex-1 min-h-0 relative">
                        
                        <x-editor
                            :/locale="$locale" 
                            content-model="content.{/{ $locale }}" 
                            title-model="title.{/{ $locale }}"/>
                            
                    </div>
                @/endforeach
            </div>
        </div> -->

        {{-- ================= 3. WIDGET MELAYANG (FAB) BAHASA ================= --}}
        {{-- <div x-data="{ isFabOpen: false }" class="absolute bottom-6 left-6 z-70 flex items-center gap-2">
            
            <div x-show="isFabOpen" @click.outside="isFabOpen = false" x-cloak 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute bottom-full left-0 mb-3 w-56 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl overflow-hidden flex flex-col">
                
                <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 text-xs font-bold text-zinc-500 uppercase tracking-wider">
                    Ganti Bahasa (Utama)
                </div>
                
                @foreach($activeLocales as $locale)
                    <button type="button" @click="activeLang = '{{ $locale }}'; isFabOpen = false" 
                            class="px-4 py-2 text-left text-sm font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center justify-between transition-colors cursor-pointer">
                        {{ strtoupper($locale) }}
                        <x-dynamic-component component="lucide-check" class="w-4 h-4 text-forest" x-show="activeLang === '{{ $locale }}'" />
                    </button>
                @endforeach

                <div class="border-t border-zinc-200 dark:border-zinc-700 my-1"></div>
                
                <button type="button" @click="viewMode = viewMode === 'single' ? 'split' : 'single'; isFabOpen = false; if(viewMode === 'split') { $dispatch('sidebar-toggle', false); }" 
                        class="px-4 py-3 text-left text-sm font-bold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors flex items-center gap-2 cursor-pointer">
                    <x-dynamic-component component="lucide-split-square-horizontal" class="w-4 h-4" />
                    <span x-text="viewMode === 'single' ? 'Mode Penerjemah' : 'Tutup Penerjemah'"></span>
                </button>
            </div>

            <button type="button" @click="isFabOpen = !isFabOpen" 
                    class="bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 px-4 py-2.5 rounded-full shadow-lg font-bold flex items-center gap-2 hover:scale-105 active:scale-95 transition-all cursor-pointer border border-zinc-700 dark:border-zinc-200">
                <x-dynamic-component component="lucide-globe" class="w-4 h-4" />
                <span x-text="activeLang.toUpperCase()"></span>
                <x-dynamic-component component="lucide-chevron-up" class="w-4 h-4 opacity-70" />
            </button>
        </div> --}}

        {{-- ================= 3. WIDGET MELAYANG (FAB) BAHASA ================= --}}
        <div x-data="{ isFabOpen: false }" class="absolute bottom-6 left-6 z-50 flex items-center gap-2">
            
            <div x-show="isFabOpen" @click.outside="isFabOpen = false" x-cloak 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="absolute bottom-full left-0 mb-3 w-60 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl overflow-hidden flex flex-col">
                
                <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 text-xs font-bold text-zinc-500 uppercase tracking-wider">
                    Ganti Bahasa (Utama)
                </div>
                
                {{-- List Bahasa yang Sudah Aktif --}}
                @foreach($activeLocales as $locale)
                    <button type="button" @click="activeLang = '{{ $locale }}'; isFabOpen = false" 
                            class="px-4 py-2.5 text-left text-sm font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center justify-between transition-colors cursor-pointer text-zinc-800 dark:text-zinc-200">
                        {{ strtoupper($locale) }}
                        <x-dynamic-component component="lucide-check" class="w-4 h-4 text-forest" x-show="activeLang === '{{ $locale }}'" />
                    </button>
                @endforeach

                {{-- 🌟 FITUR TAMBAH BAHASA YANG DIKEMBALIKAN 🌟 --}}
                @php
                    $allLocales = config('app.supported_locales', ['id', 'en']);
                    $availableToAdd = array_diff($allLocales, $activeLocales);
                @endphp
                
                @if(count($availableToAdd) > 0)
                    <div class="border-t border-zinc-100 dark:border-zinc-700"></div>
                    
                    {{-- Menggunakan x-data lokal untuk dropdown (accordion) Tambah Bahasa --}}
                    <div x-data="{ openAddMenu: false }" class="relative flex flex-col">
                        <button type="button" @click="openAddMenu = !openAddMenu" 
                                class="px-4 py-2.5 text-left text-sm font-bold text-forest hover:bg-forest/5 dark:hover:bg-forest/10 flex items-center justify-between transition-colors cursor-pointer">
                            <span class="flex items-center gap-2">
                                <x-dynamic-component component="lucide-plus-circle" class="w-4 h-4" stroke-width="2.5" />
                                Tambah Bahasa
                            </span>
                            <x-dynamic-component component="lucide-chevron-down" class="w-4 h-4 transition-transform" x-bind:class="openAddMenu ? 'rotate-180' : ''" />
                        </button>
                        
                        {{-- Daftar Bahasa Baru --}}
                        <div x-show="openAddMenu" x-collapse x-cloak class="bg-zinc-50 dark:bg-zinc-900/50 flex flex-col border-y border-zinc-100 dark:border-zinc-700">
                            @foreach($availableToAdd as $addLocale)
                                <button type="button" 
                                        wire:click="addLanguage('{{ $addLocale }}')" 
                                        @click="isFabOpen = false; activeLang = '{{ $addLocale }}'" 
                                        class="px-10 py-2 text-left text-sm font-semibold text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition-colors uppercase cursor-pointer">
                                    + {{ $addLocale }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="border-t border-zinc-200 dark:border-zinc-700 my-1"></div>
                
                {{-- Tombol Toggle Layar Terbelah --}}
                <button type="button" @click="viewMode = viewMode === 'single' ? 'split' : 'single'; isFabOpen = false; if(viewMode === 'split') { $dispatch('sidebar-toggle', false); }" 
                        class="px-4 py-3 text-left text-sm font-bold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors flex items-center gap-2 cursor-pointer">
                    <x-dynamic-component component="lucide-split-square-horizontal" class="w-4 h-4" />
                    <span x-text="viewMode === 'single' ? 'Mode Penerjemah' : 'Tutup Penerjemah'"></span>
                </button>
            </div>

            {{-- Tombol Trigger FAB Utama --}}
            <button type="button" @click="isFabOpen = !isFabOpen" 
                    class="bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 px-4 py-2.5 rounded-full shadow-lg font-bold flex items-center gap-2 hover:scale-105 active:scale-95 transition-all cursor-pointer border border-zinc-700 dark:border-zinc-200">
                <x-dynamic-component component="lucide-globe" class="w-4 h-4" />
                <span x-text="activeLang.toUpperCase()"></span>
                <x-dynamic-component component="lucide-chevron-up" class="w-4 h-4 opacity-70 transition-transform" x-bind:class="isFabOpen ? 'rotate-180' : ''" />
            </button>
        </div>

        @include('components.editor.modal-audit')
        
        <livewire:link-selector />
        
        {{-- ================== UNIVERSAL MODAL / BOTTOM SHEET ================== --}}
        {{-- Trik Tailwind: items-end untuk HP (Bottom Sheet), md:items-center untuk Desktop (Modal Tengah) --}}
        <template x-teleport="body">
            <div x-show="isMetaOpen"
                class="fixed inset-0 z-999 flex items-end justify-center md:items-center p-0 md:p-4"
                style="display: none;">

                {{-- Overlay Backdrop --}}
                <div x-show="isMetaOpen" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="isMetaOpen = false"></div>

                {{-- Konten Modal --}}
                {{-- Meluncur dari bawah di HP, Fade & Scale di Desktop --}}
                <div x-show="isMetaOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full md:translate-y-4 md:opacity-0 md:scale-95" x-transition:enter-end="translate-y-0 md:opacity-100 md:scale-100" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 md:opacity-100 md:scale-100" x-transition:leave-end="translate-y-full md:translate-y-4 md:opacity-0 md:scale-95" class="relative bg-white dark:bg-zinc-900 w-full md:w-125 rounded-t-2xl md:rounded-2xl h-[85vh] md:h-[90vh] flex flex-col overflow-hidden shadow-2xl z-10">

                    {{-- Handle Drag (Hanya terlihat di Mobile) --}}
                    <div class="md:hidden w-12 h-1 bg-zinc-300 dark:bg-zinc-700 rounded-full mx-auto my-3" @click="isMetaOpen = false"></div>

                    {{-- Header Modal (Desktop) --}}
                    <div
                        class="hidden md:flex p-4 border-b border-zinc-100 dark:border-zinc-800 items-center justify-between">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 tracking-wide">
                            Pengaturan Meta Dokumen</h3>
                        <button type="button" @click="isMetaOpen = false"
                            class="text-zinc-400 hover:text-red-500 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Konten Input Modal --}}
                    <div class="p-5 md:p-6 overflow-y-auto space-y-5 bg-zinc-50 dark:bg-zinc-950 flex-1">
                        <div class="md:hidden">
                            <h4
                                class="text-xs font-bold text-zinc-400 dark:text-zinc-500 mb-2 tracking-wide uppercase">
                                Detail Dokumen</h4>
                        </div>

                        {{-- Input Tanggal --}}
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tanggal
                                Dibuat</label>
                            <input type="date" x-model="createdAt" class="w-full p-2.5 text-sm rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-forest/20 focus:border-forest text-zinc-800 dark:text-zinc-200" />

                        </div>

                        {{-- Kategori --}}
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</label>
                            {{-- 🌟 GANTI x-ref="catInput" MENJADI wire:model="status" --}}
                            <select wire:model="status" class="w-full p-2.5 text-sm rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-forest/20 focus:border-forest text-zinc-800 dark:text-zinc-200">
                                <option value="">Pilih Status...</option>
                                <option value="online">Online</option>
                                <option value="offline">Offline</option>
                            </select>
                            
                            {{-- 🌟 TAMBAHKAN PENAMPIL ERROR --}}
                            @error('status')
                                <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    {{-- Tombol Tutup Modal --}}
                    <div
                        class="p-4 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                        <button type="button" @click="isMetaOpen = false"
                            class="w-full bg-forest hover:bg-forest/90 text-white py-2.5 rounded-xl text-sm font-bold text-center transition-colors cursor-pointer shadow-sm">
                            Terapkan & Selesai
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </form>
</div>
