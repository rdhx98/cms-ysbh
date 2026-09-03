<?php

use Livewire\Component;
use App\Models\Page;
use Livewire\Attributes\Layout; // 1. Jangan lupa import ini


new
#[Layout('layouts.landing.dynamic-preview')]
class extends Component
{
    public ?Page $page = null;
    public string $lang; // Default bahasa
    public array $activeLocales = []; // 🌟 Tambahkan properti ini
    // public array $contentBlocks = [];
    public array $allContent = [];
    public array $rootOrder = [];

    public string $viewMode = 'full';

    public function mount($pageSlug = null)
    {
        // 🌟 2. Tangkap parameter '?mode=' dari URL (default: 'full')
        $this->viewMode = request()->query('mode', 'full');

        // 1. Ambil bahasa yang sedang aktif di sistem
        $this->activeLocales = config('app.supported_locales', ['id', 'en']);
        // $this->lang = app()->getLocale();
        $this->lang = request()->query('lang', app()->getLocale());

        // 2. Kueri Jaring Laba-laba (Anti-Gagal)
        if (!empty($pageSlug)) {
            $this->page = Page::where(function($query) use ($pageSlug) {
                if (is_numeric($pageSlug)) {
                    $query->where('id', $pageSlug);
                }
                $query->orWhere('slug', $pageSlug);
                $query->orWhere('slug', 'LIKE', '%"'.$pageSlug.'"%');
            })->firstOrFail();
        } else {
            abort(404);
        }

        // 3. 🌟 EKSTRAKSI DATA ANTI-GAGAL (BYPASS ELOQUENT CASTING)
        $modelData = $this->page->toArray();
        $rawContent = $modelData['content'] ?? [];

        // Atasi kemungkinan "Double-Encoded JSON"
        if (is_string($rawContent)) {
            $decoded = json_decode($rawContent, true) ?? [];
            // Jika setelah di-decode masih berupa string, decode sekali lagi
            $rawContent = is_string($decoded) ? (json_decode($decoded, true) ?? []) : $decoded;
        }

        // Karena tidak butuh kompatibilitas mundur, kita langsung ambil dari kunci 'blocks' dan 'order'
        $this->allContent = $rawContent['blocks'] ?? [];
        $this->rootOrder = $rawContent['order'] ?? [];

        // 🌟 OPSI DEBUG: Buka komentar di bawah ini jika halaman MASIH kosong
        // untuk melihat isi data yang sebenarnya terdeteksi oleh sistem
        // dd('All Content:', $this->allContent, 'Root Order:', $this->rootOrder);
    }

    #[On('change-preview-lang')] // Gunakan attribute listener Livewire v3
    public function updatePreviewLanguage($newLang)
    {
        // Pastikan bahasa yang diminta valid
        if (in_array($newLang, $this->activeLocales)) {
            $this->lang = $newLang;
        }
    }
};
?>

<div 
  class="h-full flex flex-col overflow-x-hidden  box-border " 
  @message.window="if ($event.data && $event.data.type === 'change-lang') $wire.set('lang', $event.data.lang)"
  x-data="{ previewLang: @entangle('lang').live }"> 

    @if($viewMode === 'full')
        <template x-teleport="#editor-toolbar-portal">
            <!-- Header Halaman -->
            {{-- 🌟 2. Hapus x-data dari div ini --}}
            <div class="border-gray-200 flex flex-row justify-between gap-6 w-full">
                
                <div class="flex items-center justify-center gap-2">
                    @php
                        $titleData = $page->getTranslations('title');
                        $pageTitle = $titleData[app()->getLocale()] ?? $titleData['id'] ?? 'Tanpa Judul';
                    @endphp
                    <span class="block text-[10px] font-bold text-gray-400 uppercase md:text-right">Pratinjau Halaman</span>
                    <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">{{ $pageTitle }}</h1>
                </div>

                <!-- TOMBOL PENGALIH BAHASA -->
                <div class="flex items-center gap-6 shrink-0">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase md:text-right">Lihat Sebagai:</span>
                    <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg border border-gray-200 shadow-inner">
                        @foreach($activeLocales as $code)
                            <button
                                type="button"
                                {{-- 🌟 3. Gunakan previewLang --}}
                                @click="previewLang = '{{ $code }}'"
                                class="px-4 py-1 text-xs font-bold rounded-md transition-all duration-200 select-none cursor-pointer"
                                :class="previewLang === '{{ $code }}' 
                                    ? 'bg-white text-forest border border-gray-200/50 shadow-sm' 
                                    : 'text-gray-500 border border-transparent hover:text-gray-800 hover:bg-gray-200/50'"
                            >
                                {{ strtoupper($code) }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </template>
    @endif


    <!-- MESIN RENDER BLOK KONTEN DINAMIS -->
    <div class="w-full bg-paper">
        @php
            $groupedSections = [];
            
            // 🌟 Memori Bawaan: Diubah ke bg-paper
            $currentSection = [
                'bgClass'   => 'bg-paper', 
                'textClass' => 'text-gray-900',
                'padding'   => 'py-16 sm:py-24',
                'blocks'    => []
            ];

            // PENGELOMPOKAN
            foreach($rootOrder as $blockId) {
                if(!isset($allContent[$blockId])) continue;
                $block = $allContent[$blockId];

                if ($block['type'] === 'section_divider') {
                    if(count($currentSection['blocks']) > 0) {
                        $groupedSections[] = $currentSection;
                    }
                    
                    // 🌟 Buka seksi baru (Jika tidak ada warna yang dipilih, gunakan bg-paper)
                    $currentSection = [
                        'bgClass'   => $block['data']['background'] ?? 'bg-paper', 
                        'textClass' => $block['data']['text_color'] ?? 'text-gray-900',
                        'padding'   => $block['data']['padding'] ?? 'py-16 sm:py-24',
                        'blocks'    => []
                    ];
                    continue; 
                }

                $currentSection['blocks'][] = $blockId;
            }

            if(count($currentSection['blocks']) > 0) {
                $groupedSections[] = $currentSection;
            }
        @endphp

        {{-- EKSEKUSI RENDER HTML --}}
        @foreach($groupedSections as $section)
            <section class="w-full relative {{ $section['bgClass'] }} {{ $section['textClass'] }} {{ $section['padding'] }}">
                <div class="max-w-7xl mx-auto px-5 sm:px-8">
                    
                    {{-- Di sini kita hanya melakukan perulangan biasa --}}
                    @foreach($section['blocks'] as $blockId)
                        @php
                            $block = $allContent[$blockId];
                            $componentName = 'blocks.render.' . str_replace('_', '-', $block['type']);
                        @endphp
                        
                        <x-dynamic-component
                            :component="$componentName"
                            :data="$block['data']"
                            :lang="$lang"
                            :all-content="$allContent"
                        />
                    @endforeach

                </div>
            </section>
        @endforeach
    </div>
    <!-- OLD MESIN RENDER BLOK KONTEN DINAMIS -->
    {{-- <div class="w-full bg-paper rounded-lg">
        <!-- Looping HANYA blok level terluar (Root) dari $rootOrder -->
        @foreach($rootOrder as $blockId)
            @if(isset($allContent[$blockId]))
                @php
                    $block = $allContent[$blockId];
                    $componentName = 'blocks.render.' . str_replace('_', '-', $block['type']);

                    // 🌟 1. DAFTARKAN BLOK MAKRO (Blok yang mengatur pembungkusnya sendiri)
                    $macroBlocks = ['columns', 'hero_banner', 'stats_grid', 'dynamic_testimonials'];

                    // Cek apakah blok saat ini adalah blok makro
                    $isMacro = in_array($block['type'], $macroBlocks);
                @endphp

                @if($isMacro)
                    <!-- 🌟 2A. JIKA MAKRO: Render langsung tanpa dibungkus apa pun -->
                    <x-dynamic-component
                        :component="$componentName"
                        :data="$block['data']"
                        :lang="$lang"
                        :all-content="$allContent"
                    />
                @else
                    <!-- 🌟 2B. JIKA MIKRO: Bungkus otomatis dengan Container Standar -->
                    <section class="w-full py-4">
                        <div class="max-w-7xl mx-auto px-5 sm:px-8">
                            <x-dynamic-component
                                :component="$componentName"
                                :data="$block['data']"
                                :lang="$lang"
                                :all-content="$allContent"
                            />
                        </div>
                    </section>
                @endif

            @endif
        @endforeach
    </div> --}}
    
</div>
