{{-- <x-editor.text> — input satu baris atau banyak baris (rows). slug = huruf kecil + tanda hubung saja. path ATAU rel. --}}
@props([
  'path' => null,
  'rel' => null,
  'relExpr' => null,
  'label' => null,
  'placeholder' => '',
  'maxlength' => null,
  'upper' => false,
  'slug' => false,
  'live' => null,
  'type' => 'text',
  'rows' => null,
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
  $input = 'focus:border-foresty focus:ring-foresty w-full rounded-lg border-gray-200 p-2 text-xs shadow-sm ' . ($upper ? 'uppercase' : '');
@endphp

<div x-data="wireField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, null, @js($isLive))" {{ $attributes->class('flex flex-col gap-1.5') }}>
  @if ($label)
    <label class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</label>
  @endif

  @if ($rows)
    <textarea
      rows="{{ (int) $rows }}"
      placeholder="{{ $placeholder }}"
      @if ($maxlength) maxlength="{{ $maxlength }}" @endif
      x-bind:value="vAt('')"
      x-on:input="typeAt('', $event.target.value)"
      class="{{ $input }} resize-y"
    ></textarea>
  @else
    <input
      type="{{ $type }}"
      @if ($type === 'number') inputmode="numeric" min="0" @endif
      placeholder="{{ $placeholder }}"
      @if ($maxlength) maxlength="{{ $maxlength }}" @endif
      x-bind:value="vAt('')"
      x-on:input="
        @if ($slug) $event.target.value = $event.target.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, ''); @endif
        typeAt('', @if ($type === 'number') $event.target.value === '' ? 0 : Number($event.target.value) @else $event.target.value @endif)
      "
      class="{{ $input }}"
    />
  @endif
</div>
