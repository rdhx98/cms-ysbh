// Columns.js (v3 - lebar kolom bisa di-drag, jumlah kolom bisa diatur)
//
// Dua kontrol terpisah:
// Kontrol pertama, JUMLAH KOLOM - tombol tambah di kiri/kanan baris untuk menambah,
//    tombol hapus di tiap kolom untuk menghapus. Minimal 2, maksimal 5.
// Kontrol kedua, LEBAR KOLOM - garis pemisah tipis di antara kolom yang bisa di-drag,
//    persis seperti resize panel pada umumnya. Setiap kolom punya attrs
//    width (angka rasio, bukan wajib berjumlah 100 - hanya proporsinya
//    yang penting, langsung dipakai sebagai nilai fr di CSS grid).
//
// Node lain (InfoCard, Callout, dst.) tetap bisa di-drag masuk ke dalam
// kolom tanpa kode tambahan, karena columnSlot menerima content:'block+'.

import { Node, mergeAttributes } from '@tiptap/core'

const MAX_COLUMNS = 5
const MIN_COLUMNS = 2
const MIN_WIDTH = 15 // persen minimal per kolom saat resize, mencegah kolom hilang jadi 0

function applyGridTemplate(dom, node) {
  const widths = []
  node.forEach((slot) => widths.push(slot.attrs.width || 50))
  dom.style.gridTemplateColumns = widths.map((w) => `${w}fr`).join(' ')
}

export const ColumnSlot = Node.create({
  name: 'columnSlot',
  content: 'block+',
  isolating: true,

  addAttributes() {
    return {
      width: { default: 50 }, // rasio relatif, bukan persen absolut wajib 100
    }
  },

  parseHTML() {
    return [{
      tag: 'div[data-type="column-slot"]',
      getAttrs: (dom) => ({ width: parseFloat(dom.dataset.width) || 50 }),
    }]
  },

  renderHTML({ HTMLAttributes, node }) {
    return ['div', mergeAttributes(HTMLAttributes, {
      'data-type': 'column-slot',
      'data-width': node.attrs.width,
      class: 'column-slot',
    }), 0]
  },

  addNodeView() {
    return ({ node, editor, getPos }) => {
      const dom = document.createElement('div')
      dom.className = 'column-slot'
      dom.dataset.width = node.attrs.width

      const removeBtn = document.createElement('button')
      removeBtn.type = 'button'
      removeBtn.className = 'column-slot-remove'
      removeBtn.title = 'Hapus kolom ini'
      removeBtn.textContent = '✕'
      removeBtn.contentEditable = 'false'
      removeBtn.addEventListener('click', () => {
        if (typeof getPos !== 'function') return
        const pos = getPos()
        const $pos = editor.state.doc.resolve(pos)
        const row = $pos.parent
        if (row.childCount <= MIN_COLUMNS) return
        const slotNode = editor.state.doc.nodeAt(pos)
        if (!slotNode) return
        editor.chain().deleteRange({ from: pos, to: pos + slotNode.nodeSize }).run()
      })

      const contentDOM = document.createElement('div')
      contentDOM.className = 'column-slot-content'

      dom.append(removeBtn, contentDOM)

      return {
        dom,
        contentDOM,
        update: (updatedNode) => {
          if (updatedNode.type.name !== 'columnSlot') return false
          dom.dataset.width = updatedNode.attrs.width
          return true
        },
      }
    }
  },
})

export const ColumnRow = Node.create({
  name: 'columnRow',
  group: 'block',
  content: 'columnSlot{2,5}',
  isolating: true,

  parseHTML() {
    return [{ tag: 'div[data-type="column-row"]' }]
  },

  renderHTML({ HTMLAttributes }) {
    return ['div', mergeAttributes(HTMLAttributes, {
      'data-type': 'column-row',
      class: 'column-row',
    }), 0]
  },

  addNodeView() {
    return ({ node, editor, getPos }) => {
      const wrapper = document.createElement('div')
      wrapper.className = 'column-row-wrapper'

      const addLeftBtn = document.createElement('button')
      addLeftBtn.type = 'button'
      addLeftBtn.className = 'column-add-btn column-add-left'
      addLeftBtn.title = 'Tambah kolom di kiri'
      addLeftBtn.textContent = '+'

      const addRightBtn = document.createElement('button')
      addRightBtn.type = 'button'
      addRightBtn.className = 'column-add-btn column-add-right'
      addRightBtn.title = 'Tambah kolom di kanan'
      addRightBtn.textContent = '+'

      const contentDOM = document.createElement('div')
      contentDOM.className = 'column-row'

      // Lapisan terpisah untuk garis resize, di ATAS contentDOM tapi tidak
      // ikut dikelola ProseMirror - supaya bebas menaruh elemen non-node di sini.
      const handleLayer = document.createElement('div')
      handleLayer.className = 'column-handle-layer'
      handleLayer.style.cssText = 'position:absolute; inset:0; pointer-events:none;'

      wrapper.style.position = 'relative'
      wrapper.append(addLeftBtn, contentDOM, addRightBtn, handleLayer)

      function currentRowNode() {
        return typeof getPos === 'function' ? editor.state.doc.nodeAt(getPos()) : null
      }

      function addColumn(side) {
        const pos = getPos()
        const rowNode = currentRowNode()
        if (!rowNode || rowNode.childCount >= MAX_COLUMNS) return
        const insertPos = side === 'left' ? pos + 1 : pos + rowNode.nodeSize - 1
        editor.chain().insertContentAt(insertPos, {
          type: 'columnSlot',
          attrs: { width: 50 },
          content: [{ type: 'paragraph' }],
        }).run()
      }
      addLeftBtn.addEventListener('click', () => addColumn('left'))
      addRightBtn.addEventListener('click', () => addColumn('right'))

      // --- Resize handles ---
      function renderHandles() {
        handleLayer.innerHTML = ''
        const rowNode = currentRowNode()
        if (!rowNode) return
        const slotEls = Array.from(contentDOM.children)
        if (slotEls.length < 2) return

        for (let i = 0; i < slotEls.length - 1; i++) {
          const leftEl = slotEls[i]
          const handle = document.createElement('div')
          handle.className = 'column-resize-handle'
          const rowRect = contentDOM.getBoundingClientRect()
          const leftRect = leftEl.getBoundingClientRect()
          const xWithinRow = leftRect.right - rowRect.left
          handle.style.left = `${xWithinRow - 4}px`
          handle.style.pointerEvents = 'auto'

          handle.addEventListener('mousedown', (e) => {
            e.preventDefault()
            const startX = e.clientX
            const totalRowWidth = contentDOM.getBoundingClientRect().width
            const leftIndex = i
            const rightIndex = i + 1
            const startLeftWidth = rowNode.child(leftIndex).attrs.width || 50
            const startRightWidth = rowNode.child(rightIndex).attrs.width || 50
            const pairTotal = startLeftWidth + startRightWidth

            function onMouseMove(moveEvent) {
              const deltaPx = moveEvent.clientX - startX
              const deltaRatio = (deltaPx / totalRowWidth) * pairTotal
              let newLeft = startLeftWidth + deltaRatio
              let newRight = startRightWidth - deltaRatio
              const minAbs = (MIN_WIDTH / 100) * pairTotal
              if (newLeft < minAbs) { newLeft = minAbs; newRight = pairTotal - minAbs }
              if (newRight < minAbs) { newRight = minAbs; newLeft = pairTotal - minAbs }

              // Update dua kolom sekaligus dalam satu transaksi supaya atomik.
              const rowPos = getPos()
              const currentRow = editor.state.doc.nodeAt(rowPos)
              if (!currentRow) return
              let childPos = rowPos + 1
              const positions = []
              currentRow.forEach((child, offset) => {
                positions.push(rowPos + 1 + offset)
              })
              const tr = editor.state.tr
              tr.setNodeAttribute(positions[leftIndex], 'width', newLeft)
              tr.setNodeAttribute(positions[rightIndex], 'width', newRight)
              editor.view.dispatch(tr)
            }

            function onMouseUp() {
              document.removeEventListener('mousemove', onMouseMove)
              document.removeEventListener('mouseup', onMouseUp)
            }

            document.addEventListener('mousemove', onMouseMove)
            document.addEventListener('mouseup', onMouseUp)
          })

          handleLayer.appendChild(handle)
        }
      }

      function refreshAll(currentNode) {
        applyGridTemplate(contentDOM, currentNode)
        const atMax = currentNode.childCount >= MAX_COLUMNS
        addLeftBtn.disabled = atMax
        addRightBtn.disabled = atMax
        // Handle posisinya dihitung dari DOM yang sudah ter-render, jadi tunggu
        // satu frame supaya grid sempat reflow dulu.
        requestAnimationFrame(renderHandles)
      }

      refreshAll(node)
      window.addEventListener('resize', renderHandles)

      return {
        dom: wrapper,
        contentDOM,
        update: (updatedNode) => {
          if (updatedNode.type.name !== 'columnRow') return false
          refreshAll(updatedNode)
          return true
        },
        destroy: () => window.removeEventListener('resize', renderHandles),
      }
    }
  },

  addCommands() {
    return {
      insertColumnRow:
        () =>
        ({ commands }) => {
          return commands.insertContent({
            type: this.name,
            content: [
              { type: 'columnSlot', attrs: { width: 50 }, content: [{ type: 'paragraph' }] },
              { type: 'columnSlot', attrs: { width: 50 }, content: [{ type: 'paragraph' }] },
            ],
          })
        },
    }
  },
})
