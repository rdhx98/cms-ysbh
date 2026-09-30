@props (["blockId", "block", "code", "activeLocales" => []])

@php
  $buttons = $block["data"]["buttons"] ?? [];
  $stylesList = [
    [
      "value" => "primary",
      "label" => "Utama",
      "desc" => "Hijau Solid",
      "class" => "bg-foresty text-white",
    ],
    [
      "value" => "secondary",
      "label" => "Sekunder",
      "desc" => "Kuning Solid",
      "class" => "bg-goldy text-foresty",
    ],
    [
      "value" => "outline",
      "label" => "Garis Tepi",
      "desc" => "Transparan",
      "class" => "bg-white border-2 border-foresty text-foresty",
    ],
    [
      "value" => "text",
      "label" => "Teks Saja",
      "desc" => "Dengan Panah",
      "class" => "bg-gray-100 text-foresty",
    ],
  ];
@endphp

<x-blocks.editor.wrapper :block-id="$blockId" :block="$block">
  <x-slot:title>
    <div
      class="bg-sage-soft text-foresty flex h-5 w-5 items-center justify-center rounded-sm shadow-sm"
    >
      <x-dynamic-component
        component="lucide-mouse-pointer-click"
        class="h-3.5 w-3.5"
        stroke-width="2.5"
      />
    </div>
    Grup Tombol CTA
  </x-slot:title>

  <x-slot:snippet>
    <span
      class="block w-full text-right text-xs font-medium text-gray-400 sm:text-left"
    >
      <span class="font-bold text-gray-500">{{
        count(
          $buttons,
        )
      }}</span>
      Tombol
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
            @click="localAlign = '{{ $opt[0] }}'; $wire.set('content.{{ $blockId }}.data.align', '{{ $opt[0] }}')"
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
  <!-- TAB NAVIGASI & ISI TOMBOL                  -->
  <!-- ========================================== -->
  <div
    x-data="{ activeTab: 0 }"
    @sync-active-tab-{{ strtolower($blockId) }}.window="activeTab = $event.detail"
    class="flex flex-col"
  >
    @if (count($buttons) > 0)
      <div class="mb-4 flex flex-wrap gap-2 border-b border-gray-100 pb-3">
        @foreach ($buttons as $index => $btn)
          <button
            type="button"
            @click="activeTab = {{ $index }}; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', {{ $index }})"
            :class="activeTab === {{ $index }} ? 'bg-foresty text-white shadow-md border-transparent' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 hover:border-foresty/50'"
            class="flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-bold transition-all outline-none"
          >
            <span
              class="max-w-[100px] truncate"
              x-text="$wire.content?.['{{ $blockId }}']?.data?.buttons?.[{{ $index }}]?.label?.['{{ app()->getLocale() }}'] ?? 'Tombol {{ $index + 1 }}'"
            ></span>

            <div
              @click.stop="$wire.set('content.{{ $blockId }}.data.buttons', ($wire.content['{{ $blockId }}'].data.buttons).filter((_, i) => i !== {{ $index }})); activeTab = 0; $dispatch('sync-active-tab-{{ strtolower($blockId) }}', 0);"
              class="ml-1 rounded p-0.5 transition-colors hover:bg-red-500 hover:text-white"
              :class="activeTab === {{ $index }} ? 'text-white/60 hover:bg-white/20' : 'text-gray-400'"
            >
              <x-dynamic-component component="lucide-x" class="h-3 w-3" />
            </div>
          </button>
        @endforeach
      </div>

      <!-- KONTEN TAB -->
      <div class="min-h-[160px]">
        @foreach ($buttons as $index => $btn)
          <div
            x-show="activeTab === {{ $index }}"
            x-cloak
            wire:key="btn-tab-{{ $blockId }}-{{ $index }}"
            class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 shadow-inner"
          >
            <!-- Pengaturan Global (URL & Style) -->
            <div
              class="mb-6 grid grid-cols-1 gap-6 border-b border-gray-200 pb-6 md:grid-cols-2"
            >
              <div class="flex flex-col gap-1.5">
                <label class="text-foresty text-[10px] font-bold uppercase"
                  >Tautan (URL)</label
                >
                <div class="flex items-center gap-1">
                  <input
                    type="text"
                    wire:model.live="content.{{ $blockId }}.data.buttons.{{ $index }}.url"
                    placeholder="https://..."
                    class="focus:border-foresty focus:ring-foresty w-full rounded-md border-gray-200 bg-white py-1.5 text-xs shadow-sm transition-colors"
                  />
                  <button
                    type="button"
                    @click="$dispatch('buka-modal-link', { target: 'content.{{ $blockId }}.data.buttons.{{ $index }}.url' })"
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

              <div
                class="flex flex-col gap-1.5"
                x-data="{ localStyle: $wire.content?.['{{ $blockId }}']?.data?.buttons?.[{{ $index }}]?.style ?? 'primary' }"
              >
                <label class="text-foresty text-[10px] font-bold uppercase"
                  >Gaya Visual Tombol</label
                >
                <div class="grid grid-cols-2 gap-2">
                  @foreach ($stylesList as $style)
                    <button
                      type="button"
                      @click="localStyle = '{{ $style['value'] }}'; $wire.set('content.{{ $blockId }}.data.buttons.{{ $index }}.style', '{{ $style['value'] }}')"
                      class="flex flex-col items-start rounded-lg border bg-white p-2 text-left transition-all outline-none"
                      :class="localStyle === '{{ $style['value'] }}' ? 'border-transparent ring-2 ring-foresty ring-offset-1 shadow-sm scale-105' : 'border-gray-200 hover:border-foresty/50'"
                    >
                      <div class="mb-1 flex items-center gap-2">
                        <span
                          class="h-3 w-3 rounded-full {{ $style['class'] }} {{ $style['value'] === 'outline' ? 'border' : '' }}"
                        ></span>
                        <span
                          class="text-[11px] leading-none font-bold text-gray-700"
                          >{{
                            $style[
                              "label"
                            ]
                          }}</span
                        >
                      </div>
                      <span
                        class="pl-5 text-[9px] text-gray-400"
                        >{{ $style["desc"] }}</span
                      >
                    </button>
                  @endforeach
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
                  wire:key="btn-label-{{ $blockId }}-{{ $index }}-{{ $lang }}"
                  x-show="effectiveLayout === 'single' ? singleActiveLang === '{{ $lang }}' : splitLanguages.includes('{{ $lang }}')"
                  x-cloak
                  class="flex flex-col gap-1.5"
                >
                  <div class="flex items-center gap-2">
                    <span
                      class="bg-sage-soft text-foresty rounded px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase shadow-sm"
                      >{{ $lang }}</span
                    >
                    <span class="text-foresty text-[10px] font-bold uppercase"
                      >Teks Tombol</span
                    >
                  </div>
                  <input
                    type="text"
                    wire:model.live.debounce.300ms="content.{{ $blockId }}.data.buttons.{{ $index }}.label.{{ $lang }}"
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
          component="lucide-mouse-pointer-click"
          class="mb-2 h-8 w-8 text-gray-300"
        />
        <p class="text-xs font-bold text-gray-400 uppercase">Belum ada tombol</p>
      </div>
    @endif

    <button
      type="button"
      @click="
        let arr = $wire.content?.['{{ $blockId }}']?.data?.buttons ?? [];
        arr.push({ label: { id: '', en: '' }, url: '#', style: 'primary' });
        $wire.set('content.{{ $blockId }}.data.buttons', arr).then(() => {
            activeTab = arr.length - 1;
            $dispatch('sync-active-tab-{{ strtolower($blockId) }}', activeTab);
        });
      "
      class="hover:border-foresty hover:bg-sage-soft hover:text-foresty flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 py-3 text-xs font-bold tracking-widest text-gray-500 uppercase transition-colors outline-none"
    >
      <x-dynamic-component component="lucide-plus-square" class="h-4.5 w-4.5" />
      Tambah Tombol
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
        class="mb-4 block border-b border-gray-200 pb-2 text-[10px] font-bold tracking-widest text-gray-400 uppercase"
        >Pratinjau Grup Tombol:</span
      >
      <div
        class="flex flex-wrap gap-4 transition-all duration-300"
        :class="localAlign === 'center'
          ? 'justify-center'
          : localAlign === 'right'
            ? 'justify-end'
            : 'justify-start'"
      >
        @foreach ($buttons as $index => $btn)
          <div
            class="pointer-events-none inline-flex items-center justify-center gap-2 rounded-full px-7 py-3.5 text-[15px] font-bold transition-all duration-300"
            :class="{
                   'bg-foresty text-white shadow-[0_8px_20px_-6px_rgba(6,79,59,0.5)]': ($wire.content?.['{{ $blockId }}']?.data?.buttons?.[{{ $index }}]?.style ?? 'primary') === 'primary',
                   'bg-goldy text-foresty shadow-[0_8px_20px_-6px_rgba(235,204,38,0.5)]': ($wire.content?.['{{ $blockId }}']?.data?.buttons?.[{{ $index }}]?.style ?? 'primary') === 'secondary',
                   'bg-white border-2 border-foresty text-foresty shadow-sm': ($wire.content?.['{{ $blockId }}']?.data?.buttons?.[{{ $index }}]?.style ?? 'primary') === 'outline',
                   'bg-transparent text-foresty p-0 !px-0': ($wire.content?.['{{ $blockId }}']?.data?.buttons?.[{{ $index }}]?.style ?? 'primary') === 'text'
               }"
          >
            <span
              x-text="$wire.content?.['{{ $blockId }}']?.data?.buttons?.[{{ $index }}]?.label?.['{{ app()->getLocale() }}'] ?? 'Ketik label...'"
            ></span>
            <span
              x-show="['text', 'outline'].includes($wire.content?.['{{ $blockId }}']?.data?.buttons?.[{{ $index }}]?.style ?? 'primary')"
              x-cloak
            >
              <x-dynamic-component
                component="lucide-arrow-right"
                class="h-4 w-4"
                stroke-width="2.5"
              />
            </span>
          </div>
        @endforeach
      </div>
    </div>
  </x-slot:preview>
</x-blocks.editor.wrapper>
