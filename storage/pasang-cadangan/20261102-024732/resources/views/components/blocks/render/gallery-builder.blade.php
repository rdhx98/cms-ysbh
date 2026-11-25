{{--
  Render PUBLIK blok Galeri / Logo (blocks.render.gallery-builder): grid atau carousel, mode Foto atau Logo mitra.
  - Gambar HANYA dari model Media lewat media_id (FileInfo::lookup), dan hanya berkas berjenis gambar. Url dalam data tidak pernah dipakai.
  - Tautan melalui LinkResolver (aturan sama dengan tombol). Tautan yang gagal dipecah tidak membuang gambarnya.
  - Carousel: scroll-snap bawaan peramban (bisa digeser/digulir/papan ketik tanpa JavaScript); panah dan "berjalan otomatis" hanya dengan Alpine.
    Berjalan otomatis berhenti saat disorot/difokus, bisa dijeda, dan TIDAK dijalankan bila pengguna memilih "kurangi gerakan".
  - Pembesar foto: <dialog> bawaan (fokus terkunci, Esc menutup). Tanpa JavaScript, foto adalah tautan ke berkas gambarnya.
  Butir tanpa gambar sah dilewati di situs; di kanvas ($canvasMode) tampil pudar bertepi putus-putus. Prop `files` dan `resolver` hanya untuk pengujian.
  Penanda arah Blade ditulis hanya sebagai direktif nyata; JavaScript Alpine memakai x-on:, bukan singkatan dengan tanda at.
--}}
@props([
  'block' => [],
  'data' => [],
  'lang' => 'id',
  'allContent' => [],
  'files' => null,
  'resolver' => null,
])

@php
  use App\Content\Blocks\BlockSanitizer;
  use App\Content\Blocks\FileInfo;
  use App\Content\Blocks\GalleryList;
  use App\Content\Blocks\GalleryStyle;
  use App\Content\Links\LinkResolver;

  $inCanvas = (bool) ($canvasMode ?? false);
  $clean = BlockSanitizer::forType('gallery-builder', is_array($data) ? $data : [], array_values(array_unique([$lang, 'id', 'en'])));
  $files ??= FileInfo::lookup(array_map(fn ($i) => $i['image']['media_id'] ?? 0, $clean['items']));
  $resolver ??= LinkResolver::make();
  $items = GalleryList::prepare($clean, $files, $resolver, $lang, $inCanvas);

  $logos = $clean['mode'] === 'logos';
  $carousel = $clean['layout'] === 'carousel';
  $cols = $clean['columns'];
  $tile = GalleryStyle::tile($clean['mode'], $clean['ratio']);
  $imgClass = GalleryStyle::image($clean['mode'], $clean['grayscale']);
  $lightbox = ! $logos && $clean['lightbox'] && ! $inCanvas;
  $autoplay = $carousel && $clean['autoplay'] && ! $inCanvas;
  $needsJs = $carousel || $lightbox;
  $id = ['id' => ['prev' => 'Sebelumnya', 'next' => 'Berikutnya', 'pause' => 'Jeda', 'play' => 'Putar', 'close' => 'Tutup', 'zoom' => 'Perbesar', 'label' => $logos ? 'Logo mitra' : 'Galeri foto', 'empty' => 'Gambar belum dipilih'],
         'en' => ['prev' => 'Previous', 'next' => 'Next', 'pause' => 'Pause', 'play' => 'Play', 'close' => 'Close', 'zoom' => 'Enlarge', 'label' => $logos ? 'Partner logos' : 'Photo gallery', 'empty' => 'No image selected']];
  $t = $id[$lang === 'id' ? 'id' : 'en'];

  // Foto untuk pembesar: semua butir lengkap, urutan sama dengan tampilan.
  $photos = [];
  foreach ($items as $k => $it) {
      $items[$k]['pi'] = null;
      if (! $it['incomplete']) {
          $items[$k]['pi'] = count($photos);
          $photos[] = ['src' => $it['src'], 'alt' => $it['alt'], 'caption' => $it['caption']];
      }
  }

  // Keadaan Alpine disusun di sini (di dalam @php, bukan di dalam {{ }}), lalu dicetak sebagai satu atribut yang di-escape.
  $cfg = json_encode(['autoplay' => $autoplay, 'interval' => 4000, 'photos' => $lightbox ? $photos : []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
  $xdata = str_replace('__CFG__', $cfg, <<<'JS'
{
  cfg: __CFG__,
  atStart: true, atEnd: false, playing: false, paused: false, timer: null, reduce: false, current: null,
  init() {
    this.reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    this.$nextTick(() => this.sync());
    if (this.cfg.autoplay && !this.reduce) { this.playing = true; this.start(); }
  },
  destroy() { this.stop(); },
  step() { const t = this.$refs.track; if (!t || !t.firstElementChild) return 0; return t.firstElementChild.getBoundingClientRect().width + (parseFloat(getComputedStyle(t).columnGap) || 0); },
  sync() { const t = this.$refs.track; if (!t) return; this.atStart = t.scrollLeft <= 1; this.atEnd = t.scrollLeft + t.clientWidth >= t.scrollWidth - 1; },
  go(dir) { const t = this.$refs.track; if (t) t.scrollBy({ left: dir * this.step(), behavior: this.reduce ? 'auto' : 'smooth' }); },
  advance() { const t = this.$refs.track; if (!t) return; if (this.atEnd) t.scrollTo({ left: 0, behavior: this.reduce ? 'auto' : 'smooth' }); else this.go(1); },
  start() { this.stop(); this.timer = setInterval(() => { if (!this.paused) this.advance(); }, this.cfg.interval); },
  stop() { if (this.timer) { clearInterval(this.timer); this.timer = null; } },
  toggle() { this.playing = !this.playing; if (this.playing) this.start(); else this.stop(); },
  openAt(i) { this.current = i; this.$refs.lb.showModal(); },
  move(d) { const n = this.cfg.photos.length; if (n) this.current = (this.current + d + n) % n; },
}
JS);

  // Satu petak (dipakai grid dan carousel). Semua bagian dinamis di-escape dengan e().
  $renderTile = function (array $item) use ($lightbox, $tile, $imgClass, $logos, $t): string {
      $img = $item['incomplete']
          ? '<span class="px-2 text-center text-xs text-gray-400">' . e($t['empty']) . '</span>'
          : '<img src="' . e($item['src']) . '" alt="' . e($item['alt']) . '" loading="lazy" decoding="async" class="' . e($imgClass) . '">';
      $flag = $item['incomplete'] || $item['linkIssue'];
      $note = $item['incomplete'] ? $item['note'] . ' Tidak tampil di situs.' : 'Tautan belum sah atau tujuannya belum online/terbit; gambar tampil tanpa tautan.';
      $title = $flag ? ' title="' . e($note) . '"' : '';
      $cls = 'group ' . $tile . ($flag ? ' opacity-60 outline-1 outline-dashed outline-offset-2 outline-gray-400' : '');

      if ($item['href'] !== null) {
          $a = '<a href="' . e($item['href']) . '"' . ($item['newTab'] ? ' target="_blank" rel="noopener noreferrer"' : '') . $title . ' class="' . e($cls) . '">' . $img . '</a>';
      } elseif ($lightbox && ! $item['incomplete']) {
          $a = '<a href="' . e($item['src']) . '" target="_blank" rel="noopener" aria-haspopup="dialog" aria-label="' . e($t['zoom'] . ': ' . $item['alt']) . '" x-on:click.prevent="openAt(' . (int) $item['pi'] . ')"' . $title . ' class="' . e($cls) . ' cursor-zoom-in">' . $img . '</a>';
      } else {
          $a = '<div' . $title . ' class="' . e($cls) . '">' . $img . '</div>';
      }
      $cap = ! $logos && $item['caption'] !== '' ? '<figcaption class="mt-2 text-sm text-gray-600">' . e($item['caption']) . '</figcaption>' : '';

      return '<figure class="w-full">' . $a . $cap . '</figure>';
  };
@endphp

@if (count($items))
  <div
    data-gallery
    data-mode="{{ $clean['mode'] }}"
    data-layout="{{ $clean['layout'] }}"
    @if ($needsJs) x-data="{{ $xdata }}" @endif
    @if ($autoplay) x-on:mouseenter="paused = true" x-on:mouseleave="paused = false" x-on:focusin="paused = true" x-on:focusout="paused = false" @endif
    class="relative"
  >
    @if ($carousel)
      <div role="region" aria-roledescription="carousel" aria-label="{{ $t['label'] }}" class="relative">
        <div
          x-ref="track"
          x-on:scroll.passive="sync()"
          x-on:resize.window.debounce.150ms="sync()"
          tabindex="0"
          class="flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth pb-1 [scrollbar-width:none] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-foresty/60 [&::-webkit-scrollbar]:hidden"
        >
          @foreach ($items as $item)
            <div class="shrink-0 snap-start {{ GalleryStyle::basis($cols) }}">{!! $renderTile($item) !!}</div>
          @endforeach
        </div>

        <div style="display:none" x-show="! (atStart && atEnd)" class="mt-3 flex items-center justify-end gap-2">
          @if ($autoplay)
            <button type="button" x-on:click="toggle()" x-bind:aria-pressed="playing ? 'true' : 'false'" class="rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50">
              <span x-show="playing">{{ $t['pause'] }}</span><span x-show="! playing" style="display:none">{{ $t['play'] }}</span>
            </button>
          @endif
          <button type="button" x-on:click="go(-1)" x-bind:disabled="atStart" aria-label="{{ $t['prev'] }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
          </button>
          <button type="button" x-on:click="go(1)" x-bind:disabled="atEnd" aria-label="{{ $t['next'] }}" class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
          </button>
        </div>
      </div>
    @else
      <ul role="list" class="{{ GalleryStyle::grid($cols) }}">
        @foreach ($items as $item)
          <li>{!! $renderTile($item) !!}</li>
        @endforeach
      </ul>
    @endif

    @if ($lightbox)
      <dialog
        x-ref="lb"
        x-on:close="current = null"
        x-on:click="if ($event.target === $refs.lb) $refs.lb.close()"
        x-on:keydown.left.prevent="move(-1)"
        x-on:keydown.right.prevent="move(1)"
        aria-label="{{ $t['label'] }}"
        class="m-auto max-h-[94vh] w-[min(96vw,64rem)] overflow-hidden rounded-2xl bg-black p-0 text-white backdrop:bg-black/80"
      >
        <div class="relative flex flex-col items-center p-3 sm:p-5">
          <img
            x-bind:src="current !== null ? cfg.photos[current].src : null"
            x-bind:alt="current !== null ? cfg.photos[current].alt : ''"
            class="max-h-[76vh] w-auto max-w-full rounded-lg object-contain"
          />
          <p x-show="current !== null && cfg.photos[current].caption" x-text="current !== null ? cfg.photos[current].caption : ''" class="mt-3 text-center text-sm text-white/80" style="display:none"></p>
          <button type="button" x-on:click="$refs.lb.close()" aria-label="{{ $t['close'] }}" class="absolute top-2 right-2 flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/30">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
          </button>
          <button type="button" x-show="cfg.photos.length > 1" x-on:click="move(-1)" aria-label="{{ $t['prev'] }}" class="absolute top-1/2 left-2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/30" style="display:none">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
          </button>
          <button type="button" x-show="cfg.photos.length > 1" x-on:click="move(1)" aria-label="{{ $t['next'] }}" class="absolute top-1/2 right-2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/30" style="display:none">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
          </button>
        </div>
      </dialog>
    @endif
  </div>
@endif
