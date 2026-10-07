{{--
  <x-editor.field> — gambar satu App\Editor\Field sebagai kontrol yang tepat.
  Satu-satunya tempat yang memetakan "tipe field" -> komponen. Menambah jenis kontrol = tambah satu @case di sini.

  prefix: HANYA di dalam daftar berulang. Ungkapan JavaScript untuk path item, mis. "'data.buttons.' + i" (i = indeks item).
          Path tiap kontrol menjadi  prefix + '.kunci'  dan dievaluasi di dalam x-for, jadi mengikuti indeks item.
--}}
@props(['field', 'locales' => ['id', 'en'], 'prefix' => null])

@php
  /** @var \App\Editor\Field $field */
  $x = $field->extra;
  $key = $field->key;
  $relExpr = \App\Editor\Rel::item($prefix, $key); // null di inspektur biasa (path statis)

  $whenExpr = null;
  $whenRelJs = null;
  if ($field->when) {
      [$whenKey, $whenVal] = $field->when;
      $whenExpr = is_bool($whenVal)
          ? ($whenVal ? '!! v' : '! v')
          : 'is(' . \Illuminate\Support\Js::from($whenVal) . ')';
      $whenRelJs = \App\Editor\Rel::js($whenKey, \App\Editor\Rel::item($prefix, $whenKey));
  }
@endphp

<div
  class="contents"
  @if ($whenExpr)
    x-data="wireField(null, {!! $whenRelJs !!}, null, false)"
    x-show="{{ $whenExpr }}"
    x-cloak
  @endif
>
  @switch($field->type)
    @case('segmented')
      <x-editor.segmented :rel="$key" :rel-expr="$relExpr" :label="$field->label" :options="$field->options" :default="$field->default" :compact="$x['compact'] ?? false" :live="$x['live'] ?? null" />
      @break

    @case('swatches')
      <x-editor.swatches :rel="$key" :rel-expr="$relExpr" :label="$field->label" :options="$field->options" :default="$field->default" :mode="$x['mode'] ?? 'bg'" />
      @break

    @case('select')
      <x-editor.select :rel="$key" :rel-expr="$relExpr" :label="$field->label" :options="$field->options" :default="$field->default" :font="$x['font'] ?? false" />
      @break

    @case('icon')
      <x-editor.icon-picker :rel="$key" :rel-expr="$relExpr" :label="$field->label" :default="$field->default" :clearable="$x['clearable'] ?? false" />
      @break

    @case('toggle')
      <x-editor.toggle :rel="$key" :rel-expr="$relExpr" :label="$field->label" :live="$x['live'] ?? null" />
      @break

    @case('i18n')
      <x-editor.i18n :rel="$key" :rel-expr="$relExpr" :label="$field->label" :locales="$locales" :multi="$x['multi'] ?? false" :rows="$x['rows'] ?? 3" class="w-full" />
      @break

    @case('rich')
      <x-editor.rich :rel="$key" :rel-expr="$relExpr" :label="$field->label" :locales="$locales" :multi="$x['multi'] ?? false" class="w-full" />
      @break

    @case('text')
      <x-editor.text :rel="$key" :rel-expr="$relExpr" :label="$field->label" :placeholder="$x['placeholder'] ?? ''" :maxlength="$x['maxlength'] ?? null" :upper="$x['upper'] ?? false" :slug="$x['slug'] ?? false" class="w-full" />
      @break

    @case('media')
      <x-editor.media :rel="$key" :rel-expr="$relExpr" :label="$x['button'] ?? 'Jelajahi File Manager'" :accept="$x['accept'] ?? 'image'" class="w-full" />
      @break

    @case('link')
      <x-editor.link :rel="$key" :rel-expr="$relExpr" :label="$field->label" class="w-full" />
      @break

    @case('repeater')
      <x-editor.repeater :field="$field" :locales="$locales" class="w-full" />
      @break
  @endswitch
</div>
