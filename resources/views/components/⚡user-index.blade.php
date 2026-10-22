<?php

use Livewire\Attributes\Locked;
use Livewire\Component;
use App\Models\User;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Role;
use App\Livewire\Traits\WithNotifications;

new class extends Component {
  use WithNotifications;

  public int $totalUsers = 0;
  public int $totalActiveUsers = 0;
  public int $totalNonActiveUsers = 0;
  public int $totalAdmin = 0;
  public int $totalEditor = 0;
  public int $totalWriter = 0;

  // Hanya diubah oleh sortBy() di server. Tanpa #[Locked], browser bisa mengisi nama kolom apa pun
  // (mis. password) dan membaca urutan datanya.
  #[Locked]
  public string $orderColumn = "created_at";
  #[Locked]
  public string $orderDirection = "desc";

  /** Kolom yang boleh diurutkan. */
  private const SORTABLE = ["name", "handle", "email", "active", "created_at"];

  public function mount(): void
  {
    $this->loadStats();
  }

  /** Mengisi semua angka statistik; panggil lagi setelah ada perubahan data (hapus/ubah status/peran). */
  public function loadStats(): void
  {
    $this->totalUsers = User::count();
    $this->totalActiveUsers = User::where("active", true)->count();
    $this->totalNonActiveUsers = $this->totalUsers - $this->totalActiveUsers;

    // Satu query untuk semua peran: ['admin' => 2, 'editor' => 3, ...]
    $perRole = Role::withCount("users")->pluck("users_count", "name");

    $this->totalAdmin = (int) ($perRole["admin"] ?? 0);
    $this->totalEditor = (int) ($perRole["editor"] ?? 0);
    $this->totalWriter = (int) ($perRole["writer"] ?? 0);
  }

  public function sortBy(string $column): void
  {
    if (!in_array($column, self::SORTABLE, true)) {
      return;
    }

    $this->orderDirection =
      $this->orderColumn === $column && $this->orderDirection === "asc"
        ? "desc"
        : "asc";
    $this->orderColumn = $column;
  }

  #[Computed]
  public function userList()
  {
    return User::query()
      ->with("roles") // cegah N+1 saat getRoleNames() dipanggil per baris
      ->orderBy($this->orderColumn, $this->orderDirection)
      ->orderBy("id") // pemutus seri: urutan baris tidak bergeser antar permintaan
      ->get();
  }
};
?>

<x-slot:title>
  {{
    __(
      "ui.header.user",
    )
  }}
</x-slot:title>
<div
  class="mt-4 flex h-full max-h-screen min-h-0 w-full min-w-0 flex-col items-center justify-start gap-6 overflow-x-hidden overflow-y-auto"
>
  {{-- USERS --}}
  <div
    class="flex w-full max-w-7xl flex-wrap items-start justify-start gap-4 md:shrink-0 md:flex-nowrap"
  >
    <div class="flex flex-col gap-2 rounded-xl bg-white p-4 md:p-6">
      <span
        class="font-fraunces text-forest text-xl md:text-5xl"
        >{{ $totalUsers }}</span
      >
      <span class="font-jakarta text-forest tracking-widest"
        >Total Pengguna
      </span>
    </div>
    <div class="flex flex-col gap-2 rounded-xl bg-white p-4 md:p-6">
      <span
        class="font-fraunces text-forest text-xl md:text-5xl"
        >{{ $totalActiveUsers }}</span
      >
      <span class="font-jakarta text-forest tracking-widest">Akun Aktif </span>
    </div>
    <div class="flex flex-col gap-2 rounded-xl bg-white p-4 md:p-6">
      <span
        class="font-fraunces text-coral text-xl md:text-5xl"
        >{{ $totalNonActiveUsers }}</span
      >
      <span class="font-jakarta text-forest tracking-widest"
        >Akun Non-Aktif
      </span>
    </div>
    <div class="flex flex-col gap-2 rounded-xl bg-white p-4 md:p-6">
      <span
        class="font-fraunces text-forest text-xl md:text-5xl"
        >{{ $totalAdmin }}</span
      >
      <span class="font-jakarta text-forest tracking-widest">Admin </span>
    </div>
    <div class="flex flex-col gap-2 rounded-xl bg-white p-4 md:p-6">
      <span
        class="font-fraunces text-forest text-xl md:text-5xl"
        >{{ $totalEditor }}</span
      >
      <span class="font-jakarta text-forest tracking-widest">Editor </span>
    </div>
    <div class="flex flex-col gap-2 rounded-xl bg-white p-4 md:p-6">
      <span
        class="font-fraunces text-forest text-xl md:text-5xl"
        >{{ $totalWriter }}</span
      >
      <span class="font-jakarta text-forest tracking-widest">Writer </span>
    </div>
  </div>

  {{-- ROLES --}}
  <div
    class="flex w-full max-w-7xl shrink-0 flex-col items-start justify-center gap-4 rounded-xl bg-white p-4"
  >
    <span class="font-jakarta text-forest text-xl font-black tracking-widest"
      >Tingkat Peran</span
    >
    @php
      $roles = [
        [
          "label" => "admin",
          "attr" => "bg-forest text-aurum  dark:bg-forest dark:text-aurum",
          "icon" => "lucide-crown",
          "desc" => "Akses penuh — termasuk kelola pengguna lain & pengaturan situs.",
        ],
        [
          "label" => "editor",
          "attr" =>
            "bg-forest/90 text-aurum-soft dark:bg-forest/90 dark:text-aurum-soft",
          "icon" => "lucide-glasses",
          "desc" =>
            "Bisa membuat, meninjau & menerbitkan Halaman apa pun, tanpa akses pengguna/pengaturan.",
        ],
        [
          "label" => "writer",
          "attr" => "bg-aurum-soft text-forest  dark:bg-aurum-soft dark:text-forest",
          "icon" => "lucide-feather",
          "desc" =>
            "Bisa menyusun Halaman, tapi perlu ditinjau <b>Editor</b> atau <b>Admin</b> sebelum online.",
        ],
      ];
    @endphp

    <div
      class="flex h-full flex-col items-start justify-between gap-4 md:flex-row"
    >
      @foreach ($roles as $item)
        <div class="bg-mist flex h-full w-full flex-col gap-2 rounded-xl p-6">
          <div
            class="flex gap-2 font-jakarta w-fit self-start items-center rounded-full px-3 py-1 font-bold tracking-widest capitalize {{ $item['attr'] }}"
          >
            <x-dynamic-component
              component="{{ $item['icon'] }}"
              class="h-4 w-4"
              stroke-width="2.5"
            />
            {{ $item["label"] }}
          </div>
          <span> {!! $item["desc"] !!} </span>
        </div>
      @endforeach
    </div>
  </div>

  {{-- USERS LIST --}}
  <div
    class="mb-4 flex w-full max-w-7xl shrink-0 flex-col overflow-y-auto rounded-xl bg-white p-4"
  >
    <!--HEADER CONTAINER -->
    <div class="mb-4 flex w-full items-center justify-between">
      <div class="text-2xl font-bold">
        {{
          __(
            "Users List",
          )
        }}
      </div>
      <div class="">
        <a
          href="{{ route('user.create') }}"
          wire:navigate
          x-data="{
            isAnimating: false,
            playAnim() {
              this.isAnimating = false;
              this.$nextTick(() => {
                this.isAnimating = true;
                setTimeout(() => (this.isAnimating = false), 500);
              });
            },
          }"
          {{-- x-on:click=" playAnim(); deleteType = 'category'; deleteId = {{ $i->id }}; showDeleteModal = true " --}}
          x-on:mouseenter="playAnim()"
          class="text-foresty hover:bg-foresty hover:text-goldy inline-flex cursor-pointer items-center gap-2 rounded-xl border border-zinc-200 bg-white p-2 text-sm font-semibold shadow-sm transition-colors"
        >
          <x-dynamic-component
            :component="'lucide-user-plus'"
            class="h-5 w-5 origin-center"
            stroke-width="2"
            x-bind:class="isAnimating ? 'animate-save' : ''"
          />
          {{ __("Create") }}
        </a>
      </div>
    </div>

    <!-- TABLE CONTAINER -->
    <div
      class="max-h-[70vh] min-h-[30vh] w-full max-w-screen overflow-x-auto overflow-y-auto rounded-xl border border-zinc-200 dark:border-zinc-700"
    >
      <table class="w-full min-w-max border-collapse text-left">
        <thead class="bg-mist text-foresty text-xs">
          @php
            // kunci = kolom yang diurutkan (harus ada di SORTABLE); null = tidak bisa diurutkan
            $columns = [
              ["key" => "name", "label" => "Nama", "align" => "justify-start"],
              ["key" => "handle", "label" => "Handle", "align" => "justify-center"],
              ["key" => "email", "label" => "E-mail", "align" => "justify-start"],
              ["key" => "active", "label" => "Status", "align" => "justify-start"],
            ];
          @endphp
          <tr>
            @foreach ($columns as $col)
              <th
                scope="col"
                aria-sort="{{ $orderColumn === $col['key'] ? ($orderDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                class="bg-mist sticky top-0 z-10 px-4 py-3 font-semibold tracking-wider uppercase lg:z-20"
              >
                <button
                  type="button"
                  wire:click="sortBy('{{ $col['key'] }}')"
                  class="flex w-full cursor-pointer items-center gap-2 {{ $col['align'] }} font-semibold tracking-wider uppercase transition-colors hover:text-zinc-500"
                >
                  {{ $col["label"] }}
                  @if ($orderColumn === $col["key"])
                    <flux:icon
                      variant="solid"
                      icon="{{ $orderDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}"
                      class="size-4"
                    />
                  @endif
                </button>
              </th>
            @endforeach

            <th
              scope="col"
              class="bg-mist sticky top-0 z-10 px-4 py-3 text-center font-semibold tracking-wider uppercase lg:z-20"
            >
              {{ __("roles") }}
            </th>

            <!-- Header Kelola (Tidak perlu sorting) -->
            <th
              scope="col"
              class="bg-mist sticky top-0 z-10 px-4 py-3 text-center font-semibold tracking-wider uppercase lg:z-20"
            >
              Kelola
            </th>
          </tr>
        </thead>
        <tbody
          class="divide-y divide-zinc-200 bg-white text-zinc-700 dark:divide-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
        >
          @forelse ($this->userList as $user)
            @php
              // Foto lewat ChatAvatar (User::reviewAvatarUrl()), sama seperti di halaman profil dan header.
              $avatar = \App\Content\ChatAvatar::url($user);
            @endphp
            <tr
              wire:key="user-{{ $user->id }}"
              class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
            >
              <td class="px-4 py-3.5 text-sm">
                <div class="flex items-center gap-3">
                  {{-- Inisial di bawah, foto di atasnya; foto gagal dimuat -> <img> dibuang dan inisial terlihat --}}
                  <div
                    class="bg-mist text-forest relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-xs font-bold"
                  >
                    <span aria-hidden="true">{{
                      \App\Content\ChatAvatar::initials(
                        (string) $user->name,
                      )
                    }}</span>
                    @if ($avatar)
                      <img
                        src="{{ $avatar }}"
                        alt=""
                        width="40"
                        height="40"
                        loading="lazy"
                        decoding="async"
                        class="absolute inset-0 h-full w-full object-cover"
                        onerror="this.remove()"
                      />
                    @endif
                  </div>
                  <div class="min-w-0">
                    <div
                      class="truncate font-medium text-zinc-900 dark:text-white"
                    >
                      {{ $user->name }}
                    </div>
                    <div
                      class="truncate text-xs text-zinc-500 dark:text-zinc-400"
                    >
                      {{ $user->job_title }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="h-full px-4 py-0 align-middle text-sm">
                <div
                  class="flex h-full min-h-14 items-center justify-center gap-2"
                >
                  <div
                    class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-xs font-medium dark:bg-slate-800 dark:text-slate-300"
                  >
                    {{ $user->handle }}
                  </div>
                </div>
              </td>
              <td class="px-4 py-3.5 text-sm">{{ $user->email }}</td>
              {{-- Hapus mx-auto, ganti dengan text-center dan align-middle --}}
              <td class="px-4 py-3.5 text-center align-middle text-sm">
                @if ($user->active)
                  {{-- Tambahkan inline-block agar padding span dirender sempurna --}}
                  <span
                    class="bg-mist text-foresty border-foresty inline-flex items-center gap-2 rounded-full border-2 px-2.5 py-0.5 text-xs font-medium dark:bg-violet-950 dark:text-violet-300"
                  >
                    <span
                      class="bg-foresty inline-block h-1.5 w-1.5 rounded-full"
                    ></span
                    >Aktif
                  </span>
                @else
                  <span
                    class="bg-coral-muted inline-flex items-center gap-2 rounded-full border-2 border-red-700 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-yellow-950 dark:text-yellow-300"
                  >
                    <span
                      class="inline-block h-1.5 w-1.5 rounded-full bg-red-700"
                    ></span
                    >Nonaktif
                  </span>
                @endif
              </td>
              <td class="px-4 py-3.5 align-middle text-sm">
                <div class="flex items-center gap-4">
                  @forelse ($user->getRoleNames() as $role)
                    <div
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold gap-2 {{ $user->getRoleColor($role) }}"
                    >
                      <x-dynamic-component
                        component="{{ $user->getRoleIcon($role) }}"
                        class="h-4 w-4"
                        stroke-width="2.5"
                      />
                      {{ ucfirst($role) }}
                    </div>
                  @empty
                    <!-- Jika user tidak punya role sama sekali -->
                    <span
                      class="inline-flex items-center rounded-full border border-zinc-700 bg-zinc-50 px-2.5 py-0.5 text-xs font-semibold text-zinc-700"
                    >
                      Tanpa Wewenang
                    </span>
                  @endforelse
                </div>
              </td>
              <td class="px-4 py-3.5 text-sm">
                {{-- BUTTONS CONTAINER --}}
                <div class="flex items-center justify-center gap-2">
                  <a
                    wire:navigate
                    href="{{ route('user.detail', $user) }}"
                    class="group relative flex cursor-pointer items-center justify-center rounded-md bg-slate-600 p-1.5 text-white transition-colors hover:bg-slate-700 dark:bg-slate-800"
                  >
                    <flux:icon variant="solid" icon="eye" class="size-3.5!" />
                    <span
                      class="pointer-events-none absolute bottom-full left-1/2 z-30 mb-2 w-max -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity duration-200 group-hover:opacity-100 dark:bg-gray-100 dark:text-gray-900"
                    >
                      Detail
                      <svg class="absolute top-full left-0 h-2 w-full text-gray-900 dark:text-gray-100" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                        <polygon class="fill-current" points="0,0 127.5,127.5 255,0" />
                      </svg>
                    </span>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            {{-- INI AKAN MUNCUL JIKA TIDAK ADA DATA  --}}
            <tr>
              <td colspan="6" class="px-4 py-12 text-center">
                <div class="flex flex-col items-center justify-center">
                  <flux:icon
                    variant="outline"
                    icon="document-text"
                    class="mb-2 size-8 text-zinc-400"
                  />
                  <span
                    class="text-sm font-medium text-zinc-500 dark:text-zinc-400"
                    >Belum ada pengguna.</span
                  >
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
