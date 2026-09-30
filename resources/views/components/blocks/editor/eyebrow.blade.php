@props (["blockId", "code", "block" => [], "allContent" => []])

@php
  $iconsList = [
    "newspaper",
    "bookmark",
    "sparkles",
    "tag",
    "folder",
    "flag",
    "globe",
    "heart",
    "star",
    "shield",
    "award",
    "bell",
    "briefcase",
    "calendar",
    "check-circle",
    "compass",
    "cpu",
    "file-text",
    "filter",
    "gift",
    "home",
    "info",
    "layers",
    "life-buoy",
    "lightbulb",
    "link",
    "lock",
    "map",
    "megaphone",
    "message-square",
    "mic",
    "moon",
    "package",
    "paperclip",
    "pen-tool",
    "pie-chart",
    "play",
    "power",
    "radio",
    "rss",
    "search",
    "send",
    "settings",
    "share-2",
    "shield-check",
    "shopping-bag",
    "shopping-cart",
    "sliders",
    "smile",
    "speaker",
    "sun",
    "target",
    "terminal",
    "thumbs-up",
    "wrench",
    "trash-2",
    "trending-up",
    "triangle",
    "truck",
    "tv",
    "user",
    "users",
    "video",
    "volume-2",
    "watch",
    "zap",
  ];

  $colorsList = [
    ["name" => "Coral Dark", "value" => "#e05a47"],
    ["name" => "Forest Green", "value" => "#064f3b"],
    ["name" => "Sage Muted", "value" => "#4b5d53"],
    ["name" => "Charcoal", "value" => "#1f2937"],
    ["name" => "Ocean Blue", "value" => "#0369a1"],
    ["name" => "Amber", "value" => "#d97706"],
    ["name" => "Purple", "value" => "#7c3aed"],
    ["name" => "Rose", "value" => "#e11d48"],
  ];
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

  <!-- ========================================== -->
  <!-- 1. AREA PENGATURAN (PICKER)                -->
  <!-- ========================================== -->
  <div class="flex flex-wrap gap-6">
    <!-- 🌟 ICON PICKER GLOBAL (Berbasis Modal Teleport - Anti Terpotong) -->
    <div x-data="{ openPicker: false, searchQuery: '' }">
      <label class="mb-2 block text-xs font-semibold text-gray-500 uppercase"
        >Ikon Global</label
      >

      <!-- Tombol Pemanggil Modal -->
      <button
        type="button"
        @click="openPicker = true"
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
            @click.away="openPicker = false"
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
                  @click="openPicker = false"
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
                  x-show="'{{ $icon }}'.includes(searchQuery.toLowerCase())"
                  @click="$wire.set('content.{{ $blockId }}.data.icon', '{{$icon }}'); openPicker = false; searchQuery = ''"
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
    <div>
      <label class="mb-2 block text-xs font-semibold text-gray-500 uppercase"
        >Warna Global</label
      >
      <div class="flex flex-wrap items-center gap-2.5 pt-1">
        @foreach ($colorsList as $color)
          <button
            type="button"
            @click="$wire.set('content.{{ $blockId }}.data.color', '{{$color['value'] }}')"
            class="h-7 w-7 rounded-full shadow-sm transition-all hover:scale-110 hover:shadow-md focus:outline-none"
            :class="($wire.content['{{ $blockId }}']?.data?.color ?? '#e05a47') === '{{ $color['value'] }}' ? 'ring-2 ring-forest ring-offset-2 scale-110' : 'ring-1 ring-gray-200/50'"
            style="background-color: {{ $color['value'] }}"
            title="{{ $color['name'] }}"
          ></button>
        @endforeach
      </div>
    </div>
  </div>

  <div class="h-6"></div>

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
        <div class="flex items-center gap-2">
          <span
            class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase shadow-sm"
            >{{ $lang }}</span
          >
          <span class="text-[10px] font-semibold text-gray-400"
            >Teks Judul</span
          >
        </div>
        <div class="md:col-span-2">
          <label class="text-forest mb-1 block text-xs font-semibold uppercase"
            >Teks Eyebrow ({{ strtoupper($lang) }})</label
          >
          <input
            type="text"
            wire:model.live.debounce.300ms="content.{{ $blockId }}.data.text.{{$lang }}"
            placeholder="Contoh: ARTIKEL & CERITA LAPANGAN"
            class="focus:border-forest w-full rounded-lg border border-zinc-200 bg-white px-4 py-2.5 text-xs text-zinc-700 shadow-sm transition-colors focus:ring-0"
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
