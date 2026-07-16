import { Mark, mergeAttributes } from '@tiptap/core';

// Kalau seleksi/kursor berada TEPAT di dalam satu node tertentu (mis. Eyebrow),
// posisi from/to dari ProseMirror cuma mencakup KONTEN di dalamnya, bukan
// node itu sendiri. Untuk mark yang perlu membungkus SELURUH node (termasuk
// ikon Eyebrow, bukan cuma teksnya), rentangnya perlu diperluas ke batas
// luar node tsb.
function expandRangeToWrapNode(state, nodeTypeName) {
    const { $from, $to } = state.selection;
    let { from, to } = state.selection;

    if ($from.parent.type.name === nodeTypeName && $from.parent === $to.parent) {
        from = $from.before($from.depth);
        to = $to.after($to.depth);
    }

    return { from, to };
}

export const Pill = Mark.create({
    name: 'pill',
    inclusive: false, // Penting agar style tidak meluber saat mengetik di ujung teks

    addAttributes() {
        return {
            backgroundColor: {
                default: '#E9F1EB',
                parseHTML: (element) => element.style.backgroundColor || null,
                renderHTML: (attributes) => {
                    if (!attributes.backgroundColor) return {};
                    return { style: `background-color: ${attributes.backgroundColor}` };
                },
            },
            borderColor: {
                default: null,
                parseHTML: (element) => element.style.borderColor || null,
                renderHTML: (attributes) => {
                    if (!attributes.borderColor) return {};
                    return { style: `border-color: ${attributes.borderColor}` };
                },
            },
        };
    },

    parseHTML() { return [{ tag: 'span.pill-wrapper' }]; },

    renderHTML({ mark, HTMLAttributes }) {
        const bg = mark.attrs.backgroundColor || '#E9F1EB';
        const border = mark.attrs.borderColor;

        return [
            'span',
            mergeAttributes(HTMLAttributes, {
                // 🔑 text-[#064F3B] dihapus, diganti color:inherit — Pill tidak
                // lagi "memaksakan" warnanya sendiri, jadi warna font (dari
                // Eyebrow atau dari color-picker teks biasa) selalu menang.
                class: 'pill-wrapper inline-flex items-center font-medium rounded-full',
                style: `background-color: ${bg}; border: 1.5px solid ${border || 'transparent'}; padding: 0.3em 0.85em; color: inherit;`,
            }),
            0
        ];
    },

    addCommands() {
        return {
            togglePill: () => ({ state, chain, editor }) => {
                const { from, to } = expandRangeToWrapNode(state, 'eyebrow');
                const isActive = editor.isActive(this.name);

                return chain()
                    .setTextSelection({ from, to })[isActive ? 'unsetMark' : 'setMark'](this.name)
                    .run();
            },

            unsetPill: () => ({ state, chain }) => {
                const { from, to } = expandRangeToWrapNode(state, 'eyebrow');

                return chain()
                    .setTextSelection({ from, to })
                    .unsetMark(this.name, { extendEmptyMarkRange: true })
                    .run();
            },

            // 🔑 Tidak ada lagi percabangan "kalau sudah aktif pakai extendMarkRange".
            // Selalu: perluas ke seluruh Eyebrow (kalau relevan) -> lepas mark lama
            // di rentang itu -> pasang mark baru dengan warna barunya di rentang yang sama.
            setPillColor: (attrs = {}) => ({ state, chain }) => {
                const { from, to } = expandRangeToWrapNode(state, 'eyebrow');

                return chain()
                    .setTextSelection({ from, to })
                    .unsetMark(this.name)
                    .setMark(this.name, attrs)
                    .run();
            },
        };
    }
});



// export const Pill = Mark.create({
//     name: 'pill',
//     inclusive: false, // Penting agar style tidak meluber saat mengetik di ujung teks

//     addAttributes() {
//         return {
//             backgroundColor: {
//                 default: '#E9F1EB', // sama seperti warna lama, biar konten lama tidak berubah
//                 parseHTML: (element) => element.style.backgroundColor || null,
//                 renderHTML: (attributes) => {
//                     if (!attributes.backgroundColor) return {};
//                     return { style: `background-color: ${attributes.backgroundColor}` };
//                 },
//             },
//             borderColor: {
//                 default: null, // null = tanpa border, sama seperti perilaku lama
//                 parseHTML: (element) => element.style.borderColor || null,
//                 renderHTML: (attributes) => {
//                     if (!attributes.borderColor) return {};
//                     return { style: `border-color: ${attributes.borderColor}` };
//                 },
//             },
//         };
//     },

//     parseHTML() { return [{ tag: 'span.pill-wrapper' }]; },

//     renderHTML({ mark, HTMLAttributes }) {
//         const bg = mark.attrs.backgroundColor || '#E9F1EB';
//         const border = mark.attrs.borderColor; // null kalau tidak diset

//         return [
//             'span',
//             mergeAttributes(HTMLAttributes, {
//                 class: 'pill-wrapper inline-flex items-center px-3 py-1 text-sm font-medium text-[#064F3B] rounded-full',
//                 // border tetap "dipesan" ruangnya (1.5px) walau transparan, supaya ukuran pill
//                 // tidak melompat saat border diaktifkan/nonaktifkan nanti
//                 style: `background-color: ${bg}; border: 1.5px solid ${border || 'transparent'};`,
//             }),
//             0
//         ];
//     },

//     // addCommands() {
//     //     return {
//     //         togglePill: () => ({ commands }) => commands.toggleMark(this.name),

//     //         unsetPill: () => ({ commands }) => commands.unsetMark(this.name),

//     //         // Update warna kalau pill sudah aktif di seleksi, atau langsung
//     //         // aktifkan pill dengan warna tsb kalau belum aktif (konsisten
//     //         // dengan pola selectEyebrowIcon() di toolbar)
//     //         setPillColor: (attrs = {}) => ({ commands, editor }) => {
//     //             return editor.isActive(this.name)
//     //                 ? commands.updateAttributes(this.name, attrs)
//     //                 : commands.setMark(this.name, attrs);
//     //         },
//     //     };
//     // }
//     addCommands() {
//         return {
//             togglePill: () => ({ commands }) => commands.toggleMark(this.name),

//             unsetPill: () => ({ commands }) =>
//                 commands.unsetMark(this.name, { extendEmptyMarkRange: true }), // 👈 fix yang sama, biar "Hapus Pill" juga tidak butuh seleksi

//             setPillColor: (attrs = {}) => ({ chain, editor }) => {
//                 // 🔑 FIX: extendMarkRange dulu supaya update attribute mengenai
//                 // SELURUH rentang pill yang ada, bukan cuma posisi kursor kosong.
//                 if (editor.isActive(this.name)) {
//                     return chain().extendMarkRange(this.name).updateAttributes(this.name, attrs).run();
//                 }
//                 return chain().setMark(this.name, attrs).run();
//             },
//         };
//     }
// });
