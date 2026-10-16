{{--
  <x-editor.add-card-menu> — tombol "Tambah kartu" + popover templat (rilis 67).

  Templat: Tumpuk (1 kolom), Ikon + teks (2 kolom, 1:3), Dokumen (3 kolom, 1:2:1), Kosong (tanpa kolom). Tiap templat bergambar mini lebar kolomnya.
  Satu komponen, tiga rupa:
    variant="header" : tombol kecil di judul daftar kartu
    variant="full"   : tombol putus-putus selebar panel (di bawah daftar kartu; langsung tampak tanpa menggulir ke atas)
    variant="row"    : ikon "+" di bilah hover baris outline sebuah Kartu Builder (butuh :bid)
  Tanpa :bid, sasarannya blok Kartu Builder yang sedang difokus (panel kanan). Popover `fixed`, menutup saat templat dipilih, Esc, klik di luar,
  gulir, atau ubah ukuran (pola yang sama dengan add-menu dan pemilih warna).
--}}
@props([
  'bid' => null,
  'variant' => 'header',
  'label' => 'Tambah kartu',
])

@php
  use App\Editor\CardPanel;

  $trigger = match ($variant) {
      'full' => 'hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-center gap-1.5 rounded-md border border-dashed border-gray-300 px-2 py-1.5 text-[11px] font-bold text-gray-500 transition-colors disabled:cursor-not-allowed disabled:opacity-40',
      'row' => 'rounded p-1 hover:bg-black/10 disabled:opacity-30',
      default => 'hover:bg-sage-soft hover:text-foresty flex items-center gap-1 rounded-md border border-gray-300 px-2 py-1 text-[11px] font-bold text-gray-600 transition-colors disabled:cursor-not-allowed disabled:opacity-40',
  };
  $plus = '<svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>';
@endphp

<div
  x-data="addCardMenu(@js(['bid' => $bid, 'max' => CardPanel::MAX_CARDS]))"
  x-on:keydown.escape.window="open = false"
  x-on:resize.window="open = false"
  x-on:scroll.window.capture.passive="if (open && $event.target !== $refs.pop) open = false"
  @class([$variant === 'full' ? 'w-full' : 'inline-flex'])
  data-add-card
  data-add-card-variant="{{ $variant }}"
>
  <button
    type="button"
    x-ref="trigger"
    x-on:click.stop="toggle()"
    x-bind:disabled="full || busy"
    x-bind:title="full ? 'Batas kartu tercapai' : @js($label)"
    x-bind:aria-expanded="open"
    aria-haspopup="menu"
    aria-label="{{ $label }}"
    data-add-card-trigger
    class="{{ $trigger }} focus-visible:ring-forest focus:outline-none focus-visible:ring-2"
  >
    {!! $plus !!}
    @if ($variant !== 'row')
      {{ $label }}
    @endif
  </button>

  <div x-show="open" x-cloak x-on:click.stop="open = false" x-on:wheel.passive="open = false" class="fixed inset-0 z-40"></div>

  <div
    x-ref="pop"
    x-show="open"
    x-cloak
    x-bind:style="style"
    role="menu"
    aria-label="Templat kartu"
    data-add-card-popover
    class="fixed z-50 flex flex-col gap-0.5 overflow-y-auto overscroll-contain rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl"
  >
    <p class="px-2 pt-1 pb-0.5 text-[10px] font-semibold tracking-wider text-gray-400 uppercase">Pilih templat</p>
    @foreach (CardPanel::PRESETS as $preset => $presetLabel)
      <button
        type="button"
        role="menuitem"
        x-on:click.stop="pick(@js($preset))"
        data-add-card-preset="{{ $preset }}"
        class="hover:bg-sage-soft focus-visible:bg-sage-soft flex w-full items-center gap-2.5 rounded-lg px-2 py-1.5 text-left outline-none"
      >
        <span class="flex h-7 w-12 shrink-0 items-stretch gap-0.5 rounded border border-gray-200 bg-gray-50 p-0.5" aria-hidden="true">
          @forelse (CardPanel::PRESET_SHAPES[$preset] as $w)
            <span class="bg-foresty/40 rounded-sm" style="flex: {{ (int) $w }} 1 0%"></span>
          @empty
            <span class="flex-1 rounded-sm border border-dashed border-gray-300"></span>
          @endforelse
        </span>
        <span class="min-w-0">
          <span class="block text-xs font-bold text-gray-700">{{ explode(' (', $presetLabel)[0] }}</span>
          <span class="block truncate text-[10px] text-gray-400">{{ CardPanel::PRESET_HINTS[$preset] }}</span>
        </span>
      </button>
    @endforeach
  </div>
</div>
