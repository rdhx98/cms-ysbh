{{--
  Render PUBLIK blok Paragraf Biasa (blocks.render.simple-text). Isi = teks biasa yang di-escape lalu diberi struktur (FaqText: paragraf,
  daftar "- ", tautan https): tidak ada HTML dari penulis yang lolos. Ukuran, rata, dan warna dari daftar tetap (SimpleTextStyle) dan
  berlaku untuk SEMUA bahasa. Bila teks bahasa halaman kosong, dipakai bahasa lain (id, lalu en) supaya tidak ada ruang kosong.
  Blok tanpa teks tidak dirender di situs; di kanvas ($canvasMode) bagian kosong ditandai oleh <x-content.sections>.
--}}
@props([
  'block' => [],
  'data' => [],
  'lang' => 'id',
  'allContent' => [],
])

@php
  use App\Content\Blocks\BlockSanitizer;
  use App\Content\Blocks\FaqText;
  use App\Content\Blocks\SimpleTextStyle;

  $clean = BlockSanitizer::forType('simple-text', is_array($data) ? $data : [], array_values(array_unique([$lang, 'id', 'en'])));

  $text = '';
  foreach ([$lang, 'id', 'en'] as $l) {
      if (($clean['text'][$l] ?? '') !== '') {
          $text = $clean['text'][$l];
          break;
      }
  }
  $html = FaqText::toHtml($text);
  $classes = SimpleTextStyle::classes($clean['size'], $clean['align'], $clean['color']);
@endphp

@if ($html !== '')
  <div data-simple-text class="{{ $classes }} [&_a]:font-semibold [&_a]:underline [&_li]:mb-1 [&_p]:mb-4 [&_p:last-child]:mb-0 [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-5 [&_ul:last-child]:mb-0">{!! $html !!}</div>
@endif
