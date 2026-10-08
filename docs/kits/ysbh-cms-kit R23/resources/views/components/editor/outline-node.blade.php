{{--
  <x-editor.outline-node> — satu baris di pohon struktur, dirender REKURSIF untuk anak-anaknya.
  Struktur (blok apa ada di mana) datang dari server; yang reaktif di klien hanya label dan sorotan fokus,
  sehingga berpindah fokus dan mengetik judul tidak memerlukan request.
--}}
@props(['id', 'content', 'depth' => 0, 'index' => 0, 'count' => 1])

@php
  $block = $content[$id] ?? null;
  $type = \App\Editor\BlockPalette::canonical($block['type'] ?? '');
  $zones = $block ? \App\Editor\BlockPalette::zonesFor($block) : [];
  $childTotal = 0;
  foreach ($zones as $z) {
      $childTotal += count($block['data'][$z['key']] ?? []);
  }
  $pad = 6 + $depth * 14;
@endphp

@if ($block)
  <div wire:key="outline-{{ $id }}">
    <div
      class="group flex items-center rounded-md"
      x-bind:class="$store.editor.id === @js($id) ? 'bg-foresty text-white' : 'hover:bg-sage-soft'"
    >
      <button
        type="button"
        x-on:click="$store.editor.focusBlock(@js($id), @js($type))"
        class="flex min-w-0 flex-1 items-center gap-1.5 py-1.5 pr-1 text-left"
        style="padding-left: {{ $pad }}px"
      >
        <svg class="h-3.5 w-3.5 shrink-0"><use href="#icon-{{ \App\Editor\BlockPalette::icon($type) }}"></use></svg>
        <span class="truncate text-xs font-semibold" x-text="label(@js($id)) || @js(\App\Editor\BlockPalette::label($type))"></span>
        <span class="shrink-0 text-[9px] font-bold tracking-wide uppercase opacity-60" x-show="label(@js($id))">{{ \App\Editor\BlockPalette::label($type) }}</span>
      </button>

      <div class="flex shrink-0 items-center pr-1 opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100 [@media(hover:none)]:opacity-100">
        <button type="button" title="Naik" @disabled($index === 0) x-on:click.stop="$wire.moveBlock(@js($id), -1)" class="rounded p-1 hover:bg-black/10 disabled:opacity-30">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6" /></svg>
        </button>
        <button type="button" title="Turun" @disabled($index === $count - 1) x-on:click.stop="$wire.moveBlock(@js($id), 1)" class="rounded p-1 hover:bg-black/10 disabled:opacity-30">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
        </button>
        <button type="button" title="Duplikat" x-on:click.stop="$wire.duplicateBlock(@js($id))" class="rounded p-1 hover:bg-black/10">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" /></svg>
        </button>
        <button
          type="button"
          title="Hapus"
          x-on:click.stop="
            @if ($childTotal > 0) if (! confirm(@js('Hapus blok ini beserta ' . $childTotal . ' blok di dalamnya?'))) { return };@endif
            removed();
            $wire.removeBlock(@js($id));
          "
          class="rounded p-1 hover:bg-red-500/20"
        >
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>
        </button>
      </div>
    </div>

    {{-- Zona kontainer (kolom / langkah) beserta anak-anaknya --}}
    @foreach ($zones as $zone)
      @php $children = $block['data'][$zone['key']] ?? []; @endphp
      <div wire:key="outline-{{ $id }}-{{ $zone['key'] }}" class="mt-0.5">
        @if (count($zones) > 1)
          <p class="py-0.5 text-[9px] font-bold tracking-wider text-gray-400 uppercase" style="padding-left: {{ $pad + 14 }}px">
            {{ $zone['label'] }}@if ($zone['hidden']) <span class="text-amber-600">(tersembunyi: melebihi jumlah kolom)</span>@endif
          </p>
        @endif

        @foreach ($children as $i => $childId)
          <x-editor.outline-node :id="$childId" :content="$content" :depth="$depth + 1" :index="$i" :count="count($children)" />
        @endforeach

        @unless ($zone['hidden'])
          <div class="my-1">
            <x-editor.add-menu
              :parent="$id"
              :zone="$zone['key']"
              :types="\App\Editor\BlockPalette::childTypes($type)"
              :label="count($zones) > 1 ? 'Tambah ke ' . $zone['label'] : 'Tambah ' . strtolower($zone['label'])"
              :indent="$pad + 14"
            />
          </div>
        @endunless
      </div>
    @endforeach
  </div>
@endif
