{{--
  <x-content.thumbnail-picker> — thumbnail artikel (rilis 40), di panel "Halaman".
  Pilihan utama: gambar yang SUDAH dipasang di isi artikel (dimuat saat panel dibuka lewat $wire.thumbnailChoices(), isi yang belum disimpan ikut).
  Bila tidak ada gambar di artikel, atau ingin yang lain: File Manager (hanya gambar). Nilai tersimpan: ID media di kolom featured_image.
  featured: ['kind' => none|media|legacy|missing, 'id', 'url', 'name'] dari builder->featuredPreview (dihitung server, bukan dari browser).
--}}
@props(['featured' => [], 'editable' => true])

@php
  $kind = $featured['kind'] ?? 'none';
  $url = (string) ($featured['url'] ?? '');
  $name = (string) ($featured['name'] ?? '');
  $section = 'text-[10px] font-extrabold tracking-widest text-gray-400 uppercase';
  $btn = 'rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-[11px] font-bold text-gray-600 shadow-sm transition-colors hover:border-foresty hover:bg-sage-soft hover:text-foresty disabled:cursor-not-allowed disabled:opacity-40';
@endphp

<div x-data="thumbnailPicker" class="flex flex-col gap-2" data-thumbnail>
  <span class="{{ $section }}">Thumbnail</span>

  <div class="relative aspect-video w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-100" data-thumbnail-preview>
    @if ($url !== '')
      <img src="{{ $url }}" alt="" class="h-full w-full object-cover" loading="lazy" data-thumbnail-img />
    @else
      <div class="flex h-full w-full flex-col items-center justify-center gap-1 px-3 text-center text-[11px] text-gray-400">
        @if ($kind === 'missing')
          <span class="font-semibold text-amber-600">Gambar thumbnail tidak ditemukan (mungkin sudah dihapus).</span>
        @elseif ($kind === 'legacy')
          <span class="font-semibold text-gray-500">Thumbnail lama</span>
          <span class="max-w-full truncate">{{ $name }}</span>
        @else
          <span>Belum ada thumbnail</span>
        @endif
      </div>
    @endif
    @if ($kind === 'media' && $name !== '')
      <span class="absolute inset-x-0 bottom-0 truncate bg-black/55 px-2 py-0.5 text-[10px] text-white">{{ $name }}</span>
    @endif
  </div>

  @if ($editable)
    <div class="flex flex-wrap items-center gap-1.5">
      <button type="button" x-on:click="toggle()" x-bind:aria-expanded="open" class="{{ $btn }}" data-thumbnail-open>Pilih dari gambar artikel</button>
      <button type="button" x-on:click="browse()" class="{{ $btn }}" data-thumbnail-browse>File Manager</button>
      @if ($kind !== 'none')
        <button type="button" x-on:click="clear()" class="rounded-md px-2 py-1.5 text-[11px] font-bold text-red-500 hover:bg-red-50" data-thumbnail-clear>Hapus</button>
      @endif
    </div>

    <div x-show="open" x-cloak class="rounded-lg border border-gray-200 bg-white p-2" data-thumbnail-panel>
      <p x-show="loading" class="px-1 py-2 text-[11px] text-gray-400">Memuat gambar artikel…</p>

      <div x-show="! loading && choices.length" x-cloak class="grid grid-cols-3 gap-1.5" data-thumbnail-choices>
        <template x-for="c in choices" x-bind:key="c.id">
          <button
            type="button"
            x-on:click="pick(c)"
            x-bind:title="c.name"
            x-bind:class="selected === c.id ? 'ring-2 ring-foresty ring-offset-1' : 'hover:ring-2 hover:ring-gray-300'"
            class="aspect-square overflow-hidden rounded-md bg-gray-100 outline-none"
            data-thumbnail-choice
          >
            <img x-bind:src="c.url" x-bind:alt="c.alt || c.name" class="h-full w-full object-cover" loading="lazy" />
          </button>
        </template>
      </div>

      <div x-show="! loading && ! choices.length" x-cloak class="flex flex-col gap-2 p-1" data-thumbnail-empty>
        <p class="text-[11px] leading-relaxed text-gray-500" x-text="failed ? 'Gambar artikel tidak bisa dimuat. Pilih dari File Manager.' : 'Belum ada gambar di isi artikel. Pilih dari File Manager.'"></p>
        <button type="button" x-on:click="browse()" class="{{ $btn }} self-start">Jelajahi File Manager</button>
      </div>
    </div>
  @endif
</div>
