<?php

/**
 * CONTOH untuk aplikasi LANDING (bukan bagian yang disinkronkan oleh tools/sync-landing.php): halaman publik /{slug}.
 * Letakkan di resources/views/components/⚡page-show.blade.php dan daftarkan rute (contoh-kode/landing-routes.php):
 *   Route::livewire('/{slug}', 'page-show')->name('page.show')->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
 *
 * Semua logika pencarian dan perakitan ada di App\Content\PublicLookup (diuji); komponen ini hanya menghubungkannya ke Livewire.
 * Sesuaikan: nama layout, dan cara layout Anda mengambil judul/deskripsi (di sini dibagikan sebagai $pageTitle / $pageDescription).
 */

use App\Content\Names;
use App\Content\PublicLookup;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.landing.index')] class extends Component {
  #[Locked]
  public int $pageId = 0;

  #[Locked]
  public string $lang = 'id';

  public function mount(string $slug): void
  {
    $locales = config('app.supported_locales', ['id', 'en']);

    // Slug dicari di bahasa aktif dulu, lalu bahasa lain; bahasa tempat slug cocok menjadi bahasa halaman.
    $found = PublicLookup::findPage($slug, app()->getLocale(), $locales);
    abort_if($found === null, 404);   // tidak ada, offline, atau slug tidak sah

    $this->pageId = (int) $found['model']->id;
    $this->lang = $found['locale'];
    app()->setLocale($this->lang);

    $page = $found['model'];
    $title = Names::of($page->getRawOriginal('meta_title'), $this->lang) ?: Names::of($page->getRawOriginal('title'), $this->lang);
    view()->share('pageTitle', $title);
    view()->share('pageDescription', Names::of($page->getRawOriginal('meta_description'), $this->lang));
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
