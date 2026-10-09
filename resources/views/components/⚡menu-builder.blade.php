<?php

/**
 * Menu Builder (rilis 28): dibangun ulang mengikuti konvensi kit. OPSIONAL dan milik Anda: salin isinya MENIMPA berkas menu builder lama
 * (jalur dan nama komponen Anda tetap, jadi rute tidak berubah). Pemasang tidak menyentuhnya.
 *
 * Bedanya dengan versi lama:
 *  - Bahasa dari config (Languages::fromConfig: app.supported_locales + cms.default_locale), bukan "id"/"en" tertulis. Label bahasa bawaan
 *    wajib; bahasa lain boleh kosong dan memakai label bahasa bawaan.
 *  - Tujuan = SATU kolom `url` untuk semua bahasa (docs/BAHASA.md): pilih Beranda, Daftar artikel, Halaman CMS (dicari dari judul),
 *    URL, Email, Telepon, atau Anchor. Landing menerjemahkannya ke bahasa pembaca (NavLinks); pratinjau di bawah menunjukkan alamat tiap bahasa.
 *    `route_name` tidak lagi diisi (rute statis lama sudah dihapus rilis 23). Baris lama yang masih membawa rute mati ditandai dan harus dipilihkan tujuan baru.
 *  - Alamat divalidasi dengan aturan yang sama dengan tombol (LinkResolver): hanya https://, /jalur, mailto:, tel:, #anchor.
 *  - Baris yang menuju halaman yang belum online/tidak ada diberi tanda (tautan itu akan 404).
 *  - Simpan, hapus, dan urutan memeriksa login, memakai transaksi, dan memberi notifikasi (WithNotifications); urutan hanya menulis baris yang berubah.
 * Logika yang bisa diuji ada di App\Content\MenuTarget (tests/menu-target-test.php). Komponen ini sendiri belum dijalankan di Livewire sungguhan.
 */

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
          $t["kind"] === "page" &&
          $t["ref"] !== "" &&
          PublicLookup::sibling(
            "page",
            $t["ref"],
            $this->defaultLocale,
            $this->activeLocales,
            $this->defaultLocale,
          ) === null;

        return [
          "id" => (int) $menu->id,
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
                ? "Halaman ini belum online atau tidak ditemukan: tautannya akan 404."
                : null),
          "is_active" => (bool) $menu->is_active,
        ];
      })
      ->all();
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

  public function resetForm(): void
  {
    $this->reset(["editId", "ref", "pageQuery", "legacyRoute"]);
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
  $kindLabels = [
    "page" => "Halaman CMS",
    "home" => "Beranda",
    "articles" => "Daftar artikel",
    "url" => "URL khusus",
    "mailto" => "Email",
    "tel" => "Telepon",
    "anchor" => "Anchor (#)",
  ];
  $placeholders = [
    "url" => "https://… atau /jalur",
    "mailto" => "nama@contoh.org",
    "tel" => "+62 …",
    "anchor" => "kontak",
  ];
@endphp

<x-slot:title>
  {{
    __(
      "Menu Builder",
    )
  }}
</x-slot:title>

<x-main-wrapper>
  <div class="mb-4 flex w-full items-center justify-between">
    <div class="text-2xl font-bold">{{ __("Navigasi") }}</div>
  </div>

  <div
    class="flex max-h-full w-full max-w-screen flex-col gap-8 overflow-x-auto overflow-y-auto rounded-xl md:flex-row"
  >
    {{-- KOLOM KIRI: FORMULIR --}}
    <div class="flex w-full flex-col gap-4">
      <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-foresty mb-4 text-lg font-bold">
          {{
            $editId
              ? "Edit Menu"
              : "Tambah Menu Baru"
          }}
        </h2>

        @if ($legacyRoute !== "")
          <div
            class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800"
          >
            Baris ini memakai rute lama <b>{{ $legacyRoute }}</b> yang sudah
            tidak ada. Pilih tujuan baru di bawah, lalu simpan.
          </div>
        @endif

        <form wire:submit="save" class="space-y-4">
          @foreach ($activeLocales as $loc)
            <div wire:key="label-{{ $loc }}">
              <label class="mb-1 block text-xs font-bold text-gray-500">
                Label ({{ strtoupper($loc) }}){{
                  $loc === $defaultLocale
                    ? " *"
                    : ""
                }}
              </label>
              <input
                type="text"
                wire:model="labels.{{ $loc }}"
                maxlength="120"
                placeholder="{{ $loc === $defaultLocale ? 'Wajib diisi' : 'Kosong = memakai label ' . strtoupper($defaultLocale) }}"
                class="focus:ring-foresty w-full rounded-md border-gray-300 text-sm"
              />
              @error ("labels." . $loc)
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>
          @endforeach

          <div class="border-t border-gray-100 pt-4">
            <label class="mb-1 block text-xs font-bold text-gray-500"
              >Tujuan tautan</label
            >
            <p class="mb-2 text-[10px] text-gray-400">Satu tujuan untuk semua bahasa: pembaca otomatis dibawa ke versi bahasanya.</p>

            <select
              wire:model.live="kind"
              class="mb-3 w-full rounded-md border-gray-300 bg-gray-50 text-sm"
            >
              @foreach ($kindLabels as $value => $text)
                <option value="{{ $value }}">{{ $text }}</option>
              @endforeach
            </select>

            @if ($kind === "page")
              <input
                type="search"
                wire:model.live.debounce.300ms="pageQuery"
                placeholder="Cari judul halaman (min. 2 huruf)…"
                class="w-full rounded-md border-gray-300 bg-gray-50 text-sm"
                autocomplete="off"
              />
              @if (count($this->pageResults) > 0)
                <ul
                  class="mt-1 divide-y divide-gray-100 rounded-md border border-gray-200 bg-white text-sm"
                >
                  @foreach ($this->pageResults as $r)
                    <li wire:key="pg-{{ $r['id'] }}">
                      <button
                        type="button"
                        wire:click="pickPage({{ $r['id'] }})"
                        class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50"
                      >
                        <span>{{ $r["label"] }}</span>
                        <span
                          class="text-[10px] text-gray-400"
                          >{{ $r["hint"] }}</span
                        >
                      </button>
                    </li>
                  @endforeach
                </ul>
              @endif
              <p class="mt-2 text-xs text-gray-600">
                Dipilih:
                @if ($ref !== "")
                  <code class="rounded bg-gray-100 px-1">/{{ $ref }}</code>
                @else
                  <span class="text-gray-400">belum ada</span>
                @endif
              </p>
            @elseif (in_array( $kind, ["url", "mailto", "tel", "anchor"], true ))
              <input
                type="text"
                wire:model.live.debounce.400ms="ref"
                placeholder="{{ $placeholders[$kind] ?? '' }}"
                class="w-full rounded-md border-gray-300 bg-gray-50 text-sm"
              />
            @endif
            @error ("ref")
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror

            @if (count($this->preview) > 0)
              <div class="mt-3 rounded-md bg-gray-50 p-3">
                <p class="mb-1 text-[10px] font-bold text-gray-400 uppercase">Yang dilihat pembaca</p>
                @foreach ($this->preview as $loc => $address)
                  <p
                    class="font-mono text-xs break-all text-gray-600"
                    wire:key="pv-{{ $loc }}"
                  ><b>{{
                    strtoupper(
                      $loc,
                    )
                  }}</b> {{ $address }}</p>
                @endforeach
              </div>
            @endif
          </div>

          <div class="flex items-center gap-2 border-t border-gray-100 pt-4">
            <input
              type="checkbox"
              wire:model="is_active"
              id="isActive"
              class="text-foresty focus:ring-foresty rounded"
            />
            <label for="isActive" class="text-sm font-medium text-gray-600"
              >Tampilkan di Publik</label
            >
          </div>

          <div class="flex gap-2 pt-4">
            <button
              type="submit"
              class="bg-foresty flex-1 rounded-md py-2 text-sm font-bold text-white transition-colors hover:bg-[#043b2c]"
            >
              {{
                $editId
                  ? "Simpan Perubahan"
                  : "Tambahkan"
              }}
            </button>
            @if ($editId)
              <button
                type="button"
                wire:click="resetForm"
                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-200"
              >
                Batal
              </button>
            @endif
          </div>
        </form>
      </div>
    </div>

    {{-- KOLOM KANAN: DAFTAR MENU (DRAG & DROP) --}}
    <div class="flex max-h-full min-h-full w-full flex-col">
      <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <div
          class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-3"
        >
          <span class="text-xs font-bold text-gray-500 uppercase"
            >Susunan Menu Saat Ini</span
          >
          <span class="text-[10px] text-gray-400"
            >Geser ikon garis untuk mengubah urutan</span
          >
        </div>

        <ul
          x-data
          x-sort="$wire.updateOrder($item, $position)"
          class="max-h-full divide-y divide-gray-100 overflow-auto pb-12"
        >
          @forelse ($this->rows as $row)
            <li
              x-sort:item="{{ $row['id'] }}"
              wire:key="menu-{{ $row['id'] }}"
              class="group flex items-center justify-between bg-white p-4 transition-colors hover:bg-gray-50"
            >
              <div class="flex items-center gap-4">
                <button
                  type="button"
                  x-sort:handle
                  class="hover:text-foresty cursor-grab touch-none text-gray-400 active:cursor-grabbing"
                  title="Geser"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                </button>

                <div>
                  <h4
                    class="font-bold text-sm text-gray-800 {{ ! $row['is_active'] ? 'line-through opacity-50' : '' }}"
                  >
                    @foreach ($activeLocales as $loc)
                      @if ($loop->first)
                        {{
                          $row["labels"][$loc] !== ""
                            ? $row["labels"][$loc]
                            : "—"
                        }}
                      @else
                        <span class="ml-1 text-xs font-normal text-gray-400"
                          >/ {{
                            $row["labels"][$loc] !== ""
                              ? $row["labels"][$loc]
                              : "N/A"
                          }}</span
                        >
                      @endif
                    @endforeach
                  </h4>
                  <p class="mt-0.5 font-mono text-[10px] text-gray-400">{{ $row["target"] }}</p>
                  @if ($row["warning"])
                    <p class="mt-0.5 text-[10px] text-amber-600">⚠ {{ $row["warning"] }}</p>
                  @endif
                </div>
              </div>

              <div
                class="flex items-center gap-2 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100"
              >
                <button
                  type="button"
                  wire:click="edit({{ $row['id'] }})"
                  class="rounded p-1.5 text-blue-600 hover:bg-blue-50"
                  title="Edit"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </button>
                <button
                  type="button"
                  wire:click="delete({{ $row['id'] }})"
                  wire:confirm="Yakin ingin menghapus menu ini?"
                  class="rounded p-1.5 text-red-600 hover:bg-red-50"
                  title="Hapus"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
              </div>
            </li>
          @empty
            <li class="p-8 text-center text-sm text-gray-400">
              Belum ada menu navigasi yang ditambahkan.
            </li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</x-main-wrapper>
