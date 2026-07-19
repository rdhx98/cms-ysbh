import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'

export const TrailingNode = Extension.create({
    name: 'trailingNode',

    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('trailingNode'),
                appendTransaction: (_, __, state) => {
                    const { doc, tr, schema } = state
                    const lastNode = doc.lastChild

                    // Jika dokumen tidak kosong dan node terakhir BUKAN paragraf,
                    // maka otomatis sisipkan paragraf kosong di akhir.
                    if (lastNode && lastNode.type.name !== 'paragraph') {
                        return tr.insert(doc.content.size, schema.nodes.paragraph.create())
                    }
                },
            }),
        ]
    },
})