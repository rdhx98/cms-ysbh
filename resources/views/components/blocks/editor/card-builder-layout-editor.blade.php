s
@php
    $columns = $card['layout']['children'] ?? [];
@endphp

<div class="flex flex-col gap-5 card-builder-layout-editor">
  @foreach ($columns as $colIdx => $col)
    <div
      wire:key="col-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}"
      class="min-w-0 space-y-3 border-dashed border border-foresty rounded-xl p-2"
    >
      @if (count($columns) > 1)
        <div class="flex items-center justify-between border-b border-gray-300 pb-2 group/cardhead">
          <div class="flex justify-center items-center gap-3">
            <label class="text-xxs font-bold text-gray-600 uppercase">Kolom {{ $colIdx + 1 }}</label>

            {{-- SEGMENTED CONTROL LEBAR KOLOM --}}
            <div class="flex items-center rounded-md bg-gray-200 transition-all duration-200 p-0.5 shadow-inner">
              <button type="button" x-on:click="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', 'auto')" class="rounded px-2 py-1 text-[10px] font-bold transition-all outline-none {{ ($col['width'] ?? 1) === 'auto' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Auto</button>
              <button type="button" x-on:click="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '1')" class="rounded px-2 py-1 text-[10px] font-bold transition-all outline-none {{ ($col['width'] ?? 1) == 1 ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">1fr</button>
              <button type="button" x-on:click="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '2')" class="rounded px-2 py-1 text-[10px] font-bold transition-all outline-none {{ ($col['width'] ?? 1) == 2 ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">2fr</button>
              <button type="button" x-on:click="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '3')" class="rounded px-2 py-1 text-[10px] font-bold transition-all outline-none {{ ($col['width'] ?? 1) == 3 ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">3fr</button>
            </div>
          </div>

          <button type="button" x-on:click="$wire.removeColumnFromCard('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}')" class="rounded-full bg-red-100 p-1 text-red-600 shadow-sm transition-opacity outline-none cursor-pointer">
            <x-dynamic-component component="lucide-trash" class="h-3 w-3" />
          </button>
        </div>
      @endif

      {{-- excess loop --}}
      {{-- @foreach (($col['children'] ?? []) as $elIndex => $el)
        @php
          $elPath = "content.{$blockId}.data.cards.{$cIndex}.layout.children.{$colIdx}.children.{$elIndex}";
          $style = $el['data']['style'] ?? [];
        @endphp
        <div wire:key="el-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}-{{ $el['id'] }}" class="group relative mb-3 rounded-lg bg-white p-4 shadow-sm">
          <button type="button" x-on:click="$wire.removeElementFromColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $el['id'] }}')" class="absolute -top-2 -right-2 rounded-full bg-red-100 p-1 text-red-600 opacity-0 shadow-sm transition-opacity outline-none group-hover:opacity-100">
            <x-dynamic-component component="lucide-x" class="h-3 w-3" />
          </button> --}}

          @foreach (($col['children'] ?? []) as $elIndex => $el)
            @php
              $elPath = "content.{$blockId}.data.cards.{$cIndex}.layout.children.{$colIdx}.children.{$elIndex}";
              $type = $el['elementType'] ?? 'text';
              $style = $el['data']['style'] ?? [];
              $content = $el['data']['content'] ?? [];
            @endphp
            
            <div wire:key="el-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}-{{ $el['id'] }}" class="group relative mb-3 rounded-lg bg-white p-4 shadow-sm">
              <button type="button" x-on:click="$wire.removeElementFromColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $el['id'] }}')" class="absolute -top-2 -right-2 rounded-full bg-red-100 p-1 text-red-600 opacity-0 shadow-sm transition-opacity outline-none group-hover:opacity-100">
                <x-dynamic-component component="lucide-x" class="h-3 w-3" />
              </button>

              {{-- 🌟 KEAJAIBAN DYNAMIC INCLUDE 🌟 --}}
              @if (view()->exists('components.blocks.editor.card-builder.element-' . $type))
                @include('components.blocks.editor.card-builder.element-' . $type, [
                    'elPath' => $elPath,
                    'style' => $style,
                    'content' => $content,
                    'code' => $code, // Bahasa aktif (id/en)
                    'iconsList' => $iconsList ?? [] // Khusus elemen ikon
                ])
              @else
                <div class="p-4 bg-red-50 text-red-500 text-xs text-center border border-red-200 rounded-md">
                  Error: File editor untuk elemen <b>{{ $type }}</b> tidak ditemukan.
                </div>
              @endif

            </div>
          @endforeach

          {{-- excess loop --}}
        {{-- </div>
      @endforeach --}}

      <div class="mt-2 border-t border-gray-300 pt-2 flex justify-end items-center gap-2">
        <button type="button" x-on:click="$wire.addElementToColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', 'text')" class="hover:border-foresty hover:text-foresty hover:bg-sage-soft flex items-center rounded-lg border border-dashed border-gray-300 bg-white p-1.5 text-xxs font-bold text-gray-500 shadow-sm transition-colors outline-none gap-2" title="Teks">
          <x-dynamic-component component="lucide-square-dashed-text" class="h-3 w-3" /> Teks
        </button>
        <button type="button" x-on:click="$wire.addElementToColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', 'icon')" class="hover:border-foresty hover:text-foresty hover:bg-sage-soft flex items-center rounded-lg border border-dashed border-gray-300 bg-white p-1.5 text-xxs font-bold text-gray-500 shadow-sm transition-colors outline-none gap-2" title="Ikon">
          <x-dynamic-component component="lucide-shapes" class="h-3 w-3" /> Ikon
        </button>
        <button type="button" x-on:click="$wire.addElementToColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', 'accordion')" class="hover:border-foresty hover:text-foresty hover:bg-sage-soft flex items-center rounded-lg border border-dashed border-gray-300 bg-white p-1.5 text-xxs font-bold text-gray-500 shadow-sm transition-colors outline-none gap-2" title="Ikon">
          <x-dynamic-component component="lucide-list-chevrons-up-down" class="h-3 w-3" /> Akordion
        </button>
        <button type="button" x-on:click="$wire.addElementToColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', 'profile_photo')" class="hover:border-foresty hover:text-foresty hover:bg-sage-soft flex items-center rounded-lg border border-dashed border-gray-300 bg-white p-1.5 text-xxs font-bold text-gray-500 shadow-sm transition-colors outline-none gap-2" title="Ikon">
          <x-dynamic-component component="lucide-list-chevrons-up-down" class="h-3 w-3" /> Profil
        </button>
      </div>
    </div>
  @endforeach
  <button type="button" x-on:click="$wire.addColumnToCard('{{ $blockId }}', {{ $cIndex }})" class="bg-foresty hover:bg-foresty-dark mt-3 rounded-full px-3 py-1.5 text-xs font-bold text-white shadow-sm transition-colors">
    + Tambah Kolom
  </button>
</div>
