<?php

/**
 * CONTOH untuk aplikasi LANDING (bukan bagian yang disinkronkan oleh tools/sync-landing.php): halaman artikel /artikel/{slug}.
 * Letakkan di resources/views/components/⚡article-show.blade.php dan daftarkan rute (contoh-kode/landing-routes.php):
 *   Route::livewire('/artikel/{slug}', 'article-show')->name('article.show');
 *
 * Kolom `content` artikel lama berisi HTML mentah (cast array menghasilkan null): PublicLookup::document membaca NILAI MENTAH dan mengimpor
 * HTML itu sebagai satu blok Paragraf, jadi artikel lama tetap tampil. JANGAN mencetak {!! $article->content !!} atau {!! $article->title !!}:
 * judul berbentuk JSON per bahasa dan isi dirender lewat <x-content.sections>.
 */

use App\Content\Blocks\ArticleCards;
use App\Content\Names;
use App\Content\PublicLookup;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.landing.index')] class extends Component {
  #[Locked]
  public int $articleId = 0;

  #[Locked]
  public string $lang = 'id';

  public function mount(string $slug): void
  {
    $locales = config('app.supported_locales', ['id', 'en']);
    $found = PublicLookup::findArticle($slug, app()->getLocale(), $locales);
    abort_if($found === null, 404);   // tidak ada, bukan berstatus terbit, atau slug tidak sah

    $this->articleId = (int) $found['model']->id;
    $this->lang = $found['locale'];
    app()->setLocale($this->lang);

    $post = $found['model'];
    $title = Names::of($post->getRawOriginal('meta_title'), $this->lang) ?: Names::of($post->getRawOriginal('title'), $this->lang);
    view()->share('pageTitle', $title);
    view()->share('pageDescription', Names::of($post->getRawOriginal('meta_description'), $this->lang));
    view()->share('currentArticleId', $this->articleId);   // blok "Artikel Terbaru" tidak menampilkan artikel yang sedang dibuka
  }

  #[Computed]
  public function post()
  {
    return \App\Models\Post::query()->findOrFail($this->articleId);
  }

  #[Computed]
  public function document(): array
  {
    return PublicLookup::document($this->post->getRawOriginal('content'), config('app.supported_locales', ['id', 'en']));
  }

  /** @return array{title:string,category:string,dateIso:string,dateLabel:string} */
  #[Computed]
  public function header(): array
  {
    $post = $this->post;
    [$iso, $label] = ArticleCards::date($post->getRawOriginal('published_at'), $this->lang);
    $category = $post->category_id ? \App\Models\Category::query()->find($post->category_id) : null;

    return [
      'title' => Names::of($post->getRawOriginal('title'), $this->lang),
      'category' => $category ? Names::of($category->getRawOriginal('name'), $this->lang) : '',
      'dateIso' => $iso,
      'dateLabel' => $label,
    ];
  }
};
?>

<article>
  <header class="mx-auto max-w-3xl px-4 pt-10 pb-6">
    @if ($this->header['category'] !== '')
      <p class="text-xs font-bold tracking-wide text-foresty uppercase">{{ $this->header['category'] }}</p>
    @endif
    <h1 class="mt-2 text-3xl leading-tight font-extrabold text-gray-900">{{ $this->header['title'] }}</h1>
    @if ($this->header['dateIso'] !== '')
      <time datetime="{{ $this->header['dateIso'] }}" class="mt-3 block text-sm text-gray-500">{{ $this->header['dateLabel'] }}</time>
    @endif
  </header>

  <x-content.sections
    :blocks="$this->document['blocks']"
    :order="$this->document['order']"
    :settings="$this->document['settings']"
    :lang="$lang"
  />
</article>
