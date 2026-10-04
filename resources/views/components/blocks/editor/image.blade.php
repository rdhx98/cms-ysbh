@props ([
  "blockId",
  "block",
  "activeLocales" => [],
  "allContent" => [],
])

@php
  $imageUrl = $block["data"]["url"] ?? "";

  // Konfigurasi Pilihan Padding & Ukuran
  $paddingTopOpts = [
    ["label" => "0", "value" => "pt-0"],
    ["label" => "S", "value" => "pt-4"],
    ["label" => "M", "value" => "pt-8"],
    ["label" => "L", "value" => "pt-16"],
    ["label" => "XL", "value" => "pt-24"],
  ];
  $paddingBottomOpts = [
    ["label" => "0", "value" => "pb-0"],
    ["label" => "S", "value" => "pb-4"],
    ["label" => "M", "value" => "pb-8"],
    ["label" => "L", "value" => "pb-16"],
    ["label" => "XL", "value" => "pb-24"],
  ];
  $sizeOpts = [
    ["label" => "Kecil", "value" => "sm"],
    ["label" => "Sedang", "value" => "md"],
    ["label" => "Besar", "value" => "lg"],
    ["label" => "Penuh", "value" => "full"],
  ];
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
        component="lucide-image"
        class="h-3.5 w-3.5"
        stroke-width="2.5"
      />
    </div>
    Gambar
  </x-slot:title>

  <!-- ========================================== -->
  <!-- 2. CUPLIKAN TEKS COLLAPSE (Tengah Atas)    -->
  <!-- ========================================== -->
  <x-slot:snippet>
    <span class="block w-full truncate text-right text-gray-400 sm:text-left">
      {{
        $imageUrl
          ? "Gambar Terpilih (" . ($block["data"]["size"] ?? "full") . ")"
          : "Belum Ada Gambar..."
      }}
    </span>
  </x-slot:snippet>

  {{-- SLOT AREA --}}
  <div class="m-2 flex flex-col gap-4">
    <!-- ========================================== -->
    <!-- 3. PENGATURAN GLOBAL (Padding & Ukuran)    -->
    <!-- ========================================== -->
    <div
      class="flex flex-col gap-4 rounded-xl border border-gray-100 bg-gray-50/80 p-4 shadow-inner lg:flex-row lg:items-center lg:justify-between"
    >
      <!-- PENGATURAN PADDING -->
      <div class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
          <x-dynamic-component
            component="lucide-move-vertical"
            class="h-4 w-4 text-gray-400"
          />
          <span
            class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
            >Padding:</span
          >
        </div>

        <div class="flex flex-wrap items-center gap-4">
          <!-- Grup Tombol Padding Atas -->
          <div class="flex items-center gap-2">
            <label
              class="text-[10px] font-bold tracking-wide text-gray-400 uppercase"
              >Atas</label
            >
            <div
              class="flex items-center rounded-lg border border-gray-200 bg-white p-0.5 shadow-sm"
            >
              @foreach ($paddingTopOpts as $opt)
                <button
                  type="button"
                  @click="$wire.set('content.{{ $blockId }}.data.padding_top', '{{ $opt['value'] }}')"
                  class="rounded-md px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
                  :class="($wire.content['{{ $blockId }}']?.data?.padding_top ?? 'pt-0') === '{{ $opt['value'] }}' ? 'bg-foresty text-white shadow' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
                >
                  {{ $opt["label"] }}
                </button>
              @endforeach
            </div>
          </div>

          <!-- Grup Tombol Padding Bawah -->
          <div class="flex items-center gap-2">
            <label
              class="text-[10px] font-bold tracking-wide text-gray-400 uppercase"
              >Bawah</label
            >
            <div
              class="flex items-center rounded-lg border border-gray-200 bg-white p-0.5 shadow-sm"
            >
              @foreach ($paddingBottomOpts as $opt)
                <button
                  type="button"
                  @click="$wire.set('content.{{ $blockId }}.data.padding_bottom', '{{ $opt['value'] }}')"
                  class="rounded-md px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
                  :class="($wire.content['{{ $blockId }}']?.data?.padding_bottom ?? 'pb-0') === '{{ $opt['value'] }}' ? 'bg-foresty text-white shadow' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
                >
                  {{ $opt["label"] }}
                </button>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      <!-- Garis Pemisah untuk Mobile -->
      <div class="h-px w-full bg-gray-200 lg:hidden"></div>

      <!-- PENGATURAN UKURAN -->
      <div
        class="flex items-center gap-3 lg:border-l lg:border-gray-200 lg:pl-4"
      >
        <div class="flex items-center gap-1.5">
          <x-dynamic-component
            component="lucide-maximize"
            class="h-3.5 w-3.5 text-gray-400"
          />
          <span
            class="text-[10px] font-bold tracking-wide text-gray-400 uppercase"
            >Ukuran:</span
          >
        </div>
        <div
          class="flex items-center rounded-lg border border-gray-200 bg-white p-0.5 shadow-sm"
        >
          @foreach ($sizeOpts as $opt)
            <button
              type="button"
              @click="$wire.set('content.{{ $blockId }}.data.size', '{{ $opt['value'] }}')"
              class="rounded-md px-3 py-1 text-[10px] font-bold transition-all outline-none"
              :class="($wire.content['{{ $blockId }}']?.data?.size ?? 'full') === '{{ $opt['value'] }}' ? 'bg-foresty text-white shadow' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
            >
              {{ $opt["label"] }}
            </button>
          @endforeach
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- 4. PEMILIHAN GAMBAR & ALT TEXT             -->
    <!-- ========================================== -->
    <div class="mb-6 grid gap-6 md:grid-cols-2">
      <!-- Kolom Kiri: Input Manual & Alt Text -->
      <div class="flex flex-col gap-4">
        <!-- Input URL -->
        <div>
          <label
            class="mb-1.5 flex items-center justify-between text-xs font-semibold text-gray-500 uppercase"
          >
            <span>URL Gambar</span>
            <span class="text-[10px] font-normal text-gray-400 normal-case"
              >(Eksternal)</span
            >
          </label>
          <input
            type="text"
            wire:model.live.debounce.500ms="content.{{ $blockId }}.data.url"
            x-on:input="$wire.set('content.{{ $blockId }}.data.media_id', null)"
            placeholder="https://..."
            class="focus:border-foresty focus:ring-foresty w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-600 shadow-inner transition-colors"
          />
        </div>

        <!-- 🌟 Input Alt Text Global (Tersinkron dengan File Manager) -->
        <div>
          <label
            class="mb-1.5 flex items-center justify-between text-xs font-semibold text-gray-500 uppercase"
          >
            <span>Teks Alternatif (Alt)</span>
            <x-dynamic-component
              component="lucide-info"
              class="h-3 w-3 text-gray-400"
              title="Deskripsi untuk SEO dan Tunanetra"
            />
          </label>
          <input
            type="text"
            wire:model.live.debounce.500ms="content.{{ $blockId }}.data.alt"
            placeholder="Mendeskripsikan isi gambar..."
            class="focus:border-foresty focus:ring-foresty w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-600 shadow-sm transition-colors"
          />
          <p class="mt-1.5 text-[10px] leading-tight text-gray-400">Otomatis terisi jika diatur di File Manager, namun dapat diubah di sini.</p>
        </div>
      </div>

      <!-- Kolom Kanan: File Manager -->
      <div
        class="hover:border-foresty/50 relative overflow-hidden rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 transition-colors hover:bg-gray-100/50"
      >
        @if (!empty($imageUrl))
          <div
            class="group relative flex h-full min-h-[144px] flex-col items-center justify-center p-4"
          >
            <img
              src="{{ $imageUrl }}"
              alt="Pratinjau Gambar"
              class="max-h-40 rounded object-contain shadow-sm transition-transform group-hover:scale-95"
            />

            <div
              class="absolute inset-0 flex items-center justify-center gap-3 bg-black/60 opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100"
            >
              <button
                type="button"
                x-on:click="$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: 'content.{{ $blockId }}.data' })"
                class="bg-foresty flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold text-white shadow-md transition-colors outline-none hover:bg-emerald-700"
              >
                <x-dynamic-component component="lucide-image" class="h-4 w-4" />
                Ganti
              </button>
              <button
                type="button"
                x-on:click="$wire.set('content.{{ $blockId }}.data.url', null); $wire.set('content.{{ $blockId }}.data.media_id', null); $wire.set('content.{{ $blockId }}.data.alt', null);"
                class="flex items-center gap-2 rounded-lg bg-red-500 px-4 py-2 text-xs font-bold text-white shadow-md transition-colors outline-none hover:bg-red-700"
              >
                <x-dynamic-component
                  component="lucide-trash-2"
                  class="h-4 w-4"
                />
                Hapus
              </button>
            </div>
          </div>
        @else
          <div
            class="flex h-full min-h-[144px] flex-col items-center justify-center p-4 text-center"
          >
            <x-dynamic-component
              component="lucide-folder-open"
              class="mb-3 h-10 w-10 text-gray-300"
            />
            <p class="mb-3 text-xs font-bold text-gray-500">Belum ada gambar terpilih</p>
            <button
              type="button"
              x-on:click="$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: 'content.{{ $blockId }}.data' })"
              class="text-foresty hover:border-foresty hover:bg-sage-soft flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-xs font-bold shadow-sm transition-colors outline-none"
            >
              <x-dynamic-component
                component="lucide-search"
                class="h-3.5 w-3.5"
              />
              Buka File Manager
            </button>
          </div>
        @endif
      </div>
    </div>

    <!-- ========================================== -->
    <!-- 5. AREA CAPTION MULTI-BAHASA               -->
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
          wire:key="image-caption-{{ $blockId }}-{{ $lang }}"
          x-show="effectiveLayout === 'single' ? singleActiveLang === '{{ $lang }}' : splitLanguages.includes('{{ $lang }}')"
          x-cloak
          class="flex flex-col gap-2 border-t border-gray-100 pt-4"
        >
          <div class="flex items-center justify-between">
            <label class="text-forest text-xs font-semibold uppercase"
              >Keterangan Gambar</label
            >
            <span
              class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase shadow-sm"
              >{{ $lang }}</span
            >
          </div>

          <input
            type="text"
            wire:model.live.debounce.300ms="content.{{ $blockId }}.data.caption.{{ $lang }}"
            placeholder="Tulis caption gambar di sini (opsional)..."
            class="focus:border-forest w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-700 shadow-sm transition-colors focus:ring-0"
          />
        </div>
      @endforeach
    </div>
  </div>
</x-blocks.editor.wrapper>
