@props(['blockId', 'block', 'code'])

<div class="bg-white border border-gray-200 transition-all duration-300 rounded-xl shadow-md" x-data="{
    isCollapsed: false,
    blockData: $wire.entangle('content.{{ $blockId }}.data'),
    activeCard: 0,

    init() {
        // MIGRASI / INISIALISASI DATA BARU
        if (!this.blockData || !this.blockData.grid) {
            this.blockData = {
                grid: { cols: 3, margin_bottom: 'mb-8' },
                cards: []
            };
        }
    },

    syncPreview() {
        let rawData = JSON.parse(JSON.stringify(this.blockData));
        $wire.set('content.{{ $blockId }}.data', rawData);
    },

    addCard(blueprintType = 'stack') {
        let newCard = {
            id: 'card_' + Math.random().toString(36).substr(2, 9),
            blueprint: blueprintType,
            container: {
                padding: 'p-6',
                radius: 'rounded-[18px]',
                bg: 'bg-white',
                border: 'border border-foresty/15',
                shadow: 'shadow-sm',
                hover: 'hover:-translate-y-1'
            },
            slots: { main: [] }
        };
        this.blockData.cards.push(newCard);
        this.activeCard = this.blockData.cards.length - 1;
        this.syncPreview();
    },

    removeCard(index) {
        this.blockData.cards.splice(index, 1);
        this.activeCard = Math.max(0, this.activeCard - 1);
        this.syncPreview();
    },

    addElement(cardIndex, slotName, elType) {
        let el = { id: 'el_' + Math.random().toString(36).substr(2, 9), type: elType, content: { id: '', en: '' }, style: {} };

        // Setel Design Token Bawaan (Default)
        if (elType === 'text') {
            el.style = { font: 'font-sans', size: 'text-[15px]', weight: 'font-normal', color: 'text-ink-soft', align: 'text-left', margin: 'mb-2' };
        } else if (elType === 'badge') {
            el.style = { bg: 'bg-goldy-soft', color: 'text-foresty', radius: 'rounded-full', align: 'self-start', margin: 'mb-3' };
        }

        this.blockData.cards[cardIndex].slots[slotName].push(el);
        this.syncPreview();
    },

    removeElement(cardIndex, slotName, elIndex) {
        this.blockData.cards[cardIndex].slots[slotName].splice(elIndex, 1);
        this.syncPreview();
    }
}" @input.debounce.1000ms="syncPreview()" wire:key="super-wrapper-{{ $blockId }}">

  <!-- HEADER BLOK -->
  <div class="flex items-center justify-between p-2 bg-gray-100 rounded-t-xl border-b border-gray-200">
    <div class="flex items-center gap-2">
      <div class="p-1 bg-sage-soft rounded-md"><x-dynamic-component component="lucide-blocks" class="h-4 w-4 text-foresty" /></div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest">Atomic Card Builder</span>
    </div>
    <div class="flex gap-4 items-center">
      <select x-model="blockData.grid.cols" @change="syncPreview()" class="text-xs border-gray-300 rounded py-1 bg-white shadow-sm font-bold text-foresty">
        <option value="1">1 Kolom</option>
        <option value="2">2 Kolom</option>
        <option value="3">3 Kolom</option>
      </select>
      <select x-model="blockData.grid.margin_bottom" @change="syncPreview()" class="text-xs border-gray-300 rounded py-1 bg-white shadow-sm font-bold text-foresty">
        <option value="mb-0">Jarak Bawah: 0px</option>
        <option value="mb-8">Jarak Bawah: Normal</option>
        <option value="mb-16">Jarak Bawah: Jauh</option>
      </select>
    </div>
  </div>

  <div class="p-4" wire:ignore>

    {{-- LAYAR 1: KOSONG --}}
    <template x-if="blockData.cards.length === 0">
      <div class="py-10 text-center">
        <p class="text-sm text-gray-500 mb-4">Belum ada kartu. Mulai bangun kartu pertama Anda.</p>
        <button type="button" @click="addCard('stack')" class="px-4 py-2 bg-foresty text-white rounded-lg text-xs font-bold shadow-sm hover:bg-foresty-dark transition-colors">
          + Buat Kartu Baru
        </button>
      </div>
    </template>

    {{-- LAYAR 2: EDITOR KARTU --}}
    <template x-if="blockData.cards.length > 0">
      <div>
        {{-- Tab Kartu --}}
        <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
          <template x-for="(card, index) in blockData.cards" :key="card.id">
            <button type="button" @click="activeCard = index" :class="activeCard === index ? 'bg-foresty text-white' : 'bg-gray-100 text-gray-600'"
              class="px-3 py-1.5 rounded-md text-xs font-bold border flex items-center gap-2">
              <span x-text="'Kartu ' + (index + 1)"></span>
              <div @click.stop="removeCard(index)" class="p-0.5 hover:bg-red-500 hover:text-white rounded"><x-dynamic-component component="lucide-x" class="w-3 h-3" /></div>
            </button>
          </template>
          <button type="button" @click="addCard('stack')" class="px-3 py-1.5 rounded-md text-xs font-bold bg-sage-soft text-foresty hover:bg-foresty hover:text-white transition-colors flex items-center gap-1">
            <x-dynamic-component component="lucide-plus" class="w-3 h-3" /> Tambah Kartu
          </button>
        </div>

        {{-- Area Kerja Kartu Aktif --}}
        <div class="bg-gray-50 border border-gray-200 p-5 rounded-xl">
          <template x-if="blockData.cards[activeCard]">
            <div>
              {{-- PENGATURAN KONTAINER (Design Tokens) --}}
              <div class="flex flex-wrap gap-3 mb-6 p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="text-[10px] font-bold text-gray-400 uppercase w-full mb-1">Gaya Kotak Pembungkus</div>
                <select x-model="blockData.cards[activeCard].container.bg" @change="syncPreview()" class="text-xs border-gray-200 rounded p-1.5">
                  <option value="bg-white">Latar Putih</option>
                  <option value="bg-mist">Latar Mist</option>
                  <option value="bg-foresty text-white">Latar Foresty</option>
                </select>
                <select x-model="blockData.cards[activeCard].container.border" @change="syncPreview()" class="text-xs border-gray-200 rounded p-1.5">
                  <option value="border border-foresty/15">Border Tipis</option>
                  <option value="border-2 border-foresty">Border Tebal</option>
                  <option value="border-0">Tanpa Border</option>
                </select>
                <select x-model="blockData.cards[activeCard].container.radius" @change="syncPreview()" class="text-xs border-gray-200 rounded p-1.5">
                  <option value="rounded-none">Siku</option>
                  <option value="rounded-md">Sedikit Bulat</option>
                  <option value="rounded-[18px]">Sangat Bulat</option>
                </select>
              </div>

              {{-- SLOT UTAMA (Main Slot untuk Blueprint Stack) --}}
              <div class="space-y-3">
                <template x-for="(el, elIndex) in blockData.cards[activeCard].slots.main" :key="el.id">
                  <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm relative group">

                    {{-- Tombol Hapus Elemen --}}
                    <button @click="removeElement(activeCard, 'main', elIndex)" type="button"
                      class="absolute -right-2 -top-2 bg-red-100 text-red-600 p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"><x-dynamic-component component="lucide-x" class="w-3 h-3" /></button>

                    {{-- JIKA ELEMEN TEKS --}}
                    <template x-if="el.type === 'text'">
                      <div class="grid grid-cols-1 gap-2">
                        <div class="flex justify-between items-center mb-1">
                          <span class="text-[10px] font-extrabold text-foresty uppercase tracking-widest bg-foresty/10 px-2 py-0.5 rounded">Teks</span>
                        </div>
                        <input type="text" x-model="el.content.{{ strtolower($code) }}" placeholder="Ketik teks di sini..." class="text-sm font-semibold border-gray-300 rounded focus:ring-foresty w-full">

                        {{-- Token Teks --}}
                        <div class="flex gap-2 flex-wrap mt-1">
                          <select x-model="el.style.font" class="text-[11px] py-1 border-gray-200 rounded">
                            <option value="font-sans">Font Standar</option>
                            <option value="font-display">Font Judul</option>
                            <option value="font-serif">Font Serif</option>
                          </select>
                          <select x-model="el.style.size" class="text-[11px] py-1 border-gray-200 rounded">
                            <option value="text-[13px]">Kecil</option>
                            <option value="text-[15px]">Normal</option>
                            <option value="text-[17px]">Agak Besar</option>
                            <option value="text-[26px]">Besar (Judul)</option>
                          </select>
                          <select x-model="el.style.weight" class="text-[11px] py-1 border-gray-200 rounded">
                            <option value="font-normal">Reguler</option>
                            <option value="font-semibold">Semi Bold</option>
                            <option value="font-bold">Bold</option>
                          </select>
                          <select x-model="el.style.color" class="text-[11px] py-1 border-gray-200 rounded">
                            <option value="text-ink-soft">Abu-abu</option>
                            <option value="text-foresty">Foresty</option>
                            <option value="text-goldy">Goldy</option>
                            <option value="text-coral">Coral</option>
                          </select>
                          <select x-model="el.style.align" class="text-[11px] py-1 border-gray-200 rounded">
                            <option value="text-left">Kiri</option>
                            <option value="text-center">Tengah</option>
                            <option value="text-right">Kanan</option>
                          </select>
                        </div>
                      </div>
                    </template>

                    {{-- JIKA ELEMEN BADGE --}}
                    <template x-if="el.type === 'badge'">
                      <div class="grid grid-cols-1 gap-2">
                        <div class="flex justify-between items-center mb-1">
                          <span class="text-[10px] font-extrabold text-goldy-dark uppercase tracking-widest bg-goldy-soft px-2 py-0.5 rounded">Lencana (Pill)</span>
                        </div>
                        <input type="text" x-model="el.content.{{ strtolower($code) }}" placeholder="Teks lencana..." class="text-sm font-semibold border-gray-300 rounded focus:ring-foresty w-full">

                        {{-- Token Badge --}}
                        <div class="flex gap-2 flex-wrap mt-1">
                          <select x-model="el.style.bg" class="text-[11px] py-1 border-gray-200 rounded">
                            <option value="bg-goldy-soft">Bg Goldy Soft</option>
                            <option value="bg-mist">Bg Mist</option>
                            <option value="bg-[#FBE6E6]">Bg Merah Muda</option>
                            <option value="bg-foresty text-white">Bg Foresty</option>
                          </select>
                          <select x-model="el.style.color" class="text-[11px] py-1 border-gray-200 rounded">
                            <option value="text-foresty">Teks Foresty</option>
                            <option value="text-coral-dark">Teks Coral</option>
                            <option value="text-white">Teks Putih</option>
                          </select>
                          <select x-model="el.style.align" class="text-[11px] py-1 border-gray-200 rounded">
                            <option value="self-start">Posisi Kiri</option>
                            <option value="self-center">Posisi Tengah</option>
                            <option value="self-end">Posisi Kanan</option>
                          </select>
                        </div>
                      </div>
                    </template>

                  </div>
                </template>
              </div>

              {{-- TOMBOL TAMBAH ELEMEN --}}
              <div class="flex gap-2 mt-4">
                <button type="button" @click="addElement(activeCard, 'main', 'text')"
                  class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-600 hover:border-foresty transition-colors flex-1 flex justify-center items-center gap-1">
                  + Tambah Teks
                </button>
                <button type="button" @click="addElement(activeCard, 'main', 'badge')"
                  class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-600 hover:border-goldy transition-colors flex-1 flex justify-center items-center gap-1">
                  + Tambah Lencana
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </template>
  </div>

  {{-- LIVE PREVIEW --}}
  <div x-show="blockData.cards.length > 0" x-cloak class="mt-4 bg-gray-50 border-t border-gray-200 relative overflow-hidden rounded-b-xl">
    <div class="bg-gray-200/60 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
      <span class="text-[10px] font-bold uppercase text-gray-500 tracking-widest">Live Preview</span>
    </div>

    {{-- CSS Isolator: Menyembunyikan kartu lain di preview --}}
    <style
      x-text="`
            .preview-atomic-${blockId} .grid { grid-template-columns: 1fr !important; max-width: 400px; margin: 0 auto; }
            .preview-atomic-${blockId} .grid > *:not(:nth-child(${activeCard + 1})) { display: none !important; }
        `">
    </style>

    <div :class="'preview-atomic-' + blockId" class="p-6 md:p-10 w-full min-h-[150px]">
      @php $liveData = (isset($this) && property_exists($this, 'content')) ? ($this->content[$blockId]['data'] ?? []) : ($block['data'] ?? []); @endphp
      @include('components.blocks.render.card-builder', ['data' => $liveData, 'lang' => strtolower($code), 'isPreview' => true])
    </div>
  </div>
</div>
