import { Node, mergeAttributes } from '@tiptap/core';

// ==========================================
// 1. NODE WRAPPER (Pembungkus Utama)
// ==========================================
export const ColumnBlock = Node.create({
    name: 'columnBlock',
    group: 'block',
    content: 'column+', // Harus berisi 1 atau lebih kolom
    isolating: true,

    parseHTML() {
        return [{ tag: 'div[data-type="column-block"]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            mergeAttributes(HTMLAttributes, {
                'data-type': 'column-block',
                // Tumpuk di HP (flex-col), Berjejer di Desktop (md:flex-row)
                // class: 'flex flex-col md:flex-row gap-2 my-6 w-full !px-0'
                // class: 'flex flex-col md:flex-row gap-2 my-6 w-full p-4'
                // class: 'flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem]'
                // Di dalam renderHTML ColumnBlock
                class: 'flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem] [[data-type="section-block"]_&]:px-0'
            }),
            0 // Tempat kolom-kolom disisipkan
        ];
    },

    addCommands() {
        return {
            insertManualColumns: () => ({ commands, state }) => {
                // 1. Deteksi apakah kursor saat ini sedang berada di dalam kolom
                const { selection } = state;
                const { $from } = selection;
                let isInsideColumn = false;

                // Telusuri pohon DOM ke atas dari posisi kursor
                for (let depth = $from.depth; depth > 0; depth--) {
                    if ($from.node(depth).type.name === 'columnBlock' || $from.node(depth).type.name === 'column') {
                        isInsideColumn = true;
                        break;
                    }
                }

                // 2. Jika iya, tolak perintahnya dan munculkan notifikasi Anda
                if (isInsideColumn) {
                    window.dispatchEvent(new CustomEvent('tampilkan-notifikasi', {
                        detail: { 
                            message: 'Tidak dapat membuat kolom di dalam kolom. Buat baris baru di luar terlebih dahulu.', 
                            type: 'warning' 
                        }
                    }));
                    return false; // Hentikan eksekusi
                }

                // 3. Jika aman (di luar kolom), jalankan pembuatan kolom
                return commands.insertContent({
                    type: 'columnBlock',
                    content: [
                        { type: 'column', content: [{ type: 'paragraph' }] },
                        { type: 'column', content: [{ type: 'paragraph' }] } // Langsung buat 2 kolom agar lebih intuitif
                    ]
                });
            }
        }
    }
});

// ==========================================
// 2. NODE KOLOM (Bagian Dalam)
// ==========================================
export const Column = Node.create({
    name: 'column',
    content: 'block+', 
    isolating: true,

    addAttributes() {
        return {
            span: {
                default: 1,
                parseHTML: element => parseInt(element.getAttribute('data-span') || '1', 10),
                renderHTML: attributes => ({ 'data-span': attributes.span })
            }
        }
    },

    parseHTML() {
        return [{ tag: 'div[data-type="column"]' }];
    },

    renderHTML({ HTMLAttributes }) {
        const spanClass = HTMLAttributes.span === 2 ? 'md:flex-[2]' : 'md:flex-1';
        return [
            'div',
            mergeAttributes(HTMLAttributes, {
                'data-type': 'column',
                // KODE LAMA: class: `flex flex-col gap-2 min-w-0 py-2 px-0 rounded-xl border border-transparent ${spanClass}`
                // KODE BARU: 
                class: `flex flex-col gap-2 min-w-0 p-8 has-[[data-type=info-card]]:!p-0 rounded-xl border border-transparent ${spanClass}`
            }),
            0
        ];
    },

    // renderHTML({ HTMLAttributes }) {
    //     const spanClass = HTMLAttributes.span === 2 ? 'md:flex-[2]' : 'md:flex-1';
    //     return [
    //         'div',
    //         mergeAttributes(HTMLAttributes, {
    //             'data-type': 'column',
    //             class: `flex flex-col gap-2 min-w-0 py-2 p4 rounded-xl border border-transparent ${spanClass}`
    //             // class: `flex flex-col gap-2 min-w-0 p-4 rounded-xl border border-transparent ${spanClass}`
    //         }),
    //         0
    //     ];
    // },

    // TAMPILAN INTERAKTIF DI EDITOR
    // TAMPILAN INTERAKTIF DI EDITOR
    // addNodeView() {
    //     return ({ node, getPos, editor }) => {
    //         const dom = document.createElement('div');
    //         const spanClass = node.attrs.span === 2 ? 'md:flex-[2]' : 'md:flex-1';
            
    //         dom.className = `group relative flex flex-col gap-2 min-w-0 p-1 md:p-2 rounded-xl border border-dashed border-zinc-300 focus-within:border-[#064F3B] transition-all ${spanClass}`;
    //         // dom.className = `group relative flex flex-col gap-2 min-w-0 py-1 md:py-2 px-0 rounded-xl border border-dashed border-zinc-300 focus-within:border-[#064F3B] transition-all ${spanClass}`;
    //         dom.dataset.type = 'column';
    addNodeView() {
        return ({ node, getPos, editor }) => {
            const dom = document.createElement('div');
            const spanClass = node.attrs.span === 2 ? 'md:flex-[2]' : 'md:flex-1';
            
            // KODE LAMA: dom.className = `group relative flex flex-col gap-2 min-w-0 py-1 md:py-2 px-0 rounded-xl ...`
            // KODE BARU:
            dom.className = `group relative flex flex-col gap-2 min-w-0 p-8 has-[[data-type=info-card]]:!p-0 rounded-xl border border-dashed border-zinc-300 focus-within:border-[#064F3B] transition-all ${spanClass}`;
            dom.dataset.type = 'column';

            // Pengecekan Batas Maksimal 5 Kolom
            const checkCanAdd = () => {
                if (typeof getPos !== 'function') return false;
                const $pos = editor.state.doc.resolve(getPos());
                const parent = $pos.parent;
                return parent && parent.type.name === 'columnBlock' && parent.childCount < 5;
            };

            // 🌟 KUNCI PERBAIKAN: Fungsi untuk selalu mengambil wujud kolom terbaru
            const getFreshNode = () => {
                return editor.state.doc.nodeAt(getPos());
            };

            // ---------------------------------------------------------
            // 1. TOMBOL TAMBAH KIRI
            // ---------------------------------------------------------
            const addLeftBtn = document.createElement('button');
            addLeftBtn.type = 'button';
            addLeftBtn.innerHTML = '+';
            addLeftBtn.className = 'absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white border border-zinc-300 rounded-full flex items-center justify-center text-xs text-[#064F3B] opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm hover:bg-[#064F3B] hover:text-white';
            addLeftBtn.contentEditable = 'false';
            addLeftBtn.onclick = (e) => {
                e.preventDefault();
                if (checkCanAdd()) {
                    editor.chain().insertContentAt(getPos(), { type: 'column', content: [{ type: 'paragraph' }] }).run();
                } else {
                    window.dispatchEvent(new CustomEvent('tampilkan-notifikasi', { detail: { message: 'Maksimal 5 kolom dalam satu baris!', type: 'warning' } }));
                }
            };

            // ---------------------------------------------------------
            // 2. TOMBOL TAMBAH KANAN (Telah menggunakan data segar)
            // ---------------------------------------------------------
            const addRightBtn = document.createElement('button');
            addRightBtn.type = 'button';
            addRightBtn.innerHTML = '+';
            addRightBtn.className = 'absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white border border-zinc-300 rounded-full flex items-center justify-center text-xs text-[#064F3B] opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm hover:bg-[#064F3B] hover:text-white';
            addRightBtn.contentEditable = 'false';
            addRightBtn.onclick = (e) => {
                e.preventDefault();
                if (checkCanAdd()) {
                    const freshNode = getFreshNode(); // Ambil ukuran terbaru
                    editor.chain().insertContentAt(getPos() + freshNode.nodeSize, { type: 'column', content: [{ type: 'paragraph' }] }).run();
                } else {
                    window.dispatchEvent(new CustomEvent('tampilkan-notifikasi', { detail: { message: 'Maksimal 5 kolom dalam satu baris!', type: 'warning' } }));
                }
            };

            // ---------------------------------------------------------
            // 3. TOMBOL TOGGLE SPAN (Telah menggunakan data segar)
            // ---------------------------------------------------------
            const spanBtn = document.createElement('button');
            spanBtn.type = 'button';
            spanBtn.innerHTML = node.attrs.span === 2 ? 'Kecilkan' : 'Lebarkan';
            spanBtn.className = 'absolute -top-3 right-4 px-2 py-0.5 bg-white border border-zinc-300 rounded-md text-[10px] font-medium text-zinc-600 opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm';
            spanBtn.contentEditable = 'false';
            spanBtn.onclick = (e) => {
                e.preventDefault();
                const freshNode = getFreshNode(); // Ambil atribut terbaru
                const newSpan = freshNode.attrs.span === 2 ? 1 : 2;
                editor.chain().setNodeSelection(getPos()).updateAttributes('column', { span: newSpan }).run();
            };

            // ---------------------------------------------------------
            // 4. TOMBOL HAPUS (Telah menggunakan data segar)
            // ---------------------------------------------------------
            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.innerHTML = 'Hapus';
            delBtn.className = 'absolute -top-3 left-4 px-2 py-0.5 bg-red-50 border border-red-200 rounded-md text-[10px] font-medium text-red-600 opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm';
            delBtn.contentEditable = 'false';
            delBtn.onclick = (e) => {
                e.preventDefault();
                if (typeof getPos !== 'function') return;
                
                const pos = getPos();
                const $pos = editor.state.doc.resolve(pos);
                const parent = $pos.parent; 
                
                if (parent && parent.type.name === 'columnBlock' && parent.childCount === 1) {
                    editor.chain().deleteRange({ from: $pos.before(), to: $pos.after() }).run();
                } else {
                    const freshNode = getFreshNode(); // Ambil ukuran terbaru
                    editor.chain().deleteRange({ from: pos, to: pos + freshNode.nodeSize }).run();
                }
            };

            // ---------------------------------------------------------
            // 5. TOMBOL GESER KIRI (Telah menggunakan data segar)
            // ---------------------------------------------------------
            const moveLeftBtn = document.createElement('button');
            moveLeftBtn.type = 'button';
            moveLeftBtn.innerHTML = '←'; 
            moveLeftBtn.className = 'absolute -bottom-3 left-4 w-6 h-6 bg-white border border-zinc-300 rounded-md flex items-center justify-center text-xs font-bold text-zinc-600 opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm hover:bg-zinc-100';
            moveLeftBtn.contentEditable = 'false';
            moveLeftBtn.onclick = (e) => {
                e.preventDefault();
                const pos = getPos();
                const freshNode = getFreshNode(); // WAJIB: Ambil node beserta teks terbarunya!
                
                const $pos = editor.state.doc.resolve(pos);
                const index = $pos.index();
                const parent = $pos.parent;

                if (index > 0) {
                    const prevNode = parent.child(index - 1);
                    const prevPos = pos - prevNode.nodeSize;
                    
                    const tr = editor.state.tr;
                    // Menukar ukuran dan node yang dijamin 100% akurat
                    tr.replaceWith(prevPos, pos + freshNode.nodeSize, [freshNode, prevNode]);
                    editor.view.dispatch(tr);
                }
            };

            // ---------------------------------------------------------
            // 6. TOMBOL GESER KANAN (Telah menggunakan data segar)
            // ---------------------------------------------------------
            const moveRightBtn = document.createElement('button');
            moveRightBtn.type = 'button';
            moveRightBtn.innerHTML = '→'; 
            moveRightBtn.className = 'absolute -bottom-3 left-12 w-6 h-6 bg-white border border-zinc-300 rounded-md flex items-center justify-center text-xs font-bold text-zinc-600 opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm hover:bg-zinc-100';
            moveRightBtn.contentEditable = 'false';
            moveRightBtn.onclick = (e) => {
                e.preventDefault();
                const pos = getPos();
                const freshNode = getFreshNode(); // WAJIB: Ambil node beserta teks terbarunya!
                
                const $pos = editor.state.doc.resolve(pos);
                const index = $pos.index();
                const parent = $pos.parent;

                if (index < parent.childCount - 1) {
                    const nextNode = parent.child(index + 1);
                    
                    const tr = editor.state.tr;
                    // Menukar ukuran dan node yang dijamin 100% akurat
                    tr.replaceWith(pos, pos + freshNode.nodeSize + nextNode.nodeSize, [nextNode, freshNode]);
                    editor.view.dispatch(tr);
                }
            };

            // ---------------------------------------------------------
            // 7. AREA KONTEN
            // ---------------------------------------------------------
            const contentDOM = document.createElement('div');
            contentDOM.className = 'flex-1 w-full min-w-0'; 

            dom.appendChild(addLeftBtn);
            dom.appendChild(addRightBtn);
            dom.appendChild(spanBtn);
            dom.appendChild(delBtn);
            dom.appendChild(moveLeftBtn);
            dom.appendChild(moveRightBtn);
            dom.appendChild(contentDOM);

            return { dom, contentDOM };
        }
    }
    // addNodeView() {
    //     return ({ node, getPos, editor }) => {
    //         const dom = document.createElement('div');
    //         const spanClass = node.attrs.span === 2 ? 'md:flex-[2]' : 'md:flex-1';
            
    //         dom.className = `group relative flex flex-col gap-2 min-w-0 p-3 md:p-4 rounded-xl border border-dashed border-zinc-300 focus-within:border-[#064F3B] transition-all ${spanClass}`;
    //         dom.dataset.type = 'column';

    //         const checkCanAdd = () => {
    //             if (typeof getPos !== 'function') return false;
    //             const $pos = editor.state.doc.resolve(getPos());
    //             const parent = $pos.parent;
    //             return parent && parent.type.name === 'columnBlock' && parent.childCount < 5;
    //         };

    //         // 1. Tombol Tambah Kiri
    //         const addLeftBtn = document.createElement('button');
    //         addLeftBtn.type = 'button'; // 🌟 KUNCI PERBAIKAN
    //         addLeftBtn.innerHTML = '+';
    //         addLeftBtn.className = 'absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white border border-zinc-300 rounded-full flex items-center justify-center text-xs text-[#064F3B] opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm hover:bg-[#064F3B] hover:text-white';
    //         addLeftBtn.contentEditable = 'false';
    //         addLeftBtn.onclick = (e) => {
    //             e.preventDefault(); // Mencegah aksi tak terduga
    //             if (checkCanAdd()) {
    //                 editor.chain().insertContentAt(getPos(), { type: 'column', content: [{ type: 'paragraph' }] }).run();
    //             } else {
    //                 alert('Maksimal 5 kolom dalam satu baris!');
    //             }
    //         };

    //         // 2. Tombol Tambah Kanan
    //         const addRightBtn = document.createElement('button');
    //         addRightBtn.type = 'button'; // 🌟 KUNCI PERBAIKAN
    //         addRightBtn.innerHTML = '+';
    //         addRightBtn.className = 'absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white border border-zinc-300 rounded-full flex items-center justify-center text-xs text-[#064F3B] opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm hover:bg-[#064F3B] hover:text-white';
    //         addRightBtn.contentEditable = 'false';
    //         addRightBtn.onclick = (e) => {
    //             e.preventDefault();
    //             if (checkCanAdd()) {
    //                 editor.chain().insertContentAt(getPos() + node.nodeSize, { type: 'column', content: [{ type: 'paragraph' }] }).run();
    //             } else {
    //                 window.dispatchEvent(new CustomEvent('tampilkan-notifikasi', {
    //                     detail: {
    //                         // error, success, warning, info
    //                         message: 'Maksimal 5 kolom dalam satu baris!',
    //                         type: 'warning' // atau 'error'
    //                     }
    //                 }));
    //             }
    //         };

    //         // 3. Tombol Toggle Span (Lebarkan/Kecilkan)
    //         const spanBtn = document.createElement('button');
    //         spanBtn.type = 'button'; // 🌟 KUNCI PERBAIKAN
    //         spanBtn.innerHTML = node.attrs.span === 2 ? 'Kecilkan' : 'Lebarkan';
    //         spanBtn.className = 'absolute -top-3 right-4 px-2 py-0.5 bg-white border border-zinc-300 rounded-md text-[10px] font-medium text-zinc-600 opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm';
    //         spanBtn.contentEditable = 'false';
    //         spanBtn.onclick = (e) => {
    //             e.preventDefault();
    //             const newSpan = node.attrs.span === 2 ? 1 : 2;
    //             editor.chain().setNodeSelection(getPos()).updateAttributes('column', { span: newSpan }).run();
    //         };

    //         // // 4. Tombol Hapus Kolom
    //         // const delBtn = document.createElement('button');
    //         // delBtn.type = 'button'; // 🌟 KUNCI PERBAIKAN
    //         // delBtn.innerHTML = 'Hapus';
    //         // delBtn.className = 'absolute -top-3 left-4 px-2 py-0.5 bg-red-50 border border-red-200 rounded-md text-[10px] font-medium text-red-600 opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm';
    //         // delBtn.contentEditable = 'false';
    //         // delBtn.onclick = (e) => {
    //         //     e.preventDefault();
    //         //     editor.chain().deleteRange({ from: getPos(), to: getPos() + node.nodeSize }).run();
    //         // };
    //         // 4. Tombol Hapus Kolom
    //         const delBtn = document.createElement('button');
    //         delBtn.type = 'button';
    //         delBtn.innerHTML = 'Hapus';
    //         delBtn.className = 'absolute -top-3 left-4 px-2 py-0.5 bg-red-50 border border-red-200 rounded-md text-[10px] font-medium text-red-600 opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm';
    //         delBtn.contentEditable = 'false';
    //         delBtn.onclick = (e) => {
    //             e.preventDefault();
                
    //             if (typeof getPos !== 'function') return;
                
    //             const pos = getPos();
    //             const $pos = editor.state.doc.resolve(pos);
    //             const parent = $pos.parent; // Ini adalah elemen ColumnBlock pembungkusnya
                
    //             // Cek jika ini adalah kolom terakhir/satu-satunya di dalam baris
    //             if (parent && parent.type.name === 'columnBlock' && parent.childCount === 1) {
    //                 // Hancurkan seluruh bungkus baris (ColumnBlock)
    //                 editor.chain().deleteRange({ 
    //                     from: $pos.before(), 
    //                     to: $pos.after() 
    //                 }).run();
                    
    //                 // (Opsional) Panggil notifikasi Anda
    //                 window.dispatchEvent(new CustomEvent('tampilkan-notifikasi', {
    //                     detail: { message: 'Baris kolom berhasil dihapus', type: 'info' }
    //                 }));
    //             } else {
    //                 // Jika masih ada kolom lain, hapus kolom ini saja
    //                 editor.chain().deleteRange({ 
    //                     from: pos, 
    //                     to: pos + node.nodeSize 
    //                 }).run();
    //             }
    //         };


    //         // 5. Area Konten Tiptap
    //         const contentDOM = document.createElement('div');
    //         contentDOM.className = 'flex-1 w-full min-w-0'; 

    //         dom.appendChild(addLeftBtn);
    //         dom.appendChild(addRightBtn);
    //         dom.appendChild(spanBtn);
    //         dom.appendChild(delBtn);
    //         dom.appendChild(contentDOM);

    //         return { dom, contentDOM };
    //     }
    // }
});
// export const Column = Node.create({
//     name: 'column',
//     content: 'block+', // Mengizinkan gambar, kartu, teks untuk di-drag ke sini
//     isolating: true,

//     addAttributes() {
//         return {
//             span: {
//                 default: 1,
//                 parseHTML: element => parseInt(element.getAttribute('data-span') || '1', 10),
//                 renderHTML: attributes => ({ 'data-span': attributes.span })
//             }
//         }
//     },

//     parseHTML() {
//         return [{ tag: 'div[data-type="column"]' }];
//     },

//     // TAMPILAN UNTUK PENGUNJUNG (FRONTEND)
//     renderHTML({ HTMLAttributes }) {
//         // Jika span 2, ambil ruang 2x lipat lebih besar di layar desktop
//         const spanClass = HTMLAttributes.span === 2 ? 'md:flex-[2]' : 'md:flex-1';
//         return [
//             'div',
//             mergeAttributes(HTMLAttributes, {
//                 'data-type': 'column',
//                 class: `flex flex-col gap-2 min-w-0 p-4 rounded-xl border border-transparent ${spanClass}`
//             }),
//             0
//         ];
//     },

//     // TAMPILAN INTERAKTIF DI EDITOR
//     addNodeView() {
//         return ({ node, getPos, editor }) => {
//             const dom = document.createElement('div');
//             const spanClass = node.attrs.span === 2 ? 'md:flex-[2]' : 'md:flex-1';
            
//             // Class untuk kotak editor (dengan border putus-putus)
//             dom.className = `group relative flex flex-col gap-2 min-w-0 p-3 md:p-4 rounded-xl border border-dashed border-zinc-300 focus-within:border-[#064F3B] transition-all ${spanClass}`;
//             dom.dataset.type = 'column';

//             // Fungsi Pembantu: Cek apakah boleh menambah kolom (Maks 5)
//             const checkCanAdd = () => {
//                 if (typeof getPos !== 'function') return false;
//                 const $pos = editor.state.doc.resolve(getPos());
//                 const parent = $pos.parent;
//                 return parent && parent.type.name === 'columnBlock' && parent.childCount < 5;
//             };

//             // 1. Tombol Tambah Kiri
//             const addLeftBtn = document.createElement('button');
//             addLeftBtn.innerHTML = '+';
//             addLeftBtn.className = 'absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white border border-zinc-300 rounded-full flex items-center justify-center text-xs text-[#064F3B] opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm hover:bg-[#064F3B] hover:text-white';
//             addLeftBtn.contentEditable = 'false';
//             addLeftBtn.onclick = () => {
//                 if (checkCanAdd()) {
//                     editor.chain().insertContentAt(getPos(), { type: 'column', content: [{ type: 'paragraph' }] }).run();
//                 } else {
//                     alert('Maksimal 5 kolom dalam satu baris!');
//                 }
//             };

//             // 2. Tombol Tambah Kanan
//             const addRightBtn = document.createElement('button');
//             addRightBtn.innerHTML = '+';
//             addRightBtn.className = 'absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white border border-zinc-300 rounded-full flex items-center justify-center text-xs text-[#064F3B] opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm hover:bg-[#064F3B] hover:text-white';
//             addRightBtn.contentEditable = 'false';
//             addRightBtn.onclick = () => {
//                 if (checkCanAdd()) {
//                     editor.chain().insertContentAt(getPos() + node.nodeSize, { type: 'column', content: [{ type: 'paragraph' }] }).run();
//                 } else {
//                     alert('Maksimal 5 kolom dalam satu baris!');
//                 }
//             };

//             // 3. Tombol Toggle Span (Lebarkan/Kecilkan)
//             const spanBtn = document.createElement('button');
//             spanBtn.innerHTML = node.attrs.span === 2 ? 'Kecilkan' : 'Lebarkan';
//             spanBtn.className = 'absolute -top-3 right-4 px-2 py-0.5 bg-white border border-zinc-300 rounded-md text-[10px] font-medium text-zinc-600 opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm';
//             spanBtn.contentEditable = 'false';
//             spanBtn.onclick = () => {
//                 const newSpan = node.attrs.span === 2 ? 1 : 2;
//                 editor.chain().setNodeSelection(getPos()).updateAttributes('column', { span: newSpan }).run();
//             };

//             // 4. Tombol Hapus Kolom
//             const delBtn = document.createElement('button');
//             delBtn.innerHTML = 'Hapus';
//             delBtn.className = 'absolute -top-3 left-4 px-2 py-0.5 bg-red-50 border border-red-200 rounded-md text-[10px] font-medium text-red-600 opacity-0 group-hover:opacity-100 transition-opacity z-10 cursor-pointer shadow-sm';
//             delBtn.contentEditable = 'false';
//             delBtn.onclick = () => {
//                 editor.chain().deleteRange({ from: getPos(), to: getPos() + node.nodeSize }).run();
//             };

//             // 5. Area Konten Tiptap
//             const contentDOM = document.createElement('div');
//             // flex-1 dan min-w-0 penting agar kolom tidak meledak lebarnya jika disusupi gambar besar
//             contentDOM.className = 'flex-1 w-full min-w-0'; 

//             dom.appendChild(addLeftBtn);
//             dom.appendChild(addRightBtn);
//             dom.appendChild(spanBtn);
//             dom.appendChild(delBtn);
//             dom.appendChild(contentDOM);

//             return { dom, contentDOM };
//         }
//     }
// });