{{--
Grup Langkah (rilis 66, diperbarui rilis 73 dan 74): versi baru renderer milik proyek Anda. Perubahan dari berkas Anda: warna angka, bentuk latar angka dan warna garis
(kelas dari StepStyle); tata letak, orientasi, dan isi anak TIDAK diubah.
Rilis 73: di kanvas editor ($canvasMode) isi tiap langkah (kartu) membawa data-block-id / data-block-type, sehingga bisa difokus dari outline,
panel kanan, atau klik di kanvas (sama seperti blok di dalam Kolom). Penandanya hanya ada di kanvas: keluaran landing tidak berubah. Di kanvas animasi reveal dimatikan.
Rilis 74 (vertikal): penanda Angka atau Titik + label (label dua bahasa per langkah, data.labels), poros Samping atau Tengah (kartu selang-seling di md ke atas;
di HP selalu poros samping dengan label di atas kartu). Tanpa kunci baru tampilannya sama persis dengan rilis 73.
Kelas baru yang harus terbaca pemindai Tailwind: md:grid md:grid-cols-[1fr_auto_1fr] md:col-start-1 md:col-start-2 md:col-start-3 md:row-start-1 md:text-left md:text-right md:hidden hidden md:block w-28 mb-2 text-[10px] leading-tight text-center
Kelas yang harus terbaca pemindai Tailwind dan dibentuk di StepStyle: rounded-none rounded-full rounded-xl opacity-30 opacity-60 opacity-100 shadow-sm text-white bg-white
--}}
@props (["block", "data", "lang", "allContent"])

@php
  // 1. Konfigurasi Gaya dari Editor
  $orientation = $data["orientation"] ?? "vertical"; // vertical, horizontal-top, horizontal-bottom
  // Rilis 66: bentuk dan warna angka + garis dari palet (App\Content\Blocks\StepStyle). Data lama ("bg-foresty text-white", "bg-foresty/30") dibaca juga.
  $style = \App\Content\Blocks\StepStyle::resolve($data);
  $nodeClass = $style["badge"];
  $lineClass = $style["line"];
  $gapClass = $data["gap"] ?? "gap-8";

  // Ambil ID anak-anak (kartu/konten di dalam grup langkah ini)
  $childIds = $data["children"] ?? [];
  $totalItems = count($childIds);
  $inCanvas = (bool) ($canvasMode ?? false);

  // Rilis 74: titik + label dan poros tengah hanya untuk tata letak vertikal (horizontal menyusul)
  $isVertical = $orientation === "vertical";
  $dotMode = $isVertical && $style["marker"] === "dot";
  $center = $isVertical && $style["axis"] === "center";
  $startRight = $style["start"] === "right";
  $labels = [];
  foreach ($childIds as $lid) {
    $labels[$lid] = is_string($lid)
      ? \App\Content\Blocks\StepStyle::label($data, $lid, (string) $lang)
      : "";
  }
  $anyLabel = count(array_filter($labels, fn($t) => $t !== "")) > 0;
@endphp

<div id="{{ $block['anchor'] ?? '' }}" class="my-8 w-full">
  {{-- ======================================================== --}}
  {{-- TATA LETAK VERTIKAL (Seperti pada gambar referensi) --}}
  {{-- ======================================================== --}}
  @if ($orientation === "vertical")
    <div class="flex flex-col">
      @foreach ($childIds as $index => $childId)
        @if (isset($allContent[$childId]))
          @php
            $childBlock = $allContent[$childId];
            $component =
              "blocks.render." . str_replace("_", "-", $childBlock["type"] ?? "unknown");
            $delay = $index * 150; // Efek muncul satu per satu
            $childType = str_replace("_", "-", $childBlock["type"] ?? "unknown");
            $label = $labels[$childId] ?? "";
            $cardRight = ($index % 2 === 0) === $startRight; // poros tengah: kartu langkah ini di kanan?
            $nodeCls = trim(
              $nodeClass .
                " " .
                (mb_strlen($label) > 2 ? "text-[10px] leading-tight text-center" : ""),
            );
          @endphp

          <div
            class="{{ trim('flex ' . $gapClass . ' group ' . ($inCanvas ? '' : 'reveal animate-scroll-reveal') . ' ' . ($center ? 'md:grid md:grid-cols-[1fr_auto_1fr]' : '')) }}"
            @unless ($inCanvas) style="transition-delay: {{ $delay }}ms;" @endunless
          >
            {{-- Poros samping + titik: label di kolom kiri (md ke atas); di HP label ada di atas kartu --}}
            @if ($dotMode && !$center && $anyLabel)
              <div
                class="hidden md:block w-28 shrink-0 text-right {{ $style['label'] }}"
              >
                {{ $label }}
              </div>
            @endif

            <!-- Kolom Indikator (Angka/Titik & Garis) -->
            <div
              class="{{ trim('flex flex-col items-center ' . ($center ? 'md:col-start-2 md:row-start-1' : '')) }}"
            >
              @if ($dotMode)
                @if ($style["dot"] !== "")
                  <div class="relative z-10 shrink-0 {{ $style['dot'] }}"></div>
                @endif
              @else
                <!-- Lingkaran Angka (atau label manual) -->
                <div
                  class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center font-bold transition-transform duration-300 group-hover:scale-110 {{ $nodeCls }}"
                >
                  {{
                    $label !== ""
                      ? $label
                      : $index + 1
                  }}
                </div>
              @endif

              <!-- Potongan Garis Vertikal (Tidak dirender di elemen terakhir) -->
              @if (!$loop->last)
                <div
                  class="w-0.5 flex-1 my-2 rounded-full {{ $lineClass }}"
                ></div>
              @endif
            </div>

            <!-- Kolom Konten -->
            <div
              class="{{ trim('flex-1 pb-10 ' . ($center ? ($cardRight ? 'md:col-start-3 md:row-start-1' : 'md:col-start-1 md:row-start-1') : '')) }}"
            >
              @if ($dotMode && $label !== "")
                <div class="md:hidden mb-2 {{ $style['label'] }}">
                  {{ $label }}
                </div>
              @endif
              @if ($inCanvas)
                <div
                  data-block-id="{{ $childId }}"
                  data-block-type="{{ $childType }}"
                >
              @endif
              <x-dynamic-component
                :component="$component"
                :data="$childBlock['data'] ?? []"
                :lang="$lang"
                :all-content="$allContent"
              />
              @if ($inCanvas)
            </div>

        @endif
        </div>

        {{-- Poros tengah + titik: label di sisi seberang kartu (md ke atas) --}}
        @if ($dotMode && $center)
          <div
            class="{{ 'hidden md:block md:row-start-1 ' . ($cardRight ? 'md:col-start-1 md:text-right' : 'md:col-start-3 md:text-left') . ' ' . $style['label'] }}"
          >
            {{ $label }}
          </div>
        @endif

        </div>

  @endif
  @endforeach
</div>

{{-- ======================================================== --}}
{{-- TATA LETAK HORIZONTAL (Angka di Atas atau di Bawah) --}}
{{-- ======================================================== --}}
@else
@php
  // Hitung grid untuk desktop (misal: md:grid-cols-3)
  $gridCols = match ($totalItems) {
    1 => "md:grid-cols-1",
    2 => "md:grid-cols-2",
    3 => "md:grid-cols-3",
    4 => "md:grid-cols-4",
    default => "md:grid-cols-3 lg:grid-cols-4",
  };
@endphp

<div class="grid grid-cols-1 {{ $gridCols }} gap-6 md:gap-8">
  @foreach ($childIds as $index => $childId)
    @if (isset($allContent[$childId]))
      @php
        $childBlock = $allContent[$childId];
        $component =
          "blocks.render." . str_replace("_", "-", $childBlock["type"] ?? "unknown");
        $delay = $index * 150;
        $childType = str_replace("_", "-", $childBlock["type"] ?? "unknown");
      @endphp

      {{-- Ubah urutan flex tergantung posisi angka (Atas / Bawah) --}}
      <div
        class="relative flex flex-col gap-4 {{ $inCanvas ? '' : 'reveal animate-scroll-reveal' }} {{ $orientation === 'horizontal-bottom' ? 'justify-end' : '' }}"
        @unless ($inCanvas) style="transition-delay: {{ $delay }}ms;" @endunless
      >
        <!-- Baris Indikator (Angka & Garis) -->
        <div
          class="relative flex items-center {{ $orientation === 'horizontal-bottom' ? 'order-last mt-4' : 'mb-2' }}"
        >
          <!-- Lingkaran Angka -->
          <div
            class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center font-bold transition-transform duration-300 hover:scale-110 {{ trim($nodeClass . ' ' . (mb_strlen($labels[$childId] ?? '') > 2 ? 'text-[10px] leading-tight text-center' : '')) }}"
          >
            {{
              ($labels[$childId] ?? "") !== ""
                ? $labels[$childId]
                : $index + 1
            }}
          </div>

          <!-- Potongan Garis Horizontal -->
          <!-- Menggunakan trik absolute yang menembus celah (gap) grid desktop -->
          @if (!$loop->last)
            <!-- Garis ini hanya terlihat lurus ke samping di desktop, disembunyikan di HP -->
            <div
              class="hidden md:block absolute top-1/2 left-12 h-0.5 -translate-y-1/2 rounded-full {{ $lineClass }}"
              style="width: calc(100% + 2rem - 3rem)"
            >
              {{-- Lebar: 100% kolom + ukuran gap - ukuran ikon awal --}}
            </div>

            <!-- Garis Vertikal untuk tampilan HP (Fallback) -->
            <div
              class="md:hidden absolute top-10 left-5 h-full w-0.5 -translate-x-1/2 rounded-full {{ $lineClass }}"
            ></div>
          @endif
        </div>

        <!-- Area Konten -->
        <div class="flex-1">
          @if ($inCanvas)
            <div
              data-block-id="{{ $childId }}"
              data-block-type="{{ $childType }}"
            >
          @endif
          <x-dynamic-component
            :component="$component"
            :data="$childBlock['data'] ?? []"
            :lang="$lang"
            :all-content="$allContent"
          />
          @if ($inCanvas)
        </div>

    @endif
    </div>

    </div>
    @endif
  @endforeach
</div>
@endif

</div>
