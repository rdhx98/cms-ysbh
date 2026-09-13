@props(['data', 'lang', 'allContent'])

@php
  $colCount = (int) ($data['col_count'] ?? 2);

  $gridClass = match ($colCount) {
      2 => 'md:grid-cols-2',
      3 => 'md:grid-cols-3',
      4 => 'md:grid-cols-2 lg:grid-cols-4',
      5 => 'md:grid-cols-3 lg:grid-cols-5',
      6 => 'md:grid-cols-3 lg:grid-cols-6',
      default => 'grid-cols-1 md:grid-cols-2',
  };

  $isReverseMobile = $data['mobile_reverse'] ?? false;
@endphp

<div id="{{ $block['anchor'] ?? '' }}" class="grid grid-cols-1 gap-6 {{ $gridClass }} w-full my-6">

  @for ($i = 1; $i <= $colCount; $i++)
    @php
      // Efek Domino: Jeda waktu kemunculan per kolom
      $delay = ($i - 1) * 150;
      $zoneKey = "col_{$i}_zone";

      $childIds = $data[$zoneKey] ?? [];

      $orderClass = '';
      if ($isReverseMobile && $colCount === 2) {
          $orderClass = $i === 1 ? 'order-2 md:order-1' : 'order-1 md:order-2';
      }
    @endphp

    {{-- 🌟 Wadah Kolom dengan 'reveal' dan modifier '[&.is-revealed]:' --}}
    <div class="flex flex-col gap-4 {{ $orderClass }}">
      {{-- <div style="transition-delay: {{ $delay }}ms;" class="flex flex-col gap-4 {{ $orderClass }} reveal animate-scroll-reveal"> --}}

      @if (!empty($childIds) && is_array($childIds))
        @foreach ($childIds as $childId)
          @if (isset($allContent[$childId]))
            @php
              $childBlock = $allContent[$childId];
              $component = 'blocks.render.' . str_replace('_', '-', $childBlock['type'] ?? 'unknown');
            @endphp

            {{-- Render Mikro Blok --}}
            <x-dynamic-component :component="$component" :data="$childBlock['data'] ?? []" :lang="$lang" :all-content="$allContent" />
          @endif
        @endforeach
      @endif

    </div>
  @endfor
</div>
