@props([
    'command', 
    // 'activeName', 
    'activeName' => null,
    'activeParams' => '{}', 
    'activeType' => 'default', 
    'title' => '', 
    'icon' => null, 
])

@php
    $sanitizedParams = empty($activeParams) || $activeParams === 'null' ? '{}' : $activeParams;

    $clickExpression =
        $activeType === 'alpine'
            ? $command
            : ($activeType === 'textAlign'
                ? "runCommand('$command', '$activeName')"
                : ($activeType === 'orderedList'
                    ? "toggleCustomOrderedList('$activeName')"
                    : "runCommand('$command', $sanitizedParams !== '{}' ? $sanitizedParams : null)"));

    $classExpression =
        $activeType === 'alpine'
            ? '(updatedAt && ' . $activeName . ')'
            : "checkButtonActive('$activeName', $sanitizedParams, '$activeType')";
@endphp

<button type="button" 
    @click="{{ $clickExpression }}"

    {{-- 🌟 LOGIKA PENGUNCIAN: Tombol akan ter-disable dan kursor berubah saat isUploading = true --}}
    :disabled="isUploading"

    :class="{
        'opacity-50 cursor-not-allowed grayscale': isUploading,
        'bg-sage-soft text-forest font-semibold shadow-sm': !isUploading && {{ $classExpression }},
        'bg-zinc-50 text-gray-700 border-transparent': !isUploading && !{{ $classExpression }}
    }"
        {{-- 'bg-sage-soft text-forest border-forest font-semibold shadow-sm': !isUploading && {{ $classExpression }}, --}}

    {{ $attributes->merge([
        'class' => 'p-1.5 min-w-[2.25rem] h-9 transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer hover:bg-sage-soft hover:text-forest',
    ]) }}

    title="{{ $title }}">

    @if ($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="h-4 w-4" stroke-width="2.5" />
    @else
        <x-dynamic-component :component="'lucide-bug'" class="h-4 w-4" stroke-width="2.5" />
    @endif

    {{ $slot }}
</button>