{{--
  <x-content.lang-field> — satu isian dua bahasa untuk editor Footer dan Pengumuman (rilis 87).
  Mencetak satu kolom isian untuk TIAP bahasa, tetapi hanya yang bahasanya sedang dipilih yang terlihat (Alpine: x-show="lang === '…'").
  Karena itu komponen ini harus berada di dalam elemen yang punya  x-data="{ lang: 'id' }"  (editor yang memasangnya sudah begitu).
  Isian bahasa lain yang masih kosong menampilkan teks bahasa bawaan sebagai petunjuk abu-abu: itulah yang akan dilihat pembaca bila dibiarkan kosong.
  Nilai diikat ke Livewire lewat  wire:model.live.debounce.400ms="{path}.{bahasa}"  supaya pratinjau ikut berubah saat mengetik.

  Alat:   path    jalur Livewire tanpa bahasa, mis. "f.columns.0.heading"
          values  peta bahasa => teks saat ini (untuk petunjuk dan penghitung)
          locales daftar bahasa, default bahasa bawaan
          rows    0 = satu baris; >0 = kotak teks dengan sekian baris
          counter tampilkan "n / maks" di pojok kotak teks
          label   nama isian untuk pembaca layar (aria-label), mis. "Judul kolom"
          class   kelas tambahan untuk isiannya
  Semua teks dicetak lewat {{ }} (di-escape).
--}}
@props([
    'path',
    'values' => [],
    'locales' => [],
    'default' => 'en',
    'label' => '',
    'max' => 160,
    'rows' => 0,
    'placeholder' => '',
    'counter' => false,
    'mono' => false,
])
@php
    $base = 'peer w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm placeholder:text-gray-400 focus:border-foresty focus:ring-foresty';
    $base .= $mono ? ' font-mono text-xs' : '';
@endphp
@foreach ($locales as $l)
    @php
        $val = (string) ($values[$l] ?? '');
        $fallback = $l !== $default ? (string) ($values[$default] ?? '') : '';
        $hint = $fallback !== '' ? $fallback : $placeholder;
        $code = strtoupper($l);
    @endphp
    <div x-show="lang === '{{ $l }}'" class="relative" wire:key="lf-{{ $path }}-{{ $l }}" data-lang-field="{{ $l }}">
        @if ($rows > 0)
            <textarea
                wire:model.live.debounce.400ms="{{ $path }}.{{ $l }}"
                rows="{{ $rows }}"
                maxlength="{{ $max }}"
                aria-label="{{ $label }} ({{ $code }})"
                placeholder="{{ $hint }}"
                {{ $attributes->merge(['class' => $base . ' pr-10']) }}
            ></textarea>
            <span class="pointer-events-none absolute right-2.5 top-2 text-[10px] font-semibold text-gray-300 peer-focus:text-foresty">{{ $code }}</span>
            @if ($counter)
                <span class="pointer-events-none absolute bottom-2 right-2.5 text-[10px] tabular-nums {{ mb_strlen($val) >= $max * 0.9 ? 'text-amber-600' : 'text-gray-300' }}">{{ mb_strlen($val) }} / {{ $max }}</span>
            @endif
        @else
            <input
                type="text"
                wire:model.live.debounce.400ms="{{ $path }}.{{ $l }}"
                maxlength="{{ $max }}"
                aria-label="{{ $label }} ({{ $code }})"
                placeholder="{{ $hint }}"
                {{ $attributes->merge(['class' => $base . ' pr-10']) }}
            >
            <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-semibold text-gray-300 peer-focus:text-foresty">{{ $code }}</span>
        @endif
    </div>
@endforeach
