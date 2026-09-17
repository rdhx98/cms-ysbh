@props(['blockId', 'block', 'code'])

<div x-data="{
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
  @resize.window="if(isPinned) { togglePin(); togglePin(); }"
  @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail; if(isCollapsed && isPinned) { togglePin(); }"
  @toggle-collapse-all.window="isCollapsed = $event.detail; if(isCollapsed && isPinned) { togglePin(); }"
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; if(isPinned) { togglePin(); } }"
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }"
  class="w-full">

  <!-- 🌟 PLACEHOLDER FOKUS -->
  <div x-ref="placeholder" x-show="isPinned" x-cloak class="w-full rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
    <div class="text-center">
      <x-dynamic-component component="lucide-maximize" class="w-6 h-6 text-gray-400 mx-auto mb-2" />
      <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Mode Fokus Sedang Aktif</span>
    </div>
  </div>

  <!-- 🌟 EDITOR UTAMA -->
  <div x-ref="editor" :style="pinStyle" class="bg-white transition-all duration-200 rounded-xl flex flex-col"
    x-bind:class="isPinned ? 'border border-foresty ring-4 ring-foresty/20 shadow-2xl overflow-hidden' : 'border border-gray-200 shadow-md relative h-auto'">

    <!-- HEADER -->
    <div class="flex items-center justify-between p-2 bg-gray-100 rounded-t-xl border-b border-gray-200 shrink-0">
      <div class="flex items-center gap-2">
        <div class="p-1 bg-sage-soft rounded-md"><x-dynamic-component component="lucide-type" class="h-4 w-4 text-foresty" /></div>
        <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest">NAMA BLOK DI SINI</span>
      </div>

      <div class="flex items-center gap-4">
        <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0" x-show="!isCollapsed">{{ $code }}</span>

        <div class="flex items-center gap-1 border-l border-gray-300 pl-4">
          {{-- 1. TOMBOL PIN BARIS (Split View Pin) --}}
          <button type="button" @click="$dispatch('toggle-row-pin-{{ $blockId }}')"
            class="p-1.5 rounded-md transition-colors outline-none text-gray-500 hover:text-foresty hover:bg-gray-200"
            title="Pin Baris (Split View)">
            <x-dynamic-component component="lucide-columns" class="w-3.5 h-3.5" />
          </button>

          {{-- 2. TOMBOL PIN FOKUS (Layar Penuh Bahasa Ini) --}}
          <button type="button" @click="togglePin()"
            x-bind:class="isPinned ? 'bg-foresty text-white shadow-inner' : 'bg-gray-200 text-gray-500 hover:text-foresty hover:bg-gray-300'"
            class="p-1.5 rounded-md transition-colors outline-none shadow-sm flex items-center justify-center"
            title="Fokus Layar Penuh">
            <x-dynamic-component component="lucide-maximize" class="w-3.5 h-3.5" x-bind:class="isPinned ? 'scale-90' : ''" />
          </button>

          {{-- 3. TOMBOL LIPAT (COLLAPSE) --}}
          <button type="button" @click="toggleCollapse()"
            class="p-1.5 rounded-md transition-colors outline-none text-gray-400 hover:text-foresty hover:bg-gray-200"
            title="Lipat / Buka Editor">
            <x-dynamic-component component="lucide-chevron-down" class="w-4 h-4 transition-transform duration-300" x-bind:class="isCollapsed ? 'rotate-180' : ''" />
          </button>
        </div>
      </div>
    </div>

    <!-- BUNGKUSAN LIPATAN -->
    <div x-show="!isCollapsed" x-collapse x-cloak class="flex-1 flex flex-col min-h-0">

      <!-- AREA KERJA / FORMULIR -->
      <div class="flex-1 flex flex-col min-h-0 p-4" x-bind:class="isPinned ? 'overflow-y-auto scrollbar-thin' : ''">
        <!-- ========================================== -->
        <!-- 🌟 MASUKKAN LOGIKA FORMULIR BLOK DI SINI 🌟  -->
        <!-- ========================================== -->
      </div>

      <!-- LIVE PREVIEW -->
      <div class="shrink-0 bg-gray-50 border-t border-gray-200 rounded-b-xl flex flex-col"
        x-bind:class="isPinned ? 'max-h-[35vh] border-t-2 border-foresty/20 overflow-hidden' : ''">

        <div class="bg-gray-200/60 px-4 py-2 border-b border-gray-200 shrink-0">
          <span class="text-[10px] font-bold uppercase text-gray-500 tracking-widest">Live Preview ({{ strtoupper($code) }})</span>
        </div>

        <div class="p-6 md:p-10 w-full flex-1 flex justify-center bg-gray-50"
          x-bind:class="isPinned ? 'overflow-y-auto scrollbar-thin' : 'min-h-[150px]'">
          <!-- ========================================== -->
          <!-- 🌟 INCLUDE FILE RENDER PUBLIK DI SINI 🌟     -->
          <!-- ========================================== -->
        </div>
      </div>

    </div> <!-- Akhir Bungkusan Lipatan -->
  </div>
</div>
