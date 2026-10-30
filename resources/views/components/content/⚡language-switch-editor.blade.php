<?php

use App\Content\LanguageSwitch;
use App\Content\LanguageSwitchSettings;
use App\Livewire\Traits\WithNotifications;
use Livewire\Component;

/**
 * Pengaturan pengganti bahasa (rilis 104): pil bahasa melayang (nyala/mati, letak, kapan tampil, gaya, layar kecil) dan pil bahasa di
 * header (bilah lengket desktop dan bilah bawah layar kecil). Tautan bahasa di footer selalu ada. Dipasang sebagai tab "Bahasa" di Pengaturan:
 *
 *   <livewire:content.language-switch-editor lazy wire:key="language-switch-editor" />
 *
 * Hanya admin. Aturan isi di App\Content\LanguageSwitch (diuji di tests/language-switch-test.php). Tersimpan di site_settings (kunci "lang_switch");
 * landing membacanya lewat PublicLookup::languageSwitch(), jadi perubahan langsung tampil.
 */
new class extends Component {
  use WithNotifications;

  /** Isi formulir (bentuk LanguageSwitch::defaults). */
  public array $f = [];

  public array $errorsList = [];

  /** Apakah pengaturan sudah pernah disimpan dari CMS. */
  public bool $stored = false;

  /** Tabel site_settings sudah ada? */
  public bool $ready = true;

  public function mount(): void
  {
    abort_unless($this->canManage(), 403);
    $this->load();
  }

  public function placeholder(): string
  {
    return '<div class="p-6 text-sm text-gray-400">Memuat pengaturan bahasa…</div>';
  }

  private function canManage(): bool
  {
    $user = auth()->user();

    return $user && method_exists($user, 'hasRole') && $user->hasRole('admin');
  }

  private function load(): void
  {
    $this->errorsList = [];
    try {
      $this->ready = \Illuminate\Support\Facades\Schema::hasTable(LanguageSwitchSettings::TABLE);
      $this->f = $this->ready ? LanguageSwitchSettings::load() : LanguageSwitch::defaults();
      $this->stored = $this->ready && LanguageSwitchSettings::exists();
    } catch (\Throwable $e) {
      report($e);
      $this->ready = false;
      $this->stored = false;
      $this->f = LanguageSwitch::defaults();
    }
  }

  public function save(): void
  {
    if (!$this->canManage()) {
      $this->notify('Hanya admin yang dapat mengubah pengaturan bahasa.', 'error');

      return;
    }
    if (!$this->ready) {
      $this->notify('Tabel pengaturan belum ada. Jalankan php artisan migrate lebih dulu.', 'error');

      return;
    }
    $result = LanguageSwitch::sanitize($this->f);
    $this->errorsList = $result['errors'];
    if ($result['errors'] !== []) {
      $this->notify('Belum disimpan: periksa isian yang bermasalah.', 'error');

      return;
    }
    try {
      LanguageSwitchSettings::save($result['data'], auth()->id());
    } catch (\Throwable $e) {
      report($e);
      $this->notify('Pengaturan bahasa gagal disimpan. Coba lagi.', 'error');

      return;
    }
    $this->f = $result['data'];
    $this->stored = true;
    $this->audit('Pengaturan pengganti bahasa diperbarui');
    $this->notify('Pengaturan bahasa disimpan. Perubahan langsung tampil di situs.', 'success');
  }

  /** Hapus isi tersimpan: kembali ke bawaan (pil melayang dan pil header nyala). */
  public function restoreDefaults(): void
  {
    if (!$this->canManage() || !$this->ready) {
      return;
    }
    try {
      LanguageSwitchSettings::reset();
    } catch (\Throwable $e) {
      report($e);
      $this->notify('Gagal mengembalikan pengaturan bawaan.', 'error');

      return;
    }
    $this->audit('Pengaturan pengganti bahasa dikembalikan ke bawaan');
    $this->load();
    $this->notify('Pengaturan bahasa kembali ke bawaan.', 'success');
  }

  private function audit(string $text): void
  {
    try {
      if (function_exists('activity')) {
        activity('lang_switch_updates')->causedBy(auth()->user())->withProperties(['key' => LanguageSwitch::KEY])->log($text);
      }
    } catch (\Throwable $e) {
      report($e);
    }
  }
};
?>

@php
  $card = 'bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col gap-4';
  $label = 'block text-[11px] font-bold text-gray-500 mb-1';
  $choice = 'flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 has-[:checked]:border-foresty has-[:checked]:bg-sage-soft has-[:checked]:font-semibold has-[:checked]:text-foresty';
@endphp

<div class="flex flex-col gap-5" data-lang-switch-editor>
  <div>
    <h2 class="text-lg font-bold text-foresty">Pengganti bahasa</h2>
    <p class="text-xs text-gray-500">
      Mempermudah pengunjung berpindah bahasa dari bagian halaman mana pun. Tautan bahasa di footer selalu ada. Hanya tampil bila situs punya dua bahasa atau lebih.
      @if ($stored)
        <span class="font-semibold text-emerald-700">Pengaturan dari CMS sedang dipakai.</span>
      @else
        <span class="font-semibold text-amber-700">Belum pernah disimpan: memakai bawaan (pil melayang dan pil header nyala).</span>
      @endif
    </p>
  </div>

  @if (! $ready)
    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800" data-lang-switch-not-ready>
      Tabel <b>site_settings</b> belum ada. Jalankan <code>php artisan migrate</code> di CMS, lalu muat ulang halaman ini.
    </div>
  @endif

  @if ($errorsList !== [])
    <div class="rounded-md border border-red-200 bg-red-50 p-3 text-xs text-red-700" data-lang-switch-errors>
      <p class="mb-1 font-bold">Belum disimpan. Perbaiki hal berikut:</p>
      <ul class="list-disc pl-5">
        @foreach ($errorsList as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form wire:submit="save" class="flex flex-col gap-5">
    {{-- Pil melayang --}}
    <div class="{{ $card }}" data-lang-float-settings>
      <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Pil bahasa melayang</span>
      <label class="flex items-start gap-2 text-sm text-gray-700">
        <input type="checkbox" wire:model.live="f.floating" class="mt-0.5 rounded border-gray-300">
        <span>Tampilkan pil bahasa yang menempel di tepi layar <span class="text-[11px] text-gray-400">(ikut saat halaman digulir, jadi bahasa bisa diganti dari bagian mana pun)</span></span>
      </label>

      @if ($f['floating'] ?? false)
        <div wire:key="float-options" class="flex flex-col gap-4 border-t border-gray-100 pt-4">
          <div>
            <span class="{{ $label }}">Letak</span>
            <div class="flex flex-wrap gap-2">
              @foreach (LanguageSwitch::POSITIONS as $value => $name)
                <label class="{{ $choice }}"><input type="radio" wire:model="f.position" value="{{ $value }}" class="border-gray-300 text-foresty">{{ $name }}</label>
              @endforeach
            </div>
            <p class="mt-1 text-[11px] text-gray-400">Di layar kecil, letak bawah otomatis naik di atas bilah navigasi bawah.</p>
          </div>
          <div>
            <span class="{{ $label }}">Kapan tampil</span>
            <div class="flex flex-wrap gap-2">
              @foreach (LanguageSwitch::APPEARS as $value => $name)
                <label class="{{ $choice }}"><input type="radio" wire:model="f.appear" value="{{ $value }}" class="border-gray-300 text-foresty">{{ $name }}</label>
              @endforeach
            </div>
            <p class="mt-1 text-[11px] text-gray-400">"Setelah menggulir" tidak menutupi bagian atas halaman saat pertama dibuka (muncul setelah ±{{ LanguageSwitch::SCROLL_AFTER }} piksel).</p>
          </div>
          <div>
            <span class="{{ $label }}">Gaya</span>
            <div class="flex flex-wrap gap-2">
              @foreach (LanguageSwitch::STYLES as $value => $name)
                <label class="{{ $choice }}"><input type="radio" wire:model="f.style" value="{{ $value }}" class="border-gray-300 text-foresty">{{ $name }}</label>
              @endforeach
            </div>
            <p class="mt-1 text-[11px] text-gray-400">Kaca buram: putih tembus pandang dengan blur ala iOS. Solid: hijau Foresty, paling jelas di atas gambar.</p>
          </div>
          <label class="flex items-start gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model="f.mobile" class="mt-0.5 rounded border-gray-300">
            <span>Tampilkan juga di layar kecil <span class="text-[11px] text-gray-400">(bilah navigasi bawah di layar kecil sudah selalu terlihat; bila pil header di bawah dinyalakan, pil melayang biasanya tidak perlu di layar kecil)</span></span>
          </label>
        </div>
      @endif
    </div>

    {{-- Pil header --}}
    <div class="{{ $card }}" data-lang-header-settings>
      <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Pil bahasa di header</span>
      <label class="flex items-start gap-2 text-sm text-gray-700">
        <input type="checkbox" wire:model="f.header" class="mt-0.5 rounded border-gray-300">
        <span>Tampilkan pil bahasa di bilah menu lengket (desktop) dan di bilah bawah layar kecil, di samping tombol menu <span class="text-[11px] text-gray-400">(sebelumnya di layar kecil bahasa hanya bisa diganti setelah membuka menu)</span></span>
      </label>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <button type="submit" wire:loading.attr="disabled" @disabled(! $ready) class="rounded-md bg-foresty px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-[#043b2c] disabled:opacity-50">Simpan pengaturan bahasa</button>
      @if ($stored)
        <button type="button" wire:click="restoreDefaults" wire:confirm="Kembalikan ke bawaan? Pil bahasa melayang dan pil header akan menyala dengan letak dan gaya bawaan." class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-200">Kembalikan ke bawaan</button>
      @endif
    </div>
  </form>
</div>
