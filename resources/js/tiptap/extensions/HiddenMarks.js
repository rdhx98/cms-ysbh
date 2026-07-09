import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'

export const HiddenMarks = Extension.create({
    name: 'hiddenMarks',

    addStorage() {
        return {
            visible: false,
        }
    },
    // --- ➕ INJECT CSS OTOMATIS LEWAT JS ---
    onCreate() {
        if (!document.getElementById('tiptap-hidden-marks-styles')) {
            const style = document.createElement('style');
            style.id = 'tiptap-hidden-marks-styles';
            style.textContent = `
                /* 🌟 SOLUSI ANTI-BUG INDENTASI & JUSTIFY 🌟 */
                .tiptap-invisible-space {
                    /* Menggambar titik bulat vektor langsung di background spasi */
                    background-image: radial-gradient(circle, #a1a1aa 1.25px, transparent 1.5px) !important;

                    /* Mengunci posisi gambar persis di tengah secara horizontal, dan 55% secara vertikal */
                    background-position: center 55% !important;
                    background-repeat: no-repeat !important;
                }

                /* Matikan pemanggilan ::before yang lama agar tidak muncul dobel */
                .tiptap-invisible-space::before {
                    content: none !important;
                    display: none !important;
                }
            `;
            document.head.appendChild(style);
        }
    },

    addCommands() {
        return {
            toggleHiddenMarks: () => ({ editor }) => {
                this.storage.visible = !this.storage.visible;

                const { state, view } = editor;
                view.dispatch(
                    state.tr
                        .setMeta('hiddenMarksTrigger', Date.now())
                        .setMeta('addToHistory', false)
                );

                return true;
            },
        }
    },

    addProseMirrorPlugins() {
        const extensionThis = this;

        return [
            new Plugin({
                key: new PluginKey('hiddenMarksPlugin'),

                state: {
                    init() { return null; },
                    apply(tr, value) {
                        if (tr.getMeta('hiddenMarksTrigger') !== undefined) return tr.getMeta('hiddenMarksTrigger');
                        return value;
                    }
                },

                props: {
                    attributes() {
                        return extensionThis.storage.visible
                            ? { class: 'show-invisible-marks' }
                            : {};
                    },

                    decorations(state) {
                        if (!extensionThis.storage.visible) return DecorationSet.empty;

                        const decorations = [];
                        const { doc } = state;

                        doc.descendants((node, pos) => {

                            // 1. PERBAIKAN PARAGRAF (¶) - DIKUNCI AGAR TIDAK TURUN
                            if (node.type.name === 'paragraph') {
                                const endPos = pos + node.nodeSize - 1;
                                decorations.push(
                                    Decoration.widget(endPos, () => {
                                        const span = document.createElement('span');
                                        span.className = 'tiptap-invisible-para';
                                        span.textContent = '¶';
                                        return span;
                                    }, { side: 1, stopEvent: () => true })
                                );
                            }

                            // 2. PERBAIKAN HARD BREAK (↵) - DIKUNCI AGAR SEJAJAR TEKS
                            if (node.type.name === 'hardBreak') {
                                decorations.push(
                                    Decoration.widget(pos, () => {
                                        const span = document.createElement('span');
                                        span.className = 'tiptap-invisible-break';
                                        span.textContent = '↵';
                                        span.style.cssText = `
                                            display: inline-block !important;
                                            width: 0 !important;
                                            height: 0 !important;
                                            line-height: 0 !important;
                                            overflow: visible !important;

                                            /* Kunci posisi vertikal */
                                            vertical-align: baseline !important;
                                            transform: translateY(-0.05em) !important; /* Dorong mikro ke atas jika ikut ketarik turun */

                                            font-family: var(--font-mono) !important;
                                            font-size: 0.85em !important;
                                            color: #c300ff !important;
                                            user-select: none !important;
                                            pointer-events: none !important;
                                            margin-left: 2px !important;
                                        `;
                                        return span;
                                    }, { side: -1, stopEvent: () => true })
                                );
                            }


                            // 3. DETEKSI SPASI (·) - MENGGUNAKAN INLINE DECORATION
                            if (node.isText) {
                                const text = node.text;
                                let index = text.indexOf(' ');

                                while (index !== -1) {
                                    const startPos = pos + index;
                                    const endPos = startPos + 1;

                                    decorations.push(
                                        Decoration.inline(startPos, endPos, {
                                            class: 'tiptap-invisible-space',
                                        })
                                    );

                                    // Cari spasi berikutnya di dalam text node yang sama
                                    index = text.indexOf(' ', index + 1);
                                }
                            }
                        });

                        return DecorationSet.create(doc, decorations);
                    }
                }
            }),
        ];
    }
});
