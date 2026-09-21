{{--
    _card-layout-editor.blade.php (v2 - lengkap)

    Menggantikan DUA bagian di index_blade.php sekaligus:
      1. "Tab Area (Jika Media Object)" (baris ~493-533)
      2. "Loop Elemen di dalam Slot" (baris ~535-786, termasuk tombol
         "+ Teks"/"+ Ikon" di baris 788-804)

    Di-include persis di posisi yang sama (di dalam @foreach ($cards as
    $cIndex => $card), di dalam div wire:key="card-editor-...") - jadi
    $blockId, $cIndex, $card, $code, $iconsList semua sudah tersedia dari
    scope pemanggil, tidak perlu dioper ulang lewat @include().

    Kolom ditampilkan SEJAJAR langsung (bukan tab Kiri/Tengah/Kanan lagi) -
    karena jumlah kolom sekarang bisa berapa saja, bukan cuma 3 bernama
    tetap. activeSlot TIDAK dipakai lagi di partial ini.

    ATURAN WAJIB (pelajaran dari sesi sebelumnya): semua elemen interaktif
    baru di sini SATU JALUR lewat x-on:click="$wire...." - tidak ada
    wire:click berdampingan dengan x-on:click di elemen yang sama.
--}}
@php
    $columns = $card['layout']['children'] ?? [];
@endphp

<div
  class="flex flex-col gap-5"
  {{-- class="grid items-start gap-3" --}}
  {{-- DEFAULT style="grid-template-columns: {{ collect($columns)->map(fn($c) => ($c['width'] ?? 1) . 'fr')->implode(' ') }};" --}}
  {{-- FIXED style="grid-template-columns: {{ collect($columns)->map(function($c) { $w = $c['width'] ?? 1; return $w === 'auto' ? 'auto' : $w . 'fr'; })->implode(' ') }};" --}}
>
  @foreach ($columns as $colIdx => $col)
    <div
      wire:key="col-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}"
      class="min-w-0 space-y-3 border-dashed border border-foresty rounded-xl p-2"
    >
      @if (count($columns) > 1)
        {{-- <div class="flex items-center gap-2 border-b  pb-2 ">
          <label class="text-xxs font-bold text-foresty uppercase"
            >Kolom {{ $colIdx + 1 }} · Lebar</label
          >
          <!-- <input
            type="number"
            min="1"
            max="6"
            value="{{ $col['width'] ?? 1 }}"
            x-on:change="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', $event.target.value)"
            class="w-12 rounded border-gray-200 px-1 py-0.5 text-[11px]"
          /> -->
          <select
            x-on:change="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', $event.target.value)"
            class="rounded border-gray-200 px-1.5 py-0.5 text-[11px]"
          >
            <option value="auto" {{ ($col['width'] ?? 1) === 'auto' ? 'selected' : '' }}>Sesuai Isi (Auto)</option>
            <option value="1" {{ ($col['width'] ?? 1) == 1 ? 'selected' : '' }}>1 Bagian (1fr)</option>
            <option value="2" {{ ($col['width'] ?? 1) == 2 ? 'selected' : '' }}>2 Bagian (2fr)</option>
            <option value="3" {{ ($col['width'] ?? 1) == 3 ? 'selected' : '' }}>3 Bagian (3fr)</option>
            <option value="4" {{ ($col['width'] ?? 1) == 4 ? 'selected' : '' }}>4 Bagian (4fr)</option>
          </select>
          <button
            type="button"
            x-on:click="$wire.removeColumnFromCard('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}')"
            class="ml-auto text-[10px] font-bold text-red-500 hover:text-red-700"
          >
            Hapus Kolom
          </button>
        </div> --}}
        <div class="flex items-center justify-between border-b border-gray-300 pb-2 group/cardhead">

          <div class="flex justify-center items-center gap-3">
            <label class="text-xxs font-bold text-gray-600 uppercase">Kolom {{ $colIdx + 1 }}</label>

            {{-- 🌟 UX BARU: SEGMENTED CONTROL (1 KLIK) --}}
            <div class="flex items-center rounded-md bg-gray-200 transition-all duration-200 p-0.5 shadow-inner">
              <button
                type="button"
                x-on:click="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', 'auto')"
                class="rounded px-2 py-1 text-[10px] font-bold transition-all outline-none {{ ($col['width'] ?? 1) === 'auto' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
              >
                Auto
              </button>
              <button
                type="button"
                x-on:click="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '1')"
                class="rounded px-2 py-1 text-[10px] font-bold transition-all outline-none {{ ($col['width'] ?? 1) == 1 ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
              >
                1fr
              </button>
              <button
                type="button"
                x-on:click="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '2')"
                class="rounded px-2 py-1 text-[10px] font-bold transition-all outline-none {{ ($col['width'] ?? 1) == 2 ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
              >
                2fr
              </button>
              <button
                type="button"
                x-on:click="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '3')"
                class="rounded px-2 py-1 text-[10px] font-bold transition-all outline-none {{ ($col['width'] ?? 1) == 3 ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
              >
                3fr
              </button>
            </div>
          </div>

          <button
            type="button"
            x-on:click="$wire.removeColumnFromCard('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}')"
            class=" rounded-full bg-red-100 p-1 text-red-600  shadow-sm transition-opacity outline-none cursor-pointer"
          >
            <x-dynamic-component component="lucide-trash" class="h-3 w-3" />
          </button>
        </div>
      @endif

      @foreach (($col['children'] ?? []) as $elIndex => $el)
        @php $elPath = "content.{$blockId}.data.cards.{$cIndex}.layout.children.{$colIdx}.children.{$elIndex}"; @endphp
        <div
          wire:key="el-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}-{{ $el['id'] }}"
          class="group relative mb-3 rounded-lg  bg-white p-4 shadow-sm "
        >
          <button
            type="button"
            x-on:click="$wire.removeElementFromColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $el['id'] }}')"
            class="absolute -top-2 -right-2 rounded-full bg-red-100 p-1 text-red-600 opacity-0 shadow-sm transition-opacity outline-none group-hover:opacity-100"
          >
            <x-dynamic-component component="lucide-x" class="h-3 w-3" />
          </button>

          {{-- ================= TEKS ================= --}}
          @if (($el['elementType'] ?? 'text') === 'text')
            @php $isPill = $el['data']['style']['is_pill'] ?? false; @endphp
            <div class="mb-2 flex items-center justify-between">
              <span
                class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded {{ $isPill ? 'bg-goldy-soft text-goldy-dark' : 'bg-foresty/10 text-foresty' }}"
              >
                {{ $isPill ? 'Lencana (Pill)' : 'Teks' }}
              </span>
              <label class="flex cursor-pointer items-center gap-1.5">
                <input
                  type="checkbox"
                  wire:model.live="{{ $elPath }}.data.style.is_pill"
                  class="text-foresty focus:ring-foresty h-3.5 w-3.5 rounded border-gray-300"
                />
                <span class="text-[10px] font-bold text-gray-500 uppercase"
                  >Mode Pill</span
                >
              </label>
            </div>

            <textarea
              rows="2"
              {{-- wire:model.blur="{{ $elPath }}.data.content.{{ $code }}" --}}
              wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.{{ $code }}"
              placeholder="Ketik isi teks di sini..."
              class="focus:ring-foresty mb-2 p-2 w-full resize-none rounded-lg border-gray-200 text-sm font-semibold shadow-sm"
            ></textarea>

            <div
              class="flex flex-wrap gap-2 rounded-lg border border-gray-100 bg-gray-50 p-2"
            >
              @if (!$isPill)
                <select
                  wire:model.live="{{ $elPath }}.data.style.font"
                  class="rounded border-gray-200 py-1 text-[11px]"
                >
                  <option value="font-sans">Sistem Font</option>
                  <option value="font-display">Display Font</option>
                </select>
                <select
                  wire:model.live="{{ $elPath }}.data.style.size"
                  class="rounded border-gray-200 py-1 text-[11px]"
                >
                  <option value="text-[13px]">Kecil</option>
                  <option value="text-[15px]">Normal</option>
                  <option value="text-[21px]">Besar (H3)</option>
                </select>
                <select
                  wire:model.live="{{ $elPath }}.data.style.weight"
                  class="rounded border-gray-200 py-1 text-[11px]"
                >
                  <option value="font-normal">Reguler</option>
                  <option value="font-semibold">Semi Bold</option>
                </select>
              @else
                <select
                  wire:model.live="{{ $elPath }}.data.style.pill_bg"
                  class="rounded border-gray-200 py-1 text-[11px]"
                >
                  <option value="bg-goldy-soft">Bg Goldy</option>
                  <option value="bg-mist">Bg Mist</option>
                  <option value="bg-sage-soft">Bg Sage</option>
                </select>
                <select
                  wire:model.live="{{ $elPath }}.data.style.pill_radius"
                  class="rounded border-gray-200 py-1 text-[11px]"
                >
                  <option value="rounded-md">Sedikit Bulat</option>
                  <option value="rounded-full">Bulat Penuh</option>
                </select>
              @endif
              <select
                wire:model.live="{{ $elPath }}.data.style.color"
                class="rounded border-gray-200 py-1 text-[11px]"
              >
                <option value="text-ink-soft">Abu Gelap</option>
                <option value="text-foresty">Foresty</option>
                <option value="text-coral">Coral</option>
              </select>
              <select
                wire:model.live="{{ $elPath }}.data.style.margin"
                class="rounded border-gray-200 py-1 text-[11px]"
              >
                <option value="mb-0">Jarak Bawah: 0</option>
                <option value="mb-2">Jarak Bawah: Kecil</option>
                <option value="mb-4">Jarak Bawah: Sedang</option>
              </select>
            </div>

            {{-- ================= IKON ================= --}}
          @elseif (($el['elementType'] ?? 'icon') === 'icon')
            <div class="mb-2 flex items-center justify-between">
              <span
                class="bg-foresty rounded px-2 py-0.5 text-[10px] font-extrabold tracking-widest text-white uppercase"
                >Ikon</span
              >
            </div>

            <div
              class="relative mb-2"
              x-data="{ openPicker: false, search: '' }"
            >
              <button
                type="button"
                x-on:click="openPicker = !openPicker"
                class="hover:border-foresty flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs shadow-sm transition-colors focus:outline-none"
              >
                <div class="flex items-center gap-2 truncate">
                  <x-dynamic-component
                    :component="'lucide-' . ($el['data']['content']['icon'] ?: 'box')"
                    class="text-foresty h-5 w-5 shrink-0"
                    stroke-width="2.5"
                  />
                  <span
                    class="truncate font-mono text-[11px] font-bold text-gray-700 uppercase"
                    >{{ $el['data']['content']['icon'] ?: 'PILIH IKON...' }}</span
                  >
                </div>
                <x-dynamic-component
                  component="lucide-chevron-down"
                  class="h-4 w-4 shrink-0 text-gray-400"
                />
              </button>

              <div
                x-show="openPicker"
                x-on:click.outside="openPicker = false"
                x-cloak
                class="absolute left-0 z-50 mt-1 flex w-full flex-col gap-2 rounded-xl border border-gray-200 bg-white p-3 shadow-xl sm:w-64"
              >
                <div class="relative">
                  <x-dynamic-component
                    component="lucide-search"
                    class="absolute top-2.5 left-3 h-4 w-4 text-gray-400"
                  />
                  <input
                    type="text"
                    x-model="search"
                    placeholder="Cari ikon..."
                    class="focus:ring-foresty focus:border-foresty w-full rounded-lg border border-gray-200 py-2 pr-2 pl-9 text-xs shadow-sm"
                  />
                </div>
                <div
                  class="grid max-h-48 scrollbar-thin grid-cols-5 gap-1.5 overflow-y-auto p-1"
                >
                  @foreach ($iconsList as $iconName)
                    <button
                      type="button"
                      x-show="'{{ $iconName }}'.includes(search.toLowerCase())"
                      x-on:click="$wire.set('{{ $elPath }}.data.content.icon', '{{ $iconName }}'); openPicker = false; search = '';"
                      class="p-2.5 rounded-lg flex items-center justify-center transition-all duration-200 border {{ ($el['data']['content']['icon'] ?? '') === $iconName ? 'bg-sage-soft text-foresty border-foresty shadow-sm scale-110' : 'bg-gray-50 text-gray-400 border-transparent hover:border-foresty/50 hover:text-foresty' }}"
                    >
                      <x-dynamic-component
                        :component="'lucide-' . $iconName"
                        class="h-5 w-5 shrink-0"
                        stroke-width="2"
                      />
                    </button>
                  @endforeach
                </div>
              </div>
            </div>

            <div
              class="flex flex-wrap gap-2 rounded-lg border border-gray-100 bg-gray-50 p-2"
            >
              <select
                wire:model.live="{{ $elPath }}.data.style.bg"
                class="rounded border-gray-200 py-1 text-[11px]"
              >
                <option value="bg-goldy-soft">Latar Goldy</option>
                <option value="bg-mist">Latar Mist</option>
                <option value="bg-transparent">Latar Transparan</option>
              </select>
              <select
                wire:model.live="{{ $elPath }}.data.style.color"
                class="rounded border-gray-200 py-1 text-[11px]"
              >
                <option value="text-foresty">Warna Foresty</option>
                <option value="text-coral">Warna Coral</option>
              </select>
              <select
                wire:model.live="{{ $elPath }}.data.style.size"
                class="rounded border-gray-200 py-1 text-[11px]"
              >
                <option value="w-10 h-10">Ukuran Standar</option>
                <option value="w-16 h-16">Ukuran Besar</option>
              </select>
              <select
                wire:model.live="{{ $elPath }}.data.style.radius"
                class="rounded border-gray-200 py-1 text-[11px]"
              >
                <option value="rounded-[14px]">Agak Bulat</option>
                <option value="rounded-full">Lingkaran</option>
              </select>
            </div>
          @endif
        </div>
      @endforeach

      <div class="mt-2 border-t border-gray-300 pt-2 flex justify-end items-center gap-2">
        <button
          type="button"
          x-on:click="$wire.addElementToColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', 'text')"
          class="hover:border-foresty hover:text-foresty hover:bg-sage-soft flex rounded-lg border border-dashed border-gray-300 bg-white p-2.5 text-xs font-bold text-gray-500 shadow-sm transition-colors outline-none" title="Teks"
        >
          <x-dynamic-component component="lucide-square-dashed-text" class="h-5 w-5" />
        </button>
        <button
          type="button"
          x-on:click="$wire.addElementToColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', 'icon')"
          class="hover:border-foresty hover:text-foresty hover:bg-sage-soft flex rounded-lg border border-dashed border-gray-300 bg-white p-2.5 text-xs font-bold text-gray-500 shadow-sm transition-colors outline-none" title="Ikon"
        >
          <x-dynamic-component component="lucide-shapes" class="h- w-5" />
        </button>
      </div>
    </div>
  @endforeach
  <button
    type="button"
    x-on:click="$wire.addColumnToCard('{{ $blockId }}', {{ $cIndex }})"
    class="bg-forest hover:bg-forest-dark mt-3 rounded-full px-3 py-1.5 text-xs font-bold text-white"
  >
    + Tambah Kolom
  </button>
</div>

