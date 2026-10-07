{{--
  <x-editor.media> — tombol File Manager. path/rel menunjuk ke objek konten ({url, media_id, alt_text}).
  accept = jenis berkas yang ditawarkan File Manager ('image' bawaan). accept = '' -> TANPA filter (semua berkas, mis. dokumen) dan
  nama berkas yang dipilih ditampilkan di bawah tombol.
--}}
@props ([
  "path" => null,
  "rel" => null,
  "relExpr" => null,
  "label" => "Jelajahi File Manager",
  "accept" => "image"
])

@php
  // Objek detail event. allowedFileType HANYA dikirim bila accept tidak kosong (kosong = semua berkas).
  // Disusun di PHP: @if yang menempel pada kata ("p@if") tidak dikenali Blade sebagai direktif.
  $detail =
    "{ targetEvent: 'mediaSelected', targetComponentId: p" .
    ($accept !== ""
      ? ", allowedFileType: " . \Illuminate\Support\Js::from($accept)
      : "") .
    " }";
@endphp

<div
  x-data="wireField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, null, true)"
  {{ $attributes }}
>
  <button
    type="button"
    x-on:click="p && $dispatch('openFileManager', {!! $detail !!})"
    class="hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition-colors"
  >
    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z" /></svg>
    <span>{{ $label }}</span>
  </button>

  @if ($accept === "")
    <p
      x-show="p && $wire.$get(p + '.url')"
      x-cloak
      class="mt-1.5 truncate rounded-lg bg-gray-50 px-2.5 py-1.5 text-[11px] text-gray-600"
      x-text="
        String($wire.$get(p + '.url') || '')
          .split('/')
          .pop()
      "
    ></p>
  @endif
</div>
