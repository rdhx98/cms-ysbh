{{-- Hapus @props(['height'...]) karena kita akan menangkap semua kelas secara dinamis --}}

<div
    x-data="{
        isAtTop: true,
        isAtBottom: false,
        checkScroll() {
            this.isAtTop = $refs.scrollArea.scrollTop <= 0;
            this.isAtBottom = Math.ceil($refs.scrollArea.scrollTop + $refs.scrollArea.clientHeight) >= $refs.scrollArea.scrollHeight;
        }
    }"
    x-init="
        $nextTick(() => checkScroll());
        window.addEventListener('resize', () => checkScroll());

        // Opsional: Cek juga setiap kali ada perubahan data dari Livewire
        Livewire.hook('morph.updated', () => {
            setTimeout(() => checkScroll(), 50);
        });
    "
    {{-- Atribut apa pun yang Anda ketik akan digabungkan (merge) ke sini --}}
    {{ $attributes->merge(['class' => 'relative w-full overflow-hidden']) }}
>
    {{-- Indikator Atas --}}
    <div
        x-show="!isAtTop"
        x-transition.opacity.duration.300ms
        class="pointer-events-none absolute top-0 left-0 right-0 z-10 h-6 bg-gradient-to-b from-gray-50 to-transparent"
    ></div>

    {{-- Area Scroll (Otomatis mengambil 100% tinggi dari pembungkus utamanya) --}}
    <div
        x-ref="scrollArea"
        @scroll.passive="checkScroll"
        class="scrollbar-none h-full w-full overflow-y-auto"
    >
        {{ $slot }}
    </div>

    {{-- Indikator Bawah --}}
    <div
        x-show="!isAtBottom"
        x-transition.opacity.duration.300ms
        class="pointer-events-none absolute bottom-0 left-0 right-0 z-10 h-6 bg-gradient-to-t from-gray-50 to-transparent"
    ></div>
</div>
