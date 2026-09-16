@props(['data' => [], 'lang' => 'id', 'isPreview' => false])

@php
  // 1. Tangkap Pengaturan Grid
  $grid = $data['grid'] ?? ['cols' => 3, 'margin_bottom' => 'mb-8'];
  $cols = (int) ($grid['cols'] ?? 3);
  $cards = $data['cards'] ?? [];

  $gridClass = match ($cols) {
      1 => 'grid-cols-1',
      2 => 'grid-cols-1 md:grid-cols-2',
      4 => 'grid-cols-2 md:grid-cols-4',
      default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
  };
@endphp

@if (count($cards) > 0)
  <div class="grid {{ $gridClass }} {{ $grid['margin_bottom'] }} gap-6 w-full relative">
    @foreach ($cards as $card)
      @php
        $blueprint = $card['blueprint'] ?? 'stack';
        $c = $card['container'] ?? [];

        // 2. Susun kelas Tailwind untuk Kontainer Kartu
        $containerClasses = collect([
            $c['bg'] ?? 'bg-white',
            $c['border'] ?? 'border border-foresty/15',
            $c['radius'] ?? 'rounded-[18px]',
            $c['padding'] ?? 'p-6',
            $c['shadow'] ?? 'shadow-sm',
            $c['hover'] ?? 'hover:-translate-y-1',
            'transition-all duration-300 group flex flex-col', // Kelas wajib
        ])
            ->filter()
            ->implode(' ');
      @endphp

      {{-- BUNGKUS KARTU --}}
      <div class="{{ $containerClasses }}">

        {{-- BLUEPRINT: STACK (1 Kolom Menumpuk) --}}
        @if ($blueprint === 'stack')
          @php $mainSlot = $card['slots']['main'] ?? []; @endphp

          <div class="flex flex-col w-full h-full">
            @foreach ($mainSlot as $el)
              @php
                $type = $el['type'] ?? '';
                $s = $el['style'] ?? [];
                // Fallback Dummy Text untuk Preview
                $textContent = $el['content'][$lang] ?? '';
                if ($isPreview && empty($textContent)) {
                    $textContent = $type === 'badge' ? 'Label Baru' : 'Teks Sementara...';
                }
              @endphp

              {{-- ELEMEN: TEKS --}}
              @if ($type === 'text')
                @php
                  $textClasses = collect([$s['font'] ?? 'font-sans', $s['size'] ?? 'text-[15px]', $s['weight'] ?? 'font-normal', $s['color'] ?? 'text-ink-soft', $s['align'] ?? 'text-left', $s['margin'] ?? 'mb-2'])
                      ->filter()
                      ->implode(' ');
                @endphp
                <div class="{{ $textClasses }}">{{ $textContent }}</div>

                {{-- ELEMEN: LENCANA / PILL --}}
              @elseif ($type === 'badge')
                @php
                  $badgeClasses = collect([
                      $s['bg'] ?? 'bg-goldy-soft',
                      $s['color'] ?? 'text-foresty',
                      $s['radius'] ?? 'rounded-full',
                      $s['align'] ?? 'self-start', // self-start, self-center, self-end untuk badge
                      $s['margin'] ?? 'mb-3',
                      'inline-block px-3 py-1.5 text-[11.5px] font-extrabold tracking-[0.06em] uppercase',
                  ])
                      ->filter()
                      ->implode(' ');
                @endphp
                <div class="flex {{ $s['align'] === 'self-center' ? 'justify-center' : ($s['align'] === 'self-end' ? 'justify-end' : 'justify-start') }} w-full">
                  <span class="{{ $badgeClasses }}">{{ $textContent }}</span>
                </div>
              @endif
            @endforeach
          </div>
        @endif

      </div>
    @endforeach
  </div>
@endif
