import { Node, mergeAttributes } from '@tiptap/core';

// 🌟 SINGLE SOURCE OF TRUTH: Deklarasikan warna 1 KALI SAJA di sini
const CARD_COLORS = {
    white:      { hex: '#FFFFFF', twClass: 'bg-white',      dotClass: 'bg-white border border-zinc-300' },
    gold:       { hex: '#EBCC26', twClass: 'bg-goldy',      dotClass: 'bg-goldy' },
    softGold:   { hex: '#F7EBAF', twClass: 'bg-[#F7EBAF]',  dotClass: 'bg-[#F7EBAF]' },
    forest:     { hex: '#064F3B', twClass: 'bg-foresty',    dotClass: 'bg-foresty' },
    softForest: { hex: '#E9F1EB', twClass: 'bg-[#E9F1EB]',  dotClass: 'bg-[#E9F1EB]' },
    coral:      { hex: '#E42326', twClass: 'bg-coral',      dotClass: 'bg-coral' },
    softCoral:  { hex: '#FBE6E6', twClass: 'bg-[#FBE6E6]',  dotClass: 'bg-[#FBE6E6]' },
};

// Ambil semua kunci/nama warna untuk memudahkan looping
const colorKeys = Object.keys(CARD_COLORS);

export const Card = Node.create({
    name: 'card',
    group: 'block',
    content: 'block+', 
    isolating: true,

    addAttributes() {
        return {
            // bgColor: {
            //     default: 'white',
            // },
            bgColor: {
                default: 'white',
                parseHTML: element => element.getAttribute('bgcolor') || 'white',
                renderHTML: attributes => {
                    // 🌟 Gunakan HEX untuk disimpan ke Database agar abadi
                    const colorData = CARD_COLORS[attributes.bgColor] || CARD_COLORS.white;
                    return {
                        'bgcolor': attributes.bgColor, // Simpan nama aslinya sbg penanda
                        'style': `background-color: ${colorData.hex};` // Terjemahkan ke warna asli
                    };
                }
            },
            // 🌟 ATRIBUT BARU: Menyimpan status tinggi kartu
            fillHeight: {
                default: false,
                parseHTML: element => element.getAttribute('data-fill-height') !== 'false',
                renderHTML: attributes => ({
                    'data-fill-height': attributes.fillHeight,
                })
            }
        };
    },

    parseHTML() {
        return [{ tag: 'div[data-type="card"]' }];
    },

    // // ---------------------------------------------------------
    // // TAMPILAN PUBLIK (FRONTEND)
    // // ---------------------------------------------------------
    // renderHTML({ HTMLAttributes }) {
    //     const bgColorMap = {
    //         white: 'background-color: #FFFFFF;',
    //         gold: 'background-color: #F7EBAF;',
    //         forest: 'background-color: #E9F1EB;',
    //         coral: 'background-color: #FBE6E6;'
    //     };

    //     const activeBgColor = bgColorMap[HTMLAttributes.bgColor] || bgColorMap.white;
    //     // 🌟 LOGIKA: Tambahkan class h-full jika fillHeight bernilai true
    //     const hClass = HTMLAttributes['data-fill-height'] === false ? '' : 'h-full';

    //     return [
    //         'div',
    //         mergeAttributes(HTMLAttributes, {
    //             'data-type': 'card',
    //             // class: `flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors mx-[2.5rem] [[data-type="column"]_&]:mx-0 [[data-type="section-block"]_&]:mx-0 [[data-type="column"]_&]:w-full [[data-type="section-block"]_&]:w-full ${hClass}`,
    //             class: `flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full ${hClass}`,
    //             style: activeBgColor
    //         }),
    //         0 
    //     ];
    // },
    // ---------------------------------------------------------
    // TAMPILAN PUBLIK (FRONTEND / DATABASE)
    // ---------------------------------------------------------
    renderHTML({ HTMLAttributes }) {
        const hClass = HTMLAttributes['data-fill-height'] === false ? '' : 'h-full';

        return [
            'div',
            mergeAttributes(HTMLAttributes, {
                'data-type': 'card',
                // Perhatikan: style background-color sudah otomatis diurus oleh addAttributes di atas!
                class: `flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full ${hClass} mb-6`
            }),
            0 
        ];
    },

    addCommands() {
        return {
            setCard: () => ({ commands }) => {
                return commands.insertContent({
                    type: this.name,
                    content: [{ type: 'paragraph' }]
                });
            }
        };
    },

    // ---------------------------------------------------------
    // TAMPILAN INTERAKTIF DI EDITOR
    // ---------------------------------------------------------
    // addNodeView() {
    //     return ({ node, getPos, editor }) => {
    //         const dom = document.createElement('div');
    //         dom.dataset.type = 'card';

    //         const bgOptions = [
    //             'white', 'gold', 'softGold', 'forest', 
    //             'softForest', 'coral', 'softCoral',
    //         ];
    //         const cardBgColors = {
    //             white: 'bg-white', 
    //             gold: 'bg-goldy', 
    //             softGold: 'bg-[#F7EBAF]',
    //             forest: 'bg-foresty', 
    //             softForest: 'bg-[#E9F1EB]',
    //             coral: 'bg-coral', 
    //             softCoral: 'bg-[#FBE6E6]',
    //         };
    //         const bgDotBg = {
    //             white: 'bg-white border border-zinc-300', gold: 'bg-goldy',
    //             softGold: 'bg-[#F7EBAF]', forest: 'bg-foresty ',
    //             softForest: 'bg-[#E9F1EB]', coral: 'bg-coral', softCoral: 'bg-[#FBE6E6]',
    //         };

    //         const initialBg = cardBgColors[node.attrs.bgColor] || cardBgColors.white;
    //         // 🌟 SET INITIAL TINGGI DOM
    //         const initialHClass = node.attrs.fillHeight ? 'h-full' : '';
            
    //         // dom.className = `group relative flex flex-col ${initialHClass} ${initialBg} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors mx-[2.5rem] [[data-type="column"]_&]:mx-0 [[data-type="section-block"]_&]:mx-0 [[data-type="column"]_&]:w-full [[data-type="section-block"]_&]:w-full`;
    //         dom.className = `group relative flex flex-col ${initialHClass} ${initialBg} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors w-full`;

    //         // 1. Toolbar Melayang
    //         const toolbar = document.createElement('div');
    //         toolbar.className = 'absolute -top-3 right-4 flex items-center gap-1.5 bg-white border border-zinc-200 rounded-md p-1.5 opacity-0 group-hover:opacity-100 transition-opacity z-10 shadow-sm';
    //         toolbar.contentEditable = 'false';

    //         // --- TOMBOL WARNA LATAR ---
    //         bgOptions.forEach(color => {
    //             const btn = document.createElement('button');
    //             btn.type = 'button';
    //             btn.dataset.colorType = 'bg';
    //             btn.title = `Warna Latar: ${color}`;
    //             const isActive = node.attrs.bgColor === color;
    //             btn.className = `w-4 h-4 rounded-full ${bgDotBg[color]} hover:scale-110 transition-transform cursor-pointer ${isActive ? 'ring-2 ring-offset-2 ring-zinc-400' : ''}`;

    //             // btn.onclick = (e) => {
    //             //     e.preventDefault();
    //             //     if (typeof getPos === 'function') {
    //             //         editor.chain().setNodeSelection(getPos()).updateAttributes('infoCard', { bgColor: color }).run();
    //             //     }
    //             // };
    //             btn.onclick = (e) => {
    //                 e.preventDefault();
    //                 e.stopPropagation(); // Mencegah Tiptap mencuri fokus
    //                 if (typeof getPos === 'function') {
    //                     // 🌟 PERBAIKAN: Gunakan setNodeMarkup agar layar tidak melompat
    //                     const tr = editor.state.tr.setNodeMarkup(getPos(), null, {
    //                         ...node.attrs,
    //                         bgColor: color
    //                     });
    //                     editor.view.dispatch(tr);
    //                 }
    //             };
    //             toolbar.appendChild(btn);
    //         });

    //         const divider1 = document.createElement('div');
    //         divider1.className = 'w-[1px] h-4 bg-zinc-200 mx-1';
    //         toolbar.appendChild(divider1);

    //         // 🌟 --- TOMBOL TOGGLE TINGGI (BARU) --- 🌟
    //         const heightBtn = document.createElement('button');
    //         heightBtn.type = 'button';
            
    //         // Fungsi pembantu untuk mengubah ikon & warna tombol tinggi
    //         const updateHeightBtnUI = (isFill) => {
    //             if(isFill) {
    //                 // Ikon Panah Meregang & Aktif Hijau
    //                 heightBtn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 5L12 1L16 5"/><path d="M12 1V23"/><path d="M8 19L12 23L16 19"/></svg>`;
    //                 heightBtn.className = 'w-6 h-6 flex items-center justify-center rounded text-forest bg-sage-soft hover:bg-zinc-100 transition-colors cursor-pointer';
    //                 heightBtn.title = "Tinggi Kartu: Penuh (Mengisi Kolom)";
    //             } else {
    //                 // Ikon Panah Menyusut & Nonaktif Abu
    //                 heightBtn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 9L12 13L16 9"/><path d="M12 1V13"/><path d="M8 15L12 11L16 15"/><path d="M12 23V11"/></svg>`;
    //                 heightBtn.className = 'w-6 h-6 flex items-center justify-center rounded text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 transition-colors cursor-pointer';
    //                 heightBtn.title = "Tinggi Kartu: Dinamis (Pas Konten)";
    //             }
    //         };
            
    //         updateHeightBtnUI(node.attrs.fillHeight);

    //         // heightBtn.onclick = (e) => {
    //         //     e.preventDefault();
    //         //     if (typeof getPos === 'function') {
    //         //         // Toggle nilai true/false
    //         //         editor.chain().setNodeSelection(getPos()).updateAttributes('infoCard', { fillHeight: !node.attrs.fillHeight }).run();
    //         //     }
    //         // };
    //         heightBtn.onclick = (e) => {
    //             e.preventDefault();
    //             e.stopPropagation(); // Mencegah Tiptap mencuri fokus
    //             if (typeof getPos === 'function') {
    //                 // 🌟 PERBAIKAN: Gunakan setNodeMarkup dan ganti properti fillHeight
    //                 const tr = editor.state.tr.setNodeMarkup(getPos(), null, {
    //                     ...node.attrs,
    //                     fillHeight: !node.attrs.fillHeight
    //                 });
    //                 editor.view.dispatch(tr);
    //             }
    //         };
    //         toolbar.appendChild(heightBtn);

    //         const divider2 = document.createElement('div');
    //         divider2.className = 'w-[1px] h-4 bg-zinc-200 mx-1';
    //         toolbar.appendChild(divider2);

    //         // --- TOMBOL HAPUS ---
    //         const delBtn = document.createElement('button');
    //         delBtn.type = 'button';
    //         delBtn.innerHTML = '×';
    //         delBtn.className = 'w-5 h-5 flex items-center justify-center text-zinc-400 hover:text-red-500 text-xl leading-none cursor-pointer';
    //         delBtn.title = "Hapus Kontainer";
    //         delBtn.onclick = (e) => {
    //             e.preventDefault();
    //             if (typeof getPos === 'function') {
    //                 editor.chain().deleteRange({ from: getPos(), to: getPos() + node.nodeSize }).run();
    //             }
    //         };
    //         toolbar.appendChild(delBtn);

    //         // 2. Area Teks Bebas
    //         const contentDOM = document.createElement('div');
    //         contentDOM.className = 'flex-1 w-full min-w-0';

    //         dom.appendChild(toolbar);
    //         dom.appendChild(contentDOM);

    //         return {
    //             dom,
    //             contentDOM,
    //             update: (updatedNode) => {
    //                 if (updatedNode.type.name !== node.type.name) return false;
    //                 node = updatedNode;

    //                 const newBg = cardBgColors[updatedNode.attrs.bgColor] || cardBgColors.white;
    //                 // 🌟 PERBARUI CLASS TINGGI SAAT UPDATE
    //                 const newHClass = updatedNode.attrs.fillHeight ? 'h-full' : '';
                    
    //                 // dom.className = `group relative flex flex-col ${newHClass} ${newBg} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors mx-[2.5rem] [[data-type="column"]_&]:mx-0 [[data-type="section-block"]_&]:mx-0 [[data-type="column"]_&]:w-full [[data-type="section-block"]_&]:w-full`;
    //                 dom.className = `group relative flex flex-col ${newHClass} ${newBg} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors w-full`;

    //                 // Update UI Tombol Tinggi
    //                 updateHeightBtnUI(updatedNode.attrs.fillHeight);

    //                 const bgButtons = toolbar.querySelectorAll('button[data-color-type="bg"]');
    //                 bgButtons.forEach((btn, idx) => {
    //                     const color = bgOptions[idx];
    //                     const isActive = updatedNode.attrs.bgColor === color;
    //                     btn.className = `w-4 h-4 rounded-full ${bgDotBg[color]} hover:scale-110 transition-transform cursor-pointer ${isActive ? 'ring-2 ring-offset-2 ring-zinc-400' : ''}`;
    //                 });

    //                 return true;
    //             }
    //         };
    //     }
    // }

    // ---------------------------------------------------------
    // TAMPILAN INTERAKTIF DI EDITOR (NODEVIEW)
    // ---------------------------------------------------------
    addNodeView() {
        return ({ node, getPos, editor }) => {
            const dom = document.createElement('div');
            dom.dataset.type = 'card';

            // 🌟 Ambil class Tailwind dari objek sumber
            const currentColor = CARD_COLORS[node.attrs.bgColor] || CARD_COLORS.white;
            const initialHClass = node.attrs.fillHeight ? 'h-full' : '';
            
            dom.className = `group relative flex flex-col ${initialHClass} ${currentColor.twClass} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors w-full mb-6`;

            // 1. Toolbar Melayang
            const toolbar = document.createElement('div');
            toolbar.className = 'absolute -top-3 right-4 flex items-center gap-1.5 bg-white border border-zinc-200 rounded-md p-1.5 opacity-0 group-hover:opacity-100 transition-opacity z-10 shadow-sm';
            toolbar.contentEditable = 'false';

            // --- TOMBOL WARNA LATAR (Looping Otomatis) ---
            colorKeys.forEach(colorName => {
                const colorData = CARD_COLORS[colorName];
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.dataset.colorType = 'bg';
                btn.title = `Warna Latar: ${colorName}`;
                
                const isActive = node.attrs.bgColor === colorName;
                btn.className = `w-4 h-4 rounded-full ${colorData.dotClass} hover:scale-110 transition-transform cursor-pointer ${isActive ? 'ring-2 ring-offset-2 ring-zinc-400' : ''}`;

                btn.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof getPos === 'function') {
                        const tr = editor.state.tr.setNodeMarkup(getPos(), null, {
                            ...node.attrs,
                            bgColor: colorName
                        });
                        editor.view.dispatch(tr);
                    }
                };
                toolbar.appendChild(btn);
            });

            const divider1 = document.createElement('div');
            divider1.className = 'w-[1px] h-4 bg-zinc-200 mx-1';
            toolbar.appendChild(divider1);

            // --- TOMBOL TOGGLE TINGGI ---
            const heightBtn = document.createElement('button');
            heightBtn.type = 'button';
            
            const updateHeightBtnUI = (isFill) => {
                if(isFill) {
                    heightBtn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 5L12 1L16 5"/><path d="M12 1V23"/><path d="M8 19L12 23L16 19"/></svg>`;
                    heightBtn.className = 'w-6 h-6 flex items-center justify-center rounded text-forest bg-sage-soft hover:bg-zinc-100 transition-colors cursor-pointer';
                    heightBtn.title = "Tinggi Kartu: Penuh (Mengisi Kolom)";
                } else {
                    heightBtn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 9L12 13L16 9"/><path d="M12 1V13"/><path d="M8 15L12 11L16 15"/><path d="M12 23V11"/></svg>`;
                    heightBtn.className = 'w-6 h-6 flex items-center justify-center rounded text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 transition-colors cursor-pointer';
                    heightBtn.title = "Tinggi Kartu: Dinamis (Pas Konten)";
                }
            };
            
            updateHeightBtnUI(node.attrs.fillHeight);

            heightBtn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (typeof getPos === 'function') {
                    const tr = editor.state.tr.setNodeMarkup(getPos(), null, {
                        ...node.attrs,
                        fillHeight: !node.attrs.fillHeight
                    });
                    editor.view.dispatch(tr);
                }
            };
            toolbar.appendChild(heightBtn);

            const divider2 = document.createElement('div');
            divider2.className = 'w-[1px] h-4 bg-zinc-200 mx-1';
            toolbar.appendChild(divider2);

            // --- TOMBOL HAPUS ---
            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.innerHTML = '×';
            delBtn.className = 'w-5 h-5 flex items-center justify-center text-zinc-400 hover:text-red-500 text-xl leading-none cursor-pointer';
            delBtn.title = "Hapus Kontainer";
            delBtn.onclick = (e) => {
                e.preventDefault();
                if (typeof getPos === 'function') {
                    editor.chain().deleteRange({ from: getPos(), to: getPos() + node.nodeSize }).run();
                }
            };
            toolbar.appendChild(delBtn);

            // 2. Area Teks Bebas
            const contentDOM = document.createElement('div');
            contentDOM.className = 'flex-1 w-full min-w-0';

            dom.appendChild(toolbar);
            dom.appendChild(contentDOM);

            return {
                dom,
                contentDOM,
                update: (updatedNode) => {
                    if (updatedNode.type.name !== node.type.name) return false;
                    node = updatedNode;

                    // 🌟 Perbarui UI saat ada perubahan
                    const newColor = CARD_COLORS[updatedNode.attrs.bgColor] || CARD_COLORS.white;
                    const newHClass = updatedNode.attrs.fillHeight ? 'h-full' : '';
                    
                    dom.className = `kntl  group relative flex flex-col ${newHClass} ${newColor.twClass} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors w-full mb-6 puntangina`;

                    updateHeightBtnUI(updatedNode.attrs.fillHeight);

                    const bgButtons = toolbar.querySelectorAll('button[data-color-type="bg"]');
                    bgButtons.forEach((btn, idx) => {
                        const colorName = colorKeys[idx];
                        const isActive = updatedNode.attrs.bgColor === colorName;
                        btn.className = `w-4 h-4 rounded-full ${CARD_COLORS[colorName].dotClass} hover:scale-110 transition-transform cursor-pointer ${isActive ? 'ring-2 ring-offset-2 ring-zinc-400' : ''}`;
                    });

                    return true;
                }
            };
        }
    }
});