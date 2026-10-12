{{--
  <x-content.article-workflow :workflow="$this->workflow" /> — panel "Status" artikel (rilis 38), pengganti dropdown status.
  Tombol hanya yang BOLEH untuk pengguna ini pada status ini (keputusan ArticleWorkflow); server memeriksa ulang di builder::workflowAction().
  Aksi yang butuh alasan / tanggal membuka formulir kecil; aksi lain langsung jalan.
--}}
@props(['workflow'])

@php
  $tones = [
      'primary' => 'bg-emerald-800 text-white hover:bg-emerald-900',
      'success' => 'bg-emerald-700 text-white hover:bg-emerald-800',
      'info' => 'bg-sky-700 text-white hover:bg-sky-800',
      'warn' => 'bg-amber-100 text-amber-900 hover:bg-amber-200',
      'danger' => 'bg-red-700 text-white hover:bg-red-800',
      'neutral' => 'bg-gray-100 text-gray-700 hover:bg-gray-200',
  ];
  $meta = collect($workflow['actions'])->keyBy('action')->map(fn ($a) => ['label' => $a['label'], 'note' => $a['note'], 'date' => $a['date']])->all();
  $section = 'text-[10px] font-extrabold tracking-widest text-gray-400 uppercase';
  $nowLocal = \Illuminate\Support\Carbon::now()->format('Y-m-d\TH:i');
@endphp

<div
  x-data="{
    meta: @js($meta),
    open: null,
    note: '',
    at: @js($workflow['scheduledFor'] ?? ''),
    busy: false,
    pick(action) {
      const m = this.meta[action] || {}
      if (m.note === 'none' && !m.date) { this.send(action); return }
      this.open = action
      this.note = ''
    },
    async send(action) {
      this.busy = true
      try {
        const ok = await $wire.workflowAction(action, this.note || null, this.at || null)
        if (ok) { this.open = null; this.note = '' }
      } finally { this.busy = false }
    },
  }"
  class="flex flex-col gap-3"
  data-workflow
>
  <span class="{{ $section }}">Status</span>

  <div class="flex flex-col gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3">
    <div class="flex items-center gap-2">
      <x-content.status-badge :tone="$workflow['tone']" :label="$workflow['label']" />
      @if ($workflow['status'] === 'scheduled' && $workflow['publishedAt'])
        <span class="text-[11px] font-semibold text-sky-800" data-scheduled-at>Terbit {{ $workflow['publishedAt'] }}</span>
      @elseif ($workflow['status'] === 'published' && $workflow['publishedAt'])
        <span class="text-[11px] text-gray-500">sejak {{ $workflow['publishedAt'] }}</span>
      @endif
    </div>
    @if ($workflow['hint'] !== '')
      <p class="text-[11px] leading-relaxed text-gray-600" data-workflow-hint>{{ $workflow['hint'] }}</p>
    @endif
    @if (! $workflow['exists'])
      <p class="text-[10px] leading-relaxed text-gray-400">Artikel baru disimpan sebagai draf. Tombol Ajukan review menyimpan lalu mengajukan sekaligus.</p>
    @endif
  </div>

  {{-- Catatan peninjau terakhir: alasan dikembalikan / ditolak. Ini yang penulis perlu baca. --}}
  @if ($workflow['feedback'])
    <div class="rounded-lg border {{ $workflow['status'] === 'rejected' ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-900' }} p-3 text-[11px] leading-relaxed" data-feedback>
      <p class="font-bold">{{ $workflow['feedback']['action'] === 'reject' ? 'Alasan penolakan' : 'Catatan peninjau' }} <span class="font-normal opacity-70">— {{ $workflow['feedback']['who'] }}, {{ $workflow['feedback']['at'] }}</span></p>
      <p class="mt-1 whitespace-pre-line">{{ $workflow['feedback']['note'] }}</p>
    </div>
  @endif

  {{-- Catatan peninjau sejak pengajuan terakhir (rilis 49): masukan tanpa mengubah isi atau status. Dibaca penulis dan peninjau lain. --}}
  @if (! empty($workflow['notes']))
    <div class="flex flex-col gap-2" data-notes>
      <span class="{{ $section }}">Catatan peninjau ({{ count($workflow['notes']) }})</span>
      @foreach ($workflow['notes'] as $n)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-900" data-note>
          <p class="font-bold">{{ $n['who'] }} <span class="font-normal opacity-70">— {{ $n['at'] }}</span></p>
          <p class="mt-1 whitespace-pre-line">{{ $n['note'] }}</p>
        </div>
      @endforeach
    </div>
  @endif

  @if (count($workflow['actions']))
    <div class="flex flex-col gap-1.5" data-workflow-actions>
      @foreach ($workflow['actions'] as $a)
        <button
          type="button"
          x-on:click="pick(@js($a['action']))"
          x-bind:disabled="busy"
          class="{{ $tones[$a['tone']] ?? $tones['neutral'] }} rounded-md px-3 py-2 text-left text-xs font-bold disabled:opacity-50"
          data-action="{{ $a['action'] }}"
        >{{ $a['label'] }}</button>
      @endforeach
    </div>
  @endif

  {{-- Formulir kecil untuk aksi yang butuh alasan / waktu --}}
  <div x-show="open" x-cloak class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-white p-3" data-workflow-form>
    <p class="text-xs font-bold text-gray-800" x-text="open ? meta[open].label : ''"></p>

    <template x-if="open && meta[open].date">
      <label class="flex flex-col gap-1 text-[11px] font-semibold text-gray-600">
        Tanggal dan jam terbit <span class="font-normal text-gray-400">(zona waktu {{ $workflow['timezone'] }})</span>
        <input type="datetime-local" x-model="at" min="{{ $nowLocal }}" class="rounded-md border border-gray-300 px-2 py-1.5 text-xs" data-field="at" />
      </label>
    </template>

    <template x-if="open && meta[open].note !== 'none'">
      <label class="flex flex-col gap-1 text-[11px] font-semibold text-gray-600">
        <span x-text="meta[open].note === 'required' ? (open === 'note' ? 'Catatan untuk penulis (wajib)' : 'Alasan (wajib, dibaca penulis)') : 'Catatan (opsional)'"></span>
        <textarea x-model="note" rows="3" maxlength="{{ $workflow['noteMax'] }}" class="rounded-md border border-gray-300 px-2 py-1.5 text-xs font-normal" data-field="note"></textarea>
        <span class="self-end text-[10px] font-normal text-gray-400" x-text="note.length + ' / {{ $workflow['noteMax'] }}'"></span>
      </label>
    </template>

    <div class="flex justify-end gap-2">
      <button type="button" x-on:click="open = null" class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700" data-cancel>Batal</button>
      <button
        type="button"
        x-on:click="send(open)"
        x-bind:disabled="busy || (open && meta[open].note === 'required' && note.trim() === '') || (open && meta[open].date && !at)"
        class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-bold text-white disabled:opacity-40"
        data-confirm
      >Konfirmasi</button>
    </div>
  </div>

  {{-- Riwayat --}}
  @if ($workflow['exists'])
    <details class="rounded-lg border border-gray-100 text-[11px]" data-history>
      <summary class="cursor-pointer px-3 py-2 font-bold text-gray-600">Riwayat tinjauan @if (count($workflow['history']))({{ count($workflow['history']) }})@endif</summary>
      <div class="flex flex-col gap-2 border-t border-gray-100 p-3">
        @if (! $workflow['historyReady'])
          <p class="text-amber-700">Riwayat belum aktif: jalankan <code>php artisan migrate</code>. Tanpa itu alasan pengembalian dan penolakan tidak bisa dicatat.</p>
        @elseif (! count($workflow['history']))
          <p class="text-gray-400">Belum ada.</p>
        @endif
        @foreach ($workflow['history'] as $h)
          <div class="leading-relaxed text-gray-600">
            <p><b>{{ $h['who'] }}</b> {{ $h['verb'] }}@if ($h['for']) <span class="text-sky-700">({{ $h['for'] }})</span>@endif <span class="text-gray-400">· {{ $h['at'] }}</span></p>
            @if ($h['note'] !== '')
              <p class="mt-0.5 border-l-2 border-gray-200 pl-2 whitespace-pre-line text-gray-500">{{ $h['note'] }}</p>
            @endif
          </div>
        @endforeach
      </div>
    </details>
  @endif
</div>
