@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body
    class="bg-gray-100 flex items-center justify-center min-h-screen"
    x-data="{ showPassword: false }"
    x-init="console.log('✅ Alpine.js Berhasil Dimuat dan Aktif dari auth layout!')"
    >

    {{ $slot }}
    {{-- karena tidak ada komponen livewire disisi ini, tidak ada auto injection app.js --}}
    @livewireScripts
</body>
</html>
