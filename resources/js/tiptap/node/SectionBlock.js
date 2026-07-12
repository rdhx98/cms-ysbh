import { Node, mergeAttributes } from '@tiptap/core';

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
            const isCard = node.attrs.innerBgColor !== 'transparent';
            
            // 1. Setup Wrapper Utama
            dom.className = `tiptap-full-bleed max-w-7xl mx-auto px-5 sm:px-8 relative py-10 sm:py-12 pb-20`;
            dom.style.cssText = `--bg-outer: ${node.attrs.bgColor};`;
            dom.dataset.type = 'section-block';

            // 2. Fungsi Pembantu (Didefinisikan DI DALAM addNodeView agar tidak error scope)
            const createColorRow = (label, colors, attrName) => {
                const row = document.createElement('div');
                row.innerHTML = `<strong style="display:block; margin-bottom:6px;">${label}</strong>`;
                const btnGroup = document.createElement('div');
                btnGroup.style.cssText = 'display: flex; gap: 8px;';

                colors.forEach(c => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.style.cssText = `width:24px; height:24px; border-radius:50%; background:${c === 'transparent' ? '#f4f4f5' : c}; border:1px solid #d4d4d8; cursor:pointer;`;
                    
                    if (node.attrs[attrName] === c) { 
                        btn.style.outline = '2px solid #064F3B'; 
                        btn.style.outlineOffset = '2px'; 
                    }

                    btn.addEventListener('click', () => {
                        if (typeof getPos === 'function') {
                            editor.chain().setNodeSelection(getPos()).updateAttributes('sectionBlock', { [attrName]: c }).run();
                        }
                    });
                    btnGroup.appendChild(btn);
                });
                row.appendChild(btnGroup);
                return row;
            };

            // 3. Setup Toolbar (Sesuai keinginan: Bawah, kiri, horizontal)
            const toolbar = document.createElement('div');
            toolbar.contentEditable = 'false';
            toolbar.style.cssText = `
                position: absolute; bottom: 0px; left: 20px;
                display: flex; gap: 20px;
                background: #ffffff; padding: 10px 16px;
                border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                font-family: sans-serif; font-size: 11px; color: #52525b;
                z-index: 50; border: 1px solid #e4e4e7;
            `;

            const colorsOuter = ['#ffffff', '#FBF7EA', '#E9F1EB', '#F7EBAF'];
            const colorsInner = ['transparent', '#ffffff', '#FBF7EA', '#064F3B'];

            toolbar.appendChild(createColorRow('Latar Luar', colorsOuter, 'bgColor'));
            toolbar.appendChild(createColorRow('Latar Dalam', colorsInner, 'innerBgColor'));
            dom.appendChild(toolbar);

            // 4. Setup Area Konten
            const contentDOM = document.createElement('div');
            contentDOM.className = `transition-colors duration-300 flow-root ${isCard ? 'p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200/80' : ''}`;
            contentDOM.style.backgroundColor = node.attrs.innerBgColor;
            
            if (node.attrs.innerBgColor === '#064F3B') {
                contentDOM.style.color = '#ffffff';
            }

            dom.appendChild(contentDOM);

            return { dom, contentDOM }
        }
    },

    // Padding fed up
    // addNodeView() {
    //     return ({ node, editor, getPos }) => {
    //         // 1. DOM Section (Elemen Utama)
    //         const dom = document.createElement('section');
    //         const isCard = node.attrs.innerBgColor !== 'transparent';
    //         dom.className = `tiptap-full-bleed max-w-7xl mx-auto px-5 sm:px-8 relative py-10 sm:py-12 pb-20`;
    //         dom.style.cssText = `--bg-outer: ${node.attrs.bgColor};`;
    //         dom.dataset.type = 'section-block';

    //         // 2. Fungsi Pembantu untuk Toolbar
    //         const createColorRow = (label, colors, attrName) => {
    //             const row = document.createElement('div');
    //             row.innerHTML = `<strong style="display:block; margin-bottom:6px;">${label}</strong>`;
    //             const btnGroup = document.createElement('div');
    //             btnGroup.style.cssText = 'display: flex; gap: 8px;';

    //             colors.forEach(c => {
    //                 const btn = document.createElement('button');
    //                 btn.type = 'button';
    //                 btn.style.cssText = `width:24px; height:24px; border-radius:50%; background:${c === 'transparent' ? '#f4f4f5' : c}; border:1px solid #d4d4d8; cursor:pointer;`;
                    
    //                 if (node.attrs[attrName] === c) { 
    //                     btn.style.outline = '2px solid #064F3B'; 
    //                     btn.style.outlineOffset = '2px'; 
    //                 }

    //                 btn.addEventListener('click', () => {
    //                     if (typeof getPos === 'function') {
    //                         editor.chain().setNodeSelection(getPos()).updateAttributes('sectionBlock', { [attrName]: c }).run();
    //                     }
    //                 });
    //                 btnGroup.appendChild(btn);
    //             });
    //             row.appendChild(btnGroup);
    //             return row;
    //         };

    //         // 3. Setup Toolbar (Dengan efek hover)
    //         const toolbar = document.createElement('div');
    //         toolbar.contentEditable = 'false';
    //         toolbar.className = 'opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-300';
    //         toolbar.style.cssText = `
    //             position: absolute; bottom: 0px; left: 20px;
    //             display: flex; gap: 20px;
    //             background: #ffffff; padding: 10px 16px;
    //             border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    //             font-family: sans-serif; font-size: 11px; color: #52525b;
    //             z-index: 50; border: 1px solid #e4e4e7;
    //         `;

    //         toolbar.appendChild(createColorRow('Latar Luar', ['#ffffff', '#FBF7EA', '#E9F1EB', '#F7EBAF'], 'bgColor'));
    //         toolbar.appendChild(createColorRow('Latar Dalam', ['transparent', '#ffffff', '#FBF7EA', '#064F3B'], 'innerBgColor'));

    //         // 4. Setup Area Konten
    //         const contentDOM = document.createElement('div');
    //         contentDOM.className = `transition-colors duration-300 flow-root ${isCard ? 'p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200/80' : ''}`;
    //         contentDOM.style.backgroundColor = node.attrs.innerBgColor;
    //         if (node.attrs.innerBgColor === '#064F3B') contentDOM.style.color = '#ffffff';

    //         dom.appendChild(contentDOM);

    //         // 5. Wrapper sebagai Anchor agar hover mendeteksi section + toolbar
    //         const wrapper = document.createElement('div');
    //         // wrapper.className = 'group relative';
    //         wrapper.className = 'group relative w-full tiptap-full-bleed max-w-7xl mx-auto px-5 sm:px-8 py-10 sm:py-12 pb-20';
    //         wrapper.appendChild(toolbar);
    //         wrapper.appendChild(dom);

    //         return { dom: wrapper, contentDOM };
    //     }
    // },

    addNodeView() {
        return ({ node, editor, getPos }) => {
            // ==========================================
            // 1. DOM OUTER (Menempel dengan Drag Handle)
            // ==========================================
            const dom = document.createElement('section');
            const isCard = node.attrs.innerBgColor !== 'transparent';
            
            dom.className = `tiptap-full-bleed max-w-7xl mx-auto px-5 sm:px-8 relative py-10 sm:py-12`;
            dom.style.cssText = `--bg-outer: ${node.attrs.bgColor};`;
            dom.dataset.type = 'section-block';

            // --- MENU PALET WARNA (Mini Toolbar) ---
            const toolbar = document.createElement('div');
            toolbar.contentEditable = 'false';
            toolbar.className = 'section-color-toolbar';
            toolbar.style.cssText = `
                position: absolute; top: 12px; right: 20px;
                display: flex; flex-direction: column; gap: 10px;
                background: #ffffff; padding: 10px 12px;
                border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                font-family: 'Plus Jakarta Sans', sans-serif; font-size: 11px; color: #52525b;
                z-index: 50; border: 1px solid #e4e4e7;
            `;

            const colorsOuter = ['#ffffff', '#FBF7EA', '#E9F1EB', '#F7EBAF'];
            const colorsInner = ['transparent', '#ffffff', '#FBF7EA', '#064F3B'];

            const createColorRow = (label, colors, attrName) => {
                const row = document.createElement('div');
                row.innerHTML = `<strong style="display:block; margin-bottom:6px;">${label}</strong>`;
                const btnGroup = document.createElement('div');
                btnGroup.style.cssText = 'display: flex; gap: 8px;';

                colors.forEach(c => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    
                    if (c === 'transparent') {
                        btn.style.cssText = `width:24px; height:24px; border-radius:50%; border:1px solid #d4d4d8; cursor:pointer; background-image: linear-gradient(45deg, #e5e7eb 25%, transparent 25%), linear-gradient(-45deg, #e5e7eb 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e5e7eb 75%), linear-gradient(-45deg, transparent 75%, #e5e7eb 75%); background-size: 8px 8px; background-position: 0 0, 0 4px, 4px -4px, -4px 0px;`;
                    } else {
                        btn.style.cssText = `width:24px; height:24px; border-radius:50%; background:${c}; border:1px solid #d4d4d8; cursor:pointer;`;
                    }
                    
                    if (node.attrs[attrName] === c) { 
                        btn.style.outline = '2px solid #064F3B'; 
                        btn.style.outlineOffset = '2px'; 
                    }

                    btn.addEventListener('click', () => {
                        if (typeof getPos === 'function') {
                            editor.chain().setNodeSelection(getPos()).updateAttributes('sectionBlock', { [attrName]: c }).run();
                        }
                    });
                    btnGroup.appendChild(btn);
                });
                row.appendChild(btnGroup);
                return row;
            }

            toolbar.appendChild(createColorRow('Latar Layar Penuh', colorsOuter, 'bgColor'));
            toolbar.appendChild(createColorRow('Latar Kotak Dalam', colorsInner, 'innerBgColor'));
            dom.appendChild(toolbar);

            // ==========================================
            // 2. DOM INNER (Area Teks Penulis)
            // ==========================================
            const contentDOM = document.createElement('div');
            
            // PENTING: Class `flow-root` adalah kunci untuk mencegah teks meluber di sumbu Y (Margin Collapsing)
            contentDOM.className = `transition-colors duration-300 flow-root ${isCard ? 'p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200/80' : ''}`;
            contentDOM.style.cssText = `background-color: ${node.attrs.innerBgColor};`;
            
            // Auto ubah teks jadi putih jika latar kartu berwarna Forest (Hijau Gelap)
            if (node.attrs.innerBgColor === '#064F3B') {
                contentDOM.style.color = '#ffffff';
            }

            // Masukkan Inner ke dalam Outer
            dom.appendChild(contentDOM);

            return { dom, contentDOM }
        }
    },

    addCommands() {
        return {
            setSectionBlock: () => ({ commands }) => {
                return commands.insertContent([{ type: this.name, content: [{ type: 'paragraph' }] }, { type: 'paragraph' }])
            },
        }
    },
});