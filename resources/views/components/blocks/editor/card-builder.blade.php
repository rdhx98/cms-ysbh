@props(['blockId', 'block', 'code'])

@php
  $templates = [
      'stats' => ['label' => 'Angka Statistik', 'icon' => 'bar-chart-2', 'desc' => 'Gaya dasbor, angka besar (Cocok untuk capaian).'],
      'document' => ['label' => 'Dokumen Unduhan', 'icon' => 'file-text', 'desc' => 'Ikon PDF, judul, subjudul, dan tautan unduh.'],
      'profile' => ['label' => 'Profil / Tim', 'icon' => 'users', 'desc' => 'Avatar inisial otomatis, nama, jabatan, dan bio.'],
      'impact' => ['label' => 'Kartu Dampak', 'icon' => 'target', 'desc' => 'Teks penjelasan panjang dengan lencana (tags) di bawah.'],
      'partner' => ['label' => 'Grid Mitra', 'icon' => 'layout-grid', 'desc' => 'Kotak ringkas (rasio 2:1) untuk nama lembaga/mitra.'],
      'minimal' => ['label' => 'Info Minimalis', 'icon' => 'credit-card', 'desc' => 'Teks bersih, tanpa ikon (Cocok untuk SK/Nomor Izin).'],
  ];
@endphp

{{-- 🌟 1. ALPINE MENGAMBIL ALIH KENDALI PENUH DI SINI --}}
<div class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-300" x-data="{
    template: $wire.entangle('content.{{ $blockId }}.data.template').live,
    colCount: $wire.entangle('content.{{ $blockId }}.data.col_count').live,

    // Array items sepenuhnya hidup di memori browser (Alpine)
    items: $wire.get('content.{{ $blockId }}.data.items') || [],
    activeTab: 0,

    // Fungsi untuk menembak data ke Livewire HANYA saat diperlukan
    syncToLivewire() {
        $wire.set('content.{{ $blockId }}.data.items', this.items, false);
    },

    addItem() {
        this.items.push({
            title: { id: '', en: '' },
            subtitle: { id: '', en: '' },
            desc: { id: '', en: '' },
            url: '',
            tags: { id: '', en: '' },
            theme: 'default'
        });
        this.activeTab = this.items.length - 1;
        this.syncToLivewire();
    },

    removeItem(index) {
        this.items.splice(index, 1);
        this.activeTab = Math.max(0, this.activeTab - 1);
        this.syncToLivewire();
    }
}">

  <!-- ==========================================
         LAYAR 1: PEMILIH TEMPLATE
         ========================================== -->
  <div x-show="!template" x-collapse class="p-8">
    <div class="text-center mb-8">
      <h3 class="font-display text-xl font-bold text-foresty mb-2">Pilih Desain Kartu</h3>
      <p class="text-sm text-gray-500">Pilih tata letak dasar untuk grup kartu ini.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      @foreach ($templates as $key => $tpl)
        <button type="button"
          @click="
                        template = '{{ $key }}';
                        // Jika array kosong, pancing dengan 1 item kosong
                        if(items.length === 0) { addItem(); }
                    "
          class="text-left p-5 border-2 border-gray-100 rounded-xl hover:border-foresty hover:bg-sage-soft transition-all group">
          <div class="w-10 h-10 rounded-lg bg-gray-100 text-gray-500 group-hover:bg-foresty flex items-center justify-center mb-4 transition-colors">
            <x-dynamic-component :component="'lucide-' . $tpl['icon']" class="w-5 h-5 group-hover:text-white" />
          </div>
          <div class="font-bold text-foresty text-sm mb-1">{{ $tpl['label'] }}</div>
          <div class="text-[11.5px] text-gray-500">{{ $tpl['desc'] }}</div>
        </button>
      @endforeach
    </div>
  </div>

  <!-- ==========================================
         LAYAR 2: EDITOR GRID STACK (ALPINE DRIVEN)
         ========================================== -->
  <div x-show="template" x-cloak>

    <div class="flex items-center justify-between p-3 border-b border-gray-100 bg-gray-50 rounded-t-xl">
      <div class="flex items-center gap-3">
        <div class="p-1.5 bg-white border border-gray-200 rounded text-foresty"><x-dynamic-component component="lucide-layout-template" class="h-4 w-4" /></div>
        <div>
          <span class="text-[11px] font-extrabold text-gray-500 uppercase tracking-widest block">Card Builder</span>
          <span class="text-xs font-bold text-foresty" x-text="template ? 'Mode: ' + template.toUpperCase() : ''"></span>
        </div>
      </div>
      <button type="button" @click="if(confirm('Ganti template? Semua isi kartu ini akan hilang.')) { template = ''; items = []; syncToLivewire(); }"
        class="text-[10px] font-bold text-coral uppercase hover:underline">Ganti Template</button>
    </div>

    <div class="p-4">
      {{-- Navigasi Tab (Sepenuhnya Alpine) --}}
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
        <div class="flex items-center gap-3 shrink-0">
          <label class="text-[10px] font-bold text-gray-400 uppercase">Kolom (PC):</label>
          <select x-model="colCount" class="text-xs font-bold text-forest border-gray-300 rounded py-1 pl-2 pr-6 shadow-sm bg-white">
            <option value="1">1 Kolom</option>
            <option value="2">2 Kolom</option>
            <option value="3">3 Kolom</option>
            <option value="4">4 Kolom</option>
          </select>
        </div>

        <div class="flex flex-wrap items-center gap-2" x-show="items.length > 0">
          <template x-for="(item, index) in items" :key="index">
            <button type="button" @click="activeTab = index" :class="activeTab === index ? 'bg-foresty text-white shadow-md border-transparent' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200'"
              class="px-2.5 py-1 rounded-lg text-xs font-bold border flex items-center gap-1.5 shrink-0 transition-colors">
              <span x-text="'#' + String(index + 1).padStart(2, '0')"></span>
              <div @click.stop="removeItem(index)" class="p-0.5 rounded ml-0.5 hover:bg-red-500 hover:text-white">
                <x-dynamic-component component="lucide-x" class="w-3 h-3" />
              </div>
            </button>
          </template>
        </div>
      </div>

      {{-- Formulir Input (x-model + debounce syncToLivewire) --}}
      <div class="grid grid-cols-1 mt-4">
        <template x-for="(item, index) in items" :key="index">
          <div x-show="activeTab === index" x-transition.opacity.duration.200ms class="col-start-1 row-start-1 p-5 border border-gray-100 bg-gray-50 rounded-xl"
            :style="activeTab === index ? 'position: relative; z-index: 10;' : 'pointer-events: none; visibility: hidden; z-index: 0;'">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

              {{-- Field 1: Judul Utama --}}
              <div class="flex flex-col gap-1.5 md:col-span-2" x-show="['stats', 'document', 'profile', 'impact', 'partner', 'minimal'].includes(template)">
                <label class="text-[10px] font-bold text-foresty uppercase" x-text="template === 'stats' ? 'Angka Statistik (45%)' : (template === 'profile' ? 'Nama Tokoh' : 'Judul Utama')"></label>
                {{-- 🌟 2. PERHATIKAN: x-model menggantikan wire:model. Livewire hanya disinkronkan tiap 500ms setelah user berhenti ngetik --}}
                <input type="text" x-model="item.title.{{ $code }}" @input.debounce.500ms="syncToLivewire()" class="text-sm font-bold border-gray-200 focus:ring-foresty rounded-md py-2 bg-white shadow-sm">
              </div>

              {{-- Field 2: Subtitle --}}
              <div class="flex flex-col gap-1.5" x-show="['document', 'profile', 'impact', 'minimal'].includes(template)">
                <label class="text-[10px] font-bold text-foresty uppercase" x-text="template === 'profile' ? 'Jabatan' : 'Teks Kecil (Eyebrow)'"></label>
                <input type="text" x-model="item.subtitle.{{ $code }}" @input.debounce.500ms="syncToLivewire()" class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
              </div>

              {{-- Field 3: Deskripsi / Kutipan --}}
              <div class="flex flex-col gap-1.5 md:col-span-2" x-show="['stats', 'profile', 'impact'].includes(template)">
                <label class="text-[10px] font-bold text-foresty uppercase" x-text="template === 'profile' ? 'Bio/Kutipan' : 'Deskripsi Paragraf'"></label>
                <textarea x-model="item.desc.{{ $code }}" @input.debounce.500ms="syncToLivewire()" rows="2" class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm resize-none"></textarea>
              </div>

              {{-- Field 4: Tautan URL --}}
              <div class="flex flex-col gap-1.5" x-show="['document', 'partner'].includes(template)">
                <label class="text-[10px] font-bold text-foresty uppercase">Tautan URL</label>
                <input type="text" x-model="item.url" @input.debounce.500ms="syncToLivewire()" placeholder="https://..." class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
              </div>

              {{-- Field 5: Tags (Dampak) --}}
              <div class="flex flex-col gap-1.5 md:col-span-2" x-show="template === 'impact'">
                <label class="text-[10px] font-bold text-foresty uppercase">Lencana Bawah (Koma)</label>
                <input type="text" x-model="item.tags.{{ $code }}" @input.debounce.500ms="syncToLivewire()" class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm">
              </div>

              {{-- Field 6: Style Variation (Stats) --}}
              <div class="flex flex-col gap-1.5" x-show="template === 'stats'">
                <label class="text-[10px] font-bold text-foresty uppercase">Gaya Kartu</label>
                <select x-model="item.theme" @change="syncToLivewire()" class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1 bg-white shadow-sm">
                  <option value="default">Angka Saja (Bawaan)</option>
                  <option value="boxed">Boxed Putih</option>
                  <option value="dashed">Dashed Gelap</option>
                </select>
              </div>
            </div>
          </div>
        </template>
      </div>

      <button type="button" @click="addItem()"
        class="w-full mt-4 py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty transition-colors text-xs font-bold uppercase flex items-center justify-center gap-2 bg-gray-50">
        <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" /> Tambah Kartu
      </button>
    </div>
  </div>
</div>
