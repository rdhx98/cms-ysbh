{{--
  <x-editor.rich> — teks kaya (Tiptap) per bahasa di panel Properti. Fase 3 (rilis 31).

  Satu komponen Alpine `richField` (resources/js/rich-field.js) per bahasa. Tiptap-nya BARU dibuat saat blok difokus
  dan panel jenis blok itu tampil; dihancurkan saat fokus pindah. Jadi tidak ada ratusan editor sekaligus.

  Aturan keamanan data (tidak berubah dari sebelum Fase 3):
    - Data tidak ditulis hanya karena dibuka: hanya saat pengguna mengubah isi.
    - HTML tersimpan yang tidak bisa diwakili Tiptap tanpa kehilangan isi (tabel, gambar, judul di dalam paragraf, ...)
      tidak dipasang ke editor: tampil hanya-baca + tombol "Sunting juga" yang meminta konfirmasi.

  Toolbar: <x-editor.toolbars> milik proyek (bila ada). Judul = satu baris, jadi toolbar menyembunyikan
  daftar, kutipan, kode, dan indentasi lewat `single` (lihat proyek-anda/toolbars.blade.php). Rata teks Judul tetap ada bila `align`
  (rilis 33): ditulis ke data.align milik blok, bukan ke HTML.
--}}
@props ([
  "path" => null,
  "rel" => null,
  "relExpr" => null,
  "label" => null,
  "locales" => ["id", "en"],
  "multi" => false,
  "live" => null,
  "align" => false,
])

@php
  $isLive = $live ?? !config("cms.editor.defer_style_sync", false);
  // Tampilan kotak editor + nilai bawaan yang dibaca toolbar (menu font/ukuran/warna "Bawaan Blok")
  $look = $multi
    ? [
      "font" => "Plus Jakarta Sans",
      "size" => "default",
      "color" => "#4b5d53",
      "label" => "Normal",
      "classes" => "font-sans text-sm leading-relaxed",
      "align" => false,
    ]
    : [
      "font" => "Fraunces",
      "size" => "default",
      "color" => "#064f3b",
      "label" => "Judul H2",
      "classes" => "font-serif text-lg leading-snug font-semibold",
      "align" => (bool) $align,
    ];
  $hasToolbar = view()->exists("components.editor.toolbars");
@endphp

{{-- Tampilan di editor saja (rilis 35); HTML yang disimpan tidak berubah:
     - ukuran besar (Sub-judul H3 / Judul H2 / Raksasa H1) memakai clamp(...vw...) milik situs; di panel selebar ~300 px ukuran itu memecah kata
       huruf demi huruf, jadi pratinjau di panel diperkecil. Ukuran sebenarnya terlihat di kanvas dan di situs.
     - teks berwarna terang (putih, kuning emas, abu sangat muda) tenggelam di kotak putih: diberi latar abu gelap oleh kelas rte-light
       (dipasang rich-field.js lewat dekorasi, bukan ke data). Aturan yang sama tidak berbahaya bila tercetak lebih dari sekali. --}}
<style>
  [data-rich-editor] [style*="clamp(2.5rem"] {
    font-size: 1.75rem !important;
  }
  [data-rich-editor] [style*="clamp(1.8rem"] {
    font-size: 1.5rem !important;
  }
  [data-rich-editor] [style*="clamp(1.5rem"] {
    font-size: 1.25rem !important;
  }
  [data-rich-editor] .rte-light {
    background: #374151;
    border-radius: 3px;
    box-shadow: 0 0 0 2px #374151;
  }
</style>

<div
  {{
    $attributes->class(
      "flex flex-col gap-1",
    )
  }}
>
  @if ($label)
    <span class="text-xxs font-bold text-gray-700 uppercase">{{ $label }}</span>
  @endif

  @foreach ($locales as $lang)
    <div
      class="relative"
      x-show="$store.editor.showLang(@js($lang))"
      x-data="richField(@js($path), {!! \App\Editor\Rel::js($rel, $relExpr ?? null) !!}, @js($lang), @js((bool) $multi), @js($isLive), @js($look))"
    >
      <span
        class="bg-sage-soft text-foresty mb-0.5 inline-block rounded px-1.5 text-[9px] font-bold uppercase"
        >{{ $lang }}</span
      >

      {{-- Belum ada blok yang difokus --}}
      <div
        x-show="mode === 'off'"
        class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-1.5 py-1 text-[11px] text-gray-400"
      >
        Pilih sebuah blok untuk menyunting teksnya.
      </div>

      {{-- Tiptap terpasang --}}
      {{-- tanpa overflow-hidden: panel warna/lainnya di toolbar muncul di bawah toolbar dan melewati kotak --}}
      <div
        x-show="mode === 'edit'"
        x-cloak
        class="focus-within:border-foresty rounded-lg border border-gray-200 bg-white shadow-sm"
      >
        @if ($hasToolbar)
          <x-editor.toolbars />
        @endif
        <div
          x-ref="editorElement"
          data-rich-editor
          data-placeholder="Ketik di sini..."
          class="{{ $multi ? 'min-h-16' : 'min-h-8' }} rounded-b-[7px] px-1 py-0.5 [&_.ProseMirror]:m-0 [&_.ProseMirror]:p-0 [&_.ProseMirror]:outline-none [&_h1]:m-0 [&_h2]:m-0 [&_h3]:m-0 [&_p]:m-0 [&_p+p]:mt-1 [&_a]:text-blue-700 [&_a]:underline [&_ol]:list-decimal [&_ol]:pl-4 [&_p.is-editor-empty:first-child::before]:pointer-events-none [&_p.is-editor-empty:first-child::before]:float-left [&_p.is-editor-empty:first-child::before]:h-0 [&_p.is-editor-empty:first-child::before]:text-gray-400 [&_p.is-editor-empty:first-child::before]:content-[attr(data-placeholder)] [&_ul]:list-disc [&_ul]:pl-4"
        ></div>
      </div>

      {{-- Isi tidak aman diedit di Tiptap: hanya-baca --}}
      <div x-show="mode === 'locked'" x-cloak class="flex flex-col gap-1">
        <div
          data-rich-readonly
          class="{{ $multi ? 'min-h-16' : '' }} rounded-lg border border-dashed border-gray-300 bg-gray-50 px-1.5 py-1 text-sm text-gray-600"
          x-text="lockedText"
        ></div>
        <p class="rounded-md bg-amber-50 px-1.5 py-1 text-[10px] leading-relaxed text-amber-800">Teks ini memuat format khusus (mis. tabel, gambar, atau judul) yang tidak didukung editor ini, jadi tidak dibuka agar tidak rusak.</p>
        <button
          type="button"
          x-on:click="forceEdit()"
          class="self-start rounded-md border border-amber-300 bg-white px-2 py-1 text-[11px] font-semibold text-amber-800 hover:bg-amber-50"
        >
          Sunting juga
        </button>
      </div>

      {{-- Dialog tautan (di-teleport ke body: panel Properti bisa punya transform yang merusak `fixed`) --}}
      <template x-teleport="body">
        <div
          x-show="showLinkModal"
          x-cloak
          x-on:keydown.escape.window="cancelLink()"
          class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4"
          data-rich-link-modal
        >
          <div
            class="w-full max-w-md rounded-xl bg-white p-4 shadow-xl"
            x-on:click.outside="cancelLink()"
          >
            <div
              class="mb-3 flex gap-1 rounded-md bg-gray-100 p-0.5 text-xs font-semibold"
            >
              <button
                type="button"
                x-on:click="setTab('url')"
                x-bind:class="
                  linkTab === 'url'
                    ? 'bg-white shadow-sm text-forest'
                    : 'text-gray-500'
                "
                class="flex-1 rounded px-2 py-1"
              >
                Alamat
              </button>
              <button
                type="button"
                x-on:click="setTab('page')"
                x-bind:class="
                  linkTab === 'page'
                    ? 'bg-white shadow-sm text-forest'
                    : 'text-gray-500'
                "
                class="flex-1 rounded px-2 py-1"
              >
                Halaman
              </button>
              <button
                type="button"
                x-on:click="setTab('article')"
                x-bind:class="
                  linkTab === 'article'
                    ? 'bg-white shadow-sm text-forest'
                    : 'text-gray-500'
                "
                class="flex-1 rounded px-2 py-1"
              >
                Artikel
              </button>
            </div>

            <div x-show="linkTab === 'url'" class="flex flex-col gap-2">
              <input
                type="text"
                x-model="linkUrl"
                x-on:keydown.enter.prevent="saveLink()"
                placeholder="https://… , mailto:… , tel:… , #bagian , /jalur"
                class="w-full rounded-lg border-gray-200 text-sm shadow-sm"
              />
              <p class="text-[10px] text-gray-500">Tanpa awalan, otomatis memakai https://. Kosongkan lalu simpan untuk melepas tautan.</p>
              <div class="flex items-center justify-between gap-2">
                <button
                  type="button"
                  x-show="hasLink"
                  x-on:click="removeLink()"
                  class="text-xs font-semibold text-red-600 hover:underline"
                >
                  Lepas tautan
                </button>
                <span x-show="!hasLink"></span>
                <div class="flex gap-2">
                  <button
                    type="button"
                    x-on:click="cancelLink()"
                    class="rounded-md border border-gray-200 px-3 py-1 text-xs font-semibold text-gray-600"
                  >
                    Batal
                  </button>
                  <button
                    type="button"
                    x-on:click="saveLink()"
                    class="bg-forest rounded-md px-3 py-1 text-xs font-semibold text-white"
                  >
                    Simpan
                  </button>
                </div>
              </div>
            </div>

            <div x-show="linkTab !== 'url'" class="flex flex-col gap-2">
              <input
                type="text"
                x-model="linkQuery"
                x-on:input.debounce.300ms="searchLinks()"
                x-bind:placeholder="
                  linkTab === 'page' ? 'Cari halaman…' : 'Cari artikel…'
                "
                class="w-full rounded-lg border-gray-200 text-sm shadow-sm"
              />
              <ul
                class="max-h-56 overflow-y-auto rounded-lg border border-gray-100"
              >
                <template x-for="r in linkResults" x-bind:key="r.id">
                  <li>
                    <button
                      type="button"
                      x-on:click="pickInternal(r)"
                      class="hover:bg-sage-soft flex w-full flex-col items-start px-3 py-1.5 text-left"
                    >
                      <span
                        class="text-sm font-semibold text-gray-800"
                        x-text="r.label"
                      ></span>
                      <span
                        class="text-[10px] text-gray-500"
                        x-text="r.hint"
                      ></span>
                    </button>
                  </li>
                </template>
                <li x-show="linkBusy" class="px-3 py-2 text-xs text-gray-400">
                  Mencari…
                </li>
                <li
                  x-show="!linkBusy && linkResults.length === 0"
                  class="px-3 py-2 text-xs text-gray-400"
                >
                  Ketik minimal 2 huruf.
                </li>
              </ul>
              <div class="flex items-center justify-between gap-2">
                <button
                  type="button"
                  x-show="hasLink"
                  x-on:click="removeLink()"
                  class="text-xs font-semibold text-red-600 hover:underline"
                >
                  Lepas tautan
                </button>
                <span x-show="!hasLink"></span>
                <button
                  type="button"
                  x-on:click="cancelLink()"
                  class="rounded-md border border-gray-200 px-3 py-1 text-xs font-semibold text-gray-600"
                >
                  Batal
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  @endforeach
</div>
