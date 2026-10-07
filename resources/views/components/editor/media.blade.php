{{-- <x-editor.media> — tombol File Manager. path/rel menunjuk ke objek konten ({url, media_id, alt_text}). --}}
@props(['path' => null, 'rel' => null,
  'relExpr' => null, 'label' => 'Jelajahi File Manager', 'accept' => 'image'])

<div x-data="wireField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, null, true)" {{ $attributes }}>
  <button
    type="button"
    x-on:click="p && $dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: p, allowedFileType: @js($accept) })"
    class="hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition-colors"
  >
    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z" /></svg>
    <span>{{ $label }}</span>
  </button>
</div>
