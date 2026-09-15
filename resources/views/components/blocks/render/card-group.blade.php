@props(['block', 'data', 'lang'])

@php
  $cards = $data['cards'] ?? [];
  $colCount = (int) ($data['col_count'] ?? 3);

  $gridClass = match ($colCount) {
      2 => 'md:grid-cols-2',
      4 => 'md:grid-cols-2 lg:grid-cols-4',
      default => 'lg:grid-cols-3', // Default 3 kolom
  };
@endphp

@if (count($cards) > 0)
  <div id="{{ $block['anchor'] ?? '' }}" class="grid grid-cols-1 {{ $gridClass }} gap-7 my-8 w-full">
    @foreach ($cards as $index => $card)
      @php
        $icon = $card['icon'] ?? 'circle';
        $bg = $card['icon_bg'] ?? 'bg-mist';
        $color = $card['icon_color'] ?? '#064F3B';
        $eyebrow = $card['eyebrow'][$lang] ?? '';
        $title = $card['title'][$lang] ?? '';
        $desc = $card['description'][$lang] ?? '';
        $url = $card['url'] ?? '#';

        // Efek beruntun (stagger) animasi berdasarkan indeks
        $delay = $index * 150;
      @endphp

      <div style="transition-delay: {{ $delay }}ms;"
        class="reveal transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[28px] p-8 border border-foresty/15 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1.5 hover:shadow-[0_25px_50px_-20px_rgba(6,45,35,0.45)] flex flex-col h-full">

        {{-- Ikon --}}
        <div class="w-16 h-16 rounded-[18px] flex items-center justify-center mb-5 {{ $bg }}" style="color: {{ $color }}">
          <x-dynamic-component :component="'lucide-' . $icon" class="w-[30px] h-[30px]" stroke-width="2" />
        </div>

        {{-- Eyebrow --}}
        @if (!empty($eyebrow))
          <div class="text-xs font-bold tracking-[0.1em] uppercase mb-3.5" style="color: {{ $color }}">
            {{ $eyebrow }}
          </div>
        @endif

        {{-- Judul --}}
        @if (!empty($title))
          <h3 class="font-display text-[21px] font-semibold text-foresty mb-2.5">
            {{ $title }}
          </h3>
        @endif

        {{-- Paragraf (flex-1 agar tombol di bawah rata) --}}
        @if (!empty($desc))
          <p class="text-ink-soft text-[15px] flex-1">
            {{ $desc }}
          </p>
        @endif

        {{-- Tombol Selengkapnya --}}
        {{-- @if (!empty($url) && $url !== '#')
          <a href="{{ $url }}" wire:navigate class="group mt-5 font-bold text-[14.5px] inline-flex items-center gap-1.5 text-foresty hover:text-coral transition-colors">
            {{ $lang === 'en' ? 'Learn More' : 'Selengkapnya' }}
            <x-dynamic-component component="lucide-arrow-right" class="w-[15px] h-[15px] transition-transform duration-200 group-hover:translate-x-1" stroke-width="2.5" />
          </a>
        @endif --}}
        @if (!empty(trim($url)) && trim($url) !== '#')
          <a href="{{ $url }}" wire:navigate class="group mt-5 font-bold text-[14.5px] inline-flex items-center gap-1.5 text-foresty hover:text-coral transition-colors">
            {{ $lang === 'en' ? 'Learn More' : 'Selengkapnya' }}
            <x-dynamic-component component="lucide-arrow-right" class="w-[15px] h-[15px] transition-transform duration-200 group-hover:translate-x-1" stroke-width="2.5" />
          </a>
        @endif

      </div>
    @endforeach
  </div>
@endif
