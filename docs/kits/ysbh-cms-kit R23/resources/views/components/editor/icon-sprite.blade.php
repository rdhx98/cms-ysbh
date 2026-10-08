{{--
  <x-editor.icon-sprite> — sprite SVG untuk semua ikon di config('cms.lucide'): satu <symbol id="icon-{nama}"> per ikon.
  Dipakai <x-editor.icon-picker> (tombol) dan <x-editor.icon-picker-modal> lewat <use href="#icon-{nama}">,
  sehingga pemilih ikon tidak merender komponen ikon sama sekali.

  Sama dengan sprite yang dulu ada di page-editor lama — dipindahkan jadi komponen. Pasang SEKALI di LAYOUT admin
  (di luar komponen Livewire): di dalam komponen, 75 <x-dynamic-component> ikut dirender ulang di setiap request.
  Butuh paket ikon Lucide untuk Blade (yang sudah Anda pakai: <x-dynamic-component component="lucide-…">).
--}}
@props([
  'icons' => null,
  'prefix' => 'icon-',
])

@php
  $base = $icons ?? collect(config('cms.lucide', []))->sort()->values()->all();

  // Ikon palet blok (outline) yang belum ada di cms.lucide. Dirender toleran: nama ikon yang tidak ada di paket Lucide
  // Anda dilewati (ikonnya kosong), tidak membuat seluruh layout error.
  $extra = array_values(array_diff(\App\Editor\BlockPalette::icons(), $base));
  $extraSvg = [];
  foreach ($extra as $name) {
      try {
          $extraSvg[$name] = svg('lucide-' . $name)->toHtml();
      } catch (\Throwable) {
          // dilewati
      }
  }
@endphp

<svg aria-hidden="true" focusable="false" width="0" height="0" style="display: none" data-icon-sprite>
  @foreach ($base as $name)
    <symbol
      id="{{ $prefix }}{{ $name }}"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <x-dynamic-component :component="'lucide-' . $name" />
    </symbol>
  @endforeach

  @foreach ($extraSvg as $name => $html)
    <symbol
      id="{{ $prefix }}{{ $name }}"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      {!! $html !!}
    </symbol>
  @endforeach
</svg>
