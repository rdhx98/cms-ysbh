{{--
  <x-editor.picker> — pemilih daftar kategori / artikel (rilis 48). Nilai di state: larik [{id, label}] (label hanya tampilan di inspektur).
  Mencari lewat aksi server searchLinkTargets(jenis, kata); urutan bisa digeser (urutan = urutan tampil untuk artikel yang disematkan).
  Keabsahan nilai (id positif, tanpa duplikat, batas jumlah) ditegakkan di SERVER (App\Content\Blocks\ArticleCards::picks).
--}}
@props([
  'path' => null,
  'rel' => null,
  'relExpr' => null,
  'label' => 'Pilihan',
  'kind' => 'article',
  'max' => 6,
  'placeholder' => 'Cari…',
  'hint' => '',
  'live' => null,
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
  $input = 'focus:border-foresty focus:ring-foresty w-full rounded-lg border-gray-200 p-2 text-xs shadow-sm';
@endphp

<div x-data="pickerField(@js($kind), @js((int) $max), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, @js($isLive))" {{ $attributes->class('flex flex-col gap-1.5') }}>
  <div class="flex items-baseline justify-between gap-2">
    <span class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</span>
    <span class="text-[10px] text-gray-400"><span x-text="items.length"></span>/{{ (int) $max }}</span>
  </div>

  <ol x-show="items.length" x-cloak class="flex flex-col gap-1">
    <template x-for="(item, i) in items" x-bind:key="item.id">
      <li class="bg-sage-soft text-foresty flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold">
        <span class="w-4 shrink-0 text-center text-[10px] opacity-60" x-text="i + 1"></span>
        <span class="min-w-0 flex-1 truncate" x-text="label(item)"></span>
        <button type="button" aria-label="Geser ke atas" x-bind:disabled="i === 0" x-on:click="move(i, -1)" class="rounded px-1 leading-none hover:bg-black/10 disabled:opacity-30">↑</button>
        <button type="button" aria-label="Geser ke bawah" x-bind:disabled="i === items.length - 1" x-on:click="move(i, 1)" class="rounded px-1 leading-none hover:bg-black/10 disabled:opacity-30">↓</button>
        <button type="button" aria-label="Lepas pilihan" x-on:click="remove(i)" class="rounded px-1 leading-none hover:bg-black/10">×</button>
      </li>
    </template>
  </ol>

  <div class="relative" x-show="! full" x-cloak>
    <input
      type="text"
      x-model="q"
      x-on:input.debounce.250ms="search()"
      x-on:focus="open = true; search()"
      x-on:keydown.escape="open = false"
      x-on:blur="setTimeout(() => open = false, 150)"
      autocomplete="off"
      placeholder="{{ $placeholder }}"
      class="{{ $input }}"
    />
    <div x-show="open && (results.length || (! searching && (q.trim().length >= 2 || '{{ $kind }}' === 'category')))" x-cloak class="absolute top-full z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
      <template x-for="r in results" x-bind:key="r.id">
        <button type="button" x-on:mousedown.prevent="add(r)" class="hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-xs text-gray-600">
          <span class="truncate" x-text="r.label"></span>
          <span class="shrink-0 text-[9px] font-bold uppercase" x-bind:class="(r.hint === 'online' || r.hint === 'published') ? 'text-emerald-600' : 'text-amber-600'" x-text="r.hint"></span>
        </button>
      </template>
      <p x-show="! results.length && ! searching" class="px-3 py-2 text-xs text-gray-400">Tidak ada yang cocok.</p>
    </div>
  </div>
  <p x-show="full" x-cloak class="text-[10px] text-gray-400">Batas {{ (int) $max }} pilihan tercapai. Lepas satu untuk menambah yang lain.</p>
  @if ($hint !== '')
    <p class="text-[10px] leading-relaxed text-gray-400">{{ $hint }}</p>
  @endif
</div>
