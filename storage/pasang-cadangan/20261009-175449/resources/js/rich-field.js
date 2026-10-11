/**
 * Teks kaya (Tiptap) di inspektur: Fase 3 (rilis 31). Dipakai oleh <x-editor.rich> (heading dan paragraf).
 *
 * Pemasangan (resources/js/app.js), SETELAH registerEditor:
 *   import { SharedExtensions } from './mikro-tiptap.js'
 *   import { registerEditor } from './editor'
 *   import { registerRich } from './rich-field'
 *   document.addEventListener('alpine:init', () => {
 *     const api = registerEditor(window.Alpine)
 *     registerRich(window.Alpine, { ...api, extensions: SharedExtensions })
 *   })
 *
 * Prinsip
 *  1. SATU Tiptap AKTIF per bahasa. Editor dibuat hanya saat panel jenis blok itu tampil dan sebuah blok difokus; dihancurkan saat
 *     fokus pindah atau panel ditutup. Tidak ada ratusan instance (editor lama: dua per blok), dan riwayat urung (Ctrl+Z) tidak
 *     pernah bocor dari satu blok ke blok lain karena setiap pemasangan memulai riwayat baru.
 *  2. Data tidak boleh berubah hanya karena dibuka. Tulis ke state HANYA saat pengguna mengubah isi (onUpdate). Bila HTML yang tersimpan
 *     tidak bisa diwakili Tiptap tanpa kehilangan isi (tabel, gambar, judul h2 di dalam paragraf, ...), editor TIDAK dipasang: tampil
 *     hanya-baca dengan tombol "Sunting juga" yang meminta konfirmasi. Perbandingan memakai bentuk kanonik (canonHtml): tambahan
 *     kelas/rel/target yang diberikan Tiptap sendiri tidak dianggap perubahan.
 *  3. Heading = satu baris. Dokumen Tiptap-nya hanya boleh berisi SATU paragraf; yang disimpan adalah isi <p> tanpa pembungkus
 *     (sama dengan bentuk data lama). Paragraf = HTML Tiptap apa adanya; kosong disimpan sebagai '' (bukan "<p></p>").
 *  4. Penulisan memakai typeAt() milik wireField: state klien langsung, kirim ke server setelah berhenti mengetik (live) atau tidak sama
 *     sekali (defer_style_sync).
 */
import { Editor, Extension, Node } from '@tiptap/core'

const BLOCK_START = /^\s*<(?:p|ul|ol|blockquote|pre|h[1-6]|table|div|hr|figure)[\s>/]/i

/** Nilai tersimpan -> HTML untuk Tiptap. Teks polos / sebaris dibungkus <p>; kosong tetap ''. */
export function toEditorHtml(value) {
  const v = String(value ?? '')
  if (v.trim() === '') return ''
  return BLOCK_START.test(v) ? v : '<p>' + v + '</p>'
}

/** Mode satu baris: lepas <p> pembungkus (dokumen hanya berisi satu paragraf). */
export function unwrapParagraph(html) {
  const m = /^<p(?:\s[^>]*)?>([\s\S]*)<\/p>$/.exec(String(html ?? '').trim())
  return m ? m[1] : String(html ?? '')
}

/**
 * Bentuk kanonik HTML untuk membandingkan "isi yang sama": tag disatukan (b=strong, i=em, strike/del=s), hanya atribut yang
 * bermakna (href pada a; style per properti, terurut), spasi dilipat, span tanpa atribut dilepas, <br /> = <br>. Teks sebaris
 * diratakan (urutan penanda tidak penting) dan <li><p>x</p></li> = <li>x</li>. Kelas, target, rel, dan data-* diabaikan: itulah
 * yang ditambahkan Tiptap sendiri saat menyimpan.
 */
export function canonHtml(html) {
  const doc = new DOMParser().parseFromString('<body>' + String(html ?? '') + '</body>', 'text/html')
  const ALIAS = { b: 'strong', i: 'em', strike: 's', del: 's' }
  const INLINE = new Set(['strong', 'em', 's', 'u', 'a', 'span', 'code', 'sub', 'sup', 'mark'])
  const css = (text) =>
    text
      .split(';')
      .map((d) => d.trim())
      .filter(Boolean)
      .map((d) => {
        const i = d.indexOf(':')
        if (i < 0) return ''
        return d.slice(0, i).trim().toLowerCase() + ':' + d.slice(i + 1).trim().replace(/\s+/g, ' ').replace(/\s*,\s*/g, ',').toLowerCase()
      })
      .filter(Boolean)
      .sort()
      .join(';')
  const esc = (t) => t.replace(/&/g, '&amp;').replace(/</g, '&lt;')

  // Teks sebaris diratakan menjadi larik "potongan teks + himpunan penanda", lalu potongan bertetangga dengan penanda sama digabung.
  // Dengan begitu <strong><a>x</a></strong> dan <a><strong>x</strong></a> (urutan yang dipilih Tiptap) dianggap sama.
  const runs = (n, marks, out) => {
    if (n.nodeType === 3) {
      const t = n.nodeValue.replace(/\s+/g, ' ')
      if (t !== '') out.push({ t, m: marks })
      return
    }
    if (n.nodeType !== 1) return
    let tag = n.tagName.toLowerCase()
    tag = ALIAS[tag] || tag
    if (tag === 'br') return void out.push({ br: true })
    let m = marks
    if (INLINE.has(tag)) {
      m = { ...marks }
      if (tag === 'a') m.a = n.getAttribute('href') || ''
      else if (tag === 'span') {
        const st = n.getAttribute('style') ? css(n.getAttribute('style')) : ''
        if (st) m.span = [m.span, st].filter(Boolean).join(';')
      } else m[tag] = true
    }
    Array.from(n.childNodes).forEach((c) => runs(c, m, out))
  }
  const ORDER = ['a', 'strong', 'em', 's', 'u', 'code', 'sub', 'sup', 'mark', 'span']
  const renderRuns = (list) => {
    const merged = []
    for (const r of list) {
      const last = merged[merged.length - 1]
      const k = r.br ? 'br' : JSON.stringify(ORDER.map((o) => r.m[o] ?? null))
      if (last && !r.br && last.k === k) last.t += r.t
      else merged.push({ ...r, k })
    }
    return merged
      .map((r) => {
        if (r.br) return '<br>'
        let o = esc(r.t)
        for (const name of [...ORDER].reverse()) {
          const v = r.m[name]
          if (v === undefined) continue
          if (name === 'a') o = '<a href="' + v + '">' + o + '</a>'
          else if (name === 'span') o = '<span style="' + css(v) + '">' + o + '</span>'
          else o = '<' + name + '>' + o + '</' + name + '>'
        }
        return o
      })
      .join('')
  }
  const block = (n) => {
    if (n.nodeType === 3) return renderRuns((() => { const o = []; runs(n, {}, o); return o })())
    if (n.nodeType !== 1) return ''
    let tag = n.tagName.toLowerCase()
    tag = ALIAS[tag] || tag
    if (INLINE.has(tag) || tag === 'br') return renderRuns((() => { const o = []; runs(n, {}, o); return o })())
    let kids = Array.from(n.childNodes)
    // Tiptap membungkus isi <li> dengan <p>; <li><p>x</p></li> dan <li>x</li> adalah isi yang sama.
    if (tag === 'li') {
      const els = kids.filter((k) => k.nodeType === 1 || (k.nodeType === 3 && k.nodeValue.trim() !== ''))
      if (els.length === 1 && els[0].nodeType === 1 && els[0].tagName.toLowerCase() === 'p') kids = Array.from(els[0].childNodes)
    }
    // anak sebaris berurutan dikumpulkan jadi satu rangkaian; anak blok dirender sendiri
    let o = ''
    let buf = []
    const flush = () => {
      if (buf.length) o += renderRuns(buf)
      buf = []
    }
    for (const c of kids) {
      const isInline = c.nodeType === 3 || (c.nodeType === 1 && (INLINE.has(ALIAS[c.tagName.toLowerCase()] || c.tagName.toLowerCase()) || c.tagName.toLowerCase() === 'br'))
      if (isInline) runs(c, {}, buf)
      else {
        flush()
        o += block(c)
      }
    }
    flush()
    // gaya pada blok (rata teks, indentasi) bermakna: bila Tiptap membuangnya, isi dianggap berubah
    const bs = n.getAttribute('style') ? css(n.getAttribute('style')) : ''
    return '<' + tag + (bs ? ' style="' + bs + '"' : '') + '>' + o + '</' + tag + '>'
  }
  const top = []
  let outStr = ''
  for (const c of Array.from(doc.body.childNodes)) {
    const isInline = c.nodeType === 3 || (c.nodeType === 1 && (INLINE.has(ALIAS[c.tagName.toLowerCase()] || c.tagName.toLowerCase()) || c.tagName.toLowerCase() === 'br'))
    if (isInline) runs(c, {}, top)
    else {
      if (top.length) outStr += renderRuns(top.splice(0))
      outStr += block(c)
    }
  }
  if (top.length) outStr += renderRuns(top)
  return outStr
    .replace(/>\s+</g, '><')
    .replace(/(<(?:p|li|blockquote|td|th)>)\s+/g, '$1')
    .replace(/\s+(<\/(?:p|li|blockquote|td|th)>)/g, '$1')
    .trim()
}

/**
 * Penyesuaian ekstensi Link untuk data kita (tanpa perlu mengubah mikro-tiptap.js):
 *  - protocols ['internal']: href internal://page|article/{slug} tidak dibuang Tiptap;
 *  - target/rel tanpa nilai bawaan: tautan yang SUDAH ada (tanpa target) tidak mendadak dibuka di tab baru hanya karena disunting.
 *    Tautan luar baru diberi target/rel eksplisit oleh saveLink().
 */
export function prepareExtensions(exts) {
  return exts.map((e) =>
    e.name === 'starterKit'
      ? e.configure({ trailingNode: false }) // v3 menambah <p> kosong di akhir dokumen bila blok terakhir daftar/tabel: jangan ikut tersimpan
      : e.name === 'link'
      ? e
          .extend({
            addAttributes() {
              return { ...this.parent?.(), target: { default: null }, rel: { default: null } }
            },
          })
          .configure({ protocols: ['internal'] })
      : e,
  )
}

/** Dokumen satu paragraf + Enter dimatikan: bentuk "judul satu baris". */
const SingleDoc = Node.create({ name: 'doc', topNode: true, content: 'paragraph' })
const NoNewline = Extension.create({
  name: 'noNewline',
  priority: 1000,
  addKeyboardShortcuts() {
    return { Enter: () => true, 'Shift-Enter': () => true, 'Mod-Enter': () => true }
  },
})

/** Daftar ekstensi mode satu baris dari daftar bersama: tanpa rata teks/indentasi (hilang saat <p> dilepas), dokumen satu paragraf. */
export function singleLineExtensions(exts) {
  const dropped = ['textAlign', 'paragraphIndent']
  return [
    ...exts.filter((e) => !dropped.includes(e.name)).map((e) => (e.name === 'starterKit' ? e.configure({ document: false }) : e)),
    SingleDoc,
    NoNewline,
  ]
}

export function registerRich(Alpine, { wireFieldFactory, extend, extensions }) {
  /**
   * @param fixed  path wire lengkap (atau null)
   * @param rel    path relatif terhadap node yang difokus, mis. "data.text" (atau ungkapan JS di dalam daftar berulang)
   * @param lang   kode bahasa; teks ada di <rel>.<lang>
   * @param multi  true = paragraf (banyak blok), false = judul (satu baris)
   * @param live   kirim ke server setelah berhenti mengetik
   * @param look   {font,size,color,label,classes}: tampilan di kotak editor, diteruskan ke toolbar
   */
  Alpine.data('richField', (fixed = null, rel = null, lang = 'id', multi = false, live = true, look = {}) => {
    let editor = null // non-reaktif: Alpine tidak boleh membungkus Tiptap dengan proxy
    let silent = false // true saat kita sendiri yang mengisi editor: onUpdate diabaikan
    const base = prepareExtensions(extensions)
    const exts = multi ? base : singleLineExtensions(base)

    return extend(wireFieldFactory(fixed, rel, null, live), {
      lang,
      multi,
      single: !multi,
      mode: 'off', // 'off' (tak aktif) | 'edit' (Tiptap terpasang) | 'locked' (hanya-baca: Tiptap akan mengubah isi)
      force: false,
      key: '',
      updatedAt: Date.now(),

      // dibaca toolbar
      baseFontFamily: look.font || 'default',
      baseFontSize: look.size || 'default',
      baseFontColor: look.color || '#000000',
      labelUkuran: look.label || 'Bawaan Blok',

      // dialog tautan
      showLinkModal: false,
      linkTab: 'url', // 'url' | 'page' | 'article'
      linkUrl: '',
      linkQuery: '',
      linkResults: [],
      linkBusy: false,
      _seq: 0,

      // ------------------------------------------------------------------ siklus hidup
      get active() {
        if (!this.p) return false
        const st = Alpine.store('editor')
        if (!st.showLang(lang)) return false
        const sec = this.$el.closest('[data-panel]')
        return sec ? sec.dataset.panel === st.panel : true
      },
      init() {
        // pindah fokus / ganti panel / ganti tampilan bahasa -> pasang ulang (riwayat baru)
        this.$watch(
          () => (this.active ? this.p : ''),
          () => this.$nextTick(() => this.sync()),
        )
        // nilai berubah dari luar (muat ulang server, urung di tempat lain) -> perbarui editor
        this.$watch(
          () => this.vAt(lang),
          (v) => this.external(v),
        )
        this.$nextTick(() => this.sync())
      },
      destroy() {
        this.unmount()
      },
      sync() {
        const key = this.active ? this.p : ''
        if (key === this.key) return
        this.key = key
        this.force = false
        this.unmount()
        if (key) this.mount()
      },
      mount() {
        const host = this.$refs.editorElement
        if (!host || editor) return
        const html = toEditorHtml(this.vAt(lang))
        silent = true
        try {
          editor = new Editor({
            element: host,
            extensions: exts,
            content: html,
            editorProps: {
              attributes: { class: `focus:outline-none min-h-[40px] ${look.classes || ''}`, style: `color: ${look.color || 'inherit'};` },
            },
            onFocus: () => {
              window.activeTiptapEditor = editor // dipakai pendengar global 'insert-link-to-active-editor'
            },
            onUpdate: () => {
              if (silent) return
              this.typeAt(lang, this.outHtml())
            },
            onTransaction: () => {
              this.updatedAt = Date.now()
            },
          })
        } finally {
          silent = false
        }
        // Tiptap akan mengubah isi bila bentuk kanoniknya berbeda (tabel, gambar, h2, skema tak dikenal): jangan dipasang.
        if (!this.force && html !== '' && canonHtml(editor.getHTML()) !== canonHtml(html)) {
          editor.destroy()
          editor = null
          this.mode = 'locked'
          return
        }
        this.mode = 'edit'
      },
      unmount() {
        if (editor) {
          if (window.activeTiptapEditor === editor) window.activeTiptapEditor = null
          editor.destroy()
          editor = null
        }
        this.mode = 'off'
        this.showLinkModal = false
      },
      forceEdit() {
        if (!window.confirm('Format khusus di teks ini (mis. tabel, gambar, atau judul) bisa hilang saat disunting. Lanjutkan?')) return
        this.force = true
        this.unmount()
        this.mount()
      },
      get lockedText() {
        return String(this.vAt(lang)).replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim() || '—'
      },

      // ------------------------------------------------------------------ data <-> editor
      outHtml() {
        if (!editor || editor.isEmpty) return ''
        const h = editor.getHTML()
        return multi ? h : unwrapParagraph(h)
      },
      external(v) {
        if (!editor || silent) return
        const cur = this.outHtml()
        if (cur === String(v ?? '') || canonHtml(cur) === canonHtml(String(v ?? ''))) return // gema dari ketikan sendiri (jangan reset kursor)
        silent = true
        try {
          editor.commands.setContent(toEditorHtml(v), false)
        } finally {
          silent = false
        }
      },
      getEditor() {
        return editor
      },

      // ------------------------------------------------------------------ API toolbar (sama dengan editor lama)
      runCommand(command, args = null) {
        if (!editor) return
        try {
          const c = editor.chain().focus()
          if (command === 'setColor') c.setColor(args).run()
          else if (command === 'unsetColor') c.unsetColor().run()
          else if (command === 'setTextAlign') c.setTextAlign(typeof args === 'object' && args ? args.textAlign : args).run()
          else if (args !== null) c[command](args).run()
          else c[command]().run()
        } catch (e) {
          console.warn(`Gagal menjalankan perintah Tiptap: ${command}`, e)
        }
        this.updatedAt = Date.now()
      },
      checkButtonActive(name, params = {}, type = 'default') {
        const t = this.updatedAt // dibaca LEBIH DULU: tanpa itu Alpine tidak melacak ketergantungan saat editor belum ada, dan tombol tak pernah menyala
        if (!editor || !(t > 0)) return false
        if (type === 'textAlign') {
          const cur = editor.getAttributes('paragraph').textAlign
          return cur ? cur === params.textAlign : params.textAlign === 'left'
        }
        return Object.keys(params || {}).length === 0 ? editor.isActive(name) : editor.isActive(name, params)
      },
      isActive(type, opts = {}) {
        this.updatedAt
        return editor ? editor.isActive(type, opts) : false
      },
      changeFontFamily(name) {
        if (!editor) return
        if (name === 'default') editor.chain().focus().unsetFontFamily().run()
        else editor.chain().focus().setFontFamily(name.includes(' ') && !/^["']/.test(name) ? `"${name}"` : name).run()
        this.updatedAt = Date.now()
      },
      getCurrentFont() {
        this.updatedAt
        if (!editor) return 'default'
        const f = editor.getAttributes('textStyle').fontFamily
        return f ? f.replace(/['"]/g, '').trim() : 'default'
      },
      setFontSize(size) {
        if (!editor) return
        if (size === 'default') editor.chain().focus().unsetFontSize().run()
        else editor.chain().focus().setFontSize(size).run()
        this.updatedAt = Date.now()
      },
      getCurrentFontSize() {
        this.updatedAt
        return (editor && editor.getAttributes('textStyle').fontSize) || 'default'
      },
      setFontWeight(weight) {
        if (!editor) return
        if (weight === 'default') editor.chain().focus().unsetFontWeight().run()
        else editor.chain().focus().setFontWeight(weight).run()
        this.updatedAt = Date.now()
      },
      getCurrentFontWeight() {
        this.updatedAt
        return (editor && editor.getAttributes('textStyle').fontWeight) || 'default'
      },
      getCurrentColor() {
        this.updatedAt
        return (editor && editor.getAttributes('textStyle').color) || this.baseFontColor
      },

      // ------------------------------------------------------------------ tautan
      get hasLink() {
        this.updatedAt
        return !!editor && editor.isActive('link')
      },
      // Nama dipertahankan: tombol "Tautan" di toolbar memanggilnya.
      openInternalLinkModal() {
        if (!editor) return
        window.activeTiptapEditor = editor
        const cur = editor.getAttributes('link').href || ''
        const m = /^internal:\/\/(page|article)\//.exec(cur)
        this.linkTab = m ? m[1] : 'url'
        this.linkUrl = m ? '' : cur
        this.linkQuery = ''
        this.linkResults = []
        this.showLinkModal = true
      },
      cancelLink() {
        this.showLinkModal = false
      },
      setTab(tab) {
        this.linkTab = tab
        this.linkQuery = ''
        this.linkResults = []
      },
      // Tujuan luar: https://…, mailto:, tel:, #anchor, atau jalur /…; tanpa skema -> https://
      normalizeUrl(raw) {
        const u = String(raw ?? '').trim()
        if (u === '') return ''
        if (/^(https?:\/\/|mailto:|tel:|#|\/(?!\/))/i.test(u)) return u
        if (/^[a-z][a-z0-9+.-]*:/i.test(u) || u.startsWith('//')) return '' // skema lain (javascript:, data:, ...) ditolak
        return 'https://' + u
      },
      saveLink() {
        if (!editor) return
        const raw = this.linkUrl.trim()
        if (raw === '') {
          editor.chain().focus().extendMarkRange('link').unsetLink().run()
          this.showLinkModal = false
          return
        }
        const href = this.normalizeUrl(raw)
        if (href === '') return
        this.applyLink(/^https?:\/\//i.test(href) ? { href, target: '_blank', rel: 'noopener noreferrer nofollow' } : { href }, href)
      },
      removeLink() {
        if (editor) editor.chain().focus().extendMarkRange('link').unsetLink().run()
        this.showLinkModal = false
      },
      // Halaman/artikel: simpan sebagai internal://page|article/{slug}; alamat akhir mengikuti bahasa pembaca (LinkResolver)
      pickInternal(r) {
        if (!r || !r.slug) return
        this.applyLink({ href: `internal://${this.linkTab}/${r.slug}`, target: null, rel: null }, r.label)
      },
      applyLink(attrs, text) {
        if (!editor) return
        const empty = editor.state.selection.empty
        const c = editor.chain().focus()
        if (empty && !editor.isActive('link')) {
          c.insertContent({ type: 'text', text: text || attrs.href, marks: [{ type: 'link', attrs }] }).run()
        } else {
          c.extendMarkRange('link').setLink(attrs).run()
        }
        this.showLinkModal = false
      },
      async searchLinks() {
        const q = this.linkQuery.trim()
        if (this.linkTab === 'url' || q.length < 2) {
          this.linkResults = []
          return
        }
        const seq = ++this._seq // abaikan jawaban lama bila pengguna sudah mengetik lagi
        this.linkBusy = true
        try {
          const r = await this.$wire.searchLinkTargets(this.linkTab, q)
          if (seq === this._seq) this.linkResults = Array.isArray(r) ? r : []
        } catch (e) {
          if (seq === this._seq) this.linkResults = []
        } finally {
          if (seq === this._seq) this.linkBusy = false
        }
      },
    })
  })
}
