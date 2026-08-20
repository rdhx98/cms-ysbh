@props([
    'command', 
    'activeName' => null,
    'activeParams' => '{}', 
    'activeType' => 'default', 
    'title' => '', 
    'icon' => null,
    'label' => null,
    'mode' => 'icon-label', // Pilihan: 'icon-only', 'icon-label', 'icon-hover'
])

@php
    $sanitizedParams = empty($activeParams) || $activeParams === 'null' ? '{}' : $activeParams;

    // Ekspresi Klik yang disederhanakan
    $clickExpression =
        $activeType === 'alpine'
            ? $command
            : ($activeType === 'textAlign'
                ? "runCommand('$command', { textAlign: '$activeName' })"
                : ($activeType === 'orderedList'
                    // Menyesuaikan dengan standar Tiptap untuk Ordered List kustom
                    ? "runCommand('toggleOrderedList', $sanitizedParams !== '{}' ? $sanitizedParams : null)"
                    : "runCommand('$command', $sanitizedParams !== '{}' ? $sanitizedParams : null)"));

    $classExpression =
        $activeType === 'alpine'
            ? '(updatedAt && ' . $activeName . ')'
            : "checkButtonActive('$activeName', $sanitizedParams, '$activeType')";
@endphp

<button type="button" 
    @click="{{ $clickExpression }}"
    :class="{
        'bg-sage-soft text-forest font-semibold shadow-sm': {{ $classExpression }},
        'bg-zinc-50 text-gray-700 border-transparent': !{{ $classExpression }}
    }"
    {{ $attributes->merge([
        'class' => 'group p-1.5 px-2.5 min-h-[2.25rem] h-9 transition-all duration-300 ease-out rounded flex items-center justify-center text-xs md:text-sm font-medium cursor-pointer hover:bg-sage-soft hover:text-forest hover:shadow-sm overflow-hidden',
    ]) }}
    title="{{ $title }}">

    @if ($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="h-4 w-4 shrink-0 transition-transform duration-300 ease-out group-hover:scale-110" stroke-width="2.5" />
    @else
        <x-dynamic-component :component="'lucide-bug'" class="h-4 w-4 shrink-0 transition-transform duration-300 ease-out group-hover:scale-110" stroke-width="2.5" />
    @endif

    @if ($label)
        @if ($mode === 'icon-only')
            <span class="sr-only">{{ $label }}</span>

        @elseif ($mode === 'icon-hover')
            <span class="overflow-hidden whitespace-nowrap opacity-0 max-w-0 -translate-x-3 transition-all duration-300 ease-out group-hover:translate-x-0 group-hover:max-w-[150px] group-hover:opacity-100 group-hover:ml-1.5">
                {{ $label }}
            </span>

        @else
            <span class="ml-1.5 whitespace-nowrap">{{ $label }}</span>
        @endif
    @endif

    {{ $slot }}
</button>