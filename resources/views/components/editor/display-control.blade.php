@props(['activeLocales' => []])

<!-- Gunakan flex-nowrap agar tidak turun baris, dan overflow-x-auto sebagai pengaman di HP kecil -->
<div class="flex items-center gap-2 w-full overflow-x-auto scrollbar-hide" style="-ms-overflow-style: none; scrollbar-width: none;">

  <!-- ==========================================
      BAGIAN 1: KONTROL TAMPILAN (Hanya Muncul di PC >= 1366px)
      ========================================== -->
  <div x-show="windowWidth >= 1366" class="flex items-center bg-gray-100 p-1 rounded-lg border border-gray-200 shadow-inner shrink-0">
    <button type="button" x-on:click="layoutMode = 'single'" :class="effectiveLayout === 'single' ? 'bg-white text-foresty shadow-sm font-bold' : 'text-foresty hover:text-forest'"
      class="px-4 py-1 text-xs rounded-md transition cursor-pointer select-none">
      Tunggal
    </button>
    <button type="button" x-on:click="layoutMode = 'split'" :class="effectiveLayout === 'split' ? 'bg-white text-foresty shadow-sm font-bold' : 'text-foresty hover:text-forest'"
      class="px-4 py-1 text-xs rounded-md transition cursor-pointer select-none">
      Ganda
    </button>
  </div>

  <!-- ==========================================
      BAGIAN 2: KONTROL BAHASA (Dibuat Kompak & Menyusut)
      ========================================== -->
  <div class="flex items-center bg-gray-100 p-1 rounded-lg border border-gray-200 shadow-inner shrink-0">
    <!-- Muncul jika mode Single (Default di HP/Tablet) -->
    <div x-show="effectiveLayout === 'single'" class="flex items-center gap-1.5 px-1 sm:px-2">
      <!-- Label BAHASA disembunyikan di HP agar muat 1 baris -->
      <span x-show="windowWidth >= 768" class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Bahasa:</span>
      <select x-model="singleActiveLang" class="border-gray-300 rounded text-xs font-bold py-0.5 pl-2 pr-6 h-6 bg-white shadow-sm focus:ring-foresty focus:border-foresty text-foresty">
        @foreach ($activeLocales as $code)
          <option value="{{ $code }}">{{ strtoupper($code) }}</option>
        @endforeach
      </select>
    </div>
    <!-- Muncul jika mode Split (Hanya di PC) -->
    <div x-show="effectiveLayout === 'split'" class="flex items-center gap-2 px-2">
      <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Kolom:</span>
      <div class="flex items-center gap-1 flex-wrap">
        <template x-for="activeLang in splitLanguages" :key="activeLang">
          <div class="flex items-center gap-1 bg-white border border-gray-200 px-1.5 py-0.5 rounded shadow-sm">
            <span class="text-[10px] font-bold text-foresty uppercase" x-text="activeLang"></span>
            <button type="button" @click="removeSplitLang(activeLang)" x-show="splitLanguages.length > 1" class="text-red-400 hover:text-red-600 text-xs font-bold leading-none">×</button>
          </div>
        </template>
        <select @change="addSplitLang($event.target.value); $event.target.value = '';" x-show="splitLanguages.length < ((window.innerWidth > 1440 && allLocalesCount >= 3) ? 3 : 2)"
          class="border-dashed border-gray-300 rounded text-[10px] font-medium text-gray-500 py-0.5 pl-1 pr-5 h-6 bg-gray-50 hover:bg-gray-100 cursor-pointer">
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
  <div class="flex items-center gap-1.5 sm:gap-2 ml-auto shrink-0">

    <!-- BAGIAN 3: TABS (META & KONTEN) -->
    <div class="flex items-center bg-gray-100 p-1 rounded-lg border border-gray-200 shadow-inner">
      <button type="button" @click="editorTab = 'meta'"
        :class="[
            editorTab === 'meta' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700',
            windowWidth < 768 ? 'p-1.5' : 'px-3 py-1'
        ]"
        class="flex items-center justify-center rounded-md transition cursor-pointer select-none">
        <x-dynamic-component :component="'lucide-tags'" class="h-4 w-4" stroke-width="2.5" />
        <span x-show="windowWidth >= 768" class="ml-1.5 text-xs font-bold">Metadata</span>
      </button>

      <button type="button" @click="editorTab = 'content'"
        :class="[
            editorTab === 'content' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700',
            windowWidth < 768 ? 'p-1.5' : 'px-3 py-1'
        ]"
        class="flex items-center justify-center rounded-md transition cursor-pointer select-none">
        <x-dynamic-component :component="'lucide-file-text'" class="h-4 w-4" stroke-width="2.5" />
        <span x-show="windowWidth >= 768" class="ml-1.5 text-xs font-bold">Konten</span>
      </button>
    </div>

    <!-- BAGIAN 4 & 5: TOMBOL AKSI UTAMA -->
    <div class="flex items-center gap-1.5 sm:gap-2">
      <!-- Buka/Tutup Semua Blok -->
      <button x-on:click="editorTab = 'content'; allCollapsed = !allCollapsed; $dispatch('toggle-collapse-all', allCollapsed)"
        :class="[
            allCollapsed ? 'bg-white text-foresty shadow-sm font-bold border-foresty' : 'text-gray-500 hover:text-gray-700 border-gray-200 bg-white',
            windowWidth < 768 ? 'p-1.5' : 'px-3 py-1.5'
        ]"
        class="flex items-center justify-center border hover:bg-gray-50 rounded-lg shadow-sm transition-all focus:outline-none disabled:opacity-50 select-none cursor-pointer">
        <x-dynamic-component x-show="allCollapsed" :component="'lucide-list-chevrons-down-up'" class="h-4 w-4 shrink-0" stroke-width="2.5" />
        <x-dynamic-component x-show="!allCollapsed" :component="'lucide-list-chevrons-up-down'" class="h-4 w-4 shrink-0" stroke-width="2.5" />
        <span x-show="windowWidth >= 768" class="ml-1.5 text-xs truncate font-bold" x-text="allCollapsed ? 'Buka Semua Blok' : 'Tutup Semua Blok'"></span>
      </button>

      <!-- Pratinjau -->
      <button wire:click="saveAndPreview" wire:loading.attr="disabled" type="button" :class="windowWidth < 768 ? 'p-1.5' : 'px-3 py-1.5'"
        class="flex justify-center items-center bg-white border border-foresty text-foresty hover:bg-foresty hover:text-white rounded-lg shadow-sm transition-all focus:outline-none disabled:opacity-50 select-none cursor-pointer">
        <x-dynamic-component :component="'lucide-eye'" class="h-4 w-4" stroke-width="2.5" />
        <span x-show="windowWidth >= 768" class="ml-1.5 text-xs font-bold">Pratinjau</span>
      </button>

      <!-- Simpan -->
      <button wire:click="save" :class="windowWidth < 768 ? 'p-1.5' : 'px-4 py-1.5'"
        class="flex justify-center items-center bg-foresty hover:bg-forest text-white rounded-lg shadow-sm transition select-none cursor-pointer">
        <x-dynamic-component :component="'lucide-save-check'" class="h-4 w-4" stroke-width="2.5" />
        <x-dynamic-component :component="'lucide-save-plus'" class="h-4 w-4 hidden" stroke-width="2.5" />
        <span x-show="windowWidth >= 768" class="ml-1.5 text-xs font-semibold">Simpan</span>
      </button>
    </div>
  </div>

</div>
