<?php

use App\Content\ContentType;
use App\Content\Languages;
use App\Content\SiteStats;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Daftar "perlu perhatian" (rilis 80): halaman, artikel, dan snippet yang masih berpenanda [ISI-DULU] / berfoto kosong, serta halaman dan artikel yang
 * terjemahannya belum lengkap. Isi dan hitungannya sama dengan kartu dashboard (SiteStats). Hanya admin dan editor.
 *
 *   <livewire:content.attention-list />                          di halaman sendiri atau di bawah dashboard
 *   Route::livewire('/dashboard/perhatian', 'content.attention-list')->name('attention');   (opsional)
 *
 * Alamat tab dan saringan ada di URL (?tab=translation&kind=page&all=1), jadi kartu dashboard bisa menaut langsung ke tab yang tepat.
 * Daftar dihitung langsung (tidak dicache): setelah menyimpan perbaikan, daftar ini langsung berkurang (kartu dashboard dicache 10 menit).
 */
new class extends Component {
  #[Url(as: 'tab')]
  public string $tab = 'placeholders';

  #[Url(as: 'kind')]
  public string $kind = '';

  #[Url(as: 'all')]
  public bool $all = false;

  public bool $showInfo = false;

  public function setTab(string $tab): void
  {
    $this->tab = in_array($tab, SiteStats::ATTENTION_TABS, true) ? $tab : 'placeholders';
  }

  public function setKind(string $kind): void
  {
    $this->kind = array_key_exists($kind, SiteStats::KIND_LABELS) ? $kind : '';
  }

  public function mount(): void
  {
    $this->authorizeReviewer();
  }

  /** Nilai dari alamat (?tab=, ?kind=) bisa apa saja: dipakai hanya bila sah. */
  #[Computed]
  public function currentTab(): string
  {
    return in_array($this->tab, SiteStats::ATTENTION_TABS, true) ? $this->tab : 'placeholders';
  }

  #[Computed]
  public function currentKind(): string
  {
    return array_key_exists($this->kind, SiteStats::KIND_LABELS) ? $this->kind : '';
  }

  public function hydrate(): void
  {
    $this->authorizeReviewer();
  }

  private function authorizeReviewer(): void
  {
    $user = auth()->user();
    abort_unless($user !== null && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'editor']), 403);
  }

  #[Computed]
  public function data(): array
  {
    $locales = Languages::fromConfig()['locales'];
    $data = SiteStats::attention($locales, app()->getLocale(), $this->all, $this->showInfo, $this->currentKind);
    foreach ($data['rows'] as $tab => $rows) {
      foreach ($rows as $i => $row) {
        $data['rows'][$tab][$i]['edit_url'] = $this->address($row['kind'] . '.edit', [ContentType::from($row['kind'])->routeParam() => $row['id']]);
        $data['rows'][$tab][$i]['preview_url'] = $this->address('preview.record', ['type' => $row['kind'], 'id' => $row['id']]);
      }
    }

    return $data;
  }

  /** Alamat bernama rute pertama yang ada: awalan rute halaman ini dipertahankan ("v2.dashboard" -> "v2.article.edit"), lalu tanpa awalan, lalu "v2.". */
  private function address(string $suffix, array $params): ?string
  {
    foreach (ContentType::routeCandidates((string) request()->route()?->getName(), $suffix) as $name) {
      if (Route::has($name)) {
        return route($name, $params);
      }
    }

    return null;
  }

  public function placeholder(): string
  {
    return '<div class="h-48 w-full animate-pulse rounded-xl bg-zinc-100"></div>';
  }
};
?>

<div>
  <x-content.attention-table
    :tab="$this->currentTab"
    :counts="$this->data['counts']"
    :rows="$this->data['rows'][$this->currentTab] ?? []"
    :kind="$this->currentKind"
    :all="$all"
    :show-info="$showInfo"
  />
</div>
