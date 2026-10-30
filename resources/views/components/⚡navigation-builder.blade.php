<?php

/**
 * Navigation Builder (rilis 28, tata letak dibangun ulang di rilis 88; dulu bernama menu-builder). OPSIONAL dan milik Anda: salin isinya MENIMPA berkas
 * navigation builder Anda (jalur dan nama komponen Anda tetap, jadi rute tidak berubah). Pemasang tidak menyentuhnya.
 *
 * Rilis 88 (hanya tata letak; logika simpan / hapus / urutan tidak berubah): dua tab Header | Footer yang bertahan setelah dimuat ulang; tab Header punya bilah
 * atas tetap (jumlah menu, pilihan bahasa Ganda | ID | EN, tombol Menu baru) seperti editor Footer dan Pengumuman; daftar bisa diurutkan dengan geser dan tiap
 * baris punya tombol tampil/sembunyi, ubah, hapus yang selalu terlihat; formulir dibuka di dalam barisnya (<x-content.nav-item-form>) dengan tujuan berupa chip;
 * pratinjau menu di sisi kanan. Tab Footer memuat content.footer-editor (satu-satunya tempat mengatur footer).
 *
 * Bedanya dengan versi lama:
 *  - Bahasa dari config (Languages::fromConfig: app.supported_locales + cms.default_locale), bukan "id"/"en" tertulis. Label bahasa bawaan
 *    wajib; bahasa lain boleh kosong dan memakai label bahasa bawaan.
 *  - Tujuan = SATU kolom `url` untuk semua bahasa (docs/BAHASA.md): pilih Beranda, Daftar artikel, Halaman CMS (dicari dari judul),
 *    URL, Email, Telepon, atau Anchor. Landing menerjemahkannya ke bahasa pembaca (NavLinks); pratinjau di bawah menunjukkan alamat tiap bahasa.
 *    `route_name` tidak lagi diisi (rute statis lama sudah dihapus rilis 23). Baris lama yang masih membawa rute mati ditandai dan harus dipilihkan tujuan baru.
 *  - Alamat divalidasi dengan aturan yang sama dengan tombol (LinkResolver): hanya https://, /jalur, mailto:, tel:, #anchor.
 *  - Rilis 42: tujuan "Artikel per kategori" (pilih kategori dari daftar): menuju halaman yang menampung artikel satu kategori
 *    ("/articles/category/{slug}" EN, "/id/artikel/kategori/{slug}" ID). Disimpan "/{daftar artikel}/category/{slug}" dan diterjemahkan ke bahasa pembaca.
 *  - Baris yang menuju halaman atau kategori yang belum ada diberi tanda (tautan itu akan 404).
 *  - Simpan, hapus, dan urutan memeriksa login, memakai transaksi, dan memberi notifikasi (WithNotifications); urutan hanya menulis baris yang berubah.
 * Logika yang bisa diuji ada di App\Content\MenuTarget (tests/menu-target-test.php). Komponen ini sendiri belum dijalankan di Livewire sungguhan.
 */

use App\Content\CategoryLookup;
use App\Content\Languages;
use App\Content\Links\LinkResolver;
use App\Content\MenuTarget;
use App\Content\Names;
use App\Content\NavLinks;
use App\Content\PublicLookup;
use App\Livewire\Traits\SearchesLinkTargets;
use App\Livewire\Traits\WithNotifications;
use App\Models\Navigation;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
  use WithNotifications;
  use SearchesLinkTargets; // searchLinkTargets('page', kata): pencarian halaman untuk pemilih tujuan

  /** Bahasa bawaan dulu. Dipakai juga oleh SearchesLinkTargets. */
  public array $activeLocales = [];

  #[Locked]
  public string $defaultLocale = "en";

  #[Locked]
  public ?int $editId = null;

  /** Formulir terbuka (menu baru di atas daftar, atau menu $editId di dalam barisnya). */
  public bool $formOpen = false;

  /** bahasa => label */
  public array $labels = [];

  public string $kind = "page";

  /** Slug halaman / URL / email / nomor / anchor, sesuai $kind. */
  public string $ref = "";

  public string $pageQuery = "";

  public bool $is_active = true;

  /** Nama rute lama yang dibawa baris yang sedang diedit (informasi saja). */
  #[Locked]
  public string $legacyRoute = "";

  public function mount(): void
  {
    abort_unless(auth()->check(), 403);
    $cfg = Languages::fromConfig();
    $this->defaultLocale = $cfg["default"];
    $this->activeLocales = array_values(
      array_unique([$cfg["default"], ...$cfg["locales"]]),
    );
    $this->resetForm();
  }

  /** @return list<string> slug daftar artikel semua bahasa */
  #[Computed]
  public function indexSlugs(): array
  {
    return array_map(
      fn(string $l) => Languages::indexSlug(
        config("cms.articles_index_slug"),
        $l,
      ),
      $this->activeLocales,
    );
  }

  #[Computed]
  public function rows(): array
  {
    return Navigation::query()
      ->orderBy("order")
      ->orderBy("id")
      ->get()
      ->map(function (Navigation $menu) {
        $t = MenuTarget::parse(
          $menu->url,
          $menu->route_name,
          $this->indexSlugs,
          $this->activeLocales,
          $this->defaultLocale,
        );
        $missing =
          $t["ref"] !== "" &&
          match ($t["kind"]) {
            "page" => PublicLookup::sibling(
              "page",
              $t["ref"],
              $this->defaultLocale,
              $this->activeLocales,
              $this->defaultLocale,
            ) === null,
            "category" => PublicLookup::categorySibling(
              $t["ref"],
              $this->defaultLocale,
              $this->activeLocales,
              $this->defaultLocale,
            ) === null,
            default => false,
          };

        return [
          "id" => (int) $menu->id,
          "kind" => $t["kind"],
          "labels" => collect($this->activeLocales)
            ->mapWithKeys(
              fn($l) => [
                $l =>
                  (string) ($menu->getTranslation("label", $l, false) ?? ""),
              ],
            )
            ->all(),
          "target" => MenuTarget::describe($t["kind"], $t["ref"], $t["legacy"]),
          "warning" =>
            $t["kind"] === null || $t["legacy"] !== ""
              ? "Tujuan perlu dipilih ulang."
              : ($missing
                ? ($t["kind"] === "category"
                  ? "Kategori ini tidak ditemukan (dihapus atau slug berubah): tautannya akan 404."
                  : "Halaman ini belum online atau tidak ditemukan: tautannya akan 404.")
                : null),
          "is_active" => (bool) $menu->is_active,
        ];
      })
      ->all();
  }

  /**
   * Kategori yang bisa dituju: [slug bahasa bawaan (atau bahasa lain yang ada) => nama]. Kategori tanpa slug di bahasa mana pun dilewati
   * (tidak punya alamat). Urut nama.
   *
   * @return array<string,string>
   */
  #[Computed]
  public function categoryOptions(): array
  {
    $out = [];
    try {
      foreach (
        \App\Models\Category::query()->orderBy("id")->limit(500)->get()
        as $category
      ) {
        $row = [
          "id" => $category->getKey(),
          "name" => $category->getRawOriginal("name"),
          "slug" => $category->getRawOriginal("slug"),
        ];
        $slugs = CategoryLookup::slugs($row, $this->activeLocales);
        $slug =
          $slugs[$this->defaultLocale] ?? (array_values($slugs)[0] ?? null);
        if ($slug !== null) {
          $out[$slug] =
            CategoryLookup::name(
              $row,
              $this->defaultLocale,
              $this->activeLocales,
            ) ?:
            $slug;
        }
      }
    } catch (\Throwable) {
      return []; // tabel categories belum siap: pilihan kosong, bagian lain menu tetap berfungsi
    }
    asort($out, SORT_NATURAL | SORT_FLAG_CASE);

    return $out;
  }

  #[Computed]
  public function pageResults(): array
  {
    return mb_strlen(trim($this->pageQuery)) < 2
      ? []
      : $this->searchLinkTargets("page", $this->pageQuery);
  }

  /** Alamat yang akan dilihat pembaca tiap bahasa untuk tujuan yang sedang diisi (kosong bila belum sah). */
  #[Computed]
  public function preview(): array
  {
    $built = MenuTarget::build($this->kind, $this->ref, $this->articlesSlug());
    if ($built["error"] !== null) {
      return [];
    }
    $out = [];
    try {
      foreach ($this->activeLocales as $l) {
        $out[$l] = NavLinks::localize(
          $built["url"],
          $l,
          $this->activeLocales,
          $this->defaultLocale,
          $this->indexSlugs,
          fn(string $slug, string $loc) => PublicLookup::sibling(
            "page",
            $slug,
            $loc,
            $this->activeLocales,
            $this->defaultLocale,
          ),
          fn(string $slug, string $loc) => LinkResolver::address(
            "page",
            $slug,
            $loc,
          ),
          fn(string $loc) => LinkResolver::homeAddress($loc),
          fn(string $loc) => LinkResolver::indexAddress($loc),
          function (string $slug, string $loc) {
            $hit = PublicLookup::categorySibling(
              $slug,
              $loc,
              $this->activeLocales,
              $this->defaultLocale,
            );

            return $hit === null
              ? null
              : LinkResolver::categoryAddress($hit["slug"], $hit["locale"]);
          },
        );
      }
    } catch (\Throwable) {
      return []; // basis data belum siap / konfigurasi rusak: pratinjau dilewati, simpan tetap berfungsi
    }

    return $out;
  }

  private function articlesSlug(): string
  {
    return Languages::indexSlug(
      config("cms.articles_index_slug"),
      $this->defaultLocale,
    );
  }

  public function updatedKind(): void
  {
    $this->ref = "";
    $this->pageQuery = "";
    $this->resetErrorBag("ref");
  }

  public function pickPage(int $id): void
  {
    abort_unless(auth()->check(), 403);
    $page = Page::query()->find($id);
    $slug = $page
      ? Names::of(
        $page->getRawOriginal("slug"),
        $this->defaultLocale,
        $this->activeLocales,
      )
      : "";
    if ($slug === "") {
      $this->addError("ref", "Halaman itu belum punya slug.");

      return;
    }
    $this->ref = $slug;
    $this->pageQuery = "";
    $this->resetErrorBag("ref");
  }

  public function save(): void
  {
    abort_unless(auth()->check(), 403);
    $def = $this->defaultLocale;

    $this->validate(
      [
        "labels" => ["array"],
        "labels.$def" => ["required", "string", "max:120"],
        "labels.*" => ["nullable", "string", "max:120"],
        "is_active" => ["boolean"],
      ],
      [
        "labels.$def.required" =>
          "Label bahasa bawaan (" . strtoupper($def) . ") wajib diisi.",
        "labels.*.max" => "Label terlalu panjang (maksimal 120 karakter).",
      ],
    );

    $built = MenuTarget::build($this->kind, $this->ref, $this->articlesSlug());
    if (
      $built["error"] === null &&
      $this->kind === "category" &&
      PublicLookup::categorySibling(
        trim($this->ref),
        $this->defaultLocale,
        $this->activeLocales,
        $this->defaultLocale,
      ) === null
    ) {
      $built = [
        "url" => null,
        "error" => "Kategori itu tidak ditemukan. Pilih dari daftar.",
      ]; // nilai dari browser tidak dipercaya: harus kategori yang ada
    }
    if ($built["error"] !== null) {
      $this->addError("ref", $built["error"]);

      return;
    }

    $base = trim((string) $this->labels[$def]);
    $label = [];
    foreach ($this->activeLocales as $l) {
      $text = trim((string) ($this->labels[$l] ?? ""));
      $label[$l] = $text !== "" ? $text : $base; // bahasa tanpa label memakai label bahasa bawaan (menu tidak pernah kosong)
    }
    $data = [
      "label" => $label,
      "route_name" => "",
      "url" => $built["url"],
      "is_active" => $this->is_active,
    ];

    DB::transaction(function () use ($data) {
      if ($this->editId) {
        Navigation::query()->findOrFail($this->editId)->update($data);
      } else {
        $data["order"] = (int) Navigation::query()->max("order") + 1; // paling bawah
        Navigation::query()->create($data);
      }
    });

    $this->notify(
      $this->editId ? "Menu diperbarui" : "Menu ditambahkan",
      "success",
    );
    $this->resetForm();
  }

  public function edit(int $id): void
  {
    abort_unless(auth()->check(), 403);
    $menu = Navigation::query()->findOrFail($id);
    $t = MenuTarget::parse(
      $menu->url,
      $menu->route_name,
      $this->indexSlugs,
      $this->activeLocales,
      $this->defaultLocale,
    );

    $this->resetErrorBag();
    $this->formOpen = true;
    $this->editId = (int) $menu->id;
    $this->labels = collect($this->activeLocales)
      ->mapWithKeys(
        fn($l) => [
          $l => (string) ($menu->getTranslation("label", $l, false) ?? ""),
        ],
      )
      ->all();
    $this->kind = $t["kind"] ?? "page";
    $this->ref = $t["ref"];
    $this->legacyRoute = $t["legacy"];
    $this->pageQuery = "";
    $this->is_active = (bool) $menu->is_active;
  }

  public function delete(int $id): void
  {
    abort_unless(auth()->check(), 403);
    Navigation::query()->whereKey($id)->delete();
    if ($this->editId === $id) {
      $this->resetForm();
    }
    $this->notify("Menu dihapus", "success");
  }

  /** Buka formulir kosong untuk menu baru. */
  public function newItem(): void
  {
    abort_unless(auth()->check(), 403);
    $this->resetForm();
    $this->formOpen = true;
  }

  /** Tampilkan / sembunyikan satu menu di situs tanpa membuka formulir. */
  public function toggleActive(int $id): void
  {
    abort_unless(auth()->check(), 403);
    $menu = Navigation::query()->findOrFail($id);
    $menu->update(["is_active" => !$menu->is_active]);
    unset($this->rows);
    $this->notify(
      $menu->is_active
        ? "Menu ditampilkan di situs"
        : "Menu disembunyikan dari situs",
      "success",
    );
  }

  public function resetForm(): void
  {
    $this->reset(["editId", "ref", "pageQuery", "legacyRoute", "formOpen"]);
    $this->resetErrorBag();
    $this->labels = array_fill_keys($this->activeLocales, "");
    $this->kind = "page";
    $this->is_active = true;
  }

  /** Dipanggil Alpine Sort: $itemId digeser ke $position (0 = paling atas). */
  public function updateOrder(int $itemId, int $position): void
  {
    abort_unless(auth()->check(), 403);

    DB::transaction(function () use ($itemId, $position) {
      $current = Navigation::query()
        ->orderBy("order")
        ->orderBy("id")
        ->pluck("order", "id")
        ->all(); // id => order
      $next = MenuTarget::reorder(array_keys($current), $itemId, $position);
      foreach ($next as $index => $id) {
        if ((int) $current[$id] !== $index) {
          Navigation::query()
            ->whereKey($id)
            ->update(["order" => $index]); // hanya baris yang berubah
        }
      }
    });
  }
};
?>

@php
  $user = auth()->user();
  $canFooter =
    $user &&
    method_exists($user, "hasRole") &&
    $user->hasRole(["admin", "editor"]);
  $tabs = $canFooter ? ["header", "footer"] : ["header"];
  $rows = $this->rows;
  $total = count($rows);
  $shown = count(array_filter($rows, fn($r) => $r["is_active"]));
  $gaps = [];
  foreach ($activeLocales as $l) {
    $gaps[$l] =
      $l === $defaultLocale
        ? 0
        : count(array_filter($rows, fn($r) => ($r["labels"][$l] ?? "") === ""));
  }
  $langNames = ["en" => "English", "id" => "Indonesia"];
  $kindIcons = [
    "page" => "file",
    "home" => "home",
    "articles" => "list",
    "category" => "columns",
    "url" => "globe",
    "mailto" => "mail",
    "tel" => "phone",
    "anchor" => "link",
  ];
  $iconBtn =
    "inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty";
  $pal = \App\Content\Palette::defaults();
  $pvBg = $pal["forest"]["hex"];
  $pvGold = $pal["aurum"]["hex"];
  $formProps = [
    "editId" => $editId,
    "legacyRoute" => $legacyRoute,
    "locales" => $activeLocales,
    "default" => $defaultLocale,
    "labels" => $labels,
    "kind" => $kind,
    "ref" => $ref,
    "pageResults" => $this->pageResults,
    "categories" => $this->categoryOptions,
    "preview" => $this->preview,
  ];
@endphp

<x-slot:title>
  {{
    __(
      "Menu Builder",
    )
  }}
</x-slot:title>

<x-main-wrapper class="bg-white p-2">
  {{-- Tab (Header | Footer) bertahan setelah dimuat ulang: alamat #tab-… dulu, lalu ingatan peramban; tab yang tidak ada jatuh ke tab pertama. --}}
  <div
    class="relative flex w-full max-w-6xl min-w-0 flex-col gap-4"
    data-nav-root
    x-data="{
            tabs: @js($tabs),
            tab: 'header',
            pv: @js($defaultLocale),
            init() {
                let t = ''
                try {
                    const m = location.hash.match(/^#tab-([a-z-]+)$/)
                    t = m ? m[1] : localStorage.getItem('ysbh.nav.tab') || ''
                } catch (e) {}
                this.tab = this.tabs.includes(t) ? t : this.tabs[0]
                this.$watch('tab', (v) => {
                    try { localStorage.setItem('ysbh.nav.tab', v) } catch (e) {}
                    try { history.replaceState(history.state, '', '#tab-' + v) } catch (e) {}
                })
            },
        }"
    x-effect="if ($store.editor.lang !== 'both') pv = $store.editor.lang;"
  >
    <div class="flex w-full flex-wrap items-center justify-between gap-3">
      <h1 class="text-2xl font-bold">{{ __("Navigasi") }}</h1>
      @if ($canFooter)
        <div
          class="flex gap-1 rounded-lg bg-gray-100 p-1 text-sm font-bold"
          role="tablist"
          aria-label="Bagian navigasi"
          data-menu-tabs
        >
          <button
            type="button"
            role="tab"
            x-on:click="tab = 'header'"
            x-bind:aria-selected="(tab === 'header').toString()"
            x-bind:class="
              tab === 'header'
                ? 'bg-white shadow text-foresty'
                : 'text-gray-500'
            "
            class="focus-visible:ring-foresty rounded-md px-3 py-1 focus:outline-none focus-visible:ring-2"
            data-tab-header
          >
            Header
          </button>
          <button
            type="button"
            role="tab"
            x-on:click="tab = 'footer'"
            x-bind:aria-selected="(tab === 'footer').toString()"
            x-bind:class="
              tab === 'footer'
                ? 'bg-white shadow text-foresty'
                : 'text-gray-500'
            "
            class="focus-visible:ring-foresty rounded-md px-3 py-1 focus:outline-none focus-visible:ring-2"
            data-tab-footer
          >
            Footer
          </button>
        </div>
      @endif
    </div>

    {{-- ===== Tab Header: menu navigasi di atas halaman ===== --}}
    <div x-show="tab === 'header'" class="flex flex-col gap-4" data-header-tab>
      <div
        class="z-20 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur md:sticky md:top-0"
        data-nav-bar
      >
        <div class="mr-auto min-w-0">
          <h2 class="text-foresty text-base leading-tight font-bold">
            Menu header
          </h2>
          <p class="text-xs text-gray-500" data-nav-count>{{ $total }} menu, {{ $shown }} tampil di situs</p>
        </div>

        <div data-nav-langs>
          <x-content.lang-switch
            :locales="$activeLocales"
            :gaps="$gaps"
            :names="$langNames"
            :default="$defaultLocale"
          />
        </div>

        <button
          type="button"
          wire:click="newItem"
          class="bg-foresty focus-visible:ring-foresty inline-flex items-center gap-1.5 rounded-xl px-5 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#043b2c] focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
          data-nav-new
        >
          <x-content.icon name="plus" /> Menu baru
        </button>
      </div>

      <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_21rem]">
        <div class="flex min-w-0 flex-col gap-3">
          @if ($formOpen && !$editId)
            <x-content.nav-item-form
              :edit-id="null"
              :legacy-route="$legacyRoute"
              :locales="$activeLocales"
              :default="$defaultLocale"
              :labels="$labels"
              :kind="$kind"
              :ref="$ref"
              :page-results="$this->pageResults"
              :categories="$this->categoryOptions"
              :preview="$this->preview"
            />
          @endif

          @if ($total === 0 && !$formOpen)
            <div
              class="rounded-2xl border border-dashed border-gray-300 px-4 py-10 text-center"
              data-nav-empty
            >
              <p class="text-sm font-semibold text-gray-700">Belum ada menu navigasi.</p>
              <p class="mt-1 text-sm text-gray-500">Tambahkan menu pertama untuk bagian atas situs.</p>
            </div>
          @endif

          <ul
            x-sort="$wire.updateOrder($item, $position)"
            class="flex flex-col gap-2"
            data-nav-list
          >
            @foreach ($rows as $row)
              <li
                x-sort:item="{{ $row['id'] }}"
                wire:key="menu-{{ $row['id'] }}"
                data-nav-item="{{ $row['id'] }}"
              >
                @if ($formOpen && $editId === $row["id"])
                  <x-content.nav-item-form
                    :edit-id="$editId"
                    :legacy-route="$legacyRoute"
                    :locales="$activeLocales"
                    :default="$defaultLocale"
                    :labels="$labels"
                    :kind="$kind"
                    :ref="$ref"
                    :page-results="$this->pageResults"
                    :categories="$this->categoryOptions"
                    :preview="$this->preview"
                  />
                @else
                  <div
                    class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:border-gray-300 {{ $row['is_active'] ? '' : 'bg-gray-50' }}"
                  >
                    <button
                      type="button"
                      x-sort:handle
                      class="{{ $iconBtn }} cursor-grab touch-none active:cursor-grabbing"
                      title="Geser untuk mengubah urutan"
                      aria-label="Geser untuk mengubah urutan"
                    >
                      <x-content.icon name="grip" class="h-5 w-5" />
                    </button>

                    <div
                      class="min-w-0 flex-1 {{ $row['is_active'] ? '' : 'opacity-60' }}"
                    >
                      <p class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                        @foreach ($activeLocales as $l)
                          <span
                            x-bind:class="pv === '{{ $l }}' ? 'text-sm font-bold text-gray-900' : 'text-xs text-gray-400'"
                            >{{
                              ($row["labels"][$l] ?? "") !== ""
                                ? $row["labels"][$l]
                                : "—"
                            }}</span
                          >
                        @endforeach
                      </p>
                      <p class="mt-1 flex flex-wrap items-center gap-1.5">
                        <span
                          class="inline-flex max-w-full items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600"
                          data-nav-target
                        >
                          <x-content.icon
                            :name="$kindIcons[$row['kind'] ?? ''] ?? 'link'"
                            class="h-3 w-3"
                          />
                          <span
                            class="truncate font-mono"
                            >{{ $row["target"] }}</span
                          >
                        </span>
                        @unless ($row["is_active"])
                          <span
                            class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-semibold text-gray-600"
                            data-nav-off
                            >Disembunyikan</span
                          >
                        @endunless
                        @if ($row["warning"])
                          <span
                            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800"
                            data-nav-warning
                            ><x-content.icon name="alert" class="h-3 w-3" /> {{
                              $row[
                                "warning"
                              ]
                            }}</span
                          >
                        @endif
                      </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-0.5">
                      <button
                        type="button"
                        role="switch"
                        aria-checked="{{ $row['is_active'] ? 'true' : 'false' }}"
                        wire:click="toggleActive({{ $row['id'] }})"
                        class="relative mr-1 inline-flex h-6 w-10 items-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty focus-visible:ring-offset-2 {{ $row['is_active'] ? 'bg-foresty' : 'bg-gray-300' }}"
                        title="{{ $row['is_active'] ? 'Tampil di situs. Klik untuk menyembunyikan.' : 'Disembunyikan. Klik untuk menampilkan.' }}"
                        aria-label="Tampil di situs"
                        data-nav-toggle
                      >
                        <span
                          class="inline-block h-5 w-5 rounded-full bg-white shadow transition {{ $row['is_active'] ? 'translate-x-[1.1rem]' : 'translate-x-0.5' }}"
                        ></span>
                      </button>
                      <button
                        type="button"
                        wire:click="edit({{ $row['id'] }})"
                        class="{{ $iconBtn }}"
                        title="Ubah"
                        aria-label="Ubah menu"
                      >
                        <x-content.icon name="pencil" />
                      </button>
                      <button
                        type="button"
                        wire:click="delete({{ $row['id'] }})"
                        wire:confirm="Yakin ingin menghapus menu ini?"
                        class="{{ $iconBtn }} hover:!bg-red-50 hover:!text-red-600"
                        title="Hapus"
                        aria-label="Hapus menu"
                      >
                        <x-content.icon name="trash" />
                      </button>
                    </div>
                  </div>
                @endif
              </li>
            @endforeach
          </ul>

          @if ($total > 1)
            <p class="px-1 text-xs text-gray-400">Geser ikon titik di kiri untuk mengubah urutan. Urutan langsung tersimpan.</p>
          @endif
        </div>

        {{-- Pratinjau menu --}}
        <aside
          class="flex flex-col gap-2 lg:sticky lg:top-24"
          aria-label="Pratinjau menu"
          data-nav-aside
        >
          <p class="flex items-center gap-1.5 text-sm font-semibold text-gray-700"><x-content.icon name="eye" /> Pratinjau <x-content.preview-lang :locales="$activeLocales" /></p>
          @foreach ($activeLocales as $l)
            <div
              x-show="pv === '{{ $l }}'"
              class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-md"
              data-nav-mini="{{ $l }}"
              wire:key="nav-mini-{{ $l }}"
            >
              <div class="flex items-center gap-2 px-4 py-3">
                <span
                  class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-[10px] font-black"
                  style="background: {{ $pvBg }}; color: {{ $pvGold }}"
                  aria-hidden="true"
                  >YS</span
                >
                <b class="text-xs">Sinar Bhakti Husada</b>
              </div>
              <ul
                class="flex flex-wrap gap-x-4 gap-y-1.5 border-t border-gray-100 px-4 py-3 text-xs font-semibold text-gray-700"
              >
                @php $any = false; @endphp
                @foreach ($rows as $row)
                  @continue (!$row["is_active"])
                  @php
                    $any = true;
                    $text =
                      ($row["labels"][$l] ?? "") !== ""
                        ? $row["labels"][$l]
                        : $row["labels"][$defaultLocale] ?? "";
                  @endphp
                  <li
                    class="{{ $formOpen && $editId === $row['id'] ? 'text-foresty underline decoration-2 underline-offset-4' : '' }}"
                    wire:key="nav-mini-{{ $l }}-{{ $row['id'] }}"
                  >
                    {{
                      $text !== ""
                        ? $text
                        : "—"
                    }}
                  </li>
                @endforeach
                @unless ($any)
                  <li class="font-normal text-gray-400">
                    Belum ada menu yang tampil
                  </li>
                @endunless
              </ul>
            </div>
          @endforeach
          @if ($total > $shown)
            <p class="text-xs text-gray-500">{{ $total - $shown }} menu disembunyikan, tidak ikut tampil di atas.</p>
          @endif
          <p class="text-xs text-gray-400">Tampilan ringkas. Tata letak asli ada di situs.</p>
        </aside>
      </div>
    </div>

    @if ($canFooter)
      {{-- lazy: dimuat setelah tab Footer ditampilkan (elemen tersembunyi belum "terlihat"), jadi tab Header tidak menanggung bebannya. Footer hanya diatur di sini. --}}
      <div x-show="tab === 'footer'" x-cloak class="w-full" data-footer-tab>
        <livewire:content.footer-editor lazy wire:key="footer-editor" />
      </div>
    @endif
  </div>
</x-main-wrapper>
