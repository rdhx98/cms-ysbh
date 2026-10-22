<?php

use App\Content\FooterSettings;
use App\Content\Languages;
use App\Content\MenuTarget;
use App\Content\Names;
use App\Content\PublicLookup;
use App\Content\SiteFooter;
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
    return '<div class="p-6 text-sm text-gray-400">Memuat isi footer…</div>';
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
      'badge' => '',
    ];
  }

  public function removeContact(int $i): void
  {
    if (!$this->guard() || !isset($this->f['contacts'][$i])) {
      return;
    }
    unset($this->f['contacts'][$i]);
    $this->f['contacts'] = array_values($this->f['contacts']);
  }

  public function moveContact(int $i, int $dir): void
  {
    if (!$this->guard()) {
      return;
    }
    $this->f['contacts'] = $this->swap($this->f['contacts'], $i, $dir);
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
  $input = 'w-full text-sm border-gray-300 rounded-md focus:ring-foresty';
  $label = 'block text-[11px] font-bold text-gray-500 mb-1';
  $card = 'bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col gap-4';
  $small = 'rounded border border-gray-200 px-2 py-1 text-[11px] font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-30';
  $types = ['phone' => 'Telepon', 'address' => 'Alamat', 'email' => 'Email', 'social' => 'Media sosial'];
@endphp

<div class="flex flex-col gap-5" data-footer-editor>
  <div>
    <h2 class="text-lg font-bold text-foresty">Isi Footer</h2>
    <p class="text-xs text-gray-500">
      Mengatur isi kaki halaman situs. Tata letak footer tidak berubah. Teks yang dikosongkan di satu bahasa memakai teks bahasa bawaan ({{ strtoupper($defaultLocale) }}).
      @if ($stored)
        <span class="font-semibold text-emerald-700">Isi dari CMS sedang dipakai.</span>
      @else
        <span class="font-semibold text-amber-700">Belum pernah disimpan: situs memakai isi bawaan. Simpan untuk mulai mengaturnya dari sini.</span>
      @endif
    </p>
  </div>

  @if (! $ready)
    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800" data-footer-not-ready>
      Tabel <b>site_settings</b> belum ada. Jalankan <code>php artisan migrate</code> di CMS, lalu muat ulang halaman ini.
    </div>
  @endif

  @if ($errorsList !== [])
    <div class="rounded-md border border-red-200 bg-red-50 p-3 text-xs text-red-700" data-footer-errors>
      <p class="mb-1 font-bold">Belum disimpan. Perbaiki hal berikut:</p>
      <ul class="list-disc pl-5">
        @foreach ($errorsList as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form wire:submit="save" class="flex flex-col gap-5">
    {{-- Deskripsi --}}
    <div class="{{ $card }}">
      <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Deskripsi yayasan</span>
      @foreach ($activeLocales as $l)
        <div wire:key="desc-{{ $l }}">
          <label class="{{ $label }}">{{ $this->langName($l) }}{{ $l === $defaultLocale ? ' (bawaan)' : '' }}</label>
          <textarea wire:model="f.description.{{ $l }}" rows="3" maxlength="{{ \App\Content\SiteFooter::MAX_DESCRIPTION }}" class="{{ $input }}"></textarea>
        </div>
      @endforeach
    </div>

    {{-- Dua kolom tautan --}}
    @foreach ($f['columns'] ?? [] as $c => $col)
      <div class="{{ $card }}" wire:key="col-{{ $c }}" data-footer-column="{{ $c }}">
        <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Kolom tautan {{ $c + 1 }}</span>
        <div class="grid gap-3 md:grid-cols-2">
          @foreach ($activeLocales as $l)
            <div wire:key="head-{{ $c }}-{{ $l }}">
              <label class="{{ $label }}">Judul kolom ({{ strtoupper($l) }})</label>
              <input type="text" wire:model="f.columns.{{ $c }}.heading.{{ $l }}" maxlength="{{ \App\Content\SiteFooter::MAX_TEXT }}" class="{{ $input }}">
            </div>
          @endforeach
        </div>

        <div class="flex flex-col gap-3">
          @foreach ($col['links'] as $i => $link)
            @php
              $pv = $this->previews["$c:$i"] ?? null;
            @endphp
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3" wire:key="link-{{ $c }}-{{ $i }}" data-footer-link>
              <div class="grid gap-3 md:grid-cols-2">
                @foreach ($activeLocales as $l)
                  <div wire:key="ll-{{ $c }}-{{ $i }}-{{ $l }}">
                    <label class="{{ $label }}">Label ({{ strtoupper($l) }}){{ $l === $defaultLocale ? ' *' : '' }}</label>
                    <input type="text" wire:model="f.columns.{{ $c }}.links.{{ $i }}.label.{{ $l }}" maxlength="{{ \App\Content\SiteFooter::MAX_LABEL }}" class="{{ $input }}">
                  </div>
                @endforeach
              </div>

              <div class="mt-3">
                <label class="{{ $label }}">Tujuan (satu untuk semua bahasa)</label>
                <input type="text" wire:model.live.debounce.500ms="f.columns.{{ $c }}.links.{{ $i }}.url" placeholder="/about-us, https://…, mailto:…, tel:…, #anchor" class="{{ $input }} font-mono">
                <div class="mt-2 flex flex-wrap items-center gap-2">
                  <button type="button" wire:click="startPick({{ $c }}, {{ $i }})" class="{{ $small }}">Pilih halaman</button>
                  <label class="flex items-center gap-1 text-[11px] text-gray-600">
                    <input type="checkbox" wire:model="f.columns.{{ $c }}.links.{{ $i }}.new_tab" class="rounded text-foresty focus:ring-foresty"> Buka di tab baru
                  </label>
                  <span class="ml-auto flex gap-1">
                    <button type="button" wire:click="moveLink({{ $c }}, {{ $i }}, -1)" @disabled($i === 0) class="{{ $small }}" aria-label="Naikkan">↑</button>
                    <button type="button" wire:click="moveLink({{ $c }}, {{ $i }}, 1)" @disabled($i === count($col['links']) - 1) class="{{ $small }}" aria-label="Turunkan">↓</button>
                    <button type="button" wire:click="removeLink({{ $c }}, {{ $i }})" wire:confirm="Hapus tautan ini?" class="{{ $small }} !text-red-600">Hapus</button>
                  </span>
                </div>

                @if ($pickFor === $c . ':' . $i)
                  <div class="mt-2 rounded-md border border-gray-200 bg-white p-2" data-footer-picker>
                    <div class="mb-2 flex flex-wrap gap-2">
                      <button type="button" wire:click="pickPreset('home')" class="{{ $small }}">Beranda</button>
                      <button type="button" wire:click="pickPreset('articles')" class="{{ $small }}">Daftar artikel</button>
                      <button type="button" wire:click="cancelPick" class="{{ $small }} ml-auto">Tutup</button>
                    </div>
                    <input type="search" wire:model.live.debounce.300ms="pageQuery" placeholder="Cari judul halaman (min. 2 huruf)…" class="{{ $input }} bg-gray-50" autocomplete="off">
                    @if (count($this->pageResults) > 0)
                      <ul class="mt-1 divide-y divide-gray-100 rounded-md border border-gray-200 bg-white text-sm">
                        @foreach ($this->pageResults as $r)
                          <li wire:key="fp-{{ $c }}-{{ $i }}-{{ $r['id'] }}">
                            <button type="button" wire:click="pickPage({{ $r['id'] }})" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50">
                              <span>{{ $r['label'] }}</span>
                              <span class="text-[10px] text-gray-400">{{ $r['hint'] }}</span>
                            </button>
                          </li>
                        @endforeach
                      </ul>
                    @endif
                  </div>
                @endif

                @if ($pv !== null && isset($pv['error']))
                  <p class="mt-2 text-xs text-red-600">{{ $pv['error'] }}</p>
                @elseif ($pv !== null)
                  @if (! empty($pv['warning']))
                    <p class="mt-2 text-xs text-amber-700">{{ $pv['warning'] }}</p>
                  @endif
                  @if (! empty($pv['addresses']))
                    <div class="mt-2 rounded-md bg-white p-2">
                      <p class="mb-1 text-[10px] font-bold uppercase text-gray-400">Yang dilihat pembaca</p>
                      @foreach ($pv['addresses'] as $l => $address)
                        <p class="break-all font-mono text-xs text-gray-600" wire:key="fpv-{{ $c }}-{{ $i }}-{{ $l }}"><b>{{ strtoupper($l) }}</b> {{ $address }}</p>
                      @endforeach
                    </div>
                  @endif
                @endif
              </div>
            </div>
          @endforeach
        </div>

        <div>
          <button type="button" wire:click="addLink({{ $c }})" @disabled(count($col['links']) >= \App\Content\SiteFooter::MAX_LINKS) class="{{ $small }}">+ Tambah tautan</button>
          <span class="ml-2 text-[11px] text-gray-400">{{ count($col['links']) }} / {{ \App\Content\SiteFooter::MAX_LINKS }}</span>
        </div>
      </div>
    @endforeach

    {{-- Kontak --}}
    <div class="{{ $card }}" data-footer-contacts>
      <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Kolom kontak</span>
      <div class="grid gap-3 md:grid-cols-2">
        @foreach ($activeLocales as $l)
          <div wire:key="ch-{{ $l }}">
            <label class="{{ $label }}">Judul kolom ({{ strtoupper($l) }})</label>
            <input type="text" wire:model="f.contact_heading.{{ $l }}" maxlength="{{ \App\Content\SiteFooter::MAX_TEXT }}" class="{{ $input }}">
          </div>
        @endforeach
      </div>

      @foreach ($f['contacts'] ?? [] as $i => $contact)
        @php
          $type = $contact['type'];
        @endphp
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3" wire:key="contact-{{ $i }}" data-footer-contact>
          <div class="grid gap-3 md:grid-cols-3">
            <div>
              <label class="{{ $label }}">Jenis</label>
              <select wire:model.live="f.contacts.{{ $i }}.type" class="{{ $input }}">
                @foreach ($types as $value => $text)
                  <option value="{{ $value }}">{{ $text }}</option>
                @endforeach
              </select>
            </div>
            @if (in_array($type, ['phone', 'email'], true))
              <div class="md:col-span-2">
                <label class="{{ $label }}">{{ $type === 'phone' ? 'Nomor telepon' : 'Alamat email' }} (sama di semua bahasa)</label>
                <input type="text" wire:model="f.contacts.{{ $i }}.text.{{ $defaultLocale }}" maxlength="{{ \App\Content\SiteFooter::MAX_TEXT }}" class="{{ $input }}" placeholder="{{ $type === 'phone' ? '+62 …' : 'nama@contoh.org' }}">
              </div>
            @else
              @foreach ($activeLocales as $l)
                <div wire:key="ct-{{ $i }}-{{ $l }}">
                  <label class="{{ $label }}">Teks ({{ strtoupper($l) }}){{ $l === $defaultLocale ? ' *' : '' }}</label>
                  <input type="text" wire:model="f.contacts.{{ $i }}.text.{{ $l }}" maxlength="{{ \App\Content\SiteFooter::MAX_TEXT }}" class="{{ $input }}">
                </div>
              @endforeach
            @endif
          </div>
          @if (in_array($type, ['address', 'social'], true))
            <div class="mt-3 grid gap-3 md:grid-cols-3">
              <div class="md:col-span-2">
                <label class="{{ $label }}">Tautan (opsional, harus https://)</label>
                <input type="text" wire:model="f.contacts.{{ $i }}.url" placeholder="{{ $type === 'address' ? 'https://maps.app.goo.gl/…' : 'https://www.instagram.com/…' }}" class="{{ $input }} font-mono">
              </div>
              @if ($type === 'social')
                <div>
                  <label class="{{ $label }}">Ikon teks (maks. {{ \App\Content\SiteFooter::MAX_BADGE }} huruf)</label>
                  <input type="text" wire:model="f.contacts.{{ $i }}.badge" maxlength="{{ \App\Content\SiteFooter::MAX_BADGE }}" placeholder="IG" class="{{ $input }}">
                </div>
              @endif
            </div>
          @endif
          <div class="mt-3 flex justify-end gap-1">
            <button type="button" wire:click="moveContact({{ $i }}, -1)" @disabled($i === 0) class="{{ $small }}" aria-label="Naikkan">↑</button>
            <button type="button" wire:click="moveContact({{ $i }}, 1)" @disabled($i === count($f['contacts']) - 1) class="{{ $small }}" aria-label="Turunkan">↓</button>
            <button type="button" wire:click="removeContact({{ $i }})" wire:confirm="Hapus baris kontak ini?" class="{{ $small }} !text-red-600">Hapus</button>
          </div>
        </div>
      @endforeach

      <div class="flex flex-wrap items-center gap-2">
        <button type="button" wire:click="addContact('phone')" @disabled(count($f['contacts'] ?? []) >= \App\Content\SiteFooter::MAX_CONTACTS) class="{{ $small }}">+ Telepon</button>
        <button type="button" wire:click="addContact('address')" @disabled(count($f['contacts'] ?? []) >= \App\Content\SiteFooter::MAX_CONTACTS) class="{{ $small }}">+ Alamat</button>
        <button type="button" wire:click="addContact('email')" @disabled(count($f['contacts'] ?? []) >= \App\Content\SiteFooter::MAX_CONTACTS) class="{{ $small }}">+ Email</button>
        <button type="button" wire:click="addContact('social')" @disabled(count($f['contacts'] ?? []) >= \App\Content\SiteFooter::MAX_CONTACTS) class="{{ $small }}">+ Media sosial</button>
        <span class="text-[11px] text-gray-400">{{ count($f['contacts'] ?? []) }} / {{ \App\Content\SiteFooter::MAX_CONTACTS }}</span>
      </div>
    </div>

    {{-- Hak cipta & slogan --}}
    <div class="{{ $card }}" data-footer-bottom>
      <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Baris paling bawah</span>
      @foreach ($activeLocales as $l)
        <div class="grid gap-3 md:grid-cols-2" wire:key="bottom-{{ $l }}">
          <div>
            <label class="{{ $label }}">Hak cipta ({{ strtoupper($l) }})</label>
            <input type="text" wire:model="f.copyright.{{ $l }}" maxlength="{{ \App\Content\SiteFooter::MAX_TEXT }}" class="{{ $input }}">
          </div>
          <div>
            <label class="{{ $label }}">Slogan ({{ strtoupper($l) }})</label>
            <input type="text" wire:model="f.tagline.{{ $l }}" maxlength="{{ \App\Content\SiteFooter::MAX_TEXT }}" class="{{ $input }}">
          </div>
        </div>
      @endforeach
      <p class="text-[11px] text-gray-400">Tulis <code class="rounded bg-gray-100 px-1">{year}</code> di hak cipta untuk tahun berjalan (otomatis berganti tiap tahun). Contoh: <i>© {year} Yayasan Sinar Bhakti Husada</i>.</p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <button type="submit" wire:loading.attr="disabled" @disabled(! $ready) class="rounded-md bg-foresty px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-[#043b2c] disabled:opacity-50">Simpan footer</button>
      @if ($stored)
        <button type="button" wire:click="restoreDefaults" wire:confirm="Kembalikan footer ke isi bawaan? Isi yang sudah Anda atur akan hilang." class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-200">Kembalikan ke isi bawaan</button>
      @endif
    </div>
  </form>
</div>
