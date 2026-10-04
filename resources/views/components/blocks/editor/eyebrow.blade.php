@props ([
  "blockId",
  "code",
  "block" => [],
  "allContent" => [],
  "iconsList" => [],
  "marginBottom" => [],
])

@php
  // $iconsList = config("cms.lucide", []);

  $colorsList = config("cms.design.eyebrow_colors", []);
  // $marginsList = config("cms.design.margin_bottom", []);
@endphp

<x-blocks.editor.wrapper :block-id="$blockId" :block="$block">
  <x-slot:title>
    <div
      class="bg-sage-soft text-foresty flex h-5 w-5 items-center justify-center rounded-sm shadow-sm"
    >
      <x-dynamic-component
        component="lucide-bookmark"
        class="h-3.5 w-3.5"
        stroke-width="2.5"
      />
    </div>
    Alis mata (Eyebrow)
  </x-slot:title>

  <x-slot:snippet>
    <span
      class="block w-full truncate text-right sm:text-left"
      x-text="(() => { let t = ($wire.content['{{$blockId }}']?.data?.text?.['{{ app()->getLocale() }}'] ?? '').replace(/<[^>]*>?/gm, '').trim(); return t !== '' ? t : 'Kosong...'; })()"
    ></span>
  </x-slot:snippet>

  <x-slot:settings>
    <div
      x-show="isCollapsed"
      x-cloak
      class="flex items-center gap-2"
      {{-- x-effect="updateIcon()" --}}
      x-data="{
      colorList: @js($colorsList),
      marginList: @js($marginBottom),
        // Fungsi reaktif untuk mencari nama berdasarkan Hex yang sedang aktif
        get activeColorName() {
          let currentHex = $wire.content?.['{{ $blockId }}']?.data?.color || '#e05a47';
          let foundColor = this.colorList.find(c => c.value.toLowerCase() === currentHex.toLowerCase());
          
          return foundColor ? foundColor.name : 'Warna Kustom';
        },
        get activeHex() {
          return $wire.content?.['{{ $blockId }}']?.data?.color || '#e05a47';
        },
        get activeMarginName() {
          let currentMargin = $wire.content?.['{{ $blockId }}']?.data?.margin_bottom || 'mb-4';
          let foundMargin = this.marginList.find(m => m.value === currentMargin);
          return foundMargin ? (foundMargin.name || foundMargin.label) : 'Normal';
        },
      }"
    >
      <!-- icon stat -->
      <div class="flex h-6 items-center gap-2">
        <span
          class="text-xxs font-bold text-gray-700 uppercase"
          x-text="($wire.content['{{ $blockId }}']?.data?.icon || 'newspaper').replace(/-/g, ' ')"
        ></span>
        <span
          class="bg-forest text-aurum flex h-5 w-5 items-center justify-center rounded-md p-3 shadow-inner"
        >
          <svg class="h-4 w-4 shrink-0" stroke-width="2.5">
            <use
              :href="'#icon-' + ($wire.content['{{ $blockId }}']?.data?.icon || 'newspaper')"
            ></use>
          </svg>
        </span>
      </div>

      <div class="mx-1 h-6 w-px bg-gray-300"></div>

      <!-- color stat -->
      <span
        x-text="activeColorName"
        class="text-xxs font-bold text-gray-700 uppercase"
      ></span>
      <div
        class="ring-forest h-4 w-4 rounded-sm shadow-sm ring-2 ring-offset-2 transition-all focus:outline-none"
        x-bind:style="{ backgroundColor: activeHex || '#e05a47' }"
      ></div>

      <div class="mx-1 h-6 w-px bg-gray-300"></div>

      <!-- margin stat -->
      <div class="flex items-center gap-1.5" title="Jarak Bawah">
        <span
          class="text-xxs font-bold text-gray-700 uppercase"
          x-text="activeMarginName"
        ></span>
        <span
          class="bg-forest text-aurum flex h-5 w-5 items-center justify-center rounded-md p-3 shadow-inner"
        >
          <svg class="h-4 w-4 shrink-0" stroke-width="2.5">
            <use :href="'#icon-gap-vertical'"></use>
          </svg>
        </span>
      </div>
      <div class="mx-1 h-6 w-px bg-gray-300"></div>
    </div>
  </x-slot:settings>

  <!-- CONTROLS-->
  <div
    class="m-2 flex flex-wrap items-start justify-start gap-4 rounded-lg border border-gray-100 bg-gray-50/50 p-3 shadow-inner"
  >
    <!-- 🌟 ICON PICKER GLOBAL (Berbasis Modal Teleport - Anti Terpotong) -->
    <div
      class="flex flex-col gap-1.5"
      x-data="{ openPicker: false, searchQuery: '' }"
    >
      <label class="text-xxs font-bold text-gray-700 uppercase"
        >Ikon Global</label
      >

      <!-- Tombol Pemanggil Modal -->
      <button
        type="button"
        x-on:click="openPicker = true"
        class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm text-zinc-700 shadow-sm transition-colors hover:bg-zinc-50 focus:outline-none"
      >
        <div class="flex items-center gap-3 truncate">
          <span
            class="bg-sage-soft text-foresty flex h-6 w-6 shrink-0 items-center justify-center rounded shadow-inner"
          >
            @foreach ($iconsList as $icon)
              <span
                x-show="($wire.content['{{ $blockId }}']?.data?.icon ?? 'newspaper') === '{{ $icon }}'"
                x-cloak
                style="display: none"
              >
                <x-dynamic-component
                  :component="'lucide-' . $icon"
                  class="h-4 w-4"
                />
              </span>
            @endforeach
          </span>
          <span
            class="truncate font-mono text-[11px] font-bold uppercase"
            x-text="$wire.content['{{$blockId }}']?.data?.icon ?? 'newspaper'"
          ></span>
        </div>
        <x-dynamic-component
          component="lucide-search"
          class="h-4 w-4 shrink-0 text-gray-400"
        />
      </button>

      <!-- Jendela Modal Ikon (Dilempar ke luar editor menggunakan x-teleport) -->
      <template x-teleport="body">
        <div
          x-show="openPicker"
          x-cloak
          class="fixed inset-0 z-[10000] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
        >
          <div
            x-on:click.away="openPicker = false"
            x-transition
            x-show="openPicker"
            class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
          >
            <!-- Header Modal & Pencarian -->
            <div class="border-b border-gray-100 bg-gray-50/80 p-5">
              <div class="mb-4 flex items-center justify-between">
                <h3
                  class="text-sm font-extrabold tracking-widest text-gray-800 uppercase"
                >
                  Pilih Ikon Eyebrow
                </h3>
                <button
                  x-on:click="openPicker = false"
                  class="rounded-full bg-gray-200 p-1 text-gray-500 transition-colors outline-none hover:bg-red-100 hover:text-red-500"
                >
                  <x-dynamic-component component="lucide-x" class="h-4 w-4" />
                </button>
              </div>
              <input
                type="text"
                x-model="searchQuery"
                placeholder="Cari nama ikon..."
                class="focus:border-forest w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm shadow-sm focus:ring-0"
              />
            </div>

            <!-- Grid Daftar Ikon -->
            <div
              class="grid scrollbar-thin grid-cols-5 gap-2 overflow-y-auto p-5 sm:grid-cols-7"
            >
              @foreach ($iconsList as $icon)
                <button
                  type="button"
                  data-lucide-source="{{ $icon }}"
                  x-show="'{{ $icon }}'.includes(searchQuery.toLowerCase())"
                  x-on:click="$wire.set('content.{{ $blockId }}.data.icon', '{{$icon }}'); openPicker = false; searchQuery = ''"
                  class="hover:bg-sage-soft flex aspect-square items-center justify-center rounded-xl transition-all outline-none hover:scale-110"
                  :class="($wire.content['{{ $blockId }}']?.data?.icon ?? 'newspaper') === '{{ $icon }}' ? 'bg-forest text-goldy shadow-md ring-2 ring-forest ring-offset-1' : 'bg-zinc-50 text-forest'"
                  title="{{ $icon }}"
                >
                  <x-dynamic-component
                    :component="'lucide-' . $icon"
                    class="h-5 w-5 shrink-0"
                  />
                </button>
              @endforeach
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- 🌟 COLOR PICKER GLOBAL (Grup Tombol Warna) -->
    <div class="flex flex-col gap-1.5">
      <label class="text-xxs font-bold text-gray-700 uppercase">Warna</label>
      <div class="flex flex-wrap gap-3 pb-6">
        @foreach ($colorsList as $color)
          <!-- 1. Gunakan 'group/btn' alih-alih 'group' biasa -->
          <div class="group/btn relative flex flex-col items-center">
            <button
              type="button"
              x-on:click="$wire.set('content.{{ $blockId }}.data.color', '{{ $color['value'] }}')"
              class="border border-gray-200 hover:ring-forest {{ $color['value'] }} h-6 w-6 rounded-md shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none"
              x-bind:class="($wire.content['{{ $blockId }}']?.data?.color ?? '#e05a47').toLowerCase() === '{{ strtolower($color['value']) }}' ? 
          'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
              style="background-color: {{ $color['value'] }}"
            ></button>

            <!-- 2. Ubah 'group-hover' menjadi 'group-hover/btn' -->
            <span
              class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
              x-bind:class="($wire.content['{{ $blockId }}']?.data?.color ?? '#e05a47').toLowerCase() === '{{ strtolower($color['value']) }}' ? 
          'opacity-100 translate-y-0' : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
            >
              {{ $color["name"] }}
            </span>
          </div>
        @endforeach
      </div>
    </div>

    <!-- margin bottom control -->
    {{-- <div
      class="flex flex-col"
      x-data="{ localMargin: $wire.entangle('content.{{ $blockId }}.data.margin_bottom').live || 'mb-8' }"
    >
      <label class="mb-2 block text-xs font-semibold text-gray-500 uppercase"
        >Jarak Bawah</label
      >
      <div
        class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
      >
        @php
          // Mapping ukuran miniatur ikon (dalam pixel) murni untuk visualisasi.
          // Menjamin ikon tidak hilang/tergencet karena melebihi tinggi kotak h-4 (16px).
          $miniMargins = [
            "mb-0" => "0px",
            "mb-4" => "2px",
            "mb-8" => "4px",
            "mb-16" => "6px",
            "mb-24" => "8px",
          ];
        @endphp

        @foreach ($marginBottom as $margin)
          <button
            type="button"
            x-on:click="localMargin = '{{ $margin['value'] }}'"
            class="group flex flex-col items-center gap-0.75 rounded px-1.5 py-1 transition-all outline-none"
            :class="localMargin === '{{ $margin['value'] }}' ? 'bg-white shadow-sm text-foresty' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
            title="{{ $margin['name'] ?? $margin['label'] }}"
          >
            <!-- Representasi Visual Margin -->
            <div class="flex h-3 w-3 flex-col justify-end">
              <div
                class="w-full flex-1 rounded-[1px] bg-current opacity-80 transition-all"
                <!-- 🌟 Solusi: Gunakan inline style agar tidak terkena Purge Tailwind -->
                style="margin-bottom: {{ $miniMargins[$margin['value']] ?? '4px' }};"
              ></div>
              <div
                class="h-[2px] w-full rounded-full transition-colors"
                :class="localMargin === '{{ $margin['value'] }}' ? 'bg-foresty/50' : 'bg-gray-400/70'"
              ></div>
            </div>

            <!-- Label Teks -->
            <span class="text-xxs font-bold uppercase">{{
              $margin["name"] ??
                $margin["label"]
            }}</span>
          </button>
        @endforeach
      </div>
    </div> --}}
    <!-- MARGIN CONTROL -->
    <div
      class="flex flex-col gap-1.5"
      x-data="{ localMargin: $wire.entangle('content.{{ $blockId }}.data.margin_bottom').live || 'mb-4' }"
    >
      <label class="text-xxs font-bold text-gray-700 uppercase"
        >Jarak Bawah</label
      >
      <div
        class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
      >
        @php
          // Mapping ukuran miniatur ikon (dalam pixel) murni untuk visualisasi.
          // Menjamin ikon tidak hilang/tergencet karena melebihi tinggi kotak h-4 (16px).
          $miniMargins = [
            "mb-0" => "0px",
            "mb-4" => "2px",
            "mb-8" => "4px",
            "mb-16" => "6px",
            "mb-24" => "8px",
          ];
        @endphp

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
                style="margin-bottom: {{ $miniMargins[$margin['value']] ?? '4px' }};"
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

  <!-- ========================================== -->
  <!-- 2. AREA INPUT MULTI-BAHASA                 -->
  <!-- ========================================== -->
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
        wire:key="eyebrow-input-{{ $blockId }}-{{$lang }}"
        x-show="effectiveLayout === 'single' ? singleActiveLang === '{{ $lang }}' : splitLanguages.includes('{{$lang }}')"
        x-cloak
        class="flex flex-col gap-2"
      >
        <div class="flex flex-col items-center gap-2">
          <div class="flex w-full items-center justify-start gap-2">
            <span
              class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-xs font-bold tracking-wider uppercase shadow-sm"
              >{{ $lang }}</span
            >
            <span class="text-xs font-semibold text-gray-400 uppercase"
              >Teks</span
            >
          </div>
          <input
            type="text"
            wire:model.live.debounce.300ms="content.{{ $blockId }}.data.text.{{$lang }}"
            placeholder="Contoh: ARTIKEL & CERITA LAPANGAN"
            class="focus:border-forest w-full rounded-lg border border-zinc-200 bg-white px-4 py-2 text-xs text-zinc-700 shadow-sm transition-colors focus:ring-0"
          />
        </div>
      </div>
    @endforeach
  </div>

  <!-- ========================================== -->
  <!-- 3. AREA PREVIEW BAWAH                      -->
  <!-- ========================================== -->
  <x-slot:preview>
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
          wire:key="eyebrow-preview-{{ $blockId }}-{{$lang }}"
          x-show="effectiveLayout === 'single' ? singleActiveLang === '{{ $lang }}' : splitLanguages.includes('{{$lang }}')"
          x-cloak
          class="border-t border-dashed border-gray-200 pt-3"
        >
          <span class="mb-2 block text-xs font-semibold text-gray-400 uppercase"
            >Pratinjau ({{ strtoupper($lang) }}):</span
          >

          <div
            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-2xs"
          >
            <span
              class="inline-flex items-center gap-2.5 font-['Instrument_Sans',sans-serif] text-[11px] font-bold tracking-[0.16em] uppercase transition-colors md:text-[13px]"
              :style="`color: ${$wire.content['{{ $blockId }}']?.data?.color ?? '#e05a47'}`"
            >
              <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                @foreach ($iconsList as $icon)
                  <span
                    x-show="($wire.content['{{ $blockId }}']?.data?.icon ?? 'newspaper') === '{{ $icon }}'"
                    x-cloak
                    style="display: none"
                  >
                    <x-dynamic-component
                      :component="'lucide-' . $icon"
                      class="h-4 w-4 shrink-0"
                      stroke-width="2"
                    />
                  </span>
                @endforeach
              </span>

              <span
                x-text="$wire.content['{{ $blockId }}']?.data?.text?.['{{ $lang }}'] ?? 'TULIS TEKS EYEBROW DI ATAS...'"
              ></span>
            </span>
          </div>
        </div>
      @endforeach
    </div>
  </x-slot:preview>
</x-blocks.editor.wrapper>
