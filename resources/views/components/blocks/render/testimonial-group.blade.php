@props(['block', 'data', 'lang'])

@php
  $testimonials = $data['testimonials'] ?? [];
  $colCount = (int) ($data['col_count'] ?? 3);

  $gridClass = match ($colCount) {
      1 => 'grid-cols-1',
      2 => 'md:grid-cols-2',
      default => 'lg:grid-cols-3', // Default 3 kolom
  };
@endphp

@if (count($testimonials) > 0)
  <div id="{{ $block['anchor'] ?? '' }}" class="grid grid-cols-1 {{ $gridClass }} gap-6 my-8 w-full">
    @foreach ($testimonials as $index => $item)
      @php
        $quote = $item['quote'][$lang] ?? '';
        $name = $item['name'][$lang] ?? '';
        $role = $item['role'][$lang] ?? '';
        $theme = $item['theme'] ?? 'theme-forest';
        $delay = $index * 150;

        // 🌟 PETA KELAS CSS BERDASARKAN TEMA TERPADU
        $quoteColor = match ($theme) {
            'theme-gold' => 'text-[#D99B00]',
            'theme-coral' => 'text-coral',
            'theme-neutral' => 'text-gray-400',
            default => 'text-goldy', // theme-forest
        };

        $avatarBg = match ($theme) {
            'theme-gold' => 'bg-[#FDF8E1]',
            'theme-coral' => 'bg-[#FBE6E6]',
            'theme-neutral' => 'bg-gray-100',
            default => 'bg-[#E9F1EB]', // theme-forest
        };

        $avatarFg = match ($theme) {
            'theme-gold' => 'text-[#8A6300]',
            'theme-coral' => 'text-coral-dark',
            'theme-neutral' => 'text-gray-700',
            default => 'text-foresty', // theme-forest
        };

        // Inisial nama otomatis
        $initials = '?';
        if (!empty(trim($name))) {
            $words = explode(' ', trim($name));
            $initials = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $initials .= mb_substr($word, 0, 1);
                }
            }
            $initials = mb_strtoupper(mb_substr($initials, 0, 2));
        }
      @endphp

      <div style="transition-delay: {{ $delay }}ms;"
        class="reveal transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[18px] py-[30px] px-7 border border-foresty/15 relative h-full flex flex-col">

        {{-- Tanda Kutip --}}
        <svg class="w-[30px] h-[30px] {{ $quoteColor }} mb-3.5 shrink-0 transition-colors duration-300" viewBox="0 0 24 24" fill="currentColor">
          <path
            d="M7 7C4.8 7 3 8.8 3 11c0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1C7.3 17 6 18.5 4 19v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4zm10 0c-2.2 0-4 1.8-4 4 0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1-.6 2.1-1.9 3.6-3.9 4.1v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4z">
          </path>
        </svg>

        @if (!empty($quote))
          <p class="font-display italic text-[17px] text-foresty leading-[1.5] mb-5 flex-1">
            {{ $quote }}
          </p>
        @endif

        <div class="flex items-center gap-3 shrink-0 mt-auto">
          {{-- Avatar Inisial --}}
          <div class="w-[42px] h-[42px] rounded-full {{ $avatarBg }} {{ $avatarFg }} flex items-center justify-center font-display font-bold shrink-0 transition-colors duration-300">
            {{ $initials }}
          </div>
          <div>
            @if (!empty($name))
              <div class="font-bold text-[14.5px] text-ink">{{ $name }}</div>
            @endif
            @if (!empty($role))
              <div class="text-[13px] text-ink-soft">{{ $role }}</div>
            @endif
          </div>
        </div>

      </div>
    @endforeach
  </div>
@endif
