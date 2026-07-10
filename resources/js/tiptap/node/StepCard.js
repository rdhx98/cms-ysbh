// resources/js/tiptap/nodes/StepCard.js
import { Node, mergeAttributes } from '@tiptap/core'

export const StepCard = Node.create({
  name: 'stepCard',
  group: 'block',
  content: 'block+',
  // draggable tidak perlu diset true secara eksplisit lagi, biar Global Handle yang urus

  addAttributes() {
    return { number: { default: '01' } }
  },

  parseHTML() {
    return [{
      tag: 'div[data-type="step-card"]',
      getAttrs: (dom) => ({ number: dom.dataset.number || '01' }),
    }]
  },

  renderHTML({ HTMLAttributes, node }) {
    return [
      'div',
      mergeAttributes(HTMLAttributes, { 'data-type': 'step-card', class: 'component-card' }),
      ['span', { class: 'component-num' }, node.attrs.number],
      ['div', { class: 'component-body' }, 0],
    ]
  },

  addNodeView() {
    return ({ node, editor, getPos }) => {
      const dom = document.createElement('div')
      dom.className = 'component-card'
      dom.dataset.number = node.attrs.number

      // 🌟 PERBAIKAN: margin: 1.5rem auto; agar mengikuti hukum rata tengah max-w-7xl
      // Tuas (drag handle) lokal juga sudah dibuang agar editor jauh lebih bersih
      dom.style.cssText = `
        display: flex; gap: 24px; align-items: flex-start;
        background: #FFFFFF; border: 1px solid rgba(6, 79, 59, 0.14);
        border-radius: 18px; padding: 26px 30px;
        box-shadow: 0 20px 50px -25px rgba(6, 45, 35, 0.35);
        margin: 1.5rem auto; /* 👈 KUNCI EMASNYA DI SINI */
        width: 100%;
      `

      // --- KOTAK ANGKA ---
      const numEl = document.createElement('input')
      numEl.type = 'text'
      numEl.value = node.attrs.number
      numEl.className = 'component-num'
      numEl.maxLength = 2
      numEl.style.cssText = `
        font-family: 'Fraunces', serif; font-weight: 700; font-size: 15px;
        color: #064F3B; background: #F7EBAF; width: 40px; height: 40px;
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        text-align: center; flex-shrink: 0; outline: none; border: none;
        transition: box-shadow 0.2s ease;
      `

      numEl.addEventListener('focus', () => numEl.style.boxShadow = '0 0 0 2px #E42326')
      numEl.addEventListener('blur', () => {
        numEl.style.boxShadow = 'none'
        if (typeof getPos === 'function') {
          editor.chain().setNodeSelection(getPos()).updateAttributes('stepCard', { number: numEl.value }).run()
        }
      })
      numEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault()
          editor.commands.focus(getPos() + 2)
        }
      })

      // --- AREA TEKS ---
      const contentDOM = document.createElement('div')
      contentDOM.className = 'component-body'
      contentDOM.style.cssText = 'flex: 1; min-width: 0;'

      dom.append(numEl, contentDOM)

      return {
        dom,
        contentDOM,
        stopEvent: (event) => { return event.target === numEl; },
        ignoreMutation: (mutation) => { return !contentDOM.contains(mutation.target); }
      }
    }
  },

  addCommands() {
    return {
      setStepCard: (attrs) => ({ commands }) => {
        return commands.insertContent([
          {
            type: this.name,
            attrs,
            content: [
              { type: 'heading', attrs: { level: 3 }, content: [{ type: 'text', text: 'Judul Komponen' }] },
              { type: 'paragraph', content: [{ type: 'text', text: 'Tulis penjelasan komponen di sini...' }] },
            ],
          },
          { type: 'paragraph' }
        ])
      },
    }
  },
})
