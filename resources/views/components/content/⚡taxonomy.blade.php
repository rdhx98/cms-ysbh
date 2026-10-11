<?php

use App\Content\Languages;
use App\Content\Taxonomy;
use App\Livewire\Traits\WithNotifications;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Kelola Kategori dan Tag dua bahasa (rilis 46). Dipasang di panel samping daftar artikel (contoh-kode/panel-kategori-tag.blade.php):
 *
 *   <livewire:content.taxonomy kind="category" wire:key="tax-category" />
 *   <livewire:content.taxonomy kind="tag"      wire:key="tax-tag" />
 *
 * Semua aturan (bahasa bawaan wajib, slug per bahasa, unik per bahasa, hapus aman) ada di App\Content\Taxonomy dan diuji di sana.
 * Hanya admin dan editor; penulis tetap bisa membuat tag baru dari editor artikel (satu nama, disalin ke semua bahasa).
 */
new class extends Component {
  use WithNotifications;

  /** category | tag */
  #[Locked]
  public string $kind = 'category';

  /** null = form membuat baru; angka = mengubah baris itu */
  public ?int $editId = null;

  /** @var array<string,string> bahasa => nama */
  public array $names = [];

  /** @var array<string,string> bahasa => slug ('' = otomatis dari nama) */
  public array $slugs = [];

  public function mount(string $kind = 'category'): void
  {
    $this->kind = Taxonomy::kind($kind) ? $kind : 'category';
    $this->resetForm();
  }

  /** @return array{locales:string[],default:string} bahasa bawaan terlebih dulu */
  #[Computed]
  public function langs(): array
  {
    $cfg = Languages::fromConfig();
    $locales = array_values(array_unique(array_merge([$cfg['default']], $cfg['locales'])));

    return ['locales' => $locales, 'default' => $cfg['default']];
  }

  /** Nama bahasa untuk label; kode lain tampil sebagai kodenya. */
  public function langName(string $code): string
  {
    return ['en' => 'English', 'id' => 'Indonesia'][$code] ?? strtoupper($code);
  }

  #[Computed]
  public function rows(): array
  {
    return Taxonomy::rows($this->kind, $this->langs['locales'], $this->langs['default']);
  }

  /** Apakah kolom name per bahasa (cast array/json)? Bila tidak, hanya bahasa bawaan yang tersimpan. */
  #[Computed]
  public function perLocale(): bool
  {
    $class = Taxonomy::kind($this->kind)['model'];

    return class_exists($class) && (new $class())->hasCast('name', ['array', 'json']);
  }

  #[Computed]
  public function withSlug(): bool
  {
    $class = Taxonomy::kind($this->kind)['model'];

    return class_exists($class) && \Illuminate\Support\Facades\Schema::hasColumn((new $class())->getTable(), 'slug');
  }

  private function canManage(): bool
  {
    $user = auth()->user();

    return $user && method_exists($user, 'hasRole') && $user->hasRole(['admin', 'editor']);
  }

  private function resetForm(): void
  {
    $this->editId = null;
    $this->names = array_fill_keys($this->langs['locales'], '');
    $this->slugs = array_fill_keys($this->langs['locales'], '');
    $this->resetErrorBag();
    $this->dispatch('taxonomy-reset');
  }

  public function cancel(): void
  {
    $this->resetForm();
  }

  public function edit(int $id): void
  {
    foreach ($this->rows as $row) {
      if ($row['id'] === $id) {
        $this->editId = $id;
        $this->names = array_intersect_key($row['names'], array_flip($this->langs['locales']));
        $this->slugs = array_intersect_key($row['slugs'], array_flip($this->langs['locales']));
        $this->resetErrorBag();
        $this->dispatch('taxonomy-edit');

        return;
      }
    }
    $this->notify(Taxonomy::kind($this->kind)['label'] . ' tidak ditemukan (mungkin sudah dihapus).', 'error');
  }

  public function save(): void
  {
    if (!$this->canManage()) {
      $this->notify('Anda tidak memiliki otorisasi untuk mengelola ini.', 'error');

      return;
    }
    $label = Taxonomy::kind($this->kind)['label'];
    $locales = $this->langs['locales'];

    $result = Taxonomy::save($this->kind, $this->editId, $this->names, $this->slugs, $locales, $this->langs['default']);
    if (!$result['ok']) {
      $this->resetErrorBag();
      foreach ($result['errors'] as $key => $message) {
        $this->addError($key, $message);
      }
      if (isset($result['errors']['form'])) {
        $this->notify($result['errors']['form'], 'error');
      }

      return;
    }

    $this->audit($result['created'] ? "$label dibuat" : "$label diubah", $result['id']);
    $this->notify($result['created'] ? "$label ditambahkan." : "$label diperbarui.", 'success');
    unset($this->rows);
    $this->resetForm();
  }

  public function delete(int $id): void
  {
    if (!$this->canManage()) {
      $this->notify('Anda tidak memiliki otorisasi untuk menghapus ini.', 'error');

      return;
    }
    $label = Taxonomy::kind($this->kind)['label'];
    $result = Taxonomy::delete($this->kind, $id);
    if (!$result['ok']) {
      $this->notify($result['error'], 'error');

      return;
    }

    $this->audit("$label dihapus", $id);
    $this->notify($result['detached'] > 0 ? "$label dihapus dan dilepas dari {$result['detached']} artikel." : "$label dihapus.", 'success');
    unset($this->rows);
    if ($this->editId === $id) {
      $this->resetForm();
    }
  }

  /** Riwayat (activity_log) bila paket Spatie terpasang; galatnya tidak pernah menggagalkan simpan. */
  private function audit(string $text, ?int $id): void
  {
    try {
      if (function_exists('activity')) {
        activity('taxonomy_updates')->causedBy(auth()->user())->withProperties(['kind' => $this->kind, 'id' => $id])->log($text);
      }
    } catch (\Throwable $e) {
      report($e);
    }
  }
};
?>

@php
  $placeholders = [
    'category' => ['id' => 'Misal: Berita Internal', 'en' => 'e.g. Internal News'],
    'tag' => ['id' => 'Misal: stunting', 'en' => 'e.g. stunting'],
  ][$kind];
  $label = \App\Content\Taxonomy::kind($kind)['label'];
  $default = $this->langs['default'];
  $locales = $this->langs['locales'];
@endphp

<div
  x-data="{
    touched: {},
    confirmId: null,
    slugify(s) {
      return String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/&/g, ' dan ').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    },
    typeName(loc, value) {
      if (this.touched[loc] || {{ $this->withSlug ? 'false' : 'true' }}) return;
      $wire.$set('slugs.' + loc, this.slugify(String(value).replace(/^[#\s]+/, '')), false);
    },
  }"
  x-on:taxonomy-reset.window="touched = {}"
  x-on:taxonomy-edit.window="touched = Object.fromEntries(@js($locales).map((l) => [l, true]))"
  class="space-y-4"
>
  {{-- FORM: satu baris per bahasa --}}
  <form wire:submit="save" class="space-y-3" novalidate>
    <div class="flex items-center justify-between gap-2">
      <span class="text-xs font-semibold text-zinc-500">
        {{ $editId ? "Ubah $label" : "$label Baru" }}
      </span>
      @if ($editId)
        <button type="button" wire:click="cancel" class="cursor-pointer text-xs font-semibold text-zinc-500 underline hover:text-zinc-800">
          Batal ubah
        </button>
      @endif
    </div>

    @foreach ($locales as $loc)
      @php
        $isDefault = $loc === $default;
        $disabled = !$this->perLocale && !$isDefault;
      @endphp
      <fieldset class="space-y-1.5 rounded-xl border border-zinc-200 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900" @disabled($disabled)>
        <legend class="px-1 text-[11px] font-bold tracking-wider text-zinc-500 uppercase">
          {{ strtoupper($loc) }} · {{ $this->langName($loc) }}
          @if ($isDefault)
            <span class="text-foresty normal-case">(bahasa bawaan, wajib)</span>
          @endif
        </legend>

        <input
          id="tax-{{ $kind }}-name-{{ $loc }}"
          type="text"
          wire:model="names.{{ $loc }}"
          x-on:input="typeName('{{ $loc }}', $event.target.value)"
          x-on:keydown.enter.prevent="$wire.save()"
          placeholder="{{ $placeholders[$loc] ?? '' }}"
          maxlength="{{ \App\Content\Taxonomy::kind($kind)['max'] + 20 }}"
          autocomplete="off"
          aria-label="Nama {{ $label }} ({{ strtoupper($loc) }})"
          class="focus:ring-sbh-green w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:outline-none disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
        />
        @error("name.$loc")
          <p class="text-xs text-red-600">{{ $message }}</p>
        @enderror

        @if ($this->withSlug)
          <div class="flex items-center gap-1.5">
            <span class="text-xs text-zinc-400">slug</span>
            <input
              id="tax-{{ $kind }}-slug-{{ $loc }}"
              type="text"
              wire:model="slugs.{{ $loc }}"
              x-on:input="touched['{{ $loc }}'] = true"
              x-on:keydown.enter.prevent="$wire.save()"
              placeholder="otomatis dari nama"
              autocomplete="off"
              spellcheck="false"
              aria-label="Slug {{ $label }} ({{ strtoupper($loc) }})"
              class="focus:ring-sbh-green w-full rounded-md border border-zinc-200 bg-zinc-50 px-2 py-1 font-mono text-xs text-zinc-600 focus:ring-1 focus:outline-none disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
            />
          </div>
          @error("slug.$loc")
            <p class="text-xs text-red-600">{{ $message }}</p>
          @enderror
        @endif

        @if (!$isDefault && !$disabled)
          <p class="text-[11px] text-zinc-400">
            Kosong = memakai nama {{ $this->langName($default) }}.
            @if ($kind === 'category')
              Kategori tanpa slug di bahasa ini tidak punya halaman di bahasa ini.
            @endif
          </p>
        @endif
        @if ($disabled)
          <p class="text-[11px] text-amber-700">
            Kolom <code>name</code> belum per bahasa (tambahkan cast <code>array</code> di model), jadi hanya bahasa bawaan yang tersimpan.
          </p>
        @endif
      </fieldset>
    @endforeach

    <button
      type="submit"
      wire:loading.attr="disabled"
      wire:target="save"
      class="bg-forest hover:bg-forest/80 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-white transition-colors disabled:opacity-60"
    >
      <x-dynamic-component :component="'lucide-save'" class="h-4 w-4" stroke-width="2" />
      {{ $editId ? 'Simpan perubahan' : "Tambah $label" }}
    </button>
  </form>

  {{-- DAFTAR --}}
  <div class="max-h-75 divide-y divide-zinc-200 overflow-y-auto rounded-xl border border-zinc-200 bg-zinc-50 p-2 lg:max-h-96 dark:divide-zinc-700 dark:border-zinc-800 dark:bg-zinc-800/50">
    @forelse ($this->rows as $row)
      <div wire:key="tax-{{ $kind }}-{{ $row['id'] }}" class="space-y-1 px-2 py-2.5 text-sm {{ $editId === $row['id'] ? 'bg-sage-soft rounded-lg' : '' }}">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0 flex-1 space-y-0.5">
            @foreach ($locales as $loc)
              <div class="flex items-baseline gap-1.5">
                <span class="w-5 shrink-0 text-[10px] font-bold text-zinc-400 uppercase">{{ $loc }}</span>
                @if ($row['names'][$loc] !== '')
                  <span class="truncate font-medium text-zinc-800 dark:text-zinc-100">{{ $row['names'][$loc] }}</span>
                  @if ($this->withSlug && ($row['slugs'][$loc] ?? '') !== '')
                    <span class="truncate font-mono text-[11px] text-zinc-400">/{{ $row['slugs'][$loc] }}</span>
                  @endif
                @else
                  <span class="text-xs text-amber-700 italic">belum diterjemahkan</span>
                @endif
              </div>
            @endforeach
          </div>

          <div class="flex shrink-0 items-center gap-1">
            <span class="text-[11px] text-zinc-400" title="Jumlah artikel yang memakai">{{ $row['uses'] }} artikel</span>
            <button
              type="button"
              wire:click="edit({{ $row['id'] }})"
              aria-label="Ubah {{ $row['names'][$default] ?: '#' . $row['id'] }}"
              class="cursor-pointer rounded-md bg-zinc-200 p-1.5 text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-200"
            >
              <flux:icon variant="solid" icon="pencil" class="size-3.5!" />
            </button>
            <button
              type="button"
              x-on:click="confirmId = confirmId === {{ $row['id'] }} ? null : {{ $row['id'] }}"
              @if ($kind === 'category' && $row['uses'] > 0) disabled title="Dipakai {{ $row['uses'] }} artikel: pindahkan artikelnya dulu" @endif
              aria-label="Hapus {{ $row['names'][$default] ?: '#' . $row['id'] }}"
              class="cursor-pointer rounded-md bg-red-500 p-1.5 text-white hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-40"
            >
              <flux:icon variant="solid" icon="trash" class="size-3.5!" />
            </button>
          </div>
        </div>

        <div x-show="confirmId === {{ $row['id'] }}" x-cloak class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-red-50 px-2 py-1.5 text-xs text-red-800 dark:bg-red-950 dark:text-red-200">
          <span>
            @if ($kind === 'tag' && $row['uses'] > 0)
              Dilepas dari {{ $row['uses'] }} artikel, lalu dihapus. Lanjut?
            @else
              Hapus permanen?
            @endif
          </span>
          <span class="flex gap-1.5">
            <button type="button" x-on:click="confirmId = null" class="cursor-pointer rounded-md bg-white px-2 py-1 font-semibold text-zinc-700 ring-1 ring-zinc-300 ring-inset">Batal</button>
            <button type="button" wire:click="delete({{ $row['id'] }})" x-on:click="confirmId = null" class="cursor-pointer rounded-md bg-red-600 px-2 py-1 font-semibold text-white">Ya, hapus</button>
          </span>
        </div>
      </div>
    @empty
      <p class="px-2 py-6 text-center text-xs text-zinc-500">Belum ada {{ strtolower($label) }}. Tambahkan lewat formulir di atas.</p>
    @endforelse
  </div>
</div>
