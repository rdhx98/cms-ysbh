/**
 * Alpine untuk editor blok:
 *   - store "editor"  : node mana yang sedang difokus (base path + panel inspektur)
 *   - data "wireField": logika baca/tulis satu kontrol terhadap state Livewire ($wire.$get / $wire.$set)
 *
 * Pemasangan (resources/js/app.js):
 *   import { registerEditor } from './editor'
 *   document.addEventListener('alpine:init', () => registerEditor(window.Alpine))
 * (registerEditor mengembalikan { wireFieldFactory, extend } untuk registerRich di rich-field.js, rilis 31.)
 */
// ---------------------------------------------------------------------------------------------------------------------------------
// Blok Tabel (rilis 34): operasi MURNI atas {columns, rows}. Tidak menyentuh Alpine/Livewire, jadi bisa diuji di Node.
//   columns[] {id, label{loc}, align}     rows[] {id, cells[] {loc}}  (cells SEJAJAR dengan columns)
// Semua fungsi mengembalikan salinan baru; masukan tidak diubah.
// ---------------------------------------------------------------------------------------------------------------------------------
export const TABLE_LIMITS = { columns: 12, rows: 100 }

const clone = (v) => JSON.parse(JSON.stringify(v ?? null))
const blankMap = (locales) => Object.fromEntries(locales.map((l) => [l, '']))
const mapOf = (v, locales) => ({ ...blankMap(locales), ...(v && typeof v === 'object' && !Array.isArray(v) ? v : {}) })
const cellsOf = (row) => (Array.isArray(row && row.cells) ? row.cells : [])

/** Salinan yang dirapikan: setiap baris punya tepat sebanyak sel seperti kolom. */
export function normalizeTable(t, locales) {
  const columns = clone(Array.isArray(t && t.columns) ? t.columns : [])
  const rows = clone(Array.isArray(t && t.rows) ? t.rows : [])
  for (const c of columns) c.label = mapOf(c.label, locales)
  for (const r of rows) {
    const cells = cellsOf(r)
    r.cells = columns.map((_, i) => mapOf(cells[i], locales))
  }
  return { columns, rows }
}

export const tableOps = {
  addColumn(t, locales, uid) {
    const n = normalizeTable(t, locales)
    if (n.columns.length >= TABLE_LIMITS.columns) return n
    n.columns.push({ id: uid(), label: blankMap(locales), align: 'left' })
    for (const r of n.rows) r.cells.push(blankMap(locales))
    return n
  },
  removeColumn(t, ci, locales) {
    const n = normalizeTable(t, locales)
    if (n.columns.length <= 1 || ci < 0 || ci >= n.columns.length) return n
    n.columns.splice(ci, 1)
    for (const r of n.rows) r.cells.splice(ci, 1)
    return n
  },
  moveColumn(t, ci, d, locales) {
    const n = normalizeTable(t, locales)
    const j = ci + d
    if (ci < 0 || j < 0 || ci >= n.columns.length || j >= n.columns.length) return n
    ;[n.columns[ci], n.columns[j]] = [n.columns[j], n.columns[ci]]
    for (const r of n.rows) [r.cells[ci], r.cells[j]] = [r.cells[j], r.cells[ci]]
    return n
  },
  cycleAlign(t, ci, locales) {
    const n = normalizeTable(t, locales)
    const c = n.columns[ci]
    if (c) c.align = { left: 'center', center: 'right', right: 'left' }[c.align] || 'center'
    return n
  },
  addRow(t, locales, uid) {
    const n = normalizeTable(t, locales)
    if (n.rows.length >= TABLE_LIMITS.rows) return n
    n.rows.push({ id: uid(), cells: n.columns.map(() => blankMap(locales)) })
    return n
  },
  removeRow(t, ri, locales) {
    const n = normalizeTable(t, locales)
    if (ri >= 0 && ri < n.rows.length) n.rows.splice(ri, 1)
    return n
  },
  moveRow(t, ri, d, locales) {
    const n = normalizeTable(t, locales)
    const j = ri + d
    if (ri < 0 || j < 0 || ri >= n.rows.length || j >= n.rows.length) return n
    ;[n.rows[ri], n.rows[j]] = [n.rows[j], n.rows[ri]]
    return n
  },
  /**
   * Tempel matriks teks mulai dari sel (r0, c0) pada bahasa `lang`: kolom dan baris bertambah (sampai batas) bila matriks lebih besar.
   * Mengembalikan {table, clipped}: clipped = ada bagian yang tidak muat.
   */
  fillMatrix(t, matrix, r0, c0, lang, locales, uid) {
    const n = normalizeTable(t, locales)
    let clipped = false
    for (let mi = 0; mi < matrix.length; mi++) {
      const ri = r0 + mi
      if (ri >= TABLE_LIMITS.rows) { clipped = true; break }
      while (n.rows.length <= ri) n.rows.push({ id: uid(), cells: n.columns.map(() => blankMap(locales)) })
      for (let mj = 0; mj < matrix[mi].length; mj++) {
        const ci = c0 + mj
        if (ci >= TABLE_LIMITS.columns) { clipped = true; break }
        while (n.columns.length <= ci) {
          n.columns.push({ id: uid(), label: blankMap(locales), align: 'left' })
          for (const r of n.rows) r.cells.push(blankMap(locales))
        }
        n.rows[ri].cells[ci][lang] = matrix[mi][mj]
      }
    }
    return { table: n, clipped }
  },
  /** Ganti SELURUH tabel dengan matriks (bahasa `lang` saja terisi). headerFirst: baris pertama menjadi judul kolom. */
  replaceWithMatrix(matrix, headerFirst, lang, locales, uid) {
    const width = Math.min(TABLE_LIMITS.columns, Math.max(1, ...matrix.map((r) => r.length)))
    const columns = Array.from({ length: width }, (_, i) => ({ id: uid(), label: { ...blankMap(locales), [lang]: headerFirst && matrix[0] ? matrix[0][i] ?? '' : '' }, align: 'left' }))
    const body = (headerFirst ? matrix.slice(1) : matrix).slice(0, TABLE_LIMITS.rows)
    const rows = body.map((r) => ({ id: uid(), cells: columns.map((_, i) => ({ ...blankMap(locales), [lang]: r[i] ?? '' })) }))
    const clipped = matrix.some((r) => r.length > TABLE_LIMITS.columns) || (headerFirst ? matrix.length - 1 : matrix.length) > TABLE_LIMITS.rows
    return { table: { columns, rows }, clipped }
  },
  /** Ada isi di salah satu bahasa? (untuk meminta konfirmasi sebelum menimpa/menghapus) */
  hasText(t) {
    const any = (m) => m && typeof m === 'object' && Object.values(m).some((x) => typeof x === 'string' && x.trim() !== '')
    return !!t && ((t.columns || []).some((c) => any(c.label)) || (t.rows || []).some((r) => cellsOf(r).some(any)))
  },
}

/** TSV (tempelan dari Excel / Google Sheets) -> matriks teks. Sel berkutip ("...") boleh memuat tab dan baris baru. */
export function parseTsv(text) {
  const rows = []
  let row = []
  let cell = ''
  let quoted = false
  const s = String(text ?? '')
  for (let i = 0; i < s.length; i++) {
    const ch = s[i]
    if (quoted) {
      if (ch === '"') {
        if (s[i + 1] === '"') { cell += '"'; i++ } else quoted = false
      } else cell += ch
    } else if (ch === '"' && cell === '') quoted = true
    else if (ch === '\t') { row.push(cell); cell = '' }
    else if (ch === '\n') { row.push(cell); rows.push(row); row = []; cell = '' }
    else if (ch !== '\r') cell += ch
  }
  if (cell !== '' || row.length) { row.push(cell); rows.push(row) }
  return rows
}

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

  // Saat MENGETIK di kolom slug: spasi/simbol langsung jadi '-', huruf kecil, tanpa aksen; '-' di ujung masih boleh
  // (agar "tentang-" bisa dilanjutkan). Rapi sepenuhnya (slugify) saat kolom ditinggalkan.
  const slugLive = (text) =>
    String(text ?? '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/ß/g, 'ss')
      .replace(/&/g, '-dan-')
      .replace(/[^a-z0-9-]+/g, '-')
      .replace(/-{2,}/g, '-')
      .replace(/^-/, '')

  Alpine.store('editor', {
    id: null, // id blok yang difokus (null bila elemen kartu)
    parentId: null, // id blok Kartu Builder pemilik elemen yang difokus (null bila bukan elemen kartu); dipakai tombol "Kembali"
    panel: null, // "block:heading" | "element:text"
    base: null, // path wire node ini, mis. "content.blk_1" atau "content.blk_1.data.cards.0.layout.children.1.children.0"
    lang: 'both', // 'id' | 'en' | 'both'
    tab: 'page', // panel kanan: 'page' (pengaturan halaman) | 'block' (properti blok/elemen yang difokus)

    focusBlock(id, type) {
      this.id = id
      this.parentId = null
      this.base = 'content.' + id
      this.panel = 'block:' + canon(type)
      this.tab = 'block'
    },
    // parentId (rilis 32): blok Kartu Builder tempat elemen ini berada; panel elemen menampilkan tombol kembali ke panel kartu itu
    focusElement(base, elementType, parentId = null) {
      this.id = null
      this.parentId = parentId
      this.base = base
      this.panel = 'element:' + elementType
      this.tab = 'block'
    },
    backToParent() {
      if (this.parentId) this.focusBlock(this.parentId, 'card-builder')
    },
    clear() {
      this.id = this.parentId = this.base = this.panel = null
      this.tab = 'page'
    },
    showLang(l) {
      return this.lang === 'both' || this.lang === l
    },
  })

  /**
   * Tinggi editor = SISA tinggi layar (tinggi jendela dikurangi jarak atas elemen ini dan padding bawah induknya), bukan 100dvh.
   * Dengan 100dvh, bilah atas layout ikut dihitung dan halaman jadi bisa digulir, menggeser sidebar utama. Di sini halaman tidak
   * bergulir; hanya panel di dalam editor. Batas bawah 480px (layar yang lebih pendek tetap bisa digulir).
   */
  Alpine.data('fitViewport', () => ({
    h: null,
    init() {
      requestAnimationFrame(() => this.fit()) // setelah layout selesai (font, bilah atas)
    },
    fit() {
      const el = this.$el
      const top = el.getBoundingClientRect().top + window.scrollY
      const below = parseFloat(getComputedStyle(el.parentElement).paddingBottom) || 0
      this.h = Math.max(480, Math.floor(window.innerHeight - top - below))
    },
  }))

  /**
   * Input tag (<x-editor.tags>). Nilai di state = larik campuran: ANGKA = ID tag yang sudah ada, TEKS = nama tag baru.
   * Server (App\Content\TagResolver) memakai TIPE itu sebagai pembeda, jadi tag bernama "2026" tetap tag baru.
   */
  Alpine.data('tagInput', (path, options = [], live = true) => ({
    q: '',
    open: false,
    names: Object.fromEntries(options.map((o) => [o.id, o.name])),
    options,

    get list() {
      return this.$wire.$get(path) || []
    },
    commitList(arr) {
      this.$wire.$set(path, arr, live)
    },
    label(v) {
      return typeof v === 'number' ? (this.names[v] ?? '#' + v) : v
    },
    get suggestions() {
      const q = this.q.trim().toLowerCase()
      return this.options
        .filter((o) => !this.list.includes(o.id) && (q === '' || o.name.toLowerCase().includes(q)))
        .slice(0, 8)
    },
    pick(o) {
      this.commitList([...this.list, o.id])
      this.q = ''
    },
    // Enter / koma / meninggalkan kolom: teks yang diketik menjadi tag (memakai tag lama bila namanya sama)
    commit() {
      const name = this.q.trim().replace(/,+$/, '').trim()
      this.q = ''
      if (!name) return
      const existing = this.options.find((o) => o.name.toLowerCase() === name.toLowerCase())
      if (existing) {
        if (!this.list.includes(existing.id)) this.commitList([...this.list, existing.id])
        return
      }
      if (this.list.some((v) => typeof v === 'string' && v.toLowerCase() === name.toLowerCase())) return
      this.commitList([...this.list, name])
    },
    remove(i) {
      this.commitList(this.list.filter((_, idx) => idx !== i))
    },
    key(e) {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault()
        this.commit()
      } else if (e.key === 'Backspace' && this.q === '' && this.list.length) {
        this.remove(this.list.length - 1)
      }
    },
  }))

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

    // ------------------------------------------------------------------ operasi struktur (rilis 43)
    // Satu menu "⋯" untuk seluruh pohon. Konteks baris dibaca dari data-ctx (diisi server; hanya petunjuk tampilan,
    // server memvalidasi ulang setiap argumen). Tujuan "Pindahkan ke…" dimuat saat dibuka ($wire.moveTargets, tanpa render ulang).
    menu: { open: false, view: 'main', ctx: null, x: 0, top: null, bottom: null, max: 380 },
    targets: [],
    loading: false,
    failed: false,
    _seq: 0,

    get menuStyle() {
      const m = this.menu
      return 'left:' + m.x + 'px;' + (m.top !== null ? 'top:' + m.top + 'px;' : 'bottom:' + m.bottom + 'px;') + 'max-height:' + m.max + 'px'
    },
    rowCtx(el) {
      const row = el && el.closest ? el.closest('[data-outline-row]') : null
      try {
        return row ? JSON.parse(row.dataset.ctx || 'null') : null
      } catch (e) {
        return null
      }
    },
    openMenu(ev) {
      const ctx = this.rowCtx(ev.currentTarget)
      if (!ctx) return
      const r = ev.currentTarget.getBoundingClientRect()
      const w = 232
      const below = window.innerHeight - r.bottom - 12
      const above = r.top - 12
      const up = below < 240 && above > below
      this.menu = {
        open: true,
        view: 'main',
        ctx,
        x: Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8)),
        top: up ? null : r.bottom + 4,
        bottom: up ? window.innerHeight - r.top + 4 : null,
        max: Math.max(160, Math.min(380, up ? above : below)),
      }
      this.targets = []
      this.failed = false
      this.loading = false
      this._seq++
    },
    closeMenu(refocus = false) {
      const id = this.menu.ctx && this.menu.ctx.id
      this.menu.open = false
      this._seq++
      if (refocus && id) this.refocus(id)
    },
    show(view) {
      this.menu.view = view
      if (view === 'move') this.loadTargets()
    },
    async loadTargets() {
      const seq = ++this._seq
      const id = this.menu.ctx.id
      this.loading = true
      this.failed = false
      try {
        const r = await this.$wire.moveTargets(id)
        if (seq === this._seq) this.targets = Array.isArray(r) ? r : []
      } catch (e) {
        if (seq === this._seq) {
          this.targets = []
          this.failed = true
        }
      } finally {
        if (seq === this._seq) this.loading = false
      }
    },
    // Sisipkan jenis $type di atas / di bawah baris ini (posisi di daftar yang sama). Blok baru langsung terfokus (event block-added).
    insert(type) {
      const c = this.menu.ctx
      if (!c) return
      const at = this.menu.view === 'above' ? c.index : c.index + 1
      this.closeMenu()
      return this.$wire.addBlockAt(c.parent, c.zone, type, at)
    },
    moveTo(t) {
      const c = this.menu.ctx
      if (!c || t.current) return
      this.closeMenu()
      return this.act(c.id, () => this.$wire.moveBlockTo(c.id, t.parent, t.zone, null))
    },
    outdent() {
      const c = this.menu.ctx
      this.closeMenu()
      if (c) return this.act(c.id, () => this.$wire.outdentBlock(c.id))
    },
    indent() {
      const c = this.menu.ctx
      this.closeMenu()
      if (c) return this.act(c.id, () => this.$wire.indentBlock(c.id))
    },
    // Jalankan aksi struktur lalu kembalikan fokus keyboard ke baris yang sama (DOM baris dipindah saat render ulang).
    async act(id, fn) {
      try {
        await fn()
      } finally {
        this.refocus(id)
      }
    },
    refocus(id) {
      // Cari lewat document, bukan $root: bila DOM outline diganti utuh, komponen lama sudah dibuang saat callback berjalan.
      this.$nextTick(() => {
        const row = document.querySelector('[data-outline-row="' + CSS.escape(id) + '"] [data-outline-focus]')
        if (row) row.focus()
      })
    },
    // Pintasan di baris yang terfokus: Alt+↑/↓ geser; Alt+Shift+←/→ keluar/masuk kontainer. Tab TIDAK dipakai (aksesibilitas).
    rowKey(ev) {
      if (!ev.altKey || ev.ctrlKey || ev.metaKey) return
      const c = this.rowCtx(ev.currentTarget)
      if (!c) return
      let fn = null
      if (!ev.shiftKey && ev.key === 'ArrowUp' && c.index > 0) fn = () => this.$wire.moveBlock(c.id, -1)
      else if (!ev.shiftKey && ev.key === 'ArrowDown' && c.index < c.count - 1) fn = () => this.$wire.moveBlock(c.id, 1)
      else if (ev.shiftKey && ev.key === 'ArrowLeft' && c.out) fn = () => this.$wire.outdentBlock(c.id)
      else if (ev.shiftKey && ev.key === 'ArrowRight' && c.in) fn = () => this.$wire.indentBlock(c.id)
      if (!fn) return
      ev.preventDefault()
      return this.act(c.id, fn)
    },
  }))

  /**
   * Satu kontrol terikat ke satu path.
   *   fixed : path wire lengkap (dipakai blade lama), atau null
   *   rel   : path RELATIF terhadap node yang difokus (store.editor.base), atau null
   *   def   : nilai yang dianggap terpilih selama path belum punya nilai
   *   live  : true = kirim request; false = hanya ubah state klien ($set(..., false))
   */
  const wireFieldFactory = (fixed = null, rel = null, def = null, live = true) => ({
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

    // kolom slug: normalkan saat mengetik, rapikan saat blur. Kursor dijaga agar tidak melompat ke ujung.
    slugLive(sfx, el) {
      const before = el.value
      const pos = el.selectionStart ?? before.length
      const v = slugLive(before)
      if (v !== before) {
        el.value = v
        const p = Math.max(0, pos - (before.length - v.length))
        el.setSelectionRange(p, p)
      }
      this.typeAt(sfx, v)
    },
    slugFinal(sfx, el) {
      const v = slugify(el.value)
      el.value = v
      this.typeAt(sfx, v)
    },

    typeAt(sfx, val) {
      const p = this.p
      if (!p) return
      const path = sfx ? p + '.' + sfx : p
      this.$wire.$set(path, val, false) // state klien langsung
      if (this.live) {
        // mode live: kirim ke server setelah berhenti mengetik (setara wire:model.live.debounce)
        clearTimeout(this._t[path])
        this._t[path] = setTimeout(() => {
          // JANGAN menulis ulang nilai lama ke path lama: di dalam daftar berulang path berbasis INDEKS, dan dalam 0,8 dtk itu item
          // bisa dipindah/dihapus, sehingga path menunjuk item lain (nilai lama menimpanya, atau item hantu muncul).
          // Cukup kirim apa yang SEKARANG ada di path itu; bila sudah tidak ada, batalkan.
          const now = this.$wire.$get(path)
          if (now !== undefined) this.$wire.$set(path, now, true)
        }, 800)
      }
    },
  })
  Alpine.data('wireField', wireFieldFactory)

  // Memperluas objek data Alpine dengan MEMPERTAHANKAN getter (spread {...a} menyalin nilainya dan menghilangkan getter).
  const extend = (base, extra) => Object.defineProperties(base, Object.getOwnPropertyDescriptors(extra))

  const uid = () => 'itm_' + Math.random().toString(36).slice(2, 10)

  // Padanan JavaScript App\Editor\Defaults::materialize(): '@id' -> ID baru, '@locales' -> peta bahasa kosong.
  const materialize = (v, locales) => {
    if (v === '@id') return uid()
    if (v === '@locales') return Object.fromEntries(locales.map((l) => [l, '']))
    if (Array.isArray(v)) return v.map((x) => materialize(x, locales))
    if (v && typeof v === 'object') return Object.fromEntries(Object.entries(v).map(([k, x]) => [k, materialize(x, locales)]))
    return v
  }

  /**
   * Daftar berulang (<x-editor.repeater>): tambah, hapus, duplikat, urut, buka satu per satu.
   * Setiap perubahan = satu $set larik utuh. DOM dikunci oleh INDEKS, jadi urutan ulang hanya mengganti nilai.
   */
  Alpine.data('repeater', (rel, defaults = {}, locales = ['id', 'en'], max = 12, live = true) => ({
    open: null,
    max,
    init() {
      // berpindah fokus ke blok lain: tutup item yang terbuka
      this.$watch(() => Alpine.store('editor').base, () => {
        this.open = null
      })
    },
    get p() {
      const base = Alpine.store('editor').base
      return base ? base + '.' + rel : null
    },
    get items() {
      return (this.p && this.$wire.$get(this.p)) || []
    },
    plain() {
      return JSON.parse(JSON.stringify(this.items)) // salinan polos (bukan proxy reaktif)
    },
    commit(arr) {
      if (this.p) this.$wire.$set(this.p, arr, live)
    },
    add() {
      if (this.items.length >= max) return
      const arr = this.plain()
      arr.push(materialize(defaults, locales))
      this.commit(arr)
      this.open = arr.length - 1
    },
    remove(i) {
      const arr = this.plain()
      arr.splice(i, 1)
      this.commit(arr)
      this.open = null
    },
    move(i, d) {
      const arr = this.plain()
      const j = i + d
      if (j < 0 || j >= arr.length) return
      ;[arr[i], arr[j]] = [arr[j], arr[i]]
      this.commit(arr)
      if (this.open === i) this.open = j
      else if (this.open === j) this.open = i
    },
    duplicate(i) {
      if (this.items.length >= max) return
      const arr = this.plain()
      const copy = JSON.parse(JSON.stringify(arr[i]))
      if ('id' in copy) copy.id = uid()
      arr.splice(i + 1, 0, copy)
      this.commit(arr)
      this.open = i + 1
    },
    toggle(i) {
      this.open = this.open === i ? null : i
    },
    // Judul baris item: teks dalam bahasa halaman (atau bahasa mana pun yang terisi) + jenis tautan
    summary(item) {
      const loc = document.documentElement.lang || 'id'
      const label = item && (item.label ?? item.question ?? item.title ?? item.name) // kunci teks utama item: tombol=label, FAQ=question
      const text = typeof label === 'string' ? label : label && (label[loc] || Object.values(label).find((x) => typeof x === 'string' && x.trim()))
      const kinds = { page: 'halaman', article: 'artikel', file: 'berkas', url: 'URL', tel: 'telepon', mailto: 'surel', anchor: 'anchor' }
      const kind = item && item.link && kinds[item.link.kind]
      return (String(text || '').trim() || '(tanpa teks)') + (kind ? ' · ' + kind : '')
    },
  }))

  /**
   * Pemilih tautan (<x-editor.link>): jenis + tujuan. Halaman/artikel dicari lewat aksi server searchLinkTargets() dan disimpan
   * sebagai ID; berkas lewat File Manager (media_id). Keamanan URL ditegakkan di server, bukan di sini.
   */
  Alpine.data('linkField', (fixed = null, rel = null, live = true) =>
    extend(wireFieldFactory(fixed, rel, null, live), {
      q: '',
      results: [],
      searching: false,
      open: false,
      _seq: 0,
      get link() {
        return (this.p && this.$wire.$get(this.p)) || {}
      },
      get kind() {
        return this.link.kind || 'url'
      },
      get refId() {
        return ['page', 'article'].includes(this.kind) && Number(this.link.ref) > 0 ? Number(this.link.ref) : 0
      },
      get refLabel() {
        return this.link.ref_label || '#' + this.link.ref
      },
      get mediaId() {
        return this.kind === 'file' && Number(this.link.media_id) > 0 ? Number(this.link.media_id) : 0
      },
      get fileName() {
        const name = String(this.link.url || '').split('/').pop()
        return name || 'Berkas #' + this.mediaId
      },
      get refText() {
        return String(this.link.ref ?? '')
      },
      get urlWarning() {
        const v = String(this.link.ref ?? '').trim()
        if (!v) return ''
        return /^(https?:\/\/\S+|\/(?!\/)\S*)$/i.test(v) ? '' : 'Gunakan https://… atau jalur seperti /halaman. Skema lain (mis. javascript:) tidak dipakai.'
      },
      // Mengganti jenis mengosongkan tujuan (nilainya berbeda jenis); pilihan "tab baru" dipertahankan hanya bila masih relevan
      setKind(k) {
        const p = this.p
        if (!p) return
        const keep = !!this.link.new_tab && ['page', 'article', 'file', 'url'].includes(k)
        this.$wire.$set(p, { kind: k, ref: '', ref_label: '', media_id: null, url: '', new_tab: keep }, this.live)
        this.q = ''
        this.results = []
      },
      clearRef() {
        this.$wire.$set(this.p + '.ref', '', this.live)
        this.$wire.$set(this.p + '.ref_label', '', this.live)
      },
      async search() {
        const q = this.q.trim()
        if (q.length < 2) {
          this.results = []
          return
        }
        const seq = ++this._seq // abaikan jawaban lama bila pengguna sudah mengetik lagi
        this.searching = true
        try {
          const r = await this.$wire.searchLinkTargets(this.kind, q)
          if (seq === this._seq) this.results = Array.isArray(r) ? r : []
        } catch (e) {
          if (seq === this._seq) this.results = []
        } finally {
          if (seq === this._seq) this.searching = false
        }
      },
      pick(r) {
        this.$wire.$set(this.p + '.ref', r.id, this.live)
        this.$wire.$set(this.p + '.ref_label', r.label, this.live)
        this.q = ''
        this.results = []
        this.open = false
      },
      pickFile() {
        if (this.p) this.$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: this.p })
      },
    }),
  )

  /**
   * Pemilih tautan yang menyimpan SATU teks (<x-editor.url-link>, rilis 39): "" | internal://page|article/{slug} | https://… | /jalur.
   * Dipakai tautan seluruh kartu di Kartu Builder (nilainya memang sudah berupa teks, jadi data lama tetap terbaca). Pencarian memakai
   * aksi server yang sama dengan <x-editor.link> (searchLinkTargets). Nama tujuan ditulis ke "{path}_label" (hanya tampilan).
   * Keamanan alamat ditegakkan di server (CoreBlocks::link), bukan di sini.
   */
  const INTERNAL_URL = /^internal:\/\/(page|article)\/([a-z0-9]+(?:-[a-z0-9]+)*)$/
  const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
  Alpine.data('urlLinkField', (fixed = null, rel = null, live = true) =>
    extend(wireFieldFactory(fixed, rel, null, live), {
      local: 'none', // jenis yang dipilih selama nilai masih kosong
      q: '',
      results: [],
      searching: false,
      open: false,
      _seq: 0,
      get value() {
        return String(this.vAt('') ?? '').trim()
      },
      get internal() {
        const m = INTERNAL_URL.exec(this.value)
        return m ? { kind: m[1], slug: m[2] } : null
      },
      get mode() {
        if (this.internal) return this.internal.kind
        return this.value !== '' ? 'url' : this.local
      },
      get typed() {
        return this.internal ? '' : this.vAt('')
      },
      get labelPath() {
        return this.p ? this.p + '_label' : null
      },
      get shownLabel() {
        const l = this.labelPath && this.$wire.$get(this.labelPath)
        const i = this.internal
        return (typeof l === 'string' && l.trim()) || (i ? (i.kind === 'page' ? 'Halaman: ' : 'Artikel: ') + i.slug : '')
      },
      get urlWarning() {
        const v = this.value
        if (!v || this.internal) return ''
        return /^(https?:\/\/\S+|\/(?!\/)\S*|mailto:\S+|tel:\S+)$/i.test(v) ? '' : 'Gunakan https://… atau jalur seperti /halaman. Skema lain (mis. javascript:) tidak dipakai.'
      },
      usable(r) {
        return !!r && SLUG.test(String(r.slug || ''))
      },
      writeLabel(text) {
        const lp = this.labelPath
        if (!lp) return
        const now = this.$wire.$get(lp)
        if ((now ?? '') !== text) this.$wire.$set(lp, text, false)
      },
      // Mengganti jenis mengosongkan tujuan lama (nilainya berbeda jenis)
      setMode(m) {
        if (m === this.mode) return
        this.local = m
        this.q = ''
        this.results = []
        this.writeLabel('')
        if (this.value !== '') this.set('')
      },
      clear() {
        this.writeLabel('')
        this.set('')
        this.q = ''
        this.results = []
      },
      type(text) {
        this.local = 'url'
        this.writeLabel('')
        this.typeAt('', text)
      },
      async search() {
        const q = this.q.trim()
        const kind = this.mode
        if (q.length < 2 || (kind !== 'page' && kind !== 'article')) {
          this.results = []
          return
        }
        const seq = ++this._seq // abaikan jawaban lama bila pengguna sudah mengetik lagi
        this.searching = true
        try {
          const r = await this.$wire.searchLinkTargets(kind, q)
          if (seq === this._seq) this.results = Array.isArray(r) ? r : []
        } catch (e) {
          if (seq === this._seq) this.results = []
        } finally {
          if (seq === this._seq) this.searching = false
        }
      },
      pick(r) {
        if (!this.usable(r)) return
        const kind = this.mode
        this.writeLabel(String(r.label || ''))
        this.set('internal://' + kind + '/' + r.slug)
        this.q = ''
        this.results = []
        this.open = false
      },
    }),
  )

  /**
   * Tab "Riwayat" (audit trail, rilis 42). Memuat dari $wire.auditTrail(offset) setiap tab dibuka; permintaan lama yang terlambat
   * dibuang (_seq) supaya hasil tab yang sudah ditutup/dibuka ulang tidak menimpa yang baru. Data hanya dibaca, tidak ada yang ditulis.
   */
  Alpine.data('auditPanel', () => ({
    entries: [],
    more: false,
    next: 0,
    ready: true,
    loaded: false,
    loading: false,
    failed: false,
    _seq: 0,
    init() {
      this.$watch('$store.editor.tab', (tab) => {
        if (tab === 'audit') this.reload()
      })
    },
    async fetchPage(offset) {
      const seq = ++this._seq
      this.loading = true
      this.failed = false
      try {
        const r = await this.$wire.auditTrail(offset)
        if (seq !== this._seq) return null
        return r && typeof r === 'object' ? r : null
      } catch (e) {
        if (seq === this._seq) this.failed = true
        return null
      } finally {
        if (seq === this._seq) this.loading = false
      }
    },
    async reload() {
      const r = await this.fetchPage(0)
      if (!r) return
      this.ready = r.ready !== false
      this.entries = Array.isArray(r.entries) ? r.entries : []
      this.more = !!r.more
      this.next = Number(r.next) || 0
      this.loaded = true
    },
    async loadMore() {
      const r = await this.fetchPage(this.next)
      if (!r) return
      const seen = new Set(this.entries.map((e) => e.id))
      this.entries = this.entries.concat((Array.isArray(r.entries) ? r.entries : []).filter((e) => !seen.has(e.id)))
      this.more = !!r.more
      this.next = Number(r.next) || this.next
    },
  }))

  /**
   * Pemilih thumbnail artikel (<x-content.thumbnail-picker>, rilis 40). Daftar gambar dimuat dari server SAAT panel dibuka
   * ($wire.thumbnailChoices(): gambar yang dipasang di isi artikel, termasuk yang belum disimpan); memilih memanggil setThumbnail(id),
   * File Manager mengirim ID lewat 'mediaSelected' ke builder. Server memeriksa semua ID; nilai di sini hanya kemudahan.
   */
  Alpine.data('thumbnailPicker', () => ({
    open: false,
    loading: false,
    failed: false,
    choices: [],
    _seq: 0,
    get selected() {
      const v = this.$wire.$get('featured_image')
      return /^\d+$/.test(String(v ?? '')) ? Number(v) : 0
    },
    async load() {
      const seq = ++this._seq
      this.loading = true
      this.failed = false
      try {
        const r = await this.$wire.thumbnailChoices()
        if (seq === this._seq) this.choices = Array.isArray(r) ? r : []
      } catch (e) {
        if (seq === this._seq) {
          this.choices = []
          this.failed = true
        }
      } finally {
        if (seq === this._seq) this.loading = false
      }
    },
    async toggle() {
      this.open = !this.open
      if (this.open) await this.load()
    },
    async pick(c) {
      await this.$wire.setThumbnail(Number(c.id))
      this.open = false
    },
    clear() {
      return this.$wire.clearThumbnail()
    },
    browse() {
      this.$dispatch('openFileManager', { targetEvent: 'mediaSelected', targetComponentId: 'featured_image', allowedFileType: 'image' })
    },
  }))

  /**
   * Panel Kartu Builder (<x-editor.cards>, rilis 32): daftar Kartu -> Kolom -> Elemen untuk blok yang difokus.
   * Data dibaca dari state Livewire ($wire.content.{id}.data.cards). Perubahan STRUKTUR memanggil aksi server (trait HasContentBlocks
   * dan ManagesBlockStructure); perubahan NILAI dikerjakan kontrol biasa di dalam template. DOM dikunci oleh INDEKS.
   */
  Alpine.data('cardPanel', (limits = {}, labels = {}) => ({
    limits: { cards: 24, columns: 6, elements: 20, ...limits },
    labels,
    openCard: null,
    styleOpen: null,

    init() {
      // pindah ke blok kartu LAIN: tutup yang terbuka. (Membuka panel sebuah elemen lalu kembali tidak mengubah bid, jadi kartu tetap terbuka.)
      this.$watch(() => this.bid, () => {
        this.openCard = null
        this.styleOpen = null
      })
    },

    get bid() {
      const s = Alpine.store('editor')
      return s.parentId || s.id
    },
    get base() {
      return this.bid ? 'content.' + this.bid : null
    },
    get cards() {
      const c = this.base && this.$wire.$get(this.base + '.data.cards')
      return Array.isArray(c) ? c : []
    },
    columns(card) {
      const c = card && card.layout && card.layout.children
      return Array.isArray(c) ? c : []
    },
    elements(col) {
      const c = col && col.children
      return Array.isArray(c) ? c : []
    },

    // Aksi server: argumen pertama selalu id blok
    act(method, ...args) {
      if (!this.bid) return Promise.resolve()
      return this.$wire[method](this.bid, ...args)
    },

    toggleCard(ci) {
      this.openCard = this.openCard === ci ? null : ci
      this.styleOpen = null
    },
    async addCard(preset) {
      if (this.cards.length >= this.limits.cards) return
      await this.act('addCardItem', preset)
      this.openCard = this.cards.length - 1
    },
    async moveCard(ci, d) {
      const j = ci + d
      if (j < 0 || j >= this.cards.length) return
      if (this.openCard === ci) this.openCard = j
      else if (this.openCard === j) this.openCard = ci
      this.styleOpen = null
      await this.act('moveCard', ci, d)
    },
    async duplicateCard(ci) {
      if (this.cards.length >= this.limits.cards) return
      await this.act('duplicateCardItem', ci)
      this.openCard = ci + 1
    },
    removeCard(ci) {
      const card = this.cards[ci]
      const filled = this.columns(card).some((c) => this.elements(c).length > 0)
      if (filled && !window.confirm('Hapus kartu ini beserta isinya?')) return
      this.openCard = null
      this.styleOpen = null
      return this.act('removeCardItem', ci)
    },
    removeColumn(ci, co, col) {
      if (!col || !col.id) return
      if (this.elements(col).length && !window.confirm('Hapus kolom ini beserta isinya?')) return
      return this.act('removeColumnFromCard', ci, col.id)
    },
    removeElement(ci, col, el) {
      if (!col || !col.id || !el || !el.id) return
      return this.act('removeElementFromColumn', ci, col.id, el.id)
    },
    addElement(ci, col, type) {
      if (!type || !col || !col.id) return
      if (this.elements(col).length >= this.limits.elements) return
      return this.act('addCardElementAt', ci, col.id, type)
    },

    hasPanel(el) {
      return !!(el && this.labels[el.elementType] && this.labels[el.elementType].panel)
    },
    typeLabel(el) {
      const t = el && el.elementType
      return (this.labels[t] && this.labels[t].label) || t || '?'
    },
    focusEl(ci, co, ei, el) {
      if (!this.hasPanel(el) || !this.bid) return
      Alpine.store('editor').focusElement(
        'content.' + this.bid + '.data.cards.' + ci + '.layout.children.' + co + '.children.' + ei,
        el.elementType,
        this.bid,
      )
    },

    // Ringkasan satu baris: teks (bahasa halaman, atau bahasa mana pun yang terisi) tanpa tag; elemen tanpa teks: nama isinya (mis. ikon)
    // Bentuk isi: teks = peta bahasa {id,en} langsung di data.content; tombol = {label:{id,en}, url}; ikon = {icon}; akordeon = {question,answer}
    elementSummary(el) {
      const d = el && el.data ? el.data.content : null
      const loc = document.documentElement.lang || 'id'
      const pick = (v) => {
        if (typeof v === 'string') return v
        if (v && typeof v === 'object') return v[loc] || Object.values(v).find((x) => typeof x === 'string' && x.trim()) || ''
        return ''
      }
      let raw = ''
      if (d && typeof d === 'object') {
        raw = pick(d.text) || pick(d.label) || pick(d.title) || pick(d.question) || pick(d)
      } else {
        raw = pick(d)
      }
      const text = String(raw).replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim()
      return text ? text.slice(0, 60) : '(kosong)'
    },
    cardSummary(card) {
      const cols = this.columns(card)
      for (const c of cols) {
        for (const el of this.elements(c)) {
          if (el.elementType === 'text') {
            const t = this.elementSummary(el)
            if (t !== '(kosong)') return t
          }
        }
      }
      const n = cols.reduce((a, c) => a + this.elements(c).length, 0)
      return cols.length + ' kolom, ' + n + ' elemen'
    },
  }))

  /**
   * Kisi isi blok Tabel (<x-editor.table-grid>). Data di state Livewire: content.{id}.data.columns / .rows. Mengetik di sel = $set satu
   * path (dikirim ke server setelah berhenti mengetik); perubahan struktur (tambah, geser, hapus, tempel) = operasi murni tableOps lalu
   * dua $set (kolom tanpa kirim, baris dengan kirim). DOM dikunci oleh INDEKS, jadi geser hanya mengganti nilai.
   */
  Alpine.data('tablePanel', (locales = ['id', 'en']) => ({
    locales,
    lang: locales[0] || 'id',
    pasteOpen: false,
    pasteText: '',
    headerFirst: true,
    note: '',
    _t: {},

    init() {
      const follow = () => {
        const l = Alpine.store('editor').lang
        if (locales.includes(l)) this.lang = l
      }
      follow()
      this.$watch(() => Alpine.store('editor').lang, follow)
      // pindah ke blok lain: tutup kotak tempel, hapus catatan
      this.$watch(() => Alpine.store('editor').base, () => {
        this.pasteOpen = false
        this.pasteText = ''
        this.note = ''
      })
    },

    get base() {
      const b = Alpine.store('editor').base
      return b && /^content\.[^.]+$/.test(b) ? b : null
    },
    get columns() {
      const c = this.base && this.$wire.$get(this.base + '.data.columns')
      return Array.isArray(c) ? c : []
    },
    get rows() {
      const r = this.base && this.$wire.$get(this.base + '.data.rows')
      return Array.isArray(r) ? r : []
    },
    get table() {
      return { columns: this.columns, rows: this.rows }
    },
    get canAddColumn() {
      return this.columns.length < TABLE_LIMITS.columns
    },
    get canAddRow() {
      return this.rows.length < TABLE_LIMITS.rows
    },
    langLabel(l) {
      return String(l).toUpperCase()
    },

    // ---- nilai
    head(ci) {
      const c = this.columns[ci]
      return (c && c.label && c.label[this.lang]) || ''
    },
    cell(ri, ci) {
      const r = this.rows[ri]
      const c = r && Array.isArray(r.cells) ? r.cells[ci] : null
      return (c && c[this.lang]) || ''
    },
    alignOf(ci) {
      const c = this.columns[ci]
      return (c && c.align) || 'left'
    },
    alignTitle(ci) {
      return { left: 'Rata kiri', center: 'Rata tengah', right: 'Rata kanan' }[this.alignOf(ci)] + ' (klik untuk mengganti)'
    },

    // ---- mengetik
    _type(path, val) {
      this.$wire.$set(path, val, false)
      clearTimeout(this._t[path])
      this._t[path] = setTimeout(() => {
        // kirim apa yang SEKARANG ada di path itu; bila sel sudah dipindah/dihapus, batalkan (path berbasis indeks)
        const now = this.$wire.$get(path)
        if (now !== undefined) this.$wire.$set(path, now, true)
      }, 800)
    },
    typeHead(ci, val) {
      if (this.base) this._type(`${this.base}.data.columns.${ci}.label.${this.lang}`, val)
    },
    typeCell(ri, ci, val) {
      if (this.base) this._type(`${this.base}.data.rows.${ri}.cells.${ci}.${this.lang}`, val)
    },

    // ---- struktur
    commit(t) {
      if (!this.base) return
      for (const k of Object.keys(this._t)) clearTimeout(this._t[k])
      this._t = {}
      this.$wire.$set(this.base + '.data.columns', t.columns, false)
      this.$wire.$set(this.base + '.data.rows', t.rows, true)
    },
    addColumn() {
      this.commit(tableOps.addColumn(this.table, locales, uid))
    },
    addRow() {
      this.commit(tableOps.addRow(this.table, locales, uid))
    },
    moveColumn(ci, d) {
      this.commit(tableOps.moveColumn(this.table, ci, d, locales))
    },
    moveRow(ri, d) {
      this.commit(tableOps.moveRow(this.table, ri, d, locales))
    },
    cycleAlign(ci) {
      this.commit(tableOps.cycleAlign(this.table, ci, locales))
    },
    removeColumn(ci) {
      if (this.columns.length <= 1) return
      const probe = { columns: [this.columns[ci]], rows: this.rows.map((r) => ({ cells: [r.cells && r.cells[ci]] })) }
      if (tableOps.hasText(probe) && !window.confirm('Hapus kolom ini beserta isinya?')) return
      this.commit(tableOps.removeColumn(this.table, ci, locales))
    },
    removeRow(ri) {
      const probe = { columns: [], rows: [this.rows[ri]] }
      if (tableOps.hasText(probe) && !window.confirm('Hapus baris ini beserta isinya?')) return
      this.commit(tableOps.removeRow(this.table, ri, locales))
    },

    // ---- tempel dari spreadsheet
    pasteCell(e, ri, ci) {
      const t = (e.clipboardData || window.clipboardData)?.getData('text') || ''
      if (!/[\t\n]/.test(t.replace(/\r?\n$/, ''))) return // tempel biasa: biarkan peramban
      e.preventDefault()
      const { table, clipped } = tableOps.fillMatrix(this.table, parseTsv(t), ri, ci, this.lang, locales, uid)
      this.note = clipped ? `Sebagian tidak muat (maksimal ${TABLE_LIMITS.columns} kolom, ${TABLE_LIMITS.rows} baris).` : ''
      this.commit(table)
    },
    applyImport() {
      const m = parseTsv(this.pasteText)
      if (!m.length) return
      if (tableOps.hasText(this.table) && !window.confirm('Isi tabel sekarang akan diganti. Lanjutkan?')) return
      const { table, clipped } = tableOps.replaceWithMatrix(m, this.headerFirst, this.lang, locales, uid)
      this.note = clipped ? `Sebagian tidak muat (maksimal ${TABLE_LIMITS.columns} kolom, ${TABLE_LIMITS.rows} baris).` : `Terisi untuk bahasa ${this.langLabel(this.lang)}. Isi bahasa lain kosong.`
      this.commit(table)
      this.pasteOpen = false
      this.pasteText = ''
    },
  }))

  // Dipakai komponen lain yang perlu membaca/menulis path yang sama (mis. registerRich di rich-field.js)
  return { wireFieldFactory, extend }
}
