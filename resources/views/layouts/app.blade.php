<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    @livewireStyles
</head>
<body class="bg-gray-100 font-sans antialiased">

    <div class="flex min-h-screen">
        <aside class="w-64 bg-slate-900 text-white p-6">
            <h2 class="text-xl font-bold mb-6 tracking-wide">CMS YSBH</h2>
            <nav class="space-y-2">
                <a href="{{ route('dashboard') }}" class="block py-2 px-4 rounded bg-slate-800 text-white font-medium">
                    Dashboard
                </a>

                <form method="POST" action="{{ route('logout') }}" class="block pt-4 border-t border-slate-800">
                    @csrf
                    <button type="submit" class="text-red-400 hover:text-red-300 w-full text-left px-4 cursor-pointer">
                        Logout
                    </button>
                </form>
            </nav>
        </aside>

        <main class="flex-1 p-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
