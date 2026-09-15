@props(['blockId', 'block', 'code'])

@php
  $templates = [
      'stats' => ['label' => 'Angka Statistik', 'icon' => 'bar-chart-2', 'desc' => 'Gaya dasbor, angka besar.'],
      'document' => ['label' => 'Dokumen Unduhan', 'icon' => 'file-text', 'desc' => 'Ikon PDF, judul, subjudul, tautan.'],
      'profile' => ['label' => 'Profil / Tim', 'icon' => 'users', 'desc' => 'Avatar inisial otomatis.'],
      'impact' => ['label' => 'Kartu Dampak', 'icon' => 'target', 'desc' => 'Teks panjang dengan lencana (tags).'],
      'partner' => ['label' => 'Grid Mitra', 'icon' => 'layout-grid', 'desc' => 'Kotak ringkas (rasio 2:1).'],
      'minimal' => ['label' => 'Info Minimalis', 'icon' => 'credit-card', 'desc' => 'Teks bersih, tanpa ikon.'],
  ];
@endphp

{{-- 🌟 SUPER WRAPPER --}}
<div class="bg-white border border-gray-200 transition-all duration-300 rounded-xl" x-data="{
    isCollapsed: false,
    blockData: $wire.entangle('content.{{ $blockId }}.data'),
    activeTab: 0,
    showConfirmModal: false,

    init() {
        if (!this.blockData.items) this.blockData.items = [];
        if (!this.blockData.col_count) this.blockData.col_count = 3;

        // 🌟 Setelan bawaan untuk margin (Jarak Bawah)
        if (this.blockData.margin_bottom === undefined) this.blockData.margin_bottom = 'mb-4';

        // Normalisasi data lama
        this.blockData.items.forEach(item => {
            if (typeof item.title !== 'object') item.title = { id: item.title || '', en: item.title || '' };
            if (typeof item.subtitle !== 'object') item.subtitle = { id: item.subtitle || '', en: item.subtitle || '' };
            if (typeof item.desc !== 'object') item.desc = { id: item.desc || '', en: item.desc || '' };
            if (typeof item.tags !== 'object') item.tags = { id: item.tags || '', en: item.tags || '' };
        });

        this.$watch('activeTab', value => {
            this.$dispatch('sync-active-tab-{{ strtolower($blockId) }}', value);
        });
    },

    syncPreview() {
        let rawData = JSON.parse(JSON.stringify(this.blockData));
        $wire.set('content.{{ $blockId }}.data', rawData);
    },

    setTemplate(tpl) {
        this.blockData.template = tpl;
        if (!this.blockData.items || this.blockData.items.length === 0) {
            this.addItem(false);
        }
        this.syncPreview();
    },

    executeResetTemplate() {
        this.blockData.template = '';
        this.activeTab = 0;
        this.blockData.items = [];
        this.showConfirmModal = false;
        this.syncPreview();
    },

    addItem(triggerSync = true) {
        if (!this.blockData.items) this.blockData.items = [];
        this.blockData.items.push({
            title: { id: '', en: '' },
            subtitle: { id: '', en: '' },
            desc: { id: '', en: '' },
            url: '',
            tags: { id: '', en: '' },
            theme: 'default'
        });
        this.activeTab = this.blockData.items.length - 1;
        if (triggerSync) this.syncPreview();
    },

    removeItem(index) {
        this.blockData.items.splice(index, 1);
        this.activeTab = Math.max(0, this.activeTab - 1);
        this.syncPreview();
    },

    isTemplate(list) {
        return list.includes(this.blockData.template);
    }
}" {{-- 🌟 EVENT LISTENERS: Menggabungkan tab sync dengan tambahan global collapse Anda --}} @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
  @sync-active-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail" @toggle-collapse-all.window="isCollapsed = $event.detail"
  @force-collapse-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = true; window.blockCollapseState['{{ $blockId }}'] = true; }"
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }" wire:key="super-wrapper-{{ $blockId }}"
  class="space-y-8">

  <!-- ==========================================
         KOTAK 1: EDITOR
         ========================================== -->
  <div class="bg-white border border-gray-200 transition-all duration-300" :class="isCollapsed ? 'rounded-xl shadow-sm' : 'rounded-xl shadow-md'">

    <!-- HEADER STANDAR -->
    <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-all duration-200 hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">

      <div class="flex items-center gap-2">
        <button type="button" @click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
          class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none cursor-pointer">
          <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
        </button>
        <div class="p-1 bg-sage-soft rounded-md">
          <x-dynamic-component component="lucide-layout-template" class="h-4 w-4 text-foresty" stroke-width="2.5" />
        </div>

        <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center gap-2">
          Card Builder
          <span x-show="blockData.template" x-cloak class="px-1.5 py-0.5 bg-foresty/10 text-foresty rounded border border-foresty/20 text-[9px] font-bold">
            <span x-text="blockData.template"></span>
          </span>
        </span>
      </div>

      <div class="flex items-center justify-end gap-2 flex-1 min-w-0">
        <div x-show="isCollapsed" x-cloak class="flex-1 min-w-0 px-2 sm:px-4 text-xs text-gray-400 font-medium">
          <span class="block truncate w-full text-right font-bold text-foresty uppercase">
            <span x-text="blockData.template ? 'Mode: ' + blockData.template : 'Pilih Template'"></span>
          </span>
        </div>

        <div x-show="blockData.items && blockData.items.length > 0" x-cloak class="flex items-center gap-1 px-1.5 py-0.5 bg-gray-200 text-gray-600 text-[10px] font-bold rounded-md shrink-0">
          <span x-text="blockData.items.length"></span> Kartu
        </div>

        <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
          {{ $code }}
        </span>
      </div>
    </div>

    <!-- EDITOR BODY -->
    <div x-show="!isCollapsed" x-collapse wire:ignore>

      <!-- MODAL CUSTOM -->
      <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm transition-opacity" x-transition.opacity.duration.300ms>
        <div @click.away="showConfirmModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 mx-4 transform transition-all" x-transition.scale.80.duration.300ms>
          <div class="flex items-center gap-3 mb-3 text-coral">
            <div class="p-2 bg-coral/10 rounded-full">
              <x-dynamic-component component="lucide-alert-triangle" class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-bold font-display text-gray-800">Ganti Template?</h3>
          </div>
          <p class="text-[13.5px] text-gray-600 mb-6 leading-relaxed">
            Mengganti template akan <strong>menghapus semua teks dan kartu</strong> yang sudah Anda buat. Yakin ingin mengulang?
          </p>
          <div class="flex items-center justify-end gap-3">
            <button type="button" @click="showConfirmModal = false" class="px-5 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
            <button type="button" @click="executeResetTemplate()" class="px-5 py-2 text-xs font-bold text-white bg-coral hover:bg-red-600 rounded-xl shadow-sm transition-colors">Ya, Ganti Template</button>
          </div>
        </div>
      </div>

      <!-- LAYAR 1: PEMILIH TEMPLATE -->
      <template x-if="!blockData.template">
        <div class="p-8 relative">
          <div class="text-center mb-8">
            <h3 class="font-display text-xl font-bold text-foresty mb-2">Pilih Desain Kartu</h3>
            <p class="text-sm text-gray-500">Pilih tata letak dasar untuk grup kartu ini.</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($templates as $key => $tpl)
              <button type="button" @click="setTemplate('{{ $key }}')" class="text-left p-5 border-2 border-gray-100 rounded-xl hover:border-foresty hover:bg-sage-soft transition-all group">
                <div class="w-10 h-10 rounded-lg bg-gray-100 text-gray-500 group-hover:bg-foresty flex items-center justify-center mb-4 transition-colors">
                  <x-dynamic-component :component="'lucide-' . $tpl['icon']" class="w-5 h-5 group-hover:text-white" />
                </div>
                <div class="font-bold text-foresty text-sm mb-1">{{ $tpl['label'] }}</div>
                <div class="text-[11.5px] text-gray-500">{{ $tpl['desc'] }}</div>
              </button>
            @endforeach
          </div>
        </div>
      </template>

      <!-- LAYAR 2: EDITOR GRID STACK -->
      <template x-if="blockData.template">
        <div class="p-4 relative">
          {{-- 🌟 TOOLBAR: Pengaturan Kolom, Pengaturan Margin, dan Ganti Template --}}
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100 mb-4">

            {{-- Pengaturan Layout --}}
            <div class="flex flex-wrap items-center gap-4 shrink-0">
              {{-- Kolom --}}
              <div class="flex items-center gap-2">
                <label class="text-[10px] font-bold text-gray-400 uppercase">Kolom:</label>
                <select x-model="blockData.col_count" @change="syncPreview()" class="text-xs font-bold text-forest border-gray-300 rounded py-1 pl-2 pr-6 shadow-sm bg-white">
                  <option value="1">1 Kol</option>
                  <option value="2">2 Kol</option>
                  <option value="3">3 Kol</option>
                  <option value="4">4 Kol</option>
                </select>
              </div>

              {{-- Margin Bawah --}}
              <div class="flex items-center gap-2">
                <label class="text-[10px] font-bold text-gray-400 uppercase">Jarak Bawah:</label>
                <select x-model="blockData.margin_bottom" @change="syncPreview()" class="text-xs font-bold text-forest border-gray-300 rounded py-1 pl-2 pr-6 shadow-sm bg-white">
                  <option value="mb-0">0px (Rapat)</option>
                  <option value="mb-4">16px (Normal)</option>
                  <option value="mb-8">32px (Sedang)</option>
                  <option value="mb-12">48px (Jauh)</option>
                  <option value="mb-16">64px (Sangat Jauh)</option>
                </select>
              </div>
            </div>

            <button type="button" @click="showConfirmModal = true" class="text-[10px] font-bold text-coral uppercase hover:underline flex items-center gap-1.5 ml-auto sm:ml-0">
              <x-dynamic-component component="lucide-refresh-cw" class="w-3 h-3" /> Ganti Template
            </button>
          </div>


          {{-- Tab Navigasi --}}
          <div class="flex flex-wrap items-center gap-2 mb-4" x-show="blockData.items && blockData.items.length > 0">
            <template x-for="(item, index) in blockData.items" :key="index">
              <button type="button" @click="activeTab = index" :class="activeTab === index ? 'bg-foresty text-white shadow-md border-transparent' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200'"
                class="px-2.5 py-1 rounded-lg text-xs font-bold border flex items-center gap-1.5 shrink-0 transition-colors">
                <span x-text="'#' + String(index + 1).padStart(2, '0')"></span>
                <div @click.stop="removeItem(index)" class="p-0.5 rounded ml-0.5 hover:bg-red-500 hover:text-white">
                  <x-dynamic-component component="lucide-x" class="w-3 h-3" />
                </div>
              </button>
            </template>
          </div>

          {{-- Formulir Input --}}
          <div class="grid grid-cols-1" @input.debounce.1000ms="syncPreview()">
            <template x-for="(item, index) in blockData.items" :key="index">
              <template x-if="blockData.items[index]">
                <div x-show="activeTab === index" x-transition.opacity.duration.200ms class="col-start-1 row-start-1 p-5 border border-gray-100 bg-gray-50 rounded-xl"
                  :style="activeTab === index ? 'position: relative; z-index: 10;' : 'pointer-events: none; visibility: hidden; z-index: 0;'">

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Field 1: Judul Utama --}}
                    <div class="flex flex-col gap-1.5 md:col-span-2" x-show="isTemplate(['stats', 'document', 'profile', 'impact', 'partner', 'minimal'])">
                      <label class="text-[10px] font-bold text-foresty uppercase" x-text="blockData.template === 'stats' ? 'Angka Statistik' : (blockData.template === 'profile' ? 'Nama Tokoh' : 'Judul Utama')"></label>
                      <input type="text" x-model="blockData.items[index].title.{{ $code }}" class="text-sm font-bold border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm">
                    </div>

                    {{-- Field 2: Subtitle --}}
                    <div class="flex flex-col gap-1.5" x-show="isTemplate(['document', 'profile', 'impact', 'minimal'])">
                      <label class="text-[10px] font-bold text-foresty uppercase" x-text="blockData.template === 'profile' ? 'Jabatan' : 'Teks Kecil (Eyebrow)'"></label>
                      <input type="text" x-model="blockData.items[index].subtitle.{{ $code }}" class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
                    </div>

                    {{-- Field 3: Deskripsi / Kutipan --}}
                    <div class="flex flex-col gap-1.5 md:col-span-2" x-show="isTemplate(['stats', 'profile', 'impact'])">
                      <label class="text-[10px] font-bold text-foresty uppercase" x-text="blockData.template === 'profile' ? 'Bio/Kutipan' : 'Deskripsi Paragraf'"></label>
                      <textarea x-model="blockData.items[index].desc.{{ $code }}" rows="2" class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm resize-none"></textarea>
                    </div>

                    {{-- Field 4: Tautan URL --}}
                    <div class="flex flex-col gap-1.5" x-show="isTemplate(['document', 'partner'])">
                      <label class="text-[10px] font-bold text-foresty uppercase">Tautan URL</label>
                      <input type="text" x-model="blockData.items[index].url" placeholder="https://..." class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
                    </div>

                    {{-- Field 5: Tags (Dampak) --}}
                    <div class="flex flex-col gap-1.5 md:col-span-2" x-show="isTemplate(['impact'])">
                      <label class="text-[10px] font-bold text-foresty uppercase">Lencana Bawah (Koma)</label>
                      <input type="text" x-model="blockData.items[index].tags.{{ $code }}" placeholder="Misal: 50 langsung, 1.7 juta"
                        class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
                    </div>

                    {{-- Field 6: Style Variation (Stats) --}}
                    <div class="flex flex-col gap-1.5" x-show="isTemplate(['stats'])">
                      <label class="text-[10px] font-bold text-foresty uppercase">Gaya Kartu</label>
                      <select x-model="blockData.items[index].theme" @change="syncPreview()" class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1 bg-white shadow-sm">
                        <option value="default">Angka Saja (Bawaan)</option>
                        <option value="boxed">Boxed Putih</option>
                        <option value="dashed">Dashed Gelap</option>
                      </select>
                    </div>
                  </div>
                </div>
              </template>
            </template>
          </div>

          <button type="button" @click="addItem()"
            class="w-full mt-4 py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty transition-colors text-xs font-bold uppercase flex items-center justify-center gap-2 bg-gray-50">
            <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" /> Tambah Kartu
          </button>
        </div>
      </template>
    </div>
  </div>
  <!-- ==========================================
         KOTAK 2: LIVE PREVIEW (HANYA KARTU AKTIF)
         ========================================== -->
  <div x-show="blockData.template && !isCollapsed" x-collapse x-cloak wire:key="preview-wrapper-{{ $blockId }}" class="relative rounded-2xl overflow-hidden bg-gray-50 border border-gray-200">
    <div class="bg-gray-200/60 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
      <div class="flex items-center gap-2 text-gray-500">
        <x-dynamic-component component="lucide-eye" class="w-4 h-4" />
        <span class="text-[10px] font-bold uppercase tracking-widest">Live Preview (Kartu Aktif)</span>
      </div>
      <div class="flex gap-1.5">
        <div class="w-2.5 h-2.5 rounded-full bg-coral/50"></div>
        <div class="w-2.5 h-2.5 rounded-full bg-goldy/50"></div>
        <div class="w-2.5 h-2.5 rounded-full bg-foresty/50"></div>
      </div>
    </div>

    <style
      x-text="`
            .preview-grid-{{ $blockId }} .grid { 
                grid-template-columns: 1fr !important;
                max-width: 400px; 
                margin: 0 auto; 
            }
            .preview-grid-{{ $blockId }} .grid > div { display: none !important; }
            .preview-grid-{{ $blockId }} .grid > div:nth-child(${activeTab + 1}) { display: block !important; }
        `">
    </style>

    <div class="preview-grid-{{ $blockId }} p-6 md:p-10 w-full overflow-x-auto min-h-[150px]">
      @php
        $liveData = isset($this) && property_exists($this, 'content') ? $this->content[$blockId]['data'] ?? [] : $block['data'] ?? [];
      @endphp
      {{-- 🌟 Solusi Bahasa: Mengirim strtolower($code) agar sesuai ID/EN --}}
      @include('components.blocks.render.card-builder', [
          'data' => $liveData,
          'lang' => strtolower($code),
      ])
    </div>
  </div>


</div>
