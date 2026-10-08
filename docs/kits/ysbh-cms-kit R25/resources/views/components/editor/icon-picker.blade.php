{{-- <x-editor.icon-picker> — tombol pemicu. Daftar ikon: <x-editor.icon-picker-modal /> (sekali per halaman). path ATAU rel. --}}
@props([
  'path' => null,
  'rel' => null,
  'relExpr' => null,
  'label' => 'Ikon',
  'placeholder' => 'PILIH IKON...',
  'default' => '',
  'live' => null,
  'prefix' => 'icon-',
  'clearable' => false, // tampilkan tombol untuk mengosongkan ikon (ikon bersifat opsional)
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
@endphp

<div x-data="wireField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, @js($default), @js($isLive))" {{ $attributes->class('relative flex flex-col gap-1.5') }}>
  <label class="text-xxs bg-foresty w-fit rounded px-2 py-0.5 font-bold text-white uppercase">{{ $label }}</label>

  <button
    type="button"
    x-on:click="p && $dispatch('open-icon-picker', { path: p, live: live })"
    class="hover:border-foresty flex w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs shadow-sm transition-colors focus:outline-none"
  >
    <span class="flex items-center gap-2 truncate">
      <svg class="text-foresty h-4 w-4 shrink-0" stroke-width="2.5" x-show="v || {{ $clearable ? 'false' : 'true' }}"><use x-bind:href="'#{{ $prefix }}' + (v || 'box')"></use></svg>
      <span class="truncate font-mono text-[11px] font-bold text-gray-700 uppercase" x-text="v || @js($placeholder)"></span>
    </span>
    <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" /></svg>
  </button>

  @if ($clearable)
    <button type="button" x-show="v" x-cloak x-on:click="set('')" class="self-start text-[10px] font-semibold text-gray-400 underline hover:text-red-500">Hapus ikon</button>
  @endif
</div>
