import { Node, mergeAttributes } from '@tiptap/core';

export const StepCard = Node.create({
    name: 'stepCard',
    group: 'block',
    content: 'block+', // Mengizinkan Heading dan Paragraf di dalamnya
    defining: true,

    addAttributes() {
        return {
            stepNumber: {
                default: '01',
                parseHTML: element => element.getAttribute('data-step') || '01',
                renderHTML: attributes => ({ 'data-step': attributes.stepNumber })
            }
        }
    },

    parseHTML() {
        return [{ tag: 'div[data-type="step-card"]' }];
    },

    // ==========================================
    // TAMPILAN UNTUK PENGUNJUNG (FRONTEND)
    // ==========================================
    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            mergeAttributes(HTMLAttributes, {
                'data-type': 'step-card',
                // Tampilan Kartu menggunakan palet warna Forest & Gold
                class: 'flex gap-4 sm:gap-6 items-start bg-white border border-[#064F3B]/15 rounded-[18px] p-6 md:px-[30px] md:py-[26px] shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] my-5'
            }),
            [
                'span',
                // Badge Angka Statis (Span)
                { class: 'font-display font-bold text-[15px] text-[#064F3B] bg-[#F7EBAF] w-10 h-10 rounded-xl flex items-center justify-center shrink-0 select-none' },
                HTMLAttributes['data-step'] || '01'
            ],
            [
                'div',
                { class: 'flex-1 min-w-0 pt-1' },
                0 // Lubang untuk teks (Heading & Paragraf)
            ]
        ];
    },

    // ==========================================
    // TAMPILAN INTERAKTIF UNTUK EDITOR (NODE VIEW)
    // ==========================================
    addNodeView() {
        return ({ node, editor, getPos }) => {
            const dom = document.createElement('div');
            dom.className = 'flex gap-4 sm:gap-6 items-start bg-white border border-[#064F3B]/15 rounded-[18px] p-6 md:px-[30px] md:py-[26px] shadow-sm my-5 relative group';
            dom.dataset.type = 'step-card';

            // Wadah Badge Angka
            const badgeContainer = document.createElement('div');
            badgeContainer.className = 'font-display font-bold text-[15px] text-[#064F3B] bg-[#F7EBAF] w-10 h-10 rounded-xl flex items-center justify-center shrink-0 overflow-hidden ring-2 ring-transparent focus-within:ring-[#064F3B]/30 transition-all';

            // AJAIB: Kita gunakan <input> agar angka '01' bisa diganti 'A', 'B', '02' oleh penulis!
            const input = document.createElement('input');
            input.type = 'text';
            input.value = node.attrs.stepNumber;
            input.className = 'w-full h-full bg-transparent text-center border-none focus:outline-none focus:ring-0 p-0 m-0';
            input.style.fontFamily = 'inherit';
            input.style.fontWeight = 'bold';
            input.style.color = 'inherit';
            input.maxLength = 3; // Batasi maksimal 3 karakter

            // Simpan otomatis ke memori Tiptap saat diketik
            input.addEventListener('input', (e) => {
                if (typeof getPos === 'function') {
                    editor.chain().setNodeSelection(getPos()).updateAttributes('stepCard', { stepNumber: e.target.value }).run();
                }
            });

            badgeContainer.appendChild(input);
            dom.appendChild(badgeContainer);

            // Area Teks untuk Tiptap
            const contentDOM = document.createElement('div');
            contentDOM.className = 'flex-1 min-w-0 pt-1';
            dom.appendChild(contentDOM);

            // return {
            //     dom,
            //     contentDOM,
            //     update: (updatedNode) => {
            //         // Pastikan node yang di-update adalah tipe yang sama
            //         if (updatedNode.type.name !== node.type.name) return false;

            //         // Update variabel node lokal agar sinkron dengan data Tiptap terbaru
            //         node = updatedNode;

            //         // Logika tambahan jika ada atribut yang berubah (misal warna)
            //         // Contoh: dom.style.setProperty('--bg-outer', updatedNode.attrs.bgColor);

            //         return true; // Memberitahu Tiptap bahwa update sukses, jangan hancurkan DOM
            //     }
            // }
            return {
                dom,
                contentDOM,
                update: (updatedNode) => {
                    if (updatedNode.type.name !== 'stepCard') return false;
                    // Sinkron angka badge, tapi jangan timpa saat user sedang mengetik di situ
                    if (document.activeElement !== input && input.value !== updatedNode.attrs.stepNumber) {
                        input.value = updatedNode.attrs.stepNumber;
                    }
                    return true;
                }
            }
        }
    },

    addCommands() {
        return {
            insertStepCard: () => ({ commands }) => {
                return commands.insertContent({
                    type: this.name,
                    content: [
                        { type: 'heading', attrs: { level: 3 }, content: [{ type: 'text', text: 'Judul Langkah/Komponen' }] },
                        { type: 'paragraph', content: [{ type: 'text', text: 'Tuliskan deskripsi lengkap di sini...' }] }
                    ]
                });
            },
        }
    }
});
