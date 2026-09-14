<?php

use Livewire\Component;
use Livewire\WithFileUploads;

use App\Models\Page;

use Illuminate\Support\Str;

use Spatie\Activitylog\Models\Activity;

use App\Livewire\Traits\WithNotifications;
use App\Livewire\Traits\HasContentBlocks;

new class extends Component {
    use WithFileUploads;
    use WithNotifications;
    use HasContentBlocks;

    public ?Page $page = null;

    public array $activeLocales = [];

    public $layoutMode = 'single'; //single split
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
            'status' => 'required|in:offline,online',
            'content' => 'array',
        ];

        foreach ($this->activeLocales as $locale) {
            // UBAH VALIDASI MENJADI page_title
            $rules["page_title.{$locale}"] = 'required|string|max:255';
            $rules["slug.{$locale}"] = 'required|string|max:255';
        }

        return $rules;
    }

    protected function messages()
    {
        $messages = [
            'status.required' => 'Status halaman wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ];

        foreach ($this->activeLocales as $locale) {
            $lang = strtoupper($locale);
            // UBAH PESAN GALAT MENJADI page_title
            $messages["page_title.{$locale}.required"] = "Judul ({$lang}) wajib diisi.";
            $messages["page_title.{$locale}.max"] = "Judul ({$lang}) maksimal 255 karakter.";
            $messages["slug.{$locale}.required"] = "Slug/URL ({$lang}) wajib diisi.";
            $messages["slug.{$locale}.max"] = "Slug/URL ({$lang}) maksimal 255 karakter.";
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
            $pageModel = \App\Models\Page::where(function ($query) use ($pageSlug) {
                // Skenario A: Jika URL berupa ID angka
                if (is_numeric($pageSlug)) {
                    $query->where('id', $pageSlug);
                }

                // Skenario B: Jika slug disimpan sebagai JSON utuh
                $query->orWhere('slug->id', $pageSlug)->orWhere('slug->en', $pageSlug);

                // Skenario C: Jika slug disimpan sebagai teks murni (bukan JSON)
                $query->orWhere('slug', $pageSlug);

                // Skenario D: Jurus pamungkas menggunakan LIKE
                $query->orWhere('slug', 'LIKE', '%"' . $pageSlug . '"%');
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
                'data' => ['text' => array_fill_keys($this->activeLocales, '')],
            ];
            $this->blockOrder = [$id];
        }
    }

    public function save($isPreview = false)
    {
        $this->validate();

        $this->page->title = $this->page_title;
        $this->page->slug = $this->slug;

        $this->page->content = [
            'blocks' => $this->content, // Berisi SELURUH blok (induk & anak) dengan key ID (blk_...)
            'order' => $this->blockOrder, // Berisi HANYA urutan ID blok level terluar (root)
        ];

        // $this->page->content          = $finalContent;
        $this->page->meta_title = $this->meta_title;
        $this->page->meta_description = $this->meta_description;
        $this->page->status = $this->status;

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
    }

    public function saveAndPreview()
    {
        $this->save(true);

        $slugCantik = $this->page->id;
        if (is_array($this->slug) && !empty($this->slug['id'])) {
            $slugCantik = $this->slug['id'];
        } elseif (is_string($this->slug) && !empty($this->slug)) {
            $slugCantik = $this->slug;
        }

        // 🌟 PERBAIKAN: Tambahkan parameter mode => 'raw'
        $previewUrl = route('page.preview', [
            'pageSlug' => $slugCantik,
            'mode' => 'raw', // 'full' 'raw'
            'lang' => app()->getLocale(),
        ]);

        $this->dispatch('open-preview-panel', url: $previewUrl);
    }
};
?>

<x-slot:title>{{ __('ui.header.write_page') }}</x-slot:title>

<div class="h-[calc(100vh-4rem)] flex flex-col overflow-x-hidden bg-linear-to-b from-white via-gray-50 to-gray-100 rounded-md p-2 box-border"
  x-data='pageEditor(
        @json($activeLocales),
        @json(array_slice($activeLocales, 0, 2)),
        {{ count($activeLocales) }},
        $wire,
    )'
  @block-added.window="
    let newId = $event.detail.id;
    // Beri jeda 100ms agar Livewire & pengunci scroll selesai merapikan DOM
    setTimeout(() => {
      let el = document.getElementById('block-wrapper-' + newId);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }, 100);">
  <!-- 🌟 HEADER UTAMA (DITELEPORTASI KE NAVBAR) -->
  <template x-if="windowWidth >= 1366">
    <template x-teleport="#editor-toolbar-portal">
      <x-editor.display-control :active-locales="$activeLocales" />
    </template>
  </template>

  <template x-if="windowWidth < 1366">
    {{-- <div class="mb-2 p-2 bg-white border border-gray-200 rounded-xl shadow-sm overflow-x-auto"> --}}
    <div class="p-3 sm:p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
      <!-- Passing variabel yang sama ke sini juga -->
      <x-editor.display-control :active-locales="$activeLocales" />
    </div>
  </template>

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

  <!-- AREA KONTEN UTAMA -->
  <div id="main-editor-scroll-area" class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden pt-6 pb-24 px-4 space-y-8 scrollbar-gutter-stable" x-data="{
      scrollPos: 0,
      init() {
          Livewire.hook('commit', ({ succeed }) => {
              // 1. Catat posisi sebelum update
              this.scrollPos = this.$el.scrollTop;

              succeed(() => {
                  // 2. Selalu paksa kembali ke posisi semula (mencegah lemparan ke atas)
                  requestAnimationFrame(() => {
                      this.$el.scrollTop = this.scrollPos;
                  });
              });
          });
      }
  }">

    {{-- <div class="p-4 bg-red-100 text-red-700 font-bold mb-4">
      Mode Saat Ini: <span x-text="effectiveLayout"></span> <br>\
      <span x-text="'Aktif: ' + singleActiveLang"></span>
    </div> --}}

    <!-- ==========================================
    RUANGAN 1: METADATA (Hanya Tampil di Tab Meta)
    ========================================== -->
    <div x-show="editorTab === 'meta'" x-cloak class="space-y-8">
      <div class="flex items-center justify-between pt-2">
        <h2 class="text-xl font-bold text-gray-800">Metadata Halaman</h2>
        <select wire:model="status" class="border-gray-300 rounded-md shadow-sm text-sm font-medium">
          <option value="offline">Offline</option>
          <option value="online">Online</option>
        </select>
      </div>
      <div
        :class="{
            'grid grid-cols-1': effectiveLayout === 'single',
            'grid grid-cols-1 md:grid-cols-2': effectiveLayout === 'split' && splitLanguages.length === 2,
            'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3': effectiveLayout === 'split' && splitLanguages
                .length === 3,
            'grid grid-cols-1': effectiveLayout === 'split' && splitLanguages.length === 1
        }"
        class="gap-6">

        @foreach ($activeLocales as $code)
          <div {{-- x-show="(layoutMode === 'single' && singleActiveLang === '{{ $code }}') || (layoutMode === 'split' && splitLanguages.includes('{{ $code }}'))" --}} x-show="(effectiveLayout === 'single' && singleActiveLang === '{{ $code }}') || (effectiveLayout === 'split' && splitLanguages.includes('{{ $code }}'))"
            class="p-5 bg-white border border-gray-200 rounded-xl shadow-sm space-y-4">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="font-bold text-gray-700 text-sm">Metadata ({{ strtoupper($code) }})</h3>
              <span class="px-2 py-0.5 bg-blue-100 text-foresty text-[10px] font-bold rounded">{{ strtoupper($code) }}</span>
            </div>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Judul Halaman <span class="text-red-500">*</span></label>
                <input type="text" wire:model="page_title.{{ $code }}" placeholder="Contoh: Layanan Kesehatan Ibu dan Anak" class="w-full text-md p-2 border-gray-300 rounded-md shadow-sm">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Slug URL</label>
                <input type="text" wire:model="slug.{{ $code }}" placeholder="Contoh: layanan-kesehatan-ibu-dan-anak" class="w-full text-md p-2 bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Judul Meta</label>
                <input type="text" wire:model="meta_title.{{ $code }}" placeholder="Contoh: Layanan Kesehatan Ibu & Anak Terpadu | YSBH"
                  class="w-full text-md p-2 bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi Meta</label>
                <textarea row="6" wire:model="meta_description.{{ $code }}"
                  placeholder="{{ $code === 'id' ? 'Tulis ringkasan menarik untuk hasil pencarian Google (maks. 160 karakter)...' : 'Write a brief summary for Google search results (max. 160 characters)...' }}"
                  class="w-full text-md p-2 bg-gray-50 border-gray-300 rounded-md shadow-sm text-gray-500 min-h-36 resize-none"></textarea>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <!-- ==========================================
    RUANGAN 2: EDITOR KONTEN (Hanya Tampil di Tab Konten)
    ========================================== -->
    <div x-show="editorTab === 'content'" x-cloak class="space-y-8">
      <!-- KONTROL STATUS KONTEN -->
      <div class="flex items-center justify-between pt-2">
        <h2 class="text-xl font-bold text-gray-800">Konten Halaman</h2>
      </div>

      <!-- ALPINE SORTABLE CONTAINER -->
      <div x-sort="handleSort" class="flex flex-col gap-6"
        x-sort:config="{
            animation: 200,
            handle: '.drag-handle',
            ghostClass: 'opacity-50',
            dragClass: 'shadow-2xl'
        }">

        @foreach ($blockOrder as $blockId)
          @php $block = $content[$blockId] ?? null; @endphp
          @if ($block)
            <!-- PERBAIKAN 1: Bungkusan Utama -->
            <!-- Hapus bg-white, border, dan rounded dari sini. Sisakan hanya 'group' dan 'relative' -->
            <div id="block-wrapper-{{ $blockId }}"
            wire:key="block-{{ $blockId }}" x-sort:item="'{{ $blockId }}'" class="group relative w-full" x-data="{ showAnchorSetting: false }">
              <!-- 1. DRAG HANDLE (Selalu Tampil di Atas-Kiri saat < 1366px, Hover di Luar-Kiri saat PC) -->
              <div class="absolute transition-opacity z-20" :class="windowWidth < 1366 ? '-top-5 left-2 opacity-100' : 'top-4 -left-4 opacity-0 group-hover:opacity-100'">
                <button type="button" class="drag-handle bg-white shadow-sm" title="Geser Blok"></button>
              </div>

              <div class="absolute -top-5 flex gap-2 transition-all duration-200 z-30"
                   :class="(windowWidth < 1366 || showAnchorSetting) ? 'right-2 opacity-100 visible' : '-right-3 opacity-0 invisible group-hover:opacity-100 group-hover:visible'">

                {{-- 🌟 TOMBOL PENGATURAN BLOK (ANCHOR) --}}
                {{-- 🌟 PERBAIKAN 2: Hapus x-data="{ openSettings: false }" karena kita pakai showAnchorSetting dari parent --}}
                <div @click.outside="showAnchorSetting = false" class="relative">

                  {{-- 🌟 PERBAIKAN 3: Tombol menyala jika panel sedang aktif --}}
                  <button @click="showAnchorSetting = !showAnchorSetting" type="button"
                    class="p-1.5 text-gray-600 rounded-full border border-gray-200 shadow-sm transition-colors"
                    :class="showAnchorSetting ? 'bg-foresty text-white hover:bg-forest' : 'bg-white hover:bg-gray-50'"
                    title="Pengaturan Blok">
                    <x-dynamic-component component="lucide-settings-2" class="w-4 h-4" />
                  </button>

                  {{-- Popover Pengaturan --}}
                  <div x-show="showAnchorSetting" x-cloak style="display: none;" class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-xl shadow-xl p-4 z-50">
                    <label class="block text-xs font-bold text-foresty uppercase mb-1">ID Tautan (Anchor)</label>
                    <p class="text-[10px] text-gray-500 mb-2 leading-tight">Melompat ke blok ini (Contoh: <span class="font-mono text-coral">tentang-kami</span>).</p>

                    <input type="text" wire:model.live.debounce.500ms="content.{{ $blockId }}.anchor"
                      @input="$event.target.value = $event.target.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')" placeholder="nama-anchor"
                      class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-foresty focus:border-foresty">
                  </div>
                </div>

                {{-- Tombol Duplikat --}}
                <button wire:click="duplicateBlock('{{ $blockId }}')" type="button" class="p-1.5 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 border border-gray-200 shadow-sm" title="Gandakan Blok">
                  <x-dynamic-component component="lucide-copy" class="w-4 h-4" />
                </button>

                {{-- Tombol Hapus --}}
                <button wire:click="removeBlock('{{ $blockId }}')" type="button" class="p-1.5 bg-red-100 text-red-600 rounded-full hover:bg-red-200 border border-gray-200 shadow-sm" title="Hapus Blok">
                  <x-dynamic-component component="lucide-trash-2" class="w-4 h-4" />
                </button>

              </div>

              <!-- RENDER ISI BLOK -->
              <div class="w-full">
                <div class="gap-6"
                  :class="{
                      'grid grid-cols-1': effectiveLayout === 'single',
                      'grid grid-cols-1 md:grid-cols-2': effectiveLayout === 'split' && splitLanguages.length === 2,
                      'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3': effectiveLayout === 'split' &&
                          splitLanguages
                          .length === 3,
                      'grid grid-cols-1': effectiveLayout === 'split' && splitLanguages.length === 1
                  }">
                  @foreach ($activeLocales as $code)
                    <!-- PERBAIKAN 2: Bungkusan Bahasa (Child) -->
                    <!-- Pindahkan kelas desain ke sini. Gunakan border-transparent agar layout tidak bergeser saat di-hover -->
                    <div x-show="(effectiveLayout === 'single' && singleActiveLang === '{{ $code }}') || (effectiveLayout === 'split' && splitLanguages.includes('{{ $code }}'))"
                      class="space-y-3 bg-gray-100 group-hover:bg-white rounded-xl border-2 border-transparent group-hover:border-foresty/80 transition-colors">

                      <x-dynamic-component :component="'blocks.editor.' . str_replace('_', '-', $block['type'])" :block-id="$blockId" :code="$code" :block="$block" :all-content="$content" />
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          @endif
        @endforeach
      </div>
    </div>
  </div> <!-- AREA KONTEN UTAMA -->


  <!-- AREA TOMBOL TAMBAH BLOK BERDASARKAN KATEGORI -->
  <div class="shrink-0 border-t border-gray-200 bg-white -mx-2 -mb-2 p-2  shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)] flex flex-wrap items-center justify-center gap-6 z-20">

    <!-- KELOMPOK MIKRO (KONTEN UTAMA) -->
    <div class="flex items-center gap-2 border-r pr-6 border-gray-200">
      <span class="text-[10px] font-bold text-gray-400 uppercase">Konten:</span>
      <x-buttons.add-blocks mode="icon-hover" command="editorTab = 'content'; addNewBlock('heading')" icon="heading-1" label="Judul" />
      <x-buttons.add-blocks mode="icon-hover" command="editorTab = 'content'; addNewBlock('paragraph')" icon="align-left" label="Paragraf" />
      <x-buttons.add-blocks mode="icon-hover" command="editorTab = 'content'; addNewBlock('eyebrow')" icon="crosshair" label="Eyebrow" />
      <x-buttons.add-blocks mode="icon-hover" command="editorTab = 'content'; addNewBlock('image')" icon="image-plus" label="Gambar" />
      <x-buttons.add-blocks mode="icon-hover" command="editorTab = 'content'; addNewBlock('button-group')" icon="plus-square" label="Grup Tombol" />
      <x-buttons.add-blocks mode="icon-hover" command="editorTab = 'content'; addNewBlock('badge-group')" icon="badge-plus" label="Grup Lencana" />
    </div>

    <!-- KELOMPOK MAKRO (TATA LETAK & SEKSI) -->
    <div class="flex items-center gap-2 border-r pr-6 border-gray-200">
      <span class="text-[10px] font-bold text-gray-400 uppercase">Seksi Layout:</span>
      <x-buttons.add-blocks mode="icon-hover" command="editorTab = 'content'; addNewBlock('multi-columns')" icon="columns-4" label="Kolom" />
      {{-- <x-buttons.add-blocks mode="icon-hover" command="editorTab = 'content'; addNewBlock('columns')" icon="columns" label="2 Kolom" /> --}}
      <x-buttons.add-blocks mode="icon-hover" command="editorTab = 'content'; addNewBlock('section-divider')" icon="between-horizontal-start" label="Section Divider" />
    </div>

    <!-- KELOMPOK TEMPLATE (JIKA ADA) -->
    {{-- <div class="flex items-center gap-2">
      <span class="text-[10px] font-bold text-gray-400 uppercase">Template:</span>
      {{-- Contoh tombol yang memuat sekumpulan blok sekaligus --}
      <button type="button" wire:click="loadTemplate('landing_page_standard')" class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-bold transition">
        ✨ Muat Template Standar
      </button>
    </div> --}}

  </div>

  <!-- MODAL PENCARIAN LINK INTERNAL OLD AF -->
  {{-- <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4" x-data="{ open: false, searchQuery: '', selectedText: '' }" @buka-modal-link.window="open = true; selectedText = $event.detail.text" x-show="open"
    x-cloak>
    <div @click.outside="open = false" class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-lg p-6 space-y-4">
      <h3 class="text-lg font-bold text-gray-800">Cari Halaman Internal</h3>
      <div>
        <input type="text" x-model="searchQuery" placeholder="Ketik judul halaman yang dicari..." class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
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
  </div> --}}

  <!-- 🌟 MODAL PENCARIAN TAUTAN SUPER (Anchor & Halaman Internal) -->
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4" x-cloak x-data="{
      open: false,
      searchQuery: '',
      selectedText: '',
      targetWireModel: null, // Menyimpan alamat input Livewire jika dipanggil dari Lencana/Tombol

      // Fungsi untuk mengindeks semua Anchor yang ada di halaman ini secara real-time
      get inPageAnchors() {
          let anchors = [];
          let contentData = $wire.get('content') || {};
          for (let key in contentData) {
              if (contentData[key].anchor && contentData[key].anchor.trim() !== '') {
                  anchors.push({ id: contentData[key].anchor, type: contentData[key].type });
              }
          }
          return anchors;
      },

      // Fungsi pamungkas untuk mengirim URL ke pemanggilnya
      applyUrl(url) {
          if (this.targetWireModel) {
              // Jika dipanggil dari komponen Livewire (seperti Badge/Button)
              $wire.set(this.targetWireModel, url);
          } else {
              // Jika dipanggil dari Tiptap
              window.dispatchEvent(new CustomEvent('insert-link-to-active-editor', {
                  detail: { url: url, text: this.selectedText }
              }));
          }
          this.open = false;
          this.searchQuery = '';
          this.targetWireModel = null;
      }
  }" {{-- Listener untuk menangkap perintah buka modal --}}
    @buka-modal-link.window="
        open = true;
        selectedText = $event.detail.text || '';
        targetWireModel = $event.detail.target || null;
        searchQuery = '';
    " x-show="open">

    <div @click.outside="open = false" class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-lg p-0 flex flex-col max-h-[85vh] overflow-hidden">

      {{-- Header & Input Pencarian --}}
      <div class="p-5 border-b border-gray-100 bg-gray-50/50 shrink-0">
        <h3 class="text-lg font-bold text-gray-800 mb-3">Sisipkan Tautan</h3>
        <div class="relative">
          <x-dynamic-component component="lucide-search" class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" />
          <input type="text" x-model="searchQuery" placeholder="Cari halaman atau rekatkan URL eksternal (https://)..."
            class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm shadow-sm focus:ring-foresty focus:border-foresty">
        </div>
      </div>

      {{-- Area Daftar (Bisa di-scroll) --}}
      <div class="flex-1 overflow-y-auto p-5 space-y-6">

        {{-- 🌟 SEGMEN 1: ANCHOR DI HALAMAN INI --}}
        <div x-show="inPageAnchors.length > 0 && searchQuery === ''">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block">Melompat ke Titik di Halaman Ini</span>
          <div class="grid grid-cols-2 gap-2">
            <template x-for="anchor in inPageAnchors" :key="anchor.id">
              <button type="button" @click="applyUrl('#' + anchor.id)" class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 hover:border-foresty hover:bg-sage-soft transition-colors text-left group">
                <div class="p-1.5 bg-gray-100 text-gray-500 rounded-md group-hover:bg-white group-hover:text-foresty transition-colors">
                  <x-dynamic-component component="lucide-hash" class="w-3.5 h-3.5" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-bold text-gray-700 truncate" x-text="anchor.id"></p>
                  <p class="text-[10px] text-gray-400 capitalize truncate" x-text="'Blok: ' + anchor.type"></p>
                </div>
              </button>
            </template>
          </div>
        </div>

        {{-- 🌟 SEGMEN 2: PENCARIAN HALAMAN INTERNAL CMS --}}
        <div>
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block">Halaman Website</span>
          <div class="min-h-[100px] border border-gray-100 rounded-lg p-3 text-sm text-gray-500 bg-gray-50">
            <p class="text-center py-4">Ketik di kolom pencarian untuk melacak halaman...</p>
            {{-- Nanti logika pencarian halaman Livewire Anda masukkan di sini --}}
          </div>
        </div>

        {{-- 🌟 SEGMEN 3: URL EKSTERNAL KUSTOM --}}
        <div x-show="searchQuery !== '' && (searchQuery.startsWith('http') || searchQuery.startsWith('mailto:') || searchQuery.startsWith('tel:'))">
          <button type="button" @click="applyUrl(searchQuery)" class="w-full flex items-center justify-between p-3 rounded-lg border border-foresty bg-sage-soft text-left hover:bg-[#c2ded3] transition-colors">
            <div>
              <p class="text-xs font-bold text-foresty">Gunakan Tautan Eksternal Ini</p>
              <p class="text-sm text-foresty truncate" x-text="searchQuery"></p>
            </div>
            <x-dynamic-component component="lucide-external-link" class="w-4 h-4 text-foresty" />
          </button>
        </div>

      </div>

      {{-- Footer Tutup --}}
      <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end shrink-0">
        <button type="button" @click="open = false" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-lg transition-colors">
          Batal
        </button>
      </div>
    </div>
  </div>

  <!-- 🌟 PANEL PRATINJAU SLIDE-OVER (Meluncur dari Kanan) -->
  <div x-cloak class="relative z-[100]" @open-preview-panel.window="previewUrl = $event.detail.url; previewOpen = true;" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" x-data="{
      previewOpen: false,
      previewUrl: '',
      deviceMode: 'desktop', // Pilihan: 'desktop' atau 'mobile'
  }">

    <div x-show="previewOpen" class="fixed inset-0 overflow-hidden" style="display: none;">
      <!-- Latar Belakang Gelap (Klik untuk menutup) -->
      <div x-show="previewOpen" x-transition.opacity.duration.300ms @click="previewOpen = false; previewUrl = ''" class="absolute inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity">
      </div>

      <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
        <!-- Panel Utama -->
        <div x-show="previewOpen" x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
          x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
          class="pointer-events-auto w-screen max-w-screen flex flex-col bg-gray-100 shadow-2xl">
          <!-- max-w-7xl -->

          <!-- HEADER PANEL -->
          <div class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-200">
            <div class="flex items-center gap-4">
              <h2 class="text-lg font-extrabold text-foresty" id="slide-over-title">Live Preview</h2>

              <!-- 🌟 TOMBOL TOGGLE MOBILE / DESKTOP -->
              <div class=" bg-gray-100 p-1 rounded-lg border border-gray-200 shadow-inner hidden md:flex">
                <button @click="deviceMode = 'desktop'" :class="deviceMode === 'desktop' ? 'bg-white shadow text-foresty' :
                    'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'"
                  class="flex items-center gap-2 px-3 py-1.5 text-xs font-bold rounded-md transition-all">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                    </path>
                  </svg>
                  Desktop
                </button>
                <button @click="deviceMode = 'mobile'" :class="deviceMode === 'mobile' ? 'bg-white shadow text-foresty' :
                    'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'"
                  class="flex items-center gap-2 px-3 py-1.5 text-xs font-bold rounded-md transition-all">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                  </svg>
                  Mobile
                </button>
              </div>
              <div class="flex items-center justify-between p-4">
                {{-- <h3 class="font-bold text-gray-700">Pratinjau Halaman</h3> --}}

                <!-- 🌟 TOMBOL TOGGLE MOBILE / DESKTOP -->
                <div class="flex bg-gray-100 p-1 rounded-lg border border-gray-200 shadow-inner  md:flex" x-data="{ activeLang: '{{ app()->getLocale() }}' }">
                  <button type="button" @click="activeLang = 'id'; document.getElementById('preview-iframe').contentWindow.postMessage({ type: 'change-lang', lang: 'id' }, '*')"
                    :class="activeLang === 'id' ? 'bg-white shadow text-foresty' :
                        'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'"
                    class="flex items-center gap-2 px-3 py-1.5 text-xs font-bold rounded-md transition-all">
                    ID
                  </button>
                  <button type="button" @click="activeLang = 'en'; document.getElementById('preview-iframe').contentWindow.postMessage({ type: 'change-lang', lang: 'en' }, '*')"
                    :class="activeLang === 'en' ? 'bg-white shadow text-foresty' :
                        'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'"
                    class="flex items-center gap-2 px-3 py-1.5 text-xs font-bold rounded-md transition-all">
                    EN
                  </button>
                </div>
              </div>
            </div>

            <!-- Tombol Tutup -->
            <button @click="previewOpen = false; previewUrl = ''" class="rounded-full p-2 bg-gray-50 text-gray-400 hover:text-red-600 hover:bg-red-50 focus:outline-none transition-colors">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- AREA KONTEN (IFRAME) -->
          <div class="flex-1 overflow-y-auto bg-gray-200 flex justify-center items-start pt-6 pb-12 transition-all duration-500">
            <!-- Wrapper Iframe (Lebarnya menyesuaikan pilihan device) -->
            <div class="transition-all duration-500 ease-in-out shadow-2xl overflow-hidden bg-white"
              :class="deviceMode === 'desktop' ? 'w-full h-full mx-6 rounded-xl border border-gray-300' :
                  'w-[375px] h-[812px] rounded-[2.5rem] border-[12px] border-gray-800'">
              <!-- Iframe Halaman Publik -->
              <template x-if="previewUrl !== ''">
                <iframe id="preview-iframe" :src="previewUrl" class="w-full h-full border-0 bg-white"></iframe>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
