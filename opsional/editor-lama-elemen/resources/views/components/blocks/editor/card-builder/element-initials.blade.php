@php
  $base = $elPath . '.data.style.';
  $borders = ['border-0' => '0', 'border' => '1', 'border-2' => '2', 'border-4' => '4'];
@endphp

<div class="flex flex-col gap-3">
  <div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
    <div class="flex flex-col gap-1.5">
      <span class="text-xxs font-bold text-gray-700 uppercase">Inisial</span>
      <input
        type="text"
        maxlength="3"
        wire:model.live.debounce.300ms="{{ $elPath }}.data.content.text"
        placeholder="Contoh: JD"
        class="focus:ring-foresty focus:border-foresty w-22 rounded-md border border-gray-200 p-1.5 text-sm font-bold uppercase shadow-sm placeholder:text-xs"
      />
    </div>

    <x-editor.segmented
      label="Ukuran"
      compact
      :path="$base . 'size'"
      default="w-16 h-16 md:w-20 md:h-20 text-xl md:text-2xl"
      :options="[
        ['value' => 'w-10 h-10 md:w-12 md:h-12 text-sm md:text-base', 'title' => 'Standar', 'html' => '<span class=&quot;text-[10px] font-bold&quot;>A</span>'],
        ['value' => 'w-16 h-16 md:w-20 md:h-20 text-xl md:text-2xl', 'title' => 'Sedang', 'html' => '<span class=&quot;text-xs font-bold&quot;>A</span>'],
        ['value' => 'w-24 h-24 md:w-32 md:h-32 text-3xl md:text-5xl', 'title' => 'Besar', 'html' => '<span class=&quot;text-sm font-bold&quot;>A</span>'],
        ['value' => 'w-32 h-32 md:w-48 md:h-48 text-5xl md:text-7xl', 'title' => 'Paling Besar', 'html' => '<span class=&quot;text-base font-bold&quot;>A</span>'],
      ]"
    />

    <x-editor.segmented
      label="Bentuk"
      compact
      :path="$base . 'radius'"
      default="rounded-full"
      :options="[
        ['value' => 'rounded-md', 'title' => 'Kotak', 'shape' => 'rounded-sm'],
        ['value' => 'rounded-[20px]', 'title' => 'Agak Bulat', 'shape' => 'rounded-md'],
        ['value' => 'rounded-full', 'title' => 'Lingkaran', 'shape' => 'rounded-full'],
      ]"
    />

    <x-editor.swatches label="Warna Latar" :path="$base . 'bg_color'" default="bg-emerald-700" mode="bg" :options="config('cms.design.mini_bg_colors')" />
    <x-editor.swatches label="Warna Teks" :path="$base . 'text_color'" default="text-white" mode="text" :options="config('cms.design.text_colors')" />
    <x-editor.segmented label="Garis Tepi" :path="$base . 'border'" default="border-0" :options="$borders" />
    <x-editor.swatches label="Warna Tepian" :path="$base . 'border_color'" default="border-transparent" mode="preview" :options="config('cms.design.avatar_border_colors')" />
  </div>
</div>
