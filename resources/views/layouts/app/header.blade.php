<aside
{{ $attributes->merge(['class' => 'flex bg-sage-soft text-white p-6 m-2 rounded-lg md:hidden']) }}
:class="isExpanded ? '' : ''"
>
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
</aside>
