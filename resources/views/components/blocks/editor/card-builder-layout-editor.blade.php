@php
  $columns = $card["layout"]["children"] ?? [];
@endphp

<div class="card-builder-layout-editor flex flex-col gap-5">
  @foreach ($columns as $colIdx => $col)
    <div
      wire:key="col-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}"
      class="border-foresty min-w-0 space-y-3 rounded-xl border border-dashed bg-white/50 p-3"
    >
      {{-- PENGATURAN LEBAR KOLOM --}}
      {{-- @if (count($columns) > 1)
        <div
          class="group/cardhead flex items-center justify-between border-b border-gray-200 pb-2"
        >
          <div class="flex items-center justify-center gap-3">
            <label class="text-[10px] font-bold text-gray-500 uppercase"
              >Kolom {{ $colIdx + 1 }}</label
            >
            <div
              class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner transition-all duration-200"
            >
              @foreach ([["auto", "Auto"], ["1", "1fr"], ["2", "2fr"], ["3", "3fr"]] as $opt)
                <button
                  type="button"
                  x-on:click="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $opt[0] }}')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ ($col['width'] ?? 1) == $opt[0] ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  {{ $opt[1] }}
                </button>
              @endforeach
            </div>
          </div>
          <button
            type="button"
            x-on:click="$wire.removeColumnFromCard('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}')"
            class="cursor-pointer rounded-full bg-red-50 p-1.5 text-red-500 shadow-sm transition-colors outline-none hover:bg-red-100 hover:text-red-600"
          >
            <x-dynamic-component component="lucide-trash" class="h-3.5 w-3.5" />
          </button>
        </div>
      @endif --}}
      {{-- HEADER KOLOM: Label & Pengaturan Lebar / Posisi --}}
      <div
        class="group/cardhead flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-2"
      >
        <div class="flex flex-wrap items-center gap-4">
          <label class="text-[10px] font-bold text-gray-500 uppercase"
            >Kolom {{ $colIdx + 1 }}</label
          >

          {{-- PENGATURAN LEBAR KOLOM --}}
          @if (count($columns) > 1)
            <div
              class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner transition-all duration-200"
            >
              @foreach ([["auto", "Auto"], ["1", "1fr"], ["2", "2fr"], ["3", "3fr"]] as $opt)
                <button
                  type="button"
                  x-on:click="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $opt[0] }}')"
                  class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ ($col['width'] ?? 1) == $opt[0] ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                >
                  {{ $opt[1] }}
                </button>
              @endforeach
            </div>
          @endif

          {{-- 🌟 PENGATURAN POSISI TEKS/ELEMEN (ALIGNMENT) 🌟 --}}
          <div
            class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner transition-all duration-200"
          >
            @foreach ([
                ["items-start text-left", "lucide-align-left"],
                ["items-center text-center", "lucide-align-center"],
                ["items-end text-right", "lucide-align-right"]
              ]
              as $alignOpt)
              <button
                type="button"
                x-on:click="$wire.updateColumnAlignment('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $alignOpt[0] }}')"
                class="rounded p-1 transition-all outline-none {{ ($col['align'] ?? 'items-start text-left') == $alignOpt[0] ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                title="Atur Posisi"
              >
                <x-dynamic-component
                  component="{{ $alignOpt[1] }}"
                  class="h-3.5 w-3.5"
                />
              </button>
            @endforeach
          </div>
        </div>

        @if (count($columns) > 1)
          <button
            type="button"
            x-on:click="$wire.removeColumnFromCard('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}')"
            class="cursor-pointer rounded-full bg-red-50 p-1.5 text-red-500 shadow-sm transition-colors outline-none hover:bg-red-100 hover:text-red-600"
          >
            <x-dynamic-component component="lucide-trash" class="h-3.5 w-3.5" />
          </button>
        @endif
      </div>

      @foreach ($col["children"] ?? [] as $elIndex => $el)
        @php
          $elPath = "content.{$blockId}.data.cards.{$cIndex}.layout.children.{$colIdx}.children.{$elIndex}";
          $type = $el["elementType"] ?? "text";
          $style = $el["data"]["style"] ?? [];
          $content = $el["data"]["content"] ?? [];
        @endphp

        <div
          wire:key="el-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}-{{ $el['id'] }}"
          class="group relative mb-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
        >
          <button
            type="button"
            x-on:click="$wire.removeElementFromColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $el['id'] }}')"
            class="absolute -top-2 -right-2 rounded-full bg-red-100 p-1 text-red-600 opacity-0 shadow-sm transition-opacity outline-none group-hover:opacity-100"
          >
            <x-dynamic-component component="lucide-x" class="h-3 w-3" />
          </button>

          @if (view()->exists( "components.blocks.editor.card-builder.element-" . $type ))
            <!-- 🌟 KUNCI: Meneruskan activeLocales ke elemen -->
            @include ("components.blocks.editor.card-builder.element-" . $type,
              [
                "elPath" => $elPath,
                "style" => $style,
                "content" => $content,
                "activeLocales" => $activeLocales,
                "iconsList" => $iconsList ?? []
              ])
          @else
            <div
              class="rounded-md border border-red-200 bg-red-50 p-4 text-center text-xs text-red-500"
            >
              Error: Elemen <b>{{ $type }}</b> tidak ditemukan.
            </div>
          @endif
        </div>
      @endforeach

      <div
        class="mt-2 flex items-center justify-end gap-2 border-t border-gray-200 pt-3"
      >
        @foreach ([
            ["text", "square-dashed-text", "Teks"],
            ["icon", "shapes", "Ikon"],
            ["accordion", "list-chevrons-up-down", "Akordion"],
            ["profile_photo", "image", "Profil"]
          ]
          as $btn)
          <button
            type="button"
            x-on:click="$wire.addElementToColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $btn[0] }}')"
            class="hover:border-foresty hover:bg-sage-soft hover:text-foresty flex items-center gap-1.5 rounded-lg border border-dashed border-gray-300 bg-white px-2 py-1.5 text-[10px] font-bold text-gray-500 shadow-sm transition-colors outline-none"
          >
            <x-dynamic-component
              component="lucide-{{ $btn[1] }}"
              class="h-3 w-3"
            />
            {{ $btn[2] }}
          </button>
        @endforeach
      </div>
    </div>
  @endforeach
  <button
    type="button"
    x-on:click="$wire.addColumnToCard('{{ $blockId }}', {{ $cIndex }})"
    class="bg-foresty mt-2 rounded-full px-4 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
  >
    + Tambah Kolom Baru
  </button>
</div>
