<?php

use App\Content\Languages;
use App\Livewire\Traits\WithNotifications;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
  use WithNotifications;
  use WithPagination;

  private const PER_PAGE = 20;
  private const SORTABLE = ["title", "created_at", "category", "status"];
  private const DELETABLE = ["draft", "rejected"];

  // Hanya diubah oleh sortBy() di server; klien tidak bisa mengisinya langsung.
  #[Locked]
  public string $orderColumn = "created_at";

  #[Locked]
  public string $orderDirection = "desc";

  #[Url]
  public string $titleSearch = "";

  #[Url]
  public string $selectCategory = "";

  #[Url]
  public string $selectStatus = "";

  #[Computed]
  public function statusList()
  {
    return collect([
      (object) ["id" => "draft", "name" => "Draft", "icon" => "file-pen-line"],
      (object) ["id" => "review", "name" => "Review", "icon" => "file-clock"],
      (object) ["id" => "published", "name" => "Published", "icon" => "globe"],
      (object) [
        "id" => "scheduled",
        "name" => "Terjadwal",
        "icon" => "calendar-clock",
      ],
      (object) [
        "id" => "archived",
        "name" => "Diarsipkan",
        "icon" => "folder-archive",
      ],
      (object) ["id" => "rejected", "name" => "Ditolak", "icon" => "file-x"],
    ])->keyBy("id");
  }

  #[Computed]
  public function categoryList()
  {
    return Category::orderBy("id")->get();
  }

  #[Computed]
  public function isStaff(): bool
  {
    return (bool) auth()
      ->user()
      ?->hasAnyRole(["admin", "editor"]);
  }

  #[Computed]
  public function deletableStatuses(): array
  {
    return self::DELETABLE;
  }

  // Kembali ke halaman 1 setiap kali saringan berubah.
  public function updated(string $name): void
  {
    if (
      in_array($name, ["titleSearch", "selectCategory", "selectStatus"], true)
    ) {
      $this->resetPage();
    }
  }

  #[Computed]
  public function articles()
  {
    // Hanya kolom yang dipakai tabel (kolom content yang besar tidak ikut); relasi dimuat sekali (tanpa N+1).
    $query = Post::query()
      ->select([
        "posts.id",
        "posts.title",
        "posts.status",
        "posts.category_id",
        "posts.user_id",
        "posts.created_at",
      ])
      ->with(["author:id,name", "category"]);

    // 1. Pencarian judul: judul berupa JSON per bahasa, jadi dicari di setiap bahasa.
    $term = trim($this->titleSearch);
    if ($term !== "") {
      $like = "%" . addcslashes($term, "%_\\") . "%";
      $query->where(function ($w) use ($like) {
        foreach (Languages::fromConfig()["locales"] as $code) {
          $w->orWhere("posts.title->" . $code, "like", $like);
        }
      });
    }

    // 2. Kategori   3. Status
    if ($this->selectCategory !== "") {
      $query->where("posts.category_id", $this->selectCategory);
    }
    if ($this->selectStatus !== "") {
      $query->where("posts.status", $this->selectStatus);
    }

    // 4. Urutan. leftJoin supaya artikel tanpa kategori tidak hilang; judul/nama diurutkan menurut bahasa aktif.
    $direction = $this->orderDirection === "asc" ? "asc" : "desc";
    $locale = app()->getLocale();
    if ($this->orderColumn === "category") {
      $query
        ->leftJoin("categories", "posts.category_id", "=", "categories.id")
        ->orderBy("categories.name->" . $locale, $direction);
    } elseif ($this->orderColumn === "title") {
      $query->orderBy("posts.title->" . $locale, $direction);
    } else {
      $query->orderBy("posts." . $this->orderColumn, $direction);
    }

    // id sebagai pemutus seri supaya isi halaman tidak bergeser antar permintaan.
    return $query->orderByDesc("posts.id")->paginate(self::PER_PAGE);
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

  public function deleteArticle(int $articleId): void
  {
    $user = auth()->user();
    $article = Post::find($articleId);

    // 1. GUARD: eksistensi
    if (!$user || !$article) {
      $this->notify("Artikel tidak ditemukan.", "error");
      return;
    }

    // 2. GUARD: hanya draft & rejected
    if (!in_array($article->status, self::DELETABLE, true)) {
      $this->notify(
        "Artikel berstatus '{$article->status}' tidak boleh dihapus.",
        "error",
      );
      return;
    }

    // 3. GUARD: admin/editor, atau pemilik artikel
    $isOwner = (int) $article->user_id === (int) $user->id;
    if (!$this->isStaff && !$isOwner) {
      $this->notify(
        "Anda tidak memiliki otorisasi untuk menghapus artikel ini.",
        "error",
      );
      return;
    }

    $cover = $article->featured_image;

    // 4. Catat log lalu hapus dalam satu transaksi: bila salah satu gagal, tidak ada yang berubah.
    DB::transaction(function () use ($article, $user) {
      activity("article_updates")
        ->performedOn($article)
        ->causedBy($user)
        ->withProperties([
          "title" => $article->title,
          "status_saat_dihapus" => $article->status,
        ])
        ->log("Artikel dihapus permanen");

      $article->delete();
    });

    // 5. Berkas sampul dihapus SESUDAH baris terhapus, dan hanya bila tidak dipakai artikel lain.
    //    Gambar di dalam isi tidak lagi dihapus di sini: isi berupa blok JSON, dan gambarnya milik pustaka media.
    if (
      $cover &&
      $cover !== "default.webp" &&
      !Post::where("featured_image", $cover)->exists()
    ) {
      try {
        Storage::disk("public")->delete("articles/" . $cover);
      } catch (\Throwable) {
        // berkas yatim tidak boleh menggagalkan penghapusan artikel
      }
    }

    $this->notify("Artikel berhasil dihapus.", "success");
  }
};
?>

<x-slot:title>
  {{
    __(
      "ui.header.article",
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
      {{-- newItemName: '', --}}
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
      {{-- REVISI: Merapikan header fleksibel --}}
      <div
        class="flex flex-col items-start justify-between gap-3 px-2 pb-4 sm:flex-row sm:items-center"
      >
        <span
          class="text-foresty text-2xl font-bold dark:text-zinc-100"
          >{{ __("Index") }}</span
        >

        <!-- Group Tombol Navigasi/Aksi -->
        <div class="flex flex-wrap items-center gap-2 self-end sm:self-auto">
          <!-- Tombol Kategori (Mengubah state ke 'categories') -->
          <button
            x-data="{
              isAnimating: false,
              playAnim() {
                // 1. Matikan animasi sejenak (reset)
                this.isAnimating = false;

                // 2. Tunggu 1 kedipan sistem (nextTick), lalu nyalakan lagi
                this.$nextTick(() => {
                  this.isAnimating = true;
                  // 3. Matikan otomatis setelah 500ms (sesuai durasi CSS)
                  setTimeout(() => (this.isAnimating = false), 500);
                });
              },
            }"
            x-on:mouseenter="playAnim()"
            x-on:click="
              playAnim();
              activeSubPanel =
                activeSubPanel === 'categories' ? 'none' : 'categories';
            "
            x-bind:class="
              activeSubPanel === 'categories'
                ? 'bg-forest text-goldy'
                : 'bg-white text-foresty'
            "
            class="group hover:bg-foresty hover:text-goldy inline-flex cursor-pointer items-center gap-2 rounded-xl border border-zinc-200 px-4 py-2 text-sm font-semibold shadow-sm transition-colors"
          >
            <!-- Perhatikan perubahan pada origin-bottom dan nama animasinya -->
            <x-dynamic-component
              :component="'lucide-blocks'"
              class="group-hover:animate-blocks h-5 w-5 origin-bottom"
              stroke-width="2"
              x-bind:class="isAnimating ? 'animate-blocks' : ''"
            />
            {{
              __(
                "ui.articles.categories",
              )
            }}
          </button>

          <button
            x-data="{
              isAnimating: false,
              playAnim() {
                // 1. Matikan animasi sejenak (reset)
                this.isAnimating = false;

                // 2. Tunggu 1 kedipan sistem (nextTick), lalu nyalakan lagi
                this.$nextTick(() => {
                  this.isAnimating = true;
                  // 3. Matikan otomatis setelah 500ms (sesuai durasi CSS)
                  setTimeout(() => (this.isAnimating = false), 500);
                });
              },
            }"
            x-on:mouseenter="playAnim()"
            x-on:click="
              playAnim();
              activeSubPanel = activeSubPanel === 'tags' ? 'none' : 'tags';
            "
            x-bind:class="
              activeSubPanel === 'tags'
                ? 'bg-forest text-goldy'
                : 'bg-white text-forest'
            "
            class="group hover:bg-foresty hover:text-goldy inline-flex cursor-pointer items-center gap-2 rounded-xl border border-zinc-200 px-4 py-2 text-sm font-semibold shadow-sm transition-colors"
          >
            <x-dynamic-component
              :component="'lucide-tag'"
              class="group-hover:animate-tag h-5 w-5 origin-top-left"
              stroke-width="2"
              x-bind:class="isAnimating ? 'animate-tag' : ''"
            />
            {{
              __(
                "ui.articles.tags",
              )
            }}
          </button>

          <!-- Kode Tombol Anda -->
          <a
            href="{{ route('article.write') }}"
            wire:navigate
            class="group hover:bg-foresty hover:text-goldy inline-flex cursor-pointer items-center gap-2 overflow-hidden rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-600 shadow-sm transition-colors"
          >
            <x-dynamic-component
              :component="'lucide-feather'"
              class="group-hover:animate-stroke h-5 w-5 origin-bottom-left"
              stroke-width="2"
            />
            {{
              __(
                "ui.button.write",
              )
            }}
          </a>
        </div>
      </div>

      <!-- BARIS FILTER SEDERHANA-->
      <div
        class="mb-4 grid grid-cols-1 gap-3 rounded-xl border border-zinc-200 bg-white px-2 py-4 shadow-sm md:grid-cols-4 dark:border-zinc-800 dark:bg-zinc-900"
      >
        <!-- 1. Input Pencarian -->
        <div class="relative md:col-span-2">
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
            {{-- placeholder="Cari judul artikel..." --}}
            placeholder="{{ __('ui.articles.title_search') }}"
            class="focus:ring-sbh-green w-full rounded-lg border border-zinc-200 bg-zinc-50 py-2 pr-4 pl-9 text-sm text-zinc-700 focus:border-transparent focus:ring-2 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
            wire:model.live="titleSearch"
          />
        </div>

        <!-- 2. Dropdown Kategori -->
        <div>
          <select
            wire:model.live="selectCategory"
            class="focus:ring-sbh-green w-full cursor-pointer rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-700 focus:ring-2 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
          >
            <option value="">
              {{
                __(
                  "ui.articles.all_categories",
                )
              }}
            </option>
            @foreach ($this->categoryList as $category)
              <option value="{{ $category->id }}">
                {{
                  is_array($category->name)
                    ? $category->name[app()->getLocale()] ??
                      ($category->name["id"] ?? current($category->name))
                    : $category->name
                }}
              </option>
            @endforeach
          </select>
        </div>

        <!-- 3. Dropdown Status -->
        <div>
          <select
            wire:model.live="selectStatus"
            class="focus:ring-sbh-green w-full cursor-pointer rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-700 focus:ring-2 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
          >
            <option value="">
              {{
                __(
                  "ui.articles.all_status",
                )
              }}
            </option>
            @foreach ($this->statusList as $status)
              <option value="{{ $status->id }}">{{ $status->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- REVISI: Penambahan komputasi max-height responsif agar di mobile tidak meluber ke bawah --}}
      <!-- TABEL UTAMA -->
      <div
        class="max-h-[calc(100vh-6rem)] w-full overflow-x-auto overflow-y-auto rounded-xl border border-zinc-200 shadow-sm lg:max-h-[calc(100vh-280px)] dark:border-zinc-700"
      >
        <table class="w-full min-w-3xl table-fixed border-collapse text-left">
          <thead
            class="bg-misty text-foresty text-xs dark:bg-green-950 dark:text-green-300"
          >
            <tr>
              <!-- Header Judul -->
              <th
                class="bg-misty sticky top-0 z-10 w-2/5 px-4 py-3 font-semibold tracking-wider uppercase lg:z-20"
              >
                <button
                  wire:click="sortBy('title')"
                  class="flex w-full cursor-pointer items-center gap-2 font-semibold tracking-wider uppercase transition-colors hover:text-zinc-200"
                >
                  {{
                    __(
                      "ui.articles.title",
                    )
                  }}
                  @if ($orderColumn === "title")
                    <flux:icon
                      variant="solid"
                      icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}"
                      class="size-4"
                    />
                  @endif
                </button>
              </th>

              <!-- Tanggal -->
              <th
                class="bg-misty sticky top-0 z-10 px-4 py-3 font-semibold tracking-wider uppercase lg:z-20"
              >
                <button
                  wire:click="sortBy('created_at')"
                  class="flex w-full cursor-pointer items-center justify-center gap-2 font-semibold tracking-wider uppercase transition-colors hover:text-zinc-200"
                >
                  {{
                    __(
                      "ui.articles.dates_created",
                    )
                  }}
                  @if ($orderColumn === "created_at")
                    <flux:icon
                      variant="solid"
                      icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}"
                      class="size-4"
                    />
                  @endif
                </button>
              </th>

              <!-- Header Kategori -->
              <th
                class="bg-misty sticky top-0 z-10 px-4 py-3 font-semibold tracking-wider uppercase lg:z-20"
              >
                <button
                  wire:click="sortBy('category')"
                  class="flex w-full cursor-pointer items-center gap-2 font-semibold tracking-wider uppercase transition-colors hover:text-zinc-200"
                >
                  {{
                    __(
                      "ui.articles.category",
                    )
                  }}
                  @if ($orderColumn === "category")
                    <flux:icon
                      variant="solid"
                      icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}"
                      class="size-4"
                    />
                  @endif
                </button>
              </th>

              <!-- Header Status -->
              <th
                class="bg-misty sticky top-0 z-10 px-4 py-3 font-semibold tracking-wider uppercase lg:z-20"
              >
                <button
                  wire:click="sortBy('status')"
                  class="flex w-full cursor-pointer items-center gap-2 font-semibold tracking-wider uppercase transition-colors hover:text-zinc-200"
                >
                  {{
                    __(
                      "ui.articles.status",
                    )
                  }}
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
                class="bg-misty sticky top-0 z-10 px-4 py-3 text-center font-semibold tracking-wider uppercase lg:z-20"
              >
                {{
                  __(
                    "ui.articles.manage",
                  )
                }}
              </th>
            </tr>
          </thead>
          <tbody
            class="divide-y divide-zinc-200 bg-white text-zinc-700 dark:divide-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
          >
            @forelse ($this->articles as $article)
              @php
                $locale = app()->getLocale();
                $rawTitle = $article->title;
                $title = is_array($rawTitle)
                  ? $rawTitle[$locale] ??
                    ($rawTitle["id"] ?? (current($rawTitle) ?: "Tanpa Judul"))
                  : ($rawTitle ?:
                  "Tanpa Judul");
                $rawCategory = $article->category?->name;
                $categoryName = is_array($rawCategory)
                  ? $rawCategory[$locale] ??
                    ($rawCategory["id"] ?? (current($rawCategory) ?: "-"))
                  : ($rawCategory ?:
                  "-");
                $canDelete =
                  in_array($article->status, $this->deletableStatuses, true) &&
                  ($this->isStaff || (int) $article->user_id === (int) auth()->id());
              @endphp
              <tr
                wire:key="article-{{ $article->id }}"
                class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
              >
                <td class="px-4 py-3.5 text-sm">
                  <div
                    class="truncate font-medium text-zinc-900 dark:text-white"
                    title="{{ $title }}"
                  >
                    {{ $title }}
                  </div>

                  <div class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{
                      $article->author?->name ??
                        "-"
                    }}
                  </div>
                </td>
                <td class="h-full px-4 py-0 align-middle text-sm">
                  <div
                    class="flex h-full min-h-[3.5rem] items-center justify-center gap-2"
                  >
                    <div
                      class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-xs font-medium dark:bg-slate-800 dark:text-slate-300"
                    >
                      {{
                        $article->created_at->format(
                          "D, d/m/y",
                        )
                      }}
                    </div>
                    <div
                      class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-xs font-medium dark:bg-slate-800 dark:text-slate-300"
                    >
                      {{
                        $article->created_at->format(
                          "H:i",
                        )
                      }}
                    </div>
                  </div>
                </td>
                {{-- <td class="px-4 py-3.5 text-sm flex gap-2 items-center justify-center shrink-0">
                                        <div class="font-medium text-zinc-900 dark:text-white">{{ $article->created_at->format('D, d/m/y') }} </div>
                                        <div class="font-medium text-zinc-900 dark:text-white">{{ $article->created_at->format('h:m') }} </div>
                                        <!-- {/{ \Carbon\Carbon::parse($article->created_at)->format('d/m/y') }} -->
                                    </td> --}}
                <td class="px-4 py-3.5 text-sm">{{ $categoryName }}</td>
                <td class="px-4 py-3.5 text-sm">
                  <span
                    class="px-2 py-1 text-xs font-medium rounded-full border-2 {{ $article->status_color }}"
                  >
                    {{
                      $this->statusList[$article->status]
                        ->name ?? ucfirst($article->status)
                    }}
                  </span>
                </td>
                <td class="px-4 py-3.5 text-sm">
                  {{-- BUTTONS CONTAINER --}}
                  <div class="flex items-center justify-center gap-2">
                    <a
                      wire:navigate
                      {{-- href="{{ route('article.edit', ['category' => $article->category->slug ?? 'uncategorized', 'post'=> $article->slug]) }}" --}}
                      href="{{ route('article.edit',  $article->id) }}"
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
                      href="{{
                          route('preview.record', ['type' => 'article', 'id'=> $article->id]) }}"
                      {{-- href="{{ route('article.preview', ['category' => $article->category->slug ?? 'uncategorized', 'post'=> $article->slug]) }}" --}}
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
                        @click="deleteId = {{ $article->id }}; showDeleteModal = true"
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
                      >Tidak ada artikel yang ditemukan.</span
                    >
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4 px-2">{{ $this->articles->links() }}</div>
    </div>

    <!-- SECONDARY UX (KONTAINER KEDUA - PANEL/POPUP) -->
    <div x-show="activeSubPanel !== 'none'" x-cloak class="contents">
      <!-- latar gelap (ponsel) -->
      <div
        x-show="activeSubPanel !== 'none'"
        x-transition.opacity
        x-on:click="activeSubPanel = 'none'"
        class="fixed inset-0 z-40 bg-black/40 lg:hidden"
      ></div>

      <!-- badan panel -->
      <div
        x-show="activeSubPanel !== 'none'"
        x-transition
        class="fixed right-0 bottom-0 left-0 z-50 max-h-[85vh] space-y-4 overflow-y-auto rounded-t-3xl border border-zinc-200 bg-white p-5 shadow-xl lg:relative lg:right-auto lg:bottom-auto lg:left-auto lg:z-10 lg:max-h-none lg:w-1/3 lg:rounded-2xl lg:shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div
          class="mx-auto mb-2 h-1 w-12 rounded-full bg-zinc-300 lg:hidden dark:bg-zinc-700"
        ></div>

        <div
          class="flex items-center justify-between border-b border-zinc-200 pb-2 dark:border-zinc-800"
        >
          <div class="text-sbh-green flex items-center gap-2">
            <flux:icon.folder
              x-show="activeSubPanel === 'categories'"
              variant="solid"
              class="size-4"
            />
            <flux:icon.tag
              x-show="activeSubPanel === 'tags'"
              variant="solid"
              class="size-4"
            />
            <h3
              class="text-md font-bold text-zinc-900 dark:text-white"
              x-text="
                activeSubPanel === 'categories'
                  ? 'Kelola Kategori'
                  : 'Kelola Tag'
              "
            ></h3>
          </div>
          <button
            type="button"
            x-on:click="activeSubPanel = 'none'"
            aria-label="Tutup panel"
            class="cursor-pointer rounded-lg p-2 text-red-500 transition-colors hover:bg-red-600 hover:text-white"
          >
            <flux:icon variant="outline" icon="x-mark" class="size-6" />
          </button>
        </div>

        {{-- x-if: komponen baru dimuat saat panelnya dibuka, dan dibuang saat berpindah antara Kategori dan Tag --}}
        <template x-if="activeSubPanel === 'categories'">
          <div>
            <livewire:content.taxonomy
              kind="category"
              wire:key="taxonomy-category"
            />
          </div>
        </template>
        <template x-if="activeSubPanel === 'tags'">
          <div>
            <livewire:content.taxonomy kind="tag" wire:key="taxonomy-tag" />
          </div>
        </template>
      </div>
    </div>

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
                  Hapus Artikel
                </h3>
                <div class="mt-2">
                  <p class="text-sm text-zinc-500 dark:text-zinc-400">Apakah Anda yakin ingin menghapus artikel ini? Data yang sudah dihapus tidak dapat dikembalikan.</p>
                </div>
              </div>
            </div>
            <div class="mt-5 flex flex-col gap-3 sm:mt-6 sm:flex-row-reverse">
              <button
                type="button"
                class="bg-sage-soft text-forest inline-flex w-full cursor-pointer justify-center rounded-xl px-3 py-2 text-sm font-semibold shadow-sm transition-colors hover:bg-red-600 hover:text-white sm:w-auto"
                @click="
                  $wire.deleteArticle(deleteId);
                  deleteId = null;
                  showDeleteModal = false;
                "
              >
                Ya, Hapus
              </button>
              <button
                type="button"
                x-on:click="
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
