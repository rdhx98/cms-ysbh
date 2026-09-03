{{-- BENJAMIN BUTTONS V2 DENGAN SISTEM TAB TERPISAH (VERSI MIKRO) --}}
<div x-data="{ expanded: false, activeTab: 'format' }" class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50 w-full flex flex-col rounded-t-lg">

    {{-- ================= BARIS 1: MENU TAB NAVIGASI ================= --}}
    <div class="flex items-center gap-1 overflow-x-auto scrollbar-none border-b border-zinc-200 dark:border-zinc-700 px-2 pt-1.5 shrink-0 bg-white dark:bg-zinc-900/40">

        <button type="button" @click="activeTab = 'format'"
            :class="activeTab === 'format' ? 'bg-zinc-200 dark:bg-zinc-800 text-foresty shadow-xs font-semibold' : 'text-zinc-600 hover:bg-zinc-200/60'"
            class="px-3 py-2 text-xs rounded-t-md transition-colors whitespace-nowrap cursor-pointer">
            Teks
        </button>

        <button type="button" @click="activeTab = 'layout'"
            :class="activeTab === 'layout' ? 'bg-zinc-200 dark:bg-zinc-800 text-foresty shadow-xs font-semibold' : 'text-zinc-600 hover:bg-zinc-200/60'"
            class="px-3 py-2 text-xs rounded-t-md transition-colors whitespace-nowrap cursor-pointer">
            Format
        </button>
    </div>

    {{-- ================= BARIS 2: KONTEN TOOLS & AKSI KANAN ================= --}}
    <div class="flex items-center justify-between w-full px-2 md:px-6 min-h-14">

        {{-- Area Tombol Tools --}}
        <div x-data="{ isMobile: window.matchMedia('(pointer: coarse)').matches }"
            @resize.window.debounce.100ms="isMobile = window.matchMedia('(pointer: coarse)').matches"
            :class="isMobile
                ? (expanded ? 'flex-wrap max-h-[45vh] overflow-y-auto py-2' : 'flex-nowrap overflow-x-auto scrollbar-none [&::-webkit-scrollbar]:hidden py-2')
                : (expanded ? 'flex-wrap max-h-[45vh] overflow-y-auto py-2' : 'md:flex-wrap md:overflow-visible items-center py-1.5')"
            class="flex flex-1 gap-1.5 transition-all scroll-smooth">

            {{-- TAB 1: FORMAT & TEKS --}}
            <div x-show="activeTab === 'format'" class="flex items-center gap-1.5 flex-wrap">

                {{-- BOLD | ITALIC | STRIKE | UNDERLINE --}}
                <div class="flex items-center gap-1 shrink-0">
                    <x-buttons.toolbar command="toggleBold" activeName="bold" title="Tebal (Ctrl+B)" icon="bold" />
                    <x-buttons.toolbar command="toggleItalic" activeName="italic" title="Miring (Ctrl+I)" icon="italic" />
                    <x-buttons.toolbar command="toggleStrike" activeName="strike" title="Coretan (Ctrl+Shift+X)" icon="strikethrough" />
                    <x-buttons.toolbar command="toggleUnderline" activeName="underline" title="Garis Bawah (Ctrl+U)" icon="underline" />
                </div>

                {{-- FONT FAMILY --}}
                <div class="hidden md:flex items-center gap-4 p-1 bg-gray-50 border-l border-gray-200 shrink-0 rounded">
                    <select id="font-family-select" :value="getCurrentFont()" @change="changeFontFamily($event.target.value)"
                        class="block w-48 px-3 py-1 text-sm bg-white border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-1 focus:ring-forest transition-colors cursor-pointer">
                        <option value="default" style="font-family: 'Plus Jakarta Sans', sans-serif;">Plus Jakarta Sans (Default)</option>
                        <option value="Arial" style="font-family: Arial, sans-serif;">Arial</option>
                        <option value="Fraunces" style="font-family: Fraunces, sans-serif;">Fraunces</option>
                        <option value="Jetbrains Mono" style="font-family: 'JetBrains Mono', monospace;">JetBrains Mono</option>
                        <option value="Open Sans" style="font-family: 'Open Sans', sans-serif;">Open Sans</option>
                        <option value="Roboto" style="font-family: 'Roboto', sans-serif;">Roboto</option>
                        <option value="Times New Roman" style="font-family: 'Times New Roman', serif;">Times New Roman</option>
                    </select>
                </div>

                {{-- FONT SIZES --}}
                <div class="relative flex items-center">
                    <select @change="setFontSize($event.target.value)" 
                        :value="getCurrentFontSize()"
                        class="text-xs md:text-sm border-zinc-200 dark:border-zinc-700 bg-sage-soft dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 rounded-md py-1 pl-2 pr-6 focus:ring-0 focus:border-forest transition-colors cursor-pointer shadow-sm">
                        
                        {{-- 🌟 Opsi Bawaan Blok (Mengikuti kelas Tailwind dari PHP) --}}
                        <option value="default" x-text="labelUkuran"></option>
                        
                        {{-- 🌟 Skala Tipografi Dinamis --}}
                        <option value="clamp(0.875rem, 1vw, 1rem)">     Kecil</option>
                        <option value="clamp(1rem, 1.5vw, 1.125rem)">   Paragraf - Normal</option>
                        <option value="clamp(1.125rem, 2vw, 1.375rem)"> Lead - Teks Besar</option>
                        <option value="clamp(1.5rem, 2.5vw, 2rem)">     H3 - Sub-Judul</option>
                        <option value="clamp(1.8rem, 3vw, 2.5rem)">     H2 - Judul</option>
                        <option value="clamp(2.5rem, 5vw, 4rem)">       H1 - Judul Utama Raksasa</option>
                        
                    </select>
                    {{-- <select @change="setFontSize($event.target.value)" :value="getCurrentFontSize()"
                        class="text-xs md:text-sm border-zinc-200 dark:border-zinc-700 bg-sage-soft dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 rounded-md py-1 pl-2 pr-6 focus:ring-0 focus:border-forest transition-colors cursor-pointer shadow-sm">
                        <option value="8px">8</option>
                        <option value="10px">10</option>
                        <option value="12px">12</option>
                        <option value="13px">13</option>
                        <option value="14px">14</option>
                        <option value="16px">16</option>
                        <option value="18px">18</option>
                        <option value="20px">20</option>
                        <option value="24px">24</option>
                        <option value="28px">28</option>
                        <option value="32px">32</option>
                        <option value="36px">36</option>
                        <option value="38px">38</option>
                        <option value="42px">42</option>
                    </select> --}}
                </div>
                <div x-data="{ openWeightMenu: false }" class="relative flex items-center">
                  <!-- Tombol Pemicu Dropdown Ketebalan -->
                  <button type="button" @click="openWeightMenu = !openWeightMenu"
                      class="flex items-center gap-1 p-1.5 h-9 transition rounded cursor-pointer hover:bg-zinc-200 text-gray-700 bg-zinc-50 shadow-sm border border-transparent text-xs font-medium"
                      title="Ketebalan Teks (Font Weight)">
                      <span class="font-bold">B</span>
                      <span class="text-[10px] text-zinc-400">▼</span>
                  </button>

                  <!-- Menu Dropdown Pilihan Ketebalan -->
                  <div x-show="openWeightMenu" @click.away="openWeightMenu = false" style="display: none;"
                      class="absolute top-full left-0 mt-1 bg-white border border-zinc-200 shadow-lg rounded-xl p-2 z-[99] flex flex-col gap-1 w-36">
                      
                      <button type="button" @click="setFontWeight('default'); openWeightMenu = false" 
                          class="px-3 py-1.5 text-xs text-left rounded hover:bg-zinc-100 text-zinc-700 font-normal">
                          Bawaan Blok
                      </button>
                      <button type="button" @click="setFontWeight('300'); openWeightMenu = false" 
                          class="px-3 py-1.5 text-xs text-left rounded hover:bg-zinc-100 text-zinc-300 font-light">
                          Light (300)
                      </button>
                      <button type="button" @click="setFontWeight('400'); openWeightMenu = false" 
                          class="px-3 py-1.5 text-xs text-left rounded hover:bg-zinc-100 text-zinc-700 font-normal">
                          Regular (400)
                      </button>
                      <button type="button" @click="setFontWeight('500'); openWeightMenu = false" 
                          class="px-3 py-1.5 text-xs text-left rounded hover:bg-zinc-100 text-zinc-700 font-medium">
                          Medium (500)
                      </button>
                      <button type="button" @click="setFontWeight('600'); openWeightMenu = false" 
                          class="px-3 py-1.5 text-xs text-left rounded hover:bg-zinc-100 text-zinc-800 font-semibold">
                          Semi-Bold (600)
                      </button>
                      <button type="button" @click="setFontWeight('700'); openWeightMenu = false" 
                          class="px-3 py-1.5 text-xs text-left rounded hover:bg-zinc-100 text-zinc-900 font-bold">
                          Bold (700)
                      </button>
                      <button type="button" @click="setFontWeight('900'); openWeightMenu = false" 
                          class="px-3 py-1.5 text-xs text-left rounded hover:bg-zinc-100 text-black font-black">
                          Black (900)
                      </button>
                  </div>
              </div>

                {{-- COLOR PICKER --}}
                <div x-data="{ openColorMenu: false }" class="relative flex items-center border-l border-zinc-200 pl-2 ml-1">
                    <button type="button" @click="openColorMenu = !openColorMenu"
                        class="flex items-center gap-2 p-1.5 h-9 transition rounded cursor-pointer hover:bg-zinc-200 text-gray-700 bg-zinc-50 shadow-sm border border-transparent"
                        title="Warna Teks">
                        <x-dynamic-component component="lucide-palette" class="h-4 w-4" stroke-width="2.5" />
                        <div class="w-6 h-6 rounded border border-zinc-300 shadow-inner transition-colors"
                            {{-- :style="updatedAt && getEditor()?.getAttributes('textStyle').color ? { backgroundColor: getEditor()?.getAttributes('textStyle').color } : { backgroundColor: '#18181b' }" --}}
                            :style="{ backgroundColor: getCurrentColor() }"
                            >
                        </div>
                    </button>

                    <div x-show="openColorMenu" @click.away="openColorMenu = false" style="display: none;"
                        class="absolute top-full left-0 mt-1 bg-white border border-zinc-200 shadow-lg rounded-xl p-3 z-[99] flex flex-col gap-3 w-48">
                        {{-- DEFAULT COLOR --}}
                        <div>
                            <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-2 block">Warna Brand</span>
                            <div class="flex gap-2">
                                <button type="button" @click="runCommand('setColor', '#000000'); openColorMenu = false" class="w-6 h-6 cursor-pointer hover:border hover:border-coral-muted rounded-full bg-[#000000] hover:scale-110 transition-transform shadow-lg" title="Black"></button>
                                <button type="button" @click="runCommand('setColor', '#FFFFFF'); openColorMenu = false" class="w-6 h-6 cursor-pointer hover:border hover:border-gray-600 rounded-full bg-[#FFFFFF] hover:scale-110 transition-transform shadow-lg" title="White"></button>
                                <button type="button" @click="runCommand('setColor', '#064F3B'); openColorMenu = false" class="w-6 h-6 cursor-pointer hover:border hover:border-coral-muted rounded-full bg-[#064F3B] hover:scale-110 transition-transform shadow-lg" title="Forest"></button>
                                <button type="button" @click="runCommand('setColor', '#EBCC26'); openColorMenu = false" class="w-6 h-6 cursor-pointer hover:border hover:border-gray-600 rounded-full bg-[#EBCC26] hover:scale-110 transition-transform shadow-lg" title="Gold"></button>
                                <button type="button" @click="runCommand('setColor', '#E42326'); openColorMenu = false" class="w-6 h-6 cursor-pointer hover:border hover:border-gray-600 rounded-full bg-[#E42326] hover:scale-110 transition-transform shadow-lg" title="Coral"></button>
                            </div>
                        </div>
                        <hr class="border-zinc-100">
                        <div>
                            <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-2 block">Warna Bebas</span>
                            <input 
                                type="color" 
                                :value="getCurrentColor()"
                                @input="runCommand('setColor', $event.target.value)" 
                                class="w-full h-8 p-0 border-0 rounded cursor-pointer bg-transparent">
                        </div>
                        <hr class="border-zinc-100">
                        <button type="button" @click="runCommand('unsetColor'); openColorMenu = false"
                            class="flex items-center gap-2 px-2 py-2 -mx-1 text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700 rounded-md transition-colors text-left"
                            title="Kembalikan ke warna default">
                            <x-dynamic-component component="lucide-eraser" class="h-4 w-4" stroke-width="2.5" />
                            <span>Hapus Warna</span>
                        </button>
                    </div>
                </div>

            </div>

            {{-- TAB 2: MEDIA & TAUTAN --}}
            <div x-show="activeTab === 'layout'" class="flex items-center gap-1.5 flex-wrap" style="display: none;">
                <div class="flex items-center gap-1 md:border-zinc-300 md:dark:border-zinc-700 md:pl-2 shrink-0">
                    <x-buttons.toolbar command="setTextAlign" activeName="left" activeParams="{ textAlign: 'left' }" activeType="textAlign" title="Rata Kiri" icon="align-left" />
                    <x-buttons.toolbar command="setTextAlign" activeName="center" activeParams="{ textAlign: 'center' }" activeType="textAlign" title="Rata Tengah" icon="align-center" />
                    <x-buttons.toolbar command="setTextAlign" activeName="right" activeParams="{ textAlign: 'right' }" activeType="textAlign" title="Rata Kanan" icon="align-right" />
                    <x-buttons.toolbar command="setTextAlign" activeName="justify" activeParams="{ textAlign: 'justify' }" activeType="textAlign" title="Rata Kiri Kanan" icon="align-justify" />
                    <x-buttons.toolbar command="toggleIndent" activeName="paragraph" activeParams="{ indent: true }" activeType="default" title="Menjorokkan Baris (Tab)" icon="list-indent-increase" />
                </div>

                <!-- DIVIDER -->
                <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                {{-- LISTS --}}
                <div class="flex items-center gap-1 shrink-0">
                    <x-buttons.toolbar command="toggleBulletList" activeName="bulletList" activeParams="{}" activeType="default" title="Bullet list" icon="list" />
                    <x-buttons.toolbar command="toggleTaskList" activeName="taskList" title="Daftar Tugas" icon="list-todo" />
                    <x-buttons.toolbar command="none" activeName="number" activeParams="{ listStyle: 'number' }" activeType="orderedList" title="Daftar Angka" icon="list-tree">
                        <span class="text-[10px] font-bold ml-0.5">1.</span>
                    </x-buttons.toolbar>
                    <x-buttons.toolbar command="none" activeName="alpha" activeParams="{ listStyle: 'alpha' }" activeType="orderedList" title="Daftar Kapital" icon="list-tree">
                        <span class="text-[10px] font-bold ml-0.5">A.</span>
                    </x-buttons.toolbar>

                    {{-- EYEBROW --}}
                    <div class="relative inline-block">
                        <button type="button" @click="toggleEyebrowIconMenu()"
                            :class="checkButtonActive('eyebrow') ? 'bg-coral-dark/10 text-coral-dark' : 'text-gray-600 hover:bg-gray-100'"
                            class="flex items-center gap-1 rounded-md px-2 py-1.5 transition-colors"
                            :aria-expanded="isEyebrowIconOpen" aria-haspopup="true" title="Eyebrow">
                            <span class="w-4 h-4 [&>svg]:w-full [&>svg]:h-full" x-html="getEyebrowIconSVG(getCurrentEyebrowIcon())"></span>
                            <svg class="w-3 h-3 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6" /></svg>
                        </button>

                        <div x-show="isEyebrowIconOpen" x-cloak x-transition.origin.top.left
                            @click.outside="isEyebrowIconOpen = false" @keydown.escape.window="isEyebrowIconOpen = false"
                            class="absolute z-20 mt-1 grid grid-cols-4 gap-2 rounded-lg border border-gray-200 bg-white p-3 shadow-lg w-max" role="menu">
                            <template x-for="item in eyebrowIcons" :key="item.key">
                                <button type="button" @click="selectEyebrowIcon(item.key)"
                                    :class="checkButtonActive('eyebrow', { icon: item.key }) ? 'bg-coral-dark/10 text-coral-dark ring-1 ring-coral-dark/30' : 'text-gray-600 hover:bg-gray-100 bg-goldy'"
                                    class="flex items-center justify-center rounded-md p-2 transition-colors [&>svg]:w-full [&>svg]:h-full w-8 h-8"
                                    :title="item.label" x-html="item.svg"></button>
                            </template>
                        </div>
                    </div>

                    {{-- PILL --}}
                    {{-- <div class="relative inline-block">
                        <button type="button" @click="togglePillColorMenu()"
                            :class="checkButtonActive('pill') ? 'bg-sage-soft text-forest' : 'text-gray-600 hover:bg-gray-100'"
                            class="flex items-center gap-1.5 rounded-md px-2 py-1.5 transition-colors"
                            :aria-expanded="isPillColorOpen" aria-haspopup="true" title="Warna Pill">
                            <span class="w-4 h-4 rounded-full border border-gray-300" :style="`background-color: ${getCurrentPillSwatch()}`"></span>
                            <svg class="w-3 h-3 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6" /></svg>
                        </button>

                        <div x-show="isPillColorOpen" x-cloak x-transition.origin.top.left
                            @click.outside="isPillColorOpen = false" @keydown.escape.window="isPillColorOpen = false"
                            class="absolute z-20 mt-1 w-56 rounded-lg border border-gray-200 bg-white p-3 shadow-lg space-y-3" role="menu">
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-1.5">Preset</p>
                                <div class="grid grid-cols-6 gap-1.5">
                                    <template x-for="preset in pillColorPresets" :key="preset.key">
                                        <button type="button" @click="selectPillPreset(preset)"
                                            class="w-6 h-6 rounded-full transition-transform hover:scale-110"
                                            :style="`background-color: ${preset.backgroundColor}; border: 1.5px solid ${preset.borderColor || 'transparent'}`"
                                            :title="preset.label"></button>
                                    </template>
                                </div>
                            </div>
                            <div class="border-t border-gray-100 pt-3 space-y-2">
                                <label class="flex items-center justify-between text-xs font-medium text-gray-500">
                                    Latar belakang
                                    <input type="color" x-model="customPillBg" @change="applyCustomPillColor()" class="w-6 h-6 rounded border border-gray-300 p-0" />
                                </label>
                                <label class="flex items-center justify-between text-xs font-medium text-gray-500">
                                    <span class="flex items-center gap-1.5">
                                        <input type="checkbox" x-model="pillBorderEnabled" @change="applyCustomPillColor()" /> Border
                                    </span>
                                    <input type="color" x-model="customPillBorder" @change="applyCustomPillColor()" :disabled="!pillBorderEnabled" class="w-6 h-6 rounded border border-gray-300 p-0 disabled:opacity-40" />
                                </label>
                            </div>
                            <button type="button" @click="removePill()"
                                class="w-full text-left text-xs font-medium text-red-600 hover:bg-red-50 rounded-md px-2 py-1.5 transition-colors">
                                Hapus Pill
                            </button>
                        </div>
                    </div> --}}
                </div>

                <!-- DIVIDER -->
                <div class="h-5 w-px bg-zinc-300 dark:bg-zinc-600 mx-0.5 shrink-0"></div>

                {{-- QUOTES & CODE --}}
                <div class="flex items-center gap-1 shrink-0">
                    <x-buttons.toolbar command="toggleBlockquote" activeName="blockquote" title="Kutipan" icon="quote" />
                    <x-buttons.toolbar command="toggleCodeBlock" activeName="codeBlock" title="Blok Kode" icon="code-xml" />
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" @click="openInternalLinkModal();"
                        :class="checkButtonActive('link', {}, 'default') ? 'bg-sage-soft text-forest font-semibold shadow-sm' : 'text-gray-600'"
                        class="p-1.5 min-w-9 h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-2 text-xs cursor-pointer border border-transparent">
                        <x-dynamic-component :component="'lucide-link'" class="h-4 w-4" stroke-width="2" />
                        Tautan
                    </button>
                </div>
            </div>

        </div>

        {{-- ================= AKSI KANAN TOOLBAR (Fullscreen Dihapus) ================= --}}
        <div class="flex items-center gap-1.5 pl-3 border-l border-zinc-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-800 z-10 py-1.5">
            <button type="button" @click="expanded = !expanded"
                class="md:hidden p-1.5 bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 text-zinc-600">
                <x-dynamic-component x-show="!expanded" :component="'lucide-chevron-up'" class="h-5 w-5" stroke-width="2.5" />
                <x-dynamic-component x-show="expanded" :component="'lucide-chevron-down'" class="h-5 w-5" stroke-width="2.5" style="display: none;" />
            </button>
        </div>

    </div>
</div>
