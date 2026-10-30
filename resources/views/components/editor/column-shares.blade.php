{{--
  <x-editor.column-shares> — keterangan lebar kolom di panel blok Kolom (rilis 100).
  Mode Kustom: persen tiap kolom, dan peringatan bila ada kolom di bawah NARROW_PERCENT. Selalu terpasang (tersembunyi di luar Kustom)
  agar bisa mengembalikan pola ke "sama rata" saat jumlah kolom berganti (lihat Alpine.data('columnShares') di editor.js).
--}}
<div
  x-data="columnShares(@js(\App\Content\Blocks\LayoutStyle::COLUMN_PRESETS), @js(\App\Content\Blocks\LayoutStyle::NARROW_PERCENT))"
  x-show="custom"
  x-cloak
  {{ $attributes->class('flex flex-col gap-1') }}
>
  <p class="text-[11px] font-semibold text-gray-600" data-column-summary x-text="summary"></p>
  <template x-for="c in narrowCols" :key="c.no">
    <p class="text-[11px] font-semibold text-amber-700" data-column-narrow x-text="'Kolom ' + c.no + ' hanya ' + c.pct + '%: terlalu sempit untuk isi umum.'"></p>
  </template>
</div>
