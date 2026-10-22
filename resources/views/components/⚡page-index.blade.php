<?php

use App\Content\Languages;
use App\Livewire\Traits\WithNotifications;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
  use WithNotifications;
  use WithPagination;

  private const PER_PAGE = 20;
  private const SORTABLE = ["title", "created_at", "status"];
  private const STATUSES = ["online", "offline"];

  // Hanya diubah oleh sortBy() di server; klien tidak bisa mengisinya langsung.
  #[Locked]
  public string $orderColumn = "created_at";

  #[Locked]
  public string $orderDirection = "desc";

  #[Url]
  public string $statusFilter = "";

  #[Url]
  public string $titleSearch = "";

  #[Computed]
  public function isStaff(): bool
  {
    return (bool) auth()
      ->user()
      ?->hasAnyRole(["admin", "editor"]);
  }

  // Kembali ke halaman 1 setiap kali saringan berubah.
  public function updated(string $name): void
  {
    if (in_array($name, ["titleSearch", "statusFilter"], true)) {
      $this->resetPage();
    }
  }

  #[Computed]
  public function pages()
  {
    // Hanya kolom yang dipakai tabel (kolom content yang besar tidak ikut).
    $columns = [
      "pages.id",
      "pages.title",
      "pages.slug",
      "pages.status",
      "pages.created_at",
    ];
    if (Schema::hasColumn("pages", "user_id")) {
      $columns[] = "pages.user_id";
    }
    $query = Page::query()->select($columns);

    // 1. Pencarian judul: judul berupa JSON per bahasa, dicari di semua bahasa. Kolom JSON MariaDB
    //    memakai collation biner (peka huruf besar/kecil), jadi kedua sisi diubah ke huruf kecil.
    $term = mb_strtolower(trim($this->titleSearch));
    if ($term !== "") {
      $like = "%" . addcslashes($term, "%_\\") . "%";
      $query->where(function ($w) use ($like) {
        foreach (Languages::fromConfig()["locales"] as $code) {
          $w->orWhereRaw(
            "LOWER(JSON_UNQUOTE(JSON_EXTRACT(pages.title, ?))) LIKE ?",
            ['$."' . $code . '"', $like],
          );
        }
      });
    }

    // 2. Status (hanya nilai yang dikenal)
    if (in_array($this->statusFilter, self::STATUSES, true)) {
      $query->where("pages.status", $this->statusFilter);
    }

    // 3. Urutan
    $direction = $this->orderDirection === "asc" ? "asc" : "desc";
    if ($this->orderColumn === "title") {
      $this->sortByJsonText($query, "pages.title", $direction);
    } else {
      $query->orderBy("pages." . $this->orderColumn, $direction);
    }

    $paginator = $query->orderByDesc("pages.id")->paginate(self::PER_PAGE);

    // Judul dan path untuk tampilan: bahasa aktif, lalu id, lalu en.
    $paginator->getCollection()->each(function ($page) {
      $page->setAttribute(
        "display_title",
        $this->pickText($page->getRawOriginal("title"), "(tanpa judul)"),
      );
      $page->setAttribute(
        "display_path",
        $this->pickText($page->getRawOriginal("slug"), ""),
      );
    });

    return $paginator;
  }

  public function sortBy(string $column): void
  {
    if (!in_array($column, self::SORTABLE, true)) {
      return;
    }

    if ($this->orderColumn === $column) {
      $this->orderDirection = $this->orderDirection === "asc" ? "desc" : "asc";
    } else {
      $this->orderColumn = $column;
      $this->orderDirection = "asc";
    }
    $this->resetPage();
  }

  // Urutan teks dari kolom JSON per bahasa: tak membedakan huruf besar/kecil, dan memakai id/en bila bahasa aktif kosong.
  private function sortByJsonText(
    $query,
    string $column,
    string $direction,
  ): void {
    $parts = [];
    $bindings = [];
    foreach (array_unique([app()->getLocale(), "id", "en"]) as $code) {
      $parts[] = "NULLIF(JSON_UNQUOTE(JSON_EXTRACT({$column}, ?)), '')";
      $bindings[] = '$."' . $code . '"';
    }
    // JSON_UNQUOTE di MariaDB mengembalikan utf8mb3, jadi diubah dulu ke utf8mb4 sebelum diberi collation.
    $query->orderByRaw(
      "CONVERT(COALESCE(" .
        implode(", ", $parts) .
        ") USING utf8mb4) COLLATE utf8mb4_unicode_ci {$direction}",
      $bindings,
    );
  }

  // Teks dari kolom JSON per bahasa (atau teks biasa bila kolomnya bukan JSON).
  private function pickText(?string $raw, string $fallback): string
  {
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
      return trim((string) $raw) !== "" ? (string) $raw : $fallback;
    }
    foreach (array_unique([app()->getLocale(), "id", "en"]) as $code) {
      if (!empty($data[$code]) && is_string($data[$code])) {
        return $data[$code];
      }
    }

    return $fallback;
  }

  public function deletePage(int $identifier): void
  {
    $user = auth()->user();
    $page = Page::find($identifier);

    // 1. GUARD: eksistensi
    if (!$user || !$page) {
      $this->notify("Halaman tidak ditemukan.", "error");
      return;
    }

    // 2. GUARD: halaman yang sedang tayang tidak dihapus langsung (menu/tautan publik bisa putus)
    if ($page->status === "online") {
      $this->notify(
        "Halaman masih online. Ubah ke offline dulu sebelum menghapus.",
        "error",
      );
      return;
    }

    // 3. GUARD: admin/editor, atau pemilik halaman (bila tabel punya user_id)
    $ownerId = $page->getAttributes()["user_id"] ?? null;
    $isOwner = $ownerId !== null && (int) $ownerId === (int) $user->id;
    if (!$this->isStaff && !$isOwner) {
      $this->notify(
        "Anda tidak memiliki otorisasi untuk menghapus halaman ini.",
        "error",
      );
      return;
    }

    // 4. Catat log lalu hapus dalam satu transaksi: bila salah satu gagal, tidak ada yang berubah.
    DB::transaction(function () use ($page, $user) {
      activity("page_updates")
        ->performedOn($page)
        ->causedBy($user)
        ->withProperties([
          "title" =>
            json_decode((string) $page->getRawOriginal("title"), true) ??
            $page->getRawOriginal("title"),
          "status_saat_dihapus" => $page->status,
        ])
        ->log("Halaman dihapus permanen");

      $page->delete();
    });

    $this->notify("Halaman berhasil dihapus.", "success");
  }
};
?>

<x-slot:title>
  {{
    __(
      "ui.header.page",
    )
  }}
</x-slot:title>
{{-- <x-main-wrapper> --}}
<div
  class="mt-4 flex h-full max-h-screen min-h-0 w-full min-w-0 flex-col items-center justify-start gap-6 overflow-x-hidden overflow-y-auto"
>
  <div
    x-data="{
      activeSubPanel: 'none',
      showDeleteModal: false,
      deleteId: null,
    }"
    class="flex w-full max-w-7xl flex-col items-start gap-4 rounded-xl bg-white p-4 lg:flex-row"
  >
    <!-- MAIN UX (KONTAINER UTAMA) -->
    <div
      x-bind:class="
        activeSubPanel !== 'none'
          ? 'w-full lg:w-2/3 transition-all duration-300'
          : 'w-full transition-all duration-300'
      "
      class="min-w-0"
    >
      {{-- HEADER --}}
      <div
        class="flex flex-col items-start justify-between gap-3 px-2 pb-4 sm:flex-row sm:items-center"
      >
        <span
          class="text-foresty text-2xl font-bold dark:text-zinc-100"
          >{{ __("Index") }}</span
        >

        <!-- Group Tombol Navigasi/Aksi -->
        <div class="flex flex-wrap items-center gap-2 self-end sm:self-auto">
          <!-- Kode Tombol Anda -->
          <a
            href="{{ route('navigation.index') }}"
            wire:navigate
            class="group hover:bg-foresty hover:text-goldy inline-flex cursor-pointer items-center gap-3 overflow-hidden rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-600 shadow-sm transition-colors"
          >
            <x-dynamic-component
              :component="'lucide-menu'"
              class="group-hover:animate-stroke h-5 w-5 origin-bottom-left"
              stroke-width="2"
            />
            {{
              __(
                "Sort Navigation",
              )
            }}
          </a>
          <a
            {{-- href="{{ route('page.create') }}" --}}
            href="{{ route('page.create') }}"
            wire:navigate
            class="group hover:bg-foresty hover:text-goldy inline-flex cursor-pointer items-center gap-2 overflow-hidden rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-600 shadow-sm transition-colors"
          >
            <x-dynamic-component
              :component="'lucide-panels-top-left'"
              class="group-hover:animate-stroke h-5 w-5 origin-bottom-left"
              stroke-width="2"
            />
            {{ __("Create") }}
          </a>
        </div>
      </div>

      <!-- BARIS FILTER SEDERHANA-->
      <div
        x-data="{statusSelected: @entangle('statusFilter').live }"
        class="mb-4 grid grid-cols-1 gap-3 rounded-xl border border-zinc-200 bg-white px-2 py-4 shadow-sm md:grid-cols-4 dark:border-zinc-800 dark:bg-zinc-900"
      >
        <!-- 1. Input Pencarian -->
        <div class="relative col-span-2 md:col-span-3">
          <div
            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400"
          >
            <flux:icon
              variant="outline"
              icon="magnifying-glass"
              class="h-4 w-4"
            />
          </div>
          <input
            type="text"
            placeholder="Cari halaman..."
            class="focus:ring-sbh-green w-full rounded-lg border border-zinc-200 bg-zinc-50 py-2 pr-4 pl-9 text-sm text-zinc-700 focus:border-transparent focus:ring-2 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
            wire:model.live.debounce.300ms="titleSearch"
          />
        </div>
        <div class="col-span-2 flex flex-row gap-2 md:col-span-1">
          <button
            type="button"
            x-on:click="
              statusSelected = statusSelected === 'online' ? '' : 'online'
            "
            x-bind:class="
              statusSelected === 'online'
                ? 'bg-misty text-foresty'
                : 'bg-zinc-50 text-forest '
            "
            class="hover:bg-misty flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-zinc-200 p-1.5 text-sm select-none focus:outline-none"
          >
            <x-dynamic-component
              :component="'lucide-globe-check'"
              class="group-hover:animate-stroke h-5 w-5 origin-bottom-left"
              stroke-width="2"
            />
            <span> Online </span>
          </button>
          <button
            type="button"
            x-on:click="
              statusSelected = statusSelected === 'offline' ? '' : 'offline'
            "
            x-bind:class="
              statusSelected === 'offline'
                ? 'bg-misty text-foresty'
                : 'bg-zinc-50 text-forest '
            "
            class="hover:bg-misty flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-zinc-200 p-1.5 text-sm select-none focus:outline-none"
          >
            <x-dynamic-component
              :component="'lucide-globe-off'"
              class="group-hover:animate-stroke h-5 w-5 origin-bottom-left"
              stroke-width="2"
            />
            <span> Offline </span>
          </button>
        </div>
      </div>

      {{-- REVISI: Penambahan komputasi max-height responsif agar di mobile tidak meluber ke bawah --}}
      <!-- TABEL UTAMA -->
      <div
        class="max-h-[calc(100vh-450px)] w-full overflow-x-auto overflow-y-auto rounded-xl border border-zinc-200 shadow-sm lg:max-h-[calc(100vh-280px)] dark:border-zinc-700"
      >
        <table class="w-full min-w-3xl table-fixed border-collapse text-left">
          <thead
            class="bg-misty text-foresty text-xs dark:bg-green-950 dark:text-green-300"
          >
            <tr>
              <!-- Header Judul -->
              <th
                class="bg-misty border-sage-soft sticky top-0 z-10 w-2/5 border-r-2 px-4 py-3 font-semibold tracking-wider uppercase lg:z-20"
              >
                <button
                  wire:click="sortBy('title')"
                  class="flex w-full cursor-pointer items-center gap-2 font-semibold tracking-wider uppercase transition-colors hover:text-zinc-200"
                >
                  Judul Halaman
                  @if ($orderColumn === "title")
                    <flux:icon
                      variant="solid"
                      icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}"
                      class="size-4"
                    />
                  @endif
                </button>
              </th>
              <!-- PATH -->
              <th
                class="bg-misty border-sage-soft sticky top-0 z-10 border-r-2 px-4 py-3 font-semibold tracking-wider uppercase select-none lg:z-20"
              >
                {{-- <button wire:click="sortBy('title')" class="flex justify-center items-center gap-2 w-full uppercase tracking-wider font-semibold cursor-pointer hover:text-zinc-200 transition-colors"> --}}
                Path
                {{-- @if($orderColumn === 'title')
                                        <flux:icon variant="solid" icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                    @endif --}}
                {{-- </button> --}}
              </th>

              <!-- Tanggal -->
              <th
                class="bg-misty border-sage-soft sticky top-0 z-10 border-r-2 px-4 py-3 font-semibold tracking-wider whitespace-nowrap uppercase lg:z-20"
              >
                <button
                  wire:click="sortBy('created_at')"
                  class="flex w-full cursor-pointer items-center justify-center gap-2 font-semibold tracking-wider uppercase transition-colors hover:text-zinc-200"
                >
                  Tanggal & Waktu dibuat
                  @if ($orderColumn === "created_at")
                    <flux:icon
                      variant="solid"
                      icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}"
                      class="size-4"
                    />
                  @endif
                </button>
              </th>

              <!-- STATUS -->
              <th
                class="bg-misty border-sage-soft sticky top-0 z-10 border-r-2 px-4 py-3 font-semibold tracking-wider uppercase lg:z-20"
              >
                <button
                  wire:click="sortBy('status')"
                  class="flex w-full cursor-pointer items-center justify-center gap-2 font-semibold tracking-wider uppercase transition-colors hover:text-zinc-200"
                >
                  Status
                  @if ($orderColumn === "status")
                    <flux:icon
                      variant="solid"
                      icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}"
                      class="size-4"
                    />
                  @endif
                </button>
              </th>

              <!-- Header Kelola (Tidak perlu sorting) -->
              <th
                class="bg-misty sticky top-0 z-10 px-4 py-3 text-center font-semibold tracking-wider uppercase select-none lg:z-20"
              >
                Kelola
              </th>
            </tr>
          </thead>
          <tbody
            class="divide-y divide-zinc-200 bg-white text-zinc-700 dark:divide-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
          >
            @forelse ($this->pages as $page)
              @php
                $ownerId = $page->getAttributes()["user_id"] ?? null;
                $canDelete =
                  $page->status !== "online" &&
                  ($this->isStaff ||
                    ($ownerId !== null && (int) $ownerId === (int) auth()->id()));
                $statusLabel =
                  ["online" => "Online", "offline" => "Offline"][$page->status] ??
                  ucfirst((string) $page->status);
              @endphp
              <tr
                wire:key="page-{{ $page->id }}"
                class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
              >
                <!-- TITLE -->
                <td class="px-4 py-3.5 text-sm">
                  <div
                    class="truncate font-medium text-zinc-900 dark:text-white"
                    title="{{ $page->display_title }}"
                  >
                    {{ $page->display_title }}
                  </div>
                </td>

                <!-- PATH -->
                <td class="px-4 py-3.5 text-sm">
                  <div class="truncate" title="/{{ $page->display_path }}">
                    /{{ $page->display_path }}
                  </div>
                </td>

                <!-- DATE -->
                <td class="px-4 py-3.5 text-sm whitespace-nowrap">
                  {{
                    $page->created_at instanceof
                    \DateTimeInterface
                      ? $page->created_at->format("d/m/Y H:i")
                      : $page->created_at
                  }}
                </td>

                <!-- STATUS -->
                <td class="px-2 py-3.5 text-center text-sm">
                  <span
                    class="px-2 py-1 text-xs font-medium rounded-full border-2 {{ $page->status_color ?? '' }}"
                  >
                    {{ $statusLabel }}
                  </span>
                </td>

                {{-- BUTTONS  --}}
                <td class="px-4 py-3.5 text-sm">
                  <div class="flex items-center justify-center gap-2">
                    <a
                      wire:navigate
                      {{-- href="{{ route('page.edit', ['pageSlug' => $slugCantik]) }}" --}}
                      href="{{ route('page.edit', $page->id) }}"
                      class="group bg-forest/90 dark:bg-forest/80 hover:bg-forest/70 relative flex cursor-pointer items-center justify-center rounded-md p-1.5 text-white transition-colors"
                    >
                      <flux:icon
                        variant="solid"
                        icon="pencil"
                        class="size-3.5!"
                      />
                      <span
                        class="pointer-events-none absolute bottom-full left-0 z-30 mb-2 w-max rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity duration-200 group-hover:opacity-100 dark:bg-gray-100 dark:text-gray-900"
                      >
                        Sunting

                        <svg class="absolute top-full left-2 h-2 w-4 text-gray-900 dark:text-gray-100" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                          <polygon class="fill-current" points="0,0 127.5,127.5 255,0" />
                        </svg>
                      </span>
                    </a>

                    <a
                      wire:navigate
                      {{-- href="{{ route('page.preview', ['pageSlug' => $slugCantik]) }}" --}}
                      href="{{ route('preview.record', ['type' => 'page', 'id' => $page->id ]) }}"
                      class="group relative flex cursor-pointer items-center justify-center rounded-md bg-slate-600 p-1.5 text-white transition-colors hover:bg-slate-700 dark:bg-slate-800"
                    >
                      <flux:icon variant="solid" icon="eye" class="size-3.5!" />
                      <span
                        class="pointer-events-none absolute bottom-full left-1/2 z-30 mb-2 w-max -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity duration-200 group-hover:opacity-100 dark:bg-gray-100 dark:text-gray-900"
                      >
                        Pratinjau
                        <svg class="absolute top-full left-0 h-2 w-full text-gray-900 dark:text-gray-100" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                          <polygon class="fill-current" points="0,0 127.5,127.5 255,0" />
                        </svg>
                      </span>
                    </a>

                    @if ($canDelete)
                      <button
                        @click="deleteId = {{ $page->id }}; showDeleteModal = true"
                        type="button"
                        class="group relative flex cursor-pointer items-center justify-center rounded-md bg-red-600 p-1.5 text-white transition-colors hover:bg-red-700 dark:bg-red-800"
                      >
                        <flux:icon
                          variant="solid"
                          icon="trash"
                          class="size-3.5!"
                        />

                        <span
                          class="pointer-events-none absolute right-0 bottom-full z-30 mb-2 w-max rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity duration-200 group-hover:opacity-100 dark:bg-gray-100 dark:text-gray-900"
                        >
                          Hapus

                          <svg class="absolute top-full right-2 h-2 w-4 text-gray-900 dark:text-gray-100" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                            <polygon class="fill-current" points="0,0 127.5,127.5 255,0" />
                          </svg>
                        </span>
                      </button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              {{-- INI AKAN MUNCUL JIKA TIDAK ADA DATA ARTIKEL --}}
              <tr>
                <td colspan="5" class="px-4 py-12 text-center">
                  <div class="flex flex-col items-center justify-center">
                    <flux:icon
                      variant="outline"
                      icon="document-text"
                      class="mb-2 size-8 text-zinc-400"
                    />
                    <span
                      class="text-sm font-medium text-zinc-500 dark:text-zinc-400"
                      >Tidak ada halaman yang ditemukan.</span
                    >
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4 px-2">{{ $this->pages->links() }}</div>
    </div>

    <!-- SECONDARY UX (KONTAINER KEDUA - PANEL/POPUP) -->

    <!-- DELETE CONFIRMATION MODAL -->
    <div
      x-show="showDeleteModal"
      class="relative z-99"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
      x-cloak
    >
      <div
        x-show="showDeleteModal"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm transition-opacity"
      ></div>

      <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div
          class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
        >
          <div
            x-show="showDeleteModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            @click.away="showDeleteModal = false"
            class="relative w-full max-w-sm transform overflow-hidden rounded-2xl bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:p-6 dark:bg-zinc-900"
          >
            <div>
              <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30"
              >
                <flux:icon
                  variant="outline"
                  icon="exclamation-triangle"
                  class="text-terracotta h-6 w-6 dark:text-red-400"
                />
              </div>
              <div class="mt-3 text-center sm:mt-5">
                <h3
                  class="text-base leading-6 font-bold text-zinc-900 dark:text-white"
                  id="modal-title"
                >
                  Hapus Halaman
                </h3>
                <div class="mt-2">
                  <p class="text-sm text-zinc-500 dark:text-zinc-400">Apakah Anda yakin ingin menghapus halaman ini? Data yang sudah dihapus tidak dapat dikembalikan.</p>
                </div>
              </div>
            </div>
            <div class="mt-5 flex flex-col gap-3 sm:mt-6 sm:flex-row-reverse">
              <button
                type="button"
                class="bg-sage-soft text-forest inline-flex w-full cursor-pointer justify-center rounded-xl px-3 py-2 text-sm font-semibold shadow-sm transition-colors hover:bg-red-600 hover:text-white sm:w-auto"
                @click="
                  $wire.deletePage(deleteId);
                  deleteId = null;
                  showDeleteModal = false;
                "
              >
                Ya, Hapus
              </button>
              <button
                type="button"
                @click="
                  deleteId = null;
                  showDeleteModal = false;
                "
                class="inline-flex w-full cursor-pointer justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-zinc-900 shadow-sm ring-1 ring-zinc-300 transition-colors ring-inset hover:bg-zinc-50 sm:w-auto dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700 dark:hover:bg-zinc-700"
              >
                Batal
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
{{-- </x-main-wrapper> --}}
