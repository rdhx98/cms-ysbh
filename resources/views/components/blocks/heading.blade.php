@props(['blockId', 'code', 'block'])

<div id="block-wrapper-{{ $blockId }}" class="space-y-2">
    <div class="flex items-center justify-between">
        <label class="block text-[10px] font-semibold text-foresty uppercase">Judul / Heading ({{ strtoupper($code) }})</label>

        {{-- Opsional: Jika Anda ingin mempertahankan pilihan Level H2 / H3 --}}
        <select wire:model="content.{{ $blockId }}.data.level" class="text-xs border-gray-200 rounded-md py-0.5 px-2 bg-gray-50 text-gray-600">
            <option value="h1">Heading 1</option>
            <option value="h2">Heading 2</option>
            <option value="h3">Heading 3</option>
        </select>
    </div>

    {{-- Mengganti input text lama dengan Tiptap --}}
    <x-tiptap :block-type="$block['type']" wire:model="content.{{ $blockId }}.data.text.{{ $code }}"  placeholder="Blok Heading..." />
</div>
