@php
  $accTheme = $style['theme'] ?? 'foresty';
@endphp
<div class="mb-2 flex items-center justify-between">
  <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded bg-blue-100 text-blue-700">FAQ / Akordion</span>
</div>

{{-- <!-- Input Pertanyaan -->
<input
  type="text"
  wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.question.{{ $code }}"
  placeholder="Tulis pertanyaan di sini..."
  class="focus:ring-blue-500 mb-2 w-full rounded-lg border-gray-200 p-2 text-sm font-bold shadow-sm"
/>

<!-- Input Jawaban -->
<textarea
  rows="3"
  wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.answer.{{ $code }}"
  placeholder="Tulis jawaban di sini..."
  class="focus:ring-blue-500 mb-2 p-2 w-full resize-none rounded-lg border-gray-200 text-sm shadow-sm"
></textarea> --}}
{{-- Input Pertanyaan & Jawaban (Multi-Language) --}}
<div class="mb-3 space-y-2">
  {{-- Input Pertanyaan --}}
  <div class="relative">
    <x-dynamic-component component="lucide-message-circle-question" class="absolute top-2.5 left-3 h-4 w-4 text-gray-400" />
    <input
      type="text"
      wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.question.{{ $code }}"
      placeholder="Ketik pertanyaan di sini ({{ strtoupper($code) }})..."
      class="focus:ring-foresty focus:border-foresty w-full rounded-lg border-gray-200 p-2 pl-9 text-xs font-bold shadow-sm"
    />
  </div>

  {{-- Input Jawaban --}}
  <div class="relative">
    <x-dynamic-component component="lucide-message-square-text" class="absolute top-3 left-3 h-4 w-4 text-gray-400" />
    <textarea
      rows="3"
      wire:model.live.debounce.1000ms="{{ $elPath }}.data.content.answer.{{ $code }}"
      placeholder="Ketik jawaban lengkap di sini ({{ strtoupper($code) }})..."
      class="focus:ring-foresty focus:border-foresty w-full resize-none rounded-lg border-gray-200 p-2 pl-9 text-xs shadow-sm"
    ></textarea>
  </div>
</div>

<div class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3">
  {{-- Warna Aksen (Untuk teks aktif & latar ikon) --}}
  <div class="flex flex-col gap-1.5">
    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Warna Aksen Teks & Ikon</span>
    <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner w-fit">
      <button type="button" title="Foresty" x-on:click="$wire.set('{{ $elPath }}.data.style.theme', 'foresty')" class="rounded p-1.5 transition-all outline-none {{ $accTheme === 'foresty' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-emerald-700 shadow-sm"></div>
      </button>
      <button type="button" title="Coral" x-on:click="$wire.set('{{ $elPath }}.data.style.theme', 'coral')" class="rounded p-1.5 transition-all outline-none {{ $accTheme === 'coral' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-orange-500 shadow-sm"></div>
      </button>
      <button type="button" title="Gelap" x-on:click="$wire.set('{{ $elPath }}.data.style.theme', 'dark')" class="rounded p-1.5 transition-all outline-none {{ $accTheme === 'dark' ? 'bg-white shadow-sm ring-1 ring-gray-200' : 'hover:bg-gray-200' }}">
        <div class="h-4 w-4 rounded-full bg-gray-800 shadow-sm"></div>
      </button>
    </div>
  </div>
</div>