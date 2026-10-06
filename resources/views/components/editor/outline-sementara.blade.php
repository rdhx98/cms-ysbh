{{--
  <x-editor.outline-sementara> — OUTLINE SEMENTARA, supaya inspektur bisa dicoba SEKARANG sebelum outline/kanvas
  sungguhan (Fase 2). Membaca langsung dari state klien ($wire.blockOrder dan $wire.content): blok tingkat atas, dan
  elemen di dalam kartu pada blok card-builder. Klik = memfokus (tanpa request). Dibuang saat outline sungguhan jadi.
--}}
<nav x-data class="flex flex-col gap-0.5 text-xs">
  <template x-for="id in ($wire.blockOrder || [])" :key="id">
    <div>
      <button
        type="button"
        x-on:click="$store.editor.focusBlock(id, $wire.$get('content.' + id + '.type'))"
        x-bind:class="$store.editor.id === id ? 'bg-foresty text-white' : 'hover:bg-sage-soft'"
        class="w-full truncate rounded-md px-2 py-1.5 text-left font-semibold"
        x-text="String($wire.$get('content.' + id + '.type') || '?').replace(/[-_]/g, ' ')"
      ></button>

      {{-- Elemen di dalam kartu (card-builder) --}}
      <template x-if="$wire.$get('content.' + id + '.type') === 'card-builder'">
        <div class="mb-1 ml-3 flex flex-col gap-0.5 border-l pl-2">
          <template x-for="(card, ci) in ($wire.$get('content.' + id + '.data.cards') || [])" :key="card.id">
            <div>
              <template x-for="(col, ki) in (card.layout?.children || [])" :key="col.id">
                <div>
                  <template x-for="(el, ei) in (col.children || [])" :key="el.id">
                    <button
                      type="button"
                      x-on:click="$store.editor.focusElement('content.' + id + '.data.cards.' + ci + '.layout.children.' + ki + '.children.' + ei, el.elementType)"
                      x-bind:class="$store.editor.base === ('content.' + id + '.data.cards.' + ci + '.layout.children.' + ki + '.children.' + ei) ? 'bg-foresty text-white' : 'hover:bg-sage-soft'"
                      class="w-full truncate rounded-md px-2 py-1 text-left"
                      x-text="'Kartu ' + (ci + 1) + ' · Kolom ' + (ki + 1) + ' · ' + el.elementType"
                    ></button>
                  </template>
                </div>
              </template>
            </div>
          </template>
        </div>
      </template>
    </div>
  </template>
</nav>
