{{-- <x-editor.toggle> — kotak centang boolean. path ATAU rel. --}}
@props(['path' => null, 'rel' => null, 'label' => null, 'live' => null])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
@endphp

<label x-data="wireField(@js($path), @js($rel), false, @js($isLive))" {{ $attributes->class('flex cursor-pointer items-center gap-1.5') }}>
  <input
    type="checkbox"
    x-bind:checked="!! v"
    x-on:change="set($event.target.checked)"
    class="text-foresty focus:ring-foresty h-3.5 w-3.5 rounded border-gray-300"
  />
  <span class="text-[10px] font-bold text-gray-500 uppercase">{{ $label }}</span>
</label>
