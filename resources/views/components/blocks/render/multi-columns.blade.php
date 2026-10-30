{{--
Blok Kolom (renderer proyek, rilis 35). Letakkan di resources/views/components/blocks/render/multi-columns.blade.php (CMS dan landing).

Tinggi baris grid = kolom TERTINGGI. Kolom lain diregangkan (self-stretch) sebesar itu, lalu isinya disejajarkan pada sumbu Y
terhadap tinggi tersebut: Atas / Tengah / Bawah / Bagi rata (data.align_y -> justify-start|center|end|between).
Gambar bertinggi "Penuh" mengisi sisa tinggi kolom, dan blok anak (Tombol, Video, Galeri, ...) memakai Jarak Bawah-nya sendiri
(lihat LayoutStyle::columnChildClass).
Lebar kolom (rilis 100): LayoutStyle::columnTemplate() membaca data.col_ratio (equal | pola seperti 1:2 | custom) dan data.col_N_zone_width. Mulai layar md;
di bawahnya kolom menumpuk.
Kanvas editor ($canvasMode): tiap kolom berbingkai putus-putus, kolom kosong menampilkan kotak penanda, dan tiap blok anak bisa dipilih.

Kelas yang dicetak lewat variabel ada UTUH di app/Content/Blocks/LayoutStyle.php (terbaca Tailwind). Daftar sah rata Y:
justify-start justify-center justify-end justify-between
--}}
@props (["block", "data", "lang", "allContent"])

@php
  $inCanvas = (bool) ($canvasMode ?? false);
  $colCount = \App\Content\Blocks\LayoutStyle::columnCount(
    $data["col_count"] ?? null,
  );

  // 1. Susunan grid (grid-template-columns) dari lebar kolom (rilis 100): pola siap pakai (data.col_ratio) atau angka per kolom
  //    (Kustom, atau data lama). Hasilnya mis. "minmax(0,1fr) minmax(0,2fr)"; logika dan daftar sahnya ada di LayoutStyle.
  $gridTemplateStr = \App\Content\Blocks\LayoutStyle::columnTemplate($data);

  $marginChoices = array_column(config("cms.design.margin_bottom", []), "value"); // Jarak Bawah blok anak (Tombol, Video, dst.), lihat LayoutStyle::columnChildClass

  $isReverseMobile = $data["mobile_reverse"] ?? false;
  $gapClass = $data["gap"] ?? "gap-2"; // jarak antar blok di dalam satu kolom
  $alignYClass = \App\Content\Blocks\LayoutStyle::alignY(
    $data["align_y"] ?? null,
  ); // justify-start|center|end|between
  $alignXClass = $data["align_x"] ?? "items-start"; // items-start|center|end
@endphp

{{-- 2. Variabel CSS --md-grid-cols + kelas Tailwind MD. md:items-stretch = tiap kolom setinggi kolom tertinggi --}}
<div
  id="{{ $block['anchor'] ?? '' }}"
  class="reveal animate-scroll-reveal mb-8 grid w-full grid-cols-1 gap-6 md:[grid-template-columns:var(--md-grid-cols)] md:items-stretch"
  style="--md-grid-cols: {{ $gridTemplateStr }};"
>
  @for ($i = 1; $i <= $colCount; $i++)
    @php
      $zoneKey = "col_{$i}_zone";
      $childIds = is_array($data[$zoneKey] ?? null) ? $data[$zoneKey] : [];
      $childIds = array_values(
        array_filter($childIds, fn($id) => is_string($id) && isset($allContent[$id])),
      );

      $orderClass = "";
      if ($isReverseMobile && $colCount === 2) {
        $orderClass = $i === 1 ? "order-2 md:order-1" : "order-1 md:order-2";
      }
    @endphp

    {{-- Pembungkus kolom: setinggi baris (self-stretch) + rata vertikal/horizontal isi --}}
    <div
      class="flex min-w-0 flex-col self-stretch {{ $orderClass }} {{ $gapClass }} {{ $alignYClass }} {{ $alignXClass }} {{ $inCanvas ? 'outline-1 -outline-offset-1 outline-dashed outline-gray-300' : '' }}"
    >
      @foreach ($childIds as $childId)
        @php
          $childBlock = $allContent[$childId];
          $childType = str_replace("_", "-", $childBlock["type"] ?? "unknown");
          $component = "blocks.render." . $childType;
        @endphp

        @if (view()->exists("components." . $component))
          {{-- Render blok anak. Di kanvas membawa penanda agar bisa dipilih langsung --}}
          <div
            class="{{ \App\Content\Blocks\LayoutStyle::columnChildClass($childBlock, $marginChoices) }}"
            @if ($inCanvas) data-block-id="{{ $childId }}" data-block-type="{{ $childType }}" @endif
          >
            <x-dynamic-component
              :component="$component"
              :block="$childBlock"
              :data="$childBlock['data'] ?? []"
              :lang="$lang"
              :all-content="$allContent"
            />
          </div>
        @elseif ($inCanvas)
          <div
            class="w-full rounded-lg border border-dashed border-gray-300 bg-white/60 p-3 text-center text-xs text-gray-500"
            data-block-id="{{ $childId }}"
            data-block-type="{{ $childType }}"
          >
            Blok <b>{{ $childType }}</b> belum punya tampilan publik.
          </div>
        @endif
      @endforeach

      @if ($inCanvas && count($childIds) === 0)
        {{-- Kolom kosong di kanvas: kotak penanda supaya blok Kolom tampak dan bisa dipilih --}}
        <div
          class="flex min-h-28 w-full flex-1 items-center justify-center rounded-lg bg-white/60 px-3 py-4 text-center text-xs text-gray-400"
        >
          Kolom {{ $i }} masih kosong.<br />Tambah blok dari panel kiri.
        </div>
      @endif
    </div>
  @endfor
</div>
