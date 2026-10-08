{{--
  <x-editor.repeater> — daftar item berulang (tombol, pertanyaan FAQ, ...): tambah, hapus, duplikat, urut (↑ ↓), buka satu per satu.
  Data di state: larik objek pada path (relatif terhadap node yang difokus). Semua perubahan dikirim sebagai SATU $set larik.
  Kontrol di dalam item dirender SEKALI oleh Blade (di dalam <template x-for>) dan dipakai ulang tiap item lewat ungkapan path
  "'<kunci>.' + i"; kunci DOM = indeks, jadi urutan ulang hanya mengganti nilai, bukan membangun ulang kontrol.
--}}
@props(['field', 'locales' => ['id', 'en'], 'live' => null])

@php
  /** @var \App\Editor\Field $field */
  $x = $field->extra;
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
  $prefix = "'" . $field->key . ".' + i";
  $itemLabel = $x['itemLabel'] ?? 'item';
  $btn = 'rounded p-1 text-gray-400 outline-none hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-30';
@endphp

<div
  x-data="repeater(@js($field->key), @js($x['defaults']), @js($locales), @js($x['max']), @js($isLive))"
  {{ $attributes->class('flex flex-col gap-2') }}
>
  <div class="flex items-center justify-between">
    <span class="text-xxs font-bold text-gray-700 uppercase">{{ $field->label }} <span class="font-semibold text-gray-400" x-text="'(' + items.length + ')'"></span></span>
    <button
      type="button"
      x-on:click="add()"
      x-bind:disabled="items.length >= max"
      class="hover:bg-sage-soft hover:text-foresty rounded-md border border-dashed border-gray-300 px-2 py-1 text-[11px] font-bold text-gray-500 transition-colors disabled:cursor-not-allowed disabled:opacity-40"
    >+ Tambah {{ $itemLabel }}</button>
  </div>

  <p x-show="items.length === 0" x-cloak class="rounded-lg bg-gray-50 p-3 text-xs text-gray-500">Belum ada {{ $itemLabel }}. Tekan "Tambah {{ $itemLabel }}".</p>

  <template x-for="(item, i) in items" x-bind:key="i">
    <div class="rounded-lg border bg-white" x-bind:class="open === i ? 'border-foresty/40 shadow-sm' : 'border-gray-200'">
      <div class="flex items-center gap-0.5 pr-1">
        <button type="button" x-on:click="toggle(i)" x-bind:aria-expanded="open === i" class="flex min-w-0 flex-1 items-center gap-2 px-2.5 py-2 text-left">
          <svg class="h-3 w-3 shrink-0 text-gray-400 transition-transform" x-bind:class="open === i ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
          <span class="truncate text-xs font-semibold text-gray-700" x-text="(i + 1) + '. ' + summary(item)"></span>
        </button>
        <button type="button" title="Naik" class="{{ $btn }}" x-bind:disabled="i === 0" x-on:click="move(i, -1)">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6" /></svg>
        </button>
        <button type="button" title="Turun" class="{{ $btn }}" x-bind:disabled="i === items.length - 1" x-on:click="move(i, 1)">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
        </button>
        <button type="button" title="Duplikat" class="{{ $btn }}" x-bind:disabled="items.length >= max" x-on:click="duplicate(i)">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" /></svg>
        </button>
        <button type="button" title="Hapus" class="{{ $btn }} hover:!bg-red-50 hover:!text-red-500" x-on:click="remove(i)">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>
        </button>
      </div>

      <div x-show="open === i" x-cloak class="flex flex-col gap-4 border-t border-gray-100 p-3">
        @foreach ($x['fields'] as $sub)
          <x-editor.field :field="$sub" :locales="$locales" :prefix="$prefix" />
        @endforeach
      </div>
    </div>
  </template>
</div>
