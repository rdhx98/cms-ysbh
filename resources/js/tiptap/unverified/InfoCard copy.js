import { Node, mergeAttributes } from '@tiptap/core';

export const InfoCard = Node.create({
    name: 'infoCard',
    group: 'block',
    content: 'block+', // Tetap izinkan semua jenis blok (paragraf, gambar, heading, dll) masuk ke sini
    isolating: true,

    addAttributes() {
        return {
            bgColor: {
                default: 'white',
            }
        };
    },

    parseHTML() {
        return [{ tag: 'div[data-type="info-card"]' }];
    },

    // ---------------------------------------------------------
    // TAMPILAN PUBLIK (FRONTEND)
    // ---------------------------------------------------------
    renderHTML({ HTMLAttributes }) {
        // Pemetaan warna Latar Belakang Kartu
        const bgColorMap = {
            white: 'background-color: #FFFFFF;',
            gold: 'background-color: #F7EBAF;',
            forest: 'background-color: #E9F1EB;',
            coral: 'background-color: #FBE6E6;'
        };

        const activeBgColor = bgColorMap[HTMLAttributes.bgColor] || bgColorMap.white;

        return [
            'div',
            mergeAttributes(HTMLAttributes, {
                'data-type': 'info-card',
                // class: 'flex flex-col h-full border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors mx-[2.5rem] [[data-type="column"]_&]:mx-0 [[data-type="column"]_&]:w-full',
                // Class baru untuk InfoCard
                class: 'flex flex-col h-full border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors mx-[2.5rem] [[data-type="column"]_&]:mx-0 [[data-type="section-block"]_&]:mx-0 [[data-type="column"]_&]:w-full [[data-type="section-block"]_&]:w-full',
                style: activeBgColor
            }),
            0 // Area Konten
        ];
    },

    addCommands() {
        return {
            setInfoCard: () => ({ commands }) => {
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
    addNodeView() {
        return ({ node, getPos, editor }) => {
            const dom = document.createElement('div');
            dom.dataset.type = 'info-card';

            // Konfigurasi Warna Latar
            const bgOptions = [
                'white',
                'gold',
                'softGold',
                'forest',
                'softForest',
                'coral',
                'softCoral',
            ];
            const cardBgColors = {
                white: 'bg-white',
                gold: 'bg-goldy',
                softGold: 'bg-[#F7EBAF]',
                forest: 'bg-foresty',
                softForest: 'bg-[#E9F1EB]',
                coral: 'bg-coral',
                softCoral: 'bg-[#FBE6E6]',
            };
            const bgDotBg = {
                // white: 'bg-white border border-zinc-300',
                white: 'bg-white border border-zinc-300',
                gold: 'bg-goldy',
                softGold: 'bg-[#F7EBAF]',
                forest: 'bg-foresty ',
                softForest: 'bg-[#E9F1EB]',
                coral: 'bg-coral',
                softCoral: 'bg-[#FBE6E6]',
            };

            // // Set class awal latar belakang
            // const initialBg = cardBgColors[node.attrs.bgColor] || cardBgColors.white;
            // dom.className = `group relative flex flex-col h-full ${initialBg} border border-dashed border-zinc-400/60 rounded-[18px] p-2 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors m-0`;

            // Set class awal latar belakang
            const initialBg = cardBgColors[node.attrs.bgColor] || cardBgColors.white;
            // Terapkan Varian Tailwind
            dom.className = `group relative flex flex-col h-full ${initialBg} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors mx-[2.5rem] [[data-type="column"]_&]:mx-0 [[data-type="section-block"]_&]:mx-0 [[data-type="column"]_&]:w-full [[data-type="section-block"]_&]:w-full`;

            // 1. Toolbar Melayang
            const toolbar = document.createElement('div');
            toolbar.className = 'absolute -top-3 right-4 flex items-center gap-1.5 bg-white border border-zinc-200 rounded-md p-1.5 opacity-0 group-hover:opacity-100 transition-opacity z-10 shadow-sm';
            toolbar.contentEditable = 'false';

            // --- TOMBOL WARNA LATAR (BG) ---
            bgOptions.forEach(color => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.dataset.colorType = 'bg';
                btn.title = `Warna Latar: ${color}`;
                const isActive = node.attrs.bgColor === color;
                btn.className = `w-4 h-4 rounded-full ${bgDotBg[color]} hover:scale-110 transition-transform cursor-pointer ${isActive ? 'ring-2 ring-offset-2 ring-zinc-400' : ''}`;

                btn.onclick = (e) => {
                    e.preventDefault();
                    if (typeof getPos === 'function') {
                        editor.chain().setNodeSelection(getPos()).updateAttributes('infoCard', { bgColor: color }).run();
                    }
                };
                toolbar.appendChild(btn);
            });

            // Pemisah Hapus
            const divider = document.createElement('div');
            divider.className = 'w-[1px] h-4 bg-zinc-200 mx-1';
            toolbar.appendChild(divider);

            // --- TOMBOL HAPUS ---
            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.innerHTML = '×';
            delBtn.className = 'w-4 h-4 flex items-center justify-center text-zinc-400 hover:text-red-500 text-lg leading-none cursor-pointer';
            delBtn.title = "Hapus Kontainer";
            delBtn.onclick = (e) => {
                e.preventDefault();
                if (typeof getPos === 'function') {
                    editor.chain().deleteRange({ from: getPos(), to: getPos() + node.nodeSize }).run();
                }
            };
            toolbar.appendChild(delBtn);

            // 2. Area Teks Bebas (Bisa diisi apa saja)
            const contentDOM = document.createElement('div');
            contentDOM.className = 'flex-1 w-full min-w-0';

            dom.appendChild(toolbar);
            dom.appendChild(contentDOM);

            return {
                dom,
                contentDOM,
                update: (updatedNode) => {
                    // 1. Validasi tipe node (tetap gunakan ini agar aman)
                    if (updatedNode.type.name !== node.type.name) return false;

                    // 2. 🔥 WAJIB: Perbarui referensi node agar update berikutnya tidak menggunakan data lama
                    node = updatedNode;

                    // 3. Update Styling DOM Utama
                    const newBg = cardBgColors[updatedNode.attrs.bgColor] || cardBgColors.white;
                    dom.className = `group relative flex flex-col h-full ${newBg} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors mx-[2.5rem] [[data-type="column"]_&]:mx-0 [[data-type="column"]_&]:w-full`;

                    // 4. Update Status Aktif pada Tombol Latar (Toolbar UI)
                    const bgButtons = toolbar.querySelectorAll('button[data-color-type="bg"]');
                    bgButtons.forEach((btn, idx) => {
                        const color = bgOptions[idx];
                        const isActive = updatedNode.attrs.bgColor === color;
                        // Gunakan className yang bersih dan konsisten
                        btn.className = `w-4 h-4 rounded-full ${bgDotBg[color]} hover:scale-110 transition-transform cursor-pointer ${isActive ? 'ring-2 ring-offset-2 ring-zinc-400' : ''}`;
                    });

                    return true; // Memberitahu Tiptap bahwa update berhasil
                }
                //    update: (updatedNode) => {
                //         if (updatedNode.type.name !== 'infoCard') return false;

                //         const newBg = cardBgColors[updatedNode.attrs.bgColor] || cardBgColors.white;

                //         // Pastikan class ini sama persis dengan yang di atas
                //         dom.className = `group relative flex flex-col h-full ${newBg} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors mx-[2.5rem] [[data-type="column"]_&]:mx-0 [[data-type="column"]_&]:w-full`;

                //         // Update Status Aktif (Ring) pada Tombol Latar
                //         const bgButtons = toolbar.querySelectorAll('button[data-color-type="bg"]');
                //         bgButtons.forEach((btn, idx) => {
                //             const color = bgOptions[idx];
                //             const isActive = updatedNode.attrs.bgColor === color;
                //             btn.className = `w-4 h-4 rounded-full ${bgDotBg[color]} hover:scale-110 transition-transform cursor-pointer ${isActive ? 'ring-2 ring-offset-2 ring-zinc-400' : ''}`;
                //         });

                //         return true;
                //     }
            };
        }
    }
});
