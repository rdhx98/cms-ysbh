{{--
  <x-editor.container-kids> — daftar isi panel kontainer (rilis 65). Dipasang BlockRegistry lewat BlockType::$component pada Grup Langkah
  ("Isi langkah": satu baris per langkah) dan Kolom (satu kelompok per kolom, berisi blok-bloknya). Klik baris = masuk ke panel blok itu;
  breadcrumb memakai baris yang sama sebagai asal (disorot dan digulir ke tampilan). Data dibaca dari state ($wire.content), jadi ikut
  berubah seketika setelah tambah/hapus/pindah di outline. Menambah blok tetap lewat outline.
--}}
@props(['locales' => ['id', 'en']])

<div x-data="containerKids(@js(collect(\App\Editor\BlockRegistry::all())->map(fn ($d) => $d->label)->all()), @js(! config('cms.editor.defer_style_sync', false)))" {{ $attributes->class('flex flex-col gap-3') }} data-container-kids>
  <template x-for="z in zones" x-bind:key="z.key">
    <div class="flex flex-col gap-1.5" x-bind:data-kzone="z.key">
      <span
        class="text-[10px] font-extrabold tracking-widest uppercase"
        x-bind:class="isViaZone(z.key) ? 'text-forest' : 'text-gray-400'"
        x-text="z.title + (z.hidden ? ' (tersembunyi)' : '')"
      ></span>
      <p x-show="z.items.length === 0" class="rounded-lg bg-gray-50 px-3 py-2 text-[11px] leading-relaxed text-gray-400">
        Kosong. Tambahkan blok lewat outline di kiri.
      </p>
      <template x-for="it in z.items" x-bind:key="it.id">
        <div class="flex flex-col gap-1">
        <button
          type="button"
          x-bind:data-kid="it.id"
          x-on:click="open(it)"
          class="hover:border-forest hover:bg-sage-soft flex w-full items-center gap-2 rounded-lg border px-2.5 py-1.5 text-left transition-colors"
          x-bind:class="isVia(it.id) ? 'border-forest bg-sage-soft' : 'border-gray-200 bg-white'"
        >
          <span class="text-[11px] font-bold text-gray-700" x-text="it.label"></span>
          <span class="min-w-0 flex-1 truncate text-[10px] text-gray-400" x-text="it.hint"></span>
          <svg class="h-3 w-3 shrink-0 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
        </button>
        {{-- Rilis 74: label penanda langkah (dua bahasa; bahasa yang kosong memakai bahasa lain). Hanya untuk Grup Langkah. --}}
        <template x-if="kind === 'step-group'">
          <div class="flex flex-col gap-1 pl-2" data-step-label>
            @foreach ($locales as $lang)
              <div class="relative" x-show="$store.editor.showLang(@js($lang))">
                <span class="bg-sage-soft text-foresty pointer-events-none absolute top-1/2 left-1.5 -translate-y-1/2 rounded px-1 text-[9px] font-bold uppercase">{{ $lang }}</span>
                <input
                  type="text"
                  maxlength="60"
                  x-bind:placeholder="marker === 'dot' ? 'Label, mis. 2014–2015' : 'Angka otomatis (isi untuk menimpa)'"
                  x-bind:value="labelAt(it.id, @js($lang))"
                  x-on:input="setLabel(it.id, @js($lang), $event.target.value)"
                  class="focus:border-foresty focus:ring-foresty w-full rounded-md border-gray-200 py-1 pr-2 pl-7 text-xs shadow-sm transition-colors"
                />
              </div>
            @endforeach
          </div>
        </template>
        </div>
      </template>
    </div>
  </template>
</div>
