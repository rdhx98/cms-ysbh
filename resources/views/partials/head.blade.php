<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

{{-- <link rel="icon" href="/favicon.ico" sizes="any"> --}}
{{-- <link rel="icon" href="/favicon.svg" type="image/svg+xml"> --}}
<link rel="icon" href="/logo/logo-ysbh-v2-diamond.svg" type="image/svg+xml">
{{-- <link rel="apple-touch-icon" href="/apple-touch-icon.png"> --}}

{{-- reference --}}
{{-- <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest"> --}}

 {{-- <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> --}}

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
{{-- @fluxAppearance --}}
<script>
    console.log('✅ Vite Assets from HEAD Loaded Successfully!');
</script>
