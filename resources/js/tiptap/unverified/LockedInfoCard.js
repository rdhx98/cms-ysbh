import { Node, mergeAttributes } from '@tiptap/core';

export const InfoCard = Node.create({
    name: 'infoCard',
    group: 'block',
    content: 'block+', 
    isolating: true, 

    addAttributes() {
        return {
            cardNumber: {
                default: '01',
            },
            // Atribut dipisah menjadi dua
            textColor: {
                default: 'gold', 
            },
            bgColor: {
                default: 'white', // Default latar putih bersih
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
        // Pemetaan warna Teks Nomor
        const textColorMap = {
            gold: 'color: #EBCC26;',
            forest: 'color: #064F3B;',
            coral: 'color: #E42326;'
        };
        
        // Pemetaan warna Latar Belakang Kartu
        const bgColorMap = {
            white: 'background-color: #FFFFFF;',
            gold: 'background-color: #F7EBAF;',
            forest: 'background-color: #E9F1EB;',
            coral: 'background-color: #FBE6E6;'
        };

        const activeTextColor = textColorMap[HTMLAttributes.textColor] || textColorMap.gold;
        const activeBgColor = bgColorMap[HTMLAttributes.bgColor] || bgColorMap.white;

        return [
            'div',
            mergeAttributes(HTMLAttributes, {
                'data-type': 'info-card',
                class: 'flex flex-col h-full border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors',
                style: activeBgColor // Terapkan warna latar
            }),
            ['div', { 
                class: 'text-[22px] font-bold mb-3 font-display',
                style: activeTextColor // Terapkan warna teks nomor
            }, HTMLAttributes.cardNumber],
            ['div', { 
                class: 'text-[15.5px] font-semibold text-[#064F3B] leading-[1.4] flex-1 min-w-0' 
            }, 0] 
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
            
            // Konfigurasi Warna Teks
            const textOptions = ['gold', 'forest', 'coral'];
            const textColors = { gold: 'text-[#EBCC26]', forest: 'text-[#064F3B]', coral: 'text-[#E42326]' };
            const textDotBg = { gold: 'bg-[#EBCC26]', forest: 'bg-[#064F3B]', coral: 'bg-[#E42326]' };
            
            // Konfigurasi Warna Latar
            const bgOptions = ['white', 'gold', 'forest', 'coral'];
            const cardBgColors = { white: 'bg-white', gold: 'bg-[#F7EBAF]', forest: 'bg-[#E9F1EB]', coral: 'bg-[#FBE6E6]' };
            const bgDotBg = { 
                white: 'bg-white border border-zinc-300', // Putih diberi border agar terlihat
                gold: 'bg-[#F7EBAF]', 
                forest: 'bg-[#E9F1EB]', 
                coral: 'bg-[#FBE6E6]' 
            };

            // Set class awal latar belakang
            const initialBg = cardBgColors[node.attrs.bgColor] || cardBgColors.white;
            dom.className = `group relative flex flex-col h-full ${initialBg} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors my-4`;

            // 1. Toolbar Melayang
            const toolbar = document.createElement('div');
            toolbar.className = 'absolute -top-3 right-4 flex items-center gap-1.5 bg-white border border-zinc-200 rounded-md p-1.5 opacity-0 group-hover:opacity-100 transition-opacity z-10 shadow-sm';
            toolbar.contentEditable = 'false'; 

            // --- GRUP 1: TOMBOL WARNA TEKS ---
            textOptions.forEach(color => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.dataset.colorType = 'text'; // Penanda untuk update
                btn.title = `Warna Teks: ${color}`;
                const isActive = node.attrs.textColor === color;
                btn.className = `w-4 h-4 rounded-full ${textDotBg[color]} hover:scale-110 transition-transform cursor-pointer ${isActive ? 'ring-2 ring-offset-2 ring-zinc-400' : ''}`;
                
                btn.onclick = (e) => {
                    e.preventDefault();
                    if (typeof getPos === 'function') {
                        editor.chain().setNodeSelection(getPos()).updateAttributes('infoCard', { textColor: color }).run();
                    }
                };
                toolbar.appendChild(btn);
            });

            // Pemisah Grup
            const divider1 = document.createElement('div');
            divider1.className = 'w-[1px] h-4 bg-zinc-200 mx-1';
            toolbar.appendChild(divider1);

            // --- GRUP 2: TOMBOL WARNA LATAR (BG) ---
            bgOptions.forEach(color => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.dataset.colorType = 'bg'; // Penanda untuk update
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
            const divider2 = document.createElement('div');
            divider2.className = 'w-[1px] h-4 bg-zinc-200 mx-1';
            toolbar.appendChild(divider2);

            // --- TOMBOL HAPUS ---
            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.innerHTML = '×';
            delBtn.className = 'w-4 h-4 flex items-center justify-center text-zinc-400 hover:text-red-500 text-lg leading-none cursor-pointer';
            delBtn.title = "Hapus Kartu";
            delBtn.onclick = (e) => {
                e.preventDefault();
                if (typeof getPos === 'function') {
                    editor.chain().deleteRange({ from: getPos(), to: getPos() + node.nodeSize }).run();
                }
            };
            toolbar.appendChild(delBtn);

            // 2. Input Nomor
            const numberWrapper = document.createElement('div');
            numberWrapper.className = 'mb-3';
            numberWrapper.contentEditable = 'false'; 

            const numberInput = document.createElement('input');
            numberInput.type = 'text';
            numberInput.value = node.attrs.cardNumber;
            numberInput.placeholder = '01';
            
            const initialTextColor = textColors[node.attrs.textColor] || textColors.gold;
            numberInput.className = `w-16 bg-transparent border-none p-0 focus:ring-0 text-[22px] font-bold font-display placeholder-zinc-400/50 outline-none ${initialTextColor}`;
            
            numberInput.onchange = (e) => {
                if (typeof getPos === 'function') {
                    editor.chain().setNodeSelection(getPos()).updateAttributes('infoCard', { cardNumber: e.target.value }).run();
                }
            };
            numberWrapper.appendChild(numberInput);

            // 3. Area Teks
            const contentDOM = document.createElement('div');
            contentDOM.className = 'text-[15.5px] font-semibold text-[#064F3B] leading-[1.4] flex-1 min-w-0';

            dom.appendChild(toolbar);
            dom.appendChild(numberWrapper);
            dom.appendChild(contentDOM);

            return {
                dom,
                contentDOM,
                // KUNCI: Render ulang class secara real-time saat salah satu warna ditekan
                update: (updatedNode) => {
                    if (updatedNode.type.name !== 'infoCard') return false;
                    
                    // Update Latar Belakang Kartu
                    const newBg = cardBgColors[updatedNode.attrs.bgColor] || cardBgColors.white;
                    dom.className = `group relative flex flex-col h-full ${newBg} border border-dashed border-zinc-400/60 rounded-[18px] p-6 shadow-sm focus-within:border-[#064F3B] focus-within:border-solid transition-colors my-4`;
                    
                    // Update Warna Teks Nomor
                    const newColor = textColors[updatedNode.attrs.textColor] || textColors.gold;
                    numberInput.className = `w-16 bg-transparent border-none p-0 focus:ring-0 text-[22px] font-bold font-display placeholder-zinc-400/50 outline-none ${newColor}`;
                    
                    // Update Status Aktif (Ring) pada Tombol Teks
                    const textButtons = toolbar.querySelectorAll('button[data-color-type="text"]');
                    textButtons.forEach((btn, idx) => {
                        const color = textOptions[idx];
                        const isActive = updatedNode.attrs.textColor === color;
                        btn.className = `w-4 h-4 rounded-full ${textDotBg[color]} hover:scale-110 transition-transform cursor-pointer ${isActive ? 'ring-2 ring-offset-2 ring-zinc-400' : ''}`;
                    });

                    // Update Status Aktif (Ring) pada Tombol Latar (Bg)
                    const bgButtons = toolbar.querySelectorAll('button[data-color-type="bg"]');
                    bgButtons.forEach((btn, idx) => {
                        const color = bgOptions[idx];
                        const isActive = updatedNode.attrs.bgColor === color;
                        btn.className = `w-4 h-4 rounded-full ${bgDotBg[color]} hover:scale-110 transition-transform cursor-pointer ${isActive ? 'ring-2 ring-offset-2 ring-zinc-400' : ''}`;
                    });

                    // Jangan timpa value angka jika kursor sedang di dalam input
                    if (document.activeElement !== numberInput) {
                        numberInput.value = updatedNode.attrs.cardNumber;
                    }
                    return true;
                }
            };
        }
    }
});