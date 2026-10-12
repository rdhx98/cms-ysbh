{{--
  <x-content.article-workflow :workflow="$this->workflow" /> — isi tab "Review" artikel (rilis 49 rev 3; sebelumnya panel "Status" di tab Halaman).
  Berisi: status, penulis (satu penulis utama + penulis lain, setara), tombol alur, dan percakapan bergaya chat (pesan = gelembung, peristiwa
  status = baris kecil di tengah, alasan kembalikan/tolak = kutipan). Riwayat perubahan isi ada di tab "Riwayat" (terpisah).
  Tombol hanya yang BOLEH untuk pengguna ini pada status ini (keputusan ArticleWorkflow); server memeriksa ulang di builder.
  Aksi yang butuh alasan / tanggal membuka formulir kecil; aksi lain langsung jalan. Pesan chat = aksi "note" (tidak mengubah status/isi).
  Rilis 50: pesan boleh menempel pada SATU blok (penanda; chip di gelembung, lencana di kanvas), ditandai "Selesai" (baris riwayat tersendiri),
  dan formulir Terbitkan/Jadwalkan memperingatkan (tanpa memblokir) bila masih ada pesan berpenanda yang belum selesai.
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
  $dots = ['success' => 'bg-emerald-500', 'warn' => 'bg-amber-500', 'info' => 'bg-sky-500', 'danger' => 'bg-red-500', 'neutral' => 'bg-gray-400'];
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
  class="flex flex-col gap-4"
  data-workflow
>
  {{-- ======= Status ======= --}}
  <div class="flex flex-col gap-2">
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
      @if (($workflow['pending'] ?? 0) > 0)
        <p class="rounded-md bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-900" data-pending-pins>{{ $workflow['pinWarning'] }}</p>
      @endif
      @if (! $workflow['exists'])
        <p class="text-[10px] leading-relaxed text-gray-400">Artikel baru disimpan sebagai draf. Tombol Ajukan review menyimpan lalu mengajukan sekaligus.</p>
      @endif
    </div>
  </div>

  {{-- ======= Penulis ======= --}}
  @if ($workflow['exists'])
    <div
      x-data="authorPicker"
      class="flex flex-col gap-2"
      data-authors
    >
      <span class="{{ $section }}">Penulis ({{ count($workflow['authors']) }})</span>
      <ul class="flex flex-wrap gap-1.5">
        @forelse ($workflow['authors'] as $a)
          <li class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white py-0.5 pr-1.5 pl-2.5 text-[11px]" data-author="{{ $a['id'] }}">
            <span class="font-semibold text-gray-800">{{ $a['name'] }}</span>
            @if ($a['main'])
              <span class="rounded bg-emerald-100 px-1.5 text-[9px] font-extrabold tracking-wide text-emerald-800 uppercase" data-author-main>Utama</span>
            @endif
            @if ($a['canRemove'])
              <button type="button" x-on:click="remove({{ $a['id'] }}, @js($a['name']))" x-bind:disabled="busy" aria-label="Lepas {{ $a['name'] }} dari penulis" class="rounded-full px-1 text-gray-400 hover:bg-red-50 hover:text-red-700 disabled:opacity-40" data-author-remove>×</button>
            @endif
          </li>
        @empty
          <li class="text-[11px] text-gray-400">Belum ada.</li>
        @endforelse
      </ul>

      @if ($workflow['canManageAuthors'] && count($workflow['authors']) < $workflow['maxAuthors'])
        <div class="relative">
          <input
            type="search"
            x-model="q"
            x-on:input="search()"
            x-on:keydown.escape="results = []"
            autocomplete="off"
            placeholder="Tambah penulis: ketik nama akun…"
            class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-xs"
            data-author-search
          />
          <ul x-show="results.length" x-cloak class="absolute z-20 mt-1 w-full overflow-hidden rounded-md border border-gray-200 bg-white text-xs shadow-lg" data-author-results>
            <template x-for="r in results" x-bind:key="r.id">
              <li><button type="button" x-on:click="add(r.id)" x-bind:disabled="busy" class="block w-full px-3 py-1.5 text-left hover:bg-gray-50 disabled:opacity-40" x-text="r.label" data-author-pick></button></li>
            </template>
          </ul>
        </div>
      @endif
      <p class="text-[10px] leading-relaxed text-gray-400">Semua penulis setara: menulis selagi Draf, mengajukan, menarik kembali, dan berbalas pesan. Nama di situs publik tetap penulis utama.@if ($workflow['canManageAuthors']) Penulis tambahan bisa dilepas; penulis utama tidak.@endif</p>
    </div>
  @endif

  {{-- ======= Tombol alur ======= --}}
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

    @if (($workflow['pending'] ?? 0) > 0)
      <p x-show="open === 'publish' || open === 'schedule'" class="rounded-md border border-amber-300 bg-amber-50 px-2.5 py-2 text-[11px] leading-relaxed text-amber-900" data-pin-warning>
        <b>{{ $workflow['pinWarning'] }}</b> Anda tetap bisa melanjutkan; periksa dulu di percakapan di bawah bila perlu.
      </p>
    @endif

    <template x-if="open && meta[open].date">
      <label class="flex flex-col gap-1 text-[11px] font-semibold text-gray-600">
        Tanggal dan jam terbit <span class="font-normal text-gray-400">(zona waktu {{ $workflow['timezone'] }})</span>
        <input type="datetime-local" x-model="at" min="{{ $nowLocal }}" class="rounded-md border border-gray-300 px-2 py-1.5 text-xs" data-field="at" />
      </label>
    </template>

    <template x-if="open && meta[open].note !== 'none'">
      <label class="flex flex-col gap-1 text-[11px] font-semibold text-gray-600">
        <span x-text="meta[open].note === 'required' ? 'Alasan (wajib, dibaca penulis)' : 'Catatan (opsional)'"></span>
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

  {{-- ======= Percakapan ======= --}}
  <section x-data="reviewChat(@js($workflow['postId']), @js($workflow['userId']))" class="flex flex-col gap-2" data-chat>
    <span class="{{ $section }}">Percakapan</span>

    {{-- Nama blok untuk chip "Kaitkan ke blok terpilih" (dirender server supaya ikut segar setelah blok diubah) --}}
    <div hidden data-block-labels>
      @foreach (($workflow['blockLabels'] ?? []) as $bid => $blabel)
        <span data-bl="{{ $bid }}">{{ $blabel }}</span>
      @endforeach
    </div>

    @if (! $workflow['exists'])
      <p class="text-[11px] leading-relaxed text-gray-400">Percakapan dibuka setelah artikel disimpan.</p>
    @elseif (! $workflow['historyReady'])
      <p class="rounded-md bg-amber-50 p-2 text-[11px] leading-relaxed text-amber-800">Percakapan belum aktif: jalankan <code>php artisan migrate</code>. Tanpa itu pesan dan alasan pengembalian/penolakan tidak bisa dicatat.</p>
    @else
      <div x-ref="scroll" class="flex max-h-[24rem] min-h-24 flex-col gap-2.5 overflow-y-auto rounded-lg border border-gray-100 bg-gray-50 p-3" data-chat-scroll>
        @forelse ($workflow['chat'] as $c)
          @php($pn = $c['pin'] ?? null)
          @if ($c['kind'] === 'message')
            <div class="flex flex-col {{ $c['mine'] ? 'items-end' : 'items-start' }}" data-chat-id="{{ $c['id'] }}" data-mine="{{ $c['mine'] ? 1 : 0 }}" @if ($pn) data-chat-block="{{ $pn['blockId'] }}" data-pin-state="{{ $pn['resolved'] ? 'resolved' : 'open' }}" @endif data-chat-message>
              <p class="mb-0.5 px-1 text-[10px] text-gray-500"><b>{{ $c['mine'] ? 'Anda' : $c['who'] }}</b> · {{ $c['role'] }} · {{ $c['at'] }}</p>
              @if ($pn)
                <div class="mb-0.5 flex max-w-[88%] flex-wrap items-center gap-1.5 {{ $c['mine'] ? 'justify-end' : '' }}">
                  @if ($pn['gone'])
                    <span class="inline-flex max-w-full items-center gap-1 rounded-md border border-dashed border-gray-300 bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-500" data-pin-gone>
                      <x-content.tab-icon name="block" class="size-3!" />
                      <b>Blok sudah dihapus</b> · {{ $pn['label'] }}@if ($pn['excerpt'] !== '') <span class="truncate italic">“{{ $pn['excerpt'] }}”</span>@endif
                    </span>
                  @else
                    <button type="button" x-on:click="focusPin(@js($pn['blockId']))" class="inline-flex max-w-full items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[10px] text-emerald-900 hover:bg-emerald-100" data-pin-chip>
                      <x-content.tab-icon name="block" class="size-3!" />
                      <b>{{ $pn['label'] }}</b>@if ($pn['excerpt'] !== '') <span class="truncate italic">“{{ $pn['excerpt'] }}”</span>@endif
                    </button>
                  @endif
                  @if ($pn['resolved'])
                    <span class="rounded-md bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800" data-pin-resolved>Selesai{{ $pn['resolvedBy'] ? ' · ' . $pn['resolvedBy'] : '' }}</span>
                  @endif
                  @if ($workflow['canMessage'])
                    <button type="button" x-on:click="resolve({{ $c['id'] }}, {{ $pn['resolved'] ? 'false' : 'true' }})" x-bind:disabled="sending" class="rounded-md border border-gray-200 bg-white px-1.5 py-0.5 text-[10px] font-bold text-gray-600 hover:bg-gray-50 disabled:opacity-40" data-pin-toggle>{{ $pn['resolved'] ? 'Buka lagi' : 'Tandai selesai' }}</button>
                  @endif
                </div>
              @endif
              <div class="{{ $c['mine'] ? 'rounded-br-sm bg-emerald-700 text-white' : 'rounded-bl-sm border border-gray-200 bg-white text-gray-800' }} {{ $pn && $pn['resolved'] ? 'opacity-60' : '' }} max-w-[88%] rounded-2xl px-3 py-2 text-[11px] leading-relaxed whitespace-pre-line [overflow-wrap:anywhere]">{{ $c['note'] }}</div>
            </div>
          @else
            <div class="flex flex-col items-center gap-1" data-chat-id="{{ $c['id'] }}" data-mine="{{ $c['mine'] ? 1 : 0 }}" data-chat-event="{{ $c['action'] }}">
              <p class="text-center text-[10px] leading-relaxed text-gray-500">
                <span class="mr-0.5 inline-block size-1.5 rounded-full align-middle {{ $dots[$c['tone']] ?? $dots['neutral'] }}"></span>
                <b>{{ $c['who'] }}</b> {{ $c['verb'] }}@if ($c['target'] !== '') <b>{{ $c['target'] }}</b>@endif @if ($c['for'])<span class="text-sky-700">({{ $c['for'] }})</span>@endif · {{ $c['at'] }}
              </p>
              @if ($c['note'] !== '')
                <blockquote
                  class="{{ $c['action'] === 'reject' ? 'border-red-400 bg-red-50 text-red-900' : ($c['action'] === 'return' ? 'border-amber-400 bg-amber-50 text-amber-900' : 'border-gray-300 bg-white text-gray-700') }} max-w-[92%] rounded-lg border-l-4 px-3 py-2 text-left text-[11px] leading-relaxed whitespace-pre-line [overflow-wrap:anywhere]"
                  data-chat-quote
                >{{ $c['note'] }}</blockquote>
              @endif
            </div>
          @endif
        @empty
          <p class="py-4 text-center text-[11px] text-gray-400" data-chat-empty>Belum ada percakapan.</p>
        @endforelse
      </div>

      @if ($workflow['canMessage'])
        <form x-on:submit.prevent="send()" class="flex flex-col gap-1.5" data-composer>
          <textarea
            x-model="draft"
            x-on:keydown.ctrl.enter.prevent="send()"
            x-on:keydown.meta.enter.prevent="send()"
            rows="2"
            maxlength="{{ $workflow['noteMax'] }}"
            placeholder="Tulis pesan untuk penulis dan peninjau…"
            class="rounded-md border border-gray-300 px-2 py-1.5 text-xs"
            data-field="message"
          ></textarea>
          @if ($workflow['canPin'] ?? false)
            <label x-show="focusId()" x-cloak class="flex items-start gap-1.5 rounded-md border border-emerald-100 bg-emerald-50/60 px-2 py-1.5 text-[10px] leading-snug text-emerald-900" data-pin-option>
              <input type="checkbox" x-model="pin" class="mt-0.5 rounded border-gray-300" data-pin-checkbox />
              <span>Kaitkan pesan ke blok terpilih: <b x-text="focusLabel()"></b></span>
            </label>
            <p x-show="! focusId()" class="text-[10px] leading-snug text-gray-400" data-pin-hint>Ingin menandai blok tertentu? Klik blok itu di kanvas, lalu kembali ke tab ini.</p>
          @endif
          <div class="flex items-center justify-between gap-2">
            <span class="text-[10px] text-gray-400" x-text="draft.length + ' / {{ $workflow['noteMax'] }}  ·  Ctrl+Enter kirim'"></span>
            <button type="submit" x-bind:disabled="sending || draft.trim() === ''" class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-bold text-white disabled:opacity-40" data-send>Kirim</button>
          </div>
        </form>
      @else
        <p class="text-[10px] leading-relaxed text-gray-400" data-chat-closed>Pesan baru hanya bisa dikirim saat artikel berstatus Draf atau Ditinjau. Percakapan sebelumnya tetap bisa dibaca.</p>
      @endif
    @endif
  </section>
</div>
