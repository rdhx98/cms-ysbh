{{--
  <x-editor.glyphs> — deretan tombol berhuruf (rilis 69), mis. "AA aa Aa" untuk huruf besar/kecil dan "I U S" untuk miring/garis bawah/coret.
  options : [{value, glyph, title, class?}]  (glyph = teks pendek; class = kelas tampilan huruf pada tombolnya)
  mode    : one  = satu nilai; klik tombol yang sedang aktif mengembalikan ke `default` (menghapus pilihan)
            many = nilai berupa larik; tiap tombol menyala/mati sendiri
  default : nilai "tidak ada pilihan" (mode one: mis. normal-case; mode many: larik kosong)
  path ATAU rel (relExpr di dalam daftar berulang), seperti kontrol lain.
--}}
@props(['path' => null, 'rel' => null, 'relExpr' => null, 'options' => [], 'label' => null, 'default' => null, 'mode' => 'one', 'live' => null])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
  $many = $mode === 'many';
@endphp

<div x-data="wireField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, @js($default), @js($isLive))" data-glyphs="{{ $many ? 'many' : 'one' }}" {{ $attributes->class('flex flex-col gap-1.5') }}>
  @if ($label)
    <span class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</span>
  @endif

  <div class="flex w-fit items-center rounded-md bg-gray-200 p-0.5 shadow-inner" role="group" @if ($label) aria-label="{{ $label }}" @endif>
    @foreach ($options as $o)
      <button
        type="button"
        title="{{ $o['title'] ?? $o['glyph'] }}"
        @if ($many)
          x-on:click="flip(@js($o['value']))"
          x-bind:aria-pressed="has(@js($o['value']))"
          x-bind:class="has(@js($o['value'])) ? 'bg-white text-foresty shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        @else
          x-on:click="pick(@js($o['value']), @js($default))"
          x-bind:aria-pressed="is(@js($o['value']))"
          x-bind:class="is(@js($o['value'])) ? 'bg-white text-foresty shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
        @endif
        class="flex h-7 min-w-8 items-center justify-center rounded px-1.5 text-xs font-bold outline-none transition-all"
      >
        <span class="{{ $o['class'] ?? '' }}">{{ $o['glyph'] }}</span>
      </button>
    @endforeach
  </div>
</div>
