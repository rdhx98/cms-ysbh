@props ([
  "blockId",
  "block",
  "activeLocales" => [],
  "context" => null,
  "parentId" => null,
  "parentZone" => null
])

@php
  $iconsList = config("icons.lucide", []);
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

  <x-slot:settings>
    <div class="flex items-center gap-2">
      <span
        class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase"
        >Grid:</span
      >
      {{-- <select
        wire:model.live="content.{{ $blockId }}.data.grid.cols"
        class="focus:border-foresty focus:ring-foresty h-7 rounded-md border-gray-200 bg-white py-0 pr-7 pl-2 text-xs font-bold text-gray-700 shadow-sm"
      >
        <option value="1">1 Kolom</option>
        <option value="2">2 Kolom</option>
        <option value="3">3 Kolom</option>
        <option value="4">4 Kolom</option>
      </select> --}}
      <!-- 🌟 KUNCI: Kunci ke 1 Kolom jika bersarang -->
      @if (!$isNested)
        <select
          wire:model.live="content.{{ $blockId }}.data.grid.cols"
          class="focus:border-foresty focus:ring-foresty h-7 rounded-md border-gray-200 bg-white py-0 pr-7 pl-2 text-xs font-bold text-gray-700 shadow-sm"
        >
          <option value="1">1 Kolom</option>
          <option value="2">2 Kolom</option>
          <option value="3">3 Kolom</option>
          <option value="4">4 Kolom</option>
        </select>
      @endif
      <select
        wire:model.live="content.{{ $blockId }}.data.grid.margin_bottom"
        class="focus:border-foresty focus:ring-foresty h-7 rounded-md border-gray-200 bg-white py-0 pr-7 pl-2 text-xs font-bold text-gray-700 shadow-sm"
      >
        <option value="mb-0">Bawah: 0</option>
        <option value="mb-8">Bawah: Normal</option>
        <option value="mb-16">Bawah: Jauh</option>
      </select>
    </div>
  </x-slot:settings>

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
      @if (!$isNested)
        <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
          <div class="no-scrollbar flex flex-1 gap-2 overflow-x-auto">
            {{-- NAVIGASI TAB KARTU --}}
            @foreach ($cards as $index => $card)
              <div
                wire:key="tab-{{ $blockId }}-{{ $card['id'] ?? $index }}"
                class="flex shrink-0 items-center gap-1 rounded-md border px-2 py-1 transition-colors"
                x-bind:class="activeCard === {{ $index }} ? 'bg-foresty text-white border-foresty shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-white hover:border-gray-300'"
              >
                <button
                  type="button"
                  x-on:click="syncTabs({{ $index }})"
                  class="pr-2 pl-1 text-xs font-bold whitespace-nowrap outline-none"
                >
                  Kartu {{ $index + 1 }}
                </button>

                <button
                  type="button"
                  wire:click="removeCardItem('{{ $blockId }}', {{$index }})"
                  x-on:click="syncTabs(Math.max(0, activeCard - 1))"
                  class="rounded p-0.5 transition-colors outline-none hover:bg-red-500 hover:text-white"
                  title="Hapus Kartu"
                >
                  <x-dynamic-component component="lucide-x" class="h-3 w-3" />
                </button>
              </div>
            @endforeach
          </div>

          <div class="relative shrink-0" x-data="{ openMenu: false }">
            <button
              type="button"
              x-on:click="openMenu = !openMenu"
              x-on:click.away="openMenu = false"
              class="bg-sage-soft text-foresty hover:bg-foresty flex items-center gap-1 rounded-md px-3 py-1.5 text-xs font-bold transition-colors outline-none hover:text-white"
            >
              <x-dynamic-component component="lucide-plus" class="h-3 w-3" />
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
      @endif

      <!-- AREA PENGATURAN KARTU (WARNA, BORDER, DLL) -->
      @foreach ($cards as $cIndex => $card)
        <div
          x-show="activeCard === {{ $cIndex }}"
          x-cloak
          wire:key="card-settings-{{ $blockId }}-{{$cIndex }}"
          class="mb-4 rounded-lg border border-gray-100 bg-gray-50/50 p-3 shadow-inner"
        >
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

          <div class="flex flex-wrap gap-5">
            {{-- Latar Belakang --}}
            <div class="flex flex-col gap-1.5">
              <span
                class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
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
            </div>

            {{-- Border Width & Style --}}
            <div class="flex flex-col gap-1.5">
              <span
                class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
                >Tebal & Tipe Tepian</span
              >
              <div
                class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
              >
                <button
                  type="button"
                  x-on:click="$wire.set('{{$basePath }}.border_width', 'border-0')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cBorderWidth === 'border-0' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  Tanpa
                </button>
                <button
                  type="button"
                  x-on:click="$wire.set('{{$basePath }}.border_width', 'border')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cBorderWidth === 'border' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  1px
                </button>
                <button
                  type="button"
                  x-on:click="$wire.set('{{$basePath }}.border_width', 'border-2')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cBorderWidth === 'border-2' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  2px
                </button>
                <div class="mx-1 h-4 w-px bg-gray-300"></div>
                <button
                  type="button"
                  x-on:click="$wire.set('{{$basePath }}.border_style', 'border-solid')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cBorderStyle === 'border-solid' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  Solid
                </button>
                <button
                  type="button"
                  x-on:click="$wire.set('{{$basePath }}.border_style', 'border-dashed')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cBorderStyle === 'border-dashed' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  Dashed
                </button>
              </div>
            </div>

            {{-- Border Color --}}
            <div class="flex flex-col gap-1.5">
              <span
                class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
                >Warna Tepian</span
              >
              <div
                class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
              >
                <button
                  type="button"
                  x-on:click="$wire.set('{{$basePath }}.border_color', 'border-gray-200')"
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
            </div>

            {{-- Radius & Padding --}}
            <div class="flex flex-col gap-1.5">
              <span
                class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
                >Sudut & Padding</span
              >
              <div
                class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
              >
                <button
                  type="button"
                  x-on:click="$wire.set('{{$basePath }}.radius', 'rounded-none')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cRadius === 'rounded-none' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  Siku
                </button>
                <button
                  type="button"
                  x-on:click="$wire.set('{{$basePath }}.radius', 'rounded-[14px]')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cRadius === 'rounded-[14px]' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  Bulat
                </button>
                <div class="mx-1 h-4 w-px bg-gray-300"></div>
                <button
                  type="button"
                  x-on:click="$wire.set('{{$basePath }}.padding', 'p-4')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cPad === 'p-4' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  Pad Kecil
                </button>
                <button
                  type="button"
                  x-on:click="$wire.set('{{$basePath }}.padding', 'p-6 md:p-8')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $cPad === 'p-6 md:p-8' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  Pad Besar
                </button>
              </div>
            </div>

            {{-- 🌟 POSISI VERTIKAL (ALIGN Y) 🌟 --}}
            <div class="flex flex-col gap-1.5">
              <span
                class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
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
                    <div class="h-1 w-full rounded-[1px] bg-current"></div>
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
                    <div class="h-1 w-full rounded-[1px] bg-current"></div>
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
                    <div class="h-1 w-full rounded-[1px] bg-current"></div>
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
              <span
                class="text-[9px] font-bold tracking-wide text-gray-400 uppercase"
                >Tautan Kartu (Opsional)</span
              >
              <div class="flex items-center gap-1.5">
                <input
                  type="text"
                  wire:model.blur="content.{{ $blockId }}.data.cards.{{$cIndex }}.container.url"
                  placeholder="https://..."
                  class="focus:border-foresty focus:ring-foresty h-7 w-full rounded border-gray-300 p-1.5 text-xs shadow-sm"
                />
              </div>
            </div>
          </div>
        </div>
      @endforeach

      <!-- AREA RENDER LAYOUT KARTU BERSARANG -->
      <div>
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
        x-data="{ activeCard: 0, previewMode: 'all' }"
        @sync-card-{{ strtolower($blockId) }}.window="if ($event.detail) { activeCard =$event.detail.card; }"
        class="flex w-full flex-col"
      >
        <div
          class="mb-4 flex items-center justify-between border-b border-gray-200 pb-2"
        >
          <span
            class="text-[10px] font-bold tracking-widest text-gray-500 uppercase"
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
                  class="bg-sage-soft text-foresty mb-3 rounded px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase shadow-sm"
                  >{{ $lang }}</span
                >
              </div>

              <!-- 🌟 CSS SAKTI: Auto-Fit Grid memotong paksaan Tailwind -->
              <style
                x-text="`
                .preview-atomic-{{ $blockId }}-{{ $lang }} .grid { 
                    ${previewMode === 'focus' 
                        ? 'grid-template-columns: 1fr !important; max-width: 380px; margin: 0 auto;' 
                        : 'grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)) !important; width: 100%;'} 
                }
                ${previewMode === 'focus' 
                    ? `.preview-atomic-{{ $blockId }}-{{ $lang }} .grid > *:not(:nth-child(${activeCard + 1})) { display: none !important; }` 
                    : ''}
              `"
              ></style>

              <div
                class="preview-atomic-{{ $blockId }}-{{$lang }} w-full pointer-events-none border border-dashed border-forest p-2 bg-gray-200"
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
