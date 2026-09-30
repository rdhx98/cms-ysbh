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

  <x-slot:settings>
    <div
      x-show="isCollapsed"
      x-cloak
      class="min-w-0 flex-1 px-2 text-xs font-medium text-gray-400 sm:px-4"
      {{-- 💡 Tooltip Dinamis Alpine.js (Tidak akan mengubah layout/tinggi sama sekali) --}}
      {{-- :title="($wire.get('content.{{ $blockId }}.data.text.{{ $code }}') || '').replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'" --}}
    >
      {{-- Jadikan span sebagai block dan berikan truncate untuk memotongnya menjadi 1 baris ketat --}}
      <span
        class="block w-full truncate text-right"
        {{-- x-text="($wire.get('content.{{ $blockId }}.data.text.{{ $lang }}') || '').replace(/<\/?[^>]+(>|$)/g, '').replace(/&nbsp;/g, ' ').trim() || 'Kosong...'" --}}
      >
      </span>
    </div>
    <div class="flex items-center gap-2" @click.stop>
      <div class="flex items-center rounded border border-gray-200 text-xs">
        <span class="px-2 text-gray-400">Jarak Atas</span>
        <select
          wire:model="content.{{ $blockId }}.data.spacing.mt"
          class="rounded-r border-0 bg-gray-50 py-0.5 text-xs focus:ring-0"
        >
          <option value="0px">0</option>
          <option value="16px">Normal</option>
          <option value="32px">Lebar</option>
          <option value="64px">Sangat Lebar</option>
        </select>
      </div>
      <div class="flex items-center rounded border border-gray-200 text-xs">
        <span class="px-2 text-gray-400">Bawah</span>
        <select
          wire:model="content.{{ $blockId }}.data.spacing.mb"
          class="rounded-r border-0 bg-gray-50 py-0.5 text-xs focus:ring-0"
        >
          <option value="0px">0</option>
          <option value="16px">Normal</option>
          <option value="32px">Lebar</option>
          <option value="64px">Sangat Lebar</option>
        </select>
      </div>
      {{-- Anda bisa menduplikasi select di atas untuk pt (padding top) dan pb (padding bottom) jika diperlukan --}}
    </div>
  </x-slot:settings>

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
