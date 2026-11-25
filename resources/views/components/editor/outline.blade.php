{{--
  <x-editor.outline> — pohon struktur halaman (panel kiri). Menggantikan <x-editor.outline-sementara>.

  Props: $content (blok per ID) dan $order (urutan tingkat atas) — properti Livewire builder.
  Aksi yang dipanggil di server (semuanya divalidasi di sana): addBlockAt, moveBlock, moveBlockTo, indentBlock, outdentBlock,
  moveTargets (hanya baca), duplicateBlock, removeBlock.
  Ikon memakai sprite (<x-editor.icon-sprite /> di layout).

  Rilis 43 — SEMUA operasi struktur dari outline (kanvas sengaja tanpa kontrol tambahan). Satu menu "⋯" untuk seluruh pohon:
  baris menyimpan konteksnya di data-ctx; menu ditaruh `fixed` di dekat tombol supaya tidak terpotong sidebar yang overflow.
  Daftar jenis untuk "Sisipkan di atas/bawah" dirender SEKALI per jenis daftar (tingkat atas + tiap jenis kontainer), bukan per baris.

  Rilis 98: tanpa pembungkus terluar (tanpa latar, bingkai, dan padding di sekeliling semua seksi).

  Rilis 96: tiap blok adalah kartu terpisah (jarak 3px) berlatar tipis dari warna seksinya; seksi dan kontainer digambar sebagai pohon bersiku
  (kepala seksi = induk blok-blok seksinya). Gaya di <style> di bawah. Bagian di bawah ini (rilis 95) tetap berlaku untuk penghitungan seksi.

  Rilis 95: blok tingkat atas dikelompokkan per SEKSI (dari satu Pemisah Seksi sampai pemisah berikutnya). Tiap seksi diberi latar tipis dari
  warna latar seksi yang sebenarnya (data.background -> slot palet -> hex) dan garis tepi kiri 3px, sehingga outline mencerminkan ritme warna
  halaman. Blok sebelum pemisah pertama tidak diberi latar. Semua nilai warna yang dicetak berasal dari palet (hex divalidasi), bukan dari
  teks penulis.

  Rilis 117 — Pemisah Seksi sebagai PEMBUNGKUS: blok sebelum pemisah pertama adalah "Awal halaman" (baris maya, bukan blok; tidak bisa dipindah atau
  dihapus) dengan tombol "Bungkus dalam seksi"; kepala seksi punya panah lipat/buka dan menampilkan "N blok" saat terlipat; "Lipat semua / Buka semua"
  di atas; salin/hapus pada pemisah yang punya blok membuka dialog pilihan (hanya pemisah / seluruh seksi). Data tetap datar; yang baru hanya
  tampilan, aksi server (duplicateSection, removeSection, wrapPageStart, startSectionAt, dan moveBlock yang menggeser seluruh seksi), dan keadaan
  lipat di Alpine (hanya hidup selama halaman terbuka).
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
  // Rilis 117: metadata seksi untuk ctx baris. Seksi pertama tanpa pemisah = "Awal halaman".
  $hasHead = isset($sections[0]) && $sections[0]['divider'] === null;
  $firstDividerKey = null;
  foreach ($sections as $k => $sec) {
      if ($sec['divider'] !== null) {
          $firstDividerKey = $k;
          break;
      }
  }
  $secMeta = function (int $k) use ($sections, $hasHead, $firstDividerKey, $order, $content): ?array {
      $sec = $sections[$k] ?? null;
      if (! $sec || $sec['divider'] === null) {
          return null;
      }
      $ids = array_values(array_slice($sec['items'], 1));
      $sample = [];
      foreach (array_slice($ids, 0, 5) as $bid) {
          $sample[] = ['id' => $bid, 'type' => \App\Editor\BlockPalette::label(\App\Editor\BlockPalette::canonical($content[$bid]['type'] ?? ''))];
      }
      $first = $k === $firstDividerKey;

      return [
          'n' => count($ids), 'first' => $first, 'lockUp' => $first && $hasHead,
          'last' => array_key_last($sec['items']) === count($order) - 1, 'sample' => $sample,
      ];
  };
  $foldable = collect($sections)->filter(fn ($sec) => $sec['divider'] === null || count($sec['items']) > 1)->count();
  // Teks daftar jenis: SATU rupa untuk semua jenis (tanpa cabang per jenis), supaya semua nama jenis tampil sama.
  $itemText = 'block min-w-0 flex-1 text-[11px] leading-tight font-semibold tracking-normal normal-case';
  $item = 'hover:bg-sage-soft hover:text-foresty flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left text-[11px] font-semibold text-gray-600 transition-colors disabled:cursor-default disabled:opacity-40 disabled:hover:bg-transparent';
@endphp

<style>
  /* Rilis 96: kartu terpisah + pohon bersiku. Sengaja CSS biasa (bukan kelas Tailwind): garis pohon memakai ::before/::after dan variabel, dan
     tidak boleh bergantung pada pemindaian/`npm run build`. Warna: --ol-bg / --ol-bd diisi per baris (warna seksi), keadaan terpilih menimpanya. */
  /* Rilis 98: pembungkus terluar dihapus (wadah seksi sudah membungkus); .ol-panel kini hanya pembawa variabel garis pohon. */
  .ol-panel { --ol-line: #b4bdb7; }
  /* Rilis 97: seksi = wadah yang bergantian putih / abu-abu sedikit lebih gelap; kartu di dalamnya selalu putih. */
  .ol-sw { border-radius: 8px; padding: 4px 5px 5px; margin-bottom: 6px; border: 1px solid #d4d8d1; }
  .ol-sw-a { background: #ffffff; }
  .ol-sw-b { background: #e4e7e2; }
  .ol-item { position: relative; padding-top: 3px; --y: 17px; }
  .ol-item.ol-z { --y: 12px; }
  .ol-row { background: var(--ol-bg, #fff); border: 1px solid var(--ol-bd, #e5e7eb); color: #1f2937; }
  .ol-row.ol-sec { font-weight: 700; border-left: 5px solid var(--ol-bar, #cfd3cc); }
  .ol-chip { flex: none; width: 10px; height: 10px; margin-top: 2px; border-radius: 3px; border: 1px solid rgba(0, 0, 0, .18); }
  .ol-row:hover, .ol-row:focus-within { background: var(--color-sage-soft, #e3ede6); }
  .ol-row.is-par { background: var(--color-sage-soft, #e3ede6); border-color: color-mix(in srgb, var(--color-foresty, #064f3b) 60%, transparent); color: var(--color-foresty, #064f3b); }
  /* Terpilih (rilis 97): latar forest, border aurum, teks putih; cincin tipis aurum supaya mudah ditemukan di outline yang panjang. */
  .ol-row.is-sel { background: var(--color-foresty, #064f3b); border-color: var(--color-aurum, #e5c423); color: #fff; box-shadow: 0 0 0 1px var(--color-aurum, #e5c423), 0 0 0 3px rgba(229, 196, 35, .3); }
  .ol-zone { display: flex; align-items: center; gap: 5px; margin: 0; padding: 4px 2px 0; font-size: 10px; font-weight: 700; color: #6b7280; }
  .ol-zone::before { content: ""; width: 8px; height: 8px; border: 1.5px solid #9ca3af; border-radius: 2px; flex: none; }
  /* Rilis 117: panah lipat, baris "Awal halaman" (maya), jumlah blok saat terlipat, dialog seksi */
  .ol-fold { flex: none; display: flex; align-items: center; justify-content: center; width: 20px; margin-left: 2px; color: inherit; border-radius: 5px; }
  .ol-fold:hover { background: rgba(0, 0, 0, .08); }
  .ol-fold svg { transition: transform .15s; }
  .ol-fold[aria-expanded="false"] svg { transform: rotate(-90deg); }
  .ol-count { flex: none; align-self: center; margin-right: 6px; padding: 1px 6px; border-radius: 999px; background: #eceee9; color: #4b5563; font-size: 10px; font-weight: 700; white-space: nowrap; }
  .ol-row.is-sel .ol-count { background: rgba(255, 255, 255, .2); color: #fff; }
  .ol-row.ol-headrow { font-weight: 700; border-left: 5px solid #cfd3cc; border-style: dashed; border-left-style: solid; }
  .ol-wrap { flex: none; align-self: center; margin-right: 4px; padding: 2px 7px; border-radius: 6px; border: 1px solid #cfd3cc; background: #fff; color: var(--color-foresty, #064f3b); font-size: 10px; font-weight: 700; white-space: nowrap; }
  .ol-wrap:hover { background: var(--color-sage-soft, #e3ede6); border-color: var(--color-foresty, #064f3b); }
  .ol-kids { position: relative; margin-left: 13px; padding-left: 12px; }
  .ol-kids > .ol-item::before { content: ""; position: absolute; left: -12px; top: 0; bottom: 0; border-left: 1.5px solid var(--ol-line); }
  .ol-kids > .ol-item::after { content: ""; position: absolute; left: -12px; top: calc(var(--y) + 3px); width: 11px; border-top: 1.5px solid var(--ol-line); }
  .ol-kids > .ol-item:last-child::before { bottom: auto; width: 12px; height: calc(var(--y) + 3px); border-bottom: 1.5px solid var(--ol-line); border-bottom-left-radius: 6px; }
  .ol-kids > .ol-item:last-child::after { display: none; }
</style>

<nav
  x-data="outline"
  x-on:block-added.window="added($event)"
  x-on:keydown.escape.window="if (dlg.open) { closeDlg() } else if (menu.open) { closeMenu(true) }"
  aria-label="Struktur halaman"
  class="ol-panel flex flex-col text-xs"
>
  @if ($foldable > 0)
    <div class="mb-1 flex justify-end gap-1 px-1 text-[10px] font-bold text-gray-500" data-outline-fold-all>
      <button type="button" x-on:click="foldAll(true)" class="rounded px-1.5 py-0.5 hover:bg-black/5 hover:text-foresty" data-act="fold-all">Lipat semua</button>
      <button type="button" x-on:click="foldAll(false)" class="rounded px-1.5 py-0.5 hover:bg-black/5 hover:text-foresty" data-act="unfold-all">Buka semua</button>
    </div>
  @endif
  @forelse ($sections as $section)
    @php $head = $section['divider'] ? array_key_first($section['items']) : null; $k = $loop->index; $meta = $secMeta($k); @endphp
    <div wire:key="outline-sec-{{ $section['divider'] ?? 'head' }}" @class(['ol-sw', $loop->index % 2 ? 'ol-sw-b' : 'ol-sw-a']) data-outline-section @if ($section['divider']) data-outline-section-of="{{ $section['divider'] }}" @endif>
      @if ($section['divider'])
        @foreach ($section['items'] as $i => $id)
          @if ($i === $head)
            {{-- kepala seksi (Pemisah Seksi) --}}
            <x-editor.outline-node :id="$id" :content="$content" :index="$i" :count="count($order)" :prev="$order[$i - 1] ?? null" :tint="$section['tint']" :section="$meta" />
          @break
          @endif
        @endforeach
        {{-- isi seksi: anak kepala seksi di pohon (hanya tampilan; urutan, naik/turun, dan data-ctx tetap daftar tingkat atas) --}}
        @if (count($section['items']) > 1)
          <div class="ol-kids" data-outline-section-kids x-show="! folded[@js($section['divider'])]">
            @foreach ($section['items'] as $i => $id)
              @continue ($i === $head)
              <x-editor.outline-node :id="$id" :content="$content" :index="$i" :count="count($order)" :prev="$order[$i - 1] ?? null" />
            @endforeach
          </div>
        @endif
      @else
        {{-- "Awal halaman": baris maya (bukan blok), blok-bloknya di wadah pohon seperti isi seksi --}}
        <div class="ol-item" data-outline-head>
          <div class="ol-row ol-headrow flex rounded-md" style="--ol-bg: #ffffff; --ol-bd: #cfd3cc">
            <button type="button" class="ol-fold" data-outline-fold="head" x-on:click.stop="fold('head')" x-bind:aria-expanded="folded.head ? 'false' : 'true'" title="Lipat / buka" aria-label="Lipat atau buka Awal halaman">
              <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
            </button>
            <span class="flex min-w-0 flex-1 items-center gap-1.5 py-1.5 pr-2 pl-2 text-xs font-semibold" title="Blok di atas Pemisah Seksi pertama. Pemisah seksi tidak bisa melewati bagian ini.">
              <svg class="h-3.5 w-3.5 shrink-0"><use href="#icon-between-horizontal-start"></use></svg>
              <span class="min-w-0 flex-1 truncate" data-outline-head-label>Awal halaman</span>
            </span>
            <span class="ol-count" x-show="folded.head" x-cloak data-outline-count>{{ count($section['items']) }} blok</span>
            <button type="button" class="ol-wrap" data-outline-wrap x-on:click.stop="$wire.wrapPageStart()" title="Buat Pemisah Seksi di paling atas yang membungkus semua blok di Awal halaman">Bungkus dalam seksi</button>
          </div>
        </div>
        <div class="ol-kids" data-outline-section-kids x-show="! folded.head">
          @foreach ($section['items'] as $i => $id)
            <x-editor.outline-node :id="$id" :content="$content" :index="$i" :count="count($order)" :prev="$order[$i - 1] ?? null" :head="true" />
          @endforeach
        </div>
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
      <button type="button" role="menuitem" x-show="menu.ctx && menu.ctx.head" x-on:click="startSection()" class="{{ $item }}" data-act="start">
        <span class="flex-1">Mulai seksi dari blok ini</span>
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

  {{-- Dialog Salin / Hapus seksi (rilis 117): hanya untuk Pemisah Seksi yang punya blok di bawahnya. Satu dialog untuk seluruh outline. --}}
  <div x-show="dlg.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" data-outline-dialog x-on:click.self="closeDlg()">
    <form
      x-on:submit.prevent="dlgConfirm()"
      x-on:keydown.tab="trapTab($event)"
      role="dialog"
      aria-modal="true"
      aria-labelledby="ol-dlg-title"
      class="max-h-full w-full max-w-sm overflow-y-auto rounded-2xl bg-white p-4 text-xs text-gray-700 shadow-2xl"
    >
      <h2 id="ol-dlg-title" class="text-sm font-bold text-gray-900" x-text="dlg.kind === 'copy' ? 'Salin seksi' : 'Hapus seksi'"></h2>
      <p class="mt-1 leading-relaxed">
        <span class="font-semibold" x-text="dlg.title"></span> berisi <span x-text="dlg.n"></span> blok.
      </p>

      <fieldset class="mt-3 flex flex-col gap-2">
        <legend class="sr-only" x-text="dlg.kind === 'copy' ? 'Yang disalin' : 'Yang dihapus'"></legend>
        <label class="flex cursor-pointer gap-2 rounded-lg border p-2.5 has-[:checked]:border-foresty has-[:checked]:bg-sage-soft" x-bind:class="dlg.choice === 'self' ? 'border-foresty bg-sage-soft' : 'border-gray-200'">
          <input type="radio" name="ol-dlg-choice" value="self" x-model="dlg.choice" data-dlg-choice="self" class="mt-0.5 shrink-0 accent-[#064f3b]" />
          <span>
            <span class="block font-bold text-gray-900" x-text="dlg.kind === 'copy' ? 'Hanya pemisah ini' : 'Hanya pemisah'"></span>
            <span class="block leading-snug text-gray-500" x-text="dlg.kind === 'copy' ? 'Membuat pemisah seksi kosong baru tepat setelah seksi ini.' : (dlg.first ? 'Blok di bawahnya tetap ada dan menjadi bagian dari “Awal halaman”.' : 'Blok di bawahnya tetap ada dan bergabung ke seksi di atasnya.')"></span>
          </span>
        </label>
        <label class="flex cursor-pointer gap-2 rounded-lg border p-2.5 has-[:checked]:border-foresty has-[:checked]:bg-sage-soft" x-bind:class="dlg.choice === 'all' ? (dlg.kind === 'delete' ? 'border-red-600 bg-red-50' : 'border-foresty bg-sage-soft') : 'border-gray-200'">
          <input type="radio" name="ol-dlg-choice" value="all" x-model="dlg.choice" data-dlg-choice="all" class="mt-0.5 shrink-0 accent-[#064f3b]" />
          <span>
            <span class="block font-bold text-gray-900" x-text="dlg.kind === 'copy' ? 'Pemisah + ' + dlg.n + ' blok di bawahnya' : 'Pemisah + ' + dlg.n + ' blok'"></span>
            <span class="block leading-snug text-gray-500" x-text="dlg.kind === 'copy' ? 'Seluruh seksi disalin, termasuk isi kolom dan langkah, lalu diletakkan tepat setelah seksi ini.' : 'Semua isi seksi ikut terhapus, termasuk isi kolom dan langkah.'"></span>
          </span>
        </label>
      </fieldset>

      <div class="mt-3 rounded-lg bg-gray-50 p-2.5" data-dlg-list>
        <p class="mb-1 text-[10px] font-bold text-gray-400">Isi seksi ini</p>
        <ul class="flex flex-col gap-0.5">
          <template x-for="(b, n) in dlg.sample" x-bind:key="n"><li class="truncate" x-text="'• ' + b"></li></template>
        </ul>
        <p x-show="dlg.more > 0" class="mt-0.5 text-gray-500" x-text="'+' + dlg.more + ' lainnya'"></p>
      </div>

      <div class="mt-4 flex justify-end gap-2">
        <button type="button" x-on:click="closeDlg()" class="rounded-lg px-3 py-1.5 font-bold text-gray-600 hover:bg-gray-100" data-dlg-cancel>Batal</button>
        <button
          type="submit"
          data-dlg-ok
          class="rounded-lg px-3 py-1.5 font-bold text-white"
          x-bind:class="dlg.kind === 'delete' ? 'bg-red-600 hover:bg-red-700' : 'bg-foresty hover:bg-foresty/90'"
          x-text="dlg.kind === 'copy' ? (dlg.choice === 'all' ? 'Salin seksi dan ' + dlg.n + ' blok' : 'Salin pemisah') : (dlg.choice === 'all' ? 'Hapus seksi dan ' + dlg.n + ' blok' : 'Hapus pemisah')"
        ></button>
      </div>
    </form>
  </div>
</nav>
