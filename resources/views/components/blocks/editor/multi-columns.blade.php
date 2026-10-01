@props ([
  "blockId",
  "block",
  "activeLocales" => [],
  "allContent" => [],
])

@php
  $colCount = (int) ($block["data"]["col_count"] ?? 2);

  // Kumpulkan semua ID anak dari zona yang aktif untuk fitur "Runtuhkan Anak"
  $allChildren = [];
  for ($i = 1; $i <= $colCount; $i++) {
    $allChildren = array_merge(
      $allChildren,
      $block["data"]["col_{$i}_zone"] ?? [],
    );
  }
@endphp

<x-blocks.editor.wrapper :block-id="$blockId" :block="$block">
  <!-- ========================================== -->
  <!-- 1. IDENTITAS BLOK (Kiri Atas)              -->
  <!-- ========================================== -->
  <x-slot:title>
    <div
      class="bg-sage-soft text-foresty flex h-5 w-5 items-center justify-center rounded-sm shadow-sm"
    >
      <x-dynamic-component
        component="lucide-layout-template"
        class="h-3.5 w-3.5"
        stroke-width="2.5"
      />
    </div>
    Kolom Multi
  </x-slot:title>

  <!-- ========================================== -->
  <!-- 2. CUPLIKAN TEKS COLLAPSE (Tengah Atas)    -->
  <!-- ========================================== -->
  <x-slot:snippet>
    <span
      class="block w-full truncate text-right text-xs font-medium text-gray-400 sm:text-left"
      title="{{ $colCount }} Kolom berisi total {{ count($allChildren) }} konten"
    >
      <span class="font-bold text-gray-500">{{ $colCount }}</span> Kolom &bull;
      <span class="font-bold text-gray-500">{{
        count(
          $allChildren,
        )
      }}</span>
      Blok
    </span>
  </x-slot:snippet>

  <!-- ========================================== -->
  <!-- 3. PENGATURAN GLOBAL BLOK (Kanan Atas)     -->
  <!-- ========================================== -->
  <x-slot:settings>
    <!-- Tombol Runtuhkan Anak -->
    <button
      type="button"
      x-data="{ childrenCollapsed: false }"
      x-cloak
      @click.stop="
                if (isCollapsed) {
                    isCollapsed = false;
                    childrenCollapsed = true;
                    $dispatch('force-collapse-children', {{ json_encode($allChildren) }});
                } else {
                    childrenCollapsed = !childrenCollapsed;
                    $dispatch(childrenCollapsed ? 'force-collapse-children' : 'force-expand-children', {{ json_encode($allChildren) }});
                }
             "
      class="border-sage-soft/50 bg-sage-soft/50 text-forest hover:bg-sage-soft flex shrink-0 items-center gap-1 rounded border px-2 py-1 text-[9px] font-bold shadow-sm transition-colors outline-none"
      :title="isCollapsed
        ? 'Buka kolom dan atur susunan blok di dalamnya'
        : childrenCollapsed
          ? 'Buka kembali semua isi kolom'
          : 'Ciutkan semua isi kolom untuk menyusun urutan'"
    >
      <x-dynamic-component
        x-show="isCollapsed"
        component="lucide-list-tree"
        class="h-3 w-3"
        stroke-width="3"
      />
      <x-dynamic-component
        x-show="!isCollapsed && !childrenCollapsed"
        component="lucide-chevrons-down-up"
        class="h-3 w-3"
        stroke-width="3"
      />
      <x-dynamic-component
        x-show="!isCollapsed && childrenCollapsed"
        component="lucide-chevrons-up-down"
        class="h-3 w-3"
        stroke-width="3"
      />
      <span
        x-text="
          isCollapsed
            ? 'Atur Blok'
            : childrenCollapsed
              ? 'Buka Isi'
              : 'Ciutkan Isi'
        "
      ></span>
    </button>
  </x-slot:settings>

  <!-- ========================================== -->
  <!-- 4. AREA KONTEN UTAMA                       -->
  <!-- ========================================== -->
  <div
    x-data="{
         activeTab: 'col_1_zone',
         init() {
            this.$watch(() => $wire.content?.['{{ $blockId }}']?.data?.col_count, (value) => {
                if (value === undefined || value === null) return;
                let maxTab = parseInt(value);
                let currentTabNum = parseInt(this.activeTab.replace('col_', '').replace('_zone', ''));
                if (currentTabNum > maxTab) { this.activeTab = 'col_1_zone'; }
            });
         },
         updateZoneOrder(evt, zone) {
            let order = Array.from(evt.to.children).map(el => el.getAttribute('data-id')).filter(Boolean);
            $wire.reorderChildBlocks('{{ $blockId }}', zone, order);
         }
       }"
    @sync-columns-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail"
  >
    <!-- HEADER KONTROL -->
    <div class="mb-6 flex flex-col gap-4 border-b border-gray-100 pb-6">
      <!-- BARIS 1: Kontrol Dasar -->
      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <label class="block text-xs font-semibold text-gray-500 uppercase"
          >Pengaturan Kolom</label
        >

        <div class="flex flex-wrap items-center gap-4">
          <!-- KONTROL URUTAN HP -->
          @if ($colCount === 2)
            <div class="flex items-center gap-2 border-r border-gray-200 pr-4">
              <span
                class="text-[10px] font-bold tracking-wide text-gray-400 uppercase"
                >Urutan HP:</span
              >
              <button
                type="button"
                wire:click="$toggle('content.{{ $blockId }}.data.mobile_reverse')"
                class="flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-2 py-1 text-[10px] font-bold shadow-sm transition-colors outline-none hover:bg-gray-50"
              >
                @if ($block["data"]["mobile_reverse"] ?? false)
                  <x-dynamic-component
                    component="lucide-arrow-up-down"
                    class="h-3.5 w-3.5 text-blue-500"
                  />
                  <span class="text-blue-600">Kanan di Atas</span>
                @else
                  <x-dynamic-component
                    component="lucide-arrow-down-up"
                    class="text-foresty h-3.5 w-3.5"
                  />
                  <span class="text-gray-600">Kiri di Atas</span>
                @endif
              </button>
            </div>
          @endif

          <!-- KONTROL JUMLAH KOLOM -->
          <div class="flex items-center gap-2">
            <label
              class="text-[10px] font-bold tracking-wide text-gray-400 uppercase"
              >Jumlah:</label
            >
            <select
              wire:model.live="content.{{ $blockId }}.data.col_count"
              class="text-forest focus:border-forest focus:ring-forest cursor-pointer rounded border-gray-300 bg-white py-1 pr-6 pl-2 text-xs font-bold shadow-sm outline-none"
            >
              <option value="2">2 Kolom</option>
              <option value="3">3 Kolom</option>
              <option value="4">4 Kolom</option>
              <option value="5">5 Kolom</option>
              <option value="6">6 Kolom</option>
            </select>
          </div>
        </div>
      </div>

      <!-- BARIS 2: Tata Letak & Spasi Internal -->
      <div
        class="rounded-xl border border-gray-100 bg-gray-50/50 p-4"
        x-data="{
             localAlignX: $wire.content?.['{{ $blockId }}']?.data?.align_x || 'items-start',
             localAlignY: $wire.content?.['{{ $blockId }}']?.data?.align_y || 'justify-start',
             localGap: $wire.content?.['{{ $blockId }}']?.data?.gap || 'gap-4'
           }"
      >
        <label
          class="mb-4 block text-[10px] font-bold tracking-wide text-gray-400 uppercase"
          >Tata Letak & Spasi Kolom</label
        >

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <!-- 1. Sumbu X -->
          <div class="flex flex-col gap-2">
            <span
              class="text-foresty text-[9px] font-bold tracking-wider uppercase"
              >Perataan Horizontal (Kiri-Kanan)</span
            >
            <div
              class="flex items-center gap-1 rounded-lg border border-gray-200/50 bg-gray-200/50 p-1"
            >
              @foreach ([
                  ["items-start", "lucide-align-left", "Kiri"],
                  ["items-center", "lucide-align-center", "Tengah"],
                  ["items-end", "lucide-align-right", "Kanan"]
                ]
                as $opt)
                <button
                  type="button"
                  @click="localAlignX = '{{ $opt[0] }}'; $wire.set('content.{{ $blockId }}.data.align_x', '{{ $opt[0] }}')"
                  class="flex flex-1 items-center justify-center gap-1.5 rounded-md px-2 py-1.5 text-[10px] font-bold transition-all duration-300 outline-none"
                  :class="localAlignX === '{{ $opt[0] }}' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'"
                >
                  <x-dynamic-component
                    component="{{ $opt[1] }}"
                    class="h-3.5 w-3.5"
                  />
                  {{ $opt[2] }}
                </button>
              @endforeach
            </div>
          </div>

          <!-- 2. Sumbu Y -->
          <div class="flex flex-col gap-2">
            <span
              class="text-foresty text-[9px] font-bold tracking-wider uppercase"
              >Perataan Vertikal (Atas-Bawah)</span
            >
            <div
              class="flex flex-wrap items-center gap-1 rounded-lg border border-gray-200/50 bg-gray-200/50 p-1"
            >
              @foreach ([
                  ["justify-start", "lucide-align-vertical-justify-start", "Atas"],
                  ["justify-center", "lucide-align-vertical-justify-center", "Tengah"],
                  ["justify-end", "lucide-align-vertical-justify-end", "Bawah"]
                ]
                as $opt)
                <button
                  type="button"
                  @click="localAlignY = '{{ $opt[0] }}'; $wire.set('content.{{ $blockId }}.data.align_y', '{{ $opt[0] }}')"
                  class="flex flex-1 items-center justify-center gap-1.5 rounded-md px-2 py-1.5 text-[10px] font-bold transition-all duration-300 outline-none"
                  :class="localAlignY === '{{ $opt[0] }}' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'"
                >
                  <x-dynamic-component
                    component="{{ $opt[1] }}"
                    class="h-3.5 w-3.5"
                  />
                  {{ $opt[2] }}
                </button>
              @endforeach
            </div>
          </div>

          <!-- 3. Jarak Antar Blok -->
          <div class="flex flex-col gap-2">
            <span
              class="text-foresty text-[9px] font-bold tracking-wider uppercase"
              >Jarak Antar Blok (Gap)</span
            >
            <div
              class="flex items-center gap-1 rounded-lg border border-gray-200/50 bg-gray-200/50 p-1"
            >
              @foreach ([["gap-0", "0px"], ["gap-4", "16px"], ["gap-8", "32px"]] as $opt)
                <button
                  type="button"
                  @click="localGap = '{{ $opt[0] }}'; $wire.set('content.{{ $blockId }}.data.gap', '{{ $opt[0] }}')"
                  class="flex flex-1 items-center justify-center gap-1.5 rounded-md px-2 py-1.5 text-[10px] font-bold transition-all duration-300 outline-none"
                  :class="localGap === '{{ $opt[0] }}' ? 'bg-white shadow text-foresty' : 'text-gray-500 hover:text-foresty hover:bg-gray-200'"
                >
                  {{ $opt[1] }}
                </button>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 🌟 NAVIGASI TAB KOLOM DINAMIS (Hanya muncul jika mode Split) 🌟 -->
    <div
      x-show="effectiveLayout === 'split'"
      x-cloak
      class="scrollbar-hide relative flex space-x-1 overflow-x-auto overflow-y-hidden rounded-t-xl border border-b-0 border-gray-200 bg-gray-50/80 px-4 pt-4"
    >
      @for ($i = 1; $i <= $colCount; $i++)
        @php $zoneKey = "col_{$i}_zone"; @endphp
        <button
          type="button"
          x-on:click="activeTab = '{{ $zoneKey }}'; $dispatch('sync-columns-tab-{{ strtolower($blockId) }}', '{{ $zoneKey }}')"
          :class="activeTab === '{{ $zoneKey }}' ? 'bg-white text-foresty border-gray-200 shadow-sm border-b-white z-10 -mb-px' : 'bg-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-200/50 border-transparent border-b-gray-200'"
          class="flex shrink-0 items-center gap-2 rounded-t-lg border-t border-r border-l px-5 py-2 text-xs font-bold transition-all outline-none"
        >
          Kolom {{ $i }}
          <span
            class="bg-sage-soft text-foresty rounded px-1.5 py-0.5 text-[9px]"
            >{{
              count(
                $block["data"][$zoneKey] ?? [],
              )
            }}</span
          >
        </button>
      @endfor
    </div>

    <!-- 🌟 GRID UTAMA & DROPZONE 🌟 -->
    <div
      :class="effectiveLayout === 'single' ? 'grid grid-cols-1 lg:grid-cols-{{ $colCount }} gap-4 lg:gap-6' : 'block rounded-b-xl border border-t-0 border-gray-200 bg-white p-4'"
    >
      @for ($i = 1; $i <= $colCount; $i++)
        @php
          $zoneKey = "col_{$i}_zone";
          $zoneBlocks = $block["data"][$zoneKey] ?? [];
          $colWidth = $block["data"]["{$zoneKey}_width"] ?? "auto";
        @endphp

        <div
          x-show="effectiveLayout === 'single' ? true : activeTab === '{{ $zoneKey }}'"
          class="relative flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-gray-50/30 shadow-sm"
          style="display: none"
        >
          <!-- KONTROL LEBAR KOLOM (MUNCUL DI ATAS SETIAP KOLOM) -->
          <div
            class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-white px-4 py-2"
          >
            <span
              class="shrink-0 text-[10px] font-bold tracking-widest text-gray-500 uppercase"
            >
              <span x-show="effectiveLayout === 'single'"
                >Kolom {{ $i }} • </span
              >Lebar:
            </span>

            <div
              class="flex items-center rounded-md bg-gray-100 p-0.5 shadow-inner transition-all duration-200"
            >
              @foreach ([["auto", "Auto"], ["1", "1fr"], ["2", "2fr"], ["3", "3fr"]] as $opt)
                <button
                  type="button"
                  wire:click="$set('content.{{ $blockId }}.data.{{ $zoneKey }}_width', '{{ $opt[0] }}')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $colWidth === $opt[0] ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  {{ $opt[1] }}
                </button>
              @endforeach
            </div>
          </div>

          <!-- DROPZONE ALPINE SORTABLE -->
          <div
            x-sort
            x-sort:config="{ group: '{{ $zoneKey }}_{{ $blockId }}', animation: 150, handle: '.child-drag-handle', onEnd: (evt) => updateZoneOrder(evt, '{{ $zoneKey }}') }"
            class="min-h-[160px] flex-1 space-y-4 p-4 transition-colors"
          >
            @foreach ($zoneBlocks as $childId)
              @if (isset($allContent[$childId]))
                @php $childBlock = $allContent[$childId]; @endphp

                <!-- Wrapper Mikro Blok -->
                <div
                  id="block-wrapper-{{ $childId }}"
                  data-id="{{ $childId }}"
                  x-sort:item="'{{ $childId }}'"
                  wire:key="child-{{ $childId }}"
                  class="group relative rounded-xl transition-all"
                >
                  <!-- Drag Handle -->
                  <div
                    class="child-drag-handle hover:text-foresty absolute top-3 -left-3 z-10 cursor-move rounded-full border border-gray-200 bg-white p-1 text-gray-400 opacity-0 shadow-sm transition-opacity group-hover:opacity-100"
                  >
                    <x-dynamic-component
                      component="lucide-grip-vertical"
                      class="h-3.5 w-3.5"
                    />
                  </div>

                  <!-- Tombol Hapus -->
                  <div
                    class="absolute -top-2 -right-2 z-20 opacity-0 transition-opacity group-hover:opacity-100"
                  >
                    <button
                      type="button"
                      wire:click="removeNestedBlock('{{ $blockId }}', '{{ $zoneKey }}', '{{ $childId }}')"
                      class="cursor-pointer rounded-full border border-red-200 bg-white p-1.5 text-red-500 shadow-sm outline-none hover:bg-red-50 hover:text-red-600"
                      title="Hapus Blok Ini"
                    >
                      <x-dynamic-component
                        component="lucide-x"
                        class="h-3.5 w-3.5"
                        stroke-width="2.5"
                      />
                    </button>
                  </div>

                  <!-- 🌟 KUNCI PERBAIKAN: Meneruskan $activeLocales tanpa $code statis -->
                  <div class="p-0">
                    <x-dynamic-component
                      :component="'blocks.editor.' . str_replace('_', '-', $childBlock['type'])"
                      :block-id="$childId"
                      :block="$childBlock"
                      :all-content="$allContent"
                      :active-locales="$activeLocales"
                    />
                  </div>
                </div>
              @endif
            @endforeach
          </div>

          <!-- Menu Tambah Blok Anak -->
          <div class="mt-auto border-t border-gray-100 bg-white p-4">
            <div x-data="{ openDropdown: false }" class="relative">
              <button
                @click="openDropdown = !openDropdown"
                @click.outside="openDropdown = false"
                type="button"
                class="hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-gray-300 bg-gray-50 py-2.5 text-xs font-bold text-gray-500 transition-colors outline-none"
              >
                <x-dynamic-component
                  component="lucide-plus"
                  class="h-3.5 w-3.5"
                />
                Tambah Blok ke Kolom {{ $i }}
              </button>

              <div
                x-show="openDropdown"
                x-cloak
                class="absolute right-0 bottom-full left-0 z-50 mb-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
              >
                <div
                  class="grid max-h-[300px] scrollbar-thin grid-cols-2 gap-1 overflow-y-auto p-1.5"
                >
                  @php
                    $availableBlocks = [
                      ["type" => "heading", "icon" => "heading-1", "label" => "Judul"],
                      ["type" => "paragraph", "icon" => "align-left", "label" => "Paragraf"],
                      ["type" => "eyebrow", "icon" => "minus", "label" => "Eyebrow"],
                      ["type" => "image", "icon" => "image", "label" => "Gambar"],
                      [
                        "type" => "button-group",
                        "icon" => "mouse-pointer-click",
                        "label" => "Tombol",
                      ],
                      ["type" => "badge-group", "icon" => "tag", "label" => "Lencana"],
                      ["type" => "stats-group", "icon" => "bar-chart-2", "label" => "Statistik"],
                      ["type" => "card-group", "icon" => "layout-grid", "label" => "Kartu"],
                      [
                        "type" => "testimonial-group",
                        "icon" => "message-square-quote",
                        "label" => "Testimoni",
                      ],
                    ];
                  @endphp
                  @foreach ($availableBlocks as $b)
                    <button
                      type="button"
                      wire:click="addChildBlock('{{ $blockId }}', '{{ $zoneKey }}', '{{ $b['type'] }}'); openDropdown = false"
                      class="hover:bg-sage-soft hover:text-foresty flex cursor-pointer items-center gap-2 rounded-md px-2.5 py-2 text-left text-[11px] font-semibold text-gray-600 transition outline-none"
                    >
                      <x-dynamic-component
                        component="lucide-{{ $b['icon'] }}"
                        class="h-3.5 w-3.5 shrink-0"
                      />
                      <span class="truncate">{{ $b["label"] }}</span>
                    </button>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      @endfor
    </div>
  </div>
</x-blocks.editor.wrapper>
