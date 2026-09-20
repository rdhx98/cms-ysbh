@props (['blockId', 'block', 'code'])

@php
  $cards = $block['data']['cards'] ?? [];
  $bgList = [
      ['value' => 'bg-goldy-soft', 'label' => 'Goldy', 'class' => 'bg-[#FDF8E1]'],
      ['value' => 'bg-mist', 'label' => 'Misty', 'class' => 'bg-[#E9F1EB]'],
      ['value' => 'bg-coral/20', 'label' => 'Coral', 'class' => 'bg-[#FBE6E6]'],
      ['value' => 'bg-gray-100', 'label' => 'Abu', 'class' => 'bg-gray-100'],
  ];
@endphp

<div
  class="group/section rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200"
  x-data="{
    isCollapsed: false,
    activeTab: 0,
    init() {
        window.blockCollapseState = window.blockCollapseState || {};
        if (window.blockCollapseState['{{ $blockId }}'] !== undefined) {
            this.isCollapsed = window.blockCollapseState['{{ $blockId }}'];
        }
        this.$watch('isCollapsed', (value) => {
            window.blockCollapseState['{{ $blockId }}'] = value;
        });
    }
}"
  @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
  @toggle-collapse-all.window="isCollapsed = $event.detail"
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }"
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }"
  @sync-active-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail"
>
  <!-- Header Editor -->
  <div
    class="flex cursor-pointer items-center justify-between bg-gray-100 p-2 transition-all duration-200 select-none group-hover:bg-white"
    :class="isCollapsed
      ? 'rounded-xl'
      : 'rounded-t-xl border-b border-gray-200'"
  >
    <div class="flex items-center gap-2">
      <button
        type="button"
        @click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
        class="hover:bg-sage-soft text-foresty rounded-full p-1 transition-all duration-200 focus:outline-none"
      >
        <x-dynamic-component
          component="lucide-circle-chevron-down"
          class="text-foresty h-5 w-5 transition-transform duration-200"
          x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'"
        />
      </button>
      <div class="bg-sage-soft rounded-md p-1">
        <x-dynamic-component
          component="lucide-layout-grid"
          class="text-forest h-4 w-4"
          stroke-width="2.5"
        />
      </div>
      <span
        class="flex items-center gap-2 text-xs font-extrabold tracking-widest text-gray-500 uppercase"
      >
        Grup Kartu
      </span>
    </div>
    <div class="flex min-w-0 flex-1 items-center justify-end gap-2">
      <span
        x-show="isCollapsed"
        x-cloak
        class="rounded-md border border-gray-200 bg-white px-1.5 py-0.5 text-[9px] font-bold tracking-normal text-gray-500 shadow-sm"
        x-text="($wire.get('content.{{ $blockId }}.data.cards') || []).length + ' Kartu'"
      >
      </span>
      <span
        class="text-foresty bg-sage-soft shrink-0 rounded px-1.5 py-0.5 text-xs font-bold uppercase shadow-sm"
      >
        {{ $code }}
      </span>
    </div>
  </div>

  <div
    x-show="!isCollapsed"
    x-collapse
    x-cloak
    class="space-y-5 rounded-b-xl bg-white p-4"
  >
    {{-- BARIS KONTROL & NAVIGASI TAB --}}
    <div
      class="flex flex-col justify-between gap-4 border-b border-gray-100 pb-4 sm:flex-row sm:items-center"
    >
      {{-- Kontrol Kolom Desktop --}}
      <div class="flex shrink-0 items-center gap-3">
        <label class="text-[10px] font-bold text-gray-400 uppercase"
          >Kolom (PC):</label
        >
        <select
          wire:model.live="content.{{ $blockId }}.data.col_count"
          class="text-forest focus:ring-forest focus:border-forest rounded border-gray-300 bg-white py-1 pr-6 pl-2 text-xs font-bold shadow-sm"
        >
          <option value="2">2 Kolom</option>
          <option value="3">3 Kolom</option>
          <option value="4">4 Kolom</option>
        </select>
      </div>

      {{-- Tab Navigasi (Ringkas) --}}
      @if (count($cards) > 0)
        <div class="flex flex-wrap items-center gap-2">
          @foreach ($cards as $index => $card)
            <button
              type="button"
              @click="activeTab = {{ $index }}; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', {{ $index }})"
              :class="activeTab === {{ $index }} ? 'bg-foresty text-white shadow-md border-transparent' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 hover:border-foresty/50'"
              class="flex shrink-0 items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-bold transition-all duration-300"
            >
              {{-- Label Angka Kartu (Misal: #01, #02) --}}
              <span>#{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>

              <div
                @click.stop="$wire.set('content.{{ $blockId }}.data.cards', $wire.get('content.{{ $blockId }}.data.cards').filter((_, i) => i !== {{ $index }})); activeTab = 0; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', 0);"
                class="ml-0.5 rounded p-0.5 transition-colors hover:bg-red-500 hover:text-white"
                :class="activeTab === {{ $index }} ? 'text-white/60 hover:bg-white/20' : 'text-gray-400'"
              >
                <x-dynamic-component component="lucide-x" class="h-3 w-3" />
              </div>
            </button>
          @endforeach
        </div>
      @endif
    </div>

    {{-- FORMULIR TAB AKTIF --}}
    {{-- <div class="min-h-[250px]">
      @foreach ($cards as $index => $card)
        <div x-show="activeTab === {{ $index }}" x-cloak wire:key="card-tab-{{ $blockId }}-{{ $index }}" class="p-4 border border-gray-100 bg-gray-50 rounded-xl">

          <div class="grid grid-cols-1 md:grid-cols-[1.5fr_1fr] gap-6">

            {{-- Kolom Teks Utama --}
            <div class="space-y-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Teks Kecil (Eyebrow)</label>
                <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.cards.{{ $index }}.eyebrow.{{ $code }}" placeholder="Misal: Kesehatan Ibu & Anak"
                  class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Judul Utama</label>
                <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.cards.{{ $index }}.title.{{ $code }}" placeholder="Judul kartu..."
                  class="text-sm font-bold border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Deskripsi / Paragraf</label>
                <textarea wire:model.live.debounce.300ms="content.{{ $blockId }}.data.cards.{{ $index }}.description.{{ $code }}" rows="3" placeholder="Tuliskan penjelasan singkat..."
                  class="text-xs border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm resize-none"></textarea>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Tautan / URL (Tombol Selengkapnya)</label>
                <div class="flex items-center gap-1">
                  <input type="text" wire:model.live="content.{{ $blockId }}.data.cards.{{ $index }}.url" class="w-full text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
                  <button type="button" @click="$dispatch('buka-modal-link', { target: 'content.{{ $blockId }}.data.cards.{{ $index }}.url' })"
                    class="p-1.5 border border-gray-200 bg-white hover:bg-sage-soft text-gray-400 hover:text-foresty rounded-md shadow-sm">
                    <x-dynamic-component component="lucide-search" class="w-4 h-4" stroke-width="2.5" />
                  </button>
                </div>
              </div>
            </div>

            {{-- Kolom Pengaturan Visual --}
            <div class="space-y-4 md:border-l border-gray-200 md:pl-6" x-data="{ localBg: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.icon_bg').live, localColor: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.icon_color').live }">
              <x-editor.icon-picker label="Ikon Kartu" model="content.{{ $blockId }}.data.cards.{{ $index }}.icon" />

              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Warna Latar Ikon</label>
                <div class="grid grid-cols-4 gap-2">
                  @foreach ($bgList as $bg)
                    <button type="button" @click="localBg = '{{ $bg['value'] }}'" class="flex flex-col items-center justify-center p-2 rounded-lg border transition-all duration-300 bg-white"
                      :class="localBg === '{{ $bg['value'] }}' ? 'ring-2 ring-foresty ring-offset-1 border-transparent shadow-sm' : 'border-gray-200 hover:border-foresty/50 hover:bg-gray-50'">
                      <span class="w-4 h-4 rounded-full {{ $bg['class'] }} mb-1 shadow-inner border border-gray-200/50"></span>
                      <span class="text-[9px] font-bold text-gray-700">{{ $bg['label'] }}</span>
                    </button>
                  @endforeach
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Warna Ikon & Eyebrow (Hex)</label>
                <div class="flex items-center gap-1.5 p-1 bg-white border border-gray-200 rounded-md shadow-sm focus-within:ring-1 focus-within:ring-foresty">
                  <input type="color" x-model="localColor" class="w-7 h-7 rounded cursor-pointer border-0 p-0 bg-transparent shrink-0">
                  <input type="text" x-model="localColor" class="w-full text-xs border-0 focus:ring-0 p-0 text-gray-700 bg-transparent uppercase font-mono" placeholder="#064F3B">
                </div>
              </div>
            </div>

          </div>
        </div>
      @endforeach

      @if (count($cards) === 0)
        <div class="p-8 border-2 border-dashed border-gray-200 rounded-xl text-center flex flex-col items-center justify-center bg-gray-50">
          <x-dynamic-component component="lucide-layout-grid" class="w-8 h-8 text-gray-300 mb-2" />
          <p class="text-xs font-bold text-gray-400 uppercase">Belum ada kartu</p>
        </div>
      @endif
    </div> --}}

    {{-- AREA KONTEN TAB (Menggunakan Grid Stack untuk kestabilan tinggi kontainer) --}}
    <div class="grid grid-cols-1">
      @foreach ($cards as $index => $card)
        <div
          x-show="activeTab === {{ $index }}"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0"
          x-transition:enter-end="opacity-100"
          wire:key="card-tab-{{ $blockId }}-{{ $index }}"
          class="col-start-1 row-start-1 rounded-xl border border-gray-100 bg-gray-50 p-4 transition-all"
          :style="activeTab === {{ $index }} ? 'position: relative; z-index: 10;' : 'pointer-events: none; visibility: hidden; z-index: 0;'"
          style="position: relative"
        >
          <div class="grid grid-cols-1 gap-6 md:grid-cols-[1.5fr_1fr]">
            {{-- Kolom Teks Utama --}}
            <div class="space-y-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-foresty text-[10px] font-bold uppercase"
                  >Teks Kecil (Eyebrow)</label
                >
                <input
                  type="text"
                  wire:model.live.debounce.300ms="content.{{ $blockId }}.data.cards.{{ $index }}.eyebrow.{{ $code }}"
                  placeholder="Misal: Kesehatan Ibu & Anak"
                  class="focus:ring-foresty rounded-md border-gray-200 bg-white py-1.5 text-xs shadow-sm"
                />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-foresty text-[10px] font-bold uppercase"
                  >Judul Utama</label
                >
                <input
                  type="text"
                  wire:model.live.debounce.300ms="content.{{ $blockId }}.data.cards.{{ $index }}.title.{{ $code }}"
                  placeholder="Judul kartu..."
                  class="focus:ring-foresty rounded-md border-gray-200 bg-white py-1.5 text-sm font-bold shadow-sm"
                />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-foresty text-[10px] font-bold uppercase"
                  >Deskripsi / Paragraf</label
                >
                <textarea
                  wire:model.live.debounce.300ms="content.{{ $blockId }}.data.cards.{{ $index }}.description.{{ $code }}"
                  rows="3"
                  placeholder="Tuliskan penjelasan singkat..."
                  class="focus:ring-foresty resize-none rounded-md border-gray-200 bg-white py-2 text-xs shadow-sm"
                ></textarea>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-foresty text-[10px] font-bold uppercase"
                  >Tautan / URL (Tombol Selengkapnya)</label
                >
                <div class="flex items-center gap-1">
                  <input
                    type="text"
                    wire:model.live="content.{{ $blockId }}.data.cards.{{ $index }}.url"
                    class="focus:ring-foresty w-full rounded-md border-gray-200 bg-white py-1.5 text-xs shadow-sm"
                  />
                  <button
                    type="button"
                    @click="$dispatch('buka-modal-link', { target: 'content.{{ $blockId }}.data.cards.{{ $index }}.url' })"
                    class="hover:bg-sage-soft hover:text-foresty rounded-md border border-gray-200 bg-white p-1.5 text-gray-400 shadow-sm"
                  >
                    <x-dynamic-component
                      component="lucide-search"
                      class="h-4 w-4"
                      stroke-width="2.5"
                    />
                  </button>
                </div>
              </div>
            </div>

            {{-- Kolom Pengaturan Visual --}}
            <div
              class="space-y-4 border-gray-200 md:border-l md:pl-6"
              x-data="{ localBg: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.icon_bg').live, localColor: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.icon_color').live }"
            >
              <x-editor.icon-picker
                label="Ikon Kartu"
                model="content.{{ $blockId }}.data.cards.{{ $index }}.icon"
              />

              <div class="flex flex-col gap-1.5">
                <label class="text-foresty text-[10px] font-bold uppercase"
                  >Warna Latar Ikon</label
                >
                <div class="grid grid-cols-4 gap-2">
                  @foreach ($bgList as $bg)
                    <button
                      type="button"
                      @click="localBg = '{{ $bg['value'] }}'"
                      class="flex flex-col items-center justify-center rounded-lg border bg-white p-2 transition-all duration-300"
                      :class="localBg === '{{ $bg['value'] }}' ? 'ring-2 ring-foresty ring-offset-1 border-transparent shadow-sm' : 'border-gray-200 hover:border-foresty/50 hover:bg-gray-50'"
                    >
                      <span
                        class="w-4 h-4 rounded-full {{ $bg['class'] }} mb-1 shadow-inner border border-gray-200/50"
                      ></span>
                      <span
                        class="text-[9px] font-bold text-gray-700"
                        >{{ $bg['label'] }}</span
                      >
                    </button>
                  @endforeach
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-foresty text-[10px] font-bold uppercase"
                  >Warna Ikon & Eyebrow (Hex)</label
                >
                <div
                  class="focus-within:ring-foresty flex items-center gap-1.5 rounded-md border border-gray-200 bg-white p-1 shadow-sm focus-within:ring-1"
                >
                  <input
                    type="color"
                    x-model="localColor"
                    class="h-7 w-7 shrink-0 cursor-pointer rounded border-0 bg-transparent p-0"
                  />
                  <input
                    type="text"
                    x-model="localColor"
                    class="w-full border-0 bg-transparent p-0 font-mono text-xs text-gray-700 uppercase focus:ring-0"
                    placeholder="#064F3B"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      @endforeach

      @if (count($cards) === 0)
        <div
          class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 p-8 text-center"
        >
          <x-dynamic-component
            component="lucide-layout-grid"
            class="mb-2 h-8 w-8 text-gray-300"
          />
          <p class="text-xs font-bold text-gray-400 uppercase">Belum ada kartu</p>
        </div>
      @endif
    </div>

    {{-- PRATINJAU KARTU AKTIF (Tunggal) --}}
    <div
      class="flex flex-col items-center border-t border-dashed border-gray-200 pt-6 pb-2"
    >
      <span
        class="mb-5 block text-[10px] font-bold tracking-widest text-gray-400 uppercase"
        >Pratinjau Kartu Aktif</span
      >

      <div class="flex w-full justify-center">
        @foreach ($cards as $index => $card)
          <div
            x-show="activeTab === {{ $index }}"
            x-cloak
            wire:key="preview-{{ $blockId }}-{{ $index }}"
            {{-- 🌟 PERBAIKAN: localUrl ditambahkan dengan aman di x-data --}}
            x-data="{
              bg: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.icon_bg').live,
              color: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.icon_color').live,
              eyebrow: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.eyebrow.{{ $code }}').live,
              title: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.title.{{ $code }}').live,
              desc: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.description.{{ $code }}').live,
              localUrl: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.url').live
          }"
            class="border-foresty/15 pointer-events-none flex w-full max-w-[360px] flex-col rounded-[28px] border bg-white p-8 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] transition-all duration-300"
          >
            {{-- Ikon --}}
            <div
              class="mb-5 flex h-16 w-16 items-center justify-center rounded-[18px] transition-colors duration-300"
              :class="bg || 'bg-mist'"
              :style="`color: ${color || '#064F3B'}`"
            >
              <x-dynamic-component
                :component="'lucide-' . ($card['icon'] ?? 'circle')"
                class="h-[30px] w-[30px]"
                stroke-width="2"
              />
            </div>

            {{-- Eyebrow --}}
            <div
              class="mb-3.5 text-xs font-bold tracking-[0.1em] uppercase transition-colors duration-300"
              :style="`color: ${color || '#064F3B'}`"
              x-text="eyebrow || 'Teks Kecil...'"
            ></div>

            {{-- Judul --}}
            <h3
              class="font-display text-foresty mb-2.5 text-[21px] font-semibold"
              x-text="title || 'Judul Kartu Utama'"
            ></h3>

            {{-- Paragraf --}}
            <p class="text-ink-soft flex-1 text-[15px]" x-text="
                desc ||
                'Deskripsi atau penjelasan singkat program akan muncul di sini...'
              "></p>

            {{-- 🌟 PERBAIKAN: Logika x-show yang aman mencegah galat ".trim() of null" --}}
            <div
              x-show="
                localUrl &&
                String(localUrl).trim() !== '' &&
                String(localUrl).trim() !== '#'
              "
              x-cloak
              class="group text-foresty mt-5 inline-flex items-center gap-1.5 text-[14.5px] font-bold"
            >
              {{ $code === 'en' ? 'Learn More' : 'Selengkapnya' }}
              <x-dynamic-component
                component="lucide-arrow-right"
                class="h-[15px] w-[15px]"
                stroke-width="2.5"
              />
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Tombol Tambah --}}
    {{-- <button type="button"
      @click="
        let arr = $wire.get('content.{{ $blockId }}.data.cards') || [];
        
arr.push({ icon: 'circle', icon_bg: 'bg-mist', icon_color: '#064F3B', eyebrow: { id: '', en: '' }, title: { id: '', en: '' }, description: { id: '', en: '' }, url: '' }); // url: ''
				$wire.set('content.{{ $blockId }}.data.cards', arr);
        setTimeout(() => { activeTab = arr.length - 1; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', activeTab); }, 100);
    "
      class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50">
      <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" /> Tambah Kartu
    </button> --}}

    {{-- BAK Tombol Tambah (Diperhalus tanpa kedipan menciut) --}}
    {{-- <button type="button"
      @click="
        let arr = $wire.get('content.{{ $blockId }}.data.cards') || [];
        // 1. Pindahkan tab ke indeks baru SEBELUM data dimasukkan
        activeTab = arr.length;
        $dispatch('sync-active-tab-{{ strtolower($blockId) }}', activeTab);
        
        // 2. Masukkan data baru
        arr.push({ icon: 'circle', icon_bg: 'bg-mist', icon_color: '#064F3B', eyebrow: { id: '', en: '' }, title: { id: '', en: '' }, description: { id: '', en: '' }, url: '' });
        $wire.set('content.{{ $blockId }}.data.cards', arr);
    "
      class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50">
      <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" /> Tambah Kartu
    </button> --}}

    {{-- Tombol Tambah Kartu --}}
    <button
      type="button"
      @click="
        let arr = $wire.get('content.{{ $blockId }}.data.cards') || [];
        
        // 1. Masukkan data ke array lokal
        arr.push({ icon: 'circle', icon_bg: 'bg-mist', icon_color: '#064F3B', eyebrow: { id: '', en: '' }, title: { id: '', en: '' }, description: { id: '', en: '' }, url: '' });
        
        // 2. Minta Livewire memproses di server. JANGAN PINDAH TAB DULU.
        $wire.set('content.{{ $blockId }}.data.cards', arr).then(() => {
            // 3. Kode di dalam then() ini HANYA JALAN SETELAH HTML baru tiba dari server.
            // Saat ini dieksekusi, tidak akan ada efek menyusut/kosong.
            activeTab = arr.length - 1;
            $dispatch('sync-active-tab-{{ strtolower($blockId) }}', activeTab);
        });
    "
      class="hover:border-foresty hover:text-foresty flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 py-3 text-xs font-bold tracking-widest text-gray-500 uppercase transition-colors"
    >
      <x-dynamic-component component="lucide-plus-square" class="h-4.5 w-4.5" />
      Tambah Kartu
    </button>
  </div>
</div>
