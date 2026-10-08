@php
  $base = $elPath . '.data.style.';
@endphp

<div class="flex flex-wrap items-start justify-start gap-4 rounded-lg border border-gray-100 bg-gray-50 p-2">
  <x-editor.icon-picker :path="$elPath . '.data.content.icon'" default="box" />

  <x-editor.segmented
    label="Latar Ikon"
    :path="$base . 'bg'"
    default="bg-goldy-soft"
    :options="[
      ['value' => 'bg-goldy-soft', 'title' => 'Goldy', 'dot' => 'bg-goldy-soft', 'dot_ring' => true],
      ['value' => 'bg-mist', 'title' => 'Mist', 'dot' => 'bg-mist', 'dot_ring' => true],
      ['value' => 'bg-transparent', 'title' => 'Transparan', 'slash' => true],
    ]"
  />

  <x-editor.segmented
    label="Warna Ikon"
    :path="$base . 'color'"
    default="text-foresty"
    :options="[
      ['value' => 'text-foresty', 'title' => 'Foresty', 'dot' => 'bg-foresty'],
      ['value' => 'text-coral', 'title' => 'Coral', 'dot' => 'bg-coral'],
    ]"
  />

  <x-editor.segmented
    label="Ukuran"
    compact
    :path="$base . 'size'"
    default="w-10 h-10 md:w-12 md:h-12"
    :options="[
      ['value' => 'w-10 h-10 md:w-12 md:h-12', 'title' => 'Standar', 'square' => 'h-2.5 w-2.5'],
      ['value' => 'w-16 h-16 md:w-20 md:h-20', 'title' => 'Sedang', 'square' => 'h-3.5 w-3.5'],
      ['value' => 'w-24 h-24 md:w-32 md:h-32', 'title' => 'Besar', 'square' => 'h-4 w-4'],
    ]"
  />

  <x-editor.segmented
    label="Sudut Ikon"
    compact
    :path="$base . 'radius'"
    default="rounded-[14px]"
    :options="[
      ['value' => 'rounded-[14px]', 'title' => 'Agak Bulat', 'shape' => 'rounded-md'],
      ['value' => 'rounded-full', 'title' => 'Lingkaran', 'shape' => 'rounded-full'],
    ]"
  />
</div>
