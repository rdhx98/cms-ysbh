import { Node, mergeAttributes } from '@tiptap/core'

const ICONS = {
  mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z"/><path d="m22 6-10 7L2 6"/></svg>',
  phone: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.9.6 2.8a2 2 0 0 1-.5 2.1L7.9 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2Z"/></svg>',
  map: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>'
}

export const ContactItem = Node.create({
  name: 'contactItem',
  group: 'block',
  atom: true,

  addAttributes() {
    return {
      icon: { default: 'mail' },
      title: { default: 'Email' },
      desc: { default: 'kontak@domain.com' },
    }
  },

  parseHTML() {
    return [
      {
        tag: 'div[data-type="contact-item"]',
        getAttrs: (dom) => ({
          icon: dom.dataset.icon || 'mail',
          title: dom.querySelector('.t')?.textContent || '',
          desc: dom.querySelector('.d')?.textContent || '',
        }),
      },
    ]
  },

  renderHTML({ HTMLAttributes, node }) {
    return [
      'div',
      mergeAttributes(HTMLAttributes, { 'data-type': 'contact-item', class: 'contact-item', 'data-icon': node.attrs.icon }),
      ['div', { class: 'ico', innerHTML: ICONS[node.attrs.icon] }],
      ['div', {},
        ['div', { class: 't' }, node.attrs.title],
        ['div', { class: 'd' }, node.attrs.desc]
      ]
    ]
  },

  addNodeView() {
    return ({ node, editor, getPos }) => {
      const dom = document.createElement('div')
      dom.className = 'contact-item'

      // Icon Box (Klik untuk ganti icon)
      const icoEl = document.createElement('div')
      icoEl.className = 'ico'
      icoEl.innerHTML = ICONS[node.attrs.icon]
      icoEl.style.cursor = 'pointer'
      icoEl.title = 'Klik untuk ganti ikon'
      icoEl.addEventListener('click', () => {
        const keys = Object.keys(ICONS)
        const next = keys[(keys.indexOf(node.attrs.icon) + 1) % keys.length]
        if (typeof getPos === 'function') {
          editor.chain().setNodeSelection(getPos()).updateAttributes('contactItem', { icon: next }).run()
        }
      })

      const textWrapper = document.createElement('div')
      textWrapper.style.flex = '1'

      const update = (key, val) => {
        if (typeof getPos === 'function') editor.chain().setNodeSelection(getPos()).updateAttributes('contactItem', { [key]: val }).run()
      }

      const tInput = document.createElement('input')
      tInput.className = 't'
      tInput.value = node.attrs.title
      tInput.style.cssText = 'border:1px dashed #ccc; background:transparent; font:inherit; width:100%; margin-bottom:4px; padding:2px;'
      tInput.addEventListener('change', () => update('title', tInput.value))

      const dInput = document.createElement('input')
      dInput.className = 'd'
      dInput.value = node.attrs.desc
      dInput.style.cssText = 'border:1px dashed #ccc; background:transparent; font:inherit; width:100%; padding:2px;'
      dInput.addEventListener('change', () => update('desc', dInput.value))

      textWrapper.append(tInput, dInput)
      dom.append(icoEl, textWrapper)

      return { dom }
    }
  },

  addCommands() {
    return {
      setContactItem: (attrs) => ({ commands }) => commands.insertContent({ type: this.name, attrs }),
    }
  },
})
