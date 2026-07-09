import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'

export const LinkBackspaceHandler = Extension.create({
    name: 'linkBackspaceHandler',

    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('linkBackspacePlugin'),
                props: {
                    handleKeyDown(view, event) {
                        if (event.key !== 'Backspace') return false;

                        const { state } = view;
                        const { selection, tr } = state;
                        const { $from, empty } = selection;

                        if (!empty) return false;

                        let linkMark = state.schema.marks.link ? $from.marks().find(m => m.type.name === 'link') : null;

                        if (!linkMark && state.schema.marks.link) {
                            linkMark = $from.nodeBefore ? $from.nodeBefore.marks.find(m => m.type.name === 'link') : null;
                        }

                        if (linkMark) {
                            const linkType = state.schema.marks.link;
                            let start = $from.pos;
                            let end = $from.pos;

                            // Melacak batas awal link
                            while (start > 0 && state.doc.rangeHasMark(start - 1, start, linkType)) {
                                start--;
                            }

                            // Melacak batas akhir link
                            while (end < state.doc.content.size && state.doc.rangeHasMark(end, end + 1, linkType)) {
                                end++;
                            }

                            if (start < end) {
                                // --- ✅ PERBAIKAN DI SINI ---
                                // 1. Hapus teks link dari dokumen
                                // 2. Gunakan removeStoredMark agar ketikan setelahnya tidak bermark link
                                view.dispatch(
                                    tr.delete(start, end)
                                      .removeStoredMark(linkType)
                                );
                                return true;
                            }
                        }

                        return false;
                    },
                }
            })
        ];
    }
});
