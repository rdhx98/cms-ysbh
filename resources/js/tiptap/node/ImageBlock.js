import { Node, mergeAttributes } from '@tiptap/core';

export const ImageBlock = Node.create({
    name: 'imageBlock',
    group: 'block',
    content: 'inline*',
    isolating: true,
    draggable: true,

    addAttributes() {
        return {
            src: {
                default: null,
                parseHTML: element => element.querySelector('img')?.getAttribute('src') || null,
                renderHTML: () => ({}),
            },
            alt: {
                default: null,
                parseHTML: element => element.querySelector('img')?.getAttribute('alt') || null,
                renderHTML: () => ({}),
            },
            title: {
                default: null,
                parseHTML: element => element.querySelector('img')?.getAttribute('title') || null,
                renderHTML: () => ({}),
            },
            imageClass: {
                default: 'rounded-lg max-w-full transition-all cursor-pointer tiptap-uploaded-image',
                parseHTML: element => element.querySelector('img')?.getAttribute('class') || null,
                renderHTML: () => ({}),
            },
            'data-token': {
                default: null,
                parseHTML: element => element.querySelector('img')?.getAttribute('data-token') || null,
                renderHTML: () => ({}),
            },
            // 🌟 TAMBAHAN: menampung width/alignment yang tadinya ada di node 'image'
            style: {
                default: null,
                parseHTML: element => element.getAttribute('style'),
                renderHTML: attributes => attributes.style ? { style: attributes.style } : {},
            },
            captionPosition: {
                default: 'bottom',
                parseHTML: element => element.getAttribute('data-caption-position') || 'bottom',
                renderHTML: attributes => ({ 'data-caption-position': attributes.captionPosition }),
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'figure[data-type="image-block"]',
                contentElement: 'figcaption',
            },
        ];
    },

    renderHTML({ HTMLAttributes, node }) {
        const img = [
            'img',
            {
                src: node.attrs.src,
                alt: node.attrs.alt || '',
                title: node.attrs.title || '',
                class: node.attrs.imageClass,
                style: 'width: 100%; height: auto;',
                ...(node.attrs['data-token'] ? { 'data-token': node.attrs['data-token'] } : {}),
            },
        ];
        const figcaption = ['figcaption', { class: 'text-center text-sm text-zinc-500 italic mt-2 outline-none' }, 0];

        return [
            'figure',
            mergeAttributes(HTMLAttributes, { 'data-type': 'image-block', class: 'flex flex-col my-4' }),
            ...(node.attrs.captionPosition === 'top' ? [figcaption, img] : [img, figcaption]),
        ];
    },

    addNodeView() {
        return ({ node, editor, getPos }) => {
            const figure = document.createElement('figure');
            figure.dataset.type = 'image-block';
            figure.className = 'flex flex-col my-4 group relative';
            if (node.attrs.style) figure.setAttribute('style', node.attrs.style);

            const img = document.createElement('img');
            img.src = node.attrs.src;
            img.alt = node.attrs.alt || '';
            if (node.attrs.title) img.title = node.attrs.title;
            img.className = node.attrs.imageClass;
            img.style.width = '100%';
            img.style.height = 'auto';
            img.contentEditable = 'false';
            img.draggable = false;

            img.addEventListener('click', (e) => {
                e.preventDefault();
                if (typeof getPos === 'function') {
                    editor.commands.setNodeSelection(getPos());
                    window.activeImageBlockRef = { el: img, captionPosition: node.attrs.captionPosition };
                }
            });

            const caption = document.createElement('figcaption');
            caption.className = 'text-center text-sm text-zinc-500 italic mt-2 outline-none w-full max-w-full';
            caption.setAttribute('data-placeholder', 'Tulis keterangan gambar…');

            const toolbar = document.createElement('div');
            toolbar.contentEditable = 'false';
            toolbar.className = 'absolute -top-3 right-2 flex items-center gap-1 bg-white border border-zinc-200 rounded-md p-1 opacity-0 group-hover:opacity-100 transition-opacity z-10 shadow-sm';

            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'text-[11px] px-2 py-0.5 rounded hover:bg-zinc-100 text-zinc-600';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.textContent = 'Hapus Caption';
            removeBtn.className = 'text-[11px] px-2 py-0.5 rounded hover:bg-red-50 text-red-500';
            removeBtn.onclick = (e) => {
                e.preventDefault();
                if (typeof getPos === 'function') {
                    editor.chain().setNodeSelection(getPos()).removeImageCaption().run();
                }
            };

            // 🔧 PERBAIKAN: variabel pelacak posisi TERPISAH dari `node` yang basi
            let currentPosition = node.attrs.captionPosition;

            const updateToggleLabel = (position) => {
                toggleBtn.textContent = position === 'top' ? 'Caption ke Bawah' : 'Caption ke Atas';
            };

            toggleBtn.onclick = (e) => {
                e.preventDefault();
                if (typeof getPos === 'function') {
                    const newPos = currentPosition === 'top' ? 'bottom' : 'top';
                    editor.chain().setNodeSelection(getPos()).updateAttributes('imageBlock', { captionPosition: newPos }).run();
                }
            };

            toolbar.appendChild(toggleBtn);
            toolbar.appendChild(removeBtn);

            const applyOrder = (position) => {
                figure.innerHTML = '';
                figure.appendChild(toolbar);
                if (position === 'top') {
                    figure.appendChild(caption);
                    figure.appendChild(img);
                } else {
                    figure.appendChild(img);
                    figure.appendChild(caption);
                }
            };

            updateToggleLabel(currentPosition);
            applyOrder(currentPosition);

            return {
                dom: figure,
                contentDOM: caption,
                update: (updatedNode) => {
                    if (updatedNode.type.name !== 'imageBlock') return false;

                    img.src = updatedNode.attrs.src;
                    img.alt = updatedNode.attrs.alt || '';
                    img.className = updatedNode.attrs.imageClass;

                    if (updatedNode.attrs.style) {
                        figure.setAttribute('style', updatedNode.attrs.style);
                    } else {
                        figure.removeAttribute('style');
                    }

                    if (updatedNode.attrs.captionPosition !== currentPosition) {
                        currentPosition = updatedNode.attrs.captionPosition;
                        updateToggleLabel(currentPosition);
                        applyOrder(currentPosition);
                    }

                    // 🌟 TAMBAHAN: perbarui juga referensi global tiap node ini berubah
                    if (window.activeImageBlockRef?.el === img) {
                        window.activeImageBlockRef.captionPosition = updatedNode.attrs.captionPosition;
                    }

                    return true;
                },
            };
        };
    },

    addCommands() {
        return {
            addImageCaption: () => ({ state, chain, editor }) => {
                if (!editor.isActive('image')) return false;

                const { selection } = state;
                const imageNode = state.doc.nodeAt(selection.from);
                if (!imageNode || imageNode.type.name !== 'image') return false;

                if (imageNode.attrs.alt === '⏳ Mengunggah...') {
                    window.dispatchEvent(new CustomEvent('tampilkan-notifikasi', {
                        detail: { message: 'Tunggu sampai gambar selesai diunggah dulu.', type: 'warning' }
                    }));
                    return false;
                }

                // 🔧 PERBAIKAN: ikut bawa `style` (lebar & alignment) ke node baru
                const { src, alt, title, class: imageClass, style } = imageNode.attrs;
                const token = imageNode.attrs['data-token'];

                return chain()
                    .insertContentAt(
                        { from: selection.from, to: selection.from + imageNode.nodeSize },
                        {
                            type: this.name,
                            attrs: { src, alt, title, imageClass, style, 'data-token': token, captionPosition: 'bottom' },
                            content: [],
                        }
                    )
                    .run();
            },

            removeImageCaption: () => ({ state, chain, editor }) => {
                if (!editor.isActive(this.name)) return false;

                const { selection } = state;
                const blockNode = state.doc.nodeAt(selection.from);
                if (!blockNode || blockNode.type.name !== this.name) return false;

                // 🔧 PERBAIKAN: kembalikan juga `style`-nya ke node gambar biasa
                const { src, alt, title, imageClass, style } = blockNode.attrs;
                const token = blockNode.attrs['data-token'];

                return chain()
                    .insertContentAt(
                        { from: selection.from, to: selection.from + blockNode.nodeSize },
                        { type: 'image', attrs: { src, alt, title, class: imageClass, style, 'data-token': token } }
                    )
                    .run();
            },
        };
    },
});