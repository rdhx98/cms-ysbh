{{--
  <x-editor.field> — gambar satu App\Editor\Field sebagai kontrol yang tepat.
  Satu-satunya tempat yang memetakan "tipe field" -> komponen. Menambah jenis kontrol = tambah satu @case di sini.
--}}
@props(['field', 'locales' => ['id', 'en']])

@php
  /** @var \App\Editor\Field $field */
  $x = $field->extra;
  $whenExpr = null;
  if ($field->when) {
      [$whenKey, $whenVal] = $field->when;
      $whenExpr = is_bool($whenVal)
          ? ($whenVal ? '!! v' : '! v')
          : 'is(' . \Illuminate\Support\Js::from($whenVal) . ')';
  }
@endphp

<div
  class="contents"
  @if ($whenExpr)
    x-data="wireField(null, @js($whenKey), null, false)"
    x-show="{{ $whenExpr }}"
    x-cloak
  @endif
>
  @switch($field->type)
    @case('segmented')
      <x-editor.segmented :rel="$field->key" :label="$field->label" :options="$field->options" :default="$field->default" :compact="$x['compact'] ?? false" :live="$x['live'] ?? null" />
      @break

    @case('swatches')
      <x-editor.swatches :rel="$field->key" :label="$field->label" :options="$field->options" :default="$field->default" :mode="$x['mode'] ?? 'bg'" />
      @break

    @case('select')
      <x-editor.select :rel="$field->key" :label="$field->label" :options="$field->options" :default="$field->default" :font="$x['font'] ?? false" />
      @break

    @case('icon')
      <x-editor.icon-picker :rel="$field->key" :label="$field->label" :default="$field->default" />
      @break

    @case('toggle')
      <x-editor.toggle :rel="$field->key" :label="$field->label" :live="$x['live'] ?? null" />
      @break

    @case('i18n')
      <x-editor.i18n :rel="$field->key" :label="$field->label" :locales="$locales" :multi="$x['multi'] ?? false" :rows="$x['rows'] ?? 3" class="w-full" />
      @break

    @case('rich')
      <x-editor.rich :rel="$field->key" :label="$field->label" :locales="$locales" :multi="$x['multi'] ?? false" class="w-full" />
      @break

    @case('text')
      <x-editor.text :rel="$field->key" :label="$field->label" :placeholder="$x['placeholder'] ?? ''" :maxlength="$x['maxlength'] ?? null" :upper="$x['upper'] ?? false" :slug="$x['slug'] ?? false" class="w-full" />
      @break

    @case('media')
      <x-editor.media :rel="$field->key" :label="$x['button'] ?? 'Jelajahi File Manager'" :accept="$x['accept'] ?? 'image'" class="w-full" />
      @break
  @endswitch
</div>
