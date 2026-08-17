import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';

window.addEventListener('insert-link-to-active-editor', (event) => {
    if (window.activeTiptapEditor && event.detail && event.detail.url) {
        const { url, text } = event.detail;
        const editorInstance = window.activeTiptapEditor;
        
        if (text && !editorInstance.state.selection.empty) {
            editorInstance.chain().focus().setLink({ href: url }).run();
        } else if (text) {
            editorInstance.chain().focus().insertContent({
                type: 'text',
                text: text,
                marks: [{ type: 'link', attrs: { href: url, target: '_blank' } }]
            }).run();
        } else {
            editorInstance.chain().focus().setLink({ href: url }).run();
        }
    }
});

document.addEventListener('alpine:init', () => {
    Alpine.data('tiptap', (entangledContent) => {
        // 🌟 KUNCI UTAMA: Simpan instans editor sebagai variabel lokal murni.
        // Dengan ini, Alpine TIDAK AKAN mem-proxy TipTap, sehingga error transaksi musnah.
        let editor = null;

        return {
            content: entangledContent,
            updatedAt: Date.now(),
            showLinkModal: false,
            linkInputUrl: '',

            init() {
                editor = new Editor({
                    element: this.$refs.editorElement,
                    extensions: [
                        StarterKit.configure({ 
                            heading: false, 
                            codeBlock: false,
                            link: false,
                        }),
                        Link.configure({
                            openOnClick: false,
                            HTMLAttributes: { 
                                class: 'text-blue-600 font-semibold underline cursor-pointer' 
                            }
                        }),
                        TextAlign.configure({ types: ['paragraph'] }),
                    ],
                    content: this.content || '',
                    editorProps: {
                        attributes: {
                            class: 'prose max-w-none focus:outline-none min-h-[120px] p-4 text-gray-700',
                        },
                    },
                    onFocus: () => {
                        window.activeTiptapEditor = editor;
                    },
                    onUpdate: () => {
                        this.content = editor.getHTML();
                    },
                    onTransaction: () => {
                        this.updatedAt = Date.now();
                    }
                });

                this.$watch('content', (val) => {
                    if (editor && val && val !== editor.getHTML()) {
                        editor.commands.setContent(val, false);
                    }
                });
            },

            destroy() {
                if (editor) {
                    editor.destroy();
                    editor = null;
                }
            },

            isActive(type, opts = {}) {
                this.updatedAt; // Memicu reaktivitas UI tombol
                return editor ? editor.isActive(type, opts) : false;
            },

            toggleBold() {
                if (!editor) return;
                editor.chain().focus().toggleBold().run();
            },

            toggleItalic() {
                if (!editor) return;
                editor.chain().focus().toggleItalic().run();
            },

            toggleBulletList() {
                if (!editor) return;
                editor.chain().focus().toggleBulletList().run();
            },

            toggleOrderedList() {
                if (!editor) return;
                editor.chain().focus().toggleOrderedList().run();
            },

            setAlignment(align) {
                if (!editor) return;
                editor.chain().focus().setTextAlign(align).run();
            },
            
            setLink() {
                if (!editor) return;
                window.activeTiptapEditor = editor;
                this.linkInputUrl = editor.getAttributes('link').href || '';
                this.showLinkModal = true;
            },

            saveLink() {
                if (!editor) return;
                let url = this.linkInputUrl.trim();

                if (url === '') {
                    editor.chain().focus().unsetLink().run();
                } else {
                    if (!/^https?:\/\//i.test(url) && !/^mailto:/i.test(url) && !/^tel:/i.test(url)) {
                        url = `https://${url}`;
                    }
                    editor.chain().focus().setLink({ href: url }).run();
                }
                this.showLinkModal = false;
            },

            cancelLink() {
                this.showLinkModal = false;
            },

            openInternalLinkModal() {
                if (!editor) return;
                window.activeTiptapEditor = editor;
                const { state } = editor;
                const { from, to } = state.selection;
                const selectedText = from !== to ? state.doc.textBetween(from, to, ' ') : '';

                window.dispatchEvent(new CustomEvent('buka-modal-link', {
                    detail: { text: selectedText }
                }));
            }
        };
    });
});

document.addEventListener('alpine:init', () => {
    Alpine.data('pageEditor', (initialLocales, initialSplit, localesCount, wireInstance) => ({
        layoutMode: 'single',
        singleActiveLang: initialLocales[0] || 'id',
        splitLanguages: initialSplit,
        allLocalesCount: localesCount,

        addSplitLang(lang) {
            let maxAllowed = (window.innerWidth > 1440 && this.allLocalesCount >= 3) ? 3 : 2;
            if (lang && !this.splitLanguages.includes(lang) && this.splitLanguages.length < maxAllowed) {
                this.splitLanguages.push(lang);
            }
        },
        removeSplitLang(lang) {
            if (this.splitLanguages.length > 1) {
                this.splitLanguages = this.splitLanguages.filter(l => l !== lang);
            }
        },
        handleSort(itemIds) {
            let ids = Array.isArray(itemIds) ? itemIds : Array.from(itemIds);
            let cleanIds = ids.map(id => String(id).split("'").join("").split('"').join("").trim());
            
            // 🌟 KUNCI UTAMA: Perbarui urutan wireInstance.content secara lokal terlebih dahulu
            // agar Livewire dan DOM tidak mengalami bentrok reaktivitas (mencegah snap-back).
            let currentContent = wireInstance.content || [];
            let map = new Map(currentContent.map(block => [String(block.id).trim(), block]));
            let reordered = cleanIds.map(id => map.get(String(id).trim())).filter(Boolean);
            
            if (reordered.length > 0) {
                wireInstance.content = reordered;
                wireInstance.updateBlockOrder(cleanIds);
            }
        },
        addNewBlock(type) {
            let currentDomIds = Array.from(document.querySelectorAll("[x-sort\\:item]")).map(el => {
                return el.getAttribute("x-sort:item").split("'").join("").split('"').join("").trim();
            });
            wireInstance.addBlockWithOrder(type, currentDomIds);
        }
    }));
});

// document.addEventListener('alpine:init', () => {
//     Alpine.data('pageEditor', (initialLocales, initialSplit, localesCount) => ({
//         layoutMode: 'single',
//         singleActiveLang: initialLocales[0] || 'id',
//         splitLanguages: initialSplit,
//         allLocalesCount: localesCount,

//         addSplitLang(lang) {
//             let maxAllowed = (window.innerWidth > 1440 && this.allLocalesCount >= 3) ? 3 : 2;
//             if (lang && !this.splitLanguages.includes(lang) && this.splitLanguages.length < maxAllowed) {
//                 this.splitLanguages.push(lang);
//             }
//         },
//         removeSplitLang(lang) {
//             if (this.splitLanguages.length > 1) {
//                 this.splitLanguages = this.splitLanguages.filter(l => l !== lang);
//             }
//         },
//         handleSort(itemIds) {
//             let ids = Array.isArray(itemIds) ? itemIds : Array.from(itemIds);
//             let cleanIds = ids.map(id => String(id).split("'").join("").split('"').join("").trim());
//             $wire.updateBlockOrder(cleanIds);
//         },
//         addNewBlock(type) {
//             let currentDomIds = Array.from(document.querySelectorAll("[x-sort\\:item]")).map(el => {
//                 return el.getAttribute("x-sort:item").split("'").join("").split('"').join("").trim();
//             });
//             $wire.addBlockWithOrder(type, currentDomIds);
//         }
//     }));
// });