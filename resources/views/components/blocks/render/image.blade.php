{{-- @props (["block", "data", "lang", "allContent" => []])

@php
  $imageUrl = $data["url"] ?? "";
  $caption = $data["caption"][$lang] ?? "";

  // Ambil kelas padding yang dipilih, atau kosongkan jika menggunakan default
  $paddingTop = $data["padding_top"] ?? "";
  $paddingBottom = $data["padding_bottom"] ?? "";
@endphp

@if (!empty($imageUrl))
  <figure
    id="{{ $block['anchor'] ?? '' }}"
    class="w-full {{ $paddingTop }} {{ $paddingBottom }} reveal animate-scroll-reveal"
  >
    <img
      src="{{ $imageUrl }}"
      alt="{{ $caption ?: 'Gambar' }}"
      class="h-auto w-full rounded-lg shadow-sm"
    />

    @if (!empty($caption))
      <figcaption class="mt-3 text-center text-sm text-gray-500 italic">
        {{ $caption }}
      </figcaption>
    @endif
  </figure>
@endif --}}

@php
  /*
        'image'      => [
          'url' => '',
          'margin_bottom' => 'mb-4 md:mb-6',
          'caption'       => $emptyLocales,  // 🌟 Mendukung terjemahan multi-bahasa
          'width'         => 'w-full',       // Default menyesuaikan lebar kontainer induk
          'align'         => 'mx-auto',      // Rata tengah
          'radius'        => 'rounded-xl',   // Sudut melengkung halus
          'max_height'    => 'max-h-none',   // Tanpa batasan tinggi bawaan
          'object_fit'    => 'object-cover',
          'space_y'       => 'gap-3',
        ],
        ============================================================================================
        Saran Pilihan Konfigurasi untuk Editor Anda (Backend/UI)
        Di file Livewire / UI Builder Anda, Anda bisa menyediakan pilihan pengaturan (seperti yang Anda buat di Card Builder sebelumnya) dengan value sebagai berikut:

        1. Lebar Gambar (width):

        w-full (Lebar Penuh Layar - Cocok untuk Banner)

        max-w-4xl (Lebar Artikel Standar)

        max-w-md (Gambar Medium / Portrait)

        max-w-xs (Gambar Kecil / Logo)

        2. Perataan Posisi (align):
        (Hanya terlihat perbedaannya jika gambar bukan w-full)

        mr-auto (Rata Kiri)

        mx-auto (Rata Tengah)

        ml-auto (Rata Kanan)

        3. Radius Sudut (radius):

        rounded-none (Tajam/Siku)

        rounded-xl (Normal)

        rounded-3xl (Sangat Melengkung)
          ============================================================================================
          Rekomendasi Nilai untuk UI Builder Admin
              Untuk melengkapi fitur di atas, Anda bisa menambahkan opsi dropdown atau button group di antar muka penulis Anda dengan nilai berikut:

              1. Lebar Gambar (width)

              max-w-4xl : Lebar Artikel (Standar)

              w-full : Lebar Kontainer (Mentok di batas max-w-7xl)

              w-screen : Layar Penuh (Banner Baru)

              2. Batasan Tinggi (Height Limit) - Sangat Berguna untuk Banner

              max-h-none : Asli (Tidak Dibatasi)

              max-h-[50vh] : Setengah Layar

              max-h-[70vh] : 70% Layar

              max-h-96 : Kotak Pendek (sekitar 384px)

              3. Perilaku Gambar (object_fit)

              object-cover : Penuhi Kotak (Dipotong Otomatis) — Wajib digunakan jika Anda mengaktifkan Batasan Tinggi agar gambar tidak gepeng.

              object-contain : Muat Utuh — Akan ada ruang kosong jika rasio gambar dan layar berbeda.

              object-fill : Tarik/Gepengkan (Sangat tidak disarankan, namun kadang diminta klien).
      */
@endphp

@props (["block", "data", "lang", "allContent" => []])

@php
  $imageUrl = $data["url"] ?? "";
  $caption = trim($data["caption"][$lang] ?? "");

  // Pengaturan Layout & Spasi
  $marginBottom = $data["margin_bottom"] ?? "mb-6";
  $width = $data["width"] ?? "w-full";
  $align = $data["align"] ?? "mx-auto";

  $maxHeight = $data["max_height"] ?? "max-h-none"; // Contoh: max-h-[60vh]
  $objectFit = $data["object_fit"] ?? "object-cover"; // Contoh: object-contain
  $radius = $data["radius"] ?? "rounded-xl";
  $spaceY = $data["space-y"] ?? "gap-3";

  // Jika dibuat layar penuh (Banner), otomatis matikan efek melengkung agar menempel sempurna di tepi browser
  if ($width === "w-screen") {
    $radius = "rounded-none";
  }
@endphp

@if (!empty($imageUrl))
  <figure
    id="{{ $block['anchor'] ?? '' }}"
    {{-- Ubah w-screen menjadi w-full murni di tingkat CSS figure agar tidak memicu scroll horizontal --}}
    class="flex flex-col {{ $spaceY }} {{ $marginBottom }} {{ $width === 'w-screen' ? 'w-full' : $width }} {{ $align }} reveal animate-scroll-reveal"
  >
    <img
      src="{{ $imageUrl }}"
      alt="{{ $caption ?: 'Ilustrasi' }}"
      loading="lazy"
      decoding="async"
      {{-- 🌟 Suntikkan class max-height dan object-fit di sini --}}
      class="w-full {{ $maxHeight }} {{ $objectFit }} {{ $radius }} shadow-sm md:shadow-md"
    />

    @if (!empty($caption))
      <figcaption
        class="mx-auto max-w-3xl px-4 text-center text-sm leading-relaxed text-gray-500 italic"
      >
        {{ $caption }}
      </figcaption>
    @endif
  </figure>
@endif
