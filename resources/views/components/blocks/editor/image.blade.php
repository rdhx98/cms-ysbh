@props ([
  "blockId",
  "block",
  "activeLocales" => [],
  "allContent" => [],
])

@php
  $imageUrl = $block["data"]["url"] ?? "";
  $imageWidth = [
    [
      "label" => "standard",
      "value" => "max-w-4xl",
      "preview" => "w-1/2 rounded-[1px]",
    ],
    ["label" => "full", "value" => "w-full", "preview" => "w-5/6 rounded-[1px]"],
    ["label" => "banner", "value" => "w-screen", "preview" => "w-full"],
    // [''=>''],
  ];
  $imageMaxHeight = [
    [
      "label" => "original",
      "value" => "max-h-none",
      "preview" => "h-full", // Memenuhi ruang vertikal secara penuh
    ],
    [
      "label" => "compact",
      "value" => "max-h-96",
      "preview" => "h-[35%]", // Paling pendek, merepresentasikan tinggi statis 384px
    ],
    [
      "label" => "medium",
      "value" => "max-h-[50vh]",
      "preview" => "h-1/2", // Persis setengah layar
    ],
    [
      "label" => "tall",
      "value" => "max-h-[70vh]",
      "preview" => "h-[70%]", // Terlihat menyisakan 30% ruang kosong
    ],
  ];
  $imageFit = [
    [
      "label" => "Cover",
      "value" => "object-cover",
      // Lingkaran besar melebihi kotak (akan terpotong tepi kotak)
      "preview" => "h-6 w-6 shrink-0 rounded-full",
      "desc" => "Memenuhi layar, gambar dipotong jika rasio berbeda.",
    ],
    [
      "label" => "Contain",
      "value" => "object-contain",
      // Lingkaran kecil yang muat aman di dalam kotak
      "preview" => "h-3 w-3 shrink-0 rounded-full",
      "desc" => "Tampil utuh, menyisakan ruang kosong (letterbox).",
    ],
    [
      "label" => "Fill",
      "value" => "object-fill",
      // Dipaksa memenuhi kotak (berubah menjadi elips gepeng)
      "preview" => "h-full w-full rounded-[50%]",
      "desc" => "Ditarik paksa untuk memenuhi kotak (bisa gepeng).",
    ],
  ];
  $captionGaps = [
    [
      "label" => "Tight",
      "value" => "gap-1",
      "preview" => "gap-[1px]", // Jarak sangat tipis di miniatur
    ],
    [
      "label" => "Normal",
      "value" => "gap-2",
      "preview" => "gap-[3px]", // Jarak sedang
    ],
    [
      "label" => "Wide",
      "value" => "gap-3",
      "preview" => "gap-[6px]", // Jarak lega
    ],
  ];
  $imageAlignment = [
    [
      "label" => "Kiri",
      "value" => "mr-auto",
      "icon" => "align-left", // Ikon rata kiri
    ],
    [
      "label" => "Tengah",
      "value" => "mx-auto",
      "icon" => "align-center", // Ikon rata tengah
    ],
    [
      "label" => "Kanan",
      "value" => "ml-auto",
      "icon" => "align-right", // Ikon rata kanan
    ],
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
    {{-- CARD BUILDER CONTROL ? --}}
    <div
      class="flex flex-wrap items-start gap-4 rounded-xl border border-gray-100 bg-gray-50/80 p-4 shadow-inner"
    >
      <!-- MARGIN CONTROL -->
      <div
        class="flex flex-col gap-1.5"
        x-data="{ localMargin: $wire.entangle('content.{{ $blockId }}.data.margin_bottom').live || 'mb-4 md:mb-6' }"
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
              class="group text-xxs flex items-center gap-1 rounded px-1.5 py-1 font-bold transition-all outline-none"
              {{-- class="group flex items-center gap-1 rounded px-1.5 py-1 transition-all outline-none" --}}
              x-bind:class="localMargin === '{{ $margin['value'] }}' ? 'bg-white shadow-sm text-forst' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
              title="{{ $margin['name'] ?? $margin['label'] }}"
            >
              <!-- Representasi Visual Margin -->
              <div class="flex h-4 w-4 flex-col justify-end">
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

      <!-- WIDTH IMAGE -->
      <div
        class="flex flex-col gap-1.5"
        x-data="{ localWidth: $wire.entangle('content.{{ $blockId }}.data.width').live || 'w-full' }"
      >
        <label class="text-xxs font-bold text-gray-700 uppercase"
          >Lebar gambar</label
        >
        <div
          class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
        >
          @foreach ($imageWidth as $item)
            <button
              type="button"
              x-on:click="localWidth = '{{ $item['value'] }}'"
              class="group text-xxs flex items-center gap-1 rounded px-1.5 py-1 font-bold transition-all outline-none"
              x-bind:class="localWidth === '{{ $item['value'] }}' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700'"
              title="Lebar: {{ $item['label'] }}"
            >
              <!-- 🌟 Representasi Visual Lebar -->
              {{-- Kotak luar: Anggap ini sebagai layar monitor / browser --}}
              <div
                class="flex h-4 w-6 items-center justify-center overflow-hidden rounded-[3px] border border-current"
              >
                {{-- Kotak dalam: Anggap ini gambar, lebarnya diatur dinamis oleh $item['preview'] --}}
                <div
                  class="h-2.5 transition-all duration-300 {{ $item['preview'] }}"
                  x-bind:class="localWidth === '{{ $item['value'] }}' ? 'bg-foresty' : 'bg-current opacity-40 group-hover:opacity-70'"
                ></div>
              </div>

              <!-- Label -->
              <span
                class="text-xxs font-bold tracking-tight uppercase"
                >{{ $item["label"] }}</span
              >
            </button>
          @endforeach
        </div>
      </div>

      <!-- MAX HEIGHT -->
      <div
        class="flex flex-col gap-1.5"
        x-data="{ localHeight: $wire.entangle('content.{{ $blockId }}.data.max_height').live || 'max-h-none' }"
      >
        <label class="text-xxs font-bold text-gray-700 uppercase"
          >Tinggi Maksimal</label
        >
        <div
          class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
        >
          @foreach ($imageMaxHeight as $item)
            <button
              type="button"
              x-on:click="localHeight = '{{ $item['value'] }}'"
              class="group text-xxs flex items-center gap-1 rounded px-1.5 py-1 font-bold transition-all outline-none"
              x-bind:class="localHeight === '{{ $item['value'] }}' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700'"
              title="Tinggi: {{ $item['label'] }}"
            >
              <!-- 🌟 Representasi Visual Tinggi (Vertical) -->
              {{-- Kotak luar: Proporsi meninggi (h-5 w-3.5) seperti layar HP --}}
              <div
                class="flex h-4 w-2.5 items-center justify-center overflow-hidden rounded-[2px] border border-current"
              >
                {{-- Kotak dalam: Tingginya diatur dinamis oleh $item['preview'] --}}
                <div
                  class="w-full rounded-[1px] transition-all duration-300 {{ $item['preview'] }}"
                  x-bind:class="localHeight === '{{ $item['value'] }}' ? 'bg-foresty' : 'bg-current opacity-40 group-hover:opacity-70'"
                ></div>
              </div>

              <!-- Label -->
              <span
                class="text-xxs font-bold tracking-tight uppercase"
                >{{ $item["label"] }}</span
              >
            </button>
          @endforeach
        </div>
      </div>

      <!--IMAGE FIT-->
      <div
        class="flex flex-col gap-1.5"
        x-data="{ localFit: $wire.entangle('content.{{ $blockId }}.data.object_fit').live || 'object-cover' }"
      >
        <span class="text-xxs font-bold text-gray-700 uppercase"
          >Perilaku Gambar</span
        >

        <div
          class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
        >
          @foreach ($imageFit as $item)
            <button
              type="button"
              x-on:click="localFit = '{{ $item['value'] }}'"
              class="group text-xxs flex items-center gap-1 rounded px-1.5 py-1 font-bold transition-all outline-none"
              x-bind:class="localFit === '{{ $item['value'] }}' ? 'bg-white text-forest shadow-sm' : 'text-gray-500 hover:text-gray-700'"
              title="{{ $item['label'] }}"
            >
              <!-- 🌟 Representasi Visual Kotak Letterbox -->
              {{-- Outer Frame: Diberi background abu-abu transparan agar ruang kosong (Contain) terlihat jelas --}}
              <div
                class="relative flex h-4 w-6 items-center justify-center overflow-hidden rounded-[2px] border transition-colors duration-300"
                x-bind:class="localFit === '{{ $item['value'] }}' ? 'border-forest bg-gray-100' : 'border-current bg-gray-300/30'"
              >
                @if ($item["value"] === "object-cover")
                  <!-- COVER: Memenuhi seluruh ruang tanpa sisa -->
                  <div
                    class="h-full w-full transition-colors duration-300"
                    x-bind:class="localFit === '{{ $item['value'] }}' ? 'bg-forest' : 'bg-current opacity-40 group-hover:opacity-70'"
                  ></div>

                @elseif ($item["value"] === "object-contain")
                  <!-- CONTAIN: Berada di tengah (Pillarbox), menyisakan background abu-abu frame -->
                  <div
                    class="h-full w-2.5 transition-colors duration-300"
                    x-bind:class="localFit === '{{ $item['value'] }}' ? 'bg-forest' : 'bg-current opacity-40 group-hover:opacity-70'"
                  ></div>

                @elseif ($item["value"] === "object-fill")
                  <!-- FILL: Memenuhi ruang, ditambah panah Horizontal Stretch -->
                  <div
                    class="flex h-full w-full items-center justify-center transition-colors duration-300"
                    x-bind:class="localFit === '{{ $item['value'] }}' ? 'bg-forest' : 'bg-current opacity-40 group-hover:opacity-70'"
                  >
                    <!-- Ikon panah (Stretch) murni SVG agar presisi ukurannya -->
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      class="h-2.5 w-2.5"
                      x-bind:class="localFit === '{{ $item['value'] }}' ? 'text-white' : 'text-paper'"
                    >
                      <path d="M5 12h14"></path>
                      <path d="m9 8-4 4 4 4"></path>
                      <path d="m15 8 4 4-4 4"></path>
                    </svg>
                  </div>
                @endif
              </div>

              <!-- Label Teks -->
              <span
                class="text-xxs font-bold tracking-tight uppercase"
                >{{ $item["label"] }}</span
              >
            </button>
          @endforeach
        </div>
      </div>

      <!--CAPTION GAP -->
      <div
        class="flex flex-col gap-1.5"
        x-data="{ localCaptionGap: $wire.entangle('content.{{ $blockId }}.data.space_y').live || 'gap-3' }"
        {{-- x-data="{ localCaptionGap: $wire.entangle('{{ $basePath }}.space_y').live || 'gap-3' }" --}}
      >
        <span class="text-xxs font-bold text-gray-700 uppercase"
          >Jarak dengan Caption</span
        >

        <div
          class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
        >
          @foreach ($captionGaps as $item)
            <button
              type="button"
              x-on:click="localCaptionGap = '{{ $item['value'] }}'"
              class="group text-xxs flex items-center gap-1 rounded px-1.5 py-1 font-bold transition-all outline-none"
              x-bind:class="localCaptionGap === '{{ $item['value'] }}' ? 'bg-white text-forest shadow-sm' : 'text-gray-500 hover:text-gray-700'"
              title="Jarak: {{ $item['label'] }}"
            >
              <!-- 🌟 Representasi Visual Gambar & Teks -->
              {{-- Pembungkus flex-col, kelas gap-nya diambil dinamis dari $item['preview'] --}}
              <div
                class="flex flex-col items-center justify-center h-4 w-4 {{ $item['preview'] }}"
              >
                {{-- Elemen Atas (Simulasi Gambar) --}}
                <div
                  class="h-2.5 w-full rounded-[1px] transition-colors duration-300"
                  x-bind:class="localCaptionGap === '{{ $item['value'] }}' ? 'bg-forest' : 'bg-current opacity-40 group-hover:opacity-70'"
                ></div>

                {{-- Elemen Bawah (Simulasi Teks Caption) --}}
                <div
                  class="h-[1.5px] w-2/3 rounded-full transition-colors duration-300"
                  x-bind:class="localCaptionGap === '{{ $item['value'] }}' ? 'bg-forest opacity-80' : 'bg-current opacity-30 group-hover:opacity-60'"
                ></div>
              </div>

              <!-- Label Teks -->
              <span
                class="text-xxs font-bold tracking-tight uppercase"
                >{{ $item["label"] }}</span
              >
            </button>
          @endforeach
        </div>
      </div>

      <!-- IMAGE ALIGNMENT  -->
      <div
        class="flex flex-col gap-1.5"
        x-data="{ localAlignment: $wire.entangle('content.{{ $blockId }}.data.align').live || 'mx-auto' }"
      >
        <span class="text-xxs font-bold text-gray-700 uppercase"
          >Perataan gambar</span
        >

        <div
          class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
        >
          @foreach ($imageAlignment as $item)
            <button
              type="button"
              x-on:click="localAlignment = '{{ $item['value'] }}'"
              class="group text-xxs flex items-center gap-1 rounded px-1.5 py-1 font-bold transition-all outline-none"
              x-bind:class="localAlignment === '{{ $item['value'] }}' ? 'bg-white text-forest shadow-sm' : 'text-gray-500 hover:text-gray-700'"
              title="Jarak: {{ $item['label'] }}"
            >
              <!-- 🌟 Ikon Lucide Alignment Standar -->
              <x-dynamic-component
                :component="'lucide-' . $item['icon']"
                class="h-4 w-4 transition-colors duration-300"
                x-bind:class="localAlignment === '{{ $item['value'] }}' ? 'text-forest' : 'text-gray-400 group-hover:text-gray-600'"
                stroke-width="2.5"
              />
              <!-- Label Teks -->
              {{-- <span
                class="text-xxs font-bold tracking-tight uppercase"
                >{{ $item["label"] }}</span 
                >
                --}}
            </button>
          @endforeach
        </div>
      </div>
      <!-- RADIUS  -->
      <div
        class="flex flex-col gap-1.5"
        x-data="{ localRadius: $wire.entangle('content.{{ $blockId }}.data.radius').live || 'rounded-none' }"
      >
        <span class="text-xxs font-bold text-gray-700 uppercase"
          >Raidus sudut</span
        >

        <div
          class="flex w-fit transform items-center gap-1 rounded-md bg-gray-200 p-0.75 shadow-inner transition-all duration-300"
        >
          @foreach ($borderRadius as $item)
            <button
              type="button"
              x-on:click="localRadius = '{{ $item['value'] }}'"
              class="group text-xxs flex items-center gap-1 rounded px-1.5 py-1 font-bold transition-all outline-none"
              x-bind:class="localRadius === '{{ $item['value'] }}' ? 'bg-white text-forest shadow-sm' : 'text-gray-500 hover:text-gray-700'"
              title="{{ $item['name'] }}"
            >
              <div
                class="border-forest h-3.5 w-3.5  border-t-2 border-l-2 {{ $item['preview'] }}"
              ></div>
            </button>
          @endforeach
        </div>
      </div>
    </div>
    <!-- ========================================== -->
    <!-- 3. PENGATURAN GLOBAL (Padding & Ukuran)    -->
    <!-- ========================================== -->
    <div
      class="flex flex-col gap-4 rounded-xl border border-gray-100 bg-gray-50/80 p-4 shadow-inner lg:flex-row lg:items-center lg:justify-between"
    >
      <!-- PENGATURAN PADDING -->
      {{-- <div class="flex flex-wrap items-center gap-4">
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
      </div> --}}

      <!-- Garis Pemisah untuk Mobile -->
      {{-- <div class="h-px w-full bg-gray-200 lg:hidden"></div> --}}

      <!-- PENGATURAN UKURAN -->
      {{-- <div
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
      </div> --}}
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
