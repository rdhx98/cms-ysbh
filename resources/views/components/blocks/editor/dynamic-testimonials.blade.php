{{-- File: resources/views/components/blocks/dynamic-testimonials.blade.php --}}
@props([
    'blockId',
    'code',
    'block',
    'allContent' => [] // Tambahkan fallback array kosong agar tidak error jika dipanggil di root
])


<div id="block-wrapper-{{ $blockId }}" class="space-y-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
    <div class="border-b border-gray-200 pb-2 mb-3">
        <span class="text-xs font-bold text-gray-500 uppercase">Pengaturan Widget Testimoni Dinamis</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Input untuk Judul Section (Multibahasa) -->
        <div class="md:col-span-2">
            <label class="block text-[10px] text-gray-400 font-semibold mb-1">JUDUL BAGIAN</label>
            <input type="text"
                   wire:model="content.{{ $blockId }}.data.title.{{ $code }}"
                   placeholder="Misal: Suara Mereka..."
                   class="w-full text-sm border-gray-300 rounded-md shadow-sm py-1.5">
        </div>

        <!-- Input untuk Batas Jumlah (Limit) -->
        <div>
            <label class="block text-[10px] text-gray-400 font-semibold mb-1">JUMLAH MAKSIMAL DITAMPILKAN</label>
            <input type="number"
                   wire:model="content.{{ $blockId }}.data.limit"
                   min="1" max="20"
                   class="w-full text-sm border-gray-300 rounded-md shadow-sm py-1.5">
        </div>

        <!-- Dropdown untuk Urutan -->
        <div>
            <label class="block text-[10px] text-gray-400 font-semibold mb-1">URUTAN PENAMPILAN</label>
            <select wire:model="content.{{ $blockId }}.data.order_by"
                    class="w-full text-sm border-gray-300 rounded-md shadow-sm py-1.5">
                <option value="latest">Terbaru Ditambahkan</option>
                <option value="random">Acak (Random)</option>
            </select>
        </div>
    </div>
    <p class="text-[10px] text-gray-500 italic mt-2">*Isi testimoni akan ditarik secara otomatis dari Menu Database Testimoni saat halaman diterbitkan.</p>
</div>
