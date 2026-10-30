{{--
  <x-content.lang-switch> — pilihan bahasa KOLOM ISIAN untuk editor Footer, Pengumuman, dan Navigation builder (rilis 88).
  Sama dengan <x-editor.lang-tabs> di editor page/post/snippet dan memakai penyimpanan Alpine yang SAMA ($store.editor.lang): "Ganda" (semua bahasa
  berdampingan) atau satu bahasa. Memilih di satu editor berlaku di editor lain pada sesi yang sama. Hanya mengatur tampilan kolom, bukan data.
  Bahasa PRATINJAU dipisah (variabel `pv` milik editor): mengikuti pilihan satu bahasa, dan tetap di bahasa terakhir saat memilih Ganda.

  Alat:   locales  daftar bahasa
          gaps     peta bahasa => jumlah teks yang masih kosong (lencana "n kosong"); bahasa dengan 0 diberi centang
          names    peta kode => nama untuk tooltip
  Teks hanya dicetak lewat {{ }}.
--}}
@props(['locales' => [], 'gaps' => [], 'names' => ['en' => 'English', 'id' => 'Indonesia'], 'default' => 'en'])
@php
    $seg = 'flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold outline-none transition focus-visible:ring-2 focus-visible:ring-foresty';
@endphp
<div role="group" aria-label="Bahasa kolom isian" title="Bahasa yang ditampilkan di kolom isian" class="inline-flex items-center rounded-xl bg-gray-100 p-1" data-lang-switch>
    <button
        type="button"
        x-on:click="$store.editor.lang = 'both'"
        x-bind:aria-pressed="($store.editor.lang === 'both').toString()"
        x-bind:class="$store.editor.lang === 'both' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-800'"
        class="{{ $seg }}"
    >Ganda</button>
    @foreach ($locales as $l)
        @php $gap = (int) ($gaps[$l] ?? 0); @endphp
        <button
            type="button"
            x-on:click="$store.editor.lang = '{{ $l }}'"
            x-bind:aria-pressed="($store.editor.lang === '{{ $l }}').toString()"
            x-bind:class="$store.editor.lang === '{{ $l }}' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-800'"
            class="{{ $seg }}"
            title="{{ $names[$l] ?? strtoupper($l) }}{{ $l === $default ? ' (bawaan)' : '' }}"
        >
            {{ strtoupper($l) }}
            @if ($l !== $default && $gap > 0)
                <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800" title="{{ $gap }} teks belum diisi: pembaca melihat teks bahasa bawaan" data-lang-gap="{{ $gap }}">{{ $gap }} kosong</span>
            @endif
        </button>
    @endforeach
</div>
