{{--
  <x-content.canvas> — panel TENGAH builder: pratinjau langsung dalam <iframe> (bingkai: ⚡canvas-frame).
  Mengapa iframe: titik putus Tailwind (sm:, lg:, 2xl:) mengikuti lebar JENDELA, jadi pratinjau tablet/ponsel hanya akurat bila kanvas
  punya jendelanya sendiri; CSS situs juga tidak bercampur dengan CSS admin.

  Isi yang belum disimpan dititipkan lewat token (PreviewStore) lewat aksi publishPreview() milik builder; mengubah apa pun memperbarui
  bingkai ~0,7 dtk kemudian (tanpa memuat ulang iframe). Klik blok di kanvas = memilih blok di outline dan inspektur, dan sebaliknya.
  Logika: resources/js/canvas.js (Alpine.data 'canvasPane').
--}}
@props ([ "frameUrl" => "", "savedUrl" => "", "locales" => ["id", "en"] ])

@php
  $btn =
    "rounded p-1.5 text-gray-500 outline-none transition-colors hover:bg-gray-100 hover:text-gray-800";
  $seg =
    "rounded px-2 py-0.5 text-[10px] font-bold outline-none transition-colors";
@endphp

<section
  x-data="canvasPane(@js(['frameUrl' => $frameUrl, 'locales' => array_values($locales)]))"
  {{
    $attributes->class(
      "flex min-h-0 flex-col bg-gray-200/60",
    )
  }}
>
  <div
    class="flex shrink-0 flex-wrap items-center gap-2 border-b bg-white px-3 py-1.5 text-[11px]"
  >
    <span class="font-extrabold tracking-wide text-gray-400 uppercase"
      >Pratinjau</span
    >

    <span x-show="status === 'loading'" x-cloak class="text-gray-400"
      >Memperbarui…</span
    >
    <span
      x-show="status === 'ready'"
      x-cloak
      class="font-semibold text-emerald-700"
      >● Terkini</span
    >
    <button
      type="button"
      x-show="status === 'error'"
      x-cloak
      x-on:click="publish()"
      class="font-semibold text-red-600 underline"
      x-text="(error || 'Gagal memperbarui') + ' · Coba lagi'"
    ></button>

    <div class="ml-auto flex items-center gap-2">
      <div
        role="group"
        aria-label="Lihat sebagai"
        class="inline-flex items-center rounded-md bg-gray-100 p-0.5"
      >
        @foreach ($locales as $l)
          <button
            type="button"
            title="Lihat sebagai {{ strtoupper($l) }}"
            x-on:click="setLang('{{ $l }}')"
            x-bind:aria-pressed="frameLang === '{{ $l }}'"
            x-bind:class="frameLang === '{{ $l }}' ? 'bg-white text-foresty shadow-sm' : 'text-gray-500 hover:text-gray-800'"
            class="{{ $seg }}"
          >
            {{ strtoupper($l) }}
          </button>
        @endforeach
      </div>

      <div
        role="group"
        aria-label="Ukuran layar"
        class="inline-flex items-center rounded-md bg-gray-100 p-0.5"
      >
        <button
          type="button"
          title="Desktop"
          x-on:click="setDevice('desktop')"
          x-bind:aria-pressed="device === 'desktop'"
          x-bind:class="
            device === 'desktop'
              ? 'bg-white text-foresty shadow-sm'
              : 'text-gray-500'
          "
          class="{{ $seg }}"
        >
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="14" x="2" y="3" rx="2" />
            <path d="M8 21h8M12 17v4" />
          </svg>
        </button>
        <button
          type="button"
          title="Tablet (820 px)"
          x-on:click="setDevice('tablet')"
          x-bind:aria-pressed="device === 'tablet'"
          x-bind:class="
            device === 'tablet'
              ? 'bg-white text-foresty shadow-sm'
              : 'text-gray-500'
          "
          class="{{ $seg }}"
        >
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="16" height="20" x="4" y="2" rx="2" />
            <path d="M12 18h.01" />
          </svg>
        </button>
        <button
          type="button"
          title="Ponsel (390 px)"
          x-on:click="setDevice('phone')"
          x-bind:aria-pressed="device === 'phone'"
          x-bind:class="
            device === 'phone'
              ? 'bg-white text-foresty shadow-sm'
              : 'text-gray-500'
          "
          class="{{ $seg }}"
        >
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="14" height="20" x="5" y="2" rx="2" />
            <path d="M12 18h.01" />
          </svg>
        </button>
        <button
          type="button"
          title="Layar lebar (1600 px, diperkecil agar muat). Daftar isi (TOC) hanya tampil di lebar ini"
          x-on:click="setDevice('wide')"
          x-bind:aria-pressed="device === 'wide'"
          x-bind:class="
            device === 'wide'
              ? 'bg-white text-foresty shadow-sm'
              : 'text-gray-500'
          "
          class="{{ $seg }}"
        >
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" /></svg>
        </button>
      </div>

      <button
        type="button"
        title="Perbarui pratinjau sekarang"
        aria-label="Perbarui pratinjau"
        x-on:click="publish()"
        class="{{ $btn }}"
      >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8M21 3v5h-5M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16M8 16H3v5" /></svg>
      </button>
      @if ($savedUrl !== "")
        <a
          href="{{ $savedUrl }}"
          target="_blank"
          rel="noopener"
          title="Pratinjau versi TERSIMPAN (dari database), tanpa perubahan yang belum disimpan"
          aria-label="Buka pratinjau versi tersimpan di tab baru"
          class="{{ $btn }}"
        >
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <ellipse cx="12" cy="5" rx="9" ry="3" />
            <path d="M3 5v14a9 3 0 0 0 18 0V5" />
            <path d="M3 12a9 3 0 0 0 18 0" />
          </svg>
        </a>
      @endif
      <button
        type="button"
        title="Buka di tab baru"
        aria-label="Buka pratinjau di tab baru"
        x-on:click="openTab()"
        class="{{ $btn }}"
      >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" /></svg>
      </button>
    </div>
  </div>

  <div x-ref="pane" class="relative min-h-0 flex-1 overflow-auto p-3">
    @if ($frameUrl === "")
      <div
        class="mx-auto mt-10 max-w-sm rounded-lg bg-white p-5 text-center text-xs leading-relaxed text-gray-600 shadow"
      >
        <p class="mb-1 font-bold text-gray-800">Rute pratinjau belum dipasang.</p>
        <p>Tambahkan baris <code class="rounded bg-gray-100 px-1">Route::livewire('/preview/{token}', 'content.canvas-frame')->name('preview.frame');</code> di grup rute v2 (lihat <code>contoh-kode/routes.php</code>).</p>
      </div>
    @else
      <div
        class="mx-auto h-full overflow-hidden rounded-lg bg-white shadow-md transition-[width] duration-200"
        x-bind:style="{ width: boxWidth, maxWidth: '100%' }"
      >
        <iframe
          x-ref="frame"
          x-show="src"
          x-bind:src="src || null"
          x-bind:style="frameStyle"
          title="Pratinjau"
          class="block border-0"
        ></iframe>
        <div
          x-show="!src"
          class="flex h-full items-center justify-center text-xs text-gray-400"
        >
          Menyiapkan pratinjau…
        </div>
      </div>
    @endif
  </div>
</section>
