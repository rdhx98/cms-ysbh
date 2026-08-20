<?php

use Livewire\Component;
use App\Models\Page;
use Livewire\Attributes\Layout; // 1. Jangan lupa import ini


new 
#[Layout('layouts.landing.index')] 
class extends Component
{
    public ?Page $page = null;
    public string $lang = 'id'; // Default bahasa
    public array $activeLocales = []; // 🌟 Tambahkan properti ini
    public array $contentBlocks = [];

    public function mount($pageSlug = null)
    {
        // 1. Ambil bahasa yang sedang aktif di sistem
        $this->activeLocales = config('app.supported_locales', ['id', 'en']);
        $this->lang = app()->getLocale();

        // 2. Kueri Jaring Laba-laba (Anti-Gagal)
        if (!empty($pageSlug)) {
            $this->page = Page::where(function($query) use ($pageSlug) {
                // 1. Jika URL berupa ID angka
                if (is_numeric($pageSlug)) {
                    $query->where('id', $pageSlug);
                }
                
                // 2. Jika slug tersimpan sebagai teks biasa (string murni)
                $query->orWhere('slug', $pageSlug);
                
                // 3. 🌟 JURUS PAMUNGKAS (Mencakup SELURUH BAHASA di Spatie JSON)
                // Ini akan mendeteksi {"id":"...", "en":"...", "ar":"...", "zh":"..."}
                $query->orWhere('slug', 'LIKE', '%"'.$pageSlug.'"%');
                
            })->firstOrFail(); // Langsung 404 jika tidak ketemu
        } else {
            abort(404);
        }

        // 3. Ekstrak Data Konten dengan Aman
        $rawContent = $this->page->content ?? [];
        $rawContent = is_string($rawContent) ? json_decode($rawContent, true) ?? [] : (is_array($rawContent) ? $rawContent : []);
        
        // Penyelamat Data dari Seeder
        if (isset($rawContent['id']) && is_array($rawContent['id']) && isset($rawContent['id'][0]['type'])) {
            $rawContent = $rawContent['id'];
        } elseif (isset($rawContent['en']) && is_array($rawContent['en']) && isset($rawContent['en'][0]['type'])) {
            $rawContent = $rawContent['en'];
        }

        $this->contentBlocks = is_array($rawContent) ? $rawContent : [];
    }
};
?>

{{-- 
    Di sini Anda bisa mendefinisikan layout khusus frontend Anda.
    Misalnya: <x-layouts.app> atau semacamnya.
--}}
<div class="h-[calc(100vh-4rem)] flex flex-col overflow-x-hidden bg-gray-50 p-6 box-border">
    
    <!-- Header Halaman -->
    {{-- <div class="mb-10 pb-6 border-b border-gray-200">
        @php
            $titleData = is_string($page->title) ? json_decode($page->title, true) : $page->title;
            $pageTitle = $titleData[$lang] ?? $titleData['id'] ?? 'Tanpa Judul';
        @endphp
        <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">{{ $pageTitle }}</h1>
        <p class="text-sm text-gray-500 mt-2">Pratinjau Halaman - Mode Bahasa: <span class="font-bold uppercase">{{ $lang }}</span></p>
    </div> --}}
    <div class="mb-10 pb-6 border-b border-gray-200 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            @php
                // 🌟 GUNAKAN METODE BAWAAN SPATIE UNTUK MENGAMBIL ARRAY UTUH
                $titleData = $page->getTranslations('title');
                
                // Ambil bahasa yang diklik, atau fallback ke ID
                $pageTitle = $titleData[$lang] ?? $titleData['id'] ?? 'Tanpa Judul';
            @endphp
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">{{ $pageTitle }}</h1>
            <p class="text-sm text-gray-500 mt-2">Pratinjau Halaman</p>
        </div>

        <!-- 🌟 TOMBOL PENGALIH BAHASA (UI) -->
        <div class="shrink-0">
            <span class="block text-[10px] font-bold text-gray-400 uppercase mb-1 md:text-right">Lihat Sebagai:</span>
            <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg border border-gray-200 shadow-inner">
                @foreach($activeLocales as $code)
                    <button 
                        type="button" 
                        wire:click="$set('lang', '{{ $code }}')"
                        class="px-4 py-1.5 text-xs font-bold rounded-md transition-all duration-200 
                        {{ $lang === $code ? 'bg-white text-blue-600 shadow-sm border border-gray-200/50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-200/50' }}"
                    >
                        {{ strtoupper($code) }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- MESIN RENDER BLOK KONTEN -->
    <div class="space-y-6">
        @foreach($contentBlocks as $block)
            @php 
                $type = $block['type'] ?? '';
                $data = $block['data'] ?? [];
            @endphp

            @switch($type)
                
                @case('heading')
                    <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">
                        {!! strip_tags($data['text'][$lang] ?? '', ['span', 'strong', 'em', 'u', 's', 'a', 'br']) !!}
                    </h2>
                    {{-- <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">
                        {!! $data['text'][$lang] ?? '' !!}
                    </h2> --}}
                    {{-- Di file Blade Landing Page Anda --}}
                    {{-- <h2 class="font-bold text-gray-900">
                        @php
                            // Ambil data kotor dari database
                            $rawData = $block['data']['text'][$code];
                            
                            // Buang tag <p> atau <div>, TAPI pertahankan <span>, <strong>, <em>, <u>, <s>
                            $cleanHeading = strip_tags($rawData, ['span', 'strong', 'em', 'u', 's']);
                        @endphp

                        {!! $cleanHeading !!}
                    </h2> --}}
                    @break

                @case('paragraph')
                    <div class="prose prose-blue max-w-none text-gray-600">
                        {!! $data['text'][$lang] ?? '' !!}
                    </div>
                    @break

                @case('columns')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 my-6">
                        <div class="prose prose-blue max-w-none text-gray-600">
                            {!! $data['col_left'][$lang] ?? '' !!}
                        </div>
                        <div class="prose prose-blue max-w-none text-gray-600">
                            {!! $data['col_right'][$lang] ?? '' !!}
                        </div>
                    </div>
                    @break

                @case('stats_grid')
                    @php
                        // Ekstrak warna, gunakan default jika kosong
                        $colorTitle = $data['color_title'] ?? '#eab308';
                        $colorDesc = $data['color_desc'] ?? '#ffffff';
                        $colorBorder = $data['color_border'] ?? '#4b5563';
                        $cols = $data['columns'] ?? 4;
                        $items = $data['items'] ?? [];
                    @endphp
                    
                    <div class="grid gap-4 my-8" 
                         style="grid-template-columns: repeat({{ $cols }}, minmax(0, 1fr));">
                        
                        @foreach($items as $item)
                            <div class="p-6 rounded-xl border flex flex-col items-center justify-center text-center shadow-sm"
                                 style="border-color: {{ $colorBorder }}; background-color: #1f2937; /* Warna bg gelap sementara untuk kontras */">
                                
                                <span class="text-3xl font-extrabold block mb-2" style="color: {{ $colorTitle }};">
                                    {{ $item['title'][$lang] ?? '' }}
                                </span>
                                <span class="text-sm font-medium" style="color: {{ $colorDesc }};">
                                    {{ $item['description'][$lang] ?? '' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    @break

                @case('image')
                    @if(!empty($data['url']))
                        <div class="my-8">
                            <img src="{{ $data['url'] }}" alt="Image block" class="rounded-xl shadow-md w-full h-auto object-cover">
                        </div>
                    @endif
                    @break
                
                @case('hero_banner')
                    @php
                        $imagePos = $data['layout_image_position'] ?? 'right';
                        $btnOrder = $data['layout_button_order'] ?? 'bottom';
                        
                        $textColumnClass = $imagePos === 'left' ? 'lg:order-last' : 'lg:order-first';
                        $mediaColumnClass = $imagePos === 'left' ? 'lg:order-first' : 'lg:order-last';
                    @endphp

                    <section class="bg-paper p-12 w-full flex items-start justify-start bg-cover bg-center bg-no-repeat">
                        <div class="max-w-7xl mx-auto px-5 sm:px-8 grid grid-cols-1 lg:grid-cols-[1.05fr_0.95fr] gap-12 lg:gap-14 items-center">
                            
                            {{-- KOLOM TEKS --}}
                            <div class="{{ $textColumnClass }}">
                                
                                <!-- Tagline -->
                                <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
                                    @if(!empty($data['tagline_icon']))
                                        <x-dynamic-component :component="'lucide-' . $data['tagline_icon']" class="w-4 h-4 shrink-0" stroke-width="2.5" />
                                    @endif
                                    {{ $data['tagline_text'][$lang] ?? '' }}
                                </span>
                                
                                <!-- Title (Render HTML tag miring <em>) -->
                                <h1 class="font-display font-semibold text-[clamp(2.4rem,4.6vw,3.6rem)] leading-[1.08] text-foresty mt-4 mb-5">
                                    {!! $data['title'][$lang] ?? '' !!}
                                </h1>
                                
                                <!-- 🌟 TOMBOL & PILL (JIKA POSISI ATAS) -->
                                @if($btnOrder === 'top')
                                    <div class="flex gap-4 flex-wrap mb-6">
                                        @foreach($data['buttons'] ?? [] as $btn)
                                            @php
                                                $btnClass = ($btn['style'] ?? '') === 'outline_foresty' 
                                                    ? 'border-foresty bg-transparent text-foresty hover:bg-foresty hover:text-white focus-visible:outline-coral' 
                                                    : 'border-transparent bg-coral text-white hover:bg-coral-dark hover:shadow-[0_14px_28px_-14px_rgba(228,35,38,0.55)] focus-visible:outline-coral';
                                            @endphp
                                            <a href="{{ $btn['url'] ?? '#' }}" class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 transition-all duration-300 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[3px] {{ $btnClass }}">
                                                {{ $btn['text'][$lang] ?? '' }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <!-- Description -->
                                <p class="text-lg text-ink-soft max-w-[520px] mb-8">
                                    {{ $data['description'][$lang] ?? '' }}
                                </p>
                                
                                <!-- 🌟 TOMBOL & PILL (JIKA POSISI BAWAH - DEFAULT) -->
                                @if($btnOrder === 'bottom')
                                    <div class="flex gap-4 flex-wrap mb-11">
                                        @foreach($data['buttons'] ?? [] as $btn)
                                            @php
                                                $btnClass = ($btn['style'] ?? '') === 'outline_foresty' 
                                                    ? 'border-foresty bg-transparent text-foresty hover:bg-foresty hover:text-white focus-visible:outline-coral' 
                                                    : 'border-transparent bg-coral text-white hover:bg-coral-dark hover:shadow-[0_14px_28px_-14px_rgba(228,35,38,0.55)] focus-visible:outline-coral';
                                            @endphp
                                            <a href="{{ $btn['url'] ?? '#' }}" class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 transition-all duration-300 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[3px] {{ $btnClass }}">
                                                {{ $btn['text'][$lang] ?? '' }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- PILLS (Selalu di bawah tombol) -->
                                <div class="flex gap-3.5 flex-wrap">
                                    @foreach($data['pills'] ?? [] as $pill)
                                        <a href="{{ $pill['url'] ?? '#' }}" class="flex items-center gap-2.5 bg-white border border-foresty/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-foresty shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-0.5 transition-transform">
                                            <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0" style="background-color: {{ $pill['bg_color'] ?? '#fef3c7' }};">
                                                @if(!empty($pill['icon']))
                                                    <x-dynamic-component :component="'lucide-' . $pill['icon']" class="w-[15px] h-[15px] text-foresty" stroke-width="2" />
                                                @endif
                                            </span>
                                            {{ $pill['text'][$lang] ?? '' }}
                                        </a>
                                    @endforeach
                                </div>

                            </div>

                            {{-- KOLOM MEDIA --}}
                            <div class="flex items-center justify-center {{ $mediaColumnClass }}" aria-hidden="true">
                                @if(($data['media_type'] ?? 'svg') === 'svg')
                                    {!! $data['media_content'] ?? '' !!}
                                @else
                                    <img src="{{ $data['media_content'] ?? '' }}" alt="Banner Visual" class="w-[min(380px,80vw)] h-auto object-contain">
                                @endif
                            </div>

                        </div>
                    </section>
                @break

                @default
                    <!-- Blok tidak dikenali -->
            @endswitch
        @endforeach
    </div>

</div>