@props (['data', 'lang' => 'id', 'isPreview' => false])

@php
    $gridCols = $data['grid']['cols'] ?? 3;
    $gridMargin = $data['grid']['margin_bottom'] ?? 'mb-8';
    $cards = $data['cards'] ?? [];
@endphp

<div
  class="grid grid-cols-1 md:grid-cols-{{ $gridCols }} gap-4 lg:gap-6 {{ $gridMargin }}"
>
  @foreach ($cards as $card)
    @php
            $container = $card['container'] ?? [];
            $bg = $container['bg'] ?? 'bg-white';
            // $border = $container['border'] ?? 'border border-gray-200';
            $borderWidth = $container['border_width'] ?? 'border';
            $borderStyle = $container['border_style'] ?? 'border-solid';
            $borderColor = $container['border_color'] ?? 'border-gray-200';
            $radius = $container['radius'] ?? 'rounded-[18px]';
            $padding = $container['padding'] ?? 'p-4';
            // $alignY = $container['align_y'] ?? 'justify-start';
            $alignY = $container['align_y'] ?? 'items-start';

            // Matikan efek hover dan link jika sedang di dalam Live Preview Editor
            $hover = $isPreview ? '' : ($container['hover'] ?? 'hover:-translate-y-1 hover:shadow-md transition-all duration-300');
            $url = $isPreview ? '' : ($container['url'] ?? '');

            $columns = $card['layout']['children'] ?? [];
            $totalElements = collect($columns)->sum(function($col) {
                return count($col['children'] ?? []);
            });
            // Terjemahkan array lebar kolom menjadi pecahan grid (contoh: "1fr 2fr 1fr")
            // $gridTemplate = collect($columns)->map(fn($c) => ($c['width'] ?? 1) . 'fr')->implode(' ');
            $gridTemplate = collect($columns)->map(function($c) {
                $w = $c['width'] ?? 1;
                return $w === 'auto' ? 'auto' : $w . 'fr';
            })->implode(' ');
        @endphp

    <{{ $url ? 'a' : 'div' }}
      {!! $url ? 'href="' . htmlspecialchars($url) . '"' : '' !!}
      class="block transform-gpu transition-all duration-500 ease-in-out h-full overflow-hidden {{ $bg }} {{ $borderWidth }} {{ $borderStyle }} {{ $borderColor }} {{ $radius }} {{ $hover }}"
    >
      {{-- <div class="{{ $padding }} h-full flex flex-col min-w-0"> --}}
        <div class="{{ $padding }} h-full flex flex-col min-w-0">
        {{-- 🌟 PEMBUNGKUS BARIS (ROW) --}}
          <div
            class="grid {{ $alignY }} gap-4 lg:gap-5"
            style="grid-template-columns: {{ $gridTemplate }};"
          >
          @if ($totalElements === 0 && $isPreview)
          <div class="col-span-full flex w-full flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-white/50 py-10 px-4 text-center opacity-80 mix-blend-luminosity">
            <x-dynamic-component component="lucide-layout" class="mb-3 h-8 w-8 text-gray-400" stroke-width="1.5" />
            <span class="text-[11px] font-extrabold tracking-widest text-gray-400 uppercase">Kartu Masih Kosong</span>
            <span class="mt-1 text-[10px] font-semibold text-gray-400">Tambahkan elemen Teks atau Ikon melalui editor</span>
          </div>
        @endif
            @foreach ($columns as $col)
              {{-- 🌟 PEMBUNGKUS KOLOM (COLUMN) --}}
              <div class="flex min-w-0 flex-col [&>*:last-child]:mb-0!">

                @foreach ($col['children'] ?? [] as $el)
                  @php
                    $type = $el['elementType'] ?? 'text';
                    $style = $el['data']['style'] ?? [];
                    $content = $el['data']['content'] ?? [];
                  @endphp

                  {{-- 1. RENDER TEKS --}}
                  @if ($type === 'text')
                    @php
                      $text = $content[$lang] ?? '';
                      $isPill = $style['is_pill'] ?? false;
                      $margin = $style['margin'] ?? 'mb-1.5';
                      $color = $style['color'] ?? 'text-ink-soft';
                    @endphp

                    @if (!empty(trim($text)))
                      @if ($isPill)
                        @php
                                                  $pillBg = $style['pill_bg'] ?? 'bg-goldy-soft';
                                                  $pillRadius = $style['pill_radius'] ?? 'rounded-md';
                                              @endphp
                        <div class="{{ $margin }}">
                          <span
                            class="inline-block {{ $pillBg }} {{ $color }} {{ $pillRadius }} px-2.5 py-1 text-[11px] font-bold uppercase tracking-widest"
                          >
                            {{ $text }}
                          </span>
                        </div>
                      @else
                        @php
                                                  $font = $style['font'] ?? 'font-sans';
                                                  $size = $style['size'] ?? 'text-[15px]';
                                                  $weight = $style['weight'] ?? 'font-normal';
                                              @endphp
                        <div
                          class="{{ $font }} {{ $size }} {{ $weight }} {{ $color }} {{ $margin }}"
                        >
                          {{ $text }}
                        </div>
                      @endif
                    @endif

                    {{-- 2. RENDER IKON --}}
                  @elseif ($type === 'icon')
                    @php
                                          $icon = $content['icon'] ?? '';
                                          $iconBg = $style['bg'] ?? 'bg-mist';
                                          $iconColor = $style['color'] ?? 'text-foresty';
                                          $iconSize = $style['size'] ?? 'w-10 h-10';
                                          $iconRadius = $style['radius'] ?? 'rounded-[14px]';
                                      @endphp

                    @if ($icon)
                      {{-- Ikon akan punya margin bawah default 12px (mb-3) kecuali disetel lain --}}
                      <div
                        class="shrink-0 flex items-center justify-center {{ $iconBg }} {{ $iconColor }} {{ $iconSize }} {{ $iconRadius }} mb-3"
                      >
                        <x-dynamic-component
                          :component="'lucide-' . $icon"
                          class="h-1/2 w-1/2"
                          stroke-width="2"
                        />
                      </div>
                    @endif

                  @endif
                @endforeach
              </div>
            @endforeach
          </div>
      </div>
    </{{ $url ? 'a' : 'div' }}>
  @endforeach
</div>
