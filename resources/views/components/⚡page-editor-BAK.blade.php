<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Livewire\Traits\WithNotifications;

use App\Models\Page;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use Spatie\Activitylog\Models\Activity;

// #[Translatable('title', 'slug', 'content', 'meta_title', 'meta_description')]
// #[Fillable('title', 'slug', 'content', 'status', 'meta_title','meta_description', 'published_at', 'created_at', 'updated_at')]

new class extends Component
{
    use WithFileUploads;
    use WithNotifications;

    public ?Page $page = null;

    public array $activeLocales = [];

    public $layoutMode = 'split';          
    public $singleActiveLang = 'id';       
    public $splitLanguages = [];

    public array $title =[];
    public array $slug = [];
    public array $meta_title = [];
    public array $meta_description = [];

    public array $content = [];
    public  $status;

    /**
     * 1. VALIDATION RULES (DINAMIS)
     */
    protected function rules()
    {
        // Validasi dasar yang tidak terpengaruh bahasa
        $rules = [
            'status'  => 'required|in:draft,published',
            'content' => 'array',
        ];

        // Looping untuk mendaftarkan rule ke setiap bahasa yang aktif
        foreach ($this->activeLocales as $locale) {
            $rules["title.{$locale}"] = 'required|string|max:255';
            $rules["slug.{$locale}"]  = 'required|string|max:255';
        }

        return $rules;
    }

    /**
     * 2. CUSTOM ERROR MESSAGES (DINAMIS)
     */
    protected function messages()
    {
        // Pesan dasar
        $messages = [
            'status.required' => 'Status halaman wajib dipilih.',
            'status.in'       => 'Status tidak valid.',
        ];

        // Looping untuk mendaftarkan pesan error spesifik per bahasa
        foreach ($this->activeLocales as $locale) {
            // Mengubah 'id' jadi 'ID', 'en' jadi 'EN' untuk tampilan pesan
            $lang = strtoupper($locale); 
            
            $messages["title.{$locale}.required"] = "Judul ({$lang}) wajib diisi.";
            $messages["title.{$locale}.max"]      = "Judul ({$lang}) maksimal 255 karakter.";
            
            $messages["slug.{$locale}.required"]  = "Slug/URL ({$lang}) wajib diisi.";
            $messages["slug.{$locale}.max"]       = "Slug/URL ({$lang}) maksimal 255 karakter.";
        }

        return $messages;
    }

    public function mount($page = null)
    {
        // 1. Inisialisasi daftar bahasa aktif & split screen
        $this->activeLocales = config('app.supported_locales', ['id', 'en']);
        $this->splitLanguages = array_slice($this->activeLocales, 0, 2);
        
        if (!in_array($this->singleActiveLang, $this->activeLocales)) {
            $this->singleActiveLang = $this->activeLocales[0] ?? 'id';
        }

        // 2. Deteksi otomatis: Apakah ini Edit atau Create?
        if ($page && $page->exists) {
            $this->page = $page;
            $this->status = $page->status ?? 'draft';
            
            // Muat data metadata dan konten dari database (pastikan di model sudah dicasting sebagai array)
            $this->title = $page->title ?? array_fill_keys($this->activeLocales, '');
            $this->slug = $page->slug ?? array_fill_keys($this->activeLocales, '');
            $this->content = $page->content ?? [];
        } else {
            $this->page = new \App\Models\Page(); // Sesuaikan dengan namespace Model Anda
            $this->status = 'draft';
            $this->title = array_fill_keys($this->activeLocales, '');
            $this->slug = array_fill_keys($this->activeLocales, '');
            $this->content = [];
        }

        // 3. Fallback: Jika konten kosong, berikan satu blok default (heading) agar editor tidak kosong melompong
        if (empty($this->content)) {
            $this->content = [
                [
                    'id' => 'blk_' . uniqid(),
                    'type' => 'heading',
                    'data' => [
                        'text' => array_fill_keys($this->activeLocales, '')
                    ]
                ]
            ];
        }
    }

    private function prepareTranslation($existingData)
    {
        $data = is_array($existingData) ? $existingData : [];
        foreach ($this->activeLocales as $locale) {
            if (!array_key_exists($locale, $data)) {
                $data[$locale] = '';
            }
        }
        return $data;
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

    

    /**
     * ==========================================
     * 4. MANAJEMEN BLOK (Diarahkan ke $content)
     * ==========================================
     */
    public function addBlock($type, $insertAtIndex = null)
    {
        $newBlock = [
            'id' => 'blk_' . Str::random(8),
            'type' => $type,
            'data' => $this->getDefaultDataForType($type)
        ];

        if ($insertAtIndex !== null) {
            array_splice($this->content, $insertAtIndex, 0, [$newBlock]);
        } else {
            $this->content[] = $newBlock;
        }
    }

    public function removeBlock($blockId)
    {
        $this->content = collect($this->content)->reject(function ($block) use ($blockId) {
            return $block['id'] === $blockId;
        })->values()->toArray();
    }

    public function duplicateBlock($id)
    {
        foreach ($this->content as $index => $block) {
            if ($block['id'] === $id) {
                // Salin data blok
                $duplicatedBlock = $block;
                
                // 🌟 KUNCI UTAMA: Wajib buat ID baru yang unik agar tidak bentrok dengan aslinya!
                $duplicatedBlock['id'] = 'blk_' . uniqid(); 
                
                // Sisipkan blok salinan tepat di bawah blok aslinya
                array_splice($this->content, $index + 1, 0, [$duplicatedBlock]);
                break;
            }
        }
    }
    // public function duplicateBlock($blockId)
    // {
    //     $index = collect($this->content)->search(fn($b) => $b['id'] === $blockId);
        
    //     if ($index !== false) {
    //         $blockToCopy = $this->content[$index];
    //         $blockToCopy['id'] = 'blk_' . Str::random(8); 
    //         array_splice($this->content, $index + 1, 0, [$blockToCopy]);
    //     }
    // }

    public function updateBlockOrder($orderedIds = [])
    {
        // Jika data terkirim sebagai string JSON, decode
        if (is_string($orderedIds)) {
            $orderedIds = json_decode($orderedIds, true) ?? [];
        }

        if (!is_array($orderedIds) || empty($orderedIds)) {
            return;
        }

        $indexedBlocks = [];
        foreach ($this->content as $block) {
            $indexedBlocks[(string) $block['id']] = $block;
        }

        $newContent = [];
        foreach ($orderedIds as $id) {
            $idStr = (string) $id;
            if (isset($indexedBlocks[$idStr])) {
                $newContent[] = $indexedBlocks[$idStr];
                unset($indexedBlocks[$idStr]);
            }
        }

        // Tangkap sisa blok jika ada
        foreach ($indexedBlocks as $block) {
            $newContent[] = $block;
        }

        $this->content = $newContent;
    }
    private function getDefaultDataForType($type)
    {
        $emptyLocales = [];
        foreach ($this->activeLocales as $locale) {
            $emptyLocales[$locale] = '';
        }

        return match($type) {
            'heading'    => ['text' => $emptyLocales, 'level' => 'h2'],
            'paragraph'  => ['text' => $emptyLocales],
            'image'      => ['url' => ''],
            'media_text' => ['image_url' => '', 'image_position' => 'left', 'text' => $emptyLocales],
            // 🌟 TAMBAHAN BLOK KOLOM (2 Kolom Teks Berdampingan)
            'columns'    => [
                'col_left'  => $emptyLocales,
                'col_right' => $emptyLocales,
            ],
            default => []
        };
    }

    /**
     * 5. SIMPAN KE DATABASE
     */
    public function save()
    {
        // Jalankan validasi berdasarkan rules()
        $this->validate();

        $this->page->title            = $this->title;
        $this->page->slug             = $this->slug;
        $this->page->content          = $this->content; 
        $this->page->meta_title       = $this->meta_title;
        $this->page->meta_description = $this->meta_description;
        $this->page->status           = $this->status;

        $this->page->save();

        //public function notifyFlash(string $message, string $type = 'info') | $type Jenis: 'info', 'success', 'warning', 'error'
        $this->notifyFlash(__('ui.notification.page_saved'), 'success',);
        
        return redirect()->route('pages.edit', $this->page->id);
    }
};
?>

{{-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi --}}

<x-slot:title>{{ __('ui.header.write_page') }}</x-slot:title>

<div class="h-[calc(100vh-4rem)] flex flex-col overflow-x-hidden bg-gray-50 p-6 box-border">
    
    <!-- HEADER & TOMBOL SIMPAN -->
    <div class="flex items-center justify-between pb-4 border-b border-gray-200 shrink-0 mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Editor Halaman Multibahasa</h1>
        <button wire:click="save" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition">
            Simpan Perubahan
        </button>
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

    <!-- TOOLBAR KONTROL TATA LETAK & BAHASA ($activeLocales) -->
    <div class="flex flex-wrap items-center justify-between bg-white p-3 rounded-xl border border-gray-200 shadow-sm shrink-0 mb-6 gap-4">
        
        <!-- Pilihan Mode Layout -->
        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg">
            <button 
                type="button" 
                wire:click="$set('layoutMode', 'split')"
                :class="$wire.layoutMode === 'split' ? 'bg-white text-blue-600 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900'"
                class="px-3 py-1.5 text-xs rounded-md transition"
            >
                Split Screen
            </button>
            <button 
                type="button" 
                wire:click="$set('layoutMode', 'single')"
                :class="$wire.layoutMode === 'single' ? 'bg-white text-blue-600 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900'"
                class="px-3 py-1.5 text-xs rounded-md transition"
            >
                Tab / Single View
            </button>
        </div>

        <!-- KONTROL TAMBAHAN BERDASARKAN MODE -->
        <div class="flex items-center gap-3">
            @if($layoutMode === 'single')
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">Tampilkan Bahasa:</span>
                    <select wire:model.live="singleActiveLang" class="border-gray-300 rounded-lg text-xs font-medium py-1">
                        @foreach($activeLocales as $code)
                            <option value="{{ $code }}">{{ strtoupper($code) }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">Kolom Split:</span>
                    @foreach($splitLanguages as $index => $activeLang)
                        <div class="flex items-center gap-1 bg-blue-50 border border-blue-200 px-2 py-1 rounded-md">
                            <select wire:model.live="splitLanguages.{{ $index }}" class="bg-transparent border-0 text-xs font-bold text-blue-700 p-0 focus:ring-0 cursor-pointer">
                                @foreach($activeLocales as $code)
                                    <option value="{{ $code }}">{{ strtoupper($code) }}</option>
                                @endforeach
                            </select>
                            @if(count($splitLanguages) > 1)
                                <button type="button" wire:click="removeSplitLanguage('{{ $activeLang }}')" class="text-red-500 hover:text-red-700 text-xs font-bold px-1">×</button>
                            @endif
                        </div>
                    @endforeach

                    @if(count($splitLanguages) < 3)
                        <select wire:change="addSplitLanguage($event.target.value); $event.target.value = '';" class="border-dashed border-gray-300 rounded-lg text-xs text-gray-500 py-1 bg-gray-50">
                            <option value="">+ Tambah Kolom</option>
                            @foreach($activeLocales as $code)
                                @if(!in_array($code, $splitLanguages))
                                    <option value="{{ $code }}">{{ strtoupper($code) }}</option>
                                @endif
                            @endforeach
                        </select>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- AREA KONTEN UTAMA (Bisa Di-scroll Secara Mandiri) -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-2 pr-2 space-y-8">
        
        <!-- BAGIAN METADATA -->
        @if($layoutMode === 'single')
            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-bold text-gray-700">Metadata ({{ strtoupper($singleActiveLang) }})</h3>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Halaman <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title.{{ $singleActiveLang }}" class="w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug URL</label>
                        <input type="text" wire:model="slug.{{ $singleActiveLang }}" class="w-full bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500">
                    </div>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-{{ count($splitLanguages) }} gap-6">
                @foreach($splitLanguages as $lang)
                    <div class="p-5 bg-white border border-gray-200 rounded-xl shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="font-bold text-gray-700 text-sm">{{ strtoupper($lang) }}</h3>
                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded">{{ strtoupper($lang) }}</span>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Judul</label>
                                <input type="text" wire:model="title.{{ $lang }}" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Slug</label>
                                <input type="text" wire:model="slug.{{ $lang }}" class="w-full text-xs bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- KONTROL STATUS KONTEN -->
        <div class="flex items-center justify-between pt-2">
            <h2 class="text-xl font-bold text-gray-800">Konten Halaman</h2>
            <select wire:model="status" class="border-gray-300 rounded-md shadow-sm text-sm font-medium">
                <option value="draft">Status: Draft</option>
                <option value="published">Status: Published</option>
            </select>
        </div>

        <!-- ALPINE SORTABLE CONTAINER DENGAN CLIENT-SIDE REORDERING (ANTI SNAP-BACK) -->
        <div 
            x-data="{
                handleSort(itemIds) {
                    let ids = Array.isArray(itemIds) ? itemIds : Array.from(itemIds);
                    let map = new Map($wire.content.map(block => [String(block.id).trim(), block]));
                    let reordered = ids.map(id => map.get(String(id).trim())).filter(Boolean);
                    
                    if (reordered.length > 0) {
                        $wire.content = reordered;
                        $wire.updateBlockOrder(ids);
                    }
                }
            }"
            x-sort="handleSort"
            x-sort:config="{
                animation: 200,
                handle: '.drag-handle',
                ghostClass: 'opacity-50',
                dragClass: 'shadow-2xl'
            }"
            class="flex flex-col gap-6"
        >
            @foreach($content as $index => $block)
                <div 
                    wire:key="block-{{ $block['id'] }}" 
                    x-sort:item="'{{ $block['id'] }}'"
                    class="group relative bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:border-blue-300 transition-colors"
                >
                    <!-- Drag Handle -->
                    <div class="absolute top-1/2 -left-4 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                        <button type="button" class="drag-handle cursor-grab active:cursor-grabbing p-2 bg-white border border-gray-200 shadow-md rounded-md text-gray-400 hover:text-gray-700" title="Geser Blok">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"></path></svg>
                        </button>
                    </div>

                    <!-- Tombol Aksi Hover: Gandakan & Hapus -->
                    <div class="absolute -top-3 -right-3 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                        <button wire:click="duplicateBlock('{{ $block['id'] }}')" type="button" class="p-1.5 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 border border-gray-200 shadow-sm" title="Gandakan Blok">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                        </button>
                        <button wire:click="removeBlock('{{ $block['id'] }}')" type="button" class="p-1.5 bg-red-100 text-red-600 rounded-full hover:bg-red-200 border border-gray-200 shadow-sm" title="Hapus Blok">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- RENDER ISI BLOK BERDASARKAN MODE LAYOUT -->
                    <div class="w-full">
                        @if($layoutMode === 'single')
                            <div class="space-y-4">
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-[10px] font-bold uppercase rounded">Bahasa: {{ $singleActiveLang }}</span>
                                
                                @if($block['type'] === 'heading')
                                    <input type="text" wire:model="content.{{ $index }}.data.text.{{ $singleActiveLang }}" placeholder="Judul H2..." class="w-full text-xl font-bold border-0 border-b-2 border-transparent hover:border-gray-200 focus:border-blue-500 focus:ring-0 p-0 text-gray-800 bg-transparent">
                                @elseif($block['type'] === 'paragraph')
                                    <x-tiptap wire:model="content.{{ $index }}.data.text.{{ $singleActiveLang }}" />
                                @elseif($block['type'] === 'columns')
                                    <div class="grid grid-cols-2 gap-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                                        <div>
                                            <span class="text-[10px] text-gray-400 font-semibold mb-1 block">KOLOM KIRI</span>
                                            <x-tiptap wire:model="content.{{ $index }}.data.col_left.{{ $singleActiveLang }}" />
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-gray-400 font-semibold mb-1 block">KOLOM KANAN</span>
                                            <x-tiptap wire:model="content.{{ $index }}.data.col_right.{{ $singleActiveLang }}" />
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-{{ count($splitLanguages) }} gap-6">
                                @foreach($splitLanguages as $lang)
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase">Bahasa: {{ strtoupper($lang) }}</span>
                                        </div>

                                        @if($block['type'] === 'heading')
                                            <input type="text" wire:model="content.{{ $index }}.data.text.{{ $lang }}" placeholder="Judul H2..." class="w-full text-lg font-bold border-0 border-b-2 border-transparent hover:border-gray-200 focus:border-blue-500 focus:ring-0 p-0 text-gray-800 bg-transparent">
                                        @elseif($block['type'] === 'paragraph')
                                            <x-tiptap wire:model="content.{{ $index }}.data.text.{{ $lang }}" />
                                        @elseif($block['type'] === 'columns')
                                            <div class="space-y-3 bg-gray-50 p-3 rounded-xl border border-gray-200">
                                                <div>
                                                    <span class="text-[10px] text-gray-400 font-semibold">KOLOM KIRI</span>
                                                    <x-tiptap wire:model="content.{{ $index }}.data.col_left.{{ $lang }}" />
                                                </div>
                                                <div>
                                                    <span class="text-[10px] text-gray-400 font-semibold">KOLOM KANAN</span>
                                                    <x-tiptap wire:model="content.{{ $index }}.data.col_right.{{ $lang }}" />
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

    </div> <!-- Akhir Area Scroll Konten -->

    <!-- TOMBOL TAMBAH BLOK PERMANEN DI BAWAH (Sticky Footer) -->
    <div class="shrink-0 pt-4 border-t border-gray-200 bg-white -mx-6 -mb-6 p-4 px-6 shadow-lg flex flex-wrap justify-center gap-3 z-20">
        <button wire:click="addBlock('heading')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm transition">
            + Tambah Judul
        </button>
        <button wire:click="addBlock('paragraph')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm transition">
            + Tambah Paragraf
        </button>
        <button wire:click="addBlock('columns')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm transition">
            + Tambah 2 Kolom
        </button>
        <button wire:click="addBlock('image')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm">
            + Gambar Tengah
        </button>
        
    </div>

    <!-- MODAL PENCARIAN LINK INTERNAL (Global Listener di luar kontainer utama) -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4" 
        x-data="{ open: false, searchQuery: '', selectedText: '' }"
        @buka-modal-link.window="open = true; selectedText = $event.detail.text"
        x-show="open" 
        x-cloak  >
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
                <p class="text-center py-6">Ketik untuk mulai mencari halaman...</p>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="open = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

{{-- <div class="h-[calc(100vh-4rem)] flex flex-col overflow-hidden bg-gray-50 p-6">
    <!-- HEADER & TOMBOL SIMPAN -->
    <div class="flex items-center justify-between mb-8 pb-4 border-b border-gray-200">
        <h1 class="text-2xl font-bold text-gray-800">Editor Halaman Multibahasa</h1>
        <button wire:click="save" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition">
            Simpan Perubahan
        </button>
    </div>

    <div class="h-[calc(100vh-4rem)] flex flex-col overflow-x-hidden overflow-y-auto bg-gray-50 p-6 box-border">
    
        <!-- PESAN GALAT VALIDASI -->
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg">
                <p class="font-bold mb-1">Gagal menyimpan, periksa isian berikut:</p>
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    
        <!-- BAGIAN 1: METADATA -->
        <div class="grid grid-cols-2 gap-8 mb-10">
            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-bold text-gray-700">Bahasa Indonesia</h3>
                    <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">ID</span>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Halaman <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title.id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug URL <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="slug.id" class="w-full bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500">
                    </div>
                </div>
            </div>
    
            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-bold text-gray-700">English</h3>
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded">EN</span>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Page Title <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title.en" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Slug <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="slug.en" class="w-full bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500">
                    </div>
                </div>
            </div>
        </div>
    
        <!-- BAGIAN 2: EDITOR BLOK KONTEN -->
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-800">Konten Halaman</h2>
            <select wire:model="status" class="border-gray-300 rounded-md shadow-sm text-sm font-medium">
                <option value="draft">Status: Draft</option>
                <option value="published">Status: Published</option>
            </select>
        </div>
    
        <!-- ALPINE SORTABLE CONTAINER DENGAN CLIENT-SIDE REORDERING (ANTI SNAP-BACK) -->
        <div class="flex flex-col gap-6"
            x-data="{
                handleSort(itemIds) {
                    // Pastikan itemIds berbentuk array yang valid
                    let ids = Array.isArray(itemIds) ? itemIds : Array.from(itemIds);
                    
                    // Buat peta pencarian berdasarkan ID string yang bersih dari spasi
                    let map = new Map($wire.content.map(block => [String(block.id).trim(), block]));
                    
                    // Susun ulang array berdasarkan urutan baru hasil drag
                    let reordered = ids.map(id => map.get(String(id).trim())).filter(Boolean);
                    
                    // Sinkronisasi lokal instan (mencegah snap-back) & kirim ke database
                    if (reordered.length > 0) {
                        $wire.content = reordered;
                        $wire.updateBlockOrder(ids);
                    }
                }
            }"
            
            x-sort:config="{
                animation: 200,
                handle: '.drag-handle',
                ghostClass: 'opacity-50',
                dragClass: 'shadow-2xl'
            }"
            x-sort="handleSort">
        
            @foreach($content as $index => $block)
                <div 
                    wire:key="block-{{ $block['id'] }}" 
                    x-sort:item="'{{ $block['id'] }}'"
                    class="group relative bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:border-blue-300 transition-colors"
                >
                    <!-- Drag Handle -->
                    <div class="absolute top-1/2 -left-4 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button type="button" class="drag-handle cursor-grab active:cursor-grabbing p-2 bg-white border border-gray-200 shadow-md rounded-md text-gray-400 hover:text-gray-700" title="Geser Blok">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8"></path></svg>
                        </button>
                    </div>
    
                    <!-- 🌟 TOMBOL AKSI HOVER: GANDAKAN & HAPUS (KEMBALI UTUH) -->
                    <div class="absolute -top-3 -right-3 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                        <button wire:click="duplicateBlock('{{ $block['id'] }}')" type="button" class="p-1.5 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 border border-gray-200 shadow-sm" title="Gandakan Blok">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                        </button>
                        <button wire:click="removeBlock('{{ $block['id'] }}')" type="button" class="p-1.5 bg-red-100 text-red-600 rounded-full hover:bg-red-200 border border-red-200 shadow-sm" title="Hapus Blok">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
    
                    <!-- RENDER ISI BLOK -->
                    <div class="w-full">
                        @if($block['type'] === 'heading')
                            <div class="grid grid-cols-2 gap-8">
                                <input type="text" wire:model="content.{{ $index }}.data.text.id" placeholder="Judul H2 (Indonesia)..." class="w-full text-xl font-bold border-0 border-b-2 border-transparent hover:border-gray-200 focus:border-blue-500 focus:ring-0 p-0 text-gray-800 bg-transparent">
                                <input type="text" wire:model="content.{{ $index }}.data.text.en" placeholder="H2 Heading (English)..." class="w-full text-xl font-bold border-0 border-b-2 border-transparent hover:border-gray-200 focus:border-blue-500 focus:ring-0 p-0 text-gray-800 bg-transparent">
                            </div>
                        
                        @elseif($block['type'] === 'paragraph')
                            <div class="grid grid-cols-2 gap-8">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Paragraf (ID)</label>
                                    <x-tiptap wire:model="content.{{ $index }}.data.text.id" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Paragraph (EN)</label>
                                    <x-tiptap wire:model="content.{{ $index }}.data.text.en" />
                                </div>
                            </div>
    
                        @elseif($block['type'] === 'columns')
                            <div class="space-y-3">
                                <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Blok 2 Kolom Sejajar</span>
                                <div class="grid grid-cols-2 gap-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Kolom Kiri (ID & EN)</label>
                                        <div class="space-y-4">
                                            <div>
                                                <span class="text-[10px] text-gray-400 font-semibold">INDONESIA</span>
                                                <x-tiptap wire:model="content.{{ $index }}.data.col_left.id" />
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-gray-400 font-semibold">ENGLISH</span>
                                                <x-tiptap wire:model="content.{{ $index }}.data.col_left.en" />
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Kolom Kanan (ID & EN)</label>
                                        <div class="space-y-4">
                                            <div>
                                                <span class="text-[10px] text-gray-400 font-semibold">INDONESIA</span>
                                                <x-tiptap wire:model="content.{{ $index }}.data.col_right.id" />
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-gray-400 font-semibold">ENGLISH</span>
                                                <x-tiptap wire:model="content.{{ $index }}.data.col_right.en" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        
                        @elseif($block['type'] === 'image')
                            <div class="w-full h-40 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 flex flex-col items-center justify-center text-gray-400">
                                <span class="text-sm">Upload Gambar Lebar Penuh (Belum Aktif)</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    
    </div><!-- OVERFLOW -->

    

    <!-- TOMBOL TAMBAH BLOK -->
    <div class="mt-8 pt-6 border-t border-gray-200 flex flex-wrap justify-center gap-3">
        <button wire:click="addBlock('heading')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm">
            + Tambah Judul
        </button>
        <button wire:click="addBlock('paragraph')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm">
            + Tambah Paragraf
        </button>
        <button wire:click="addBlock('columns')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm">
            + Tambah 2 Kolom
        </button>
        <button wire:click="addBlock('image')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm">
            + Gambar Tengah
        </button>
    </div>

    <!-- MODAL PENCARIAN LINK INTERNAL (Global Listener) -->
        <div 
            x-data="{ open: false, searchQuery: '', selectedText: '' }"
            @buka-modal-link.window="open = true; selectedText = $event.detail.text"
            x-show="open" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4"
        >
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
                    <p class="text-center py-6">Ketik untuk mulai mencari halaman...</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="open = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

</div> --}}
