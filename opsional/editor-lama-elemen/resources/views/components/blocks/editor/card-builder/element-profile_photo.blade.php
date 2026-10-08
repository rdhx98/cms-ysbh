@php
  $base = $elPath . '.data.style.';
@endphp

<div class="mb-3 flex items-center justify-between">
  <span class="rounded bg-indigo-100 px-2 py-0.5 text-[10px] font-extrabold tracking-widest text-indigo-700 uppercase">Foto Profil</span>
</div>

<div class="mb-3 flex items-start gap-3">
  <div class="flex-1 space-y-2">
    <button
      type="button"
      x-on:click="$dispatch('openFileManager', {
        targetEvent: 'mediaSelected',
        targetComponentId: '{{ $elPath }}.data.content',
        allowedFileType: 'image'
      })"
      class="hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition-colors"
    >
      <x-dynamic-component component="lucide-folder-search" class="h-4 w-4 shrink-0" />
      <span>Jelajahi File Manager</span>
    </button>
    <input
      type="text"
      wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.alt"
      placeholder="Teks Alternatif (Untuk SEO & Tunanetra)"
      class="focus:ring-foresty focus:border-foresty w-full rounded-lg border-gray-200 p-2 text-xs shadow-sm"
    />
  </div>
</div>

<div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
  <x-editor.segmented
    label="Ukuran"
    compact
    :path="$base . 'size'"
    default="w-16 h-16 md:w-20 md:h-20"
    :options="[
      ['value' => 'w-10 h-10 md:w-12 md:h-12', 'title' => 'Standar', 'square' => 'h-2.5 w-2.5'],
      ['value' => 'w-16 h-16 md:w-20 md:h-20', 'title' => 'Sedang', 'square' => 'h-3.5 w-3.5'],
      ['value' => 'w-24 h-24 md:w-32 md:h-32', 'title' => 'Besar', 'square' => 'h-4 w-4'],
      ['value' => 'w-32 h-32 md:w-48 md:h-48', 'title' => 'Paling Besar', 'square' => 'h-5 w-5'],
    ]"
  />
  <x-editor.segmented
    label="Bentuk"
    compact
    :path="$base . 'radius'"
    default="rounded-full"
    :options="[
      ['value' => 'rounded-md', 'title' => 'Kotak', 'shape' => 'rounded-md'],
      ['value' => 'rounded-[20px]', 'title' => 'Agak Bulat', 'shape' => 'rounded-[8px]'],
      ['value' => 'rounded-full', 'title' => 'Lingkaran', 'shape' => 'rounded-full'],
    ]"
  />
  <x-editor.segmented
    label="Garis Tepi"
    :path="$base . 'border'"
    default="border-0"
    :options="['border-0' => 'Tanpa Garis', 'border-2' => 'Tipis (2px)', 'border-4' => 'Tebal (4px)']"
  />
  <x-editor.segmented
    label="Warna Garis"
    :path="$base . 'border_color'"
    default="border-transparent"
    :options="[
      ['value' => 'border-transparent', 'title' => 'Transparan', 'slash' => true],
      ['value' => 'border-foresty', 'title' => 'Foresty', 'dot' => 'bg-foresty'],
      ['value' => 'border-coral', 'title' => 'Coral', 'dot' => 'bg-coral'],
      ['value' => 'border-gray-200', 'title' => 'Abu-abu', 'dot' => 'bg-gray-200', 'dot_ring' => true],
    ]"
  />
</div>
