{{--
  <x-content.link-target> — pemilih tujuan tautan satu tombol, tanpa mengetik alamat (rilis 88). Dipakai editor Pengumuman.
  Tujuan dipilih lewat chip (Tanpa tombol, Halaman, Beranda, Daftar artikel, Kategori, Alamat web, Email, Telepon, Anchor). Halaman dipilih dari hasil
  pencarian CMS, kategori dari daftar: yang tersimpan adalah slug yang SUDAH ada, bukan ketikan, jadi tidak ada salah eja yang berujung 404; pembaca
  otomatis dibawa ke versi bahasanya. Hanya Alamat web, Email, Telepon, dan Anchor yang diketik.
  Hanya tampilan: komponen Livewire pemuat harus punya metode setDestKind($index, $kind), startPick($index), pickPage($id) dan properti
  f.items.{index}.dest_ref (nilai pilihan). Alamat hasilnya dibentuk di server (MenuTarget::build), bukan di komponen ini.

  Alat:   index        nomor pengumuman di komponen pemuat
          kind         none|page|home|articles|category|url|mailto|tel|anchor
          ref          nilai pilihan saat ini (slug halaman, slug kategori, alamat, ...)
          picking      pemilih halaman sedang dibuka untuk pengumuman ini
          pageResults  hasil pencarian halaman [{id,label,hint}]
          categories   peta slug => nama kategori
  Semua teks pengguna dicetak lewat {{ }}.
--}}
@props(['index' => 0, 'kind' => 'none', 'ref' => '', 'picking' => false, 'pageResults' => [], 'categories' => []])
@php
    $field = 'w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm placeholder:text-gray-400 focus:border-foresty focus:ring-foresty';
    $kinds = [
        'none' => ['Tanpa tombol', 'x'],
        'page' => ['Halaman', 'file'],
        'home' => ['Beranda', 'home'],
        'articles' => ['Daftar artikel', 'list'],
        'category' => ['Kategori artikel', 'columns'],
        'url' => ['Alamat web', 'globe'],
        'mailto' => ['Email', 'mail'],
        'tel' => ['Telepon', 'phone'],
        'anchor' => ['Anchor (#)', 'link'],
    ];
    $placeholders = ['url' => 'https://… atau /jalur', 'mailto' => 'nama@contoh.org', 'tel' => '+62 …', 'anchor' => 'kontak'];
    $explain = [
        'none' => 'Pengumuman tampil tanpa tombol.',
        'page' => 'Pilih halaman dari CMS. Pembaca dibawa ke versi bahasanya.',
        'home' => 'Menuju beranda bahasa pembaca.',
        'articles' => 'Menuju daftar semua artikel.',
        'category' => 'Menuju daftar artikel satu kategori.',
        'url' => 'Alamat https://… atau jalur di situs ini, mis. /tentang.',
        'mailto' => 'Membuka aplikasi email pembaca.',
        'tel' => 'Menelepon langsung dari ponsel pembaca.',
        'anchor' => 'Loncat ke bagian di halaman yang sama. Tulis tanpa tanda #.',
    ];
    $needsRef = in_array($kind, ['page', 'category', 'url', 'mailto', 'tel', 'anchor'], true);
    $incomplete = $needsRef && trim((string) $ref) === '';
@endphp
<div class="flex flex-col gap-2" data-link-target="{{ $kind }}">
    <div role="group" aria-label="Jenis tujuan tombol" class="flex flex-wrap gap-1.5" data-link-kinds>
        @foreach ($kinds as $value => [$text, $icon])
            <button
                type="button"
                wire:click="setDestKind({{ $index }}, '{{ $value }}')"
                wire:key="lk-{{ $index }}-{{ $value }}"
                aria-pressed="{{ $kind === $value ? 'true' : 'false' }}"
                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty {{ $kind === $value ? 'border-foresty bg-emerald-50 text-foresty' : 'border-gray-200 bg-white text-gray-600 hover:border-foresty hover:text-foresty' }}"
            ><x-content.icon :name="$icon" /> {{ $text }}</button>
        @endforeach
    </div>
    <p class="px-1 text-xs text-gray-500">{{ $explain[$kind] ?? '' }}</p>

    @if ($kind === 'page')
        @if ($ref !== '' && ! $picking)
            <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2" data-link-picked>
                <x-content.icon name="file" class="text-gray-400" />
                <code class="min-w-0 flex-1 truncate font-mono text-sm">/{{ $ref }}</code>
                <button type="button" wire:click="startPick({{ $index }})" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-gray-600 transition hover:bg-gray-100 hover:text-foresty focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty">Ganti</button>
            </div>
        @else
            <div class="flex flex-col gap-2" data-link-page-search x-init="$el.querySelector('input[type=search]')?.focus()">
                <div class="relative">
                    <x-content.icon name="search" class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
                    <input type="search" wire:model.live.debounce.300ms="pageQuery" x-on:focus="$wire.startPick({{ $index }})" placeholder="Cari judul halaman (min. 2 huruf)…" class="{{ $field }} pl-8" autocomplete="off" aria-label="Cari halaman">
                </div>
                @if (count($pageResults) > 0)
                    <ul class="divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-200 bg-white text-sm" data-link-page-results>
                        @foreach ($pageResults as $r)
                            <li wire:key="lp-{{ $index }}-{{ $r['id'] }}">
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
    @elseif ($kind === 'category')
        <select wire:model.live="f.items.{{ $index }}.dest_ref" class="{{ $field }}" aria-label="Kategori">
            <option value="">Pilih kategori…</option>
            @foreach ($categories as $slug => $name)
                <option value="{{ $slug }}">{{ $name }}</option>
            @endforeach
            @if ($ref !== '' && ! array_key_exists($ref, $categories))
                <option value="{{ $ref }}">{{ $ref }} (tidak ditemukan)</option>
            @endif
        </select>
        @if (count($categories) === 0)
            <p class="text-xs text-amber-700">Belum ada kategori yang punya slug. Isi slug kategori lebih dulu.</p>
        @endif
    @elseif (isset($placeholders[$kind]))
        <input type="text" wire:model.live.debounce.500ms="f.items.{{ $index }}.dest_ref" placeholder="{{ $placeholders[$kind] }}" aria-label="Tujuan tombol" class="{{ $field }} font-mono text-xs">
    @endif

    @if ($incomplete)
        <p class="flex items-start gap-1.5 px-1 text-xs text-amber-700" data-link-incomplete><x-content.icon name="alert" class="mt-px h-3.5 w-3.5" /> Tujuan belum dipilih, jadi tombol tidak tampil.</p>
    @endif
</div>
