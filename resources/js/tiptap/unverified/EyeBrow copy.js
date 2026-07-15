import { Node, mergeAttributes } from '@tiptap/core';

export const Eyebrow = Node.create({
    name: 'eyebrow',
    group: 'block',
    content: 'inline*', 
    defining: true,

    parseHTML() {
        return [
            { tag: 'div[data-type="eyebrow"]' },
        ]
    },

    // 1. TAMPILAN UNTUK DISIMPAN KE DATABASE (FRONTEND)
    renderHTML({ HTMLAttributes }) {
        return [
            'div', 
            mergeAttributes(HTMLAttributes, { 
                'data-type': 'eyebrow',
                class: 'inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark mb-3 mt-4' 
            }),
            [
                'span', 
                { contenteditable: "false", class: 'flex shrink-0 select-none' }, 
                [
                    'svg', 
                    // Wajib menambahkan xmlns agar SVG tidak rusak saat ditampilkan di Frontend
                    { xmlns: 'http://www.w3.org/2000/svg', class: 'w-4 h-4', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' },
                    ['circle', { cx: '12', cy: '12', r: '9' }],
                    ['path', { d: 'M12 3v2M12 19v2M3 12h2M19 12h2' }]
                ]
            ],
            [
                'span', 
                { class: 'outline-none flex-1 min-w-0' }, 
                0 
            ] 
        ];
    },

    // 2. TAMPILAN INTERAKTIF DI DALAM EDITOR
    addNodeView() {
        return () => {
            const dom = document.createElement('div');
            // Menambahkan warna #BE1417 (coral-dark) secara eksplisit untuk berjaga-jaga 
            // jika class text-coral-dark belum diregistrasi di Tailwind editor Anda
            dom.className = 'inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase mb-3 mt-4 w-full';
            dom.style.color = '#BE1417'; 
            dom.dataset.type = 'eyebrow';

            // KUNCI PERBAIKAN: Gunakan innerHTML agar browser memproses SVG dengan benar
            const iconWrapper = document.createElement('span');
            iconWrapper.contentEditable = 'false';
            iconWrapper.className = 'flex shrink-0 select-none';
            iconWrapper.innerHTML = `
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 3v2M12 19v2M3 12h2M19 12h2"></path>
                </svg>
            `;

            const contentDOM = document.createElement('span');
            contentDOM.className = 'outline-none flex-1 min-w-0';

            dom.appendChild(iconWrapper);
            dom.appendChild(contentDOM);

            return { dom, contentDOM }
        }
    },

    addCommands() {
        return {
            setEyebrow: () => ({ commands }) => {
                return commands.setNode(this.name)
            },
            toggleEyebrow: () => ({ commands }) => {
                return commands.toggleNode(this.name, 'paragraph')
            },
        }
    }
});