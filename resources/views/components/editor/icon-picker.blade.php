@props(['disabled' => false])

<div x-data="{
        open: false,
        value: @entangle($attributes->wire('model')),
        search: '',
        // Daftar ikon Lucide yang sering dipakai Yayasan/Kesehatan (bisa Anda tambah)
        icons: ['activity', 'heart', 'heart-pulse', 'shield', 'shield-check', 'star', 'users', 'baby', 'syringe', 'pill', 'map-pin', 'globe', 'sun', 'check-circle', 'arrow-right'],
        get filteredIcons() {
            return this.search === '' ? this.icons : this.icons.filter(i => i.toLowerCase().includes(this.search.toLowerCase()));
        },
        selectIcon(icon) {
            this.value = icon;
            this.open = false;
            this.search = '';
        }
    }" 
    class="relative w-full"
    @click.outside="open = false"
>
    <!-- Tombol Pemilih (Menampilkan Ikon Aktif) -->
    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 py-1.5 bg-white border border-gray-300 rounded-md shadow-sm text-sm hover:bg-gray-50 focus:outline-none">
        <div class="flex items-center gap-2">
            <!-- Render ikon jika ada, jika tidak tampilkan kotak kosong -->
            <template x-if="value">
                <span class="text-foresty flex items-center justify-center w-5 h-5 bg-gray-100 rounded">
                    <!-- Memanfaatkan trik CSS/Alpine atau fallback ke teks -->
                    <span x-text="value" class="text-[10px] uppercase font-bold truncate max-w-[80px]"></span>
                </span>
            </template>
            <template x-if="!value">
                <span class="text-gray-400 italic text-xs">Pilih Ikon...</span>
            </template>
        </div>
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </button>

    <!-- Dropdown Modal Ikon -->
    <div x-show="open" 
         x-transition.opacity 
         style="display: none;" 
         class="absolute z-50 mt-1 w-[240px] bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden">
        
        <!-- Kolom Pencarian -->
        <div class="p-2 border-b border-gray-100 bg-gray-50">
            <input x-model="search" type="text" placeholder="Cari ikon..." class="w-full text-xs border-gray-300 rounded px-2 py-1.5 focus:border-foresty focus:ring-foresty">
        </div>
        
        <!-- Grid Daftar Ikon -->
        <div class="p-2 max-h-48 overflow-y-auto grid grid-cols-4 gap-1">
            <template x-for="icon in filteredIcons" :key="icon">
                <button @click="selectIcon(icon)" type="button" 
                        :class="{'bg-foresty/10 border-foresty text-foresty': value === icon, 'hover:bg-gray-100 border-transparent text-gray-600': value !== icon}"
                        class="p-2 border rounded flex flex-col items-center justify-center gap-1 transition-colors" :title="icon">
                    
                    <!-- Karena Blade tidak bisa merender dinamis dari JS, kita panggil ikon statis/teks sementara di editor -->
                    <span class="w-4 h-4 rounded bg-gray-200 block"></span> 
                    <span x-text="icon" class="text-[8px] truncate w-full text-center"></span>
                </button>
            </template>
            <template x-if="filteredIcons.length === 0">
                <div class="col-span-4 text-center text-xs text-gray-400 py-2">Tidak ditemukan</div>
            </template>
        </div>
    </div>
</div>