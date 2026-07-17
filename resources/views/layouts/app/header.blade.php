@props(['title' => null])

<aside
{{-- {{ $attributes->merge(['class' => 'flex flex-row items-center justify-between bg-white text-forest rounded-lg h-10 p-2 ']) }} --}}
{{ $attributes->merge(['class' => 'flex flex-row items-center justify-between bg-white text-forest rounded-lg h-10 p-2 z-50']) }}
:class="isExpanded ? '' : ''"
>
    <button
        @click="isExpanded = !isExpanded"

        class="p-2 flex md:hidden hover:bg-sage-soft text-forest rounded-lg focus:outline-none">
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
        <div class=" bg-sage-soft py-1 px-2 rounded-4xl">
            {{ now()->format('d M Y') }}
            <span 
                x-data="{ time: '' }" 
                x-init="
                    const updateTime = () => {
                        // Memaksa zona waktu ke Jayapura, format 24 jam, tanpa detik
                        time = new Date().toLocaleTimeString('id-ID', { 
                            timeZone: 'Asia/Jayapura', 
                            hour: '2-digit', 
                            minute: '2-digit',
                            hour12: false
                        });
                    };
                    updateTime(); // Jalankan pertama kali
                    setInterval(updateTime, 1000); // Perbarui setiap detik
                "
                x-text="time"
                class="font-bold text-gray-700"
            >
            </span>
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
                    <x-dynamic-component
                        :component="'lucide-circle-user'"
                        class="h-5 w-5 text-forest"
                        stroke-width="2"
                    />
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
            class="absolute right-0 mt-3 w-56 bg-white rounded-3xl shadow-xl border border-zinc-100 overflow-hidden py-2"
        >
            <!-- Header Info (Opsional) -->
            <div class="px-4 py-3 border-b border-zinc-50 mb-1">
                <p class="text-xs text-zinc-400 font-medium uppercase tracking-wider">Akun Saya</p>
                <p class="text-sm font-bold text-forest truncate">{{ auth()->user()->name ?? 'Administrator' }}</p>
            </div>

            <!-- Menu Items -->
            <div class="flex flex-col space-y-0.5 px-2">
                <!-- Profil -->
                <a href="#" class="flex items-center px-3 py-2.5 text-sm font-medium text-zinc-600 rounded-2xl hover:bg-zinc-50 hover:text-forest transition-all group">
                    <svg class="w-5 h-5 mr-3 text-zinc-400 group-hover:text-forest" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profil
                </a>

                <!-- Pengaturan -->
                <a href="#" class="flex items-center px-3 py-2.5 text-sm font-medium text-zinc-600 rounded-2xl hover:bg-zinc-50 hover:text-forest transition-all group">
                    <svg class="w-5 h-5 mr-3 text-zinc-400 group-hover:text-forest" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Pengaturan
                </a>

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
