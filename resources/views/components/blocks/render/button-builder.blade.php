{{--
  Render PUBLIK blok Tombol (blocks.render.button-builder). Props sama dengan komponen render lain: block, data, lang, allContent.

  Defense in depth: data dibersihkan LAGI di sini (BlockSanitizer) walau sudah dibersihkan saat disimpan, karena data bisa berasal dari
  impor/edit langsung di database. Tombol hanya dirender bila PUNYA teks DAN tautan yang sah (halaman/artikel harus online/terbit;
  javascript:, data:, //host tidak pernah menjadi href). Kelas CSS berasal dari daftar tetap (ButtonStyle), bukan dari data.

  Rilis 102: aksi "Salin teks" (action = copy): <button> yang menyalin `copy_text` ke papan klip lalu mengganti teksnya dengan "Tersalin ✓"
  (atau teks pilihan) selama 2 detik. Memakai Alpine inline (x-data di tombol), jadi tidak perlu mengubah JavaScript landing; tanpa JavaScript
  tombol hanya tidak berbuat apa-apa. Dipakai juga oleh elemen Tombol di Kartu Builder; `inLink` = kartu induk bertautan: tombol tautan
  menjadi <span> (tidak ada <a> di dalam <a>) dan klik tombol salin tidak ikut membuka tautan kartu.
--}}
@props([
  'block' => [],
  'data' => [],
  'lang' => 'id',
  'allContent' => [],
  'resolver' => null, // untuk pengujian; bawaan: LinkResolver::make()
  'inLink' => false, // rilis 102: dirender di dalam kartu yang bertautan
])

@php
  use App\Content\Blocks\BlockSanitizer;
  use App\Content\Blocks\ButtonStyle;

  $resolver ??= \App\Content\Links\LinkResolver::make();
  $clean = BlockSanitizer::buttonBuilder(is_array($data) ? $data : [], array_values(array_unique([$lang, 'id', 'en'])));
  $stack = $clean['stack_mobile'];
  $allowedIcons = config('cms.lucide', []);
  $inCanvas = (bool) ($canvasMode ?? false); // kanvas editor: tombol yang belum lengkap tetap tampak (bertanda), agar bisa dilihat saat disusun

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
      $isCopy = ($b['action'] ?? 'link') === 'copy';
      $copyText = $isCopy ? (string) ($b['copy_text'] ?? '') : '';
      $url = $isCopy ? null : $resolver->url($b['link'], $lang);
      $incomplete = $text === '' || ($isCopy ? $copyText === '' : $url === null);
      if ($incomplete && ! $inCanvas) {
          continue; // situs publik: tidak ada tombol mati
      }
      if ($text === '') {
          $text = '(tanpa teks)';
      }
      // tab baru hanya untuk tautan http(s)/jalur; tel:, mailto:, #anchor tidak
      $newTab = $url !== null && $b['link']['new_tab'] && !preg_match('/^(tel:|mailto:|#)/', $url);
      $icon = in_array($b['icon'], $allowedIcons, true) ? $b['icon'] : '';
      // teks setelah disalin: bahasa halaman, lalu id, lalu en; kosong semua = bawaan
      $copied = '';
      foreach ([$lang, 'id', 'en'] as $candidate) {
          if (($b['copied_label'][$candidate] ?? '') !== '') {
              $copied = $b['copied_label'][$candidate];
              break;
          }
      }
      if ($copied === '') {
          $copied = $lang === 'en' ? 'Copied ✓' : 'Tersalin ✓';
      }
      // Keadaan Alpine tombol salin. Js::from menulis string aman untuk JavaScript; seluruhnya di-escape lagi oleh {{ }} sebagai atribut.
      // Lebar tombol dikunci saat diklik pertama supaya tombol tidak menyusut ketika teksnya berganti.
      $copyData = $isCopy
          ? '{ done: false, timer: null, label: ' . \Illuminate\Support\Js::from($text) . ', copied: ' . \Illuminate\Support\Js::from($copied) . ', value: ' . \Illuminate\Support\Js::from($copyText)
              . ', copy(el) { const v = this.value; el.style.minWidth = el.offsetWidth + \'px\'; const fallback = () => { const t = document.createElement(\'textarea\'); t.value = v; t.setAttribute(\'readonly\', \'\'); t.style.cssText = \'position:fixed;top:0;left:0;opacity:0\'; document.body.appendChild(t); t.select(); try { document.execCommand(\'copy\') } catch (e) {} t.remove() }; const ok = () => { this.done = true; clearTimeout(this.timer); this.timer = setTimeout(() => { this.done = false }, 2000) }; if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(v).then(ok, () => { fallback(); ok() }) } else { fallback(); ok() } } }'
          : '';
      // array_replace, BUKAN '+': operator '+' mempertahankan kunci kiri, sehingga 'icon' hasil penyaringan tidak akan menimpa aslinya
      $buttons[] = array_replace($b, compact('text', 'url', 'newTab', 'icon', 'incomplete', 'isCopy', 'copyData'));
  }
@endphp

@if ($buttons)
  <div class="{{ ButtonStyle::row($clean['align'], $stack) }}">
    @foreach ($buttons as $b)
      @php
        $look = ButtonStyle::button($b['variant'], $b['color'], $b['size'], $stack, $b['radius'] ?? null) . ($b['incomplete'] ? ' opacity-60 outline-1 outline-dashed outline-offset-2 outline-gray-400' : '');
        $hint = $b['isCopy']
            ? 'Belum lengkap: teks tombol atau teks yang disalin kosong. Tidak tampil di situs.'
            : 'Belum lengkap: teks atau tujuan tautan kosong, atau tujuannya belum online/terbit. Tidak tampil di situs.';
      @endphp
      @if ($b['isCopy'])
        {{-- Salin teks (rilis 102): bukan tautan; teks tombol berganti menjadi "Tersalin ✓" sesaat. data-copy-button hanya penanda untuk pengujian. --}}
        <button
          type="button"
          data-copy-button
          x-data="{{ $b['copyData'] }}"
          @if ($inLink) x-on:click.prevent.stop="copy($el)" @else x-on:click="copy($el)" @endif
          @if ($b['incomplete']) title="{{ $hint }}" @endif
          class="{{ $look }}"
        >
          @if ($b['icon'] && $b['icon_position'] === 'left')
            <x-dynamic-component :component="'lucide-' . $b['icon']" class="h-4 w-4 shrink-0" aria-hidden="true" />
          @endif
          <span aria-live="polite" x-text="done ? copied : label">{{ $b['text'] }}</span>
          @if ($b['icon'] && $b['icon_position'] === 'right')
            <x-dynamic-component :component="'lucide-' . $b['icon']" class="h-4 w-4 shrink-0" aria-hidden="true" />
          @endif
        </button>
      @else
        <{{ $inLink ? 'span' : 'a' }}
          @if (! $inLink && $b['url'] !== null) href="{{ $b['url'] }}" @endif
          @if (! $inLink && $b['newTab']) target="_blank" rel="noopener noreferrer" @endif
          @if ($b['incomplete']) title="{{ $hint }}" @endif
          class="{{ $look }}"
        >
          @if ($b['icon'] && $b['icon_position'] === 'left')
            <x-dynamic-component :component="'lucide-' . $b['icon']" class="h-4 w-4 shrink-0" aria-hidden="true" />
          @endif
          <span>{{ $b['text'] }}</span>
          @if ($b['icon'] && $b['icon_position'] === 'right')
            <x-dynamic-component :component="'lucide-' . $b['icon']" class="h-4 w-4 shrink-0" aria-hidden="true" />
          @endif
        </{{ $inLink ? 'span' : 'a' }}>
      @endif
    @endforeach
  </div>
@endif
