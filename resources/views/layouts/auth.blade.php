@props(['title' => null]) {{-- [!code highlight] --}}

<x-auth::split :title="$title ?? null">
    {{ $slot }}
</x-auth::split>
