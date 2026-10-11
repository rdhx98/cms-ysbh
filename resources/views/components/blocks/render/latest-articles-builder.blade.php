{{--
  Render PUBLIK blok Artikel Terbaru (blocks.render.latest-articles-builder). Isinya dibaca dari basis data (App\Content\PublicLookup::blockArticles),
  lalu disiapkan oleh ArticleCards (murni). Dua bagian (rilis 48):
    - UNGGULAN: 0-6 artikel dalam kisi bento (ArticleCards::bento): yang disematkan di blok, sisanya dilengkapi artikel terbaru sesuai sumber blok.
    - DAFTAR: kartu seragam (kolom = kartu lama; baris = gambar kiri), jumlah tetap atau berhalaman. Penomoran = TAUTAN BIASA (?hal…=N), bukan JavaScript,
      jadi bekerja tanpa JS dan terbaca mesin pencari. Nama parameter unik per blok (ArticleCards::pageParam).
  Semua teks di-escape; sampul hanya dari nama berkas/ID media yang lolos pola ketat; tautan artikel dari templat konfigurasi cms.public.article.
  Gaya bento ditulis di <style> kecil bawaan blok (kelas ".ula-…", warna merek dengan nilai cadangan heksa), supaya tidak bergantung pada kelas Tailwind
  yang belum dikompilasi.
  Gagal membaca basis data: blok tidak dirender (dilaporkan), halaman lain tetap tampil. Tanpa artikel: tidak dirender di situs; di kanvas tampil keterangan.
  Prop `feed` dipakai halaman daftar artikel (landing: ⚡articles-index) yang memberi data berhalaman sendiri dari PublicLookup::articlesPage: jalur LAMA,
  hanya daftar kartu (tanpa unggulan dan tanpa tombol halaman blok). `articleUrl`, `coverFile`, `mediaUrl`, `resolver`, `pageQuery` hanya untuk pengujian.
  $currentArticleId (bila dibagikan oleh halaman artikel) dikeluarkan dari semua bagian.
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
  'pageQuery' => null,
])

@php
  use App\Content\Blocks\ArticleCards;
  use App\Content\Blocks\BlockSanitizer;
  use App\Content\Blocks\FileInfo;
  use App\Content\Links\LinkResolver;
  use App\Content\PublicLookup;

  $inCanvas = (bool) ($canvasMode ?? false);
  $clean = BlockSanitizer::forType('latest-articles-builder', is_array($data) ? $data : [], array_values(array_unique([$lang, 'en', 'id'])));
  $legacy = $feed !== null;   // halaman daftar artikel: hanya kartu, tanpa unggulan dan tanpa penomoran blok

  $param = ArticleCards::pageParam($block['id'] ?? null);
  $query = [];
  if (is_array($pageQuery)) {
      $query = $pageQuery;
  } elseif (! $inCanvas) {
      try {
          $query = function_exists('request') ? (array) request()->query() : [];
      } catch (\Throwable) {
          $query = [];
      }
  }
  $paged = ! $legacy && $clean['paging'] === 'pages';
  $featuredWanted = $legacy ? 0 : (int) $clean['featured_count'];

  $empty = ['featured' => [], 'rows' => [], 'categories' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'perPage' => (int) $clean['limit'], 'noCategories' => false];
  if ($legacy) {
      $found = ['rows' => $feed['rows'], 'categories' => $feed['categories']] + $empty;
  } else {
      try {
          $found = PublicLookup::blockArticles([
              'locale' => $lang,   // hanya artikel yang diterjemahkan ke bahasa halaman
              'source' => $clean['source'],
              'categoryIds' => ArticleCards::pickIds($clean['categories']),
              'pinnedIds' => ArticleCards::pickIds($clean['pinned']),
              'featured' => $featuredWanted,
              'paging' => $clean['paging'],
              'perPage' => (int) $clean['limit'],
              'page' => ArticleCards::pageNumber($query[$param] ?? null, PHP_INT_MAX),
              'excludeId' => (int) ($currentArticleId ?? 0) ?: null,
          ]);
      } catch (\Throwable $e) {
          if (function_exists('report')) {
              report($e);
          }
          $found = $empty;
      }
  }

  // Sampul berupa angka = ID media: ambil sekaligus dalam satu kueri.
  $mediaIds = [];
  foreach (array_merge($found['featured'], $found['rows']) as $r) {
      $f = is_scalar($r['featured_image'] ?? null) ? trim((string) $r['featured_image']) : '';
      if ($f !== '' && ctype_digit($f)) {
          $mediaIds[] = (int) $f;
      }
  }
  $mediaMap = ($mediaUrl === null && $mediaIds) ? FileInfo::lookup($mediaIds) : [];

  $articleUrl ??= fn (string $slug) => LinkResolver::address('article', $slug, $lang);
  $coverFile ??= fn (string $file) => LinkResolver::publicUrl($file, function_exists('config') ? config('cms.public.cover') : null, function_exists('config') ? (config('cms.public.base') ?? '') : '', '{file}');
  $mediaUrl ??= fn (int $id) => isset($mediaMap[$id]) && str_starts_with(strtolower($mediaMap[$id]['mime']), 'image/') ? $mediaMap[$id]['url'] : null;

  $featuredCards = ArticleCards::prepare($found['featured'], $found['categories'], $lang, $articleUrl, $coverFile, $mediaUrl);
  $cards = ArticleCards::prepare($found['rows'], $found['categories'], $lang, $articleUrl, $coverFile, $mediaUrl);
  $bento = $featuredCards ? ArticleCards::bento(count($featuredCards), $clean['mirror']) : null;
  $bentoStyle = '';
  if ($bento) {
      $bentoStyle = "grid-template-areas:'" . implode("' '", $bento['areas']) . "';grid-template-columns:repeat({$bento['cols']},minmax(0,1fr));grid-template-rows:" . ($bento['rows'] ?? 'repeat(' . count($bento['areas']) . ',minmax(0,1fr))');
  }

  $pick = function (array $byLocale) use ($lang): string {
      foreach ([$lang, 'en', 'id'] as $l) {
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

  // tombol halaman: tautan biasa; nomor lain dipertahankan di alamat; halaman 1 = tanpa parameter
  $page = (int) $found['page'];
  $pages = (int) $found['pages'];
  $pagerItems = $paged ? ArticleCards::pagerItems($page, $pages) : [];
  $pageHref = function (int $n) use ($query, $param): string {
      $q = $query;
      unset($q[$param]);
      if ($n > 1) {
          $q[$param] = $n;
      }
      $qs = http_build_query($q);

      return '?' . $qs . '#ula-' . $param;
  };
  $prevLabel = $lang === 'id' ? '← Sebelumnya' : '← Previous';
  $nextLabel = $lang === 'id' ? 'Berikutnya →' : 'Next →';
  $pagerName = $lang === 'id' ? 'Halaman artikel' : 'Article pages';

  $hasAny = count($featuredCards) || count($cards);
  $tileCat = fn (array $card) => $clean['show_category'] && $card['category'] !== '' ? $card['category'] : '';
@endphp

@if ($hasAny)
  <section id="ula-{{ $param }}" data-latest-articles class="ula" @if ($title !== '') aria-label="{{ $title }}" @endif>
    <style>
      .ula{--ula-forest:#064F3B;--ula-gold:#EBCC26;--ula-paper:#FBF7EA;--ula-ink:#12241C;--ula-soft:#4B5D53;--ula-line:rgba(6,79,59,.18)}
      .ula a{color:inherit;text-decoration:none}
      .ula-bento{display:grid;gap:14px;position:relative}
      .ula-b1{aspect-ratio:16/8}.ula-b2{aspect-ratio:21/9}.ula-b3{aspect-ratio:15/8}.ula-b4,.ula-b5{aspect-ratio:7/4}.ula-b6{aspect-ratio:8/5}
      .ula-t{position:relative;overflow:hidden;min-width:0;min-height:0;background:var(--ula-forest);isolation:isolate;border-radius:.6rem 2.6rem .6rem 2.6rem}
      .ula-t:nth-child(odd){border-radius:2.6rem .6rem 2.6rem .6rem}
      .ula-t img,.ula-ph{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .7s cubic-bezier(.2,.7,.2,1)}
      .ula-t:hover img,.ula-t:hover .ula-ph{transform:scale(1.05)}
      .ula-ph{background:linear-gradient(145deg,var(--ula-forest),#2F8F6B)}
      .ula-over{position:absolute;inset:auto 0 0 0;z-index:1;color:#fff;padding:64px clamp(14px,2vw,26px) clamp(14px,2vw,24px);background:linear-gradient(to top,rgba(4,38,29,.9),rgba(4,38,29,.5) 58%,transparent)}
      .ula-cat{display:inline-flex;align-items:center;gap:7px;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--ula-forest)}
      .ula-cat::before{content:"";width:7px;height:7px;border-radius:50%;background:var(--ula-gold);flex:none}
      .ula-over .ula-cat{color:var(--ula-gold)}
      .ula-meta{display:block;font-size:12.5px;color:var(--ula-soft)}
      .ula-over .ula-meta{color:rgba(255,255,255,.82)}
      .ula-t h3,.ula-row h3{margin:7px 0 5px;font-family:'Fraunces',Georgia,serif;font-weight:700;line-height:1.15;text-wrap:balance}
      .ula-t h3 a::after,.ula-row h3 a::after{content:"";position:absolute;inset:0;z-index:2}
      .ula-t a:focus-visible,.ula-row a:focus-visible{outline:none}
      .ula-t:focus-within,.ula-row:focus-within{outline:3px solid var(--ula-gold);outline-offset:3px}
      .ula-ex{margin:8px 0 0;font-size:14.5px;max-width:52ch;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;color:rgba(255,255,255,.86)}
      .ula-xl h3{font-size:clamp(1.5rem,3.1vw,2.4rem)}.ula-l h3{font-size:clamp(1.25rem,2.3vw,1.8rem)}.ula-m h3{font-size:clamp(1.05rem,1.7vw,1.35rem)}.ula-s h3{font-size:clamp(.98rem,1.4vw,1.1rem)}
      .ula-s .ula-over{padding-top:48px}.ula-s .ula-meta,.ula-m .ula-meta{font-size:11.5px}
      .ula-txt{border-radius:2.2rem .5rem 2.2rem .5rem;display:flex;flex-direction:column;justify-content:center;gap:4px;padding:clamp(12px,1.8vw,24px) clamp(16px,2.4vw,32px)}
      .ula-txt:nth-child(n){border-radius:2.2rem .5rem 2.2rem .5rem}
      .ula-txt h3{font-size:clamp(1rem,1.7vw,1.35rem);margin:3px 0 2px}
      .ula-txt .ula-meta{color:inherit;opacity:.78}
      .ula-tg{background:var(--ula-gold);color:var(--ula-ink)}.ula-tg .ula-cat{color:var(--ula-ink)}.ula-tg .ula-cat::before{background:var(--ula-ink)}
      .ula-tf{background:var(--ula-forest);color:var(--ula-paper)}.ula-tf .ula-cat{color:var(--ula-gold)}
      .ula-tf:nth-child(n){border-radius:.5rem 2.2rem .5rem 2.2rem}
      .ula-after{margin-top:2.5rem}
      .ula-rows{list-style:none;margin:0;padding:0;display:flex;flex-direction:column}
      .ula-row{position:relative;display:grid;grid-template-columns:minmax(0,220px) minmax(0,1fr);gap:26px;align-items:center;padding:22px 0;border-top:1px solid var(--ula-line)}
      .ula-rows>li:first-child .ula-row{border-top:0;padding-top:0}
      .ula-pic{position:relative;overflow:hidden;aspect-ratio:4/3;border-radius:1.2rem;background:var(--ula-forest)}
      .ula-pic img,.ula-pic .ula-ph{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
      .ula-row h3{font-size:1.3rem;margin:6px 0 4px;color:var(--ula-ink)}
      .ula-row .ula-ex{color:var(--ula-soft);-webkit-line-clamp:3}
      .ula-pager{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px;margin-top:3rem}
      .ula-pg{display:inline-grid;place-items:center;min-width:42px;height:42px;padding:0 16px;border-radius:99px;font-weight:700;font-size:14px;color:var(--ula-forest)}
      a.ula-pg:hover{background:rgba(6,79,59,.07)}
      a.ula-pg:focus-visible{outline:3px solid var(--ula-gold);outline-offset:2px}
      .ula-pg[aria-current="page"]{background:var(--ula-forest);color:var(--ula-paper)}
      .ula-pg.ula-nx{border:2px solid var(--ula-forest)}
      @media (max-width:900px){.ula-b2{aspect-ratio:16/9}.ula-b3{aspect-ratio:4/3}.ula-b4,.ula-b5{aspect-ratio:1/1}.ula-b6{aspect-ratio:4/5}.ula-over .ula-ex{display:none}.ula-over{padding-top:44px}}
      @media (max-width:640px){
        .ula-bento{display:flex;flex-direction:column;aspect-ratio:auto!important;gap:12px}
        .ula-bento .ula-t{aspect-ratio:4/3}.ula-bento .ula-t:first-child{aspect-ratio:4/5}.ula-bento .ula-txt{aspect-ratio:auto!important;min-height:130px}
        .ula-xl h3{font-size:1.6rem}.ula-l h3{font-size:1.3rem}.ula-m h3,.ula-s h3{font-size:1.15rem}
        .ula-row{grid-template-columns:104px minmax(0,1fr);gap:16px}.ula-row .ula-ex{display:none}.ula-row h3{font-size:1.08rem}
      }
      @media (prefers-reduced-motion:reduce){.ula-t img,.ula-ph{transition:none}.ula-t:hover img,.ula-t:hover .ula-ph{transform:none}}
    </style>

    @if ($title !== '')
      <h2 class="mb-6 text-2xl font-bold text-gray-900">{{ $title }}</h2>
    @endif

    @if ($bento)
      <div class="ula-bento ula-b{{ $bento['n'] }}" data-ula-featured="{{ $bento['n'] }}" style="{{ $bentoStyle }}">
        @foreach ($bento['tiles'] as $i => $tile)
          @php($card = $featuredCards[$i])
          @if ($tile['text'] !== null)
            <article class="ula-t ula-txt {{ $tile['text'] === 'gold' ? 'ula-tg' : 'ula-tf' }}" style="grid-area:{{ $tile['area'] }}">
              @if ($tileCat($card) !== '')
                <span class="ula-cat">{{ $tileCat($card) }}</span>
              @endif
              <h3>
                @if ($card['url'] !== null)
                  <a href="{{ $card['url'] }}">{{ $card['title'] }}<span class="sr-only"> — {{ $readMore }}</span></a>
                @else
                  {{ $card['title'] }}
                @endif
              </h3>
              @if ($clean['show_date'] && $card['dateIso'] !== '')
                <time class="ula-meta" datetime="{{ $card['dateIso'] }}">{{ $card['dateLabel'] }}</time>
              @endif
            </article>
          @else
            <article class="ula-t ula-{{ $tile['size'] }}" style="grid-area:{{ $tile['area'] }}">
              @if ($clean['show_image'] && $card['cover'] !== null)
                <img src="{{ $card['cover'] }}" alt="" loading="lazy" decoding="async" />
              @else
                <div class="ula-ph" aria-hidden="true"></div>
              @endif
              <div class="ula-over">
                @if ($tileCat($card) !== '')
                  <span class="ula-cat">{{ $tileCat($card) }}</span>
                @endif
                <h3>
                  @if ($card['url'] !== null)
                    <a href="{{ $card['url'] }}">{{ $card['title'] }}<span class="sr-only"> — {{ $readMore }}</span></a>
                  @else
                    {{ $card['title'] }}
                  @endif
                </h3>
                @if ($clean['show_date'] && $card['dateIso'] !== '')
                  <time class="ula-meta" datetime="{{ $card['dateIso'] }}">{{ $card['dateLabel'] }}</time>
                @endif
                @if ($tile['size'] === 'xl' && $clean['show_excerpt'] && $card['excerpt'] !== '')
                  <p class="ula-ex">{{ $card['excerpt'] }}</p>
                @endif
              </div>
            </article>
          @endif
        @endforeach
      </div>
    @endif

    @if (count($cards))
      <div @class(['ula-after' => (bool) $bento])>
        @if ($clean['list_layout'] === 'rows')
          <ul role="list" class="ula-rows" data-ula-list="rows">
            @foreach ($cards as $card)
              <li>
                <article class="ula-row">
                  @if ($clean['show_image'])
                    <div class="ula-pic">
                      @if ($card['cover'] !== null)
                        <img src="{{ $card['cover'] }}" alt="" loading="lazy" decoding="async" />
                      @else
                        <div class="ula-ph" aria-hidden="true"></div>
                      @endif
                    </div>
                  @endif
                  <div @if (! $clean['show_image']) style="grid-column:1/-1" @endif>
                    @if ($clean['show_category'] && $card['category'] !== '')
                      <span class="ula-cat">{{ $card['category'] }}</span>
                    @endif
                    <h3>
                      @if ($card['url'] !== null)
                        <a href="{{ $card['url'] }}">{{ $card['title'] }}<span class="sr-only"> — {{ $readMore }}</span></a>
                      @else
                        {{ $card['title'] }}
                      @endif
                    </h3>
                    @if ($clean['show_date'] && $card['dateIso'] !== '')
                      <time class="ula-meta" datetime="{{ $card['dateIso'] }}">{{ $card['dateLabel'] }}</time>
                    @endif
                    @if ($clean['show_excerpt'] && $card['excerpt'] !== '')
                      <p class="ula-ex">{{ $card['excerpt'] }}</p>
                    @endif
                  </div>
                </article>
              </li>
            @endforeach
          </ul>
        @else
          <ul role="list" class="{{ ArticleCards::grid($clean['columns']) }}" data-ula-list="cols">
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
        @endif

        @if ($pagerItems)
          <nav class="ula-pager" aria-label="{{ $pagerName }}" data-ula-pager>
            @if ($page > 1)
              @if ($inCanvas)
                <span class="ula-pg ula-nx">{{ $prevLabel }}</span>
              @else
                <a class="ula-pg ula-nx" rel="prev" href="{{ $pageHref($page - 1) }}">{{ $prevLabel }}</a>
              @endif
            @endif
            @foreach ($pagerItems as $item)
              @if ($item === '…')
                <span class="ula-pg" aria-hidden="true">…</span>
              @elseif ($inCanvas)
                <span class="ula-pg" {!! $item === $page ? 'aria-current="page"' : '' !!}>{{ $item }}</span>
              @else
                <a class="ula-pg" href="{{ $pageHref($item) }}" {!! $item === $page ? 'aria-current="page"' : '' !!}>{{ $item }}</a>
              @endif
            @endforeach
            @if ($page < $pages)
              @if ($inCanvas)
                <span class="ula-pg ula-nx">{{ $nextLabel }}</span>
              @else
                <a class="ula-pg ula-nx" rel="next" href="{{ $pageHref($page + 1) }}">{{ $nextLabel }}</a>
              @endif
            @endif
          </nav>
        @endif
      </div>
    @endif

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
    @if ($found['noCategories'])
      Blok <b>Artikel Terbaru</b>: sumbernya "Kategori tertentu", tetapi belum ada kategori yang dipilih. Pilih minimal satu kategori di panel kanan.
    @elseif (! $legacy && $clean['source'] === 'categories')
      Blok <b>Artikel Terbaru</b>: belum ada artikel terbit (dan diterjemahkan ke bahasa ini) pada kategori yang dipilih. Kartu akan muncul otomatis setelah ada.
    @else
      Blok <b>Artikel Terbaru</b>: belum ada artikel berstatus terbit. Kartu akan muncul otomatis setelah ada.
    @endif
  </div>
@endif
