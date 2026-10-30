{{--
  <x-editor.outline> — pohon struktur halaman (panel kiri). Menggantikan <x-editor.outline-sementara>.

  Props: $content (blok per ID) dan $order (urutan tingkat atas) — properti Livewire builder.
  Aksi yang dipanggil di server (semuanya divalidasi di sana): addBlockAt, moveBlock, moveBlockTo, indentBlock, outdentBlock,
  moveTargets (hanya baca), duplicateBlock, removeBlock.
  Ikon memakai sprite (<x-editor.icon-sprite /> di layout).

  Rilis 43 — SEMUA operasi struktur dari outline (kanvas sengaja tanpa kontrol tambahan). Satu menu "⋯" untuk seluruh pohon:
  baris menyimpan konteksnya di data-ctx; menu ditaruh `fixed` di dekat tombol supaya tidak terpotong sidebar yang overflow.
  Daftar jenis untuk "Sisipkan di atas/bawah" dirender SEKALI per jenis daftar (tingkat atas + tiap jenis kontainer), bukan per baris.

  Rilis 96: tiap blok adalah kartu terpisah (jarak 3px) berlatar tipis dari warna seksinya; seksi dan kontainer digambar sebagai pohon bersiku
  (kepala seksi = induk blok-blok seksinya). Gaya di <style> di bawah. Bagian di bawah ini (rilis 95) tetap berlaku untuk penghitungan seksi.

  Rilis 95: blok tingkat atas dikelompokkan per SEKSI (dari satu Pemisah Seksi sampai pemisah berikutnya). Tiap seksi diberi latar tipis dari
  warna latar seksi yang sebenarnya (data.background -> slot palet -> hex) dan garis tepi kiri 3px, sehingga outline mencerminkan ritme warna
  halaman. Blok sebelum pemisah pertama tidak diberi latar. Semua nilai warna yang dicetak berasal dari palet (hex divalidasi), bukan dari
  teks penulis.
--}}
@props(['content' => [], 'order' => []])

@php
  // jenis daftar -> jenis blok yang boleh ada di dalamnya
  $lists = ['root' => \App\Editor\BlockPalette::rootTypes()];
  foreach (\App\Editor\BlockPalette::CONTAINERS as $container) {
      $lists[$container] = \App\Editor\BlockPalette::childTypes($container);
  }
  // Rilis 95: kelompok seksi. Satu elemen daftar = ['divider' => id|null, 'items' => [indeks => id], 'tint' => hex|null].
  $palette = [];
  try {
      $palette = class_exists(\App\Content\PublicLookup::class) ? \App\Content\PublicLookup::palette() : \App\Content\Palette::defaults();
  } catch (\Throwable) {
      $palette = [];
  }
  $tintOf = function (?string $bg) use ($palette): ?string {
      $slot = $bg !== null && str_starts_with($bg, 'bg-') && class_exists(\App\Content\Palette::class) ? \App\Content\Palette::slotFor(substr($bg, 3)) : null;
      $hex = $slot !== null ? ($palette[$slot]['hex'] ?? null) : null;

      return is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex) === 1 ? $hex : null;
  };
  $sections = [];
  $cur = ['divider' => null, 'items' => [], 'tint' => null];
  foreach ($order as $i => $oid) {
      if (\App\Editor\BlockPalette::canonical($content[$oid]['type'] ?? '') === 'section-divider') {
          if ($cur['items']) {
              $sections[] = $cur;
          }
          $cur = ['divider' => $oid, 'items' => [], 'tint' => $tintOf($content[$oid]['data']['background'] ?? 'bg-paper')];
      }
      $cur['items'][$i] = $oid;
  }
  if ($cur['items']) {
      $sections[] = $cur;
  }
  // Teks daftar jenis: SATU rupa untuk semua jenis (tanpa cabang per jenis), supaya semua nama jenis tampil sama.
  $itemText = 'block min-w-0 flex-1 text-[11px] leading-tight font-semibold tracking-normal normal-case';
  $item = 'hover:bg-sage-soft hover:text-foresty flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left text-[11px] font-semibold text-gray-600 transition-colors disabled:cursor-default disabled:opacity-40 disabled:hover:bg-transparent';
@endphp

<style>
  /* Rilis 96: kartu terpisah + pohon bersiku. Sengaja CSS biasa (bukan kelas Tailwind): garis pohon memakai ::before/::after dan variabel, dan
     tidak boleh bergantung pada pemindaian/`npm run build`. Warna: --ol-bg / --ol-bd diisi per baris (warna seksi), keadaan terpilih menimpanya. */
  .ol-panel { background: #f3f4f2; border: 1px solid #d6d9d4; border-radius: 10px; padding: 8px; --ol-line: #b4bdb7; }
  .ol-panel > [data-outline-section] + [data-outline-section] { margin-top: 5px; }
  .ol-item { position: relative; padding-top: 3px; --y: 17px; }
  .ol-item.ol-z { --y: 12px; }
  .ol-row { background: var(--ol-bg, #fff); border: 1px solid var(--ol-bd, #e5e7eb); color: #1f2937; }
  .ol-row.ol-sec { font-weight: 700; }
  .ol-row:hover, .ol-row:focus-within { background: var(--color-sage-soft, #e3ede6); }
  .ol-row.is-par { background: var(--color-sage-soft, #e3ede6); border-color: color-mix(in srgb, var(--color-foresty, #064f3b) 60%, transparent); color: var(--color-foresty, #064f3b); }
  .ol-row.is-sel { background: var(--color-foresty, #064f3b); border-color: var(--color-foresty, #064f3b); color: #fff; }
  .ol-zone { display: flex; align-items: center; gap: 5px; margin: 0; padding: 4px 2px 0; font-size: 10px; font-weight: 700; color: #6b7280; }
  .ol-zone::before { content: ""; width: 8px; height: 8px; border: 1.5px solid #9ca3af; border-radius: 2px; flex: none; }
  .ol-kids { position: relative; margin-left: 13px; padding-left: 12px; }
  .ol-kids > .ol-item::before { content: ""; position: absolute; left: -12px; top: 0; bottom: 0; border-left: 1.5px solid var(--ol-line); }
  .ol-kids > .ol-item::after { content: ""; position: absolute; left: -12px; top: calc(var(--y) + 3px); width: 11px; border-top: 1.5px solid var(--ol-line); }
  .ol-kids > .ol-item:last-child::before { bottom: auto; width: 12px; height: calc(var(--y) + 3px); border-bottom: 1.5px solid var(--ol-line); border-bottom-left-radius: 6px; }
  .ol-kids > .ol-item:last-child::after { display: none; }
</style>

<nav
  x-data="outline"
  x-on:block-added.window="added($event)"
  x-on:keydown.escape.window="if (menu.open) { closeMenu(true) }"
  aria-label="Struktur halaman"
  class="ol-panel flex flex-col text-xs"
>
  @forelse ($sections as $section)
    @php $head = $section['divider'] ? array_key_first($section['items']) : null; @endphp
    <div data-outline-section @if ($section['divider']) data-outline-section-of="{{ $section['divider'] }}" @endif>
      @foreach ($section['items'] as $i => $id)
        @if ($i === $head)
          {{-- kepala seksi (Pemisah Seksi) --}}
          <x-editor.outline-node :id="$id" :content="$content" :index="$i" :count="count($order)" :prev="$order[$i - 1] ?? null" :tint="$section['tint']" />
        @break
        @endif
      @endforeach
      {{-- isi seksi: anak kepala seksi di pohon (hanya tampilan; urutan, naik/turun, dan data-ctx tetap daftar tingkat atas) --}}
      @if ($section['divider'])
        @if (count($section['items']) > 1)
          <div class="ol-kids" data-outline-section-kids>
            @foreach ($section['items'] as $i => $id)
              @continue ($i === $head)
              <x-editor.outline-node :id="$id" :content="$content" :index="$i" :count="count($order)" :prev="$order[$i - 1] ?? null" :tint="$section['tint']" />
            @endforeach
          </div>
        @endif
      @else
        @foreach ($section['items'] as $i => $id)
          <x-editor.outline-node :id="$id" :content="$content" :index="$i" :count="count($order)" :prev="$order[$i - 1] ?? null" />
        @endforeach
      @endif
    </div>
  @empty
    <p class="rounded-lg bg-gray-50 p-3 leading-relaxed text-gray-500">Belum ada blok. Tambahkan yang pertama di bawah.</p>
  @endforelse

  <div class="mt-2">
    <x-editor.add-menu :types="\App\Editor\BlockPalette::rootTypes()" label="Tambah blok" />
  </div>

  {{-- Menu baris (satu untuk seluruh pohon) --}}
  <div x-show="menu.open" x-cloak x-on:click="closeMenu()" class="fixed inset-0 z-40"></div>
  <div
    x-show="menu.open"
    x-cloak
    role="menu"
    data-outline-menu
    x-bind:style="menuStyle"
    class="fixed z-50 w-[232px] overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 text-xs shadow-xl"
  >
    {{-- tampilan utama --}}
    <div x-show="menu.view === 'main'" class="flex flex-col gap-0.5">
      <button type="button" role="menuitem" x-show="menu.ctx && menu.ctx.canAdd" x-on:click="show('above')" class="{{ $item }}" data-act="above">
        <span class="flex-1">Sisipkan di atas…</span>
      </button>
      <button type="button" role="menuitem" x-show="menu.ctx && menu.ctx.canAdd" x-on:click="show('below')" class="{{ $item }}" data-act="below">
        <span class="flex-1">Sisipkan di bawah…</span>
      </button>
      <button type="button" role="menuitem" x-on:click="show('move')" class="{{ $item }}" data-act="move">
        <span class="flex-1">Pindahkan ke…</span>
      </button>
      <button type="button" role="menuitem" x-show="menu.ctx && menu.ctx.out" x-on:click="outdent()" class="{{ $item }}" data-act="out">
        <span class="flex-1">Keluarkan dari kontainer</span><kbd class="text-[9px] font-bold text-gray-400">Alt+⇧+←</kbd>
      </button>
      <button type="button" role="menuitem" x-show="menu.ctx && menu.ctx.in" x-on:click="indent()" class="{{ $item }}" data-act="in">
        <span class="flex-1">Masukkan ke <span x-text="menu.ctx && menu.ctx.inLabel"></span> di atas</span><kbd class="text-[9px] font-bold text-gray-400">Alt+⇧+→</kbd>
      </button>
      <p class="mt-1 border-t border-gray-100 px-2 pt-1.5 pb-0.5 text-[9px] leading-snug text-gray-400">Naik / turun: Alt+↑ / Alt+↓ saat baris terfokus.</p>
    </div>

    {{-- pilih jenis blok yang disisipkan: daftar sesuai jenis daftar baris (tingkat atas atau jenis kontainer induk) --}}
    @foreach ($lists as $kind => $types)
      <div x-show="(menu.view === 'above' || menu.view === 'below') && menu.ctx && menu.ctx.kind === @js($kind)" class="flex flex-col gap-0.5">
        <button type="button" x-on:click="show('main')" class="flex items-center gap-1 px-1.5 py-1 text-[10px] font-bold text-gray-500 hover:text-foresty">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
          <span x-text="menu.view === 'above' ? 'Sisipkan di atas' : 'Sisipkan di bawah'"></span>
        </button>
        @foreach (collect($types)->groupBy('group') as $group => $items)
          <p class="px-1.5 pt-1.5 pb-1 text-[9px] font-bold tracking-wider text-gray-400 uppercase">{{ $group }}</p>
          @foreach ($items as $t)
            <button type="button" role="menuitem" x-on:click="insert(@js($t['type']))" class="{{ $item }}" data-insert="{{ $t['type'] }}">
              <svg class="h-3.5 w-3.5 shrink-0"><use href="#icon-{{ $t['icon'] }}"></use></svg>
              <span class="{{ $itemText }}">{{ $t['label'] }}</span>
            </button>
          @endforeach
        @endforeach
      </div>
    @endforeach

    {{-- Pindahkan ke…: tujuan dihitung server saat dibuka --}}
    <div x-show="menu.view === 'move'" class="flex flex-col gap-0.5">
      <button type="button" x-on:click="show('main')" class="flex items-center gap-1 px-1.5 py-1 text-[10px] font-bold text-gray-500 hover:text-foresty">
        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
        Pindahkan ke…
      </button>
      <p x-show="loading" class="px-2 py-2 text-[11px] text-gray-400">Memuat…</p>
      <p x-show="failed" class="px-2 py-2 text-[11px] text-red-600">Gagal memuat tujuan. <button type="button" class="font-bold underline" x-on:click="loadTargets()">Coba lagi</button></p>
      <p x-show="! loading && ! failed && targets.length === 0" class="px-2 py-2 text-[11px] text-gray-400">Tidak ada tujuan lain untuk jenis blok ini.</p>
      <template x-for="(t, n) in targets" x-bind:key="n">
        <button type="button" role="menuitem" x-bind:disabled="t.current" x-on:click="moveTo(t)" class="{{ $item }}" data-target>
          <span class="flex-1 leading-tight" x-text="t.label"></span>
          <span x-show="t.current" class="text-[9px] font-bold text-gray-400">sekarang</span>
        </button>
      </template>
    </div>
  </div>
</nav>
