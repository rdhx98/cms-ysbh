<?php

/**
 * Artikel publik (rilis 24): /articles/{slug} (EN) dan /id/artikel/{slug} (ID); bahasa = awalan alamat. Slug dicari KETAT di bahasa itu;
 * slug bahasa lain yang artikelnya punya padanan di bahasa ini dialihkan 301 ke padanannya, selain itu 404.
 * Kolom `content` artikel lama berisi HTML mentah: PublicLookup::document membaca NILAI MENTAH dan mengimpornya sebagai satu blok Paragraf.
 * JANGAN mencetak {!! $article->content !!} atau {!! $article->title !!}: judul berbentuk JSON per bahasa, isi dirender lewat <x-content.sections>.
 */

use App\Content\Blocks\ArticleCards;
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
  public int $articleId = 0;

  #[Locked]
  public string $lang = 'en';

  public function mount(string $slug): void
  {
    $this->lang = Languages::forRequest();
    $locales = Languages::fromConfig()['locales'];
    $found = PublicLookup::findArticle($slug, $this->lang, $locales);
    abort_if($found === null, 404);   // tidak ada, bukan berstatus terbit, belum diterjemahkan, atau slug tidak sah

    if ($found['redirect'] !== null) {
      $to = LinkResolver::address('article', $found['redirect'], $this->lang);
      abort_if($to === null, 404);
      throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect($to, 301));
    }

    $this->articleId = (int) $found['model']->id;

    $post = $found['model'];
    view()->share('title', Names::exact($post->getRawOriginal('meta_title'), $this->lang) ?: Names::exact($post->getRawOriginal('title'), $this->lang));
    view()->share('description', Names::exact($post->getRawOriginal('meta_description'), $this->lang));
    view()->share('alternates', PublicLookup::alternateUrls('article', $post, $locales));
    view()->share('currentArticleId', $this->articleId);   // blok "Artikel Terbaru" tidak menampilkan artikel yang sedang dibuka
  }

  public function hydrate(): void
  {
    app()->setLocale($this->lang);
  }

  #[Computed]
  public function post()
  {
    return \App\Models\Post::query()->findOrFail($this->articleId);
  }

  #[Computed]
  public function document(): array
  {
    return PublicLookup::document($this->post->getRawOriginal('content'), Languages::fromConfig()['locales'], true, $this->lang);
  }

  /** @return array{title:string,category:string,dateIso:string,dateLabel:string} */
  #[Computed]
  public function header(): array
  {
    $post = $this->post;
    [$iso, $label] = ArticleCards::date($post->getRawOriginal('published_at'), $this->lang);
    $category = $post->category_id ? \App\Models\Category::query()->find($post->category_id) : null;

    return [
      'title' => Names::exact($post->getRawOriginal('title'), $this->lang),
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
      <p class="font-jakarta text-xs font-bold tracking-[0.14em] text-coral uppercase">{{ $this->header['category'] }}</p>
    @endif
    <h1 class="font-fraunces mt-2 text-3xl leading-tight font-bold text-forest">{{ $this->header['title'] }}</h1>
    @if ($this->header['dateIso'] !== '')
      <time datetime="{{ $this->header['dateIso'] }}" class="mt-3 block text-sm text-ink-soft">{{ $this->header['dateLabel'] }}</time>
    @endif
  </header>

  <x-content.sections
    :blocks="$this->document['blocks']"
    :order="$this->document['order']"
    :settings="$this->document['settings']"
    :lang="$lang"
  />
</article>
