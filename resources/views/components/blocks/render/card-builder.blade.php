@props(['data' => [], 'lang' => 'id'])

@php
  $template = $data['template'] ?? '';
  $colCount = (int) ($data['col_count'] ?? 3);
  $items = $data['items'] ?? [];

  $marginBottom = $data['margin_bottom'] ?? 'mb-4';

  // Menentukan Grid CSS berdasarkan jumlah kolom
  $gridClass = match ($colCount) {
      1 => 'grid-cols-1',
      2 => 'grid-cols-1 md:grid-cols-2',
      4 => 'grid-cols-2 md:grid-cols-4',
      default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
  };
@endphp

@if ($template && count($items) > 0)
  <div class="grid {{ $gridClass }} {{ $marginBottom }} gap-4 sm:gap-6 w-full">
    @foreach ($items as $item)
      @php
        $title = $item['title'][$lang] ?? '';
        $subtitle = $item['subtitle'][$lang] ?? '';
        $desc = $item['desc'][$lang] ?? '';
        $url = !empty(trim($item['url'] ?? '')) ? trim($item['url']) : '#';
        $theme = $item['theme'] ?? 'default'; // Khusus template stats

        // Memecah tags (Lencana) berdasarkan koma
        $tagsRaw = $item['tags'][$lang] ?? '';
        $tags = array_filter(array_map('trim', explode(',', $tagsRaw)));

        // Generator Inisial Avatar (Khusus template Profile)
        $initials = '';
        if ($template === 'profile') {
            foreach (explode(' ', trim($title)) as $w) {
                if (!empty($w)) {
                    $initials .= mb_substr($w, 0, 1);
                }
            }
            $initials = mb_strtoupper(mb_substr($initials, 0, 2)) ?: '?';
        }
      @endphp

      {{-- 1. TEMPLATE: STATS (3 Variasi Gaya) --}}
      @if ($template === 'stats')
        @if ($theme === 'dashed')
          <div class="text-center py-7 px-4 border border-dashed border-gray-400 bg-foresty/90 rounded-[18px] reveal opacity-100 translate-y-0 shadow-sm">
            <span class="font-serif text-[clamp(2rem,4vw,2.6rem)] font-bold text-goldy block leading-none mb-1">{{ $title }}</span>
            <span class="text-[13.5px] text-white/90 block">{{ $desc }}</span>
          </div>
        @elseif($theme === 'boxed')
          <div class="text-center bg-white border border-foresty/15 rounded-[18px] px-4 py-6 reveal opacity-100 translate-y-0 shadow-sm">
            <div class="font-display font-bold text-[26px] text-foresty leading-none mb-1">{{ $title }}</div>
            <div class="text-[13px] text-ink-soft">{{ $desc }}</div>
          </div>
        @else
          <div class="bg-white border border-gray-200 rounded-md p-5 text-center shadow-sm reveal opacity-100 translate-y-0">
            <div class="font-serif font-bold text-[26px] text-foresty tracking-[-0.01em] leading-none mb-1">{{ $title }}</div>
            <div class="text-[12.5px] text-ink-soft">{{ $desc }}</div>
          </div>
        @endif

        {{-- 2. TEMPLATE: DOKUMEN --}}
      @elseif($template === 'document')
        <a href="{{ $url }}" @if ($url !== '#') target="_blank" @endif
          class="flex items-center gap-4 bg-white border border-foresty/15 rounded-[18px] p-5 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1 transition-transform duration-300 group reveal opacity-100 translate-y-0 h-full">
          <div class="w-[52px] h-[52px] rounded-[14px] bg-goldy-soft text-foresty flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 8a2 2 0 0 1 2-2h9l5 5v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"></path>
              <path d="M14 6v5h5"></path>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-bold text-[15px] text-foresty mb-0.5 truncate">{{ $title }}</div>
            <div class="text-[13px] text-ink-soft truncate">{{ $subtitle }}</div>
          </div>
          <div class="w-9 h-9 rounded-full bg-mist text-foresty flex items-center justify-center shrink-0 group-hover:bg-foresty group-hover:text-white transition-colors">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 19h16"></path>
            </svg>
          </div>
        </a>

        {{-- 3. TEMPLATE: PROFIL TIM --}}
      @elseif($template === 'profile')
        <div class="bg-white border border-foresty/15 rounded-[18px] p-6 text-center shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] reveal opacity-100 translate-y-0 h-full flex flex-col">
          <div class="w-[76px] h-[76px] rounded-full bg-mist text-foresty flex items-center justify-center mx-auto mb-4 font-display font-bold text-[24px] tracking-wider">{{ $initials }}</div>
          <h3 class="text-[16px] font-semibold mb-1 text-foresty">{{ $title }}</h3>
          <div class="text-[13px] text-coral font-bold mb-2.5">{{ $subtitle }}</div>
          <div class="text-[13.5px] text-ink-soft leading-relaxed flex-1">{{ $desc }}</div>
        </div>

        {{-- 4. TEMPLATE: DAMPAK (IMPACT) --}}
      @elseif($template === 'impact')
        <div class="bg-white border border-gray-200 rounded-md py-5 px-[22px] shadow-sm reveal opacity-100 translate-y-0 h-full flex flex-col">
          <span class="inline-block self-start text-[11.5px] font-extrabold tracking-[0.05em] uppercase py-1 px-[11px] rounded-full bg-goldy-soft text-foresty mb-2.5">{{ $subtitle }}</span>
          <p class="text-[13.5px] text-ink-soft m-0 mb-3.5 leading-relaxed flex-1">{{ $desc }}</p>
          @if (count($tags) > 0)
            <div class="flex gap-2.5 flex-wrap border-t border-gray-100 pt-3 mt-auto">
              @foreach ($tags as $index => $t)
                <span class="{{ $index === 0 ? 'bg-mist text-foresty' : 'bg-gray-100 text-gray-600' }} rounded-full py-[7px] px-3.5 text-[12.5px] font-bold">{{ $t }}</span>
              @endforeach
            </div>
          @endif
        </div>

        {{-- 5. TEMPLATE: MITRA (PARTNER) --}}
      @elseif($template === 'partner')
        <a href="{{ $url }}"
          class="aspect-[2/1] bg-white border border-foresty/15 rounded-[18px] flex items-center justify-center text-ink-soft hover:text-foresty hover:border-foresty transition-colors font-bold text-[13px] text-center p-3 reveal opacity-100 translate-y-0 shadow-sm">
          {!! nl2br(e($title)) !!}
        </a>

        {{-- 6. TEMPLATE: MINIMAL (SK / IZIN) --}}
      @elseif($template === 'minimal')
        <div class="bg-white border border-foresty/15 rounded-[18px] px-6 py-5 reveal opacity-100 translate-y-0 shadow-sm h-full flex flex-col justify-center">
          <div class="text-[12.5px] font-bold uppercase tracking-[0.06em] text-coral mb-1.5">{{ $subtitle }}</div>
          <div class="text-[15px] font-semibold text-foresty leading-snug">{{ $title }}</div>
        </div>
      @endif
    @endforeach
  </div>
@endif
