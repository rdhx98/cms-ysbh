{{--
  <x-editor.swatches> — palet warna dari config. mode: bg | text | preview | hex  (lihat versi sebelumnya).
  path (lengkap) ATAU rel (relatif ke node yang difokus).
--}}
@props([
  'path' => null,
  'rel' => null,
  'relExpr' => null,
  'options' => [],
  'label' => null,
  'default' => null,
  'live' => null,
  'mode' => 'bg',
])

@php
  $isLive = $live ?? ! config('cms.editor.defer_style_sync', false);
  $items = \App\Editor\Options::normalize($options);

  // Rilis 61: opsi dari palet membawa 'group' (Dasar / Yayasan / Program / Kustom). Tanpa 'group' (mis. config lama) = satu kelompok tanpa judul.
  $groups = [];
  foreach ($items as $it) {
    $groups[(string) ($it['group'] ?? '')][] = $it;
  }
  // [nilai, nama] untuk keterangan satu baris di bawah semua swatch (menggantikan label melayang per swatch yang tertimpa baris berikutnya)
  // Rilis 61: 'aliases' = nama lama slot (bg-foresty untuk bg-forest): konten lama tampil terpilih pada swatch slotnya
  $nameList = [];
  foreach ($items as $o) {
    foreach (array_merge([$o['value']], (array) ($o['aliases'] ?? [])) as $v) {
      $nameList[] = [$v, (string) ($o['name'] ?? ($o['label'] ?? $o['value']))];
    }
  }
@endphp

<div x-data="wireField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, @js($default), @js($isLive))" {{ $attributes->class('flex flex-col gap-1.5') }}>
  @if ($label)
    <label class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</label>
  @endif

  <div class="flex flex-col gap-3" x-data="{ hover: null, list: @js($nameList) }" data-swatches>
    @foreach ($groups as $groupName => $groupItems)
      <div class="flex flex-col gap-1.5" @if ($groupName !== '') data-swatch-group="{{ $groupName }}" @endif>
        @if ($groupName !== '' && count($groups) > 1)
          <span class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase">{{ $groupName }}</span>
        @endif

        {{-- Program: warna utama di atas, warna muda tepat di bawahnya (satu kolom per program). Yayasan: 5 kolom (10 slot = 2 baris rata). Kustom: 4 kolom. Selain itu: baris yang membungkus. --}}
        <div class="{{ match ($groupName) {
            'Program' => 'grid grid-flow-col grid-rows-2 justify-start gap-2',
            'Yayasan' => 'grid grid-cols-[repeat(5,1.75rem)] justify-start gap-2',
            'Kustom' => 'grid grid-cols-[repeat(4,1.75rem)] justify-start gap-2',
            default => 'flex flex-wrap gap-2',
          } }}">
          @foreach ($groupItems as $opt)
          @php
            $value = $opt['value'];
            $name = $opt['name'] ?? ($opt['label'] ?? $value);
            $selected = collect(array_merge([$value], (array) ($opt['aliases'] ?? [])))->map(fn ($v) => 'isCi(' . \Illuminate\Support\Js::from($v)->toHtml() . ')')->implode(' || ');
            $face = match ($mode) {
              'text' => trim($value . ' ' . ($opt['preview'] ?? '')),
              'preview' => $opt['preview'] ?? $value,
              'hex' => '',
              default => $value,
            };
            // Rilis 59: opsi dari palet membawa hex warnanya. Admin TIDAK mencetak palet (sidebar tetap warna tetap), jadi kelas bg-forest di sini
            // akan berwarna bawaan dan slot kustom tak berwarna; wajah swatch dilukis langsung dengan hex dan kelasnya dibuang.
            $swatchHex = \App\Content\Palette::hex($opt['hex'] ?? null);
            $faceStyle = '';
            if ($swatchHex !== null) {
              $faceStyle = match ($mode) {
                'text' => 'color:' . $swatchHex . ';background-color:' . (\App\Content\Palette::hex($opt['preview_hex'] ?? null) ?? '#FFFFFF'),
                default => 'background-color:' . $swatchHex,
              };
              $face = '';
            } elseif ($mode === 'hex') {
              $faceStyle = 'background-color:' . $value;
            }
          @endphp
            <button
              type="button"
              title="{{ $name }}"
              aria-label="{{ $name }}"
              x-on:mouseenter="hover = @js($name)"
              x-on:mouseleave="hover = null"
              x-on:focus="hover = @js($name)"
              x-on:blur="hover = null"
              x-on:click="set(@js($value))"
              x-bind:aria-pressed="{{ $selected }}"
              x-bind:class="{{ $selected }}
                ? 'ring-2 ring-forest ring-offset-2 scale-110'
                : 'ring-1 hover:ring-offset-1 ring-gray-200/50'"
              @if ($faceStyle !== '') style="{{ $faceStyle }}" @endif
              class="hover:ring-forest relative flex h-7 w-7 items-center justify-center overflow-hidden rounded-md border border-gray-200 text-sm font-semibold shadow-sm transition-all duration-300 hover:scale-110 hover:shadow-md hover:ring-2 focus:outline-none {{ $face }}"
            >
              @if ($mode === 'text')
                Aa
              @endif

              @if (! empty($opt['is_transparent']))
                <span class="absolute top-1/2 left-0 h-[1.5px] w-full -translate-y-1/2 -rotate-45 bg-red-500 opacity-60"></span>
              @endif
            </button>

          @endforeach
        </div>
      </div>
    @endforeach

    {{-- keterangan tetap: nama warna yang disorot (hover/fokus) atau yang terpilih; tinggi tetap supaya tata letak tidak bergeser --}}
    <p class="text-xxs min-h-4 font-bold text-gray-700 uppercase" aria-live="polite" x-text="hover ?? (list.find(([v]) => isCi(v)) || [null, ''])[1]"></p>
  </div>
</div>
