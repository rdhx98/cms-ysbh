<?php

use App\Content\Announcement;
use App\Content\AnnouncementSettings;
use App\Content\CategoryLookup;
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
 * dan footer (MenuTarget); sejak rilis 88 tujuannya dipilih lewat chip (<x-content.link-target>), bukan diketik. Admin dan editor. Aturan isi di App\Content\Announcement (diuji di tests/announcement-test.php).
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

  /** Pengumuman yang kartunya dibuka saat dirender pertama kali (id). Hanya hiasan: membuka / menutup selanjutnya dilakukan di peramban. */
  public string $openId = '';

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
    return '<div class="flex flex-col gap-3 p-2" aria-busy="true"><div class="h-14 animate-pulse rounded-2xl bg-gray-100"></div><div class="h-20 animate-pulse rounded-2xl bg-gray-100"></div><div class="h-20 animate-pulse rounded-2xl bg-gray-100"></div></div>';
  }

  private function canManage(): bool
  {
    $user = auth()->user();

    return $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'editor']);
  }

  public function langName(string $code): string
  {
    return ['en' => 'English', 'id' => 'Indonesia'][$code] ?? strtoupper($code);
  }

  private function guard(): bool
  {
    if (!$this->canManage()) {
      $this->notify('Hanya admin dan editor yang dapat mengubah pengumuman.', 'error');

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
    $this->f = $this->withDest(Announcement::forEditor($data, $this->activeLocales, $this->defaultLocale));
    $this->openId = count($this->f['items']) === 1 ? (string) $this->f['items'][0]['id'] : '';
    $this->forgetComputed();
  }

  private function forgetComputed(): void
  {
    unset($this->imageUrls, $this->previews, $this->pageResults, $this->statuses, $this->gaps, $this->summaries, $this->categoryOptions);
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

  /** Kalimat jadwal tiap pengumuman (Announcement::summary), kunci = indeks. */
  #[Computed]
  public function summaries(): array
  {
    $tz = Announcement::timezone($this->f['timezone'] ?? null);
    $now = time();
    $out = [];
    foreach ($this->f['items'] ?? [] as $i => $item) {
      $out[$i] = Announcement::summary($item, $now, $tz);
    }

    return $out;
  }

  /**
   * Berapa teks yang masih kosong di tiap bahasa NON-bawaan padahal bahasa bawaannya terisi (pembaca melihat teks bahasa bawaan).
   * Judul dihitung untuk popup, teks tombol hanya bila ada tujuan tombol.
   *
   * @return array<string,int>
   */
  #[Computed]
  public function gaps(): array
  {
    $out = array_fill_keys($this->activeLocales, 0);
    $d = $this->defaultLocale;
    foreach ($this->f['items'] ?? [] as $item) {
      $maps = [$item['message'] ?? []];
      if (($item['type'] ?? '') === 'popup') {
        $maps[] = $item['title'] ?? [];
      }
      if (trim((string) ($item['url'] ?? '')) !== '') {
        $maps[] = $item['button'] ?? [];
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
      $kind = (string) ($item['dest_kind'] ?? 'none');
      $ref = trim((string) ($item['dest_ref'] ?? ''));
      if ($url === '' && $kind !== 'none' && $ref !== '') {
        $typed = MenuTarget::build($kind, $ref, $this->articlesSlug());
        $out[$i] = ['error' => $typed['error'] ?? 'Tujuan ini tidak sah.'];   // diketik tetapi ditolak: tampilkan alasannya, bukan diam
        continue;
      }
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

  /**
   * Jalan pintas jadwal: tayang langsung dan berakhir $days hari lagi pukul 23.59 (zona jadwal); 0 = tanpa batas.
   * Hanya mengisi "selesai" dan mengosongkan "mulai"; tanggal tetap bisa diubah tangan sesudahnya.
   */
  public function setDuration(int $i, int $days): void
  {
    if (!$this->guard() || !isset($this->f['items'][$i]) || $days < 0 || $days > 365) {
      return;
    }
    $this->f['items'][$i]['starts_at'] = '';
    if ($days === 0) {
      $this->f['items'][$i]['ends_at'] = '';
    } else {
      $tz = new \DateTimeZone(Announcement::timezone($this->f['timezone'] ?? null));
      $this->f['items'][$i]['ends_at'] = (new \DateTimeImmutable('now', $tz))->modify("+$days days")->format('Y-m-d') . 'T23:59';
    }
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
    $item += ['dest_kind' => 'none', 'dest_ref' => ''];
    $item['type'] = in_array($type, Announcement::TYPES, true) ? $type : 'banner';
    if ($item['type'] === 'popup') {
      $item['tone'] = 'sorotan';
      $item['frequency'] = 'once';
    }
    $this->f['items'][] = $item;
    $this->openId = (string) $item['id'];
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

  /** Jenis tujuan yang bisa dipilih di <x-content.link-target>. */
  private const DEST_KINDS = ['none', 'page', 'home', 'articles', 'category', 'url', 'mailto', 'tel', 'anchor'];

  /**
   * Isian khusus tampilan per pengumuman (dest_kind, dest_ref): jenis tujuan tombol dan nilainya, diturunkan dari `url` yang tersimpan.
   * Tidak ikut tersimpan (sanitize hanya membaca isian yang dikenal); `url` tetap satu-satunya data tujuan.
   */
  private function withDest(array $form): array
  {
    foreach ($form['items'] ?? [] as $i => $item) {
      $form['items'][$i] = $item + $this->destOf((string) ($item['url'] ?? ''));
    }

    return $form;
  }

  /** @return array{dest_kind:string,dest_ref:string} */
  private function destOf(string $url): array
  {
    $url = trim($url);
    if ($url === '') {
      return ['dest_kind' => 'none', 'dest_ref' => ''];
    }
    $t = MenuTarget::parse($url, null, $this->indexSlugs, $this->activeLocales, $this->defaultLocale);

    return ['dest_kind' => $t['kind'] ?? 'url', 'dest_ref' => $t['kind'] === null ? $url : $t['ref']];
  }

  /** Bentuk ulang `url` pengumuman $i dari jenis dan nilai pilihannya. Tidak sah / belum dipilih = '' (tanpa tombol); galat tampil di pratinjau. */
  private function syncDest(int $i): void
  {
    if (!isset($this->f['items'][$i])) {
      return;
    }
    $kind = (string) ($this->f['items'][$i]['dest_kind'] ?? 'none');
    $ref = trim((string) ($this->f['items'][$i]['dest_ref'] ?? ''));
    $url = '';
    if ($kind !== 'none' && in_array($kind, self::DEST_KINDS, true)) {
      $built = MenuTarget::build($kind, $ref, $this->articlesSlug());
      if ($built['error'] === null && $kind === 'category' && !array_key_exists($ref, $this->categoryOptions)) {
        $built = ['url' => null, 'error' => 'Kategori itu tidak ditemukan. Pilih dari daftar.'];   // nilai dari browser tidak dipercaya
      }
      $url = $built['error'] === null ? (string) $built['url'] : '';
    }
    $this->f['items'][$i]['url'] = $url;
  }

  /** Pilih jenis tujuan tombol pengumuman $i. Berganti jenis mengosongkan nilai lama. */
  public function setDestKind(int $i, string $kind): void
  {
    if (!$this->guard() || !isset($this->f['items'][$i]) || !in_array($kind, self::DEST_KINDS, true)) {
      return;
    }
    if (($this->f['items'][$i]['dest_kind'] ?? 'none') !== $kind) {
      $this->f['items'][$i]['dest_kind'] = $kind;
      $this->f['items'][$i]['dest_ref'] = '';
    }
    $this->syncDest($i);
    $this->pickFor = $kind === 'page' ? (string) $i : '';
    $this->pageQuery = '';
    $this->forgetComputed();
  }

  /** Kategori yang bisa dituju: [slug => nama]. Kategori tanpa slug dilewati. */
  #[Computed]
  public function categoryOptions(): array
  {
    $out = [];
    try {
      foreach (\App\Models\Category::query()->orderBy('id')->limit(500)->get() as $category) {
        $row = ['id' => $category->getKey(), 'name' => $category->getRawOriginal('name'), 'slug' => $category->getRawOriginal('slug')];
        $slugs = CategoryLookup::slugs($row, $this->activeLocales);
        $slug = $slugs[$this->defaultLocale] ?? (array_values($slugs)[0] ?? null);
        if ($slug !== null) {
          $out[$slug] = CategoryLookup::name($row, $this->defaultLocale, $this->activeLocales) ?: $slug;
        }
      }
    } catch (\Throwable) {
      return [];   // tabel categories belum siap: pilihan kosong, bagian lain tetap berfungsi
    }
    asort($out, SORT_NATURAL | SORT_FLAG_CASE);

    return $out;
  }

  public function startPick(int $i): void
  {
    $same = $this->pickFor === (string) $i;
    $this->pickFor = isset($this->f['items'][$i]) ? (string) $i : '';
    if (!$same) {
      $this->pageQuery = '';   // fokus berulang di kotak cari yang sama tidak menghapus ketikan
    }
    unset($this->pageResults);
  }

  public function cancelPick(): void
  {
    $this->pickFor = '';
    $this->pageQuery = '';
    unset($this->pageResults);
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
    $this->applyPick($slug);
  }

  private function applyPick(string $slug): void
  {
    $i = $this->pickFor === '' ? -1 : (int) $this->pickFor;
    if (isset($this->f['items'][$i])) {
      $this->f['items'][$i]['dest_kind'] = 'page';
      $this->f['items'][$i]['dest_ref'] = $slug;
      $this->syncDest($i);
    }
    $this->cancelPick();
    $this->forgetComputed();
  }

  public function updated(string $name): void
  {
    // tujuan tombol diketik / dipilih: bentuk ulang url; pratinjau alamat dan status ikut berubah saat jadwal / centang diubah
    if (preg_match('/^f\.items\.(\d+)\.dest_ref$/D', $name, $m)) {
      $this->syncDest((int) $m[1]);
    }
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
    $this->f = $this->withDest(Announcement::forEditor($result['data'], $this->activeLocales, $this->defaultLocale));
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
  $field = 'w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm placeholder:text-gray-400 focus:border-foresty focus:ring-foresty';
  $iconBtn = 'inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty disabled:pointer-events-none disabled:opacity-30';
  $chip = 'inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-foresty hover:text-foresty focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty disabled:pointer-events-none disabled:opacity-40';
  $chipOn = 'border-foresty bg-emerald-50 text-foresty';
  $heading = 'flex items-center gap-2 text-sm font-semibold text-gray-800';
  $seg = 'block rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-600 transition hover:text-gray-900 peer-checked:bg-white peer-checked:text-foresty peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-foresty';
  $statusLook = [
    'live' => ['Tayang', 'bg-emerald-50 text-emerald-800', 'bg-emerald-500'],
    'scheduled' => ['Terjadwal', 'bg-sky-50 text-sky-800', 'bg-sky-500'],
    'expired' => ['Berakhir', 'bg-gray-100 text-gray-600', 'bg-gray-400'],
    'off' => ['Nonaktif', 'bg-gray-100 text-gray-500', 'bg-gray-300'],
  ];
  $toneNames = ['info' => 'Info', 'penting' => 'Penting', 'sorotan' => 'Sorotan'];
  $frequencyNames = ['always' => 'Selalu', 'session' => 'Per kunjungan', 'day' => 'Per hari', 'once' => 'Sekali'];
  $frequencyCaption = [
    'always' => 'Muncul di setiap halaman yang dibuka. Banner tidak punya tombol tutup.',
    'session' => 'Sekali per kunjungan. Muncul lagi setelah peramban ditutup.',
    'day' => 'Sekali sehari untuk tiap pengunjung.',
    'once' => 'Sekali saja. Muncul lagi hanya bila Anda mengubah isinya.',
  ];
  $max = \App\Content\Announcement::MAX_ITEMS;
  $count = count($f['items']);
  $pal = \App\Content\PublicLookup::palette();   // palet tersimpan, sama dengan yang dilihat pembaca
  $toneBg = fn (string $tone): string => $pal[\App\Content\Announcement::TONE_SLOTS[$tone] ?? 'forest']['hex'];
  $toneFg = fn (string $tone): string => \App\Content\Palette::on($pal[\App\Content\Announcement::TONE_SLOTS[$tone] ?? 'forest']);
  $pick = fn (array $map, string $l): string => \App\Content\SiteFooter::pick($map, $l, $defaultLocale, $activeLocales);
  $live = ['banner' => false, 'popup' => false];
  foreach ($f['items'] as $n => $it) {
    if (($this->statuses[$n]['wins'] ?? false) === true) {
      $live[$it['type']] = true;
    }
  }
  $liveNames = array_keys(array_filter($live));
  $langNames = [];
  foreach ($activeLocales as $l) {
    $langNames[$l] = $this->langName($l);
  }
@endphp

<div class="relative flex flex-col gap-4" data-announcement-editor x-effect="if ($store.editor.lang !== 'both') pv = $store.editor.lang" x-data="{ pv: @js(in_array(app()->getLocale(), $activeLocales, true) ? app()->getLocale() : $defaultLocale) }">
  @if (! $ready)
    <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" data-announcement-not-ready>
      <x-content.icon name="alert" class="mt-0.5 h-4 w-4" />
      <span>Tabel <b>site_settings</b> belum ada. Jalankan <code class="rounded bg-amber-100 px-1">php artisan migrate</code>, lalu muat ulang halaman.</span>
    </div>
  @endif

  @if ($errorsList !== [])
    <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" data-announcement-errors role="alert">
      <p class="mb-1 flex items-center gap-1.5 font-semibold"><x-content.icon name="alert" class="h-4 w-4" /> Belum tersimpan. Perbaiki:</p>
      <ul class="list-disc pl-9 text-red-700">
        @foreach ($errorsList as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form wire:submit="save" class="flex flex-col gap-4">
    {{-- Bilah atas: ringkasan tayang, zona waktu, bahasa, simpan --}}
    <div class="z-20 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur md:sticky md:top-0" data-announcement-bar>
      <div class="mr-auto min-w-0">
        <h2 class="text-base font-bold leading-tight text-foresty">Pengumuman</h2>
        <p class="flex items-center gap-1.5 text-xs text-gray-500" data-announcement-live="{{ implode(',', $liveNames) }}">
          <span class="h-2 w-2 rounded-full {{ $liveNames === [] ? 'bg-gray-300' : 'bg-emerald-500' }}" aria-hidden="true"></span>
          @if ($liveNames === [])
            Tidak ada yang tayang sekarang
          @else
            Tayang sekarang: {{ implode(' dan ', $liveNames) }}
          @endif
        </p>
      </div>

      <label class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 pl-3 text-xs font-semibold text-gray-600" data-announcement-timezone title="Jam jadwal dibaca dalam zona ini">
        <x-content.icon name="globe" class="h-3.5 w-3.5" />
        <span class="sr-only">Zona waktu jadwal</span>
        <select wire:model.live="f.timezone" class="border-0 bg-transparent py-1 pl-0 pr-8 text-xs font-semibold text-gray-700 focus:ring-0">
          @foreach (\App\Content\Announcement::TIMEZONES as $zone => $zoneLabel)
            <option value="{{ $zone }}">{{ $zoneLabel }}</option>
          @endforeach
        </select>
      </label>

      <div data-announcement-langs>
        <x-content.lang-switch :locales="$activeLocales" :gaps="$count > 0 ? $this->gaps : []" :names="$langNames" :default="$defaultLocale" />
      </div>

      <button type="submit" wire:loading.attr="disabled" wire:target="save" @disabled(! $ready) class="inline-flex items-center gap-1.5 rounded-xl bg-foresty px-5 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#043b2c] focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty focus-visible:ring-offset-2 disabled:opacity-50">
        <x-content.icon name="save" />
        <span wire:loading.remove wire:target="save">Simpan</span>
        <span wire:loading wire:target="save">Menyimpan…</span>
      </button>
    </div>

    @forelse ($f['items'] as $i => $item)
      @php
        $st = $this->statuses[$i] ?? ['status' => 'off', 'wins' => false];
        [$stText, $stLook, $stDot] = $statusLook[$st['status']];
        $pv = $this->previews[$i] ?? null;
        $isPopup = ($item['type'] ?? 'banner') === 'popup';
        $imgId = $item['image_media'] ?? null;
        $imgUrl = $imgId ? ($this->imageUrls[$imgId] ?? '') : '';
        $maxMessage = \App\Content\Announcement::maxMessage($item['type'] ?? 'banner');
        $bg = $toneBg($item['tone']);
        $fg = $toneFg($item['tone']);
        $snippet = trim($pick($isPopup && trim((string) ($item['title'][$defaultLocale] ?? '')) !== '' ? $item['title'] : $item['message'], $defaultLocale));
        $hasUrl = trim((string) ($item['url'] ?? '')) !== '';
        $noLimit = ($item['starts_at'] ?? '') === '' && ($item['ends_at'] ?? '') === '';
      @endphp
      <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" wire:key="ann-{{ $item['id'] }}" data-announcement-item="{{ $item['type'] }}" x-data="{ open: {{ $item['id'] === $openId ? 'true' : 'false' }} }">
        {{-- Kepala kartu: ringkas dan bisa dilipat --}}
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 px-3 py-2.5">
          <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" class="flex min-w-0 flex-1 basis-56 items-center gap-3 rounded-lg p-1 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl" style="background: {{ $bg }}; color: {{ $fg }}" aria-hidden="true"><x-content.icon :name="$isPopup ? 'popup' : 'banner'" class="h-5 w-5" /></span>
            <span class="min-w-0">
              <span class="block text-sm font-semibold text-gray-900">{{ $isPopup ? 'Popup' : 'Banner' }} {{ $i + 1 }}</span>
              <span class="block truncate text-xs {{ $snippet === '' ? 'italic text-gray-400' : 'text-gray-500' }}">{{ $snippet === '' ? 'Belum ada teks' : mb_strimwidth($snippet, 0, 70, '…') }}</span>
            </span>
            <x-content.icon name="chevron-down" class="ml-auto h-4 w-4 text-gray-400 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
          </button>

          <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $stLook }}" data-announcement-status="{{ $st['status'] }}"><span class="h-1.5 w-1.5 rounded-full {{ $stDot }}" aria-hidden="true"></span>{{ $stText }}</span>
          @if ($st['status'] === 'live' && ! $st['wins'])
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800" title="Ada {{ $isPopup ? 'popup' : 'banner' }} lain yang tayang di atasnya, jadi yang ini tidak terlihat" data-announcement-shadowed><x-content.icon name="alert" class="h-3 w-3" /> Tertutup</span>
          @endif

          <label class="relative inline-flex shrink-0 cursor-pointer items-center" title="{{ ($item['enabled'] ?? false) ? 'Aktif' : 'Nonaktif' }}">
            <input type="checkbox" role="switch" wire:model.live="f.items.{{ $i }}.enabled" class="peer sr-only" aria-label="Aktifkan {{ $isPopup ? 'popup' : 'banner' }} {{ $i + 1 }}">
            <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-foresty peer-focus-visible:ring-2 peer-focus-visible:ring-foresty peer-focus-visible:ring-offset-2"></span>
            <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
          </label>

          <span class="flex items-center gap-0.5">
            <button type="button" wire:click="moveItem({{ $i }}, -1)" @disabled($i === 0) class="{{ $iconBtn }}" aria-label="Naikkan" title="Naikkan: yang paling atas diutamakan"><x-content.icon name="up" /></button>
            <button type="button" wire:click="moveItem({{ $i }}, 1)" @disabled($i === $count - 1) class="{{ $iconBtn }}" aria-label="Turunkan"><x-content.icon name="down" /></button>
            <button type="button" wire:click="removeItem({{ $i }})" wire:confirm="Hapus pengumuman ini?" class="{{ $iconBtn }} hover:!bg-red-50 hover:!text-red-600" aria-label="Hapus pengumuman"><x-content.icon name="trash" /></button>
          </span>
        </div>

        {{-- Isi kartu --}}
        <div x-show="open" class="grid gap-6 border-t border-gray-100 p-4 lg:grid-cols-[minmax(0,1fr)_19rem]" data-announcement-body>
          <div class="flex min-w-0 flex-col gap-6">
            {{-- Jenis --}}
            <fieldset class="flex flex-col gap-2" data-announcement-type>
              <legend class="{{ $heading }}"><x-content.icon name="bell" class="text-gray-400" /> Jenis</legend>
              <div class="grid grid-cols-2 gap-2">
                @foreach (['banner' => ['Banner', 'Pita di atas halaman'], 'popup' => ['Popup', 'Jendela di tengah layar']] as $value => [$name, $caption])
                  <label class="relative cursor-pointer" wire:key="type-{{ $item['id'] }}-{{ $value }}">
                    <input type="radio" name="type-{{ $item['id'] }}" value="{{ $value }}" wire:model.live="f.items.{{ $i }}.type" class="peer sr-only">
                    <span class="flex flex-col gap-2 rounded-xl border border-gray-200 bg-white p-2.5 transition hover:border-gray-300 peer-checked:border-foresty peer-checked:ring-2 peer-checked:ring-foresty/25 peer-focus-visible:ring-2 peer-focus-visible:ring-foresty">
                      @if ($value === 'banner')
                        <span class="block h-12 overflow-hidden rounded-md border border-gray-200 bg-white" aria-hidden="true">
                          <span class="block h-3 w-full" style="background: {{ $bg }}"></span>
                          <span class="mx-2 mt-2 block h-1.5 rounded bg-gray-200"></span>
                          <span class="mx-2 mt-1 block h-1.5 w-2/3 rounded bg-gray-200"></span>
                        </span>
                      @else
                        <span class="flex h-12 items-center justify-center overflow-hidden rounded-md border border-gray-200 bg-gray-300" aria-hidden="true">
                          <span class="block h-8 w-14 overflow-hidden rounded bg-white shadow">
                            <span class="block h-1" style="background: {{ $bg }}"></span>
                            <span class="mx-1.5 mt-1.5 block h-1 rounded bg-gray-300"></span>
                            <span class="mx-1.5 mt-1 block h-1 w-2/3 rounded bg-gray-200"></span>
                          </span>
                        </span>
                      @endif
                      <span class="text-sm font-semibold text-gray-800">{{ $name }}</span>
                      <span class="-mt-1.5 text-xs text-gray-500">{{ $caption }}</span>
                    </span>
                  </label>
                @endforeach
              </div>
            </fieldset>

            {{-- Warna --}}
            <fieldset class="flex flex-col gap-2" data-announcement-tone>
              <legend class="{{ $heading }}"><x-content.icon name="sparkle" class="text-gray-400" /> Warna</legend>
              <div class="flex flex-wrap gap-2">
                @foreach ($toneNames as $tone => $toneName)
                  <label class="relative cursor-pointer" wire:key="tone-{{ $item['id'] }}-{{ $tone }}">
                    <input type="radio" name="tone-{{ $item['id'] }}" value="{{ $tone }}" wire:model.live="f.items.{{ $i }}.tone" class="peer sr-only">
                    <span class="flex items-center gap-2 rounded-full border border-gray-200 bg-white py-1.5 pl-1.5 pr-3.5 text-sm font-semibold text-gray-700 transition hover:border-gray-300 peer-checked:border-foresty peer-checked:ring-2 peer-checked:ring-foresty/25 peer-focus-visible:ring-2 peer-focus-visible:ring-foresty">
                      <span class="grid h-6 w-6 place-items-center rounded-full" style="background: {{ $toneBg($tone) }}; color: {{ $toneFg($tone) }}" aria-hidden="true">@if ($item['tone'] === $tone)<x-content.icon name="check" class="h-3.5 w-3.5" />@endif</span>
                      {{ $toneName }}
                    </span>
                  </label>
                @endforeach
              </div>
            </fieldset>

            {{-- Isi --}}
            <div class="flex flex-col gap-2" data-announcement-text>
              <p class="{{ $heading }}"><x-content.icon name="type" class="text-gray-400" /> Isi</p>
              @if ($isPopup)
                <x-content.lang-field :path="'f.items.' . $i . '.title'" :values="$item['title']" :locales="$activeLocales" :default="$defaultLocale" label="Judul" :max="\App\Content\Announcement::MAX_TITLE" placeholder="Judul singkat" class="!font-semibold" />
              @endif
              @if ($isPopup)
                <x-content.lang-field :path="'f.items.' . $i . '.message'" :values="$item['message']" :locales="$activeLocales" :default="$defaultLocale" label="Pesan" :max="$maxMessage" :rows="4" :counter="true" placeholder="Tulis pesan untuk pengunjung" />
              @else
                <x-content.lang-field :path="'f.items.' . $i . '.message'" :values="$item['message']" :locales="$activeLocales" :default="$defaultLocale" label="Pesan" :max="$maxMessage" placeholder="Satu kalimat, mis. Pendaftaran poli gigi dibuka 1 November" />
              @endif
            </div>

            {{-- Tombol --}}
            <div class="flex flex-col gap-2" data-announcement-button>
              <p class="{{ $heading }}"><x-content.icon name="link" class="text-gray-400" /> Tombol</p>
              <x-content.lang-field :path="'f.items.' . $i . '.button'" :values="$item['button']" :locales="$activeLocales" :default="$defaultLocale" label="Teks tombol" :max="\App\Content\Announcement::MAX_BUTTON" placeholder="Teks tombol, mis. Selengkapnya" />
              <x-content.link-target :index="$i" :kind="$item['dest_kind'] ?? 'none'" :ref="$item['dest_ref'] ?? ''" :picking="$pickFor === (string) $i" :page-results="$pickFor === (string) $i ? $this->pageResults : []" :categories="$this->categoryOptions" />
              <label class="relative inline-flex cursor-pointer items-center gap-2 self-start text-sm text-gray-700" title="Buka di tab baru">
                <input type="checkbox" wire:model.live="f.items.{{ $i }}.new_tab" class="peer sr-only" aria-label="Buka di tab baru">
                <span class="{{ $iconBtn }} !h-9 !w-9 peer-checked:bg-emerald-50 peer-checked:text-foresty peer-focus-visible:ring-2 peer-focus-visible:ring-foresty"><x-content.icon name="external" /></span>
                Buka di tab baru
              </label>

              @if ($pv !== null && isset($pv['error']))
                <p class="flex items-start gap-1.5 px-1 text-xs text-red-600"><x-content.icon name="alert" class="mt-px h-3.5 w-3.5" /> {{ $pv['error'] }}</p>
              @elseif ($pv !== null)
                @if (! empty($pv['warning']))
                  <p class="flex items-start gap-1.5 px-1 text-xs text-amber-700"><x-content.icon name="alert" class="mt-px h-3.5 w-3.5" /> {{ $pv['warning'] }}</p>
                @endif
                @foreach ($pv['addresses'] ?? [] as $l => $address)
                  <p x-show="$store.editor.showLang('{{ $l }}')" class="flex items-center gap-1.5 px-1 text-xs text-gray-500" title="Alamat yang dibuka pembaca" wire:key="apv-{{ $i }}-{{ $l }}"><x-content.icon name="external" class="h-3.5 w-3.5" /> <span class="break-all font-mono">{{ $address }}</span></p>
                @endforeach
              @endif
            </div>

            {{-- Gambar (popup) --}}
            @if ($isPopup)
              <div class="flex flex-col gap-2" data-announcement-image>
                <p class="{{ $heading }}"><x-content.icon name="image" class="text-gray-400" /> Gambar</p>
                <div class="flex items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-3">
                  <div class="grid h-20 w-28 shrink-0 place-items-center overflow-hidden rounded-lg border border-gray-200 bg-white">
                    @if ($imgUrl !== '')
                      <img src="{{ $imgUrl }}" alt="" class="max-h-full max-w-full object-contain">
                    @elseif ($imgId)
                      <span class="px-2 text-center text-xs text-red-600">Berkas hilang</span>
                    @else
                      <x-content.icon name="image" class="h-7 w-7 text-gray-300" />
                    @endif
                  </div>
                  <div class="flex min-w-0 flex-col items-start gap-1.5">
                    <button type="button" x-on:click="$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: 'announcement_{{ $item['id'] }}', allowedFileType: 'image' })" class="{{ $chip }}"><x-content.icon name="image" /> {{ $imgId ? 'Ganti gambar' : 'Pilih gambar' }}</button>
                    @if ($imgId)
                      <button type="button" wire:click="clearImage({{ $i }})" class="inline-flex items-center gap-1 rounded px-1 text-xs font-semibold text-gray-500 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty"><x-content.icon name="trash" class="h-3 w-3" /> Hapus gambar</button>
                    @else
                      <span class="text-xs text-gray-400">Opsional. Tampil di atas teks.</span>
                    @endif
                  </div>
                </div>
              </div>
            @endif

            {{-- Jadwal --}}
            <div class="flex flex-col gap-2" data-announcement-schedule>
              <p class="{{ $heading }}"><x-content.icon name="calendar" class="text-gray-400" /> Jadwal</p>
              <div class="flex flex-wrap gap-1.5">
                @foreach ([0 => 'Tanpa batas', 7 => '7 hari', 14 => '14 hari', 30 => '30 hari'] as $days => $text)
                  <button type="button" wire:click="setDuration({{ $i }}, {{ $days }})" class="{{ $chip }} {{ $days === 0 && $noLimit ? $chipOn : '' }}" wire:key="dur-{{ $item['id'] }}-{{ $days }}">{{ $text }}</button>
                @endforeach
              </div>
              <div class="grid gap-2 sm:grid-cols-2">
                <label class="flex flex-col gap-1 text-xs font-medium text-gray-600">Mulai
                  <input type="datetime-local" wire:model.live="f.items.{{ $i }}.starts_at" class="{{ $field }}">
                </label>
                <label class="flex flex-col gap-1 text-xs font-medium text-gray-600">Selesai
                  <input type="datetime-local" wire:model.live="f.items.{{ $i }}.ends_at" class="{{ $field }}">
                </label>
              </div>
              <p class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700" data-announcement-summary><x-content.icon name="clock" class="text-gray-400" /> {{ $this->summaries[$i] ?? '' }}</p>
            </div>

            {{-- Tampil di mana dan seberapa sering --}}
            <div class="flex flex-col gap-3" data-announcement-rules>
              <p class="{{ $heading }}"><x-content.icon name="eye" class="text-gray-400" /> Tampilan</p>
              <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <div class="inline-flex rounded-xl bg-gray-100 p-1" role="radiogroup" aria-label="Tampil di">
                  @foreach (['all' => ['Semua halaman', 'globe'], 'home' => ['Beranda saja', 'home']] as $value => [$text, $icon])
                    <label class="relative cursor-pointer" wire:key="scope-{{ $item['id'] }}-{{ $value }}">
                      <input type="radio" name="scope-{{ $item['id'] }}" value="{{ $value }}" wire:model.live="f.items.{{ $i }}.scope" class="peer sr-only">
                      <span class="{{ $seg }} flex items-center gap-1.5"><x-content.icon :name="$icon" class="h-3.5 w-3.5" />{{ $text }}</span>
                    </label>
                  @endforeach
                </div>
                @if ($isPopup)
                  <label class="flex items-center gap-2 whitespace-nowrap text-sm text-gray-700">Muncul setelah
                    <input type="number" min="0" max="{{ \App\Content\Announcement::MAX_DELAY }}" wire:model.live.debounce.400ms="f.items.{{ $i }}.delay" class="{{ $field }} w-20 text-center" aria-label="Jeda sebelum muncul, dalam detik">
                    detik
                  </label>
                @endif
              </div>
              <div class="flex flex-col gap-1.5">
                <div class="inline-flex flex-wrap self-start rounded-xl bg-gray-100 p-1" role="radiogroup" aria-label="Seberapa sering">
                  @foreach ($frequencyNames as $value => $text)
                    <label class="relative cursor-pointer" wire:key="freq-{{ $item['id'] }}-{{ $value }}">
                      <input type="radio" name="freq-{{ $item['id'] }}" value="{{ $value }}" wire:model.live="f.items.{{ $i }}.frequency" class="peer sr-only">
                      <span class="{{ $seg }}">{{ $text }}</span>
                    </label>
                  @endforeach
                </div>
                <p class="text-xs text-gray-500" data-announcement-frequency="{{ $item['frequency'] }}">{{ $frequencyCaption[$item['frequency']] ?? '' }}</p>
              </div>
            </div>
          </div>

          {{-- Pratinjau langsung --}}
          <aside class="flex min-w-0 flex-col gap-2 lg:sticky lg:top-24 lg:self-start" aria-label="Pratinjau pengumuman" data-announcement-preview>
            <p class="flex items-center gap-1.5 text-sm font-semibold text-gray-700"><x-content.icon name="eye" /> Pratinjau <x-content.preview-lang :locales="$activeLocales" /></p>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
              <div class="flex items-center gap-1.5 border-b border-gray-200 bg-gray-100 px-3 py-1.5" aria-hidden="true">
                <span class="h-2 w-2 rounded-full bg-gray-300"></span><span class="h-2 w-2 rounded-full bg-gray-300"></span><span class="h-2 w-2 rounded-full bg-gray-300"></span>
                <span class="ml-2 flex-1 truncate rounded bg-white px-2 py-0.5 text-[10px] text-gray-400">ysbh.org</span>
              </div>
              <div class="relative h-64 overflow-hidden bg-white">
                @unless ($isPopup)
                  <div class="flex items-center gap-2 px-3 py-2 text-[11px] leading-snug" style="background: {{ $bg }}; color: {{ $fg }}" data-announcement-mock="banner">
                    <span class="min-w-0 flex-1 font-medium">
                      @foreach ($activeLocales as $l)
                        @php $m = $pick($item['message'], $l); @endphp
                        <span x-show="pv === '{{ $l }}'" class="line-clamp-2">{{ $m !== '' ? $m : 'Pesan pengumuman tampil di sini' }}</span>
                      @endforeach
                    </span>
                    @if ($hasUrl)
                      <span class="shrink-0 rounded border border-current px-1.5 py-0.5 text-[10px] font-semibold">
                        @foreach ($activeLocales as $l)
                          <span x-show="pv === '{{ $l }}'">{{ $pick($item['button'], $l) ?: '…' }}</span>
                        @endforeach
                      </span>
                    @endif
                    @if (($item['frequency'] ?? '') !== 'always')
                      <x-content.icon name="x" class="h-3 w-3 shrink-0 opacity-80" />
                    @endif
                  </div>
                @endunless
                <div class="space-y-2 p-3" aria-hidden="true">
                  <div class="h-3 w-1/3 rounded bg-gray-200"></div>
                  <div class="h-20 rounded-lg bg-gray-100"></div>
                  <div class="h-2 w-5/6 rounded bg-gray-100"></div>
                  <div class="h-2 w-2/3 rounded bg-gray-100"></div>
                  <div class="h-2 w-3/4 rounded bg-gray-100"></div>
                </div>
                @if ($isPopup)
                  <div class="absolute inset-0 flex items-center justify-center bg-black/55 p-3" data-announcement-mock="popup">
                    <div class="w-full max-w-[15rem] overflow-hidden rounded-xl bg-white text-gray-800 shadow-xl">
                      <div class="h-1.5" style="background: {{ $bg }}"></div>
                      @if ($imgUrl !== '')
                        <img src="{{ $imgUrl }}" alt="" class="h-20 w-full object-cover">
                      @endif
                      <div class="flex flex-col gap-1.5 p-3">
                        @foreach ($activeLocales as $l)
                          @php $ti = $pick($item['title'], $l); $me = $pick($item['message'], $l); @endphp
                          <div x-show="pv === '{{ $l }}'" class="flex flex-col gap-1.5">
                            @if ($ti !== '')
                              <p class="text-sm font-bold leading-tight text-foresty">{{ $ti }}</p>
                            @endif
                            <p class="line-clamp-4 whitespace-pre-line text-[11px] leading-snug text-gray-600">{{ $me !== '' ? $me : 'Pesan pengumuman tampil di sini' }}</p>
                            @if ($hasUrl)
                              <span class="mt-1 inline-flex self-start rounded-md px-2.5 py-1 text-[10px] font-semibold" style="background: {{ $bg }}; color: {{ $fg }}">{{ $pick($item['button'], $l) ?: '…' }}</span>
                            @endif
                          </div>
                        @endforeach
                      </div>
                    </div>
                  </div>
                @endif
              </div>
            </div>
            <p class="text-xs text-gray-400">Contoh tampilan. Ukuran asli menyesuaikan layar pengunjung.</p>
          </aside>
        </div>
      </article>
    @empty
      <div class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center" data-announcement-empty>
        <span class="grid h-12 w-12 place-items-center rounded-full bg-emerald-50 text-foresty" aria-hidden="true"><x-content.icon name="bell" class="h-6 w-6" /></span>
        <div>
          <p class="text-base font-semibold text-gray-800">Belum ada pengumuman</p>
          <p class="text-sm text-gray-500">Kabarkan program atau jadwal baru langsung di atas situs.</p>
        </div>
        <div class="flex flex-wrap justify-center gap-2">
          <button type="button" wire:click="addItem('banner')" class="inline-flex items-center gap-1.5 rounded-xl bg-foresty px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-[#043b2c] focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty focus-visible:ring-offset-2"><x-content.icon name="plus" /> Buat banner</button>
          <button type="button" wire:click="addItem('popup')" class="{{ $chip }} !rounded-xl !px-4 !py-2"><x-content.icon name="plus" /> Buat popup</button>
        </div>
      </div>
    @endforelse

    @if ($count > 0)
      <div class="flex flex-wrap items-center gap-2" data-announcement-add>
        <button type="button" wire:click="addItem('banner')" @disabled($count >= $max) class="{{ $chip }}"><x-content.icon name="plus" class="h-3.5 w-3.5" /><x-content.icon name="banner" /> Buat banner</button>
        <button type="button" wire:click="addItem('popup')" @disabled($count >= $max) class="{{ $chip }}"><x-content.icon name="plus" class="h-3.5 w-3.5" /><x-content.icon name="popup" /> Buat popup</button>
        <span class="text-xs text-gray-400">{{ $count }} / {{ $max }}</span>
      </div>
    @endif

    <details class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 open:shadow-sm" data-announcement-help>
      <summary class="flex cursor-pointer items-center gap-2 font-semibold text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty"><x-content.icon name="info" class="text-gray-400" /> Cara kerja</summary>
      <ul class="mt-2 list-disc space-y-1 pl-9">
        <li>Pengunjung melihat paling banyak satu banner dan satu popup.</li>
        <li>Bila beberapa tayang bersamaan, yang paling atas dalam daftar diutamakan.</li>
        <li>Yang sudah ditutup pengunjung muncul lagi bila isinya Anda ubah. Jadwal dan lingkup tidak memunculkannya ulang.</li>
      </ul>
    </details>

    <div class="flex flex-wrap items-center gap-3">
      <button type="submit" wire:loading.attr="disabled" wire:target="save" @disabled(! $ready) class="inline-flex items-center gap-1.5 rounded-xl bg-foresty px-5 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#043b2c] focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty focus-visible:ring-offset-2 disabled:opacity-50 md:hidden">
        <x-content.icon name="save" /> Simpan
      </button>
      @if ($stored)
        <button type="button" wire:click="clearAll" wire:confirm="Hapus SEMUA pengumuman? Situs tidak lagi menampilkan banner atau popup." class="ml-auto inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-gray-500 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty">
          <x-content.icon name="trash" /> Hapus semua
        </button>
      @endif
    </div>
  </form>
</div>
