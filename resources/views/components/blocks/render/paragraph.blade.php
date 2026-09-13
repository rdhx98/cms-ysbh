@props(['block', 'data', 'lang', 'allContent' => []])


<div id="{{ $block['anchor'] ?? '' }}" class="tiptap-content font-sans text-[16px] text-[#4B5D53] leading-relaxed reveal animate-scroll-reveal [&>p:first-child]:mt-0 [&>p:last-child]:mb-0">

  {!! $data['text'][$lang] ?? '' !!}

</div>

{{-- <div id="{{ $block['anchor'] ?? '' }}" class="max-w-none text-gray-600 tiptap-content reveal animate-scroll-reveal">
  {!! $data['text'][$lang] ?? '' !!}
</div> --}}
