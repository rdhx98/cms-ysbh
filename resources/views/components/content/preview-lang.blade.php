{{--
  <x-content.preview-lang> — pilihan kecil "pratinjau dalam bahasa apa" untuk panel pratinjau editor (rilis 88).
  Mengubah variabel `pv` milik editor pemuatnya (x-data="{ pv: '…' }"), TIDAK mengubah kolom isian (itu <x-content.lang-switch>).
--}}
@props(['locales' => []])
<span role="group" aria-label="Bahasa pratinjau" class="ml-auto inline-flex rounded-lg bg-gray-100 p-0.5" data-preview-lang>
    @foreach ($locales as $l)
        <button
            type="button"
            x-on:click="pv = '{{ $l }}'"
            x-bind:aria-pressed="(pv === '{{ $l }}').toString()"
            x-bind:class="pv === '{{ $l }}' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-800'"
            class="rounded-md px-2 py-0.5 text-[11px] font-bold outline-none transition focus-visible:ring-2 focus-visible:ring-foresty"
        >{{ strtoupper($l) }}</button>
    @endforeach
</span>
