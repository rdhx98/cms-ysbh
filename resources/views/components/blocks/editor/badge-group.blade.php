@props(['blockId', 'block', 'code'])

@php
  $badges = $block['data']['badges'] ?? [];

  // 🌟 Daftar Ikon (Sama seperti Eyebrow)
  $iconsList = [
      'newspaper',
      'bookmark',
      'sparkles',
      'tag',
      'folder',
      'flag',
      'globe',
      'heart',
      'star',
      'shield',
      'award',
      'bell',
      'briefcase',
      'calendar',
      'check-circle',
      'compass',
      'cpu',
      'file-text',
      'filter',
      'gift',
      'home',
      'info',
      'layers',
      'life-buoy',
      'lightbulb',
      'link',
      'lock',
      'map',
      'megaphone',
      'message-square',
      'mic',
      'moon',
      'package',
      'paperclip',
      'pen-tool',
      'pie-chart',
      'play',
      'power',
      'radio',
      'rss',
      'search',
      'send',
      'settings',
      'share-2',
      'shield-check',
      'shopping-bag',
      'shopping-cart',
      'sliders',
      'smile',
      'speaker',
      'sun',
      'target',
      'terminal',
      'thumbs-up',
      'wrench',
      'trash-2',
      'trending-up',
      'triangle',
      'truck',
      'tv',
      'user',
      'users',
      'video',
      'volume-2',
      'watch',
      'zap',
      'heart-pulse',
      'baby',
      'syringe',
      'bug',
  ];

  // 🌟 Daftar Warna untuk Latar Ikon (Bg)
  $bgColorsList = [
      ['name' => 'Misty (Biru/Hijau Air)', 'class' => 'bg-[#E9F1EB]', 'value' => 'bg-misty'],
      ['name' => 'Goldy Soft (Kuning Lembut)', 'class' => 'bg-[#F7EBAF]', 'value' => 'bg-goldy-soft'],
      ['name' => 'Coral Soft (Merah Lembut)', 'class' => 'bg-[#FBE6E6]', 'value' => 'bg-[#FBE6E6]'],
      ['name' => 'Sage Soft', 'class' => 'bg-[#D2E7DF]', 'value' => 'bg-sage-soft'],
      ['name' => 'Putih Murni', 'class' => 'bg-white', 'value' => 'bg-white'],
  ];

  // 🌟 Daftar Warna untuk Garis Ikon (Stroke)
  $iconColorsList = [
      ['name' => 'Foresty (Hijau Gelap)', 'hex' => '#064F3B'],
      ['name' => 'Coral (Merah Terang)', 'hex' => '#E42326'],
      ['name' => 'Goldy (Kuning Emas)', 'hex' => '#EBCC26'],
      ['name' => 'Charcoal (Hitam/Abu)', 'hex' => '#1f2937'],
  ];
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm transition-all duration-200 group/section" x-data="{
    isCollapsed: false,
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
  @force-expand-children.window="if ($event.detail.includes('{{ $blockId }}')) { isCollapsed = false; window.blockCollapseState['{{ $blockId }}'] = false; }">

  <!-- Header Editor -->
  <div class="flex items-center justify-between p-2 bg-gray-100 cursor-pointer select-none transition-all duration-200 group-hover:bg-white" :class="isCollapsed ? 'rounded-xl' : 'rounded-t-xl border-b border-gray-200'">
    <div class="flex items-center gap-2">
      <button type="button" x-on:click="isCollapsed = !isCollapsed; $dispatch('sync-collapse-{{ strtolower($blockId) }}', isCollapsed)"
        class="p-1 hover:bg-sage-soft text-foresty rounded-full transition-all duration-200 focus:outline-none">
        <x-dynamic-component component="lucide-circle-chevron-down" class="w-5 h-5 text-foresty transition-transform duration-200" x-bind:class="isCollapsed ? '-rotate-90' : 'rotate-0'" />
      </button>
      <div class="p-1 bg-sage-soft rounded-md">
        <x-dynamic-component :component="'lucide-tags'" class="h-4 w-4 text-forest" stroke-width="2.5" />
      </div>
      <span class="text-xs font-extrabold text-gray-500 uppercase tracking-widest flex items-center">
        Grup Lencana (Badge)
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

    @foreach ($badges as $index => $badge)
      {{-- <div wire:key="badge-{{ $blockId }}-{{ $index }}" class="p-4 border border-gray-100 bg-gray-50 rounded-xl relative" x-data="{
          openPicker: false,
          searchQuery: '',
          localIcon: '{{ $badge['icon'] ?? 'check-circle' }}',
          localIconBg: '{{ $badge['icon_bg'] ?? 'bg-misty' }}',
          localIconColor: '{{ $badge['icon_color'] ?? '#064F3B' }}'
      }">

        {{-- Tombol Hapus --}
        <button type="button" @click="$wire.set('content.{{ $blockId }}.data.badges', $wire.get('content.{{ $blockId }}.data.badges').filter((_, i) => i !== {{ $index }}))"
          class="absolute top-3 right-3 p-1.5 bg-white text-gray-400 hover:text-coral hover:bg-[#FBE6E6] rounded-md shadow-sm border border-gray-200 transition-colors z-10">
          <x-dynamic-component component="lucide-trash-2" class="w-4 h-4" />
        </button>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-4">

          {{-- KOLOM KIRI: Teks & Tautan --}
          <div class="space-y-3">
            <div class="flex flex-col gap-1">
              <label class="text-[10px] font-bold text-foresty uppercase">Label Lencana</label>
              <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.badges.{{ $index }}.label.{{ $code }}" placeholder="Misal: Ibu & Anak"
                class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white">
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-[10px] font-bold text-foresty uppercase">Tautan (URL)</label>
              <input type="text" wire:model.live="content.{{ $blockId }}.data.badges.{{ $index }}.url" placeholder="#program atau /tentang"
                class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white">
            </div>
          </div>

          {{-- KOLOM KANAN: Visual (Ikon & Warna) --}
          <div class="space-y-4">

            {{-- Icon Picker (Dropdown Modal) --}
            <div class="flex flex-col gap-1">
              <label class="text-[10px] font-bold text-foresty uppercase">Pilih Ikon</label>
              <div class="relative">
                <button type="button" @click="openPicker = !openPicker"
                  class="w-full flex items-center justify-between border border-gray-200 bg-white text-gray-700 rounded-md py-1.5 px-3 text-xs shadow-sm hover:bg-gray-50 transition-colors">
                  <div class="flex items-center gap-2 truncate">
                    <span class="w-4 h-4 shrink-0 flex items-center justify-center">
                      @foreach ($iconsList as $icon)
                        <span x-show="localIcon === '{{ $icon }}'" x-cloak style="display: none;">
                          <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4" />
                        </span>
                      @endforeach
                    </span>
                    <span class="truncate uppercase font-mono text-[11px]" x-text="localIcon || 'Pilih Ikon'"></span>
                  </div>
                  <x-dynamic-component component="lucide-chevron-down" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                </button>

                {{-- Modal Ikon --}
                <div x-show="openPicker" @click.away="openPicker = false" x-cloak style="display: none;"
                  class="absolute left-0 mt-1 w-64 bg-white border border-gray-200 rounded-xl shadow-xl p-3 z-50 flex flex-col gap-2">
                  <input type="text" x-model="searchQuery" placeholder="Cari ikon..." class="w-full text-xs border border-gray-200 rounded-lg px-2.5 py-1.5 focus:ring-foresty" />
                  <div class="grid grid-cols-5 gap-1.5 max-h-48 overflow-y-auto p-1 scrollbar-thin">
                    @foreach ($iconsList as $icon)
                      <button type="button" x-show="'{{ $icon }}'.includes(searchQuery.toLowerCase())"
                        @click="localIcon = '{{ $icon }}'; $wire.set('content.{{ $blockId }}.data.badges.{{ $index }}.icon', '{{ $icon }}'); openPicker = false; searchQuery = '';"
                        class="p-2 rounded-lg flex items-center justify-center hover:bg-sage-soft transition-colors" :class="localIcon === '{{ $icon }}' ? 'bg-foresty text-white' : 'bg-gray-50 text-foresty'"
                        title="{{ $icon }}">
                        <span class="w-4 h-4 flex items-center justify-center">
                          <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 shrink-0" />
                        </span>
                      </button>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>

            {{-- Color Swatches --}
            <div class="grid grid-cols-2 gap-4">
              {{-- Warna Background --}
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Latar Ikon</label>
                <div class="flex flex-wrap gap-1.5">
                  @foreach ($bgColorsList as $c)
                    <button type="button" @click="localIconBg = '{{ $c['value'] }}'; $wire.set('content.{{ $blockId }}.data.badges.{{ $index }}.icon_bg', '{{ $c['value'] }}')"
                      class="w-5 h-5 rounded-full border transition-all cursor-pointer shadow-sm {{ $c['class'] }}" 
											{-- 🌟 3. Logika Ring yang sama diterapkan di sini --}
                      :class="localIconBg === '{{ $c['value'] }}'
                          ?
                          'ring-2 ring-foresty ring-offset-2 ring-offset-white border-transparent scale-110' :
                          'border-gray-200 hover:scale-110 hover:border-gray-300'"
                      title="{{ $c['name'] }}">
                    </button>
                  @endforeach
                </div>
              </div>
              {{-- Warna Ikon (Garis) --}
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Warna Ikon</label>
                <div class="flex flex-wrap gap-1.5">
                  @foreach ($iconColorsList as $ic)
                    <button type="button" @click="localIconColor = '{{ $ic['hex'] }}'; $wire.set('content.{{ $blockId }}.data.badges.{{ $index }}.icon_color', '{{ $ic['hex'] }}')"
                      {{-- 🌟 1. Ubah border-2 menjadi border biasa sebagai garis tepi saat tidak dipilih --} class="w-5 h-5 rounded-full border transition-all cursor-pointer shadow-sm" 
											{{-- 🌟 2. Tambahkan ring-2, ring-offset-2, dan ring-offset-white saat dipilih --}
                      :class="localIconColor === '{{ $ic['hex'] }}'
                          ?
                          'ring-2 ring-foresty ring-offset-2 ring-offset-white border-transparent scale-110' :
                          'border-gray-200 hover:scale-110 hover:border-gray-300'"
                      style="background-color: {{ $ic['hex'] }}" title="{{ $ic['name'] }}">
                    </button>
                  @endforeach
                </div>
              </div>
            </div>

          </div>
        </div>

        {{-- 🌟 LIVE PREVIEW COMPONENT 🌟 --}
        <div class="pt-4 mt-4 border-t border-dashed border-gray-200 flex flex-col items-center sm:items-start">
          <span class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Pratinjau Langsung:</span>

          {{-- Wadah Badge (Mengadopsi kelas render asli) --}
          <div class="inline-flex items-center gap-2.5 bg-white border border-foresty/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-foresty shadow-[0_10px_20px_-10px_rgba(6,45,35,0.2)]">

            {{-- Lingkaran Ikon --}
            <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 transition-colors duration-300"
              :class="localIconBg === 'bg-mist' ? 'bg-[#E9F1EB]' : (localIconBg === 'bg-misty' ? 'bg-[#E9F1EB]' : localIconBg)">

              {{-- Ikon SVG --}
              <span class="flex items-center justify-center transition-colors duration-300" :style="`color: ${localIconColor}`">
                @foreach ($iconsList as $icon)
                  <span x-show="localIcon === '{{ $icon }}'" x-cloak style="display: none;">
                    <x-dynamic-component :component="'lucide-' . $icon" class="w-[15px] h-[15px]" stroke-width="2.5" />
                  </span>
                @endforeach
              </span>
            </span>

            {{-- Teks Label --}
            <span x-text="$wire.get('content.{{ $blockId }}.data.badges.{{ $index }}.label.{{ $code }}') || 'Ketik label...'"></span>
          </div>
        </div>

      </div> --}}
      <div wire:key="badge-{{ $blockId }}-{{ $index }}" class="p-4 border border-gray-100 bg-gray-50 rounded-xl relative" {{-- 🌟 1. KUNCI PERBAIKAN: Gunakan $wire.entangle.live agar Alpine & Livewire tersinkron otomatis --}} x-data="{
          openPicker: false,
          searchQuery: '',
          localIcon: $wire.entangle('content.{{ $blockId }}.data.badges.{{ $index }}.icon').live,
          localIconBg: $wire.entangle('content.{{ $blockId }}.data.badges.{{ $index }}.icon_bg').live,
          localIconColor: $wire.entangle('content.{{ $blockId }}.data.badges.{{ $index }}.icon_color').live
      }">

        {{-- Tombol Hapus --}}
        <button type="button" @click="$wire.set('content.{{ $blockId }}.data.badges', $wire.get('content.{{ $blockId }}.data.badges').filter((_, i) => i !== {{ $index }}))"
          class="absolute top-3 right-3 p-1.5 bg-white text-gray-400 hover:text-coral hover:bg-[#FBE6E6] rounded-md shadow-sm border border-gray-200 transition-colors z-10">
          <x-dynamic-component component="lucide-trash-2" class="w-4 h-4" />
        </button>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-4">

          {{-- KOLOM KIRI: Teks & Tautan --}}
          <div class="space-y-3">
            <div class="flex flex-col gap-1">
              <label class="text-[10px] font-bold text-foresty uppercase">Label Lencana</label>
              <input type="text" wire:model.live.debounce.300ms="content.{{ $blockId }}.data.badges.{{ $index }}.label.{{ $code }}" placeholder="Misal: Ibu & Anak"
                class="text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white">
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-[10px] font-bold text-foresty uppercase">Tautan (URL)</label>
              <div class="flex items-center gap-1">
                {{-- 🌟 1. Variabel wire:model sekarang diisi dengan benar, bukan "..." --}}
                <input type="text" wire:model.live="content.{{ $blockId }}.data.badges.{{ $index }}.url" placeholder="#program atau https://..."
                  class="w-full text-xs border-gray-200 focus:ring-foresty rounded-md py-1.5 bg-white">

                {{-- 🌟 2. Tombol pemanggil Modal Pencarian Pintar --}}
                <button type="button" @click="$dispatch('buka-modal-link', { target: 'content.{{ $blockId }}.data.badges.{{ $index }}.url' })"
                  class="p-1.5 border border-gray-200 bg-white hover:bg-sage-soft text-gray-400 hover:text-foresty rounded-md shadow-sm transition-colors cursor-pointer shrink-0" title="Cari Tautan">
                  <x-dynamic-component component="lucide-search" class="w-4 h-4" stroke-width="2.5" />
                </button>
              </div>
            </div>
          </div>

          {{-- KOLOM KANAN: Visual (Ikon & Warna) --}}
          <div class="space-y-4">

            {{-- Icon Picker --}}
            <div class="flex flex-col gap-1">
              <label class="text-[10px] font-bold text-foresty uppercase">Pilih Ikon</label>
              <div class="relative">
                <button type="button" @click="openPicker = !openPicker"
                  class="w-full flex items-center justify-between border border-gray-200 bg-white text-gray-700 rounded-md py-1.5 px-3 text-xs shadow-sm hover:bg-gray-50 transition-colors">
                  <div class="flex items-center gap-2 truncate">
                    <span class="w-4 h-4 shrink-0 flex items-center justify-center">
                      @foreach ($iconsList as $icon)
                        <span x-show="localIcon === '{{ $icon }}'" x-cloak style="display: none;">
                          <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4" />
                        </span>
                      @endforeach
                    </span>
                    <span class="truncate uppercase font-mono text-[11px]" x-text="localIcon || 'Pilih Ikon'"></span>
                  </div>
                  <x-dynamic-component component="lucide-chevron-down" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                </button>

                {{-- Modal Ikon --}}
                <div x-show="openPicker" @click.away="openPicker = false" x-cloak style="display: none;"
                  class="absolute left-0 mt-1 w-64 bg-white border border-gray-200 rounded-xl shadow-xl p-3 z-50 flex flex-col gap-2">
                  <input type="text" x-model="searchQuery" placeholder="Cari ikon..." class="w-full text-xs border border-gray-200 rounded-lg px-2.5 py-1.5 focus:ring-foresty" />
                  <div class="grid grid-cols-5 gap-1.5 max-h-48 overflow-y-auto p-1 scrollbar-thin">
                    @foreach ($iconsList as $icon)
                      {{-- 🌟 2. Cukup ubah localIcon, $wire.entangle akan menyimpannya otomatis! --}}
                      <button type="button" x-show="'{{ $icon }}'.includes(searchQuery.toLowerCase())" @click="localIcon = '{{ $icon }}'; openPicker = false; searchQuery = '';"
                        class="p-2 rounded-lg flex items-center justify-center hover:bg-sage-soft transition-colors" :class="localIcon === '{{ $icon }}' ? 'bg-foresty text-white' : 'bg-gray-50 text-foresty'"
                        title="{{ $icon }}">
                        <span class="w-4 h-4 flex items-center justify-center">
                          <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 shrink-0" />
                        </span>
                      </button>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>

            {{-- Color Swatches --}}
            <div class="grid grid-cols-2 gap-4">
              {{-- Warna Background --}}
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Latar Ikon</label>
                <div class="flex flex-wrap gap-1.5">
                  @foreach ($bgColorsList as $c)
                    {{-- 🌟 3. Hapus $wire.set, biarkan Alpine mengatur UI dan Entangle yang melapor ke server --}}
                    <button type="button" @click="localIconBg = '{{ $c['value'] }}'" class="w-5 h-5 rounded-full border transition-all cursor-pointer shadow-sm {{ $c['class'] }}"
                      :class="localIconBg === '{{ $c['value'] }}'
                          ?
                          'ring-2 ring-foresty ring-offset-2 ring-offset-white border-transparent scale-110' :
                          'border-gray-200 hover:scale-110 hover:border-gray-300'"
                      title="{{ $c['name'] }}">
                    </button>
                  @endforeach
                </div>
              </div>

              {{-- Warna Ikon (Garis) --}}
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-foresty uppercase">Warna Ikon</label>
                <div class="flex flex-wrap gap-1.5">
                  @foreach ($iconColorsList as $ic)
                    {{-- 🌟 3. Hapus $wire.set, biarkan Alpine mengatur UI dan Entangle yang melapor ke server --}}
                    <button type="button" @click="localIconColor = '{{ $ic['hex'] }}'" class="w-5 h-5 rounded-full border transition-all cursor-pointer shadow-sm"
                      :class="localIconColor === '{{ $ic['hex'] }}'
                          ?
                          'ring-2 ring-foresty ring-offset-2 ring-offset-white border-transparent scale-110' :
                          'border-gray-200 hover:scale-110 hover:border-gray-300'"
                      style="background-color: {{ $ic['hex'] }}" title="{{ $ic['name'] }}">
                    </button>
                  @endforeach
                </div>
              </div>
            </div>

          </div>
        </div>
        {{-- 🌟 LIVE PREVIEW COMPONENT 🌟 --}}
        <div class="pt-4 mt-4 border-t border-dashed border-gray-200 flex flex-col items-center sm:items-start">
          <span class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Pratinjau Langsung:</span>

          {{-- Wadah Badge (Mengadopsi kelas render asli) --}}
          <div class="inline-flex items-center gap-2.5 bg-white border border-foresty/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-foresty shadow-[0_10px_20px_-10px_rgba(6,45,35,0.2)]">

            {{-- Lingkaran Ikon --}}
            <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 transition-colors duration-300"
              :class="localIconBg === 'bg-mist' ? 'bg-[#E9F1EB]' : (localIconBg === 'bg-misty' ? 'bg-[#E9F1EB]' : localIconBg)">

              {{-- Ikon SVG --}}
              <span class="flex items-center justify-center transition-colors duration-300" :style="`color: ${localIconColor}`">
                @foreach ($iconsList as $icon)
                  <span x-show="localIcon === '{{ $icon }}'" x-cloak style="display: none;">
                    <x-dynamic-component :component="'lucide-' . $icon" class="w-[15px] h-[15px]" stroke-width="2.5" />
                  </span>
                @endforeach
              </span>
            </span>

            {{-- Teks Label --}}
            <span x-text="$wire.get('content.{{ $blockId }}.data.badges.{{ $index }}.label.{{ $code }}') || 'Ketik label...'"></span>
          </div>
        </div>

      </div>
    @endforeach

    {{-- Tombol Tambah --}}
    <button type="button"
      @click="
        let arr = $wire.get('content.{{ $blockId }}.data.badges') || [];
        arr.push({ label: { id: '', en: '' }, url: '#', icon: 'heart-pulse', icon_bg: 'bg-misty', icon_color: '#064F3B' });
        $wire.set('content.{{ $blockId }}.data.badges', arr);
    "
      class="w-full py-3 border-2 border-dashed border-gray-300 text-gray-500 rounded-xl hover:border-foresty hover:text-foresty transition-colors text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 bg-gray-50 hover:bg-sage-soft">
      <x-dynamic-component component="lucide-badge-plus" class="w-4.5 h-4.5" />
      Tambah Lencana
    </button>

  </div>
</div>
