@props(['data', 'lang', 'allContent' => []])

@php
  $badges = $data['badges'] ?? [];
@endphp

@if (!empty($badges) && is_array($badges))
  <div id="{{ $block['anchor'] ?? '' }}" class="flex gap-3.5 flex-wrap">

    @foreach ($badges as $badge)
      @php
        $label = $badge['label'][$lang] ?? '';
        $url = $badge['url'] ?? '';
        $icon = $badge['icon'] ?? 'check-circle'; // Ikon Lucide bawaan

        // Konfigurasi warna lingkaran ikon
        $iconBg = $badge['icon_bg'] ?? 'bg-mist';
        $iconColor = $badge['icon_color'] ?? '#064F3B';

        if (empty($label)) {
            continue;
        }

        // Kelas untuk wadah pil/badge
        $badgeClasses =
            'flex items-center gap-2.5 bg-white border border-foresty/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-foresty shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-0.5 transition-transform duration-300';
      @endphp

      {{-- Jika ada URL, gunakan <a>, jika tidak, gunakan <div> --}}
      @if (!empty($url) && $url !== '#')
        <a href="{{ $url }}" class="{{ $badgeClasses }}">
        @else
          <div class="cursor-default {{ $badgeClasses }}">
      @endif

      {{-- Lingkaran Ikon Berwarna --}}
      <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 {{ $iconBg }}">
        <x-dynamic-component :component="'lucide-' . $icon" class="w-[15px] h-[15px]" style="color: {{ $iconColor }}; stroke: {{ $iconColor }};" stroke-width="2.5" />
      </span>

      {{-- Teks Badge --}}
      {{ $label }}

      @if (!empty($url) && $url !== '#')
        </a>
      @else
  </div>
@endif
@endforeach

</div>
@endif
