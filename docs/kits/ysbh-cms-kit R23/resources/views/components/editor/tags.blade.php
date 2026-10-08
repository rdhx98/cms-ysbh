{{--
  <x-editor.tags> — input tag: ketik lalu Enter/koma, atau pilih dari saran. Nilai = larik campuran
  (angka = ID tag lama, teks = nama tag baru); server memakai TIPE itu sebagai pembeda.
  options: [['id' => 1, 'name' => 'Imunisasi'], ...]
--}}
@props([
  'path' => 'tags',
  'options' => [],
  'label' => null,
  'placeholder' => 'Ketik tag, lalu Enter…',
  'live' => null,
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
@endphp

<div x-data="tagInput(@js($path), @js(array_values($options)), @js($isLive))" {{ $attributes->class('flex flex-col gap-1.5') }}>
  @if ($label)
    <span class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</span>
  @endif

  <div class="relative">
    <div class="focus-within:border-foresty flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-1.5 shadow-sm" x-on:click="$refs.q.focus()">
      <template x-for="(v, i) in list" x-bind:key="i + '-' + String(v)">
        <span class="bg-sage-soft text-foresty inline-flex items-center gap-1 rounded-md py-0.5 pr-1 pl-2 text-[11px] font-semibold">
          <span x-text="label(v)"></span>
          <button type="button" aria-label="Hapus tag" x-on:click.stop="remove(i)" class="rounded px-1 leading-none hover:bg-black/10">×</button>
        </span>
      </template>

      <input
        x-ref="q"
        x-model="q"
        type="text"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        x-on:keydown="key($event)"
        x-on:focus="open = true"
        x-on:blur="open = false; commit()"
        class="min-w-[7rem] flex-1 border-0 bg-transparent p-1 text-xs focus:ring-0"
      />
    </div>

    <div x-show="open && suggestions.length" x-cloak class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
      <template x-for="o in suggestions" x-bind:key="o.id">
        <button
          type="button"
          x-on:mousedown.prevent="pick(o)"
          class="hover:bg-sage-soft hover:text-foresty block w-full px-3 py-1.5 text-left text-xs text-gray-600"
          x-text="o.name"
        ></button>
      </template>
    </div>
  </div>
</div>
