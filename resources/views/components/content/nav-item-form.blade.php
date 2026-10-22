{{--
  <x-content.nav-item-form> — formulir satu menu header untuk opsional/navigation-builder.blade.php (rilis 88).
  Hanya tampilan: semua isian terikat (wire:model) ke properti komponen Livewire pemuatnya (labels, kind, ref, pageQuery, is_active) dan tombolnya
  memanggil metodenya (save, resetForm, pickPage). Dipakai di dua tempat (menu baru di atas daftar, menu yang diedit di dalam barisnya), jadi ditulis sekali.
  Tujuan dipilih lewat chip (bukan daftar tarik-turun); label memakai <x-content.lang-field> yang mengikuti pilihan bahasa bersama (Ganda | ID | EN).

  Alat:   editId        id menu yang diedit (null = menu baru)
          legacyRoute   nama rute lama yang dibawa baris itu ('' bila tidak ada)
          locales       daftar bahasa;  default  bahasa bawaan
          labels        peta bahasa => label (untuk petunjuk abu-abu)
          kind, ref     jenis tujuan dan nilainya saat ini
          pageResults   hasil pencarian halaman [{id,label,hint}]
          categories    peta slug => nama kategori
          preview       peta bahasa => alamat yang dilihat pembaca
  Semua teks pengguna dicetak lewat {{ }}.
--}}
@props([
    'editId' => null,
    'legacyRoute' => '',
    'locales' => [],
    'default' => 'en',
    'labels' => [],
    'kind' => 'page',
    'ref' => '',
    'pageResults' => [],
    'categories' => [],
    'preview' => [],
])
@php
    $field = 'w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm placeholder:text-gray-400 focus:border-foresty focus:ring-foresty';
    $kinds = [
        'page' => ['Halaman', 'file'],
        'home' => ['Beranda', 'home'],
        'articles' => ['Daftar artikel', 'list'],
        'category' => ['Artikel per kategori', 'columns'],
        'url' => ['Alamat web', 'globe'],
        'mailto' => ['Email', 'mail'],
        'tel' => ['Telepon', 'phone'],
        'anchor' => ['Anchor (#)', 'link'],
    ];
    $placeholders = ['url' => 'https://… atau /jalur', 'mailto' => 'nama@contoh.org', 'tel' => '+62 …', 'anchor' => 'kontak'];
    $explain = [
        'page' => 'Pilih halaman yang sudah dibuat di CMS.',
        'home' => 'Menuju beranda. Pembaca dibawa ke beranda bahasanya.',
        'articles' => 'Menuju daftar semua artikel.',
        'category' => 'Menuju daftar artikel dari satu kategori.',
        'url' => 'Alamat https://… atau jalur di situs ini, mis. /tentang.',
        'mailto' => 'Membuka aplikasi email pembaca.',
        'tel' => 'Menelepon langsung dari ponsel pembaca.',
        'anchor' => 'Loncat ke bagian di halaman yang sama. Tulis tanpa tanda #.',
    ];
@endphp
<form wire:submit="save" class="relative flex flex-col gap-4 rounded-2xl border border-foresty/30 bg-emerald-50/40 p-4 shadow-sm" data-nav-form="{{ $editId ? 'edit' : 'new' }}">
    <div class="flex items-center gap-2">
        <x-content.icon :name="$editId ? 'pencil' : 'plus'" class="h-4 w-4 text-foresty" />
        <h3 class="text-sm font-bold text-foresty">{{ $editId ? 'Ubah menu' : 'Menu baru' }}</h3>
        <button type="button" wire:click="resetForm" class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-white hover:text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty" aria-label="Tutup formulir"><x-content.icon name="x" /></button>
    </div>

    @if ($legacyRoute !== '')
        <p class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900" data-nav-legacy>
            <x-content.icon name="alert" class="mt-px h-4 w-4" />
            <span>Baris ini memakai rute lama <b>{{ $legacyRoute }}</b> yang sudah tidak ada. Pilih tujuan baru di bawah, lalu simpan.</span>
        </p>
    @endif

    <div class="flex flex-col gap-1.5">
        <p class="text-xs font-semibold text-gray-600">Teks menu</p>
        <x-content.lang-field path="labels" :values="$labels" :locales="$locales" :default="$default" label="Teks menu" :max="120" placeholder="Wajib diisi, mis. Tentang kami" class="!font-semibold" />
        @error('labels.' . $default)<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        @error('labels.*')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col gap-2">
        <p class="text-xs font-semibold text-gray-600">Tujuan <span class="font-normal text-gray-400">satu tujuan untuk semua bahasa: pembaca dibawa ke versi bahasanya</span></p>
        <div role="radiogroup" aria-label="Jenis tujuan" class="flex flex-wrap gap-1.5" data-nav-kinds>
            @foreach ($kinds as $value => [$text, $icon])
                <label class="relative cursor-pointer" wire:key="kind-{{ $value }}">
                    <input type="radio" name="nav-kind" value="{{ $value }}" wire:model.live="kind" class="peer sr-only">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-gray-600 shadow-sm transition hover:border-foresty hover:text-foresty peer-checked:border-foresty peer-checked:bg-emerald-50 peer-checked:text-foresty peer-focus-visible:ring-2 peer-focus-visible:ring-foresty">
                        <x-content.icon :name="$icon" /> {{ $text }}
                    </span>
                </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500">{{ $explain[$kind] ?? '' }}</p>

        @if ($kind === 'page')
            <div class="relative">
                <x-content.icon name="search" class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
                <input type="search" wire:model.live.debounce.300ms="pageQuery" placeholder="Cari judul halaman (min. 2 huruf)…" class="{{ $field }} pl-8" autocomplete="off" aria-label="Cari halaman">
            </div>
            @if (count($pageResults) > 0)
                <ul class="divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-200 bg-white text-sm" data-nav-page-results>
                    @foreach ($pageResults as $r)
                        <li wire:key="pg-{{ $r['id'] }}">
                            <button type="button" wire:click="pickPage({{ $r['id'] }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-emerald-50 focus:bg-emerald-50 focus:outline-none">
                                <span class="flex min-w-0 items-center gap-2"><x-content.icon name="file" class="text-gray-400" /><span class="truncate">{{ $r['label'] }}</span></span>
                                <span class="shrink-0 text-xs text-gray-400">{{ $r['hint'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
            <p class="flex items-center gap-1.5 text-xs text-gray-600" data-nav-picked>
                Dipilih:
                @if ($ref !== '')
                    <code class="rounded bg-white px-1.5 py-0.5 font-mono">/{{ $ref }}</code>
                @else
                    <span class="text-gray-400">belum ada</span>
                @endif
            </p>
        @elseif ($kind === 'category')
            <select wire:model.live="ref" class="{{ $field }}" aria-label="Kategori">
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
            <input type="text" wire:model.live.debounce.400ms="ref" placeholder="{{ $placeholders[$kind] }}" aria-label="Tujuan tautan" class="{{ $field }} font-mono text-xs">
        @endif
        @error('ref')<p class="flex items-center gap-1.5 text-xs text-red-600"><x-content.icon name="alert" class="h-3.5 w-3.5" /> {{ $message }}</p>@enderror

        @if (count($preview) > 0)
            <div class="rounded-xl bg-white p-3 text-xs text-gray-600 shadow-sm" data-nav-preview>
                <p class="mb-1 flex items-center gap-1.5 font-semibold text-gray-700"><x-content.icon name="eye" class="h-3.5 w-3.5" /> Yang dilihat pembaca</p>
                @foreach ($preview as $loc => $address)
                    <p class="break-all font-mono" wire:key="pv-{{ $loc }}"><b class="mr-1 font-sans text-gray-400">{{ strtoupper($loc) }}</b>{{ $address }}</p>
                @endforeach
            </div>
        @endif
    </div>

    <label class="relative flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700">
        <input type="checkbox" wire:model="is_active" class="peer sr-only">
        <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition peer-checked:bg-foresty peer-focus-visible:ring-2 peer-focus-visible:ring-foresty peer-focus-visible:ring-offset-2 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5" aria-hidden="true"></span>
        Tampilkan di situs
    </label>

    <div class="flex items-center gap-2 border-t border-foresty/10 pt-3">
        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center gap-1.5 rounded-xl bg-foresty px-5 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#043b2c] focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty focus-visible:ring-offset-2 disabled:opacity-50">
            <x-content.icon name="save" />
            <span wire:loading.remove wire:target="save">{{ $editId ? 'Simpan perubahan' : 'Tambahkan' }}</span>
            <span wire:loading wire:target="save">Menyimpan…</span>
        </button>
        <button type="button" wire:click="resetForm" class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-foresty">Batal</button>
    </div>
</form>
