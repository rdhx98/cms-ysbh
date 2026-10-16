@props([
    'items' => [],
    'label' => null, // judul kartu; bawaan mengikuti bahasa halaman (id: Daftar Isi, selain itu: Contents)
    // Format array yang diharapkan: 
    // [['title' => 'Beban Kasus', 'anchor' => 'beban-kasus'], ['title' => 'Lima Komponen', 'anchor' => 'lima-komponen']]
])

@php
    $label ??= app()->getLocale() === 'id' ? 'Daftar Isi' : 'Contents';
@endphp

<nav 
    x-data="{
        activeAnchor: '',
        anchors: {{ json_encode(array_column($items, 'anchor')) }},
        
        init() {
            // Jalankan pengecekan saat pertama kali dimuat
            this.checkScroll();
        },

        checkScroll() {
            let current = '';
            const middleOfViewport = window.innerHeight / 2;

            // Cari anchor terakhir yang sudah melewati garis tengah layar
            for (let i = 0; i < this.anchors.length; i++) {
                const el = document.getElementById(this.anchors[i]);
                if (el) {
                    const rect = el.getBoundingClientRect();
                    
                    // Jika bagian atas elemen sudah menyentuh atau melewati tengah layar
                    if (rect.top <= middleOfViewport) {
                        current = this.anchors[i];
                    }
                }
            }

            // Fallback: Jika pengguna menggulir ke paling atas, aktifkan yang pertama (opsional)
            if (current === '' && this.anchors.length > 0 && window.scrollY < 100) {
                current = this.anchors[0];
            }

            this.activeAnchor = current;
        },

        scrollTo(id) {
            const el = document.getElementById(id);
            if (el) {
                // Gulir halus dengan penyesuaian jarak (offset) agar tidak tertutup header melayang
                const y = el.getBoundingClientRect().top + window.scrollY - 100;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
        }
    }"
    @scroll.window.passive="checkScroll"
    class="w-full"
>
    {{-- Header Daftar Isi --}}
    <h3 class="text-coral mb-4 text-xs font-extrabold tracking-widest uppercase">
        {{ $label }}
    </h3>

    {{-- Garis Pembatas Kiri Keseluruhan --}}
    <ul class="border-charcoal/10 flex flex-col border-l-2">
        @foreach($items as $item)
            <li>
                <button 
                    type="button" 
                    @click="scrollTo(@js($item['anchor']))"
                    class="-ml-[2px] block w-full border-l-2 py-2 pr-1 pl-3 text-left text-sm leading-snug break-words outline-none transition-all duration-200"
                    :class="activeAnchor === @js($item['anchor']) 
                        ? 'border-forest text-forest font-bold' 
                        : 'border-transparent text-ink-soft hover:text-forest hover:border-gray-300'"
                >
                    {{ $item['title'] }}
                </button>
            </li>
        @endforeach
    </ul>
</nav>