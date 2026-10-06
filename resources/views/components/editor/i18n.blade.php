{{--
  <x-editor.i18n> — satu teks, satu kolom per bahasa. Menulis lewat $wire.$set pada path dinamis
  (wire:model tidak bisa berpindah target), sehingga satu kotak bisa melayani blok mana pun.
  Bahasa yang tampil mengikuti $store.editor.lang.
--}}
@props([
  'path' => null,
  'rel' => null,
  'label' => null,
  'locales' => ['id', 'en'],
  'multi' => false,
  'rows' => 3,
  'placeholder' => 'Ketik di sini...',
  'live' => null,
  'counter' => null, // batas anjuran karakter (mis. 160 untuk deskripsi SEO); hanya penunjuk, bukan pembatas
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
@endphp

<div x-data="wireField(@js($path), @js($rel), null, @js($isLive))" {{ $attributes->class('flex flex-col gap-1.5') }}>
  @if ($label)
    <span class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</span>
  @endif

  @foreach ($locales as $lang)
    <div class="relative" x-show="$store.editor.showLang(@js($lang))">
      <span class="bg-sage-soft text-foresty absolute top-1.5 left-2 rounded px-1.5 text-[9px] font-bold uppercase">{{ $lang }}</span>
      @if ($multi)
        <textarea
          rows="{{ $rows }}"
          placeholder="{{ $placeholder }}"
          x-bind:value="vAt(@js($lang))"
          x-on:input="typeAt(@js($lang), $event.target.value)"
          class="focus:border-foresty focus:ring-foresty w-full resize-y rounded-lg border-gray-200 px-2 pt-6 pb-2 text-sm shadow-sm transition-colors"
        ></textarea>
      @else
        <input
          type="text"
          placeholder="{{ $placeholder }}"
          x-bind:value="vAt(@js($lang))"
          x-on:input="typeAt(@js($lang), $event.target.value)"
          class="focus:border-foresty focus:ring-foresty w-full rounded-lg border-gray-200 px-2 pt-5 pb-1.5 text-sm shadow-sm transition-colors"
        />
      @endif

      @if ($counter)
        <span
          class="pointer-events-none absolute right-2 bottom-1 text-[9px] font-semibold"
          x-bind:class="vAt(@js($lang)).length > {{ (int) $counter }} ? 'text-amber-600' : 'text-gray-400'"
          x-text="vAt(@js($lang)).length + '/{{ (int) $counter }}'"
        ></span>
      @endif
    </div>
  @endforeach
</div>
