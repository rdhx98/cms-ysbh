@props(['title' => null])

<aside
{{ $attributes->merge(['class' => 'flex flex-row items-center justify-between bg-white text-forest rounded-lg h-10 p-2 z-50']) }}
:class="isExpanded ? '' : ''"
>
    <div class="">
        <button x-on:click="isExpanded = !isExpanded" class="p-2 flex md:hidden hover:bg-sage-soft text-forest rounded-lg focus:outline-none">
            <div class="relative flex items-center justify-center w-5 h-5">
                <span x-cloak
                    :class="isExpanded ? 'rotate-45 translate-y-0' : '-translate-y-1.5'"
                    class="absolute block h-0.5 w-5 bg-current transition-all duration-300">
                </span>
                <span x-cloak
                    :class="isExpanded ? 'opacity-0' : 'opacity-100'"
                    class="absolute block h-0.5 w-5 bg-current transition-all duration-300">
                </span>
                <span x-cloak
                    :class="isExpanded ? '-rotate-45 translate-y-0' : 'translate-y-1.5'"
                    class="absolute block h-0.5 w-5 bg-current transition-all duration-300">
                </span>
            </div>
        </button>

        <div class="text-sm font-bold text-forest tracking-wide p-2 flex items-center gap-4">
            {{ $title }}
        </div>
    </div>

    <button
        @click="userMenuExpand = !userMenuExpand"
        @click.away="userMenuExpand = false"
        {{ $attributes->merge([
            'class' => 'cursor-pointer flex justify-center items-center rounded-2xl group border-2 border-forest whitespace-nowrap transition-all duration-300 easew-full-in-out h-11 w-11 bg-sage-soft text-white font-semibold shadow-sm '
        ]) }}
        >
        <div class="flex items-center justify-center w-9 h-9 shrink-0 transition-transform duration-200 group-hover:scale-105 ">
            @if(auth()->user()->avatar)
                {{-- avatar here --}}
            @else
                <span class="text-foresty">{{ auth()->user()->initials() }}</span>
            @endif
        </div>
    </button>

    <!-- Container Profil (Sudut Kanan Atas) -->
    <div class="fixed top-12 right-2 z-50">
        <!-- THE ISLAND (Floating Menu) -->
        <div
            x-show="userMenuExpand"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-[-10px]"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-[-10px]"
            class="absolute right-0 mt-3 w-64 bg-white rounded-3xl shadow-xl border border-foresty overflow-hidden py-2"
        >
            <!-- Header Info -->
            <div class="px-4 py-3 border-b border-zinc-100 mb-1">
                <p class="text-xs text-zinc-400 font-medium uppercase tracking-wider mb-1">Akun Saya</p>
                <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1.5">
                    <p class="text-sm font-bold text-forest max-w-full truncate">
                        {{ auth()->user()->name ?? 'Administrator' }}
                    </p>
                    <span class="bg-goldy rounded-4xl px-2 py-0.5 text-xs text-center shrink-0 whitespace-nowrap">
                        {{ auth()->user()->handle() ?? '' }}
                    </span>
                </div>
            </div>

            <!-- Menu Items -->
            <div class="flex flex-col space-y-0.5 px-2">

                <!-- Profil -->
                <a href="{{ route('user.detail', auth()->user()) }}" wire:navigate class="flex items-center px-3 py-2.5 text-sm font-medium text-zinc-600 rounded-2xl hover:bg-zinc-50 hover:text-forest transition-all group">
                    <svg class="w-5 h-5 mr-3 text-zinc-400 group-hover:text-forest" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profil
                </a>

                <!-- Pengaturan -->
                <a href="#" class="flex items-center px-3 py-2.5 text-sm font-medium text-zinc-600 rounded-2xl hover:bg-zinc-50 hover:text-forest transition-all group">
                    <svg class="w-5 h-5 mr-3 text-zinc-400 group-hover:text-forest" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Pengaturan
                </a>

                <!-- 🌟 TOGGLE BAHASA (BARU) 🌟 -->
                <div class="flex items-center justify-between px-3 py-2 text-sm font-medium text-zinc-600 rounded-2xl hover:bg-zinc-50 transition-all group">
                    <div class="flex items-center">
                        {{-- <svg class="w-5 h-5 mr-3 text-zinc-400 group-hover:text-forest" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg> --}}
                        <x-dynamic-component :component="'lucide-earth'" class="w-5 h-5 mr-3 text-zinc-400 group-hover:text-forest" stroke-width="2.5" />

                        Bahasa
                    </div>

                    <!-- Kotak Switch Bahasa -->
                    {{-- <div class="flex items-center bg-zinc-100 rounded-xl p-1 gap-1 border border-zinc-200">
                        <a href="{{ route('language.switch', 'id') }}"
                           class="px-2.5 py-1 text-xs font-bold rounded-lg transition-all {{ app()->getLocale() === 'id' ? 'bg-foresty text-white shadow-sm border border-zinc-200/50' : 'text-zinc-400 hover:text-zinc-600' }}">
                            ID
                        </a>
                        <a href="{{ route('language.switch', 'en') }}"
                           class="px-2.5 py-1 text-xs font-bold rounded-lg transition-all {{ app()->getLocale() === 'en' ? 'bg-foresty   text-white shadow-sm border border-zinc-200/50' : 'text-zinc-400 hover:text-zinc-600' }}">
                            EN
                        </a>
                    </div> --}}
                    <div class="flex items-center bg-white/40 backdrop-blur-md border border-forest/20 rounded-full p-1 shadow-sm">
                            <a href="{{ route('language.switch', 'id') }}"
                            class="px-3 py-1 text-xs font-bold rounded-full transition-all {{ app()->getLocale() === 'id' ? 'bg-forest text-white shadow-md' : 'text-forest/70 hover:text-forest hover:bg-forest/10' }}">
                                ID
                            </a>
                            <a href="{{ route('language.switch', 'en') }}"
                            class="px-3 py-1 text-xs font-bold rounded-full transition-all {{ app()->getLocale() === 'en' ? 'bg-forest text-white shadow-md' : 'text-forest/70 hover:text-forest hover:bg-forest/10' }}">
                                EN
                            </a>
                        </div>
                </div>
                <!-- 🌟 END TOGGLE BAHASA 🌟 -->

                <!-- Divider -->
                <div class="h-px bg-zinc-100 my-1 mx-2"></div>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="cursor-pointer w-full flex items-center px-3 py-2.5 text-sm font-bold text-red-500 rounded-2xl hover:bg-red-50 transition-all group">
                        <svg class="w-5 h-5 mr-3 text-red-400 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
