import { Node, mergeAttributes } from '@tiptap/core';

export const Eyebrow = Node.create({
    name: 'eyebrow',
    group: 'block',
    content: 'inline*',
    defining: true,

    addAttributes() {
        return {
            icon: { default: 'circle' }, // Default ikon
            color: { default: '#BE1417' },
            size: { default: '13px' },
            font: { default: 'sans-serif' }
        };
    },

    parseHTML() { return [{ tag: 'div[data-type="eyebrow"]' }]; },

    renderHTML({ node, HTMLAttributes }) {
    const { color, size, font } = node.attrs;
        
        return [
            'div', 
            mergeAttributes(HTMLAttributes, { 
                style: `color: ${color}; font-size: ${size}; font-family: ${font};`,
                class: 'inline-flex items-center gap-[0.5em] tracking-[0.16em] uppercase mb-3 mt-4' 
            }),
            [
                'span', 
                { class: 'flex shrink-0' }, 
                [
                    'svg', 
                    { 
                        // Menggunakan 'currentcolor' membuat ikon otomatis mengikuti warna teks
                        // Menggunakan ukuran '1em' membuatnya selalu proporsional dengan font-size
                        class: 'w-[1em] h-[1em]', 
                        viewBox: '0 0 24 24', 
                        fill: 'none', 
                        stroke: 'currentColor', 
                        'stroke-width': '2' 
                    },
                    // Ikon akan mengikuti warna & ukuran teks induk
                    node.attrs.icon === 'circle' 
                        ? ['circle', { cx: '12', cy: '12', r: '9' }]
                        : ['path', { d: 'M12 2l9 10-9 10-9-10z' }]
                ]
            ],
            ['span', { class: 'flex-1' }, 0]
        ];
    },
    // renderHTML({ node, HTMLAttributes }) {
    //     const { color, size, font } = node.attrs;
    //     return [
    //         'div', mergeAttributes(HTMLAttributes, { 'data-type': 'eyebrow', class: 'inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-[#BE1417] mb-3 mt-4', style: `color: ${color}; font-size: ${size}; font-family: ${font};`, }),
    //         ['span', { class: 'flex shrink-0' }, icon === 'circle' ? '○' : '◆'], // Gunakan SVG jika perlu
    //         ['span', { class: 'flex-1' }, 0]
    //     ];
    // },

    addNodeView() {
        return ({ node, getPos, editor }) => {
            const dom = document.createElement('div');
            dom.className = 'inline-flex items-center gap-2 text-[13px] font-bold uppercase mb-3 mt-4 w-full';
            
            // Ikon yang bisa diklik untuk toggle
            dom.style.color = node.attrs.color;
            dom.style.fontSize = node.attrs.size;
            dom.style.fontFamily = node.attrs.font;
            const iconBtn = document.createElement('button');
            iconBtn.textContent = node.attrs.icon === 'circle' ? '○' : '◆';
            iconBtn.className = 'cursor-pointer hover:opacity-70 transition';
            iconBtn.onclick = () => {
                const nextIcon = node.attrs.icon === 'circle' ? 'diamond' : 'circle';
                editor.chain().focus().updateAttributes('eyebrow', { icon: nextIcon }).run();
            };

            const contentDOM = document.createElement('span');
            contentDOM.className = 'outline-none flex-1';

            dom.appendChild(iconBtn);
            dom.appendChild(contentDOM);
            return { dom, contentDOM };
        }
    },
    addCommands() {
        return {
            setEyebrow: () => ({ commands }) => {
                return commands.setNode(this.name);
            },
            toggleEyebrow: () => ({ commands }) => {
                return commands.toggleNode(this.name, 'paragraph');
            },
            updateEyebrowIcon: (icon) => ({ commands }) => {
                return commands.updateAttributes('eyebrow', { icon });
            },
            updateEyebrowStyle: (attrs) => ({ commands }) => {
                return commands.updateAttributes('eyebrow', attrs);
            },
        };
    }
});