import { Node, mergeAttributes } from '@tiptap/core'

export const SectionBlock2 = Node.create({
  name: 'sectionBlock',
  group: 'block',
  content: 'block+',
  draggable: true, // Memungkinkan seluruh container ditarik oleh Global Drag Handle

  addAttributes() {
    return {
      bgColor: {
        default: '#f3f4f6',
        parseHTML: element => element.getAttribute('data-bg-color') || '#f3f4f6',
        renderHTML: attributes => ({ 'data-bg-color': attributes.bgColor })
      },
      innerBgColor: {
        default: 'transparent',
        parseHTML: element => element.getAttribute('data-inner-color') || 'transparent',
        renderHTML: attributes => ({ 'data-inner-color': attributes.innerBgColor })
      }
    }
  },

  parseHTML() {
    return [{ tag: 'div[data-type="section-block"]' }]
  },

  // Render HTML bersih untuk disimpan ke Database (Frontend Publik)
  renderHTML({ HTMLAttributes }) {
    const bgColor = HTMLAttributes['data-bg-color'] || '#f3f4f6'
    const innerColor = HTMLAttributes['data-inner-color'] || 'transparent'

    return [
      'div',
      mergeAttributes(HTMLAttributes, {
        'data-type': 'section-block',
        class: 'full-width-section',
        style: `background-color: ${bgColor}; padding: 4rem 0;`
      }),
      // KUNCI: Div ini mengunci teks ke batas layar standar (max-w-7xl) dengan warna independen
      ['div', {
        class: 'max-w-7xl mx-auto px-4 md:px-8',
        style: `background-color: ${innerColor}; border-radius: 1rem; padding-top: 2rem; padding-bottom: 2rem;`
      }, 0]
    ]
  },

  // Tampilan & UI Khusus di Dalam Editor
  addNodeView() {
    return ({ node, editor, getPos }) => {
      const dom = document.createElement('div')
      dom.className = 'full-width-section'
      dom.style.cssText = `
        background-color: ${node.attrs.bgColor};
        padding: 3rem 0;
        margin: 2rem 0;
        position: relative;
        transition: background-color 0.3s ease;
      `

      // --- MENU PALET WARNA (Pojok Kanan Atas) ---
      const toolbar = document.createElement('div')
      toolbar.contentEditable = 'false'
      toolbar.style.cssText = `
        position: absolute; top: 12px; right: 20px;
        display: flex; flex-direction: column; gap: 10px;
        background: #ffffff; padding: 10px 12px;
        border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        font-family: var(--font-jakarta), sans-serif; font-size: 11px; color: #52525b;
        z-index: 10; border: 1px solid #e4e4e7;
      `

      const colorsOuter = ['#f3f4f6', '#F7EBAF', '#e0f2fe', '#dcfce7'];
      const colorsInner = ['transparent', '#ffffff', '#fffbeb', '#f0fdf4'];

      const createColorRow = (label, colors, attrName) => {
        const row = document.createElement('div')
        row.innerHTML = `<strong style="display:block; margin-bottom:6px;">${label}</strong>`
        const btnGroup = document.createElement('div')
        btnGroup.style.display = 'flex'
        btnGroup.style.gap = '8px'

        colors.forEach(c => {
          const btn = document.createElement('button')
          btn.title = c === 'transparent' ? 'Transparan' : `Warna ${c}`

          if (c === 'transparent') {
             // Motif catur untuk menandakan "Transparan"
             btn.style.cssText = `width:24px; height:24px; border-radius:50%; border:1px solid #d4d4d8; cursor:pointer; background-image: linear-gradient(45deg, #e5e7eb 25%, transparent 25%), linear-gradient(-45deg, #e5e7eb 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e5e7eb 75%), linear-gradient(-45deg, transparent 75%, #e5e7eb 75%); background-size: 8px 8px; background-position: 0 0, 0 4px, 4px -4px, -4px 0px;`
          } else {
             btn.style.cssText = `width:24px; height:24px; border-radius:50%; background:${c}; border:1px solid #d4d4d8; cursor:pointer;`
          }

          // Efek Ring jika warna sedang aktif
          if (node.attrs[attrName] === c) {
            btn.style.outline = '2px solid #064F3B'
            btn.style.outlineOffset = '2px'
          }

          btn.addEventListener('click', () => {
            if (typeof getPos === 'function') {
              editor.chain().setNodeSelection(getPos()).updateAttributes('sectionBlock', { [attrName]: c }).run()
            }
          })
          btnGroup.appendChild(btn)
        })
        row.appendChild(btnGroup)
        return row
      }

      toolbar.appendChild(createColorRow('Latar Membentang', colorsOuter, 'bgColor'))
      toolbar.appendChild(createColorRow('Kotak 7xl Dalam', colorsInner, 'innerBgColor'))
      dom.appendChild(toolbar)

      // --- KOTAK KONTEN MAX-W-7XL ---
      const contentDOM = document.createElement('div')
      contentDOM.className = 'max-w-7xl mx-auto px-4 md:px-8'

      // Tambahkan efek bayangan halus jika latar tidak transparan agar menonjol
      const isCard = node.attrs.innerBgColor !== 'transparent';

      contentDOM.style.cssText = `
        background-color: ${node.attrs.innerBgColor};
        border-radius: 1.25rem;
        padding: ${isCard ? '2rem' : '1rem 0'};
        min-height: 120px;
        transition: all 0.3s ease;
        box-shadow: ${isCard ? '0 10px 40px -10px rgba(0,0,0,0.06)' : 'none'};
      `

      dom.appendChild(contentDOM)

      return { dom, contentDOM }
    }
  },

  addCommands() {
    return {
      setSectionBlock2: () => ({ commands }) => {
        return commands.insertContent([
          {
            type: this.name,
            content: [
              { type: 'heading', attrs: { level: 2 }, content: [{ type: 'text', text: 'Judul Seksi Baru' }] },
              { type: 'paragraph', content: [{ type: 'text', text: 'Ketik konten Anda di dalam area berwarna ini...' }] }
            ]
          },
          { type: 'paragraph' }
        ])
      },
    }
  },
})
