<?php

use Livewire\Component;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

new #[Layout('layouts.landing.dynamic-preview')] class extends Component {
    public ?Page $page = null;
    public string $lang; 
    public array $activeLocales = []; 
    public array $allContent = [];
    public array $rootOrder = [];
    public array $settings = []; 
    public string $viewMode = 'full';

    public function mount($pageSlug = null)
    {
        $this->viewMode = request()->query('mode', 'full');
        $this->activeLocales = config('app.supported_locales', ['id', 'en']);
        $this->lang = request()->query('lang', app()->getLocale());

        if (!empty($pageSlug)) {
            $this->page = Page::where(function ($query) use ($pageSlug) {
                if (is_numeric($pageSlug)) {
                    $query->where('id', $pageSlug);
                }
                $query->orWhere('slug', $pageSlug);
                $query->orWhere('slug', 'LIKE', '%"' . $pageSlug . '"%');
            })->firstOrFail();
        } else {
            abort(404);
        }

        $modelData = $this->page->toArray();
        $rawContent = $modelData['content'] ?? [];

        if (is_string($rawContent)) {
            $decoded = json_decode($rawContent, true) ?? [];
            $rawContent = is_string($decoded) ? json_decode($decoded, true) ?? [] : $decoded;
        }

        // Ekstrak blok dan urutan
        $this->allContent = $rawContent['blocks'] ?? [];
        $this->rootOrder = $rawContent['order'] ?? [];

        // Penyelamatan Setting 
        $this->settings = $this->allContent['settings'] ?? ($rawContent['settings'] ?? []);
        unset($this->allContent['settings']); 
    }

    #[On('change-preview-lang')]
    public function updatePreviewLanguage($newLang)
    {
        if (in_array($newLang, $this->activeLocales)) {
            $this->lang = $newLang;
        }
    }
};
?>

{{-- 🌟 KEMBALI KE ASLI: Aman, tidak mengubah lebar halaman, Navbar tidak akan bergeser --}}
<div class="h-full flex flex-col overflow-x-hidden box-border w-full" 
     @message.window="if ($event.data && $event.data.type === 'change-lang') $wire.set('lang', $event.data.lang)" 
     x-data="{ previewLang: @entangle('lang').live }" 
     x-init="setTimeout(() => window.initScrollReveal?.(), 150); $watch('previewLang', () => setTimeout(() => window.initScrollReveal?.(), 50));">

  @if ($viewMode === 'full')
    <template x-teleport="#editor-toolbar-portal">
      <div class="border-gray-200 flex flex-row justify-between gap-6 w-full">
        <div class="flex items-center justify-center gap-2">
          @php
            $titleData = $page->getTranslations('title');
            $pageTitle = $titleData[app()->getLocale()] ?? ($titleData['id'] ?? 'Tanpa Judul');
          @endphp
          <span class="block text-[10px] font-bold text-gray-400 uppercase md:text-right">Pratinjau Halaman</span>
          <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">{{ $pageTitle }}</h1>
        </div>

        <div class="flex items-center gap-6 shrink-0">
          <span class="block text-[10px] font-bold text-gray-400 uppercase md:text-right">Lihat Sebagai:</span>
          <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg border border-gray-200 shadow-inner">
            @foreach ($activeLocales as $code)
              <button type="button" @click="previewLang = '{{ $code }}'" class="px-4 py-1 text-xs font-bold rounded-md transition-all duration-200 select-none cursor-pointer"
                :class="previewLang === '{{ $code }}' ? 'bg-white text-forest border border-gray-200/50 shadow-sm' : 'text-gray-500 border border-transparent hover:text-gray-800 hover:bg-gray-200/50'">
                {{ strtoupper($code) }}
              </button>
            @endforeach
          </div>
        </div>
      </div>
    </template>
  @endif

  <!-- MESIN RENDER BLOK KONTEN DINAMIS -->
  <div class="w-full bg-paper relative">
    @php
      $groupedSections = [];
      $tocItems = []; 

      // Pengaturan Posisi TOC
      $tocPosition = $settings['toc_position'] ?? 'right';

      $currentSection = [
          'bgClass' => 'bg-paper',
          'textClass' => 'text-gray-900',
          'padding' => 'py-16 sm:py-24',
          'anchor'  => '', 
          'blocks'  => [],
      ];

      // PENGELOMPOKAN SEKSI DAN PERAKITAN DAFTAR ISI
      foreach ($rootOrder as $blockId) {
          if (!isset($allContent[$blockId])) {
              continue;
          }
          $block = $allContent[$blockId];
          $normalizedType = str_replace('-', '_', $block['type']); 

          // Deteksi Blok Anchor
          if (!empty($block['anchor'])) {
              $tocTitle = '';
              
              $extractText = function($dataField) {
                  if (is_array($dataField)) {
                      return $dataField[$this->lang] ?? $dataField['id'] ?? reset($dataField) ?? '';
                  }
                  return $dataField ?? '';
              };

              if ($normalizedType === 'heading') {
                  $tocTitle = strip_tags($extractText($block['data']['text'] ?? ''));
              } 
              elseif ($normalizedType === 'section_divider') {
                  $tocTitle = strip_tags($extractText($block['data']['title'] ?? ''));
              }
              
              if (empty($tocTitle) && !empty($block['data']['title'])) {
                  $tocTitle = strip_tags($extractText($block['data']['title']));
              }

              if (!empty(trim($tocTitle))) {
                  $tocItems[] = [
                      'title' => trim($tocTitle),
                      'anchor' => $block['anchor']
                  ];
              }
          }

          if ($normalizedType === 'section_divider') {
              if (count($currentSection['blocks']) > 0) {
                  $groupedSections[] = $currentSection;
              }

              $currentSection = [
                  'bgClass'   => $block['data']['background'] ?? 'bg-paper',
                  'textClass' => $block['data']['text_color'] ?? 'text-gray-900',
                  'padding'   => $block['data']['padding'] ?? 'py-16 sm:py-24',
                  'anchor'    => $block['anchor'] ?? '', 
                  'blocks'    => [],
              ];
              continue;
          }

          $currentSection['blocks'][] = $blockId;
      }

      if (count($currentSection['blocks']) > 0) {
          $groupedSections[] = $currentSection;
      }
    @endphp

    
    {{-- ================================================================ --}}
    {{-- 🌟 WIDGET KACA DENGAN ALIGNMENT LUAR MAX-W-7XL 🌟 --}}
    {{-- ================================================================ --}}
    @if(count($tocItems) > 0 && $tocPosition !== 'hidden')
        <!-- 
          Pembungkus 'fixed inset-0' membuat area ini kebal overflow dan tidak mengubah Navbar.
          Gunakan '2xl:flex' agar TOC hanya muncul di layar 1536px ke atas (cukup ruang untuk Konten + TOC).
        -->
        <div class="fixed inset-0 z-50 hidden 2xl:flex justify-center pointer-events-none">
            
            <!-- Kontainer patokan: Sama lebarnya dengan max-w-7xl konten utama -->
            <div class="w-full max-w-7xl relative h-full">
                
                @if($tocPosition === 'left')
                    <!-- TOC DARI KIRI: Mendorong tepat ke luar batas kiri max-w-7xl -->
                    <div class="absolute right-full top-32 mr-8 w-64 pointer-events-auto transition-all duration-500">
                        <div class="max-h-[75vh] overflow-y-auto scrollbar-hide bg-white/40 backdrop-blur-xl border border-white/60 shadow-2xl rounded-2xl p-5">
                            <x-table-of-contents :items="$tocItems" />
                        </div>
                    </div>
                @else
                    <!-- TOC DARI KANAN: Mendorong tepat ke luar batas kanan max-w-7xl -->
                    <div class="absolute left-full top-32 ml-8 w-64 pointer-events-auto transition-all duration-500">
                        <div class="max-h-[75vh] overflow-y-auto scrollbar-hide bg-white/40 backdrop-blur-xl border border-white/60 shadow-2xl rounded-2xl p-5">
                            <x-table-of-contents :items="$tocItems" />
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @endif

    {{-- EKSEKUSI RENDER HTML KONTEN UTAMA --}}
    @foreach ($groupedSections as $section)
      <section id="{{ $section['anchor'] }}" class="w-full relative {{ $section['bgClass'] }} {{ $section['textClass'] }} {{ $section['padding'] }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

          @foreach ($section['blocks'] as $index => $blockId)
            @php
              $block = $allContent[$blockId];
              $componentName = 'blocks.render.' . str_replace('_', '-', $block['type']);
              $delay = min($index * 150, 750);
            @endphp

            <div id="{{ !empty($block['anchor']) ? $block['anchor'] : $blockId }}" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)" :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'" class="transition-all duration-700 ease-out delay-[{{ $delay }}ms]">

              <x-dynamic-component
                :component="$componentName"
                :block="$block"
                :data="$block['data']"
                :lang="$lang"
                :all-content="$allContent"
              />

            </div>
          @endforeach

        </div>
      </section>
    @endforeach
  </div>

</div>