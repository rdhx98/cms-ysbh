@props(['blockId', 'code', 'title' => 'Block Editor', 'icon' => 'lucide-box'])

<div
  x-data="{
    isPinned: false,
    isRowPinned: false,
    isCollapsed: false,
    pinStyle: '',

    init() {
        const reportPinStatus = () => {
            this.$dispatch('global-pin-update', {
                id: '{{ $blockId }}',
                active: this.isPinned || this.isRowPinned
            });
        };
        this.$watch('isPinned', reportPinStatus);
        this.$watch('isRowPinned', reportPinStatus);
    },

    togglePin() {
        if (this.isCollapsed) {
            this.isCollapsed = false;
            this.$dispatch('sync-collapse-{{ strtolower($blockId) }}', false);
        }
        if (!this.isPinned && this.isRowPinned) {
            this.$dispatch('force-close-row-pin-{{ strtolower($blockId) }}');
        }

        this.isPinned = !this.isPinned;
        if (this.isPinned) {
            let area = document.getElementById('main-editor-scroll-area');
            if (area) {
                let rect = area.getBoundingClientRect();
                this.pinStyle = `position: fixed !important; top: ${rect.top + 5 }px !important; left: ${rect.left + 16}px !important; width: ${rect.width - 32}px !important; height: ${rect.height - 10}px !important; z-index: 60 !important; margin: 0 !important;`;
                this.$refs.placeholder.style.height = this.$refs.editor.offsetHeight + 'px';
            }
        } else {
            this.pinStyle = '';
        }
    },

    toggleCollapse() {
        this.isCollapsed = !this.isCollapsed;
        if (this.isCollapsed) {
            if (this.isPinned) {
                this.isPinned = false;
                this.pinStyle = '';
            }
            if (this.isRowPinned) {
                this.$dispatch('force-close-row-pin-{{ strtolower($blockId) }}');
            }
        }
        this.$dispatch('sync-collapse-{{ strtolower($blockId) }}', this.isCollapsed);
    }
  }"
  @toggle-row-pin-{{ strtolower($blockId) }}.window="
    isRowPinned = !isRowPinned;
    if (isRowPinned) {
        if (isPinned) { isPinned = false; pinStyle = ''; }
        if (isCollapsed) { isCollapsed = false; $dispatch('sync-collapse-{{ strtolower($blockId) }}', false); }
    }
  "
  @force-close-row-pin-{{ strtolower($blockId) }}.window="isRowPinned = false"
  @sync-collapse-{{ strtolower($blockId) }}.window="isCollapsed = $event.detail"
  @toggle-collapse-all.window="
    isCollapsed = $event.detail;
    if (isCollapsed && isPinned) { isPinned = false; pinStyle = ''; }
  "
  class="flex w-full flex-col"
  x-bind:class="isPinned || isRowPinned ? 'h-full flex-1 min-h-0' : ''"
>
  {{-- PLACEHOLDER SAAT PINNED --}}
  <div
    x-ref="placeholder"
    x-show="isPinned"
    x-cloak
    class="flex w-full items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50"
  >
    <div class="text-center">
      <x-dynamic-component component="lucide-maximize" class="mx-auto mb-2 h-6 w-6 text-gray-400" />
      <span class="text-xs font-bold tracking-widest text-gray-400 uppercase">Mode Fokus Sedang Aktif</span>
    </div>
  </div>

  {{-- EDITOR UTAMA --}}
  <div
    x-ref="editor"
    :style="isPinned ? pinStyle : ''"
    class="flex flex-col bg-white transition-all duration-200"
    x-bind:class="
      isPinned ? 'border-foresty ring-4 ring-foresty/20 shadow-2xl overflow-hidden flex-1 min-h-0 h-full'
      : isRowPinned ? 'border-foresty/50 rounded-xl ring-2 ring-foresty/20 overflow-hidden flex-1 min-h-0 h-full max-h-[calc(100vh-120px)]'
      : 'rounded-xl border border-gray-200 shadow-md relative overflow-hidden flex-1 min-h-0 max-h-[calc(100vh-160px)]'
    "
  >
    <!-- HEADER BLOK -->
    <div class="flex shrink-0 items-center justify-between rounded-t-xl border-b border-gray-200 bg-gray-100 p-2">
      <div class="flex items-center gap-2">
        <div class="bg-sage-soft rounded-md p-1">
          <x-dynamic-component :component="$icon" class="text-foresty h-4 w-4" />
        </div>
        <span class="text-xs font-extrabold tracking-widest text-gray-500 uppercase">{{ $title }}</span>
      </div>

      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2" x-show="!isCollapsed">
          {{-- SLOT PENGATURAN HEADER CUSTOM --}}
          @if (isset($headerSettings))
            {{ $headerSettings }}
          @endif
          <span class="text-foresty bg-sage-soft shrink-0 rounded px-1.5 py-0.5 text-xs font-bold uppercase shadow-sm">{{ $code }}</span>
        </div>

        <div class="flex items-center gap-1 border-l border-gray-300 pl-4">
          <button type="button" :disabled="isPinned || isCollapsed" x-on:click="$dispatch('toggle-row-pin-{{ strtolower($blockId) }}')" x-bind:class="isPinned || isCollapsed ? 'bg-gray-100 text-gray-300 cursor-not-allowed opacity-50' : isRowPinned ? 'bg-foresty/10 text-foresty shadow-inner' : 'bg-gray-200 text-gray-500 hover:text-foresty hover:bg-gray-300'" class="flex items-center justify-center rounded-md p-1.5 transition-colors outline-none" title="Pin Baris (Split View)">
            <x-dynamic-component component="lucide-columns" class="h-3.5 w-3.5" />
          </button>
          <button type="button" :disabled="isRowPinned || isCollapsed" x-on:click="togglePin()" x-bind:class="isRowPinned || isCollapsed ? 'bg-gray-100 text-gray-300 cursor-not-allowed opacity-50' : isPinned ? 'bg-foresty text-white shadow-inner' : 'bg-gray-200 text-gray-500 hover:text-foresty hover:bg-gray-300'" class="flex items-center justify-center rounded-md p-1.5 shadow-sm transition-colors outline-none" title="Fokus Layar Penuh">
            <x-dynamic-component component="lucide-maximize" class="h-3.5 w-3.5" x-bind:class="isPinned ? 'scale-90' : ''" />
          </button>
          <button type="button" :disabled="isPinned || isRowPinned" x-on:click="toggleCollapse()" x-bind:class="isPinned || isRowPinned ? 'text-gray-300 cursor-not-allowed opacity-50' : 'hover:text-foresty text-gray-400 hover:bg-gray-200'" class="rounded-md p-1.5 transition-colors outline-none" title="Lipat Blok">
            <x-dynamic-component component="lucide-chevron-down" class="h-4 w-4 transition-transform duration-300" x-bind:class="isCollapsed ? 'rotate-180' : ''" />
          </button>
        </div>
      </div>
    </div>

    <!-- BUNGKUSAN LIPATAN (MAIN SLOT) -->
    <div x-show="!isCollapsed" x-collapse x-cloak class="flex flex-col flex-1 min-h-0" x-bind:class="isPinned || isRowPinned ? 'h-full' : ''">
      {{ $slot }}
    </div>
  </div>
</div>