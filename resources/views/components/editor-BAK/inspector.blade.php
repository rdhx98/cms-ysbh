{{--
  <x-editor.inspector> — panel Properti. Dirender SEKALI untuk semua blok: satu panel per TIPE
  (dibentuk dari App\Editor\BlockRegistry), dan panel yang tampil mengikuti $store.editor.panel.
  Berpindah fokus hanya mengganti base path di store, jadi tanpa request dan tanpa render ulang.

  wire:ignore: markup ini statis; seluruh state-nya ada di Alpine dan $wire.
--}}
@props (["locales" => ["id", "en"], "types" => null])

@php
  $types = $types ?? \App\Editor\BlockRegistry::all();
  $anchor = \App\Editor\Field::text("anchor", "ID Tautan (Anchor)", [
    "slug" => true,
    "placeholder" => "nama-anchor",
  ]);
@endphp

<div
  wire:ignore
  x-data
  {{
    $attributes->class(
      "text-sm",
    )
  }}
>
  <div
    x-show="!$store.editor.panel"
    class="rounded-xl bg-gray-50 p-4 text-xs leading-relaxed text-gray-500"
  >
    Pilih sebuah blok atau elemen untuk mengubah isi dan gayanya.
  </div>

  @foreach ($types as $panel => $def)
    <section
      x-show="$store.editor.panel === @js($panel)"
      x-cloak
      data-panel="{{ $panel }}"
      class="flex flex-col gap-4"
    >
      <div class="flex items-center gap-2">
        <span
          class="bg-foresty rounded px-2 py-0.5 text-[10px] font-extrabold tracking-widest text-white uppercase"
          >{{ $def->label }}</span
        >
        <span class="text-[10px] font-semibold text-gray-400 uppercase">{{
          $def->kind === "block"
            ? "Blok"
            : "Elemen kartu"
        }}</span>
      </div>

      @foreach ($def->contentFields() as $field)
        <x-editor.field :field="$field" :locales="$locales" />
      @endforeach

      @if ($def->styleFields())
        <div
          class="flex flex-wrap gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3"
        >
          @foreach ($def->styleFields() as $field)
            <x-editor.field :field="$field" :locales="$locales" />
          @endforeach
        </div>
      @endif

      @if ($def->kind === "block")
        <x-editor.field :field="$anchor" :locales="$locales" />
      @endif
    </section>
  @endforeach
</div>
