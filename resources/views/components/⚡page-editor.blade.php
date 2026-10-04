<?php

use Livewire\Component;
use Livewire\WithFileUploads;

use App\Models\Page;
use App\Models\Post;

use Illuminate\Support\Str;

use Spatie\Activitylog\Models\Activity;

use Livewire\Attributes\On;
use App\Livewire\Traits\WithNotifications;
use App\Livewire\Traits\HasContentBlocks;

new class extends Component {
  use WithFileUploads;
  use WithNotifications;
  use HasContentBlocks;

  public ?Page $page = null;

  public array $activeLocales = [];

  public $layoutMode = "single"; //single split
  public $singleActiveLang = "id";
  public $splitLanguages = [];

  // public array $title = [];
  public array $page_title = [];
  public array $slug = [];
  public array $meta_title = [];
  public array $meta_description = [];

  // 🌟 Pendekatan Hibrida: Pisahkan data konten dan urutan
  public array $content = [];
  public array $blockOrder = []; // Menyimpan urutan ID secara akurat
  public array $settings = []; // Menyimpan urutan ID secara akurat

  public $status;

  public $isEditMode = false;

  protected function rules()
  {
    $rules = [
      "status" => "required|in:offline,online",
      "content" => "array",
    ];

    foreach ($this->activeLocales as $locale) {
      // UBAH VALIDASI MENJADI page_title
      $rules["page_title.{$locale}"] = "required|string|max:255";
      $rules["slug.{$locale}"] = "required|string|max:255";
    }

    return $rules;
  }

  #[On("mediaSelected")]
  public function handleMediaSelection($data)
  {
    $mediaId = $data["id"];
    $url = $data["url"];

    // basePath adalah path array JSON. Contoh: 'content.block_1.data'
    $basePath = $data["componentId"];

    // Buang teks awalan 'content.' agar sesuai dengan struktur $this->content di Livewire
    $cleanPath = preg_replace("/^content\./", "", $basePath);

    // Secara ajaib, helper data_set akan menembus array sedalam apa pun
    data_set($this->content, $cleanPath . ".url", $url);
    data_set($this->content, $cleanPath . ".media_id", $mediaId);
  }

  protected function messages()
  {
    $messages = [
      "status.required" => "Status halaman wajib dipilih.",
      "status.in" => "Status tidak valid.",
    ];

    foreach ($this->activeLocales as $locale) {
      $lang = strtoupper($locale);
      // UBAH PESAN GALAT MENJADI page_title
      $messages[
        "page_title.{$locale}.required"
      ] = "Judul ({$lang}) wajib diisi.";
      $messages[
        "page_title.{$locale}.max"
      ] = "Judul ({$lang}) maksimal 255 karakter.";
      $messages["slug.{$locale}.required"] = "Slug/URL ({$lang}) wajib diisi.";
      $messages[
        "slug.{$locale}.max"
      ] = "Slug/URL ({$lang}) maksimal 255 karakter.";
    }

    return $messages;
  }

  public function mount($pageSlug = null)
  {
    // 1. Konfigurasi Bahasa Dasar
    $this->activeLocales = config("app.supported_locales", ["id", "en"]);
    $this->splitLanguages = array_slice($this->activeLocales, 0, 2);

    if (!in_array($this->singleActiveLang, $this->activeLocales)) {
      $this->singleActiveLang = $this->activeLocales[0] ?? "id";
    }

    // 2. KUERI PENCARIAN SUPER KETAT & FLEKSIBEL
    $pageModel = null;
    if (!empty($pageSlug)) {
      $pageModel = \App\Models\Page::where(function ($query) use ($pageSlug) {
        // Skenario A: Jika URL berupa ID angka
        if (is_numeric($pageSlug)) {
          $query->where("id", $pageSlug);
        }

        // Skenario B: Jika slug disimpan sebagai JSON utuh
        $query->orWhere("slug->id", $pageSlug)->orWhere("slug->en", $pageSlug);

        // Skenario C: Jika slug disimpan sebagai teks murni (bukan JSON)
        $query->orWhere("slug", $pageSlug);

        // Skenario D: Jurus pamungkas menggunakan LIKE
        $query->orWhere("slug", "LIKE", '%"' . $pageSlug . '"%');
      })->first();
    } elseif ($pageSlug instanceof \App\Models\Page) {
      $pageModel = $pageSlug; // Berjaga-jaga jika dipanggil via object binding
    }

    // 3. POPULASI DATA KE FORMULIR JIKA DITEMUKAN
    if ($pageModel && $pageModel->exists) {
      $this->isEditMode = true;
      $this->page = $pageModel;
      $this->status = $pageModel->status ?? "draft";

      // 🌟 BYPASS MUTATOR: Ambil data mentah persis seperti hasil dd()
      $modelData = $pageModel->toArray();

      // Ekstrak data (Pasti berbentuk array jika di DB berupa JSON dan sudah di-cast)
      $titleData = $modelData["title"] ?? [];
      $slugData = $modelData["slug"] ?? [];
      $metaTitleData = $modelData["meta_title"] ?? [];
      $metaDescData = $modelData["meta_description"] ?? [];

      // Pertahanan ekstra jika ternyata masih ada yang berbentuk string JSON
      $titleData = is_string($titleData)
        ? json_decode($titleData, true) ?? []
        : (is_array($titleData)
          ? $titleData
          : []);
      $slugData = is_string($slugData)
        ? json_decode($slugData, true) ?? []
        : (is_array($slugData)
          ? $slugData
          : []);
      $metaTitleData = is_string($metaTitleData)
        ? json_decode($metaTitleData, true) ?? []
        : (is_array($metaTitleData)
          ? $metaTitleData
          : []);
      $metaDescData = is_string($metaDescData)
        ? json_decode($metaDescData, true) ?? []
        : (is_array($metaDescData)
          ? $metaDescData
          : []);

      // Petakan per bahasa
      foreach ($this->activeLocales as $loc) {
        // ✅ Pastikan menggunakan page_title
        $this->page_title[$loc] = $titleData[$loc] ?? "";
        $this->slug[$loc] = $slugData[$loc] ?? "";
        $this->meta_title[$loc] = $metaTitleData[$loc] ?? "";
        $this->meta_description[$loc] = $metaDescData[$loc] ?? "";
      }

      // 4. PENYELAMATAN STRUKTUR BLOK (Dari Seeder & Database ke Livewire)
      $rawContent = $modelData["content"] ?? [];
      $rawContent = is_string($rawContent)
        ? json_decode($rawContent, true) ?? []
        : (is_array($rawContent)
          ? $rawContent
          : []);

      $this->content = [];
      $this->blockOrder = [];
      $this->settings = []; // 🌟 Inisialisasi

      // 🌟 1. DETEKSI FORMAT BARU (Flat Data Structure)
      if (isset($rawContent["blocks"]) && isset($rawContent["order"])) {
        $this->content = $rawContent["blocks"];
        $this->blockOrder = $rawContent["order"];
        $this->settings = $rawContent["settings"] ?? []; // Ambil dari root
        // 🌟 AUTO-MIGRASI: Keluarkan 'settings' jika masih terselip di dalam 'blocks' (dari bug sebelumnya)
        if (isset($this->content["settings"])) {
          $this->settings = $this->content["settings"];
          unset($this->content["settings"]);
        }
      }
      // 🌟 2. FALLBACK KE FORMAT LAMA (Untuk kompabilitas dengan Seeder lawas)
      else {
        if (
          isset($rawContent["id"]) &&
          is_array($rawContent["id"]) &&
          isset($rawContent["id"][0]["type"])
        ) {
          $rawContent = $rawContent["id"];
        } elseif (
          isset($rawContent["en"]) &&
          is_array($rawContent["en"]) &&
          isset($rawContent["en"][0]["type"])
        ) {
          $rawContent = $rawContent["en"];
        }

        foreach ($rawContent as $block) {
          if (is_array($block) && isset($block["type"])) {
            $id = $block["id"] ?? "blk_" . Str::random(8);
            $block["id"] = $id;
            $this->content[$id] = $block;
            $this->blockOrder[] = $id;
          }
        }
      }
      // 🌟 3. Pastikan pengaturan TOC memiliki nilai default agar UI tidak error
      if (!isset($this->settings["toc_position"])) {
        $this->settings["toc_position"] = "right";
      }
    } else {
      // 5. HALAMAN BARU (Jika URL benar-benar tidak ditemukan)
      $this->isEditMode = false;
      $this->page = new \App\Models\Page();
      $this->status = "offline";
      // ✅ Gunakan page_title
      $this->page_title = array_fill_keys($this->activeLocales, "");
      $this->slug = array_fill_keys($this->activeLocales, "");
      $this->meta_title = array_fill_keys($this->activeLocales, "");
      $this->meta_description = array_fill_keys($this->activeLocales, "");
    }

    // 6. BUAT BLOK DEFAULT JIKA EDITOR KOSONG TOTAL
    if (empty($this->content)) {
      $id = "blk_" . uniqid();
      $this->content[$id] = [
        "id" => $id,
        "type" => "heading",
        "data" => ["text" => array_fill_keys($this->activeLocales, "")],
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
      "blocks" => $this->content, // Berisi SELURUH blok (induk & anak) dengan key ID (blk_...)
      "order" => $this->blockOrder, // Berisi HANYA urutan ID blok level terluar (root)
      "settings" => $this->settings,
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
      $editUrl = route("page.edit", ["pageSlug" => $redirectSlug]);

      // 🌟 UBAH URL BROWSER TANPA REDIRECT (ZERO BLINK)
      // Ini akan mengganti /pages/create menjadi /pages/slug-baru/edit di address bar
      $this->js("window.history.replaceState(null, '', '{$editUrl}');");

      $this->isEditMode = true;
    }
    // $this->notifyFlash(__('ui.notification.page_saved'), 'success');
    $this->notify(__("ui.notification.page_saved"), "success");
  }

  public function saveAndPreview()
  {
    $this->save(true);

    $slugCantik = $this->page->id;
    if (is_array($this->slug) && !empty($this->slug["id"])) {
      $slugCantik = $this->slug["id"];
    } elseif (is_string($this->slug) && !empty($this->slug)) {
      $slugCantik = $this->slug;
    }

    // 🌟 PERBAIKAN: Tambahkan parameter mode => 'raw'
    $previewUrl = route("page.preview", [
      "pageSlug" => $slugCantik,
      "mode" => "raw", // 'full' 'raw'
      "lang" => app()->getLocale(),
    ]);

    $this->dispatch("open-preview-panel", url: $previewUrl);
  }
  public function searchInternalPages($keyword)
  {
    if (empty(trim($keyword))) {
      return [];
    }

    $keyword = strtolower(trim($keyword));
    $locales = config("app.supported_locales", ["id", "en"]);

    // ==========================================
    // 1. PENCARIAN DI MODEL PAGE
    // ==========================================
    $pages = Page::where(function ($query) use ($keyword, $locales) {
      foreach ($locales as $locale) {
        // Mencari ke dalam properti JSON berdasarkan bahasa
        // (Menggunakan strtolower untuk memastikan case-insensitive)
        $query->orWhereRaw(
          "LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.\"$locale\"'))) LIKE ?",
          ["%" . $keyword . "%"],
        );
      }
    })
      ->orderBy("created_at", "desc")
      ->limit(5)
      ->get()
      ->map(function ($page) {
        return [
          // $page->title otomatis menggunakan bahasa yang sedang aktif berkat Spatie Translatable
          "title" => "📄 " . $page->title,
          // 🌟 MENGGUNAKAN PSEUDO-URL yang cocok dengan Regex getParsedContentAttribute Anda
          "url" => "internal://page/" . $page->slug,
        ];
      });

    // ==========================================
    // 2. PENCARIAN DI MODEL POST (ARTIKEL)
    // ==========================================
    $posts = Post::where(function ($query) use ($keyword, $locales) {
      foreach ($locales as $locale) {
        $query->orWhereRaw(
          "LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.\"$locale\"'))) LIKE ?",
          ["%" . $keyword . "%"],
        );
      }
    })
      ->orderBy("created_at", "desc")
      ->limit(5)
      ->get()
      ->map(function ($post) {
        return [
          "title" => "📝 " . $post->title,
          // 🌟 MENGGUNAKAN PSEUDO-URL article
          "url" => "internal://article/" . $post->slug,
        ];
      });

    // ==========================================
    // 3. GABUNGKAN & KEMBALIKAN KE ALPINE.JS
    // ==========================================
    // Menggabungkan Collection Pages dan Posts, lalu mengubahnya menjadi Array murni
    return $pages->merge($posts)->toArray();
  }
};
?>

<x-slot:title>
  {{
    __(
      "ui.header.write_page",
    )
  }}
</x-slot:title>

<div
  class="box-border flex h-[calc(100vh-5rem)] min-h-0 w-full flex-1 scrollbar-thin flex-col overflow-x-hidden rounded-md"
  {{-- class="box-border flex h-[calc(100vh-5rem)] flex-col overflow-x-hidden rounded-md" --}}
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
  @php
    $iconsList = collect(config("cms.lucide", []))->sort()->values()->all();
    $marginBottom = config("cms.design.margin_bottom", []);
    $designTemplate = config("cms.design", []);
  @endphp

  <svg style="display: none">
    @foreach ($iconsList as $icon)
      <symbol
        id="icon-{{ $icon }}"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        {{-- Ambil path bawaan Lucide atau biarkan component merendernya sekali di sini --}}
        <x-dynamic-component :component="'lucide-' . $icon" />
      </symbol>
    @endforeach
  </svg>
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

  <!-- WRAPPER UTAMA -->
  <div class="flex min-h-0 w-full flex-1 overflow-hidden">
    <!-- AREA KONTEN UTAMA -->
    <div
      id="main-editor-scroll-area"
      class="relative min-h-0 flex-1 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent scrollbar-gutter-stable space-y-8 overflow-x-hidden overflow-y-auto rounded-md bg-linear-to-b from-white via-gray-50 to-gray-100 px-4 pt-0 pb-24"
      {{--bg-linear-to-b from-white via-gray-50 to-gray-100 --}}
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
      <!--  RUANGAN 1: METADATA (Hanya Tampil di Tab Meta) -->
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
                  >{{
                    strtoupper(
                      $code,
                    )
                  }}</span
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
        <div
          class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
        >
          <label
            class="mb-3 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
          >
            Navigasi Daftar Isi (TOC)
          </label>

          <div
            class="flex w-full items-center rounded-md bg-gray-100 p-1 shadow-inner"
          >
            <!-- Opsi Sembunyikan -->
            <button
              type="button"
              wire:click="$set('settings.toc_position', 'hidden')"
              class="flex-1 rounded py-1.5 text-xs font-bold transition-all outline-none {{ ($settings['toc_position'] ?? 'right') === 'hidden' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
              Sembunyi
            </button>

            <!-- Opsi Kiri -->
            <button
              type="button"
              wire:click="$set('settings.toc_position', 'left')"
              class="flex-1 rounded py-1.5 text-xs font-bold transition-all outline-none {{ ($settings['toc_position'] ?? 'right') === 'left' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
              Di Kiri
            </button>

            <!-- Opsi Kanan -->
            <button
              type="button"
              wire:click="$set('settings.toc_position', 'right')"
              class="flex-1 rounded py-1.5 text-xs font-bold transition-all outline-none {{ ($settings['toc_position'] ?? 'right') === 'right' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
              Di Kanan
            </button>
          </div>
          <p class="mt-2 text-[10px] text-gray-400">Daftar isi akan memindai blok Judul (Heading) secara otomatis. Hanya tampil di layar komputer (Desktop).</p>
        </div>
      </div>

      <!-- RUANGAN 2: EDITOR KONTEN (Hanya Tampil di Tab Konten) -->
      <div x-show="editorTab === 'content'" x-cloak class="space-y-8">
        <!-- KONTROL STATUS KONTEN -->
        <div class="sticky top-0 z-30 flex items-center justify-between pt-2">
          <h2 class="text-xl font-bold text-gray-800">Konten Halaman</h2>
        </div>

        <!-- ALPINE SORTABLE CONTAINER -->
        <div
          x-sort="handleSort"
          class="flex flex-col"
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
              <x-dynamic-component
                :component="'blocks.editor.' . str_replace('_', '-', $block['type'])"
                :block-id="$blockId"
                :block="$block"
                :all-content="$content"
                :active-locales="$activeLocales"
                :icons-list="$iconsList"
                :margin-bottom="$designTemplate['margin_bottom']"
                :border-radius="$designTemplate['border_radiuses']"
              />
            @endif
          @endforeach
        </div>
      </div>
    </div>

    <!-- MINIMAP -->
    <div
      x-show="windowWidth >= 1366 && editorTab === 'content' && isMinimapOpen"
      x-transition:enter="transition ease-out duration-300"
      x-transition:enter-start="opacity-0 translate-x-10"
      x-transition:enter-end="opacity-100 translate-x-0"
      x-cloak
      x-data="{
        activeBlockId: null,
        visibleBlocks: new Set(),
        observer: null,

        initMinimap() {
          const rootArea = document.getElementById('main-editor-scroll-area');
          if (!rootArea) return;

          // ATURAN 1 & 2: Zona deteksi di 15% hingga 40% layar (dari atas), Threshold 0
          const options = {
            root: rootArea,
            rootMargin: '-15% 0px -60% 0px',
            threshold: 0,
          };

          this.observer = new IntersectionObserver((entries) => {
            let hasChanges = false;
            entries.forEach((entry) => {
              const id = entry.target.id.replace('block-wrapper-', '');
              if (entry.isIntersecting) {
                this.visibleBlocks.add(id);
                hasChanges = true;
              } else {
                if (this.visibleBlocks.has(id)) {
                  this.visibleBlocks.delete(id);
                  hasChanges = true;
                }
              }
            });

            if (hasChanges) this.calculateActiveBlock();
          }, options);

          this.observeBlocks();

          // Pasang ulang observer setiap kali ada blok ditambah/dihapus oleh Livewire
          Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
              setTimeout(() => this.observeBlocks(), 150);
            });
          });
        },

        observeBlocks() {
          if (!this.observer) return;
          this.observer.disconnect();
          this.visibleBlocks.clear();

          // Ambil semua elemen pembungkus blok yang ada di DOM saat ini
          document
            .querySelectorAll('[id^=\'block-wrapper-\']')
            .forEach((el) => {
              this.observer.observe(el);
            });
        },

        calculateActiveBlock() {
          if (this.visibleBlocks.size === 0) return;

          // ATURAN 3: Jika ada 2 blok masuk radar, selalu pilih blok yang urutannya paling atas di DOM
          const domBlocks = document.querySelectorAll(
            '[id^=\'block-wrapper-\']',
          );
          for (let el of domBlocks) {
            const id = el.id.replace('block-wrapper-', '');
            if (this.visibleBlocks.has(id)) {
              this.activeBlockId = id;
              break;
            }
          }
        },
      }"
      x-init="initMinimap()"
      {{-- class="w-64 shrink-0 overflow-y-auto border-l border-gray-200 bg-gray-50/50 p-4 scrollbar-thin pb-24" --}}
      class="ml-2 w-38 shrink-0 scrollbar-thin overflow-y-auto rounded-t-md border-l border-gray-200 bg-linear-to-b from-white via-gray-50 to-gray-100 px-4 pt-2 pb-24"
      style="display: none"
    >
      <div class="sticky top-0 z-10 mb-2 bg-gray-50/50 backdrop-blur-sm">
        <span
          class="text-xxs font-extrabold tracking-widest text-gray-500 uppercase"
        >
          Minimap
        </span>
        {{-- <p class="mt-1 text-[10px] text-gray-400">Lompat cepat ke blok.</p> --}}
      </div>

      <div class="flex flex-col justify-start gap-2">
        @forelse ($blockOrder as $index => $bId)
          @php
            $bType = $content[$bId]["type"] ?? "unknown";
            $bName = str_replace(["_", "-"], " ", $bType);
          @endphp

          <button
            type="button"
            wire:key="minimap-btn-{{ $bId }}"
            x-on:click="
              let el = document.getElementById('block-wrapper-{{ $bId }}');
              if(el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                el.classList.add('ring-2', 'ring-foresty', 'ring-offset-2');
                setTimeout(() => el.classList.remove('ring-2', 'ring-foresty', 'ring-offset-2'), 1500);
              }
            "
            x-bind:class="activeBlockId === '{{ $bId }}' ? 
            'border-foresty bg-sage-soft shadow-sm scale-110' : 
            'border-gray-200 bg-white hover:border-foresty hover:bg-sage-soft hover:scale-110'"
            class="group ml-2 flex w-full origin-right items-center gap-3 rounded-md border text-left transition-all outline-none"
          >
            <span
              x-bind:class="activeBlockId === '{{ $bId }}' ? 
              'bg-foresty text-gray-100 shadow-sm' : 
              'bg-gray-100 text-gray-500 group-hover:bg-white group-hover:text-foresty'"
              class="text-xxs flex h-5 w-5 shrink-0 items-center justify-center rounded-md font-bold transition-colors"
            >
              {{ $index + 1 }}
            </span>
            <span
              x-bind:class="activeBlockId === '{{ $bId }}' ? 'text-foresty' : 'text-gray-700 group-hover:text-foresty'"
              class="text-xxs truncate font-bold capitalize transition-colors"
            >
              {{ $bName }}
            </span>
          </button>

        @empty
          <div
            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 p-4 text-center"
          >
            <span class="text-xxs font-bold text-gray-400">Belum ada blok</span>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <!-- AREA TOMBOL TAMBAH BLOK BERDASARKAN KATEGORI -->
  <div
    class="z-20 flex shrink-0 flex-wrap items-center justify-center gap-6 border-t border-gray-200 bg-white px-4 py-3 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)]"
  >
    <!-- <div
    class="z-20 -mx-2 -mb-2 flex shrink-0 flex-wrap items-center justify-center gap-6 border-t border-gray-200 bg-white p-2 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)]"
  > -->
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
      {{-- <x-buttons.add-blocks
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
      /> --}}
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('card-builder')"
        icon="playing-cards-fan"
        label="Kartu Builder"
      />
      <x-buttons.add-blocks
        mode="icon-hover"
        command="editorTab = 'content'; addNewBlock('step-group')"
        icon="list-ordered"
        label="Step builder"
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
        class="absolute inset-0 cursor-pointer bg-gray-900/75 backdrop-blur-sm transition-opacity"
      ></div>

      <!-- 🌟 KUNCI: w-full memastikan panel bisa tumbuh selebar layar jika diperlukan -->
      <div
        class="pointer-events-none fixed inset-y-0 right-0 flex w-full max-w-full justify-end sm:pl-16"
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
          class="pointer-events-auto flex w-full max-w-full flex-col bg-gray-100 shadow-2xl transition-all duration-500"
          x-bind:class="
            deviceMode === 'desktop' ? 'max-w-[100vw]' : 'max-w-2xl'
          "
        >
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
                  class="flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-bold transition-all outline-none"
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
                  class="flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-bold transition-all outline-none"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                  </svg>
                  Mobile
                </button>
              </div>

              <!-- 🌟 TOMBOL TOGGLE BAHASA -->
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
                  class="flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-bold transition-all outline-none"
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
                  class="flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-bold transition-all outline-none"
                >
                  EN
                </button>
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
                  ? 'w-full h-full mx-0 sm:mx-6 rounded-none sm:rounded-xl border-0 sm:border border-gray-300'
                  : 'w-[375px] h-[812px] rounded-[2.5rem] border-[12px] border-gray-800 shrink-0'
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
  <x-editor.modal-link-finder />
</div>
