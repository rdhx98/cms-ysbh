{{--
  Rilis 13. Perubahan dari berkas lama:
  - judul, deskripsi, canonical, dan Open Graph dari variabel $title / $description (diisi halaman CMS lewat view()->share);
    halaman tanpa $title tetap memakai aturan lama (beranda "Sinar Bhakti Husada", lainnya nama aplikasi);
  - warna latar dari tabel settings DIVALIDASI (#RRGGBB) sebelum masuk ke CSS (sebelumnya nilai mentah dari basis data);
  - blok CSS lama yang sudah dikomentari (±200 baris) dihapus; riwayatnya ada di git.
--}}
@php
    $siteName = config('app.name', 'Sinar Bhakti Husada');
    // Beranda: judul penuh ditentukan editor (judul meta halaman beranda), tanpa akhiran " | nama situs". Halaman lain: "judul | nama situs".
    $pageTitle = filled($title ?? null)
        ? (request()->routeIs('home') ? $title : $title . ' | ' . $siteName)
        : (request()->routeIs('home') ? 'Sinar Bhakti Husada' : $siteName);
    $pageDescription = \Illuminate\Support\Str::limit(trim(strip_tags((string) ($description ?? ''))), 160, '');
    $pageCanonical = (string) ($canonical ?? url()->current());
    $pageImage = (string) ($ogImage ?? asset('ysbh.png'));

    // Warna latar dari CMS; hanya #RRGGBB yang diterima, selain itu bawaan. Gagal membaca basis data tidak menjatuhkan halaman.
    $landingBg = rescue(fn () => \App\Models\Setting::where('key', 'landing_bg_color')->value('value'), null, false);
    $landingBg = is_string($landingBg) && preg_match('/^#[0-9a-fA-F]{6}$/D', trim($landingBg)) ? trim($landingBg) : '#FBF7EA';
@endphp
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $pageTitle }}</title>
@if ($pageDescription !== '')
    <meta name="description" content="{{ $pageDescription }}">
@endif
<link rel="canonical" href="{{ $pageCanonical }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ filled($title ?? null) ? $title : $siteName }}">
@if ($pageDescription !== '')
    <meta property="og:description" content="{{ $pageDescription }}">
@endif
<meta property="og:url" content="{{ $pageCanonical }}">
<meta property="og:image" content="{{ $pageImage }}">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="{{ asset('logo/emblem-sinar-bhakti-husada.svg') }}" type="image/svg+xml" >

<style>
    :root {
        /* Menimpa warna CSS di Tailwind dengan pilihan dari CMS */
        --color-paper: {{ $landingBg }};
    }
</style>

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles()
