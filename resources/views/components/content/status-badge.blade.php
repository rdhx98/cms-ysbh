{{-- <x-content.status-badge tone="warn" label="Ditinjau" /> — lencana status artikel. Kelas ditulis utuh di sini (dipindai Tailwind). --}}
@props(['tone' => 'neutral', 'label' => ''])

@php
  $class = match ($tone) {
      'success' => 'bg-emerald-100 text-emerald-800',
      'warn' => 'bg-amber-100 text-amber-800',
      'info' => 'bg-sky-100 text-sky-800',
      'danger' => 'bg-red-100 text-red-700',
      default => 'bg-gray-100 text-gray-600',
  };
@endphp

<span {{ $attributes->class(['rounded-full px-2.5 py-1 text-[10px] font-extrabold tracking-wide uppercase', $class]) }} data-workflow-badge>{{ $label }}</span>
