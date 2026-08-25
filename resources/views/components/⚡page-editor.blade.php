<?php

use Livewire\Component;
use Livewire\WithFileUploads;

use App\Models\Page;

use Illuminate\Support\Str;

use Spatie\Activitylog\Models\Activity;

use App\Livewire\Traits\WithNotifications;
use App\Livewire\Traits\HasContentBlocks;

new class extends Component
{
    use WithFileUploads;
    use WithNotifications;
    use HasContentBlocks;

    public ?Page $page = null;

    public array $activeLocales = [];

    public $layoutMode = 'single';//single split
    public $singleActiveLang = 'id';
    public $splitLanguages = [];

    // public array $title = [];
    public array $page_title = [];
    public array $slug = [];
    public array $meta_title = [];
    public array $meta_description = [];

    // 🌟 Pendekatan Hibrida: Pisahkan data konten dan urutan
    public array $content = [];
    public array $blockOrder = []; // Menyimpan urutan ID secara akurat

    public $status;

    public $isEditMode = false;

    protected function rules()
    {
        $rules = [
            'status'  => 'required|in:offline,online',
            'content' => 'array',
        ];

        foreach ($this->activeLocales as $locale) {
            // UBAH VALIDASI MENJADI page_title
            $rules["page_title.{$locale}"] = 'required|string|max:255';
            $rules["slug.{$locale}"]  = 'required|string|max:255';
        }

        return $rules;
    }

    protected function messages()
    {
        $messages = [
            'status.required' => 'Status halaman wajib dipilih.',
            'status.in'       => 'Status tidak valid.',
        ];

        foreach ($this->activeLocales as $locale) {
            $lang = strtoupper($locale);
            // UBAH PESAN GALAT MENJADI page_title
            $messages["page_title.{$locale}.required"] = "Judul ({$lang}) wajib diisi.";
            $messages["page_title.{$locale}.max"]      = "Judul ({$lang}) maksimal 255 karakter.";
            $messages["slug.{$locale}.required"]  = "Slug/URL ({$lang}) wajib diisi.";
            $messages["slug.{$locale}.max"]       = "Slug/URL ({$lang}) maksimal 255 karakter.";
        }

        return $messages;
    }

    public function mount($pageSlug = null)
    {
        // 1. Konfigurasi Bahasa Dasar
        $this->activeLocales = config('app.supported_locales', ['id', 'en']);
        $this->splitLanguages = array_slice($this->activeLocales, 0, 2);

        if (!in_array($this->singleActiveLang, $this->activeLocales)) {
            $this->singleActiveLang = $this->activeLocales[0] ?? 'id';
        }

        // 2. KUERI PENCARIAN SUPER KETAT & FLEKSIBEL
        $pageModel = null;
        if (!empty($pageSlug)) {
            $pageModel = \App\Models\Page::where(function($query) use ($pageSlug) {
                // Skenario A: Jika URL berupa ID angka
                if (is_numeric($pageSlug)) {
                    $query->where('id', $pageSlug);
                }

                // Skenario B: Jika slug disimpan sebagai JSON utuh
                $query->orWhere('slug->id', $pageSlug)
                      ->orWhere('slug->en', $pageSlug);

                // Skenario C: Jika slug disimpan sebagai teks murni (bukan JSON)
                $query->orWhere('slug', $pageSlug);

                // Skenario D: Jurus pamungkas menggunakan LIKE
                $query->orWhere('slug', 'LIKE', '%"'.$pageSlug.'"%');
            })->first();
        } elseif ($pageSlug instanceof \App\Models\Page) {
            $pageModel = $pageSlug; // Berjaga-jaga jika dipanggil via object binding
        }

        // 3. POPULASI DATA KE FORMULIR JIKA DITEMUKAN
        if ($pageModel && $pageModel->exists) {
            $this->isEditMode = true;
            $this->page = $pageModel;
            $this->status = $pageModel->status ?? 'draft';

            // 🌟 BYPASS MUTATOR: Ambil data mentah persis seperti hasil dd()
            $modelData = $pageModel->toArray();

            // Ekstrak data (Pasti berbentuk array jika di DB berupa JSON dan sudah di-cast)
            $titleData = $modelData['title'] ?? [];
            $slugData = $modelData['slug'] ?? [];
            $metaTitleData = $modelData['meta_title'] ?? [];
            $metaDescData = $modelData['meta_description'] ?? [];

            // Pertahanan ekstra jika ternyata masih ada yang berbentuk string JSON
            $titleData = is_string($titleData) ? json_decode($titleData, true) ?? [] : (is_array($titleData) ? $titleData : []);
            $slugData = is_string($slugData) ? json_decode($slugData, true) ?? [] : (is_array($slugData) ? $slugData : []);
            $metaTitleData = is_string($metaTitleData) ? json_decode($metaTitleData, true) ?? [] : (is_array($metaTitleData) ? $metaTitleData : []);
            $metaDescData = is_string($metaDescData) ? json_decode($metaDescData, true) ?? [] : (is_array($metaDescData) ? $metaDescData : []);

            // Petakan per bahasa
            foreach ($this->activeLocales as $loc) {
                // ✅ Pastikan menggunakan page_title
                $this->page_title[$loc] = $titleData[$loc] ?? '';
                $this->slug[$loc] = $slugData[$loc] ?? '';
                $this->meta_title[$loc] = $metaTitleData[$loc] ?? '';
                $this->meta_description[$loc] = $metaDescData[$loc] ?? '';
            }

            // 4. PENYELAMATAN STRUKTUR BLOK (Dari Seeder ke Livewire)
            // $rawContent = $modelData['content'] ?? [];
            // $rawContent = is_string($rawContent) ? json_decode($rawContent, true) ?? [] : (is_array($rawContent) ? $rawContent : []);

            // // Jika konten terbungkus kunci bahasa dari Seeder
            // if (isset($rawContent['id']) && is_array($rawContent['id']) && isset($rawContent['id'][0]['type'])) {
            //     $rawContent = $rawContent['id'];
            // } elseif (isset($rawContent['en']) && is_array($rawContent['en']) && isset($rawContent['en'][0]['type'])) {
            //     $rawContent = $rawContent['en'];
            // }

            // // Petakan ke Editor Grid TipTap
            // $this->content = [];
            // $this->blockOrder = [];
            // foreach ($rawContent as $block) {
            //     if (is_array($block) && isset($block['type'])) {
            //         $id = $block['id'] ?? 'blk_' . Str::random(8);
            //         $block['id'] = $id;
            //         $this->content[$id] = $block;
            //         $this->blockOrder[] = $id;
            //     }
            // }
            // 4. PENYELAMATAN STRUKTUR BLOK (Dari Seeder & Database ke Livewire)
            $rawContent = $modelData['content'] ?? [];
            $rawContent = is_string($rawContent) ? json_decode($rawContent, true) ?? [] : (is_array($rawContent) ? $rawContent : []);

            $this->content = [];
            $this->blockOrder = [];

            // 🌟 1. DETEKSI FORMAT BARU (Flat Data Structure)
            if (isset($rawContent['blocks']) && isset($rawContent['order'])) {
                $this->content = $rawContent['blocks'];
                $this->blockOrder = $rawContent['order'];
            } 
            // 🌟 2. FALLBACK KE FORMAT LAMA (Untuk kompabilitas dengan Seeder lawas)
            else {
                if (isset($rawContent['id']) && is_array($rawContent['id']) && isset($rawContent['id'][0]['type'])) {
                    $rawContent = $rawContent['id'];
                } elseif (isset($rawContent['en']) && is_array($rawContent['en']) && isset($rawContent['en'][0]['type'])) {
                    $rawContent = $rawContent['en'];
                }

                foreach ($rawContent as $block) {
                    if (is_array($block) && isset($block['type'])) {
                        $id = $block['id'] ?? 'blk_' . Str::random(8);
                        $block['id'] = $id;
                        $this->content[$id] = $block;
                        $this->blockOrder[] = $id;
                    }
                }
            }

        } else {
            // 5. HALAMAN BARU (Jika URL benar-benar tidak ditemukan)
            $this->isEditMode = false;
            $this->page = new \App\Models\Page();
            $this->status = 'offline';
            // ✅ Gunakan page_title
            $this->page_title = array_fill_keys($this->activeLocales, '');
            $this->slug = array_fill_keys($this->activeLocales, '');
            $this->meta_title = array_fill_keys($this->activeLocales, '');
            $this->meta_description = array_fill_keys($this->activeLocales, '');
        }

        // 6. BUAT BLOK DEFAULT JIKA EDITOR KOSONG TOTAL
        if (empty($this->content)) {
            $id = 'blk_' . uniqid();
            $this->content[$id] = [
                'id' => $id,
                'type' => 'heading',
                'data' => ['text' => array_fill_keys($this->activeLocales, '')]
            ];
            $this->blockOrder = [$id];
        }
    }


    public function save($isPreview = false)
    {

        $this->validate();

        // $finalContent = [];
        // foreach ($this->blockOrder as $id) {
        //     if (isset($this->content[$id])) {
        //         $finalContent[] = $this->content[$id];
        //     }
        // }

        $this->page->title            = $this->page_title;
        $this->page->slug             = $this->slug;

        $this->page->content          = [
            'blocks' => $this->content,       // Berisi SELURUH blok (induk & anak) dengan key ID (blk_...)
            'order'  => $this->blockOrder     // Berisi HANYA urutan ID blok level terluar (root)
        ];

        // $this->page->content          = $finalContent;
        $this->page->meta_title       = $this->meta_title;
        $this->page->meta_description = $this->meta_description;
        $this->page->status           = $this->status;

        $this->page->save();

        if (!$this->isEditMode && !$isPreview) {

            // 🌟 PENGAMBIL SLUG SUPER KETAT
            $redirectSlug = null;
            if (is_array($this->slug) && !empty($this->slug)) {
                $locale = app()->getLocale();
                // Ambil dari bahasa aktif, jika tidak ada, paksa ambil elemen pertama apapun bahasanya
                $redirectSlug = $this->slug[$locale] ?? reset($this->slug);
            }

            if (empty($redirectSlug)) {
                $redirectSlug = $this->page->id;
            }
            // Dapatkan URL edit yang baru berdasarkan slug/id yang baru disimpan
            $editUrl = route('page.edit', ['pageSlug' => $redirectSlug]);

            // 🌟 UBAH URL BROWSER TANPA REDIRECT (ZERO BLINK)
            // Ini akan mengganti /pages/create menjadi /pages/slug-baru/edit di address bar
            $this->js("window.history.replaceState(null, '', '{$editUrl}');");

            $this->isEditMode = true;
        }
        // $this->notifyFlash(__('ui.notification.page_saved'), 'success');
        $this->notify(__('ui.notification.page_saved'), 'success');
       // ... kode lainnya ...
        // if (empty($redirectSlug)) {
        //     $redirectSlug = $this->page->id;
        // }

        // // 🌟 Gunakan key 'pageSlug' di sini
        // if (!$isPreview) {
        //     return redirect()->route('page.edit', ['pageSlug' => $redirectSlug]);
        // }
    }
    public function saveAndPreview()
    {
        // 1. Panggil save dengan parameter TRUE
        // (Ini akan menyimpan DB dan memunculkan notifikasi, TAPI halamannya tidak akan ter-refresh)
        $this->save(true);

        // 2. Dapatkan Slug untuk URL
        $slugCantik = $this->page->id;
        if (is_array($this->slug) && !empty($this->slug['id'])) {
            $slugCantik = $this->slug['id'];
        } elseif (is_string($this->slug) && !empty($this->slug)) {
            $slugCantik = $this->slug;
        }

        // 3. Bangun URL Pratinjau
        $previewUrl = route('page.preview', ['pageSlug' => $slugCantik]);

        // 4. Picu event ke Alpine.js
        $this->dispatch('open-preview-panel', url: $previewUrl);
    }
};
?>

<x-slot:title>{{ __('ui.header.write_page') }}</x-slot:title>

<div class="h-[calc(100vh-4rem)] flex flex-col overflow-x-hidden bg-gray-50 p-6 box-border"
    x-data='pageEditor(
        @json($activeLocales),
        @json(array_slice($activeLocales, 0, 2)),
        {{ count($activeLocales) }},
        $wire
    )'
    @block-added.window="
        let newId = $event.detail.id;
        // Tunggu render DOM selesai, lalu gulirkan halaman secara halus
        $nextTick(() => {
            let el = document.getElementById('block-wrapper-' + newId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    "                   >

    <!-- HEADER & TOMBOL SIMPAN -->
    <div class="flex items-center justify-between pb-4 border-b border-gray-200 shrink-0 mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Editor Halaman Multibahasa</h1>

        <!-- KELOMPOK TOMBOL AKSI DI HEADER -->
        <div class="flex items-center gap-3">

            <!-- 🌟 Tombol Simpan & Pratinjau (Buka Tab Baru) -->
            <button wire:click="saveAndPreview"
                    wire:loading.attr="disabled"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 bg-white border border-foresty text-foresty hover:bg-foresty hover:text-white rounded-lg text-sm font-bold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-foresty focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed">

                <!-- Ikon Loading (Muncul saat proses save berjalan) -->
                <svg wire:loading wire:target="saveAndPreview" class="animate-spin -ml-1 mr-1 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>

                <!-- Ikon Mata (Hilang saat loading) -->
                <svg wire:loading.remove wire:target="saveAndPreview" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>

                Pratinjau
            </button>

            {{-- <!-- Tombol Simpan Biasa (Yang sudah Anda miliki) -->
            <button wire:click="save"
                    wire:loading.attr="disabled"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 bg-foresty text-white hover:bg-[#043b2c] rounded-lg text-sm font-bold shadow-md transition-all duration-200">
                Simpan
            </button> --}}
            <button wire:click="save" class="px-5 py-2.5 bg-foresty hover:bg-forest text-white font-semibold rounded-lg shadow-sm transition">
                Simpan Perubahan
            </button>
        </div>
    </div>

    <!-- PESAN GALAT VALIDASI -->
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg shrink-0">
            <p class="font-bold mb-1">Gagal menyimpan, periksa isian berikut:</p>
            <ul class="list-disc pl-5 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- TOOLBAR KONTROL TATA LETAK & BAHASA -->
    <div class="flex flex-wrap items-center justify-between bg-white p-3 rounded-xl border border-gray-200 shadow-sm shrink-0 mb-6 gap-4">

        <!-- Pilihan Mode Layout -->
        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg">
            <button
                type="button"
                @click="layoutMode = 'single'"
                :class="layoutMode === 'single' ? 'bg-sage-soft text-foersty shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900'"
                class="px-3 py-1.5 text-xs rounded-md transition cursor-pointer select-none"
            >
                Single View
            </button>
            <button
                type="button"
                @click="layoutMode = 'split'"
                :class="layoutMode === 'split' ? 'bg-sage-soft text-foersty shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900'"
                class="px-3 py-1.5 text-xs rounded-md transition cursor-pointer select-none"
            >
                Split View
            </button>
        </div>

        <!-- KONTROL TAMBAHAN BERDASARKAN MODE -->
        <div class="flex items-center gap-3">
            <!-- Mode Single -->
            <div x-show="layoutMode === 'single'" class="flex items-center gap-2">
                <span class="text-xs font-semibold text-gray-500">Tampilkan Bahasa:</span>
                <select x-model="singleActiveLang" class="border-gray-300 rounded-lg text-xs font-medium py-1">
                    @foreach($activeLocales as $code)
                        <option value="{{ $code }}">{{ strtoupper($code) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Mode Split -->
            <div x-show="layoutMode === 'split'" class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-semibold text-gray-500">Kolom Split:</span>
                <template x-for="activeLang in splitLanguages" :key="activeLang">
                    <div class="flex items-center gap-1 bg-blue-50 border border-blue-200 px-2 py-1 rounded-md">
                        <span class="text-xs font-bold text-foresty uppercase" x-text="activeLang"></span>
                        <button type="button" @click="removeSplitLang(activeLang)" x-show="splitLanguages.length > 1" class="text-red-500 hover:text-red-700 text-xs font-bold px-1">×</button>
                    </div>
                </template>

                <select @change="addSplitLang($event.target.value); $event.target.value = '';" x-show="splitLanguages.length < ((window.innerWidth > 1440 && allLocalesCount >= 3) ? 3 : 2)" class="border-dashed border-gray-300 rounded-lg text-xs text-gray-500 py-1 bg-gray-50">
                    <option value="">+ Tambah Kolom</option>
                    @foreach($activeLocales as $code)
                        <option value="{{ $code }}">{{ strtoupper($code) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- AREA KONTEN UTAMA -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-2 pr-2 space-y-8 mb-8">

        <!-- BAGIAN METADATA -->
        <div :class="{
                'grid grid-cols-1': layoutMode === 'single',
                'grid grid-cols-1 md:grid-cols-2': layoutMode === 'split' && splitLanguages.length === 2,
                'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3': layoutMode === 'split' && splitLanguages.length === 3,
                'grid grid-cols-1': layoutMode === 'split' && splitLanguages.length === 1
             }"
             class="gap-6">
            @foreach($activeLocales as $code)
                <div x-show="(layoutMode === 'single' && singleActiveLang === '{{ $code }}') || (layoutMode === 'split' && splitLanguages.includes('{{ $code }}'))"
                     class="p-5 bg-white border border-gray-200 rounded-xl shadow-sm space-y-4">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-bold text-gray-700 text-sm">Metadata ({{ strtoupper($code) }})</h3>
                        <span class="px-2 py-0.5 bg-blue-100 text-foresty text-[10px] font-bold rounded">{{ strtoupper($code) }}</span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Judul Halaman <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="page_title.{{ $code }}"
                            placeholder="Contoh: Layanan Kesehatan Ibu dan Anak"
                            class="w-full text-md p-2 border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Slug URL</label>
                            <input type="text" wire:model="slug.{{ $code }}"
                            placeholder="Contoh: layanan-kesehatan-ibu-dan-anak"
                            class="w-full text-md p-2 bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Judul Meta</label>
                            <input type="text" wire:model="meta_title.{{ $code }}"
                            placeholder="Contoh: Layanan Kesehatan Ibu & Anak Terpadu | YSBH"
                            class="w-full text-md p-2 bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi Meta</label>
                            <textarea row="6" wire:model="meta_description.{{ $code }}"
                            {{-- placeholder="Tulis ringkasan singkat (maks. 160 karakter) untuk hasil mesin pencari ..."  --}}
                            placeholder="{{ $code === 'id' ? 'Tulis ringkasan menarik untuk hasil pencarian Google (maks. 160 karakter)...' : 'Write a brief summary for Google search results (max. 160 characters)...' }}"
                            class="w-full text-md p-2 bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500 min-h-36 resize-none"></textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- KONTROL STATUS KONTEN -->
        <div class="flex items-center justify-between pt-2">
            <h2 class="text-xl font-bold text-gray-800">Konten Halaman</h2>
            <select wire:model="status" class="border-gray-300 rounded-md shadow-sm text-sm font-medium">
                <option value="offline">Offline</option>
                <option value="online">Online</option>
            </select>
        </div>

        <!-- ALPINE SORTABLE CONTAINER -->
        <div
            x-sort="handleSort"
            x-sort:config="{
                animation: 200,
                handle: '.drag-handle',
                ghostClass: 'opacity-50',
                dragClass: 'shadow-2xl'
            }"
            class="flex flex-col gap-6"  >
            {{-- 🌟 1. LOOP MENGGUNAKAN $blockOrder AGAR URUTAN TETAP UTUH --}}
            @foreach($blockOrder as $blockId)
                @php $block = $content[$blockId] ?? null; @endphp
                @if($block)
                    <div
                        wire:key="block-{{ $blockId }}"
                        x-sort:item="'{{ $blockId }}'"
                        class="group relative bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:border-forest transition-colors"
                    >
                        <!-- Drag Handle -->
                        <div class="absolute top-1/2 -left-4 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                            <button type="button" class="drag-handle cursor-grab active:cursor-grabbing p-2 bg-white border border-gray-200 shadow-md rounded-md text-gray-400 hover:text-gray-700" title="Geser Blok">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"></path></svg>
                            </button>
                        </div>

                        <!-- Tombol Aksi Hover: Gandakan & Hapus -->
                        <div class="absolute -top-3 -right-3 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                            <button wire:click="duplicateBlock('{{ $blockId }}')" type="button" class="p-1.5 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 border border-gray-200 shadow-sm" title="Gandakan Blok">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                            </button>
                            <button wire:click="removeBlock('{{ $blockId }}')" type="button" class="p-1.5 bg-red-100 text-red-600 rounded-full hover:bg-red-200 border border-gray-200 shadow-sm" title="Hapus Blok">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <!-- RENDER ISI BLOK -->
                        <div class="w-full">
                            <div :class="{
                                    'grid grid-cols-1': layoutMode === 'single',
                                    'grid grid-cols-1 md:grid-cols-2': layoutMode === 'split' && splitLanguages.length === 2,
                                    'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3': layoutMode === 'split' && splitLanguages.length === 3,
                                    'grid grid-cols-1': layoutMode === 'split' && splitLanguages.length === 1
                                }"
                                class="gap-6">
                                @foreach($activeLocales as $code)
                                    <div x-show="(layoutMode === 'single' && singleActiveLang === '{{ $code }}') || (layoutMode === 'split' && splitLanguages.includes('{{ $code }}'))"
                                        class="space-y-3">

                                        {{-- <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase" x-text="layoutMode === 'split' ? 'Bahasa: ' + '{{ strtoupper($code) }}' : ''"></span>
                                            <span x-show="layoutMode === 'single'" class="px-2 py-0.5 bg-gray-100 text-gray-600 text-[10px] font-bold uppercase rounded">Bahasa: {{ strtoupper($code) }}</span>
                                        </div> --}}

                                        {{-- 🌟 2. WIRE:MODEL DIKUNCI MENGGUNAKAN $blockId (MUSTAHIL TERTUKAR/ACAK) --}}
                                        {{-- @if($block['type'] === 'heading')
                                            <input id="block-wrapper-{{ $blockId }}" type="text" wire:model="content.{{ $blockId }}.data.text.{{ $code }}" placeholder="Judul H2..." class="w-full text-lg font-bold border-0 border-b-2 border-transparent hover:border-gray-200 focus:border-blue-500 focus:ring-0 p-0 text-gray-800 bg-transparent"> --}}
                                        <x-dynamic-component
                                            :component="'blocks.editor.' . str_replace('_', '-', $block['type'])"
                                            :block-id="$blockId"
                                            :code="$code"
                                            :block="$block"
                                            :all-content="$content" {{-- 🌟 TAMBAHKAN INI --}}
                                        />


                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

    </div> <!-- Akhir Area Scroll Konten -->


    <!-- AREA TOMBOL TAMBAH BLOK BERDASARKAN KATEGORI -->
    <div class="shrink-0 pt-4 border-t border-gray-200 bg-white -mx-6 -mb-6 p-4 px-6 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)] flex flex-wrap items-center justify-center gap-6 z-20">

        <!-- KELOMPOK MIKRO (KONTEN UTAMA) -->
        <div class="flex items-center gap-2 border-r pr-6 border-gray-200">
            <span class="text-[10px] font-bold text-gray-400 uppercase">Konten:</span>
            <x-buttons.add-blocks mode="icon-hover" command="addNewBlock('heading')" icon="heading-1" label="Judul" />
            <x-buttons.add-blocks mode="icon-hover" command="addNewBlock('paragraph')" icon="align-left" label="Paragraf" />
            <x-buttons.add-blocks mode="icon-hover" command="addNewBlock('image')" icon="image" label="Gambar" />
        </div>

        <!-- KELOMPOK MAKRO (TATA LETAK & SEKSI) -->
        <div class="flex items-center gap-2 border-r pr-6 border-gray-200">
            <span class="text-[10px] font-bold text-gray-400 uppercase">Seksi Layout:</span>
            <x-buttons.add-blocks mode="icon-hover" command="addNewBlock('columns')" icon="columns" label="2 Kolom" />
            <x-buttons.add-blocks mode="icon-hover" command="addNewBlock('stats_grid')" icon="layout-grid" label="Grid Info" />
            <x-buttons.add-blocks mode="icon-hover" command="addNewBlock('dynamic_testimonials')" icon="message-square-quote" label="Testimoni" />
            <x-buttons.add-blocks mode="icon-hover" command="addNewBlock('hero_banner')" icon="image" label="Hero Banner" />
            <x-buttons.add-blocks mode="icon-hover" command="addNewBlock('image')" icon="image-plus" label="Image" />
        </div>

        <!-- KELOMPOK TEMPLATE (JIKA ADA) -->
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold text-gray-400 uppercase">Template:</span>
            {{-- Contoh tombol yang memuat sekumpulan blok sekaligus --}}
            <button type="button" wire:click="loadTemplate('landing_page_standard')" class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-bold transition">
                ✨ Muat Template Standar
            </button>
        </div>

    </div>

    <!-- MODAL PENCARIAN LINK INTERNAL -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4"
        x-data="{ open: false, searchQuery: '', selectedText: '' }"
        @buka-modal-link.window="open = true; selectedText = $event.detail.text"
        x-show="open"
        x-cloak>
        <div @click.outside="open = false" class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-lg p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Cari Halaman Internal</h3>
            <div>
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Ketik judul halaman yang dicari..."
                    class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                >
            </div>
            <div class="min-h-[150px] max-h-60 overflow-y-auto border border-gray-100 rounded-lg p-2 text-sm text-gray-500">
                <p class="text-center py-6">Ketik untuk mulai mencari halaman...[cite: 1]</p>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="open = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- 🌟 PANEL PRATINJAU SLIDE-OVER (Meluncur dari Kanan) -->
    <div x-data="{
            previewOpen: false,
            previewUrl: '',
            deviceMode: 'desktop', // Pilihan: 'desktop' atau 'mobile'
        }"
         @open-preview-panel.window="previewUrl = $event.detail.url; previewOpen = true;"
         x-cloak
         class="relative z-[100]"
         aria-labelledby="slide-over-title"
         role="dialog"
         aria-modal="true">

        <div x-show="previewOpen" class="fixed inset-0 overflow-hidden" style="display: none;">
            <!-- Latar Belakang Gelap (Klik untuk menutup) -->
            <div x-show="previewOpen"
                 x-transition.opacity.duration.300ms
                 @click="previewOpen = false; previewUrl = ''"
                 class="absolute inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"></div>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                <!-- Panel Utama -->
                <div x-show="previewOpen"
                     x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="pointer-events-auto w-screen max-w-screen flex flex-col bg-gray-100 shadow-2xl">
                    <!-- max-w-7xl -->

                    <!-- HEADER PANEL -->
                    <div class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-200">
                        <div class="flex items-center gap-6">
                            <h2 class="text-lg font-extrabold text-foresty" id="slide-over-title">Live Preview</h2>

                            <!-- 🌟 TOMBOL TOGGLE MOBILE / DESKTOP -->
                            <div class="flex bg-gray-100 p-1 rounded-lg border border-gray-200 shadow-inner hidden md:flex">
                                <button @click="deviceMode = 'desktop'" :class="deviceMode === 'desktop' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'" class="flex items-center gap-2 px-3 py-1.5 text-xs font-bold rounded-md transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    Desktop
                                </button>
                                <button @click="deviceMode = 'mobile'" :class="deviceMode === 'mobile' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'" class="flex items-center gap-2 px-3 py-1.5 text-xs font-bold rounded-md transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    Mobile
                                </button>
                            </div>
                        </div>

                        <!-- Tombol Tutup -->
                        <button @click="previewOpen = false; previewUrl = ''" class="rounded-full p-2 bg-gray-50 text-gray-400 hover:text-red-600 hover:bg-red-50 focus:outline-none transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- AREA KONTEN (IFRAME) -->
                    <div class="flex-1 overflow-y-auto bg-gray-200 flex justify-center items-start pt-6 pb-12 transition-all duration-500">
                        <!-- Wrapper Iframe (Lebarnya menyesuaikan pilihan device) -->
                        <div class="transition-all duration-500 ease-in-out shadow-2xl overflow-hidden bg-white"
                             :class="deviceMode === 'desktop' ? 'w-full h-full mx-6 rounded-xl border border-gray-300' : 'w-[375px] h-[812px] rounded-[2.5rem] border-[12px] border-gray-800'">

                            <!-- Iframe Halaman Publik -->
                            <template x-if="previewUrl !== ''">
                                <iframe :src="previewUrl" class="w-full h-full border-0 bg-white"></iframe>
                            </template>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>
