{{--
  <x-content.chat-avatar :avatar="$c['avatar']" :name="$c['who']" /> — foto profil bulat pengirim di percakapan Review (rilis 51).
  $avatar = ['url' => ?string, 'initials' => 'WS', 'tone' => 0..7] dari App\Content\ChatAvatar. Tanpa foto (atau foto gagal dimuat): inisial berwarna.
  Ukuran juga ditulis inline supaya tidak bergantung pada build Tailwind yang mungkin belum dijalankan ulang.
--}}
@props(['avatar' => [], 'name' => '', 'size' => 28])

@php
  $tones = [
      'bg-emerald-100 text-emerald-800',
      'bg-sky-100 text-sky-800',
      'bg-amber-100 text-amber-800',
      'bg-violet-100 text-violet-800',
      'bg-rose-100 text-rose-800',
      'bg-teal-100 text-teal-800',
      'bg-indigo-100 text-indigo-800',
      'bg-orange-100 text-orange-800',
  ];
  $tone = $tones[(int) ($avatar['tone'] ?? 0) % count($tones)];
  $url = $avatar['url'] ?? null;
@endphp

<span
  class="{{ $tone }} relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full text-[10px] font-extrabold tracking-tight select-none"
  style="width: {{ (int) $size }}px; height: {{ (int) $size }}px; min-width: {{ (int) $size }}px"
  title="{{ $name }}"
  data-chat-avatar
>{{ $avatar['initials'] ?? '?' }}@if ($url) <img src="{{ $url }}" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer" x-on:error="$el.remove()" class="absolute inset-0 size-full object-cover" style="width: 100%; height: 100%" data-avatar-img />@endif</span>
