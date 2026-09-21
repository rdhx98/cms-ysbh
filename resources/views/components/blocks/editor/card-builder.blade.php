@props (['blockId', 'block', 'code'])

@php
  $iconsList = config('icons.lucide', []);

  $data = $block['data'] ?? [];
  $grid = $data['grid'] ?? ['cols' => 3, 'margin_bottom' => 'mb-8'];
  $cards = $data['cards'] ?? [];
@endphp

<!-- 🌟 PEMBUNGKUS LUAR -->
<div
  x-data="{
    activeCard: 0,
    activeSlot: 'main',
    isPinned: false,
    isRowPinned: false,
    isCollapsed: false,
    pinStyle: '',

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

    syncTabs(cardIndex, slotName) {
        this.activeCard = cardIndex;
        this.activeSlot = slotName;
        // 🌟 PERBAIKAN 1: Wajib menggunakan this.$dispatch
        this.$dispatch('sync-card-{{ strtolower($blockId) }}', { card: cardIndex, slot: slotName });
    },

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
                this.pinStyle = `position: fixed !important; top: ${rect.top + 5 }px !important; left: ${rect.left + 16}px !important; width: ${rect.width - 32}px !important; height: ${rect.height - 10}px !important; z-index: 60 !important; margin: 0 !important;`;
                this.$refs.placeholder.style.height = this.$refs.editor.offsetHeight + 'px';
            }
        } else {
            this.pinStyle = '';
        }
    },

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
        this.$dispatch('sync-collapse-{{ strtolower($blockId) }}', this.isCollapsed);
    }
  }"
  @toggle-row-pin-{{ strtolower($blockId) }}.window="
    isRowPinned = !isRowPinned;
    if (isRowPinned) {
        if (isPinned) {
            isPinned = false;
            pinStyle = '';
        }
        if (isCollapsed) {
            isCollapsed = false;
            $dispatch('sync-collapse-{{ strtolower($blockId) }}', false);
        }
    }
  "
  @force-close-row-pin-{{ strtolower($blockId) }}.window="isRowPinned = false"
  @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
  @toggle-collapse-all.window="
    isCollapsed = $event.detail;
    if (isCollapsed && isPinned) {
      isPinned = false;
      pinStyle = '';
    }
  "
  {{-- 🌟 PERBAIKAN 2: Listener untuk menangkap perintah pindah tab antar bahasa --}}
  @sync-card-{{ strtolower($blockId) }}.window="if ($event.detail) { activeCard = $event.detail.card; activeSlot = $event.detail.slot; }"
  class="flex w-full flex-col"
  x-bind:class="isPinned || isRowPinned ? 'h-full flex-1 min-h-0' : ''"
>
  {{-- PLACEHOLDER --}}
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

  {{-- EDITOR UTAMA --}}
  <div
    x-ref="editor"
    :style="isPinned ? pinStyle : ''"
    class="flex flex-col bg-white transition-all duration-200"
    x-bind:class="
      isPinned
        ? 'border-foresty ring-4 ring-foresty/20 shadow-2xl overflow-hidden flex-1 min-h-0 h-full'
        : isRowPinned
          {{-- 🌟 PERBAIKAN 3: Penambahan h-full untuk menjamin batas scroll bekerja --}}
          ? 'border-foresty/50 rounded-xl ring-2 ring-foresty/20 overflow-hidden flex-1 min-h-0 h-full max-h-[calc(100vh-120px)]'
          {{-- : 'rounded-xl border border-gray-200 shadow-md relative h-auto' --}}
          : 'rounded-xl border border-gray-200 shadow-md relative overflow-hidden flex-1 min-h-0 max-h-[calc(100vh-160px)]'
    "
  >
    <!-- HEADER BLOK -->
    <div
      class="flex shrink-0 items-center justify-between rounded-t-xl border-b border-gray-200 bg-gray-100 p-2"
    >
      <div class="flex items-center gap-2">
        <div class="bg-sage-soft rounded-md p-1">
          <x-dynamic-component
            component="lucide-blocks"
            class="text-foresty h-4 w-4"
          />
        </div>
        <span
          class="text-xs font-extrabold tracking-widest text-gray-500 uppercase"
          >Card Builder</span
        >
      </div>

      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2" x-show="!isCollapsed">
          <select
            wire:model.live="content.{{ $blockId }}.data.grid.cols"
            class="text-foresty rounded border-gray-300 bg-white py-1 text-xs font-bold shadow-sm"
          >
            <option value="1">1 Kolom</option>
            <option value="2">2 Kolom</option>
            <option value="3">3 Kolom</option>
            <option value="4">4 Kolom</option>
          </select>
          <select
            wire:model.live="content.{{ $blockId }}.data.grid.margin_bottom"
            class="text-foresty rounded border-gray-300 bg-white py-1 text-xs font-bold shadow-sm"
          >
            <option value="mb-0">Bawah: 0px</option>
            <option value="mb-8">Bawah: Normal</option>
            <option value="mb-16">Bawah: Jauh</option>
          </select>
          <span
            class="text-foresty bg-sage-soft shrink-0 rounded px-1.5 py-0.5 text-xs font-bold uppercase shadow-sm"
            >{{ $code }}</span
          >
        </div>

        <div class="flex items-center gap-1 border-l border-gray-300 pl-4">
          {{-- BUTTON PIN ROW --}}
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

          {{-- BUTTON PIN --}}
          <button
            type="button"
            :disabled="isRowPinned || isCollapsed"
            x-on:click="togglePin()"
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

          {{-- BUTTON COLLAPSE --}}
          <button
            type="button"
            :disabled="isPinned || isRowPinned"
            x-on:click="toggleCollapse()"
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

    <!-- 🌟 BUNGKUSAN LIPATAN -->
    <!-- <div
      x-show="!isCollapsed"
      x-collapse
      x-cloak
      class="flex flex-col"
      {{-- 🌟 Penambahan h-full untuk menembus gaya height:auto bawaan x-collapse --}}
      x-bind: class="isPinned || isRowPinned ? 'flex-1 min-h-0 h-full' : ''"
    > -->
      {{-- 🌟 BADAN TENGAH --}}
      {{-- <div
        class="flex flex-col p-4"
        x-bind:class="isPinned || isRowPinned ? 'flex-1 min-h-0 h-full' : ''"
      > --}}
      <!-- 🌟 BUNGKUSAN LIPATAN -->
    <div
      x-show="!isCollapsed"
      x-collapse
      x-cloak
      class="flex flex-col flex-1 min-h-0"
      x-bind:class="isPinned || isRowPinned ? 'h-full' : ''"
    >
      {{-- 🌟 BADAN TENGAH --}}
      <div
        class="flex flex-col p-4 flex-1 min-h-0"
        x-bind:class="isPinned || isRowPinned ? 'h-full' : ''"
      >
        @if (count($cards) === 0)
          <!-- Tampilan Kosong (Zero State) -->
          <div class="flex flex-1 flex-col items-center justify-center py-10">
            <p class="mb-4 text-sm text-gray-500">Belum ada kartu. Silakan pilih kerangka dasar (blueprint) kartu pertama Anda.</p>
            <div class="flex justify-center gap-4">
              <button
                type="button"
                wire:click="addCardItem('{{ $blockId }}', 'stack')"
                x-on:click="syncTabs(0, 'main')"
                class="hover:border-foresty group flex flex-col items-center gap-3 rounded-xl border-2 border-dashed border-gray-300 px-5 py-3 transition-colors outline-none"
              >
                <div class="flex w-12 flex-col items-center gap-1">
                  <div
                    class="group-hover:bg-foresty/50 h-2 w-8 rounded bg-gray-300 transition-colors"
                  ></div>
                  <div
                    class="group-hover:bg-foresty/50 h-2 w-12 rounded bg-gray-300 transition-colors"
                  ></div>
                  <div
                    class="group-hover:bg-foresty/50 h-2 w-10 rounded bg-gray-300 transition-colors"
                  ></div>
                </div>
                <span
                  class="group-hover:text-foresty text-xs font-bold text-gray-600 transition-colors"
                  >Stack (Tumpuk)</span
                >
              </button>

              <button
                type="button"
                wire:click="addCardItem('{{ $blockId }}', 'media-object')"
                x-on:click="syncTabs(0, 'middle')"
                class="hover:border-foresty group flex flex-col items-center gap-3 rounded-xl border-2 border-dashed border-gray-300 px-5 py-3 transition-colors outline-none"
              >
                <div class="flex items-center gap-2">
                  <div
                    class="group-hover:bg-foresty/50 h-5 w-5 rounded-sm bg-gray-300 transition-colors"
                  ></div>
                  <div class="flex flex-col gap-1">
                    <div
                      class="group-hover:bg-foresty/50 h-1.5 w-8 rounded bg-gray-300 transition-colors"
                    ></div>
                    <div
                      class="group-hover:bg-foresty/50 h-1.5 w-12 rounded bg-gray-300 transition-colors"
                    ></div>
                  </div>
                </div>
                <span
                  class="group-hover:text-foresty text-xs font-bold text-gray-600 transition-colors"
                  >Dokumen (3 Kolom)</span
                >
              </button>
            </div>
          </div>
        @else
          <!-- KONTROL TAB KARTU -->
          <div
            class="mb-4 flex shrink-0 items-center gap-2 border-b border-gray-200 pb-2"
          >
            <div class="no-scrollbar flex flex-1 gap-2 overflow-x-auto pb-1">
              @foreach ($cards as $index => $card)
                <div
                  wire:key="tab-{{ $blockId }}-{{ $card['id'] ?? $index }}"
                  class="flex shrink-0 items-center gap-1 rounded-md border px-2 py-1 transition-colors"
                  x-bind:class="activeCard === {{ $index }} ? 'bg-foresty text-white border-foresty shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-white hover:border-gray-300'"
                >
                  <button
                    type="button"
                    x-on:click="syncTabs({{ $index }}, '{{ ($card['blueprint'] ?? 'stack') === 'stack' ? 'main' : 'middle' }}')"
                    class="pr-2 pl-1 text-xs font-bold whitespace-nowrap outline-none"
                  >
                    Kartu {{ $index + 1 }}
                  </button>

                  <button
                    type="button"
                    wire:click="removeCardItem('{{ $blockId }}', {{ $index }})"
                    x-on:click="syncTabs(Math.max(0, activeCard - 1), 'main')"
                    class="rounded p-0.5 transition-colors outline-none hover:bg-red-500 hover:text-white"
                    title="Hapus Kartu"
                  >
                    <x-dynamic-component component="lucide-x" class="h-3 w-3" />
                  </button>
                </div>
              @endforeach
            </div>

            <!-- Tombol Tambah Kartu Baru -->
            <div class="relative shrink-0" x-data="{ openMenu: false }">
              <button
                type="button"
                x-on:click="openMenu = !openMenu"
                x-on:click.away="openMenu = false"
                class="bg-sage-soft text-foresty hover:bg-foresty flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-bold transition-colors hover:text-white"
              >
                <x-dynamic-component component="lucide-plus" class="h-3 w-3" />
                Tambah
              </button>
              <div
                x-show="openMenu"
                x-cloak
                class="absolute top-full right-0 z-50 mt-1 w-40 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg"
              >
                <button
                  type="button"
                  wire:key="add-stack-{{ $blockId }}"
                  x-on:click="$wire.addCardItem('{{ $blockId }}', 'stack'); syncTabs({{ count($cards) }}, 'main'); openMenu = false"
                  class="w-full border-b border-gray-100 px-3 py-2 text-left text-xs outline-none hover:bg-gray-50"
                >
                  Stack (Tumpuk)
                </button>
                <button
                  type="button"
                  wire:key="add-icon-text-{{ $blockId }}"
                  x-on:click="$wire.addCardItem('{{ $blockId }}', 'icon-text'); syncTabs({{ count($cards) }}, 'main'); openMenu = false"
                  class="w-full border-b border-gray-100 px-3 py-2 text-left text-xs outline-none hover:bg-gray-50"
                >
                  Baris: Ikon + Teks
                </button>
                <button
                  type="button"
                  wire:key="add-document-{{ $blockId }}"
                  x-on:click="$wire.addCardItem('{{ $blockId }}', 'document'); syncTabs({{ count($cards) }}, 'main'); openMenu = false"
                  class="w-full border-b border-gray-100 px-3 py-2 text-left text-xs outline-none hover:bg-gray-50"
                >
                  Dokumen (3 Kolom)
                </button>
                {{-- <button
                  type="button"
                  wire:key="add-media-{{ $blockId }}"
                  x-on:click="$wire.addCardItem('{{ $blockId }}', 'media-object'); syncTabs({{ count($cards) }}, 'middle'); openMenu = false"
                  class="w-full px-3 py-2 text-left text-xs outline-none hover:bg-gray-50"
                >
                  Dokumen (3 Kolom)
                </button> --}}
              </div>
            </div>
          </div>

          <!-- AREA KOMPONEN (Isi Kartu) -->
          {{-- <div
            class="flex flex-col border border-gray-200 bg-gray-50 p-4 sm:p-5"
            x-bind:class="
              isPinned || isRowPinned
                ? 'flex-1 min-h-0 overflow-y-auto scrollbar-thin rounded-none'
                : 'rounded-xl'
            "
          > --}}
          <div
            class="flex flex-col border border-gray-200 bg-gray-50 p-4 sm:p-5 flex-1 min-h-0 overflow-y-auto scrollbar-thin"
            x-bind:class="isPinned || isRowPinned ? 'rounded-none' : 'rounded-xl'"
          >
            @foreach ($cards as $cIndex => $card)
              <div
                x-show="activeCard === {{ $cIndex }}"
                x-cloak
                wire:key="card-editor-{{ $blockId }}-{{ $cIndex }}"
              >
                <!-- Pengaturan Kontainer Kartu -->
                <div
                  class="mb-6 flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm"
                >
                  <div class="flex items-center justify-between">
                    <div
                      class="w-full text-[10px] font-bold text-gray-400 uppercase"
                    >
                      Gaya Kotak & Tautan
                    </div>
                    <span
                      class="shrink-0 rounded bg-gray-100 px-2 py-0.5 text-[9px] font-bold text-gray-500"
                      >Blueprint: {{ $card['blueprint'] ?? 'stack' }}</span
                    >
                  </div>

                  <div class="flex flex-wrap gap-2">
                    <select
                      wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.bg"
                      class="rounded border-gray-200 p-1.5 text-xs"
                    >
                      <option value="bg-white">Latar Putih</option>
                      <option value="bg-mist">Latar Mist</option>
                      <option value="bg-foresty text-white">
                        Latar Foresty
                      </option>
                      <option value="bg-transparent">Transparan</option>
                    </select>
                    <select
                      wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.border"
                      class="rounded border-gray-200 p-1.5 text-xs"
                    >
                      <option value="border border-gray-200">
                        Border Standar
                      </option>
                      <option value="border border-foresty/15">
                        Border Tipis
                      </option>
                      <option value="border-0">Tanpa Border</option>
                    </select>
                    <select
                      wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.radius"
                      class="rounded border-gray-200 p-1.5 text-xs"
                    >
                      <option value="rounded-none">Siku</option>
                      <option value="rounded-[14px]">Agak Bulat</option>
                      <option value="rounded-[28px]">Sangat Bulat</option>
                    </select>
                    <select
                      wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.padding"
                      class="rounded border-gray-200 p-1.5 text-xs"
                    >
                      <option value="p-4">Padding Kecil</option>
                      <option value="p-6 md:p-8">Padding Besar</option>
                      <option value="p-0">Tanpa Padding</option>
                    </select>
                    <select
                      wire:model.live="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.align_y"
                      class="rounded border-gray-200 p-1.5 text-xs"
                    >
                      <option value="items-start">Posisi Atas (Top)</option>
                      <option value="items-center">Posisi Tengah (Center)</option>
                      <option value="items-end">Posisi Bawah (Bottom)</option>
                      <option value="items-stretch">Sama Tinggi (Stretch)</option>
                    </select>
                  </div>

                  <div class="mt-1 flex items-center gap-1">
                    <input
                      type="text"
                      wire:model.blur="content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.url"
                      placeholder="https:// atau pilih dari pencarian internal..."
                      class="focus:ring-foresty w-full rounded border-gray-300 py-1.5 text-xs shadow-sm"
                    />
                    <button
                      type="button"
                      x-on:click="$dispatch('buka-modal-link', { target: 'content.{{ $blockId }}.data.cards.{{ $cIndex }}.container.url' })"
                      class="hover:bg-sage-soft hover:text-foresty shrink-0 rounded-md border border-gray-200 bg-white p-1.5 text-gray-400 shadow-sm transition-colors"
                    >
                      <x-dynamic-component
                        component="lucide-search"
                        class="h-4 w-4"
                        stroke-width="2.5"
                      />
                    </button>
                  </div>
                </div>

                <!-- Isi Kartu: pohon layout row/column/element -->
                {{--  <x-editor.display-control :active-locales="$activeLocales" /> --}}
                <x-blocks.editor.card-builder-layout-editor
                  :block-id="$blockId"
                  :c-index="$cIndex"
                  :card="$card"
                  :code="$code"
                  :icons-list="$iconsList"
                />
                {{-- @include ('components.blocks.editor.card-builder-layout-editor', ['blockId' => $blockId, 'cIndex' => $cIndex, 'card' => $card, 'code' => $code, 'iconsList' => $iconsList]) --}}
              </div>
            @endforeach
          </div>
        @endif
      </div>
      {{-- Akhir Badan Tengah --}}

      {{-- PREVIEW BLOK --}}
      @if (count($cards) > 0)
        {{-- <div
          class="flex shrink-0 flex-col overflow-hidden rounded-b-xl border-t border-gray-200 bg-gray-50"
          x-bind: class="
            isPinned || isRowPinned
              ? 'max-h-[35vh] border-t-2 border-foresty/20'
              : ''
          "
        > --}}
        <div
          class="flex shrink-0 flex-col overflow-hidden rounded-b-xl border-t border-gray-200 bg-gray-50 max-h-[35vh]"
          x-bind:class="isPinned || isRowPinned ? 'border-t-2 border-foresty/20' : ''"
        >
          <div
            class="shrink-0 border-b border-gray-200 bg-gray-200/60 px-4 py-2"
          >
            <span
              class="text-[10px] font-bold tracking-widest text-gray-500 uppercase"
              >Live Preview ({{ strtoupper($code) }})</span
            >
          </div>

          {{-- 🌟 PERBAIKAN 4: Penambahan \x3E untuk memperbaiki error DOM Parser di VS Code --}}
          {{-- <style
            x-text="`.preview-atomic-{{ $blockId }}-{{ strtolower($code) }} .grid { grid-template-columns: 1fr !important; max-width: 400px; margin: 0 auto; } .preview-atomic-{{ $blockId }}-{{ strtolower($code) }} .grid \x3E *:not(:nth-child(${activeCard + 1})) { display: none !important; }`"
          ></style> --}}
          {{-- 🌟 PERBAIKAN 5: Penambahan \x3E di depan .grid agar hanya menyasar Grid Kartu terluar, bukan Grid Kolom di dalamnya --}}
          <style
            x-text="`.preview-atomic-{{ $blockId }}-{{ strtolower($code) }} \x3E .grid { grid-template-columns: 1fr !important; max-width: 400px; margin: 0 auto; width: 100%; } .preview-atomic-{{ $blockId }}-{{ strtolower($code) }} \x3E .grid \x3E *:not(:nth-child(${activeCard + 1})) { display: none !important; }`"
          ></style>

          {{-- <div
            class="preview-atomic-{{ $blockId }}-{{ strtolower($code) }} p-6 md:p-10 w-full flex justify-center bg-gray-50"
            x-bind: class="
              isPinned || isRowPinned
                ? 'flex-1 overflow-y-auto scrollbar-thin'
                : 'min-h-[150px]'
            "
          >
            @include ('components.blocks.render.card-builder', ['data' => $data, 'lang' => strtolower($code), 'isPreview' => true])
          </div> --}}
          <div
            class="preview-atomic-{{ $blockId }}-{{ strtolower($code) }} p-6 md:p-10 w-full flex justify-center bg-gray-50 flex-1 overflow-y-auto scrollbar-thin"
          >
            @include ('components.blocks.render.card-builder', ['data' => $data, 'lang' => strtolower($code), 'isPreview' => true])
          </div>
        </div>
      @endif
    </div>
    {{-- Akhir Bungkusan Lipatan --}}
  </div>
</div>
