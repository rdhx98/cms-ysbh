{{--
  Render PUBLIK blok Tabel (blocks.render.table-builder). Sel = teks biasa yang di-escape (TableStyle::cell: baris baru, **tebal**, tautan https);
  tidak ada HTML dari penulis yang lolos. Kelas CSS dari daftar tetap (TableStyle). Data dibersihkan lagi di sini (jaring kedua).
  Bahasa: sel/judul kosong di bahasa halaman jatuh ke id lalu en. Baris yang kosong di SEMUA bahasa tidak dirender di situs (di kanvas tetap
  tampil, supaya baris baru bisa terlihat). Tabel tanpa isi sama sekali tidak dirender di situs; di kanvas tampil "Tabel masih kosong".
  Layar kecil: tabel digulir mendatar di dalam bingkainya (bingkai bisa difokus dengan papan tik dan diberi nama untuk pembaca layar).
--}}
@props([
  'block' => [],
  'data' => [],
  'lang' => 'id',
  'allContent' => [],
])

@php
  use App\Content\Blocks\BlockSanitizer;
  use App\Content\Blocks\TableStyle;

  $inCanvas = (bool) ($canvasMode ?? false);
  $clean = BlockSanitizer::forType('table-builder', is_array($data) ? $data : [], array_values(array_unique([$lang, 'id', 'en'])));

  $pick = function (array $byLocale) use ($lang): string {
      foreach ([$lang, 'id', 'en'] as $l) {
          if (($byLocale[$l] ?? '') !== '') {
              return $byLocale[$l];
          }
      }

      return '';
  };

  $tid = 't' . ($block['id'] ?? substr(md5(json_encode($clean)), 0, 8)); // id untuk aria-labelledby
  $columns = $clean['columns'];
  $count = count($columns);
  $caption = $pick($clean['caption']);
  $note = $pick($clean['note']);
  $heads = array_map(fn ($c) => $pick($c['label']), $columns);
  $showHead = $clean['header'] && array_filter($heads, fn ($h) => $h !== '') !== [];

  $rows = [];
  $anyText = false;
  foreach ($clean['rows'] as $row) {
      $cells = array_map(fn ($c) => TableStyle::cell($pick($c)), $row['cells']);
      $empty = array_filter($cells, fn ($c) => $c !== '') === [];
      $anyText = $anyText || ! $empty;
      $rows[] = ['cells' => $cells, 'empty' => $empty];
  }
  // Situs: baris kosong tidak dirender. Kanvas: tetap tampil (supaya baris baru terlihat), asalkan tabelnya sudah punya isi.
  $rows = array_values(array_map(fn ($r) => $r['cells'], array_filter($rows, fn ($r) => ! $r['empty'] || $inCanvas)));

  $hasContent = $showHead || $anyText;
  $border = $clean['border'];
  $pad = TableStyle::pad($clean['density']);
  $cellBorder = TableStyle::cellBorder($border);
@endphp

@if ($count > 0 && ($hasContent || $caption !== ''))
  <div data-table-block class="w-full">
    @if ($caption !== '')
      <p id="tbl-{{ $tid }}" class="mb-3 text-base leading-snug font-bold">{{ $caption }}</p>
    @endif

    @if ($hasContent)
      {{-- Petunjuk "geser" hanya muncul bila tabel memang lebih lebar dari bingkainya (dihitung di peramban, dihitung ulang saat ukuran berubah) --}}
      <div
        x-data="{ over: false }"
        x-init="over = $refs.frame.scrollWidth > $refs.frame.clientWidth + 1"
        x-on:resize.window.debounce.150ms="over = $refs.frame.scrollWidth > $refs.frame.clientWidth + 1"
      >
        <div
          x-ref="frame"
          class="{{ TableStyle::frame($border) }}"
          role="region"
          tabindex="0"
          @if ($caption !== '') aria-labelledby="tbl-{{ $tid }}" @else aria-label="{{ $lang === 'en' ? 'Table' : 'Tabel' }}" @endif
        >
          <table class="w-full text-sm leading-relaxed" style="min-width: {{ $count * 6 }}rem">
            @if ($showHead)
              <thead class="{{ TableStyle::head($clean['color']) }}" style="{{ TableStyle::headStyle($clean['color']) }}">
                <tr>
                  @foreach ($columns as $i => $col)
                    <th scope="col" class="{{ $pad }} {{ TableStyle::align($col['align']) }} {{ $cellBorder }} font-bold" style="{{ TableStyle::headStyle($clean['color']) }}">{{ $heads[$i] }}</th>
                  @endforeach
                </tr>
              </thead>
            @endif
            <tbody class="{{ TableStyle::body($border) }}">
              @foreach ($rows as $cells)
                <tr class="{{ $clean['striped'] ? TableStyle::stripe() : '' }}">
                  @foreach ($cells as $i => $html)
                    @if ($i === 0 && $clean['first_col_header'])
                      <th scope="row" class="{{ $pad }} {{ TableStyle::align($columns[$i]['align']) }} {{ $cellBorder }} font-bold align-top">{!! $html !!}</th>
                    @else
                      <td class="{{ $pad }} {{ TableStyle::align($columns[$i]['align']) }} {{ $cellBorder }} align-top [&_a]:font-semibold [&_a]:underline">{!! $html !!}</td>
                    @endif
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <p x-show="over" x-cloak class="mt-1.5 text-[11px] text-gray-400">{{ $lang === 'en' ? 'Swipe sideways to see the whole table.' : 'Geser ke samping untuk melihat seluruh tabel.' }}</p>
      </div>
    @endif

    @if ($note !== '')
      <p class="mt-2 text-xs leading-relaxed text-gray-500">{{ $note }}</p>
    @endif
  </div>
@elseif ($inCanvas)
  <div data-table-block class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-center text-sm text-gray-400">
    Tabel masih kosong. Isi di panel Properti.
  </div>
@endif
