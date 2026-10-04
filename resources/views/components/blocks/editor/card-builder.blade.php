@props ([
  "blockId",
  "block",
  "activeLocales" => [],
  "context" => null,
  "parentId" => null,
  "parentZone" => null,
  "marginBottom" => [],
])

@php
  // $iconsList = config("icons.lucide", []);
  $borderStyles = config("cms.design.border_styles", []);
  // $borderRadius = config("cms.design.border_radiuses", []);
  $cardPadding = config("cms.design.card_paddings", []);
  $cardBgColors = config("cms.design.card_bg_colors", []);
  $cardBorderColors = config("cms.design.card_border_colors", []);
  $data = $block["data"] ?? [];
  $cards = $data["cards"] ?? [];
  // $isNested = $context !== null;
  $isNested = $parentId !== null || $context !== null;
@endphp

<x-blocks.editor.wrapper
  :block-id="$blockId"
  :block="$block"
  :is-nested="$isNested"
  :parent-id="$parentId"
  :parent-zone="$parentZone"
>
  <x-slot:title>
    <div
      class="bg-sage-soft text-foresty flex h-5 w-5 items-center justify-center rounded-sm shadow-sm"
    >
      <x-dynamic-component
        component="lucide-blocks"
        class="h-3.5 w-3.5"
        stroke-width="2.5"
      />
    </div>
    Card Builder
  </x-slot:title>

  <x-slot:snippet>
    <span
      class="block w-full text-right text-xs font-medium text-gray-400 sm:text-left"
    >
      <span class="font-bold text-gray-500">{{ count($cards) }}</span> Kartu
      Tersusun
    </span>
  </x-slot:snippet>

  <div
    x-data="{
        activeCard: 0,
        syncTabs(cardIndex) {
            this.activeCard = cardIndex;
            this.$dispatch('sync-card-{{ strtolower($blockId) }}', { card: cardIndex });
        }
       }"
    @sync-card-{{ strtolower($blockId) }}.window="if ($event.detail) { activeCard =$event.detail.card; }"
    class="flex flex-col gap-4"
  >
    {{-- JIKA BELUM ADA KARTU --}}
    @if (count($cards) === 0)
      <div class="flex flex-col items-center justify-center py-10">
        <p class="mb-4 text-sm text-gray-500">Pilih kerangka dasar (blueprint) kartu pertama Anda.</p>
        <div class="flex flex-wrap justify-center gap-4">
          <button
            type="button"
            wire:click="addCardItem('{{ $blockId }}', 'stack')"
            x-on:click="syncTabs(0)"
            class="group hover:border-foresty hover:bg-sage-soft flex flex-col items-center gap-3 rounded-xl border-2 border-dashed border-gray-300 px-5 py-3 transition-colors outline-none"
          >
            <div class="flex w-12 flex-col items-center gap-1">
              <div
                class="group-hover:bg-foresty/50 h-2 w-8 rounded bg-gray-300"
              ></div>
              <div
                class="group-hover:bg-foresty/50 h-2 w-12 rounded bg-gray-300"
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
            x-on:click="syncTabs(0)"
            class="group hover:border-foresty hover:bg-sage-soft flex flex-col items-center gap-3 rounded-xl border-2 border-dashed border-gray-300 px-5 py-3 transition-colors outline-none"
          >
            <div class="flex items-center gap-2">
              <div
                class="group-hover:bg-foresty/50 h-5 w-5 rounded-sm bg-gray-300"
              ></div>
              <div class="flex flex-col gap-1">
                <div
                  class="group-hover:bg-foresty/50 h-1.5 w-8 rounded bg-gray-300"
                ></div>
              </div>
            </div>
            <span
              class="group-hover:text-foresty text-xs font-bold text-gray-600 transition-colors"
              >Dokumen</span
            >
          </button>
        </div>
      </div>
      {{-- SUDAH ADA KARTU MIN 1 --}}
    @else
      <x-slot:controls>
        {{-- CONTAINER FOR CARD BUILDER CONTROL AND CARD TAB NAV  --}}
        {{-- class="flex flex-col items-start justify-start gap-4 md:flex-row md:items-center md:justify-between" --}}
        <div
          x-data="{
            activeCard: 0,
            syncTabs(cardIndex) {
              this.activeCard = cardIndex;
              this.$dispatch('sync-card-{{ strtolower($blockId) }}', { card: cardIndex });
            }
          }"
          class="flex flex-col gap-2"
          @sync-card-{{ strtolower($blockId) }}.window="if ($event.detail !== undefined) { activeCard = $event.detail.card; }"
        >
          @if (!$isNested)
            <div
              class="flex flex-col flex-wrap items-center justify-start gap-y-2 rounded-lg border border-gray-100 bg-gray-50/50 p-3 shadow-inner md:flex-row md:flex-nowrap md:items-end md:justify-between md:gap-x-6"
            >
              {{-- CARD BUILDER CONTROL ? --}}
              <div class="flex items-start gap-2">
                <!-- GRID CONTROL -->
                <div
                  class="flex flex-col gap-1.5"
                  x-data="{ gridColumns: $wire.entangle('content.{{ $blockId }}.data.grid.cols').live || '1' }"
                >
                  <label class="text-xxs font-bold text-gray-700 uppercase"
                    >Jumlah Kolom</label
                  >
                  <div
                    class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
                  >
                    @for ($i = 1; $i <= 4; $i++)
                      <button
                        type="button"
                        x-on:click="gridColumns = '{{ $i }}'"
                        class="group flex h-5.5 w-5.5 items-center justify-center rounded-sm p-2 text-xs font-black transition-all outline-none"
                        x-bind:class="gridColumns === '{{ $i }}' ? 'bg-white shadow-sm text-forest' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                      >
                        {{ $i }}
                      </button>
                    @endfor
                  </div>
                </div>
                <!-- MARGIN CONTROL -->
                <div
                  class="flex flex-col gap-1.5"
                  x-data="{ localMargin: $wire.entangle('content.{{ $blockId }}.data.grid.margin_bottom').live || 'mb-4' }"
                >
                  <label class="text-xxs font-bold text-gray-700 uppercase"
                    >Margin Bawah</label
                  >
                  <div
                    class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
                  >
                    @foreach ($marginBottom as $margin)
                      <button
                        type="button"
                        x-on:click="localMargin = '{{ $margin['value'] }}'"
                        class="group flex items-center gap-1 rounded px-1.5 py-1 transition-all outline-none"
                        x-bind:class="localMargin === '{{ $margin['value'] }}' ? 'bg-white shadow-sm text-forst' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                        title="{{ $margin['name'] ?? $margin['label'] }}"
                      >
                        <!-- Representasi Visual Margin -->
                        <div class="flex h-3 w-3 flex-col justify-end">
                          <div
                            class="w-full flex-1 rounded-[1px] opacity-80 transition-all"
                            x-bind:class="localMargin === '{{ $margin['value'] }}' ? 'bg-forest' : 'bg-gray-400/70'"
                            {{-- 🌟 Solusi: Gunakan inline style agar tidak terkena Purge Tailwind --}}
                            style="margin-bottom: {{ $margin['preview'] }};"
                          ></div>
                          <div
                            class="h-[2px] w-full rounded-full transition-colors"
                            x-bind:class="localMargin === '{{ $margin['value'] }}' ? 'bg-forest' : 'bg-gray-400/70'"
                          ></div>
                        </div>

                        <!-- Label Teks -->
                        <span
                          x-bind:class="localMargin === '{{ $margin['value'] }}' ? 'text-forest' : 'text-gray-400/70'"
                          class="text-xxs font-bold uppercase"
                          >{{
                            $margin["name"] ??
                              $margin["label"]
                          }}</span
                        >
                      </button>
                    @endforeach
                  </div>
                </div>
              </div>

              {{-- TAB CARD NAVIGATION --}}
              <div
                class="flex w-full min-w-0 items-end gap-2 md:w-auto md:flex-1 md:justify-end"
              >
                {{-- 🌟 WADAH SCROLL DENGAN ALPINE JS --}}
                <div
                  class="relative flex min-w-0 flex-1 items-center md:max-w-[400px] md:flex-none lg:max-w-[600px]"
                  x-data="{
                    canScrollLeft: false,
                    canScrollRight: false,
                    checkScroll() {
                      let el = this.$refs.tabContainer;
                      if (!el) return;
                      // Cek apakah bisa geser kiri (posisi > 2px)
                      this.canScrollLeft = el.scrollLeft > 2;
                      // Cek apakah bisa geser kanan (total scroll + lebar terlihat < lebar total)
                      this.canScrollRight =
                        Math.ceil(el.scrollLeft + el.clientWidth) <
                        el.scrollWidth - 2;
                    },
                  }"
                  x-init="
                    $nextTick(() => checkScroll());
                    // Pantau perubahan ukuran layar
                    window.addEventListener('resize', () => checkScroll());
                    // Pantau penambahan/pengurangan tab oleh Livewire
                    let observer = new MutationObserver(() => checkScroll());
                    observer.observe($refs.tabContainer, {
                      childList: true,
                      subtree: true,
                    });
                  "
                >
                  <!-- 🌟 TOMBOL PANAH KIRI -->
                  <div
                    x-show="canScrollLeft"
                    x-transition.opacity.duration.300ms
                    x-cloak
                    class="absolute top-0 bottom-0 left-0 z-10 flex items-center bg-linear-to-r from-gray-50 via-gray-50/90 to-transparent pr-6 pl-1"
                  >
                    <button
                      type="button"
                      x-on:click="
                        $refs.tabContainer.scrollBy({
                          left: -200,
                          behavior: 'smooth',
                        })
                      "
                      class="hover:text-foresty flex h-6 w-6 items-center justify-center rounded-full bg-white text-gray-600 shadow-md ring-1 ring-gray-200 transition-transform hover:scale-110"
                    >
                      <x-dynamic-component
                        component="lucide-chevron-left"
                        class="h-3.5 w-3.5"
                        stroke-width="3"
                      />
                    </button>
                  </div>

                  {{-- NAVIGASI TAB KARTU --}}
                  <div
                    x-ref="tabContainer"
                    @scroll.debounce.50ms="checkScroll"
                    class="no-scrollbar flex flex-1 gap-2 overflow-x-auto scroll-smooth"
                  >
                    @foreach ($cards as $index => $card)
                      <div
                        wire:key="tab-{{ $blockId }}-{{ $card['id'] ?? $index }}"
                        class="flex shrink-0 items-center gap-1 rounded-md border px-2 py-1 transition-colors"
                        x-bind:class="activeCard === {{ $index }} ? 'bg-foresty text-white border-foresty shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-white hover:border-gray-300'"
                      >
                        <!-- NAV CARD -->
                        <button
                          type="button"
                          x-on:click="syncTabs({{ $index }})"
                          class="pr-2 pl-1 text-xs font-bold whitespace-nowrap outline-none"
                        >
                          Kartu {{ $index + 1 }}
                        </button>

                        <!-- DUPLICATE CARD -->
                        <button
                          type="button"
                          wire:click="duplicateCardItem('{{ $blockId }}', {{ $index }})"
                          class="rounded p-0.5 transition-colors outline-none hover:bg-blue-500 hover:text-white"
                          x-bind:class="activeCard === {{ $index }} ? 'text-gray-200 hover:text-white' : 'text-gray-400 hover:text-white'"
                          title="Gandakan Kartu"
                        >
                          <x-dynamic-component
                            component="lucide-copy"
                            class="h-3 w-3"
                          />
                        </button>

                        <!-- REMOVE CARD -->
                        <button
                          type="button"
                          wire:click="removeCardItem('{{ $blockId }}', {{$index }})"
                          x-on:click="syncTabs(Math.max(0, activeCard - 1))"
                          class="rounded p-0.5 transition-colors outline-none hover:bg-red-500 hover:text-white"
                          x-bind:class="activeCard === {{ $index }} ? 'text-gray-200 hover:text-white' : 'text-gray-400 hover:text-white'"
                          title="Hapus Kartu"
                        >
                          <x-dynamic-component
                            component="lucide-x"
                            class="h-3 w-3"
                          />
                        </button>
                      </div>
                    @endforeach
                  </div>

                  <!-- 🌟 TOMBOL PANAH KANAN -->
                  <div
                    x-show="canScrollRight"
                    x-transition.opacity.duration.300ms
                    x-cloak
                    class="absolute top-0 right-0 bottom-0 z-10 flex items-center bg-linear-to-l from-gray-50 via-gray-50/90 to-transparent pr-1 pl-6"
                  >
                    <button
                      type="button"
                      x-on:click="
                        $refs.tabContainer.scrollBy({
                          left: 200,
                          behavior: 'smooth',
                        })
                      "
                      class="hover:text-foresty flex h-6 w-6 items-center justify-center rounded-full bg-white text-gray-600 shadow-md ring-1 ring-gray-200 transition-transform hover:scale-110"
                    >
                      <x-dynamic-component
                        component="lucide-chevron-right"
                        class="h-3.5 w-3.5"
                        stroke-width="3"
                      />
                    </button>
                  </div>
                </div>

                <!-- TOMBOL MENU TAMBAH -->
                <div class="relative shrink-0" x-data="{ openMenu: false }">
                  <button
                    type="button"
                    x-on:click="openMenu = !openMenu"
                    x-on:click.away="openMenu = false"
                    class="bg-sage-soft text-foresty hover:bg-foresty flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-bold transition-colors outline-none hover:text-white"
                  >
                    <x-dynamic-component
                      component="lucide-plus"
                      class="h-3 w-3"
                    />
                    Tambah
                  </button>

                  <div
                    x-show="openMenu"
                    x-cloak
                    class="absolute top-full right-0 z-50 mt-1 w-40 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg"
                  >
                    @foreach ([
                        "stack" => "Stack (Tumpuk)",
                        "icon-text" => "Baris Ikon",
                        "document" => "Dokumen (3 Kolom)"
                      ]
                      as $bType => $bLabel)
                      <button
                        type="button"
                        x-on:click="$wire.addCardItem('{{ $blockId }}', '{{ $bType }}'); openMenu = false"
                        class="w-full border-b border-gray-100 px-3 py-2 text-left text-xs outline-none hover:bg-gray-50"
                      >
                        {{ $bLabel }}
                      </button>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>
          @endif
          <!-- AREA PENGATURAN KARTU (WARNA, BORDER, DLL) -->
          @foreach ($cards as $cIndex => $card)
            <!-- ========================================== -->
            <!-- CONTROLS CARD CONTAINER -->
            <!-- ========================================== -->
            <div
              x-show="activeCard === {{ $cIndex }}"
              x-cloak
              @sync-card-{{ strtolower($blockId) }}.window="if ($event.detail !== undefined) { activeCard = $event.detail.card; }"
              wire:key="card-settings-{{ $blockId }}-{{$cIndex }}"
              class="rounded-t-xl border-x border-t border-gray-400 px-2 pt-2 pb-4"
            >
              <div class="rounded-lg border border-gray-100 p-3 shadow-inner">
                @php
                  $cBg = $card["container"]["bg"] ?? "bg-white";
                  $cBorderWidth = $card["container"]["border_width"] ?? "border-0";
                  $cBorderStyle = $card["container"]["border_style"] ?? "border-solid";
                  $cBorderColor = $card["container"]["border_color"] ?? "border-gray-200";
                  $cRadius = $card["container"]["radius"] ?? "rounded-[14px]";
                  $cPad = $card["container"]["padding"] ?? "p-4";
                  $cAlign = $card["container"]["align_y"] ?? "items-center";
                  $basePath = "content.{$blockId}.data.cards.{$cIndex}.container";
                @endphp

                <!-- CONTROLS-->
                <div class="flex flex-wrap gap-5">
                  {{-- Latar Belakang --}}
                  {{-- <div class="flex flex-col gap-1.5">
                    <span class="text-xxs font-bold text-gray-700 uppercase"
                      >Latar Kartu</span
                    >
                    <div
                      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
                    >
                      <button
                        type="button"
                        title="Putih"
                        x-on:click="$wire.set('{{$basePath }}.bg', 'bg-white')"
                        class="rounded p-1.5 transition-all outline-none {{ $cBg === 'bg-white' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
                      >
                        <div
                          class="h-4 w-4 rounded-full border border-gray-300 bg-white"
                        ></div>
                      </button>
                      <button
                        type="button"
                        title="Mist"
                        x-on:click="$wire.set('{{$basePath }}.bg', 'bg-mist')"
                        class="rounded p-1.5 transition-all outline-none {{ $cBg === 'bg-mist' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
                      >
                        <div
                          class="h-4 w-4 rounded-full border border-gray-200 bg-gray-100"
                        ></div>
                      </button>
                      <button
                        type="button"
                        title="Foresty"
                        x-on:click="$wire.set('{{$basePath }}.bg', 'bg-foresty text-white')"
                        class="rounded p-1.5 transition-all outline-none {{ $cBg === 'bg-foresty text-white' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}"
                      >
                        <div class="bg-foresty h-4 w-4 rounded-full"></div>
                      </button>
                    </div>
                  </div> --}}

                  {{-- BG COLOR --}}
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xxs font-bold text-gray-700 uppercase"
                      >Latar Kartu</label
                    >
                    <div class="flex flex-wrap gap-3 pb-6">
                      @foreach ($cardBgColors as $color)
                        <!-- 1. Gunakan 'group/btn' alih-alih 'group' biasa -->
                        <div
                          class="group/btn relative flex flex-col items-center"
                        >
                          {{-- wire:model.live="content.{{ $blockId }}.data.background" --}}
                          <button
                            type="button"
                            {{-- $wire.set('{{$basePath }}.bg', 'bg-white') --}}
                            x-on:click="$wire.set('{{ $basePath }}.bg}}', '{{ $color['value'] }}')"
                            class="border border-gray-200 hover:ring-forest {{ $color['value'] }} h-6 w-6 rounded-md shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none"
                            x-bind:class="($wire.get('{{ $basePath }}')?.bg ?? 'bg-paper').toLowerCase() === 
                            '{{ strtolower($color['value']) }}' ? 
                            'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
                          ></button>

                          <!-- 2. Ubah 'group-hover' menjadi 'group-hover/btn' -->
                          <span
                            class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
                            x-bind:class="($wire.get('{{ $basePath }}')?.bg ?? 'bg-paper').toLowerCase() === 
                            '{{ strtolower($color['value']) }}' ? 
                            'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
                          >
                            {{ $color["name"] }}
                          </span>
                        </div>
                      @endforeach
                    </div>
                  </div>

                  {{-- Border Width --}}
                  <div class="flex flex-col gap-1.5">
                    <span class="text-xxs font-bold text-gray-700 uppercase"
                      >Tebal Tepian</span
                    >
                    <div
                      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
                    >
                      @foreach ([
                          ["value" => "border-0", "label" => "0px"],
                          ["value" => "border", "label" => "1px"],
                          ["value" => "border-2", "label" => "2px"]
                        ]
                        as $bw)
                        <button
                          type="button"
                          x-on:click="$wire.set('{{$basePath }}.border_width', '{{ $bw['value'] }}')"
                          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cBorderWidth === $bw['value'] ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                        >
                          {{ $bw["label"] }}
                        </button>
                      @endforeach
                    </div>
                  </div>
                  {{-- Border Style --}}
                  <div class="flex flex-col gap-1.5">
                    <span class="text-xxs font-bold text-gray-700 uppercase"
                      >Tipe Tepian</span
                    >

                    <div
                      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
                    >
                      @foreach ($borderStyles as $borderStyle)
                        <button
                          type="button"
                          x-on:click="$wire.set('{{$basePath }}.border_style', {{ $borderStyle['value'] }})"
                          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cBorderStyle === $borderStyle['value'] ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                        >
                          <div
                            class="border-forest h-4 w-4 rounded-sm border-2 {{ $borderStyle['value'] }}"
                            title="{{ $borderStyle['name'] }}"
                          ></div>
                        </button>
                      @endforeach
                    </div>
                  </div>

                  {{-- Border Color --}}
                  {{-- <div class="flex flex-col gap-1.5">
                    <span
                      class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
                      >Warna Tepian</span
                    >
                    <div
                      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
                    >
                      <button
                        type="button"
                        x-on:click="$wire.set('{{$basePath }}.border_color', 'border-transparent')"
                        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cBorderColor === 'border-gray-200' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                      >
                        Abu-abu
                      </button>
                      <button
                        type="button"
                        x-on:click="$wire.set('{{$basePath }}.border_color', 'border-foresty')"
                        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cBorderColor === 'border-foresty' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                      >
                        Foresty
                      </button>
                    </div>
                  </div> --}}

                  {{-- BORDER COLOR --}}
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xxs font-bold text-gray-700 uppercase"
                      >Warna Tepian</label
                    >
                    <div class="flex flex-wrap gap-3 pb-6">
                      <!--  x-on:/click="$wire.set('{/{$basePath }}.border_color', 'border-transparent')"-->
                      {{-- <div
                        class="group/btn relative flex flex-col items-center"
                      >
                      <!-- $wire.set('{{$basePath }}.bg', 'bg-white') -->
                        <button
                          type="button"
                          x-on:click="$wire.set('{{ $basePath }}.border_color}}', '{{ $color['value'] }}')"
                          class="border border-gray-200 hover:ring-forest {{ $color['preview'] }} h-6 w-6 rounded-md shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none"
                          x-bind:class="
                          ($wire.get('{{ $basePath }}')?.border_color ?? 'bg-paper').toLowerCase() ===  '{{ strtolower($color['value']) }}' ?  'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
                        ></button>

                        <!-- 2. Ubah 'group-hover' menjadi 'group-hover/btn' -->
                        <span
                          class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
                          x-bind:class="($wire.get('{{ $basePath }}')?.border_color ?? 'bg-paper').toLowerCase() === 
                          '{{ strtolower($color['value']) }}' ? 
                          'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
                        >
                          {{ $color["name"] }/}
                        </span>
                      </div> --}}
                      @foreach ($cardBorderColors as $color)
                        <div
                          class="group/btn relative flex flex-col items-center"
                        >
                          <button
                            type="button"
                            x-on:click="$wire.set('{{ $basePath }}.border_color', '{{ $color['value'] }}')"
                            class="flex items-center justify-center border border-gray-200 hover:ring-forest {{ $color['preview'] ?? $color['value'] }} h-6 w-6 rounded-md shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none"
                            x-bind:class="($wire.get('{{ $basePath }}')?.border_color ?? 'border-transparent').toLowerCase() === '{{ strtolower($color['value']) }}' ? 'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
                          >
                            {{-- 🌟 MUNCULKAN IKON BAN JIKA VALUE ADALAH TRANSPARANT --}}
                            @if (str_contains( strtolower($color["value"]), "border-transparent" ))
                              {{-- @if (strtolower($color["value"]) === "border-transparent") --}}
                              {{-- @if (preg_match( "/(transparent|none|\/0)/i", $color["value"] )) --}}
                              <x-dynamic-component
                                component="lucide-ban"
                                class="text-coral h-3.5 w-3.5 opacity-80"
                                stroke-width="2.5"
                              />

                            @endif
                          </button>

                          <!-- Label Hover -->
                          <span
                            class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
                            x-bind:class="($wire.get('{{ $basePath }}')?.border_color ?? 'border-transparent').toLowerCase() === '{{ strtolower($color['value']) }}' ? 'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
                          >
                            {{ $color["name"] }}
                          </span>
                        </div>
                      @endforeach
                    </div>
                  </div>

                  {{-- Radius --}}
                  <div class="flex flex-col gap-1.5">
                    <span class="text-xxs font-bold text-gray-700 uppercase"
                      >Radius Sudut</span
                    >
                    <div
                      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
                    >
                      @foreach ($borderRadius as $item)
                        <button
                          type="button"
                          x-on:click="$wire.set('{{$basePath }}.radius', '{{ $item['value'] }}')"
                          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cRadius === $item['value'] ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                          title="{{ $item["name"] }}"
                        >
                          <div
                            class="border-forest h-3.5 w-3.5  border-t-2 border-l-2 {{ $item['preview'] }}"
                          ></div>
                        </button>
                      @endforeach
                    </div>
                  </div>

                  {{-- Padding --}}
                  <div class="flex flex-col gap-1.5">
                    <span class="text-xxs font-bold text-gray-700 uppercase"
                      >Sudut & Padding</span
                    >
                    <div
                      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
                    >
                      @foreach ($cardPadding as $item)
                        <button
                          type="button"
                          x-on:click="$wire.set('{{$basePath }}.padding', '{{ $item['value'] }}')"
                          class="rounded flex gap-2 px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cPad === $item['value'] ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                        >
                          <!-- 🌟 Representasi Visual Padding -->
                          <div
                            {{-- Menerapkan 'p-0.5' dll pada kotak luar --}}
                            class="flex h-4- w-4 items-center justify-center rounded-[3px] border border-current {{ $item['preview'] }}"
                          >
                            <!-- Kotak dalam yang otomatis menyusut sesuai padding luar -->
                            <div
                              class="h-full w-full rounded-[1px] transition-all duration-200"
                              x-bind:class="'{{ $cPad }}' === '{{ $item['preview'] }}' ? 'bg-foresty' : 'bg-current opacity-40 group-hover:opacity-70'"
                            ></div>
                          </div>

                          <!-- Teks Keterangan (Kecil, Sedang, Besar) -->
                          <span>{{ $item["name"] }}</span>
                        </button>
                      @endforeach
                    </div>
                  </div>

                  {{-- 🌟 POSISI VERTIKAL (ALIGN Y) 🌟 --}}
                  <div class="flex flex-col gap-1.5">
                    <span class="text-xxs font-bold text-gray-700 uppercase"
                      >Perataan Vertikal</span
                    >
                    <div
                      class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
                    >
                      <!-- Atas -->
                      <button
                        type="button"
                        title="Rata Atas"
                        x-on:click="$wire.set('{{$basePath }}.align_y', 'items-start')"
                        class="rounded p-1.5 transition-all outline-none {{ $cAlign === 'items-start' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}"
                      >
                        <div
                          class="flex h-4 w-4 flex-col justify-start rounded-sm border border-current p-0.5"
                        >
                          <div
                            class="h-1 w-full rounded-[1px] bg-current"
                          ></div>
                        </div>
                      </button>

                      <!-- Tengah -->
                      <button
                        type="button"
                        title="Rata Tengah"
                        x-on:click="$wire.set('{{$basePath }}.align_y', 'items-center')"
                        class="rounded p-1.5 transition-all outline-none {{ $cAlign === 'items-center' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}"
                      >
                        <div
                          class="flex h-4 w-4 flex-col justify-center rounded-sm border border-current p-0.5"
                        >
                          <div
                            class="h-1 w-full rounded-[1px] bg-current"
                          ></div>
                        </div>
                      </button>

                      <!-- Bawah -->
                      <button
                        type="button"
                        title="Rata Bawah"
                        x-on:click="$wire.set('{{$basePath }}.align_y', 'items-end')"
                        class="rounded p-1.5 transition-all outline-none {{ $cAlign === 'items-end' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}"
                      >
                        <div
                          class="flex h-4 w-4 flex-col justify-end rounded-sm border border-current p-0.5"
                        >
                          <div
                            class="h-1 w-full rounded-[1px] bg-current"
                          ></div>
                        </div>
                      </button>

                      <!-- Sama Tinggi -->
                      <button
                        type="button"
                        title="Sama Tinggi (Stretch)"
                        x-on:click="$wire.set('{{$basePath }}.align_y', 'items-stretch')"
                        class="rounded p-1.5 transition-all outline-none {{ $cAlign === 'items-stretch' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}"
                      >
                        <div
                          class="flex h-4 w-4 flex-col justify-stretch rounded-sm border border-current p-0.5"
                        >
                          <div
                            class="h-full w-full rounded-[1px] bg-current opacity-60"
                          ></div>
                        </div>
                      </button>
                    </div>
                  </div>

                  {{-- URL Link (Opsional) --}}
                  <div class="flex min-w-[200px] flex-1 flex-col gap-1.5">
                    <span class="text-xxs font-bold text-gray-700 uppercase"
                      >Tautan Kartu (Opsional)</span
                    >
                    <div class="flex items-center gap-1.5">
                      {{-- <input
                      type="text"
                      wire:model.blur="content.{{ $blockId }}.data.cards.{{$cIndex }}.container.url"
                      placeholder="https://..."
                      class="focus:border-foresty focus:ring-foresty h-7 w-full rounded border-gray-300 p-1.5 text-xs shadow-sm"
                    /> --}}
                      <button
                        type="button"
                        {{-- 🌟 KUNCI: Kirim event dengan detail 'target' berisi alamat array Livewire --}}
                        x-on:click="$dispatch('buka-modal-link', { target: 'content.{{ $blockId }}.data.cards.{{$cIndex }}.container.url' })"
                        class="focus:border-foresty focus:ring-foresty tezt-xxs text-forest flex h-7 w-fit items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white p-1.5 px-3 py-2 text-sm shadow-sm transition-colors hover:bg-gray-50"
                      >
                        <span
                          x-text="$wire.content['{{ $blockId }}'].data?.cards?.['{{$cIndex }}'].container?.url || 'Pilih Tautan...'"
                        ></span>
                        <x-dynamic-component
                          component="lucide-link"
                          class="text-forest h-4 w-4"
                        />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </x-slot:controls>

      <!-- AREA RENDER LAYOUT KARTU BERSARANG -->
      <div class="">
        @foreach ($cards as $cIndex => $card)
          <div
            x-show="activeCard === {{ $cIndex }}"
            x-cloak
            wire:key="card-editor-{{ $blockId }}-{{$cIndex }}"
          >
            <!-- 🌟 KUNCI: Teruskan activeLocales ke layout editor -->
            <x-blocks.editor.card-builder-layout-editor
              :block-id="$blockId"
              :c-index="$cIndex"
              :card="$card"
              :active-locales="$activeLocales"
              :icons-list="$iconsList"
            />
          </div>
        @endforeach
      </div>

    @endif
  </div>

  <!-- ========================================== -->
  <!-- AREA PRATINJAU LANGSUNG (LIVE PREVIEW)     -->
  <!-- ========================================== -->
  @if (count($cards) > 0 && !$isNested)
    <x-slot:preview>
      <div
        x-data="{ activeCard: 0, previewMode: 'focus' }"
        @sync-card-{{ strtolower($blockId) }}.window="if ($event.detail) { activeCard =$event.detail.card; }"
        class="flex w-full flex-col"
      >
        <div
          class="mb-2 flex items-center justify-between border-b border-gray-200"
        >
          <span
            class="text-xs font-bold tracking-widest text-gray-500 uppercase"
            >Pratinjau Kartu</span
          >

          <!-- 🌟 TOGGLE MODE PREVIEW (FOKUS VS SEMUA) -->
          <div
            class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
          >
            <button
              type="button"
              @click="previewMode = 'focus'"
              class="rounded px-2.5 py-1 text-[9px] font-bold transition-all outline-none"
              :class="previewMode === 'focus'
                ? 'bg-white text-foresty shadow-sm'
                : 'text-gray-500 hover:text-gray-700'"
            >
              Fokus Aktif
            </button>
            <button
              type="button"
              @click="previewMode = 'all'"
              class="rounded px-2.5 py-1 text-[9px] font-bold transition-all outline-none"
              :class="previewMode === 'all'
                ? 'bg-white text-foresty shadow-sm'
                : 'text-gray-500 hover:text-gray-700'"
            >
              Lihat Semua (Grid)
            </button>
          </div>
        </div>

        <div
          class="grid gap-6"
          :class="effectiveLayout === 'single'
            ? 'grid-cols-1'
            : splitLanguages.length >= 3
              ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
              : 'grid-cols-1 md:grid-cols-2'"
        >
          @foreach ($activeLocales as $lang)
            <div
              wire:key="cb-preview-{{ $blockId }}-{{$lang }}"
              x-show="effectiveLayout === 'single' ? singleActiveLang === '{{ $lang }}' : splitLanguages.includes('{{$lang }}')"
              x-cloak
              class="flex w-full flex-col items-center"
            >
              <div class="flex w-full items-center justify-start">
                <span
                  class="bg-sage-soft text-foresty text-xxs rounded px-2 py-0.5 font-bold tracking-wider uppercase shadow-sm"
                  >{{ $lang }}</span
                >
              </div>
              <style
                x-html="
                  '.preview-atomic-{{ $blockId }}-{{ $lang }} > .grid { ' + 
                  (previewMode === 'focus' 
                      ? 'grid-template-columns: 1fr !important; max-width: 380px; margin: 0 auto;' 
                      : 'grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)) !important; width: 100%;') + 
                  ' } ' + 
                  (previewMode === 'focus' 
                      ? '.preview-atomic-{{ $blockId }}-{{ $lang }} > .grid > *:not(:nth-child(' + (Number(activeCard) + 1) + ')) { display: none !important; }' 
                      : '')
                "
              ></style>

              <div
                class="preview-atomic-{{ $blockId }}-{{$lang }} w-full pointer-events-none border border-dashed border-forest p-2 bg-gray-200"
                style="zoom: 0.75"
              >
                @include ("components.blocks.render.card-builder",
                  ["data" => $data, "lang" => $lang, "isPreview" => true])
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </x-slot:preview>
  @endif
</x-blocks.editor.wrapper>
