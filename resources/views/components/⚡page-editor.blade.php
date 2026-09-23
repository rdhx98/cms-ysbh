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

<x-slot:title>
  {{ __('ui.header.write_page') }}
</x-slot:title>

<div
  class="box-border flex h-[calc(100vh-4rem)] flex-col overflow-x-hidden rounded-md bg-linear-to-b from-white via-gray-50 to-gray-100 p-2"
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
    }, 100);
  "
>
  <!-- 🌟 HEADER UTAMA (DITELEPORTASI KE NAVBAR) -->
  <template x-if="windowWidth >= 1366">
    <template x-teleport="#editor-toolbar-portal">
      <x-editor.display-control :active-locales="$activeLocales" />
    </template>
  </template>

  <template x-if="windowWidth < 1366">
    {{-- <div class="mb-2 p-2 bg-white border border-gray-200 rounded-xl shadow-sm overflow-x-auto"> --}}
    <div
      class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4"
    >
      <!-- Passing variabel yang sama ke sini juga -->
      <x-editor.display-control :active-locales="$activeLocales" />
    </div>
  </template>

  <!-- PESAN GALAT VALIDASI -->
  @if ($errors->any())
    <div
      class="mb-4 shrink-0 rounded-r-lg border-l-4 border-red-500 bg-red-50 p-4 text-red-700"
    >
      <p class="mb-1 font-bold">Gagal menyimpan, periksa isian berikut:</p>
      <ul class="list-disc space-y-1 pl-5 text-sm">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- AREA KONTEN UTAMA -->
  <div
    id="main-editor-scroll-area"
    class="relative min-h-0 flex-1 scrollbar-gutter-stable space-y-8 overflow-x-hidden overflow-y-auto px-4 pt-6 pb-24"
    {{-- class="min-h-0 flex-1 scrollbar-gutter-stable space-y-8 overflow-x-hidden overflow-y-auto px-4 pt-6 pb-24" --}}
    x-data="{
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
      },
    }"
  >
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
        <select
          wire:model="status"
          class="rounded-md border-gray-300 text-sm font-medium shadow-sm"
        >
          <option value="offline">Offline</option>
          <option value="online">Online</option>
        </select>
      </div>
      <div
        x-bind:class="{
          'grid grid-cols-1': effectiveLayout === 'single',
          'grid grid-cols-1 md:grid-cols-2':
            effectiveLayout === 'split' && splitLanguages.length === 2,
          'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3':
            effectiveLayout === 'split' && splitLanguages.length === 3,
          'grid grid-cols-1':
            effectiveLayout === 'split' && splitLanguages.length === 1,
        }"
        class="gap-6"
      >
        @foreach ($activeLocales as $code)
          <div
            {{-- x-show="(layoutMode === 'single' && singleActiveLang === '{{ $code }}') || (layoutMode === 'split' && splitLanguages.includes('{{ $code }}'))" --}}
            x-show="(effectiveLayout === 'single' && singleActiveLang === '{{ $code }}') || (effectiveLayout === 'split' && splitLanguages.includes('{{ $code }}'))"
            class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
          >
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-sm font-bold text-gray-700">
                Metadata ({{ strtoupper($code) }})
              </h3>
              <span
                class="text-foresty rounded bg-blue-100 px-2 py-0.5 text-[10px] font-bold"
                >{{ strtoupper($code) }}</span
              >
            </div>
            <div class="space-y-3">
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-600"
                  >Judul Halaman <span class="text-red-500">*</span></label
                >
                <input
                  type="text"
                  wire:model="page_title.{{ $code }}"
                  placeholder="Contoh: Layanan Kesehatan Ibu dan Anak"
                  class="text-md w-full rounded-md border-gray-300 p-2 shadow-sm"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-600"
                  >Slug URL</label
                >
                <input
                  type="text"
                  wire:model="slug.{{ $code }}"
                  placeholder="Contoh: layanan-kesehatan-ibu-dan-anak"
                  class="text-md w-full rounded-md border-gray-300 bg-gray-50 p-2 text-gray-500 shadow-sm"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-600"
                  >Judul Meta</label
                >
                <input
                  type="text"
                  wire:model="meta_title.{{ $code }}"
                  placeholder="Contoh: Layanan Kesehatan Ibu & Anak Terpadu | YSBH"
                  class="text-md w-full rounded-md border-gray-300 bg-gray-50 p-2 text-gray-500 shadow-sm"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-600"
                  >Deskripsi Meta</label
                >
                <textarea
                  row="6"
                  wire:model="meta_description.{{ $code }}"
                  placeholder="{{ $code === 'id' ? 'Tulis ringkasan menarik untuk hasil pencarian Google (maks. 160 karakter)...' : 'Write a brief summary for Google search results (max. 160 characters)...' }}"
                  class="text-md min-h-36 w-full resize-none rounded-md border-gray-300 bg-gray-50 p-2 text-gray-500 shadow-sm"
                ></textarea>
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
      <div
        x-sort="handleSort"
        class="flex flex-col gap-6"
        x-sort:config="{
          animation: 200,
          handle: '.drag-handle',
          ghostClass: 'opacity-50',
          dragClass: 'shadow-2xl',
        }"
      >
        @foreach ($blockOrder as $blockId)
          @php $block = $content[$blockId] ?? null; @endphp
          @if ($block)
            <!-- Block Loop -->
            {{-- <div id="block-wrapper-{{ $blockId }}" wire:key="block-{{ $blockId }}" x-sort:item="'{{ $blockId }}'" class="group relative w-full" x-data="{ showAnchorSetting: false }">
              <!-- 1. DRAG HANDLE (Selalu Tampil di Atas-Kiri saat < 1366px, Hover di Luar-Kiri saat PC) -->
              <div class="absolute transition-opacity z-20" x-bind:class="windowWidth < 1366 ? '-top-5 left-2 opacity-100' : 'top-4 -left-4 opacity-0 group-hover:opacity-100'">
                <button type="button" class="drag-handle bg-white shadow-sm" title="Geser Blok"></button>
              </div>

              <div class="absolute -top-4 flex gap-2 transition-all duration-200 z-30 right-3"
                x-bind:class="(windowWidth < 1366 || showAnchorSetting) ? ' opacity-100 visible' : 'opacity-0 invisible group-hover:opacity-100 group-hover:visible'">

                <div x-on:click.outside="showAnchorSetting = false" class="relative">

                  <button x-on:click="showAnchorSetting = !showAnchorSetting" type="button" class="p-1.5 text-gray-600 rounded-full border border-gray-200 shadow-sm transition-colors"
                    x-bind:class="showAnchorSetting ? 'bg-foresty text-white hover:bg-forest' : 'bg-white hover:bg-gray-50'" title="Pengaturan Blok">
                    <x-dynamic-component component="lucide-settings-2" class="w-4 h-4" />
                  </button>

                  <div x-show="showAnchorSetting" x-cloak style="display: none;" class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-xl shadow-xl p-4 z-50">
                    <label class="block text-xs font-bold text-foresty uppercase mb-1">ID Tautan (Anchor)</label>
                    <p class="text-[10px] text-gray-500 mb-2 leading-tight">Melompat ke blok ini (Contoh: <span class="font-mono text-coral">tentang-kami</span>).</p>

                    <input type="text" wire:model.live.debounce.500ms="content.{{ $blockId }}.anchor"
                      @input="$event.target.value = $event.target.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')" placeholder="nama-anchor"
                      class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-foresty focus:border-foresty">
                  </div>
                </div>

                {{-- Tombol Duplikat -->
                <button wire:click="duplicateBlock('{{ $blockId }}')" type="button" class="p-1.5 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 border border-gray-200 shadow-sm" title="Gandakan Blok">
                  <x-dynamic-component component="lucide-copy" class="w-4 h-4" />
                </button>

                {{-- Tombol Hapus -->
                <button wire:click="removeBlock('{{ $blockId }}')" type="button" class="p-1.5 bg-red-100 text-red-600 rounded-full hover:bg-red-200 border border-gray-200 shadow-sm" title="Hapus Blok">
                  <x-dynamic-component component="lucide-trash-2" class="w-4 h-4" />
                </button>

              </div>

              <!-- WORKING RENDER ISI BLOK -->
              <div class="w-full">
                <div class="gap-6"
                  x-bind:class="{
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
            </div> --}}
            <!-- BUNGKUSAN UTAMA BLOK (Di dalam loop blockOrder) -->
            <div
              id="block-wrapper-{{ $blockId }}"
              wire:key="block-{{ $blockId }}"
              x-sort:item="'{{ $blockId }}'"
              class="group relative flex w-full flex-col"
              x-bind:class="
                isRowPinned ? '!transform-none !static z-50' : 'relative'
              "
              @toggle-row-pin-{{ strtolower($blockId) }}.window="toggleRowPin()"
              {{-- 🌟 2 LISTENER BARU AGAR DIA MAU MENGALAH --}}
              @force-close-row-pin-{{ strtolower($blockId) }}.window="if(isRowPinned) toggleRowPin()"
              @toggle-collapse-all.window="
                if ($event.detail && isRowPinned) toggleRowPin();
              "
              x-data="{
                showAnchorSetting: false,

                isRowPinned: false,

                rowPinStyle: '',

                toggleRowPin() {
                    this.isRowPinned = !this.isRowPinned;
                    let editor = this.$refs.rowEditor;
                    let wrapper = document.getElementById('block-wrapper-{{ $blockId }}');

                    if (this.isRowPinned) {
                        // 1. Tahan tinggi pembungkus agar blok lain tidak melompat berantakan
                        if (wrapper) wrapper.style.minHeight = editor.offsetHeight + 'px';

                        let area = document.getElementById('main-editor-scroll-area');
                        if (area) {
                            let rect = area.getBoundingClientRect();
                            
                            // 🌟 RUMUS SAKTI (Mode Sederhana): 
                            // Tiru prinsip togglePin() di card-builder yang terbukti berhasil!
                            // Tanpa $nextTick, tanpa hitung Trap offset. Langsung tembak ukuran area.
                            this.rowPinStyle = `position: fixed !important; top: ${rect.top}px !important; left: ${rect.left}px !important; width: ${rect.width}px !important; height: ${rect.height}px !important; z-index: 9999 !important; margin: 0 !important; transform: none !important;`;
                        }
                    } else {
                        // 2. Kembalikan semuanya ke kondisi normal saat Pin dilepas
                        if (wrapper) wrapper.style.minHeight = '';
                        this.rowPinStyle = '';
                    }
                }                
              }"
              @resize.window="
                if (isRowPinned) {
                  toggleRowPin();
                  toggleRowPin();
                }
              "
            >
              <!-- 🌟 PLACEHOLDER BARIS -->
              <div
                x-ref="rowPlaceholder"
                x-show="isRowPinned"
                x-cloak
                class="border-foresty/50 bg-foresty/5 mb-6 flex w-full items-center justify-center rounded-xl border-2 border-dashed"
              >
                <span
                  class="text-foresty text-xs font-bold tracking-widest uppercase"
                  >Pin Baris (Split View) Aktif</span
                >
              </div>

              <!-- 🌟 EDITOR BARIS (Akan fixed jika isRowPinned = true) -->
              <div
                x-ref="rowEditor"
                :style="rowPinStyle"
                class="relative w-full transition-all duration-300"
                x-bind:class="
                  isRowPinned
                    ? 'bg-gray-100/90 backdrop-blur-md p-4 rounded-xl ring-4 ring-foresty/30 shadow-2xl overflow-hidden flex flex-col !static md:!fixed'
                    : ''
                "
              >
                <!-- 1. DRAG HANDLE (Kode Asli Anda) -->
                <div
                  class="absolute z-20 transition-opacity"
                  x-bind:class="
                    isRowPinned
                      ? 'hidden'
                      : windowWidth < 1366
                        ? '-top-5 left-2 opacity-100'
                        : 'top-4 -left-4 opacity-0 group-hover:opacity-100'
                  "
                >
                  <button
                    type="button"
                    class="drag-handle bg-white shadow-sm"
                    title="Geser Blok"
                  ></button>
                </div>

                <!-- 2. TOMBOL PENGATURAN & HAPUS -->
                <div
                  class="absolute -top-4 right-3 z-30 flex gap-2 transition-all duration-200"
                  x-bind:class="
                    isRowPinned
                      ? 'hidden'
                      : windowWidth < 1366 || showAnchorSetting
                        ? 'opacity-100 visible'
                        : 'opacity-0 invisible group-hover:opacity-100 group-hover:visible'
                  "
                >
                  {{-- Anchor ID Input --}}
                  <div
                    x-on:click.outside="showAnchorSetting = false"
                    class="relative"
                  >
                    <button
                      x-on:click="showAnchorSetting = !showAnchorSetting"
                      type="button"
                      class="rounded-full border border-gray-200 p-1.5 text-gray-600 shadow-sm transition-colors"
                      x-bind:class="
                        showAnchorSetting
                          ? 'bg-foresty text-white hover:bg-forest'
                          : 'bg-white hover:bg-gray-50'
                      "
                      title="Pengaturan Blok"
                    >
                      <x-dynamic-component
                        component="lucide-settings-2"
                        class="h-4 w-4"
                      />
                    </button>

                    <div
                      x-show="showAnchorSetting"
                      x-cloak
                      style="display: none"
                      class="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-gray-200 bg-white p-4 shadow-xl"
                    >
                      <label
                        class="text-foresty mb-1 block text-xs font-bold uppercase"
                        >ID Tautan (Anchor)</label
                      >
                      <p class="mb-2 text-[10px] leading-tight text-gray-500">Melompat ke blok ini (Contoh: <span class="text-coral font-mono">tentang-kami</span>).</p>

                      <input
                        type="text"
                        wire:model.live.debounce.500ms="content.{{ $blockId }}.anchor"
                        @input="
                          $event.target.value = $event.target.value
                            .toLowerCase()
                            .replace(/\s+/g, '-')
                            .replace(/[^a-z0-9-]/g, '')
                        "
                        placeholder="nama-anchor"
                        class="focus:ring-foresty focus:border-foresty w-full rounded-lg border border-gray-300 p-2 text-xs"
                      />
                    </div>
                  </div>

                  {{-- Tombol Duplikat --}}
                  <button
                    wire:click="duplicateBlock('{{ $blockId }}')"
                    type="button"
                    class="rounded-full border border-gray-200 bg-gray-100 p-1.5 text-gray-600 shadow-sm hover:bg-gray-200"
                    title="Gandakan Blok"
                  >
                    <x-dynamic-component
                      component="lucide-copy"
                      class="h-4 w-4"
                    />
                  </button>

                  {{-- Tombol Hapus --}}
                  <button
                    wire:click="removeBlock('{{ $blockId }}')"
                    type="button"
                    class="rounded-full border border-gray-200 bg-red-100 p-1.5 text-red-600 shadow-sm hover:bg-red-200"
                    title="Hapus Blok"
                  >
                    <x-dynamic-component
                      component="lucide-trash-2"
                      class="h-4 w-4"
                    />
                  </button>
                </div>

                <!-- 🌟 3. BUNGKUSAN EDITOR BARIS -->
                {{-- <div
                  class="w-full transition-all duration-300"
                  x-bind:class="
                    isRowPinned
                      ? 'bg-gray-100/90 backdrop-blur-md p-4 rounded-xl ring-4 ring-foresty/30 shadow-2xl overflow-hidden flex flex-col'
                      : ''
                  "
                > --}}
                {{-- Perhatikan di atas: overflow-y-auto diganti menjadi overflow-hidden --}}

                <!-- Pembungkus Dalam (Harus flex-col saat dipin) -->
                <div
                  class="relative flex w-full flex-col"
                  :class="isRowPinned ? 'flex-1 min-h-0' : ''"
                >
                  <!-- Drag Handle & Setting Asli Anda biarkan seperti semula -->
                  {{-- <div class="absolute transition-opacity z-20" :class="windowWidth < 1366 ? '-top-5 left-2 opacity-100' : 'top-4 -left-4 opacity-0 group-hover:opacity-100'">
                      <button type="button" class="drag-handle..."></button>
                    </div>
                    <div class="absolute -top-4 flex gap-2 transition-all duration-200 z-30 right-3"
                      :class="showAnchorSetting ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2 pointer-events-none'">
                      ...
                    </div> --}}

                  <!-- Grid Multi-Bahasa -->
                  <div
                    class="flex flex-col gap-6"
                    :class="{
                      'grid grid-cols-1': effectiveLayout === 'single',
                      'grid grid-cols-1 md:grid-cols-2':
                        effectiveLayout === 'split' &&
                        splitLanguages.length === 2,
                      'flex-1 min-h-0': isRowPinned,
                    }"
                  >
                    @foreach ($activeLocales as $code)
                      <div
                        wire:key="lang-wrapper-{{ $blockId }}-{{ $code }}"
                        x-show="(effectiveLayout === 'single' && singleActiveLang === '{{ $code }}') || (effectiveLayout === 'split' && splitLanguages.includes('{{ $code }}'))"
                        class="group-hover:border-foresty/80 flex flex-col space-y-3 rounded-xl border-2 border-transparent bg-gray-100 transition-colors group-hover:bg-white"
                        :class="isRowPinned ? 'flex-1 min-h-0' : 'h-full'"
                      >
                        <!-- Render Blok Komponen -->
                        <x-dynamic-component
                          :component="'blocks.editor.' . str_replace('_', '-', $block['type'])"
                          :block-id="$blockId"
                          :code="$code"
                          :block="$block"
                          :all-content="$content"
                        />
                      </div>
                    @endforeach
                    {{-- @foreach ($activeLocales as $code)
                      <div
                        wire:key="lang-wrapper-{{ $blockId }}-{{ $code }}"
                        x-show="(effectiveLayout === 'single' && singleActiveLang === '{{ $code }}') || (effectiveLayout === 'split' && splitLanguages.includes('{{ $code }}'))"
                        class="group-hover:border-foresty/80 flex flex-col space-y-3 rounded-xl border-2 border-transparent bg-gray-100 transition-colors group-hover:bg-white"
                        :class="isRowPinned ? 'flex-1 min-h-0' : 'h-full'"
                      >
                        <!-- 🌟 LOGIKA PENCARIAN KOMPONEN OTOMATIS -->
                        @php
                            $baseName = 'blocks.editor.' . str_replace('_', '-', $block['type']);
                            
                            // Cek apakah ada file index.blade.php di dalam folder tersebut
                            $componentPath = View::exists('components.' . $baseName . '.index') 
                                ? $baseName . '.index' 
                                : $baseName;
                        @endphp

                        <!-- Render Blok Komponen -->
                        <x-dynamic-component
                          :component="$componentPath"
                          :block-id="$blockId"
                          :code="$code"
                          :block="$block"
                          :all-content="$content"
                        />
                      </div>
                    @endforeach --}}
                  </div>
                </div>
                {{-- </div> --}}
                <!--delete this-->

                <!-- 3. RENDER ISI BLOK BAHASA (Kode Asli Anda yang disesuaikan) -->
                {{-- <div class="w-full" x-bind:class="isRowPinned ? 'flex-1 min-h-0' : ''">
                  <div class="gap-6"
                       x-bind:class="{
                           'grid grid-cols-1': effectiveLayout === 'single',
                           'grid grid-cols-1 md:grid-cols-2': effectiveLayout === 'split' && splitLanguages.length === 2,
                           'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3': effectiveLayout === 'split' && splitLanguages.length === 3,
                           'grid grid-cols-1': effectiveLayout === 'split' && splitLanguages.length === 1
                       }">
                    @foreach ($activeLocales as $code)
                      <div x-show="(effectiveLayout === 'single' && singleActiveLang === '{{ $code }}') || (effectiveLayout === 'split' && splitLanguages.includes('{{ $code }}'))"
                           class="space-y-3 bg-gray-100 group-hover:bg-white rounded-xl border-2 border-transparent group-hover:border-foresty/80 transition-colors h-full flex flex-col">

                        <!-- Render Blok Komponen -->
                        <x-dynamic-component :component="'blocks.editor.' . str_replace('_', '-', $block['type'])" :block-id="$blockId" :code="$code" :block="$block" :all-content="$content" />

                      </div>
                    @endforeach
                  </div>
                </div> --}}
              </div>
              <!-- Akhir rowEditor -->
            </div>
            <!-- Akhir block-wrapper -->
          @endif
        @endforeach
      </div>
    </div>
  </div>
  <!-- AREA KONTEN UTAMA -->

  <!-- AREA TOMBOL TAMBAH BLOK BERDASARKAN KATEGORI -->
  <div
    class="z-20 -mx-2 -mb-2 flex shrink-0 flex-wrap items-center justify-center gap-6 border-t border-gray-200 bg-white p-2 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)]"
  >
    <!-- KELOMPOK MIKRO (KONTEN UTAMA) -->
    <div class="flex items-center gap-2 border-r border-gray-200 pr-6">
      <span class="text-[10px] font-bold text-gray-400 uppercase">Konten:</span>
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('heading')"
        icon="heading-1"
        label="Judul"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('paragraph')"
        icon="align-left"
        label="Paragraf"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('eyebrow')"
        icon="crosshair"
        label="Eyebrow"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('image')"
        icon="image-plus"
        label="Gambar"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('button-group')"
        icon="plus-square"
        label="Grup Tombol"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('badge-group')"
        icon="badge-plus"
        label="Grup Lencana"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('stats-group')"
        icon="chart-column-big"
        label="Grup Statistik"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('card-group')"
        icon="credit-card"
        label="Grup Kartu"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('testimonial-group')"
        icon="message-circle"
        label="Grup Testimoni"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('OG-card-builder')"
        icon="playing-cards-fan"
        label="OG Kartu Builder"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('card-builder')"
        icon="playing-cards-fan"
        label="Kartu Builder"
      />
    </div>

    <!-- KELOMPOK MAKRO (TATA LETAK & SEKSI) -->
    <div class="flex items-center gap-2 border-r border-gray-200 pr-6">
      <span class="text-[10px] font-bold text-gray-400 uppercase"
        >Seksi Layout:</span
      >
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('multi-columns')"
        icon="columns-4"
        label="Kolom"
      />
      {{-- <x-buttons.add-blocks mode="icon-hover" command="editorTab = 'content'; addNewBlock('columns')" icon="columns" label="2 Kolom" /> --}}
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('section-divider')"
        icon="between-horizontal-start"
        label="Section Divider"
      />
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

  <!-- 🌟 MODAL PENCARIAN TAUTAN SUPER (Anchor & Halaman Internal) -->
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
    x-cloak
    x-data="{
      open: false,
      searchQuery: '',
      selectedText: '',
      targetWireModel: null, // Menyimpan alamat input Livewire jika dipanggil dari Lencana/Tombol

      // Fungsi untuk mengindeks semua Anchor yang ada di halaman ini secara real-time
      get inPageAnchors() {
        let anchors = [];
        let contentData = $wire.get('content') || {};
        for (let key in contentData) {
          if (
            contentData[key].anchor &&
            contentData[key].anchor.trim() !== ''
          ) {
            anchors.push({
              id: contentData[key].anchor,
              type: contentData[key].type,
            });
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
          window.dispatchEvent(
            new CustomEvent('insert-link-to-active-editor', {
              detail: { url: url, text: this.selectedText },
            }),
          );
        }
        this.open = false;
        this.searchQuery = '';
        this.targetWireModel = null;
      },
    }"
    {{-- Listener untuk menangkap perintah buka modal --}}
    @buka-modal-link.window="
      open = true;
      selectedText = $event.detail.text || '';
      targetWireModel = $event.detail.target || null;
      searchQuery = '';
    "
    x-show="open"
  >
    <div
      x-on:click.outside="open = false"
      class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-xl border border-gray-200 bg-white p-0 shadow-xl"
    >
      {{-- Header & Input Pencarian --}}
      <div class="shrink-0 border-b border-gray-100 bg-gray-50/50 p-5">
        <h3 class="mb-3 text-lg font-bold text-gray-800">Sisipkan Tautan</h3>
        <div class="relative">
          <x-dynamic-component
            component="lucide-search"
            class="absolute top-2.5 left-3 h-4 w-4 text-gray-400"
          />
          <input
            type="text"
            x-model="searchQuery"
            placeholder="Cari halaman atau rekatkan URL eksternal (https://)..."
            class="focus:ring-foresty focus:border-foresty w-full rounded-lg border border-gray-300 py-2 pr-3 pl-9 text-sm shadow-sm"
          />
        </div>
      </div>

      {{-- Area Daftar (Bisa di-scroll) --}}
      <div class="flex-1 space-y-6 overflow-y-auto p-5">
        {{-- 🌟 SEGMEN 1: ANCHOR DI HALAMAN INI --}}
        <div x-show="inPageAnchors.length > 0 && searchQuery === ''">
          <span
            class="mb-2 block text-[10px] font-bold tracking-widest text-gray-400 uppercase"
            >Melompat ke Titik di Halaman Ini</span
          >
          <div class="grid grid-cols-2 gap-2">
            <template x-for="anchor in inPageAnchors" :key="anchor.id">
              <button
                type="button"
                x-on:click="applyUrl('#' + anchor.id)"
                class="hover:border-foresty hover:bg-sage-soft group flex items-center gap-2 rounded-lg border border-gray-200 p-2 text-left transition-colors"
              >
                <div
                  class="group-hover:text-foresty rounded-md bg-gray-100 p-1.5 text-gray-500 transition-colors group-hover:bg-white"
                >
                  <x-dynamic-component
                    component="lucide-hash"
                    class="h-3.5 w-3.5"
                  />
                </div>
                <div class="min-w-0 flex-1">
                  <p
                    class="truncate text-xs font-bold text-gray-700"
                    x-text="anchor.id"
                  ></p>
                  <p
                    class="truncate text-[10px] text-gray-400 capitalize"
                    x-text="'Blok: ' + anchor.type"
                  ></p>
                </div>
              </button>
            </template>
          </div>
        </div>

        {{-- 🌟 SEGMEN 2: PENCARIAN HALAMAN INTERNAL CMS --}}
        <div>
          <span
            class="mb-2 block text-[10px] font-bold tracking-widest text-gray-400 uppercase"
            >Halaman Website</span
          >
          <div
            class="min-h-[100px] rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm text-gray-500"
          >
            <p class="py-4 text-center">Ketik di kolom pencarian untuk melacak halaman...</p>
            {{-- Nanti logika pencarian halaman Livewire Anda masukkan di sini --}}
          </div>
        </div>

        {{-- 🌟 SEGMEN 3: URL EKSTERNAL KUSTOM --}}
        <div
          x-show="
            searchQuery !== '' &&
            (searchQuery.startsWith('http') ||
              searchQuery.startsWith('mailto:') ||
              searchQuery.startsWith('tel:'))
          "
        >
          <button
            type="button"
            x-on:click="applyUrl(searchQuery)"
            class="border-foresty bg-sage-soft flex w-full items-center justify-between rounded-lg border p-3 text-left transition-colors hover:bg-[#c2ded3]"
          >
            <div>
              <p class="text-foresty text-xs font-bold">Gunakan Tautan Eksternal Ini</p>
              <p class="text-foresty truncate text-sm" x-text="searchQuery"></p>
            </div>
            <x-dynamic-component
              component="lucide-external-link"
              class="text-foresty h-4 w-4"
            />
          </button>
        </div>
      </div>

      {{-- Footer Tutup --}}
      <div
        class="flex shrink-0 justify-end border-t border-gray-100 bg-gray-50 p-4"
      >
        <button
          type="button"
          x-on:click="open = false"
          class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-100"
        >
          Batal
        </button>
      </div>
    </div>
  </div>

  <!-- 🌟 PANEL PRATINJAU SLIDE-OVER (Meluncur dari Kanan) -->
  <div
    x-cloak
    class="relative z-[100]"
    @open-preview-panel.window="
      previewUrl = $event.detail.url;
      previewOpen = true;
    "
    aria-labelledby="slide-over-title"
    role="dialog"
    aria-modal="true"
    x-data="{
      previewOpen: false,
      previewUrl: '',
      deviceMode: 'desktop', // Pilihan: 'desktop' atau 'mobile'
    }"
  >
    <div
      x-show="previewOpen"
      class="fixed inset-0 overflow-hidden"
      style="display: none"
    >
      <!-- Latar Belakang Gelap (Klik untuk menutup) -->
      <div
        x-show="previewOpen"
        x-transition.opacity.duration.300ms
        x-on:click="
          previewOpen = false;
          previewUrl = '';
        "
        class="absolute inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"
      ></div>

      <div
        class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16"
      >
        <!-- Panel Utama -->
        <div
          x-show="previewOpen"
          x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
          x-transition:enter-start="translate-x-full"
          x-transition:enter-end="translate-x-0"
          x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
          x-transition:leave-start="translate-x-0"
          x-transition:leave-end="translate-x-full"
          class="pointer-events-auto flex w-screen max-w-screen flex-col bg-gray-100 shadow-2xl"
        >
          <!-- max-w-7xl -->

          <!-- HEADER PANEL -->
          <div
            class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4"
          >
            <div class="flex items-center gap-4">
              <h2
                class="text-foresty text-lg font-extrabold"
                id="slide-over-title"
              >
                Live Preview
              </h2>

              <!-- 🌟 TOMBOL TOGGLE MOBILE / DESKTOP -->
              <div
                class="hidden rounded-lg border border-gray-200 bg-gray-100 p-1 shadow-inner md:flex"
              >
                <button
                  x-on:click="deviceMode = 'desktop'"
                  x-bind:class="
                    deviceMode === 'desktop'
                      ? 'bg-white shadow text-foresty'
                      : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'
                  "
                  class="flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-bold transition-all"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                    ></path>
                  </svg>
                  Desktop
                </button>
                <button
                  x-on:click="deviceMode = 'mobile'"
                  x-bind:class="
                    deviceMode === 'mobile'
                      ? 'bg-white shadow text-foresty'
                      : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'
                  "
                  class="flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-bold transition-all"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                  </svg>
                  Mobile
                </button>
              </div>
              <div class="flex items-center justify-between p-4">
                {{-- <h3 class="font-bold text-gray-700">Pratinjau Halaman</h3> --}}

                <!-- 🌟 TOMBOL TOGGLE MOBILE / DESKTOP -->
                <div
                  class="flex rounded-lg border border-gray-200 bg-gray-100 p-1 shadow-inner md:flex"
                  x-data="{ activeLang: '{{ app()->getLocale() }}' }"
                >
                  <button
                    type="button"
                    x-on:click="
                      activeLang = 'id';
                      document
                        .getElementById('preview-iframe')
                        .contentWindow.postMessage(
                          { type: 'change-lang', lang: 'id' },
                          '*',
                        );
                    "
                    x-bind:class="
                      activeLang === 'id'
                        ? 'bg-white shadow text-foresty'
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'
                    "
                    class="flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-bold transition-all"
                  >
                    ID
                  </button>
                  <button
                    type="button"
                    x-on:click="
                      activeLang = 'en';
                      document
                        .getElementById('preview-iframe')
                        .contentWindow.postMessage(
                          { type: 'change-lang', lang: 'en' },
                          '*',
                        );
                    "
                    x-bind:class="
                      activeLang === 'en'
                        ? 'bg-white shadow text-foresty'
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50'
                    "
                    class="flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-bold transition-all"
                  >
                    EN
                  </button>
                </div>
              </div>
            </div>

            <!-- Tombol Tutup -->
            <button
              x-on:click="
                previewOpen = false;
                previewUrl = '';
              "
              class="rounded-full bg-gray-50 p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 focus:outline-none"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- AREA KONTEN (IFRAME) -->
          <div
            class="flex flex-1 items-start justify-center overflow-y-auto bg-gray-200 pt-6 pb-12 transition-all duration-500"
          >
            <!-- Wrapper Iframe (Lebarnya menyesuaikan pilihan device) -->
            <div
              class="overflow-hidden bg-white shadow-2xl transition-all duration-500 ease-in-out"
              x-bind:class="
                deviceMode === 'desktop'
                  ? 'w-full h-full mx-6 rounded-xl border border-gray-300'
                  : 'w-[375px] h-[812px] rounded-[2.5rem] border-[12px] border-gray-800'
              "
            >
              <!-- Iframe Halaman Publik -->
              <template x-if="previewUrl !== ''">
                <iframe
                  id="preview-iframe"
                  :src="previewUrl"
                  class="h-full w-full border-0 bg-white"
                ></iframe>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
