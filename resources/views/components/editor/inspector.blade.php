{{--
  <x-editor.inspector> — panel Properti. Dirender SEKALI untuk semua blok: satu panel per TIPE
  (dibentuk dari App\Editor\BlockRegistry), dan panel yang tampil mengikuti $store.editor.panel.
  Berpindah fokus hanya mengganti base path di store, jadi tanpa request dan tanpa render ulang.

  wire:ignore: markup ini statis; seluruh state-nya ada di Alpine dan $wire.
--}}
@props(['locales' => ['id', 'en'], 'types' => null])

@php
  $types = $types ?? \App\Editor\BlockRegistry::all();
  $anchor = \App\Editor\Field::text('anchor', 'ID Tautan (Anchor)', ['slug' => true, 'placeholder' => 'nama-anchor']);
  $crumbLabels = collect($types)->map(fn ($d) => $d->label)->all(); // 'block:heading' => 'Judul', 'element:text' => 'Teks', ...
@endphp

<div wire:ignore x-data {{ $attributes->class('text-sm') }}>
  {{-- Rilis 65: breadcrumb blok bertingkat (menggantikan tombol kembali per elemen). Hanya tampil bila ada tingkat di atasnya. --}}
  <nav
    x-data="crumbs(@js($crumbLabels))"
    x-show="trail.length > 1"
    x-cloak
    aria-label="Lokasi di dalam blok bertingkat"
    data-crumbs
    class="sticky top-0 z-10 -mx-1 mb-3 flex items-start gap-1.5 border-b border-gray-100 bg-white/95 px-1 pb-2 backdrop-blur"
  >
    <button
      type="button"
      x-on:click="up()"
      data-back-to-parent
      title="Naik satu tingkat"
      aria-label="Naik satu tingkat"
      class="hover:bg-sage-soft hover:text-forest mt-px flex h-5 w-5 shrink-0 items-center justify-center rounded border border-gray-200 text-gray-600"
    >
      <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
    </button>
    <ol class="flex min-w-0 flex-wrap items-center gap-x-1 gap-y-0.5 text-[10px] font-bold">
      <template x-for="(c, i) in trail" x-bind:key="i + ':' + c.label">
        <li class="flex min-w-0 items-center gap-1">
          <svg x-show="i > 0" class="h-2.5 w-2.5 shrink-0 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
          <button
            type="button"
            x-show="c.kind !== 'current'"
            x-on:click="go(c)"
            data-crumb-link
            class="hover:text-forest max-w-[8.5rem] truncate text-gray-500 underline-offset-2 hover:underline"
            x-text="c.label"
          ></button>
          <span x-show="c.kind === 'current'" aria-current="page" data-crumb-current class="max-w-[10rem] truncate text-gray-800" x-text="c.label"></span>
        </li>
      </template>
    </ol>
  </nav>

  <div x-show="! $store.editor.panel" class="rounded-xl bg-gray-50 p-4 text-xs leading-relaxed text-gray-500">
    Pilih sebuah blok atau elemen untuk mengubah isi dan gayanya.
  </div>

  {{-- Blok/elemen yang belum didaftarkan di BlockRegistry (mis. tipe lama yang tidak dikenal): jelaskan, jangan biarkan panel kosong --}}
  <div
    x-show="$store.editor.panel && ! @js(array_keys($types)).includes($store.editor.panel)"
    x-cloak
    class="rounded-xl bg-amber-50 p-4 text-xs leading-relaxed text-amber-800"
  >
    Tipe ini belum punya panel di inspektur (belum ada di <code>BlockRegistry</code>). Ubah lewat editor lama.
  </div>

  @foreach ($types as $panel => $def)
    <section x-show="$store.editor.panel === @js($panel)" x-cloak data-panel="{{ $panel }}" class="flex flex-col gap-4">
      <div class="flex items-center gap-2">
        <span class="bg-foresty rounded px-2 py-0.5 text-[10px] font-extrabold tracking-widest text-white uppercase">{{ $def->label }}</span>
        <span class="text-[10px] font-semibold text-gray-400 uppercase">{{ $def->kind === 'block' ? 'Blok' : 'Elemen kartu' }}</span>
      </div>

      @if ($def->component && $def->componentFirst)
        <x-dynamic-component :component="$def->component" :locales="$locales" />
      @endif

      @foreach ($def->contentFields() as $field)
        <x-editor.field :field="$field" :locales="$locales" />
      @endforeach

      @if ($def->styleFields())
        <div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
          @foreach ($def->styleFields() as $field)
            <x-editor.field :field="$field" :locales="$locales" />
          @endforeach
        </div>
      @endif

      @if ($def->component && ! $def->componentFirst)
        <x-dynamic-component :component="$def->component" :locales="$locales" />
      @endif


      @if ($def->kind === 'block')
        <x-editor.field :field="$anchor" :locales="$locales" />
      @endif
    </section>
  @endforeach
</div>
