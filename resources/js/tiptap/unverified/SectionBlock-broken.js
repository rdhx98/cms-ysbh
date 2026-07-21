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

        return [
            'section',
            mergeAttributes(HTMLAttributes, {
                'data-type': 'section-block',
                class: 'tiptap-full-bleed max-w-7xl mx-auto px-[2.5rem] relative py-10 sm:py-12',
                style: `--bg-outer: ${bgColor};`
            }),
            [
                'div',
                {
                    class: `transition-colors duration-300 flow-root ${isCard ? 'p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200/80' : ''}`,
                    style: `background-color: ${innerColor};`
                },
                0
            ]
        ];
    },

    addNodeView() {
        return ({ node, editor, getPos }) => {
            const section = document.createElement('section');
            const isCard = node.attrs.innerBgColor !== 'transparent';
            section.className = 'relative w-full';
            section.style.cssText = `--bg-outer: ${node.attrs.bgColor};`;
            section.dataset.type = 'section-block';

            // 1. Setup Toolbar
            const toolbar = document.createElement('div');
            toolbar.contentEditable = 'false';
            toolbar.className = 'absolute -bottom-16 left-5 sm:left-8 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-300';
            toolbar.style.cssText = `display: flex; gap: 20px; background: #ffffff; padding: 10px 16px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: sans-serif; font-size: 11px; color: #52525b; z-index: 50; border: 1px solid #e4e4e7;`;

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

            // 2. Setup Area Konten
            const contentDOM = document.createElement('div');
            contentDOM.className = `transition-colors duration-300 flow-root relative z-10 min-h-[100px] ${isCard ? 'p-8 sm:p-10 rounded-2xl shadow-sm border border-zinc-200/80' : ''}`;
            contentDOM.style.backgroundColor = node.attrs.innerBgColor;
            if (node.attrs.innerBgColor === '#064F3B') contentDOM.style.color = '#ffffff';

            // 3. Placeholder
            const placeholder = document.createElement('div');
            placeholder.textContent = 'Section Blok';
            placeholder.contentEditable = 'false';
            placeholder.className = 'absolute inset-0 flex items-center justify-center text-zinc-400 font-medium italic pointer-events-none z-20 transition-opacity duration-200 select-none';

            const checkEmpty = () => {
                requestAnimationFrame(() => {
                    if (!contentDOM) return;
                    const isEmpty = contentDOM.textContent.trim() === '' && contentDOM.childElementCount <= 1;
                    placeholder.style.opacity = isEmpty ? '1' : '0';
                });
            };

            const observer = new MutationObserver(checkEmpty);
            observer.observe(contentDOM, { childList: true, characterData: true, subtree: true });
            checkEmpty();

            section.appendChild(contentDOM);
            section.appendChild(placeholder);

            // 4. Wrapper Utama
            const wrapper = document.createElement('div');
            wrapper.className = 'group relative w-full tiptap-full-bleed max-w-7xl mx-auto px-5 sm:px-8 py-10 sm:py-12 pb-20';

            wrapper.appendChild(section);
            wrapper.appendChild(toolbar);

            return {
                dom: wrapper,
                contentDOM: contentDOM,
                // 🌟 TAMBAHAN 1: Mencegah Tiptap melacak perubahan elemen UI eksternal
                ignoreMutation(mutation) {
                    return !contentDOM.contains(mutation.target) && contentDOM !== mutation.target;
                },
                // 🌟 TAMBAHAN 2: Mencegah Memory Leak dengan memutus observer
                destroy() {
                    observer.disconnect();
                }
            };
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
