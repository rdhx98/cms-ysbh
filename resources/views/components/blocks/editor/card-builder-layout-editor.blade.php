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
  class="grid items-start gap-3"
  style="grid-template-columns: {{ collect($columns)->map(fn($c) => ($c['width'] ?? 1) . 'fr')->implode(' ') }};"
>
  @foreach ($columns as $colIdx => $col)
    <div
      wire:key="col-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}"
      class="min-w-0 space-y-3"
    >
      @if (count($columns) > 1)
        <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
          <label class="text-[9px] font-bold text-gray-400 uppercase"
            >Kolom {{ $colIdx + 1 }} · Lebar</label
          >
          <input
            type="number"
            min="1"
            max="6"
            value="{{ $col['width'] ?? 1 }}"
            x-on:change="$wire.updateColumnWidth('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', $event.target.value)"
            class="w-12 rounded border-gray-200 px-1 py-0.5 text-[11px]"
          />
          <button
            type="button"
            x-on:click="$wire.removeColumnFromCard('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}')"
            class="ml-auto text-[10px] font-bold text-red-500 hover:text-red-700"
          >
            Hapus Kolom
          </button>
        </div>
      @endif

      @foreach (($col['children'] ?? []) as $elIndex => $el)
        @php $elPath = "content.{$blockId}.data.cards.{$cIndex}.layout.children.{$colIdx}.children.{$elIndex}"; @endphp
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
              wire:model.blur="{{ $elPath }}.data.content.{{ $code }}"
              placeholder="Ketik isi teks di sini..."
              class="focus:ring-foresty mb-2 w-full resize-none rounded-lg border-gray-200 text-sm font-semibold shadow-sm"
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

      <div class="mt-2 flex gap-2">
        <button
          type="button"
          x-on:click="$wire.addElementToColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', 'text')"
          class="hover:border-foresty hover:text-foresty flex-1 rounded-lg border border-dashed border-gray-300 bg-white py-2.5 text-xs font-bold text-gray-500 shadow-sm transition-colors outline-none"
        >
          + Teks
        </button>
        <button
          type="button"
          x-on:click="$wire.addElementToColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', 'icon')"
          class="hover:border-foresty hover:text-foresty flex-1 rounded-lg border border-dashed border-gray-300 bg-white py-2.5 text-xs font-bold text-gray-500 shadow-sm transition-colors outline-none"
        >
          + Ikon
        </button>
      </div>
    </div>
  @endforeach
</div>

<button
  type="button"
  x-on:click="$wire.addColumnToCard('{{ $blockId }}', {{ $cIndex }})"
  class="bg-forest hover:bg-forest-dark mt-3 rounded-full px-3 py-1.5 text-xs font-bold text-white"
>
  + Tambah Kolom
</button>
