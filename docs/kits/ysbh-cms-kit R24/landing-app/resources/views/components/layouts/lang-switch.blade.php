{{--
  Rilis 24: sakelar bahasa. Tautan BIASA (bukan tombol Alpine, bukan navigasi SPA Livewire: muat ulang penuh agar <html lang> ikut berganti) ke versi
  bahasa lain halaman yang sedang dibuka: $alternates (bahasa => alamat) diisi komponen halaman lewat view()->share. Halaman yang belum
  diterjemahkan (atau halaman galat) menautkan ke beranda bahasa itu. Bahasa ditentukan HANYA oleh alamat: tidak ada cookie atau ?lang.
  Pemakaian: <x-layouts.lang-switch class="..." icon-class="w-4 h-4 mr-2" />  (satu tautan per bahasa selain yang sedang dibuka)
--}}
@props(['iconClass' => 'w-4 h-4 mr-2'])
@php
    $cfg = \App\Content\Languages::fromConfig();
    $currentLocale = app()->getLocale();
    $alts = is_array($alternates ?? null) ? $alternates : [];
    $languageNames = ['en' => 'English', 'id' => 'Bahasa Indonesia'];
@endphp
@foreach ($cfg['locales'] as $code)
    @continue($code === $currentLocale)
    <a href="{{ $alts[$code] ?? \App\Content\Links\LinkResolver::homeAddress($code) ?? '/' }}" hreflang="{{ $code }}" lang="{{ $code }}" title="{{ $languageNames[$code] ?? strtoupper($code) }}" {{ $attributes }}>
        <x-dynamic-component :component="'lucide-globe'" :class="$iconClass" stroke-width="2"/>
        <span>{{ strtoupper($code) }}</span>
    </a>
@endforeach
