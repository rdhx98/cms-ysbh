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
    // public array $contentBlocks = [];
    public array $allContent = [];
    public array $rootOrder = [];

    
    public function mount($pageSlug = null)
    {
        // 1. Ambil bahasa yang sedang aktif di sistem
        $this->activeLocales = config('app.supported_locales', ['id', 'en']);
        $this->lang = app()->getLocale();

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
};
?>

<div class="h-[calc(100vh-4rem)] flex flex-col overflow-x-hidden bg-gray-50 p-6 box-border">

    <!-- Header Halaman -->
    <div class="mb-10 pb-6 border-b border-gray-200 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            @php
                // Ambil judul halaman
                $titleData = $page->getTranslations('title');
                $pageTitle = $titleData[$lang] ?? $titleData['id'] ?? 'Tanpa Judul';
            @endphp
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">{{ $pageTitle }}</h1>
            <p class="text-sm text-gray-500 mt-2">Pratinjau Halaman</p>
        </div>

        <!-- TOMBOL PENGALIH BAHASA -->
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

    <!-- MESIN RENDER BLOK KONTEN DINAMIS -->
    <div class="w-full bg-white"> 
        {{-- Looping HANYA blok level terluar (Root) dari $rootOrder --}}
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
                    {{-- 🌟 2A. JIKA MAKRO: Render langsung tanpa dibungkus apa pun --}}
                    <x-dynamic-component
                        :component="$componentName"
                        :data="$block['data']"
                        :lang="$lang"
                        :all-content="$allContent"
                    />
                @else
                    {{-- 🌟 2B. JIKA MIKRO: Bungkus otomatis dengan Container Standar --}}
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
    </div>

    <!-- MESIN RENDER BLOK KONTEN DINAMIS -->
    {{-- <div class="space-y-6">
        <!-- Looping HANYA blok level terluar (Root) dari $rootOrder -->
        @foreach($rootOrder as $blockId)
            @if(isset($allContent[$blockId]))
                @php
                    $block = $allContent[$blockId];

                    // Format nama komponen (contoh: 'hero_banner' menjadi 'blocks.frontend.hero-banner')
                    $componentName = 'blocks.render.' . str_replace('_', '-', $block['type']);
                @endphp

                <div class="block-wrapper mb-8">
                    <!-- PANGGIL KOMPONEN FRONTEND SECARA DINAMIS -->
                    <x-dynamic-component
                        :component="$componentName"
                        :data="$block['data']"
                        :lang="$lang"
                        :all-content="$allContent"
                    />
                </div>
            @endif
        @endforeach
    </div> --}}

</div>
