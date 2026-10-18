{{--
  Daftar "perlu perhatian" (rilis 80): record yang masih berpenanda [ISI-DULU] / berfoto kosong, dan yang terjemahannya belum lengkap.
  Hanya MENGGAMBAR hasil App\Content\SiteStats::attention() yang sudah diberi alamat (edit_url, preview_url); dipakai komponen content.attention-list.
    @props tab : placeholders|translation   counts : ['placeholders'=>n,'translation'=>n]   rows : daftar tab terpilih   kind : ''|page|article|snippet
           all : termasuk yang belum tayang   showInfo : ikut yang hanya "belum diterjemahkan"
  Tabel memakai lebar minimum tetap (min-w-[44rem]), BUKAN min-w-max + table-fixed (Firefox menghitung lebarnya tak terhingga).
--}}
@props(['tab' => 'placeholders', 'counts' => ['placeholders' => 0, 'translation' => 0], 'rows' => [], 'kind' => '', 'all' => false, 'showInfo' => false])

@php
  $kinds = ['' => 'Semua', 'page' => 'Halaman', 'article' => 'Artikel', 'snippet' => 'Snippet'];
  $kindTone = ['page' => 'bg-sky-100 text-sky-800', 'article' => 'bg-emerald-100 text-emerald-800', 'snippet' => 'bg-violet-100 text-violet-800'];
  $problemTone = ['separuh' => 'border-red-300 bg-red-50 text-red-800', 'isi' => 'border-amber-300 bg-amber-50 text-amber-900', 'belum' => 'border-zinc-300 bg-zinc-50 text-zinc-700'];
  $problemLabel = \App\Content\SiteStats::PROBLEM_LABELS;
  $tabs = ['placeholders' => 'Penanda belum diisi', 'translation' => 'Terjemahan perlu perhatian'];
@endphp

<section class="flex w-full flex-col gap-4" data-attention data-tab="{{ $tab }}">
  {{-- Tab --}}
  <div class="flex flex-wrap gap-2 border-b border-zinc-200" role="tablist">
    @foreach ($tabs as $key => $label)
      <button
        type="button"
        role="tab"
        wire:click="setTab('{{ $key }}')"
        aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
        class="{{ $tab === $key ? 'border-foresty text-foresty border-b-2 font-bold' : 'text-zinc-500 hover:text-zinc-700' }} -mb-px cursor-pointer px-3 py-2 text-sm transition-colors"
      >{{ $label }} <span class="ml-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700">{{ $counts[$key] ?? 0 }}</span></button>
    @endforeach
  </div>

  {{-- Saringan --}}
  <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
    <div class="inline-flex overflow-hidden rounded-lg border border-zinc-200" role="group" aria-label="Jenis">
      @foreach ($kinds as $k => $label)
        <button
          type="button"
          wire:click="setKind('{{ $k }}')"
          aria-pressed="{{ $kind === $k ? 'true' : 'false' }}"
          class="{{ $kind === $k ? 'bg-foresty text-white' : 'bg-white text-zinc-600 hover:bg-sage-soft' }} cursor-pointer px-3 py-1.5 font-semibold transition-colors"
        >{{ $label }}</button>
      @endforeach
    </div>
    <div class="flex flex-wrap items-center gap-4 text-zinc-700">
      <label class="inline-flex cursor-pointer items-center gap-2">
        <input type="checkbox" wire:model.live="all" @checked($all) class="rounded border-zinc-300" />
        Termasuk yang belum tayang (draf, offline)
      </label>
      @if ($tab === 'translation')
        <label class="inline-flex cursor-pointer items-center gap-2">
          <input type="checkbox" wire:model.live="showInfo" @checked($showInfo) class="rounded border-zinc-300" />
          Termasuk yang belum diterjemahkan
        </label>
      @endif
    </div>
  </div>

  <p class="text-sm text-zinc-600" data-hint>
    @if ($tab === 'placeholders')
      Record yang masih memuat <code class="rounded bg-zinc-100 px-1">[ISI-DULU]</code> atau blok Gambar tanpa berkas. Lengkapi, lalu simpan; daftar ini langsung berkurang.
    @else
      Terjemahan setengah jalan (slug atau judul saja yang terisi) dan teks blok yang kosong di satu bahasa padahal terisi di bahasa lain: bagian itu tampil kosong di situs.
    @endif
  </p>

  @if ($rows === [])
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-8 text-center text-sm font-semibold text-emerald-900" data-empty>
      Tidak ada yang perlu dibereskan{{ $kind !== '' ? ' untuk jenis ini' : '' }}.
    </div>
  @else
    <div class="w-full overflow-x-auto rounded-xl border border-zinc-200">
      <table class="w-full min-w-[44rem] border-collapse text-left text-sm" data-rows>
        <thead class="bg-misty text-foresty text-xs uppercase">
          <tr>
            <th class="px-4 py-3 font-semibold tracking-wider">Jenis</th>
            <th class="px-4 py-3 font-semibold tracking-wider">Judul</th>
            <th class="px-4 py-3 font-semibold tracking-wider">Status</th>
            <th class="px-4 py-3 font-semibold tracking-wider">{{ $tab === 'placeholders' ? 'Yang belum diisi' : 'Masalah per bahasa' }}</th>
            <th class="px-4 py-3 text-center font-semibold tracking-wider">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 bg-white text-zinc-700">
          @foreach ($rows as $r)
            <tr class="align-top transition-colors hover:bg-zinc-50" data-row="{{ $r['kind'] }}-{{ $r['id'] }}">
              <td class="px-4 py-3"><span class="{{ $kindTone[$r['kind']] ?? 'bg-zinc-100 text-zinc-800' }} inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ \App\Content\SiteStats::KIND_LABELS[$r['kind']] ?? $r['kind'] }}</span></td>
              <td class="max-w-xs px-4 py-3 font-medium text-zinc-900">{{ $r['title'] }}</td>
              <td class="px-4 py-3 whitespace-nowrap">{{ \App\Content\SiteStats::statusLabel($r['kind'], $r['status']) }}</td>
              <td class="px-4 py-3">
                @if ($tab === 'placeholders')
                  <div class="flex flex-col gap-1">
                    @if ($r['columns'] !== [])
                      <span><b class="text-amber-800">[ISI-DULU]</b> di: {{ implode(', ', $r['columns']) }}</span>
                    @endif
                    @if ($r['empty_images'] > 0)
                      <span><b class="text-amber-800">{{ $r['empty_images'] }}</b> blok Gambar tanpa berkas</span>
                    @endif
                  </div>
                @else
                  <div class="flex flex-col gap-1.5">
                    @foreach ($r['issues'] as $i)
                      <div class="flex flex-wrap items-baseline gap-2">
                        <span class="{{ $problemTone[$i['problem']] ?? $problemTone['belum'] }} inline-block rounded-full border px-2 py-0.5 text-xs font-semibold uppercase">{{ $i['locale'] }} · {{ $problemLabel[$i['problem']] ?? $i['problem'] }}</span>
                        <span class="text-xs text-zinc-500">{{ $i['detail'] }}</span>
                      </div>
                    @endforeach
                  </div>
                @endif
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-2 whitespace-nowrap">
                  @if (($r['edit_url'] ?? null) !== null)
                    <a href="{{ $r['edit_url'] }}" wire:navigate class="bg-foresty hover:bg-foresty/80 rounded-md px-3 py-1.5 text-xs font-semibold text-white transition-colors">Sunting</a>
                  @endif
                  @if (($r['preview_url'] ?? null) !== null)
                    <a href="{{ $r['preview_url'] }}" wire:navigate class="rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-50">Pratinjau</a>
                  @endif
                  @if (($r['edit_url'] ?? null) === null && ($r['preview_url'] ?? null) === null)
                    <span class="text-xs text-zinc-400">#{{ $r['id'] }}</span>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <p class="text-xs text-zinc-500">{{ count($rows) }} record. Perintah setara: <code class="rounded bg-zinc-100 px-1">php artisan {{ $tab === 'placeholders' ? 'cms:audit-placeholders' : 'cms:audit-translations' }}{{ $all ? ' --all' : '' }}</code></p>
  @endif
</section>
