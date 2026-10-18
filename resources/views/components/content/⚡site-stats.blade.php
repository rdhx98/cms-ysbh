<?php

use App\Content\Languages;
use App\Content\SiteStats;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Statistik situs untuk dashboard (rilis 79). Pasang di halaman dashboard Anda:   <livewire:content.site-stats lazy />
 *
 * Admin/editor melihat seluruh situs (kunjungan, terpopuler, antrean tinjauan, audit terjemahan dan penanda, pengguna). Penulis hanya melihat
 * artikelnya sendiri. Angka dihitung ulang setiap tombol periode ditekan; audit terjemahan dan penanda dicache 10 menit.
 * Rincian dan privasi pencacah: docs/STATISTIK.md.
 */
new class extends Component {
  public int $days = 30;

  /** Rilis 80: alamat halaman content.attention-list. Bila diisi, kartu Terjemahan dan Penanda menaut ke tab daftarnya. <livewire:content.site-stats attention-url="/v2/perhatian" /> */
  #[\Livewire\Attributes\Locked]
  public string $attentionUrl = '';

  public function setDays(int $days): void
  {
    if (in_array($days, SiteStats::PERIODS, true)) {
      $this->days = $days;
    }
  }

  #[Computed]
  public function data(): array
  {
    $user = auth()->user();
    abort_if($user === null, 403);
    $reviewer = method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'editor']);
    $locales = Languages::fromConfig()['locales'];

    return SiteStats::dashboard((int) $user->id, $reviewer, $this->days, $locales, app()->getLocale());
  }

  public function placeholder(): string
  {
    return '<div class="h-64 w-full animate-pulse rounded-xl bg-zinc-100"></div>';
  }
};
?>

<div>
  <x-content.stats-panel :stats="$this->data" :days="$days" :attention-url="$attentionUrl" />
</div>
