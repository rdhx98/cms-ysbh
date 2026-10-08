{{--
  <x-editor.icon-picker-modal> — pasang SEKALI di page-editor (dekat sprite).
  Mendengarkan event window "open-icon-picker" { path, live } dari <x-editor.icon-picker>.
  Grid dibuat di sisi klien dari satu daftar nama (x-for) dan memakai sprite "#icon-{nama}".
--}}
@props([
  'prefix' => 'icon-',
  'icons' => null,
])

@php
  $names = $icons ?? collect(config('cms.lucide', []))->sort()->values()->all();
@endphp

<div
  wire:ignore
  x-data="{
    open: false,
    path: null,
    live: true,
    q: '',
    names: @js($names),
    prefix: @js($prefix),
    get shown() {
      const q = this.q.trim().toLowerCase()
      return q ? this.names.filter((n) => n.includes(q)) : this.names
    },
    get current() { return this.path ? this.$wire.$get(this.path) : null },
    show(d) {
      this.path = d.path
      this.live = d.live ?? true
      this.q = ''
      this.open = true
      this.$nextTick(() => this.$refs.q && this.$refs.q.focus())
    },
    close() { this.open = false },
    pick(n) {
      this.$wire.$set(this.path, n, this.live)
      this.close()
    },
  }"
  x-on:open-icon-picker.window="show($event.detail)"
  x-on:keydown.escape.window="open && close()"
  x-show="open"
  x-cloak
  x-on:click.self="close()"
  class="fixed inset-0 z-[10000] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
  role="dialog"
  aria-modal="true"
  aria-label="Pilih ikon"
>
  <div class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
    <div class="border-b border-gray-100 bg-gray-50/80 p-4">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-extrabold tracking-widest text-gray-800 uppercase">Pilih Ikon</h3>
        <button
          type="button"
          x-on:click="close()"
          aria-label="Tutup"
          class="rounded-full bg-gray-200 p-1 text-gray-500 transition-colors outline-none hover:bg-red-100 hover:text-red-500"
        >
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
        </button>
      </div>
      <input
        x-ref="q"
        x-model="q"
        type="text"
        placeholder="Cari nama ikon..."
        class="focus:border-forest w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm shadow-sm focus:ring-0"
      />
    </div>

    <div class="grid scrollbar-thin grid-cols-5 gap-2 overflow-y-auto p-4 sm:grid-cols-7">
      <template x-for="n in shown" x-bind:key="n">
        <button
          type="button"
          x-on:click="pick(n)"
          x-bind:title="n"
          x-bind:class="n === current
            ? 'bg-forest text-goldy shadow-md ring-2 ring-forest ring-offset-1'
            : 'bg-zinc-50 text-forest hover:bg-sage-soft'"
          class="flex aspect-square items-center justify-center rounded-xl outline-none transition-all hover:scale-110"
        >
          <svg class="h-5 w-5 shrink-0" stroke-width="2"><use x-bind:href="'#' + prefix + n"></use></svg>
        </button>
      </template>
      <p x-show="shown.length === 0" x-cloak class="col-span-full py-8 text-center text-xs text-gray-400">Tidak ada ikon yang cocok.</p>
    </div>
  </div>
</div>
