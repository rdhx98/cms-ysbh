@props(['blockId', 'block', 'code'])

@php
  $testimonials = $block['data']['testimonials'] ?? [];

  // 🌟 4-5 PILIHAN PALET KOMBINASI TERKONTROL
  $paletteList = [
      [
          'value' => 'theme-forest',
          'label' => 'Foresty',
          'quote' => 'text-goldy',
          'avatar_bg' => 'bg-[#E9F1EB]',
          'avatar_fg' => 'text-foresty',
          'class' => 'bg-[#064F3B]',
      ],
      [
          'value' => 'theme-gold',
          'label' => 'Goldy',
          'quote' => 'text-[#D99B00]',
          'avatar_bg' => 'bg-[#FDF8E1]',
          'avatar_fg' => 'text-[#8A6300]',
          'class' => 'bg-[#EBCC26]',
      ],
      [
          'value' => 'theme-coral',
          'label' => 'Coral',
          'quote' => 'text-coral',
          'avatar_bg' => 'bg-[#FBE6E6]',
          'avatar_fg' => 'text-coral-dark',
          'class' => 'bg-[#E06B5E]',
      ],
      [
          'value' => 'theme-neutral',
          'label' => 'Neutral',
          'quote' => 'text-gray-400',
          'avatar_bg' => 'bg-gray-100',
          'avatar_fg' => 'text-gray-700',
          'class' => 'bg-gray-500',
      ],
  ];
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/section" x-data="{
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
}" @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
  @toggle-collapse-all.window="isCollapsed = $event.detail"
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }"
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }"
  @sync-active-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail">

  <!-- Header Editor -->
  <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-all duration-200 group-hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">
    <div class="flex items-center gap-2">
      <button type="button" @click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
        class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none">
        <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
      </button>
      <div class="p-1 bg-sage-soft rounded-md">
        <x-dynamic-component component="lucide-message-square-quote" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center gap-2">
        Grup Testimoni
        <span class="bg-white border border-gray-200 shadow-sm text-gray-500 px-1.5 py-0.5 rounded-md text-[9px] font-bold tracking-normal"
          x-text="($wire.get('content.{{ $blockId }}.data.testimonials') || []).length + ' Orang'">
        </span>
      </span>
    </div>
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0">
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
        {{ $code }}
      </span>
    </div>
  </div>

  <div x-show="!isCollapsed" x-collapse x-cloak class="p-4 space-y-5 bg-white rounded-b-xl">

    {{-- BARIS KONTROL & NAVIGASI TAB --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
      <div class="flex items-center gap-3 shrink-0">
        <label class="text-[10px] font-bold text-gray-400 uppercase">Kolom (PC):</label>
        <select wire:model.live="content.{{ $blockId }}.data.col_count" class="text-xs font-bold text-forest border-gray-300 rounded py-1 pl-2 pr-6 shadow-sm focus:ring-forest focus:border-forest bg-white">
          <option value="1">1 Kolom</option>
          <option value="2">2 Kolom</option>
          <option value="3">3 Kolom</option>
        </select>
      </div>

      @if (count($testimonials) > 0)
        <div class="flex flex-wrap items-center gap-2">
          @foreach ($testimonials as $index => $item)
            <button type="button" @click="activeTab = {{ $index }}; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', {{ $index }})"
              :class="activeTab === {{ $index }} ? 'bg-foresty text-white shadow-md border-transparent' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 hover:border-foresty/50'"
              class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all duration-300 flex items-center gap-1.5 shrink-0">
              <span>#{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
              <div
                @click.stop="$wire.set('content.{{ $blockId }}.data.testimonials', $wire.get('content.{{ $blockId }}.data.testimonials').filter((_, i) => i !== {{ $index }})); activeTab = 0; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', 0);"
                class="p-0.5 rounded hover:bg-red-500 hover:text-white transition-colors ml-0.5" :class="activeTab === {{ $index }} ? 'text-white/60 hover:bg-white/20' : 'text-gray-400'">
                <x-dynamic-component component="lucide-x" class="w-3 h-3" />
              </div>
            </button>
          @endforeach
        </div>
      @endif
    </div>

    {{-- FORMULIR TAB AKTIF --}}
    {{-- <div class="min-h-[220px]">
      @foreach ($testimonials as $index => $item)
        <div x-show="activeTab === {{ $index }}" x-cloak wire:key="testi-tab-{{ $blockId }}-{{ $index }}" class="p-4 border border-gray-100 bg-gray-50 rounded-xl" x-data="{ theme: $wire.entangle('content.{{ $blockId }}.data.testimonials.{{ $index }}.theme').live || 'theme-forest' }">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Teks Kutipan --}
            <div class="flex flex-col gap-1.5 md:col-span-2">
              <label class="text-[10px] font-bold text-foresty uppercase">Isi Testimoni / Kutipan</label>
              <textarea wire:model.live.debounce.300ms="content.{{ $blockId }}.data.testimonials.{{ $index }}.quote.{{ $code }}" rows="3" placeholder="Tuliskan ucapan tokoh di sini..."
                class="text-sm font-medium italic border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm resize-none"></textarea>
            </div>

            {{-- Data Tokoh --}
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-foresty uppercase">Nama Lengkap</label>
              <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.testimonials.{{ $index }}.name.{{ $code }}" placeholder="Misal: Sri Wahyuni"
                class="text-xs font-bold border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-foresty uppercase">Jabatan & Lokasi</label>
              <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.testimonials.{{ $index }}.role.{{ $code }}" placeholder="Misal: Kader Posyandu, Sikka"
                class="text-xs border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm">
            </div>

            {{-- 🌟 PEMILIH TEMA WARNA TERPADU --}
            <div class="flex flex-col gap-1.5 md:col-span-2 pt-2 border-t border-gray-200">
              <label class="text-[10px] font-bold text-foresty uppercase">Tema Kombinasi Warna (Tanda Kutip & Avatar)</label>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach ($paletteList as $p)
                  <button type="button" @click="theme = '{{ $p['value'] }}'" class="flex items-center gap-2.5 p-2 rounded-lg border transition-all duration-300 bg-white text-left"
                    :class="theme === '{{ $p['value'] }}' ? 'ring-2 ring-foresty ring-offset-1 border-transparent shadow-sm scale-[1.02]' : 'border-gray-200 hover:border-foresty/50 hover:bg-gray-50'">
                    <span class="w-5 h-5 rounded-full {{ $p['class'] }} shadow-inner shrink-0"></span>
                    <span class="text-[11px] font-bold text-gray-700 leading-none">{{ $p['label'] }}</span>
                  </button>
                @endforeach
              </div>
            </div>

          </div>
        </div>
      @endforeach

      @if (count($testimonials) === 0)
        <div class="p-8 border-2 border-dashed border-gray-200 rounded-xl text-center flex flex-col items-center justify-center bg-gray-50">
          <x-dynamic-component component="lucide-message-square-quote" class="w-8 h-8 text-gray-300 mb-2" />
          <p class="text-xs font-bold text-gray-400 uppercase">Belum ada testimoni</p>
        </div>
      @endif
    </div> --}}
    {{-- FORMULIR TAB AKTIF (Menggunakan Grid Stack) --}}
    <div class="grid grid-cols-1">
      @foreach ($testimonials as $index => $item)
        <div x-show="activeTab === {{ $index }}" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
          wire:key="testi-tab-{{ $blockId }}-{{ $index }}" class="col-start-1 row-start-1 p-4 border border-gray-100 bg-gray-50 rounded-xl transition-all"
          :style="activeTab === {{ $index }} ? 'position: relative; z-index: 10;' : 'pointer-events: none; visibility: hidden; z-index: 0;'" style="position: relative;" x-data="{ theme: $wire.entangle('content.{{ $blockId }}.data.testimonials.{{ $index }}.theme').live || 'theme-forest' }">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Teks Kutipan --}}
            <div class="flex flex-col gap-1.5 md:col-span-2">
              <label class="text-[10px] font-bold text-foresty uppercase">Isi Testimoni / Kutipan</label>
              <textarea wire:model.live.debounce.300ms="content.{{ $blockId }}.data.testimonials.{{ $index }}.quote.{{ $code }}" rows="3" placeholder="Tuliskan ucapan tokoh di sini..."
                class="text-sm font-medium italic border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm resize-none"></textarea>
            </div>

            {{-- Data Tokoh --}}
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-foresty uppercase">Nama Lengkap</label>
              <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.testimonials.{{ $index }}.name.{{ $code }}" placeholder="Misal: Sri Wahyuni"
                class="text-xs font-bold border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm">
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-foresty uppercase">Jabatan & Lokasi</label>
              <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.testimonials.{{ $index }}.role.{{ $code }}" placeholder="Misal: Kader Posyandu, Sikka"
                class="text-xs border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm">
            </div>

            {{-- Pemilih Tema Warna Terpadu --}}
            <div class="flex flex-col gap-1.5 md:col-span-2 pt-2 border-t border-gray-200">
              <label class="text-[10px] font-bold text-foresty uppercase">Tema Kombinasi Warna (Tanda Kutip & Avatar)</label>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach ($paletteList as $p)
                  <button type="button" @click="theme = '{{ $p['value'] }}'" class="flex items-center gap-2.5 p-2 rounded-lg border transition-all duration-300 bg-white text-left"
                    :class="theme === '{{ $p['value'] }}' ? 'ring-2 ring-foresty ring-offset-1 border-transparent shadow-sm scale-[1.02]' : 'border-gray-200 hover:border-foresty/50 hover:bg-gray-50'">
                    <span class="w-5 h-5 rounded-full {{ $p['class'] }} shadow-inner shrink-0"></span>
                    <span class="text-[11px] font-bold text-gray-700 leading-none">{{ $p['label'] }}</span>
                  </button>
                @endforeach
              </div>
            </div>

          </div>
        </div>
      @endforeach

      @if (count($testimonials) === 0)
        <div class="p-8 border-2 border-dashed border-gray-200 rounded-xl text-center flex flex-col items-center justify-center bg-gray-50">
          <x-dynamic-component component="lucide-message-square-quote" class="w-8 h-8 text-gray-300 mb-2" />
          <p class="text-xs font-bold text-gray-400 uppercase">Belum ada testimoni</p>
        </div>
      @endif
    </div>

    {{-- PRATINJAU KARTU AKTIF (Tunggal) --}}
    <div class="pt-6 pb-2 border-t border-dashed border-gray-200 flex flex-col items-center">
      <span class="block text-[10px] font-bold text-gray-400 uppercase mb-5 tracking-widest">Pratinjau Testimoni Aktif</span>

      <div class="w-full flex justify-center">
        @foreach ($testimonials as $index => $item)
          @php
            // Mapping mapping warna untuk pratinjau Alpine
            $mapQuote = [
                'theme-forest' => 'text-goldy',
                'theme-gold' => 'text-[#D99B00]',
                'theme-coral' => 'text-coral',
                'theme-neutral' => 'text-gray-400',
            ];
            $mapAvatarBg = [
                'theme-forest' => 'bg-[#E9F1EB]',
                'theme-gold' => 'bg-[#FDF8E1]',
                'theme-coral' => 'bg-[#FBE6E6]',
                'theme-neutral' => 'bg-gray-100',
            ];
            $mapAvatarFg = [
                'theme-forest' => 'text-foresty',
                'theme-gold' => 'text-[#8A6300]',
                'theme-coral' => 'text-coral-dark',
                'theme-neutral' => 'text-gray-700',
            ];
          @endphp

          <div x-show="activeTab === {{ $index }}" x-cloak wire:key="preview-{{ $blockId }}-{{ $index }}" x-data="{
              quote: $wire.entangle('content.{{ $blockId }}.data.testimonials.{{ $index }}.quote.{{ $code }}').live,
              name: $wire.entangle('content.{{ $blockId }}.data.testimonials.{{ $index }}.name.{{ $code }}').live,
              role: $wire.entangle('content.{{ $blockId }}.data.testimonials.{{ $index }}.role.{{ $code }}').live,
              theme: $wire.entangle('content.{{ $blockId }}.data.testimonials.{{ $index }}.theme').live || 'theme-forest'
          }"
            class="w-full max-w-[360px] bg-white rounded-[18px] py-[30px] px-7 border border-foresty/15 relative pointer-events-none transition-all duration-300 shadow-md">

            {{-- Tanda Kutip dengan Warna Dinamis Berdasarkan Tema --}}
            <svg class="w-[30px] h-[30px] mb-3.5 transition-colors duration-300"
              :class="{
                  'text-goldy': theme === 'theme-forest',
                  'text-[#D99B00]': theme === 'theme-gold',
                  'text-coral': theme === 'theme-coral',
                  'text-gray-400': theme === 'theme-neutral'
              }"
              viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M7 7C4.8 7 3 8.8 3 11c0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1C7.3 17 6 18.5 4 19v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4zm10 0c-2.2 0-4 1.8-4 4 0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1-.6 2.1-1.9 3.6-3.9 4.1v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4z">
              </path>
            </svg>

            <p class="font-display italic text-[17px] text-foresty leading-[1.5] mb-5 line-clamp-4" x-text="quote || 'Kutipan kosong...'"></p>

            <div class="flex items-center gap-3">
              {{-- Avatar dengan Latar & Teks Dinamis Berdasarkan Tema --}}
              <div class="w-[42px] h-[42px] rounded-full flex items-center justify-center font-display font-bold shrink-0 transition-colors duration-300"
                :class="{
                    'bg-[#E9F1EB] text-foresty': theme === 'theme-forest',
                    'bg-[#FDF8E1] text-[#8A6300]': theme === 'theme-gold',
                    'bg-[#FBE6E6] text-coral-dark': theme === 'theme-coral',
                    'bg-gray-100 text-gray-700': theme === 'theme-neutral'
                }"
                x-text="name ? String(name).split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase() : '?'"></div>
              <div>
                <div class="font-bold text-[14.5px] text-ink" x-text="name || 'Nama Tokoh'"></div>
                <div class="text-[13px] text-ink-soft" x-text="role || 'Jabatan / Lokasi'"></div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Tombol Tambah --}}
    {{-- <button type="button"
      @click="
        let arr = $wire.get('content.{{ $blockId }}.data.testimonials') || [];
        arr.push({ quote: { id: '', en: '' }, name: { id: '', en: '' }, role: { id: '', en: '' }, theme: 'theme-forest' });
        $wire.set('content.{{ $blockId }}.data.testimonials', arr);
        setTimeout(() => { activeTab = arr.length - 1; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', activeTab); }, 100);
    "
      class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50">
      <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" /> Tambah Testimoni
    </button> --}}
    {{-- Tombol Tambah (Diperhalus tanpa kedipan menciut) --}}
    {{-- <button type="button"
      @click="
        let arr = $wire.get('content.{{ $blockId }}.data.testimonials') || [];
        // 1. Pindahkan tab ke indeks baru SEBELUM data dimasukkan
        activeTab = arr.length;
        $dispatch('sync-active-tab-{{ strtolower($blockId) }}', activeTab);
        
        // 2. Masukkan data baru
        arr.push({ quote: { id: '', en: '' }, name: { id: '', en: '' }, role: { id: '', en: '' }, theme: 'theme-forest' });
        $wire.set('content.{{ $blockId }}.data.testimonials', arr);
    "
      class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50">
      <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" /> Tambah Testimoni
    </button> --}}

    {{-- Tombol Tambah Testimoni --}}
    <button type="button"
      @click="
        let arr = $wire.get('content.{{ $blockId }}.data.testimonials') || [];
        
        // 1. Masukkan data ke array lokal
        arr.push({ quote: { id: '', en: '' }, name: { id: '', en: '' }, role: { id: '', en: '' }, theme: 'theme-forest' });
        
        // 2. Minta Livewire memproses di server. Tahan perpindahan tab.
        $wire.set('content.{{ $blockId }}.data.testimonials', arr).then(() => {
            // 3. Pindah tab hanya setelah render HTML selesai.
            activeTab = arr.length - 1;
            $dispatch('sync-active-tab-{{ strtolower($blockId) }}', activeTab);
        });
    "
      class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50">
      <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" /> Tambah Testimoni
    </button>

  </div>
</div>
