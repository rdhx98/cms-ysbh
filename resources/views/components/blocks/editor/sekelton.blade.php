{{-- VERSION 2 --}}
@props (['blockId', 'block', 'code'])

@php
  // Ambil data spesifik blok di sini
  $data =$block['data'] ?? [];
  // $variabel_lain = ...
@endphp

<!-- 🌟 PEMBUNGKUS LUAR BLOK -->
<div
  x-data="{
    // State Wajib Editor
    isPinned: false,
    isRowPinned: false,
    isCollapsed: false,
    pinStyle: '',

    // 🌟 PELAPOR STATUS KE RADAR PUSAT (ANTI-CRASH)
    init() {
        const reportPinStatus = () => {
            this.$dispatch('global-pin-update', { 
                id: '{{ $blockId }}', 
                active: this.isPinned || this.isRowPinned 
            });
        };
        
        this.$watch('isPinned', reportPinStatus);
        this.$watch('isRowPinned', reportPinStatus);
    },

    // 🌟 LOGIKA TOMBOL FOCUS PIN
    togglePin() {
        if (this.isCollapsed) {
            this.isCollapsed = false;
            this.$dispatch('sync-collapse-{{ strtolower($blockId) }}', false);
        }
        if (!this.isPinned && this.isRowPinned) {
            this.$dispatch('force-close-row-pin-{{ strtolower($blockId) }}');
        }

        this.isPinned = !this.isPinned;
        if (this.isPinned) {
            let area = document.getElementById('main-editor-scroll-area');
            if (area) {
                let rect = area.getBoundingClientRect();
                this.pinStyle = `position: fixed !important; top: ${rect.top + 5}px !important; left: ${rect.left + 16}px !important; width: ${rect.width - 32}px !important; height: ${rect.height - 10}px !important; z-index: 60 !important; margin: 0 !important;`;
                this.$refs.placeholder.style.height = this.$refs.editor.offsetHeight + 'px';
            }
        } else {
            this.pinStyle = '';
        }
    },

    // 🌟 LOGIKA TOMBOL COLLAPSE
    toggleCollapse() {
        this.isCollapsed = !this.isCollapsed;
        
        if (this.isCollapsed) {
            if (this.isPinned) {
                this.isPinned = false;
                this.pinStyle = '';
            }
            if (this.isRowPinned) {
                this.$dispatch('force-close-row-pin-{{ strtolower($blockId) }}');
            }
        }
        // Laporkan perubahan ini ke bahasa lain di split view
        this.$dispatch('sync-collapse-{{ strtolower($blockId) }}', this.isCollapsed);
    }
  }"
  {{-- 🌟 DAFTAR PENDENGAR (LISTENERS) --}}
  @toggle-row-pin-{{ strtolower($blockId) }}.window="
    isRowPinned = !isRowPinned;
    if (isRowPinned) {
        if (isPinned) { isPinned = false; pinStyle = ''; }
        if (isCollapsed) {
            isCollapsed = false;
            $dispatch('sync-collapse-{{ strtolower($blockId) }}', false);
        }
    }
  "
  @force-close-row-pin-{{ strtolower($blockId) }}.window="isRowPinned = false"
  @toggle-collapse-all.window="
    isCollapsed = $event.detail;
    if (isCollapsed && isPinned) {
      isPinned = false;
      pinStyle = '';
    }
  "
  @sync-collapse-{{ strtolower($blockId) }}.window="
    isCollapsed = $event.detail;
    // Pengamanan tambahan: jika disuruh runtuh via sync, pastikan pin mati
    if (isCollapsed && isPinned) { isPinned = false; pinStyle = ''; }
  "
  {{-- 🌟 GAYA DASAR PEMBUNGKUS LUAR --}}
  class="flex w-full flex-col"
  x-bind:class="isPinned || isRowPinned ? 'h-full flex-1 min-h-0' : ''"
>
  {{-- 🌟 PLACEHOLDER (Muncul saat Focus Pin aktif) --}}
  <div
    x-ref="placeholder"
    x-show="isPinned"
    x-cloak
    class="flex w-full items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50"
  >
    <div class="py-10 text-center">
      <x-dynamic-component
        component="lucide-maximize"
        class="mx-auto mb-2 h-6 w-6 text-gray-400"
      />
      <span class="text-xs font-bold tracking-widest text-gray-400 uppercase"
        >Mode Fokus Sedang Aktif</span
      >
    </div>
  </div>

  {{-- 🌟 EDITOR UTAMA --}}
  <div
    x-ref="editor"
    :style="isPinned ? pinStyle : ''"
    class="flex flex-col bg-white transition-all duration-200"
    x-bind:class="
      isPinned
        ? 'border-foresty ring-4 ring-foresty/20 shadow-2xl overflow-hidden flex-1 min-h-0'
        : isRowPinned
          ? 'border-foresty/50 ring-2 ring-foresty/20 overflow-hidden flex-1 min-h-0 max-h-[calc(100vh-120px)]'
          : 'rounded-xl border border-gray-200 shadow-md relative h-auto'
    "
  >
    <!-- HEADER BLOK -->
    <div
      class="flex shrink-0 items-center justify-between rounded-t-xl border-b border-gray-200 bg-gray-100 p-2"
    >
      <!-- Judul & Ikon Blok -->
      <div class="flex items-center gap-2">
        <div class="bg-sage-soft rounded-md p-1">
          <x-dynamic-component
            component="lucide-box"
            class="text-foresty h-4 w-4"
          />
        </div>
        <span
          class="text-xs font-extrabold tracking-widest text-gray-500 uppercase"
          >Nama Blok Anda</span
        >
      </div>

      <!-- Kumpulan Kontrol Kanan -->
      <div class="flex items-center gap-4">
        <!-- Kontrol Spesifik Blok (Dropdown Grid, dsb) -->
        <div class="flex items-center gap-2" x-show="!isCollapsed">
          <!-- (Tambahkan kontrol spesifik Anda di sini) -->
          <span
            class="text-foresty bg-sage-soft shrink-0 rounded px-1.5 py-0.5 text-xs font-bold uppercase shadow-sm"
            >{{ $code }}</span
          >
        </div>

        <!-- 🌟 TOMBOL KONTROL UTAMA (Dilengkapi :disabled) -->
        <div class="flex items-center gap-1 border-l border-gray-300 pl-4">
          <!-- 1. Tombol Row Pin -->
          <button
            type="button"
            :disabled="isPinned || isCollapsed"
            x-on:click="$dispatch('toggle-row-pin-{{ strtolower($blockId) }}')"
            x-bind:class="
              isPinned || isCollapsed
                ? 'bg-gray-100 text-gray-300 cursor-not-allowed opacity-50'
                : isRowPinned
                  ? 'bg-foresty/10 text-foresty shadow-inner'
                  : 'bg-gray-200 text-gray-500 hover:text-foresty hover:bg-gray-300'
            "
            class="flex items-center justify-center rounded-md p-1.5 transition-colors outline-none"
            title="Pin Baris (Split View)"
          >
            <x-dynamic-component
              component="lucide-columns"
              class="h-3.5 w-3.5"
            />
          </button>

          <!-- 2. Tombol Focus Pin -->
          <button
            type="button"
            :disabled="isRowPinned || isCollapsed"
            @click="togglePin()"
            x-bind:class="
              isRowPinned || isCollapsed
                ? 'bg-gray-100 text-gray-300 cursor-not-allowed opacity-50'
                : isPinned
                  ? 'bg-foresty text-white shadow-inner'
                  : 'bg-gray-200 text-gray-500 hover:text-foresty hover:bg-gray-300'
            "
            class="flex items-center justify-center rounded-md p-1.5 shadow-sm transition-colors outline-none"
            title="Fokus Layar Penuh"
          >
            <x-dynamic-component
              component="lucide-maximize"
              class="h-3.5 w-3.5"
              x-bind:class="isPinned ? 'scale-90' : ''"
            />
          </button>

          <!-- 3. Tombol Collapse -->
          <button
            type="button"
            :disabled="isPinned || isRowPinned"
            @click="toggleCollapse()"
            x-bind:class="
              isPinned || isRowPinned
                ? 'text-gray-300 cursor-not-allowed opacity-50'
                : 'hover:text-foresty text-gray-400 hover:bg-gray-200'
            "
            class="rounded-md p-1.5 transition-colors outline-none"
            title="Lipat Blok"
          >
            <x-dynamic-component
              component="lucide-chevron-down"
              class="h-4 w-4 transition-transform duration-300"
              x-bind:class="isCollapsed ? 'rotate-180' : ''"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- 🌟 BUNGKUSAN LIPATAN & ISI (Otomatis Scroll saat Pin) -->
    <div
      x-show="!isCollapsed"
      x-collapse
      x-cloak
      class="flex flex-col"
      x-bind:class="isPinned || isRowPinned ? 'flex-1 min-h-0' : ''"
    >
      <!-- Area Konten Blok Spesifik -->
      <div
        class="flex flex-col border border-gray-200 bg-gray-50 p-4 sm:p-5"
        x-bind:class="
          isPinned || isRowPinned
            ? 'flex-1 min-h-0 overflow-y-auto scrollbar-thin rounded-none'
            : 'rounded-b-xl'
        "
      >
        <!-- ISI BLOK ANDA DI SINI -->
        <div class="rounded-lg bg-white p-4 shadow-sm">
          <p class="text-sm text-gray-500">Formulir atau konten blok diletakkan di sini.</p>
        </div>
      </div>

      <!-- 🌟 (OPSIONAL) AREA PREVIEW BLOK -->
      <div
        class="flex shrink-0 flex-col overflow-hidden rounded-b-xl border-t border-gray-200 bg-gray-50"
        x-bind:class="
          isPinned || isRowPinned
            ? 'max-h-[35vh] border-t-2 border-foresty/20'
            : ''
        "
      >
        <div class="shrink-0 border-b border-gray-200 bg-gray-200/60 px-4 py-2">
          <span
            class="text-[10px] font-bold tracking-widest text-gray-500 uppercase"
            >Live Preview ({{ strtoupper($code) }})</span
          >
        </div>

        <div
          class="flex w-full justify-center bg-gray-50 p-6 md:p-10"
          x-bind:class="
            isPinned || isRowPinned
              ? 'flex-1 overflow-y-auto scrollbar-thin'
              : 'min-h-[150px]'
          "
        >
          <!-- Include view komponen preview Anda di sini -->
        </div>
      </div>
    </div>
  </div>
</div>

{{-- VERSION 1 --}}
@props (['blockId', 'block', 'code'])

<div
  x-data="{
    isPinned: false,
    isCollapsed: false,
    pinStyle: '',

    togglePin() {
        if (this.isCollapsed) this.isCollapsed = false;

        this.isPinned = !this.isPinned;
        if (this.isPinned) {
            let area = document.getElementById('main-editor-scroll-area');
            if (area) {
                let rect = area.getBoundingClientRect();
                this.pinStyle = `position: fixed; top: ${rect.top + 5}px; left: ${rect.left + 16}px; width: ${rect.width - 32}px; height: ${rect.height - 10}px; z-index: 40;`;
                this.$refs.placeholder.style.height = this.$refs.editor.offsetHeight + 'px';
            }
        } else {
            this.pinStyle = '';
        }
    },

    toggleCollapse() {
        this.isCollapsed = !this.isCollapsed;
        if (this.isCollapsed && this.isPinned) {
            this.togglePin();
        }
        $dispatch('sync-collapse-{{ strtolower($blockId) }}', this.isCollapsed);
    }
}"
  @resize.window="
    if (isPinned) {
      togglePin();
      togglePin();
    }
  "
  @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail; if(isCollapsed && isPinned) { togglePin(); }"
  @toggle-collapse-all.window="
    isCollapsed = $event.detail;
    if (isCollapsed && isPinned) {
      togglePin();
    }
  "
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; if(isPinned) { togglePin(); } }"
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }"
  class="w-full"
>
  <!-- 🌟 PLACEHOLDER FOKUS -->
  <div
    x-ref="placeholder"
    x-show="isPinned"
    x-cloak
    class="flex w-full items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50"
  >
    <div class="text-center">
      <x-dynamic-component
        component="lucide-maximize"
        class="mx-auto mb-2 h-6 w-6 text-gray-400"
      />
      <span class="text-xs font-bold tracking-widest text-gray-400 uppercase"
        >Mode Fokus Sedang Aktif</span
      >
    </div>
  </div>

  <!-- 🌟 EDITOR UTAMA -->
  <div
    x-ref="editor"
    :style="pinStyle"
    class="flex flex-col rounded-xl bg-white transition-all duration-200"
    x-bind:class="
      isPinned
        ? 'border border-foresty ring-4 ring-foresty/20 shadow-2xl overflow-hidden'
        : 'border border-gray-200 shadow-md relative h-auto'
    "
  >
    <!-- HEADER -->
    <div
      class="flex shrink-0 items-center justify-between rounded-t-xl border-b border-gray-200 bg-gray-100 p-2"
    >
      <div class="flex items-center gap-2">
        <div class="bg-sage-soft rounded-md p-1">
          <x-dynamic-component
            component="lucide-type"
            class="text-foresty h-4 w-4"
          />
        </div>
        <span
          class="text-xs font-extrabold tracking-widest text-gray-500 uppercase"
          >NAMA BLOK DI SINI</span
        >
      </div>

      <div class="flex items-center gap-4">
        <span
          class="text-foresty bg-sage-soft shrink-0 rounded px-1.5 py-0.5 text-xs font-bold uppercase shadow-sm"
          x-show="!isCollapsed"
          >{{ $code }}</span
        >

        <div class="flex items-center gap-1 border-l border-gray-300 pl-4">
          {{-- 1. TOMBOL PIN BARIS (Split View Pin) --}}
          <button
            type="button"
            @click="$dispatch('toggle-row-pin-{{ $blockId }}')"
            class="hover:text-foresty rounded-md p-1.5 text-gray-500 transition-colors outline-none hover:bg-gray-200"
            title="Pin Baris (Split View)"
          >
            <x-dynamic-component
              component="lucide-columns"
              class="h-3.5 w-3.5"
            />
          </button>

          {{-- 2. TOMBOL PIN FOKUS (Layar Penuh Bahasa Ini) --}}
          <button
            type="button"
            @click="togglePin()"
            x-bind:class="
              isPinned
                ? 'bg-foresty text-white shadow-inner'
                : 'bg-gray-200 text-gray-500 hover:text-foresty hover:bg-gray-300'
            "
            class="flex items-center justify-center rounded-md p-1.5 shadow-sm transition-colors outline-none"
            title="Fokus Layar Penuh"
          >
            <x-dynamic-component
              component="lucide-maximize"
              class="h-3.5 w-3.5"
              x-bind:class="isPinned ? 'scale-90' : ''"
            />
          </button>

          {{-- 3. TOMBOL LIPAT (COLLAPSE) --}}
          <button
            type="button"
            @click="toggleCollapse()"
            class="hover:text-foresty rounded-md p-1.5 text-gray-400 transition-colors outline-none hover:bg-gray-200"
            title="Lipat / Buka Editor"
          >
            <x-dynamic-component
              component="lucide-chevron-down"
              class="h-4 w-4 transition-transform duration-300"
              x-bind:class="isCollapsed ? 'rotate-180' : ''"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- BUNGKUSAN LIPATAN -->
    <div
      x-show="!isCollapsed"
      x-collapse
      x-cloak
      class="flex min-h-0 flex-1 flex-col"
    >
      <!-- AREA KERJA / FORMULIR -->
      <div
        class="flex min-h-0 flex-1 flex-col p-4"
        x-bind:class="isPinned ? 'overflow-y-auto scrollbar-thin' : ''"
      >
        <!-- ========================================== -->
        <!-- 🌟 MASUKKAN LOGIKA FORMULIR BLOK DI SINI 🌟  -->
        <!-- ========================================== -->
      </div>

      <!-- LIVE PREVIEW -->
      <div
        class="flex shrink-0 flex-col rounded-b-xl border-t border-gray-200 bg-gray-50"
        x-bind:class="
          isPinned
            ? 'max-h-[35vh] border-t-2 border-foresty/20 overflow-hidden'
            : ''
        "
      >
        <div class="shrink-0 border-b border-gray-200 bg-gray-200/60 px-4 py-2">
          <span
            class="text-[10px] font-bold tracking-widest text-gray-500 uppercase"
            >Live Preview ({{ strtoupper($code) }})</span
          >
        </div>

        <div
          class="flex w-full flex-1 justify-center bg-gray-50 p-6 md:p-10"
          x-bind:class="
            isPinned ? 'overflow-y-auto scrollbar-thin' : 'min-h-[150px]'
          "
        >
          <!-- ========================================== -->
          <!-- 🌟 INCLUDE FILE RENDER PUBLIK DI SINI 🌟     -->
          <!-- ========================================== -->
        </div>
      </div>
    </div>
    <!-- Akhir Bungkusan Lipatan -->
  </div>
</div>
