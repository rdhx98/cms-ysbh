<div 
    x-data="tiptap(@entangle($attributes->wire('model')))" 
    wire:ignore 
    
    class="border border-gray-200 rounded-lg bg-white overflow-hidden shadow-sm focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition-all"
>
    {{-- <!-- TOOLBAR MIKRO -->
    <div class="flex flex-wrap items-center gap-1 bg-gray-50 border-b border-gray-200 p-2 text-gray-600">
        <button type="button" @click.prevent="toggleBold()" :class="{'bg-gray-200 text-gray-900': isActive('bold'), 'hover:bg-gray-200': !isActive('bold')}" class="p-1.5 rounded font-bold text-xs px-2" title="Tebal">B</button>
        <button type="button" @click.prevent="toggleItalic()" :class="{'bg-gray-200 text-gray-900': isActive('italic'), 'hover:bg-gray-200': !isActive('italic')}" class="p-1.5 rounded italic font-serif text-xs px-2" title="Miring">I</button>
        <div class="w-px h-4 bg-gray-300 mx-1"></div>

        <div class="relative flex items-center">
            <select 
                @change="changeFontFamily($event.target.value)"
                :value="getCurrentFont()"
                class="text-xs border-zinc-200 bg-white text-zinc-700 rounded-md py-1 px-2 focus:ring-0 focus:border-forest transition-colors cursor-pointer"
            >
                <option value="default">Default Font</option>
                <option value="Arial">Arial</option>
                <option value="Fraunces">Fraunces</option>
                <option value="Jetbrains Mono">JetBrains Mono</option>
                <option value="Open Sans">Open Sans</option>
                <option value="Roboto">Roboto</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Plus Jakarta Sans">Plus Jakarta Sans</option>
            </select>
        </div>

        <div class="relative flex items-center shrink-0">
            <select
                @change="setFontSize($event.target.value)"
                :value="getCurrentFontSize()"
                class="text-xs border-zinc-200 bg-white text-zinc-700 rounded-md py-1 pl-2 pr-6 focus:ring-0 focus:border-forest transition-colors cursor-pointer shadow-sm"
            >
                <option value="8px">8</option>
                <option value="10px">10</option>
                <option value="12px">12</option>
                <option value="13px">13</option>
                <option value="14px">14</option>
                <option value="default">16 (Normal)</option>
                <option value="18px">18</option>
                <option value="20px">20</option>
                <option value="24px">24</option>
                <option value="28px">28</option>
                <option value="32px">32</option>
                <option value="36px">36</option>
                <option value="38px">38</option>
                <option value="42px">42</option>
            </select>
        </div>
        
        <!-- Tombol Link Eksternal -->
        <button type="button" @click="setLink()" :class="{'bg-gray-200 text-gray-900': isActive('link'), 'hover:bg-gray-200': !isActive('link')}" class="p-1.5 rounded text-xs font-medium px-2" title="Link URL">Link</button>
        
        <!-- 🌟 Tombol Link Internal (Pencarian Halaman) -->
        <button type="button" @click="openInternalLinkModal()" class="p-1.5 rounded text-xs font-medium px-2 text-blue-600 hover:bg-blue-50 flex items-center gap-1" title="Cari Link Internal">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round5" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            Cari Internal
        </button>

        <div class="w-px h-4 bg-gray-300 mx-1"></div>
        <button type="button" @click.prevent="toggleBulletList()" :class="{'bg-gray-200 text-gray-900': isActive('bulletList'), 'hover:bg-gray-200': !isActive('bulletList')}" class="p-1.5 rounded text-xs font-medium px-2">• List</button>
        <button type="button" @click.prevent="toggleOrderedList()" :class="{'bg-gray-200 text-gray-900': isActive('orderedList'), 'hover:bg-gray-200': !isActive('orderedList')}" class="p-1.5 rounded text-xs font-medium px-2">1. List</button>
        <div class="w-px h-4 bg-gray-300 mx-1"></div>
        <button type="button" @click.prevent="setAlignment('left')" :class="{'bg-gray-200 text-gray-900': isActive({ textAlign: 'left' }), 'hover:bg-gray-200': !isActive({ textAlign: 'left' })}" class="p-1.5 rounded text-xs font-medium px-2">Kiri</button>
        <button type="button" @click.prevent="setAlignment('center')" :class="{'bg-gray-200 text-gray-900': isActive({ textAlign: 'center' }), 'hover:bg-gray-200': !isActive({ textAlign: 'center' })}" class="p-1.5 rounded text-xs font-medium px-2">Tengah</button>
    </div> --}}
    <x-editor.toolbars />

    <!-- KANVAS MENGETIK TIPTAP -->
    <div x-ref="editorElement" class="min-h-48 bg-white"></div>

    <!-- MODAL KUSTOM URL EKSTERNAL -->
    <div x-show="showLinkModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
        <div @click.outside="cancelLink()" class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Sisipkan Tautan Eksternal</h3>
            
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">URL / Tautan (Contoh: https://google.com)</label>
                <input 
                    type="text" 
                    x-model="linkInputUrl" 
                    @keydown.enter.prevent="saveLink()"
                    placeholder="https://" 
                    class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                > <!-- Atribut autofocus dihapus di sini -->
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="cancelLink()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                    Batal
                </button>
                <button type="button" @click="saveLink()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition shadow-sm">
                    Simpan Tautan
                </button>
            </div>
        </div>
    </div>

</div>