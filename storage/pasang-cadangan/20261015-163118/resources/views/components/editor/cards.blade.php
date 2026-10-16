{{--
  <x-editor.cards> — daftar Kartu → Kolom → Elemen untuk blok Kartu Builder (rilis 32). Dipasang BlockRegistry lewat BlockType::$component.

  Data dibaca langsung dari state ($wire.content.{id}.data.cards), jadi daftar ini ikut berubah seketika setelah aksi server selesai.
  Perubahan STRUKTUR (tambah, hapus, duplikat, geser) memanggil aksi Livewire; perubahan NILAI (warna, lebar, ...) memakai kontrol biasa
  (wireField) dengan path berindeks, sama seperti <x-editor.repeater>. Klik sebuah elemen membuka panelnya (element:*) di inspektur,
  dengan tombol kembali ke sini.

  Kunci DOM = indeks (bukan id), supaya pindah urutan hanya mengganti nilai, tidak membangun ulang kontrol.
--}}
@props(['locales' => ['id', 'en']])

@php
  use App\Editor\CardPanel;

  $btn = 'rounded p-1 text-gray-400 outline-none hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-30';
  $dangerBtn = $btn . ' hover:!bg-red-50 hover:!text-red-500';
  $up = '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6" /></svg>';
  $down = '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>';
  $left = '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>';
  $right = '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>';
  $copy = '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" /></svg>';
  $trash = '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>';
@endphp

<div
  x-data="cardPanel(@js(['cards' => CardPanel::MAX_CARDS, 'columns' => CardPanel::MAX_COLUMNS, 'elements' => CardPanel::MAX_ELEMENTS]), @js(CardPanel::elementLabels()))"
  {{ $attributes->class('flex flex-col gap-2') }}
  data-card-panel
>
  <div class="flex items-center justify-between">
    <span class="text-xxs font-bold text-gray-700 uppercase">Kartu <span class="font-semibold text-gray-400" x-text="'(' + cards.length + ')'"></span></span>
  </div>

  {{-- Tambah kartu: pilih titik awal --}}
  <div class="flex flex-wrap items-center gap-1.5" data-add-card>
    @foreach (CardPanel::PRESETS as $preset => $label)
      <button
        type="button"
        x-on:click="addCard(@js($preset))"
        x-bind:disabled="cards.length >= limits.cards"
        class="hover:bg-sage-soft hover:text-foresty rounded-md border border-dashed border-gray-300 px-2 py-1 text-[11px] font-bold text-gray-500 transition-colors disabled:cursor-not-allowed disabled:opacity-40"
      >+ {{ $label }}</button>
    @endforeach
  </div>

  <p x-show="cards.length === 0" x-cloak class="rounded-lg bg-gray-50 p-3 text-xs text-gray-500">Belum ada kartu. Pilih titik awal di atas.</p>

  <template x-for="(card, ci) in cards" x-bind:key="ci">
    <div class="rounded-lg border bg-white" x-bind:class="openCard === ci ? 'border-foresty/40 shadow-sm' : 'border-gray-200'" data-card>
      <div class="flex items-center gap-0.5 pr-1">
        <button type="button" x-on:click="toggleCard(ci)" x-bind:aria-expanded="openCard === ci" class="flex min-w-0 flex-1 items-center gap-2 px-2.5 py-2 text-left">
          <svg class="h-3 w-3 shrink-0 text-gray-400 transition-transform" x-bind:class="openCard === ci ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
          <span class="truncate text-xs font-semibold text-gray-700" x-text="'Kartu ' + (ci + 1) + ' · ' + cardSummary(card)"></span>
        </button>
        <button type="button" title="Naik" class="{{ $btn }}" x-bind:disabled="ci === 0" x-on:click="moveCard(ci, -1)">{!! $up !!}</button>
        <button type="button" title="Turun" class="{{ $btn }}" x-bind:disabled="ci === cards.length - 1" x-on:click="moveCard(ci, 1)">{!! $down !!}</button>
        <button type="button" title="Duplikat" class="{{ $btn }}" x-bind:disabled="cards.length >= limits.cards" x-on:click="duplicateCard(ci)">{!! $copy !!}</button>
        <button type="button" title="Hapus" class="{{ $dangerBtn }}" x-on:click="removeCard(ci)">{!! $trash !!}</button>
      </div>

      <div x-show="openCard === ci" x-cloak class="flex flex-col gap-3 border-t border-gray-100 p-3">
        {{-- Tampilan kartu (latar, ruang, garis, bayangan, tautan) --}}
        <div class="rounded-md border border-gray-100 bg-gray-50">
          <button type="button" x-on:click="styleOpen = styleOpen === ci ? null : ci" class="flex w-full items-center gap-2 px-2.5 py-1.5 text-left text-[11px] font-bold text-gray-600" x-bind:aria-expanded="styleOpen === ci">
            <svg class="h-3 w-3 shrink-0 text-gray-400 transition-transform" x-bind:class="styleOpen === ci ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
            Tampilan kartu
          </button>
          <div x-show="styleOpen === ci" x-cloak class="flex flex-wrap gap-4 border-t border-gray-100 p-3">
            @foreach (CardPanel::containerFields() as $field)
              <x-editor.field :field="$field" :locales="$locales" prefix="'data.cards.' + ci + '.container'" />
            @endforeach
          </div>
        </div>

        {{-- Kolom --}}
        <div class="flex items-center justify-between">
          <span class="text-[10px] font-bold tracking-wide text-gray-500 uppercase">Kolom <span class="font-semibold text-gray-400" x-text="'(' + columns(card).length + ')'"></span></span>
          <button
            type="button"
            x-on:click="act('addColumnToCard', ci)"
            x-bind:disabled="columns(card).length >= limits.columns"
            class="hover:bg-sage-soft hover:text-foresty rounded-md border border-dashed border-gray-300 px-2 py-0.5 text-[11px] font-bold text-gray-500 disabled:cursor-not-allowed disabled:opacity-40"
          >+ Kolom</button>
        </div>

        <template x-for="(col, co) in columns(card)" x-bind:key="co">
          <div class="rounded-md border border-gray-200" data-column>
            <div class="flex items-center gap-0.5 border-b border-gray-100 bg-gray-50 pr-1">
              <span class="flex-1 px-2.5 py-1.5 text-[11px] font-bold text-gray-600" x-text="'Kolom ' + (co + 1)"></span>
              <button type="button" title="Geser ke kiri" class="{{ $btn }}" x-bind:disabled="co === 0" x-on:click="act('moveCardColumn', ci, co, 'left')">{!! $left !!}</button>
              <button type="button" title="Geser ke kanan" class="{{ $btn }}" x-bind:disabled="co === columns(card).length - 1" x-on:click="act('moveCardColumn', ci, co, 'right')">{!! $right !!}</button>
              <button type="button" title="Hapus kolom" class="{{ $dangerBtn }}" x-on:click="removeColumn(ci, co, col)">{!! $trash !!}</button>
            </div>

            <div class="flex flex-col gap-3 p-2.5">
              <div class="flex flex-wrap gap-4">
                @foreach (CardPanel::columnFields() as $field)
                  <x-editor.field :field="$field" :locales="$locales" prefix="'data.cards.' + ci + '.layout.children.' + co" />
                @endforeach
              </div>

              {{-- Elemen di kolom ini --}}
              <ul class="flex flex-col gap-1">
                <li x-show="elements(col).length === 0" x-cloak class="rounded bg-gray-50 px-2 py-1.5 text-[11px] text-gray-500">Kolom kosong. Tambahkan elemen di bawah.</li>
                <template x-for="(el, ei) in elements(col)" x-bind:key="ei">
                  <li class="flex items-center gap-0.5 rounded border border-gray-100 bg-white pr-1" data-element>
                    <button
                      type="button"
                      x-on:click="focusEl(ci, co, ei, el)"
                      x-bind:disabled="! hasPanel(el)"
                      x-bind:title="hasPanel(el) ? 'Buka pengaturan elemen' : 'Jenis elemen ini belum punya panel'"
                      class="hover:text-foresty flex min-w-0 flex-1 items-center gap-1.5 px-2 py-1.5 text-left disabled:cursor-not-allowed disabled:opacity-60"
                    >
                      <span class="bg-sage-soft text-foresty shrink-0 rounded px-1.5 text-[9px] font-bold uppercase" x-text="typeLabel(el)"></span>
                      <span class="truncate text-[11px] text-gray-600" x-text="elementSummary(el)"></span>
                    </button>
                    <button type="button" title="Naik" class="{{ $btn }}" x-bind:disabled="ei === 0" x-on:click="act('moveCardElement', ci, co, ei, 'up')">{!! $up !!}</button>
                    <button type="button" title="Turun" class="{{ $btn }}" x-bind:disabled="ei === elements(col).length - 1" x-on:click="act('moveCardElement', ci, co, ei, 'down')">{!! $down !!}</button>
                    <button type="button" title="Hapus elemen" class="{{ $dangerBtn }}" x-on:click="removeElement(ci, col, el)">{!! $trash !!}</button>
                  </li>
                </template>
              </ul>

              <select
                x-on:change="addElement(ci, col, $event.target.value); $event.target.value = ''"
                x-bind:disabled="elements(col).length >= limits.elements"
                class="focus:border-foresty focus:ring-foresty w-full rounded-md border-gray-300 py-1 text-[11px] font-semibold text-gray-600 disabled:opacity-40"
                data-add-element
              >
                <option value="">+ Tambah elemen…</option>
                @foreach (CardPanel::elementTypes() as $t)
                  <option value="{{ $t['type'] }}">{{ $t['label'] }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </template>
      </div>
    </div>
  </template>
</div>
