<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')

</head>
<body
    class="bg-zinc-100 font-sans antialiased min-h-screen"
    x-init="console.log('✅ Alpine.js Berhasil Dimuat dan Aktif dari app layout!')"
    >


    <div
        class="flex flex-col min-h-screen"
        x-data="{ isSticky: false }"
    {{-- class="flex flex-col md:flex-row h-screen overflow-hidden" --}}
    {{-- x-data="{ isExpanded: Alpine.$persist(true), userMenuExpand: false }" --}}
    >
        <x-layouts.header />

        {{-- <x-layouts::app.sidebar /> --}}

        {{-- <main class="flex-1 py-2 pr-2"> --}}
        <main
         class="flex-1 bg-paper space-y-2"
        {{-- md:p-4 overflow-y-auto h-full  --}}
         >
            {{-- <x-layouts::app.header :title="$title ?? 'Nu uh'" /> --}}
            {{ $slot }}
        </main>

        <x-layouts.footer />
    </div>


    @livewireScripts
    {{-- <x-layouts::app.floating-notifications mobileTop="top-16" /> --}}
</body>
</html>
