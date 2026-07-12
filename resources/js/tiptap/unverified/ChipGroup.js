// ChipGroup.js
// Pola: ATTRS-BASED NODE (atom) — dipakai kalau isinya BUKAN rich text bebas,
// tapi data terstruktur (daftar label pendek). Tidak ada ProseMirror content
// di dalamnya sama sekali; semua diatur lewat attrs + UI custom di NodeView.
// Mewakili gaya ".region-card" di halaman Malaria: label beban + daftar chip kabupaten.

import { Node, mergeAttributes } from '@tiptap/core'

export const ChipGroup = Node.create({
  name: 'chipGroup',
  group: 'block',
  atom: true, // node "sekali jadi" - tidak bisa disunting karakter per karakter di dalamnya

  addAttributes() {
    return {
      label: { default: 'Beban Sedang' },
      color: { default: 'gold' }, // gold | coral
      chips: { default: ['Kabupaten A', 'Kabupaten B'] },
    }
  },

  parseHTML() {
    return [
      {
        tag: 'div[data-type="chip-group"]',
        getAttrs: (dom) => ({
          label: dom.querySelector('.region-tag')?.textContent || '',
          color: dom.dataset.color || 'gold',
          chips: Array.from(dom.querySelectorAll('.region-chip')).map((el) => el.textContent || ''),
        }),
      },
    ]
  },

  renderHTML({ HTMLAttributes, node }) {
    const chipEls = node.attrs.chips.map((chip) => ['span', { class: 'region-chip' }, chip])
    return [
      'div',
      mergeAttributes(HTMLAttributes, {
        'data-type': 'chip-group',
        class: `region-card region-${node.attrs.color === 'coral' ? 'high' : 'medium'}`,
        'data-color': node.attrs.color,
      }),
      ['div', { class: 'region-tag' }, node.attrs.label],
      ['div', { class: 'chip-row' }, ...chipEls],
    ]
  },

  addNodeView() {
    return ({ node, editor, getPos }) => {
      const update = (attrs) => {
        if (typeof getPos === 'function') {
          editor.chain().setNodeSelection(getPos()).updateAttributes('chipGroup', attrs).run()
        }
      }

      const dom = document.createElement('div')
      dom.className = `region-card region-${node.attrs.color === 'coral' ? 'high' : 'medium'}`
      dom.contentEditable = 'false' // seluruh node dikontrol lewat UI custom, bukan ketikan langsung

      const labelEl = document.createElement('input')
      labelEl.className = 'region-tag'
      labelEl.value = node.attrs.label
      labelEl.style.cssText = 'border:none;background:transparent;font:inherit;width:100%;margin-bottom:10px;'
      labelEl.addEventListener('change', () => update({ label: labelEl.value }))

      const chipRow = document.createElement('div')
      chipRow.className = 'chip-row'

      const renderChips = () => {
        chipRow.innerHTML = ''
        node.attrs.chips.forEach((chip, idx) => {
          const chipEl = document.createElement('span')
          chipEl.className = 'region-chip'
          chipEl.textContent = chip + ' ✕'
          chipEl.style.cursor = 'pointer'
          chipEl.title = 'Klik untuk hapus'
          chipEl.addEventListener('click', () => {
            const next = node.attrs.chips.filter((_, i) => i !== idx)
            update({ chips: next })
          })
          chipRow.appendChild(chipEl)
        })
      }
      renderChips()

      const addBtn = document.createElement('input')
      addBtn.placeholder = '+ Tambah kabupaten, lalu Enter'
      addBtn.style.cssText = 'border:1px dashed currentColor;background:transparent;font-size:13px;padding:6px 12px;border-radius:999px;margin-top:8px;width:220px;'
      addBtn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && addBtn.value.trim()) {
          e.preventDefault()
          update({ chips: [...node.attrs.chips, addBtn.value.trim()] })
          addBtn.value = ''
        }
      })

      dom.append(labelEl, chipRow, addBtn)

      return {
        dom,
        // "atom" node tanpa contentDOM - update() dipanggil ulang tiap attrs berubah
        update: (updatedNode) => {
          if (updatedNode.type.name !== 'chipGroup') return false
          node = updatedNode
          labelEl.value = node.attrs.label
          renderChips()
          return true
        },
      }
    }
  },

  addCommands() {
    return {
      setChipGroup:
        (attrs) =>
        ({ commands }) => {
          return commands.insertContent({ type: this.name, attrs })
        },
    }
  },
})
