{{--
    _card-layout-editor.blade.php (v2 - lengkap dengan UI Warna Visual)
--}}
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

      @foreach (($col['children'] ?? []) as $elIndex => $el)
        @php
          $elPath = "content.{$blockId}.data.cards.{$cIndex}.layout.children.{$colIdx}.children.{$elIndex}";
          $style = $el['data']['style'] ?? [];
        @endphp
        <div wire:key="el-{{ $blockId }}-{{ $cIndex }}-{{ $col['id'] }}-{{ $el['id'] }}" class="group relative mb-3 rounded-lg bg-white p-4 shadow-sm">
          <button type="button" x-on:click="$wire.removeElementFromColumn('{{ $blockId }}', {{ $cIndex }}, '{{ $col['id'] }}', '{{ $el['id'] }}')" class="absolute -top-2 -right-2 rounded-full bg-red-100 p-1 text-red-600 opacity-0 shadow-sm transition-opacity outline-none group-hover:opacity-100">
            <x-dynamic-component component="lucide-x" class="h-3 w-3" />
          </button>

          {{-- ================= TEKS ================= --}}
          @if (($el['elementType'] ?? 'text') === 'text')
            @php
              $isPill = $style['is_pill'] ?? false;
              $size = $style['size'] ?? 'text-[13px]';
              $weight = $style['weight'] ?? 'font-normal';
              $pillBg = $style['pill_bg'] ?? 'bg-goldy-soft';
              $pillRadius = $style['pill_radius'] ?? 'rounded-md';
              $textColor = $style['color'] ?? 'text-ink-soft';
              $margin = $style['margin'] ?? 'mb-0';
              // 🌟 MAPPING DAFTAR FONT YANG DIIZINKAN KE KELAS TAILWIND
              $font = $style['font'] ?? 'font-fraunces';
              $fontOptions = [
                  'font-arial'    => 'Arial',
                  'font-fraunces' => 'Fraunces',
                  'font-times'    => 'Times New Roman',
                  'font-roboto'   => 'Roboto',
                  'font-jetbrains'=> 'JetBrains Mono',
                  'font-opensans' => 'Open Sans',
                  'font-jakarta'  => 'Plus Jakarta Sans',
              ];
              $currentFontName = $fontOptions[$font] ?? 'Fraunces';
            @endphp
            <div class="mb-2 flex items-center justify-between">
              <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded {{ $isPill ? 'bg-goldy-soft text-goldy-dark' : 'bg-foresty/10 text-foresty' }}">{{ $isPill ? 'Lencana (Pill)' : 'Teks' }}</span>
              <label class="flex cursor-pointer items-center gap-1.5">
                <input type="checkbox" wire:model.live="{{ $elPath }}.data.style.is_pill" class="text-foresty focus:ring-foresty h-3.5 w-3.5 rounded border-gray-300" />
                <span class="text-[10px] font-bold text-gray-500 uppercase">Mode Pill</span>
              </label>
            </div>

            <textarea rows="2" wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.{{ $code }}" placeholder="Ketik isi teks di sini..." class="focus:ring-foresty mb-2 p-2 w-full resize-none rounded-lg border-gray-200 text-sm font-semibold shadow-sm"></textarea>

            <div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
              @if (!$isPill)
                {{-- Font & Weight --}}
                {{-- <div class="flex flex-col gap-1.5">
                  <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Tipe Font</span>
                  <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.font', 'font-sans')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $font === 'font-sans' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Sistem</button>
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.font', 'font-display')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $font === 'font-display' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Display</button>
                  </div>
                </div> --}}
                {{-- Font Custom Dropdown --}}
                <div class="flex flex-col gap-1.5">
                  <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Tipe Font</span>

                  <div class="relative w-36" x-data="{ openFont: false }">
                    {{-- Tombol Utama --}}
                    <button
                      type="button"
                      x-on:click="openFont = !openFont"
                      class="flex w-full items-center justify-between rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-[11px] shadow-sm transition-colors hover:border-foresty focus:outline-none"
                    >
                      {{-- Teks tombol utama menggunakan inline-style agar persis dengan font terpilih --}}
                      <span class="truncate" style="font-family: '{{ $currentFontName }}', sans-serif;">
                        {{ $currentFontName }}
                      </span>
                      <x-dynamic-component component="lucide-chevron-down" class="h-3 w-3 shrink-0 text-gray-400" />
                    </button>

                    {{-- Menu Melayang (Dropdown) --}}
                    <div
                      x-show="openFont"
                      x-on:click.outside="openFont = false"
                      x-cloak
                      class="absolute left-0 z-50 mt-1 max-h-48 w-full overflow-y-auto rounded-md border border-gray-200 bg-white p-1 shadow-lg scrollbar-thin"
                    >
                      @foreach ($fontOptions as $fClass => $fName)
                        <button
                          type="button"
                          x-on:click="$wire.set('{{ $elPath }}.data.style.font', '{{ $fClass }}'); openFont = false"
                          class="group flex w-full items-center rounded-sm px-2 py-1.5 text-left transition-colors {{ $font === $fClass ? 'bg-sage-soft text-foresty font-bold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
                        >
                          {{-- Teks menu menggunakan inline-style agar admin langsung melihat pratinjau bentuk asli font-nya --}}
                          <span class="text-[11px] truncate transition-transform group-hover:scale-105 origin-left" style="font-family: '{{ $fName }}', sans-serif;">
                            {{ $fName }}
                          </span>

                          @if ($font === $fClass)
                            <x-dynamic-component component="lucide-check" class="ml-auto h-3 w-3 text-foresty shrink-0" stroke-width="3" />
                          @endif
                        </button>
                      @endforeach
                    </div>
                  </div>
                </div>

                <div class="flex flex-col gap-1.5">
                  <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Ketebalan</span>
                  <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.weight', 'font-normal')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $weight === 'font-normal' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Reguler</button>
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.weight', 'font-semibold')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $weight === 'font-semibold' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Semi Bold</button>
                  </div>
                </div>

                <div class="flex flex-col gap-1.5">
                  <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Ukuran</span>
                  <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'text-[13px]')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $size === 'text-[13px]' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Kecil</button>
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'text-[15px]')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $size === 'text-[15px]' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Normal</button>
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'text-[21px]')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $size === 'text-[21px]' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Besar (H3)</button>
                  </div>
                </div>
              @else
                {{-- Mode Pill Options --}}
                <div class="flex flex-col gap-1.5">
                  <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Warna Latar Pill</span>
                  <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                    <button type="button" title="Goldy" x-on:click="$wire.set('{{ $elPath }}.data.style.pill_bg', 'bg-goldy-soft')" class="rounded p-1.5 transition-all outline-none {{ $pillBg === 'bg-goldy-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                      <div class="h-4 w-4 rounded-full bg-[#fde68a] shadow-sm"></div>
                    </button>
                    <button type="button" title="Mist" x-on:click="$wire.set('{{ $elPath }}.data.style.pill_bg', 'bg-mist')" class="rounded p-1.5 transition-all outline-none {{ $pillBg === 'bg-mist' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                      <div class="h-4 w-4 rounded-full bg-gray-200 border border-gray-300 shadow-sm"></div>
                    </button>
                    <button type="button" title="Sage" x-on:click="$wire.set('{{ $elPath }}.data.style.pill_bg', 'bg-sage-soft')" class="rounded p-1.5 transition-all outline-none {{ $pillBg === 'bg-sage-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                      <div class="h-4 w-4 rounded-full bg-[#dcfce7] shadow-sm"></div>
                    </button>
                  </div>
                </div>

                <div class="flex flex-col gap-1.5">
                  <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Bentuk Pill</span>
                  <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.pill_radius', 'rounded-md')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $pillRadius === 'rounded-md' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Bulat Sedikit</button>
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.pill_radius', 'rounded-full')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $pillRadius === 'rounded-full' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Lingkaran</button>
                  </div>
                </div>
              @endif

              {{-- Shared Options (Color & Margin) --}}
              <div class="flex flex-col gap-1.5">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Warna Teks</span>
                <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                  <button type="button" title="Abu Gelap" x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-ink-soft')" class="rounded p-1.5 transition-all outline-none {{ $textColor === 'text-ink-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full bg-gray-700 shadow-sm"></div>
                  </button>
                  <button type="button" title="Foresty" x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-foresty')" class="rounded p-1.5 transition-all outline-none {{ $textColor === 'text-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full bg-foresty shadow-sm"></div>
                  </button>
                  <button type="button" title="Coral" x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-coral')" class="rounded p-1.5 transition-all outline-none {{ $textColor === 'text-coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full bg-coral shadow-sm"></div>
                  </button>
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Jarak Bawah</span>
                <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                  <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.margin', 'mb-0')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $margin === 'mb-0' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">0px</button>
                  <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.margin', 'mb-2')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $margin === 'mb-2' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Kecil</button>
                  <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.margin', 'mb-4')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $margin === 'mb-4' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Sedang</button>
                </div>
              </div>
            </div>

            {{-- ================= IKON ================= --}}
          @elseif (($el['elementType'] ?? 'icon') === 'icon')
            @php
              $iconBg = $style['bg'] ?? 'bg-goldy-soft';
              $iconColor = $style['color'] ?? 'text-foresty';
              $iconSize = $style['size'] ?? 'w-10 h-10';
              $iconRadius = $style['radius'] ?? 'rounded-[14px]';
            @endphp
            <div class="mb-2 flex items-center justify-between">
              <span class="bg-foresty rounded px-2 py-0.5 text-[10px] font-extrabold tracking-widest text-white uppercase">Ikon</span>
            </div>

            <div class="relative mb-2" x-data="{ openPicker: false, search: '' }">
              <button type="button" x-on:click="openPicker = !openPicker" class="hover:border-foresty flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs shadow-sm transition-colors focus:outline-none">
                <div class="flex items-center gap-2 truncate">
                  <x-dynamic-component :component="'lucide-' . ($el['data']['content']['icon'] ?: 'box')" class="text-foresty h-5 w-5 shrink-0" stroke-width="2.5" />
                  <span class="truncate font-mono text-[11px] font-bold text-gray-700 uppercase">{{ $el['data']['content']['icon'] ?: 'PILIH IKON...' }}</span>
                </div>
                <x-dynamic-component component="lucide-chevron-down" class="h-4 w-4 shrink-0 text-gray-400" />
              </button>

              <div x-show="openPicker" x-on:click.outside="openPicker = false" x-cloak class="absolute left-0 z-50 mt-1 flex w-full flex-col gap-2 rounded-xl border border-gray-200 bg-white p-3 shadow-xl sm:w-64">
                <div class="relative">
                  <x-dynamic-component component="lucide-search" class="absolute top-2.5 left-3 h-4 w-4 text-gray-400" />
                  <input type="text" x-model="search" placeholder="Cari ikon..." class="focus:ring-foresty focus:border-foresty w-full rounded-lg border border-gray-200 py-2 pr-2 pl-9 text-xs shadow-sm" />
                </div>
                <div class="grid max-h-48 scrollbar-thin grid-cols-5 gap-1.5 overflow-y-auto p-1">
                  @foreach ($iconsList as $iconName)
                    <button type="button" x-show="'{{ $iconName }}'.includes(search.toLowerCase())" x-on:click="$wire.set('{{ $elPath }}.data.content.icon', '{{ $iconName }}'); openPicker = false; search = '';" class="p-2.5 rounded-lg flex items-center justify-center transition-all duration-200 border {{ ($el['data']['content']['icon'] ?? '') === $iconName ? 'bg-sage-soft text-foresty border-foresty shadow-sm scale-110' : 'bg-gray-50 text-gray-400 border-transparent hover:border-foresty/50 hover:text-foresty' }}">
                      <x-dynamic-component :component="'lucide-' . $iconName" class="h-5 w-5 shrink-0" stroke-width="2" />
                    </button>
                  @endforeach
                </div>
              </div>
            </div>

            <div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
              <div class="flex flex-col gap-1.5">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Latar Ikon</span>
                <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                  <button type="button" title="Goldy" x-on:click="$wire.set('{{ $elPath }}.data.style.bg', 'bg-goldy-soft')" class="rounded p-1.5 transition-all outline-none {{ $iconBg === 'bg-goldy-soft' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full bg-[#fde68a] shadow-sm"></div>
                  </button>
                  <button type="button" title="Mist" x-on:click="$wire.set('{{ $elPath }}.data.style.bg', 'bg-mist')" class="rounded p-1.5 transition-all outline-none {{ $iconBg === 'bg-mist' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full bg-gray-200 border border-gray-300 shadow-sm"></div>
                  </button>
                  <button type="button" title="Transparan" x-on:click="$wire.set('{{ $elPath }}.data.style.bg', 'bg-transparent')" class="rounded p-1.5 transition-all outline-none {{ $iconBg === 'bg-transparent' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="relative h-4 w-4 overflow-hidden rounded-full border border-gray-300 bg-white shadow-sm">
                      <div class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"></div>
                    </div>
                  </button>
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Warna Ikon</span>
                <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                  <button type="button" title="Foresty" x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-foresty')" class="rounded p-1.5 transition-all outline-none {{ $iconColor === 'text-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full bg-emerald-700 shadow-sm"></div>
                  </button>
                  <button type="button" title="Coral" x-on:click="$wire.set('{{ $elPath }}.data.style.color', 'text-coral')" class="rounded p-1.5 transition-all outline-none {{ $iconColor === 'text-coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full bg-orange-500 shadow-sm"></div>
                  </button>
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Ukuran</span>
                <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                  <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-10 h-10')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $iconSize === 'w-10 h-10' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Standar</button>
                  <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-16 h-16')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $iconSize === 'w-16 h-16' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Besar</button>
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Sudut Ikon</span>
                <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                  <button type="button" title="Agak Bulat" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-[14px]')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $iconRadius === 'rounded-[14px]' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-md border-2 border-current"></div>
                  </button>
                  <button type="button" title="Lingkaran" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-full')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $iconRadius === 'rounded-full' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full border-2 border-current"></div>
                  </button>
                </div>
              </div>
            </div>
          {{-- ================= AKORDION / FAQ ================= --}}
          @elseif (($el['elementType'] ?? '') === 'accordion')
            @php
              $accTheme = $style['theme'] ?? 'foresty';
            @endphp
            <div class="mb-2 flex items-center justify-between">
              <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded bg-blue-100 text-blue-700">FAQ / Akordion</span>
            </div>

            {{-- <!-- Input Pertanyaan -->
            <input
              type="text"
              wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.question.{{ $code }}"
              placeholder="Tulis pertanyaan di sini..."
              class="focus:ring-blue-500 mb-2 w-full rounded-lg border-gray-200 p-2 text-sm font-bold shadow-sm"
            />

            <!-- Input Jawaban -->
            <textarea
              rows="3"
              wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.answer.{{ $code }}"
              placeholder="Tulis jawaban di sini..."
              class="focus:ring-blue-500 mb-2 p-2 w-full resize-none rounded-lg border-gray-200 text-sm shadow-sm"
            ></textarea> --}}
            {{-- Input Pertanyaan & Jawaban (Multi-Language) --}}
            <div class="mb-3 space-y-2">
              {{-- Input Pertanyaan --}}
              <div class="relative">
                <x-dynamic-component component="lucide-message-circle-question" class="absolute top-2.5 left-3 h-4 w-4 text-gray-400" />
                <input
                  type="text"
                  wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.question.{{ $code }}"
                  placeholder="Ketik pertanyaan di sini ({{ strtoupper($code) }})..."
                  class="focus:ring-foresty focus:border-foresty w-full rounded-lg border-gray-200 p-2 pl-9 text-xs font-bold shadow-sm"
                />
              </div>

              {{-- Input Jawaban --}}
              <div class="relative">
                <x-dynamic-component component="lucide-message-square-text" class="absolute top-3 left-3 h-4 w-4 text-gray-400" />
                <textarea
                  rows="3"
                  wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.answer.{{ $code }}"
                  placeholder="Ketik jawaban lengkap di sini ({{ strtoupper($code) }})..."
                  class="focus:ring-foresty focus:border-foresty w-full resize-none rounded-lg border-gray-200 p-2 pl-9 text-xs shadow-sm"
                ></textarea>
              </div>
            </div>

            <div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
              {{-- Warna Aksen (Untuk teks aktif & latar ikon) --}}
              <div class="flex flex-col gap-1.5">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Warna Aksen Teks & Ikon</span>
                <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                  <button type="button" title="Foresty" x-on:click="$wire.set('{{ $elPath }}.data.style.theme', 'foresty')" class="rounded p-1.5 transition-all outline-none {{ $accTheme === 'foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full bg-emerald-700 shadow-sm"></div>
                  </button>
                  <button type="button" title="Coral" x-on:click="$wire.set('{{ $elPath }}.data.style.theme', 'coral')" class="rounded p-1.5 transition-all outline-none {{ $accTheme === 'coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full bg-orange-500 shadow-sm"></div>
                  </button>
                  <button type="button" title="Gelap" x-on:click="$wire.set('{{ $elPath }}.data.style.theme', 'dark')" class="rounded p-1.5 transition-all outline-none {{ $accTheme === 'dark' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                    <div class="h-4 w-4 rounded-full bg-gray-800 shadow-sm"></div>
                  </button>
                </div>
              </div>
            </div>

            {{-- ================= FOTO PROFIL ================= --}}
            @elseif (($el['elementType'] ?? '') === 'profile_photo')
              @php
                $imgUrl = $el['data']['content']['url'] ?? '';
                $imgAlt = $el['data']['content']['alt'] ?? '';
                $imgSize = $style['size'] ?? 'w-20 h-20';
                $imgRadius = $style['radius'] ?? 'rounded-full';
                $imgBorder = $style['border'] ?? 'border-0';
                $imgBorderColor = $style['border_color'] ?? 'border-transparent';
              @endphp
              <div class="mb-3 flex items-center justify-between">
                <span class="bg-indigo-100 text-indigo-700 rounded px-2 py-0.5 text-[10px] font-extrabold tracking-widest uppercase">Foto Profil</span>
              </div>

              {{-- Area Pratinjau & Input Data --}}
              <div class="mb-3 flex items-start gap-3">
                {{-- Kotak Pratinjau (Live Preview) --}}
                <div class="shrink-0 flex items-center justify-center bg-gray-100 object-cover shadow-sm transition-all {{ $imgSize }} {{ $imgRadius }} {{ $imgBorder }} {{ $imgBorderColor }}">
                  @if($imgUrl)
                    <img src="{{ $imgUrl }}" class="h-full w-full object-cover {{ $imgRadius }}" alt="Preview">
                  @else
                    <x-dynamic-component component="lucide-image" class="h-6 w-6 text-gray-400" />
                  @endif
                </div>

                {{-- Input URL & Alt Text --}}
                <div class="flex-1 space-y-2">
                  {{-- Opsi 1: Tombol Upload (Via Endpoint API Controller Anda) --}}
                  <div x-data="{ isUploading: false }" class="w-full">
                    <label
                      class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-xs font-bold shadow-sm transition-colors"
                      x-bind:class="isUploading ? 'cursor-wait bg-gray-200 text-gray-400' : 'hover:border-foresty hover:bg-sage-soft hover:text-foresty text-gray-600'"
                    >
                      {{-- Ikon: Berubah jadi spinner saat loading --}}
                      <x-dynamic-component component="lucide-upload-cloud" class="h-4 w-4 shrink-0" x-show="!isUploading" />
                      <x-dynamic-component component="lucide-loader-2" class="h-4 w-4 shrink-0 animate-spin" x-show="isUploading" x-cloak />

                      {{-- Teks: Berubah saat proses upload --}}
                      <span class="truncate" x-text="isUploading ? 'Mengunggah...' : 'Unggah Foto dari Komputer'"></span>

                      <input
                        type="file"
                        accept="image/png, image/jpeg, image/webp, image/gif"
                        class="hidden"
                        :disabled="isUploading"
                        x-on:change="
                          const file = $event.target.files[0];
                          if(!file) return;

                          isUploading = true;

                          // Siapkan data untuk dikirim ke Controller
                          let formData = new FormData();
                          formData.append('image', file);
                          formData.append('_token', '{{ csrf_token() }}'); // Wajib untuk Laravel POST

                          // Kirim ke route yang Anda buat
                          fetch('{{ route('editor.upload-image') }}', {
                            method: 'POST',
                            body: formData
                          })
                          .then(res => {
                            if(!res.ok) throw new Error('Gagal mengunggah gambar.');
                            return res.json();
                          })
                          .then(data => {
                            // Update Livewire dengan URL final dari server
                            $wire.set('{{ $elPath }}.data.content.url', data.url);
                          })
                          .catch(err => {
                            alert(err.message);
                          })
                          .finally(() => {
                            isUploading = false;
                            $event.target.value = ''; // Reset input agar bisa upload file yang sama lagi jika perlu
                          });
                        "
                      />
                    </label>
                  </div>
                  <input
                    type="text"
                    wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.alt"
                    placeholder="Teks Alternatif (Untuk SEO & Tunanetra)"
                    class="focus:ring-foresty focus:border-foresty w-full rounded-lg border-gray-200 p-2 text-xs shadow-sm"
                  />
                </div>
              </div>

              {{-- Pengaturan Gaya Visual --}}
              <div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
                {{-- Ukuran --}}
                <div class="flex flex-col gap-1.5">
                  <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Ukuran</span>
                  <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-12 h-12')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgSize === 'w-12 h-12' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Kecil</button>
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-20 h-20')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgSize === 'w-20 h-20' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Sedang</button>
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.size', 'w-32 h-32')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgSize === 'w-32 h-32' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Besar</button>
                  </div>
                </div>

                {{-- Bentuk --}}
                <div class="flex flex-col gap-1.5">
                  <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Bentuk</span>
                  <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                    <button type="button" title="Kotak" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-md')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $imgRadius === 'rounded-md' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                      <div class="h-4 w-4 rounded-md border-2 border-current"></div>
                    </button>
                    <button type="button" title="Agak Bulat" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-[20px]')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $imgRadius === 'rounded-[20px]' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                      <div class="h-4 w-4 rounded-[8px] border-2 border-current"></div>
                    </button>
                    <button type="button" title="Lingkaran" x-on:click="$wire.set('{{ $elPath }}.data.style.radius', 'rounded-full')" class="text-gray-400 hover:text-gray-700 rounded p-1.5 transition-all outline-none {{ $imgRadius === 'rounded-full' ? 'bg-white !text-foresty shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                      <div class="h-4 w-4 rounded-full border-2 border-current"></div>
                    </button>
                  </div>
                </div>

                {{-- Ketebalan Garis --}}
                <div class="flex flex-col gap-1.5">
                  <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Garis Tepi</span>
                  <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-0')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgBorder === 'border-0' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tanpa Garis</button>
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-2')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgBorder === 'border-2' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tipis (2px)</button>
                    <button type="button" x-on:click="$wire.set('{{ $elPath }}.data.style.border', 'border-4')" class="rounded px-2.5 py-1 text-[10px] font-bold transition-all outline-none {{ $imgBorder === 'border-4' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Tebal (4px)</button>
                  </div>
                </div>

                {{-- Warna Garis --}}
                <div class="flex flex-col gap-1.5">
                  <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Warna Garis</span>
                  <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
                    <button type="button" title="Transparan" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-transparent')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-transparent' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                      <div class="relative h-4 w-4 overflow-hidden rounded-full border border-gray-300 bg-white shadow-sm">
                        <div class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"></div>
                      </div>
                    </button>
                    <button type="button" title="Foresty" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-foresty')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                      <div class="h-4 w-4 rounded-full bg-emerald-700 shadow-sm"></div>
                    </button>
                    <button type="button" title="Coral" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-coral')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                      <div class="h-4 w-4 rounded-full bg-orange-500 shadow-sm"></div>
                    </button>
                    <button type="button" title="Abu-abu" x-on:click="$wire.set('{{ $elPath }}.data.style.border_color', 'border-gray-200')" class="rounded p-1.5 transition-all outline-none {{ $imgBorderColor === 'border-gray-200' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
                      <div class="h-4 w-4 rounded-full bg-gray-200 border border-gray-300 shadow-sm"></div>
                    </button>
                  </div>
                </div>
              </div>
          @endif
        </div>
      @endforeach

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
