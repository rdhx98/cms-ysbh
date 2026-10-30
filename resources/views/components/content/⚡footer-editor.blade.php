<?php

use App\Content\FooterSettings;
use App\Content\Languages;
use App\Content\MenuTarget;
use App\Content\Names;
use App\Content\PublicLookup;
use App\Content\SiteFooter;
use App\Content\SocialIcons;
use App\Livewire\Traits\SearchesLinkTargets;
use App\Livewire\Traits\WithNotifications;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Editor isi footer situs publik (rilis 55). Tata letak footer TETAP milik landing (opsional/footer.blade.php); di sini hanya ISINYA:
 * deskripsi, dua kolom tautan, kolom kontak, hak cipta, dan slogan. Semua teks dua bahasa; bahasa yang dikosongkan memakai bahasa bawaan.
 *
 * Dipasang di mana saja, mis. sebagai tab/bagian di halaman Navigation builder (satu baris), atau di halamannya sendiri:
 *
 *   <livewire:content.footer-editor />
 *
 * Aturan isi dan keamanan alamat ada di App\Content\SiteFooter (diuji di tests/site-footer-test.php); komponen ini hanya formulir.
 * Hanya admin dan editor. Tersimpan di tabel site_settings (kunci "footer"; php artisan migrate).
 */
new class extends Component {
  use WithNotifications;
  use SearchesLinkTargets; // searchLinkTargets('page', kata): pemilih halaman untuk tujuan tautan

  /** Bahasa bawaan dulu. Dipakai juga oleh SearchesLinkTargets. */
  public array $activeLocales = [];

  #[Locked]
  public string $defaultLocale = 'en';

  /** Isi formulir (bentuk SiteFooter::forEditor). */
  public array $f = [];

  /** Galat dari penyimpanan terakhir (daftar kalimat). */
  public array $errorsList = [];

  /** Tautan yang sedang memilih halaman, "kolom:baris" ('' = tidak ada). */
  public string $pickFor = '';

  public string $pageQuery = '';

  /** Indeks kontak yang pemilih ikon merek-nya sedang terbuka ('' = tertutup). */
  public string $iconPick = '';

  /** Apakah isi sudah pernah disimpan dari CMS (false = situs masih memakai isi bawaan). */
  public bool $stored = false;

  /** Tabel site_settings sudah ada? Bila belum, formulir hanya menampilkan petunjuk migrasi. */
  public bool $ready = true;

  public function mount(): void
  {
    abort_unless($this->canManage(), 403);
    $cfg = Languages::fromConfig();
    $this->defaultLocale = $cfg['default'];
    $this->activeLocales = array_values(array_unique([$cfg['default'], ...$cfg['locales']]));
    $this->load();
  }

  /** Tampilan sementara saat dimuat malas (lazy) di tab Footer Navigation builder. */
  public function placeholder(): string
  {
    return '<div class="flex flex-col gap-3 p-2" aria-busy="true"><div class="h-14 animate-pulse rounded-2xl bg-gray-100"></div><div class="h-10 animate-pulse rounded-xl bg-gray-100"></div><div class="h-64 animate-pulse rounded-2xl bg-gray-100"></div></div>';
  }

  private function canManage(): bool
  {
    $user = auth()->user();

    return $user && method_exists($user, 'hasRole') && $user->hasRole(['admin', 'editor']);
  }

  /** Nama bahasa untuk label; kode lain tampil sebagai kodenya. */
  public function langName(string $code): string
  {
    return ['en' => 'English', 'id' => 'Indonesia'][$code] ?? strtoupper($code);
  }

  private function load(): void
  {
    $this->errorsList = [];
    $this->pickFor = '';
    $this->pageQuery = '';
    try {
      $this->ready = \Illuminate\Support\Facades\Schema::hasTable(FooterSettings::TABLE);
      $data = $this->ready ? FooterSettings::load() : SiteFooter::defaults();
      $this->stored = $this->ready && FooterSettings::exists();
    } catch (\Throwable $e) {
      report($e);
      $this->ready = false;
      $this->stored = false;
      $data = SiteFooter::defaults();
    }
    $this->f = SiteFooter::forEditor($data, $this->activeLocales, $this->defaultLocale);
  }

  /** @return list<string> slug daftar artikel semua bahasa */
  #[Computed]
  public function indexSlugs(): array
  {
    return array_map(fn (string $l) => Languages::indexSlug(config('cms.articles_index_slug'), $l), $this->activeLocales);
  }

  private function articlesSlug(): string
  {
    return Languages::indexSlug(config('cms.articles_index_slug'), $this->defaultLocale);
  }

  /**
   * Untuk tiap tautan yang tujuannya terisi: alamat yang dilihat pembaca tiap bahasa, atau galat / peringatan.
   * Kunci "kolom:baris" -> {error?: string, warning?: string, addresses?: array<bahasa,string>}
   */
  #[Computed]
  public function previews(): array
  {
    $out = [];
    foreach ($this->f['columns'] ?? [] as $c => $col) {
      foreach ($col['links'] ?? [] as $i => $link) {
        $url = trim((string) ($link['url'] ?? ''));
        if ($url === '') {
          continue;
        }
        $built = SiteFooter::target($url, $this->activeLocales, $this->defaultLocale, $this->indexSlugs, $this->articlesSlug());
        if ($built['error'] !== null) {
          $out["$c:$i"] = ['error' => $built['error']];
          continue;
        }
        $row = ['addresses' => []];
        try {
          foreach ($this->activeLocales as $l) {
            $row['addresses'][$l] = PublicLookup::localizeUrl($built['url'], $l);
          }
          $t = MenuTarget::parse($built['url'], null, $this->indexSlugs, $this->activeLocales, $this->defaultLocale);
          if ($t['kind'] === 'page' && $t['ref'] !== '' && PublicLookup::sibling('page', $t['ref'], $this->defaultLocale, $this->activeLocales, $this->defaultLocale) === null) {
            $row['warning'] = 'Halaman ini belum online atau tidak ditemukan: tautannya akan 404.';
          }
        } catch (\Throwable) {
          $row['addresses'] = [];   // basis data belum siap: pratinjau dilewati, simpan tetap berfungsi
        }
        $out["$c:$i"] = $row;
      }
    }

    return $out;
  }

  /**
   * Berapa teks yang masih kosong di tiap bahasa NON-bawaan padahal bahasa bawaannya sudah terisi (pembaca melihat teks bawaan di tempatnya).
   * Dipakai untuk lencana di tab bahasa. Bahasa bawaan selalu 0.
   *
   * @return array<string,int>
   */
  #[Computed]
  public function gaps(): array
  {
    $out = array_fill_keys($this->activeLocales, 0);
    $d = $this->defaultLocale;
    $maps = [$this->f['description'] ?? [], $this->f['contact_heading'] ?? [], $this->f['copyright'] ?? [], $this->f['tagline'] ?? []];
    foreach ($this->f['columns'] ?? [] as $col) {
      $maps[] = $col['heading'] ?? [];
      foreach ($col['links'] ?? [] as $link) {
        $maps[] = $link['label'] ?? [];
      }
    }
    foreach ($this->f['contacts'] ?? [] as $contact) {
      if (in_array($contact['type'] ?? '', ['address', 'social'], true)) {   // telepon dan email sama di semua bahasa
        $maps[] = $contact['text'] ?? [];
      }
    }
    foreach ($maps as $map) {
      if (trim((string) ($map[$d] ?? '')) === '') {
        continue;
      }
      foreach ($this->activeLocales as $l) {
        if ($l !== $d && trim((string) ($map[$l] ?? '')) === '') {
          $out[$l]++;
        }
      }
    }

    return $out;
  }

  /**
   * Footer mini per bahasa untuk panel pratinjau: model tampil yang SAMA dengan yang dipakai situs (SiteFooter::view),
   * dari isi formulir saat ini (belum disimpan). Alamat tautan tidak perlu di sini, jadi tanpa basis data.
   *
   * @return array<string,?array>
   */
  #[Computed]
  public function miniFooters(): array
  {
    $out = [];
    foreach ($this->activeLocales as $l) {
      try {
        $out[$l] = SiteFooter::view($this->f, $l, $this->activeLocales, $this->defaultLocale, fn (string $url, string $loc): string => $url);
      } catch (\Throwable) {
        $out[$l] = null;
      }
    }

    return $out;
  }

  #[Computed]
  public function pageResults(): array
  {
    return $this->pickFor === '' || mb_strlen(trim($this->pageQuery)) < 2 ? [] : $this->searchLinkTargets('page', $this->pageQuery);
  }

  private function guard(): bool
  {
    if (!$this->canManage()) {
      $this->notify('Anda tidak memiliki otorisasi untuk mengubah footer.', 'error');

      return false;
    }

    return true;
  }

  private function blankMap(): array
  {
    return array_fill_keys($this->activeLocales, '');
  }

  /** Tukar posisi $i dengan tetangganya ($dir -1 ke atas, +1 ke bawah); di luar jangkauan = tidak berbuat apa-apa. */
  private function swap(array $list, int $i, int $dir): array
  {
    $j = $i + ($dir < 0 ? -1 : 1);
    if (!isset($list[$i], $list[$j])) {
      return array_values($list);
    }
    [$list[$i], $list[$j]] = [$list[$j], $list[$i]];

    return array_values($list);
  }

  public function addLink(int $c): void
  {
    if (!$this->guard() || !isset($this->f['columns'][$c])) {
      return;
    }
    if (count($this->f['columns'][$c]['links']) >= SiteFooter::MAX_LINKS) {
      $this->notify('Satu kolom memuat maksimal ' . SiteFooter::MAX_LINKS . ' tautan.', 'error');

      return;
    }
    $this->f['columns'][$c]['links'][] = ['label' => $this->blankMap(), 'url' => '', 'new_tab' => false];
    $this->pickFor = '';
  }

  public function removeLink(int $c, int $i): void
  {
    if (!$this->guard() || !isset($this->f['columns'][$c]['links'][$i])) {
      return;
    }
    unset($this->f['columns'][$c]['links'][$i]);
    $this->f['columns'][$c]['links'] = array_values($this->f['columns'][$c]['links']);
    $this->pickFor = '';
  }

  public function moveLink(int $c, int $i, int $dir): void
  {
    if (!$this->guard() || !isset($this->f['columns'][$c]['links'])) {
      return;
    }
    $this->f['columns'][$c]['links'] = $this->swap($this->f['columns'][$c]['links'], $i, $dir);
    $this->pickFor = '';
  }

  public function addContact(string $type = 'address'): void
  {
    if (!$this->guard()) {
      return;
    }
    if (count($this->f['contacts']) >= SiteFooter::MAX_CONTACTS) {
      $this->notify('Maksimal ' . SiteFooter::MAX_CONTACTS . ' baris kontak.', 'error');

      return;
    }
    $this->f['contacts'][] = [
      'type' => in_array($type, SiteFooter::CONTACT_TYPES, true) ? $type : 'address',
      'text' => $this->blankMap(),
      'url' => '',
      'icon' => '',
      'badge' => '',
    ];
  }

  /** Buka / tutup pemilih ikon merek untuk satu kontak sosial (rilis 86). */
  public function toggleIconPicker(int $i): void
  {
    $isSocial = ($this->f['contacts'][$i]['type'] ?? '') === 'social';
    $this->iconPick = $isSocial && $this->iconPick !== (string) $i ? (string) $i : '';
  }

  /** Pilih ikon merek (slug dari SocialIcons); slug yang tidak dikenal ditolak. */
  public function setIcon(int $i, string $slug): void
  {
    if (!$this->guard() || ($this->f['contacts'][$i]['type'] ?? '') !== 'social' || !SocialIcons::has($slug)) {
      return;
    }
    $this->f['contacts'][$i]['icon'] = $slug;
    $this->iconPick = '';
  }

  /** Kembali ke otomatis: ikon ditebak dari tautan. */
  public function clearIcon(int $i): void
  {
    if (!$this->guard() || !isset($this->f['contacts'][$i])) {
      return;
    }
    $this->f['contacts'][$i]['icon'] = '';
    $this->iconPick = '';
  }

  public function removeContact(int $i): void
  {
    if (!$this->guard() || !isset($this->f['contacts'][$i])) {
      return;
    }
    unset($this->f['contacts'][$i]);
    $this->f['contacts'] = array_values($this->f['contacts']);
    $this->iconPick = '';
  }

  public function moveContact(int $i, int $dir): void
  {
    if (!$this->guard()) {
      return;
    }
    $this->f['contacts'] = $this->swap($this->f['contacts'], $i, $dir);
    $this->iconPick = '';
  }

  public function startPick(int $c, int $i): void
  {
    $this->pickFor = isset($this->f['columns'][$c]['links'][$i]) ? "$c:$i" : '';
    $this->pageQuery = '';
  }

  public function cancelPick(): void
  {
    $this->pickFor = '';
    $this->pageQuery = '';
  }

  /** Tujuan siap pakai: 'home' (beranda) atau 'articles' (daftar artikel). */
  public function pickPreset(string $kind): void
  {
    if (!$this->guard() || !in_array($kind, ['home', 'articles'], true)) {
      return;
    }
    $built = MenuTarget::build($kind, '', $this->articlesSlug());
    if ($built['error'] === null) {
      $this->applyPick($built['url']);
    }
  }

  public function pickPage(int $id): void
  {
    if (!$this->guard()) {
      return;
    }
    $page = \App\Models\Page::query()->find($id);
    $slug = $page ? Names::of($page->getRawOriginal('slug'), $this->defaultLocale, $this->activeLocales) : '';
    if ($slug === '') {
      $this->notify('Halaman itu belum punya slug.', 'error');

      return;
    }
    $this->applyPick('/' . $slug);
  }

  private function applyPick(string $url): void
  {
    [$c, $i] = array_map('intval', explode(':', $this->pickFor . ':0'));
    if ($this->pickFor !== '' && isset($this->f['columns'][$c]['links'][$i])) {
      $this->f['columns'][$c]['links'][$i]['url'] = $url;
    }
    $this->cancelPick();
  }

  public function save(): void
  {
    if (!$this->guard()) {
      return;
    }
    if (!$this->ready) {
      $this->notify('Tabel pengaturan belum ada. Jalankan php artisan migrate lebih dulu.', 'error');

      return;
    }

    $result = SiteFooter::sanitize($this->f, $this->activeLocales, $this->defaultLocale, $this->indexSlugs, $this->articlesSlug());
    $this->errorsList = $result['errors'];
    if ($result['errors'] !== []) {
      $this->notify('Footer belum disimpan: periksa ' . count($result['errors']) . ' isian yang bermasalah.', 'error');

      return;
    }

    try {
      FooterSettings::save($result['data'], auth()->id());
    } catch (\Throwable $e) {
      report($e);
      $this->notify('Footer gagal disimpan. Coba lagi.', 'error');

      return;
    }
    $this->f = SiteFooter::forEditor($result['data'], $this->activeLocales, $this->defaultLocale);
    $this->stored = true;
    $this->pickFor = '';
    $this->audit('Isi footer diperbarui');
    $this->notify('Footer disimpan. Perubahan langsung tampil di situs.', 'success');
  }

  /** Hapus isi tersimpan: situs memakai isi bawaan (sama dengan footer lama). */
  public function restoreDefaults(): void
  {
    if (!$this->guard() || !$this->ready) {
      return;
    }
    try {
      FooterSettings::reset();
    } catch (\Throwable $e) {
      report($e);
      $this->notify('Gagal mengembalikan isi bawaan.', 'error');

      return;
    }
    $this->audit('Isi footer dikembalikan ke bawaan');
    $this->load();
    $this->notify('Footer kembali memakai isi bawaan.', 'success');
  }

  /** Riwayat (activity_log) bila paket Spatie terpasang; galatnya tidak pernah menggagalkan simpan. */
  private function audit(string $text): void
  {
    try {
      if (function_exists('activity')) {
        activity('footer_updates')->causedBy(auth()->user())->withProperties(['key' => SiteFooter::KEY])->log($text);
      }
    } catch (\Throwable $e) {
      report($e);
    }
  }
};
?>

@php
  $field = 'w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm placeholder:text-gray-400 focus:border-foresty focus:ring-foresty';
  $iconBtn = 'inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty disabled:pointer-events-none disabled:opacity-30';
  $chip = 'inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-foresty hover:text-foresty focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty disabled:pointer-events-none disabled:opacity-40';
  $panel = 'flex flex-col gap-3';
  $contactTypes = ['phone' => ['Telepon', 'phone'], 'address' => ['Alamat', 'pin'], 'email' => ['Email', 'mail'], 'social' => ['Media sosial', 'share']];
  $columns = $f['columns'] ?? [];
  $contacts = $f['contacts'] ?? [];
  $maxLinks = \App\Content\SiteFooter::MAX_LINKS;
  $maxContacts = \App\Content\SiteFooter::MAX_CONTACTS;
  $atMaxContacts = count($contacts) >= $maxContacts;
  $sections = ['desc' => ['Deskripsi', 'type']];
  foreach ($columns as $c => $col) {
    $head = trim((string) ($col['heading'][$defaultLocale] ?? ''));
    $sections['c' . $c] = [$head === '' ? 'Tautan ' . ($c + 1) : mb_strimwidth($head, 0, 16, '…'), 'columns'];
  }
  $sections['contact'] = ['Kontak', 'phone'];
  $sections['bottom'] = ['Hak cipta', 'copyright'];
  $pal = \App\Content\PublicLookup::palette();
  $pvBg = $pal['forest']['hex'];
  $pvGold = $pal['aurum']['hex'];
  $pvIcons = ['phone' => 'phone', 'address' => 'pin', 'email' => 'mail'];
  $langNames = [];
  foreach ($activeLocales as $l) {
    $langNames[$l] = $this->langName($l);
  }
@endphp

<div class="relative flex flex-col gap-4" data-footer-editor x-effect="if ($store.editor.lang !== 'both') pv = $store.editor.lang" x-data="{ pv: @js(in_array(app()->getLocale(), $activeLocales, true) ? app()->getLocale() : $defaultLocale), sec: 'desc', insertYear(l) { const el = this.$root.querySelector('[data-copyright] [data-lang-field=' + l + '] input'); if (!el || el.value.includes('{year}')) return; el.value = (el.value + ' {year}').trim(); el.dispatchEvent(new Event('input', { bubbles: true })); el.focus(); } }">
  @if (! $ready)
    <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" data-footer-not-ready>
      <x-content.icon name="alert" class="mt-0.5 h-4 w-4" />
      <span>Tabel <b>site_settings</b> belum ada. Jalankan <code class="rounded bg-amber-100 px-1">php artisan migrate</code>, lalu muat ulang halaman.</span>
    </div>
  @endif

  @if ($errorsList !== [])
    <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" data-footer-errors role="alert">
      <p class="mb-1 flex items-center gap-1.5 font-semibold"><x-content.icon name="alert" class="h-4 w-4" /> Belum tersimpan. Perbaiki:</p>
      <ul class="list-disc pl-9 text-red-700">
        @foreach ($errorsList as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form wire:submit="save" class="flex flex-col gap-4">
    {{-- Bilah atas: status, bahasa, simpan --}}
    <div class="z-20 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur md:sticky md:top-0" data-footer-bar>
      <div class="mr-auto min-w-0">
        <h2 class="text-base font-bold leading-tight text-foresty">Footer</h2>
        <p class="flex items-center gap-1.5 text-xs text-gray-500" data-footer-state="{{ $stored ? 'cms' : 'default' }}">
          <span class="h-2 w-2 rounded-full {{ $stored ? 'bg-emerald-500' : 'bg-amber-400' }}" aria-hidden="true"></span>
          {{ $stored ? 'Sudah diatur dari CMS' : 'Masih isi bawaan' }}
        </p>
      </div>

      <div data-footer-langs>
        <x-content.lang-switch :locales="$activeLocales" :gaps="$this->gaps" :names="$langNames" :default="$defaultLocale" />
      </div>

      <button type="submit" wire:loading.attr="disabled" wire:target="save" @disabled(! $ready) class="inline-flex items-center gap-1.5 rounded-xl bg-foresty px-5 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#043b2c] focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty focus-visible:ring-offset-2 disabled:opacity-50">
        <x-content.icon name="save" />
        <span wire:loading.remove wire:target="save">Simpan</span>
        <span wire:loading wire:target="save">Menyimpan…</span>
      </button>
    </div>

    {{-- Pilihan bagian --}}
    <div role="tablist" aria-label="Bagian footer" class="flex gap-1 overflow-x-auto rounded-xl bg-gray-100 p-1" data-footer-sections>
      @foreach ($sections as $key => [$text, $icon])
        <button
          type="button"
          role="tab"
          x-on:click="sec = '{{ $key }}'"
          x-bind:aria-selected="(sec === '{{ $key }}').toString()"
          x-bind:class="sec === '{{ $key }}' ? 'bg-white text-foresty shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          class="flex shrink-0 items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty"
        >
          <x-content.icon :name="$icon" />
          {{ $text }}
        </button>
      @endforeach
    </div>

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_21rem]">
      <div class="flex min-w-0 flex-col gap-4">
        {{-- Deskripsi --}}
        <section x-show="sec === 'desc'" class="{{ $panel }}" data-footer-panel="desc" aria-label="Deskripsi">
          <x-content.lang-field path="f.description" :values="$f['description'] ?? []" :locales="$activeLocales" :default="$defaultLocale" label="Deskripsi yayasan" :max="\App\Content\SiteFooter::MAX_DESCRIPTION" :rows="5" :counter="true" placeholder="Satu atau dua kalimat tentang yayasan" />
        </section>

        {{-- Dua kolom tautan --}}
        @foreach ($columns as $c => $col)
          <section x-show="sec === 'c{{ $c }}'" class="{{ $panel }}" wire:key="col-{{ $c }}" data-footer-column="{{ $c }}" aria-label="Kolom tautan {{ $c + 1 }}">
            <x-content.lang-field :path="'f.columns.' . $c . '.heading'" :values="$col['heading']" :locales="$activeLocales" :default="$defaultLocale" label="Judul kolom" :max="\App\Content\SiteFooter::MAX_TEXT" placeholder="Judul kolom, mis. Tentang" class="!text-base !font-bold" />

            @forelse ($col['links'] as $i => $link)
              @php $pv = $this->previews["$c:$i"] ?? null; @endphp
              <div class="rounded-xl border border-gray-200 bg-white p-2.5 shadow-sm" wire:key="link-{{ $c }}-{{ $i }}" data-footer-link>
                <div class="flex flex-col gap-2" x-bind:class="$store.editor.lang === 'both' ? '' : 'md:flex-row md:items-start'">
                  <div x-bind:class="$store.editor.lang === 'both' ? '' : 'md:w-5/12'">
                    <x-content.lang-field :path="'f.columns.' . $c . '.links.' . $i . '.label'" :values="$link['label']" :locales="$activeLocales" :default="$defaultLocale" label="Teks tautan" :max="\App\Content\SiteFooter::MAX_LABEL" placeholder="Teks tautan" />
                  </div>
                  <div class="flex items-start gap-1 md:flex-1">
                    <div class="relative min-w-0 flex-1">
                      <x-content.icon name="link" class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
                      <input type="text" wire:model.live.debounce.500ms="f.columns.{{ $c }}.links.{{ $i }}.url" aria-label="Tujuan tautan" placeholder="/halaman, https://…, mailto:…" class="{{ $field }} pl-8 font-mono text-xs">
                    </div>
                    <div class="flex shrink-0 items-center gap-0.5">
                      <button type="button" wire:click="startPick({{ $c }}, {{ $i }})" class="{{ $iconBtn }}" title="Pilih dari halaman situs" aria-label="Pilih halaman"><x-content.icon name="search" /></button>
                      <label class="relative inline-flex cursor-pointer" title="Buka di tab baru">
                        <input type="checkbox" wire:model.live="f.columns.{{ $c }}.links.{{ $i }}.new_tab" class="peer sr-only" aria-label="Buka di tab baru">
                        <span class="{{ $iconBtn }} peer-checked:bg-emerald-50 peer-checked:text-foresty peer-focus-visible:ring-2 peer-focus-visible:ring-foresty"><x-content.icon name="external" /></span>
                      </label>
                      <button type="button" wire:click="moveLink({{ $c }}, {{ $i }}, -1)" @disabled($i === 0) class="{{ $iconBtn }}" aria-label="Naikkan"><x-content.icon name="up" /></button>
                      <button type="button" wire:click="moveLink({{ $c }}, {{ $i }}, 1)" @disabled($i === count($col['links']) - 1) class="{{ $iconBtn }}" aria-label="Turunkan"><x-content.icon name="down" /></button>
                      <button type="button" wire:click="removeLink({{ $c }}, {{ $i }})" wire:confirm="Hapus tautan ini?" class="{{ $iconBtn }} hover:!bg-red-50 hover:!text-red-600" aria-label="Hapus tautan"><x-content.icon name="trash" /></button>
                    </div>
                  </div>
                </div>

                @if ($pickFor === $c . ':' . $i)
                  <div class="mt-2 rounded-xl border border-gray-200 bg-gray-50 p-2.5" data-footer-picker x-init="$el.querySelector('input[type=search]')?.focus()">
                    <div class="flex flex-wrap items-center gap-1.5">
                      <div class="relative min-w-[11rem] flex-1">
                        <x-content.icon name="search" class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
                        <input type="search" wire:model.live.debounce.300ms="pageQuery" placeholder="Cari halaman…" class="{{ $field }} pl-8" autocomplete="off" aria-label="Cari halaman">
                      </div>
                      <button type="button" wire:click="pickPreset('home')" class="{{ $chip }}"><x-content.icon name="home" /> Beranda</button>
                      <button type="button" wire:click="pickPreset('articles')" class="{{ $chip }}"><x-content.icon name="list" /> Artikel</button>
                      <button type="button" wire:click="cancelPick" class="{{ $iconBtn }}" aria-label="Tutup pemilih"><x-content.icon name="x" /></button>
                    </div>
                    @if (count($this->pageResults) > 0)
                      <ul class="mt-2 divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-200 bg-white text-sm">
                        @foreach ($this->pageResults as $r)
                          <li wire:key="fp-{{ $c }}-{{ $i }}-{{ $r['id'] }}">
                            <button type="button" wire:click="pickPage({{ $r['id'] }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-emerald-50 focus:bg-emerald-50 focus:outline-none">
                              <span class="flex min-w-0 items-center gap-2"><x-content.icon name="file" class="text-gray-400" /><span class="truncate">{{ $r['label'] }}</span></span>
                              <span class="shrink-0 text-xs text-gray-400">{{ $r['hint'] }}</span>
                            </button>
                          </li>
                        @endforeach
                      </ul>
                    @endif
                  </div>
                @endif

                @if ($pv !== null && isset($pv['error']))
                  <p class="mt-2 flex items-start gap-1.5 px-1 text-xs text-red-600"><x-content.icon name="alert" class="mt-px h-3.5 w-3.5" /> {{ $pv['error'] }}</p>
                @elseif ($pv !== null)
                  @if (! empty($pv['warning']))
                    <p class="mt-2 flex items-start gap-1.5 px-1 text-xs text-amber-700"><x-content.icon name="alert" class="mt-px h-3.5 w-3.5" /> {{ $pv['warning'] }}</p>
                  @endif
                  @foreach ($pv['addresses'] ?? [] as $l => $address)
                    <p x-show="$store.editor.showLang('{{ $l }}')" class="mt-2 flex items-center gap-1.5 px-1 text-xs text-gray-500" title="Alamat yang dibuka pembaca" wire:key="fpv-{{ $c }}-{{ $i }}-{{ $l }}"><x-content.icon name="external" class="h-3.5 w-3.5" /> <span class="break-all font-mono">{{ $address }}</span></p>
                  @endforeach
                @endif
              </div>
            @empty
              <p class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500" data-footer-empty="links">Belum ada tautan. Kolom tanpa tautan tidak tampil di situs.</p>
            @endforelse

            <button type="button" wire:click="addLink({{ $c }})" @disabled(count($col['links']) >= $maxLinks) class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-gray-300 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-foresty hover:text-foresty focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty disabled:pointer-events-none disabled:opacity-40">
              <x-content.icon name="plus" /> Tambah tautan <span class="text-xs font-normal text-gray-400">{{ count($col['links']) }} / {{ $maxLinks }}</span>
            </button>
          </section>
        @endforeach

        {{-- Kontak --}}
        <section x-show="sec === 'contact'" class="{{ $panel }}" data-footer-contacts data-footer-panel="contact" aria-label="Kontak">
          <x-content.lang-field path="f.contact_heading" :values="$f['contact_heading'] ?? []" :locales="$activeLocales" :default="$defaultLocale" label="Judul kolom kontak" :max="\App\Content\SiteFooter::MAX_TEXT" placeholder="Judul kolom, mis. Hubungi kami" class="!text-base !font-bold" />

          @forelse ($contacts as $i => $contact)
            @php
              $type = $contact['type'];
              [$typeName, $typeIcon] = $contactTypes[$type] ?? $contactTypes['address'];
              $chosen = (string) ($contact['icon'] ?? '');
              $detected = $type === 'social' ? \App\Content\SocialIcons::detect($contact['url'] ?? '') : null;
              $effective = $type === 'social' ? \App\Content\SocialIcons::resolve($chosen, $contact['url'] ?? '') : '';
            @endphp
            <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm" wire:key="contact-{{ $i }}" data-footer-contact="{{ $type }}">
              <div class="flex items-start gap-3">
                @if ($type === 'social')
                  <button type="button" wire:click="toggleIconPicker({{ $i }})" aria-expanded="{{ $iconPick === (string) $i ? 'true' : 'false' }}" aria-label="Pilih ikon" title="Pilih ikon" class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-foresty text-white transition hover:ring-2 hover:ring-foresty hover:ring-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty focus-visible:ring-offset-2">
                    @if ($effective !== '')
                      <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true"><path d="{{ \App\Content\SocialIcons::path($effective) }}"/></svg>
                    @else
                      <x-content.icon name="share" class="h-5 w-5" />
                    @endif
                    <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm"><x-content.icon name="pencil" class="h-3 w-3" /></span>
                  </button>
                @else
                  <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-foresty text-white" aria-hidden="true"><x-content.icon :name="$typeIcon" class="h-5 w-5" /></span>
                @endif

                <div class="flex min-w-0 flex-1 flex-col gap-2">
                  <div class="flex items-center gap-2">
                    <select wire:model.live="f.contacts.{{ $i }}.type" aria-label="Jenis kontak" class="w-auto rounded-full border-gray-200 bg-gray-50 py-1 pl-3 pr-8 text-xs font-semibold text-gray-700 focus:border-foresty focus:ring-foresty">
                      @foreach ($contactTypes as $value => [$text])
                        <option value="{{ $value }}">{{ $text }}</option>
                      @endforeach
                    </select>
                    <span class="ml-auto flex items-center gap-0.5">
                      <button type="button" wire:click="moveContact({{ $i }}, -1)" @disabled($i === 0) class="{{ $iconBtn }}" aria-label="Naikkan"><x-content.icon name="up" /></button>
                      <button type="button" wire:click="moveContact({{ $i }}, 1)" @disabled($i === count($contacts) - 1) class="{{ $iconBtn }}" aria-label="Turunkan"><x-content.icon name="down" /></button>
                      <button type="button" wire:click="removeContact({{ $i }})" wire:confirm="Hapus baris kontak ini?" class="{{ $iconBtn }} hover:!bg-red-50 hover:!text-red-600" aria-label="Hapus kontak"><x-content.icon name="trash" /></button>
                    </span>
                  </div>

                  @if (in_array($type, ['phone', 'email'], true))
                    <div class="relative">
                      <input type="text" wire:model.live.debounce.400ms="f.contacts.{{ $i }}.text.{{ $defaultLocale }}" maxlength="{{ \App\Content\SiteFooter::MAX_TEXT }}" aria-label="{{ $type === 'phone' ? 'Nomor telepon' : 'Alamat email' }}" class="{{ $field }} pr-24" placeholder="{{ $type === 'phone' ? '+62 …' : 'nama@contoh.org' }}">
                      <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-semibold text-gray-300">semua bahasa</span>
                    </div>
                  @else
                    <x-content.lang-field :path="'f.contacts.' . $i . '.text'" :values="$contact['text']" :locales="$activeLocales" :default="$defaultLocale" :label="$type === 'address' ? 'Alamat' : 'Nama akun'" :max="\App\Content\SiteFooter::MAX_TEXT" :placeholder="$type === 'address' ? 'Jl. …, Jayapura' : '@ysbh'" />
                    <div class="relative">
                      <x-content.icon name="link" class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
                      <input type="text" wire:model.live.debounce.500ms="f.contacts.{{ $i }}.url" aria-label="{{ $type === 'address' ? 'Tautan peta' : 'Tautan akun' }}" placeholder="{{ $type === 'address' ? 'Tautan peta (opsional), https://maps.app.goo.gl/…' : 'https://www.instagram.com/…' }}" class="{{ $field }} pl-8 font-mono text-xs">
                    </div>
                  @endif

                  @if ($type === 'social')
                    <p class="flex flex-wrap items-center gap-1.5 text-xs {{ $effective === '' ? 'text-amber-700' : 'text-gray-500' }}" data-footer-icon data-footer-icon-status>
                      @if ($chosen !== '')
                        <b class="font-semibold text-gray-800">{{ \App\Content\SocialIcons::title($chosen) }}</b>
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">pilihan Anda</span>
                        <button type="button" wire:click="clearIcon({{ $i }})" class="inline-flex items-center gap-1 rounded px-1 text-gray-500 underline-offset-2 hover:text-foresty hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty"><x-content.icon name="refresh" class="h-3 w-3" /> Otomatis</button>
                      @elseif ($detected !== null)
                        <b class="font-semibold text-gray-800">{{ \App\Content\SocialIcons::title($detected) }}</b>
                        <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-800">terdeteksi dari tautan</span>
                      @else
                        <x-content.icon name="alert" class="h-3.5 w-3.5" /> Ikon belum ditentukan. Klik lingkaran di kiri untuk memilih.
                      @endif
                    </p>
                  @endif
                </div>
              </div>

              @if ($type === 'social' && $iconPick === (string) $i)
                <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-3" x-data="{ q: '' }" x-init="$nextTick(() => $el.querySelector('input[type=search]')?.focus())" data-footer-icon-picker>
                  <div class="mb-2 flex items-center gap-2">
                    <div class="relative flex-1">
                      <x-content.icon name="search" class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
                      <input type="search" x-model="q" placeholder="Cari, mis. instagram" class="{{ $field }} pl-8" autocomplete="off" aria-label="Cari ikon">
                    </div>
                    <button type="button" wire:click="toggleIconPicker({{ $i }})" class="{{ $iconBtn }}" aria-label="Tutup pemilih ikon"><x-content.icon name="x" /></button>
                  </div>
                  <div class="grid max-h-72 grid-cols-4 gap-1.5 overflow-y-auto sm:grid-cols-6 md:grid-cols-8" role="listbox" aria-label="Ikon merek">
                    @foreach (\App\Content\SocialIcons::ICONS as $slug => [$title, $path])
                      <button
                        type="button"
                        wire:click="setIcon({{ $i }}, '{{ $slug }}')"
                        x-show="q === '' || '{{ strtolower($title) }} {{ $slug }}'.includes(q.toLowerCase().trim())"
                        title="{{ $title }}"
                        aria-label="{{ $title }}"
                        role="option"
                        aria-selected="{{ $chosen === $slug ? 'true' : 'false' }}"
                        wire:key="si-{{ $i }}-{{ $slug }}"
                        class="flex h-16 flex-col items-center justify-center gap-1 rounded-lg border bg-white px-1 text-gray-700 transition hover:border-foresty hover:text-foresty focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty {{ $chosen === $slug ? 'border-foresty ring-1 ring-foresty' : 'border-gray-200' }}"
                      >
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true"><path d="{{ $path }}"/></svg>
                        <span class="w-full truncate text-[10px] leading-none">{{ $title }}</span>
                      </button>
                    @endforeach
                  </div>
                  <p class="mt-2 text-[11px] text-gray-400">Ikon: Simple Icons (CC0).</p>
                </div>
              @endif
            </div>
          @empty
            <p class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500" data-footer-empty="contacts">Belum ada kontak. Tambahkan telepon, alamat, email, atau akun media sosial.</p>
          @endforelse

          <div class="flex flex-wrap items-center gap-2" data-footer-add-contact>
            @foreach ($contactTypes as $value => [$text, $icon])
              <button type="button" wire:click="addContact('{{ $value }}')" @disabled($atMaxContacts) class="{{ $chip }}"><x-content.icon name="plus" class="h-3.5 w-3.5" /><x-content.icon :name="$icon" /> {{ $text }}</button>
            @endforeach
            <span class="ml-auto text-xs text-gray-400">{{ count($contacts) }} / {{ $maxContacts }}</span>
          </div>
        </section>

        {{-- Hak cipta dan slogan --}}
        <section x-show="sec === 'bottom'" class="{{ $panel }}" data-footer-bottom data-footer-panel="bottom" aria-label="Hak cipta dan slogan">
          <div class="flex flex-col gap-1.5" data-copyright>
            <x-content.lang-field path="f.copyright" :values="$f['copyright'] ?? []" :locales="$activeLocales" :default="$defaultLocale" label="Hak cipta" :max="\App\Content\SiteFooter::MAX_TEXT" placeholder="© {year} Nama yayasan" />
            @foreach ($activeLocales as $l)
              <button type="button" x-show="$store.editor.showLang('{{ $l }}')" x-on:click="insertYear('{{ $l }}')" class="self-start rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 transition hover:bg-emerald-50 hover:text-foresty focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty" title="{year} diganti tahun berjalan, otomatis tiap tahun" wire:key="year-{{ $l }}">
                <x-content.icon name="sparkle" class="mr-1 inline h-3.5 w-3.5" />Sisipkan tahun otomatis
              </button>
            @endforeach
          </div>
          <x-content.lang-field path="f.tagline" :values="$f['tagline'] ?? []" :locales="$activeLocales" :default="$defaultLocale" label="Slogan" :max="\App\Content\SiteFooter::MAX_TEXT" placeholder="Slogan singkat" />
        </section>
      </div>

      {{-- Pratinjau langsung --}}
      <aside class="flex flex-col gap-2 lg:sticky lg:top-24" aria-label="Pratinjau footer" data-footer-preview>
        <p class="flex items-center gap-1.5 text-sm font-semibold text-gray-700"><x-content.icon name="eye" /> Pratinjau <x-content.preview-lang :locales="$activeLocales" /></p>
        @foreach ($this->miniFooters as $l => $m)
          @continue($m === null)
          <div x-show="pv === '{{ $l }}'" class="overflow-hidden rounded-2xl text-[11px] leading-snug text-white shadow-md" style="background: {{ $pvBg }}" data-footer-mini="{{ $l }}" wire:key="mini-{{ $l }}">
            <div class="grid grid-cols-2 gap-x-4 gap-y-4 p-4">
              <div class="col-span-2 flex flex-col gap-1.5">
                <div class="flex items-center gap-2">
                  <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-[10px] font-black" style="background: {{ $pvGold }}; color: {{ $pvBg }}" aria-hidden="true">YS</span>
                  <b class="text-xs">Sinar Bhakti Husada</b>
                </div>
                @if ($m['description'] !== '')
                  <p class="line-clamp-4 opacity-85">{{ $m['description'] }}</p>
                @endif
              </div>
              @foreach ($m['columns'] as $col)
                <div class="min-w-0">
                  @if ($col['heading'] !== '')
                    <p class="mb-1 text-xs font-bold">{{ $col['heading'] }}</p>
                  @endif
                  <ul class="space-y-0.5 opacity-90">
                    @foreach ($col['links'] as $link)
                      <li class="truncate">{{ $link['label'] }}</li>
                    @endforeach
                  </ul>
                </div>
              @endforeach
              @if ($m['contacts'] !== [])
                <div class="col-span-2 min-w-0">
                  @if ($m['contact_heading'] !== '')
                    <p class="mb-1.5 text-xs font-bold">{{ $m['contact_heading'] }}</p>
                  @endif
                  <ul class="space-y-1.5">
                    @foreach ($m['contacts'] as $contact)
                      <li class="flex items-center gap-2">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full" style="background: {{ $pvGold }}; color: {{ $pvBg }}" aria-hidden="true">
                          @if ($contact['type'] === 'social' && $contact['icon'] !== '')
                            <svg viewBox="0 0 24 24" class="h-3 w-3" fill="currentColor"><path d="{{ \App\Content\SocialIcons::path($contact['icon']) }}"/></svg>
                          @elseif ($contact['type'] === 'social')
                            <span class="text-[8px] font-black">{{ $contact['badge'] }}</span>
                          @else
                            <x-content.icon :name="$pvIcons[$contact['type']] ?? 'pin'" class="h-3 w-3" />
                          @endif
                        </span>
                        <span class="min-w-0 truncate">{{ $contact['text'] }}</span>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif
            </div>
            <div style="border-top: 2px solid {{ $pvGold }}"></div>
            <div class="flex flex-wrap justify-between gap-x-3 gap-y-0.5 px-4 py-2.5 opacity-85">
              <span>{{ $m['copyright'] }}</span>
              <span>{{ $m['tagline'] }}</span>
            </div>
          </div>
        @endforeach
        <p class="text-xs text-gray-400">Tampilan ringkas. Tata letak asli ada di situs.</p>
      </aside>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <button type="submit" wire:loading.attr="disabled" wire:target="save" @disabled(! $ready) class="inline-flex items-center gap-1.5 rounded-xl bg-foresty px-5 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#043b2c] focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty focus-visible:ring-offset-2 disabled:opacity-50 md:hidden">
        <x-content.icon name="save" /> Simpan
      </button>
      @if ($stored)
        <button type="button" wire:click="restoreDefaults" wire:confirm="Kembalikan footer ke isi bawaan? Isi yang sudah Anda atur akan hilang." class="ml-auto inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty">
          <x-content.icon name="refresh" /> Kembali ke isi bawaan
        </button>
      @endif
    </div>
  </form>
</div>
