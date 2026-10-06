<?php

use App\Content\ContentDocument;
use App\Content\ContentRules;
use App\Content\ContentType;
use App\Content\ContentWriter;
use App\Livewire\Traits\HasContentBlocks;
use App\Livewire\Traits\ManagesBlockStructure;
use App\Livewire\Traits\WithNotifications;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * content.builder — satu editor untuk Page, Article (Post), dan Snippet.
 *
 * Memuat record, judul halaman per rute, pengaturan halaman (judul/slug/status/SEO; snippet: kunci/penutup/urutan), simpan.
 * Logika yang bisa diuji dipisah dari komponen: ContentType, ContentDocument, ContentRules (validasi), ContentWriter (isi record),
 * Slug. Komponen ini sendiri belum dijalankan di Livewire oleh saya (sandbox tanpa Livewire).
 */
new class extends Component {
  use WithFileUploads;
  use WithNotifications;
  use HasContentBlocks;
  use ManagesBlockStructure;

  /** Jenis konten. Ditentukan SEKALI di mount() dari nama rute; request update Livewire tidak punya rute aslinya. */
  #[Locked]
  public string $type = "page";

  /** Nama rute saat mount, mis. "v2.page.create". Dipakai agar redirect mempertahankan awalan ("v2.page.edit"). */
  #[Locked]
  public string $routeName = "";

  public ?Model $record = null;

  public array $activeLocales = [];

  // Dulu `page_title` (kemungkinan karena `$title` bentrok dengan slot `title` milik layout). Bentuk jamak = netral.
  public array $titles = [];
  public array $slug = [];
  public array $meta_title = [];
  public array $meta_description = [];

  public array $content = [];
  public array $blockOrder = [];
  public array $settings = [];
  public ?string $status = null;

  // Khusus artikel (pemilih kategori belum ada di UI: butuh model Category)
  public ?int $category_id = null;

  // Khusus snippet (ContentType::usesKey()). Tak dipakai untuk halaman/artikel.
  public string $key = "";
  public string $description = "";
  public bool $is_closing = false;
  public int $sort_order = 0;

  // ------------------------------------------------------------------ memuat

  public function mount(): void
  {
    // Hapus & duplikat di outline memakai removeBlock()/duplicateBlock() dari trait. Versi asli menyisakan anak
    // yatim (hapus) dan berbagi anak dengan salinannya (duplikat): hentikan dengan pesan jelas, bukan merusak data.
    if (
      !method_exists($this, "deleteBlockTree") ||
      !method_exists($this, "cloneBlockTree")
    ) {
      throw new \LogicException(
        "HasContentBlocks belum di-patch (deleteBlockTree/cloneBlockTree tidak ada). Pasang patch trait dulu.",
      );
    }

    $this->routeName = (string) request()->route()?->getName();
    $type = ContentType::fromRouteName($this->routeName);
    $this->type = $type->value;

    $this->activeLocales = config("app.supported_locales", ["id", "en"]);

    $this->record = $this->resolveRecord($type);

    // TODO: $this->authorize($this->record->exists ? 'update' : 'create', $this->record);

    $this->fillFromRecord($type);
  }

  /** Computed, bukan method publik (aksi yang bisa dipanggil browser) dan bukan private (tak terjangkau dari view). */
  #[Computed]
  public function contentType(): ContentType
  {
    return ContentType::from($this->type);
  }

  /**
   * Record dari parameter rute. Binding model untuk komponen halaman Livewire HANYA terjadi lewat tipe parameter mount()
   * (Livewire menunjuk ulang aksi rute ke mount() lalu menjalankan substituteImplicitBindings). mount() ini tanpa
   * parameter, jadi parameter rute tiba sebagai string mentah ("5"), bukan model: tanpa penanganan ini, rute edit
   * diam-diam masuk mode BUAT. Karena itu: model -> pakai; kosong -> record baru; selain itu -> cari lewat id.
   */
  private function resolveRecord(ContentType $type): Model
  {
    $class = $type->modelClass();
    $param = request()->route($type->routeParam());

    if ($param instanceof Model) {
      return $param;
    }
    if ($param === null || $param === "") {
      return new $class();
    }

    return $class::query()->findOrFail($param);
  }

  private function fillFromRecord(ContentType $type): void
  {
    $data = $this->record->exists ? $this->record->toArray() : [];

    $this->titles = $this->localeMap($data["title"] ?? null);
    if ($type->usesSlug()) {
      $this->slug = $this->localeMap($data["slug"] ?? null);
    }
    if ($type->usesMeta()) {
      $this->meta_title = $this->localeMap($data["meta_title"] ?? null);
      $this->meta_description = $this->localeMap(
        $data["meta_description"] ?? null,
      );
    }
    if ($type->usesKey()) {
      $this->key = (string) ($this->record->key ?? "");
      $this->description = (string) ($this->record->description ?? "");
      $this->is_closing = (bool) ($this->record->is_closing ?? false);
      $this->sort_order = (int) ($this->record->sort_order ?? 0);
    }
    $this->status = $this->record->status ?: $type->defaultStatus();
    if ($type === ContentType::Article) {
      $this->category_id = $this->record->category_id
        ? (int) $this->record->category_id
        : null;
    }

    // Satu-satunya pembaca `content` (menggantikan ±70 baris di mount() lama dan salinannya di page-preview)
    $doc = ContentDocument::fromRaw($data["content"] ?? null);
    $this->content = $doc->blocks;
    $this->blockOrder = $doc->order;
    $this->settings = $doc->settings;

    // Konten baru: mulai dengan satu Judul, memakai satu-satunya sumber nilai bawaan
    if (!$this->record->exists && empty($this->content)) {
      $id = "blk_" . uniqid();
      $this->content[$id] = [
        "id" => $id,
        "type" => "heading",
        "data" => $this->getDefaultDataForType("heading"),
      ];
      $this->blockOrder = [$id];
    }
  }

  /** Peta bahasa -> teks; tahan terhadap string JSON / null / bahasa yang belum terisi. */
  private function localeMap(mixed $value): array
  {
    if (is_string($value)) {
      $value = json_decode($value, true);
    }
    $value = is_array($value) ? $value : [];

    $out = [];
    foreach ($this->activeLocales as $locale) {
      $out[$locale] = (string) ($value[$locale] ?? "");
    }
    return $out;
  }

  // ------------------------------------------------------------------ judul halaman (<title>)

  /** Teks untuk <x-slot:title>. Dipanggil saat halaman dirender penuh (muat awal & wire:navigate). */
  #[Computed]
  public function pageTitle(): string
  {
    $editing = (bool) $this->record?->exists;
    $key = $this->contentType->titleKey($editing);

    if (!trans()->has($key)) {
      return $this->contentType->titleFallback($editing, $this->displayTitle());
    }

    return $editing ? __($key, ["title" => $this->displayTitle()]) : __($key);
  }

  /** Judul di header editor; memakai teks cadangan bila kunci terjemahan belum ditambahkan. */
  #[Computed]
  public function headerLabel(): string
  {
    $editing = (bool) $this->record?->exists;
    $key = $this->contentType->headerKey($editing);

    return trans()->has($key)
      ? __($key)
      : $this->contentType->headerFallback($editing);
  }

  private function displayTitle(): string
  {
    foreach ([app()->getLocale(), ...$this->activeLocales] as $locale) {
      if (filled($this->titles[$locale] ?? null)) {
        return $this->titles[$locale];
      }
    }
    return "…";
  }

  // ------------------------------------------------------------------ menyimpan

  /** Aturan & nama atribut ada di ContentRules (diuji terhadap validator Laravel + SQLite). */
  protected function rules(): array
  {
    return ContentRules::for(
      $this->contentType,
      $this->activeLocales,
      $this->record?->getKey(),
    );
  }

  protected function validationAttributes(): array
  {
    return ContentRules::attributes($this->contentType, $this->activeLocales);
  }

  public function save(): void
  {
    $type = $this->contentType;

    try {
      $this->validate();
    } catch (ValidationException $e) {
      // Kolom yang salah ada di tab Halaman: tampilkan, jangan biarkan pengguna menebak
      $this->js("Alpine.store('editor').tab = 'page'");
      throw $e;
    }

    ContentWriter::fill($this->record, $type, [
      "titles" => $this->titles,
      "slug" => $this->slug,
      "meta_title" => $this->meta_title,
      "meta_description" => $this->meta_description,
      "status" => $this->status,
      "blocks" => $this->content,
      "order" => $this->blockOrder,
      "settings" => $this->settings,
      "key" => $this->key,
      "description" => $this->description,
      "is_closing" => $this->is_closing,
      "sort_order" => $this->sort_order,
      "category_id" => $this->category_id,
      "user_id" => auth()->id(),
    ]);

    $wasNew = !$this->record->exists;
    $this->record->save();

    // Editor lama memakai ui.notification.page_saved; jenis lain memakai pola yang sama bila kuncinya ada.
    $notice = "ui.notification." . $this->type . "_saved";
    $this->notify(
      trans()->has($notice) ? __($notice) : "Berhasil disimpan",
      "success",
    );

    if ($wasNew) {
      // Pindah ke rute edit PADA AWALAN YANG SAMA ("v2.page.edit", bukan "page.edit" = editor lama). Kirim ID eksplisit
      // (getRouteKey() model ini = slug, bukan id).
      $this->redirect(
        route($type->routeNameFor($this->routeName, "edit"), [
          $type->routeParam() => $this->record->getKey(),
        ]),
        navigate: true,
      );
    }
  }

  /** Tautan kembali ke daftar: rute "<awalan>.<jenis>.index" bila ada, jika tidak "<jenis>.index" (editor lama). */
  #[Computed]
  public function indexUrl(): string
  {
    foreach (
      [
        $this->contentType->routeNameFor($this->routeName, "index"),
        $this->contentType->indexRoute(),
      ]
      as $name
    ) {
      if (Route::has($name)) {
        return route($name);
      }
    }

    return url("/");
  }

  // ------------------------------------------------------------------ media dari File Manager

  #[On("mediaSelected")]
  public function handleMediaSelection(array $data): void
  {
    $basePath = (string) ($data["componentId"] ?? "");

    // Path datang dari browser: hanya terima bentuk content.{idBlok}.data.… untuk blok yang ada
    if (
      !preg_match(
        '/^content\.([A-Za-z0-9_]+)(\.[A-Za-z0-9_]+)*$/',
        $basePath,
        $m,
      ) ||
      !isset($this->content[$m[1]]) ||
      !str_contains($basePath, ".data")
    ) {
      return;
    }
    $path = preg_replace("/^content\./", "", $basePath);

    // TODO: ambil url & alt dari model Media berdasarkan id, jangan percaya url/alt kiriman klien
    data_set($this->content, $path . ".url", (string) ($data["url"] ?? ""));
    data_set($this->content, $path . ".media_id", $data["id"] ?? null);
    data_set(
      $this->content,
      $path . ".alt_text",
      (string) ($data["alt_text"] ?? ""),
    );

    $this->dispatch(
      "update-block-alt",
      componentId: $basePath,
      altText: $data["alt_text"] ?? "",
    );
  }

  // TODO (Fase E): salin dari page-editor lama -> searchInternalPages() (pindahkan ke trait), saveAndPreview()
  //   (ganti: pratinjau tidak boleh menimpa record yang online — lihat MIGRASI.md).
};
?>

{{-- Harus berada DI LUAR elemen akar, dan view hanya boleh punya SATU elemen akar. --}}
<x-slot:title>
  {{ $this->pageTitle }}
</x-slot:title>

{{-- x-effect: judul tab mengikuti ketikan (slot di atas hanya dievaluasi saat halaman dirender penuh). --}}
<div
  x-data
  x-init="$store.editor.clear()"
  x-effect="
    const t = (($wire.titles || {})[@js(app()->getLocale())] || '').trim()
    document.title = (t || @js($this->pageTitle)) + ' — ' + @js(config('app.name'))
  "
  class="flex h-dvh flex-col"
>
  {{-- ======= HEADER (atas-tengah) ======= --}}
  <header class="flex items-center gap-3 border-b px-4 py-2">
    <a href="{{ $this->indexUrl }}" wire:navigate class="text-sm font-semibold"
      >←</a
    >
    <h1 class="flex-1 truncate text-sm font-extrabold">
      {{ $this->headerLabel }}
    </h1>
    {{-- TODO: tab bahasa (Tunggal/Ganda) -> $store.editor.lang, tab Metadata/Konten, Pratinjau, Minimap --}}
    <button
      type="button"
      wire:click="save"
      class="rounded-full bg-emerald-800 px-4 py-1.5 text-xs font-bold text-white"
    >
      Simpan
    </button>
  </header>

  {{-- Ringkasan galat validasi: tanpa ini, Simpan yang gagal tidak menampilkan apa pun. --}}
  @if ($errors->any())
    <div
      role="alert"
      class="border-b border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700"
    >
      <p class="font-bold">Belum bisa disimpan:</p>
      <ul class="list-disc pl-4">
        @foreach ($errors->all() as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="grid min-h-0 flex-1 grid-cols-[250px_1fr_330px]">
    {{-- ======= KIRI: pohon struktur (tambah / pindah / duplikat / hapus) ======= --}}
    <div class="overflow-y-auto border-r p-3">
      <x-editor.outline :content="$content" :order="$blockOrder" />
    </div>

    {{-- ======= TENGAH: kanvas + pratinjau (Fase 2) ======= --}}
    <section class="overflow-y-auto p-4">Kanvas</section>

    {{-- ======= KANAN: tab "Halaman" (judul, slug, status, SEO) dan "Blok" (properti yang difokus) ======= --}}
    <aside class="flex min-h-0 flex-col border-l">
      <div class="flex shrink-0 border-b text-xs font-bold" role="tablist">
        <button
          type="button"
          role="tab"
          x-on:click="$store.editor.tab = 'page'"
          x-bind:aria-selected="$store.editor.tab === 'page'"
          x-bind:class="
            $store.editor.tab === 'page'
              ? 'border-foresty text-foresty border-b-2'
              : 'text-gray-500 hover:text-gray-700'
          "
          class="flex-1 px-3 py-2.5"
        >
          Halaman
        </button>
        <button
          type="button"
          role="tab"
          x-on:click="$store.editor.tab = 'block'"
          x-bind:aria-selected="$store.editor.tab === 'block'"
          x-bind:class="
            $store.editor.tab === 'block'
              ? 'border-foresty text-foresty border-b-2'
              : 'text-gray-500 hover:text-gray-700'
          "
          class="flex-1 px-3 py-2.5"
        >
          Blok
        </button>
      </div>

      <div class="min-h-0 flex-1 overflow-y-auto p-3">
        <div x-show="$store.editor.tab === 'page'">
          <x-content.page-settings
            :type="$type"
            :locales="$activeLocales"
            :is-new="!$record?->exists"
          />
        </div>
        <div x-show="$store.editor.tab === 'block'" x-cloak>
          <x-editor.inspector :locales="$activeLocales" />
        </div>
      </div>
    </aside>
  </div>

  {{-- Satu modal untuk semua tombol ikon. Sprite ikon (<x-editor.icon-sprite />) dipasang di LAYOUT, bukan di sini. --}}
  <x-editor.icon-picker-modal />

  {{-- Panel debug: hanya tampil bila APP_DEBUG=true --}}
  <x-editor.debug />
</div>
