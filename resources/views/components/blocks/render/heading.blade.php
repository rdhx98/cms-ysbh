@props(['block', 'data', 'lang', 'allContent' => []])

@php
  $level = $data['level'] ?? 'h2';
  $ariaLevel = str_replace('h', '', $level);

  // 🌟 KUNCI PARITY: Gabungkan pengaturan level Anda dengan $defaultClasses dari editor
  $baseClasses = match ($level) {
      'h1' => "font-['Fraunces',serif] text-[clamp(2.5rem,_4vw,_3.5rem)] font-extrabold text-[#064f3b]",
      // h2 disamakan persis dengan $defaultClasses di wrapper editor Anda
      'h2' => "font-['Fraunces',serif] text-[clamp(1.8rem,_3vw,_2.5rem)] font-semibold text-[#064f3b]",

      'h3' => "font-['Fraunces',serif] text-xl md:text-2xl font-semibold text-[#064f3b]",

      default => "font-['Fraunces',serif] text-[clamp(1.8rem,_3vw,_2.5rem)] font-semibold text-[#064f3b]",
  };
@endphp

<div id="{{ $block['anchor'] ?? '' }}" role="heading" aria-level="{{ $ariaLevel }}" class="tiptap-content {{ $baseClasses }} mb-3 reveal animate-scroll-reveal [&>p]:m-0">
  {!! $data['text'][$lang] ?? '' !!}
</div>

{{-- @php
  // 1. Ambil pilihan level dari editor (h1, h2, h3), gunakan h2 sebagai default
  $level = $data['level'] ?? 'h2';

  // 2. Ekstrak angka saja (1, 2, atau 3) untuk keperluan aksesibilitas (SEO/Screen Reader)
  $ariaLevel = str_replace('h', '', $level);

  // 3. Tentukan ukuran dasar dinamis Tailwind berdasarkan level heading
  $baseClasses = match ($level) {
      'h1' => 'text-4xl md:text-5xl font-extrabold',
      'h2' => 'text-2xl md:text-3xl font-bold',
      'h3' => 'text-xl md:text-2xl font-semibold',
      default => 'text-2xl font-bold',
  };
@endphp --}}

{{--
  role="heading" dan aria-level="..." memastikan struktur SEO tetap sempurna layaknya <h1>/<h2>/<h3>
  meskipun secara teknis kita menggunakan tag <div> agar HTML tetap valid jika TipTap memuntahkan tag <p>.
--}}
{{-- <div role="heading" aria-level="{{ $ariaLevel }}" class="tiptap-content font-['Plus_Jakarta_Sans',sans-serif] {{ $baseClasses }} mt-8 mb-4 reveal animate-scroll-reveal [&>p]:m-0">
  {!! $data['text'][$lang] ?? '' !!}
</div> --}}

{{-- <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4 reveal animate-scroll-reveal">
  {!! strip_tags($data['text'][$lang] ?? '', ['span', 'strong', 'em', 'u', 's', 'a', 'br']) !!} --}}
{{-- </h2> --}}
