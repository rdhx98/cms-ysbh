<?php

use App\Content\ContentType;
use App\Content\SavedPreview;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pratinjau versi TERSIMPAN dari sebuah record (halaman / artikel / snippet), dibaca dari database. Tampilannya seperti situs
 * (layout polos, tanpa sidebar admin), termasuk record yang masih offline/draf (hanya untuk pengguna yang sudah masuk).
 * Beda dengan kanvas: kanvas menampilkan isi yang BELUM disimpan (lewat token); ini menampilkan yang SUDAH tersimpan.
 *
 *   /v2/preview/page/12        /v2/preview/article/7?lang=en        /v2/preview/snippet/3
 */
new #[Layout('layouts.landing.index')] class extends Component {
  #[Locked]
  public string $type = 'page';

  #[Locked]
  public int $recordId = 0;

  #[Locked]
  public string $editUrl = '';

  public string $lang = 'id';

  /** @var string[] */
  public array $activeLocales = [];

  public function mount(string $type, int $id): void
  {
    abort_unless(auth()->check(), 403);

    $contentType = ContentType::tryFrom($type);
    abort_if($contentType === null, 404);

    $this->type = $contentType->value;
    $this->recordId = $id;
    $this->activeLocales = config('app.supported_locales', ['id', 'en']);

    $lang = (string) request()->query('lang', app()->getLocale());
    $this->lang = in_array($lang, $this->activeLocales, true) ? $lang : $this->activeLocales[0];

    abort_if($this->record === null, 404);

    // tautan "Edit": awalan rute ini ("v2." pada "v2.preview.record") dipertahankan
    $current = (string) request()->route()?->getName();
    $prefix = str_ends_with($current, 'preview.record') ? substr($current, 0, -strlen('preview.record')) : '';
    $editRoute = $prefix . $contentType->value . '.edit';
    if (Route::has($editRoute)) {
      $this->editUrl = route($editRoute, [$contentType->routeParam() => $id]);
    }
  }

  #[Computed]
  public function record()
  {
    $class = ContentType::from($this->type)->modelClass();

    return $class::query()->find($this->recordId);
  }

  #[Computed]
  public function preview(): array
  {
    return SavedPreview::from(ContentType::from($this->type), $this->record, $this->activeLocales, $this->lang);
  }
};
?>

@php
  $p = $this->preview;
  $kind = ['page' => 'Halaman', 'article' => 'Artikel', 'snippet' => 'Snippet'][$p['type']] ?? $p['type'];
@endphp

<div class="relative w-full" data-record-preview>
  {{-- Pita kecil di pojok: bukan bagian halaman, hanya penanda bahwa ini pratinjau tersimpan --}}
  <div class="pointer-events-none fixed top-3 right-3 z-[60]">
    <div class="pointer-events-auto flex max-w-[92vw] items-center gap-2 rounded-full bg-gray-900/90 px-3 py-1.5 text-[11px] font-semibold text-white shadow-lg backdrop-blur">
      <span class="text-white/60 uppercase">Tersimpan</span>
      <span class="truncate">{{ $kind }}: {{ $p['title'] }}</span>
      <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $p['published'] ? 'bg-emerald-500/30 text-emerald-200' : 'bg-amber-500/30 text-amber-200' }}">{{ $p['status'] !== '' ? $p['status'] : '—' }}</span>
      @foreach ($activeLocales as $code)
        <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}" class="rounded px-1.5 py-0.5 {{ $lang === $code ? 'bg-white text-gray-900' : 'text-white/70 hover:text-white' }}">{{ strtoupper($code) }}</a>
      @endforeach
      @if ($editUrl !== '')
        <a href="{{ $editUrl }}" class="rounded px-1.5 py-0.5 text-white/70 hover:text-white">Edit</a>
      @endif
    </div>
  </div>

  <x-content.sections :blocks="$p['blocks']" :order="$p['order']" :settings="$p['settings']" :lang="$lang" />
</div>
