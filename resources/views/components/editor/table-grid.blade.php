{{--
  <x-editor.table-grid> — kisi isi blok Tabel (rilis 34). Dipasang BlockRegistry lewat BlockType::$component (componentFirst: di atas kontrol).

  Satu bahasa tampil pada satu waktu (tombol ID/EN; mengikuti pilihan bahasa di bilah atas bila hanya satu bahasa). Kolom: judul, geser,
  rata teks (klik untuk berganti), hapus. Baris: nomor, naik/turun, hapus. Menempel beberapa sel dari Excel/Google Sheets ke sebuah sel
  mengisi mulai dari sel itu; "Tempel dari spreadsheet" mengganti seluruh tabel. Logika: resources/js/editor.js (tablePanel, tableOps).
  Kunci DOM = indeks (bukan id), supaya menggeser hanya mengganti nilai.
--}}
@props(['locales' => ['id', 'en']])

@php
  $ic = fn (string $path, string $size = 'h-3 w-3') => '<svg class="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
  $left = $ic('<path d="m15 18-6-6 6-6" />');
  $right = $ic('<path d="m9 18 6-6-6-6" />');
  $up = $ic('<path d="m18 15-6-6-6 6" />');
  $down = $ic('<path d="m6 9 6 6 6-6" />');
  $trash = $ic('<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />');
  $plus = $ic('<path d="M12 5v14M5 12h14" />');
  $btn = 'inline-flex h-5 w-5 shrink-0 items-center justify-center rounded text-gray-400 outline-none transition-colors hover:bg-gray-200 hover:text-gray-700 focus-visible:ring-1 focus-visible:ring-foresty disabled:cursor-not-allowed disabled:opacity-30';
  $danger = $btn . ' hover:!bg-red-50 hover:!text-red-500';
  $chip = 'inline-flex items-center gap-1 rounded-md border border-dashed border-gray-300 px-2 py-1 text-[11px] font-bold text-gray-500 transition-colors hover:border-foresty/50 hover:bg-foresty/5 hover:text-foresty disabled:cursor-not-allowed disabled:opacity-40';
  $input = 'block w-full rounded border-gray-300 px-1.5 py-1 text-xs shadow-none focus:border-foresty focus:ring-1 focus:ring-foresty';
@endphp

<div
  x-data="tablePanel(@js(array_values($locales)))"
  {{ $attributes->class('flex flex-col gap-2') }}
  data-table-panel
>
  <div class="flex items-center justify-between gap-2">
    <span class="text-xxs font-bold text-gray-700 uppercase">
      Isi tabel <span class="font-semibold text-gray-400" x-text="'(' + columns.length + ' kolom, ' + rows.length + ' baris)'"></span>
    </span>
    <div class="inline-flex shrink-0 rounded-md bg-gray-100 p-0.5" role="group" aria-label="Bahasa yang disunting">
      <template x-for="l in locales" x-bind:key="l">
        <button
          type="button"
          x-on:click="lang = l"
          x-bind:class="lang === l ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-700'"
          x-bind:aria-pressed="lang === l"
          class="rounded px-2 py-0.5 text-[10px] font-bold uppercase"
          x-text="l"
          data-lang
        ></button>
      </template>
    </div>
  </div>

  {{-- Kisi: digulir mendatar di dalam panel --}}
  <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white" data-grid>
    <table class="border-collapse text-xs">
      <thead>
        <tr class="bg-gray-50">
          <th class="w-12 min-w-12 px-1 py-1 text-left text-[10px] font-semibold text-gray-400">#</th>
          <template x-for="(col, ci) in columns" x-bind:key="ci">
            <th class="min-w-32 border-l border-gray-200 p-1 align-top font-normal" data-col>
              <input
                type="text"
                maxlength="120"
                x-bind:value="head(ci)"
                x-on:input="typeHead(ci, $event.target.value)"
                x-bind:placeholder="'Kolom ' + (ci + 1)"
                x-bind:aria-label="'Judul kolom ' + (ci + 1)"
                class="{{ $input }} font-bold"
                data-head
              />
              <div class="mt-1 flex items-center gap-0.5">
                <button type="button" title="Geser ke kiri" class="{{ $btn }}" x-bind:disabled="ci === 0" x-on:click="moveColumn(ci, -1)" data-col-left>{!! $left !!}</button>
                <button type="button" title="Geser ke kanan" class="{{ $btn }}" x-bind:disabled="ci === columns.length - 1" x-on:click="moveColumn(ci, 1)" data-col-right>{!! $right !!}</button>
                <button type="button" x-bind:title="alignTitle(ci)" class="{{ $btn }} !w-auto gap-0.5 px-1 text-[10px] font-bold" x-on:click="cycleAlign(ci)" data-col-align>
                  <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <path x-show="alignOf(ci) === 'left'" d="M3 6h18M3 12h12M3 18h16" />
                    <path x-show="alignOf(ci) === 'center'" d="M3 6h18M6 12h12M4 18h16" />
                    <path x-show="alignOf(ci) === 'right'" d="M3 6h18M9 12h12M5 18h16" />
                  </svg>
                </button>
                <button type="button" title="Hapus kolom" class="{{ $danger }} ml-auto" x-bind:disabled="columns.length <= 1" x-on:click="removeColumn(ci)" data-col-remove>{!! $trash !!}</button>
              </div>
            </th>
          </template>
          <th class="w-9 border-l border-gray-200 px-1 align-middle">
            <button type="button" title="Tambah kolom" class="{{ $btn }} !h-6 !w-6 text-foresty hover:!bg-foresty/10" x-bind:disabled="! canAddColumn" x-on:click="addColumn()" data-add-col>{!! $plus !!}</button>
          </th>
        </tr>
      </thead>
      <tbody>
        <template x-for="(row, ri) in rows" x-bind:key="ri">
          <tr class="border-t border-gray-200" data-row>
            <td class="bg-gray-50 px-1 py-1 align-top">
              <div class="flex items-start gap-0.5">
                <span class="w-3.5 pt-0.5 text-center text-[10px] font-semibold text-gray-400" x-text="ri + 1"></span>
                <div class="flex flex-col">
                  <button type="button" title="Naik" class="{{ $btn }} !h-4" x-bind:disabled="ri === 0" x-on:click="moveRow(ri, -1)" data-row-up>{!! $up !!}</button>
                  <button type="button" title="Turun" class="{{ $btn }} !h-4" x-bind:disabled="ri === rows.length - 1" x-on:click="moveRow(ri, 1)" data-row-down>{!! $down !!}</button>
                </div>
                <button type="button" title="Hapus baris" class="{{ $danger }}" x-on:click="removeRow(ri)" data-row-remove>{!! $trash !!}</button>
              </div>
            </td>
            <template x-for="(col, ci) in columns" x-bind:key="ci">
              <td class="border-l border-gray-200 p-1 align-top">
                <textarea
                  rows="1"
                  maxlength="600"
                  x-bind:value="cell(ri, ci)"
                  x-on:input="typeCell(ri, ci, $event.target.value)"
                  x-on:paste="pasteCell($event, ri, ci)"
                  x-bind:aria-label="'Baris ' + (ri + 1) + ', kolom ' + (ci + 1)"
                  class="{{ $input }} field-sizing-content min-h-7 resize-none"
                  x-bind:class="alignOf(ci) === 'center' ? 'text-center' : (alignOf(ci) === 'right' ? 'text-right' : '')"
                  data-cell
                ></textarea>
              </td>
            </template>
            <td class="border-l border-gray-200"></td>
          </tr>
        </template>
        <tr x-show="rows.length === 0" x-cloak>
          <td x-bind:colspan="columns.length + 2" class="px-3 py-3 text-center text-[11px] text-gray-400">Belum ada baris.</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="flex flex-wrap items-center gap-1.5">
    <button type="button" class="{{ $chip }}" x-bind:disabled="! canAddRow" x-on:click="addRow()" data-add-row>{!! $plus !!} Baris</button>
    <button type="button" class="{{ $chip }}" x-on:click="pasteOpen = ! pasteOpen" x-bind:aria-expanded="pasteOpen" data-import-toggle>Tempel dari spreadsheet</button>
  </div>

  <div x-show="pasteOpen" x-cloak class="flex flex-col gap-1.5 rounded-lg border border-gray-200 bg-gray-50 p-2.5" data-import>
    <p class="text-[11px] leading-relaxed text-gray-500">
      Salin sel dari Excel atau Google Sheets, tempel di sini. Mengganti seluruh tabel (hanya bahasa
      <b class="text-gray-700 uppercase" x-text="lang"></b> yang terisi). Tip: menempel beberapa sel langsung ke sebuah sel di kisi mengisi mulai dari sel itu.
    </p>
    <textarea x-model="pasteText" rows="4" class="{{ $input }} font-mono" placeholder="Tempel di sini…" data-import-text></textarea>
    <label class="flex items-center gap-1.5 text-[11px] text-gray-600">
      <input type="checkbox" x-model="headerFirst" class="rounded border-gray-300 text-foresty focus:ring-foresty" />
      Baris pertama = judul kolom
    </label>
    <div class="flex gap-1.5">
      <button type="button" x-on:click="applyImport()" x-bind:disabled="pasteText.trim() === ''" class="rounded-md bg-foresty px-3 py-1 text-[11px] font-bold text-white disabled:opacity-40" data-import-apply>Ganti isi tabel</button>
      <button type="button" x-on:click="pasteOpen = false; pasteText = ''" class="rounded-md border border-gray-200 bg-white px-3 py-1 text-[11px] font-bold text-gray-600">Batal</button>
    </div>
  </div>

  <p x-show="note" x-cloak x-text="note" class="rounded-md bg-amber-50 px-2 py-1.5 text-[11px] text-amber-800" data-note></p>
  <p class="text-[10px] leading-relaxed text-gray-400">Sel berupa teks biasa: baris baru tampil sebagai baris baru, <b>**tebal**</b> dengan dua bintang, alamat https:// menjadi tautan.</p>
</div>
