{{--
  Rilis 23. Dipakai bila beranda CMS belum dibuat (abort 503 di ⚡page-show) dan juga oleh `php artisan down`.
  Memakai layout landing (menu dan kaki halaman), jadi pengunjung bisa membuka halaman lain yang sudah ada.
--}}
{{-- Rilis 24: bahasa dari ALAMAT (galat dilempar sebelum/di luar mount komponen, jadi bahasa belum ditetapkan di sini). --}}
@php($isId = \App\Content\Languages::forRequest() === 'id')
<x-layouts.app>
    <section class="mx-auto max-w-3xl px-6 py-24 text-center">
        <p class="font-jakarta text-sm font-bold tracking-[0.14em] text-coral uppercase">Yayasan Sinar Bhakti Husada</p>
        <h1 class="font-fraunces mt-3 text-4xl font-bold text-forest">{{ $isId ? 'Situs sedang disiapkan' : 'Our site is being prepared' }}</h1>
        <p class="mt-4 text-ink-soft">{{ $isId ? 'Halaman ini akan segera tersedia. Silakan kembali beberapa saat lagi.' : 'This page will be available shortly. Please check back soon.' }}</p>
    </section>
</x-layouts.app>
