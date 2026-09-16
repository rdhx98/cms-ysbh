@php
  $s = $el['style'] ?? [];
  $iconName = $el['content']['icon'] ?? 'box'; // Ikon bawaan jika kosong

  // Trik agar ikon di dalam layar Editor (Livewire) tetap me-render saat nama diketik
  if (empty($iconName)) {
      $iconName = 'box';
  }

  $boxClasses = collect([
      $s['bg'] ?? 'bg-goldy-soft',
      $s['color'] ?? 'text-foresty',
      $s['radius'] ?? 'rounded-[14px]',
      $s['size'] ?? 'w-[52px] h-[52px]',
      $s['hover'] ?? '', // Efek hover khusus seperti group-hover
      'flex items-center justify-center shrink-0 transition-colors duration-300',
  ])
      ->filter()
      ->implode(' ');

  $iconSize = $s['icon_size'] ?? 'w-6 h-6';
@endphp

<div class="{{ $boxClasses }}">
  <x-dynamic-component :component="'lucide-' . $iconName" class="{{ $iconSize }}" />
</div>
