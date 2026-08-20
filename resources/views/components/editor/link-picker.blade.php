@props(['urlModel', 'typeModel'])

<div class="flex rounded-md shadow-sm">
    <!-- Dropdown Tipe (Internal/Eksternal) -->
    <select wire:model="{{ $typeModel }}" class="rounded-l-md border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-xs py-1.5 pl-2 pr-6 focus:ring-foresty focus:border-foresty">
        <option value="internal">Internal (Route/Slug)</option>
        <option value="external">Eksternal (URL)</option>
        <option value="section">Bagian (#id)</option>
    </select>
    
    <!-- Input Tautan -->
    <input type="text" wire:model="{{ $urlModel }}" 
           placeholder="Misal: /program atau https://..." 
           class="flex-1 min-w-0 block w-full rounded-none rounded-r-md border-gray-300 text-xs py-1.5 focus:ring-foresty focus:border-foresty">
</div>