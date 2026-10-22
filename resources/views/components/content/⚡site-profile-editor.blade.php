<?php

use App\Content\Languages;
use App\Content\ProfileSettings;
use App\Content\SiteProfile;
use App\Livewire\Traits\WithNotifications;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Editor profil situs & SEO (rilis 83): nama situs, slogan, deskripsi bawaan, logo, favicon, gambar berbagi (Open Graph), akun X,
 * larangan pengindeksan (noindex), dan JSON-LD Organization. KONTAK dan SOSIAL ada di editor footer (<livewire:content.footer-editor />),
 * tidak diulang di sini; JSON-LD memakainya.
 *
 *   <livewire:content.site-profile-editor lazy wire:key="site-profile-editor" />
 *
 * Gambar dipilih lewat File Manager (event openFileManager -> mediaSelected) dan disimpan sebagai ID media, bukan alamat.
 * Hanya admin. Aturan isi di App\Content\SiteProfile (diuji di tests/site-profile-test.php). Tersimpan di site_settings (kunci "profile").
 */
new class extends Component {
  use WithNotifications;

  /** Bahasa bawaan dulu. */
  public array $activeLocales = [];

  #[Locked]
  public string $defaultLocale = 'en';

  /** Isi formulir (bentuk SiteProfile::forEditor). */
  public array $f = [];

  /** Galat dari penyimpanan terakhir (daftar kalimat). */
  public array $errorsList = [];

  /** Apakah profil sudah pernah disimpan dari CMS. */
  public bool $stored = false;

  /** Tabel site_settings sudah ada? */
  public bool $ready = true;

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
    return '<div class="p-6 text-sm text-gray-400">Memuat profil situs…</div>';
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
      $this->notify('Hanya admin yang dapat mengubah profil situs.', 'error');

      return false;
    }

    return true;
  }

  private function load(): void
  {
    $this->errorsList = [];
    try {
      $this->ready = \Illuminate\Support\Facades\Schema::hasTable(ProfileSettings::TABLE);
      $data = $this->ready ? ProfileSettings::load() : SiteProfile::defaults();
      $this->stored = $this->ready && ProfileSettings::exists();
    } catch (\Throwable $e) {
      report($e);
      $this->ready = false;
      $this->stored = false;
      $data = SiteProfile::defaults();
    }
    $this->f = SiteProfile::forEditor($data, $this->activeLocales, $this->defaultLocale);
    unset($this->imageUrls);
  }

  /** Penanda tujuan File Manager -> kolom profil. */
  private function slots(): array
  {
    return ['profile_logo' => 'logo_media', 'profile_favicon' => 'favicon_media', 'profile_og' => 'og_media'];
  }

  /** @return array<int,string> id media => alamat (hanya yang masih ada) */
  #[Computed]
  public function imageUrls(): array
  {
    $ids = SiteProfile::mediaIds($this->f);
    if ($ids === []) {
      return [];
    }
    try {
      return \App\Content\Blocks\MediaUrls::fromModel($ids);
    } catch (\Throwable) {
      return [];
    }
  }

  /** Alasan noindex untuk spanduk: 'environment' | 'setting' | null. */
  #[Computed]
  public function noindexReason(): ?string
  {
    return SiteProfile::noindexReason(['noindex' => !empty($this->f['noindex'])], app()->environment('production'));
  }

  /** File Manager mengirim id media terpilih; hanya gambar yang ADA yang diterima (alamat kiriman klien tidak dipercaya). */
  #[On('mediaSelected')]
  public function handleMediaSelection(array $data): void
  {
    $field = $this->slots()[(string) ($data['componentId'] ?? '')] ?? null;
    if ($field === null || !$this->guard()) {
      return;
    }
    $id = (int) ($data['id'] ?? 0);
    $media = $id > 0 ? \App\Models\Media::query()->find($id) : null;
    if ($media === null || !$media->isImage()) {
      $this->notify('Pilih berkas gambar.', 'error');

      return;
    }
    $this->f[$field] = (int) $media->id;
    unset($this->imageUrls);
  }

  public function clearImage(string $field): void
  {
    if (!$this->guard() || !in_array($field, $this->slots(), true)) {
      return;
    }
    $this->f[$field] = null;
    unset($this->imageUrls);
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

    $result = SiteProfile::sanitize($this->f, $this->activeLocales, $this->defaultLocale);
    // Gambar harus masih ada di File Manager (bisa terhapus sejak dipilih)
    foreach (['logo_media' => 'Logo', 'favicon_media' => 'Favicon', 'og_media' => 'Gambar berbagi'] as $field => $name) {
      $id = $result['data'][$field];
      if ($id !== null && !\App\Models\Media::query()->whereKey($id)->exists()) {
        $result['errors'][] = "$name tidak ditemukan di File Manager: pilih ulang atau hapus.";
      }
    }
    $this->errorsList = $result['errors'];
    if ($result['errors'] !== []) {
      $this->notify('Profil belum disimpan: periksa ' . count($result['errors']) . ' isian yang bermasalah.', 'error');

      return;
    }

    try {
      ProfileSettings::save($result['data'], auth()->id());
    } catch (\Throwable $e) {
      report($e);
      $this->notify('Profil gagal disimpan. Coba lagi.', 'error');

      return;
    }
    $this->f = SiteProfile::forEditor($result['data'], $this->activeLocales, $this->defaultLocale);
    $this->stored = true;
    unset($this->imageUrls, $this->noindexReason);
    $this->audit('Profil situs & SEO diperbarui');
    $this->notify('Profil disimpan. Perubahan langsung tampil di situs.', 'success');
  }

  /** Hapus isi tersimpan: situs kembali ke perilaku lama (tanpa profil). */
  public function restoreDefaults(): void
  {
    if (!$this->guard() || !$this->ready) {
      return;
    }
    try {
      ProfileSettings::reset();
    } catch (\Throwable $e) {
      report($e);
      $this->notify('Gagal mengosongkan profil.', 'error');

      return;
    }
    $this->audit('Profil situs & SEO dikosongkan');
    $this->load();
    unset($this->noindexReason);
    $this->notify('Profil dikosongkan: situs kembali ke pengaturan bawaan.', 'success');
  }

  private function audit(string $text): void
  {
    try {
      if (function_exists('activity')) {
        activity('profile_updates')->causedBy(auth()->user())->withProperties(['key' => SiteProfile::KEY])->log($text);
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
  $images = [
    'logo_media' => ['profile_logo', 'Logo', 'Dipakai di data terstruktur (JSON-LD). Gambar persegi atau mendatar dengan latar bersih.'],
    'favicon_media' => ['profile_favicon', 'Favicon', 'Ikon kecil di tab peramban. PNG persegi (minimal 48×48) paling aman. Kosong = ikon bawaan situs.'],
    'og_media' => ['profile_og', 'Gambar berbagi', 'Tampil saat tautan dibagikan (WhatsApp, Facebook, X). Disarankan 1200×630. Artikel dengan thumbnail memakai thumbnailnya sendiri.'],
  ];
@endphp

<div class="flex flex-col gap-5" data-profile-editor>
  <div>
    <h2 class="text-lg font-bold text-foresty">Profil &amp; SEO</h2>
    <p class="text-xs text-gray-500">
      Identitas situs dan bagaimana situs tampil di mesin pencari dan saat dibagikan. Isi halaman sendiri (judul meta, deskripsi meta, thumbnail artikel) selalu didahulukan.
      Kontak dan media sosial diatur di tab Footer.
      @if ($stored)
        <span class="font-semibold text-emerald-700">Profil dari CMS sedang dipakai.</span>
      @else
        <span class="font-semibold text-amber-700">Belum pernah disimpan: situs memakai pengaturan bawaan.</span>
      @endif
    </p>
  </div>

  @if (! $ready)
    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800" data-profile-not-ready>
      Tabel <b>site_settings</b> belum ada. Jalankan <code>php artisan migrate</code> di CMS, lalu muat ulang halaman ini.
    </div>
  @endif

  @if ($this->noindexReason === 'environment')
    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800" data-profile-noindex="environment">
      <b>Mesin pencari dilarang mengindeks situs ini</b> karena aplikasi tidak berjalan di lingkungan <code>production</code> (APP_ENV={{ app()->environment() }}).
      Ini disengaja agar salinan lokal / staging tidak muncul di Google. Di server produksi pastikan <code>APP_ENV=production</code> di berkas <code>.env</code> landing.
    </div>
  @elseif ($this->noindexReason === 'setting')
    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800" data-profile-noindex="setting">
      <b>Mesin pencari dilarang mengindeks situs ini</b> (kotak di bagian "Mesin pencari" dicentang). Hapus centang dan simpan saat situs siap diumumkan.
    </div>
  @endif

  @if ($errorsList !== [])
    <div class="rounded-md border border-red-200 bg-red-50 p-3 text-xs text-red-700" data-profile-errors>
      <p class="mb-1 font-bold">Belum disimpan. Perbaiki hal berikut:</p>
      <ul class="list-disc pl-5">
        @foreach ($errorsList as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form wire:submit="save" class="flex flex-col gap-5">
    {{-- Identitas --}}
    <div class="{{ $card }}" data-profile-identity>
      <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Identitas</span>
      <div>
        <label class="{{ $label }}">Nama situs / organisasi *</label>
        <input type="text" wire:model="f.name" maxlength="{{ \App\Content\SiteProfile::MAX_NAME }}" placeholder="Yayasan Sinar Bhakti Husada" class="{{ $input }}">
        <p class="mt-1 text-[11px] text-gray-400">Dipakai di judul tab ("Judul halaman | Nama situs"), kartu berbagi, dan data terstruktur.</p>
      </div>
      @foreach ($activeLocales as $l)
        <div class="grid gap-3 md:grid-cols-2" wire:key="identity-{{ $l }}">
          <div>
            <label class="{{ $label }}">Slogan ({{ strtoupper($l) }})</label>
            <input type="text" wire:model="f.tagline.{{ $l }}" maxlength="{{ \App\Content\SiteProfile::MAX_TAGLINE }}" class="{{ $input }}">
          </div>
          <div>
            <label class="{{ $label }}">Deskripsi bawaan ({{ strtoupper($l) }})</label>
            <textarea wire:model="f.description.{{ $l }}" rows="3" maxlength="{{ \App\Content\SiteProfile::MAX_DESCRIPTION }}" class="{{ $input }}"></textarea>
          </div>
        </div>
      @endforeach
      <p class="text-[11px] text-gray-400">Deskripsi bawaan dipakai untuk halaman yang belum punya deskripsi meta sendiri (Google menampilkan ±160 karakter). Teks yang dikosongkan di satu bahasa memakai bahasa bawaan ({{ strtoupper($defaultLocale) }}).</p>
    </div>

    {{-- Gambar --}}
    <div class="{{ $card }}" data-profile-images>
      <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Gambar</span>
      <div class="grid gap-4 md:grid-cols-3">
        @foreach ($images as $field => [$slot, $title, $hint])
          @php($id = $f[$field] ?? null)
          @php($url = $id ? ($this->imageUrls[$id] ?? '') : '')
          <div class="flex flex-col gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3" wire:key="img-{{ $field }}" data-profile-image="{{ $field }}">
            <span class="text-[11px] font-bold text-gray-600">{{ $title }}</span>
            <div class="flex h-24 items-center justify-center overflow-hidden rounded-md border border-dashed border-gray-300 bg-white">
              @if ($url !== '')
                <img src="{{ $url }}" alt="" class="max-h-full max-w-full object-contain">
              @elseif ($id)
                <span class="px-2 text-center text-[11px] text-red-600">Berkas tidak ditemukan. Pilih ulang atau hapus.</span>
              @else
                <span class="text-[11px] text-gray-400">Belum dipilih</span>
              @endif
            </div>
            <div class="flex gap-2">
              <button type="button" x-on:click="$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: '{{ $slot }}', allowedFileType: 'image' })" class="{{ $small }}">Pilih gambar</button>
              @if ($id)
                <button type="button" wire:click="clearImage('{{ $field }}')" class="{{ $small }}">Hapus</button>
              @endif
            </div>
            <p class="text-[11px] text-gray-400">{{ $hint }}</p>
          </div>
        @endforeach
      </div>
      @foreach ($activeLocales as $l)
        <div wire:key="og-alt-{{ $l }}">
          <label class="{{ $label }}">Teks pengganti gambar berbagi ({{ strtoupper($l) }})</label>
          <input type="text" wire:model="f.og_alt.{{ $l }}" maxlength="{{ \App\Content\SiteProfile::MAX_ALT }}" class="{{ $input }}">
        </div>
      @endforeach
    </div>

    {{-- Mesin pencari & media sosial --}}
    <div class="{{ $card }}" data-profile-seo>
      <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Mesin pencari &amp; media sosial</span>
      <div class="max-w-xs">
        <label class="{{ $label }}">Akun X (Twitter)</label>
        <input type="text" wire:model="f.twitter" maxlength="16" placeholder="@ysbh" class="{{ $input }} font-mono">
        <p class="mt-1 text-[11px] text-gray-400">Opsional. Dicantumkan di kartu berbagi X.</p>
      </div>
      <label class="flex items-start gap-2 text-sm text-gray-700">
        <input type="checkbox" wire:model="f.jsonld" class="mt-0.5 rounded border-gray-300">
        <span>Sertakan data terstruktur Organization di beranda <span class="text-[11px] text-gray-400">(nama, logo, deskripsi, email, telepon, alamat, dan akun sosial dari tab Footer; membantu Google mengenali organisasi)</span></span>
      </label>
      <label class="flex items-start gap-2 text-sm text-gray-700">
        <input type="checkbox" wire:model="f.noindex" class="mt-0.5 rounded border-gray-300">
        <span>Larang mesin pencari mengindeks situs <span class="text-[11px] text-gray-400">(tag robots noindex + robots.txt "Disallow: /"). Centang selama situs belum siap diumumkan. Di luar lingkungan production larangan ini selalu aktif.</span></span>
      </label>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <button type="submit" wire:loading.attr="disabled" @disabled(! $ready) class="rounded-md bg-foresty px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-[#043b2c] disabled:opacity-50">Simpan profil</button>
      @if ($stored)
        <button type="button" wire:click="restoreDefaults" wire:confirm="Kosongkan profil? Situs kembali ke pengaturan bawaan (nama dari APP_NAME, ikon dan gambar berbagi bawaan)." class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-200">Kosongkan profil</button>
      @endif
    </div>
  </form>
</div>
