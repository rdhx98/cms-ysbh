<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Livewire\Traits\WithNotifications;
use App\Models\Page;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

new class extends Component
{
    use WithFileUploads;
    use WithNotifications;

    public ?Page $page = null;

    public array $activeLocales = [];

    public $layoutMode = 'single';          
    public $singleActiveLang = 'id';       
    public $splitLanguages = [];

    public array $title = [];
    public array $slug = [];
    public array $meta_title = [];
    public array $meta_description = [];

    // 🌟 Pendekatan Hibrida: Pisahkan data konten dan urutan
    public array $content = [];
    public array $blockOrder = []; // Menyimpan urutan ID secara akurat
    
    public $status;

    protected function rules()
    {
        $rules = [
            'status'  => 'required|in:draft,published',
            'content' => 'array',
        ];

        foreach ($this->activeLocales as $locale) {
            $rules["title.{$locale}"] = 'required|string|max:255';
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
            $messages["title.{$locale}.required"] = "Judul ({$lang}) wajib diisi.";
            $messages["title.{$locale}.max"]      = "Judul ({$lang}) maksimal 255 karakter.";
            $messages["slug.{$locale}.required"]  = "Slug/URL ({$lang}) wajib diisi.";
            $messages["slug.{$locale}.max"]       = "Slug/URL ({$lang}) maksimal 255 karakter.";
        }

        return $messages;
    }

    public function mount($page = null)
    {
        $this->activeLocales = config('app.supported_locales', ['id', 'en']);
        $this->splitLanguages = array_slice($this->activeLocales, 0, 2);
        
        if (!in_array($this->singleActiveLang, $this->activeLocales)) {
            $this->singleActiveLang = $this->activeLocales[0] ?? 'id';
        }

        if ($page && $page->exists) {
            $this->page = $page;
            $this->status = $page->status ?? 'draft';
            $this->title = $page->title ?? array_fill_keys($this->activeLocales, '');
            $this->slug = $page->slug ?? array_fill_keys($this->activeLocales, '');
            
            // Petakan konten database menjadi array asosiatif & urutan
            $rawContent = $page->content ?? [];
            foreach ($rawContent as $block) {
                $id = $block['id'] ?? 'blk_' . Str::random(8);
                $block['id'] = $id;
                $this->content[$id] = $block;
                $this->blockOrder[] = $id;
            }
        } else {
            $this->page = new \App\Models\Page();
            $this->status = 'draft';
            $this->title = array_fill_keys($this->activeLocales, '');
            $this->slug = array_fill_keys($this->activeLocales, '');
        }

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

    public function addBlockWithOrder($type, $orderedIds = [])
    {
        if (!empty($orderedIds)) {
            $this->updateBlockOrder($orderedIds);
        }
        $this->addBlock($type);
    }

    public function addBlock($type)
    {
        $id = 'blk_' . Str::random(8);
        $this->content[$id] = [
            'id' => $id,
            'type' => $type,
            'data' => $this->getDefaultDataForType($type)
        ];
        
        $this->blockOrder[] = $id; // Letakkan di paling bawah
    }

    public function removeBlock($blockId)
    {
        unset($this->content[$blockId]);
        $this->blockOrder = array_values(array_filter($this->blockOrder, fn($id) => $id !== $blockId));
    }

    public function duplicateBlock($id)
    {
        if (isset($this->content[$id])) {
            $newId = 'blk_' . uniqid();
            $duplicatedBlock = $this->content[$id];
            $duplicatedBlock['id'] = $newId;
            
            // Simpan konten kloningannya
            $this->content[$newId] = $duplicatedBlock;
            
            // Sisipkan ke urutan tepat di bawah blok aslinya
            $index = array_search($id, $this->blockOrder);
            if ($index !== false) {
                array_splice($this->blockOrder, $index + 1, 0, [$newId]);
            } else {
                $this->blockOrder[] = $newId;
            }
        }
    }

    public function updateBlockOrder($orderedIds = [])
    {
        if (is_string($orderedIds)) {
            $orderedIds = json_decode($orderedIds, true) ?? [];
        }

        if (is_array($orderedIds) && !empty($orderedIds)) {
            // Ambil ID yang valid saja dari hasil drag-and-drop
            $validIds = array_filter($orderedIds, fn($id) => isset($this->content[$id]));
            
            // Amankan sisa blok jika ada yang luput
            $missingIds = array_diff(array_keys($this->content), $validIds);
            
            $this->blockOrder = array_values(array_merge($validIds, $missingIds));
        }
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
            'columns'    => [
                'col_left'  => $emptyLocales,
                'col_right' => $emptyLocales,
            ],
            default => []
        };
    }

    public function save()
    {
        $this->validate();

        // 🌟 Susun ulang menjadi Array utuh untuk disimpan ke database JSON
        $finalContent = [];
        foreach ($this->blockOrder as $id) {
            if (isset($this->content[$id])) {
                $finalContent[] = $this->content[$id];
            }
        }

        $this->page->title            = $this->title;
        $this->page->slug             = $this->slug;
        $this->page->content          = $finalContent; 
        $this->page->meta_title       = $this->meta_title;
        $this->page->meta_description = $this->meta_description;
        $this->page->status           = $this->status;

        $this->page->save();

        $this->notifyFlash(__('ui.notification.page_saved'), 'success');
        
        return redirect()->route('pages.edit', $this->page->id);
    }
};
?>

<x-slot:title>{{ __('ui.header.write_page') }}</x-slot:title>

<div 
    x-data='pageEditor(
        @json($activeLocales), 
        @json(array_slice($activeLocales, 0, 2)), 
        {{ count($activeLocales) }},
        $wire
    )'
    class="h-[calc(100vh-4rem)] flex flex-col overflow-x-hidden bg-gray-50 p-6 box-border"
>
    
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

    <!-- TOOLBAR KONTROL TATA LETAK & BAHASA -->
    <div class="flex flex-wrap items-center justify-between bg-white p-3 rounded-xl border border-gray-200 shadow-sm shrink-0 mb-6 gap-4">
        
        <!-- Pilihan Mode Layout -->
        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg">
            <button 
                type="button" 
                @click="layoutMode = 'split'"
                :class="layoutMode === 'split' ? 'bg-white text-blue-600 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900'"
                class="px-3 py-1.5 text-xs rounded-md transition"
            >
                Split Screen
            </button>
            <button 
                type="button" 
                @click="layoutMode = 'single'"
                :class="layoutMode === 'single' ? 'bg-white text-blue-600 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900'"
                class="px-3 py-1.5 text-xs rounded-md transition"
            >
                Tab / Single View
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
                        <span class="text-xs font-bold text-blue-700 uppercase" x-text="activeLang"></span>
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
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-2 pr-2 space-y-8">
        
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
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded">{{ strtoupper($code) }}</span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Judul Halaman <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="title.{{ $code }}" class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Slug URL</label>
                            <input type="text" wire:model="slug.{{ $code }}" class="w-full text-xs bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- KONTROL STATUS KONTEN -->
        <div class="flex items-center justify-between pt-2">
            <h2 class="text-xl font-bold text-gray-800">Konten Halaman</h2>
            <select wire:model="status" class="border-gray-300 rounded-md shadow-sm text-sm font-medium">
                <option value="draft">Status: Draft</option>
                <option value="published">Status: Published</option>
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
            class="flex flex-col gap-6"
        >
            {{-- 🌟 1. LOOP MENGGUNAKAN $blockOrder AGAR URUTAN TETAP UTUH --}}
            @foreach($blockOrder as $blockId)
                @php $block = $content[$blockId] ?? null; @endphp
                @if($block)
                    <div 
                        wire:key="block-{{ $blockId }}" 
                        x-sort:item="'{{ $blockId }}'"
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
                                        
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase" x-text="layoutMode === 'split' ? 'Bahasa: ' + '{{ strtoupper($code) }}' : ''"></span>
                                            <span x-show="layoutMode === 'single'" class="px-2 py-0.5 bg-gray-100 text-gray-600 text-[10px] font-bold uppercase rounded">Bahasa: {{ strtoupper($code) }}</span>
                                        </div>

                                        {{-- 🌟 2. WIRE:MODEL DIKUNCI MENGGUNAKAN $blockId (MUSTAHIL TERTUKAR/ACAK) --}}
                                        @if($block['type'] === 'heading')
                                            <input type="text" wire:model="content.{{ $blockId }}.data.text.{{ $code }}" placeholder="Judul H2..." class="w-full text-lg font-bold border-0 border-b-2 border-transparent hover:border-gray-200 focus:border-blue-500 focus:ring-0 p-0 text-gray-800 bg-transparent">
                                        @elseif($block['type'] === 'paragraph')
                                            <x-tiptap wire:model="content.{{ $blockId }}.data.text.{{ $code }}" />
                                        @elseif($block['type'] === 'columns')
                                            <div class="space-y-3 bg-gray-50 p-3 rounded-xl border border-gray-200">
                                                <div>
                                                    <span class="text-[10px] text-gray-400 font-semibold mb-1 block">KOLOM KIRI</span>
                                                    <x-tiptap wire:model="content.{{ $blockId }}.data.col_left.{{ $code }}" />
                                                </div>
                                                <div>
                                                    <span class="text-[10px] text-gray-400 font-semibold mb-1 block">KOLOM KANAN</span>
                                                    <x-tiptap wire:model="content.{{ $blockId }}.data.col_right.{{ $code }}" />
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

    </div> <!-- Akhir Area Scroll Konten -->

    <!-- TOMBOL TAMBAH BLOK PERMANEN DI BAWAH (Sticky Footer) -->
    <div class="shrink-0 pt-4 border-t border-gray-200 bg-white -mx-6 -mb-6 p-4 px-6 shadow-lg flex flex-wrap justify-center gap-3 z-20">
        <button @click="addNewBlock('heading')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm transition">
            + Tambah Judul
        </button>
        <button @click="addNewBlock('paragraph')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm transition">
            + Tambah Paragraf
        </button>
        <button @click="addNewBlock('columns')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm transition">
            + Tambah 2 Kolom
        </button>
        <button @click="addNewBlock('image')" type="button" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium shadow-sm">
            + Gambar Tengah
        </button>
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
</div>