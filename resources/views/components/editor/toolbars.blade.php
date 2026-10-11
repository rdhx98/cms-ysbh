{{--
  Toolbar Tiptap — satu kesatuan, dua baris, tanpa tab (rilis 33). File milik PROYEK: taruh di resources/views/components/editor/toolbars.blade.php.

    Baris 1  B I U │ rata kiri/tengah/kanan │ daftar bullet/angka │ tautan
    Baris 2  jenis huruf │ ukuran │ warna │ ⋯ (lainnya: ketebalan, coret, rata kiri-kanan, jorok, daftar tugas, kutipan, kode)

  Dipakai dua pemilik state, keduanya memberi metode yang sama:
    - richField (panel Properti, resources/js/rich-field.js): ada `single` (Judul = satu baris), `alignable`, `setBlockAlign`, `blockAlignIs`
    - Alpine.data('tiptap') (editor lama): tidak punya `single`/`alignable`; nama baru dibaca lewat $data supaya tidak error di sana.
  Judul (`single`): daftar, kutipan, kode, jorok, rata kiri-kanan disembunyikan (Judul disimpan satu baris TANPA <p>). Rata teks Judul
  ditulis ke data.align milik blok (bukan ke HTML), jadi aman untuk penyimpanan. Tombol berdiri sendiri (tidak lagi memakai x-buttons.toolbar).

  Warna: putih + abu netral; aksen satu-satunya hijau forest (text-foresty / bg-foresty/10).
--}}
@php
  $btn =
    "inline-flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center rounded-md text-gray-500 outline-none transition-colors hover:bg-gray-100 hover:text-gray-900 focus-visible:ring-1 focus-visible:ring-foresty";
  $on = "bg-foresty/10 text-foresty";
  $off = "text-gray-500";
  $select =
    "h-6 min-w-0 cursor-pointer rounded-md border border-gray-200 bg-white py-0 pr-5 pl-1.5 text-[11px] leading-none shadow-none outline-none focus:border-foresty focus:ring-1 focus:ring-foresty";
  $chip =
    "inline-flex h-6 cursor-pointer items-center gap-1 rounded-md border border-gray-200 bg-white px-1.5 text-[11px] font-medium text-gray-600 outline-none transition-colors hover:border-foresty/40 hover:text-foresty focus-visible:ring-1 focus-visible:ring-foresty";
  $label =
    "mb-0.5 block text-[10px] font-bold tracking-wide text-gray-500 uppercase";

  // [perintah, nama aktif, judul, ikon]
  $marks = [
    ["toggleBold", "bold", "Tebal (Ctrl+B)", "bold"],
    ["toggleItalic", "italic", "Miring (Ctrl+I)", "italic"],
    ["toggleUnderline", "underline", "Garis bawah (Ctrl+U)", "underline"],
  ];
  // [nilai, judul, ikon]
  $aligns = [
    ["left", "Rata kiri", "align-left"],
    ["center", "Rata tengah", "align-center"],
    ["right", "Rata kanan", "align-right"],
  ];
  $lists = [
    ["toggleBulletList", "bulletList", "Daftar bullet", "list"],
    ["toggleOrderedList", "orderedList", "Daftar angka", "list-ordered"],
  ];
  // hanya di menu ⋯ (tidak untuk Judul)
  $more = [
    ["toggleTaskList", "taskList", "Daftar tugas", "list-todo"],
    ["toggleBlockquote", "blockquote", "Kutipan", "quote"],
    ["toggleCodeBlock", "codeBlock", "Blok kode", "code-xml"],
  ];
  $swatches = [
    ["#000000", "Hitam"],
    ["#FFFFFF", "Putih"],
    ["#064F3B", "Forest"],
    ["#EBCC26", "Emas"],
    ["#E42326", "Koral"],
  ];
  $weights = [
    "300" => "Light",
    "400" => "Reguler",
    "500" => "Medium",
    "600" => "Semi-bold",
    "700" => "Bold",
    "900" => "Black",
  ];
  $fonts = [
    "Plus Jakarta Sans" => "'Plus Jakarta Sans', sans-serif",
    "Arial" => "Arial, sans-serif",
    "Fraunces" => "Fraunces, serif",
    "JetBrains Mono" => "'JetBrains Mono', monospace",
    "Open Sans" => "'Open Sans', sans-serif",
    "Roboto" => "Roboto, sans-serif",
    "Times New Roman" => "'Times New Roman', serif",
  ];
  $sizes = [
    "clamp(0.875rem, 1vw, 1rem)" => "Kecil",
    "clamp(1rem, 1.5vw, 1.125rem)" => "Normal",
    "clamp(1.125rem, 2vw, 1.375rem)" => "Besar (lead)",
    "clamp(1.5rem, 2.5vw, 2rem)" => "Sub-judul H3",
    "clamp(1.8rem, 3vw, 2.5rem)" => "Judul H2",
    "clamp(2.5rem, 5vw, 4rem)" => "Raksasa H1",
  ];
@endphp

<div
  x-data="{ tbOpen: null }"
  x-on:click.outside="tbOpen = null"
  x-on:keydown.escape.window="tbOpen = null"
  class="relative flex w-full flex-col gap-0.5 rounded-t-[7px] border-b border-gray-200 bg-white p-1"
  data-toolbar
>
  {{-- ============ BARIS 1: gaya, rata teks, daftar, tautan ============ --}}
  <div class="flex flex-wrap items-center gap-px" data-toolbar-row="1">
    @foreach ($marks as [$cmd, $name, $title, $icon])
      <button
        type="button"
        title="{{ $title }}"
        aria-label="{{ $title }}"
        x-on:mousedown.prevent
        x-on:click="runCommand('{{ $cmd }}')"
        x-bind:class="checkButtonActive('{{ $name }}', {}, 'default') ? '{{ $on }}' : '{{ $off }}'"
        class="{{ $btn }}"
      >
        <x-dynamic-component
          :component="'lucide-' . $icon"
          class="h-4 w-4"
          stroke-width="2.25"
        />
      </button>
    @endforeach

    <span class="mx-0.5 h-4 w-px shrink-0 bg-gray-200"></span>

    {{-- Rata teks: Paragraf = per paragraf (Tiptap); Judul = data.align milik blok (bila `alignable`) --}}
    <div
      x-show="!$data.single || $data.alignable"
      class="flex items-center gap-px"
      data-toolbar-align
    >
      @foreach ($aligns as [$val, $title, $icon])
        <button
          type="button"
          title="{{ $title }}"
          aria-label="{{ $title }}"
          x-on:mousedown.prevent
          x-on:click="$data.single ? $data.setBlockAlign('{{ $val }}') : runCommand('setTextAlign', { textAlign: '{{ $val }}' })"
          x-bind:class="($data.single ? $data.blockAlignIs('{{ $val }}') : checkButtonActive('{{ $val }}', { textAlign: '{{ $val }}' }, 'textAlign')) ? '{{ $on }}' : '{{ $off }}'"
          class="{{ $btn }}"
        >
          <x-dynamic-component
            :component="'lucide-' . $icon"
            class="h-4 w-4"
            stroke-width="2.25"
          />
        </button>
      @endforeach
      <span class="mx-0.5 h-4 w-px shrink-0 bg-gray-200"></span>
    </div>

    <div
      x-show="!$data.single"
      class="flex items-center gap-px"
      data-toolbar-lists
    >
      @foreach ($lists as [$cmd, $name, $title, $icon])
        <button
          type="button"
          title="{{ $title }}"
          aria-label="{{ $title }}"
          x-on:mousedown.prevent
          x-on:click="runCommand('{{ $cmd }}')"
          x-bind:class="checkButtonActive('{{ $name }}', {}, 'default') ? '{{ $on }}' : '{{ $off }}'"
          class="{{ $btn }}"
        >
          <x-dynamic-component
            :component="'lucide-' . $icon"
            class="h-4 w-4"
            stroke-width="2.25"
          />
        </button>
      @endforeach
      <span class="mx-0.5 h-4 w-px shrink-0 bg-gray-200"></span>
    </div>

    <button
      type="button"
      title="Tautan"
      aria-label="Tautan"
      x-on:mousedown.prevent
      x-on:click="openInternalLinkModal()"
      x-bind:class="checkButtonActive('link', {}, 'default') ? '{{ $on }}' : '{{ $off }}'"
      class="{{ $btn }}"
      data-toolbar-link
    >
      <x-dynamic-component
        :component="'lucide-link'"
        class="h-4 w-4"
        stroke-width="2.25"
      />
    </button>
  </div>

  {{-- ============ BARIS 2: jenis huruf, ukuran, warna, lainnya ============ --}}
  <div class="flex items-center gap-0.5" data-toolbar-row="2">
    <select
      title="Jenis huruf"
      aria-label="Jenis huruf"
      x-on:change="changeFontFamily($event.target.value)"
      x-bind:value="getCurrentFont()"
      x-bind:class="
        getCurrentFont() === 'default'
          ? 'text-gray-500'
          : 'text-gray-900 font-semibold'
      "
      class="{{ $select }} min-w-0 flex-1"
      data-toolbar-font
    >
      <option
        value="default"
        x-text="baseFontFamily.replace('Plus ', '')"
      ></option>
      @foreach ($fonts as $name => $stack)
        <option value="{{ $name }}" style="font-family: {{ $stack }};">
          {{
            str_replace(
              "Plus ",
              "",
              $name,
            )
          }}
        </option>
      @endforeach
    </select>

    <select
      title="Ukuran huruf"
      aria-label="Ukuran huruf"
      x-on:change="setFontSize($event.target.value)"
      x-bind:value="getCurrentFontSize()"
      x-bind:class="
        getCurrentFontSize() === 'default'
          ? 'text-gray-500'
          : 'text-gray-900 font-semibold'
      "
      class="{{ $select }} w-[96px] shrink-0"
      data-toolbar-size
    >
      <option value="default" x-text="labelUkuran"></option>
      @foreach ($sizes as $val => $text)
        <option value="{{ $val }}">{{ $text }}</option>
      @endforeach
    </select>

    {{-- Warna --}}
    <button
      type="button"
      title="Warna teks"
      aria-label="Warna teks"
      x-on:mousedown.prevent
      x-on:click="tbOpen = tbOpen === 'color' ? null : 'color'"
      x-bind:aria-expanded="tbOpen === 'color'"
      x-bind:class="tbOpen === 'color' ? '{{ $on }}' : '{{ $off }}'"
      class="{{ $btn }} relative"
      data-toolbar-color
    >
      <x-dynamic-component
        :component="'lucide-palette'"
        class="h-4 w-4"
        stroke-width="2.25"
      />
      <span
        class="absolute inset-x-1.5 bottom-[3px] h-[3px] rounded-full border border-black/10"
        x-bind:style="{ backgroundColor: getCurrentColor() }"
      ></span>
    </button>

    {{-- Lainnya --}}
    <button
      type="button"
      title="Lainnya"
      aria-label="Lainnya"
      x-on:mousedown.prevent
      x-on:click="tbOpen = tbOpen === 'more' ? null : 'more'"
      x-bind:aria-expanded="tbOpen === 'more'"
      x-bind:class="tbOpen === 'more' ? '{{ $on }}' : '{{ $off }}'"
      class="{{ $btn }}"
      data-toolbar-more
    >
      {{-- titik tiga digambar sendiri (nama ikon ellipsis/more-horizontal berbeda antarversi paket ikon) --}}
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <circle cx="5" cy="12" r="1.8" />
        <circle cx="12" cy="12" r="1.8" />
        <circle cx="19" cy="12" r="1.8" />
      </svg>
    </button>
  </div>

  {{-- ============ Panel warna ============ --}}
  <div
    x-show="tbOpen === 'color'"
    x-cloak
    class="absolute inset-x-1 top-full z-30 mt-0.5 rounded-lg border border-gray-200 bg-white p-2 shadow-lg"
    data-toolbar-panel="color"
  >
    <span class="{{ $label }}">Warna teks</span>
    <div class="flex flex-wrap items-center gap-1.5">
      @foreach ($swatches as [$hex, $name])
        <button
          type="button"
          title="{{ $name }}"
          aria-label="{{ $name }}"
          x-on:click="runCommand('setColor', '{{ $hex }}'); tbOpen = null"
          x-bind:class="getCurrentColor().toLowerCase() === '{{ strtolower($hex) }}' ? 'ring-2 ring-foresty ring-offset-1' : ''"
          class="h-5 w-5 shrink-0 cursor-pointer rounded-full border border-gray-300 transition-transform hover:scale-110"
          style="background-color: {{ $hex }};"
        ></button>
      @endforeach
      <label
        class="ml-auto flex items-center gap-1.5 text-[11px] text-gray-500"
      >
        Bebas
        <input
          type="color"
          x-bind:value="getCurrentColor()"
          x-on:input="runCommand('setColor', $event.target.value)"
          class="h-5 w-7 cursor-pointer rounded border border-gray-300 bg-white p-0"
        />
      </label>
    </div>
    <button
      type="button"
      x-on:click="
        runCommand('unsetColor');
        tbOpen = null;
      "
      class="hover:text-foresty mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-semibold text-gray-500"
    >
      <x-dynamic-component
        :component="'lucide-eraser'"
        class="h-3.5 w-3.5"
        stroke-width="2.25"
      />
      Kembalikan ke warna bawaan
    </button>
  </div>

  {{-- ============ Panel lainnya ============ --}}
  <div
    x-show="tbOpen === 'more'"
    x-cloak
    class="absolute inset-x-1 top-full z-30 mt-0.5 flex flex-col gap-2 rounded-lg border border-gray-200 bg-white p-2 shadow-lg"
    data-toolbar-panel="more"
  >
    <div>
      <span class="{{ $label }}">Ketebalan huruf</span>
      <select
        title="Ketebalan huruf"
        aria-label="Ketebalan huruf"
        x-on:change="setFontWeight($event.target.value)"
        x-bind:value="getCurrentFontWeight()"
        x-bind:class="
          getCurrentFontWeight() === 'default'
            ? 'text-gray-500'
            : 'text-gray-900 font-semibold'
        "
        class="{{ $select }} w-full"
        data-toolbar-weight
      >
        <option value="default">Bawaan blok</option>
        @foreach ($weights as $val => $text)
          <option value="{{ $val }}">{{ $text }} ({{ $val }})</option>
        @endforeach
      </select>
    </div>

    <div class="flex flex-wrap gap-0.5" data-toolbar-extra>
      <button
        type="button"
        x-on:mousedown.prevent
        x-on:click="runCommand('toggleStrike')"
        x-bind:class="checkButtonActive('strike', {}, 'default') ? '{{ $on }}' : ''"
        class="{{ $chip }}"
      >
        <x-dynamic-component
          :component="'lucide-strikethrough'"
          class="h-3.5 w-3.5"
          stroke-width="2.25"
        />Coretan
      </button>
      <span x-show="!$data.single" class="contents">
        <button
          type="button"
          x-on:mousedown.prevent
          x-on:click="runCommand('setTextAlign', { textAlign: 'justify' })"
          x-bind:class="checkButtonActive('justify', { textAlign: 'justify' }, 'textAlign') ? '{{ $on }}' : ''"
          class="{{ $chip }}"
        >
          <x-dynamic-component
            :component="'lucide-align-justify'"
            class="h-3.5 w-3.5"
            stroke-width="2.25"
          />Rata kiri-kanan
        </button>
        <button
          type="button"
          x-on:mousedown.prevent
          x-on:click="runCommand('toggleIndent')"
          class="{{ $chip }}"
        >
          <x-dynamic-component
            :component="'lucide-list-indent-increase'"
            class="h-3.5 w-3.5"
            stroke-width="2.25"
          />Jorok
        </button>
        @foreach ($more as [$cmd, $name, $title, $icon])
          <button
            type="button"
            x-on:mousedown.prevent
            x-on:click="runCommand('{{ $cmd }}')"
            x-bind:class="checkButtonActive('{{ $name }}', {}, 'default') ? '{{ $on }}' : ''"
            class="{{ $chip }}"
          >
            <x-dynamic-component
              :component="'lucide-' . $icon"
              class="h-3.5 w-3.5"
              stroke-width="2.25"
            />{{ $title }}
          </button>
        @endforeach
      </span>
    </div>
  </div>
</div>
