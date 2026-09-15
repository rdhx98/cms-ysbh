@props(['blockId', 'block', 'code'])

@php
  $cards = $block['data']['cards'] ?? [];
  $bgList = [
      ['value' => 'bg-goldy-soft', 'label' => 'Goldy', 'class' => 'bg-[#FDF8E1]'],
      ['value' => 'bg-mist', 'label' => 'Misty', 'class' => 'bg-[#E9F1EB]'],
      ['value' => 'bg-coral/20', 'label' => 'Coral', 'class' => 'bg-[#FBE6E6]'],
      ['value' => 'bg-gray-100', 'label' => 'Abu', 'class' => 'bg-gray-100'],
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
        <x-dynamic-component component="lucide-layout-grid" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center gap-2">
        Grup Kartu

      </span>
    </div>
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0">
      <span x-show="isCollapsed" x-cloak class="bg-white border border-gray-200 shadow-sm text-gray-500 px-1.5 py-0.5 rounded-md text-[9px] font-bold tracking-normal"
        x-text="($wire.get('content.{{ $blockId }}.data.cards') || []).length + ' Kartu'">
      </span>
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
        {{ $code }}
      </span>
    </div>
  </div>

  <div x-show="!isCollapsed" x-collapse x-cloak class="p-4 space-y-5 bg-white rounded-b-xl">

    {{-- BARIS KONTROL & NAVIGASI TAB --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">

      {{-- Kontrol Kolom Desktop --}}
      <div class="flex items-center gap-3 shrink-0">
        <label class="text-[10px] font-bold text-gray-400 uppercase">Kolom (PC):</label>
        <select wire:model.live="content.{{ $blockId }}.data.col_count" class="text-xs font-bold text-forest border-gray-300 rounded py-1 pl-2 pr-6 shadow-sm focus:ring-forest focus:border-forest bg-white">
          <option value="2">2 Kolom</option>
          <option value="3">3 Kolom</option>
          <option value="4">4 Kolom</option>
        </select>
      </div>

      {{-- Tab Navigasi (Ringkas) --}}
      @if (count($cards) > 0)
        <div class="flex flex-wrap items-center gap-2">
          @foreach ($cards as $index => $card)
            <button type="button" @click="activeTab = {{ $index }}; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', {{ $index }})"
              :class="activeTab === {{ $index }} ? 'bg-foresty text-white shadow-md border-transparent' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 hover:border-foresty/50'"
              class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all duration-300 flex items-center gap-1.5 shrink-0">

              {{-- Label Angka Kartu (Misal: #01, #02) --}}
              <span>#{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>

              <div
                @click.stop="$wire.set('content.{{ $blockId }}.data.cards', $wire.get('content.{{ $blockId }}.data.cards').filter((_, i) => i !== {{ $index }})); activeTab = 0; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', 0);"
                class="p-0.5 rounded hover:bg-red-500 hover:text-white transition-colors ml-0.5" :class="activeTab === {{ $index }} ? 'text-white/60 hover:bg-white/20' : 'text-gray-400'">
                <x-dynamic-component component="lucide-x" class="w-3 h-3" />
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
        <div x-show="activeTab === {{ $index }}" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
          wire:key="card-tab-{{ $blockId }}-{{ $index }}" class="col-start-1 row-start-1 p-4 border border-gray-100 bg-gray-50 rounded-xl transition-all"
          :style="activeTab === {{ $index }} ? 'position: relative; z-index: 10;' : 'pointer-events: none; visibility: hidden; z-index: 0;'" style="position: relative;">

          <div class="grid grid-cols-1 md:grid-cols-[1.5fr_1fr] gap-6">

            {{-- Kolom Teks Utama --}}
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

            {{-- Kolom Pengaturan Visual --}}
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
    </div>

    {{-- PRATINJAU KARTU AKTIF (Tunggal) --}}
    <div class="pt-6 pb-2 border-t border-dashed border-gray-200 flex flex-col items-center">
      <span class="block text-[10px] font-bold text-gray-400 uppercase mb-5 tracking-widest">Pratinjau Kartu Aktif</span>

      <div class="w-full flex justify-center">
        @foreach ($cards as $index => $card)
          <div x-show="activeTab === {{ $index }}" x-cloak wire:key="preview-{{ $blockId }}-{{ $index }}" {{-- 🌟 PERBAIKAN: localUrl ditambahkan dengan aman di x-data --}} x-data="{
              bg: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.icon_bg').live,
              color: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.icon_color').live,
              eyebrow: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.eyebrow.{{ $code }}').live,
              title: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.title.{{ $code }}').live,
              desc: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.description.{{ $code }}').live,
              localUrl: $wire.entangle('content.{{ $blockId }}.data.cards.{{ $index }}.url').live
          }"
            class="w-full max-w-[360px] bg-white rounded-[28px] p-8 border border-foresty/15 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] flex flex-col pointer-events-none transition-all duration-300">

            {{-- Ikon --}}
            <div class="w-16 h-16 rounded-[18px] flex items-center justify-center mb-5 transition-colors duration-300" :class="bg || 'bg-mist'" :style="`color: ${color || '#064F3B'}`">
              <x-dynamic-component :component="'lucide-' . ($card['icon'] ?? 'circle')" class="w-[30px] h-[30px]" stroke-width="2" />
            </div>

            {{-- Eyebrow --}}
            <div class="text-xs font-bold tracking-[0.1em] uppercase mb-3.5 transition-colors duration-300" :style="`color: ${color || '#064F3B'}`" x-text="eyebrow || 'Teks Kecil...'">
            </div>

            {{-- Judul --}}
            <h3 class="font-display text-[21px] font-semibold text-foresty mb-2.5" x-text="title || 'Judul Kartu Utama'">
            </h3>

            {{-- Paragraf --}}
            <p class="text-ink-soft text-[15px] flex-1" x-text="desc || 'Deskripsi atau penjelasan singkat program akan muncul di sini...'"></p>

            {{-- 🌟 PERBAIKAN: Logika x-show yang aman mencegah galat ".trim() of null" --}}
            <div x-show="localUrl && String(localUrl).trim() !== '' && String(localUrl).trim() !== '#'" x-cloak class="group mt-5 font-bold text-[14.5px] inline-flex items-center gap-1.5 text-foresty">
              {{ $code === 'en' ? 'Learn More' : 'Selengkapnya' }}
              <x-dynamic-component component="lucide-arrow-right" class="w-[15px] h-[15px]" stroke-width="2.5" />
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
    <button type="button"
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
      class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50">
      <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" /> Tambah Kartu
    </button>

  </div>
</div>
