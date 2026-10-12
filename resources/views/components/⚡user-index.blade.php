<?php

use Livewire\Attributes\Title;

use Livewire\Component;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Livewire\Traits\WithNotifications;

new class extends Component {
  use WithNotifications;
  //
  public function mount()
  {
    $this->orderDirection = "desc";
    $this->orderColumn = "created_at";
  }
  public string $orderDirection;
  public string $orderColumn;

  #[Computed]
  public function userList()
  {
    $query = User::query();
    return $query->get();
  }
};
?>

{{-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi --}}
{{-- <div class="">
    --}}
<x-slot:title>
  {{
    __(
      "ui.header.user",
    )
  }}
</x-slot:title>
<x-main-wrapper>
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
      {{-- <a href="{{ route('user.create') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-zinc-600 bg-white border border-zinc-200 rounded-xl hover:bg-foresty hover:text-goldy transition-colors shadow-sm">
                <x-dynamic-component :component="'lucide-user-plus'" class="w-4 h-4" />
                {{ __('Create') }}
            </a> --}}
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
    class="max-h-full w-full max-w-screen overflow-x-auto overflow-y-auto rounded-xl border border-zinc-200 dark:border-zinc-700"
  >
    <table class="w-full min-w-max border-collapse text-left">
      <thead class="bg-sbh-green text-foresty bg-misty text-xs">
        <tr>
          <!-- Header Judul -->
          <th
            class="bg-misty sticky top-0 z-10 px-4 py-3 font-semibold tracking-wider uppercase lg:z-20"
          >
            <button
              wire:click="sortBy('name')"
              class="flex w-full cursor-pointer items-center gap-2 font-semibold tracking-wider uppercase transition-colors hover:text-zinc-200"
            >
              Nama
              @if ($orderColumn === "name")
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
              handle
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
              E-mail
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
          <th
            class="bg-misty sticky top-0 z-10 px-4 py-3 text-center font-semibold tracking-wider uppercase lg:z-20"
          >
            {{ __("roles") }}
          </th>

          <!-- Header Kelola (Tidak perlu sorting) -->
          <th
            class="bg-misty sticky top-0 z-10 px-4 py-3 text-center font-semibold tracking-wider uppercase lg:z-20"
          >
            Kelola
          </th>
        </tr>
      </thead>
      <tbody
        class="divide-y divide-zinc-200 bg-white text-zinc-700 dark:divide-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
      >
        @forelse ($this->userList as $user)
          @foreach (range(1, 1) as $i)
            <tr
              class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
            >
              <td class="px-4 py-3.5 text-sm">
                <div class="font-medium text-zinc-900 dark:text-white">
                  {{ $user->name }}
                </div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                  {{ $user->job_title }}
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
                    class="bg-misty text-foresty border-foresty bg-misty inline-flex items-center gap-2 rounded-full border-2 px-2.5 py-0.5 text-xs font-medium dark:bg-violet-950 dark:text-violet-300"
                  >
                    <span
                      class="bg-foresty inline-block h-1.5 w-1.5 rounded-full"
                    ></span
                    >Aktif
                  </span>
                @else
                  <span
                    class="py-0.5text-xs bg-coral-muted inline-flex items-center gap-2 rounded-full border-2 border-red-700 px-2.5 font-medium text-red-700 dark:bg-yellow-950 dark:text-yellow-300"
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
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border gap-2 {{ $user->getRoleColor($role) }}"
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
          @endforeach
        @empty
          {{-- INI AKAN MUNCUL JIKA TIDAK ADA DATA  --}}
          <tr>
            <td colspan="4" class="px-4 py-12 text-center">
              <div class="flex flex-col items-center justify-center">
                <flux:icon
                  variant="outline"
                  icon="document-text"
                  class="mb-2 size-8 text-zinc-400"
                />
                <span
                  class="text-sm font-medium text-zinc-500 dark:text-zinc-400"
                  >Nu uh</span
                >
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</x-main-wrapper>
{{-- </div> --}}
