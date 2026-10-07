{{--
  <x-editor.rich> — teks kaya (HTML Tiptap) per bahasa, sementara SEBELUM Tiptap tunggal terpasang (Fase 3).

  Aturan keamanan data:
    - nilai teks POLOS (kosong atau tanpa tag, mis. blok baru)  -> kotak teks biasa; yang diketik di-escape (< > &)
    - nilai yang mengandung HTML (hasil Tiptap di editor lama)  -> hanya-baca; HTML tidak disentuh
  Jadi blok baru bisa langsung diisi, dan format yang sudah ada tidak mungkin rusak lewat kotak teks.
--}}
@props([
  'path' => null,
  'rel' => null,
  'relExpr' => null,
  'label' => null,
  'locales' => ['id', 'en'],
  'multi' => false,
  'live' => null,
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
@endphp

<div x-data="wireField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, null, @js($isLive))" {{ $attributes->class('flex flex-col gap-1.5') }}>
  @if ($label)
    <span class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</span>
  @endif

  @foreach ($locales as $lang)
    <div class="relative" x-show="$store.editor.showLang(@js($lang))">
      <span class="bg-sage-soft text-foresty absolute top-1.5 left-2 rounded px-1.5 text-[9px] font-bold uppercase">{{ $lang }}</span>

      {{-- polos: bisa diedit --}}
      <div x-show="isPlain(@js($lang))">
        @if ($multi)
          <textarea
            rows="5"
            placeholder="Ketik di sini..."
            x-bind:value="plainAt(@js($lang))"
            x-on:input="typePlain(@js($lang), $event.target.value)"
            class="focus:border-foresty focus:ring-foresty w-full resize-y rounded-lg border-gray-200 px-2 pt-6 pb-2 text-sm shadow-sm transition-colors"
          ></textarea>
        @else
          <input
            type="text"
            placeholder="Ketik di sini..."
            x-bind:value="plainAt(@js($lang))"
            x-on:input="typePlain(@js($lang), $event.target.value)"
            class="focus:border-foresty focus:ring-foresty w-full rounded-lg border-gray-200 px-2 pt-5 pb-1.5 text-sm shadow-sm transition-colors"
          />
        @endif
      </div>

      {{-- berisi HTML: hanya-baca --}}
      <div
        x-show="! isPlain(@js($lang))"
        data-rich-readonly
        class="{{ $multi ? 'min-h-16' : '' }} rounded-lg border border-dashed border-gray-300 bg-gray-50 px-2 pt-6 pb-2 text-sm text-gray-600"
        x-text="String(vAt(@js($lang))).replace(/<[^>]*>/g, '').trim() || '—'"
      ></div>
    </div>
  @endforeach

  <p
    x-show="@js($locales).some((l) => ! isPlain(l))"
    class="rounded-md bg-amber-50 px-2 py-1.5 text-[10px] leading-relaxed text-amber-800"
  >
    Teks berformat masih diedit lewat editor lama. Pemformatan tidak diubah di sini.
  </p>
</div>
