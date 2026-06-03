@props(['title' => null]) {{-- [!code highlight] --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    {{ $slot }}

</body>
</html>
