import { Node, mergeAttributes } from '@tiptap/core';

export const TransferCard = Node.create({
    name: 'transferCard',
    group: 'block',
    atom: true, // KUNCI: Menandakan ini node solid, tidak bisa di-enter ke dalamnya
    
    addAttributes() {
        return {
            bank: { default: 'BCA' },
            account: { default: '123 4567 890' },
            name: { default: 'Yayasan Sinar Bhakti Husada' }
        }
    },

    parseHTML() {
        return [{ tag: 'div[data-type="transfer-card"]' }]
    },

    // ==========================================
    // TAMPILAN UNTUK PENGUNJUNG (FRONTEND)
    // ==========================================
    renderHTML({ HTMLAttributes }) {
        return [
            'div', 
            mergeAttributes(HTMLAttributes, { 
                'data-type': 'transfer-card',
                class: 'bg-[#FBF7EA] border border-[#064F3B]/20 rounded-[18px] p-6 sm:p-7 shadow-sm my-6 max-w-sm' 
            }),
            ['div', { class: 'text-[12px] font-bold tracking-[0.1em] uppercase text-[#064F3B]/70 mb-2' }, 'Transfer Bank'],
            ['div', { class: 'font-display text-[22px] font-semibold text-[#064F3B] mb-1' }, `${HTMLAttributes.bank} · ${HTMLAttributes.account}`],
            ['div', { class: 'text-[14px] font-medium text-[#4B5D53] mb-4' }, `a.n. ${HTMLAttributes.name}`],
            // Tombol Copy otomatis ter-generate di Frontend lengkap dengan JS Native-nya!
            ['button', { 
                type: 'button',
                class: 'bg-white border border-[#064F3B]/20 text-[#E42326] rounded-full px-4 py-2 font-bold text-[13px] hover:bg-[#F3ECD6] transition-colors shadow-sm cursor-pointer',
                onclick: `navigator.clipboard.writeText('${HTMLAttributes.account}'); const orig = this.innerText; this.innerText = 'Tersalin ✓'; setTimeout(() => this.innerText = orig, 2000);`
            }, 'Salin Nomor Rekening']
        ]
    },

    // ==========================================
    // TAMPILAN INTERAKTIF UNTUK EDITOR (NODE VIEW)
    // ==========================================
    addNodeView() {
        return ({ node, editor, getPos }) => {
            const dom = document.createElement('div');
            dom.className = 'bg-[#FBF7EA] border border-[#064F3B]/30 rounded-[18px] p-6 sm:p-7 shadow-sm my-6 max-w-sm relative group';
            
            // Helper untuk membuat input interaktif yang cantik
            const createInput = (value, placeholder, classes, attrKey) => {
                const input = document.createElement('input');
                input.type = 'text';
                input.value = value;
                input.placeholder = placeholder;
                input.className = `bg-transparent border-b border-dashed border-[#064F3B]/30 hover:border-[#064F3B] focus:border-[#064F3B] focus:outline-none focus:ring-0 p-0 m-0 w-full transition-colors ${classes}`;
                
                input.addEventListener('input', (e) => {
                    if (typeof getPos === 'function') {
                        editor.chain().setNodeSelection(getPos()).updateAttributes('transferCard', { [attrKey]: e.target.value }).run();
                    }
                });
                return input;
            };

            const label = document.createElement('div');
            label.className = 'text-[12px] font-bold tracking-[0.1em] uppercase text-[#064F3B]/70 mb-3';
            label.innerText = 'Kartu Transfer (Mode Edit)';
            dom.appendChild(label);

            // Baris: Bank & No Rekening
            const row = document.createElement('div');
            row.className = 'flex gap-2 items-end mb-2';
            
            const bankInput = createInput(node.attrs.bank, 'Cth: BCA', 'font-display text-xl font-semibold text-[#064F3B] w-16', 'bank');
            const dot = document.createElement('span');
            dot.className = 'font-display text-xl font-semibold text-[#064F3B] mb-1';
            dot.innerText = '·';
            const acctInput = createInput(node.attrs.account, 'Nomor Rekening', 'font-display text-xl font-semibold text-[#064F3B] flex-1', 'account');
            
            row.appendChild(bankInput);
            row.appendChild(dot);
            row.appendChild(acctInput);
            dom.appendChild(row);

            // Baris: Nama Yayasan
            const nameWrapper = document.createElement('div');
            nameWrapper.className = 'flex gap-1 items-end mb-5';
            const an = document.createElement('span');
            an.className = 'text-[14px] font-medium text-[#4B5D53] pb-0.5';
            an.innerText = 'a.n. ';
            const nameInput = createInput(node.attrs.name, 'Nama Pemilik Rek', 'text-[14px] font-medium text-[#4B5D53] flex-1 pb-0.5', 'name');
            
            nameWrapper.appendChild(an);
            nameWrapper.appendChild(nameInput);
            dom.appendChild(nameWrapper);

            // Dummy Button (Hanya estetika di editor)
            const btn = document.createElement('div');
            btn.className = 'inline-block bg-white border border-[#064F3B]/20 text-[#E42326] rounded-full px-4 py-2 font-bold text-[13px] opacity-60 pointer-events-none select-none';
            btn.innerText = 'Salin Nomor Rekening';
            dom.appendChild(btn);

            // Tombol Hapus Rahasia (Muncul saat di-hover)
            const deleteBtn = document.createElement('button');
            deleteBtn.innerHTML = 'Hapus';
            deleteBtn.className = 'absolute top-3 right-3 bg-red-100 text-red-600 border border-red-200 text-xs font-bold px-2.5 py-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer shadow-sm';
            deleteBtn.onclick = () => {
                if (typeof getPos === 'function') {
                    editor.chain().deleteRange({ from: getPos(), to: getPos() + node.nodeSize }).run();
                }
            };
            dom.appendChild(deleteBtn);

            return { dom }
        }
    },

    addCommands() {
        return {
            insertTransferCard: () => ({ commands }) => {
                return commands.insertContent({ type: this.name })
            },
        }
    }
});