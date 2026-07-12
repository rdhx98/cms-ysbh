{{-- BENJAMIN BUTTONS V2 --}}
<div x-data="{ expanded: false }" class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50 w-full">
    <div class="px-2 md:px-6 flex items-start md:items-center w-full">
        <div class="flex items-start md:items-center w-full">
            <div x-data="{ isMobile: window.matchMedia('(pointer: coarse)').matches }"
                @resize.window.debounce.100ms="isMobile = window.matchMedia('(pointer: coarse)').matches"
                :class="isMobile
                    ?
                    (expanded ? 'flex-wrap max-h-[45vh] overflow-y-auto' :
                        'flex-nowrap overflow-x-auto scrollbar-none [&::-webkit-scrollbar]:hidden') :
                    (expanded ? 'flex-wrap max-h-[45vh] overflow-y-auto' :
                        'md:flex-wrap md:overflow-visible items-center')"
                class="flex flex-1 gap-1.5 p-2 transition-all scroll-smooth">

                {{-- BOLD | ITALIC | STRIKE | UNDERLINE --}}
                <div class="flex items-center gap-1 shrink-0">
                    <x-layouts::app.editor-toolbar-btn command="toggleBold" activeName="bold"
                        title="Tebal (Ctrl+B)" icon="bold" />
                    <x-layouts::app.editor-toolbar-btn command="toggleItalic" activeName="italic"
                        title="Miring (Ctrl+I)" icon="italic" />
                    <x-layouts::app.editor-toolbar-btn command="toggleStrike" activeName="strike"
                        title="Coretan (Ctrl+⇑+X)" icon="strikethrough" />
                    <x-layouts::app.editor-toolbar-btn command="toggleUnderline"
                        activeName="underline" title="Garis Bawah (Ctrl+U)" icon="underline" />
                </div>

                {{-- FONT FAMILY (Disembunyikan di Mobile agar bersih) --}}
                <div
                    class="hidden md:flex items-center gap-4 p-1 bg-gray-50 border-l border-gray-200 shrink-0 rounded">
                    <select id="font-family-select" :value="getCurrentFont()"
                        @change="changeFontFamily($event.target.value)"
                        class="block w-48 px-3 py-1 text-sm bg-white border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-1 focus:ring-forest transition-colors">
                        <option value="default" style="font-family: 'Plus Jakarta Sans', sans-serif;">  Plus Jakarta Sans (Default)</option>
                        <option value="Arial" style="font-family: Arial, sans-serif;">Arial</option>
                        <option value="Fraunces" style="font-family: Fraunces, sans-serif;">Fraunces</option>
                        <option value="Jetbrains Mono" style="font-family: 'JetBrains Mono', monospace;">JetBrains Mono</option>
                        <option value="Open Sans" style="font-family: 'Open Sans', sans-serif;">Open  Sans</option>
                        <option value="Roboto" style="font-family: 'Roboto', sans-serif;">Roboto </option>
                        <option value="Times New Roman" style="font-family: 'Times New Roman', serif;">Times New Roman</option>
                    </select>
                </div>

                {{-- GRUP WARNA TEKS (CUSTOM ALPINE DROPDOWN) --}}
                <div x-data="{ openColorMenu: false }" class="relative flex items-center border-l border-zinc-200 pl-2 ml-1">
                    
                    {{-- Tombol Pemicu Menu (Ikon + Kotak Warna) --}}
                    <button type="button" 
                        @click="openColorMenu = !openColorMenu"
                        class="flex items-center gap-2 p-1.5 h-9 transition rounded cursor-pointer hover:bg-zinc-200 text-gray-700 bg-zinc-50 shadow-sm border border-transparent"
                        title="Warna Teks">
                        
                        <x-dynamic-component component="lucide-palette" class="h-4 w-4" stroke-width="2.5" />

                        <div class="w-6 h-6 rounded border border-zinc-300 shadow-inner transition-colors"
                            :style="updatedAt && window.tiptapEditor?.getAttributes('textStyle').color ? { backgroundColor: window.tiptapEditor.getAttributes('textStyle').color } : { backgroundColor: '#18181b' }">
                        </div>
                    </button>

                    {{-- Panel Menu (Muncul saat tombol diklik) --}}
                    <div x-show="openColorMenu" 
                        @click.away="openColorMenu = false"
                        style="display: none;"
                        class="absolute top-full left-0 mt-1 bg-white border border-zinc-200 shadow-lg rounded-xl p-3 z-50 flex flex-col gap-3 w-48">
                        
                        {{-- Sesi 1: Warna Default --}}
                        <div>
                            <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-2 block">Warna Brand</span>
                            <div class="flex gap-2">
                                <button type="button" @click="runCommand('setColor', '#064F3B'); openColorMenu = false" class="w-6 h-6 rounded-full bg-[#064F3B] hover:scale-110 transition-transform shadow-sm" title="Forest"></button>
                                <button type="button" @click="runCommand('setColor', '#EBCC26'); openColorMenu = false" class="w-6 h-6 rounded-full bg-[#EBCC26] hover:scale-110 transition-transform shadow-sm" title="Gold"></button>
                                <button type="button" @click="runCommand('setColor', '#E42326'); openColorMenu = false" class="w-6 h-6 rounded-full bg-[#E42326] hover:scale-110 transition-transform shadow-sm" title="Coral"></button>
                            </div>
                        </div>

                        <hr class="border-zinc-100">

                        {{-- Sesi 2: Color Picker Bebas --}}
                        <div>
                            <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-2 block">Warna Bebas</span>
                            <input type="color" @input="runCommand('setColor', $event.target.value)" class="w-full h-8 p-0 border-0 rounded cursor-pointer bg-transparent">
                        </div>

                        <hr class="border-zinc-100">

                        {{-- 🌟 Sesi 3: Tombol Hapus Warna (Pindah ke Dalam) --}}
                        <button type="button" 
                            @click="runCommand('unsetColor'); openColorMenu = false" 
                            class="flex items-center gap-2 px-2 py-2 -mx-1 text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700 rounded-md transition-colors text-left"
                            title="Kembalikan ke warna default">
                            <x-dynamic-component component="lucide-eraser" class="h-4 w-4" stroke-width="2.5" />
                            <span>Hapus Warna</span>
                        </button>
                    </div>
                </div>

                {{-- PILCROW --}}
                <div class="shrink-0 flex items-center">
                    <x-layouts::app.editor-toolbar-btn command="toggleHiddenMarks()"
                        activeName="showMarks" activeParams="{}" activeType="alpine"
                        title="Tampilkan Tanda Baca Terselubung" icon="pilcrow" />
                </div>

                {{-- INDENTATION --}}
                <div
                    class="flex items-center gap-1 md:border-l md:border-zinc-300 md:dark:border-zinc-700 md:pl-2 shrink-0">
                    <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="left"
                        activeParams="{ textAlign: 'left' }" activeType="textAlign" title="Rata Kiri"
                        icon="align-left" />
                    <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="center"
                        activeParams="{ textAlign: 'center' }" activeType="textAlign"
                        title="Rata Tengah" icon="align-center" />
                    <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="right"
                        activeParams="{ textAlign: 'right' }" activeType="textAlign"
                        title="Rata Kanan" icon="align-right" />
                    <x-layouts::app.editor-toolbar-btn command="setTextAlign" activeName="justify"
                        activeParams="{ textAlign: 'justify' }" activeType="textAlign"
                        title="Rata Kiri Kanan" icon="align-justify" />
                    <x-layouts::app.editor-toolbar-btn command="toggleIndent" activeName="paragraph"
                        activeParams="{ indent: true }" activeType="default"
                        title="Menjorokkan Baris (Tab)" icon="list-indent-increase" />
                </div>

                <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                {{-- HEADINGs --}}
                <div class="flex items-center gap-1 shrink-0">
                    <x-layouts::app.editor-toolbar-btn command="toggleHeading" activeName="1"
                        activeParams="{ level: 1 }" activeType="heading" title="Heading 1"
                        icon="heading-1" />
                    <x-layouts::app.editor-toolbar-btn command="toggleHeading" activeName="2"
                        activeParams="{ level: 2 }" activeType="heading" title="Heading 2"
                        icon="heading-2" />
                    <x-layouts::app.editor-toolbar-btn command="toggleHeading" activeName="3"
                        activeParams="{ level: 3 }" activeType="heading" title="Heading 3"
                        icon="heading-3" />
                </div>

                <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                {{-- LISTS --}}
                <div class="flex items-center gap-1 shrink-0">
                    <x-layouts::app.editor-toolbar-btn command="toggleBulletList" activeName=""
                        activeParams="{}" activeType="heading" title="Bullet list" icon="list" />
                    <x-layouts::app.editor-toolbar-btn command="toggleTaskList" activeName="taskList"
                        title="Daftar Tugas" icon="list-todo" />
                    <x-layouts::app.editor-toolbar-btn command="none" activeName="number"
                        activeParams="{ listStyle: 'number' }" activeType="orderedList"
                        title="Daftar Angka" icon="list-tree">
                        <span class="text-[10px] font-bold ml-0.5">1.</span>
                    </x-layouts::app.editor-toolbar-btn>
                    <x-layouts::app.editor-toolbar-btn command="none" activeName="alpha"
                        activeParams="{ listStyle: 'alpha' }" activeType="orderedList"
                        title="Daftar Kapital" icon="list-tree">
                        <span class="text-[10px] font-bold ml-0.5">A.</span>
                    </x-layouts::app.editor-toolbar-btn>
                    <x-layouts::app.editor-toolbar-btn command="toggleEyebrow" activeName="eyebrow" title="Eyebrow (Teks Konteks)" icon="circle-small" />
                </div>

                <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                {{-- QUOTES --}}
                <div class="flex items-center gap-1 shrink-0">
                    <x-layouts::app.editor-toolbar-btn command="toggleBlockquote"
                        activeName="blockquote" title="Kutipan" icon="quote" />
                    <x-layouts::app.editor-toolbar-btn command="toggleCodeBlock"
                        activeName="codeBlock" title="Blok Kode" icon="code-xml" />
                </div>

                {{-- TABLES DO NOT REMOVED --}}
                {{--
                    <button type="button" @click="runCommand('insertTable')"
                        class="px-2.5 py-1.5 rounded text-xs cursor-pointer text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">📊
                        +Table</button>

                    <template x-if="isActive('table', {}, updatedAt)">
                        <div class="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded ml-2">
                            <button type="button" @click="runCommand('addColumnAfter')"
                                class="px-1.5 py-0.5 text-[10px] bg-white dark:bg-zinc-600 border border-zinc-300 rounded hover:bg-zinc-50">+Col</button>
                            <button type="button" @click="runCommand('addRowAfter')"
                                class="px-1.5 py-0.5 text-[10px] bg-white dark:bg-zinc-600 border border-zinc-300 rounded hover:bg-zinc-50">+Row</button>
                            <button type="button" @click="runCommand('deleteTable')"
                                class="px-1.5 py-0.5 text-[10px] bg-red-500 text-white rounded hover:bg-red-600">Hapus</button>
                        </div>
                    </template>
                    --}}
                <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                {{-- MEDIA & LINK --}}
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" @click="openLinkModal(); $dispatch('buka-modal-link');"
                        :disabled="isUploading"
                        :class="checkButtonActive('link', {}, 'default') ?
                            'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </button>
                    <button type="button" @click="insertMediaPlaceholder()" :disabled="isUploading"
                        :class="checkButtonActive('mediaPlaceholder', {}, 'default') ?
                            'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                        <x-dynamic-component :component="'lucide-image-plus'" class="h-4 w-4" stroke-width="2" />
                        
                    </button>
                    
                </div>
                
                <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                {{-- LAYOUTS --}}
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" @click="runCommand('insertManualColumns')"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50"
                        title="Tambahkan Grid Layout">
                        <x-dynamic-component :component="'lucide-grid-2x2-plus'" class="h-4 w-4" stroke-width="2" />
                    </button>
                    <button type="button" @click="runCommand('setSectionBlock')"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50"
                        title="Tambahkan Kartu Langkah">
                        <x-dynamic-component :component="'lucide-layers-plus'" class="h-4 w-4" stroke-width="2" />
                    </button>
                    <button type="button" @click="runCommand('insertStepCard')"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50"
                        title="Tambahkan Blok Seksi">
                        <x-dynamic-component :component="'lucide-rectangle-ellipsis'" class="h-4 w-4" stroke-width="2" />
                    </button>
                    {{-- <button type="button" @click="runCommand('setInfoCard')"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50"
                        title="Tambahkan Blok Seksi">
                        <x-dynamic-component :component="'lucide-rectangle-ellipsis'" class="h-4 w-4" stroke-width="2" />
                    </button> --}}

                    <x-layouts::app.editor-toolbar-btn command="setInfoCard" activeName="setInfoCard" title="Kartu Info" icon="code-xml" />
                </div>  

                {{-- NOTIFY  BUTTONs --}}
                {{-- <div class="flex items-center gap-1 shrink-0">
                    <button type="button" @click="notifyTheUser('putangina','warning');" :disabled="isUploading"
                        :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                        <x-dynamic-component :component="'lucide-bell'" class="h-5 w-5" stroke-width="2.5" />
                    </button>
                    <button type="button" @click="notifyTheUser('karena sesungguhnya kemerdekaan itu adalah hak segala bangsa','info');" :disabled="isUploading"
                        :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                        <x-dynamic-component :component="'lucide-bell'" class="h-5 w-5" stroke-width="2.5" />
                    </button>
                    <button type="button" @click="notifyTheUser('faggotron assemble','success');" :disabled="isUploading"
                        :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                        <x-dynamic-component :component="'lucide-bell'" class="h-5 w-5" stroke-width="2.5" />
                    </button>
                    <button type="button" @click="notifyTheUser('i luv musc','error');" :disabled="isUploading"
                        :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                        <x-dynamic-component :component="'lucide-bell'" class="h-5 w-5" stroke-width="2.5" />
                    </button>
                </div> 
                --}}
            </div>

            {{-- ================= AKSI KANAN TOOLBAR ================= --}}
            <div
                class="flex items-center gap-1.5 p-2 pl-3 border-l border-zinc-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-800 z-10 shadow-[-4px_0_10px_rgba(0,0,0,0.02)] md:shadow-none">

                {{-- 🌟 TOMBOL LAYAR PENUH (Sekarang aman di dalam scope editor) --}}
                <button type="button" @click="isFullscreen = !isFullscreen"
                    class="p-1.5 text-zinc-500 hover:text-forest dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors cursor-pointer"
                    title="Layar Penuh">
                    <x-dynamic-component x-show="!isFullscreen" :component="'lucide-maximize'"
                        class="h-5 w-5 md:h-4 md:w-4" stroke-width="2" />
                    <x-dynamic-component x-show="isFullscreen" :component="'lucide-minimize'"
                        class="h-5 w-5 md:h-4 md:w-4" stroke-width="2" style="display: none;" />
                </button>

                {{-- TOMBOL EXPAND (KHUSUS HP) --}}
                <button type="button" @click="expanded = !expanded"
                    class="md:hidden p-1.5 bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors cursor-pointer">
                    <x-dynamic-component x-show="!expanded" :component="'lucide-chevron-up'" class="h-5 w-5"
                        stroke-width="2.5" />
                    <x-dynamic-component x-show="expanded" :component="'lucide-chevron-down'" class="h-5 w-5"
                        stroke-width="2.5" style="display: none;" />
                </button>

            </div>

            {{-- TOMBOL EXPAND (MENU PANEL NAIK DARI BAWAH KHUSUS HP) --}}
            {{-- <button type="button" @click="expanded = !expanded"
            class="md:hidden m-2 p-1.5 bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 shrink-0 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
            <x-dynamic-component x-show="!expanded" :component="'lucide-chevron-up'" class="h-5 w-5" stroke-width="2.5" />
            <x-dynamic-component x-show="expanded" :component="'lucide-chevron-down'" class="h-5 w-5" stroke-width="2.5" style="display: none;" />
        </button> --}}
        </div>
    </div>
</div>