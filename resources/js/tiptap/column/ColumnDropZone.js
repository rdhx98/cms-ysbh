// ColumnDropZone.js
// Menambahkan "zona kolom" di sisi kiri/kanan setiap blok saat drag berlangsung.
// Strategi yang dipakai SENGAJA dua tahap, bukan satu:
// Tahap 1 - Saat drop terjadi di zona kiri/kanan, kita TIDAK campur tangan sama sekali -
//      biarkan drag-drop normal (dari GlobalDragHandle) berjalan apa adanya, karena
//      bagian "pindahkan node dari posisi A ke B" itu sudah benar & teruji di ProseMirror/
//      package tersebut. Menulis ulang logika pemindahan itu sendiri berisiko tinggi salah
//      hitung posisi (off-by-one, dsb).
// Tahap 2 - SETELAH pemindahan selesai (lewat appendTransaction), kita cek: apakah node yang
//      baru saja pindah kini bersebelahan dengan node target? Kalau ya, baru kita BUNGKUS
//      keduanya jadi columnList+column lewat transaksi susulan.
//
// Konsekuensi jujur: langkah 2 mengasumsikan drag-drop yang dipakai (GlobalDragHandle)
// menyisipkan node yang di-drag sebagai SIBLING langsung di sisi target saat drop -
// ini perilaku umum/wajar untuk drag handle berbasis blok, tapi belum saya uji langsung
// terhadap package tiptap-extension-global-drag-handle secara spesifik. Coba dulu, dan
// kalau posisi pembungkusan meleset, kemungkinan besar cukup menyesuaikan asumsi ini.

import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'

const key = new PluginKey('columnDropZone')
const ZONE_RATIO = 0.25 // 25% kiri & 25% kanan dari lebar blok = zona kolom

export const ColumnDropZone = Extension.create({
  name: 'columnDropZone',

  addProseMirrorPlugins() {
    // Variabel tertutup (closure) untuk komunikasi antara drop handler dan
    // appendTransaction - lebih sederhana daripada lewat plugin state/meta
    // karena keduanya terjadi berurutan dalam siklus drag yang sama.
    let pendingWrap = null // { side: 'left' | 'right' } | null

    return [
      new Plugin({
        key,

        state: {
          init() {
            return { zone: null, blockPos: null }
          },
          apply(tr, prev) {
            const meta = tr.getMeta(key)
            if (meta !== undefined) return meta
            // Posisi decoration perlu ikut bergeser kalau ada perubahan dokumen lain
            if (tr.docChanged && prev.blockPos != null) {
              return { ...prev, blockPos: tr.mapping.map(prev.blockPos) }
            }
            return prev
          },
        },

        props: {
          decorations(state) {
            const s = key.getState(state)
            if (!s?.zone || s.blockPos == null) return DecorationSet.empty
            const widget = Decoration.widget(
              s.blockPos,
              () => {
                const el = document.createElement('div')
                el.className = `column-drop-indicator column-drop-${s.zone}`
                return el
              },
              { side: s.zone === 'left' ? -1 : 1, key: 'column-drop-indicator' }
            )
            return DecorationSet.create(state.doc, [widget])
          },

          handleDOMEvents: {
            dragover(view, event) {
              const coords = { left: event.clientX, top: event.clientY }
              const atPos = view.posAtCoords(coords)
              if (!atPos) return false

              // Naik ke level blok teratas (anak langsung dari doc) - kolom hanya
              // didukung di level ini dulu untuk versi pertama.
              const $pos = view.state.doc.resolve(atPos.pos)
              const topLevelPos = $pos.before(1)
              const blockNode = view.state.doc.nodeAt(topLevelPos)
              if (!blockNode || blockNode.type.name === 'columnList') {
                // Belum menangani drop ke dalam columnList yang sudah ada di versi ini.
                view.dispatch(view.state.tr.setMeta(key, { zone: null, blockPos: null }))
                return false
              }

              const dom = view.nodeDOM(topLevelPos)
              if (!(dom instanceof HTMLElement)) return false
              const rect = dom.getBoundingClientRect()
              const relativeX = (event.clientX - rect.left) / rect.width

              let zone = null
              if (relativeX <= ZONE_RATIO) zone = 'left'
              else if (relativeX >= 1 - ZONE_RATIO) zone = 'right'

              view.dispatch(view.state.tr.setMeta(key, {
                zone,
                blockPos: zone === 'left' ? topLevelPos : topLevelPos + blockNode.nodeSize,
              }))

              // Selalu preventDefault supaya drop diizinkan browser; TIDAK return true,
              // supaya dropCursor bawaan / handler drag-handle lain tetap jalan untuk
              // kasus di luar zona kolom.
              event.preventDefault()
              return false
            },

            drop(view) {
              const s = key.getState(view.state)
              if (s?.zone) {
                // Simpan niat "bungkus jadi kolom" untuk dibaca appendTransaction
                // setelah drag-drop normal selesai memindahkan node-nya.
                pendingWrap = { side: s.zone }
              }
              // Selalu return false - biarkan drop asli (GlobalDragHandle) yang
              // memindahkan node seperti biasa.
              return false
            },

            dragleave(view, event) {
              // Hanya reset kalau benar-benar keluar dari elemen editor, bukan
              // sekadar pindah antar child element di dalamnya.
              if (event.target === view.dom && !view.dom.contains(event.relatedTarget)) {
                view.dispatch(view.state.tr.setMeta(key, { zone: null, blockPos: null }))
              }
              return false
            },
          },
        },

        appendTransaction(transactions, oldState, newState) {
          if (!pendingWrap) return null
          const moved = transactions.some((tr) => tr.docChanged)
          const { side } = pendingWrap
          pendingWrap = null // konsumsi sekali pakai, apa pun hasilnya di bawah
          if (!moved) return null

          // Bersihkan indikator visual zona kolom.
          let tr = newState.tr.setMeta(key, { zone: null, blockPos: null })

          // Cari pasangan node top-level yang sekarang bersebelahan tepat di sekitar
          // posisi kursor terakhir (didekati lewat mapping posisi lama -> baru).
          const oldPos = key.getState(oldState)?.blockPos
          if (oldPos == null) return tr
          const mappedPos = transactions.reduce((p, t) => t.mapping.map(p), oldPos)

          const $mapped = newState.doc.resolve(Math.min(mappedPos, newState.doc.content.size))
          const targetIndex = side === 'left' ? $mapped.index(0) : $mapped.index(0) - 1
          const neighborIndex = side === 'left' ? targetIndex - 1 : targetIndex + 1

          if (targetIndex < 0 || neighborIndex < 0) return tr
          if (targetIndex >= newState.doc.childCount || neighborIndex >= newState.doc.childCount) return tr

          const targetNode = newState.doc.child(targetIndex)
          const neighborNode = newState.doc.child(neighborIndex)
          if (!targetNode || !neighborNode) return tr
          if (targetNode.type.name === 'columnList' || neighborNode.type.name === 'columnList') return tr

          const schema = newState.schema
          if (!schema.nodes.column || !schema.nodes.columnList) return tr

          const firstIsNeighbor = neighborIndex < targetIndex
          const leftNode = firstIsNeighbor ? neighborNode : targetNode
          const rightNode = firstIsNeighbor ? targetNode : neighborNode

          const columnList = schema.nodes.columnList.create(null, [
            schema.nodes.column.create(null, leftNode),
            schema.nodes.column.create(null, rightNode),
          ])

          // Hitung posisi awal dari node pertama (index terkecil di antara keduanya).
          let startPos = 0
          for (let i = 0; i < Math.min(targetIndex, neighborIndex); i++) {
            startPos += newState.doc.child(i).nodeSize
          }
          const endPos = startPos + leftNode.nodeSize + rightNode.nodeSize

          tr = tr.replaceWith(startPos, endPos, columnList)
          return tr
        },
      }),
    ]
  },
})
