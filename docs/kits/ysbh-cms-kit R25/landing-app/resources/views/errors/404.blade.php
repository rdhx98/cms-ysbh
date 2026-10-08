{{-- Rilis 24: bahasa dari ALAMAT (galat dilempar sebelum/di luar mount komponen, jadi bahasa belum ditetapkan di sini). --}}
@php($isId = \App\Content\Languages::forRequest() === 'id')
<x-layouts.app>
    <section class="mx-auto max-w-3xl px-6 py-24 text-center">
        <p class="font-jakarta text-sm font-bold tracking-[0.14em] text-coral uppercase">404</p>
        <h1 class="font-fraunces mt-3 text-4xl font-bold text-forest">{{ $isId ? 'Halaman tidak ditemukan' : 'Page not found' }}</h1>
        <p class="mt-4 text-ink-soft">{{ $isId ? 'Alamat yang Anda buka tidak ada atau belum diterbitkan.' : 'The address you opened does not exist or has not been published yet.' }}</p>
        <a href="{{ \App\Content\Links\LinkResolver::homeAddress() ?? '/' }}" class="mt-8 inline-flex rounded-full bg-coral px-6 py-3 font-bold text-white">{{ $isId ? 'Kembali ke beranda' : 'Back to the home page' }}</a>
    </section>
</x-layouts.app>
