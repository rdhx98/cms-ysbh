@props ([
    'blockId',
    'code',
    'block',
    'allContent' => [], // Tambahkan fallback array kosong agar tidak error jika dipanggil di root
])

<div
  class="group/heading rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200"
  x-data="{
    isCollapsed: false,
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
    }
}"
  @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
   
  @toggle-collapse-all.window="isCollapsed = $event.detail"
   
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }"
   
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }"
>
   
  <!-- HEADER -->
   
  <div
    class="flex cursor-pointer items-center justify-between bg-gray-100 p-2 transition-colors select-none group-hover:bg-white"
    :class="isCollapsed
      ? 'rounded-xl'
      : 'rounded-t-xl border-b border-gray-200'"
  >
       
    {{-- LEFT HEADER --}}
       
    <div class="flex items-center gap-2">
           
      {{-- Tombol Collapse --}}
           
      <button
        type="button"
        x-on:click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
         
         
         
         
        class="hover:bg-sage-soft text-foresty cursor-pointer rounded-full p-1 transition-all duration-200 focus:outline-none"
      >
        <x-dynamic-component
          component="lucide-circle-chevron-down"
          class="text-foresty h-5 w-5 transition-transform duration-200"
          x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'"
        />
      </button>

           
      {{-- Label Identitas --}}

           
      <div class="bg-sage-soft rounded-md p-1">
               
        <x-dynamic-component
          :component="'lucide-heading'"
          class="text-forest h-4 w-4"
          stroke-width="2.5"
        />
             
      </div>

           
      <span
        class="flex items-center text-xs font-extrabold tracking-widest text-gray-500 uppercase"
      >
                Judul (Heading)      
      </span>
         
    </div>
       
    {{-- RIGHT HEADER --}}
       
    {{-- Tambahkan flex-1 dan min-w-0 di sini agar ia berani mengambil sisa ruang tapi juga mau menyusut --}}
       
    <div class="flex min-w-0 flex-1 items-center justify-end gap-2">
           
      {{-- 🌟 FITUR UX: Cuplikan Teks saat Runtuh (Terbatas & Memiliki Tooltip) --}}
           
      <div
        x-show="isCollapsed"
        x-cloak
        class="min-w-0 flex-1 px-2 text-xs font-medium text-gray-400 sm:px-4"
        {{-- 💡 Tooltip Dinamis Alpine.js (Tidak akan mengubah layout/tinggi sama sekali) --}}
         
         
         
         
        :title="($wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || '').replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'"
      >
               
        {{-- Jadikan span sebagai block dan berikan truncate untuk memotongnya menjadi 1 baris ketat --}}
               
        <span
          class="block w-full truncate text-right"
           
           
           
           
           
          x-text="($wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || '').replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'"
        >
                 
        </span>
             
      </div>

           
      {{-- Indikator Bahasa --}}
           
      {{-- Tambahkan shrink-0 agar kotak bahasa ini TIDAK IKUT tergencet saat teks di sebelahnya sangat panjang --}}
           
      <span
        class="text-foresty bg-sage-soft shrink-0 rounded px-1.5 py-0.5 text-xs font-bold uppercase shadow-sm"
      >
                {{ $code }}      
      </span>
         
    </div>
     
  </div>

   
  {{-- Collapsed wrapper --}}
   
  <div
    x-show="!isCollapsed"
    x-collapse
    x-cloak
    class="space-y-4 bg-gray-50/50 p-4"
  >
       
    <div class="flex items-center justify-between gap-2">
           
      <label class="block text-xs font-semibold text-gray-500 uppercase"
        >Level Heading</label
      >
           
      <select
        wire:model="content.{{ $blockId }}.data.level"
        class="rounded-md border border-gray-300 bg-white px-2 py-0 text-xs text-gray-600"
        @click.stop
      >
               
        <option value="h1">Heading 1</option>
               
        <option value="h2">Heading 2</option>
               
        <option value="h3">Heading 3</option>
             
      </select>
         
    </div>

       
    <x-tiptap
      :block-type="$block['type']"
      wire:model="content.{{ $blockId }}.data.text.{{ $code }}"
      placeholder="Blok Heading..."
    />

     
  </div>
</div>
