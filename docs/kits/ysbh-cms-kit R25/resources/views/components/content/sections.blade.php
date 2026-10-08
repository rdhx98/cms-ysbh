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
  $grouped = \App\Content\SectionBuilder::group($blocks, $order, $settings, $lang);
  $sections = $grouped['sections'];
  $tocItems = $grouped['toc'];
  $tocPosition = $grouped['tocPosition'];
@endphp

<div class="bg-paper relative w-full">
  {{-- Daftar isi kaca di luar max-w-7xl; hanya muncul di layar >= 1536px dan menyingkir bila menabrak blok layar penuh --}}
  @if (count($tocItems) > 0 && $tocPosition !== 'hidden')
    <div
      x-data="{
        isTocHidden: false,
        checkOverlap() {
          const toc = $refs.tocCard
          if (! toc) return
          const tocRect = toc.getBoundingClientRect()
          const blocks = document.querySelectorAll('[data-banner-block]')
          let overlap = false
          for (let i = 0; i < blocks.length; i++) {
            const rect = blocks[i].getBoundingClientRect()
            if (rect.top < tocRect.bottom + 50 && rect.bottom > tocRect.top - 50) {
              overlap = true
              break
            }
          }
          this.isTocHidden = overlap
        },
      }"
      x-init="setTimeout(() => checkOverlap(), 300)"
      @scroll.window.capture.passive="checkOverlap()"
      @resize.window.capture.passive="checkOverlap()"
      class="pointer-events-none fixed inset-0 z-50 hidden 2xl:block"
    >
      <div class="relative mx-auto h-full w-full max-w-7xl">
        @if ($tocPosition === 'left')
          <div class="pointer-events-auto absolute top-32 right-full mr-8 w-64 transition-all duration-500">
            <div
              x-ref="tocCard"
              class="scrollbar-hide max-h-[75vh] overflow-y-auto rounded-2xl border border-white/60 bg-white/40 p-5 shadow-2xl backdrop-blur-xl transition-all duration-500 ease-in-out"
              x-bind:class="isTocHidden ? 'opacity-0 -translate-x-8 pointer-events-none' : 'opacity-100 translate-x-0'"
            >
              <x-table-of-contents :items="$tocItems" />
            </div>
          </div>
        @else
          <div class="pointer-events-auto absolute top-32 left-full ml-8 w-64 transition-all duration-500">
            <div
              x-ref="tocCard"
              class="scrollbar-hide max-h-[75vh] overflow-y-auto rounded-2xl border border-white/60 bg-white/40 p-5 shadow-2xl backdrop-blur-xl transition-all duration-500 ease-in-out"
              x-bind:class="isTocHidden ? 'opacity-0 translate-x-8 pointer-events-none' : 'opacity-100 translate-x-0'"
            >
              <x-table-of-contents :items="$tocItems" />
            </div>
          </div>
        @endif
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
          @endphp

          <div
            @if ($isFullScreen) data-banner-block="true" @endif
            @if ($canvas) data-block-id="{{ $blockId }}" data-block-type="{{ $type }}" @endif
            id="{{ ! empty($block['anchor']) ? $block['anchor'] : $blockId }}"
            @unless ($canvas)
              x-data="{ shown: false }"
              x-init="setTimeout(() => (shown = true), 100)"
              :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
              style="transition-delay: {{ $delay }}ms"
            @endunless
            class="{{ $canvas ? '' : 'transition-all duration-700 ease-out' }} {{ $isFullScreen ? 'w-full px-0' : 'mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8' }}"
          >
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
