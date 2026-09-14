@props(['block', 'data', 'lang', 'allContent' => []])

@php
    $buttons = $data['buttons'] ?? [];

    $align = $data['align'] ?? 'left';
    $justifyClass = match($align) {
        'center' => 'justify-center',
        'right'  => 'justify-end',
        default  => 'justify-start'
    };
@endphp

@if(count($buttons) > 0)
<div id="{{ $block['anchor'] ?? '' }}" class="flex flex-wrap items-center gap-4 mt-8 mb-8 reveal animate-scroll-reveal {{ $justifyClass }}">

    @foreach($buttons as $btn)
        @php
            $label = $btn['label'][$lang] ?? '';
            $url = $btn['url'] ?? '#';
            $style = $btn['style'] ?? 'primary';

            // 🌟 DETEKSI ANCHOR (Apakah ini tautan internal?)
            $isAnchor = str_starts_with($url, '#') && strlen($url) > 1;

            $baseClass = "inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-full font-bold text-[15px] transition-all duration-300 group cursor-pointer";

            $styleClass = match($style) {
                'primary' => 'bg-foresty text-white hover:bg-[#043328] shadow-[0_8px_20px_-6px_rgba(6,79,59,0.5)] hover:shadow-[0_12px_25px_-6px_rgba(6,79,59,0.6)] hover:-translate-y-1',
                'secondary' => 'bg-goldy text-foresty hover:bg-[#F4D945] shadow-[0_8px_20px_-6px_rgba(235,204,38,0.5)] hover:shadow-[0_12px_25px_-6px_rgba(235,204,38,0.6)] hover:-translate-y-1',
                'outline' => 'bg-white border-2 border-foresty text-foresty hover:bg-sage-soft shadow-sm hover:shadow-md hover:-translate-y-1',
                'text' => 'bg-transparent text-foresty hover:text-goldy p-0 !px-0',
                default => 'bg-foresty text-white hover:bg-[#043328]'
            };
        @endphp

        @if(!empty($label))
            <a href="{{ $url }}"

                {{-- 🌟 SUNTIKAN ALPINE.JS HANYA JIKA INI TAUTAN ANCHOR --}}
                @if($isAnchor)
                    x-data
                    @click.prevent="document.querySelector('{{ $url }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                @endif

                class="{{ $baseClass }} {{ $styleClass }}">

                <span>{{ $label }}</span>

                @if($style === 'text' || $style === 'outline')
                    <x-dynamic-component component="lucide-arrow-right" class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" stroke-width="2.5" />
                @endif
            </a>
        @endif
    @endforeach

</div>
@endif
