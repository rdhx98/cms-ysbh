import { Node, mergeAttributes } from '@tiptap/core';
import { Plugin } from '@tiptap/pm/state';

export const SectionBlock = Node.create({
    name: 'sectionBlock',
    group: 'block',
    content: 'block+',
    defining: true,

    addAttributes() {
        return {
            bgColor: {
                default: '#E9F1EB',
                parseHTML: element => element.getAttribute('data-bg-color') || '#E9F1EB',
                renderHTML: attributes => ({ 'data-bg-color': attributes.bgColor })
            },
            innerBgColor: {
                default: 'transparent',
                parseHTML: element => element.getAttribute('data-inner-color') || 'transparent',
                renderHTML: attributes => ({ 'data-inner-color': attributes.innerBgColor })
            }
        }
    },

    parseHTML() {
        return [
            { tag: 'section[data-type="section-block"]' },
        ]
    },

    renderHTML({ HTMLAttributes }) {
        const bgColor = HTMLAttributes['data-bg-color'] || '#E9F1EB';
        const innerColor = HTMLAttributes['data-inner-color'] || 'transparent';
        const isCard = innerColor !== 'transparent';

        // 🌟 KITA KEMBALI KE STRUKTUR 2 LAPIS 🌟
        return [
            'section',
            mergeAttributes(HTMLAttributes, {
                'data-type': 'section-block',
                // OUTER: Mengurus batas 7xl dan Padding utama Seksi
                class: 'tiptap-full-bleed max-w-7xl mx-auto px-[2.5rem] relative py-10 sm:py-12',
                style: `--bg-outer: ${bgColor};`
            }),
            [
                'div',
                {
                    // INNER: Mengurus bentuk Kartu dan mencegah konten meluber (flow-root)
                    class: `transition-colors duration-300 flow-root ${isCard ? 'p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200/80' : ''}`,
                    style: `background-color: ${innerColor};`
                },
                0 // 0 adalah lubang untuk teks
            ]
        ];
    },


    addNodeView() {
        return ({ node, editor, getPos }) => {
            const dom = document.createElement('section');
            dom.className = `tiptap-full-bleed max-w-7xl mx-auto px-5 sm:px-8 relative py-10 sm:py-12`;
            dom.style.cssText = `--bg-outer: ${node.attrs.bgColor};`;
            dom.dataset.type = 'section-block';

            const menuWrapper = document.createElement('div');
            menuWrapper.contentEditable = 'false';
            menuWrapper.style.cssText = `
                position: absolute; top: 12px; right: 20px;
                display: flex; flex-direction: column; align-items: flex-end; gap: 8px;
                z-index: 50;
            `;

            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.title = 'Pengaturan Warna Section';
            toggleBtn.style.cssText = `
                width: 32px; height: 32px; border-radius: 50%;
                background-color: ${node.attrs.bgColor};
                border: 2px solid #e4e4e7;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                cursor: pointer; display: flex; align-items: center; justify-content: center;
                transition: transform 0.2s;
            `;
            toggleBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#52525b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9 9 0 0 1 0-18c4.97 0 9 3.5 9 5.5 0 2.8-2.96 4-5 4a3 3 0 0 1-3-3 1 1 0 0 0-2 0 3 3 0 0 1-3 3c-2.04 0-5-1.2-5-4 0-2 4.03-5.5 9-5.5"/><circle cx="8" cy="9" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="16" cy="9" r="1"/></svg>`;

            const toolbar = document.createElement('div');
            toolbar.className = 'section-color-toolbar';
            toolbar.style.cssText = `
                display: none; 
                flex-direction: column; gap: 10px;
                background: #ffffff; padding: 10px 12px;
                border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                font-family: 'Plus Jakarta Sans', sans-serif; font-size: 11px; color: #52525b;
                border: 1px solid #e4e4e7;
                opacity: 1 !important;
                visibility: visible !important;
                z-index: 999 !important;
            `;

            let isMenuOpen = false;

            // 1. Sakelar Menu
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                isMenuOpen = !isMenuOpen;
                if (isMenuOpen) {
                    toolbar.style.setProperty('display', 'flex', 'important');
                    toggleBtn.style.borderColor = '#064F3B'; 
                } else {
                    toolbar.style.setProperty('display', 'none', 'important');
                    toggleBtn.style.borderColor = '#e4e4e7';
                }
            });

            // 2. Mencegah Tiptap mencuri klik
            menuWrapper.addEventListener('mousedown', (e) => {
                e.stopPropagation();
            });

            // 🌟 SOLUSI 1: Logika Klik di Luar Menu
            const handleOutsideClick = (e) => {
                // Jika menu sedang terbuka, DAN yang diklik bukanlah bagian dari menuWrapper
                if (isMenuOpen && !menuWrapper.contains(e.target)) {
                    isMenuOpen = false;
                    toolbar.style.setProperty('display', 'none', 'important');
                    toggleBtn.style.borderColor = '#e4e4e7';
                }
            };
            document.addEventListener('mousedown', handleOutsideClick);

            const colorsOuter = ['#ffffff', '#FBF7EA', '#E9F1EB', '#F7EBAF'];
            const colorsInner = ['transparent', '#ffffff', '#FBF7EA', '#064F3B'];

            const buttonRefs = { bgColor: [], innerBgColor: [] };

            const createColorRow = (label, colors, attrName) => {
                const row = document.createElement('div');
                row.innerHTML = `<strong style="display:block; margin-bottom:6px;">${label}</strong>`;
                const btnGroup = document.createElement('div');
                btnGroup.style.cssText = 'display: flex; gap: 8px;';

                colors.forEach(c => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.dataset.color = c;

                    if (c === 'transparent') {
                        btn.style.cssText = `width:24px; height:24px; border-radius:50%; border:1px solid #d4d4d8; cursor:pointer; background-image: linear-gradient(45deg, #e5e7eb 25%, transparent 25%), linear-gradient(-45deg, #e5e7eb 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e5e7eb 75%), linear-gradient(-45deg, transparent 75%, #e5e7eb 75%); background-size: 8px 8px; background-position: 0 0, 0 4px, 4px -4px, -4px 0px;`;
                    } else {
                        btn.style.cssText = `width:24px; height:24px; border-radius:50%; background:${c}; border:1px solid #d4d4d8; cursor:pointer;`;
                    }

                    if (node.attrs[attrName] === c) {
                        btn.style.outline = '2px solid #064F3B';
                        btn.style.outlineOffset = '2px';
                    }

                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();

                        if (typeof getPos === 'function') {
                            // 🌟 SOLUSI 2: Update Atribut TANPA mengubah seleksi kursor!
                            // Memakai Transaksi ProseMirror langsung (setNodeMarkup) mencegah lompatan gulir
                            const tr = editor.state.tr.setNodeMarkup(getPos(), null, {
                                ...node.attrs,
                                [attrName]: c
                            });
                            editor.view.dispatch(tr);
                        }
                    });
                    
                    buttonRefs[attrName].push(btn);
                    btnGroup.appendChild(btn);
                });
                row.appendChild(btnGroup);
                return row;
            }

            toolbar.appendChild(createColorRow('Latar Layar Penuh', colorsOuter, 'bgColor'));
            toolbar.appendChild(createColorRow('Latar Kotak Dalam', colorsInner, 'innerBgColor'));

            menuWrapper.appendChild(toggleBtn);
            menuWrapper.appendChild(toolbar);
            dom.appendChild(menuWrapper);

            const contentDOM = document.createElement('div');
            const isCard = node.attrs.innerBgColor !== 'transparent';
            contentDOM.className = `transition-colors duration-300 flow-root ${isCard ? 'p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200/80' : ''}`;
            contentDOM.style.cssText = `background-color: ${node.attrs.innerBgColor};`;

            if (node.attrs.innerBgColor === '#064F3B') {
                contentDOM.style.color = '#ffffff';
            }

            dom.appendChild(contentDOM);

            return {
                dom,
                contentDOM,
                update: (updatedNode) => {
                    if (updatedNode.type.name !== 'sectionBlock') return false;
                    node = updatedNode;

                    const updatedIsCard = updatedNode.attrs.innerBgColor !== 'transparent';
                    dom.style.cssText = `--bg-outer: ${updatedNode.attrs.bgColor};`;
                    contentDOM.className = `transition-colors duration-300 flow-root ${updatedIsCard ? 'p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200/80' : ''}`;
                    contentDOM.style.cssText = `background-color: ${updatedNode.attrs.innerBgColor};`;
                    contentDOM.style.color = updatedNode.attrs.innerBgColor === '#064F3B' ? '#ffffff' : '';
                    toggleBtn.style.backgroundColor = updatedNode.attrs.bgColor;

                    ['bgColor', 'innerBgColor'].forEach(attrName => {
                        buttonRefs[attrName].forEach(btn => {
                            const isActive = updatedNode.attrs[attrName] === btn.dataset.color;
                            btn.style.outline = isActive ? '2px solid #064F3B' : '';
                            btn.style.outlineOffset = isActive ? '2px' : '';
                        });
                    });

                    return true;
                },
                ignoreMutation: (mutation) => {
                    if (menuWrapper.contains(mutation.target)) return true; 
                    return false;
                },
                stopEvent: (event) => {
                    if (menuWrapper.contains(event.target)) return true;
                    return false;
                },
                // 🌟 SOLUSI 1 (Lanjutan): Pembersih Memori Wajib
                destroy: () => {
                    document.removeEventListener('mousedown', handleOutsideClick);
                }
            }
        }
    },

    // addNodeView() {
    //     return ({ node, editor, getPos }) => {
    //         // ==========================================
    //         // 1. DOM OUTER (Menempel dengan Drag Handle)
    //         // ==========================================
    //         const dom = document.createElement('section');
            
    //         dom.className = `tiptap-full-bleed max-w-7xl mx-auto px-5 sm:px-8 relative py-10 sm:py-12`;
    //         dom.style.cssText = `--bg-outer: ${node.attrs.bgColor};`;
    //         dom.dataset.type = 'section-block';

    //         // --- 1. WRAPPER MENU ---
    //         // Membungkus tombol indikator dan palet warna agar posisinya teratur
    //         const menuWrapper = document.createElement('div');
    //         menuWrapper.contentEditable = 'false';
    //         menuWrapper.style.cssText = `
    //             position: absolute; top: 12px; right: 20px;
    //             display: flex; flex-direction: column; align-items: flex-end; gap: 8px;
    //             z-index: 50;
    //         `;

    //         // --- 2. TOMBOL INDIKATOR (TOGGLE) ---
    //         const toggleBtn = document.createElement('button');
    //         toggleBtn.type = 'button';
    //         toggleBtn.title = 'Pengaturan Warna Section';
    //         // Desain tombol bulat, warnanya mengikuti warna outer background saat ini
    //         toggleBtn.style.cssText = `
    //             width: 32px; height: 32px; border-radius: 50%;
    //             background-color: ${node.attrs.bgColor};
    //             border: 2px solid #e4e4e7;
    //             box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    //             cursor: pointer; display: flex; align-items: center; justify-content: center;
    //             transition: transform 0.2s;
    //         `;
    //         // Ikon palet warna kecil
    //         toggleBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#52525b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9 9 0 0 1 0-18c4.97 0 9 3.5 9 5.5 0 2.8-2.96 4-5 4a3 3 0 0 1-3-3 1 1 0 0 0-2 0 3 3 0 0 1-3 3c-2.04 0-5-1.2-5-4 0-2 4.03-5.5 9-5.5"/><circle cx="8" cy="9" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="16" cy="9" r="1"/></svg>`;

    //         // --- 3. MENU PALET WARNA ---
    //         const toolbar = document.createElement('div');
    //         toolbar.className = 'section-color-toolbar';
    //         // Default disembunyikan
    //         toolbar.style.cssText = `
    //             display: none; 
    //             flex-direction: column; gap: 10px;
    //             background: #ffffff; padding: 10px 12px;
    //             border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    //             font-family: 'Plus Jakarta Sans', sans-serif; font-size: 11px; color: #52525b;
    //             border: 1px solid #e4e4e7;
    //             /* 🌟 TAMBAHAN: Paksa agar tidak terpengaruh CSS opacity dari luar */
    //             opacity: 1 !important;
    //             visibility: visible !important;
    //             z-index: 999 !important;
    //         `;

    //         let isMenuOpen = false;

    //         // 1. Logika Klik Sakelar
    //         toggleBtn.addEventListener('click', (e) => {
    //             isMenuOpen = !isMenuOpen;
    //             if (isMenuOpen) {
    //                 toolbar.style.setProperty('display', 'flex', 'important');
    //                 toggleBtn.style.borderColor = '#064F3B'; // Hijau tanda aktif
    //             } else {
    //                 toolbar.style.setProperty('display', 'none', 'important');
    //                 toggleBtn.style.borderColor = '#e4e4e7'; // Abu-abu
    //             }
    //         });

    //         // 2. Tahan mousedown agar Tiptap tidak mencuri klik dan membuat editor kehilangan fokus
    //         menuWrapper.addEventListener('mousedown', (e) => {
    //             e.stopPropagation();
    //         });
    //         // toggleBtn.addEventListener('mousedown', (e) => {
    //         //     e.preventDefault();
    //         //     e.stopPropagation(); // Hentikan event agar tidak disedot oleh Tiptap

    //         //     isMenuOpen = !isMenuOpen;
    //         //     toolbar.style.display = isMenuOpen ? 'flex' : 'none';
    //         //     console.log('button pinched');
    //         // });
    //         // toggleBtn.addEventListener('click', (e) => {
    //         //     e.preventDefault();
    //         //     isMenuOpen = !isMenuOpen;
    //         //     toolbar.style.display = isMenuOpen ? 'flex' : 'none';
    //         // });

    //         const colorsOuter = ['#ffffff', '#FBF7EA', '#E9F1EB', '#F7EBAF'];
    //         const colorsInner = ['transparent', '#ffffff', '#FBF7EA', '#064F3B'];

    //         const buttonRefs = { bgColor: [], innerBgColor: [] };

    //         const createColorRow = (label, colors, attrName) => {
    //             const row = document.createElement('div');
    //             row.innerHTML = `<strong style="display:block; margin-bottom:6px;">${label}</strong>`;
    //             const btnGroup = document.createElement('div');
    //             btnGroup.style.cssText = 'display: flex; gap: 8px;';

    //             colors.forEach(c => {
    //                 const btn = document.createElement('button');
    //                 btn.type = 'button';
    //                 btn.dataset.color = c;

    //                 if (c === 'transparent') {
    //                     btn.style.cssText = `width:24px; height:24px; border-radius:50%; border:1px solid #d4d4d8; cursor:pointer; background-image: linear-gradient(45deg, #e5e7eb 25%, transparent 25%), linear-gradient(-45deg, #e5e7eb 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e5e7eb 75%), linear-gradient(-45deg, transparent 75%, #e5e7eb 75%); background-size: 8px 8px; background-position: 0 0, 0 4px, 4px -4px, -4px 0px;`;
    //                 } else {
    //                     btn.style.cssText = `width:24px; height:24px; border-radius:50%; background:${c}; border:1px solid #d4d4d8; cursor:pointer;`;
    //                 }

    //                 if (node.attrs[attrName] === c) {
    //                     btn.style.outline = '2px solid #064F3B';
    //                     btn.style.outlineOffset = '2px';
    //                 }

    //                 btn.addEventListener('click', () => {
    //                     if (typeof getPos === 'function') {
    //                         editor.chain().setNodeSelection(getPos()).updateAttributes('sectionBlock', { [attrName]: c }).run();
    //                     }
    //                 });
    //                 buttonRefs[attrName].push(btn);
    //                 btnGroup.appendChild(btn);
    //             });
    //             row.appendChild(btnGroup);
    //             return row;
    //         }

    //         toolbar.appendChild(createColorRow('Latar Layar Penuh', colorsOuter, 'bgColor'));
    //         toolbar.appendChild(createColorRow('Latar Kotak Dalam', colorsInner, 'innerBgColor'));

    //         // Masukkan tombol dan toolbar ke dalam wrapper, lalu tempel wrapper ke DOM outer
    //         menuWrapper.appendChild(toggleBtn);
    //         menuWrapper.appendChild(toolbar);
    //         dom.appendChild(menuWrapper);

    //         // ==========================================
    //         // 2. DOM INNER (Area Teks Penulis)
    //         // ==========================================
    //         const contentDOM = document.createElement('div');
    //         const isCard = node.attrs.innerBgColor !== 'transparent';

    //         contentDOM.className = `transition-colors duration-300 flow-root ${isCard ? 'p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200/80' : ''}`;
    //         contentDOM.style.cssText = `background-color: ${node.attrs.innerBgColor};`;

    //         if (node.attrs.innerBgColor === '#064F3B') {
    //             contentDOM.style.color = '#ffffff';
    //         }

    //         dom.appendChild(contentDOM);

    //         return {
    //             dom,
    //             contentDOM,
    //             update: (updatedNode) => {
    //                 if (updatedNode.type.name !== 'sectionBlock') return false;

    //                 // WAJIB: Perbarui referensi node!
    //                 node = updatedNode;

    //                 const updatedIsCard = updatedNode.attrs.innerBgColor !== 'transparent';
                    
    //                 dom.style.cssText = `--bg-outer: ${updatedNode.attrs.bgColor};`;
    //                 contentDOM.className = `transition-colors duration-300 flow-root ${updatedIsCard ? 'p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200/80' : ''}`;
    //                 contentDOM.style.cssText = `background-color: ${updatedNode.attrs.innerBgColor};`;
    //                 contentDOM.style.color = updatedNode.attrs.innerBgColor === '#064F3B' ? '#ffffff' : '';

    //                 // 🌟 PERBAIKAN: Ubah warna tombol indikator saat warna section diubah
    //                 toggleBtn.style.backgroundColor = updatedNode.attrs.bgColor;

    //                 ['bgColor', 'innerBgColor'].forEach(attrName => {
    //                     buttonRefs[attrName].forEach(btn => {
    //                         const isActive = updatedNode.attrs[attrName] === btn.dataset.color;
    //                         btn.style.outline = isActive ? '2px solid #064F3B' : '';
    //                         btn.style.outlineOffset = isActive ? '2px' : '';
    //                     });
    //                 });

    //                 return true;
    //             },
    //             // 🌟 PELINDUNG 1: Abaikan Perubahan UI
    //             // Mencegah Tiptap menghapus menu saat display berubah dari 'none' ke 'flex'
    //             ignoreMutation: (mutation) => {
    //                 if (menuWrapper.contains(mutation.target)) {
    //                     return true; 
    //                 }
    //                 return false;
    //             },

    //             // 🌟 PELINDUNG 2: Kebal Event
    //             stopEvent: (event) => {
    //                 // Memblokir ProseMirror agar tidak mempedulikan klik di area menu
    //                 if (menuWrapper.contains(event.target)) {
    //                     return true;
    //                 }
    //                 return false;
    //             }
    //         }
    //     }
    // },

    addCommands() {
        return {
            setSectionBlock: () => ({ state, chain, dispatch }) => {
                // 🛑 LARANGAN BARU: SectionBlock tidak boleh di dalam Kolom
                const { $from } = state.selection;
                for (let depth = $from.depth; depth > 0; depth--) {
                    const ancestorType = $from.node(depth).type.name;
                    if (ancestorType === 'column' || ancestorType === 'columnBlock') {
                        window.dispatchEvent(new CustomEvent('tampilkan-notifikasi', {
                            detail: {
                                message: 'Section Block tidak bisa dibuat di dalam tata letak kolom.',
                                type: 'warning'
                            }
                        }));
                        return false; // Hentikan perintah
                    }
                }

                // 🛑 BATASAN LAMA: Gunakan this.editor agar tidak undefined
                if (this.editor.isActive(this.name)) {
                    return false;
                }

                const { selection } = state;
                const { from, to } = selection;
                const sectionName = this.name;

                let isSafeToWrap = true;

                state.doc.nodesBetween(from, to, (node) => {
                    const safeNodes = [
                        'paragraph', 'heading', 'text', 'doc', 'hardBreak', 
                        'eyebrow', 'taskList', 'taskItem', 'bulletList', 'orderedList', 'listItem'
                    ];
                    if (!safeNodes.includes(node.type.name)) {
                        isSafeToWrap = false;
                    }
                });

                // 🌟 PERBAIKAN 2: Penyelamat Kursor yang Tahan Banting (Anti Dry-Run Bug)
                // Kita tambahkan parameter dispatch dan membungkus tr.insert di dalamnya
                const insertEscapeHatch = ({ tr, state, dispatch }) => {
                    if (dispatch) {
                        const depth = tr.selection.$from.depth;
                        for (let d = depth; d > 0; d--) {
                            if (tr.selection.$from.node(d).type.name === sectionName) {
                                const endPos = tr.selection.$from.after(d);
                                if (endPos === tr.doc.content.size) {
                                    tr.insert(endPos, state.schema.nodes.paragraph.create());
                                }
                                break;
                            }
                        }
                    }
                    return true; // Beritahu Tiptap bahwa simulasi fungsi ini selalu valid
                };

                // 3. EKSEKUSI
                // (Kita hilangkan selectParentNode karena wrapIn secara alami sudah membungkus blok induk teks saat ini)
                if (isSafeToWrap) {
                    return chain()
                        .wrapIn(sectionName)
                        .command(insertEscapeHatch)
                        .focus()
                        .run();
                } else {
                    return chain()
                        .insertContent({
                            type: sectionName,
                            content: [{ type: 'paragraph' }]
                        })
                        .command(insertEscapeHatch)
                        .focus()
                        .run();
                }
            },
        }
    },
    
    addProseMirrorPlugins() {
        return [
            new Plugin({
                filterTransaction: (tr) => {
                    if (!tr.docChanged) return true;

                    let isValid = true;
                    tr.doc.descendants((node, pos) => {
                        if (!isValid) return false;
                        if (node.type.name === 'sectionBlock') {
                            const $pos = tr.doc.resolve(pos);
                            for (let d = $pos.depth; d > 0; d--) {
                                const ancestorType = $pos.node(d).type.name;
                                if (ancestorType === 'column' || ancestorType === 'columnBlock') {
                                    isValid = false;
                                    break;
                                }
                            }
                        }
                    });

                    return isValid; // false = transaksi ditolak total, dokumen tidak berubah
                }
            })
        ];
    },
});
