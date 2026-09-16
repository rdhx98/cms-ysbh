@props(['data', 'lang', 'allContent' => []])

@php
  $buttons = $data['buttons'] ?? [];
@endphp

@if (!empty($buttons) && is_array($buttons))
  {{-- Margin bottom 11 (mb-11) sesuai dengan desain referensi Anda --}}
  <div id="{{ $block['anchor'] ?? '' }}" class="flex gap-4 flex-wrap mb-11">

    @foreach ($buttons as $btn)
      @php
        // Ambil data dengan sistem bahasa (lang) yang aman
        $label = $btn['label'][$lang] ?? '';
        $url = $btn['url'] ?? '#';
        $style = $btn['style'] ?? 'primary';

        // Lewati perulangan jika label kosong
        if (empty($label)) {
            continue;
        }

        // Kelas utilitas dasar yang dimiliki semua tombol
        $baseClasses =
            'inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-[3px]';

        // Distribusi warna/gaya menggunakan match PHP
        $styleClasses = match ($style) {
            'primary' => 'border-transparent bg-coral text-white hover:bg-coral-dark hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-14px_rgba(228,35,38,0.55)]',

            'outline' => 'border-foresty bg-transparent text-foresty hover:bg-foresty hover:text-white hover:-translate-y-0.5',

            default => 'border-transparent bg-gray-200 text-gray-800 hover:bg-gray-300 hover:-translate-y-0.5',
        };
      @endphp

      <a href="{{ $url }}" class="{{ $baseClasses }} {{ $styleClasses }}">
        {{ $label }}
      </a>
    @endforeach

  </div>
@endif
