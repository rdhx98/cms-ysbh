@props (['activeLocales' => []])

<!-- Gunakan flex-nowrap agar tidak turun baris, dan overflow-x-auto sebagai pengaman di HP kecil -->
<div
  class="scrollbar-hide flex w-full items-center gap-2 overflow-x-auto"
  style="-ms-overflow-style: none; scrollbar-width: none"
>
  <!-- ==========================================
      BAGIAN 1: KONTROL TAMPILAN (Hanya Muncul di PC >= 1366px)
      ========================================== -->
  <div
    x-show="windowWidth >= 1366"
    class="flex shrink-0 items-center rounded-lg border border-gray-200 bg-gray-100 p-1 shadow-inner"
  >
    <button
      type="button"
      x-on:click="layoutMode = 'single'"
      :class="effectiveLayout === 'single'
        ? 'bg-white text-foresty shadow-sm font-bold'
        : 'text-foresty hover:text-forest'"
      class="cursor-pointer rounded-md px-4 py-1 text-xs transition select-none"
    >
      Tunggal
    </button>
    <button
      type="button"
      x-on:click="layoutMode = 'split'"
      :class="effectiveLayout === 'split'
        ? 'bg-white text-foresty shadow-sm font-bold'
        : 'text-foresty hover:text-forest'"
      class="cursor-pointer rounded-md px-4 py-1 text-xs transition select-none"
    >
      Ganda
    </button>
  </div>

  <!-- ==========================================
      BAGIAN 2: KONTROL BAHASA (Dibuat Kompak & Menyusut)
      ========================================== -->
  <div
    class="flex shrink-0 items-center rounded-lg border border-gray-200 bg-gray-100 p-1 shadow-inner"
  >
    <!-- Muncul jika mode Single (Default di HP/Tablet) -->
    <div
      x-show="effectiveLayout === 'single'"
      class="flex items-center gap-1.5 px-1 sm:px-2"
    >
      <!-- Label BAHASA disembunyikan di HP agar muat 1 baris -->
      <span
        x-show="windowWidth >= 768"
        class="text-[10px] font-bold tracking-wide text-gray-400 uppercase"
        >Bahasa:</span
      >
      <select
        x-model="singleActiveLang"
        class="focus:ring-foresty focus:border-foresty text-foresty h-6 rounded border-gray-300 bg-white py-0.5 pr-6 pl-2 text-xs font-bold shadow-sm"
      >
        @foreach ($activeLocales as $code)
          <option value="{{ $code }}">{{ strtoupper($code) }}</option>
        @endforeach
      </select>
    </div>
    <!-- Muncul jika mode Split (Hanya di PC) -->
    <div
      x-show="effectiveLayout === 'split'"
      class="flex items-center gap-2 px-2"
    >
      <span class="text-[10px] font-bold tracking-wide text-gray-400 uppercase"
        >Kolom:</span
      >
      <div class="flex flex-wrap items-center gap-1">
        <template x-for="activeLang in splitLanguages" :key="activeLang">
          <div
            class="flex items-center gap-1 rounded border border-gray-200 bg-white px-1.5 py-0.5 shadow-sm"
          >
            <span
              class="text-foresty text-[10px] font-bold uppercase"
              x-text="activeLang"
            ></span>
            <button
              type="button"
              @click="removeSplitLang(activeLang)"
              x-show="splitLanguages.length > 1"
              class="text-xs leading-none font-bold text-red-400 hover:text-red-600"
            >
              ×
            </button>
          </div>
        </template>
        <select
          @change="
            addSplitLang($event.target.value);
            $event.target.value = '';
          "
          x-show="
            splitLanguages.length <
            (window.innerWidth > 1440 && allLocalesCount >= 3 ? 3 : 2)
          "
          class="h-6 cursor-pointer rounded border-dashed border-gray-300 bg-gray-50 py-0.5 pr-5 pl-1 text-[10px] font-medium text-gray-500 hover:bg-gray-100"
        >
          <option value="">+ Tambah</option>
          @foreach ($activeLocales as $code)
            <option value="{{ $code }}">{{ strtoupper($code) }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </div>

  <!-- ==========================================
      BARIS AKSI KANAN (Akan menempel ke kanan berkat ml-auto)
      ========================================== -->
  <div class="ml-auto flex shrink-0 items-center gap-1.5 sm:gap-2">
    <!-- BAGIAN 3: TABS (META & KONTEN) -->
    <div
      class="flex items-center rounded-lg border border-gray-200 bg-gray-100 p-1 shadow-inner"
    >
      <button
        type="button"
        @click="editorTab = 'meta'"
        :class="[
          editorTab === 'meta'
            ? 'bg-white text-foresty shadow-sm'
            : 'text-gray-500 hover:text-gray-700',
          windowWidth < 768 ? 'p-1.5' : 'px-3 py-1',
        ]"
        class="flex cursor-pointer items-center justify-center rounded-md transition select-none"
      >
        <x-dynamic-component
          :component="'lucide-tags'"
          class="h-4 w-4"
          stroke-width="2.5"
        />
        <span x-show="windowWidth >= 768" class="ml-1.5 text-xs font-bold"
          >Metadata</span
        >
      </button>

      <button
        type="button"
        @click="editorTab = 'content'"
        :class="[
          editorTab === 'content'
            ? 'bg-white text-foresty shadow-sm'
            : 'text-gray-500 hover:text-gray-700',
          windowWidth < 768 ? 'p-1.5' : 'px-3 py-1',
        ]"
        class="flex cursor-pointer items-center justify-center rounded-md transition select-none"
      >
        <x-dynamic-component
          :component="'lucide-file-text'"
          class="h-4 w-4"
          stroke-width="2.5"
        />
        <span x-show="windowWidth >= 768" class="ml-1.5 text-xs font-bold"
          >Konten</span
        >
      </button>
    </div>

    <!-- BAGIAN 4 & 5: TOMBOL AKSI UTAMA -->
    <div class="flex items-center gap-1.5 sm:gap-2">
      <!-- Buka/Tutup Semua Blok -->
      <!-- 🌟 PEMBUNGKUS RADAR UNTUK TOMBOL -->
      <div
        x-data="{
          pinnedBlocks: [],
          get isAnyPinned() {
            return this.pinnedBlocks.length > 0;
          },
        }"
        @global-pin-update.window="
          if ($event.detail.active) {
            // Masukkan ID blok jika belum ada
            if (!pinnedBlocks.includes($event.detail.id))
              pinnedBlocks.push($event.detail.id);
          } else {
            // Buang ID blok dari daftar
            pinnedBlocks = pinnedBlocks.filter((id) => id !== $event.detail.id);
          }
        "
      >
        <button
          x-on:click="
            editorTab = 'content';
            allCollapsed = !allCollapsed;
            $dispatch('toggle-collapse-all', allCollapsed);
          "
          {{-- 🌟 1. Kunci tombol jika ada blok di dalam daftar pinnedBlocks --}}
          :disabled="isAnyPinned"
          :class="[
            {{-- 🌟 2. Timpa gaya jika tombol terkunci --}}
            isAnyPinned
              ? 'cursor-not-allowed bg-gray-100 text-gray-400 border-gray-200 opacity-60'
              : (allCollapsed ? 'bg-white text-foresty shadow-sm font-bold border-foresty cursor-pointer' : 'text-gray-500 hover:text-gray-700 border-gray-200 bg-white hover:bg-gray-50 cursor-pointer'),
            windowWidth < 768 ? 'p-1.5' : 'px-3 py-1.5'
          ]"
          class="flex items-center justify-center rounded-lg border shadow-sm transition-all select-none focus:outline-none"
        >
          <x-dynamic-component
            x-show="allCollapsed"
            :component="'lucide-list-chevrons-down-up'"
            class="h-4 w-4 shrink-0"
            stroke-width="2.5"
          />
          <x-dynamic-component
            x-show="!allCollapsed"
            :component="'lucide-list-chevrons-up-down'"
            class="h-4 w-4 shrink-0"
            stroke-width="2.5"
          />
          <span
            x-show="windowWidth >= 768"
            class="ml-1.5 truncate text-xs font-bold"
            x-text="allCollapsed ? 'Buka Semua Blok' : 'Tutup Semua Blok'"
          ></span>
        </button>
      </div>
      {{-- <button
        x-on:click="
          editorTab = 'content';
          allCollapsed = !allCollapsed;
          $dispatch('toggle-collapse-all', allCollapsed);
        "
        :class="[
          allCollapsed
            ? 'bg-white text-foresty shadow-sm font-bold border-foresty'
            : 'text-gray-500 hover:text-gray-700 border-gray-200 bg-white',
          windowWidth < 768 ? 'p-1.5' : 'px-3 py-1.5',
        ]"
        class="flex cursor-pointer items-center justify-center rounded-lg border shadow-sm transition-all select-none hover:bg-gray-50 focus:outline-none disabled:opacity-50"
      >
        <x-dynamic-component
          x-show="allCollapsed"
          :component="'lucide-list-chevrons-down-up'"
          class="h-4 w-4 shrink-0"
          stroke-width="2.5"
        />
        <x-dynamic-component
          x-show="!allCollapsed"
          :component="'lucide-list-chevrons-up-down'"
          class="h-4 w-4 shrink-0"
          stroke-width="2.5"
        />
        <span
          x-show="windowWidth >= 768"
          class="ml-1.5 truncate text-xs font-bold"
          x-text="allCollapsed ? 'Buka Semua Blok' : 'Tutup Semua Blok'"
        ></span>
      </button> --}}

      <!-- Pratinjau -->
      <button
        wire:click="saveAndPreview"
        wire:loading.attr="disabled"
        type="button"
        :class="windowWidth < 768 ? 'p-1.5' : 'px-3 py-1.5'"
        class="border-foresty text-foresty hover:bg-foresty flex cursor-pointer items-center justify-center rounded-lg border bg-white shadow-sm transition-all select-none hover:text-white focus:outline-none disabled:opacity-50"
      >
        <x-dynamic-component
          :component="'lucide-eye'"
          class="h-4 w-4"
          stroke-width="2.5"
        />
        <span x-show="windowWidth >= 768" class="ml-1.5 text-xs font-bold"
          >Pratinjau</span
        >
      </button>

      <!-- Simpan -->
      <button
        wire:click="save"
        :class="windowWidth < 768 ? 'p-1.5' : 'px-4 py-1.5'"
        class="bg-foresty hover:bg-forest flex cursor-pointer items-center justify-center rounded-lg text-white shadow-sm transition select-none"
      >
        <x-dynamic-component
          :component="'lucide-save-check'"
          class="h-4 w-4"
          stroke-width="2.5"
        />
        <x-dynamic-component
          :component="'lucide-save-plus'"
          class="hidden h-4 w-4"
          stroke-width="2.5"
        />
        <span x-show="windowWidth >= 768" class="ml-1.5 text-xs font-semibold"
          >Simpan</span
        >
      </button>
    </div>
  </div>
</div>
