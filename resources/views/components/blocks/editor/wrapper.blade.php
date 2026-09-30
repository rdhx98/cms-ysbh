@props (["blockId", "block",])

<div
  id="block-wrapper-{{ $blockId }}"
  wire:key="block-{{ $blockId }}"
  x-sort:item="'{{ $blockId }}'"
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
  class="group relative mb-6 flex w-full flex-col rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200"
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
        ? 'fixed inset-0 z-[9999] bg-gray-50 h-screen'
        : 'relative rounded-xl border border-gray-200 bg-white shadow-sm'
    "
  >
    <!-- HEADER GLOBAL BLOK (Sticky) -->
    <div
      class="sticky top-0 z-30 flex items-center justify-between rounded-t-xl border-b border-gray-200 bg-gray-50/95 px-4 py-2 shadow-[0_4px_10px_rgba(0,0,0,0.03)] backdrop-blur-md transition-all"
    >
      <!-- KIRI: Drag Handle & Identitas Blok (Bisa dikustomisasi via Slot) -->
      <div class="flex items-center gap-3">
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

        <div
          class="text-xxs flex items-center gap-3 font-extrabold tracking-widest text-gray-500 uppercase"
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
          <div
            class="flex items-center gap-2 border-r border-gray-300 pr-3 sm:mr-1"
          >
            {{ $settings }}
          </div>
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
        <div class="hidden h-4 w-px bg-gray-300 sm:block"></div>

        <!-- Tombol Duplikat -->
        <button
          wire:click="duplicateBlock('{{ $blockId }}')"
          type="button"
          class="hover:bg-sage-soft hover:text-foresty rounded p-1.5 text-gray-500 transition-colors outline-none"
          title="Gandakan Blok"
        >
          <x-dynamic-component component="lucide-copy" class="h-4 w-4" />
        </button>

        <!-- Tombol Hapus -->
        <button
          wire:click="removeBlock('{{ $blockId }}')"
          type="button"
          wire:confirm="Hapus blok ini beserta isinya?"
          class="rounded p-1.5 text-red-400 transition-colors outline-none hover:bg-red-50 hover:text-red-600"
          title="Hapus Blok"
        >
          <x-dynamic-component component="lucide-trash-2" class="h-4 w-4" />
        </button>
        <!-- 🌟 TOMBOL FULLSCREEN (FOKUS) -->
        <button
          type="button"
          x-on:click="toggleFullscreen()"
          class="rounded p-1 transition-colors outline-none"
          x-bind:class="
            isFullscreen
              ? 'bg-foresty text-white hover:bg-forest'
              : 'text-gray-400 hover:bg-foresty hover:text-white'
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
        <!-- Tombol collapse runtuh -->
        <button
          type="button"
          x-on:click="isCollapsed = !isCollapsed"
          class="hover:bg-sage-soft text-foresty cursor-pointer rounded-full p-1 transition-all duration-200 focus:outline-none"
        >
          <x-dynamic-component
            component="lucide-circle-chevron-down"
            class="text-foresty h-4 w-4 transition-transform duration-200"
            x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'"
          />
        </button>
      </div>
    </div>

    <!-- OLD AREA KONTEN UTAMA -->
    {{-- <div x-show="!isCollapsed" x-collapse x-cloak class="flex flex-col p-4">
      {{ $slot }}
    </div> --}}

    <!-- AREA KONTEN (Tengah & Bawah) -->
    {{-- <div
      x-show="!isCollapsed"
      x-collapse
      x-cloak
      class="flex h-full min-h-0 flex-col overflow-hidden"
      x-bind:class="isFullscreen ? 'flex-1' : ''"
    >
      <!-- TENGAN: AREA EDITOR (Bisa di-scroll saat fullscreen) -->
      <div
        class="flex flex-col"
        x-bind:class="
          isFullscreen ? 'flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8' : 'p-4'
        "
      >
        {{ $slot }}
      </div>

      <!-- 🌟 BAWAH: AREA PREVIEW (Permanen di Bawah saat Fullscreen) -->
      @if (isset($preview))
        <div
          x-show="isFullscreen"
          x-cloak
          x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="translate-y-full opacity-0"
          x-transition:enter-end="translate-y-0 opacity-100"
          class="z-40 shrink-0 border-t border-gray-200 bg-white p-4 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)]"
        >
          {{ $preview }}
        </div>
      @endif
    </div> --}}
    <!-- AREA KONTEN (Tengah & Bawah) -->
    <div
      x-show="!isCollapsed"
      x-collapse
      x-cloak
      class="flex h-full min-h-0 flex-col overflow-hidden"
      x-bind:class="isFullscreen ? 'flex-1' : ''"
    >
      <!-- TENGAH: AREA EDITOR (Bisa di-scroll saat fullscreen) -->
      <div
        class="flex flex-col"
        x-bind:class="
          isFullscreen ? 'flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8' : 'p-4'
        "
      >
        {{ $slot }}
      </div>

      <!-- 🌟 BAWAH: AREA PREVIEW (Selalu Tampil!) -->
      @if (isset($preview))
        <div
          class="shrink-0 border-t border-gray-100 bg-gray-50/50 p-4 transition-all duration-300"
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
