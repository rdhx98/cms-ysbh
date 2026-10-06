{{--
  <x-editor.segmented> — pilihan 2–5 opsi sebaris.
  path = path wire lengkap (pemakaian lama)  ATAU  rel = path relatif terhadap node yang difokus (inspektur).
  options : lihat App\Editor\Options::normalize(). Glyph per opsi: dot | dot_ring | slash | square | shape | icon | html.
  default : nilai yang tampil terpilih selama path belum punya nilai.
  live    : true kirim request tiap klik; false hanya state klien; null = config('cms.editor.defer_style_sync').
--}}
@props([
  'path' => null,
  'rel' => null,
  'options' => [],
  'label' => null,
  'default' => null,
  'live' => null,
  'compact' => false,
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
  $items = \App\Editor\Options::normalize($options);
@endphp

<div x-data="wireField(@js($path), @js($rel), @js($default), @js($isLive))" {{ $attributes->class('flex flex-col gap-1.5') }}>
  @if ($label)
    <span class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</span>
  @endif

  <div class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner" role="group" @if ($label) aria-label="{{ $label }}" @endif>
    @foreach ($items as $item)
      <button
        type="button"
        title="{{ $item['title'] ?? ($item['label'] ?? '') }}"
        x-on:click="set(@js($item['value']))"
        x-bind:aria-pressed="is(@js($item['value']))"
        x-bind:class="is(@js($item['value']))
          ? 'bg-white text-foresty shadow-sm ring-1 ring-gray-200'
          : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        class="{{ $compact ? 'flex h-7 w-7 items-center justify-center p-1' : 'flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold' }} rounded outline-none transition-all"
      >
        @if (isset($item['dot']))
          <span class="{{ ! empty($item['dot_ring']) ? 'border border-gray-300' : '' }} h-4 w-4 rounded-full shadow-sm {{ $item['dot'] }}"></span>
        @elseif (! empty($item['slash']))
          <span class="relative h-4 w-4 overflow-hidden rounded-full border border-gray-300 bg-white shadow-sm">
            <span class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"></span>
          </span>
        @elseif (isset($item['square']))
          <span class="{{ $item['square'] }} rounded-sm bg-current transition-all"></span>
        @elseif (isset($item['shape']))
          <span class="h-4 w-4 border-2 border-current {{ $item['shape'] }}"></span>
        @elseif (isset($item['icon']))
          <x-dynamic-component :component="$item['icon']" class="h-3.5 w-3.5" />
        @elseif (isset($item['html']))
          {!! $item['html'] !!}
        @endif

        @if (isset($item['label']) && (! $compact || (! isset($item['dot']) && ! isset($item['square']) && ! isset($item['shape']) && empty($item['slash']))))
          <span>{{ $item['label'] }}</span>
        @endif
      </button>
    @endforeach
  </div>
</div>
