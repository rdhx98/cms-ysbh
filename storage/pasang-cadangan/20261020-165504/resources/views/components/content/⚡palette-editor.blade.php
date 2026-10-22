<?php

use App\Content\Palette;
use App\Content\PaletteSettings;
use App\Livewire\Traits\WithNotifications;
use Livewire\Component;

/**
 * Editor palet warna situs (rilis 57): warna tiap slot (forest, aurum, coral, ... dan 8 slot kustom) dan warna teks di atasnya
 * (Otomatis = putih/gelap menurut kontras, atau hex sendiri dengan peringatan bila kontras < 4,5).
 *
 * Dipasang di mana saja, mis. sebagai tab "Palet" di halaman Navigation builder (opsional/menu-builder.blade.php):
 *
 *   <livewire:content.palette-editor />
 *
 * Aturan isi ada di App\Content\Palette (diuji di tests/palette-test.php); komponen ini hanya formulir. Hanya admin dan editor.
 * Tersimpan di tabel site_settings (kunci "palette"; php artisan migrate rilis 55). Landing mencetak variabel CSS lewat <x-palette-style />.
 */
new class extends Component {
  use WithNotifications;

  /** Bentuk Palet::forEditor: kunci => {label, hex, auto, on}. */
  public array $f = [];

  public array $errorsList = [];

  public array $warningsList = [];

  /** Sudah pernah disimpan dari CMS (false = situs memakai warna bawaan). */
  public bool $stored = false;

  /** Tabel site_settings ada? */
  public bool $ready = true;

  public function mount(): void
  {
    abort_unless($this->canManage(), 403);
    $this->load();
  }

  /** Tampilan sementara saat dimuat malas (lazy) di tab Palet. */
  public function placeholder(): string
  {
    return '<div class="p-6 text-sm text-gray-400">Memuat palet warna…</div>';
  }

  private function canManage(): bool
  {
    $user = auth()->user();

    return $user && method_exists($user, 'hasRole') && $user->hasRole(['admin', 'editor']);
  }

  private function guard(): bool
  {
    if (!$this->canManage()) {
      $this->notify('Anda tidak memiliki otorisasi untuk mengubah palet warna.', 'error');

      return false;
    }

    return true;
  }

  private function load(): void
  {
    $this->errorsList = [];
    $this->warningsList = [];
    try {
      $this->ready = \Illuminate\Support\Facades\Schema::hasTable(PaletteSettings::TABLE);
      $palette = $this->ready ? PaletteSettings::load() : Palette::defaults();
      $this->stored = $this->ready && PaletteSettings::exists();
    } catch (\Throwable $e) {
      report($e);
      $this->ready = false;
      $this->stored = false;
      $palette = Palette::defaults();
    }
    $this->f = Palette::forEditor($palette);
  }

  /** Kosongkan satu slot kustom (= tidak dipakai). */
  public function clearSlot(string $key): void
  {
    if (!$this->guard() || !Palette::isCustom($key) || !isset($this->f[$key])) {
      return;
    }
    $this->f[$key]['hex'] = '';
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

    $result = Palette::sanitize(Palette::fromEditor($this->f));
    $this->errorsList = $result['errors'];
    $this->warningsList = $result['warnings'];
    if ($result['errors'] !== []) {
      $this->notify('Palet belum disimpan: periksa ' . count($result['errors']) . ' isian yang bermasalah.', 'error');

      return;
    }

    try {
      PaletteSettings::save($result['data'], auth()->id());
    } catch (\Throwable $e) {
      report($e);
      $this->notify('Palet gagal disimpan. Coba lagi.', 'error');

      return;
    }
    $this->f = Palette::forEditor($result['data']);
    $this->stored = true;
    $this->audit('Palet warna diperbarui');
    $this->notify($result['warnings'] === [] ? 'Palet disimpan. Perubahan langsung tampil di situs.' : 'Palet disimpan, tetapi ada peringatan kontras.', 'success');
  }

  /** Hapus palet tersimpan: situs memakai warna bawaan. */
  public function restoreDefaults(): void
  {
    if (!$this->guard() || !$this->ready) {
      return;
    }
    try {
      PaletteSettings::reset();
    } catch (\Throwable $e) {
      report($e);
      $this->notify('Gagal mengembalikan warna bawaan.', 'error');

      return;
    }
    $this->audit('Palet warna dikembalikan ke bawaan');
    $this->load();
    $this->notify('Palet kembali ke warna bawaan.', 'success');
  }

  /** Riwayat (activity_log) bila paket Spatie terpasang; galatnya tidak pernah menggagalkan simpan. */
  private function audit(string $text): void
  {
    try {
      if (function_exists('activity')) {
        activity('palette_updates')->causedBy(auth()->user())->withProperties(['key' => Palette::KEY])->log($text);
      }
    } catch (\Throwable $e) {
      report($e);
    }
  }
};
?>

@php
  $input = 'w-full text-sm border-gray-300 rounded-md focus:ring-foresty';
  $small = 'rounded border border-gray-200 px-2 py-1 text-[11px] font-semibold text-gray-600 hover:bg-gray-50';
@endphp

<div class="flex flex-col gap-5" data-palette-editor>
  <div>
    <h2 class="text-lg font-bold text-foresty">Palet Warna</h2>
    <p class="text-xs text-gray-500">
      Warna situs publik. Tiap slot punya warna dan warna teks di atasnya (Otomatis = putih atau gelap, mana yang lebih terbaca).
      Slot kustom boleh dikosongkan.
      @if ($stored)
        <span class="font-semibold text-emerald-700">Palet dari CMS sedang dipakai.</span>
      @else
        <span class="font-semibold text-amber-700">Belum pernah disimpan: situs memakai warna bawaan.</span>
      @endif
    </p>
  </div>

  @if (! $ready)
    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800" data-palette-not-ready>
      Tabel <b>site_settings</b> belum ada. Jalankan <code>php artisan migrate</code> di CMS, lalu muat ulang halaman ini.
    </div>
  @endif

  @if ($errorsList !== [])
    <div class="rounded-md border border-red-200 bg-red-50 p-3 text-xs text-red-700" data-palette-errors>
      <p class="mb-1 font-bold">Belum disimpan. Perbaiki hal berikut:</p>
      <ul class="list-disc pl-5">
        @foreach ($errorsList as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if ($warningsList !== [])
    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800" data-palette-warnings>
      <p class="mb-1 font-bold">Peringatan kontras (tetap tersimpan):</p>
      <ul class="list-disc pl-5">
        @foreach ($warningsList as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form wire:submit="save" class="flex flex-col gap-3">
    @foreach ($f as $key => $slot)
      @php
        $valid = \App\Content\Palette::hex($slot['hex']);
        $onHex = $valid === null ? null : (($slot['auto'] ?? true) ? \App\Content\Palette::autoOn($valid) : (\App\Content\Palette::hex($slot['on'] ?? '') ?? \App\Content\Palette::autoOn($valid)));
        $ratio = ($valid !== null && $onHex !== null) ? \App\Content\Palette::contrast($valid, $onHex) : null;
        $custom = \App\Content\Palette::isCustom($key);
      @endphp
      <div class="grid items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm md:grid-cols-[7rem_1fr_1fr_1fr]" wire:key="slot-{{ $key }}" data-palette-slot="{{ $key }}">
        {{-- pratinjau: warna slot dengan teks di atasnya --}}
        <div class="flex h-14 items-center justify-center rounded-lg border border-gray-200 text-sm font-bold" style="background-color: {{ $valid ?? 'transparent' }}; color: {{ $onHex ?? '#9CA3AF' }}">
          {{ $valid === null ? '—' : 'Aa' }}
        </div>

        <div>
          <label class="mb-1 block text-[11px] font-bold text-gray-500">Nama <code class="font-mono text-[10px] text-gray-400">{{ $key }}</code></label>
          <input type="text" wire:model="f.{{ $key }}.label" maxlength="{{ \App\Content\Palette::MAX_LABEL }}" class="{{ $input }}">
        </div>

        <div>
          <label class="mb-1 block text-[11px] font-bold text-gray-500">Warna{{ $custom ? ' (kosong = tidak dipakai)' : '' }}</label>
          <div class="flex items-center gap-2">
            <input type="color" value="{{ $valid ?? '#000000' }}" wire:model.live.debounce.400ms="f.{{ $key }}.hex" class="h-9 w-10 cursor-pointer rounded border border-gray-300 bg-white p-0.5" aria-label="Pilih warna {{ $key }}">
            <input type="text" wire:model.live.debounce.400ms="f.{{ $key }}.hex" maxlength="7" placeholder="#RRGGBB" class="{{ $input }} font-mono uppercase">
            @if ($custom)
              <button type="button" wire:click="clearSlot('{{ $key }}')" class="{{ $small }}">Kosongkan</button>
            @endif
          </div>
        </div>

        <div>
          <label class="mb-1 flex items-center gap-2 text-[11px] font-bold text-gray-500">
            Teks di atasnya
            <span class="flex items-center gap-1 font-normal"><input type="checkbox" wire:model.live="f.{{ $key }}.auto" class="rounded text-foresty focus:ring-foresty"> Otomatis</span>
          </label>
          @if (! ($slot['auto'] ?? true))
            <div class="flex items-center gap-2">
              <input type="color" value="{{ \App\Content\Palette::hex($slot['on'] ?? '') ?? '#FFFFFF' }}" wire:model.live.debounce.400ms="f.{{ $key }}.on" class="h-9 w-10 cursor-pointer rounded border border-gray-300 bg-white p-0.5" aria-label="Warna teks di atas {{ $key }}">
              <input type="text" wire:model.live.debounce.400ms="f.{{ $key }}.on" maxlength="7" placeholder="#RRGGBB" class="{{ $input }} font-mono uppercase">
            </div>
          @elseif ($onHex !== null)
            <p class="text-xs text-gray-500">Memakai <span class="font-mono">{{ $onHex }}</span></p>
          @endif
          @if ($ratio !== null)
            <p class="mt-1 text-[11px] {{ $ratio < \App\Content\Palette::MIN_CONTRAST ? 'font-semibold text-amber-700' : 'text-emerald-700' }}" data-palette-contrast>
              Kontras {{ number_format($ratio, 1) }}{{ $ratio < \App\Content\Palette::MIN_CONTRAST ? ': kurang terbaca (disarankan minimal ' . \App\Content\Palette::MIN_CONTRAST . ')' : ': terbaca' }}
            </p>
          @endif
        </div>
      </div>
    @endforeach

    <div class="flex flex-wrap items-center gap-3">
      <button type="submit" wire:loading.attr="disabled" @disabled(! $ready) class="rounded-md bg-foresty px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-[#043b2c] disabled:opacity-50">Simpan palet</button>
      @if ($stored)
        <button type="button" wire:click="restoreDefaults" wire:confirm="Kembalikan palet ke warna bawaan? Warna yang sudah Anda atur akan hilang." class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-200">Kembalikan ke warna bawaan</button>
      @endif
    </div>
  </form>
</div>
