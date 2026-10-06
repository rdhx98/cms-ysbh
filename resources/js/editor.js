/**
 * Alpine untuk editor blok:
 *   - store "editor"  : node mana yang sedang difokus (base path + panel inspektur)
 *   - data "wireField": logika baca/tulis satu kontrol terhadap state Livewire ($wire.$get / $wire.$set)
 *
 * Pemasangan (resources/js/app.js):
 *   import { registerEditor } from './editor'
 *   document.addEventListener('alpine:init', () => registerEditor(window.Alpine))
 */
export function registerEditor(Alpine) {
  const canon = (t) => String(t || '').replace(/_/g, '-').toLowerCase()

  // Sama dengan App\Content\Slug::make() di PHP, supaya slug di browser dan di server identik.
  const slugify = (text) =>
    String(text ?? '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/ß/g, 'ss')
      .replace(/&/g, ' dan ')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')

  Alpine.store('editor', {
    id: null, // id blok yang difokus (null bila elemen kartu)
    panel: null, // "block:heading" | "element:text"
    base: null, // path wire node ini, mis. "content.blk_1" atau "content.blk_1.data.cards.0.layout.children.1.children.0"
    lang: 'both', // 'id' | 'en' | 'both'
    tab: 'page', // panel kanan: 'page' (pengaturan halaman) | 'block' (properti blok/elemen yang difokus)

    focusBlock(id, type) {
      this.id = id
      this.base = 'content.' + id
      this.panel = 'block:' + canon(type)
      this.tab = 'block'
    },
    focusElement(base, elementType) {
      this.id = null
      this.base = base
      this.panel = 'element:' + elementType
      this.tab = 'block'
    },
    clear() {
      this.id = this.base = this.panel = null
      this.tab = 'page'
    },
    showLang(l) {
      return this.lang === 'both' || this.lang === l
    },
  })

  /**
   * Pengaturan halaman: slug mengikuti judul HANYA saat membuat baru (auto = true), dan hanya selama slug masih kosong atau
   * masih sama dengan hasil judul sebelumnya. Begitu Anda mengubah slug sendiri, ia tidak ditimpa lagi.
   * Halaman yang sudah ada tidak pernah di-slug otomatis: mengubah slug mematahkan tautan lama.
   */
  Alpine.data('pageSettings', (auto = false, locales = []) => ({
    init() {
      if (!auto) return
      locales.forEach((l) => {
        this.$watch(
          () => (this.$wire.titles || {})[l],
          (now, before) => {
            const cur = (this.$wire.slug || {})[l] ?? ''
            if (cur === '' || cur === slugify(before ?? '')) this.$wire.$set('slug.' + l, slugify(now ?? ''), false)
          },
        )
      })
    },
  }))

  /**
   * Panel debug (<x-editor.debug>): memeriksa keutuhan pohon blok LANGSUNG dari state klien, tanpa menyimpan.
   * Padanan klien dari pemeriksaan tinker di DEBUG.md: yatim, ID hantu, dan blok tanpa kunci id.
   */
  Alpine.data('editorDebug', () => ({
    report() {
      const c = this.$wire.content || {}
      const order = this.$wire.blockOrder || []
      const isZone = (k) => k === 'children' || String(k).endsWith('_zone')

      const seen = new Set()
      const ghosts = []
      const stack = [...order].reverse()
      while (stack.length) {
        const id = stack.pop()
        if (seen.has(id)) continue
        if (!c[id]) { ghosts.push('urutan → ' + id); continue }
        seen.add(id)
        for (const [k, v] of Object.entries(c[id].data || {})) {
          if (isZone(k) && Array.isArray(v)) {
            for (const ch of [...v].reverse()) {
              if (!c[ch]) ghosts.push(id + '.' + k + ' → ' + ch)
              else stack.push(ch)
            }
          }
        }
      }

      const ids = Object.keys(c)
      return {
        blok: ids.length,
        tersambung: seen.size,
        yatim: ids.filter((id) => !seen.has(id)),
        hantu: ghosts,
        tanpaId: ids.filter((id) => !c[id] || !c[id].id),
      }
    },
    // Data node yang sedang difokus (blok, atau elemen kartu): untuk memeriksa tulisan kontrol tanpa membuka konsol
    focused() {
      const base = Alpine.store('editor').base
      return base ? this.$wire.$get(base) ?? null : null
    },
    get ok() {
      const r = this.report()
      return !r.yatim.length && !r.hantu.length && !r.tanpaId.length
    },
  }))

  /** Outline (pohon struktur): label baris dan fokus otomatis. Strukturnya sendiri dirender server. */
  Alpine.data('outline', () => ({
    // Label dari isi blok (teks/judul) dalam bahasa halaman; kosong bila blok belum berisi teks.
    label(id) {
      const d = this.$wire.$get('content.' + id + '.data') || {}
      const loc = document.documentElement.lang || 'id'
      const pick = (v) => {
        if (v && typeof v === 'object') v = v[loc] || v.id || Object.values(v).find((x) => typeof x === 'string' && x)
        return typeof v === 'string' ? v.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim() : ''
      }
      return pick(d.text) || pick(d.title) || pick(d.label) || ''
    },
    // Event "block-added" dari server (aksi tambah): fokuskan blok baru.
    added(e) {
      // detail = {id} (dispatch dengan argumen bernama) atau string id langsung, tergantung versi Livewire
      const id = typeof e.detail === 'string' ? e.detail : e.detail && e.detail.id
      const type = id && this.$wire.$get('content.' + id + '.type')
      if (type) Alpine.store('editor').focusBlock(id, type)
    },
    // Dipanggil sebelum aksi hapus: lepaskan fokus supaya inspektur tidak menunjuk blok yang sudah tidak ada.
    removed() {
      Alpine.store('editor').clear()
    },
  }))

  /**
   * Satu kontrol terikat ke satu path.
   *   fixed : path wire lengkap (dipakai blade lama), atau null
   *   rel   : path RELATIF terhadap node yang difokus (store.editor.base), atau null
   *   def   : nilai yang dianggap terpilih selama path belum punya nilai
   *   live  : true = kirim request; false = hanya ubah state klien ($set(..., false))
   */
  Alpine.data('wireField', (fixed = null, rel = null, def = null, live = true) => ({
    d: def,
    live,
    _t: {},

    get p() {
      if (rel === null) return fixed
      const base = Alpine.store('editor').base
      return base ? base + '.' + rel : null
    },
    get v() {
      const p = this.p
      return p ? (this.$wire.$get(p) ?? this.d) : this.d
    },
    is(x) {
      return String(this.v ?? '') === String(x)
    },
    isCi(x) {
      return String(this.v ?? '').toLowerCase() === String(x).toLowerCase()
    },
    set(x) {
      const p = this.p
      if (p) this.$wire.$set(p, x, this.live)
    },

    // teks: sub-path (mis. kode bahasa) di bawah p; '' = p itu sendiri
    vAt(sfx) {
      const p = this.p
      if (!p) return ''
      return this.$wire.$get(sfx ? p + '.' + sfx : p) ?? ''
    },
    // ---- teks kaya: boleh disunting HANYA bila nilainya teks polos (tanpa tag). Isi HTML dari Tiptap tetap hanya-baca.
    isPlain(sfx) {
      return !/<\/?[a-z][^>]*>|<!--/i.test(String(this.vAt(sfx)))
    },
    plainAt(sfx) {
      return String(this.vAt(sfx)).replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&')
    },
    typePlain(sfx, text) {
      // menulis ke state sebagai HTML yang aman: tanda < > & di-escape, jadi hasilnya selalu tetap "polos"
      this.typeAt(sfx, String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'))
    },

    typeAt(sfx, val) {
      const p = this.p
      if (!p) return
      const path = sfx ? p + '.' + sfx : p
      this.$wire.$set(path, val, false) // state klien langsung
      if (this.live) {
        // mode live: kirim ke server setelah berhenti mengetik (setara wire:model.live.debounce)
        clearTimeout(this._t[path])
        this._t[path] = setTimeout(() => this.$wire.$set(path, val, true), 800)
      }
    },
  }))
}
