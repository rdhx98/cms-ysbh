@props (["block", "data", "lang", "allContent" => []])

@php
  $margin = $data["margin_bottom"] ?? "mb-2"; // Default margin bawah 2 (mb-2)
@endphp
<div
  id="{{ $block['anchor'] ?? '' }}"
  class="tiptap-content reveal animate-scroll-reveal {{ $margin }} [&>p:first-child]:mt-0 [&>p:last-child]:mb-0 font-sans text-[16px] leading-relaxed text-[#4B5D53]"
>
  {!!
    $data["text"][$lang] ??
      ""
  !!}
</div>

{{-- <div id="{{ $block['anchor'] ?? '' }}" class="max-w-none text-gray-600 tiptap-content reveal animate-scroll-reveal">
  {!! $data['text'][$lang] ?? '' !!}
</div> --}}
