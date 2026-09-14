@props(['block', 'data', 'lang', 'allContent' => []])

@php
    $badges = $data['badges'] ?? [];
@endphp

@if(count($badges) > 0)
<div id="{{ $block['anchor'] ?? '' }}" class="flex flex-wrap items-center gap-3 mt-4 mb-6 reveal animate-scroll-reveal">

    @foreach($badges as $badge)
        @php
            $label = $badge['label'][$lang] ?? '';
            $url = $badge['url'] ?? '#';
            $icon = $badge['icon'] ?? 'check-circle';
            $iconBg = $badge['icon_bg'] ?? 'bg-misty';
            $iconColor = $badge['icon_color'] ?? '#064F3B';

            // Normalisasi warna misty jika ada perbedaan penamaan
            if ($iconBg === 'bg-mist') $iconBg = 'bg-[#E9F1EB]';

            // 🌟 DETEKSI ANCHOR (Apakah ini tautan internal?)
            $isAnchor = str_starts_with($url, '#') && strlen($url) > 1;
        @endphp

        @if(!empty($label))
            {{-- Jika URL kosong atau hanya '#', gunakan <div>. Jika ada URL, gunakan <a> --}}
            <{{ $url !== '#' && !empty($url) ? 'a href='.$url : 'div' }}

                {{-- 🌟 SUNTIKAN ALPINE.JS HANYA JIKA INI TAUTAN ANCHOR --}}
                @if($isAnchor)
                    x-data
                    @click.prevent="document.querySelector('{{ $url }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                @endif

                class="inline-flex items-center gap-2.5 bg-white border border-foresty/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-foresty shadow-[0_10px_20px_-10px_rgba(6,45,35,0.2)] transition-all duration-300 {{ $url !== '#' && !empty($url) ? 'hover:shadow-[0_15px_25px_-10px_rgba(6,45,35,0.3)] hover:-translate-y-0.5 cursor-pointer' : '' }}">

                {{-- Lingkaran Ikon --}}
                <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 {{ $iconBg }}">
                    <x-dynamic-component :component="'lucide-' . $icon" class="w-[15px] h-[15px]" style="color: {{ $iconColor }};" stroke-width="2.5" />
                </span>

                {{-- Teks Lencana --}}
                <span>{{ $label }}</span>

            </{{ $url !== '#' && !empty($url) ? 'a' : 'div' }}>
        @endif
    @endforeach

</div>
@endif
