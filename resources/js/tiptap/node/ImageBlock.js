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

    // GEMINI TESTING
    renderHTML({ HTMLAttributes, node }) {
        const img = [
            'img',
            {
                src: node.attrs.src,
                alt: node.attrs.alt || '',
                title: node.attrs.title || '',
                class: node.attrs.imageClass,
                // 🌟 Gambar di dalam figure SELALU mengambil 100% ruang dari figure
                style: 'display: block; width: 100%; height: auto;', 
                ...(node.attrs['data-token'] ? { 'data-token': node.attrs['data-token'] } : {}),
            },
        ];
        const figcaption = ['figcaption', { class: 'text-center text-sm text-zinc-500 italic mt-2 outline-none', style: 'clear: both;' }, 0];

        // 🌟 Memindahkan atribut style (width & alignment) ke pembungkus <figure>
        const figureAttrs = { 'data-type': 'image-block', class: 'my-4' };
        if (node.attrs.style) {
            figureAttrs.style = node.attrs.style;
        }

        return [
            'figure',
            mergeAttributes(HTMLAttributes, figureAttrs), 
            ...(node.attrs.captionPosition === 'top' ? [figcaption, img] : [img, figcaption]),
        ];
    },
    // WORKING
    // renderHTML({ HTMLAttributes, node }) {
    //     const img = [
    //         'img',
    //         {
    //             src: node.attrs.src,
    //             alt: node.attrs.alt || '',
    //             title: node.attrs.title || '',
    //             class: node.attrs.imageClass,
    //             style: node.attrs.style || 'display: block; width: 100%; height: auto;', // 🔧 pakai attrs.style
    //             ...(node.attrs['data-token'] ? { 'data-token': node.attrs['data-token'] } : {}),
    //         },
    //     ];
    //     const figcaption = ['figcaption', { class: 'text-center text-sm text-zinc-500 italic mt-2 outline-none', style: 'clear: both;' }, 0];

    //     return [
    //         'figure',
    //         mergeAttributes(HTMLAttributes, { 'data-type': 'image-block', class: 'my-4' }), // 🔧 hapus flex flex-col
    //         ...(node.attrs.captionPosition === 'top' ? [figcaption, img] : [img, figcaption]),
    //     ];
    // },

    //  IMAGE SCALING BUG, CAPTION BUG ON RIGHT MARGIN
    addNodeView() {
        return ({ node, editor, getPos }) => {
            const figure = document.createElement('figure');
            figure.dataset.type = 'image-block';
            figure.className = 'my-4 group relative';
            if (node.attrs.style) figure.setAttribute('style', node.attrs.style); // 🔧 style pindah ke sini

            const img = document.createElement('img');
            img.src = node.attrs.src;
            img.alt = node.attrs.alt || '';
            if (node.attrs.title) img.title = node.attrs.title;
            img.className = node.attrs.imageClass;
            img.setAttribute('style', 'display: block; width: 100%; height: auto;'); // 🔧 SELALU tetap, tidak lagi pakai node.attrs.style
            img.contentEditable = 'false';
            img.draggable = false;

            img.addEventListener('click', (e) => {
                e.preventDefault();
                if (typeof getPos === 'function') {
                    editor.commands.setNodeSelection(getPos());
                    window.activeImageBlockRef = { el: figure, captionPosition: node.attrs.captionPosition }; // 🔧 figure, bukan img
                }
            });

            const caption = document.createElement('figcaption');
            caption.className = 'text-center text-sm text-zinc-500 italic mt-2 outline-none w-full max-w-full';
            caption.setAttribute('data-placeholder', 'Tulis keterangan gambar…');
            // 🔧 baris caption.style.clear = 'both' DIHAPUS — tidak perlu lagi, img sudah tidak float sendiri

            let currentPosition = node.attrs.captionPosition;

            const applyOrder = (position) => {
                figure.innerHTML = '';
                if (position === 'top') {
                    figure.appendChild(caption);
                    figure.appendChild(img);
                } else {
                    figure.appendChild(img);
                    figure.appendChild(caption);
                }
            };

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
                        figure.setAttribute('style', updatedNode.attrs.style); // 🔧 target figure
                    } else {
                        figure.removeAttribute('style');
                    }

                    if (updatedNode.attrs.captionPosition !== currentPosition) {
                        currentPosition = updatedNode.attrs.captionPosition;
                        applyOrder(currentPosition);
                    }

                    if (window.activeImageBlockRef?.el === figure) { // 🔧 bandingkan dengan figure
                        window.activeImageBlockRef.captionPosition = updatedNode.attrs.captionPosition;
                    }

                    return true;
                },
            };
        };
    },

    // GEMINI TESTING
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

                let { src, alt, title, class: imageClass, style } = imageNode.attrs;
                const token = imageNode.attrs['data-token'];

                // 🌟 PERBAIKAN: Menggunakan parseFloat agar desimal (seperti 33.33%) tidak hilang
                style = style || '';
                const widthMatch = style.match(/width:\s*([\d.]+)%/);
                if (widthMatch) {
                    const widthNum = parseFloat(widthMatch[1]); 
                    const cleanedStyle = style.replace(/width:\s*[^;]+;?/g, '').trim();
                    style = `width: calc(${widthNum / 100} * min(100%, 64rem)) !important; ${cleanedStyle}`.trim();
                }

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

                let { src, alt, title, imageClass, style } = blockNode.attrs;
                const token = blockNode.attrs['data-token'];

                style = style || '';
                const widthMatch = style.match(/width:\s*calc\(([\d.]+)\s*\*/);
                if (widthMatch) {
                    // 🌟 Mengembalikan kembali ke persen dengan presisi 2 desimal (mencegah ukuran menciut)
                    const widthNum = parseFloat((parseFloat(widthMatch[1]) * 100).toFixed(2));
                    const cleanedStyle = style.replace(/width:\s*[^;]+;?/g, '').trim();
                    style = `width: ${widthNum}% !important; ${cleanedStyle}`.trim();
                }

                return chain()
                    .insertContentAt(
                        { from: selection.from, to: selection.from + blockNode.nodeSize },
                        { type: 'image', attrs: { src, alt, title, class: imageClass, style, 'data-token': token } }
                    )
                    .run();
            },
            
            toggleCaptionPosition: () => ({ state, chain, editor }) => {
                if (!editor.isActive(this.name)) return false;
                const blockNode = state.doc.nodeAt(state.selection.from);
                if (!blockNode || blockNode.type.name !== this.name) return false;
                const newPos = blockNode.attrs.captionPosition === 'top' ? 'bottom' : 'top';
                return chain().updateAttributes(this.name, { captionPosition: newPos }).run();
            },
        };
    },


    // GEMINI WORKING
    // addCommands() {
    //     return {
    //         addImageCaption: () => ({ state, chain, editor }) => {
    //             if (!editor.isActive('image')) return false;

    //             const { selection } = state;
    //             const imageNode = state.doc.nodeAt(selection.from);
    //             if (!imageNode || imageNode.type.name !== 'image') return false;

    //             if (imageNode.attrs.alt === '⏳ Mengunggah...') {
    //                 window.dispatchEvent(new CustomEvent('tampilkan-notifikasi', {
    //                     detail: { message: 'Tunggu sampai gambar selesai diunggah dulu.', type: 'warning' }
    //                 }));
    //                 return false;
    //             }

    //             let { src, alt, title, class: imageClass, style } = imageNode.attrs;
    //             const token = imageNode.attrs['data-token'];

    //             // 🌟 TRANSLASI STYLE: Ubah % menjadi calc() untuk imageBlock
    //             style = style || '';
    //             const widthMatch = style.match(/width:\s*(\d+)%/);
    //             if (widthMatch) {
    //                 const widthNum = parseInt(widthMatch[1], 10);
    //                 const cleanedStyle = style.replace(/width:\s*[^;]+;?/, '').trim();
    //                 style = `width: calc(${widthNum / 100} * min(100%, 64rem)) !important; ${cleanedStyle}`.trim();
    //             }

    //             return chain()
    //                 .insertContentAt(
    //                     { from: selection.from, to: selection.from + imageNode.nodeSize },
    //                     {
    //                         type: this.name,
    //                         attrs: { src, alt, title, imageClass, style, 'data-token': token, captionPosition: 'bottom' },
    //                         content: [],
    //                     }
    //                 )
    //                 .run();
    //         },

    //         removeImageCaption: () => ({ state, chain, editor }) => {
    //             if (!editor.isActive(this.name)) return false;

    //             const { selection } = state;
    //             const blockNode = state.doc.nodeAt(selection.from);
    //             if (!blockNode || blockNode.type.name !== this.name) return false;

    //             let { src, alt, title, imageClass, style } = blockNode.attrs;
    //             const token = blockNode.attrs['data-token'];

    //             // 🌟 TRANSLASI STYLE: Ubah calc() kembali menjadi % untuk gambar biasa
    //             style = style || '';
    //             const widthMatch = style.match(/width:\s*calc\(([\d.]+)\s*\*/);
    //             if (widthMatch) {
    //                 const widthNum = Math.round(parseFloat(widthMatch[1]) * 100);
    //                 const cleanedStyle = style.replace(/width:\s*[^;]+;?/, '').trim();
    //                 style = `width: ${widthNum}% !important; ${cleanedStyle}`.trim();
    //             }

    //             return chain()
    //                 .insertContentAt(
    //                     { from: selection.from, to: selection.from + blockNode.nodeSize },
    //                     { type: 'image', attrs: { src, alt, title, class: imageClass, style, 'data-token': token } }
    //                 )
    //                 .run();
    //         },
    //         toggleCaptionPosition: () => ({ state, chain, editor }) => {
    //             if (!editor.isActive(this.name)) return false;
    //             const blockNode = state.doc.nodeAt(state.selection.from);
    //             if (!blockNode || blockNode.type.name !== this.name) return false;
    //             const newPos = blockNode.attrs.captionPosition === 'top' ? 'bottom' : 'top';
    //             return chain().updateAttributes(this.name, { captionPosition: newPos }).run();
    //         },
    //     };
    // },

    // BEFORE TESTIN GEMINI CODE
    // addCommands() {
    //     return {
    //         addImageCaption: () => ({ state, chain, editor }) => {
    //             if (!editor.isActive('image')) return false;

    //             const { selection } = state;
    //             const imageNode = state.doc.nodeAt(selection.from);
    //             if (!imageNode || imageNode.type.name !== 'image') return false;

    //             if (imageNode.attrs.alt === '⏳ Mengunggah...') {
    //                 window.dispatchEvent(new CustomEvent('tampilkan-notifikasi', {
    //                     detail: { message: 'Tunggu sampai gambar selesai diunggah dulu.', type: 'warning' }
    //                 }));
    //                 return false;
    //             }

    //             // 🔧 PERBAIKAN: ikut bawa `style` (lebar & alignment) ke node baru
    //             const { src, alt, title, class: imageClass, style } = imageNode.attrs;
    //             const token = imageNode.attrs['data-token'];

    //             return chain()
    //                 .insertContentAt(
    //                     { from: selection.from, to: selection.from + imageNode.nodeSize },
    //                     {
    //                         type: this.name,
    //                         attrs: { src, alt, title, imageClass, style, 'data-token': token, captionPosition: 'bottom' },
    //                         content: [],
    //                     }
    //                 )
    //                 .run();
    //         },

    //         removeImageCaption: () => ({ state, chain, editor }) => {
    //             if (!editor.isActive(this.name)) return false;

    //             const { selection } = state;
    //             const blockNode = state.doc.nodeAt(selection.from);
    //             if (!blockNode || blockNode.type.name !== this.name) return false;

    //             // 🔧 PERBAIKAN: kembalikan juga `style`-nya ke node gambar biasa
    //             const { src, alt, title, imageClass, style } = blockNode.attrs;
    //             const token = blockNode.attrs['data-token'];

    //             return chain()
    //                 .insertContentAt(
    //                     { from: selection.from, to: selection.from + blockNode.nodeSize },
    //                     { type: 'image', attrs: { src, alt, title, class: imageClass, style, 'data-token': token } }
    //                 )
    //                 .run();
    //         },
    //         toggleCaptionPosition: () => ({ state, chain, editor }) => {
    //             if (!editor.isActive(this.name)) return false;
    //             const blockNode = state.doc.nodeAt(state.selection.from);
    //             if (!blockNode || blockNode.type.name !== this.name) return false;
    //             const newPos = blockNode.attrs.captionPosition === 'top' ? 'bottom' : 'top';
    //             return chain().updateAttributes(this.name, { captionPosition: newPos }).run();
    //         },
    //     };
    // },
});