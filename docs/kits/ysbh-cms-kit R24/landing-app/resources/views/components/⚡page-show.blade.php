<?php

/**
 * Halaman publik dari CMS (rilis 24: dua bahasa). Rute (routes/web.php): '/' dan '/id' (tanpa $slug = BERANDA bahasa itu),
 * '/{slug}' (EN) dan '/id/{slug}' (ID), yang terakhir didaftarkan PALING AKHIR.
 * Bahasa = awalan alamat (App\Content\Languages::forRequest), BUKAN bahasa tempat slug kebetulan cocok. Slug dicari KETAT di bahasa itu:
 * slug bahasa lain yang halamannya punya padanan di bahasa ini dialihkan 301 ke padanannya (/about-us di /id -> /id/tentang-kami), selain itu 404.
 * Beranda = halaman ber-slug config('cms.home_slug') bahasa itu. Alamat "/home" (EN) atau "/id/beranda" (ID) dialihkan 301 ke jalur beranda
 * (satu halaman, satu alamat). Beranda belum dibuat atau offline = 503 "situs sedang disiapkan" (bukan 404).
 * Semua logika ada di App\Content\PublicLookup (diuji). Judul/deskripsi dibagikan sebagai $title dan $description (partials/head.blade.php);
 * $alternates (bahasa => alamat) dipakai sakelar bahasa di header dan tag hreflang.
 */

use App\Content\Languages;
use App\Content\Links\LinkResolver;
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
  public string $lang = 'en';

  public function mount(?string $slug = null): void
  {
    $this->lang = Languages::forRequest();
    $locales = Languages::fromConfig()['locales'];
    $homeSlug = Languages::homeSlug(config('cms.home_slug'), $this->lang);

    if ($slug !== null && $slug === $homeSlug) {
      throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect(LinkResolver::homeAddress($this->lang) ?? '/', 301));   // /home -> /, /id/beranda -> /id
    }

    $isHome = $slug === null;   // rute '/' dan '/id' tidak punya parameter
    $found = PublicLookup::findPage($isHome ? $homeSlug : $slug, $this->lang, $locales);
    if ($isHome && ($found === null || $found['redirect'] !== null)) {
      abort(503, 'Situs sedang disiapkan.', ['Retry-After' => '3600']);   // beranda bahasa ini belum ada atau offline
    }
    abort_if($found === null, 404);   // tidak ada, offline, belum diterjemahkan, atau slug tidak sah

    if ($found['redirect'] !== null) {   // slug bahasa lain: ke padanannya di bahasa ALAMAT ini
      $to = LinkResolver::address('page', $found['redirect'], $this->lang);
      abort_if($to === null, 404);
      throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect($to, 301));
    }

    $this->pageId = (int) $found['model']->id;

    $page = $found['model'];
    view()->share('title', Names::exact($page->getRawOriginal('meta_title'), $this->lang) ?: Names::exact($page->getRawOriginal('title'), $this->lang));
    view()->share('description', Names::exact($page->getRawOriginal('meta_description'), $this->lang));
    view()->share('alternates', PublicLookup::alternateUrls('page', $page, $locales));
  }

  /** Permintaan pembaruan Livewire (/livewire/update) tidak membawa awalan bahasa: pulihkan dari properti terkunci. */
  public function hydrate(): void
  {
    app()->setLocale($this->lang);
  }

  /** Isi sendiri + snippet sisipan + snippet penutup, sudah dibersihkan. */
  #[Computed]
  public function document(): array
  {
    $page = \App\Models\Page::query()->findOrFail($this->pageId);

    return PublicLookup::document($page->getRawOriginal('content'), Languages::fromConfig()['locales'], true, $this->lang);
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
