@props(['blockId', 'block', 'code'])

@php
  $iconsList = config('icons.lucide', []);

  $data = $block['data'] ?? [];
  $grid = $data['grid'] ?? ['cols' => 3, 'margin_bottom' => 'mb-8'];
  $cards = $data['cards'] ?? [];
@endphp

<!-- 🌟 PEMBUNGKUS LUAR -->
<div x-data="{
    activeCard: 0,
    activeSlot: 'main',
    isPinned: false,
    isRowPinned: false,
    isCollapsed: false,
    pinStyle: '',

    syncTabs(cardIndex, slotName) {
        this.activeCard = cardIndex;
        this.activeSlot = slotName;
        $dispatch('sync-card-{{ strtolower($blockId) }}', { card: cardIndex, slot: slotName });
    },

    togglePin() {
        if (this.isCollapsed) this.isCollapsed = false;
        console.log('togglePin Fired');
        this.isPinned = !this.isPinned;
        if (this.isPinned) {
            let area = document.getElementById('main-editor-scroll-area');
            if (area) {
                let rect = area.getBoundingClientRect();
                this.pinStyle = `position: fixed; top: ${rect.top + 5 }px; left: ${rect.left + 16}px; width: ${rect.width - 32}px; height: ${rect.height - 10}px; z-index: 40;`;
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

    },
    //fireToggleRowPin() {
    //    $dispatch('toggle-row-pin-{{ strtolower($blockId) }}', this.isCollapsed);
    //}
}" @sync-card-{{ strtolower($blockId) }}.window=" if ($event.detail) { activeCard = $event.detail.card; activeSlot = $event.detail.slot;} "
  @resize.window="if(isPinned) { togglePin(); togglePin(); }"
  @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail; if(isCollapsed && isPinned) { togglePin(); }"
  @toggle-collapse-all.window="isCollapsed = $event.detail; if(isCollapsed && isPinned) { togglePin(); } "
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; if(isPinned) { togglePin(); } } "
  	@force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }"
  {{-- 🌟 MENDENGARKAN SINYAL ROW PIN DARI DIRI SENDIRI / KEMBARANNYA --}}
  x-on:toggle-row-pin-{{ strtolower($blockId) }}.window="isRowPinned = !isRowPinned"
  class="w-full h-full flex flex-col min-h-0"
>

  {{-- PLACEHOLDER --}}
  <div x-ref="placeholder" x-show="isPinned" x-cloak class="w-full rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
    <div class="text-center">
      <x-dynamic-component component="lucide-maximize" class="w-6 h-6 text-gray-400 mx-auto mb-2" />
      <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Mode Fokus Sedang Aktif</span>
    </div>
  </div>

  {{-- EDITOR UTAMA --}}
	<div x-ref="editor" :style="isPinned ? pinStyle : ''" class="bg-white border transition-all duration-200 rounded-xl flex flex-col"
       x-bind:class="isPinned ? 'border-foresty ring-4 ring-foresty/20 shadow-2xl overflow-hidden' : (isRowPinned ? 'border-foresty/50 ring-2 ring-foresty/20 overflow-hidden flex-1 min-h-0' : 'border-gray-200 shadow-md relative h-auto')">

    <!-- HEADER BLOK -->
    {{-- <div class="flex items-center justify-between p-2 bg-gray-100 rounded-t-xl border-b border-gray-200 shrink-0">
      <div class="flex items-center gap-2">
        <div class="p-1 bg-sage-soft rounded-md"><x-dynamic-component component="lucide-blocks" class="h-4 w-4 text-foresty" /></div>
        <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest">Card Builder</span>
      </div>
      
      <div class="flex gap-4 items-center">
        <!-- Pengaturan Grid (Sembunyi saat dilipat) -->
        <div class="flex items-center gap-2" x-show="!isCollapsed">
          <select wire:model.live="content.{{ $blockId }}.data.grid.cols" class="text-xs border-gray-300 rounded py-1 bg-white shadow-sm font-bold text-foresty">
            <option value="1">1 Kolom</option>
            <option value="2">2 Kolom</option>
            <option value="3">3 Kolom</option>
            <option value="4">4 Kolom</option>
          </select>
          <select wire:model.live="content.{{ $blockId }}.data.grid.margin_bottom" class="text-xs border-gray-300 rounded py-1 bg-white shadow-sm font-bold text-foresty">
            <option value="mb-0">Jarak Bawah: 0px</option>
            <option value="mb-8">Jarak Bawah: Normal</option>
            <option value="mb-16">Jarak Bawah: Jauh</option>
          </select>
          <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">{{ $code }}</span>
        </div>
        
        <!-- Grup Tombol Aksi -->
        <div class="flex items-center gap-1 border-l border-gray-300 pl-4">
          
          {{-- 1. TOMBOL PIN BARIS (Split View) -->
          <button type="button" x-on:click="$dispatch('toggle-row-pin-{{ strtolower($blockId) }}'); console.log('e dispatched')"
                  class="p-1.5 rounded-md transition-colors outline-none text-gray-500 hover:text-foresty hover:bg-gray-200"
                  title="Pin Baris (Split View)">
            <x-dynamic-component component="lucide-columns" class="w-3.5 h-3.5" />
          </button>

          {{-- 2. TOMBOL PIN FOKUS (Layar Penuh Bahasa Ini) -->
          <button type="button" x-on:click="togglePin()"
                  x-bind:class="isPinned ? 'bg-foresty text-white shadow-inner' : 'bg-gray-200 text-gray-500 hover:text-foresty hover:bg-gray-300'"
                  class="p-1.5 rounded-md transition-colors outline-none shadow-sm flex items-center justify-center"
                  title="Fokus Layar Penuh">
            <x-dynamic-component component="lucide-maximize" class="w-3.5 h-3.5" x-bind:class="isPinned ? 'scale-90' : ''" />
          </button>
          
          {{-- 3. TOMBOL LIPAT (COLLAPSE) -->
          <button type="button" x-on:click="toggleCollapse()"
                  class="p-1.5 rounded-md transition-colors outline-none text-gray-400 hover:text-foresty hover:bg-gray-200"
                  title="Lipat / Buka Editor">
            <x-dynamic-component component="lucide-chevron-down" class="w-4 h-4 transition-transform duration-300" x-bind:class="isCollapsed ? 'rotate-180' : ''" />
          </button>

        </div>
      </div>
    </div> --}}
		<!-- HEADER BLOK -->
    <div class="flex items-center justify-between p-2 bg-gray-100 rounded-t-xl border-b border-gray-200 shrink-0">
      <div class="flex items-center gap-2">
        <div class="p-1 bg-sage-soft rounded-md"><x-dynamic-component component="lucide-blocks" class="h-4 w-4 text-foresty" /></div>
        <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest">Card Builder</span>
      </div>
      
      <div class="flex gap-4 items-center">
        <div class="flex items-center gap-2" x-show="!isCollapsed">
          <select wire:model.live="content.{{ $blockId }}.data.grid.cols" class="text-xs border-gray-300 rounded py-1 bg-white shadow-sm font-bold text-foresty"><option value="1">1 Kolom</option><option value="2">2 Kolom</option><option value="3">3 Kolom</option><option value="4">4 Kolom</option></select>
          <select wire:model.live="content.{{ $blockId }}.data.grid.margin_bottom" class="text-xs border-gray-300 rounded py-1 bg-white shadow-sm font-bold text-foresty"><option value="mb-0">Bawah: 0px</option><option value="mb-8">Bawah: Normal</option><option value="mb-16">Bawah: Jauh</option></select>
          <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">{{ $code }}</span>
        </div>
        
        <div class="flex items-center gap-1 border-l border-gray-300 pl-4">
          <button type="button" x-on:click="$dispatch('toggle-row-pin-{{ strtolower($blockId) }}')"
                  x-bind:class="isRowPinned ? 'bg-foresty/10 text-foresty shadow-inner' : 'bg-gray-200 text-gray-500 hover:text-foresty hover:bg-gray-300'"
                  class="p-1.5 rounded-md transition-colors outline-none flex items-center justify-center" title="Pin Baris (Split View)">
            <x-dynamic-component component="lucide-columns" class="w-3.5 h-3.5" />
          </button>

          <button type="button" @click="togglePin()"
                  x-bind:class="isPinned ? 'bg-foresty text-white shadow-inner' : 'bg-gray-200 text-gray-500 hover:text-foresty hover:bg-gray-300'"
                  class="p-1.5 rounded-md transition-colors outline-none shadow-sm flex items-center justify-center" title="Fokus Layar Penuh">
            <x-dynamic-component component="lucide-maximize" class="w-3.5 h-3.5" x-bind:class="isPinned ? 'scale-90' : ''" />
          </button>
          
          <button type="button" @click="toggleCollapse()" class="p-1.5 rounded-md transition-colors outline-none text-gray-400 hover:text-foresty hover:bg-gray-200">
            <x-dynamic-component component="lucide-chevron-down" class="w-4 h-4 transition-transform duration-300" x-bind:class="isCollapsed ? 'rotate-180' : ''" />
          </button>
        </div>
      </div>
    </div>

		<!-- BUNGKUSAN LIPATAN -->
    <div x-show="!isCollapsed" x-collapse x-cloak class="flex-1 flex flex-col min-h-0">
      
      {{-- BADAN TENGAH (Scrollable jika isPinned ATAU isRowPinned) --}}
      <div class="flex-1 flex flex-col min-h-0 p-4" x-bind:class="(isPinned || isRowPinned) ? 'overflow-y-auto scrollbar-thin' : ''">
    <!-- 🌟 BUNGKUSAN LIPATAN -->
    {{-- <div x-show="!isCollapsed" x-collapse x-cloak class="flex-1 flex flex-col min-h-0"> --}}
      
      {{-- BADAN TENGAH --}}
      {{-- <div class="flex-1 flex flex-col min-h-0 p-4"> --}}
        
        @if (count($cards) === 0)
          <!-- Tampilan Kosong (Zero State) -->
          <div class="flex-1 flex flex-col items-center justify-center py-10">
            <p class="text-sm text-gray-500 mb-4">Belum ada kartu. Silakan pilih kerangka dasar (blueprint) kartu pertama Anda.</p>
            <div class="flex justify-center gap-4">
              
              <button type="button" wire:click="addCardItem('{{ $blockId }}', 'stack')" @click="syncTabs(0, 'main')" class="px-5 py-3 border-2 border-dashed border-gray-300 rounded-xl hover:border-foresty transition-colors flex flex-col items-center gap-3 group outline-none">
                <div class="flex flex-col gap-1 w-12 items-center">
                  <div class="w-8 h-2 bg-gray-300 rounded group-hover:bg-foresty/50 transition-colors"></div>
                  <div class="w-12 h-2 bg-gray-300 rounded group-hover:bg-foresty/50 transition-colors"></div>
                  <div class="w-10 h-2 bg-gray-300 rounded group-hover:bg-foresty/50 transition-colors"></div>
                </div>
                <span class="text-xs font-bold text-gray-600 group-hover:text-foresty transition-colors">Stack (Tumpuk)</span>
              </button>
              
              <button type="button" wire:click="addCardItem('{{ $blockId }}', 'media-object')" @click="syncTabs(0, 'middle')" class="px-5 py-3 border-2 border-dashed border-gray-300 rounded-xl hover:border-foresty transition-colors flex flex-col items-center gap-3 group outline-none">
                <div class="flex gap-2 items-center">
                  <div class="w-5 h-5 bg-gray-300 rounded-sm group-hover:bg-foresty/50 transition-colors"></div>
                  <div class="flex flex-col gap-1">
                    <div class="w-8 h-1.5 bg-gray-300 rounded group-hover:bg-foresty/50 transition-colors"></div>
                    <div class="w-12 h-1.5 bg-gray-300 rounded group-hover:bg-foresty/50 transition-colors"></div>
                  </div>
                </div>
                <span class="text-xs font-bold text-gray-600 group-hover:text-foresty transition-colors">Dokumen (3 Kolom)</span>
              </button>

            </div>
          </div>
        @else
          <!-- KONTROL TAB KARTU -->
          <div class="shrink-0 flex items-center gap-2 mb-4 border-b border-gray-200 pb-2">
            <div class="flex-1 overflow-x-auto flex gap-2 no-scrollbar pb-1">
              @foreach ($cards as $index => $card)
                <div wire:key="tab-{{ $blockId }}-{{ $card['id'] ?? $index }}"
                     class="flex items-center gap-1 border rounded-md px-2 py-1 transition-colors shrink-0"
                     x-bind:class="activeCard === {{ $index }} ? 'bg-foresty text-white border-foresty shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-white hover:border-gray-300'">
                  
                  <button type="button" @click="syncTabs({{ $index }}, '{{ ($card['blueprint'] ?? 'stack') === 'stack' ? 'main' : 'middle' }}')" class="text-xs font-bold pl-1 pr-2 whitespace-nowrap outline-none">
                    Kartu {{ $index + 1 }}
                  </button>
                  
                  <button type="button" wire:click="removeCardItem('{{ $blockId }}', {{ $index }})" @click="syncTabs(Math.max(0, activeCard - 1), 'main')" class="p-0.5 hover:bg-red-500 hover:text-white rounded transition-colors outline-none" title="Hapus Kartu">
                    <x-dynamic-component component="lucide-x" class="w-3 h-3" />
                  </button>
                </div> @endforeach
  </div>

  <!-- Tombol Tambah Kartu Baru -->
  <div class="relative shrink-0" x-data="{ openMenu: false }">
    <button type="button" @click="openMenu = !openMenu" @click.away="openMenu = false"
      class="px-3 py-1.5 rounded-md text-xs font-bold bg-sage-soft text-foresty hover:bg-foresty hover:text-white transition-colors flex items-center gap-1">
      <x-dynamic-component component="lucide-plus" class="w-3 h-3" /> Tambah
    </button>
    <div x-show="openMenu" x-cloak class="absolute top-full right-0 mt-1 w-40 bg-white border border-gray-200 shadow-lg rounded-md overflow-hidden z-50">
      <button type="button" wire:click="addCardItem('{{ $blockId }}', 'stack')" @click="syncTabs({{ count($cards) }}, 'main'); openMenu = false"
        class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 border-b border-gray-100 outline-none">Stack (Tumpuk)</button>
      <button type="button" wire:click="addCardItem('{{ $blockId }}', 'media-object')" @click="syncTabs({{ count($cards) }}, 'middle'); openMenu = false"
        class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 outline-none">Dokumen (3 Kolom)</button>
    </div>
  </div>
</div>

<!-- AREA KOMPONEN (Isi Kartu) -->
<div class="flex-1 min-h-0 bg-gray-50 border border-gray-200 p-4 sm:p-5 rounded-xl" x-bind:class="isPinned ? 'overflow-y-auto scrollbar-thin' : ''">

  @foreach ($cards as $cIndex => $card)
    <div x-show="activeCard === {{ $cIndex }}" x-cloak wire:key="card-editor-{{ $blockId }}-{{ $cIndex }}">

      <!-- Pengaturan Kontainer Kartu -->
      <div class="flex flex-col gap-3 mb-6 p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="flex items-center justify-between">
          <div class="text-[10px] font-bold text-gray-400 uppercase w-full">Gaya Kotak & Tautan</div>
          <span class="px-2 py-0.5 bg-gray-100 rounded text-[9px] text-gray-500 font-bold shrink-0">Blueprint: {{ $card['blueprint'] ?? 'stack' }}</span>
        </div>

        <div class="flex flex-wrap gap-2">
          <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.bg" class="text-xs border-gray-200 rounded p-1.5">
            <option value="bg-white">Latar Putih</option>
            <option value="bg-mist">Latar Mist</option>
            <option value="bg-foresty text-white">Latar Foresty</option>
            <option value="bg-transparent">Transparan</option>
          </select>
          <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.border" class="text-xs border-gray-200 rounded p-1.5">
            <option value="border border-gray-200">Border Standar</option>
            <option value="border border-foresty/15">Border Tipis</option>
            <option value="border-0">Tanpa Border</option>
          </select>
          <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.radius" class="text-xs border-gray-200 rounded p-1.5">
            <option value="rounded-none">Siku</option>
            <option value="rounded-[14px]">Agak Bulat</option>
            <option value="rounded-[28px]">Sangat Bulat</option>
          </select>
          <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.padding" class="text-xs border-gray-200 rounded p-1.5">
            <option value="p-4">Padding Kecil</option>
            <option value="p-6 md:p-8">Padding Besar</option>
            <option value="p-0">Tanpa Padding</option>
          </select>
        </div>

        <div class="flex items-center gap-1 mt-1">
          <input type="text" wire:model.blur="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.url"
            placeholder="https:// atau pilih dari pencarian internal..." class="w-full text-xs border-gray-300 rounded py-1.5 focus:ring-foresty shadow-sm">
          <button type="button" @click="$dispatch('buka-modal-link', { target: 'content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.url' })"
            class="p-1.5 border border-gray-200 bg-white hover:bg-sage-soft text-gray-400 hover:text-foresty rounded-md shadow-sm shrink-0 transition-colors">
            <x-dynamic-component component="lucide-search" class="w-4 h-4" stroke-width="2.5" />
          </button>
        </div>
      </div>

      <!-- Tab Area (Jika Media Object) -->
      @if (($card['blueprint'] ?? 'stack') === 'media-object')
        <div class="flex gap-2 mb-4 border-b border-gray-200 pb-2">
          <button type="button" @click="activeSlot = 'left'"
            x-bind:class="activeSlot === 'left' ? 'text-foresty border-foresty' : 'text-gray-400 border-transparent hover:text-foresty'"
            class="px-2 py-1 text-xs font-bold border-b-2 outline-none transition-colors">Area Kiri</button>
          <button type="button" @click="activeSlot = 'middle'"
            x-bind:class="activeSlot === 'middle' ? 'text-foresty border-foresty' : 'text-gray-400 border-transparent hover:text-foresty'"
            class="px-2 py-1 text-xs font-bold border-b-2 outline-none transition-colors">Area Tengah</button>
          <button type="button" @click="activeSlot = 'right'"
            x-bind:class="activeSlot === 'right' ? 'text-foresty border-foresty' : 'text-gray-400 border-transparent hover:text-foresty'"
            class="px-2 py-1 text-xs font-bold border-b-2 outline-none transition-colors">Area Kanan</button>
        </div>
      @endif

      <!-- Loop Elemen di dalam Slot -->
      @foreach ($card['slots'] ?? [] as $slotName => $elements)
        <div x-show="activeSlot === '{{ $slotName }}'" x-cloak>
          <div class="space-y-3">
            @foreach ($elements as $elIndex => $el)
              <div wire:key="el-{{ $blockId }}-{{ $cIndex }}-{{ $slotName }}-{{ $elIndex }}"
                class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm relative group mb-3">

                <button type="button" wire:click="removeCardElement('{{ $blockId }}', {{ $cIndex }}, '{{ $slotName }}', {{ $elIndex }})"
                  class="absolute -right-2 -top-2 bg-red-100 text-red-600 p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity outline-none shadow-sm">
                  <x-dynamic-component component="lucide-x" class="w-3 h-3" />
                </button>

                <!-- Jika Tipe TEKS -->
                @if (($el['type'] ?? 'text') === 'text')
                  <div class="flex justify-between items-center mb-2">
                    <span
                      class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded {{ $el['style']['is_pill'] ?? false ? 'bg-goldy-soft text-goldy-dark' : 'bg-foresty/10 text-foresty' }}">
                      {{ $el['style']['is_pill'] ?? false ? 'Lencana (Pill)' : 'Teks' }}
                    </span>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                      <input type="checkbox"
                        wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.is_pill"
                        class="rounded text-foresty focus:ring-foresty w-3.5 h-3.5 border-gray-300">
                      <span class="text-[10px] font-bold text-gray-500 uppercase">Mode Pill</span>
                    </label>
                  </div>

                  <textarea rows="2" wire:model.blur="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.content.{{ $code }}"
                    placeholder="Ketik isi teks di sini..." class="w-full text-sm font-semibold border-gray-200 rounded-lg focus:ring-foresty mb-2 shadow-sm resize-none"></textarea>

                  <div class="flex gap-2 flex-wrap bg-gray-50 p-2 rounded-lg border border-gray-100">
                    @if (!($el['style']['is_pill'] ?? false))
                      <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.font"
                        class="text-[11px] py-1 border-gray-200 rounded">
                        <option value="font-sans">Sistem Font</option>
                        <option value="font-display">Display Font</option>
                      </select>
                      <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.size"
                        class="text-[11px] py-1 border-gray-200 rounded">
                        <option value="text-[13px]">Kecil</option>
                        <option value="text-[15px]">Normal</option>
                        <option value="text-[21px]">Besar (H3)</option>
                      </select>
                      <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.weight"
                        class="text-[11px] py-1 border-gray-200 rounded">
                        <option value="font-normal">Reguler</option>
                        <option value="font-semibold">Semi Bold</option>
                      </select>
                    @else
                      <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.pill_bg"
                        class="text-[11px] py-1 border-gray-200 rounded">
                        <option value="bg-goldy-soft">Bg Goldy</option>
                        <option value="bg-mist">Bg Mist</option>
                        <option value="bg-sage-soft">Bg Sage</option>
                      </select>
                      <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.pill_radius"
                        class="text-[11px] py-1 border-gray-200 rounded">
                        <option value="rounded-md">Sedikit Bulat</option>
                        <option value="rounded-full">Bulat Penuh</option>
                      </select>
                    @endif
                    <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.color"
                      class="text-[11px] py-1 border-gray-200 rounded">
                      <option value="text-ink-soft">Abu Gelap</option>
                      <option value="text-foresty">Foresty</option>
                      <option value="text-coral">Coral</option>
                    </select>
                    <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.margin"
                      class="text-[11px] py-1 border-gray-200 rounded">
                      <option value="mb-0">Jarak Bawah: 0</option>
                      <option value="mb-2">Jarak Bawah: Kecil</option>
                      <option value="mb-4">Jarak Bawah: Sedang</option>
                    </select>
                  </div>

                  <!-- Jika Tipe IKON -->
                @elseif(($el['type'] ?? 'icon') === 'icon')
                  <div class="flex justify-between items-center mb-2">
                    <span class="text-[10px] font-extrabold text-white uppercase tracking-widest bg-foresty px-2 py-0.5 rounded">Ikon</span>
                  </div>

                  <div class="relative mb-2" x-data="{ openPicker: false, search: '' }">
                    <button type="button" @click="openPicker = !openPicker"
                      class="w-full flex items-center justify-between bg-white border border-gray-300 rounded-lg py-2 px-3 text-xs shadow-sm hover:border-foresty focus:outline-none transition-colors">
                      <div class="flex items-center gap-2 truncate">
                        <x-dynamic-component :component="'lucide-' . ($el['content']['icon'] ?: 'box')" class="w-5 h-5 text-foresty shrink-0" stroke-width="2.5" />
                        <span class="truncate uppercase font-mono text-[11px] font-bold text-gray-700">{{ $el['content']['icon'] ?: 'PILIH IKON...' }}</span>
                      </div>
                      <x-dynamic-component component="lucide-chevron-down" class="w-4 h-4 text-gray-400 shrink-0" />
                    </button>

                    <div x-show="openPicker" @click.outside="openPicker = false" x-cloak
                      class="absolute left-0 mt-1 w-full sm:w-64 bg-white border border-gray-200 rounded-xl shadow-xl p-3 z-50 flex flex-col gap-2">
                      <div class="relative">
                        <x-dynamic-component component="lucide-search" class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" />
                        <input type="text" x-model="search" placeholder="Cari ikon..."
                          class="w-full text-xs border border-gray-200 rounded-lg pl-9 pr-2 py-2 focus:ring-foresty focus:border-foresty shadow-sm" />
                      </div>
                      <div class="grid grid-cols-5 gap-1.5 max-h-48 overflow-y-auto p-1 scrollbar-thin">
                        @foreach ($iconsList as $iconName)
                          <button type="button" x-show="'{{ $iconName }}'.includes(search.toLowerCase())"
                            wire:click="$set('content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.content.icon', '{{ $iconName }}')"
                            @click="openPicker = false; search = ''"
                            class="p-2.5 rounded-lg flex items-center justify-center transition-all duration-200 border {{ ($el['content']['icon'] ?? '') === $iconName ? 'bg-sage-soft text-foresty border-foresty shadow-sm scale-110' : 'bg-gray-50 text-gray-400 border-transparent hover:border-foresty/50 hover:text-foresty' }}">
                            <x-dynamic-component :component="'lucide-' . $iconName" class="w-5 h-5 shrink-0" stroke-width="2" />
                          </button>
                        @endforeach
                      </div>
                    </div>
                  </div>

                  <div class="flex gap-2 flex-wrap bg-gray-50 p-2 rounded-lg border border-gray-100">
                    <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.bg"
                      class="text-[11px] py-1 border-gray-200 rounded">
                      <option value="bg-goldy-soft">Latar Goldy</option>
                      <option value="bg-mist">Latar Mist</option>
                      <option value="bg-transparent">Latar Transparan</option>
                    </select>
                    <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.color"
                      class="text-[11px] py-1 border-gray-200 rounded">
                      <option value="text-foresty">Warna Foresty</option>
                      <option value="text-coral">Warna Coral</option>
                    </select>
                    <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.size"
                      class="text-[11px] py-1 border-gray-200 rounded">
                      <option value="w-10 h-10">Ukuran Standar</option>
                      <option value="w-16 h-16">Ukuran Besar</option>
                    </select>
                    <select wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.slots.{{ $slotName }}.{{ $elIndex }}.style.radius"
                      class="text-[11px] py-1 border-gray-200 rounded">
                      <option value="rounded-[14px]">Agak Bulat</option>
                      <option value="rounded-full">Lingkaran</option>
                    </select>
                  </div>
                @endif

              </div>
            @endforeach
          </div>
        </div>
      @endforeach

      <!-- Tombol Tambah Elemen Baru -->
      <div class="flex gap-2 mt-2">
        <button type="button" wire:click="addCardElement('{{ $blockId }}', {{ $cIndex }}, activeSlot, 'text')"
          class="flex-1 py-2.5 bg-white border border-dashed border-gray-300 rounded-lg text-xs font-bold text-gray-500 hover:border-foresty hover:text-foresty transition-colors shadow-sm outline-none">+
          Teks</button>
        <button type="button" wire:click="addCardElement('{{ $blockId }}', {{ $cIndex }}, activeSlot, 'icon')"
          class="flex-1 py-2.5 bg-white border border-dashed border-gray-300 rounded-lg text-xs font-bold text-gray-500 hover:border-foresty hover:text-foresty transition-colors shadow-sm outline-none">+
          Ikon</button>
      </div>

    </div>
  @endforeach
</div>
@endif

</div> {{-- Akhir Badan Tengah --}}

{{-- PREVIEW BLOK --}}
{{-- @if (count($cards) > 0)
  <div class="shrink-0 bg-gray-50 border-t border-gray-200 rounded-b-xl overflow-hidden flex flex-col" x-bind:class="isPinned ? 'max-h-[35vh] border-t-2 border-foresty/20' : ''">
    <div class="bg-gray-200/60 px-4 py-2 border-b border-gray-200 shrink-0">
      <span class="text-[10px] font-bold uppercase text-gray-500 tracking-widest">Live Preview ({{ strtoupper($code) }})</span>
    </div>

    <style
      x-text="`
              .preview-atomic-{{ $blockId }}-{{ strtolower($code) }} .grid { grid-template-columns: 1fr !important; max-width: 400px; margin: 0 auto; }
              .preview-atomic-{{ $blockId }}-{{ strtolower($code) }} .grid > *:not(:nth-child(${activeCard + 1})) { display: none !important; }
          `">
    </style>

    <div class="preview-atomic-{{ $blockId }}-{{ strtolower($code) }} p-6 md:p-10 w-full flex-1 flex justify-center bg-gray-50"
      x-bind:class="isPinned ? 'overflow-y-auto scrollbar-thin' : 'min-h-[150px]'"> ugal-ugalan
      @include('components.blocks.render.card-builder', ['data' => $data, 'lang' => strtolower($code), 'isPreview' => true])
    </div>
  </div>
@endif --}}
{{-- PREVIEW BLOK --}}
@if (count($cards) > 0)
  <div class="shrink-0 bg-gray-50 border-t border-gray-200 rounded-b-xl overflow-hidden flex flex-col"
    x-bind:class="(isPinned || isRowPinned) ? 'max-h-[35vh] border-t-2 border-foresty/20' : ''">
    <div class="bg-gray-200/60 px-4 py-2 border-b border-gray-200 shrink-0">
      <span class="text-[10px] font-bold uppercase text-gray-500 tracking-widest">Live Preview ({{ strtoupper($code) }})</span>
    </div>

    <style
      x-text="` .preview-atomic-{{ $blockId }}-{{ strtolower($code) }} .grid { grid-template-columns: 1fr !important; max-width: 400px; margin: 0 auto; } .preview-atomic-{{ $blockId }}-{{ strtolower($code) }} .grid > *:not(:nth-child(${activeCard + 1})) { display: none !important; } `">
    </style>

    <div class="preview-atomic-{{ $blockId }}-{{ strtolower($code) }} p-6 md:p-10 w-full flex-1 flex justify-center bg-gray-50"
      x-bind:class="(isPinned || isRowPinned) ? 'overflow-y-auto scrollbar-thin' : 'min-h-[150px]'">
      @include('components.blocks.render.card-builder', ['data' => $data, 'lang' => strtolower($code), 'isPreview' => true])
    </div>
  </div>
@endif

</div> {{-- Akhir Bungkusan Lipatan --}}
</div> {{-- Akhir Editor Utama --}}
</div> {{-- Akhir Pembungkus Luar --}}
