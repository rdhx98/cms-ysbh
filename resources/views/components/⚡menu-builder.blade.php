<?php

use Livewire\Component;
use App\Models\Navigation;

new 
class extends Component
{
    public $menus = [];
    
    // Properti Formulir
    public $editId = null;
    public $label_id = '';
    public $label_en = '';
    public $route_name = '';
    public $url = '';
    public $is_active = true;

    public function mount()
    {
        $this->loadMenus();
    }

    public function loadMenus()
    {
        // Ambil semua menu, urutkan berdasarkan kolom 'order'
        $this->menus = Navigation::orderBy('order', 'asc')->get();
    }

    public function save()
    {
        // Validasi Sederhana
        $this->validate([
            'label_id' => 'required|string',
        ]);

        $data = [
            'label' => [
                'id' => $this->label_id, 
                'en' => $this->label_en ?: $this->label_id // Fallback jika EN kosong
            ],
            'route_name' => $this->route_name,
            'url' => $this->url,
            'is_active' => $this->is_active,
        ];

        if ($this->editId) {
            // Proses Update
            Navigation::find($this->editId)->update($data);
        } else {
            // Proses Tambah Baru (Letakkan di urutan paling bawah)
            $data['order'] = Navigation::max('order') + 1;
            Navigation::create($data);
        }

        $this->resetForm();
        $this->loadMenus();
    }

    public function edit($id)
    {
        $menu = Navigation::find($id);
        $this->editId = $menu->id;
        $this->label_id = $menu->getTranslation('label', 'id');
        $this->label_en = $menu->getTranslation('label', 'en', false) ?? '';
        $this->route_name = $menu->route_name;
        $this->url = $menu->url;
        $this->is_active = $menu->is_active;
    }

    public function delete($id)
    {
        Navigation::find($id)->delete();
        $this->loadMenus();
    }

    public function resetForm()
    {
        $this->reset(['editId', 'label_id', 'label_en', 'route_name', 'url']);
        $this->is_active = true;
    }

    // 🌟 FUNGSI AJAIB UNTUK ALPINE SORT
    public function updateOrder($itemId, $newPosition)
    {
        // 1. Ambil susunan ID saat ini yang berurutan
        $currentOrders = Navigation::orderBy('order')->pluck('id')->toArray();
        
        // 2. Buang ID item yang digeser dari susunan lama
        $currentOrders = array_diff($currentOrders, [$itemId]);
        
        // 3. Sisipkan kembali ID tersebut ke indeks/posisi yang baru
        array_splice($currentOrders, $newPosition, 0, $itemId);
        
        // 4. Perbarui seluruh urutan di Database berdasarkan susunan array yang baru
        foreach ($currentOrders as $index => $id) {
            Navigation::where('id', $id)->update(['order' => $index]);
        }

        // 5. Muat ulang daftar menu
        $this->loadMenus();
    }
};
?>

<x-slot:title>{{ __('Menu Builder') }}</x-slot:title>

<x-main-wrapper>
    <!--HEADER CONTAINER -->
    <div class="mb-4  flex justify-between w-full items-center">
        <div class="font-bold text-2xl"> {{ __('Navigasi') }} </div>
        <div class="">
            
        </div>
    </div>
    
    {{-- <div class="grid grid-cols-1 lg:grid-cols-3 gap-8"> --}}
    <div class="flex md:flex-row flex-col gap-8 overflow-x-auto overflow-y-auto rounded-xl max-h-full b w-full max-w-screen">
        
        {{-- KOLOM KIRI: FORMULIR --}}
        <div class="flex flex-col gap-4 w-full ">
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <h2 class="text-lg font-bold text-foresty mb-4">
                    {{ $editId ? 'Edit Menu' : 'Tambah Menu Baru' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Label (ID) *</label>
                        <input type="text" wire:model="label_id" placeholder="Misal: Beranda" class="w-full text-sm border-gray-300 rounded-md focus:ring-foresty" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Label (EN)</label>
                        <input type="text" wire:model="label_en" placeholder="Misal: Home" class="w-full text-sm border-gray-300 rounded-md focus:ring-foresty">
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Tujuan Tautan</label>
                        <p class="text-[10px] text-gray-400 mb-2">Isi salah satu: Route Laravel atau URL Eksternal.</p>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[10px] text-gray-400">Route Name</label>
                                <input type="text" wire:model="route_name" placeholder="Misal: home atau programs" class="w-full text-sm border-gray-300 rounded-md bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-[10px] text-gray-400">Atau URL Khusus</label>
                                <input type="text" wire:model="url" placeholder="https://..." class="w-full text-sm border-gray-300 rounded-md bg-gray-50">
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 flex items-center gap-2">
                        <input type="checkbox" wire:model="is_active" id="isActive" class="rounded text-foresty focus:ring-foresty">
                        <label for="isActive" class="text-sm text-gray-600 font-medium">Tampilkan di Publik</label>
                    </div>

                    <div class="pt-4 flex gap-2">
                        <button type="submit" class="flex-1 bg-foresty text-white py-2 rounded-md font-bold text-sm hover:bg-[#043b2c] transition-colors">
                            {{ $editId ? 'Simpan Perubahan' : 'Tambahkan' }}
                        </button>
                        
                        @if($editId)
                            <button type="button" wire:click="resetForm" class="px-4 bg-gray-100 text-gray-600 py-2 rounded-md font-bold text-sm hover:bg-gray-200">
                                Batal
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- KOLOM KANAN: DAFTAR MENU (DRAG & DROP) --}}
        <div class="flex flex-col w-full max-h-full min-h-full">
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500 uppercase">Susunan Menu Saat Ini</span>
                    <span class="text-[10px] text-gray-400">Geser ikon titik-titik untuk mengubah urutan</span>
                </div>

                {{-- WADAH ALPINE SORT --}}
                <ul x-data 
                    x-sort="$wire.updateOrder($item, $position)" 
                    class="divide-y divide-gray-100 overflow-auto max-h-full  pb-12">
                    @for($i = 0; $i < 1; $i++)
                        @forelse($menus as $menu)
                            
                            {{-- DEKLARASI ITEM SORT --}}
                            <li x-sort:item="{{ $menu->id }}" wire:key="menu-{{ $menu->id }}" class="flex items-center justify-between p-4 bg-white hover:bg-gray-50 transition-colors group">
                                
                                <div class="flex items-center gap-4">
                                    <!-- Handle / Pegangan Drag -->
                                    <button type="button" x-sort:handle class="cursor-grab active:cursor-grabbing text-gray-400 hover:text-foresty touch-none">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                                    </button>

                                    <div>
                                        <h4 class="font-bold text-sm text-gray-800 {{ !$menu->is_active ? 'line-through opacity-50' : '' }}">
                                            {{ $menu->getTranslation('label', 'id') }}
                                            <span class="text-xs font-normal text-gray-400 ml-1">/ {{ $menu->getTranslation('label', 'en', false) ?? 'N/A' }}</span>
                                        </h4>
                                        <p class="text-[10px] text-gray-400 font-mono mt-0.5">
                                            {{ $menu->route_name ? 'Route: ' . $menu->route_name : 'URL: ' . $menu->url }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="edit({{ $menu->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    <button wire:click="delete({{ $menu->id }})" wire:confirm="Yakin ingin menghapus menu ini?" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>

                            </li>
                        @empty
                            <li class="p-8 text-center text-gray-400 text-sm">Belum ada menu navigasi yang ditambahkan.</li>
                        @endforelse
                    @endfor
                </ul>
            </div>
        </div>

    </div>
</x-main-wrapper>