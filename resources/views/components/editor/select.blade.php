{{-- <x-editor.select> — dropdown native untuk daftar panjang (font). path ATAU rel. --}}
@props([
  'path' => null,
  'rel' => null,
  'relExpr' => null,
  'options' => [],
  'label' => null,
  'default' => null,
  'live' => null,
  'width' => 'w-36',
  'font' => false,
  'cast' => null, // 'int': nilai kosong -> null, selain itu angka (untuk properti ?int milik Livewire)
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
  $items = \App\Editor\Options::normalize($options);
  $onChange = $cast === 'int' ? "set(\$event.target.value === '' ? null : Number(\$event.target.value))" : 'set($event.target.value)';
@endphp

<div x-data="wireField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, @js($default), @js($isLive))" {{ $attributes->class('flex flex-col gap-1.5') }}>
  @if ($label)
    <span class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</span>
  @endif

  <select
    x-bind:value="v"
    x-on:change="{{ $onChange }}"
    @if ($label) aria-label="{{ $label }}" @endif
    class="{{ $width }} hover:border-foresty rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-[11px] shadow-sm transition-colors focus:outline-none"
  >
    @foreach ($items as $item)
      <option value="{{ $item['value'] }}" @if ($font) style="font-family: '{{ $item['label'] ?? $item['value'] }}', sans-serif;" @endif>{{ $item['label'] ?? $item['name'] ?? $item['value'] }}</option>
    @endforeach
  </select>
</div>
