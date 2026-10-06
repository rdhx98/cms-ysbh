{{--
  <x-editor.swatches> — palet warna dari config. mode: bg | text | preview | hex  (lihat versi sebelumnya).
  path (lengkap) ATAU rel (relatif ke node yang difokus).
--}}
@props([
  'path' => null,
  'rel' => null,
  'options' => [],
  'label' => null,
  'default' => null,
  'live' => null,
  'mode' => 'bg',
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
  $items = \App\Editor\Options::normalize($options);
@endphp

<div x-data="wireField(@js($path), @js($rel), @js($default), @js($isLive))" {{ $attributes->class('flex flex-col gap-1.5') }}>
  @if ($label)
    <label class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</label>
  @endif

  <div class="flex flex-wrap gap-3 pb-6">
    @foreach ($items as $opt)
      @php
        $value = $opt['value'];
        $name = $opt['name'] ?? ($opt['label'] ?? $value);
        $face = match ($mode) {
          'text' => trim($value . ' ' . ($opt['preview'] ?? '')),
          'preview' => $opt['preview'] ?? $value,
          'hex' => '',
          default => $value,
        };
      @endphp
      <div class="group/btn relative flex flex-col items-center">
        <button
          type="button"
          title="{{ $name }}"
          x-on:click="set(@js($value))"
          x-bind:aria-pressed="isCi(@js($value))"
          x-bind:class="isCi(@js($value))
            ? 'ring-2 ring-forest ring-offset-2 scale-110'
            : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
          @if ($mode === 'hex') style="background-color: {{ $value }}" @endif
          class="hover:ring-forest relative flex h-6 w-6 items-center justify-center overflow-hidden rounded-md border border-gray-200 text-sm font-semibold shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none {{ $face }}"
        >
          @if ($mode === 'text')
            Aa
          @endif

          @if (! empty($opt['is_transparent']))
            <span class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"></span>
          @endif
        </button>

        <span
          class="text-xxs absolute top-full mt-2 font-bold whitespace-nowrap text-gray-700 uppercase transition-all duration-300"
          x-bind:class="isCi(@js($value))
            ? 'opacity-100 translate-y-0'
            : 'pointer-events-none opacity-0 -translate-y-1 group-hover/btn:opacity-100 group-hover/btn:translate-y-0'"
        >{{ $name }}</span>
      </div>
    @endforeach
  </div>
</div>
