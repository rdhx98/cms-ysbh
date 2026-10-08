{{--
  <x-editor.link> — pemilih tautan: jenis (halaman, artikel, berkas, URL, telepon, surel, anchor) + tujuannya.
  Nilai di state: { kind, ref, ref_label, media_id, url }. Halaman/artikel disimpan sebagai ID (ref), bukan slug, supaya mengganti slug
  tidak mematahkan tombol. Berkas: media_id (diisi File Manager). Keamanan URL ditegakkan di SERVER (LinkResolver / BlockSanitizer).
--}}
@props([
  'path' => null,
  'rel' => null,
  'relExpr' => null,
  'label' => 'Tautan',
  'live' => null,
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
  $kinds = ['page' => 'Halaman', 'article' => 'Artikel', 'file' => 'Berkas', 'url' => 'URL luar', 'tel' => 'Telepon', 'mailto' => 'Surel', 'anchor' => 'Anchor (#)'];
  $input = 'focus:border-foresty focus:ring-foresty w-full rounded-lg border-gray-200 p-2 text-xs shadow-sm';
@endphp

<div x-data="linkField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, @js($isLive))" {{ $attributes->class('flex flex-col gap-1.5') }}>
  <span class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</span>

  <select x-bind:value="kind" x-on:change="setKind($event.target.value)" aria-label="Jenis tautan" class="hover:border-foresty w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-[11px] shadow-sm focus:outline-none">
    @foreach ($kinds as $value => $name)
      <option value="{{ $value }}">{{ $name }}</option>
    @endforeach
  </select>

  {{-- Halaman / Artikel: cari judul, simpan ID --}}
  <div x-show="kind === 'page' || kind === 'article'" x-cloak class="relative flex flex-col gap-1.5">
    <div x-show="refId" x-cloak class="bg-sage-soft text-foresty flex items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
      <span class="truncate" x-text="refLabel"></span>
      <button type="button" aria-label="Lepas pilihan" x-on:click="clearRef()" class="rounded px-1 leading-none hover:bg-black/10">×</button>
    </div>
    <input
      x-show="! refId"
      type="text"
      x-model="q"
      x-on:input.debounce.250ms="search()"
      x-on:focus="open = true"
      x-on:keydown.escape="open = false"
      x-on:blur="setTimeout(() => open = false, 150)"
      autocomplete="off"
      x-bind:placeholder="kind === 'page' ? 'Cari judul halaman…' : 'Cari judul artikel…'"
      class="{{ $input }}"
    />
    <div x-show="open && ! refId && (results.length || (q.trim().length >= 2 && ! searching))" x-cloak class="absolute top-full z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
      <template x-for="r in results" x-bind:key="r.id">
        <button type="button" x-on:mousedown.prevent="pick(r)" class="hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-xs text-gray-600">
          <span class="truncate" x-text="r.label"></span>
          <span class="shrink-0 text-[9px] font-bold uppercase" x-bind:class="(r.hint === 'online' || r.hint === 'published') ? 'text-emerald-600' : 'text-amber-600'" x-text="r.hint"></span>
        </button>
      </template>
      <p x-show="! results.length && ! searching" class="px-3 py-2 text-xs text-gray-400">Tidak ada yang cocok.</p>
    </div>
    <p x-show="refId" x-cloak class="text-[10px] leading-relaxed text-gray-400">Tombol hanya tampil bila tujuannya sudah <span class="font-semibold">online / terbit</span>.</p>
  </div>

  {{-- Berkas: dipilih lewat File Manager --}}
  <div x-show="kind === 'file'" x-cloak class="flex flex-col gap-1.5">
    <button type="button" x-on:click="pickFile()" class="hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition-colors">
      <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z" /></svg>
      <span x-text="mediaId ? 'Ganti berkas' : 'Pilih berkas'"></span>
    </button>
    <p x-show="mediaId" x-cloak class="truncate rounded-lg bg-gray-50 px-2.5 py-1.5 text-[11px] text-gray-600" x-text="fileName"></p>
  </div>

  {{-- URL / telepon / surel / anchor: diketik --}}
  <div x-show="['url', 'tel', 'mailto', 'anchor'].includes(kind)" x-cloak class="flex flex-col gap-1">
    <input
      type="text"
      x-bind:value="refText"
      x-on:input="typeAt('ref', $event.target.value)"
      autocomplete="off"
      spellcheck="false"
      x-bind:placeholder="{ url: 'https://… atau /halaman', tel: '+62 812-3456-7890', mailto: 'nama@ysbh.org', anchor: 'nama-bagian' }[kind]"
      class="{{ $input }}"
    />
    <p x-show="kind === 'url' && urlWarning" x-cloak class="text-[10px] font-semibold text-amber-600" x-text="urlWarning"></p>
  </div>
</div>
