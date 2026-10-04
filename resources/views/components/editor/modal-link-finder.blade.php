<!-- 🌟 KOMPONEN MODAL PENCARIAN TAUTAN SUPER -->
<div
  class="fixed inset-0 z-99 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
  x-cloak
  x-data="{
    open: false,
    searchQuery: '',
    selectedText: '',
    targetWireModel: null, // Menyimpan alamat input Livewire jika dipanggil dari Blok/Tombol
    internalPages: [], // Menyimpan hasil pencarian database
    isSearching: false, // Indikator loading pencarian

    // 🌟 Panta ketikan untuk pencarian halaman internal
    init() {
      this.$watch('searchQuery', async (value) => {
        // Hanya mencari jika > 2 karakter dan bukan URL eksternal
        if (
          value.length > 2 &&
          !value.startsWith('http') &&
          !value.startsWith('mailto:') &&
          !value.startsWith('tel:')
        ) {
          this.isSearching = true;
          // Panggil fungsi Livewire di Controller
          this.internalPages = await $wire.searchInternalPages(value);
          this.isSearching = false;
        } else {
          this.internalPages = [];
        }
      });
    },

    // 🌟 Ekstrak semua Anchor yang ada di halaman ini secara real-time
    get inPageAnchors() {
      let anchors = [];
      let contentData = $wire.get('content') || {};
      for (let key in contentData) {
        if (contentData[key].anchor && contentData[key].anchor.trim() !== '') {
          anchors.push({
            id: contentData[key].anchor,
            type: contentData[key].type,
          });
        }
      }
      return anchors;
    },

    // 🌟 Fungsi pamungkas untuk mengirim URL ke pemanggilnya
    applyUrl(url) {
      if (this.targetWireModel) {
        // Jika dipanggil dari komponen Livewire (seperti Button/Card)
        $wire.set(this.targetWireModel, url);
      } else {
        // Jika dipanggil dari Tiptap (Rich Text Editor)
        window.dispatchEvent(
          new CustomEvent('insert-link-to-active-editor', {
            detail: { url: url, text: this.selectedText },
          }),
        );
      }
      // Tutup dan bersihkan modal
      this.open = false;
      this.searchQuery = '';
      this.targetWireModel = null;
      this.internalPages = [];
    },
  }"
  {{-- Listener global untuk menangkap perintah buka modal dari blok manapun --}}
  @buka-modal-link.window="
    open = true;
    selectedText = $event.detail.text || '';
    targetWireModel = $event.detail.target || null;
    searchQuery = '';
    internalPages = [];
  "
  x-show="open"
>
  <div
    x-on:click.outside="open = false"
    class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-xl border border-gray-200 bg-white p-0 shadow-xl"
  >
    {{-- Header & Input Pencarian --}}
    <div class="shrink-0 border-b border-gray-100 bg-gray-50/50 p-5">
      <h3 class="mb-3 text-lg font-bold text-gray-800">Sisipkan Tautan</h3>
      <div class="relative">
        <x-dynamic-component
          component="lucide-search"
          class="absolute top-2.5 left-3 h-4 w-4 text-gray-400"
        />
        <input
          type="text"
          x-model="searchQuery"
          placeholder="Cari halaman atau rekatkan URL eksternal (https://)..."
          class="focus:ring-foresty focus:border-foresty w-full rounded-lg border border-gray-300 py-2 pr-3 pl-9 text-sm shadow-sm"
        />
      </div>
    </div>

    {{-- Area Daftar (Bisa di-scroll) --}}
    <div class="flex-1 scrollbar-thin space-y-6 overflow-y-auto p-5">
      {{-- 🌟 SEGMEN 1: ANCHOR DI HALAMAN INI --}}
      <div x-show="inPageAnchors.length > 0 && searchQuery === ''">
        <span
          class="mb-2 block text-[10px] font-bold tracking-widest text-gray-400 uppercase"
          >Melompat ke Titik di Halaman Ini</span
        >
        <div class="grid grid-cols-2 gap-2">
          <template x-for="anchor in inPageAnchors" :key="anchor.id">
            <button
              type="button"
              x-on:click="applyUrl('#' + anchor.id)"
              class="hover:border-foresty hover:bg-sage-soft group flex items-center gap-2 rounded-lg border border-gray-200 p-2 text-left transition-colors"
            >
              <div
                class="group-hover:text-foresty rounded-md bg-gray-100 p-1.5 text-gray-500 transition-colors group-hover:bg-white"
              >
                <x-dynamic-component
                  component="lucide-hash"
                  class="h-3.5 w-3.5"
                />
              </div>
              <div class="min-w-0 flex-1">
                <p
                  class="truncate text-xs font-bold text-gray-700"
                  x-text="anchor.id"
                ></p>
                <p
                  class="truncate text-[10px] text-gray-400 capitalize"
                  x-text="'Blok: ' + anchor.type"
                ></p>
              </div>
            </button>
          </template>
        </div>
      </div>

      {{-- 🌟 SEGMEN 2: PENCARIAN HALAMAN INTERNAL CMS --}}
      <div
        x-show="
          !searchQuery.startsWith('http') &&
          !searchQuery.startsWith('mailto:') &&
          !searchQuery.startsWith('tel:')
        "
      >
        <span
          class="mb-2 block text-[10px] font-bold tracking-widest text-gray-400 uppercase"
          >Halaman Website</span
        >
        <div
          class="min-h-[100px] overflow-hidden rounded-lg border border-gray-100 bg-gray-50 text-sm text-gray-500"
        >
          <!-- Jika belum mengetik cukup panjang -->
          <p
            x-show="searchQuery.length <= 2"
            class="py-6 text-center text-xs text-gray-400"
          >Ketik minimal 3 huruf untuk mencari halaman...</p>

          <!-- Indikator Loading -->
          <p
            x-show="isSearching"
            class="text-foresty animate-pulse py-6 text-center text-xs"
          >Mencari halaman database...</p>

          <!-- Jika tidak ditemukan -->
          <p
            x-show="
              searchQuery.length > 2 &&
              !isSearching &&
              internalPages.length === 0
            "
            class="py-6 text-center text-xs text-red-400"
          >Halaman tidak ditemukan.</p>

          <!-- Hasil Pencarian -->
          <div
            x-show="internalPages.length > 0 && !isSearching"
            class="flex flex-col divide-y divide-gray-100"
          >
            <template x-for="page in internalPages" :key="page.url">
              <button
                type="button"
                x-on:click="applyUrl(page.url)"
                class="hover:text-foresty flex items-center justify-between p-3 text-left transition-colors hover:bg-white"
              >
                <div>
                  <p class="font-bold text-gray-700" x-text="page.title"></p>
                  <p class="text-[10px] text-gray-400" x-text="page.url"></p>
                </div>
                <x-dynamic-component
                  component="lucide-arrow-right"
                  class="h-4 w-4 text-gray-300"
                />
              </button>
            </template>
          </div>
        </div>
      </div>

      {{-- 🌟 SEGMEN 3: URL EKSTERNAL KUSTOM --}}
      <div
        x-show="
          searchQuery !== '' &&
          (searchQuery.startsWith('http') ||
            searchQuery.startsWith('mailto:') ||
            searchQuery.startsWith('tel:'))
        "
      >
        <button
          type="button"
          x-on:click="applyUrl(searchQuery)"
          class="border-foresty bg-sage-soft flex w-full items-center justify-between rounded-lg border p-3 text-left transition-colors hover:bg-[#c2ded3]"
        >
          <div class="min-w-0 pr-4">
            <p class="text-foresty text-xs font-bold">Gunakan Tautan Eksternal Ini</p>
            <p class="text-foresty truncate text-sm" x-text="searchQuery"></p>
          </div>
          <x-dynamic-component
            component="lucide-external-link"
            class="text-foresty h-4 w-4 shrink-0"
          />
        </button>
      </div>
    </div>

    {{-- Footer Tutup --}}
    <div
      class="flex shrink-0 justify-end border-t border-gray-100 bg-gray-50 p-4"
    >
      <button
        type="button"
        x-on:click="open = false"
        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-100"
      >
        Batal
      </button>
    </div>
  </div>
</div>
