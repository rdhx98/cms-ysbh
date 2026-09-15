@props(['block', 'data', 'lang', 'allContent' => []])

@php
  $stats = $data['stats'] ?? [];

  // Logika Perataan (Alignment)
  $align = $data['align'] ?? 'left';
  $justifyClass = match ($align) {
      'center' => 'justify-center text-center',
      'right' => 'justify-end text-right',
      default => 'justify-start text-left',
  };
@endphp

@if (count($stats) > 0)
  <div id="{{ $block['anchor'] ?? '' }}" class="flex gap-7 mt-6 mb-6 flex-wrap reveal animate-scroll-reveal {{ $justifyClass }}">

    @foreach ($stats as $stat)
      @php
        $val = $stat['value'][$lang] ?? '';
        $lbl = $stat['label'][$lang] ?? '';
      @endphp

      @if (!empty($val) || !empty($lbl))
        <div>
          <b class="block font-display text-[26px] text-foresty">{{ $val }}</b>
          <span class="text-[13px] text-ink-soft">{{ $lbl }}</span>
        </div>
      @endif
    @endforeach

  </div>
@endif
