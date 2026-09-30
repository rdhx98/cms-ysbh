@props (["blockId", "block", "code", "activeLocales" => []])

@php
  $badges = $block["data"]["badges"] ?? [];
  $bgList = [
    ["value" => "bg-goldy-soft", "label" => "Goldy", "class" => "bg-[#FDF8E1]"],
    ["value" => "bg-misty", "label" => "Misty", "class" => "bg-[#E9F1EB]"],
    ["value" => "bg-coral/20", "label" => "Coral", "class" => "bg-[#FBE6E6]"],
  ];
@endphp

<x-blocks.editor.wrapper :block-id="$blockId" :block="$block">
  <x-slot:title>
    <div
      class="bg-sage-soft text-foresty flex h-5 w-5 items-center justify-center rounded-sm shadow-sm"
    >
      <x-dynamic-component
        component="lucide-heart-pulse"
        class="h-3.5 w-3.5"
        stroke-width="2.5"
      />
    </div>
    Grup Lencana
  </x-slot:title>

  <x-slot:snippet>
    <span
      class="block w-full text-right text-xs font-medium text-gray-400 sm:text-left"
    >
      <span class="font-bold text-gray-500">{{ count($badges) }}</span> Lencana
    </span>
  </x-slot:snippet>

  <x-slot:settings>
    <div
      class="flex items-center gap-2"
      x-data="{ localAlign: $wire.content?.['{{ $blockId }}']?.data?.align ?? 'left' }"
    >
      <span
        class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase"
        >Posisi:</span
      >
      <div class="flex items-center rounded-md bg-gray-200 p-0.5 shadow-inner">
        @foreach ([
            ["left", "lucide-align-left"],
            ["center", "lucide-align-center"],
            ["right", "lucide-align-right"]
          ]
          as $opt)
          <button
            type="button"
            @click="localAlign = '{{ $opt[0] }}';$wire.set('content.{{ $blockId }}.data.align', '{{$opt[0] }}')"
            class="rounded p-1 transition-all outline-none"
            :class="localAlign === '{{ $opt[0] }}' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
          >
            <x-dynamic-component
              component="{{ $opt[1] }}"
              class="h-3.5 w-3.5"
            />
          </button>
        @endforeach
      </div>
    </div>
  </x-slot:settings>

  <!-- ========================================== -->
  <!-- TAB NAVIGASI & ISI LENCANA                 -->
  <!-- ========================================== -->
  <div
    x-data="{ activeTab: 0 }"
    @sync-active-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail"
    class="flex flex-col"
  >
    @if (count($badges) > 0)
      <!-- TAB NAVIGASI -->
      <div class="mb-4 flex flex-wrap gap-2 border-b border-gray-100 pb-3">
        @foreach ($badges as $index => $badge)
          <button
            type="button"
            @click="activeTab = {{ $index }};$dispatch('sync-active-tab-{{ strtolower($blockId) }}', {{$index }})"
            :class="activeTab === {{ $index }} ? 'bg-foresty text-white shadow-md border-transparent' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 hover:border-foresty/50'"
            class="flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-bold transition-all outline-none"
          >
            <span
              class="max-w-[100px] truncate"
              x-text="$wire.content?.['{{ $blockId }}']?.data?.badges?.[{{ $index }}]?.label?.['{{ app()->getLocale() }}'] ?? 'Lencana {{ $index + 1 }}'"
            ></span>

            <div
              @click.stop="$wire.set('content.{{ $blockId }}.data.badges', ($wire.content['{{ $blockId }}'].data.badges).filter((_, i) => i !== {{$index }})); activeTab = 0; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', 0);"
              class="ml-1 rounded p-0.5 transition-colors hover:bg-red-500 hover:text-white"
              :class="activeTab === {{ $index }} ? 'text-white/60 hover:bg-white/20' : 'text-gray-400'"
            >
              <x-dynamic-component component="lucide-x" class="h-3 w-3" />
            </div>
          </button>
        @endforeach
      </div>

      <!-- KONTEN TAB -->
      <div class="min-h-[180px]">
        @foreach ($badges as $index => $badge)
          <div
            x-show="activeTab === {{ $index }}"
            x-cloak
            wire:key="badge-tab-{{ $blockId }}-{{$index }}"
            class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 shadow-inner"
          >
            <!-- Global Setting (URL & Visual) -->
            <div
              class="mb-6 grid grid-cols-1 gap-6 border-b border-gray-200 pb-6 md:grid-cols-2"
            >
              <div class="flex flex-col gap-4">
                <!-- Tautan -->
                <div class="flex flex-col gap-1.5">
                  <label class="text-foresty text-[10px] font-bold uppercase"
                    >Tautan (URL)</label
                  >
                  <div class="flex items-center gap-1">
                    <input
                      type="text"
                      wire:model.live="content.{{ $blockId }}.data.badges.{{$index }}.url"
                      class="focus:border-foresty focus:ring-foresty w-full rounded-md border-gray-200 bg-white py-1.5 text-xs shadow-sm transition-colors"
                    />
                    <button
                      type="button"
                      @click="$dispatch('buka-modal-link', { target: 'content.{{ $blockId }}.data.badges.{{$index }}.url' })"
                      class="hover:bg-sage-soft hover:text-foresty shrink-0 cursor-pointer rounded-md border border-gray-200 bg-white p-1.5 text-gray-400 shadow-sm transition-colors"
                    >
                      <x-dynamic-component
                        component="lucide-search"
                        class="h-4 w-4"
                        stroke-width="2.5"
                      />
                    </button>
                  </div>
                </div>
                <!-- Pemanggil Komponen Icon Picker -->
                <x-editor.icon-picker
                  label="Ikon Lencana"
                  model="content.{{ $blockId }}.data.badges.{{$index }}.icon"
                />
              </div>

              <!-- Latar & Warna -->
              <div
                class="flex flex-col gap-4"
                x-data="{ localBg: $wire.content?.['{{ $blockId }}']?.data?.badges?.[{{ $index }}]?.icon_bg ?? 'bg-misty', localColor: $wire.content?.['{{ $blockId }}']?.data?.badges?.[{{ $index }}]?.icon_color ?? '#064F3B' }"
              >
                <div class="flex flex-col gap-1.5">
                  <label class="text-foresty text-[10px] font-bold uppercase"
                    >Warna Latar</label
                  >
                  <div class="grid grid-cols-3 gap-2">
                    @foreach ($bgList as $bg)
                      <button
                        type="button"
                        @click="localBg = '{{ $bg['value'] }}'; $wire.set('content.{{$blockId }}.data.badges.{{ $index }}.icon_bg', '{{$bg['value'] }}')"
                        class="flex flex-col items-center justify-center rounded-lg border bg-white p-2 transition-all duration-300 outline-none"
                        :class="localBg === '{{ $bg['value'] }}' ? 'border-transparent ring-2 ring-foresty ring-offset-1 shadow-sm scale-105' : 'border-gray-200 hover:border-foresty/50 hover:bg-gray-50'"
                      >
                        <span
                          class="mb-1 h-4 w-4 rounded-full shadow-inner {{ $bg['class'] }}"
                        ></span>
                        <span
                          class="text-[9px] leading-none font-bold text-gray-700"
                          >{{ $bg["label"] }}</span
                        >
                      </button>
                    @endforeach
                  </div>
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-foresty text-[10px] font-bold uppercase"
                    >Warna Ikon (Hex)</label
                  >
                  <div
                    class="focus-within:border-foresty focus-within:ring-foresty flex items-center gap-1.5 rounded-md border border-gray-200 bg-white p-1 shadow-sm transition-all duration-300 focus-within:ring-1"
                  >
                    <input
                      type="color"
                      x-model="localColor"
                      @change="$wire.set('content.{{ $blockId }}.data.badges.{{$index }}.icon_color', localColor)"
                      class="h-7 w-7 shrink-0 cursor-pointer rounded border-0 bg-transparent p-0"
                    />
                    <input
                      type="text"
                      x-model="localColor"
                      @change="$wire.set('content.{{ $blockId }}.data.badges.{{$index }}.icon_color', localColor)"
                      class="w-full border-0 bg-transparent p-0 font-mono text-xs text-gray-700 uppercase focus:ring-0"
                      placeholder="#064F3B"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Teks Label (Multi-Bahasa) -->
            <div
              class="grid gap-6"
              :class="effectiveLayout === 'single'
                ? 'grid-cols-1'
                : splitLanguages.length >= 3
                  ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
                  : 'grid-cols-1 md:grid-cols-2'"
            >
              @foreach ($activeLocales as $lang)
                <div
                  wire:key="badge-label-{{ $blockId }}-{{ $index }}-{{$lang }}"
                  x-show="effectiveLayout === 'single' ? singleActiveLang === '{{ $lang }}' : splitLanguages.includes('{{$lang }}')"
                  x-cloak
                  class="flex flex-col gap-1.5"
                >
                  <div class="flex items-center gap-2">
                    <span
                      class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase shadow-sm"
                      >{{ $lang }}</span
                    >
                    <span class="text-foresty text-[10px] font-bold uppercase"
                      >Teks Label</span
                    >
                  </div>
                  <input
                    type="text"
                    wire:model.live.debounce.300ms="content.{{ $blockId }}.data.badges.{{ $index }}.label.{{$lang }}"
                    class="focus:border-foresty focus:ring-foresty rounded-md border-gray-200 bg-white py-1.5 text-xs shadow-sm transition-colors"
                  />
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div
        class="mb-6 flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 p-8 text-center"
      >
        <x-dynamic-component
          component="lucide-tags"
          class="mb-2 h-8 w-8 text-gray-300"
        />
        <p class="text-xs font-bold text-gray-400 uppercase">Belum ada lencana</p>
      </div>
    @endif

    <!-- TOMBOL TAMBAH -->
    <button
      type="button"
      @click="
        let arr = $wire.content?.['{{ $blockId }}']?.data?.badges ?? [];
        arr.push({ label: { id: '', en: '' }, url: '#', icon: 'check-circle', icon_bg: 'bg-goldy-soft', icon_color: '#064F3B' });
        $wire.set('content.{{$blockId }}.data.badges', arr).then(() => {
            activeTab = arr.length - 1;
            $dispatch('sync-active-tab-{{ strtolower($blockId) }}', activeTab);
        });
      "
      class="hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 py-3 text-xs font-bold tracking-widest text-gray-500 uppercase transition-colors outline-none"
    >
      <x-dynamic-component component="lucide-plus-square" class="h-4.5 w-4.5" />
      Tambah Lencana
    </button>
  </div>

  <!-- ========================================== -->
  <!-- AREA PREVIEW BAWAH                         -->
  <!-- ========================================== -->
  <x-slot:preview>
    <div
      class="flex flex-col"
      x-data="{ localAlign: $wire.content?.['{{ $blockId }}']?.data?.align ?? 'left' }"
    >
      <span
        class="mb-4 block w-full border-b border-gray-200 pb-2 text-center text-[10px] font-bold tracking-widest text-gray-400 uppercase"
        >Pratinjau Grup Lencana:</span
      >
      <div
        class="flex flex-wrap gap-3 transition-all duration-500 ease-out"
        :class="localAlign === 'center'
          ? 'justify-center'
          : localAlign === 'right'
            ? 'justify-end'
            : 'justify-start'"
      >
        @foreach ($badges as $index => $badge)
          <div
            class="border-foresty/15 text-foresty pointer-events-none inline-flex items-center gap-2.5 rounded-full border bg-white py-[9px] pr-[18px] pl-2.5 text-sm font-semibold shadow-[0_10px_20px_-10px_rgba(6,45,35,0.2)] transition-all duration-300"
          >
            <span
              class="flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded-full transition-colors duration-300"
              :class="($wire.content?.['{{ $blockId }}']?.data?.badges?.[{{ $index }}]?.icon_bg ?? 'bg-misty') === 'bg-mist' ? 'bg-[#E9F1EB]' : ($wire.content?.['{{ $blockId }}']?.data?.badges?.[{{ $index }}]?.icon_bg ?? 'bg-misty')"
            >
              <div
                :style="`color: ${$wire.content?.['{{ $blockId }}']?.data?.badges?.[{{ $index }}]?.icon_color ?? '#064F3B'};`"
                class="flex h-[15px] w-[15px] items-center justify-center transition-colors duration-300"
              >
                <!-- 🌟 PERBAIKAN: Gunakan sintaks standar PHP untuk rendering komponen Blade di sisi server -->
                <x-dynamic-component
                  :component="'lucide-' . ($badge['icon'] ?? 'check-circle')"
                  stroke-width="2.5"
                  class="h-full w-full"
                />
              </div>
            </span>
            <span
              x-text="$wire.content?.['{{ $blockId }}']?.data?.badges?.[{{ $index }}]?.label?.['{{ app()->getLocale() }}'] ?? 'Ketik lencana...'"
            ></span>
          </div>
        @endforeach
      </div>
    </div>
  </x-slot:preview>
</x-blocks.editor.wrapper>
