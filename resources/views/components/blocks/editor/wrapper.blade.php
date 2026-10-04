@props (["blockId", "block", "isNested" => false])

<div
  id="block-wrapper-{{ $blockId }}"
  wire:key="block-{{ $blockId }}"
  x-sort:item="'{{ $blockId }}'"
  x-bind:class="showAnchorSetting ? 'z-50' : ''"
  x-data="{ 
    showAnchorSetting: false,
    isCollapsed: false,
    isFullscreen: false,
    init() {
        // 1. Saat dirender ulang, periksa apakah blok ini punya ingatan status
        window.blockCollapseState = window.blockCollapseState || {};
        if (window.blockCollapseState['{{ $blockId }}'] !== undefined) {
            this.isCollapsed = window.blockCollapseState['{{ $blockId }}'];
        }

        // 2. Setiap kali status berubah, titipkan ingatannya ke memori peramban
        this.$watch('isCollapsed', (value) => {
            window.blockCollapseState['{{ $blockId }}'] = value;
        });
    },
toggleFullscreen() {
          this.isFullscreen = !this.isFullscreen;
          if (this.isFullscreen) {
              this.isCollapsed = false; // Paksa buka blok jika masuk fullscreen
              document.body.style.overflow = 'hidden'; // Kunci scroll halaman belakang
          } else {
              document.body.style.overflow = ''; // Lepas kunci scroll
          }
      },
  }"
  @toggle-collapse-all.window="isCollapsed = $event.detail"
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }"
   
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }"
  {{-- class="group relative mb-6 flex w-full flex-col rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200" --}}
  {{
    $attributes->merge([
      "class" =>
        "group/block relative mb-6 flex w-full flex-col rounded-xl border hover:border-dashed hover:border-forest border-gray-200  shadow-sm transition-all duration-200",
    ])
  }}
>
  <!-- 🌟 1. PLACEHOLDER (Muncul saat blok ini terbang menjadi Fullscreen agar tata letak tidak melompat) -->
  <div
    x-show="isFullscreen"
    x-cloak
    class="border-foresty/40 bg-foresty/5 flex h-32 w-full items-center justify-center rounded-xl border-2 border-dashed"
  >
    <span class="text-foresty text-xs font-bold tracking-widest uppercase">
      Mode Fokus Sedang Aktif
    </span>
  </div>

  <!-- 🌟 2. KONTEM UTAMA (Bisa normal, bisa terbang jadi Fullscreen) -->
  <div
    class="flex w-full flex-col transition-all duration-300"
    x-bind:class="
      isFullscreen
        ? 'fixed inset-0 z-99 bg-gray-50 h-screen'
        : 'relative rounded-xl border border-gray-200 bg-white shadow-sm'
    "
  >
    <!-- HEADER GLOBAL BLOK (Sticky) -->
    <div
      class="sticky top-0 z-30 flex items-center justify-between rounded-t-xl border-b border-gray-200 px-4 py-2 shadow-[0_4px_10px_rgba(0,0,0,0.03)] backdrop-blur-md transition-all"
      x-bind:class="isCollapsed ? ' rounded-b-xl' : 'rounced-b-none'"
    >
      <div class="flex items-center gap-3">
        @if (!$isNested)
          <button
            type="button"
            class="drag-handle hover:text-foresty cursor-grab text-gray-400 transition-colors outline-none"
            title="Geser Blok"
          >
            <x-dynamic-component
              component="lucide-grip-vertical"
              class="h-5 w-5"
            />
          </button>
        @endif

        <div
          class="flex items-center gap-3 text-xs font-extrabold tracking-widest text-gray-500 uppercase"
        >
          @if (isset($title))
            {{ $title }}
          @else
            {{
              str_replace(
                ["_", "-"],
                " ",
                $block["type"],
              )
            }}
          @endif
        </div>
      </div>

      <!-- 🌟 TENGAH: INJEKSI CUPLIKAN TEKS (Hanya muncul saat diringkas) -->
      @if (isset($snippet))
        <div
          x-show="isCollapsed"
          x-cloak
          class="min-w-0 flex-1 px-4 text-xs font-medium text-gray-400"
        >
          {{ $snippet }}
        </div>
      @else
        <div class="min-w-0 flex-1"></div>
        <!-- Pendorong agar tata letak tetap rata -->
      @endif

      <!-- KANAN: Pengaturan Khusus Blok + Aksi Global -->
      <div class="flex items-center gap-2 sm:gap-3">
        <!-- 🌟 INJEKSI PENGATURAN KHUSUS BLOK (Dari x-slot:settings) -->
        @if (isset($settings))
          <div class="flex items-center gap-2">{{ $settings }}</div>
        @endif

        <!-- Pengaturan Anchor -->
        <div x-on:click.outside="showAnchorSetting = false" class="relative">
          <button
            x-on:click="showAnchorSetting = !showAnchorSetting"
            type="button"
            class="rounded p-1.5 transition-colors outline-none"
            x-bind:class="
              showAnchorSetting
                ? 'bg-foresty text-white'
                : 'text-gray-500 hover:bg-gray-200 hover:text-gray-700'
            "
            title="Pengaturan Tautan (Anchor)"
          >
            <x-dynamic-component component="lucide-link" class="h-4 w-4" />
          </button>
          <!-- Popup Anchor -->
          <div
            x-show="showAnchorSetting"
            x-cloak
            style="display: none"
            class="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-gray-200 bg-white p-4 shadow-xl"
          >
            <label class="text-foresty mb-1 block text-xs font-bold uppercase"
              >ID Tautan (Anchor)</label
            >
            <p class="mb-3 text-[10px] leading-tight text-gray-500">Melompat ke blok ini (Contoh: <span class="text-coral font-mono">tentang-kami</span>).</p>
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

        <!-- Garis Pemisah Kecil -->
        <div
          x-show="!isFullscreen"
          x-cloak
          class="hidden h-4 w-px bg-gray-300 sm:block"
        ></div>

        @if ($isNested && $parentId && $parentZone)
          <!-- Jika Bersarang: Panggil fungsi duplikat khusus anak -->
          <button
            type="button"
            wire:click="duplicateNestedBlock('{{ $parentId }}', '{{ $parentZone }}', '{{ $blockId }}')"
            title="Duplikat Langkah Ini"
          >
            <x-dynamic-component component="lucide-copy" class="h-4 w-4" />
          </button>
        @else
          <!-- Jika Jalur Utama: Panggil fungsi duplikat normal -->
          <button
            type="button"
            wire:click="duplicateBlock('{{ $blockId }}')"
            title="Duplikat Blok"
          >
            <x-dynamic-component component="lucide-copy" class="h-4 w-4" />
          </button>
        @endif

        <!-- Tombol Hapus -->
        <button
          x-show="!isFullscreen"
          x-cloak
          wire:click="removeBlock('{{ $blockId }}')"
          type="button"
          wire:confirm="Hapus blok ini beserta isinya?"
          class="rounded p-1.5 text-red-400 transition-colors outline-none hover:bg-red-50 hover:text-red-600"
          title="Hapus Blok"
        >
          <x-dynamic-component component="lucide-trash-2" class="h-4 w-4" />
        </button>

        @if (!$isNested)
          <!-- 🌟 TOMBOL FULLSCREEN (FOKUS) -->
          <button
            type="button"
            x-on:click="toggleFullscreen()"
            class="rounded p-1 transition-colors outline-none"
            x-bind:class="
              isFullscreen
                ? 'bg-sage-soft text-forest hover:bg-sage-soft'
                : 'hover:bg-sage-soft text-foresty'
            "
            title="Mode Fokus (Layar Penuh)"
          >
            <x-dynamic-component
              component="lucide-maximize"
              x-show="!isFullscreen"
              class="h-4 w-4"
            />
            <x-dynamic-component
              component="lucide-minimize"
              x-show="isFullscreen"
              x-cloak
              class="h-4 w-4"
            />
          </button>
        @endif

        <!-- Tombol collapse runtuh -->
        <button
          x-show="!isFullscreen"
          x-cloak
          type="button"
          x-on:click="isCollapsed = !isCollapsed"
          class="hover:bg-sage-soft text-foresty cursor-pointer rounded-md p-1 transition-all duration-200 focus:outline-none"
        >
          <div class="relative flex h-4 w-4 items-center justify-center">
            <!-- Ikon 1: Muncul saat TERTUTUP (isCollapsed = true) -->
            <div
              x-show="!isCollapsed"
              x-transition:enter="transition duration-300 transform ease-out"
              x-transition:enter-start="opacity-0 -rotate-180 scale-50"
              x-transition:enter-end="opacity-100 rotate-0 scale-100"
              x-transition:leave="transition duration-200 transform ease-in absolute"
              x-transition:leave-start="opacity-100 rotate-0 scale-100"
              x-transition:leave-end="opacity-0 rotate-180 scale-50"
              class="text-foresty absolute inset-0"
              x-cloak
            >
              <x-dynamic-component
                component="lucide-list-chevrons-down-up"
                class="h-4 w-4"
              />
            </div>

            <!-- Ikon 2: Muncul saat TERBUKA (isCollapsed = false) -->
            <div
              x-show="isCollapsed"
              x-transition:enter="transition duration-300 transform ease-out"
              x-transition:enter-start="opacity-0 rotate-180 scale-50"
              x-transition:enter-end="opacity-100 rotate-0 scale-100"
              x-transition:leave="transition duration-200 transform ease-in absolute"
              x-transition:leave-start="opacity-100 rotate-0 scale-100"
              x-transition:leave-end="opacity-0 -rotate-180 scale-50"
              class="text-foresty absolute inset-0"
              x-cloak
            >
              <x-dynamic-component
                component="lucide-list-chevrons-up-down"
                class="h-4 w-4"
              />
            </div>
          </div>
        </button>
      </div>
    </div>

    <div
      x-show="!isCollapsed"
      x-collapse
      x-cloak
      x-transition.opacity.duration.300ms
      class="flex h-full min-h-0 flex-col overflow-hidden rounded-b-xl bg-white"
      x-bind:class="isFullscreen ? 'flex-1' : ''"
    >
      @if (isset($controls))
        <div class="px-2 pt-2">{{ $controls }}</div>
      @endif

      @php
        $editorHeight = isset($controls)
          ? "max-h-[40vh] md:max-h-[45vh]" // Lebih pendek karena ruang atas dipakai kontrol
          : "max-h-[55vh] md:max-h-[60vh]"; // Bisa lebih panjang karena tidak ada kontrol
      @endphp
      <!-- TENGAH: AREA EDITOR (Sekarang bisa di-scroll secara normal) -->
      <div
        {{-- 🌟 1. Tambahkan overflow-y-auto dan scrollbar-thin border-coral border border-dashed --}}
        class="mx-2 mt-0 mb-2 flex scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent scrollbar-gutter-both flex-col gap-4 overflow-y-auto rounded-b-xl border-x border-b border-gray-400 transition-all duration-300"
        x-bind:class="
          isFullscreen 
            ? 'flex-1' 
            : '{{ $editorHeight }} max-h-[45vh] md:max-h-[50vh]' 
            "
        {{-- 🌟 2. Batasi tinggi maksimal (misal 60% dari tinggi layar) --}}
      >
        {{ $slot }}
      </div>

      <!-- 🌟 BAWAH: AREA PREVIEW (Selalu Tampil!) -->
      @if (isset($preview))
        <div
          {{-- Area ini akan otomatis "lengket" di bawah editor yang bisa di-scroll --}}
          class="shrink-0 border-t border-gray-100 bg-gray-50/50 p-2 transition-all duration-300"
          x-bind:class="
            isFullscreen
              ? 'bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)] z-40'
              : 'bg-gray-50/50'
          "
        >
          {{ $preview }}
        </div>
      @endif
    </div>
  </div>
</div>
