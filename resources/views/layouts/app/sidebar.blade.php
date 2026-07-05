<aside
{{ $attributes->merge([
    'class' => '
    fixed top-0 left-0 z-50 -translate-x-full h-screen md:h-[calc(100vh-1rem)]

    md:relative md:translate-x-0

    transform-gpu transition-all duration-300 ease-in-out overflow-x-hidden flex flex-col items-start bg-white text-white p-1 md:mx-1.5  md:my-2 md:rounded-lg max-h-dvh md:border-0 border-r-2 border-forest'
    ]) }}
{{-- :class="isExpanded ? 'w-64' : 'w-20'" --}}
:class="{
    'translate-x-0': isExpanded,
    '-translate-x-full md:translate-x-0': !isExpanded,
    'w-72 md:w-64': isExpanded,
    'w-72 md:w-11': !isExpanded
}"
>
    {{-- top row expand button and title --}}
    <div class="flex items-center h-11 w-full whitespace-nowrap">
        <button
            @click="isExpanded = !isExpanded"
            class=" hover:bg-sage-soft text-forest rounded-lg focus:outline-none h-9 items-center justify-center transition-colors duration-300 shrink-0">
             <div class="relative flex items-center justify-center w-9 h-5">
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
    <nav class="flex flex-col w-full space-y-2">

        <x-layouts::app.sidebar-link
        route="{{ route('dashboard') }}"
        :active="request()->routeIs('dashboard')"
        icon="home">
            {{ __('Dashboard') }}
        </x-layouts.app.sidebar-link>

        <x-layouts::app.sidebar-link
        route="{{ route('article.index') }}"
        :active="request()->routeIs('article.*')"
        icon="square-pen">
            {{ __('Articles') }}
        </x-layouts.app.sidebar-link>

        <x-layouts::app.sidebar-link
        route="{{ route('article.index') }}"
        :active="request()->routeIs('article.*')"
        icon="chevrons-left-right-ellipsis"
        iconSize="5">
            {{ __('Laman') }}
        </x-layouts.app.sidebar-link>

    </nav>
    {{-- <div class="border-t-2 border-forest my-2"></div> --}}
    <div class="flex-1 grow"></div>
    {{-- <div class="border-t-2 border-forest my-2"></div> --}}
    <nav class="flex w-full flex-col space-y-2 mb-2">
        <x-layouts::app.sidebar-link
            route="{{ route('documentation') }}"
            :active="request()->routeIs('documentation')"
            icon="book-open-text">
            {{ __('Documentation') }}
        </x-layouts.app.sidebar-link>
    </nav>
</aside>
