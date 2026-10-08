<?php

/**
 * Halaman publik dari CMS. Dua rute (routes/web.php): Route::livewire('/', 'page-show')->name('home') (tanpa $slug = BERANDA) dan
 * Route::livewire('/{slug}', 'page-show')->name('page.show') (PALING AKHIR).
 * Beranda = halaman ber-slug config('cms.home_slug'). Alamat "/{home_slug}" dialihkan 301 ke "/" (satu halaman, satu alamat).
 * Beranda belum dibuat atau offline = 503 "situs sedang disiapkan" (bukan 404: situs tidak dianggap hilang oleh mesin pencari).
 * Semua logika ada di App\Content\PublicLookup (diuji): slug JSON dicari di bahasa aktif lalu bahasa lain, snippet sisipan dan penutup dirakit,
 * data dibersihkan. Judul/deskripsi dibagikan sebagai $title dan $description, yang dibaca partials/head.blade.php.
 */

use App\Content\Names;
use App\Content\PublicLookup;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
  #[Locked]
  public int $pageId = 0;

  #[Locked]
  public string $lang = 'id';

  public function mount(?string $slug = null): void
  {
    $locales = config('app.supported_locales', ['id', 'en']);
    $homeSlug = (string) config('cms.home_slug', 'home');

    if ($slug !== null && $homeSlug !== '' && $slug === $homeSlug) {
      throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect('/', 301));   // /home -> /
    }

    $isHome = $slug === null;   // rute '/' tidak punya parameter
    $found = PublicLookup::findPage($isHome ? $homeSlug : $slug, app()->getLocale(), $locales);
    if ($found === null && $isHome) {
      abort(503, 'Situs sedang disiapkan.', ['Retry-After' => '3600']);   // beranda belum ada atau offline
    }
    abort_if($found === null, 404);   // tidak ada, offline, atau slug tidak sah

    $this->pageId = (int) $found['model']->id;
    $this->lang = $found['locale'];   // bahasa tempat slug itu cocok menjadi bahasa halaman
    app()->setLocale($this->lang);

    $page = $found['model'];
    view()->share('title', Names::of($page->getRawOriginal('meta_title'), $this->lang) ?: Names::of($page->getRawOriginal('title'), $this->lang));
    view()->share('description', Names::of($page->getRawOriginal('meta_description'), $this->lang));
  }

  /** Isi sendiri + snippet sisipan + snippet penutup, sudah dibersihkan. */
  #[Computed]
  public function document(): array
  {
    $page = \App\Models\Page::query()->findOrFail($this->pageId);

    return PublicLookup::document($page->getRawOriginal('content'), config('app.supported_locales', ['id', 'en']));
  }
};
?>

<div>
  <x-content.sections
    :blocks="$this->document['blocks']"
    :order="$this->document['order']"
    :settings="$this->document['settings']"
    :lang="$lang"
  />
</div>
