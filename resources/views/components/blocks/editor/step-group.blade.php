@props ([ "blockId", "code", "block", "allContent", "activeLocales" => [] ])

@php
  $data = $block["data"] ?? [];
  $children = $data["children"] ?? [];

  $orientation = $data["orientation"] ?? "vertical";
  $gap = $data["gap"] ?? "gap-8";
@endphp

<x-blocks.editor.wrapper :block-id="$blockId" :block="$block">
  <x-slot:title>
    <div
      class="bg-sage-soft text-foresty flex h-5 w-5 items-center justify-center rounded-sm shadow-sm"
    >
      <x-dynamic-component
        component="lucide-list-ordered"
        class="h-3.5 w-3.5"
        stroke-width="2.5"
      />
    </div>
    Grup Langkah (Timeline)
  </x-slot:title>

  <x-slot:snippet>
    <span
      class="block w-full text-right text-xs font-medium text-gray-400 sm:text-left"
    >
      <span class="font-bold text-gray-500">{{
        count(
          $children,
        )
      }}</span>
      Langkah Berurutan
    </span>
  </x-slot:snippet>

  <!-- ========================================== -->
  <!-- PENGATURAN GLOBAL BLOK                     -->
  <!-- ========================================== -->
  <div
    class="mb-6 flex flex-col gap-4 rounded-xl border border-gray-100 bg-gray-50/80 p-4"
  >
    <div class="mb-2 flex items-center gap-2 border-b border-gray-200 pb-2">
      <x-dynamic-component
        component="lucide-settings-2"
        class="h-4 w-4 text-gray-400"
      />
      <span
        class="text-[10px] font-bold tracking-widest text-gray-500 uppercase"
        >Pengaturan Tampilan Timeline</span
      >
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
      <!-- Orientasi -->
      <div class="flex items-center gap-3">
        <label
          class="w-20 text-[10px] font-bold tracking-wide text-gray-400 uppercase"
          >Orientasi:</label
        >
        <div
          class="flex flex-1 flex-wrap items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
        >
          @foreach ([
              ["vertical", "Vertikal"],
              ["horizontal-top", "Horiz. Atas"],
              ["horizontal-bottom", "Horiz. Bawah"]
            ]
            as $opt)
            <button
              type="button"
              wire:click="$set('content.{{ $blockId }}.data.orientation', '{{ $opt[0] }}')"
              class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none flex-1 {{ $orientation === $opt[0] ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
              {{ $opt[1] }}
            </button>
          @endforeach
        </div>
      </div>

      <!-- Jarak (Gap) -->
      <div class="flex items-center gap-3">
        <label
          class="w-20 text-[10px] font-bold tracking-wide text-gray-400 uppercase"
          >Jarak:</label
        >
        <div
          class="flex flex-1 flex-wrap items-center rounded-md bg-gray-200 p-0.5 shadow-inner"
        >
          @foreach ([
              ["gap-4", "Rapat"],
              ["gap-8", "Sedang"],
              ["gap-12", "Renggang"],
              ["gap-16", "Jauh"]
            ]
            as $opt)
            <button
              type="button"
              wire:click="$set('content.{{ $blockId }}.data.gap', '{{ $opt[0] }}')"
              class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none flex-1 {{ $gap === $opt[0] ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
              {{ $opt[1] }}
            </button>
          @endforeach
        </div>
      </div>

      <!-- Warna Node & Garis -->
      <div class="flex items-center gap-3">
        <label
          class="w-20 text-[10px] font-bold tracking-wide text-gray-400 uppercase"
          >Warna Angka:</label
        >
        <input
          type="text"
          wire:model.live.debounce.500ms="content.{{ $blockId }}.data.node_color"
          placeholder="Misal: bg-foresty text-white"
          class="focus:ring-foresty focus:border-foresty w-full flex-1 rounded-md border-gray-300 py-1.5 font-mono text-xs shadow-sm"
        />
      </div>
      <div class="flex items-center gap-3">
        <label
          class="w-20 text-[10px] font-bold tracking-wide text-gray-400 uppercase"
          >Warna Garis:</label
        >
        <input
          type="text"
          wire:model.live.debounce.500ms="content.{{ $blockId }}.data.line_color"
          placeholder="Misal: bg-foresty/30"
          class="focus:ring-foresty focus:border-foresty w-full flex-1 rounded-md border-gray-300 py-1.5 font-mono text-xs shadow-sm"
        />
      </div>
    </div>
  </div>

  <!-- ========================================== -->
  <!-- AREA RENDER ANAK (LANGKAH-LANGKAH)         -->
  <!-- ========================================== -->
  <div
    class="relative ml-2 space-y-6 border-l-[3px] border-gray-200 pl-6 sm:ml-4 sm:pl-8"
  >
    @foreach ($children as $index => $childId)
      @if (isset($allContent[$childId]))
        @php $childBlock = $allContent[$childId]; @endphp
        <div class="group/step relative">
          <!-- Lingkaran Indikator -->
          <div
            class="group-hover/step:border-foresty group-hover/step:text-foresty absolute top-3 -left-[39px] z-10 flex h-7 w-7 items-center justify-center rounded-full border-[3px] border-gray-200 bg-white text-[11px] font-extrabold text-gray-400 shadow-sm transition-colors sm:-left-[49px] sm:h-8 sm:w-8"
          >
            {{ $index + 1 }}
          </div>

          <!-- Tombol Hapus Langkah -->
          <button
            type="button"
            wire:click="removeNestedBlock('{{ $blockId }}', 'children', '{{ $childId }}')"
            class="absolute -top-2 -right-2 z-20 flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-red-500 opacity-0 shadow-sm transition-all outline-none group-hover/step:opacity-100 hover:bg-red-500 hover:text-white"
            title="Hapus Langkah Ini"
          >
            <x-dynamic-component component="lucide-trash-2" class="h-3 w-3" />
          </button>

          <!-- Render Pembungkus Komponen Anak -->
          <div
            class="group-hover/step:border-foresty/50 rounded-xl border border-gray-200 bg-white p-1 shadow-sm transition-all group-hover/step:shadow-md"
          >
            <x-dynamic-component
              :component="'blocks.editor.' . str_replace('_', '-', $childBlock['type'])"
              :block-id="$childId"
              :code="$code"
              :block="$childBlock"
              :all-content="$allContent"
              :active-locales="$activeLocales"
            />
          </div>
        </div>
      @endif
    @endforeach

    <div class="pt-2">
      <button
        type="button"
        wire:click="addChildBlock('{{ $blockId }}', 'children', 'card-builder')"
        class="hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-xs font-bold tracking-wide text-gray-400 uppercase shadow-sm transition-all outline-none"
      >
        <x-dynamic-component component="lucide-plus-circle" class="h-4 w-4" />
        Tambah Langkah Baru
      </button>
    </div>
  </div>
</x-blocks.editor.wrapper>
