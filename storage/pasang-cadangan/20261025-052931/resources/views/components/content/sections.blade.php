{{--
  <x-content.sections> — MESIN RENDER seksi + daftar isi untuk halaman, artikel, snippet, dan KANVAS. Terjemahan dari mesin render
  page-preview (markup publik sama), dengan pengelompokan seksi di App\Content\SectionBuilder (diuji terhadap logika aslinya).

  mode="public" : persis tampilan situs: animasi muncul bertahap per blok.
  mode="canvas" : untuk bingkai kanvas editor: tanpa animasi, tiap blok membawa data-block-id / data-block-type agar bisa dipilih,
                  dan seksi yang dibuka oleh pemisah seksi membawa ID pemisahnya.

  Blok yang tidak punya komponen tampilan (blocks.render.<tipe>) TIDAK menjatuhkan halaman: di kanvas tampil kotak penanda, di publik dilewati.
--}}
@props([
  'blocks' => [],
  'order' => [],
  'settings' => [],
  'lang' => 'id',
  'mode' => 'public',
])

@php
  $canvas = $mode === 'canvas';
  view()->share('canvasMode', $canvas); // komponen render yang butuh tahu (mis. FAQ: semua terbuka di kanvas) membacanya sebagai $canvasMode
  $blocks = \App\Content\Blocks\MediaUrls::resolve((array) $blocks); // alamat gambar dibentuk ulang dari media_id (rilis 45): tidak tergantung host saat konten disimpan
  view()->share('articlePrimaryId', \App\Content\ArticleListing::primaryId($blocks, (array) $order)); // blok Artikel Terbaru UTAMA di halaman: memakai ?page= dan ?category= (rilis 52)
  $grouped = \App\Content\SectionBuilder::group($blocks, $order, $settings, $lang);
  $sections = $grouped['sections'];
  $tocItems = $grouped['toc'];
  $tocPosition = $grouped['tocPosition'];
  $topBannerId = \App\Content\SectionBuilder::topBanner($sections, $blocks); // gambar w-screen sebagai blok pertama halaman, atau null
  $marginChoices = array_column(config('cms.design.margin_bottom', []), 'value'); // jarak bawah yang sah (rilis 32, lihat Spacing)
@endphp

<div class="bg-paper relative w-full">
  {{-- Daftar isi (rilis 62, mengganti kartu kaca rilis 29): melayang di samping kolom max-w-7xl, TANPA kartu: tidak ada latar, blur,
       bingkai, atau bayangan, jadi menyatu dengan latar halaman. Lebarnya mengikuti ruang yang ada, tidak pernah terpotong:
       lebar = min(256, ruang di sisi kolom + 32 px bantalan kolom - 12 px jarak - 16 px tepi layar); bila < 150 px daftar isi
       disembunyikan (menggantikan gerbang 2xl lama). Rilis 64: arah (kiri/kanan) dibaca dari atribut data-toc setiap kali dihitung dan diawasi
       MutationObserver; dulu tertanam di dalam skrip x-data sehingga mengganti arah di editor menyisakan data Alpine lama (TOC tidak muncul lagi). Posisi vertikal & aturan menyingkir dari rilis 29 tidak berubah: saat dimuat di
       tengah sumbu vertikal, ikut gulir sampai menempel di 128 px dari atas; tersembunyi selama banner puncak halaman belum lewat dan
       selama menimpa blok layar penuh lain (margin 50 px). --}}
  @if (count($tocItems) > 0 && $tocPosition !== 'hidden')
    <div
      x-data="{
        isTocHidden: true,
        tocTop: 128,
        tocLeft: 0,
        tocWidth: 0,
        side: 'right',
        checkOverlap() {
          const toc = $refs.tocCard
          if (! toc) return
          const vw = document.documentElement.clientWidth
          const gutter = (vw - Math.min(vw, 1280)) / 2
          const width = Math.min(256, Math.floor(gutter + 32 - 12 - 16))
          this.tocWidth = Math.max(width, 0)
          const left = this.$root.dataset.toc === 'left'
          this.side = left ? 'left' : 'right'
          this.tocLeft = left ? Math.round(gutter + 32 - 12 - this.tocWidth) : Math.round(vw - gutter - 32 + 12)
          const stick = 128
          const height = toc.offsetHeight
          const top = Math.max(stick, Math.round((window.innerHeight - height) / 2) - window.scrollY)
          this.tocTop = top
          if (width < 150) {
            this.isTocHidden = true
            return
          }
          const bottom = top + height
          const banner = document.querySelector('[data-top-banner]')
          if (banner && banner.getBoundingClientRect().bottom > top) {
            this.isTocHidden = true
            return
          }
          const blocks = document.querySelectorAll('[data-banner-block]:not([data-top-banner])')
          let overlap = false
          for (let i = 0; i < blocks.length; i++) {
            const rect = blocks[i].getBoundingClientRect()
            if (rect.top < bottom + 50 && rect.bottom > top - 50) {
              overlap = true
              break
            }
          }
          this.isTocHidden = overlap
        },
      }"
      x-init="$nextTick(() => checkOverlap()); setTimeout(() => checkOverlap(), 300); new MutationObserver(() => checkOverlap()).observe($el, { attributes: true, attributeFilter: ['data-toc'] })"
      @scroll.window.capture.passive="checkOverlap()"
      @resize.window.capture.passive="checkOverlap()"
      @load.window.capture="checkOverlap()"
      data-toc="{{ $tocPosition === 'left' ? 'left' : 'right' }}"
      class="pointer-events-none fixed inset-0 z-50"
    >
      <div class="pointer-events-auto absolute" x-bind:style="'top:' + tocTop + 'px;left:' + tocLeft + 'px;width:' + tocWidth + 'px'">
        <div
          x-ref="tocCard"
          class="scrollbar-hide max-h-[75vh] overflow-y-auto transition-all duration-500 ease-in-out"
          x-bind:class="isTocHidden ? 'opacity-0 pointer-events-none ' + (side === 'left' ? '-translate-x-8' : 'translate-x-8') : 'opacity-100 translate-x-0'"
        >
          <x-table-of-contents :items="$tocItems" />
        </div>
      </div>
    </div>
  @endif

  @forelse ($sections as $section)
    <section
      @if ($section['anchor'] !== '') id="{{ $section['anchor'] }}" @endif
      @if ($canvas && $section['dividerId']) data-block-id="{{ $section['dividerId'] }}" data-block-type="section-divider" @endif
      class="relative w-full {{ $section['bgClass'] }} {{ $section['textClass'] }} {{ $section['padding'] }}"
    >
      {{-- max-w-7xl ada per blok (bukan per seksi) supaya blok bertanda w-screen bisa memenuhi layar --}}
      <div class="flex w-full flex-col">
        @foreach ($section['blocks'] as $index => $blockId)
          @php
            $block = $blocks[$blockId];
            $type = str_replace('_', '-', (string) ($block['type'] ?? ''));
            $componentName = 'blocks.render.' . $type;
            $hasView = $type !== '' && view()->exists('components.' . $componentName);
            $delay = min($index * 150, 750);
            $isFullScreen = isset($block['data']['width']) && $block['data']['width'] === 'w-screen';
            // Jarak bawah: blok yang rendernya mengurus margin sendiri (judul, paragraf, eyebrow, gambar, kartu) menghasilkan ''; lainnya dari data.margin_bottom
            $spacing = \App\Content\Blocks\Spacing::wrapperClass($block, $marginChoices);
            // Rata teks Judul (rilis 33): data.align, dicetak pembungkus karena Judul tidak punya <p>; kiri = tidak mencetak apa pun
            $textAlign = \App\Content\Blocks\Align::wrapperClass($block);
            // Padding blok (rilis 70): data.pad_x / data.pad_y dari daftar putih BoxPadding; kosong = tidak ada pembungkus tambahan
            $boxPad = \App\Content\Blocks\BoxPadding::classes(is_array($block['data'] ?? null) ? $block['data'] : []);
          @endphp

          <div
            @if ($isFullScreen) data-banner-block="true" @endif
            @if ((string) $blockId === $topBannerId) data-top-banner="true" @endif
            @if ($canvas) data-block-id="{{ $blockId }}" data-block-type="{{ $type }}" @endif
            id="{{ ! empty($block['anchor']) ? $block['anchor'] : $blockId }}"
            @unless ($canvas)
              x-data="{ shown: false }"
              x-init="setTimeout(() => (shown = true), 100)"
              :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
              style="transition-delay: {{ $delay }}ms"
            @endunless
            class="{{ $canvas ? '' : 'transition-all duration-700 ease-out' }} {{ $isFullScreen ? 'w-full px-0' : 'mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8' }} {{ $spacing }} {{ $textAlign }}"
          >
            @if ($boxPad !== '')
              <div class="{{ $boxPad }}">
            @endif
            @if ($hasView && $canvas)
              {{-- Kanvas: tangkap keluaran; blok yang TIDAK mencetak apa pun (mis. FAQ tanpa pertanyaan) diberi penanda supaya tetap bisa dipilih --}}
              @php ob_start(); @endphp
              <x-dynamic-component
                :component="$componentName"
                :block="$block"
                :data="$block['data'] ?? []"
                :lang="$lang"
                :all-content="$blocks"
              />
              @php $rendered = ob_get_clean(); @endphp
              {!! $rendered !!}
              @if (trim(preg_replace('/<!--.*?-->/s', '', $rendered)) === '')
                <div class="my-2 rounded-lg border border-dashed border-gray-300 bg-white/60 p-4 text-center text-xs text-gray-500">
                  Blok <b>{{ \App\Editor\BlockPalette::label($type) }}</b> masih kosong. Isi di panel kanan.
                </div>
              @endif
            @elseif ($hasView)
              <x-dynamic-component
                :component="$componentName"
                :block="$block"
                :data="$block['data'] ?? []"
                :lang="$lang"
                :all-content="$blocks"
              />
            @elseif ($canvas)
              <div class="my-2 rounded-lg border border-dashed border-gray-300 bg-white/60 p-4 text-center text-xs text-gray-500">
                Blok <b>{{ $type ?: '(tanpa tipe)' }}</b> belum punya tampilan publik.
              </div>
            @endif
            @if ($boxPad !== '')
              </div>
            @endif
          </div>
        @endforeach
      </div>
    </section>
  @empty
    @if ($canvas)
      <div class="flex min-h-[40vh] items-center justify-center p-8 text-center text-sm text-gray-400">
        Belum ada blok yang bisa ditampilkan.<br />Tambahkan blok dari panel kiri.
      </div>
    @endif
  @endforelse
</div>
