{{--
  <x-editor.add-menu> — tombol "+ Tambah …" untuk tingkat atas dan tiap zona kontainer.
  Memanggil aksi server addBlockAt(parent, zone, type), yang memvalidasi ulang parent/zona/tipe (argumen datang dari browser).

  - Hanya SATU tipe yang diizinkan (mis. langkah di step-group) -> tombol langsung, tanpa menu.
  - Menu SELEBAR TOMBOL, satu kolom, dan `fixed` (rilis 62). Dulu `absolute` di dalam sidebar ber-overflow-y-auto: menu yang melewati tepi
    bawah layar memperpanjang area gulir sidebar, bilah gulir muncul, tetapi tidak bisa digulir (penutup `fixed inset-0` menelan roda dan
    klik pada bilah), jadi blok di bagian bawah daftar tidak terpilih. Kini posisi dihitung dari tombol, tinggi menu dibatasi ke ruang
    yang ada di layar (naik ke atas tombol bila ruang bawah sempit), dan daftar bergulir DI DALAM menu; sidebar tidak ikut memanjang.
  - Tanpa .outside: penutup transparan di belakang menu, supaya tidak bergantung pada urutan event klik. Roda/gulir di luar menu menutupnya.
--}}
@props ([
  "parent" => null,
  "zone" => null,
  "types" => [],
  "label" => "Tambah blok",
  "indent" => 0,
])

@php
  $types = array_values($types);
  $single = count($types) === 1 ? $types[0] : null;
  $groups = collect($types)->groupBy("group");
  $button =
    "hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-center gap-1.5 rounded-md border border-dashed border-gray-300 px-2 py-1.5 text-[11px] font-bold text-gray-500 transition-colors";
@endphp

@if ($single)
  <div style="padding-left: {{ $indent }}px">
    <button
      type="button"
      x-on:click="$wire.addBlockAt(@js($parent), @js($zone), @js($single['type']))"
      class="{{ $button }}"
    >
      <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
      {{ $label }}
    </button>
  </div>
@else
  <div
    x-data="{
      open: false,
      pos: { left: 0, width: 0, top: null, bottom: null, max: 320 },
      toggle() {
        if (this.open) {
          this.open = false;
          return;
        }
        const r = this.$refs.btn.getBoundingClientRect();
        const below = window.innerHeight - r.bottom - 12;
        const above = r.top - 12;
        const up = below < 200 && above > below;
        this.pos = {
          left: Math.round(r.left),
          width: Math.round(r.width),
          top: up ? null : Math.round(r.bottom + 4),
          bottom: up ? Math.round(window.innerHeight - r.top + 4) : null,
          max: Math.max(120, Math.min(320, up ? above : below)),
        };
        this.open = true;
      },
      get style() {
        const p = this.pos;
        return (
          'left:' +
          p.left +
          'px;width:' +
          p.width +
          'px;' +
          (p.top !== null
            ? 'top:' + p.top + 'px;'
            : 'bottom:' + p.bottom + 'px;') +
          'max-height:' +
          p.max +
          'px'
        );
      },
    }"
    x-on:keydown.escape.window="open = false"
    x-on:resize.window="open = false"
    x-on:scroll.window.capture.passive="
      if (open && $event.target !== $refs.list) open = false;
    "
    class="relative"
    style="padding-left: {{ $indent }}px"
    data-add-menu
  >
    <button
      type="button"
      x-ref="btn"
      x-on:click="toggle()"
      x-bind:aria-expanded="open"
      class="{{ $button }}"
    >
      <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
      {{ $label }}
    </button>

    <div
      x-show="open"
      x-cloak
      x-on:click="open = false"
      x-on:wheel.passive="open = false"
      class="fixed inset-0 z-20"
    ></div>

    <div
      x-ref="list"
      x-show="open"
      x-cloak
      x-bind:style="style"
      data-add-menu-list
      class="fixed z-30 overflow-y-auto overscroll-contain rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl"
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
              <span class="leading-tight">{{ $t["label"] }}</span>
            </button>
          @endforeach
        </div>
      @endforeach
    </div>
  </div>
@endif
