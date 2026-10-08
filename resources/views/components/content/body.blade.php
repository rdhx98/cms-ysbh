{{--
  <x-content.body> — mencetak ISI artikel/halaman/snippet dari nilai mentah kolom `content`, apa pun bentuknya:
  dokumen blok (builder) ATAU HTML lama (editor artikel lama; diimpor sebagai satu blok Paragraf). Pengganti `{!! $article->content !!}`.

    <x-content.body :raw="$article->getRawOriginal('content')" :lang="app()->getLocale()" />

  Data blok dibersihkan lagi di sini (BlockSanitizer): data bisa berasal dari impor atau edit langsung di database.
--}}
@props(['raw' => null, 'lang' => null])

@php
  $lang ??= app()->getLocale();
  $locales = config('app.supported_locales', ['id', 'en']);
  $doc = \App\Content\ContentDocument::fromRaw($raw, $locales);
  $blocks = \App\Content\Blocks\BlockSanitizer::forPublic($doc->blocks, $locales);
@endphp

<x-content.sections :blocks="$blocks" :order="$doc->order" :settings="$doc->settings" :lang="$lang" />
