@php
  $base = $elPath . '.data.style.';
  $pillPath = $base . 'is_pill';
  $wireModel = config('cms.editor.defer_style_sync', false) ? 'wire:model' : 'wire:model.live';
@endphp

{{-- Mode Pill menukar kelompok kontrol di bawah — dilakukan di klien, tanpa request. --}}
<div x-data="{ pp: @js($pillPath), get pill() { return !! this.$wire.$get(this.pp) } }">
  <div class="mb-4 flex items-center justify-between">
    <span
      class="rounded px-2 py-0.5 text-[10px] font-extrabold tracking-widest uppercase"
      x-bind:class="pill ? 'bg-goldy-soft text-goldy-dark' : 'bg-foresty/10 text-foresty'"
      x-text="pill ? 'Lencana (Pill)' : 'Teks'"
    ></span>
    <label class="flex cursor-pointer items-center gap-1.5">
      <input
        type="checkbox"
        {{ $wireModel }}="{{ $pillPath }}"
        class="text-foresty focus:ring-foresty h-3.5 w-3.5 rounded border-gray-300"
      />
      <span class="text-[10px] font-bold text-gray-500 uppercase">Mode Pill</span>
    </label>
  </div>

  <!-- Loop multi-bahasa: tidak diubah -->
  <div
    class="mb-4 grid gap-4"
    :class="effectiveLayout === 'single'
      ? 'grid-cols-1'
      : splitLanguages.length >= 3
        ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
        : 'grid-cols-1 md:grid-cols-2'"
  >
    @foreach ($activeLocales as $lang)
      <div
        wire:key="text-{{ $elPath }}-{{ $lang }}"
        x-show="effectiveLayout === 'single' ? singleActiveLang === '{{ $lang }}' : splitLanguages.includes('{{ $lang }}')"
        x-cloak
      >
        <div class="mb-1.5 flex items-center gap-2">
          <span class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase shadow-sm">{{ $lang }}</span>
        </div>
        <textarea
          rows="2"
          wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.{{ $lang }}"
          placeholder="Ketik isi teks di sini..."
          class="focus:border-foresty focus:ring-foresty w-full resize-none rounded-lg border-gray-200 p-2 text-sm font-semibold shadow-sm transition-colors"
        ></textarea>
      </div>
    @endforeach
  </div>

  <div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
    <div x-show="! pill" x-cloak class="contents">
      <x-editor.select
        label="Tipe Font"
        font
        :path="$base . 'font'"
        default="font-fraunces"
        :options="config('cms.fonts')"
      />
      <x-editor.segmented
        label="Ketebalan"
        :path="$base . 'weight'"
        default="font-normal"
        :options="['font-normal' => 'Reguler', 'font-semibold' => 'Semi Bold']"
      />
      <x-editor.segmented
        label="Ukuran"
        :path="$base . 'size'"
        default="text-[13px]"
        :options="['text-[13px]' => 'Kecil', 'text-[15px]' => 'Normal', 'text-[21px]' => 'Besar (H3)']"
      />
    </div>

    <div x-show="pill" x-cloak class="contents">
      <x-editor.segmented
        label="Warna Latar Pill"
        :path="$base . 'pill_bg'"
        default="bg-goldy-soft"
        :options="[
          ['value' => 'bg-goldy-soft', 'title' => 'Goldy', 'dot' => 'bg-goldy-soft', 'dot_ring' => true],
          ['value' => 'bg-mist', 'title' => 'Mist', 'dot' => 'bg-mist', 'dot_ring' => true],
          ['value' => 'bg-sage-soft', 'title' => 'Sage', 'dot' => 'bg-sage-soft', 'dot_ring' => true],
        ]"
      />
      <x-editor.segmented
        label="Bentuk Pill"
        :path="$base . 'pill_radius'"
        default="rounded-md"
        :options="['rounded-md' => 'Bulat Sedikit', 'rounded-full' => 'Lingkaran']"
      />
    </div>

    <x-editor.segmented
      label="Warna Teks"
      :path="$base . 'color'"
      default="text-ink-soft"
      :options="[
        ['value' => 'text-ink-soft', 'title' => 'Abu Gelap', 'dot' => 'bg-ink-soft'],
        ['value' => 'text-foresty', 'title' => 'Foresty', 'dot' => 'bg-foresty'],
        ['value' => 'text-coral', 'title' => 'Coral', 'dot' => 'bg-coral'],
        ['value' => 'text-aurum', 'title' => 'Aurum', 'dot' => 'bg-aurum'],
      ]"
    />
    <x-editor.segmented
      label="Jarak Bawah"
      :path="$base . 'margin'"
      default="mb-0"
      :options="['mb-0' => '0px', 'mb-2' => 'Kecil', 'mb-4' => 'Sedang']"
    />
  </div>
</div>
