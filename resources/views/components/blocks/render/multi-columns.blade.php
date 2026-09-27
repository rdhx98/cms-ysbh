@props(['block', 'data', 'lang', 'allContent'])

@php
  $colCount = (int) ($data['col_count'] ?? 2);

  // 🌟 1. Bangun susunan grid (grid-template-columns) secara dinamis
  $gridTemplateArray = [];
  for ($i = 1; $i <= $colCount; $i++) {
      // Baca data lebar yang diatur di editor, default 1fr jika kosong
      $width = $data["col_{$i}_zone_width"] ?? '1'; 
      $gridTemplateArray[] = $width === 'auto' ? 'auto' : "{$width}fr";
  }
  $gridTemplateStr = implode(' ', $gridTemplateArray); // Hasilnya misal: "1fr 2fr 1fr"

  $isReverseMobile = $data['mobile_reverse'] ?? false;
  $gapClass = $data['gap'] ?? 'gap-2';
  $alignYClass = $data['align_y'] ?? 'justify-start'; // justify-start, center, end
  $alignXClass = $data['align_x'] ?? 'items-start'; // items-start, center, end
@endphp

{{-- 🌟 2. Suntikkan CSS Variabel --md-grid-cols, dan eksekusi menggunakan kelas Tailwind MD: --}}
<div id="{{ $block['anchor'] ?? '' }}" 
     class="grid grid-cols-1 md:[grid-template-columns:var(--md-grid-cols)] gap-6 w-full mb-8 reveal animate-scroll-reveal"
     style="--md-grid-cols: {{ $gridTemplateStr }};">

  @for ($i = 1; $i <= $colCount; $i++)
    @php
      $zoneKey = "col_{$i}_zone";
      $childIds = $data[$zoneKey] ?? [];

      $orderClass = '';
      if ($isReverseMobile && $colCount === 2) {
          $orderClass = $i === 1 ? 'order-2 md:order-1' : 'order-1 md:order-2';
      }
    @endphp

    {{-- 🌟 Pembungkus Kolom dengan pengatur perataan --}}
    <div class="flex flex-col h-full {{ $orderClass }} {{ $gapClass }} {{ $alignYClass }} {{ $alignXClass }}">

      @if (!empty($childIds) && is_array($childIds))
        @foreach ($childIds as $childId)
          @if (isset($allContent[$childId]))
            @php
              $childBlock = $allContent[$childId];
              $component = 'blocks.render.' . str_replace('_', '-', $childBlock['type'] ?? 'unknown');
            @endphp

            {{-- Render Mikro Blok --}}
            <div class="w-full">
              <x-dynamic-component :component="$component" :data="$childBlock['data'] ?? []" :lang="$lang" :all-content="$allContent" />
            </div>
          @endif
        @endforeach
      @endif

    </div>
  @endfor
</div>