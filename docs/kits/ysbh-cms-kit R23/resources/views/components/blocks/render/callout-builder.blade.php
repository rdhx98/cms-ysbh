{{--
  Render PUBLIK blok Callout (blocks.render.callout-builder). Isi = teks biasa yang di-escape lalu diberi struktur (FaqText: paragraf, daftar,
  tautan https): tidak ada HTML dari penulis yang lolos. Kelas CSS dan ikon bawaan dari daftar tetap (CalloutStyle). Tautan aksi memakai
  LinkResolver (hanya http(s), mailto, tel, /jalur, #anchor; halaman/artikel hanya bila online/terbit).
  Blok tanpa judul DAN tanpa isi tidak dirender. Aksi tanpa teks atau tanpa tujuan sah tidak tampil di situs; di kanvas ($canvasMode) tampil
  pudar bertepi putus-putus. Prop `resolver` hanya untuk pengujian.
--}}
@props([
  'block' => [],
  'data' => [],
  'lang' => 'id',
  'allContent' => [],
  'resolver' => null,
])

@php
  use App\Content\Blocks\BlockSanitizer;
  use App\Content\Blocks\CalloutStyle;
  use App\Content\Blocks\FaqText;

  $inCanvas = (bool) ($canvasMode ?? false);
  $clean = BlockSanitizer::forType('callout-builder', is_array($data) ? $data : [], array_values(array_unique([$lang, 'id', 'en'])));

  $pick = function (array $byLocale) use ($lang): string {
      foreach ([$lang, 'id', 'en'] as $l) {
          if (($byLocale[$l] ?? '') !== '') {
              return $byLocale[$l];
          }
      }

      return '';
  };

  $title = $pick($clean['title']);
  $bodyHtml = FaqText::toHtml($pick($clean['body']));

  // aksi
  $actionLabel = $pick($clean['action']['label']);
  $actionUrl = null;
  if ($actionLabel !== '') {
      $resolver ??= \App\Content\Links\LinkResolver::make();
      $actionUrl = $resolver->url($clean['action']['link'], $lang);
  }
  $showAction = $actionLabel !== '' && ($actionUrl !== null || $inCanvas);
  $newTab = $actionUrl !== null && $clean['action']['link']['new_tab'] && ! preg_match('/^(tel:|mailto:|#)/', $actionUrl);

  $tone = $clean['tone'];
  $style = $clean['style'];
  $customIcon = in_array($clean['icon'], config('cms.lucide', []), true) ? $clean['icon'] : '';
@endphp

@if ($title !== '' || $bodyHtml !== '')
  <aside
    role="note"
    aria-label="{{ CalloutStyle::label($tone, $lang) }}"
    data-callout
    data-tone="{{ $tone }}"
    class="{{ CalloutStyle::box($style, $tone) }}"
  >
    <div class="flex gap-4">
      @if ($clean['show_icon'])
        @if ($customIcon !== '')
          <x-dynamic-component :component="'lucide-' . $customIcon" class="{{ CalloutStyle::icon($style, $tone) }}" aria-hidden="true" />
        @else
          <svg class="{{ CalloutStyle::icon($style, $tone) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! CalloutStyle::iconPath($tone) !!}</svg>
        @endif
      @endif

      <div class="min-w-0 flex-1">
        <span class="sr-only">{{ CalloutStyle::label($tone, $lang) }}: </span>
        @if ($title !== '')
          <p class="text-base leading-snug font-bold">{{ $title }}</p>
        @endif
        @if ($bodyHtml !== '')
          <div class="{{ $title !== '' ? 'mt-1.5 ' : '' }}text-[15px] leading-relaxed [&_a]:font-semibold [&_a]:underline [&_li]:mb-1 [&_p]:mb-3 [&_p:last-child]:mb-0 [&_ul]:list-disc [&_ul]:pl-5">{!! $bodyHtml !!}</div>
        @endif
        @if ($showAction)
          <a
            @if ($actionUrl !== null) href="{{ $actionUrl }}" @endif
            @if ($newTab) target="_blank" rel="noopener noreferrer" @endif
            @if ($actionUrl === null) title="Belum lengkap: tujuan tautan kosong atau belum online/terbit. Tidak tampil di situs." @endif
            class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold underline underline-offset-4 hover:no-underline{{ $actionUrl === null ? ' opacity-60 outline-1 outline-dashed outline-offset-2 outline-gray-400' : '' }}"
          >
            <span>{{ $actionLabel }}</span>
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
          </a>
        @endif
      </div>
    </div>
  </aside>
@endif
