{{--
  <x-editor.lang-tabs> — bahasa yang DITAMPILKAN di kolom isian (inspektur): Ganda (semua bahasa berdampingan) atau satu bahasa.
  Hanya mengatur tampilan kolom ($store.editor.lang); TIDAK mengubah data, dan tidak menentukan bahasa pratinjau
  (pratinjau punya pilihan "Lihat sebagai" sendiri, yang otomatis mengikuti tab ini saat Anda memilih satu bahasa).
--}}
@props(['locales' => ['id', 'en']])

@php
  $seg = 'rounded-md px-2.5 py-1 text-[11px] font-bold outline-none transition-colors';
@endphp

<div x-data role="group" aria-label="Bahasa kolom isian" title="Bahasa yang ditampilkan di kolom isian" class="inline-flex items-center rounded-lg bg-gray-100 p-0.5">
  <button
    type="button"
    x-on:click="$store.editor.lang = 'both'"
    x-bind:aria-pressed="$store.editor.lang === 'both'"
    x-bind:class="$store.editor.lang === 'both' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-800'"
    class="{{ $seg }}"
  >Ganda</button>
  @foreach ($locales as $l)
    <button
      type="button"
      x-on:click="$store.editor.lang = '{{ $l }}'"
      x-bind:aria-pressed="$store.editor.lang === '{{ $l }}'"
      x-bind:class="$store.editor.lang === '{{ $l }}' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-800'"
      class="{{ $seg }}"
    >{{ strtoupper($l) }}</button>
  @endforeach
</div>
