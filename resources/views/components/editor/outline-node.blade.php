{{--
  <x-editor.outline-node> — satu baris di pohon struktur, dirender REKURSIF untuk anak-anaknya.
  Struktur (blok apa ada di mana) datang dari server; yang reaktif di klien hanya label dan sorotan fokus,
  sehingga berpindah fokus dan mengetik judul tidak memerlukan request.

  Rilis 43: tombol "⋯" membuka menu bersama di <x-editor.outline> (satu menu untuk seluruh pohon). Baris menyimpan konteksnya di
  data-ctx (induk, zona, indeks, jenis daftar); menu dan pintasan Alt+panah membacanya dari situ.

  Rilis 95:
  - Label memenuhi lebar baris. Kelima kontrol (⋯, naik, turun, duplikat, hapus) menjadi lapisan `absolute` di kanan baris yang hanya muncul
    saat hover / fokus keyboard, berlatar PENUH sewarna baris (hijau tua bila terpilih, sage-soft selain itu) sehingga teks di bawahnya
    tertutup rapi. Di layar sentuh (hover:none) kontrol kembali ke aliran normal dan selalu tampak, karena tidak ada hover.
  - Jenis blok tidak lagi ditulis sebagai lencana di samping label (memotong lebar); ia ada di tooltip dan pada ikon.
  - Judul dan Paragraf boleh dua baris (line-clamp-2); jenis lain satu baris dipotong "…", teks penuh di tooltip.
  - Hapus SELALU meminta konfirmasi (dulu hanya bila blok punya anak/kartu/langkah).
  - Kontainer (Kolom, Grup Langkah) digambar sebagai panel berbingkai; tiap kolom di dalamnya sub-panel putus-putus.

  Rilis 96 (menggantikan panel berbingkai rilis 95; pewarnaannya diganti lagi di rilis 97):
  - Tiap node adalah KARTU sendiri berjarak 3px dari tetangganya. Rilis 97: kartu selalu putih; $tint (hex slot palet) hanya dipakai kepala seksi
    (garis tepi kiri + kotak warna), dan seksi dibedakan oleh wadah yang bergantian putih/abu-abu (outline.blade.php). Warna dikirim lewat
    variabel CSS (--ol-bg, --ol-bd, --ol-bar) supaya keadaan terpilih/sorot (kelas is-sel / is-par, diatur Alpine) bisa menimpanya. Gaya ada di <style> pada outline.blade.php (kelas ol-*), tidak bergantung pada pemindaian Tailwind.
  - Anak (di kolom, langkah, atau seksi) berada di dalam .ol-kids dan disambung ke induknya dengan garis pohon bersiku (├ └) lewat CSS.
    Indentasi datang dari .ol-kids, bukan dari padding baris, jadi kedalaman tak terbatas tanpa perhitungan.
--}}
@props(['id', 'content', 'depth' => 0, 'index' => 0, 'count' => 1, 'parent' => null, 'zone' => null, 'prev' => null, 'zoneHidden' => false, 'tint' => null])

@php
  $block = $content[$id] ?? null;
  $type = \App\Editor\BlockPalette::canonical($block['type'] ?? '');
  $zones = $block ? \App\Editor\BlockPalette::zonesFor($block) : [];
  $childTotal = 0;
  foreach ($zones as $z) {
      $childTotal += count($block['data'][$z['key']] ?? []);
  }

  // Rilis 77: konfirmasi hapus untuk Pembuat Kartu. Kartunya tersimpan di data.cards (bukan zona), jadi $childTotal tidak
  // menghitungnya. Sebagai langkah Grup Langkah, menghapusnya juga menghapus satu langkah (dan label penandanya).
  $cardTotal = $type === 'card-builder' ? count($block['data']['cards'] ?? []) : 0;
  $inSteps = $parent !== null && \App\Editor\BlockPalette::canonical($content[$parent]['type'] ?? '') === 'step-group';
  // Rilis 95: SELALU ada konfirmasi. Pesan khusus untuk blok yang membawa isi (anak, kartu, langkah); selain itu pesan polos berisi jenis blok,
  // dan teks labelnya ditambahkan di klien (confirmText) supaya jelas blok mana yang akan dihapus.
  $typeLabel = \App\Editor\BlockPalette::label($type);
  $plain = false;
  if ($childTotal > 0) {
      $confirmMsg = 'Hapus blok ini beserta ' . $childTotal . ' blok di dalamnya?';
  } elseif ($inSteps) {
      $confirmMsg = $cardTotal > 0
          ? 'Hapus langkah ini beserta ' . $cardTotal . ' kartu di dalamnya? Nomor dan label langkah sesudahnya ikut bergeser.'
          : 'Hapus langkah ini dari Grup Langkah? Nomor dan label langkah sesudahnya ikut bergeser.';
  } elseif ($cardTotal > 0) {
      $confirmMsg = 'Hapus blok ini beserta ' . $cardTotal . ' kartu di dalamnya?';
  } else {
      $confirmMsg = 'Hapus blok ' . $typeLabel . '?';
      $plain = true;
  }
  // Judul/Paragraf boleh dua baris; jenis lain satu baris (dipotong). Teks penuh selalu ada di tooltip.
  $labelClass = in_array($type, ['heading', 'paragraph', 'simple-text'], true) ? 'line-clamp-2 break-words' : 'truncate';

  // Konteks baris untuk menu "⋯" dan pintasan keyboard (rilis 43). Hanya petunjuk tampilan: server memvalidasi ulang SEMUA argumen.
  //   kind   = daftar jenis untuk "Sisipkan di atas/bawah": 'root' atau jenis induk (kontainer)
  //   canAdd = false di zona tersembunyi (server menolak menambah ke sana)
  //   in     = saudara tepat di atasnya kontainer yang menerima jenis ini dan punya zona tampil
  $ctx = null;
  if ($block) {
      $inLabel = null;
      $prevBlock = $prev ? ($content[$prev] ?? null) : null;
      if ($prevBlock && \App\Editor\BlockPalette::isContainer($prevBlock['type'] ?? '')
          && \App\Editor\BlockPalette::allowsChild($prevBlock['type'], $type)
          && collect(\App\Editor\BlockPalette::zonesFor($prevBlock))->contains(fn ($z) => ! $z['hidden'])) {
          $inLabel = \App\Editor\BlockPalette::label($prevBlock['type']);
      }
      $ctx = [
          'id' => $id, 'parent' => $parent, 'zone' => $zone, 'index' => $index, 'count' => $count,
          'kind' => $parent ? \App\Editor\BlockPalette::canonical($content[$parent]['type'] ?? '') : 'root',
          'canAdd' => ! $zoneHidden,
          'out' => $parent !== null,
          'in' => $inLabel !== null, 'inLabel' => $inLabel,
      ];
  }
@endphp

@if ($block)
  @php
    $isDivider = $type === 'section-divider';
    $hex = is_string($tint) && preg_match('/^#[0-9a-fA-F]{6}$/', $tint) === 1 ? $tint : null;
    // Rilis 97: kartu SELALU putih. Warna seksi hanya muncul di kepala seksi: garis tepi kiri 5px + kotak kecil di kanan label. Warna yang sangat
    // terang (paper, mist) dipakai untuk kotak apa adanya, tetapi garis tepinya digelapkan sedikit supaya tetap terlihat.
    $bar = null;
    if ($hex !== null) {
        [$r, $g, $b] = [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
        $light = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255 > 0.82;
        $bar = $light ? 'color-mix(in srgb, ' . $hex . ', #64748b 38%)' : $hex;
    }
    $rowStyle = $isDivider
        ? '--ol-bg: #ffffff; --ol-bd: #cfd3cc; --ol-bar: ' . ($bar ?? '#cfd3cc')
        : '--ol-bg: #ffffff; --ol-bd: #dcdfd9';
  @endphp
  <div wire:key="outline-{{ $id }}" class="ol-item">
    <div
      @class(['ol-row group relative flex rounded-md', 'ol-sec' => $isDivider])
      style="{{ $rowStyle }}"
      data-outline-row="{{ $id }}"
      data-ctx="{{ json_encode($ctx) }}"
      x-bind:class="{ 'is-sel': $store.editor.id === @js($id), 'is-par': $store.editor.parentId === @js($id) }"
    >
      <button
        type="button"
        x-on:click="$store.editor.focusBlock(@js($id), @js($type))"
        x-on:keydown="rowKey($event)"
        x-bind:title="tip(@js($id), @js($typeLabel))"
        data-outline-focus
        class="flex w-full min-w-0 items-start gap-1.5 py-1.5 pr-2 pl-2 text-left"
      >
        <svg class="mt-px h-3.5 w-3.5 shrink-0"><use href="#icon-{{ \App\Editor\BlockPalette::icon($type) }}"></use></svg>
        <span class="min-w-0 flex-1 text-xs leading-snug font-semibold {{ $labelClass }}" data-outline-label x-text="label(@js($id)) || @js($typeLabel)"></span>
        @if ($isDivider && $hex !== null)
          <span class="ol-chip" style="background-color: {{ $hex }}" data-outline-chip aria-hidden="true"></span>
        @endif
      </button>

      {{-- Kontrol: lapisan di kanan baris, latar penuh sewarna baris (bukan transparan), tampil saat hover / fokus keyboard.
           Tanpa hover (layar sentuh) ia kembali ke aliran normal dan selalu tampak. --}}
      <div
        data-outline-controls
        class="pointer-events-none absolute inset-y-0 right-0 flex items-center rounded-md pr-1 pl-1.5 opacity-0 transition-opacity group-focus-within:pointer-events-auto group-focus-within:opacity-100 group-hover:pointer-events-auto group-hover:opacity-100 [@media(hover:none)]:pointer-events-auto [@media(hover:none)]:static [@media(hover:none)]:shrink-0 [@media(hover:none)]:opacity-100"
        x-bind:class="$store.editor.id === @js($id) ? 'bg-foresty text-white' : 'bg-sage-soft'"
      >
        @if ($type === 'card-builder')
          {{-- rilis 67: tambah kartu langsung dari outline (menu templat), tanpa membuka panelnya dulu --}}
          <x-editor.add-card-menu variant="row" :bid="$id" label="Tambah kartu" />
        @endif
        <button type="button" title="Menu: sisipkan, pindahkan, masuk/keluar kontainer" aria-label="Menu blok" aria-haspopup="menu" x-on:click.stop="openMenu($event)" class="rounded p-1 hover:bg-black/10">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2.2" /><circle cx="12" cy="12" r="2.2" /><circle cx="19" cy="12" r="2.2" /></svg>
        </button>
        <button type="button" title="Naik" aria-label="Naik" @disabled($index === 0) x-on:click.stop="$wire.moveBlock(@js($id), -1)" class="rounded p-1 hover:bg-black/10 disabled:opacity-30">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6" /></svg>
        </button>
        <button type="button" title="Turun" aria-label="Turun" @disabled($index === $count - 1) x-on:click.stop="$wire.moveBlock(@js($id), 1)" class="rounded p-1 hover:bg-black/10 disabled:opacity-30">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
        </button>
        <button type="button" title="Duplikat" aria-label="Duplikat" x-on:click.stop="$wire.duplicateBlock(@js($id))" class="rounded p-1 hover:bg-black/10">
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" /></svg>
        </button>
        <button
          type="button"
          title="Hapus"
          aria-label="Hapus"
          data-outline-delete
          x-on:click.stop="
            if (! confirm(confirmText(@js($id), @js($confirmMsg), @js($plain)))) { return };
            removed();
            $wire.removeBlock(@js($id));
          "
          class="rounded p-1 hover:bg-red-500/20"
        >
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>
        </button>
      </div>
    </div>

    {{-- Kontainer (Kolom / Grup Langkah): anak di dalam .ol-kids, disambung ke induk dengan garis pohon. Kolom lebih dari satu: tiap kolom
         menjadi cabang berlabel ("Kolom 1", "Kolom 2") yang punya anak dan tombol tambah sendiri. --}}
    @if (count($zones))
      <div class="ol-kids" data-outline-panel="{{ $id }}">
        @foreach ($zones as $zone)
          @php $children = $block['data'][$zone['key']] ?? []; $multi = count($zones) > 1; @endphp
          @if ($multi)
            <div wire:key="outline-{{ $id }}-{{ $zone['key'] }}" class="ol-item ol-z" data-outline-zone="{{ $zone['key'] }}">
              <p class="ol-zone">
                {{ $zone['label'] }}@if ($zone['hidden']) <span class="text-amber-600">(tersembunyi: melebihi jumlah kolom)</span>@endif
              </p>
              <div class="ol-kids">
          @endif

          @foreach ($children as $i => $childId)
            <x-editor.outline-node :id="$childId" :content="$content" :depth="$depth + 1" :index="$i" :count="count($children)"
              :parent="$id" :zone="$zone['key']" :prev="$children[$i - 1] ?? null" :zone-hidden="$zone['hidden']" />
          @endforeach

          @unless ($zone['hidden'])
            <div class="ol-item" @unless ($multi) data-outline-zone="{{ $zone['key'] }}" @endunless>
              <x-editor.add-menu
                :parent="$id"
                :zone="$zone['key']"
                :types="\App\Editor\BlockPalette::childTypes($type)"
                :label="$multi ? 'Tambah ke ' . $zone['label'] : 'Tambah ' . strtolower($zone['label'])"
                :indent="0"
              />
            </div>
          @endunless

          @if ($multi)
              </div>
            </div>
          @endif
        @endforeach
      </div>
    @endif
  </div>
@endif
