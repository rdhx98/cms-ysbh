@props ([
  "blockId",
  "block",
  "activeLocales" => [],
  "allContent" => [],
  "marginBottom" => [],
])

<x-blocks.editor.wrapper :block-id="$blockId" :block="$block">
  <!-- 🌟 INJEKSI IDENTITAS KE HEADER KIRI -->
  <x-slot:title>
    <div
      class="bg-sage-soft text-foresty flex h-5 w-5 items-center justify-center rounded-sm shadow-sm"
    >
      <x-dynamic-component
        component="lucide-heading"
        class="h-3.5 w-3.5"
        stroke-width="2.5"
      />
    </div>

    Judul
  </x-slot:title>

  <!-- 🌟 INJEKSI CUPLIKAN TEKS KE TENGAH HEADER -->
  <x-slot:snippet>
    <span
      class="block w-full truncate text-right sm:text-left"
      {{-- Mengambil teks secara paten berdasarkan bahasa UI CMS yang sedang aktif --}}
      x-text="( $wire.content['{{ $blockId }}']?.data?.text?.['{{ app()->getLocale() }}'] || '' ).replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'"
      :title="( $wire.content['{{ $blockId }}']?.data?.text?.['{{ app()->getLocale() }}'] || '' ).replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'"
    ></span>
  </x-slot:snippet>

  <x-slot:settings>
    <div class="ml-2 h-6 w-px bg-gray-300"></div>
  </x-slot:settings>

  <!--CONTROLS -->
  <div
    class="flex flex-wrap items-start justify-start gap-4 rounded-lg border border-gray-100 bg-gray-50/50 p-3 shadow-inner"
  >
    <!-- HEADING CONTROL -->
    <div class="flex flex-col gap-1.5">
      <span class="text-xxs font-bold text-gray-700 uppercase"> Level: </span>

      <!-- 🌟 BUNGKUSAN GRUP TOMBOL (Segmented Control) -->
      <div class="flex items-center rounded-md bg-gray-200 p-0.75 shadow-inner">
        <!-- Tombol H1 -->
        <button
          type="button"
          x-on:click="$wire.set('content.{{ $blockId }}.data.level', 'h1')"
          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
          :class="($wire.content?.['{{ $blockId }}']?.data?.level ?? 'h2') === 'h1' ? 'bg-white text-forest shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        >
          H1
        </button>

        <!-- Tombol H2 (Diasumsikan sebagai Default/Bawaan) -->
        <button
          type="button"
          x-on:click="$wire.set('content.{{ $blockId }}.data.level', 'h2')"
          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
          :class="($wire.content?.['{{ $blockId }}']?.data?.level ?? 'h2') === 'h2' ? 'bg-white text-forest shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        >
          H2
        </button>

        <!-- Tombol H3 -->
        <button
          type="button"
          x-on:click="$wire.set('content.{{ $blockId }}.data.level', 'h3')"
          class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
          :class="($wire.content?.['{{ $blockId }}']?.data?.level ?? 'h2') === 'h3' ? 'bg-white text-forest shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        >
          H3
        </button>
      </div>
    </div>

    <!-- MARGIN CONTROL -->
    <div
      class="flex flex-col gap-1.5"
      x-data="{ localMargin: $wire.entangle('content.{{ $blockId }}.data.margin_bottom').live || 'mb-4 md:mb-6' }"
    >
      <label class="text-xxs font-bold text-gray-700 uppercase"
        >Jarak Bawah</label
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

  <!-- 🌟 AREA INPUT MULTI-BAHASA -->
  <div
    x-bind:class="{
      'grid grid-cols-1': effectiveLayout === 'single',
      'grid grid-cols-1 md:grid-cols-2':
        effectiveLayout === 'split' && splitLanguages.length === 2,
      'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3':
        effectiveLayout === 'split' && splitLanguages.length >= 3,
    }"
    style="gap: 1.5rem"
  >
    @foreach ($activeLocales as $lang)
      <div
        wire:key="heading-input-{{ $blockId }}-{{ $lang }}"
        {{-- 🌟 LOGIKA SAKTI: Cukup dengan 1 baris x-show ini, input akan otomatis bereaksi terhadap Toolbar --}}
        x-show="(effectiveLayout === 'single' && singleActiveLang === '{{ $lang }}') || (effectiveLayout === 'split' && splitLanguages.includes('{{ $lang }}'))"
        x-cloak
        class="flex flex-col gap-2"
      >
        <!-- Indikator Bahasa -->
        <div class="flex items-center gap-2">
          <span
            class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase shadow-sm"
          >
            {{ $lang }}
          </span>
          <span class="text-[10px] font-semibold text-gray-400"
            >Teks Judul</span
          >
        </div>

        <x-tiptap
          :block-type="$block['type']"
          wire:model="content.{{ $blockId }}.data.text.{{ $lang }}"
          placeholder="Tulis judul ({{ strtoupper($lang) }})..."
        />
      </div>
    @endforeach
  </div>
</x-blocks.editor.wrapper>
