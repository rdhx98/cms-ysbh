{{--
  Render PUBLIK blok Tombol (blocks.render.button-builder). Props sama dengan komponen render lain: block, data, lang, allContent.

  Defense in depth: data dibersihkan LAGI di sini (BlockSanitizer) walau sudah dibersihkan saat disimpan, karena data bisa berasal dari
  impor/edit langsung di database. Tombol hanya dirender bila PUNYA teks DAN tautan yang sah (halaman/artikel harus online/terbit;
  javascript:, data:, //host tidak pernah menjadi href). Kelas CSS berasal dari daftar tetap (ButtonStyle), bukan dari data.
--}}
@props([
  'block' => [],
  'data' => [],
  'lang' => 'id',
  'allContent' => [],
  'resolver' => null, // untuk pengujian; bawaan: LinkResolver::make()
])

@php
  use App\Content\Blocks\BlockSanitizer;
  use App\Content\Blocks\ButtonStyle;

  $resolver ??= \App\Content\Links\LinkResolver::make();
  $clean = BlockSanitizer::buttonBuilder(is_array($data) ? $data : [], array_values(array_unique([$lang, 'id', 'en'])));
  $stack = $clean['stack_mobile'];
  $allowedIcons = config('cms.lucide', []);

  $buttons = [];
  foreach ($clean['buttons'] as $b) {
      // teks: bahasa halaman, lalu id, lalu en (bahasa yang kosong tidak membuat tombol hilang bila bahasa lain terisi)
      $text = '';
      foreach ([$lang, 'id', 'en'] as $candidate) {
          if (($b['label'][$candidate] ?? '') !== '') {
              $text = $b['label'][$candidate];
              break;
          }
      }
      $url = $resolver->url($b['link'], $lang);
      if ($text === '' || $url === null) {
          continue;
      }
      // tab baru hanya untuk tautan http(s)/jalur; tel:, mailto:, #anchor tidak
      $newTab = $b['link']['new_tab'] && !preg_match('/^(tel:|mailto:|#)/', $url);
      $icon = in_array($b['icon'], $allowedIcons, true) ? $b['icon'] : '';
      // array_replace, BUKAN '+': operator '+' mempertahankan kunci kiri, sehingga 'icon' hasil penyaringan tidak akan menimpa aslinya
      $buttons[] = array_replace($b, compact('text', 'url', 'newTab', 'icon'));
  }
@endphp

@if ($buttons)
  <div class="{{ ButtonStyle::row($clean['align'], $stack) }}">
    @foreach ($buttons as $b)
      <a
        href="{{ $b['url'] }}"
        @if ($b['newTab']) target="_blank" rel="noopener noreferrer" @endif
        class="{{ ButtonStyle::button($b['variant'], $b['color'], $b['size'], $stack) }}"
      >
        @if ($b['icon'] && $b['icon_position'] === 'left')
          <x-dynamic-component :component="'lucide-' . $b['icon']" class="h-4 w-4 shrink-0" aria-hidden="true" />
        @endif
        <span>{{ $b['text'] }}</span>
        @if ($b['icon'] && $b['icon_position'] === 'right')
          <x-dynamic-component :component="'lucide-' . $b['icon']" class="h-4 w-4 shrink-0" aria-hidden="true" />
        @endif
      </a>
    @endforeach
  </div>
@endif
