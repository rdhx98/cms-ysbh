{{--
  Render PUBLIK blok Daftar Unduhan (blocks.render.downloads-builder). Tautan unduhan HANYA dibentuk dari media_id lewat model Media
  (FileInfo::lookup); url dalam data tidak pernah dipakai. Data dibersihkan lagi di sini (DownloadsBlock::sanitize).
  Butir tanpa judul atau tanpa berkas (belum dipilih / sudah dihapus) tidak tampil di situs; di kanvas ($canvasMode) tampil sebagai butir
  belum lengkap (pudar, tepi putus-putus). Ukuran dan jenis berkas otomatis dari Media. Prop `files` hanya untuk pengujian.
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
  use App\Content\Blocks\DownloadList;
  use App\Content\Blocks\DownloadsStyle;
  use App\Content\Blocks\FileInfo;

  $canvas = (bool) ($canvasMode ?? false);
  $clean = BlockSanitizer::forType('downloads-builder', is_array($data) ? $data : [], array_values(array_unique([$lang, 'id', 'en'])));
  $files ??= FileInfo::lookup(array_map(fn ($i) => $i['file']['media_id'] ?? 0, $clean['items']));
  $list = DownloadList::prepare($clean, $files, $lang, $canvas);
  $layout = $clean['layout'];
  $color = $clean['color'];
  $download = $lang === 'id' ? 'Unduh' : 'Download';
@endphp

@if ($list['count'])
  <div data-downloads>
    @foreach ($list['groups'] as $group)
      @if ($group['heading'] !== null)
        <h3 class="mt-8 mb-3 text-lg font-bold text-gray-900 first:mt-0">{{ $group['heading'] }}</h3>
      @endif
      <ul class="{{ DownloadsStyle::wrapper($layout) }}">
        @foreach ($group['items'] as $item)
          @php
            $meta = implode(' · ', array_filter([$item['year'], $item['type'], $item['size']], fn ($v) => $v !== null && $v !== ''));
          @endphp
          <li class="{{ $layout === 'cards' ? 'h-full' : '' }}">
            <a
              @if ($item['url'] !== null) href="{{ $item['url'] }}" @if ($clean['new_tab']) target="_blank" rel="noopener noreferrer" @endif @endif
              @if ($item['incomplete']) title="{{ $item['note'] }} Tidak tampil di situs." @endif
              class="{{ DownloadsStyle::row($layout, $color) }}{{ $item['incomplete'] ? ' opacity-60 outline-1 outline-dashed outline-offset-2 outline-gray-400' : '' }}"
            >
              <span class="{{ DownloadsStyle::badge($color) }}">{{ $item['type'] !== '' ? $item['type'] : '—' }}</span>
              <span class="min-w-0 flex-1">
                <span class="block font-semibold text-gray-900 {{ $layout === 'cards' ? '' : 'truncate' }}">{{ $item['title'] }}</span>
                @if ($clean['show_meta'] && $meta !== '')
                  <span class="mt-0.5 block text-xs text-gray-500">{{ $meta }}</span>
                @endif
              </span>
              <svg class="{{ DownloadsStyle::icon($color) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 15V3M7 10l5 5 5-5M5 21h14" /></svg>
              <span class="sr-only">{{ $download }}</span>
            </a>
          </li>
        @endforeach
      </ul>
    @endforeach
  </div>
@endif
