{{--
  Panel statistik dashboard (rilis 79). Hanya MENGGAMBAR larik hasil App\Content\SiteStats::dashboard(); tidak membaca basis data.
  Dipakai komponen Livewire content.site-stats, dan bisa dirender langsung di pengujian.
    @props stats : larik dashboard()      days : periode terpilih (7/30/90)      selectable : tampilkan tombol periode (wire:click="setDays")
  Grafik = SVG sebaris (tanpa pustaka JS). Kelas Tailwind ditulis utuh; angka dibuat di PHP. Tidak ada HTML dari data yang dicetak mentah.
--}}
@props(['stats', 'days' => 30, 'selectable' => true])

@php
  $fmt = fn (int $n): string => number_format($n, 0, ',', '.');
  $toneClass = ['ok' => 'border-emerald-300 bg-emerald-50 text-emerald-900', 'warn' => 'border-amber-300 bg-amber-50 text-amber-900', 'neutral' => 'border-zinc-200 bg-white text-zinc-900'];
  $months = ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu', '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'];
  $dayLabel = fn (string $d): string => (int) substr($d, 8, 2) . ' ' . ($months[substr($d, 5, 2)] ?? '');
  $visits = $stats['visits'] ?? null;
  $series = $visits['series'] ?? [];
  $maxViews = max(1, ...array_column($series ?: [['views' => 0]], 'views'));
  $count = max(1, count($series));
  $step = 100 / $count;
  $monthly = $stats['monthly'] ?? [];
  $maxMonth = max(1, ...array_column($monthly ?: [['count' => 0]], 'count'));
  $statusTotal = max(1, array_sum(array_column($stats['statuses'] ?? [], 'count')));
  $statusTone = ['draft' => 'bg-zinc-400', 'review' => 'bg-amber-400', 'scheduled' => 'bg-sky-400', 'published' => 'bg-emerald-500', 'archived' => 'bg-zinc-300', 'rejected' => 'bg-red-400'];
@endphp

<section class="flex w-full flex-col gap-4" data-stats-panel data-scope="{{ $stats['scope'] }}">
  {{-- Kepala + periode --}}
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h2 class="text-xl font-bold text-zinc-800">{{ $stats['scope'] === 'all' ? 'Statistik situs' : 'Statistik artikel saya' }}</h2>
    @if ($selectable)
      <div class="inline-flex overflow-hidden rounded-lg border border-zinc-200 text-sm" role="group" aria-label="Periode">
        @foreach ([7, 30, 90] as $p)
          <button
            type="button"
            wire:click="setDays({{ $p }})"
            aria-pressed="{{ $days === $p ? 'true' : 'false' }}"
            class="{{ $days === $p ? 'bg-foresty text-white' : 'bg-white text-zinc-600 hover:bg-sage-soft' }} cursor-pointer px-3 py-1.5 font-semibold transition-colors"
          >{{ $p }} hari</button>
        @endforeach
      </div>
    @endif
  </div>

  {{-- Kartu angka --}}
  <div class="grid grid-cols-2 gap-3 lg:grid-cols-4" data-stats-cards>
    @foreach ($stats['cards'] as $card)
      <div class="{{ $toneClass[$card['tone']] ?? $toneClass['neutral'] }} flex flex-col gap-1 rounded-xl border p-4" data-card="{{ $card['key'] }}">
        <span class="text-xs font-semibold tracking-wide uppercase opacity-70">{{ $card['label'] }}</span>
        <span class="text-3xl font-black">{{ $fmt((int) $card['value']) }}</span>
        <span class="text-xs opacity-70">
          {{ $card['hint'] }}
          @if ($card['delta'] !== null)
            <b class="{{ $card['delta'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $card['delta'] >= 0 ? '▲' : '▼' }} {{ abs($card['delta']) }}%</b>
          @endif
        </span>
      </div>
    @endforeach
  </div>

  {{-- Grafik kunjungan harian (peninjau) --}}
  @if ($visits !== null)
    <div class="rounded-xl border border-zinc-200 bg-white p-4" data-visits-chart>
      <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
        <h3 class="font-bold text-zinc-800">Kunjungan harian</h3>
        <div class="flex items-center gap-4 text-xs text-zinc-600">
          <span class="inline-flex items-center gap-1"><span class="inline-block size-2.5 rounded-sm bg-foresty/30"></span>Kunjungan</span>
          <span class="inline-flex items-center gap-1"><span class="inline-block size-2.5 rounded-sm bg-foresty"></span>Pengunjung unik</span>
        </div>
      </div>
      @if ($visits['views'] === 0)
        <p class="py-8 text-center text-sm text-zinc-500">Belum ada kunjungan tercatat pada periode ini.</p>
      @else
        <svg viewBox="0 0 100 40" preserveAspectRatio="none" class="h-40 w-full" role="img" aria-label="Grafik kunjungan harian {{ $days }} hari terakhir">
          @foreach ($series as $i => $row)
            @php
              $h = $row['views'] / $maxViews * 38;
              $hu = $row['uniques'] / $maxViews * 38;
              $x = $i * $step + $step * 0.15;
              $w = $step * 0.7;
            @endphp
            <g>
              <title>{{ $dayLabel($row['day']) }}: {{ $fmt($row['views']) }} kunjungan, {{ $fmt($row['uniques']) }} unik</title>
              <rect x="{{ round($x, 3) }}" y="{{ round(40 - $h, 3) }}" width="{{ round($w, 3) }}" height="{{ round($h, 3) }}" class="fill-foresty/30"></rect>
              <rect x="{{ round($x, 3) }}" y="{{ round(40 - $hu, 3) }}" width="{{ round($w, 3) }}" height="{{ round($hu, 3) }}" class="fill-foresty"></rect>
            </g>
          @endforeach
        </svg>
        <div class="mt-1 flex justify-between text-xs text-zinc-500">
          <span>{{ $dayLabel($visits['from']) }}</span>
          @if ($visits['peak'] !== null)
            <span>puncak {{ $dayLabel($visits['peak']['day']) }}: {{ $fmt($visits['peak']['views']) }} kunjungan</span>
          @endif
          <span>{{ $dayLabel($visits['to']) }}</span>
        </div>
      @endif
    </div>
  @endif

  <div class="grid gap-4 lg:grid-cols-2">
    {{-- Artikel menurut status --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4" data-status-bar>
      <h3 class="mb-3 font-bold text-zinc-800">Artikel menurut status</h3>
      <div class="flex h-3 w-full overflow-hidden rounded-full bg-zinc-100" role="img" aria-label="Komposisi status artikel">
        @foreach ($stats['statuses'] as $s)
          @if ($s['count'] > 0)
            <span class="{{ $statusTone[$s['key']] ?? 'bg-zinc-400' }}" style="width: {{ round($s['count'] / $statusTotal * 100, 2) }}%" title="{{ $s['label'] }}: {{ $s['count'] }}"></span>
          @endif
        @endforeach
      </div>
      <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
        @foreach ($stats['statuses'] as $s)
          <li class="flex items-center justify-between gap-2">
            <span class="inline-flex items-center gap-2"><span class="{{ $statusTone[$s['key']] ?? 'bg-zinc-400' }} inline-block size-2.5 rounded-full"></span>{{ $s['label'] }}</span>
            <b>{{ $fmt($s['count']) }}</b>
          </li>
        @endforeach
      </ul>
    </div>

    {{-- Artikel terbit per bulan --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4" data-monthly>
      <h3 class="mb-3 font-bold text-zinc-800">Artikel terbit per bulan</h3>
      <div class="flex h-32 items-end gap-1.5" role="img" aria-label="Artikel terbit per bulan, 12 bulan terakhir">
        @foreach ($monthly as $m)
          <div class="flex h-full flex-1 flex-col items-center justify-end gap-1" title="{{ $months[substr($m['month'], 5, 2)] ?? '' }} {{ substr($m['month'], 0, 4) }}: {{ $m['count'] }} artikel">
            <span class="text-[10px] text-zinc-500">{{ $m['count'] > 0 ? $m['count'] : '' }}</span>
            <span class="{{ $m['count'] > 0 ? 'bg-foresty' : 'bg-zinc-200' }} w-full rounded-t" style="height: {{ $m['count'] > 0 ? max(6, round($m['count'] / $maxMonth * 80)) : 3 }}%"></span>
            <span class="text-[10px] text-zinc-500">{{ $months[substr($m['month'], 5, 2)] ?? '' }}</span>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Terpopuler + bahasa --}}
  @if ($visits !== null || ($stats['tops']['article'] ?? []) !== [])
    <div class="grid gap-4 lg:grid-cols-2" data-tops>
      <div class="rounded-xl border border-zinc-200 bg-white p-4">
        <h3 class="mb-2 font-bold text-zinc-800">{{ $stats['scope'] === 'all' ? 'Artikel terpopuler' : 'Artikel saya yang paling dibaca' }}</h3>
        @forelse ($stats['tops']['article'] as $row)
          <div class="flex items-center justify-between gap-3 border-b border-zinc-100 py-1.5 text-sm last:border-0">
            <span class="min-w-0 truncate" title="{{ $row['title'] }}">{{ $row['title'] }}</span>
            <b class="shrink-0">{{ $fmt($row['views']) }}</b>
          </div>
        @empty
          <p class="py-4 text-center text-sm text-zinc-500">Belum ada data.</p>
        @endforelse
      </div>
      @if ($stats['scope'] === 'all')
        <div class="rounded-xl border border-zinc-200 bg-white p-4">
          <h3 class="mb-2 font-bold text-zinc-800">Halaman terpopuler</h3>
          @forelse ($stats['tops']['page'] as $row)
            <div class="flex items-center justify-between gap-3 border-b border-zinc-100 py-1.5 text-sm last:border-0">
              <span class="min-w-0 truncate" title="{{ $row['title'] }}">{{ $row['title'] }}</span>
              <b class="shrink-0">{{ $fmt($row['views']) }}</b>
            </div>
          @empty
            <p class="py-4 text-center text-sm text-zinc-500">Belum ada data.</p>
          @endforelse
          @if (($stats['locales'] ?? []) !== [])
            <h3 class="mt-4 mb-2 font-bold text-zinc-800">Menurut bahasa</h3>
            <div class="flex flex-col gap-1.5" data-locales>
              @foreach ($stats['locales'] as $l)
                <div class="flex items-center gap-2 text-sm">
                  <span class="w-8 font-semibold uppercase">{{ $l['code'] !== '' ? $l['code'] : '-' }}</span>
                  <span class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-100"><span class="block h-full rounded-full bg-foresty" style="width: {{ $l['share'] }}%"></span></span>
                  <b class="w-20 text-right">{{ $fmt($l['views']) }} ({{ $l['share'] }}%)</b>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      @endif
    </div>
  @endif

  @if ($stats['scope'] === 'all' && ($stats['users']['roles'] ?? []) !== [])
    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-white p-4 text-sm" data-roles>
      <span class="font-bold text-zinc-800">Pengguna menurut peran:</span>
      @foreach ($stats['users']['roles'] as $name => $n)
        <span class="rounded-full bg-sage-soft px-3 py-1 font-semibold text-foresty">{{ ucfirst($name) }} · {{ $n }}</span>
      @endforeach
    </div>
  @endif
</section>
