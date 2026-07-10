import { Node, mergeAttributes } from '@tiptap/core'

export const SectionBlock = Node.create({
  name: 'sectionBlock',
  group: 'block',
  content: 'block+', // Mengizinkan elemen apapun (teks, gambar, kartu) masuk ke dalamnya

  addAttributes() {
    return {
      bgColor: { default: '#f3f4f6' } // Default abu-abu terang
    }
  },

  parseHTML() {
    return [{ tag: 'div[data-type="section-block"]' }]
  },

  // HTML yang dirender untuk pengunjung website (Frontend Publik)
  renderHTML({ HTMLAttributes }) {
    return [
      'div',
      // Class 'full-width-section' akan membuatnya menembus batas 7xl
      mergeAttributes(HTMLAttributes, { 'data-type': 'section-block', class: 'full-width-section', style: `background-color: ${HTMLAttributes.bgColor}; padding: 4rem 0;` }),
      // Tapi pembungkus dalamnya akan mengunci teks kembali ke tengah (max-w-7xl)
      ['div', { class: 'max-w-7xl mx-auto px-6' }, 0]
    ]
  },

  // Tampilan UI Interaktif Khusus di dalam Editor
  addNodeView() {
    return ({ node, editor, getPos }) => {
      const dom = document.createElement('div')
      dom.className = 'full-width-section'
      dom.style.cssText = `
        background-color: ${node.attrs.bgColor};
        padding: 3rem 0;
        margin: 2rem 0;
        position: relative;
        border-top: 1px dashed #ccc;
        border-bottom: 1px dashed #ccc;
      `

      // --- COLOR PICKER MINI DI POJOK KANAN ATAS BLOK ---
      const toolbar = document.createElement('div')
      toolbar.contentEditable = 'false'
      toolbar.style.cssText = `
        position: absolute; top: 10px; right: 20px;
        display: flex; gap: 8px; background: white; padding: 4px 8px;
        border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      `

      const colors = ['#f3f4f6', '#F7EBAF', '#e0f2fe', '#dcfce7']; // Pilihan warna
      colors.forEach(c => {
        const btn = document.createElement('button')
        btn.style.cssText = `width:20px; height:20px; border-radius:100%; background:${c}; border:1px solid #ddd; cursor:pointer;`
        btn.title = `Ubah latar ke ${c}`
        btn.addEventListener('click', () => {
          if (typeof getPos === 'function') {
            editor.chain().setNodeSelection(getPos()).updateAttributes('sectionBlock', { bgColor: c }).run()
          }
        })
        toolbar.appendChild(btn)
      })
      dom.appendChild(toolbar)

      // --- AREA KONTEN (Terkunci max-w-7xl di tengah) ---
      const contentDOM = document.createElement('div')
      // Tailwind classes disuntikkan secara dinamis
      contentDOM.className = 'max-w-7xl mx-auto px-6'

      dom.appendChild(contentDOM)

      return { dom, contentDOM }
    }
  },

  addCommands() {
    return {
      setSectionBlock: () => ({ commands }) => {
        return commands.insertContent({
          type: this.name,
          content: [
            { type: 'heading', attrs: { level: 2 }, content: [{ type: 'text', text: 'Judul Seksi Baru' }] },
            { type: 'paragraph', content: [{ type: 'text', text: 'Ketik konten Anda di dalam area bewarna ini...' }] }
          ]
        })
      },
    }
  },
})
