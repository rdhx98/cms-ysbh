<?php

/**
 * Indeks artikel: /artikel?page=2. Rute: Route::livewire('/artikel', 'articles-index')->name('articles').
 *
 * Daftar dan penomorannya adalah kode ini (data dinamis, berhalaman). KEPALA-nya boleh diatur dari CMS: bila ada halaman CMS online ber-slug
 * config('cms.articles_index_slug') (bawaan "artikel"), judulnya menjadi judul halaman, deskripsi SEO-nya dipakai, blok-bloknya tampil sebagai
 * pengantar di halaman 1, dan snippet penutupnya (ajakan donasi, dst.) tampil SESUDAH daftar. Tanpa halaman itu: judul "Artikel" bawaan.
 * Kartu memakai renderer blok "Artikel Terbaru" (satu tampilan untuk blok dan indeks); datanya dari PublicLookup::articlesPage.
 */

use App\Content\PublicLookup;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
  private const PER_PAGE = 9;

  #[Locked]
  public string $lang = 'id';

  #[Locked]
  public int $page = 1;

  public function mount(): void
  {
    $this->page = max(1, (int) request()->query('page', 1));

    $header = $this->header;   // null bila halaman kepala tidak ada / offline
    $this->lang = $header['locale'] ?? app()->getLocale();
    app()->setLocale($this->lang);

    abort_if($this->page > 1 && $this->feed['rows'] === [], 404);   // halaman di luar jangkauan

    $title = $header['metaTitle'] ?? ($this->lang === 'id' ? 'Artikel' : 'Articles');
    if ($this->page > 1) {
      $title .= ($this->lang === 'id' ? ' (halaman ' : ' (page ') . $this->page . ')';
    }
    view()->share('title', $title);
    if (filled($header['description'] ?? null)) {
      view()->share('description', $header['description']);
    }
    // canonical PER HALAMAN: tanpa ini halaman 2 dan seterusnya menyatakan dirinya sama dengan halaman 1
    view()->share('canonical', $this->page > 1 ? url()->current() . '?page=' . $this->page : url()->current());
  }

  #[Computed]
  public function header(): ?array
  {
    return PublicLookup::articlesHeader((string) config('cms.articles_index_slug', 'artikel'), app()->getLocale(), config('app.supported_locales', ['id', 'en']));
  }

  /** @return array{rows:array,categories:array,total:int,page:int,perPage:int} */
  #[Computed]
  public function feed(): array
  {
    return PublicLookup::articlesPage($this->page, self::PER_PAGE);
  }

  #[Computed]
  public function paginator(): LengthAwarePaginator
  {
    return new LengthAwarePaginator($this->feed['rows'], $this->feed['total'], self::PER_PAGE, $this->page, ['path' => url()->current()]);
  }
};
?>

<div>
  <div class="mx-auto max-w-7xl px-4 pt-12">
    <h1 class="font-fraunces text-4xl font-bold text-forest">
      {{ filled($this->header['title'] ?? null) ? $this->header['title'] : ($lang === 'id' ? 'Artikel' : 'Articles') }}
    </h1>
  </div>

  {{-- Pengantar dari halaman CMS: hanya di halaman 1 (halaman 2 dan seterusnya langsung daftar) --}}
  @if ($page === 1 && ($this->header['intro']['order'] ?? []) !== [])
    <x-content.sections
      :blocks="$this->header['intro']['blocks']"
      :order="$this->header['intro']['order']"
      :settings="$this->header['intro']['settings']"
      :lang="$lang"
    />
  @endif

  <section class="mx-auto max-w-7xl px-4 py-12">
    <x-blocks.render.latest-articles-builder
      :data="['limit' => '12', 'columns' => '3']"
      :feed="$this->feed"
      :lang="$lang"
    />

    @if ($this->feed['rows'] === [])
      <p class="mt-8 text-ink-soft">{{ $lang === 'id' ? 'Belum ada artikel.' : 'No articles yet.' }}</p>
    @endif

    <div class="mt-10">
      {{ $this->paginator->links() }}
    </div>
  </section>

  {{-- Penutup (snippet penutup halaman CMS) SESUDAH daftar --}}
  @if (($this->header['closing']['order'] ?? []) !== [])
    <x-content.sections
      :blocks="$this->header['closing']['blocks']"
      :order="$this->header['closing']['order']"
      :settings="$this->header['closing']['settings']"
      :lang="$lang"
    />
  @endif
</div>
