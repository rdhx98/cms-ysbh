@props(['data' => [], 'lang' => 'id', 'isPreview' => false])

@php
  // 1. PENGATURAN GRID & MARGIN UTAMA
  $gridCols = $data['grid']['cols'] ?? 3;
  $gridClass = match ((int) $gridCols) {
      1 => 'grid-cols-1',
      2 => 'grid-cols-1 md:grid-cols-2',
      3 => 'grid-cols-1 md:grid-cols-3',
      4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
      default => 'grid-cols-1 md:grid-cols-3',
  };
  $marginBottom = $data['grid']['margin_bottom'] ?? 'mb-8';
  $cards = $data['cards'] ?? [];
@endphp

{{-- A. JIKA BELUM ADA KARTU SAMA SEKALI --}}
@if ($isPreview && empty($cards))
  <div class="w-full border-2 border-dashed border-gray-300 rounded-xl p-8 flex flex-col items-center justify-center text-gray-400 {{ $marginBottom }} bg-gray-50/50">
    <x-dynamic-component component="lucide-layout-template" class="w-8 h-8 mb-2 opacity-50" />
    <span class="text-xs font-bold uppercase tracking-widest">Preview: Belum Ada Kartu</span>
  </div>
@else
  {{-- B. JIKA SUDAH ADA KARTU --}}
  <div class="grid gap-6 {{ $gridClass }} {{ $marginBottom }}">
    @foreach ($cards as $card)
      @php
        // PENGATURAN KONTAINER KARTU
        $c = $card['container'] ?? [];
        $classes = implode(
            ' ',
            array_filter([
                $c['bg'] ?? 'bg-white',
                $c['border'] ?? 'border border-gray-200',
                $c['radius'] ?? 'rounded-[18px]',
                $c['padding'] ?? 'p-5',
                $c['shadow'] ?? 'shadow-sm',
                $c['hover'] ?? '',
                'transition-all duration-300 relative group flex flex-col h-full min-h-[120px]', // min-h-[120px] mencegah kartu kempes
            ]),
        );

        $url = $c['url'] ?? '';
        $isLink = !empty($url) && !$isPreview; // Matikan link saat preview agar tidak terpencet
        $Tag = $isLink ? 'a' : 'div';
        $href = $isLink ? 'href="' . $url . '"' : '';
      @endphp

      <{{ $Tag }} {!! $href !!} class="{{ $classes }}">

        {{-- PERCABANGAN BLUEPRINT LAYOUT --}}
        @if (($card['blueprint'] ?? 'stack') === 'stack')
          {{-- LAYOUT TUMPUK (STACK) --}}
          <div class="flex flex-col w-full h-full gap-2">
            @if ($isPreview && empty($card['slots']['main']))
              {{-- Placeholder Slot Utama --}}
              <div class="flex-1 border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center bg-gray-50">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Area Konten</span>
              </div>
            @else
              @foreach ($card['slots']['main'] ?? [] as $el)
                @if (in_array($el['type'], ['text', 'icon']))
                  @include('components.blocks.render.partials._atomic-' . $el['type'], ['el' => $el, 'lang' => $lang, 'isPreview' => $isPreview])
                @endif
              @endforeach
            @endif
          </div>
        @else
          {{-- LAYOUT DOKUMEN (MEDIA OBJECT) --}}
          <div class="flex gap-4 items-start w-full h-full">

            {{-- Area Kiri --}}
            @if (!empty($card['slots']['left']))
              <div class="shrink-0 flex flex-col">
                @foreach ($card['slots']['left'] as $el)
                  @if (in_array($el['type'], ['text', 'icon']))
                    @include('components.blocks.render.partials._atomic-' . $el['type'], ['el' => $el, 'lang' => $lang, 'isPreview' => $isPreview])
                  @endif
                @endforeach
              </div>
            @elseif($isPreview)
              {{-- Placeholder Area Kiri --}}
              <div class="shrink-0 w-12 h-12 border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center bg-gray-50">
                <x-dynamic-component component="lucide-image" class="w-4 h-4 text-gray-300" />
              </div>
            @endif

            {{-- Area Tengah --}}
            <div class="flex-1 flex flex-col min-w-0 h-full">
              @if ($isPreview && empty($card['slots']['middle']))
                {{-- Placeholder Area Tengah --}}
                <div class="flex-1 h-full min-h-[48px] border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center bg-gray-50">
                  <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Konten Utama</span>
                </div>
              @else
                @foreach ($card['slots']['middle'] ?? [] as $el)
                  @if (in_array($el['type'], ['text', 'icon']))
                    @include('components.blocks.render.partials._atomic-' . $el['type'], ['el' => $el, 'lang' => $lang, 'isPreview' => $isPreview])
                  @endif
                @endforeach
              @endif
            </div>

            {{-- Area Kanan --}}
            @if (!empty($card['slots']['right']))
              <div class="shrink-0 flex flex-col">
                @foreach ($card['slots']['right'] as $el)
                  @if (in_array($el['type'], ['text', 'icon']))
                    @include('components.blocks.render.partials._atomic-' . $el['type'], ['el' => $el, 'lang' => $lang, 'isPreview' => $isPreview])
                  @endif
                @endforeach
              </div>
            @elseif($isPreview)
              {{-- Placeholder Area Kanan --}}
              <div class="shrink-0 w-8 h-8 border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center bg-gray-50">
                <x-dynamic-component component="lucide-chevron-right" class="w-4 h-4 text-gray-300" />
              </div>
            @endif

          </div>
        @endif

        </{{ $Tag }}>
    @endforeach
  </div>
@endif
