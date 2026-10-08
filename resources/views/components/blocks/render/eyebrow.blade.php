@props ([
  "block",
  "data" => [], // Menerima :data="$block['data']" dari parent
  "lang" => app()->getLocale() // Menerima :lang="$lang" dari parent
])

@php
  // Ambil teks sesuai bahasa ($lang)
  // Karena dari induk sudah dikirim $block['data'], kita cukup panggil $data
  $text = $data["text"][$lang] ?? "";

  // Ikon dan Warna bersifat Global
  $icon = $data["icon"] ?? "newspaper";
  $color = $data["color"] ?? "#e05a47";
  $marginBottom = $data["margin_bottom"] ?? "mb-2"; // Default margin bawah adalah mb-8 (32px)
@endphp

@if ($text)
  <div
    id="{{ $block['anchor'] ?? '' }}"
    class="{{ $marginBottom }} animate-scroll-reveal reveal"
  >
    <span
      class="inline-flex items-center gap-2.5 font-['Instrument_Sans',sans-serif] text-[11px] font-bold tracking-[0.16em] uppercase md:text-[13px]"
      style="color: {{ $color }};"
    >
      <x-dynamic-component
        :component="'lucide-' . $icon"
        class="h-4 w-4 shrink-0"
        stroke-width="2"
      />
      {{ $text }}
    </span>
  </div>
@endif
