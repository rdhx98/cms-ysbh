{{-- BENJAMIN BUTTONS V2 DENGAN SISTEM TAB TERPISAH (VERSI MIKRO) --}}
<div
  x-data="{ expanded: false, activeTab: 'format' }"
  class="flex w-full flex-col rounded-t-lg border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800/50"
>
  {{-- ================= BARIS 1: MENU TAB NAVIGASI ================= --}}
  <div
    class="flex shrink-0 scrollbar-none items-center gap-1 overflow-x-auto border-b border-zinc-200 bg-white px-2 pt-1.5 dark:border-zinc-700 dark:bg-zinc-900/40"
  >
    <button
      type="button"
      @click="activeTab = 'format'"
      :class="activeTab === 'format'
        ? 'bg-zinc-200 dark:bg-zinc-800 text-foresty shadow-xs font-semibold'
        : 'text-zinc-600 hover:bg-zinc-200/60'"
      class="cursor-pointer rounded-t-md px-3 py-2 text-xs whitespace-nowrap transition-colors"
    >
      Teks
    </button>

    <button
      type="button"
      @click="activeTab = 'layout'"
      :class="activeTab === 'layout'
        ? 'bg-zinc-200 dark:bg-zinc-800 text-foresty shadow-xs font-semibold'
        : 'text-zinc-600 hover:bg-zinc-200/60'"
      class="cursor-pointer rounded-t-md px-3 py-2 text-xs whitespace-nowrap transition-colors"
    >
      Format
    </button>
  </div>

  {{-- ================= BARIS 2: KONTEN TOOLS & AKSI KANAN ================= --}}
  <div class="flex min-h-14 w-full items-center justify-between px-2 md:px-6">
    {{-- Area Tombol Tools --}}
    <div
      x-data="{ isMobile: window.matchMedia('(pointer: coarse)').matches }"
      @resize.window.debounce.100ms="
        isMobile = window.matchMedia('(pointer: coarse)').matches
      "
      :class="isMobile
        ? expanded
          ? 'flex-wrap max-h-[45vh] overflow-y-auto py-2'
          : 'flex-nowrap overflow-x-auto scrollbar-none [&::-webkit-scrollbar]:hidden py-2'
        : expanded
          ? 'flex-wrap max-h-[45vh] overflow-y-auto py-2'
          : 'md:flex-wrap md:overflow-visible items-center py-1.5'"
      class="flex flex-1 gap-1.5 scroll-smooth transition-all"
    >
      {{-- TAB 1: FORMAT & TEKS --}}
      <div
        x-show="activeTab === 'format'"
        class="flex flex-wrap items-center gap-1.5"
      >
        {{-- BOLD | ITALIC | STRIKE | UNDERLINE --}}
        <div class="flex shrink-0 items-center gap-1">
          <x-buttons.toolbar
            command="toggleBold"
            activeName="bold"
            title="Tebal (Ctrl+B)"
            icon="bold"
          />
          <x-buttons.toolbar
            command="toggleItalic"
            activeName="italic"
            title="Miring (Ctrl+I)"
            icon="italic"
          />
          <x-buttons.toolbar
            command="toggleStrike"
            activeName="strike"
            title="Coretan (Ctrl+Shift+X)"
            icon="strikethrough"
          />
          <x-buttons.toolbar
            command="toggleUnderline"
            activeName="underline"
            title="Garis Bawah (Ctrl+U)"
            icon="underline"
          />
        </div>

        {{-- FONT FAMILY --}}
        {{-- <div class="hidden md:flex items-center gap-4 p-1 bg-gray-50 border-l border-gray-200 shrink-0 rounded">
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
        </div> --}}
        {{-- FONT FAMILY --}}
        <div
          class="hidden shrink-0 items-center gap-4 rounded border-l border-gray-200 bg-gray-50 p-1 md:flex"
        >
          <select
            id="font-family-select"
            :value="getCurrentFont()"
            @change="changeFontFamily($event.target.value)"
            class="focus:ring-forest block w-48 cursor-pointer rounded border border-gray-300 bg-white px-3 py-1 text-sm shadow-sm transition-colors focus:ring-1 focus:outline-none"
          >
            {{-- 🌟 1. Opsi Default yang Cerdas (Bisa berubah labelnya sesuai tipe blok) --}}
            <option
              value="default"
              x-text="'Bawaan Blok (' + baseFontFamily + ')'"
            ></option>

            {{-- 🌟 2. Opsi Font Eksplisit (Agar bisa dipaksakan kapan saja) --}}
            <option
              value="Plus Jakarta Sans"
              style="font-family: &quot;Plus Jakarta Sans&quot;, sans-serif"
            >
              Plus Jakarta Sans
            </option>
            <option value="Arial" style="font-family: Arial, sans-serif">
              Arial
            </option>
            <option value="Fraunces" style="font-family: Fraunces, sans-serif">
              Fraunces
            </option>

            {{-- Perbaikan Huruf B besar pada JetBrains Mono agar dikenali Tiptap --}}
            <option
              value="JetBrains Mono"
              style="font-family: &quot;JetBrains Mono&quot;, monospace"
            >
              JetBrains Mono
            </option>

            <option
              value="Open Sans"
              style="font-family: &quot;Open Sans&quot;, sans-serif"
            >
              Open Sans
            </option>
            <option
              value="Roboto"
              style="font-family: &quot;Roboto&quot;, sans-serif"
            >
              Roboto
            </option>
            <option
              value="Times New Roman"
              style="font-family: &quot;Times New Roman&quot;, serif"
            >
              Times New Roman
            </option>
          </select>
        </div>

        {{-- FONT SIZES --}}
        <div class="relative flex items-center">
          <select
            @change="setFontSize($event.target.value)"
            :value="getCurrentFontSize()"
            class="bg-sage-soft focus:border-forest cursor-pointer rounded-md border-zinc-200 py-1 pr-6 pl-2 text-xs text-zinc-700 shadow-sm transition-colors focus:ring-0 md:text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300"
          >
            {{-- 🌟 Opsi Bawaan Blok (Mengikuti kelas Tailwind dari PHP) --}}
            <option value="default" x-text="labelUkuran"></option>

            {{-- 🌟 Skala Tipografi Dinamis --}}
            <option value="clamp(0.875rem, 1vw, 1rem)">Kecil</option>
            <option value="clamp(1rem, 1.5vw, 1.125rem)">
              Paragraf - Normal
            </option>
            <option value="clamp(1.125rem, 2vw, 1.375rem)">
              Lead - Teks Besar
            </option>
            <option value="clamp(1.5rem, 2.5vw, 2rem)">H3 - Sub-Judul</option>
            <option value="clamp(1.8rem, 3vw, 2.5rem)">H2 - Judul</option>
            <option value="clamp(2.5rem, 5vw, 4rem)">
              H1 - Judul Utama Raksasa
            </option>
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
        <div
          x-data="{ openWeightMenu: false }"
          class="relative flex items-center"
        >
          <!-- Tombol Pemicu Dropdown Ketebalan -->
          <button
            type="button"
            @click="openWeightMenu = !openWeightMenu"
            class="flex h-9 cursor-pointer items-center gap-1 rounded border border-transparent bg-zinc-50 p-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-zinc-200"
            title="Ketebalan Teks (Font Weight)"
          >
            <span class="font-bold">B</span>
            <span class="text-[10px] text-zinc-400">▼</span>
          </button>

          <!-- Menu Dropdown Pilihan Ketebalan -->
          <div
            x-show="openWeightMenu"
            @click.away="openWeightMenu = false"
            style="display: none"
            class="absolute top-full left-0 z-[99] mt-1 flex w-36 flex-col gap-1 rounded-xl border border-zinc-200 bg-white p-2 shadow-lg"
          >
            <button
              type="button"
              @click="
                setFontWeight('default');
                openWeightMenu = false;
              "
              class="rounded px-3 py-1.5 text-left text-xs font-normal text-zinc-700 hover:bg-zinc-100"
            >
              Bawaan Blok
            </button>
            <button
              type="button"
              @click="
                setFontWeight('300');
                openWeightMenu = false;
              "
              class="rounded px-3 py-1.5 text-left text-xs font-light text-zinc-300 hover:bg-zinc-100"
            >
              Light (300)
            </button>
            <button
              type="button"
              @click="
                setFontWeight('400');
                openWeightMenu = false;
              "
              class="rounded px-3 py-1.5 text-left text-xs font-normal text-zinc-700 hover:bg-zinc-100"
            >
              Regular (400)
            </button>
            <button
              type="button"
              @click="
                setFontWeight('500');
                openWeightMenu = false;
              "
              class="rounded px-3 py-1.5 text-left text-xs font-medium text-zinc-700 hover:bg-zinc-100"
            >
              Medium (500)
            </button>
            <button
              type="button"
              @click="
                setFontWeight('600');
                openWeightMenu = false;
              "
              class="rounded px-3 py-1.5 text-left text-xs font-semibold text-zinc-800 hover:bg-zinc-100"
            >
              Semi-Bold (600)
            </button>
            <button
              type="button"
              @click="
                setFontWeight('700');
                openWeightMenu = false;
              "
              class="rounded px-3 py-1.5 text-left text-xs font-bold text-zinc-900 hover:bg-zinc-100"
            >
              Bold (700)
            </button>
            <button
              type="button"
              @click="
                setFontWeight('900');
                openWeightMenu = false;
              "
              class="rounded px-3 py-1.5 text-left text-xs font-black text-black hover:bg-zinc-100"
            >
              Black (900)
            </button>
          </div>
        </div>

        {{-- COLOR PICKER --}}
        <div
          x-data="{ openColorMenu: false }"
          class="relative ml-1 flex items-center border-l border-zinc-200 pl-2"
        >
          <button
            type="button"
            @click="openColorMenu = !openColorMenu"
            class="flex h-9 cursor-pointer items-center gap-2 rounded border border-transparent bg-zinc-50 p-1.5 text-gray-700 shadow-sm transition hover:bg-zinc-200"
            title="Warna Teks"
          >
            <x-dynamic-component
              component="lucide-palette"
              class="h-4 w-4"
              stroke-width="2.5"
            />
            <div
              class="h-6 w-6 rounded border border-zinc-300 shadow-inner transition-colors"
              {{-- :style="updatedAt && getEditor()?.getAttributes('textStyle').color ? { backgroundColor: getEditor()?.getAttributes('textStyle').color } : { backgroundColor: '#18181b' }" --}}
              :style="{ backgroundColor: getCurrentColor() }"
            ></div>
          </button>

          <div
            x-show="openColorMenu"
            @click.away="openColorMenu = false"
            style="display: none"
            class="absolute top-full left-0 z-[99] mt-1 flex w-48 flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-lg"
          >
            {{-- DEFAULT COLOR --}}
            <div>
              <span
                class="mb-2 block text-[11px] font-bold tracking-wider text-zinc-400 uppercase"
                >Warna Brand</span
              >
              <div class="flex gap-2">
                <button
                  type="button"
                  @click="
                    runCommand('setColor', '#000000');
                    openColorMenu = false;
                  "
                  class="hover:border-coral-muted h-6 w-6 cursor-pointer rounded-full bg-[#000000] shadow-lg transition-transform hover:scale-110 hover:border"
                  title="Black"
                ></button>
                <button
                  type="button"
                  @click="
                    runCommand('setColor', '#FFFFFF');
                    openColorMenu = false;
                  "
                  class="h-6 w-6 cursor-pointer rounded-full bg-[#FFFFFF] shadow-lg transition-transform hover:scale-110 hover:border hover:border-gray-600"
                  title="White"
                ></button>
                <button
                  type="button"
                  @click="
                    runCommand('setColor', '#064F3B');
                    openColorMenu = false;
                  "
                  class="hover:border-coral-muted h-6 w-6 cursor-pointer rounded-full bg-[#064F3B] shadow-lg transition-transform hover:scale-110 hover:border"
                  title="Forest"
                ></button>
                <button
                  type="button"
                  @click="
                    runCommand('setColor', '#EBCC26');
                    openColorMenu = false;
                  "
                  class="h-6 w-6 cursor-pointer rounded-full bg-[#EBCC26] shadow-lg transition-transform hover:scale-110 hover:border hover:border-gray-600"
                  title="Gold"
                ></button>
                <button
                  type="button"
                  @click="
                    runCommand('setColor', '#E42326');
                    openColorMenu = false;
                  "
                  class="h-6 w-6 cursor-pointer rounded-full bg-[#E42326] shadow-lg transition-transform hover:scale-110 hover:border hover:border-gray-600"
                  title="Coral"
                ></button>
              </div>
            </div>
            <hr class="border-zinc-100" />
            <div>
              <span
                class="mb-2 block text-[11px] font-bold tracking-wider text-zinc-400 uppercase"
                >Warna Bebas</span
              >
              <input
                type="color"
                :value="getCurrentColor()"
                @input="runCommand('setColor', $event.target.value)"
                class="h-8 w-full cursor-pointer rounded border-0 bg-transparent p-0"
              />
            </div>
            <hr class="border-zinc-100" />
            <button
              type="button"
              @click="
                runCommand('unsetColor');
                openColorMenu = false;
              "
              class="-mx-1 flex items-center gap-2 rounded-md px-2 py-2 text-left text-sm font-medium text-red-600 transition-colors hover:bg-red-50 hover:text-red-700"
              title="Kembalikan ke warna default"
            >
              <x-dynamic-component
                component="lucide-eraser"
                class="h-4 w-4"
                stroke-width="2.5"
              />
              <span>Hapus Warna</span>
            </button>
          </div>
        </div>
      </div>

      {{-- TAB 2: MEDIA & TAUTAN --}}
      <div
        x-show="activeTab === 'layout'"
        class="flex flex-wrap items-center gap-1.5"
        style="display: none"
      >
        <div
          x-show="!single"
          class="flex shrink-0 items-center gap-1 md:border-zinc-300 md:pl-2 md:dark:border-zinc-700"
        >
          <x-buttons.toolbar
            command="setTextAlign"
            activeName="left"
            activeParams="{ textAlign: 'left' }"
            activeType="textAlign"
            title="Rata Kiri"
            icon="align-left"
          />
          <x-buttons.toolbar
            command="setTextAlign"
            activeName="center"
            activeParams="{ textAlign: 'center' }"
            activeType="textAlign"
            title="Rata Tengah"
            icon="align-center"
          />
          <x-buttons.toolbar
            command="setTextAlign"
            activeName="right"
            activeParams="{ textAlign: 'right' }"
            activeType="textAlign"
            title="Rata Kanan"
            icon="align-right"
          />
          <x-buttons.toolbar
            command="setTextAlign"
            activeName="justify"
            activeParams="{ textAlign: 'justify' }"
            activeType="textAlign"
            title="Rata Kiri Kanan"
            icon="align-justify"
          />
          <x-buttons.toolbar
            command="toggleIndent"
            activeName="paragraph"
            activeParams="{ indent: true }"
            activeType="default"
            title="Menjorokkan Baris (Tab)"
            icon="list-indent-increase"
          />
        </div>

        <!-- DIVIDER -->
        <div
          x-show="!single"
          class="mx-0.5 h-5 w-px shrink-0 bg-zinc-300 dark:bg-zinc-600"
        ></div>

        {{-- LISTS --}}
        <div x-show="!single" class="flex shrink-0 items-center gap-1">
          <x-buttons.toolbar
            command="toggleBulletList"
            activeName="bulletList"
            activeParams="{}"
            activeType="default"
            title="Bullet list"
            icon="list"
          />
          <x-buttons.toolbar
            command="toggleTaskList"
            activeName="taskList"
            title="Daftar Tugas"
            icon="list-todo"
          />
          <x-buttons.toolbar
            command="none"
            activeName="number"
            activeParams="{ listStyle: 'number' }"
            activeType="orderedList"
            title="Daftar Angka"
            icon="list-tree"
          >
            <span class="ml-0.5 text-[10px] font-bold">1.</span>
          </x-buttons.toolbar>
          <x-buttons.toolbar
            command="none"
            activeName="alpha"
            activeParams="{ listStyle: 'alpha' }"
            activeType="orderedList"
            title="Daftar Kapital"
            icon="list-tree"
          >
            <span class="ml-0.5 text-[10px] font-bold">A.</span>
          </x-buttons.toolbar>

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
        <div
          x-show="!single"
          class="mx-0.5 h-5 w-px shrink-0 bg-zinc-300 dark:bg-zinc-600"
        ></div>

        {{-- QUOTES & CODE --}}
        <div x-show="!single" class="flex shrink-0 items-center gap-1">
          <x-buttons.toolbar
            command="toggleBlockquote"
            activeName="blockquote"
            title="Kutipan"
            icon="quote"
          />
          <x-buttons.toolbar
            command="toggleCodeBlock"
            activeName="codeBlock"
            title="Blok Kode"
            icon="code-xml"
          />
        </div>
        <div class="flex shrink-0 items-center gap-1">
          <button
            type="button"
            @click="openInternalLinkModal()"
            :class="checkButtonActive('link', {}, 'default')
              ? 'bg-sage-soft text-forest font-semibold shadow-sm'
              : 'text-gray-600'"
            class="hover:bg-sage-soft hover:text-forest flex h-9 min-w-9 cursor-pointer items-center justify-center gap-2 rounded border border-transparent p-1.5 text-xs transition"
          >
            <x-dynamic-component
              :component="'lucide-link'"
              class="h-4 w-4"
              stroke-width="2"
            />
            Tautan
          </button>
        </div>
      </div>
    </div>

    {{-- ================= AKSI KANAN TOOLBAR (Fullscreen Dihapus) ================= --}}
    <div
      class="z-10 flex shrink-0 items-center gap-1.5 border-l border-zinc-200 bg-zinc-50 py-1.5 pl-3 dark:border-zinc-700 dark:bg-zinc-800"
    >
      <button
        type="button"
        @click="expanded = !expanded"
        class="rounded-lg border border-zinc-200 bg-white p-1.5 text-zinc-600 shadow-sm md:hidden dark:bg-zinc-800"
      >
        <x-dynamic-component
          x-show="!expanded"
          :component="'lucide-chevron-up'"
          class="h-5 w-5"
          stroke-width="2.5"
        />
        <x-dynamic-component
          x-show="expanded"
          :component="'lucide-chevron-down'"
          class="h-5 w-5"
          stroke-width="2.5"
          style="display: none"
        />
      </button>
    </div>
  </div>
</div>
