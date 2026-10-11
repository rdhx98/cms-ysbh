{{--
  <x-editor.url-link> — pemilih tautan yang menyimpan SATU teks (rilis 39), untuk tempat yang sudah menyimpan alamat sebagai teks
  (tautan seluruh kartu di Kartu Builder). Nilai di state: "" | "internal://page/{slug}" | "internal://article/{slug}" | "https://…" | "/jalur".
  Nama tujuan yang dipilih ditulis ke saudaranya "{key}_label" (hanya tampilan di panel; tidak pernah dipakai sebagai href).
  Halaman/artikel dicari lewat aksi server searchLinkTargets() (sama dengan <x-editor.link>); keamanan alamat ditegakkan di SERVER (CoreBlocks::link).
--}}
@props([
  'path' => null,
  'rel' => null,
  'relExpr' => null,
  'label' => 'Tautan',
  'live' => null,
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
  $input = 'focus:border-foresty focus:ring-foresty w-full rounded-lg border-gray-200 p-2 text-xs shadow-sm';
@endphp

<div x-data="urlLinkField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, @js($isLive))" data-url-link {{ $attributes->class('flex flex-col gap-1.5') }}>
  <span class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</span>

  <select x-bind:value="mode" x-on:change="setMode($event.target.value)" aria-label="Jenis tautan" class="hover:border-foresty w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-[11px] shadow-sm focus:outline-none">
    <option value="none">Tanpa tautan</option>
    <option value="page">Halaman</option>
    <option value="article">Artikel</option>
    <option value="url">URL luar / jalur</option>
  </select>

  {{-- Halaman / Artikel: cari judul, simpan internal://… --}}
  <div x-show="mode === 'page' || mode === 'article'" x-cloak class="relative flex flex-col gap-1.5">
    <div x-show="internal" x-cloak class="bg-sage-soft text-foresty flex items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
      <span class="truncate" x-text="shownLabel" data-picked></span>
      <button type="button" aria-label="Lepas pilihan" x-on:click="clear()" class="rounded px-1 leading-none hover:bg-black/10">×</button>
    </div>
    <input
      x-show="! internal"
      type="text"
      x-model="q"
      x-on:input.debounce.250ms="search()"
      x-on:focus="open = true"
      x-on:keydown.escape="open = false"
      x-on:blur="setTimeout(() => open = false, 150)"
      autocomplete="off"
      x-bind:placeholder="mode === 'page' ? 'Cari judul halaman…' : 'Cari judul artikel…'"
      class="{{ $input }}"
      data-search
    />
    <div x-show="open && ! internal && (results.length || (q.trim().length >= 2 && ! searching))" x-cloak class="absolute top-full z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
      <template x-for="r in results" x-bind:key="r.id">
        <button type="button" x-bind:disabled="! usable(r)" x-on:mousedown.prevent="pick(r)" class="hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-xs text-gray-600 disabled:cursor-not-allowed disabled:opacity-50" data-result>
          <span class="truncate" x-text="r.label"></span>
          <span class="shrink-0 text-[9px] font-bold uppercase" x-bind:class="! usable(r) ? 'text-red-500' : (r.hint === 'online' || r.hint === 'published') ? 'text-emerald-600' : 'text-amber-600'" x-text="usable(r) ? r.hint : 'tanpa slug'"></span>
        </button>
      </template>
      <p x-show="! results.length && ! searching" class="px-3 py-2 text-xs text-gray-400">Tidak ada yang cocok.</p>
    </div>
    <p x-show="internal" x-cloak class="text-[10px] leading-relaxed text-gray-400">Kartu hanya menjadi tautan bila tujuannya sudah <span class="font-semibold">online / terbit</span>.</p>
  </div>

  {{-- URL luar atau jalur: diketik --}}
  <div x-show="mode === 'url'" x-cloak class="flex flex-col gap-1">
    <input
      type="text"
      x-bind:value="typed"
      x-on:input="type($event.target.value)"
      autocomplete="off"
      spellcheck="false"
      placeholder="https://… atau /halaman"
      class="{{ $input }}"
      data-typed
    />
    <p x-show="urlWarning" x-cloak class="text-[10px] font-semibold text-amber-600" x-text="urlWarning"></p>
  </div>
</div>
