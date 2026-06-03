<aside
{{ $attributes->merge([
    'class' => '
    fixed top-0 left-0 z-50 w-64 -translate-x-full h-screen md:h-[calc(100vh-1rem)]

    md:relative md:translate-x-0

    transform-gpu transition-all duration-300 ease-in-out overflow-x-hidden flex flex-col justify-start bg-white text-white p-4 md:m-2 md:rounded-lg max-h-dvh'
    ]) }}
{{-- :class="isExpanded ? 'w-64' : 'w-20'" --}}
:class="{
    'translate-x-0': isExpanded,
    '-translate-x-full md:translate-x-0': !isExpanded,
    'md:w-64': isExpanded,
    'md:w-20': !isExpanded
}"
>

    <div class="flex items-center justify-start mb-2 h-11">
        <button
        @click="isExpanded = !isExpanded"

        class="p-2 hiden md:block hover:bg-sage-soft text-forest rounded-lg focus:outline-none">
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
        <h2
        x-show="isExpanded"
        x-transition:enter="transition ease-out duration-300 delay-150"
        x-transition:enter-start="opacity-0 translate-x-[-10px]"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        {{-- :class="isExpanded ? 'block' : 'hidden'"  --}}
        class="w-full text-forest text-center delay-300 text-xl font-bold tracking-wide whitespace-nowrap">CMS YSBH</h2>
    </div>
    <div class="border-t border-forest my-2"></div>
    <nav class="flex flex-col space-y-2">
        <x-layouts::app.sidebar-link
        route="{{ route('dashboard') }}"
        :active="request()->routeIs('dashboard')"
        icon="home">
            {{ __('Dashboard') }}
        </x-layouts.app.sidebar-link>

        <x-layouts::app.sidebar-link
        route="{{ route('article') }}"
        :active="request()->routeIs('article')"
        icon="square-pen">
            {{ __('Articles') }}
        </x-layouts.app.sidebar-link>

    </nav>
    {{-- <div class="border-t-2 border-forest my-2"></div> --}}
    <div class="flex-1 grow"></div>
    {{-- <div class="border-t-2 border-forest my-2"></div> --}}
    <nav flex flex-col space-y-2>
        <x-layouts::app.sidebar-link
            route="{{ route('documentation') }}"
            :active="request()->routeIs('documentation')"
            icon="book-open-text">
            {{ __('Documentation') }}
        </x-layouts.app.sidebar-link>
        {{-- <flux:sidebar.item icon="newspaper" :href="route('cms.articles')" :current="request()->routeIs('cms.articles*')" wire:navigate>
                        {{ __('Articles') }}
                    </flux:sidebar.item> --}}

        {{-- <form method="POST" action="{{ route('logout') }}" class="block pt-4 border-t border-slate-800">
            @csrf
            <button type="submit" class="text-red-400 hover:text-red-300 w-full text-left px-4 cursor-pointer">
                Logout
            </button>
        </form> --}}
    </nav>
</aside>
