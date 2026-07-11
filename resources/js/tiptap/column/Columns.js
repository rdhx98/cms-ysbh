// Columns.js
// Skema node untuk layout kolom: columnList (wadah) berisi 2+ column (masing-masing
// bisa diisi block apa saja - paragraf, gambar, heading, dst). Ini bagian yang PALING
// aman/sederhana dari seluruh fitur - hanya struktur dokumen, belum termasuk logika drag.

import { Node, mergeAttributes } from '@tiptap/core'

export const Column = Node.create({
  name: 'column',
  content: 'block+',
  isolating: true, // supaya backspace/delete di ujung tidak "bocor" ke kolom sebelah

  parseHTML() {
    return [{ tag: 'div[data-type="column"]' }]
  },

  renderHTML({ HTMLAttributes }) {
    return ['div', mergeAttributes(HTMLAttributes, { 'data-type': 'column', class: 'editor-column' }), 0]
  },
})

export const ColumnList = Node.create({
  name: 'columnList',
  group: 'block',
  content: 'column{2,3}', // batasi 2-3 kolom dulu; bisa dilonggarkan kalau perlu lebih
  isolating: true,

  parseHTML() {
    return [{ tag: 'div[data-type="column-list"]' }]
  },

  renderHTML({ HTMLAttributes, node }) {
    return [
      'div',
      mergeAttributes(HTMLAttributes, {
        'data-type': 'column-list',
        class: 'editor-column-list',
        style: `--column-count:${node.childCount}`,
      }),
      0,
    ]
  },

  addCommands() {
    return {
      // Command manual lewat toolbar, di luar drag - berguna untuk fallback/testing
      setTwoColumns:
        () =>
        ({ commands }) => {
          return commands.insertContent({
            type: this.name,
            content: [
              { type: 'column', content: [{ type: 'paragraph' }] },
              { type: 'column', content: [{ type: 'paragraph' }] },
            ],
          })
        },
    }
  },
})
