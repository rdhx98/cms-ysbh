@props(['blockId', 'block', 'code'])

@php
    $badges = $block['data']['badges'] ?? [];

    // 🌟 Daftar Visual Warna Latar Ikon
    $bgList = [
        ['value' => 'bg-goldy-soft', 'label' => 'Goldy', 'class' => 'bg-[#FDF8E1]'],
        ['value' => 'bg-misty', 'label' => 'Misty', 'class' => 'bg-[#E9F1EB]'],
        ['value' => 'bg-coral/20', 'label' => 'Coral', 'class' => 'bg-[#FBE6E6]'],
    ];
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/section"
  x-data="{
      isCollapsed: false,
      activeTab: 0,
      localAlign: $wire.entangle('content.{{ $blockId }}.data.align').live,
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
  @toggle-collapse-all.window="isCollapsed = $event.detail"
  @sync-columns-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail"
  @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail">

  <!-- Header Editor -->
  <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-all duration-200 group-hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">
    <div class="flex items-center gap-2">
      <button type="button" @click="isCollapsed = !isCollapsed" class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none">
        <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
      </button>
      <div class="p-1 bg-sage-soft rounded-md">
        <x-dynamic-component :component="'lucide-heart-pulse'" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center">
        Grup Lencana
      </span>
    </div>
    <div class="flex items-center justify-end gap-2 flex-1 min-w-0">
      <span class="text-xs font-bold text-foresty uppercase bg-sage-soft px-1.5 py-0.5 rounded shadow-sm shrink-0">
        {{ $code }}
      </span>
    </div>
  </div>

  {{-- BODIES (Repeater Area) --}}
  <div x-show="!isCollapsed" x-collapse x-cloak class="p-4 space-y-5 bg-white rounded-b-xl">

    {{-- 🌟 Pengaturan Tata Letak Blok (Animasi Mulus via Alpine) --}}
    <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
        <label class="text-[10px] font-bold text-gray-400 uppercase">Posisi Lencana:</label>
        <div class="flex items-center gap-1.5 bg-gray-50 p-1 rounded-lg border border-gray-200 shadow-inner">
            <button type="button" @click="localAlign = 'left'"
                class="px-3 py-1 text-xs font-bold rounded-md transition-all duration-300 flex items-center gap-1.5"
                :class="localAlign === 'left' ? 'bg-white shadow text-foresty border border-gray-200/50' : 'text-gray-400 hover:text-foresty hover:bg-gray-200/50'">
                <x-dynamic-component component="lucide-align-left" class="w-3.5 h-3.5" /> Kiri
            </button>
            <button type="button" @click="localAlign = 'center'"
                class="px-3 py-1 text-xs font-bold rounded-md transition-all duration-300 flex items-center gap-1.5"
                :class="localAlign === 'center' ? 'bg-white shadow text-foresty border border-gray-200/50' : 'text-gray-400 hover:text-foresty hover:bg-gray-200/50'">
                <x-dynamic-component component="lucide-align-center" class="w-3.5 h-3.5" /> Tengah
            </button>
            <button type="button" @click="localAlign = 'right'"
                class="px-3 py-1 text-xs font-bold rounded-md transition-all duration-300 flex items-center gap-1.5"
                :class="localAlign === 'right' ? 'bg-white shadow text-foresty border border-gray-200/50' : 'text-gray-400 hover:text-foresty hover:bg-gray-200/50'">
                <x-dynamic-component component="lucide-align-right" class="w-3.5 h-3.5" /> Kanan
            </button>
        </div>
    </div>

    {{-- TAB NAVIGASI LENCANA --}}
    @if(count($badges) > 0)
        <div class="flex flex-wrap gap-2">
            @foreach($badges as $index => $badge)
                <button type="button" @click="activeTab = {{ $index }}"
                    :class="activeTab === {{ $index }} ? 'bg-foresty text-white shadow-md border-transparent' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 hover:border-foresty/50'"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-all duration-300 flex items-center gap-2">

                    <span x-text="$wire.get('content.{{ $blockId }}.data.badges.{{ $index }}.label.{{ $code }}') || 'Lencana {{ $index + 1 }}'" class="max-w-[100px] truncate"></span>

                    <div @click.stop="$wire.set('content.{{ $blockId }}.data.badges', $wire.get('content.{{ $blockId }}.data.badges').filter((_, i) => i !== {{ $index }})); activeTab = 0;"
                          class="p-0.5 rounded hover:bg-red-500 hover:text-white transition-colors ml-1"
                          :class="activeTab === {{ $index }} ? 'text-white/60 hover:bg-white/20' : 'text-gray-400'">
                        <x-dynamic-component component="lucide-x" class="w-3 h-3" />
                    </div>
                </button>
            @endforeach
        </div>
    @endif

    {{-- AREA KONTEN TAB --}}
    <div class="min-h-[180px]">
        @foreach($badges as $index => $badge)
          <div x-show="activeTab === {{ $index }}" x-cloak wire:key="badge-tab-{{ $blockId }}-{{ $index }}"
               class="p-4 border border-gray-100 bg-gray-50 rounded-xl"
               x-data="{
                   localBg: $wire.entangle('content.{{ $blockId }}.data.badges.{{ $index }}.icon_bg').live,
                   localColor: $wire.entangle('content.{{ $blockId }}.data.badges.{{ $index }}.icon_color').live
               }">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Kolom Input Teks & URL --}}
                <div class="space-y-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-foresty uppercase">Label Lencana</label>
                        <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.badges.{{ $index }}.label.{{ $code }}" placeholder="Misal: Ibu & Anak" class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm transition-colors">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-foresty uppercase">Tautan (URL)</label>
                        <div class="flex items-center gap-1">
                            <input type="text" wire:model.live="content.{{ $blockId }}.data.badges.{{ $index }}.url" placeholder="#program atau https://..." class="w-full text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white shadow-sm transition-colors">
                            <button type="button" @click="$dispatch('buka-modal-link', { target: 'content.{{ $blockId }}.data.badges.{{ $index }}.url' })" class="p-1.5 border border-gray-200 bg-white hover:bg-sage-soft text-gray-400 hover:text-foresty rounded-md shadow-sm transition-colors cursor-pointer shrink-0">
                                <x-dynamic-component component="lucide-search" class="w-4 h-4" stroke-width="2.5" />
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Kolom Input Desain Ikon --}}
                <div class="space-y-4 md:border-l border-gray-200 md:pl-6">

                  {{-- Panggil Pemilih Ikon --}}
                  <x-editor.icon-picker label="Ikon Lencana" model="content.{{ $blockId }}.data.badges.{{ $index }}.icon" />


                    <div class="grid grid-cols-2 gap-4">
                        {{-- 🌟 PEMILIH VISUAL WARNA LATAR (Animasi Mulus via Alpine) --}}
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold text-foresty uppercase">Warna Latar</label>
                            <div class="grid grid-cols-1 xl:grid-cols-3 gap-2">
                                @foreach($bgList as $bg)
                                    <button type="button"
                                        @click="localBg = '{{ $bg['value'] }}'"
                                        class="flex flex-col items-center justify-center p-2 rounded-lg border transition-all duration-300 bg-white"
                                        :class="localBg === '{{ $bg['value'] }}' ? 'ring-2 ring-foresty ring-offset-1 border-transparent shadow-sm scale-105' : 'border-gray-200 hover:border-foresty/50 hover:bg-gray-50'">
                                        <span class="w-4 h-4 rounded-full {{ $bg['class'] }} mb-1 shadow-inner"></span>
                                        <span class="text-[9px] font-bold text-gray-700 leading-none">{{ $bg['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- 🌟 PEMILIH WARNA HEX NATIVE (Ultra Responsif via x-model) --}}
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold text-foresty uppercase">Warna Ikon (Hex)</label>
                            <div class="flex items-center gap-1.5 p-1 bg-white border border-gray-200 rounded-md shadow-sm focus-within:ring-1 focus-within:ring-foresty focus-within:border-foresty transition-all duration-300">
                                {{-- Input Color Native --}}
                                <input type="color" x-model="localColor" class="w-7 h-7 rounded cursor-pointer border-0 p-0 bg-transparent shrink-0">
                                {{-- Input Text --}}
                                <input type="text" x-model="localColor" class="w-full text-xs border-0 focus:ring-0 p-0 text-gray-700 bg-transparent uppercase font-mono" placeholder="#064F3B">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
          </div>
        @endforeach

        @if(count($badges) === 0)
            <div class="p-8 border-2 border-dashed border-gray-200 rounded-xl text-center flex flex-col items-center justify-center bg-gray-50">
                <x-dynamic-component component="lucide-tags" class="w-8 h-8 text-gray-300 mb-2" />
                <p class="text-xs font-bold text-gray-400 uppercase">Belum ada lencana</p>
                <p class="text-[10px] text-gray-400 mt-1">Tambahkan lencana untuk memperkaya deskripsi konten.</p>
            </div>
        @endif
    </div>

    {{-- 🌟 PRATINJAU GABUNGAN (Super Responsif & Mulus) --}}
    <div class="pt-5 border-t border-dashed border-gray-200">
        <span class="block text-[10px] font-bold text-gray-400 uppercase mb-3">Pratinjau Grup Lencana:</span>
        <div class="flex flex-wrap gap-3 transition-all duration-500 ease-out"
             :class="{
                 'justify-start': localAlign === 'left',
                 'justify-center': localAlign === 'center',
                 'justify-end': localAlign === 'right'
             }">
            @foreach($badges as $index => $badge)
                <div x-data="{
                        label: $wire.entangle('content.{{ $blockId }}.data.badges.{{ $index }}.label.{{ $code }}').live,
                        bg: $wire.entangle('content.{{ $blockId }}.data.badges.{{ $index }}.icon_bg').live,
                        color: $wire.entangle('content.{{ $blockId }}.data.badges.{{ $index }}.icon_color').live
                     }"
                     class="inline-flex items-center gap-2.5 bg-white border border-foresty/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-foresty shadow-[0_10px_20px_-10px_rgba(6,45,35,0.2)] pointer-events-none transition-all duration-300">

                    {{-- Animasi transisi warna background --}}
                    <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 transition-colors duration-300"
                          :class="bg === 'bg-mist' ? 'bg-[#E9F1EB]' : (bg || 'bg-misty')">

                        {{-- Animasi transisi warna ikon HEX --}}
                        <div :style="`color: ${color || '#064F3B'};`" class="w-[15px] h-[15px] transition-colors duration-300 flex items-center justify-center">
                            <x-dynamic-component :component="'lucide-' . ($badge['icon'] ?? 'check-circle')" stroke-width="2.5" class="w-full h-full" />
                        </div>
                    </span>

                    <span x-text="label || 'Ketik lencana...'"></span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Tombol Tambah --}}
    <button type="button" @click="
        let arr = $wire.get('content.{{ $blockId }}.data.badges') || [];
        arr.push({ label: { id: '', en: '' }, url: '#', icon: 'check-circle', icon_bg: 'bg-goldy-soft', icon_color: '#064F3B' });
        $wire.set('content.{{ $blockId }}.data.badges', arr);
        setTimeout(() => activeTab = arr.length - 1, 100);
    " class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50 hover:bg-sage-soft">
        <x-dynamic-component component="lucide-plus-square" class="w-4.5 h-4.5" />
        Tambah Lencana
    </button>

  </div>
</div>
