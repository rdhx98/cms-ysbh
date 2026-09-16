@php
  $s = $el['style'] ?? [];
  $isPill = filter_var($s['is_pill'] ?? false, FILTER_VALIDATE_BOOLEAN);
  $textContent = $el['content'][$lang] ?? '';

  if ($isPreview && empty($textContent)) {
      $textContent = $isPill ? 'Label Baru' : 'Teks Sementara...';
  }

  if ($isPill) {
      $classes = collect([
          $s['pill_bg'] ?? 'bg-goldy-soft',
          $s['color'] ?? 'text-foresty',
          $s['pill_radius'] ?? 'rounded-full',
          $s['align'] ?? 'self-start', // self-start, self-center, self-end untuk pill
          $s['margin'] ?? 'mb-3',
          'inline-block px-3 py-1.5 text-[11.5px] font-extrabold tracking-[0.06em] uppercase',
      ])
          ->filter()
          ->implode(' ');

      $alignWrap = $s['align'] === 'self-center' ? 'justify-center' : ($s['align'] === 'self-end' ? 'justify-end' : 'justify-start');

      echo "<div class=\"flex w-full {$alignWrap}\"><span class=\"{$classes}\">{$textContent}</span></div>";
  } else {
      $classes = collect([
          $s['font'] ?? 'font-sans',
          $s['size'] ?? 'text-[15px]',
          $s['weight'] ?? 'font-normal',
          $s['color'] ?? 'text-ink-soft',
          $s['align'] ?? 'text-left',
          $s['margin'] ?? 'mb-2',
          'break-words w-full', // 🌟 PERBAIKAN: Memaksa teks yang terlalu panjang untuk turun baris
      ])
          ->filter()
          ->implode(' ');

      echo "<div class=\"{$classes}\">{$textContent}</div>";
  }
  // } else {
  //     $classes = collect([$s['font'] ?? 'font-sans', $s['size'] ?? 'text-[15px]', $s['weight'] ?? 'font-normal', $s['color'] ?? 'text-ink-soft', $s['align'] ?? 'text-left', $s['margin'] ?? 'mb-2'])
  //         ->filter()
  //         ->implode(' ');

  //     echo "<div class=\"{$classes}\">{$textContent}</div>";
  // }
@endphp
