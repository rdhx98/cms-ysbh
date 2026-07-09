import { Node, mergeAttributes } from '@tiptap/core'

export const TransferCard = Node.create({
  name: 'transferCard',
  group: 'block',
  atom: true, // Tidak bisa diedit kursor biasa

  addAttributes() {
    return {
      label: { default: 'Transfer Bank' },
      acct: { default: 'BCA · 123 4567 890' },
      name: { default: 'a.n. Yayasan Sinar Bhakti Husada' },
    }
  },

  parseHTML() {
    return [
      {
        tag: 'div[data-type="transfer-card"]',
        getAttrs: (dom) => ({
          label: dom.querySelector('.label')?.textContent || '',
          acct: dom.querySelector('.acct')?.textContent || '',
          name: dom.querySelector('.name')?.textContent || '',
        }),
      },
    ]
  },

  renderHTML({ HTMLAttributes, node }) {
    return [
      'div',
      mergeAttributes(HTMLAttributes, { 'data-type': 'transfer-card', class: 'transfer-card', style: 'background:var(--forest);' }),
      ['div', { class: 'label' }, node.attrs.label],
      ['div', { class: 'acct' }, node.attrs.acct],
      ['div', { class: 'name' }, node.attrs.name],
      // Tombol copy disertakan untuk frontend publik
      ['button', { class: 'copy-btn', 'data-copy': node.attrs.acct }, 'Salin Nomor Rekening']
    ]
  },

  addNodeView() {
    return ({ node, editor, getPos }) => {
      const dom = document.createElement('div')
      dom.className = 'transfer-card'
      // Memberi warna hijau gelap agar kontras teks putihnya terlihat di dalam editor
      dom.style.cssText = 'background:var(--forest); padding:26px 28px; border-radius:18px; margin:1rem 0;'

      const update = (key, value) => {
        if (typeof getPos === 'function') {
          editor.chain().setNodeSelection(getPos()).updateAttributes('transferCard', { [key]: value }).run()
        }
      }

      // Helper untuk membuat input transparan
      const createInput = (className, value, key) => {
        const input = document.createElement('input')
        input.className = className
        input.value = value
        input.style.cssText = 'width:100%; border:1px dashed rgba(255,255,255,0.4); background:transparent; color:inherit; font:inherit; margin-bottom:4px;'
        input.addEventListener('change', () => update(key, input.value))
        return input
      }

      dom.appendChild(createInput('label', node.attrs.label, 'label'))
      dom.appendChild(createInput('acct', node.attrs.acct, 'acct'))
      dom.appendChild(createInput('name', node.attrs.name, 'name'))

      return { dom }
    }
  },

  addCommands() {
    return {
      setTransferCard: (attrs) => ({ commands }) => commands.insertContent({ type: this.name, attrs }),
    }
  },
})
