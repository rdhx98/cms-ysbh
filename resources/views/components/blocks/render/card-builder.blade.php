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

<div class="grid gap-6 {{ $gridClass }} {{ $marginBottom }}">
  @foreach ($cards as $card)
    @php
      // 2. PENGATURAN KONTAINER KARTU
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
              'transition-all duration-300 relative group flex flex-col h-full',
          ]),
      );

      $url = $c['url'] ?? '';
      $isLink = !empty($url);
      $Tag = $isLink ? 'a' : 'div';
      $href = $isLink ? 'href="' . $url . '"' : '';
    @endphp

    <{{ $Tag }} {!! $href !!} class="{{ $classes }}">

      {{-- 3. PERCABANGAN BLUEPRINT LAYOUT --}}
      @if (($card['blueprint'] ?? 'stack') === 'stack')
        {{-- A. LAYOUT TUMPUK (STACK) --}}
        <div class="flex flex-col w-full h-full">
          @foreach ($card['slots']['main'] ?? [] as $el)
            @if (in_array($el['type'], ['text', 'icon']))
              @include('components.blocks.render.partials._atomic-' . $el['type'], [
                  'el' => $el,
                  'lang' => $lang,
                  'isPreview' => $isPreview,
              ])
            @endif
          @endforeach
        </div>
      @else
        {{-- B. LAYOUT DOKUMEN (MEDIA OBJECT / 3 KOLOM) --}}
        <div class="flex gap-4 items-start w-full h-full">

          {{-- Area Kiri --}}
          @if (!empty($card['slots']['left']))
            <div class="shrink-0 flex flex-col">
              @foreach ($card['slots']['left'] as $el)
                @if (in_array($el['type'], ['text', 'icon']))
                  @include('components.blocks.render.partials._atomic-' . $el['type'], [
                      'el' => $el,
                      'lang' => $lang,
                      'isPreview' => $isPreview,
                  ])
                @endif
              @endforeach
            </div>
          @endif

          {{-- Area Tengah --}}
          <div class="flex-1 flex flex-col min-w-0">
            @foreach ($card['slots']['middle'] ?? [] as $el)
              @if (in_array($el['type'], ['text', 'icon']))
                @include('components.blocks.render.partials._atomic-' . $el['type'], [
                    'el' => $el,
                    'lang' => $lang,
                    'isPreview' => $isPreview,
                ])
              @endif
            @endforeach
          </div>

          {{-- Area Kanan --}}
          @if (!empty($card['slots']['right']))
            <div class="shrink-0 flex flex-col">
              @foreach ($card['slots']['right'] as $el)
                @if (in_array($el['type'], ['text', 'icon']))
                  @include('components.blocks.render.partials._atomic-' . $el['type'], [
                      'el' => $el,
                      'lang' => $lang,
                      'isPreview' => $isPreview,
                  ])
                @endif
              @endforeach
            </div>
          @endif
        </div>
      @endif

      </{{ $Tag }}>
  @endforeach
</div>
