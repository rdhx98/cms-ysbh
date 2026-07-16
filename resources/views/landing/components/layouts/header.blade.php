<header>
    {{-- <div x-data="{ isSticky: false }" @scroll.window="isSticky = (window.scrollY >= $refs.contentStart.offsetTop)" class="hidden md:flex md:relative w-full"> --}}
    <div x-data="{ isSticky: false }" @scroll.window="isSticky = (window.scrollY >= (document.getElementById('contentStart')?.offsetTop || 300))" class="hidden md:flex md:relative w-full">
        <div class="flex flex-col w-full">
            <header class="shrink-0">
                <div class="bg-paper">
                    <div class="flex flex-row justify-between items-stretch">
                        <div class="mx-4 text-2xl flex items-center py-4">
                            <img src="{{ asset('/ysbh.png') }}" alt="Logo YSBH" class="h-16 mr-4">
                            <div class="flex flex-col items-start justify-center">
                                <span class="text-forest font-fraunces text-3xl font-bold leading-[1.15]">Sinar Bhakti Husada</span>
                                <span class="text-coral block font-jakarta font-semibold text-sm tracking-[0.14em] uppercase">Yayasan Kesehatan</span>
                            </div>
                        </div>
                        <div class="mx-4 text-forest flex items-center">
                            <button
                                x-data="{ lang: 'ID' }"
                                @click="lang = lang === 'ID' ? 'EN' : 'ID'; console.log('Bahasa saat ini:', lang);"
                                type="button" class="flex items-center border border-forest rounded-lg p-2 cursor-pointer hover:bg-forest hover:text-white transition-colors">
                                <x-dynamic-component :component="'lucide-globe'" class="w-4 h-4 mr-2" stroke-width="2"/>
                                <span x-text="lang"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="min-h-8 bg-paper border-b-2 border-t-2 border-forest">
                    <div class="flex flex-row justify-between items-stretch">
                        <div class="mx-4 text-forest text-lg flex items-center py-1.5">
                            {{-- <img src="{{ asset('/ysbh.png') }}" alt="Logo YSBH" class="h-8 mr-4">
                            <span class="text-forest font-bold">Yayasan Sinar Bhakti Husada</span> --}}
                        </div>
                        <div class="mx-4 text-forest flex items-stretch">
                            @foreach ($navLinks as $link)
                                <a wire:navigate href="{{ route($link['route']) }}" class="flex items-center border-y-4 mr-4 {{ request()->routeIs($link['route']) ? 'text-forest border-b-forest border-t-paper' : 'text-forest hover:text-forest border-paper' }}">{{ $link['label'] }}</a>

                            @endforeach
                        </div>
                    </div>
                </div>
            </header>
        </div>
        {{-- DESKTOP STICKY NAV --}}
        <div x-cloak
            x-show="isSticky"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="-translate-y-full"
            class="fixed top-0 inset-x-0 z-50 shadow-lg bg-white">
            <div class="min-h-8 bg-paper border-b-2 border-forest">
                <div class="flex flex-row justify-between items-stretch">
                    <div class="mx-4 text-forest text-lg flex items-center py-1.5">
                        <img src="{{ asset('/ysbh.png') }}" alt="Logo YSBH" class="h-8 mr-4">
                        <span class="text-forest font-bold uppercase font-jakarta tracking-[0.14em] leading-[1.15]">Sinar Bhakti Husada</span>
                    </div>
                    <div class="mx-4 text-forest flex items-stretch">
                        @foreach ($navLinks as $link)
                                <a wire:navigate href="{{ route($link['route']) }}" class="flex items-center border-y-4 mr-4 {{ request()->routeIs($link['route']) ? 'text-forest border-b-forest border-t-paper' : 'text-forest hover:text-forest border-paper' }}">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- MOBILE NAV --}}
    <div x-data="{ mobileMenuOpen: false }" class="md:hidden">

        <div class="fixed bottom-0 inset-x-0 bg-white border-t-2 border-forest z-40 shadow-[0_-4px_10px_rgba(0,0,0,0.05)] pb-2">
            <div class="flex justify-between items-center h-16 px-4">
                <div class="flex items-center">
                    <img src="{{ asset('/ysbh.png') }}" alt="Logo YSBH" class="h-8 mr-4">
                    <span class="text-forest font-bold text-sm uppercase">Yayasan Sinar Bhakti Husada</span>
                    {{-- max-w-30 --}}
                </div>

                <div class="flex items-center space-x-3">
                    {{-- <button
                        x-data="{ lang: 'ID' }"
                        @click="lang = lang === 'ID' ? 'EN' : 'ID'"
                        type="button" class="flex items-center border border-forest rounded-lg p-1.5 cursor-pointer hover:bg-forest hover:text-white transition-colors text-xs text-forest">
                        <x-dynamic-component :component="'lucide-globe'" class="w-3 h-3 mr-1" stroke-width="2"/>
                        <span x-text="lang"></span>
                    </button> --}}

                    <button @click="mobileMenuOpen = true" class="text-forest p-2 bg-gray-50 rounded-lg border border-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div x-cloak
            x-show="mobileMenuOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileMenuOpen = false"
            class="fixed inset-0 bg-black/50 z-50">
        </div>

        <div x-cloak
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            class="fixed bottom-0 inset-x-0 bg-white z-50 rounded-t-3xl shadow-2xl max-h-[85vh] flex flex-col">

            <div class="flex justify-center pt-3 pb-2" @click="mobileMenuOpen = false">
                <div class="w-12 h-1.5 bg-gray-300 rounded-full cursor-pointer"></div>
            </div>

            <div class="px-6 pb-4 pt-2 flex justify-between items-center border-b border-gray-100 shrink-0">
                <button
                    x-data="{ lang: 'ID' }"
                    @click="lang = lang === 'ID' ? 'EN' : 'ID'"
                    type="button" class="flex items-center border border-forest rounded-lg p-1.5 cursor-pointer hover:bg-forest hover:text-white transition-colors text-xs text-forest">
                    <x-dynamic-component :component="'lucide-globe'" class="w-3 h-3 mr-1" stroke-width="2"/>
                    <span x-text="lang"></span>
                </button>
                <h3 class="text-forest font-bold text-xl">Menu Navigasi</h3>
                <button @click="mobileMenuOpen = false" class="text-gray-400 hover:text-forest p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="px-6 py-6 overflow-y-auto flex-1 pb-12">
                <div class="flex flex-col space-y-3">
                    @foreach ($navLinks as $link)
                        <a wire:navigate
                            href="{{ route($link['route']) }}"
                            @click="mobileMenuOpen = false"
                            class="flex items-center px-4 py-3.5 rounded-xl border transition-colors {{ request()->routeIs($link['route']) ? 'bg-forest text-white border-forest shadow-md' : 'bg-gray-50 text-forest border-gray-100 hover:bg-forest/10' }}">

                            <span class="font-medium text-lg">{{ $link['label'] }}</span>

                            <svg class="w-5 h-5 ml-auto opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</header>
