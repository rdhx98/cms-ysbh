<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body
    {{-- min-h-screen --}}
    {{-- class="bg-zinc-100 font-sans antialiased overflow-x-hidden" --}}
    class="bg-misty font-sans antialiased overflow-x-hidden"
    x-init="console.log('✅ Alpine.js Berhasil Dimuat dan Aktif dari app layout!')"
    >

    {{-- <div class="flex flex-col md:flex-row h-screen overflow-hidden min-h-screen" x-data="{ isExpanded: Alpine.$persist(true), userMenuExpand: false }" > --}}
    {{-- <div class="flex flex-col md:flex-row " x-data="{ isExpanded: Alpine.$persist(true), userMenuExpand: false }" > --}}
    <div class="flex flex-col md:flex-row h-dvh w-full" x-data="{ isExpanded: Alpine.$persist(true), userMenuExpand: false, isAuditOpen: false }" >

        <x-layouts::app.sidebar />
        <main class="flex-1 min-w-0 flex flex-col md:p-[0.5rem_0.5rem_0.5rem_0rem] p-[0.5rem_0.5rem_0.5rem_0.5rem] space-y-2">
        {{-- <main class="flex-1 min-w-0 md:p-[0.5rem_0.5rem_0.5rem_0rem] p-[0.5rem_0.5rem_0.5rem_0.5rem] space-y-2 "> --}}
            <x-layouts::app.header :title="$title ?? 'Nu uh'" />
            {{ $slot }}
        </main>
    </div>

    {{-- @livewireScripts --}}
    <x-layouts::app.floating-notifications mobileTop="top-16" />
</body>
</html>
