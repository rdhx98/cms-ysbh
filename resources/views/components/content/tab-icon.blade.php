{{-- <x-content.tab-icon name="review" /> — ikon bilah tab panel kanan editor (rilis 49 rev 3). name: page | block | review | audit. Garis tipis, mengikuti warna teks. --}}
@props(['name' => 'page'])

@php
  $paths = [
      'page' => '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/><path d="M10 13h6M10 17h6"/>',
      'block' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M4 7.5l8 4.5 8-4.5M12 12v9"/>',
      'review' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M9 11.5l2.2 2.2L15 9.8"/>',
      'audit' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  ];
@endphp

<svg {{ $attributes->class(['size-4 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-tab-icon="{{ $name }}">{!! $paths[$name] ?? $paths['page'] !!}</svg>
