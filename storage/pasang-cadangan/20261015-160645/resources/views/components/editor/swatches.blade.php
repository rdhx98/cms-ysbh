{{--
  <x-editor.swatches> — pemilih warna dari palet. mode: bg | text | preview | hex.
  path (lengkap) ATAU rel (relatif ke node yang difokus).

  Rilis 64: bila opsinya banyak (> $inlineMax, bawaan 6) tampil SATU tombol: kotak warna terpilih berujung rounded + nama warna di sebelah
  kanannya. Klik membuka POPOVER `fixed` di bawah (atau di atas, bila ruang bawah sempit) tombol berisi kisi swatch bergrup; popover
  menutup saat warna dipilih, Esc, klik di luar, roda/gulir di luar, atau ubah ukuran. Dengan 28+ warna per pemilih, panel kanan tidak lagi
  memuat 2 x 28 swatch. Opsi sedikit (<= 6) tetap sebaris seperti sebelumnya. Kisi dan tombolnya SAMA di kedua mode (hanya dibungkus).
--}}
@props ([
  "path" => null,
  "rel" => null,
  "relExpr" => null,
  "options" => [],
  "label" => null,
  "default" => null,
  "live" => null,
  "mode" => "bg",
  "inlineMax" => 6,
])

@php
  $isLive = $live ?? !config("cms.editor.defer_style_sync", false);
  $items = \App\Editor\Options::normalize($options);
  $popover = count($items) > (int) $inlineMax;

  // Rilis 61: opsi dari palet membawa 'group' (Dasar / Yayasan / Program / Kustom). Tanpa 'group' (mis. config lama) = satu kelompok tanpa judul.
  // Rilis 64: wajah tiap swatch dihitung SEKALI di sini, dipakai kisi dan tombol.
  $groups = [];
  $nameList = [];
  foreach ($items as $opt) {
    $value = $opt["value"];
    $name = $opt["name"] ?? ($opt["label"] ?? $value);
    $selected = collect(array_merge([$value], (array) ($opt["aliases"] ?? [])))
      ->map(fn($v) => "isCi(" . \Illuminate\Support\Js::from($v)->toHtml() . ")")
      ->implode(" || ");
    $face = match ($mode) {
      "text" => trim($value . " " . ($opt["preview"] ?? "")),
      "preview" => $opt["preview"] ?? $value,
      "hex" => "",
      default => $value,
    };
    // Rilis 59: opsi dari palet membawa hex warnanya. Admin TIDAK mencetak palet (sidebar tetap warna tetap), jadi kelas bg-forest di sini
    // akan berwarna bawaan dan slot kustom tak berwarna; wajah swatch dilukis langsung dengan hex dan kelasnya dibuang.
    $swatchHex = \App\Content\Palette::hex($opt["hex"] ?? null);
    $faceStyle = "";
    if ($swatchHex !== null) {
      $faceStyle = match ($mode) {
        "text" => "color:" .
          $swatchHex .
          ";background-color:" .
          (\App\Content\Palette::hex($opt["preview_hex"] ?? null) ?? "#FFFFFF"),
        default => "background-color:" . $swatchHex,
      };
      $face = "";
    } elseif ($mode === "hex") {
      $faceStyle = "background-color:" . $value;
    }
    $groups[(string) ($opt["group"] ?? "")][] = compact(
      "opt",
      "value",
      "name",
      "selected",
      "face",
      "faceStyle",
    );
    // Rilis 61: 'aliases' = nama lama slot (bg-foresty untuk bg-forest): konten lama tampil terpilih pada swatch slotnya
    foreach (array_merge([$value], (array) ($opt["aliases"] ?? [])) as $v) {
      $nameList[] = [$v, (string) $name];
    }
  }
  $faceBase =
    "relative flex items-center justify-center overflow-hidden rounded-md border border-gray-200 text-sm font-semibold shadow-sm";
@endphp

<div
  x-data="wireField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, @js($default), @js($isLive))"
  {{
    $attributes->class(
      "flex flex-col gap-1.5",
    )
  }}
>
  @if ($label)
    <label
      class="text-xxs font-bold text-gray-700 uppercase"
      >{{ $label }}</label
    >
  @endif

  <div
    class="flex flex-col gap-3"
    x-data="{
      hover: null,
      list: @js($nameList),
      open: false,
      pos: { left: 0, width: 232, top: null, bottom: null, max: 360 },
      toggle() {
        if (this.open) {
          this.open = false
          return
        }
        const r = this.$refs.trigger.getBoundingClientRect()
        const w = 232
        const below = window.innerHeight - r.bottom - 12
        const above = r.top - 12
        const up = below < 240 && above > below
        this.pos = {
          left: Math.max(8, Math.min(Math.round(r.left), window.innerWidth - w - 8)),
          width: w,
          top: up ? null : Math.round(r.bottom + 4),
          bottom: up ? Math.round(window.innerHeight - r.top + 4) : null,
          max: Math.max(160, Math.min(380, up ? above : below)),
        }
        this.open = true
      },
      get style() {
        const p = this.pos
        return 'left:' + p.left + 'px;width:' + p.width + 'px;' + (p.top !== null ? 'top:' + p.top + 'px;' : 'bottom:' + p.bottom + 'px;') + 'max-height:' + p.max + 'px'
      },
      get current() {
        return (this.list.find(([v]) => isCi(v)) || [null, ''])[1]
      },
    }"
    @if ($popover)
      x-on:keydown.escape.window="open = false"
      x-on:resize.window="open = false"
      x-on:scroll.window.capture.passive="
        if (open && $event.target !== $refs.pop) open = false;
      "
    @endif
    data-swatches
    @if ($popover) data-swatches-popover @endif
  >
    @if ($popover)
      <button
        type="button"
        x-ref="trigger"
        x-on:click="toggle()"
        x-bind:aria-expanded="open"
        aria-haspopup="true"
        data-swatch-trigger
        class="hover:border-forest focus-visible:ring-forest flex w-full items-center gap-2 rounded-lg border border-gray-200 bg-white p-1 pr-2 text-left transition-colors focus:outline-none focus-visible:ring-2"
      >
        <span
          class="{{ $faceBase }} h-7 w-7 shrink-0 bg-[repeating-conic-gradient(#e5e7eb_0_25%,#fff_0_50%)] bg-[length:8px_8px]"
          aria-hidden="true"
        >
          @foreach ($groups as $cells)
            @foreach ($cells as $c)
              <span
                x-show="{{ $c['selected'] }}"
                x-cloak
                @if ($c["faceStyle"] !== "") style="{{ $c['faceStyle'] }}" @endif
                class="absolute inset-0 flex items-center justify-center {{ $c['face'] }}"
              >
                @if ($mode === "text") Aa @endif
                @if (!empty($c["opt"]["is_transparent"]))
                  <span
                    class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"
                  ></span>
                @endif
              </span>
            @endforeach
          @endforeach
        </span>
        <span
          class="text-xxs min-w-0 flex-1 truncate font-bold text-gray-700 uppercase"
          x-text="current || 'Pilih warna'"
        ></span>
        <svg class="h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform" x-bind:class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
      </button>

      <div
        x-show="open"
        x-cloak
        x-on:click="open = false"
        x-on:wheel.passive="open = false"
        class="fixed inset-0 z-40"
      ></div>
    @endif

    <div
      @if ($popover)
        x-ref="pop"
        x-show="open"
        x-cloak
        x-bind:style="style"
        role="dialog"
        aria-label="{{ $label ?? 'Pilih warna' }}"
        data-swatch-popover
      @endif
      class="flex flex-col gap-3 {{ $popover ? 'fixed z-50 overflow-y-auto overscroll-contain rounded-xl border border-gray-200 bg-white p-3 shadow-xl' : '' }}"
    >
      @foreach ($groups as $groupName => $cells)
        <div
          class="flex flex-col gap-1.5"
          @if ($groupName !== "") data-swatch-group="{{ $groupName }}" @endif
        >
          @if ($groupName !== "" && count($groups) > 1)
            <span
              class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase"
              >{{ $groupName }}</span
            >
          @endif

          {{-- Program: warna utama di atas, warna muda tepat di bawahnya (satu kolom per program). Yayasan: 5 kolom (10 slot = 2 baris rata). Kustom: 4 kolom. Selain itu: baris yang membungkus. --}}
          <div
            class="{{ match ($groupName) {
              'Program' => 'grid grid-flow-col grid-rows-2 justify-start gap-2',
              'Yayasan' => 'grid grid-cols-[repeat(5,1.75rem)] justify-start gap-2',
              'Kustom' => 'grid grid-cols-[repeat(4,1.75rem)] justify-start gap-2',
              default => 'flex flex-wrap gap-2',
            } }}"
          >
            @foreach ($cells as $c)
              <button
                type="button"
                title="{{ $c['name'] }}"
                aria-label="{{ $c['name'] }}"
                x-on:mouseenter="hover = @js($c['name'])"
                x-on:mouseleave="hover = null"
                x-on:focus="hover = @js($c['name'])"
                x-on:blur="hover = null"
                x-on:click="set(@js($c['value'])){{ $popover ? '; open = false' : '' }}"
                x-bind:aria-pressed="{{ $c['selected'] }}"
                x-bind:class="{{ $c['selected'] }}
                  ? 'ring-2 ring-forest ring-offset-2 scale-110'
                  : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
                @if ($c["faceStyle"] !== "") style="{{ $c['faceStyle'] }}" @endif
                class="hover:ring-forest {{ $faceBase }} h-7 w-7 transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none {{ $c['face'] }}"
              >
                @if ($mode === "text")
                  Aa
                @endif

                @if (!empty($c["opt"]["is_transparent"]))
                  <span
                    class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"
                  ></span>
                @endif
              </button>
            @endforeach
          </div>
        </div>
      @endforeach

      {{-- keterangan tetap: nama warna yang disorot (hover/fokus) atau yang terpilih; tinggi tetap supaya tata letak tidak bergeser --}}
      <p class="text-xxs min-h-4 font-bold text-gray-700 uppercase" aria-live="polite" x-text="
          hover ?? current
        "></p>
    </div>
  </div>
</div>
