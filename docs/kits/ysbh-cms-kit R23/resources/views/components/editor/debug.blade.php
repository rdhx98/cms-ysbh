{{--
  <x-editor.debug> — panel debug kecil di pojok kanan bawah. Hanya dirender bila APP_DEBUG=true.
  Menunjukkan: fokus saat ini, urutan blok, dan pemeriksaan keutuhan (yatim / hantu / tanpa id) yang diperbarui langsung.
  Merah = ada yang rusak. Buang atau abaikan di produksi (tidak muncul saat APP_DEBUG=false).
--}}
@if (config('app.debug'))
  <details x-data="editorDebug" class="fixed right-3 bottom-3 z-[9999] w-80 rounded-lg border border-gray-300 bg-white font-mono text-[10px] shadow-xl">
    <summary class="cursor-pointer rounded-lg px-2 py-1.5 font-bold select-none" x-bind:class="ok ? 'text-emerald-700' : 'bg-red-50 text-red-700'">
      <span x-text="ok ? '● debug editor: utuh' : '● debug editor: ADA MASALAH'"></span>
    </summary>
    <pre class="max-h-72 overflow-auto p-2 whitespace-pre-wrap" x-text="JSON.stringify({
      fokus: { id: $store.editor.id, panel: $store.editor.panel, base: $store.editor.base, bahasa: $store.editor.lang },
      urutan: $wire.blockOrder,
      keutuhan: report(),
      terfokus: focused(),
    }, null, 2)"></pre>
  </details>
@endif
