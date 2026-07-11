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
                        <option value="default" style="font-family: 'Plus Jakarta Sans', sans-serif;">
                            Plus Jakarta Sans (Default)</option>
                        <option value="Arial" style="font-family: Arial, sans-serif;">Arial</option>
                        <option value="Jetbrains Mono"
                            style="font-family: 'JetBrains Mono', monospace;">JetBrains Mono</option>
                        <option value="Open Sans" style="font-family: 'Open Sans', sans-serif;">Open
                            Sans</option>
                        <option value="Roboto" style="font-family: 'Roboto', sans-serif;">Roboto
                        </option>
                        <option value="Times New Roman"
                            style="font-family: 'Times New Roman', serif;">Times New Roman</option>
                    </select>
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
                    {{-- <button type="button" wire:click="scanEditorImages" :disabled="isUploading" @click="$dispatch('buka-featured-modal')"
                    class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border border-transparent disabled:hover:bg-zinc-50">
                    <x-dynamic-component :component="'lucide-view'" class="h-4 w-4" stroke-width="2" />
                </button> --}}
                    <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>


                </div>

                {{-- NOTIFY  BUTTONs --}}
                <div class="flex items-center gap-1 shrink-0">
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

                <div class="flex items-center gap-1 shrink-0">

                    <div class="w-px h-6 bg-gray-300 mx-1 self-center"></div>
                    <button type="button" @click="insertStepCard()"
                        class="flex items-center gap-1 px-2 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                        title="Tambahkan Kartu Komponen/Langkah">
                        <svg class="w-4 h-4 text-forest" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"
                                ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6">
                            </line>
                            <line x1="8" y1="2" x2="8" y2="6">
                            </line>
                            <line x1="3" y1="10" x2="21" y2="10">
                            </line>
                        </svg>
                        Langkah
                    </button>

                    <button type="button" @click="insertTransferCard()"
                        class="flex items-center gap-1 px-2 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                        title="Tambahkan Kartu Rekening Donasi">
                        <svg class="w-4 h-4 text-forest" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10">
                            </line>
                        </svg>
                        Rekening
                    </button>
                    <button type="button" @click="runCommand('toggleEyebrow')"
                        class="flex items-center gap-1 px-2 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                        title="Tambahkan Eyebrow (Teks Kecil di atas Judul)">
                        <svg class="w-4 h-4 text-coral-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 3v2M12 19v2M3 12h2M19 12h2"></path>
                        </svg>
                        Eyebrow
                    </button>
                    {{-- 
                    <button type="button" @click="insertContactItem()"
                        class="flex items-center gap-1 px-2 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                        title="Tambahkan Item Kontak">
                        <svg class="w-4 h-4 text-forest" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path
                                d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.9.6 2.8a2 2 0 0 1-.5 2.1L7.9 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2Z">
                            </path>
                        </svg>
                        Kontak
                    </button> --}}
                    <button type="button" @click="insertSectionBlock()"
                        class="flex items-center gap-1 px-2 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
                        title="Tambahkan Blok Seksi ">
                        <svg class="w-4 h-4 text-forest" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path
                                d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.9.6 2.8a2 2 0 0 1-.5 2.1L7.9 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2Z">
                            </path>
                        </svg>
                        Seksi
                    </button>
                </div>
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