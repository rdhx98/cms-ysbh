{{--
  <x-editor.add-menu> — tombol "+ Tambah …" untuk tingkat atas dan tiap zona kontainer.
  Memanggil aksi server addBlockAt(parent, zone, type), yang memvalidasi ulang parent/zona/tipe (argumen datang dari browser).

  - Hanya SATU tipe yang diizinkan (mis. langkah di step-group) -> tombol langsung, tanpa menu.
  - Menu SELEBAR WADAH, satu kolom: sidebar ber-overflow-y-auto memotong apa pun yang melebar ke samping.
  - Tanpa .outside: penutup transparan di belakang menu, supaya tidak bergantung pada urutan event klik.
--}}
@props([
  'parent' => null,
  'zone' => null,
  'types' => [],
  'label' => 'Tambah blok',
  'indent' => 0,
])

@php
  $types = array_values($types);
  $single = count($types) === 1 ? $types[0] : null;
  $groups = collect($types)->groupBy('group');
  $button = 'hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-center gap-1.5 rounded-md border border-dashed border-gray-300 px-2 py-1.5 text-[11px] font-bold text-gray-500 transition-colors';
@endphp

@if ($single)
  <div style="padding-left: {{ $indent }}px">
    <button type="button" x-on:click="$wire.addBlockAt(@js($parent), @js($zone), @js($single['type']))" class="{{ $button }}">
      <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
      {{ $label }}
    </button>
  </div>
@else
  <div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="relative" style="padding-left: {{ $indent }}px">
    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" class="{{ $button }}">
      <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
      {{ $label }}
    </button>

    <div x-show="open" x-cloak x-on:click="open = false" class="fixed inset-0 z-20"></div>

    <div
      x-show="open"
      x-cloak
      class="absolute right-0 left-0 z-30 mt-1 max-h-80 overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl"
      style="margin-left: {{ $indent }}px"
    >
      @foreach ($groups as $group => $items)
        <p class="px-1.5 pt-1.5 pb-1 text-[9px] font-bold tracking-wider text-gray-400 uppercase">{{ $group }}</p>
        <div class="flex flex-col gap-0.5">
          @foreach ($items as $t)
            <button
              type="button"
              x-on:click="open = false; $wire.addBlockAt(@js($parent), @js($zone), @js($t['type']))"
              class="hover:bg-sage-soft hover:text-foresty flex items-center gap-2 rounded-lg px-2 py-1.5 text-left text-[11px] font-semibold text-gray-600 transition-colors"
            >
              <svg class="h-3.5 w-3.5 shrink-0"><use href="#icon-{{ $t['icon'] }}"></use></svg>
              <span class="leading-tight">{{ $t['label'] }}</span>
            </button>
          @endforeach
        </div>
      @endforeach
    </div>
  </div>
@endif
