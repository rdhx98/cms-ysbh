@props(['blockId', 'code', 'block'])

<div id="block-wrapper-{{ $blockId }}" class="space-y-1">

    <label class="block text-[10px] font-semibold text-foresty uppercase">Teks Paragraf ({{ strtoupper($code) }})</label>
    <x-tiptap :block-type="$block['type']"  wire:model="content.{{ $blockId }}.data.text.{{ $code }}" placeholder="Sweet Escape....."/>
</div>
