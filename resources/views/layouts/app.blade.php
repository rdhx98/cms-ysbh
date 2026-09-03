<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body
    class="bg-misty font-sans antialiased overflow-x-hidden"
    x-init="console.log('✅ Alpine.js Berhasil Dimuat dan Aktif dari app layout!')"
    >
    <div class="flex flex-col md:flex-row h-dvh w-full"
      x-data="{ 
        isExpanded: Alpine.$persist(true), 
        userMenuExpand: false, 
        isAuditOpen: false }" >

        <x-layouts::app.sidebar />
        <main class="flex-1 min-w-0 flex flex-col md:p-[0.5rem_0.5rem_0.5rem_0rem] p-[0.5rem_0.5rem_0.5rem_0.5rem] space-y-2">
            <x-layouts::app.header :header="$header ?? '' " :title="$title ?? '' " />
            {{ $slot }}
        </main>
    </div>

    {{-- @livewireScripts --}}
    <x-layouts::app.floating-notifications mobileTop="top-16" />
</body>
</html>
