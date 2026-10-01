@props ([
  "blockId",
  "block",
  "activeLocales" => [],
  "allContent" => [],
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

  <!-- 🌟 INJEKSI PENGATURAN KE HEADER KANAN (Gabung jadi satu!) -->
  {{-- <x-slot:settings>
    <span
      class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase"
      >Level:</span
    >
    <select
      wire:model="content.{{ $blockId }}.data.level"
      class="focus:border-foresty focus:ring-foresty h-7 rounded-md border-gray-200 bg-white py-0 pr-7 pl-2 text-xs font-bold text-gray-700 shadow-sm transition-colors"
    >
      <option value="h1">H1</option>
      <option value="h2">H2</option>
      <option value="h3">H3</option>
    </select>
  </x-slot:settings> --}}
  <x-slot:settings>
    <span class="text-xs font-semibold tracking-wider text-gray-400 uppercase">
      Level:
    </span>

    <!-- 🌟 BUNGKUSAN GRUP TOMBOL (Segmented Control) -->
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner">
      <!-- Tombol H1 -->
      <button
        type="button"
        @click="$wire.set('content.{{ $blockId }}.data.level', 'h1')"
        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
        :class="($wire.content?.['{{ $blockId }}']?.data?.level ?? 'h2') === 'h1' ? 'bg-forest text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
      >
        H1
      </button>

      <!-- Tombol H2 (Diasumsikan sebagai Default/Bawaan) -->
      <button
        type="button"
        @click="$wire.set('content.{{ $blockId }}.data.level', 'h2')"
        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
        :class="($wire.content?.['{{ $blockId }}']?.data?.level ?? 'h2') === 'h2' ? 'bg-forest text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
      >
        H2
      </button>

      <!-- Tombol H3 -->
      <button
        type="button"
        @click="$wire.set('content.{{ $blockId }}.data.level', 'h3')"
        class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none"
        :class="($wire.content?.['{{ $blockId }}']?.data?.level ?? 'h2') === 'h3' ? 'bg-forest text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
      >
        H3
      </button>
    </div>
  </x-slot:settings>

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
