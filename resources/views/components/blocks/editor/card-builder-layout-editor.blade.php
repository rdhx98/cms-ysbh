@php
  $columns = $card["layout"]["children"] ?? [];
  $elementTitles = [
    "text" => "Teks",
    "icon" => "Ikon",
    "accordion" => "Akordion",
    "profile_photo" => "Foto Profil",
    "initials" => "Inisial Nama",
  ];
  $iconsList = collect(config("cms.lucide", []))->sort()->values()->all();
@endphp

{{-- pt-4 --}}
<div
  class="card-builder-layout-editor flex transform-gpu flex-col gap-2 transition-all"
  x-data="{ collapsedColumns: {} }"
>
  @foreach ($columns as $colIdx => $col)
    <div
      wire:key="col-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}"
      class="min-w-0 space-y-3 border-dashed border-gray-700 bg-gray-100 transition-all duration-300 last:border-b-2"
      x-data="{ isColumnCollapsed: false }"
      x-bind:class="collapsedColumns['{{ $col['id'] }}'] ? 'border-y-2' : 'border-b-2 '"
    >
      {{-- HEADER KOLOM: Label & Pengaturan Lebar / Posisi --}}
      <div
        class="group/cardhead flex flex-wrap items-center justify-between gap-3 bg-white p-2"
        x-bind:class="collapsedColumns['{{ $col['id'] }}'] ? 'mb-0' : ''"
      >
        <div class="flex flex-wrap items-center gap-4">
          <label class="text-xxs font-bold text-gray-700 uppercase"
            >Kolom {{ $colIdx + 1 }}</label
          >
          <!-- BUTTONS MOVE -->
          <div class="flex items-center gap-1 rounded-md bg-gray-100 p-0.5">
            {{-- Geser Kiri --}}
            <button
              type="button"
              wire:click="moveCardColumn('{{ $blockId }}', {{ $cIndex }}, {{ $colIdx }}, 'left')"
              @if ($colIdx === 0) disabled class="cursor-not-allowed p-1 opacity-30" @else class="hover:text-foresty p-1 transition-colors" @endif
              title="Geser Kolom ke Kiri"
            >
              <x-dynamic-component
                component="lucide-chevron-left"
                class="h-3.5 w-3.5"
                stroke-width="3"
              />
            </button>

            {{-- Garis Pemisah --}}
            <div class="h-3 w-px bg-gray-300"></div>

            {{-- Geser Kanan --}}
            <button
              type="button"
              wire:click="moveCardColumn('{{ $blockId }}', {{ $cIndex }}, {{ $colIdx }}, 'right')"
              @if ($colIdx === count($columns) - 1) disabled class="cursor-not-allowed p-1 opacity-30" @else class="hover:text-foresty p-1 transition-colors" @endif
              title="Geser Kolom ke Kanan"
            >
              <x-dynamic-component
                component="lucide-chevron-right"
                class="h-3.5 w-3.5"
                stroke-width="3"
              />
            </button>
          </div>

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

        <div class="flex gap-2">
          <!-- COLLAPSE BUTTON -->
          <button
            type="button"
            {{-- x-on:click="$wire.removeColumnFromCard('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}')" --}}
            {{-- x-on:click="isColumnCollapsed = !isColumnCollapsed" --}}
            x-on:click="collapsedColumns['{{ $col['id'] }}'] = !collapsedColumns['{{ $col['id'] }}']"
            class="cursor-pointer rounded-sm px-1.5 py-1 transition-colors outline-none"
            {{-- x-bind:class="
                isColumnCollapsed
                  ? 'bg-forest text-white shadow-sm'
                  : 'cursor-pointer rounded-sm shadow-sm transition-colors outline-none'
              " --}}
            x-bind:class="collapsedColumns['{{ $col['id'] }}'] ? 'bg-foresty text-white shadow-sm' : 'cursor-pointer rounded-sm shadow-sm transition-colors outline-none text-gray-500 hover:bg-gray-200'"
          >
            <x-dynamic-component
              x-show="!collapsedColumns['{{ $col['id'] }}']"
              x-cloak
              component="lucide-list-chevrons-down-up"
              class="h-3.5 w-3.5"
            />
            <x-dynamic-component
              x-show="collapsedColumns['{{ $col['id'] }}']"
              x-cloak
              component="lucide-list-chevrons-up-down"
              class="h-3.5 w-3.5"
            />
          </button>
          @if (count($columns) > 1)
            <!-- DELETE BUTTON -->
            <button
              type="button"
              x-on:click="if (confirm('Yakin ingin menghapus kolom ini beserta seluruh elemen di dalamnya?')) { $wire.removeColumnFromCard('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}') }"
              class="cursor-pointer rounded-sm bg-red-50 p-1 text-red-500 shadow-sm transition-colors outline-none hover:bg-red-100 hover:text-red-600"
            >
              <x-dynamic-component
                component="lucide-trash"
                class="h-3.5 w-3.5"
              />
            </button>
          @endif
        </div>
      </div>

      <!-- COLLAPSED CONTAINER -->
      <div
        class="grid transition-all duration-300 ease-in-out"
        x-bind:class="collapsedColumns['{{ $col['id'] }}'] ? 'grid-rows-[0fr] opacity-0 mb-0' : 'grid-rows-[1fr] opacity-100 mb-2'"
      >
        {{-- Inner wrapper dengan min-h-0 mutlak diperlukan agar Grid bisa menyusut --}}
        <div class="min-h-0 overflow-hidden">
          <div class="flex flex-col gap-6 pt-4">
            <!-- LOOP ELEMENTS -->
            @if (count($col["children"]) === 0)
              <div
                class="flex items-center justify-center p-2 text-xs normal-case"
              >
                Belum ada element pada kartu. Tambahkan dengan tombol di bawah.
              </div>
            @else
              @foreach ($col["children"] ?? [] as $elIndex => $el)
                @php
                  $elPath = "content.{$blockId}.data.cards.{$cIndex}.layout.children.{$colIdx}.children.{$elIndex}";
                  $type = $el["elementType"] ?? "text";
                  $style = $el["data"]["style"] ?? [];
                  $content = $el["data"]["content"] ?? [];
                  $displayTitle = $elementTitles[$type] ?? ucwords(str_replace("_", " ", $type));
                @endphp

                <div
                  {{-- wire:key="el-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}-{{ $el['id'] }}" --}}
                  wire:key="el-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}-{{ $el['id'] }}-{{ $elIndex }}"
                  class="group relative mr-3 ml-2 rounded-xl border border-gray-400 bg-white shadow-sm"
                >
                  <div class="mb-2 flex justify-end gap-2">
                    <span
                      class="border-forest text-forest bg-mist text-xxs -mt-3 flex items-center gap-1 rounded-md border px-2 font-extrabold tracking-widest uppercase shadow-inner"
                    >
                      {{
                        ucwords(
                          str_replace("_", " ", $displayTitle),
                        )
                      }}
                    </span>
                    <div
                      class="-mt-3 -mr-2 flex items-center gap-1 rounded-md border border-gray-400 bg-gray-100 shadow-inner"
                    >
                      <!-- Geser Atas (Naik) -->
                      <button
                        type="button"
                        wire:click="moveCardElement('{{ $blockId }}', {{ $cIndex }}, {{ $colIdx }}, {{ $elIndex }}, 'up')"
                        @if ($elIndex === 0) disabled class="cursor-not-allowed p-1 opacity-30" @else class="hover:text-foresty p-1 transition-colors" @endif
                        title="Pindah ke Atas"
                      >
                        <x-dynamic-component
                          component="lucide-chevron-up"
                          class="h-3.5 w-3.5"
                          stroke-width="3"
                        />
                      </button>

                      <div class="h-3 w-px bg-gray-300"></div>

                      <!-- Geser Bawah (Turun) -->
                      <button
                        type="button"
                        wire:click="moveCardElement('{{ $blockId }}', {{ $cIndex }}, {{ $colIdx }}, {{ $elIndex }}, 'down')"
                        @if ($elIndex === count($col["children"] ?? []) - 1)
                          disabled
                          class="cursor-not-allowed p-1 opacity-30"
                        @else
                          class="hover:text-foresty p-1 transition-colors"
                        @endif
                        title="Pindah ke Bawah"
                      >
                        <x-dynamic-component
                          component="lucide-chevron-down"
                          class="h-3.5 w-3.5"
                          stroke-width="3"
                        />
                      </button>
                      <!-- hapus element-->
                      <button
                        type="button"
                        x-on:click="if (confirm('Yakin ingin menghapus elemen ini?')) { $wire.removeElementFromColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $el['id'] }}') }"
                        class="rounded-md bg-red-100 p-1 text-red-600 shadow-sm transition-opacity outline-none"
                        {{-- x-bind:class="" --}}
                      >
                        <x-dynamic-component
                          component="lucide-x"
                          class="h-3.5 w-3.5"
                        />
                      </button>
                    </div>
                  </div>

                  @if (view()->exists( "components.blocks.editor.card-builder.element-" . $type ))
                    <div class="px-2 pb-2">
                      <!-- 🌟 KUNCI: Meneruskan activeLocales ke elemen -->
                      @include ("components.blocks.editor.card-builder.element-" . $type,
                        [
                          "elPath" => $elPath,
                          "style" => $style,
                          "content" => $content,
                          "activeLocales" => $activeLocales,
                          "iconsList" => $iconsList ?? []
                        ])
                    </div>
                  @else
                    <div
                      class="rounded-md border border-red-200 bg-red-50 p-4 text-center text-xs text-red-500"
                    >
                      Error: Elemen <b>{{ $type }}</b> tidak ditemukan.
                    </div>
                  @endif
                </div>
              @endforeach
            @endif
            <!-- BUTTONS ADD ELEMENTS -->
            <div
              class="flex items-center justify-end gap-2 border-t border-gray-200 p-2"
            >
              @foreach ([
                  ["text", "square-dashed-text", "Teks"],
                  ["icon", "shapes", "Ikon"],
                  ["accordion", "list-chevrons-up-down", "Akordion"],
                  ["profile_photo", "image", "Profil"],
                  ["initials", "a-large-small", "Inisial"]
                ]
                as $btn)
                <button
                  type="button"
                  x-on:click="$wire.addElementToColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $btn[0] }}')"
                  class="bg-mist hover:text-foresty text-charcoal text-xxs hover:border-forest flex items-center gap-1.5 rounded-lg border border-dashed border-transparent px-2 py-1.5 font-bold shadow-sm transition-colors outline-none"
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
        </div>
      </div>
    </div>
  @endforeach
  <button
    type="button"
    x-on:click="$wire.addColumnToCard('{{ $blockId }}', {{ $cIndex }})"
    class="bg-forest/80 sticky bottom-0 mt-2 rounded-lg px-4 py-2 text-xs font-bold text-white shadow-sm backdrop-blur-md backdrop-saturate-150 transition-colors"
  >
    + Tambah Kolom Baru
  </button>
</div>
