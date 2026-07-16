import { Node, mergeAttributes } from '@tiptap/core';

// ─── Kumpulan ikon umum untuk Eyebrow ───────────────────────────────
// Didefinisikan sekali sebagai deskriptor SVG, dipakai ulang di
// renderHTML (output/database) maupun addNodeView (tampilan editor)
// supaya tidak dobel maintain.
const ICONS = {
  crosshair: [
    ['circle', { cx: '12', cy: '12', r: '9' }],
    ['path', { d: 'M12 3v2M12 19v2M3 12h2M19 12h2' }],
  ],
  star: [
    ['path', { d: 'M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z' }],
  ],
  zap: [
    ['path', { d: 'M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z' }],
  ],
  sparkles: [
    ['path', { d: 'M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z' }],
    ['path', { d: 'M20 2v4' }],
    ['path', { d: 'M22 4h-4' }],
    ['circle', { cx: '4', cy: '20', r: '2' }],
  ],
  flag: [
    ['path', { d: 'M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528' }],
  ],
  tag: [
    ['path', { d: 'M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z' }],
    ['circle', { cx: '7.5', cy: '7.5', r: '.5', fill: 'currentColor' }],
  ],
  'badge-check': [
    ['path', { d: 'M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z' }],
    ['path', { d: 'm9 12 2 2 4-4' }],
  ],
  'trending-up': [
    ['path', { d: 'M16 7h6v6' }],
    ['path', { d: 'm22 7-8.5 8.5-5-5L2 17' }],
  ],
};

const DEFAULT_ICON = 'crosshair';
const DEFAULT_COLOR = '#BE1417';   // fallback kalau tidak ada mark warna di teks
const DEFAULT_FONT_SIZE = '13px'; // fallback kalau tidak ada mark font-size di teks

function getIconChildren(iconKey) {
  return ICONS[iconKey] || ICONS[DEFAULT_ICON];
}

// Dipakai addNodeView() untuk membangun SVG via innerHTML,
// sumber datanya tetap satu (ICONS) biar tidak dobel maintain.
function iconToSVGString(iconKey) {
  const children = getIconChildren(iconKey);
  const inner = children
    .map(([tag, attrs]) => {
      const attrStr = Object.entries(attrs)
        .map(([key, value]) => `${key}="${value}"`)
        .join(' ');
      return `<${tag} ${attrStr} />`;
    })
    .join('');

  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:1.2em;height:1.2em;display:block">${inner}</svg>`;
}

// Menelusuri isi node eyebrow untuk menemukan mark 'textStyle'
// (dipakai ekstensi Color & FontSize), ambil warna/ukuran pertama
// yang ditemukan. Inilah yang bikin Eyebrow "mengikuti" gaya teks.
function extractTextStyle(node) {
  let color = null;
  let fontSize = null;

  node.descendants((child) => {
    if (color && fontSize) return false; // sudah lengkap, berhenti lebih awal
    if (child.isText) {
      child.marks.forEach((mark) => {
        if (mark.type.name === 'textStyle') {
          if (!color && mark.attrs.color) color = mark.attrs.color;
          if (!fontSize && mark.attrs.fontSize) fontSize = mark.attrs.fontSize;
        }
      });
    }
  });

  return { color, fontSize };
}

export const Eyebrow = Node.create({
    name: 'eyebrow',

    // 🔑 PERUBAHAN UTAMA: block -> inline
    // Supaya Eyebrow bisa dibungkus mark (Pill) dan dipakai berkali-kali
    // berdampingan dalam satu paragraf/baris yang sama.
    group: 'inline',
    inline: true,
    content: 'text*', // teks polos saja — mencegah Eyebrow bersarang di dalam Eyebrow

    addAttributes() {
        return {
            icon: {
                default: DEFAULT_ICON,
                parseHTML: (element) => element.getAttribute('data-icon') || DEFAULT_ICON,
                renderHTML: (attributes) => ({ 'data-icon': attributes.icon }),
            },
        };
    },

    parseHTML() {
        return [
            { tag: 'span[data-type="eyebrow"]' },
            { tag: 'div[data-type="eyebrow"]' }, // kompatibilitas dgn data lama sebelum jadi inline
        ]
    },

    renderHTML({ node, HTMLAttributes }) {
        const { color, fontSize } = extractTextStyle(node);
        const iconKey = ICONS[node.attrs.icon] ? node.attrs.icon : DEFAULT_ICON;

        return [
            'span', // sebelumnya 'div'
            mergeAttributes(HTMLAttributes, {
                'data-type': 'eyebrow',
                class: 'inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5',
                style: `color: ${color || DEFAULT_COLOR}; font-size: ${fontSize || DEFAULT_FONT_SIZE};`,
            }),
            [
                'span',
                { contenteditable: "false", class: 'flex shrink-0 select-none' },
                [
                    'svg',
                    {
                        xmlns: 'http://www.w3.org/2000/svg',
                        viewBox: '0 0 24 24',
                        fill: 'none',
                        stroke: 'currentColor',
                        'stroke-width': '2',
                        'stroke-linecap': 'round',
                        'stroke-linejoin': 'round',
                        style: 'width:1.2em;height:1.2em;display:block',
                    },
                    ...getIconChildren(iconKey),
                ]
            ],
            ['span', { class: 'outline-none' }, 0],
        ];
    },

    addNodeView() {
        return ({ node }) => {
            const dom = document.createElement('span'); // sebelumnya 'div'
            dom.className = 'inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5';
            dom.dataset.type = 'eyebrow';

            const iconWrapper = document.createElement('span');
            iconWrapper.contentEditable = 'false';
            iconWrapper.className = 'flex shrink-0 select-none';

            const contentDOM = document.createElement('span');
            contentDOM.className = 'outline-none';

            dom.appendChild(iconWrapper);
            dom.appendChild(contentDOM);

            const syncView = (currentNode) => {
                const { color, fontSize } = extractTextStyle(currentNode);
                dom.style.color = color || DEFAULT_COLOR;
                dom.style.fontSize = fontSize || DEFAULT_FONT_SIZE;

                const iconKey = ICONS[currentNode.attrs.icon] ? currentNode.attrs.icon : DEFAULT_ICON;
                if (iconWrapper.dataset.icon !== iconKey) {
                    iconWrapper.innerHTML = iconToSVGString(iconKey);
                    iconWrapper.dataset.icon = iconKey;
                }
            };

            syncView(node);

            return {
                dom,
                contentDOM,
                update(updatedNode) {
                    if (updatedNode.type.name !== 'eyebrow') return false;
                    syncView(updatedNode);
                    return true;
                },
            }
        }
    },

    addCommands() {
        return {
            // Kursor kosong -> sisipkan node baru berisi placeholder "Label" (langsung terpilih, siap ditimpa).
            // Ada teks terpilih -> bungkus teks itu ke dalam node Eyebrow baru.
            setEyebrow: (icon) => ({ commands, state, chain }) => {
                const { from, to, empty } = state.selection;

                if (empty) {
                    const label = 'Label';
                    return chain()
                        .insertContent({
                            type: this.name,
                            attrs: icon ? { icon } : undefined,
                            content: [{ type: 'text', text: label }],
                        })
                        .setTextSelection({ from: from + 1, to: from + 1 + label.length })
                        .run();
                }

                const selectedText = state.doc.textBetween(from, to, ' ');
                return commands.insertContentAt(
                    { from, to },
                    {
                        type: this.name,
                        attrs: icon ? { icon } : undefined,
                        content: selectedText ? [{ type: 'text', text: selectedText }] : [],
                    }
                );
            },

            // Lepas node Eyebrow di sekitar kursor, sisakan teksnya saja
            unsetEyebrow: () => ({ state, tr, dispatch }) => {
                const { $from } = state.selection;

                for (let depth = $from.depth; depth >= 0; depth--) {
                    const node = $from.node(depth);
                    if (node.type.name === this.name) {
                        const pos = $from.before(depth);
                        if (dispatch) {
                            tr.replaceWith(pos, pos + node.nodeSize, node.content);
                        }
                        return true;
                    }
                }

                return false;
            },

            toggleEyebrow: (icon) => ({ commands, editor }) => {
                return editor.isActive(this.name)
                    ? commands.unsetEyebrow()
                    : commands.setEyebrow(icon);
            },

            setEyebrowIcon: (icon) => ({ commands }) => {
                return commands.updateAttributes(this.name, { icon })
            },
        }
    }
});


// export const Eyebrow = Node.create({
//     name: 'eyebrow',
//     group: 'block',
//     content: 'inline*',
//     defining: true,

//     addAttributes() {
//         return {
//             icon: {
//                 default: DEFAULT_ICON,
//                 parseHTML: (element) => element.getAttribute('data-icon') || DEFAULT_ICON,
//                 renderHTML: (attributes) => ({ 'data-icon': attributes.icon }),
//             },
//         };
//     },

//     parseHTML() {
//         return [
//             { tag: 'div[data-type="eyebrow"]' },
//         ]
//     },

//     // 1. TAMPILAN UNTUK DISIMPAN KE DATABASE (FRONTEND)
//     renderHTML({ node, HTMLAttributes }) {
//         const { color, fontSize } = extractTextStyle(node);
//         const iconKey = ICONS[node.attrs.icon] ? node.attrs.icon : DEFAULT_ICON;

//         return [
//             'div',
//             mergeAttributes(HTMLAttributes, {
//                 'data-type': 'eyebrow',
//                 class: 'inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase mb-3 mt-4',
//                 style: `color: ${color || DEFAULT_COLOR}; font-size: ${fontSize || DEFAULT_FONT_SIZE};`,
//             }),
//             [
//                 'span',
//                 { contenteditable: "false", class: 'flex shrink-0 select-none' },
//                 [
//                     'svg',
//                     {
//                         xmlns: 'http://www.w3.org/2000/svg',
//                         viewBox: '0 0 24 24',
//                         fill: 'none',
//                         stroke: 'currentColor',
//                         'stroke-width': '2',
//                         'stroke-linecap': 'round',
//                         'stroke-linejoin': 'round',
//                         style: 'width:1.2em;height:1.2em;display:block',
//                     },
//                     ...getIconChildren(iconKey),
//                 ]
//             ],
//             [
//                 'span',
//                 { class: 'outline-none flex-1 min-w-0' },
//                 0
//             ]
//         ];
//     },

//     // 2. TAMPILAN INTERAKTIF DI DALAM EDITOR
//     addNodeView() {
//         return ({ node }) => {
//             const dom = document.createElement('div');
//             dom.className = 'inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase mb-3 mt-4 w-full';
//             dom.dataset.type = 'eyebrow';

//             const iconWrapper = document.createElement('span');
//             iconWrapper.contentEditable = 'false';
//             iconWrapper.className = 'flex shrink-0 select-none';

//             const contentDOM = document.createElement('span');
//             contentDOM.className = 'outline-none flex-1 min-w-0';

//             dom.appendChild(iconWrapper);
//             dom.appendChild(contentDOM);

//             // Sinkronkan warna, ukuran font, dan ikon tiap kali node berubah
//             const syncView = (currentNode) => {
//                 const { color, fontSize } = extractTextStyle(currentNode);
//                 dom.style.color = color || DEFAULT_COLOR;
//                 dom.style.fontSize = fontSize || DEFAULT_FONT_SIZE;

//                 const iconKey = ICONS[currentNode.attrs.icon] ? currentNode.attrs.icon : DEFAULT_ICON;
//                 if (iconWrapper.dataset.icon !== iconKey) {
//                     iconWrapper.innerHTML = iconToSVGString(iconKey);
//                     iconWrapper.dataset.icon = iconKey;
//                 }
//             };

//             syncView(node);

//             return {
//                 dom,
//                 contentDOM,
//                 update(updatedNode) {
//                     if (updatedNode.type.name !== 'eyebrow') return false;
//                     syncView(updatedNode);
//                     return true;
//                 },
//             }
//         }
//     },

//     // addCommands() {
//     //     return {
//     //         setEyebrow: () => ({ commands }) => {
//     //             return commands.setNode(this.name)
//     //         },
//     //         toggleEyebrow: () => ({ commands }) => {
//     //             return commands.toggleNode(this.name, 'paragraph')
//     //         },
//     //         setEyebrowIcon: (icon) => ({ commands }) => {
//     //             return commands.updateAttributes(this.name, { icon })
//     //         },
//     //     }
//     // }
//     addCommands() {
//       return {
//           setEyebrow: (icon) => ({ commands }) => {
//               return commands.setNode(this.name, icon ? { icon } : undefined)
//           },
//           toggleEyebrow: () => ({ commands }) => {
//               return commands.toggleNode(this.name, 'paragraph')
//           },
//           setEyebrowIcon: (icon) => ({ commands }) => {
//               return commands.updateAttributes(this.name, { icon })
//           },
//       }
//   },
// });
