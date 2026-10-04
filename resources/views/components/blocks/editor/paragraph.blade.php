@props ([
  "blockId",
  "code",
  "block",
  "allContent" => [] // Tambahkan fallback array kosong agar tidak error jika dipanggil di root
])

<x-blocks.editor.wrapper :block-id="$blockId" :block="$block">
  <x-slot:title>
    <div
      class="bg-sage-soft text-foresty flex h-5 w-5 items-center justify-center rounded-sm shadow-sm"
    >
      <x-dynamic-component
        component="lucide-text-align-start"
        class="h-3.5 w-3.5"
        stroke-width="2.5"
      />
    </div>

    Paragraf
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

  {{-- <x-slot:settings>
    <span class="text-xs font-bold tracking-wide text-gray-400 uppercase">
      Margin bawah
    </span>
    {{-- kontrol margin --}
    <div class="flex flex-col gap-1.5">
      <div
        class="flex w-fit items-center justify-center rounded-md bg-gray-200 p-0.75 shadow-inner"
      >
        <!-- 1. Jarak 0 (Menempel Bawah) -->
        <button
          type="button"
          title="0px (Menempel)"
          @click="$wire.set('content.{{ $blockId }}.data.margin', 'mb-0')"
          class="flex items-center justify-center gap-1.5 rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
          :class="($wire.content?.['{{ $blockId }}']?.data?.margin ?? 'mb-0') === 'mb-0' ? 'bg-forest text-goldy shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        >
          {{-- Ikon Visual Margin Bawah 0 --}
          <div class="flex h-3.5 w-3.5 flex-col">
            <div class="mb-0 w-full flex-1 rounded-[2px] bg-current"></div>
            <div class="h-[2px] w-full rounded-full bg-gray-400/70"></div>
          </div>
          <span>Nihil</span>
        </button>

        <!-- 2. Jarak Kecil (mb-4 / 16px) -->
        <button
          type="button"
          title="Kecil (16px)"
          @click="$wire.set('content.{{ $blockId }}.data.margin', 'mb-4')"
          class="flex items-center justify-center gap-1.5 rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
          :class="($wire.content?.['{{ $blockId }}']?.data?.margin ?? 'mb-4') === 'mb-4' ? 'bg-forest text-goldy shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        >
          {{-- Ikon Visual Margin Bawah Kecil --}
          <div class="flex h-3.5 w-3.5 flex-col">
            <div
              class="mb-[2px] w-full flex-1 rounded-[2px] bg-current opacity-90"
            ></div>
            <div class="h-[2px] w-full rounded-full bg-gray-400/70"></div>
          </div>
          <span>Kecil</span>
        </button>

        <!-- 3. Jarak Normal (mb-8 / 32px) - DEFAULT -->
        <button
          type="button"
          title="Normal (32px)"
          @click="$wire.set('content.{{ $blockId }}.data.margin', 'mb-8')"
          class="flex items-center justify-center gap-1.5 rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
          :class="($wire.content?.['{{ $blockId }}']?.data?.margin ?? 'mb-8') === 'mb-8' ? 'bg-forest text-goldy shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        >
          {{-- Ikon Visual Margin Bawah Normal --}
          <div class="flex h-3.5 w-3.5 flex-col">
            <div
              class="mb-1 w-full flex-1 rounded-[2px] bg-current opacity-90"
            ></div>
            <div class="h-[2px] w-full rounded-full bg-gray-400/70"></div>
          </div>
          <span>Normal</span>
        </button>

        <!-- 4. Jarak Lebar (mb-16 / 64px) -->
        <button
          type="button"
          title="Lebar (64px)"
          @click="$wire.set('content.{{ $blockId }}.data.margin', 'mb-16')"
          class="flex items-center justify-center gap-1.5 rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
          :class="($wire.content?.['{{ $blockId }}']?.data?.margin ?? 'mb-16') === 'mb-16' ? 'bg-forest text-goldy shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        >
          {{-- Ikon Visual Margin Bawah Lebar --}
          <div class="flex h-3.5 w-3.5 flex-col">
            <div
              class="mb-1.5 w-full flex-1 rounded-[2px] bg-current opacity-90"
            ></div>
            <div class="h-[2px] w-full rounded-full bg-gray-400/70"></div>
          </div>
          <span>Lebar</span>
        </button>

        <!-- 5. Jarak Sangat Lebar / Penuh (mb-24 / 96px) -->
        <button
          type="button"
          title="Maksimal (96px)"
          @click="$wire.set('content.{{ $blockId }}.data.margin', 'mb-24')"
          class="flex items-center justify-center gap-1.5 rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
          :class="($wire.content?.['{{ $blockId }}']?.data?.margin ?? 'mb-24') === 'mb-24' ? 'bg-forest text-goldy shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        >
          {{-- Ikon Visual Margin Bawah Sangat Lebar --}
          <div class="flex h-3.5 w-3.5 flex-col">
            <div
              class="mb-2 w-full flex-1 rounded-[2px] bg-current opacity-90"
            ></div>
            <div class="h-[2px] w-full rounded-full bg-gray-400/70"></div>
          </div>
          <span>Penuh</span>
        </button>
      </div>
    </div>
  </x-slot:settings> --}}

  <!-- CONTROLS -->
  <div
    class="m-2 flex flex-wrap items-start justify-start gap-4 rounded-lg border border-gray-100 bg-gray-50/50 p-3 shadow-inner"
  >
    <!-- PADDING CONTROL -->
    <div
      class="flex flex-col gap-1.5"
      x-data="{ localMargin: $wire.entangle('content.{{ $blockId }}.data.margin_bottom').live || 'mb-4' }"
      {{-- $wire.set('content.{{ $blockId }}.data.margin', 'mb-0') --}}
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
        wire:key="paragraf-input-{{ $blockId }}-{{ $lang }}"
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
          <span class="text-[10px] font-semibold text-gray-400">Paragraf</span>
        </div>
        <x-tiptap
          :block-type="$block['type']"
          wire:model="content.{{ $blockId }}.data.text.{{ $lang }}"
          placeholder="Blok Paragraf..."
        />
      </div>
    @endforeach
  </div>
</x-blocks.editor.wrapper>
