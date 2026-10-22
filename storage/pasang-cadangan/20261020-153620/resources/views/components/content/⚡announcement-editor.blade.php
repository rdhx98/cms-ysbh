<?php

use App\Content\Announcement;
use App\Content\AnnouncementSettings;
use App\Content\Languages;
use App\Content\MenuTarget;
use App\Content\Names;
use App\Content\PublicLookup;
use App\Content\SiteFooter;
use App\Livewire\Traits\SearchesLinkTargets;
use App\Livewire\Traits\WithNotifications;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Editor pengumuman situs (rilis 85): banner (pita di atas halaman) dan popup (jendela di tengah layar), masing-masing dengan isi dua bahasa,
 * tombol tautan, jadwal tayang, lingkup halaman, dan seberapa sering tampil. Maksimal Announcement::MAX_ITEMS pengumuman; dari yang aktif,
 * yang paling atas menang (satu banner dan satu popup sekaligus).
 *
 *   <livewire:content.announcement-editor lazy wire:key="announcement-editor" />
 *
 * Gambar popup dipilih lewat File Manager (event openFileManager -> mediaSelected) dan disimpan sebagai ID media. Tautan memakai aturan menu
 * dan footer (MenuTarget). Hanya admin. Aturan isi di App\Content\Announcement (diuji di tests/announcement-test.php).
 * Tersimpan di site_settings (kunci "announcement"). Landing menampilkannya lewat <x-announcement />.
 */
new class extends Component {
  use WithNotifications;
  use SearchesLinkTargets; // searchLinkTargets('page', kata): pemilih halaman untuk tujuan tombol

  /** Bahasa bawaan dulu. */
  public array $activeLocales = [];

  #[Locked]
  public string $defaultLocale = 'en';

  /** Isi formulir (bentuk Announcement::forEditor). */
  public array $f = ['timezone' => 'Asia/Jayapura', 'items' => []];

  /** Galat dari penyimpanan terakhir (daftar kalimat). */
  public array $errorsList = [];

  /** Apakah pengumuman sudah pernah disimpan dari CMS. */
  public bool $stored = false;

  /** Tabel site_settings sudah ada? */
  public bool $ready = true;

  /** Indeks pengumuman yang sedang memilih halaman tujuan ('' = tidak ada). */
  public string $pickFor = '';

  public string $pageQuery = '';

  public function mount(): void
  {
    abort_unless($this->canManage(), 403);
    $cfg = Languages::fromConfig();
    $this->defaultLocale = $cfg['default'];
    $this->activeLocales = array_values(array_unique([$cfg['default'], ...$cfg['locales']]));
    $this->load();
  }

  public function placeholder(): string
  {
    return '<div class="p-6 text-sm text-gray-400">Memuat pengumuman…</div>';
  }

  private function canManage(): bool
  {
    $user = auth()->user();

    return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
  }

  public function langName(string $code): string
  {
    return ['en' => 'English', 'id' => 'Indonesia'][$code] ?? strtoupper($code);
  }

  private function guard(): bool
  {
    if (!$this->canManage()) {
      $this->notify('Hanya admin yang dapat mengubah pengumuman.', 'error');

      return false;
    }

    return true;
  }

  private function load(): void
  {
    $this->errorsList = [];
    $this->pickFor = '';
    $this->pageQuery = '';
    try {
      $this->ready = \Illuminate\Support\Facades\Schema::hasTable(AnnouncementSettings::TABLE);
      $data = $this->ready ? AnnouncementSettings::load() : Announcement::defaults();
      $this->stored = $this->ready && AnnouncementSettings::exists();
    } catch (\Throwable $e) {
      report($e);
      $this->ready = false;
      $this->stored = false;
      $data = Announcement::defaults();
    }
    $this->f = Announcement::forEditor($data, $this->activeLocales, $this->defaultLocale);
    $this->forgetComputed();
  }

  private function forgetComputed(): void
  {
    unset($this->imageUrls, $this->previews, $this->pageResults, $this->statuses);
  }

  /** @return array<int,string> id media => alamat (hanya yang masih ada) */
  #[Computed]
  public function imageUrls(): array
  {
    $ids = Announcement::mediaIds($this->f);
    if ($ids === []) {
      return [];
    }
    try {
      return \App\Content\Blocks\MediaUrls::fromModel($ids);
    } catch (\Throwable) {
      return [];
    }
  }

  /** Status tiap pengumuman SEKARANG: indeks => 'off' | 'scheduled' | 'expired' | 'live', dan apakah ia benar-benar yang tampil. */
  #[Computed]
  public function statuses(): array
  {
    $tz = Announcement::timezone($this->f['timezone'] ?? null);
    $now = time();
    $shown = ['banner' => null, 'popup' => null];
    $out = [];
    foreach ($this->f['items'] ?? [] as $i => $item) {
      $status = Announcement::status($item, $now, $tz);
      $wins = false;
      if ($status === 'live' && ($shown[$item['type']] ?? null) === null) {
        $shown[$item['type']] = $i;
        $wins = true;
      }
      $out[$i] = ['status' => $status, 'wins' => $wins];
    }

    return $out;
  }

  /** @return list<string> */
  #[Computed]
  public function indexSlugs(): array
  {
    return array_map(fn (string $l) => Languages::indexSlug(config('cms.articles_index_slug'), $l), $this->activeLocales);
  }

  private function articlesSlug(): string
  {
    return Languages::indexSlug(config('cms.articles_index_slug'), $this->defaultLocale);
  }

  /** Alamat tombol tiap pengumuman, per bahasa, atau galat / peringatan. Kunci = indeks pengumuman. */
  #[Computed]
  public function previews(): array
  {
    $out = [];
    foreach ($this->f['items'] ?? [] as $i => $item) {
      $url = trim((string) ($item['url'] ?? ''));
      if ($url === '') {
        continue;
      }
      $built = SiteFooter::target($url, $this->activeLocales, $this->defaultLocale, $this->indexSlugs, $this->articlesSlug());
      if ($built['error'] !== null) {
        $out[$i] = ['error' => $built['error']];
        continue;
      }
      $row = ['addresses' => []];
      try {
        foreach ($this->activeLocales as $l) {
          $row['addresses'][$l] = PublicLookup::localizeUrl($built['url'], $l);
        }
        $t = MenuTarget::parse($built['url'], null, $this->indexSlugs, $this->activeLocales, $this->defaultLocale);
        if ($t['kind'] === 'page' && $t['ref'] !== '' && PublicLookup::sibling('page', $t['ref'], $this->defaultLocale, $this->activeLocales, $this->defaultLocale) === null) {
          $row['warning'] = 'Halaman ini belum online atau tidak ditemukan: tombolnya akan 404.';
        }
      } catch (\Throwable) {
        $row['addresses'] = [];   // basis data belum siap: pratinjau dilewati, simpan tetap berfungsi
      }
      $out[$i] = $row;
    }

    return $out;
  }

  #[Computed]
  public function pageResults(): array
  {
    return $this->pickFor === '' || mb_strlen(trim($this->pageQuery)) < 2 ? [] : $this->searchLinkTargets('page', $this->pageQuery);
  }

  /** File Manager mengirim id media terpilih; hanya gambar yang ADA yang diterima (alamat kiriman klien tidak dipercaya). */
  #[On('mediaSelected')]
  public function handleMediaSelection(array $data): void
  {
    $target = (string) ($data['componentId'] ?? '');
    if (!preg_match('/^announcement_(ann_[a-f0-9]{8})$/D', $target, $m) || !$this->guard()) {
      return;
    }
    $index = $this->indexOfId($m[1]);
    if ($index === null) {
      return;
    }
    $id = (int) ($data['id'] ?? 0);
    $media = $id > 0 ? \App\Models\Media::query()->find($id) : null;
    if ($media === null || !$media->isImage()) {
      $this->notify('Pilih berkas gambar.', 'error');

      return;
    }
    $this->f['items'][$index]['image_media'] = (int) $media->id;
    $this->forgetComputed();
  }

  private function indexOfId(string $id): ?int
  {
    foreach ($this->f['items'] as $i => $item) {
      if (($item['id'] ?? null) === $id) {
        return $i;
      }
    }

    return null;
  }

  public function clearImage(int $i): void
  {
    if (!$this->guard() || !isset($this->f['items'][$i])) {
      return;
    }
    $this->f['items'][$i]['image_media'] = null;
    $this->forgetComputed();
  }

  public function addItem(string $type = 'banner'): void
  {
    if (!$this->guard()) {
      return;
    }
    if (count($this->f['items']) >= Announcement::MAX_ITEMS) {
      $this->notify('Maksimal ' . Announcement::MAX_ITEMS . ' pengumuman. Hapus yang sudah tidak dipakai.', 'error');

      return;
    }
    $item = Announcement::blank($this->activeLocales, $this->defaultLocale);
    $item['type'] = in_array($type, Announcement::TYPES, true) ? $type : 'banner';
    if ($item['type'] === 'popup') {
      $item['tone'] = 'sorotan';
      $item['frequency'] = 'once';
    }
    $this->f['items'][] = $item;
    $this->pickFor = '';
    $this->forgetComputed();
  }

  public function removeItem(int $i): void
  {
    if (!$this->guard() || !isset($this->f['items'][$i])) {
      return;
    }
    unset($this->f['items'][$i]);
    $this->f['items'] = array_values($this->f['items']);
    $this->pickFor = '';
    $this->forgetComputed();
  }

  /** Tukar posisi dengan tetangganya ($dir -1 ke atas, +1 ke bawah). Urutan menentukan siapa yang menang. */
  public function moveItem(int $i, int $dir): void
  {
    if (!$this->guard()) {
      return;
    }
    $j = $i + ($dir < 0 ? -1 : 1);
    if (!isset($this->f['items'][$i], $this->f['items'][$j])) {
      return;
    }
    [$this->f['items'][$i], $this->f['items'][$j]] = [$this->f['items'][$j], $this->f['items'][$i]];
    $this->f['items'] = array_values($this->f['items']);
    $this->pickFor = '';
    $this->forgetComputed();
  }

  public function startPick(int $i): void
  {
    $this->pickFor = isset($this->f['items'][$i]) ? (string) $i : '';
    $this->pageQuery = '';
    unset($this->pageResults);
  }

  public function cancelPick(): void
  {
    $this->pickFor = '';
    $this->pageQuery = '';
    unset($this->pageResults);
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
    $i = $this->pickFor === '' ? -1 : (int) $this->pickFor;
    if (isset($this->f['items'][$i])) {
      $this->f['items'][$i]['url'] = $url;
    }
    $this->cancelPick();
    $this->forgetComputed();
  }

  public function updated(string $name): void
  {
    // pratinjau alamat ikut berubah saat tujuan diketik; status ikut berubah saat jadwal / centang diubah
    $this->forgetComputed();
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

    $result = Announcement::sanitize($this->f, $this->activeLocales, $this->defaultLocale, $this->indexSlugs, $this->articlesSlug());
    // Gambar harus masih ada di File Manager (bisa terhapus sejak dipilih)
    foreach ($result['data']['items'] as $n => $item) {
      $id = $item['image_media'];
      if ($id !== null && !\App\Models\Media::query()->whereKey($id)->exists()) {
        $result['errors'][] = 'Pengumuman ' . ($n + 1) . ': gambar tidak ditemukan di File Manager, pilih ulang atau hapus.';
      }
    }
    $this->errorsList = $result['errors'];
    if ($result['errors'] !== []) {
      $this->notify('Pengumuman belum disimpan: periksa ' . count($result['errors']) . ' isian yang bermasalah.', 'error');

      return;
    }

    try {
      AnnouncementSettings::save($result['data'], auth()->id());
    } catch (\Throwable $e) {
      report($e);
      $this->notify('Pengumuman gagal disimpan. Coba lagi.', 'error');

      return;
    }
    $this->f = Announcement::forEditor($result['data'], $this->activeLocales, $this->defaultLocale);
    $this->stored = true;
    $this->forgetComputed();
    $this->audit('Pengumuman situs diperbarui');
    $this->notify('Pengumuman disimpan. Perubahan langsung berlaku di situs.', 'success');
  }

  /** Hapus semua pengumuman tersimpan. */
  public function clearAll(): void
  {
    if (!$this->guard() || !$this->ready) {
      return;
    }
    try {
      AnnouncementSettings::reset();
    } catch (\Throwable $e) {
      report($e);
      $this->notify('Gagal menghapus pengumuman.', 'error');

      return;
    }
    $this->audit('Semua pengumuman situs dihapus');
    $this->load();
    $this->notify('Semua pengumuman dihapus.', 'success');
  }

  private function audit(string $text): void
  {
    try {
      if (function_exists('activity')) {
        activity('announcement_updates')->causedBy(auth()->user())->withProperties(['key' => Announcement::KEY])->log($text);
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
  $badges = [
    'live' => ['Tayang sekarang', 'bg-emerald-100 text-emerald-800'],
    'scheduled' => ['Terjadwal', 'bg-sky-100 text-sky-800'],
    'expired' => ['Sudah berakhir', 'bg-gray-200 text-gray-600'],
    'off' => ['Nonaktif', 'bg-gray-100 text-gray-500'],
  ];
  $toneNames = ['info' => 'Hijau (info)', 'penting' => 'Merah (penting)', 'sorotan' => 'Emas (sorotan)'];
  $toneClasses = \App\Content\Announcement::TONE_CLASSES;
  $frequencyNames = [
    'always' => 'Selalu (setiap membuka halaman)',
    'session' => 'Sekali per kunjungan (sampai peramban ditutup)',
    'day' => 'Sekali sehari',
    'once' => 'Sekali saja per pengunjung',
  ];
  $count = count($f['items']);
@endphp

<div class="flex flex-col gap-5" data-announcement-editor>
  <div>
    <h2 class="text-lg font-bold text-foresty">Pengumuman</h2>
    <p class="text-xs text-gray-500">
      <b>Banner</b> = pita di atas semua halaman. <b>Popup</b> = jendela di tengah layar (bisa memuat gambar). Setiap pengumuman punya jadwal dan bisa dibatasi ke beranda.
      Bila beberapa aktif bersamaan, yang <b>paling atas</b> dalam daftar menang: pengunjung melihat paling banyak satu banner dan satu popup.
      @if ($stored)
        <span class="font-semibold text-emerald-700">Pengumuman dari CMS sedang dipakai.</span>
      @else
        <span class="font-semibold text-amber-700">Belum ada yang disimpan: situs tidak menampilkan pengumuman.</span>
      @endif
    </p>
  </div>

  @if (! $ready)
    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800" data-announcement-not-ready>
      Tabel <b>site_settings</b> belum ada. Jalankan <code>php artisan migrate</code> di CMS, lalu muat ulang halaman ini.
    </div>
  @endif

  @if ($errorsList !== [])
    <div class="rounded-md border border-red-200 bg-red-50 p-3 text-xs text-red-700" data-announcement-errors>
      <p class="mb-1 font-bold">Belum disimpan. Perbaiki hal berikut:</p>
      <ul class="list-disc pl-5">
        @foreach ($errorsList as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form wire:submit="save" class="flex flex-col gap-5">
    <div class="{{ $card }}" data-announcement-timezone>
      <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Zona waktu jadwal</span>
      <div class="max-w-xs">
        <select wire:model.live="f.timezone" class="{{ $input }}">
          @foreach (\App\Content\Announcement::TIMEZONES as $zone => $zoneLabel)
            <option value="{{ $zone }}">{{ $zoneLabel }}</option>
          @endforeach
        </select>
      </div>
      <p class="text-[11px] text-gray-400">Jam mulai dan selesai di bawah dibaca dalam zona ini, apa pun zona server.</p>
    </div>

    @forelse ($f['items'] as $i => $item)
      @php
        $st = $this->statuses[$i] ?? ['status' => 'off', 'wins' => false];
        $badge = $badges[$st['status']];
        $pv = $this->previews[$i] ?? null;
        $isPopup = ($item['type'] ?? 'banner') === 'popup';
        $imgId = $item['image_media'] ?? null;
        $imgUrl = $imgId ? ($this->imageUrls[$imgId] ?? '') : '';
        $max = \App\Content\Announcement::maxMessage($item['type'] ?? 'banner');
      @endphp
      <div class="{{ $card }}" wire:key="ann-{{ $item['id'] }}" data-announcement-item="{{ $item['type'] }}">
        <div class="flex flex-wrap items-center gap-2">
          <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Pengumuman {{ $i + 1 }} · {{ $isPopup ? 'Popup' : 'Banner' }}</span>
          <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badge[1] }}" data-announcement-status="{{ $st['status'] }}">{{ $badge[0] }}</span>
          @if ($st['status'] === 'live' && ! $st['wins'])
            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800" data-announcement-shadowed>Tertutup oleh {{ $isPopup ? 'popup' : 'banner' }} di atasnya</span>
          @endif
          <span class="ml-auto flex gap-1">
            <button type="button" wire:click="moveItem({{ $i }}, -1)" @disabled($i === 0) class="{{ $small }}" aria-label="Naikkan">↑</button>
            <button type="button" wire:click="moveItem({{ $i }}, 1)" @disabled($i === $count - 1) class="{{ $small }}" aria-label="Turunkan">↓</button>
            <button type="button" wire:click="removeItem({{ $i }})" wire:confirm="Hapus pengumuman ini?" class="{{ $small }} !text-red-600">Hapus</button>
          </span>
        </div>

        <div class="flex flex-wrap items-center gap-4">
          <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
            <input type="checkbox" wire:model.live="f.items.{{ $i }}.enabled" class="rounded border-gray-300"> Aktif
          </label>
          <div>
            <label class="{{ $label }}">Jenis</label>
            <select wire:model.live="f.items.{{ $i }}.type" class="{{ $input }}">
              <option value="banner">Banner (pita di atas halaman)</option>
              <option value="popup">Popup (jendela di tengah layar)</option>
            </select>
          </div>
          <div>
            <label class="{{ $label }}">Warna</label>
            <select wire:model.live="f.items.{{ $i }}.tone" class="{{ $input }}">
              @foreach ($toneNames as $tone => $toneName)
                <option value="{{ $tone }}">{{ $toneName }}</option>
              @endforeach
            </select>
          </div>
          <span class="{{ $toneClasses[$item['tone']] ?? $toneClasses['info'] }} rounded px-3 py-1 text-xs font-semibold" aria-hidden="true">Contoh warna</span>
        </div>

        @foreach ($activeLocales as $l)
          <div class="grid gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3 md:grid-cols-2" wire:key="ann-{{ $item['id'] }}-{{ $l }}">
            @if ($isPopup)
              <div class="md:col-span-2">
                <label class="{{ $label }}">Judul ({{ strtoupper($l) }})</label>
                <input type="text" wire:model="f.items.{{ $i }}.title.{{ $l }}" maxlength="{{ \App\Content\Announcement::MAX_TITLE }}" class="{{ $input }}">
              </div>
            @endif
            <div class="md:col-span-2">
              <label class="{{ $label }}">Isi ({{ strtoupper($l) }}){{ $l === $defaultLocale ? ' *' : '' }} <span class="font-normal text-gray-400">maks. {{ $max }} karakter, teks biasa</span></label>
              @if ($isPopup)
                <textarea wire:model="f.items.{{ $i }}.message.{{ $l }}" rows="4" maxlength="{{ $max }}" class="{{ $input }}"></textarea>
              @else
                <input type="text" wire:model="f.items.{{ $i }}.message.{{ $l }}" maxlength="{{ $max }}" class="{{ $input }}">
              @endif
            </div>
            <div class="md:col-span-2 md:max-w-xs">
              <label class="{{ $label }}">Teks tombol ({{ strtoupper($l) }}){{ $l === $defaultLocale ? ' *bila ada tautan' : '' }}</label>
              <input type="text" wire:model="f.items.{{ $i }}.button.{{ $l }}" maxlength="{{ \App\Content\Announcement::MAX_BUTTON }}" class="{{ $input }}">
            </div>
          </div>
        @endforeach
        <p class="-mt-2 text-[11px] text-gray-400">Teks yang dikosongkan di satu bahasa memakai bahasa bawaan ({{ strtoupper($defaultLocale) }}).</p>

        {{-- Tujuan tombol --}}
        <div>
          <label class="{{ $label }}">Tujuan tombol (satu untuk semua bahasa; kosong = tanpa tombol)</label>
          <input type="text" wire:model.live.debounce.500ms="f.items.{{ $i }}.url" placeholder="/program-kami, https://…, mailto:…, tel:…" class="{{ $input }} font-mono">
          <div class="mt-2 flex flex-wrap items-center gap-2">
            <button type="button" wire:click="startPick({{ $i }})" class="{{ $small }}">Pilih halaman</button>
            <label class="flex items-center gap-1 text-[11px] text-gray-600">
              <input type="checkbox" wire:model="f.items.{{ $i }}.new_tab" class="rounded text-foresty focus:ring-foresty"> Buka di tab baru
            </label>
          </div>
          @if ($pickFor === (string) $i)
            <div class="mt-2 rounded-md border border-gray-200 bg-white p-2" data-announcement-picker>
              <div class="mb-2 flex flex-wrap gap-2">
                <button type="button" wire:click="pickPreset('home')" class="{{ $small }}">Beranda</button>
                <button type="button" wire:click="pickPreset('articles')" class="{{ $small }}">Daftar artikel</button>
                <button type="button" wire:click="cancelPick" class="{{ $small }} ml-auto">Tutup</button>
              </div>
              <input type="search" wire:model.live.debounce.300ms="pageQuery" placeholder="Cari judul halaman (min. 2 huruf)…" class="{{ $input }} bg-gray-50" autocomplete="off">
              @if (count($this->pageResults) > 0)
                <ul class="mt-1 divide-y divide-gray-100 rounded-md border border-gray-200 bg-white text-sm">
                  @foreach ($this->pageResults as $r)
                    <li wire:key="ap-{{ $i }}-{{ $r['id'] }}">
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
              <div class="mt-2 rounded-md bg-gray-50 p-2">
                <p class="mb-1 text-[10px] font-bold uppercase text-gray-400">Yang dilihat pembaca</p>
                @foreach ($pv['addresses'] as $l => $address)
                  <p class="break-all font-mono text-xs text-gray-600" wire:key="apv-{{ $i }}-{{ $l }}"><b>{{ strtoupper($l) }}</b> {{ $address }}</p>
                @endforeach
              </div>
            @endif
          @endif
        </div>

        {{-- Gambar (popup) --}}
        @if ($isPopup)
          <div class="flex flex-col gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3" data-announcement-image>
            <span class="text-[11px] font-bold text-gray-600">Gambar popup (opsional)</span>
            <div class="flex h-24 items-center justify-center overflow-hidden rounded-md border border-dashed border-gray-300 bg-white">
              @if ($imgUrl !== '')
                <img src="{{ $imgUrl }}" alt="" class="max-h-full max-w-full object-contain">
              @elseif ($imgId)
                <span class="px-2 text-center text-[11px] text-red-600">Berkas tidak ditemukan. Pilih ulang atau hapus.</span>
              @else
                <span class="text-[11px] text-gray-400">Belum dipilih</span>
              @endif
            </div>
            <div class="flex gap-2">
              <button type="button" x-on:click="$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: 'announcement_{{ $item['id'] }}', allowedFileType: 'image' })" class="{{ $small }}">Pilih gambar</button>
              @if ($imgId)
                <button type="button" wire:click="clearImage({{ $i }})" class="{{ $small }}">Hapus</button>
              @endif
            </div>
            <p class="text-[11px] text-gray-400">Tampil di atas teks, tinggi maksimal 224 piksel (dipotong rapi). Gambar dianggap hiasan: informasi penting harus ada di teks.</p>
          </div>
        @endif

        {{-- Jadwal dan aturan tampil --}}
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
          <div>
            <label class="{{ $label }}">Mulai tayang <span class="font-normal text-gray-400">(kosong = langsung)</span></label>
            <input type="datetime-local" wire:model.live="f.items.{{ $i }}.starts_at" class="{{ $input }}">
          </div>
          <div>
            <label class="{{ $label }}">Selesai tayang <span class="font-normal text-gray-400">(kosong = tanpa batas)</span></label>
            <input type="datetime-local" wire:model.live="f.items.{{ $i }}.ends_at" class="{{ $input }}">
          </div>
          <div>
            <label class="{{ $label }}">Tampil di</label>
            <select wire:model="f.items.{{ $i }}.scope" class="{{ $input }}">
              <option value="all">Semua halaman</option>
              <option value="home">Beranda saja</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="{{ $label }}">Seberapa sering</label>
            <select wire:model="f.items.{{ $i }}.frequency" class="{{ $input }}">
              @foreach ($frequencyNames as $value => $name)
                <option value="{{ $value }}">{{ $name }}</option>
              @endforeach
            </select>
          </div>
          @if ($isPopup)
            <div>
              <label class="{{ $label }}">Jeda sebelum muncul (detik)</label>
              <input type="number" min="0" max="{{ \App\Content\Announcement::MAX_DELAY }}" wire:model="f.items.{{ $i }}.delay" class="{{ $input }}">
            </div>
          @endif
        </div>
        <p class="text-[11px] text-gray-400">
          @if ($isPopup)
            Popup dihitung saat <b>ditampilkan</b>. Pengunjung yang pernah menutupnya melihatnya lagi bila Anda mengubah teks, tautan, gambar, atau warnanya.
          @else
            Banner dihitung saat <b>ditutup</b> (pada pilihan "Selalu", banner tidak punya tombol tutup). Pengunjung yang pernah menutupnya melihatnya lagi bila Anda mengubah teks, tautan, atau warnanya.
          @endif
        </p>
      </div>
    @empty
      <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500" data-announcement-empty>
        Belum ada pengumuman. Tambahkan banner atau popup di bawah.
      </div>
    @endforelse

    <div class="flex flex-wrap items-center gap-3">
      <button type="button" wire:click="addItem('banner')" @disabled($count >= \App\Content\Announcement::MAX_ITEMS) class="{{ $small }} !px-3 !py-2">+ Tambah banner</button>
      <button type="button" wire:click="addItem('popup')" @disabled($count >= \App\Content\Announcement::MAX_ITEMS) class="{{ $small }} !px-3 !py-2">+ Tambah popup</button>
      <span class="text-[11px] text-gray-400">{{ $count }} dari {{ \App\Content\Announcement::MAX_ITEMS }}</span>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <button type="submit" wire:loading.attr="disabled" @disabled(! $ready) class="rounded-md bg-foresty px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-[#043b2c] disabled:opacity-50">Simpan pengumuman</button>
      @if ($stored)
        <button type="button" wire:click="clearAll" wire:confirm="Hapus SEMUA pengumuman? Situs tidak lagi menampilkan banner atau popup." class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-200">Hapus semua</button>
      @endif
    </div>
  </form>
</div>
