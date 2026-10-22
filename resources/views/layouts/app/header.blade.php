@props (["title" => null, "header" => null])

@php
  $u = auth()->user();
  // Alamat foto lewat ChatAvatar: memakai User::reviewAvatarUrl() bila ada, jadi alamatnya sama dengan halaman profil
  // (jalur di kolom avatar diubah ke alamat disk "public" di lingkungan ini). Null = tampilkan inisial.
  $avatarUrl = \App\Content\ChatAvatar::url($u);
@endphp

<aside
  {{
    $attributes->merge([
      "class" =>
        "flex flex-row bg-white text-forest items-center border-foresty justify-between rounded-lg h-10 p-2 gap-4 z-50 transition-all",
    ])
  }}
  :class="isExpanded ? 'border' : ''"
>
  <div class="flex w-full items-center justify-between">
    <button
      type="button"
      x-on:click="isExpanded = !isExpanded"
      x-bind:aria-expanded="isExpanded.toString()"
      aria-label="Menu"
      class="hover:bg-sage-soft flex rounded-lg p-2 focus:outline-none md:hidden"
    >
      <div class="relative flex h-5 w-5 items-center justify-center">
        <span
          x-cloak
          :class="isExpanded ? 'rotate-45 translate-y-0' : '-translate-y-1.5'"
          class="absolute block h-0.5 w-5 bg-current transition-all duration-300"
        >
        </span>
        <span
          x-cloak
          :class="isExpanded ? 'opacity-0' : 'opacity-100'"
          class="absolute block h-0.5 w-5 bg-current transition-all duration-300"
        >
        </span>
        <span
          x-cloak
          :class="isExpanded ? '-rotate-45 translate-y-0' : 'translate-y-1.5'"
          class="absolute block h-0.5 w-5 bg-current transition-all duration-300"
        >
        </span>
      </div>
    </button>

    <div class="flex items-center gap-4 p-2 text-sm font-bold tracking-wide">
      {{
        filled($title)
          ? $title
          : config("app.name")
      }}

      {{ $header }}
    </div>
    <div id="editor-toolbar-portal"></div>
  </div>

  {{-- Tombol akun + menu dibungkus satu elemen, supaya "klik di luar" menutup menu tetapi klik di dalam menu tidak. --}}
  <div
    class="shrink-0"
    x-on:click.outside="userMenuExpand = false"
    x-on:keydown.escape.window="userMenuExpand = false"
  >
    <button
      type="button"
      x-on:click="userMenuExpand = !userMenuExpand"
      x-bind:aria-expanded="userMenuExpand.toString()"
      aria-haspopup="true"
      aria-label="{{ __('Menu akun') }}"
      class="group bg-sage-soft border-forest flex h-11 w-11 cursor-pointer items-center justify-center rounded-2xl border-2 font-semibold whitespace-nowrap text-white shadow-sm transition-all duration-300"
    >
      {{-- Inisial di bawah, foto di atasnya. Foto gagal dimuat: <img> disembunyikan dan inisial terlihat.
           Setelah profil sendiri disimpan, halaman profil mengirim 'avatar-updated' agar foto di sini ikut berganti. --}}
      <div
        x-data="{ url: @js($avatarUrl), broken: false }"
        x-on:avatar-updated.window="
          url = $event.detail.url ?? null;
          broken = false;
        "
        class="relative flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl transition-transform duration-200 group-hover:scale-105"
      >
        <span class="text-foresty">{{ $u->initials() }}</span>
        <img
          @if ($avatarUrl) src="{{ $avatarUrl }}" @endif
          x-bind:src="url"
          x-show="url && !broken"
          x-on:error="broken = true"
          alt=""
          class="absolute inset-0 h-full w-full object-cover"
        />
      </div>
    </button>

    <!-- Container Profil (Sudut Kanan Atas) -->
    <div class="fixed top-12 right-2 z-50">
      <!-- THE ISLAND (Floating Menu) -->
      <div
        x-show="userMenuExpand"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-[-10px]"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-[-10px]"
        class="border-forest absolute right-1 mt-3 w-64 overflow-hidden rounded-xl border bg-white/60 p-2 shadow-lg shadow-black/5 backdrop-blur-xl backdrop-saturate-150"
      >
        <!-- Header Info -->
        <div class="mb-1 flex flex-col gap-2 border-b border-zinc-100 p-2">
          <div
            class="flex items-center justify-between gap-x-2 gap-y-1 border-b pb-2"
          >
            <span
              title="{{ $u->name ?? 'nama' }}"
              class="text-forest max-w-full truncate text-sm font-bold"
            >
              {{
                $u->name ??
                  "nama"
              }}
            </span>
            <span
              class="bg-mist text-forest shrink-0 rounded-4xl px-2 py-0.5 text-center text-xs whitespace-nowrap"
            >
              {{
                "@" .
                  $u->handle
              }}
            </span>
          </div>
          <div
            class="flex flex-wrap items-center justify-start gap-x-2 gap-y-1"
          >
            @forelse ($u->getRoleNames() as $item)
              <div
                class="text-xxs inline-flex items-center gap-2 rounded-full px-1.5 py-1 font-semibold {{ $u->getRoleColor($item) }}"
              >
                <x-dynamic-component
                  :component="$u->getRoleIcon($item)"
                  class="h-3 w-3"
                  stroke-width="2.5"
                />
                {{ ucfirst($item) }}
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
        </div>

        <!-- Menu Items -->
        <div class="flex flex-col space-y-0.5 px-2">
          <!-- Profil -->
          <a
            href="{{ route('user.detail', $u) }}"
            wire:navigate
            class="hover:text-forest group flex items-center rounded-2xl px-3 py-2.5 text-sm font-medium text-zinc-600 transition-all hover:bg-zinc-50"
          >
            <svg class="group-hover:text-forest mr-3 h-5 w-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            Profil
          </a>

          <!-- Pengaturan (rute hanya untuk admin, jadi tautannya juga hanya tampil untuk admin) -->
          @if ($u->hasRole("admin"))
            <a
              href="{{ route('settings.manager') }}"
              wire:navigate
              class="hover:text-forest group flex items-center rounded-2xl px-3 py-2.5 text-sm font-medium text-zinc-600 transition-all hover:bg-zinc-50"
            >
              <svg class="group-hover:text-forest mr-3 h-5 w-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Pengaturan
            </a>
          @endif

          <!-- Toggle bahasa -->
          <div
            class="group flex items-center justify-between rounded-2xl px-3 py-2 text-sm font-medium text-zinc-600 transition-all hover:bg-zinc-50"
          >
            <div class="flex items-center">
              <x-dynamic-component
                :component="'lucide-earth'"
                class="group-hover:text-forest mr-3 h-5 w-5 text-zinc-400"
                stroke-width="2.5"
              />

              Bahasa
            </div>

            <div
              role="group"
              aria-label="Language"
              class="border-forest/20 flex items-center rounded-full border bg-white/40 p-1 shadow-sm backdrop-blur-md"
            >
              @foreach (["id" => "ID", "en" => "EN"] as $code => $label)
                <a
                  href="{{ route('language.switch', $code) }}"
                  @if (app()->getLocale() === $code) aria-current="true" @endif
                  class="rounded-full px-3 py-1 text-xs font-bold transition-all {{ app()->getLocale() === $code ? 'bg-forest text-white shadow-md' : 'text-forest/70 hover:text-forest hover:bg-forest/10' }}"
                >
                  {{ $label }}
                </a>
              @endforeach
            </div>
          </div>

          <!-- Divider -->
          <div class="mx-2 my-1 h-px bg-zinc-100"></div>

          <!-- Logout -->
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
              type="submit"
              class="group flex w-full cursor-pointer items-center rounded-2xl px-3 py-2.5 text-sm font-bold text-red-500 transition-all hover:bg-red-50"
            >
              <svg class="mr-3 h-5 w-5 text-red-400 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
              Keluar
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</aside>
