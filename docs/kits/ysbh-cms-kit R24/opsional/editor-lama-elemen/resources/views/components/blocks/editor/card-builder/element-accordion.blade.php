<div class="mb-4 flex items-center justify-between">
  <span class="rounded bg-blue-100 px-2 py-0.5 text-[10px] font-extrabold tracking-widest text-blue-700 uppercase">FAQ / Akordion</span>
</div>

<!-- Loop multi-bahasa: tidak diubah -->
<div
  class="mb-4 grid gap-6"
  :class="effectiveLayout === 'single'
    ? 'grid-cols-1'
    : splitLanguages.length >= 3
      ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
      : 'grid-cols-1 md:grid-cols-2'"
>
  @foreach ($activeLocales as $lang)
    <div
      wire:key="acc-{{ $elPath }}-{{ $lang }}"
      x-show="effectiveLayout === 'single' ? singleActiveLang === '{{ $lang }}' : splitLanguages.includes('{{ $lang }}')"
      x-cloak
      class="space-y-2"
    >
      <div class="mb-1.5 flex items-center gap-2">
        <span class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase shadow-sm">{{ $lang }}</span>
      </div>
      <div class="relative">
        <x-dynamic-component component="lucide-message-circle-question" class="absolute top-2.5 left-3 h-4 w-4 text-gray-400" />
        <input
          type="text"
          wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.question.{{ $lang }}"
          placeholder="Ketik pertanyaan di sini..."
          class="focus:border-foresty focus:ring-foresty w-full rounded-lg border-gray-200 p-2 pl-9 text-xs font-bold shadow-sm transition-colors"
        />
      </div>
      <div class="relative">
        <x-dynamic-component component="lucide-message-square-text" class="absolute top-3 left-3 h-4 w-4 text-gray-400" />
        <textarea
          rows="3"
          wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.answer.{{ $lang }}"
          placeholder="Ketik jawaban lengkap di sini..."
          class="focus:border-foresty focus:ring-foresty w-full resize-none rounded-lg border-gray-200 p-2 pl-9 text-xs shadow-sm transition-colors"
        ></textarea>
      </div>
    </div>
  @endforeach
</div>

<div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
  <x-editor.segmented
    label="Warna Aksen Teks & Ikon"
    :path="$elPath . '.data.style.theme'"
    default="foresty"
    :options="[
      ['value' => 'foresty', 'title' => 'Foresty', 'dot' => 'bg-foresty'],
      ['value' => 'coral', 'title' => 'Coral', 'dot' => 'bg-coral'],
      ['value' => 'dark', 'title' => 'Gelap', 'dot' => 'bg-gray-800'],
    ]"
  />
</div>
