{{--
  Render PUBLIK blok Artikel Terbaru (blocks.render.latest-articles-builder): kartu artikel terbit terbaru. Isinya dibaca dari basis data
  (App\Content\PublicLookup::latestArticles), lalu disiapkan oleh ArticleCards (murni). Semua teks di-escape; sampul hanya dari nama berkas/ID
  media yang lolos pola ketat; tautan artikel dari templat konfigurasi cms.public.article.
  Gagal membaca basis data: blok tidak dirender (dilaporkan), halaman lain tetap tampil. Tanpa artikel: tidak dirender di situs; di kanvas
  tampil keterangan. Prop `feed` dipakai halaman daftar artikel (landing: ⚡articles-index) yang memberi data berhalaman dari PublicLookup::articlesPage; `articleUrl`, `coverFile`, `mediaUrl`, `resolver` hanya untuk pengujian. $currentArticleId (bila dibagikan
  oleh halaman artikel) dikeluarkan dari daftar.
--}}
@props([
  'block' => [],
  'data' => [],
  'lang' => 'id',
  'allContent' => [],
  'feed' => null,
  'articleUrl' => null,
  'coverFile' => null,
  'mediaUrl' => null,
  'resolver' => null,
])

@php
  use App\Content\Blocks\ArticleCards;
  use App\Content\Blocks\BlockSanitizer;
  use App\Content\Blocks\FileInfo;
  use App\Content\Links\LinkResolver;
  use App\Content\PublicLookup;

  $inCanvas = (bool) ($canvasMode ?? false);
  $clean = BlockSanitizer::forType('latest-articles-builder', is_array($data) ? $data : [], array_values(array_unique([$lang, 'id', 'en'])));

  if ($feed === null) {
      try {
          $exclude = (int) ($currentArticleId ?? 0) ?: null;
          $feed = PublicLookup::latestArticles((int) $clean['limit'], $exclude);
      } catch (\Throwable $e) {
          if (function_exists('report')) {
              report($e);
          }
          $feed = ['rows' => [], 'categories' => []];
      }
  }

  // Sampul berupa angka = ID media: ambil sekaligus dalam satu kueri.
  $mediaIds = [];
  foreach ($feed['rows'] as $r) {
      $f = is_scalar($r['featured_image'] ?? null) ? trim((string) $r['featured_image']) : '';
      if ($f !== '' && ctype_digit($f)) {
          $mediaIds[] = (int) $f;
      }
  }
  $mediaMap = ($mediaUrl === null && $mediaIds) ? FileInfo::lookup($mediaIds) : [];

  $articleUrl ??= fn (string $slug) => LinkResolver::address('article', $slug);
  $coverFile ??= fn (string $file) => LinkResolver::publicUrl($file, function_exists('config') ? config('cms.public.cover') : null, function_exists('config') ? (config('cms.public.base') ?? '') : '', '{file}');
  $mediaUrl ??= fn (int $id) => isset($mediaMap[$id]) && str_starts_with(strtolower($mediaMap[$id]['mime']), 'image/') ? $mediaMap[$id]['url'] : null;

  $cards = ArticleCards::prepare($feed['rows'], $feed['categories'], $lang, $articleUrl, $coverFile, $mediaUrl);

  $pick = function (array $byLocale) use ($lang): string {
      foreach ([$lang, 'id', 'en'] as $l) {
          if (($byLocale[$l] ?? '') !== '') {
              return $byLocale[$l];
          }
      }

      return '';
  };
  $title = $pick($clean['title']);
  $allLabel = $pick($clean['all_label']);
  $allUrl = null;
  if ($allLabel !== '') {
      $resolver ??= LinkResolver::make();
      $allUrl = $resolver->url($clean['all_link'], $lang);
  }
  $allNewTab = $allUrl !== null && $clean['all_link']['new_tab'] && ! preg_match('/^(tel:|mailto:|#)/', $allUrl);
  $readMore = $lang === 'id' ? 'Baca selengkapnya' : 'Read more';
@endphp

@if (count($cards))
  <section data-latest-articles @if ($title !== '') aria-label="{{ $title }}" @endif>
    @if ($title !== '')
      <h2 class="mb-6 text-2xl font-bold text-gray-900">{{ $title }}</h2>
    @endif

    <ul role="list" class="{{ ArticleCards::grid($clean['columns']) }}">
      @foreach ($cards as $card)
        <li>
          <article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-shadow focus-within:ring-2 focus-within:ring-foresty/40 hover:shadow-md">
            @if ($clean['show_image'])
              @if ($card['cover'] !== null)
                <img src="{{ $card['cover'] }}" alt="" loading="lazy" decoding="async" class="aspect-[16/10] w-full object-cover" />
              @else
                <div aria-hidden="true" class="from-foresty to-gray-900 aspect-[16/10] w-full bg-gradient-to-br"></div>
              @endif
            @endif

            <div class="flex flex-1 flex-col gap-2 p-5">
              @if ($clean['show_category'] && $card['category'] !== '')
                <p class="text-xs font-bold tracking-wide text-foresty uppercase">{{ $card['category'] }}</p>
              @endif

              <h3 class="line-clamp-2 text-lg leading-snug font-bold text-gray-900">
                @if ($card['url'] !== null)
                  <a href="{{ $card['url'] }}" class="outline-none after:absolute after:inset-0">{{ $card['title'] }}<span class="sr-only"> — {{ $readMore }}</span></a>
                @else
                  {{ $card['title'] }}
                @endif
              </h3>

              @if ($clean['show_excerpt'] && $card['excerpt'] !== '')
                <p class="line-clamp-3 text-sm leading-relaxed text-gray-600">{{ $card['excerpt'] }}</p>
              @endif

              @if ($clean['show_date'] && $card['dateIso'] !== '')
                <time datetime="{{ $card['dateIso'] }}" class="mt-auto pt-2 text-xs text-gray-500">{{ $card['dateLabel'] }}</time>
              @endif
            </div>
          </article>
        </li>
      @endforeach
    </ul>

    @if ($allLabel !== '' && ($allUrl !== null || $inCanvas))
      <div class="mt-6 text-right">
        <a
          @if ($allUrl !== null) href="{{ $allUrl }}" @endif
          @if ($allNewTab) target="_blank" rel="noopener noreferrer" @endif
          @if ($allUrl === null) title="Belum lengkap: tujuan tautan kosong atau belum online/terbit. Tidak tampil di situs." @endif
          class="inline-flex items-center gap-1.5 text-sm font-bold text-foresty underline underline-offset-4 hover:no-underline{{ $allUrl === null ? ' opacity-60 outline-1 outline-dashed outline-offset-2 outline-gray-400' : '' }}"
        >
          <span>{{ $allLabel }}</span>
          <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
        </a>
      </div>
    @endif
  </section>
@elseif ($inCanvas)
  <div class="rounded-xl border border-dashed border-gray-300 bg-white/60 p-4 text-center text-xs text-gray-500">
    Blok <b>Artikel Terbaru</b>: belum ada artikel berstatus terbit. Kartu akan muncul otomatis setelah ada.
  </div>
@endif
