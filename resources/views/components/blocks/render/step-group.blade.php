@props(['block', 'data', 'lang', 'allContent'])

@php
    // 1. Konfigurasi Gaya dari Editor
    $orientation = $data['orientation'] ?? 'vertical'; // vertical, horizontal-top, horizontal-bottom
    $nodeColor = $data['node_color'] ?? 'bg-foresty text-white';
    $lineColor = $data['line_color'] ?? 'bg-foresty/30';
    $gapClass = $data['gap'] ?? 'gap-8';
    
    // Ambil ID anak-anak (kartu/konten di dalam grup langkah ini)
    $childIds = $data['children'] ?? [];
    $totalItems = count($childIds);
@endphp

<div id="{{ $block['anchor'] ?? '' }}" class="w-full my-8">

    {{-- ======================================================== --}}
    {{-- TATA LETAK VERTIKAL (Seperti pada gambar referensi) --}}
    {{-- ======================================================== --}}
    @if($orientation === 'vertical')
        <div class="flex flex-col">
            @foreach($childIds as $index => $childId)
                @if(isset($allContent[$childId]))
                    @php 
                        $childBlock = $allContent[$childId]; 
                        $component = 'blocks.render.' . str_replace('_', '-', $childBlock['type'] ?? 'unknown');
                        $delay = $index * 150; // Efek muncul satu per satu
                    @endphp

                    <div class="flex {{ $gapClass }} group reveal animate-scroll-reveal" style="transition-delay: {{ $delay }}ms;">
                        
                        <!-- Kolom Indikator (Angka & Garis) -->
                        <div class="flex flex-col items-center">
                            <!-- Lingkaran Angka -->
                            <div class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-bold shadow-sm transition-transform duration-300 group-hover:scale-110 {{ $nodeColor }}">
                                {{ $index + 1 }}
                            </div>
                            
                            <!-- Potongan Garis Vertikal (Tidak dirender di elemen terakhir) -->
                            @if(!$loop->last)
                                <div class="w-0.5 flex-1 my-2 rounded-full {{ $lineColor }}"></div>
                            @endif
                        </div>

                        <!-- Kolom Konten -->
                        <div class="flex-1 pb-10">
                            <x-dynamic-component :component="$component" :data="$childBlock['data'] ?? []" :lang="$lang" :all-content="$allContent" />
                        </div>
                        
                    </div>
                @endif
            @endforeach
        </div>

    {{-- ======================================================== --}}
    {{-- TATA LETAK HORIZONTAL (Angka di Atas atau di Bawah) --}}
    {{-- ======================================================== --}}
    @else
        @php
            // Hitung grid untuk desktop (misal: md:grid-cols-3)
            $gridCols = match($totalItems) {
                1 => 'md:grid-cols-1',
                2 => 'md:grid-cols-2',
                3 => 'md:grid-cols-3',
                4 => 'md:grid-cols-4',
                default => 'md:grid-cols-3 lg:grid-cols-4',
            };
        @endphp

        <div class="grid grid-cols-1 {{ $gridCols }} gap-6 md:gap-8">
            @foreach($childIds as $index => $childId)
                @if(isset($allContent[$childId]))
                    @php 
                        $childBlock = $allContent[$childId]; 
                        $component = 'blocks.render.' . str_replace('_', '-', $childBlock['type'] ?? 'unknown');
                        $delay = $index * 150;
                    @endphp

                    {{-- Ubah urutan flex tergantung posisi angka (Atas / Bawah) --}}
                    <div class="relative flex flex-col gap-4 reveal animate-scroll-reveal {{ $orientation === 'horizontal-bottom' ? 'justify-end' : '' }}" style="transition-delay: {{ $delay }}ms;">
                        
                        <!-- Baris Indikator (Angka & Garis) -->
                        <div class="relative flex items-center {{ $orientation === 'horizontal-bottom' ? 'order-last mt-4' : 'mb-2' }}">
                            
                            <!-- Lingkaran Angka -->
                            <div class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-bold shadow-sm transition-transform duration-300 hover:scale-110 {{ $nodeColor }}">
                                {{ $index + 1 }}
                            </div>

                            <!-- Potongan Garis Horizontal -->
                            <!-- Menggunakan trik absolute yang menembus celah (gap) grid desktop -->
                            @if(!$loop->last)
                                <!-- Garis ini hanya terlihat lurus ke samping di desktop, disembunyikan di HP -->
                                <div class="hidden md:block absolute top-1/2 left-12 h-0.5 -translate-y-1/2 rounded-full {{ $lineColor }}" 
                                     style="width: calc(100% + 2rem - 3rem);"> 
                                     {{-- Lebar: 100% kolom + ukuran gap - ukuran ikon awal --}}
                                </div>
                                
                                <!-- Garis Vertikal untuk tampilan HP (Fallback) -->
                                <div class="md:hidden absolute top-10 left-5 h-full w-0.5 -translate-x-1/2 rounded-full {{ $lineColor }}"></div>
                            @endif
                        </div>

                        <!-- Area Konten -->
                        <div class="flex-1">
                            <x-dynamic-component :component="$component" :data="$childBlock['data'] ?? []" :lang="$lang" :all-content="$allContent" />
                        </div>
                        
                    </div>
                @endif
            @endforeach
        </div>
    @endif

</div>