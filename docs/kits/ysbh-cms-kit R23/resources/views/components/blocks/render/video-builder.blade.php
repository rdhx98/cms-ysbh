{{--
  Render PUBLIK blok Video (blocks.render.video-builder): sematan YouTube/Vimeo sebagai "fasad klik-untuk-memutar".
  - Sebelum diklik: TIDAK ada iframe dan TIDAK ada permintaan ke pihak ketiga (gambar sampul hanya dari File Manager sendiri).
  - Setelah diklik (Alpine): iframe dimuat dari domain mode privasi (youtube-nocookie.com / player.vimeo.com?dnt=1).
  - Tanpa JavaScript: fasad adalah tautan biasa ke halaman video di penyedia.
  - Alamat iframe dan tautan dibangun ulang dari ID yang lolos pola ketat (VideoUrl); alamat yang ditempel penulis tidak pernah dipasang.
  Alamat tak dikenali / kosong: tidak dirender di situs; di kanvas ($canvasMode) tampil kotak penjelasan. Prop `files` hanya untuk pengujian.
--}}
@props([
  'block' => [],
  'data' => [],
  'lang' => 'id',
  'allContent' => [],
  'files' => null,
])

@php
  use App\Content\Blocks\BlockSanitizer;
  use App\Content\Blocks\FileInfo;
  use App\Content\Blocks\VideoStyle;
  use App\Content\Blocks\VideoUrl;

  $inCanvas = (bool) ($canvasMode ?? false);
  $clean = BlockSanitizer::forType('video-builder', is_array($data) ? $data : [], array_values(array_unique([$lang, 'id', 'en'])));
  $video = VideoUrl::parse($clean['url']);

  $pick = function (array $byLocale) use ($lang): string {
      foreach ([$lang, 'id', 'en'] as $l) {
          if (($byLocale[$l] ?? '') !== '') {
              return $byLocale[$l];
          }
      }

      return '';
  };
  $title = $pick($clean['title']);
  $caption = $pick($clean['caption']);

  $posterUrl = null;
  $posterId = $clean['poster']['media_id'];
  if ($video && $posterId) {
      $files ??= FileInfo::lookup([$posterId]);
      $file = $files[$posterId] ?? null;
      $posterUrl = $file && str_starts_with(strtolower($file['mime'] ?? ''), 'image/') ? $file['url'] : null; // hanya gambar, dari model Media
  }

  $provider = $video ? VideoUrl::providerLabel($video['provider']) : '';
  $playLabel = ($lang === 'id' ? 'Putar video' : 'Play video') . ($title !== '' ? ': ' . $title : '');
@endphp

@if ($video)
  <figure class="{{ VideoStyle::widthClass($clean['max_width']) }}" data-video data-provider="{{ $video['provider'] }}">
    <div x-data="{ playing: false }" class="relative overflow-hidden rounded-2xl bg-gray-900 shadow-sm {{ VideoStyle::ratioClass($clean['ratio']) }}">
      <template x-if="playing">
        <iframe
          src="{{ VideoUrl::embed($video) }}"
          title="{{ $title !== '' ? $title : $provider }}"
          allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
          allowfullscreen
          referrerpolicy="strict-origin-when-cross-origin"
          class="absolute inset-0 h-full w-full border-0"
        ></iframe>
      </template>

      <a
        href="{{ VideoUrl::watch($video) }}"
        target="_blank"
        rel="noopener noreferrer"
        x-show="!playing"
        x-on:click.prevent="playing = true"
        aria-label="{{ $playLabel }}"
        class="group absolute inset-0 flex items-center justify-center outline-none focus-visible:ring-4 focus-visible:ring-white/70 focus-visible:ring-inset"
      >
        @if ($posterUrl !== null)
          <img src="{{ $posterUrl }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" />
          <span class="absolute inset-0 bg-black/20 transition-colors group-hover:bg-black/30"></span>
        @else
          <span class="from-foresty to-gray-900 absolute inset-0 bg-gradient-to-br"></span>
        @endif
        <span class="relative flex h-16 w-16 items-center justify-center rounded-full bg-white/95 text-gray-900 shadow-lg transition-transform group-hover:scale-110">
          <svg class="ml-1 h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z" /></svg>
        </span>
        <span class="absolute bottom-3 left-3 rounded-full bg-black/55 px-2.5 py-1 text-[11px] font-bold text-white">{{ $provider }}</span>
      </a>
    </div>

    @if ($caption !== '')
      <figcaption class="mt-3 text-sm text-gray-600">{{ $caption }}</figcaption>
    @endif
  </figure>
@elseif ($inCanvas)
  <div class="rounded-xl border border-dashed border-gray-300 bg-white/60 p-4 text-center text-xs text-gray-500">
    @if ($clean['url'] === '')
      Blok <b>Video</b>: isi alamat YouTube atau Vimeo di panel kanan.
    @else
      Alamat video belum dikenali. Hanya alamat <b>YouTube</b> dan <b>Vimeo</b> yang didukung (mis. https://www.youtube.com/watch?v=…).
    @endif
  </div>
@endif
