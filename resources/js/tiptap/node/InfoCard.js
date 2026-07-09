// InfoCard.js
// Pola: CONTENT-BASED NODE — dipakai kalau isi kartu adalah rich text
// (judul + paragraf) yang tetap perlu bisa diedit bebas oleh pengguna.
// Mewakili gaya ".partner-card" / ".program-card" di halaman Malaria:
// ikon + label kecil (tag) di header, lalu judul & deskripsi di bawahnya.

import { Node, mergeAttributes } from '@tiptap/core'

// Kumpulan ikon inline SVG - dipakai bareng di NodeView (tampilan editor)
// maupun renderHTML (HTML final yang disimpan/ditampilkan ke publik),
// supaya WYSIWYG: apa yang diedit = persis apa yang tampil di halaman.
const ICONS = {
  building: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21h16M5 21V9l7-5 7 5v12M9 21v-6h6v6"/></svg>',
  graduation: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12.5V17c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5"/></svg>',
  heart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.6-9.3-9.2C1.4 8.6 3 5 6.6 5c2 0 3.4 1.1 4.4 2.6C12 6.1 13.4 5 15.4 5 19 5 20.6 8.6 19.3 11.8 17 16.4 12 21 12 21z"/></svg>',
  shield: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z"/><path d="M9 12l2 2 4-4"/></svg>',
}

export const InfoCard = Node.create({
  name: 'infoCard',
  group: 'block',
  content: 'block+', // isinya boleh heading + paragraf, bebas diedit seperti biasa
  draggable: true,

  addAttributes() {
    return {
      icon: { default: 'building' },
      tag: { default: 'Label' },
      color: { default: 'forest' }, // forest | gold | coral - dipetakan ke CSS var yang sama dgn situs
    }
  },

  parseHTML() {
    return [
      {
        tag: 'div[data-type="info-card"]',
        // Supaya konten yang sudah tersimpan bisa dibuka & diedit lagi,
        // bukan cuma sekali tulis lalu jadi HTML mati.
        getAttrs: (dom) => ({
          icon: dom.dataset.icon || 'building',
          color: dom.dataset.color || 'forest',
          tag: dom.querySelector('.tag')?.textContent || '',
        }),
      },
    ]
  },

  renderHTML({ HTMLAttributes, node }) {
    return [
      'div',
      mergeAttributes(HTMLAttributes, {
        'data-type': 'info-card',
        class: 'partner-card',
        'data-color': node.attrs.color,
        'data-icon': node.attrs.icon,
      }),
      [
        'div',
        { class: 'partner-icon-row', contenteditable: 'false' },
        ['span', { class: 'partner-icon', innerHTML: ICONS[node.attrs.icon] }],
        ['span', { class: 'tag' }, node.attrs.tag],
      ],
      ['div', { class: 'partner-card-body' }, 0], // "0" = lubang tempat content (heading/paragraf) dirender
    ]
  },

  addNodeView() {
    return ({ node, editor, getPos }) => {
      const dom = document.createElement('div')
      dom.className = 'partner-card'
      dom.dataset.color = node.attrs.color
      dom.dataset.icon = node.attrs.icon

      const header = document.createElement('div')
      header.className = 'partner-icon-row'
      header.contentEditable = 'false'

      const iconEl = document.createElement('span')
      iconEl.className = 'partner-icon'
      iconEl.innerHTML = ICONS[node.attrs.icon]

      const tagEl = document.createElement('span')
      tagEl.className = 'tag'
      tagEl.textContent = node.attrs.tag
      tagEl.contentEditable = 'true'
      tagEl.addEventListener('blur', () => {
        if (typeof getPos === 'function') {
          editor.chain().setNodeSelection(getPos()).updateAttributes('infoCard', { tag: tagEl.textContent }).run()
        }
      })

      // Ganti ikon lewat klik - siklus sederhana antar 4 pilihan.
      iconEl.style.cursor = 'pointer'
      iconEl.title = 'Klik untuk ganti ikon'
      iconEl.addEventListener('click', () => {
        const keys = Object.keys(ICONS)
        const next = keys[(keys.indexOf(node.attrs.icon) + 1) % keys.length]
        if (typeof getPos === 'function') {
          editor.chain().setNodeSelection(getPos()).updateAttributes('infoCard', { icon: next }).run()
        }
      })

      header.append(iconEl, tagEl)

      const contentDOM = document.createElement('div')
      contentDOM.className = 'partner-card-body'

      dom.append(header, contentDOM)

      return { dom, contentDOM }
    }
  },

  addCommands() {
    return {
      setInfoCard:
        (attrs) =>
        ({ commands }) => {
          return commands.insertContent({
            type: this.name,
            attrs,
            content: [
              { type: 'heading', attrs: { level: 3 }, content: [{ type: 'text', text: 'Judul Kartu' }] },
              { type: 'paragraph', content: [{ type: 'text', text: 'Tulis deskripsi di sini...' }] },
            ],
          })
        },
    }
  },
})
