@props(['blockId', 'block', 'code'])

<!-- ==========================================
  1. PEMBUNGKUS LUAR & MESIN ALPINE
========================================== -->
<div x-data="{
    isPinned: false,
    pinStyle: '',

    togglePin() {
        this.isPinned = !this.isPinned;
        if (this.isPinned) {
            // Cari area scroll induk
            let area = document.getElementById('main-editor-scroll-area');
            if (area) {
                let rect = area.getBoundingClientRect();
                // Cetak koordinat (angka 16 dan 32 disesuaikan dengan padding px-4 parent)
                this.pinStyle = `position: fixed; top: ${rect.top}px; left: ${rect.left + 16}px; width: ${rect.width - 32}px; height: ${rect.height - 10}px; z-index: 40;`;
                // Tumbuhkan placeholder setinggi editor saat ini
                this.$refs.placeholder.style.height = this.$refs.editor.offsetHeight + 'px';
            }
        } else {
            this.pinStyle = '';
        }
    }
}"
  @resize.window="if(isPinned) { togglePin(); togglePin(); }"
  class="w-full">

  <!-- ==========================================
      2. PLACEHOLDER (Muncul saat Pin Aktif)
    ========================================== -->
  <div x-ref="placeholder" x-show="isPinned" x-cloak class="w-full rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
    <div class="text-center">
      <x-dynamic-component component="lucide-pin" class="w-6 h-6 text-gray-400 mx-auto mb-2" />
      <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Mode Pin Sedang Aktif</span>
    </div>
  </div>

  <!-- ==========================================
      3. EDITOR UTAMA (Bereaksi terhadap pinStyle)
    ========================================== -->
  <div x-ref="editor"
    :style="pinStyle"
    class="bg-white transition-all duration-200 rounded-xl flex flex-col"
    :class="isPinned ? 'border border-foresty ring-4 ring-foresty/20 shadow-2xl overflow-hidden' : 'border border-gray-200 shadow-md relative h-auto'">

    <!-- A. HEADER (shrink-0) -->
    <div class="flex items-center justify-between p-2 bg-gray-100 rounded-t-xl border-b border-gray-200 shrink-0">
      <div class="flex items-center gap-2">
        <div class="p-1 bg-sage-soft rounded-md"><x-dynamic-component component="lucide-type" class="h-4 w-4 text-foresty" /></div>
        <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest">NAMA BLOK</span>
      </div>

      <div class="flex items-center gap-4">
        <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">{{ $code }}</span>

        <!-- TOMBOL PIN -->
        <button type="button" @click="togglePin()"
          :class="isPinned ? 'bg-foresty text-white shadow-inner' : 'bg-gray-200 text-gray-500 hover:text-foresty hover:bg-gray-300'"
          class="p-1.5 rounded-md transition-colors outline-none shadow-sm flex items-center justify-center"
          title="Pin / Unpin Editor">
          <x-dynamic-component component="lucide-pin" class="w-3.5 h-3.5" :class="isPinned ? 'rotate-45' : ''" />
        </button>
      </div>
    </div>

    <!-- B. AREA KERJA / FORMULIR (flex-1 min-h-0 overflow-y-auto) -->
    <div class="flex-1 flex flex-col min-h-0 p-4" :class="isPinned ? 'overflow-y-auto scrollbar-thin' : ''">

      <!-- ISI FORMULIR BLOK ANDA DI SINI (Inputs, Selects, Textareas, dll) -->

    </div>

    <!-- C. LIVE PREVIEW (shrink-0, dibatasi max-height-nya jika dipin) -->
    <div class="shrink-0 bg-gray-50 border-t border-gray-200 rounded-b-xl flex flex-col"
      :class="isPinned ? 'max-h-[35vh] border-t-2 border-foresty/20 overflow-hidden' : ''">

      <div class="bg-gray-200/60 px-4 py-2 border-b border-gray-200 shrink-0">
        <span class="text-[10px] font-bold uppercase text-gray-500 tracking-widest">Live Preview ({{ strtoupper($code) }})</span>
      </div>

      <!-- Area Preview (Ikut bergulir secara mandiri jika preview sangat panjang) -->
      <div class="p-6 md:p-10 w-full flex-1 flex justify-center bg-gray-50"
        :class="isPinned ? 'overflow-y-auto scrollbar-thin' : 'min-h-[150px]'">

        <!-- INCLUDE FILE RENDER PUBLIK ANDA DI SINI -->

      </div>
    </div>

  </div>
</div>
