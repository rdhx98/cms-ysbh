import { Editor, Extension } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import { FontFamily } from '@tiptap/extension-font-family'
import { Underline } from '@tiptap/extension-underline'
import TaskList from '@tiptap/extension-task-list';
import TaskItem from '@tiptap/extension-task-item';
import CodeBlock from '@tiptap/extension-code-block';
import Bold from '@tiptap/extension-bold';

import { FontSize } from "./tiptap/node/FontSize.js";
import { Eyebrow } from "./tiptap/node/EyeBrow.js";
import { Pill } from "./tiptap/node/Pill.js";
import { ParagraphIndent } from './tiptap/extensions/ParagraphIndent.js'

import Placeholder from '@tiptap/extension-placeholder';

const ALLOWED_FONTS = ['Arial', 'Fraunces', 'Times New Roman', 'Roboto', 'Jetbrains Mono', 'Open Sans', 'Plus Jakarta Sans'];

const EYEBROW_ICONS = [
    { key: 'crosshair', label: 'Crosshair', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>` },
    { key: 'star', label: 'Star', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>` },
    { key: 'zap', label: 'Zap', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>` },
    { key: 'sparkles', label: 'Sparkles', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/><circle cx="4" cy="20" r="2"/></svg>` },
    { key: 'flag', label: 'Flag', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/></svg>` },
    { key: 'tag', label: 'Tag', svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>` },
];

const FontWeight = Extension.create({
    name: 'fontWeight',

    addOptions() {
        return {
            types: ['textStyle'],
        };
    },

    addGlobalAttributes() {
        return [
            {
                types: this.options.types,
                attributes: {
                    fontWeight: {
                        default: null,
                        parseHTML: element => element.style.fontWeight || null,
                        renderHTML: attributes => {
                            if (!attributes.fontWeight) {
                                return {};
                            }
                            return {
                                style: `font-weight: ${attributes.fontWeight}`,
                            };
                        },
                    },
                },
            },
        ];
    },

    addCommands() {
        return {
            setFontWeight: fontWeight => ({ chain }) => {
                return chain()
                    .setMark('textStyle', { fontWeight })
                    .run();
            },
            unsetFontWeight: () => ({ chain }) => {
                return chain()
                    .setMark('textStyle', { fontWeight: null })
                    .removeEmptyTextStyle()
                    .run();
            },
        };
    },
});

const PILL_COLOR_PRESETS = [
    { key: 'green', label: 'Hijau (default)', backgroundColor: '#E9F1EB', borderColor: null },
    { key: 'red', label: 'Merah', backgroundColor: '#FEE2E2', borderColor: '#FCA5A5' },
    { key: 'blue', label: 'Biru', backgroundColor: '#DBEAFE', borderColor: '#93C5FD' },
    { key: 'yellow', label: 'Kuning', backgroundColor: '#FEF9C3', borderColor: '#FDE68A' },
    { key: 'purple', label: 'Ungu', backgroundColor: '#F3E8FF', borderColor: '#D8B4FE' },
    { key: 'gray', label: 'Abu-abu', backgroundColor: '#F3F4F6', borderColor: '#D1D5DB' },
];

const SharedExtensions = [
    StarterKit.configure({
        heading: false,
        // codeBlock: false,
        link: false,
        underline: false,
        bold: false,
    }),
    Link.configure({
        openOnClick: false,
        HTMLAttributes: {
            class: 'text-blue-600 font-semibold underline cursor-pointer'
        }
    }),
    FontWeight,
    TaskList.configure({
        HTMLAttributes: {
            class: 'not-prose list-none pl-0 my-4 space-y-2',
        },
    }),
    TaskItem.configure({
    HTMLAttributes: {
        class: [
        // 1. Container utama (<li>) dibuat flex dan sejajar vertikal di tengah baris
        'flex items-center my-1',

        // 2. Styling wrapper checkbox (<label>)
        // Kita beri h-5 (20px) agar punya ruang tinggi yang konsisten
        '[&>label]:flex [&>label]:items-center [&>label]:h-5 [&>label]:mr-3 [&>label]:select-none [&>label]:cursor-pointer [&>label]:flex-shrink-0',

        // 3. Styling input checkbox asli
        '[&>label>input]:w-4 [&>label>input]:h-4 [&>label>input]:rounded [&>label>input]:border-gray-300 [&>label>input]:text-blue-600',

        // 4. Styling konten teks (<div>)
        // leading-5 (20px) disamakan dengan h-5 milik label agar garis tengahnya (horizontal) benar-benar sejajar
        '[&>div]:m-0 [&>div]:leading-5 [&>div]:flex-1',

        // 5. Efek coret saat dicentang
        'data-[checked=true]:[&>div]:line-through data-[checked=true]:[&>div]:text-gray-400'
        ].join(' '),
    },
        nested: true,
    }),
    TextAlign.configure({ types: ['paragraph', 'heading', 'codeBlock'] }),
    TextStyle.extend({
        priority: 1000,
    }),
    Underline,
    Bold.configure({
        HTMLAttributes: {
            class: 'font-bold',
        },
    }),
    Color,
    FontFamily.extend({
        parseHTML() {
            return [
                {
                    style: 'font-family',
                    getAttrs: value => {
                        const cleanedFont = value.replace(/['"]/g, '').split(',')[0].trim();
                        if (ALLOWED_FONTS.includes(cleanedFont)) {
                            return { fontFamily: cleanedFont };
                        }
                        return false;
                    },
                },
            ];
        },
    }),
    FontSize,
    Eyebrow,
    Pill,
    ParagraphIndent,
    Placeholder.configure({
        emptyEditorClass: 'is-editor-empty',
        placeholder: ({ editor }) => {
            // Ambil teks dari atribut data-placeholder milik elemen HTML editor ini
            return editor.options.element.getAttribute('data-placeholder') || 'Ketik di sini...';
        },
    }),
];

window.addEventListener('insert-link-to-active-editor', (event) => {
    if (window.activeTiptapEditor && event.detail && event.detail.url) {
        const { url, text } = event.detail;
        const editorInstance = window.activeTiptapEditor;

        if (text && !editorInstance.state.selection.empty) {
            editorInstance.chain().focus().setLink({ href: url }).run();
        } else if (text) {
            editorInstance.chain().focus().insertContent({
                type: 'text',
                text: text,
                marks: [{ type: 'link', attrs: { href: url, target: '_blank' } }]
            }).run();
        } else {
            editorInstance.chain().focus().setLink({ href: url }).run();
        }
    }
});



//pageEditor
document.addEventListener('alpine:init', () => {
    // EDITOR
    Alpine.data('pageEditor', (initialLocales, initialSplit, localesCount, wireInstance) => ({
        layoutMode: 'split', //single split
        editorTab: 'content', //content | meta
        singleActiveLang: initialLocales[0] || 'id',
        splitLanguages: initialSplit,
        allLocalesCount: localesCount,
        allCollapsed: false,


        addSplitLang(lang) {
            let maxAllowed = (window.innerWidth > 1440 && this.allLocalesCount >= 3) ? 3 : 2;
            if (lang && !this.splitLanguages.includes(lang) && this.splitLanguages.length < maxAllowed) {
                this.splitLanguages.push(lang);
            }
        },
        removeSplitLang(lang) {
            if (this.splitLanguages.length > 1) {
                this.splitLanguages = this.splitLanguages.filter(l => l !== lang);
            }
        },

        // 🌟 PERBAIKAN TOTAL DI SINI: Kosongkan parameternya
        handleSort() {
            // Abaikan parameter bawaan Alpine.
            // Langsung scan ulang seluruh DOM persis setelah blok dijatuhkan (drop).
            let currentDomIds = Array.from(document.querySelectorAll("[x-sort\\:item]")).map(el => {
                return el.getAttribute("x-sort:item").split("'").join("").split('"').join("").trim();
            });

            // Kirim urutan yang 100% akurat ke Livewire
            wireInstance.updateBlockOrder(currentDomIds);
        },

        addNewBlock(type) {
            let currentDomIds = Array.from(document.querySelectorAll("[x-sort\\:item]")).map(el => {
                return el.getAttribute("x-sort:item").split("'").join("").split('"').join("").trim();
            });
            wireInstance.addBlockWithOrder(type, currentDomIds);
        },

    }));

    // TIPTAP
    Alpine.data('tiptap', (entangledContent, placeholderText = 'Ketik di sini...', editorClasses , defaultFontName, defaultFontSize, defaultFontColor, labelFontSize) => {
        // 🌟 KUNCI UTAMA: Simpan instans editor sebagai variabel lokal murni.
        // Dengan ini, Alpine TIDAK AKAN mem-proxy TipTap, sehingga error transaksi musnah.
        let editor = null;
        let baseFont = defaultFontName || 'default';
        let baseSize = defaultFontSize || 'default';
        let baseColor = defaultFontColor || '#ffffff';
        let customLabel = labelFontSize || 'Bawaan Blok';

        return {
            content: entangledContent,
            editorClasses: editorClasses,
            baseFontFamily: baseFont,
            baseFontSize: baseSize,  
            baseFontColor: baseColor,
            labelUkuran: customLabel,

            updatedAt: Date.now(),
            showLinkModal: false,
            linkInputUrl: '',

            isEyebrowIconOpen: false,
            eyebrowIcons: typeof EYEBROW_ICONS !== 'undefined' ? EYEBROW_ICONS : [],

            // State Pill Color
            isPillColorOpen: false,
            customPillBg: '#f3f4f6',
            pillBorderEnabled: false,
            customPillBorder: '#d1d5db',
            pillColorPresets: PILL_COLOR_PRESETS,


            init() {
                editor = new Editor({
                    element: this.$refs.editorElement,
                    extensions: SharedExtensions,
                    content: this.content || '',
                    editorProps: {
                        attributes: {
                            class: `focus:outline-none min-h-[40px] ${this.editorClasses}`,
                            style: `color: ${this.baseFontColor};`
                        },
                    },
                    // prose was in the class
                    onFocus: () => {
                        window.activeTiptapEditor = editor;
                    },
                    onUpdate: () => {
                        this.content = editor.getHTML();
                    },
                    onTransaction: () => {
                        this.updatedAt = Date.now();
                    }
                });

                this.$watch('content', (val) => {
                    if (editor && val && val !== editor.getHTML()) {
                        editor.commands.setContent(val, false);
                    }
                });
            },

            focusEditor() {
                // Pastikan editor sudah jalan, lalu paksa fokus
                if (editor) {
                    editor.chain().focus().run();
                }
            },

            getEditor() {
                return editor;
            },

            destroy() {
                if (editor) {
                    editor.destroy();
                    editor = null;
                }
            },

            runCommand(command, args = null) {
                if (!editor) return;

                try {
                    if (command === 'setColor') {
                        // editor.chain().focus().setMark('textStyle', { color: args }).run();
                        editor.chain().focus().setColor(args).run();
                    } else if (command === 'unsetColor') {
                        // editor.chain().focus().removeEmptyTextStyle().run();
                        editor.chain().focus().unsetColor().run();
                    } else if (command === 'setTextAlign') {
                        // Pastikan parameter alignment diterima sebagai string (misal: 'left', 'center')
                        const alignValue = typeof args === 'object' ? args.textAlign : args;
                        editor.chain().focus().setTextAlign(alignValue).run();
                    } else if (command === 'toggleTaskList') {
                        editor.chain().focus().toggleTaskList().run();
                    } else if (command === 'toggleCodeBlock') {
                        editor.chain().focus().toggleCodeBlock().run();
                    } else {
                        if (args !== null) {
                            editor.chain().focus()[command](args).run();
                        } else {
                            editor.chain().focus()[command]().run();
                        }
                    }
                } catch (e) {
                    console.warn(`Gagal menjalankan perintah Tiptap: ${command}`, e);
                }

                this.updatedAt = Date.now();
            },

            // runCommand(command, args = null) {
            //     if (!editor) return;

            //     // Peta penanganan khusus perintah Tiptap agar tidak error
            //     try {
            //         // if (command === 'setColor') {
            //         //     editor.chain().focus().setColor(args).run();
            //         // } else if (command === 'unsetColor') {
            //         //     editor.chain().focus().unsetColor().run();
            //         // }
            //         if (command === 'setColor') {
            //             // 🌟 Gunakan setMark agar bisa menumpuk dengan bold/italic
            //             editor.chain().focus().setMark('textStyle', { color: args }).run();
            //         } else if (command === 'unsetColor') {
            //             editor.chain().focus().removeEmptyTextStyle().run();
            //         }
            //         else if (command === 'setTextAlign') {
            //             editor.chain().focus().setTextAlign(args).run();
            //         } else if (command === 'toggleIndent') {
            //             // Jika ekstensi indent Anda ada, sesuaikan di sini.
            //             // Jika memakai perintah umum, pastikan command-nya terdaftar.
            //             if (typeof editor.chain().focus().toggleIndent === 'function') {
            //                 editor.chain().focus().toggleIndent().run();
            //             }
            //         } else if (command === 'toggleTaskList') {
            //             editor.chain().focus().toggleTaskList().run();
            //         } else if (command === 'toggleCodeBlock') {
            //             editor.chain().focus().toggleCodeBlock().run();
            //         } else {
            //             // Perintah standar lainnya
            //             if (args !== null) {
            //                 editor.chain().focus()[command](args).run();
            //             } else {
            //                 editor.chain().focus()[command]().run();
            //             }
            //         }
            //     } catch (e) {
            //         console.warn(`Gagal menjalankan perintah Tiptap: ${command}`, e);
            //     }

            //     this.updatedAt = Date.now();
            // },

            checkButtonActive(name, params = {}, type = 'default') {
                const forceReactiveUpdate = this.updatedAt > 0; // Pemicu reaktivitas
                if (!editor || !forceReactiveUpdate) return false;

                switch (type) {
                    case 'textAlign':
                        const currentAlign = editor.getAttributes('paragraph').textAlign || editor.getAttributes('heading').textAlign;
                        if (!currentAlign) return params.textAlign === 'left';
                        return currentAlign === params.textAlign;
                    case 'default':
                    default:
                        if (Object.keys(params).length === 0) return editor.isActive(name);
                        return editor.isActive(name, params);
                }
            },

            isActive(type, opts = {}) {
                this.updatedAt; // Memicu reaktivitas UI tombol
                return editor ? editor.isActive(type, opts) : false;
            },

            toggleBold() {
                if (!editor) return;
                editor.chain().focus().toggleBold().run();
            },

            toggleItalic() {
                if (!editor) return;
                editor.chain().focus().toggleItalic().run();
            },

            toggleBulletList() {
                if (!editor) return;
                editor.chain().focus().toggleBulletList().run();
            },

            toggleOrderedList() {
                if (!editor) return;
                editor.chain().focus().toggleOrderedList().run();
            },

            setAlignment(align) {
                if (!editor) return;
                editor.chain().focus().setTextAlign(align).run();
            },

            setLink() {
                if (!editor) return;
                window.activeTiptapEditor = editor;
                this.linkInputUrl = editor.getAttributes('link').href || '';
                this.showLinkModal = true;
            },

            saveLink() {
                if (!editor) return;
                let url = this.linkInputUrl.trim();

                if (url === '') {
                    editor.chain().focus().unsetLink().run();
                } else {
                    if (!/^https?:\/\//i.test(url) && !/^mailto:/i.test(url) && !/^tel:/i.test(url)) {
                        url = `https://${url}`;
                    }
                    editor.chain().focus().setLink({ href: url }).run();
                }
                this.showLinkModal = false;
            },

            cancelLink() {
                this.showLinkModal = false;
            },

            openInternalLinkModal() {
                if (!editor) return;
                window.activeTiptapEditor = editor;
                const { state } = editor;
                const { from, to } = state.selection;
                const selectedText = from !== to ? state.doc.textBetween(from, to, ' ') : '';

                window.dispatchEvent(new CustomEvent('buka-modal-link', {
                    detail: { text: selectedText }
                }));
            },
            changeFontFamily(fontName) {
                if (!editor) return; // 🌟 Ubah di sini

                if (fontName === 'default') {
                    editor.chain().focus().unsetFontFamily().run();
                } else {
                    editor.chain().focus().setFontFamily(fontName).run();
                }
                this.updatedAt = Date.now();
            },

            getCurrentFont() {
                this.updatedAt;
                // if (!editor) return 'default'; // 🌟 Ubah di sini
                if (!editor) return this.baseFontFamily;

                const attributes = editor.getAttributes('textStyle');
                // return attributes.fontFamily || 'default';
                return attributes.fontFamily || this.baseFontFamily;
            },

            // setFontSize(size) {
            //     if (!editor) return; // 🌟 Ubah di sini

            //     if (size === 'default') {
            //         editor.chain().focus().unsetFontSize().run();
            //     } else {
            //         editor.chain().focus().setFontSize(size).run();
            //     }
            //     this.updatedAt = Date.now();
            // },

            // getCurrentFontSize() {
            //     this.updatedAt;
            //     if (!editor) return 'default'; // 🌟 Ubah di sini

            //     const attributes = editor.getAttributes('textStyle');
            //     return attributes.fontSize || 'default';
            // },

            setFontSize(size) {
                if (!editor) return;
                if (size === 'default') {
                    editor.chain().focus().unsetFontSize().run();
                } else {
                    editor.chain().focus().setFontSize(size).run(); // Tiptap otomatis memproses string seperti '16px'
                }
                this.updatedAt = Date.now();
            },

            getCurrentFontSize() {
                this.updatedAt;
                // if (!editor) return this.baseFontSize;
                if (!editor) return 'default';

                const attributes = editor.getAttributes('textStyle');
                // 🌟 Mengembalikan ukuran spesifik, atau ukuran bawaan (contoh: '32px' untuk Heading)
                if (attributes.fontSize) {
                    return attributes.fontSize; // Contoh: "clamp(1.5rem, 2.5vw, 2rem)"
                }
                return 'default';
                // return attributes.fontSize || this.baseFontSize;
            },

            // --- FONT COLOR ---
            getCurrentColor() {
                this.updatedAt;
                if (!editor) return this.baseFontColor;

                const attributes = editor.getAttributes('textStyle');
                // 🌟 Mengembalikan warna spesifik, atau warna bawaan (contoh: '#064F3B' untuk Heading)
                return attributes.color || this.baseFontColor;
            },
            toggleEyebrowIconMenu() {
                this.isEyebrowIconOpen = !this.isEyebrowIconOpen;
            },
            selectEyebrowIcon(icon) {
                if (!editor) return;
                if (editor.isActive('eyebrow')) {
                    editor.chain().focus().setEyebrowIcon(icon).run();
                } else {
                    editor.chain().focus().setEyebrow(icon).run();
                }
                this.isEyebrowIconOpen = false;
                this.updatedAt = Date.now();
            },
            getCurrentEyebrowIcon() {
                this.updatedAt;
                if (!editor) return EYEBROW_ICONS[0].key;
                return editor.getAttributes('eyebrow').icon || EYEBROW_ICONS[0].key;
            },
            getEyebrowIconSVG(key) {
                const found = EYEBROW_ICONS.find((item) => item.key === key);
                return found ? found.svg : EYEBROW_ICONS[0].svg;
            },
            togglePillColorMenu() {
                this.isPillColorOpen = !this.isPillColorOpen;
            },

            getCurrentPillSwatch() {
                this.updatedAt;
                if (!editor) return '#f3f4f6';
                return editor.getAttributes('pill').backgroundColor || '#f3f4f6';
            },

            selectPillPreset(preset) {
                if (!editor) return;
                if (typeof editor.chain().focus().setPill === 'function') {
                    editor.chain().focus().setPill({ backgroundColor: preset.backgroundColor, borderColor: preset.borderColor }).run();
                }
                this.isPillColorOpen = false;
                this.updatedAt = Date.now();
            },

            applyCustomPillColor() {
                if (!editor) return;
                if (typeof editor.chain().focus().setPill === 'function') {
                    const attrs = { backgroundColor: this.customPillBg };
                    if (this.pillBorderEnabled) attrs.borderColor = this.customPillBorder;
                    editor.chain().focus().setPill(attrs).run();
                }
                this.updatedAt = Date.now();
            },

            removePill() {
                if (!editor) return;
                if (typeof editor.chain().focus().unsetPill === 'function') {
                    editor.chain().focus().unsetPill().run();
                }
                this.isPillColorOpen = false;
                this.updatedAt = Date.now();
            },
            // --- FONT WEIGHT (Ketebalan Teks) ---
            setFontWeight(weight) {
                if (!editor) return;
                if (weight === 'default') {
                    // Menghapus inline style font-weight agar kembali ke bawaan blok/Tailwind
                    editor.chain().focus().unsetFontWeight().run(); 
                } else {
                    editor.chain().focus().setFontWeight(weight).run();
                }
                this.updatedAt = Date.now();
            },

            getCurrentFontWeight() {
                this.updatedAt;
                if (!editor) return 'default';
                const attributes = editor.getAttributes('textStyle');
                return attributes.fontWeight || 'default';
            },
        };
    });
});
