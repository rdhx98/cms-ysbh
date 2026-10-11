{{--
  <x-content.audit-panel /> — tab "Riwayat" di panel kanan editor (rilis 42): audit trail record yang sedang dibuka (Page, Artikel, Snippet).
  Data dari builder->auditTrail(offset) (dibaca dari activity_log milik spatie/laravel-activitylog), dimuat setiap tab dibuka dan 20 per muatan.
  Semua nilai ditampilkan lewat x-text (teks, bukan HTML). Hanya admin/editor yang melihat tab ini; server memeriksa ulang di auditTrail().
--}}
@php
  $section = 'text-[10px] font-extrabold tracking-widest text-gray-400 uppercase';
  $btn = 'rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-[11px] font-bold text-gray-600 shadow-sm transition-colors hover:border-foresty hover:bg-sage-soft hover:text-foresty disabled:cursor-not-allowed disabled:opacity-40';
@endphp

<div x-data="auditPanel" class="flex flex-col gap-3" data-audit>
  <div class="flex items-center justify-between gap-2">
    <span class="{{ $section }}">Riwayat perubahan</span>
    <button type="button" x-on:click="reload()" x-bind:disabled="loading" class="{{ $btn }}" data-audit-reload>Muat ulang</button>
  </div>

  <p x-show="loading && ! entries.length" x-cloak class="text-[11px] text-gray-400">Memuat riwayat…</p>

  <p x-show="failed" x-cloak class="rounded-md bg-amber-50 p-2 text-[11px] leading-relaxed text-amber-800" data-audit-failed>Riwayat tidak bisa dimuat. Coba muat ulang.</p>

  <p x-show="loaded && ! ready && ! failed" x-cloak class="rounded-md bg-amber-50 p-2 text-[11px] leading-relaxed text-amber-800" data-audit-notready>
    Tabel riwayat (<code>activity_log</code>) belum ada atau tidak terbaca. Jalankan <code>php artisan migrate</code>.
  </p>

  <p x-show="loaded && ready && ! failed && ! entries.length" x-cloak class="text-[11px] leading-relaxed text-gray-500" data-audit-empty>
    Belum ada riwayat untuk record ini. Perubahan dicatat sejak model memakai <code>LogsActivity</code> (lihat docs/AUDIT.md).
  </p>

  <ol class="flex flex-col gap-2" data-audit-list>
    <template x-for="e in entries" x-bind:key="e.id">
      <li class="rounded-lg border border-gray-200 bg-white p-2.5" data-audit-entry>
        <div class="flex items-center justify-between gap-2">
          <span
            class="rounded-full px-2 py-0.5 text-[10px] font-bold"
            x-bind:class="{
              'bg-emerald-100 text-emerald-800': e.tone === 'success',
              'bg-sky-100 text-sky-800': e.tone === 'info',
              'bg-red-100 text-red-700': e.tone === 'danger',
              'bg-amber-100 text-amber-800': e.tone === 'warn',
              'bg-gray-100 text-gray-600': e.tone === 'neutral',
            }"
            x-text="e.label"
          ></span>
          <span class="text-[10px] text-gray-400" x-text="e.at"></span>
        </div>
        <p class="mt-1 text-[11px] text-gray-600"><span class="font-semibold" x-text="e.who"></span><span x-show="e.note" x-cloak x-text="' — ' + e.note"></span></p>

        <ul x-show="e.changes.length" x-cloak class="mt-1.5 flex flex-col gap-1">
          <template x-for="c in e.changes" x-bind:key="c.field">
            <li class="text-[11px] leading-snug text-gray-700">
              <span class="font-semibold" x-text="c.label"></span>
              <template x-if="c.bulk"><span class="text-gray-500"> diubah</span></template>
              <template x-if="! c.bulk">
                <span>
                  <template x-if="c.old !== null"><span class="break-words text-gray-400 line-through" x-text="c.old"></span></template>
                  <template x-if="c.old !== null && c.new !== null"><span class="text-gray-400"> → </span></template>
                  <template x-if="c.new !== null"><span class="break-words" x-text="c.new"></span></template>
                </span>
              </template>
            </li>
          </template>
        </ul>
        <p x-show="e.more > 0" x-cloak class="mt-1 text-[10px] text-gray-400" x-text="'+ ' + e.more + ' perubahan lainnya'"></p>
      </li>
    </template>
  </ol>

  <button type="button" x-show="more" x-cloak x-on:click="loadMore()" x-bind:disabled="loading" class="{{ $btn }} self-center" data-audit-more>Muat lebih banyak</button>
</div>
