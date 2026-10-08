<?php

/**
 * Halaman publik dari CMS: /{slug}. Rute (routes/web.php, PALING AKHIR): Route::livewire('/{slug}', 'page-show')->name('page.show').
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

  public function mount(string $slug): void
  {
    $locales = config('app.supported_locales', ['id', 'en']);

    $found = PublicLookup::findPage($slug, app()->getLocale(), $locales);
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
