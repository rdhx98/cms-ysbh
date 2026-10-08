/**
 * Kanvas: panel pratinjau langsung di builder (<x-content.canvas>), terhubung ke bingkai (⚡canvas-frame) lewat postMessage.
 * Daftarkan sekali di app.js (setelah registerEditor):   import { registerCanvas } from './canvas'
 *                                                        document.addEventListener('alpine:init', () => registerCanvas(Alpine))
 *
 * Alur: isi builder berubah -> (jeda 0,7 dtk) $wire.publishPreview(token) menitipkan isi di cache -> pesan 'canvas-refresh' ke bingkai
 * -> bingkai render ulang dari cache. Klik blok di bingkai -> 'canvas-select' -> store editor memfokus blok (outline + inspektur).
 * Fokus berubah di editor -> 'canvas-focus' (sorot + gulir) ke bingkai.
 */
export function registerCanvas(Alpine) {
  const ID = /^[A-Za-z0-9_-]{1,64}$/
  const DEBOUNCE = 700

  Alpine.data('canvasPane', (cfg) => ({
    // Lebar jendela bingkai. "wide" (1600) memperlihatkan tata letak >= 1536 px, termasuk daftar isi (TOC) yang hanya muncul di lebar itu.
    widths: { desktop: '100%', tablet: '820px', phone: '390px', wide: '1600px' },
    device: 'desktop',
    paneW: 0, // lebar area panel (px), diukur oleh ResizeObserver
    src: '',
    token: null,
    status: 'idle', // idle | loading | ready | error
    error: '',
    frameLang: (cfg.locales && cfg.locales[0]) || 'id',
    ready: false, // bingkai sudah memuat dan siap menerima pesan
    inflight: false,
    queued: false,
    timer: null,
    fromCanvas: false, // fokus berasal dari klik di kanvas: jangan menggulir balik
    srcRev: 0,
    lastRev: 0,
    handler: null,

    // Bila lebar yang diminta (px) melebihi panel, bingkai TETAP berjendela selebar itu (titik putus Tailwind benar) lalu DIPERKECIL agar muat.
    get px() {
      const w = this.widths[this.device]
      return w.endsWith('px') ? parseFloat(w) : null
    },
    get scale() {
      const w = this.px
      return w && this.paneW > 0 && w > this.paneW ? this.paneW / w : 1
    },
    get boxWidth() {
      return this.scale < 1 ? '100%' : this.widths[this.device]
    },
    get frameStyle() {
      const k = this.scale
      return k < 1
        ? { width: this.px + 'px', height: 100 / k + '%', transform: 'scale(' + k + ')', transformOrigin: '0 0' }
        : { width: '100%', height: '100%' }
    },

    init() {
      const store = () => Alpine.store('editor')

      if (this.$refs.pane && typeof ResizeObserver !== 'undefined') {
        this._ro = new ResizeObserver((entries) => {
          this.paneW = Math.floor(entries[0].contentRect.width)
        })
        this._ro.observe(this.$refs.pane)
      }

      // tab bahasa tunggal di header -> pratinjau ikut bahasa itu; "Ganda" membiarkan pilihan "Lihat sebagai" apa adanya
      const follow = () => {
        const l = store().lang
        if (l !== 'both' && (cfg.locales || []).includes(l)) this.setLang(l)
      }
      follow()
      this.$watch(() => store().lang, follow)

      this.$watch(() => store().id, (id) => {
        const scroll = !this.fromCanvas
        this.fromCanvas = false
        this.sendFocus(id, scroll)
      })

      // isi blok / urutan / pengaturan berubah (dari kolom, aksi struktur, atau jawaban server)
      for (const prop of ['content', 'blockOrder', 'settings']) this.$wire.$watch(prop, () => this.schedule())

      this.handler = (e) => this.onMessage(e)
      window.addEventListener('message', this.handler)

      if (cfg.frameUrl) this.publish()
    },

    destroy() {
      if (this._ro) this._ro.disconnect()
      window.removeEventListener('message', this.handler)
      clearTimeout(this.timer)
    },

    urlFor() {
      return cfg.frameUrl.replace('__TOKEN__', this.token) + '?lang=' + encodeURIComponent(this.frameLang)
    },

    post(msg) {
      const w = this.$refs.frame && this.$refs.frame.contentWindow
      if (w) w.postMessage(msg, window.location.origin)
    },

    schedule(delay = DEBOUNCE) {
      if (!cfg.frameUrl) return
      this.status = 'loading'
      clearTimeout(this.timer)
      this.timer = setTimeout(() => this.publish(), delay)
    },

    // Menitipkan isi terbaru lalu menyuruh bingkai memperbarui. Permintaan yang datang saat satu masih berjalan digabung menjadi satu susulan.
    async publish() {
      if (!cfg.frameUrl) return
      clearTimeout(this.timer)
      if (this.inflight) {
        this.queued = true
        return
      }
      this.inflight = true
      this.status = 'loading'
      this.error = ''
      try {
        const r = await this.$wire.publishPreview(this.token)
        if (!r || r.error || !r.token) throw new Error((r && r.error) || 'Gagal memperbarui pratinjau')
        this.lastRev = r.rev
        if (r.token !== this.token) {
          // pertama kali, atau token lama kedaluwarsa/bukan milik kita: muat bingkai baru
          this.token = r.token
          this.ready = false
          this.srcRev = r.rev
          this.src = this.urlFor()
        } else if (this.ready) {
          this.post({ type: 'canvas-refresh' })
        }
        this.status = 'ready'
      } catch (e) {
        this.status = 'error'
        this.error = (e && e.message) || 'Gagal memperbarui pratinjau'
      } finally {
        this.inflight = false
        if (this.queued) {
          this.queued = false
          this.schedule(0)
        }
      }
    },

    setLang(l) {
      if (this.frameLang === l) return
      this.frameLang = l
      this.post({ type: 'change-lang', lang: l })
    },

    setDevice(d) {
      if (this.widths[d]) this.device = d
    },

    sendFocus(id, scroll) {
      this.post({ type: 'canvas-focus', id: typeof id === 'string' ? id : null, scroll: !!scroll })
    },

    onMessage(e) {
      // hanya dari bingkai kita sendiri, asal yang sama
      const frame = this.$refs.frame
      if (e.origin !== window.location.origin || !frame || e.source !== frame.contentWindow) return
      const m = e.data
      if (!m || typeof m !== 'object') return

      if (m.type === 'canvas-ready') {
        this.ready = true
        this.sendFocus(Alpine.store('editor').id, true)
        // ada perubahan yang terjadi selagi bingkai memuat: susul
        if (this.lastRev > this.srcRev) this.post({ type: 'canvas-refresh' })
      } else if (m.type === 'canvas-select') {
        if (typeof m.id !== 'string' || !ID.test(m.id)) return
        const type = this.$wire.$get('content.' + m.id + '.type') // jenis dari data, bukan dari pesan
        if (!type) return
        this.fromCanvas = true
        Alpine.store('editor').focusBlock(m.id, type)
      } else if (m.type === 'canvas-expired') {
        this.token = null
        this.publish()
      }
    },

    // Tab baru: dibuka sinkron (agar tidak diblokir pop-up), alamatnya diisi setelah isi terbaru dititipkan
    async openTab() {
      if (!cfg.frameUrl) return
      const w = window.open('about:blank', '_blank')
      await this.publish()
      if (w && this.token) w.location.href = this.urlFor()
      else if (w) w.close()
    },
  }))
}
