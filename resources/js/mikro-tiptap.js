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
import { Pill } from "./tiptap/node/Pill.js";
import { ParagraphIndent } from './tiptap/extensions/ParagraphIndent.js'

import Placeholder from '@tiptap/extension-placeholder';

const ALLOWED_FONTS = ['Arial', 'Fraunces', 'Times New Roman', 'Roboto', 'JetBrains Mono', 'Open Sans', 'Plus Jakarta Sans'];

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

// Diekspor: dipakai juga oleh rich-field.js (Tiptap tunggal di panel Properti, rilis 31).
export const SharedExtensions = [
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
        isMinimapOpen:true,

				// 🌟 1. Gunakan windowWidth sebagai pemicu reaktivitas
				windowWidth: window.innerWidth,

				// 🌟 2. Getter dengan ambang batas 1366px (Mencakup Tablet Portret & HP)
				get effectiveLayout() {
						return this.windowWidth < 1366 ? 'single' : this.layoutMode;
				},

				init() {
						const handleResize = () => {
								this.windowWidth = window.innerWidth;
						};

						window.addEventListener('resize', handleResize);

						if (this.$cleanup) {
								this.$cleanup(() => window.removeEventListener('resize', handleResize));
						}
				},

				
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
            single: false, // dibaca toolbars.blade.php (x-show="! single"); editor lama selalu multi-baris

            updatedAt: Date.now(),
            showLinkModal: false,
            linkInputUrl: '',

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
            // changeFontFamily(fontName) {
            //     if (!editor) return; // 🌟 Ubah di sini

            //     if (fontName === 'default') {
            //         editor.chain().focus().unsetFontFamily().run();
            //     } else {
            //         editor.chain().focus().setFontFamily(fontName).run();
            //     }
            //     this.updatedAt = Date.now();
            // },
						changeFontFamily(fontName) {
							if (!editor) return;

							if (fontName === 'default') {
								editor.chain().focus().unsetFontFamily().run();
							} else {
								// Bungkus font yang memiliki spasi agar CSS inline valid
								const formattedFont = fontName.includes(' ') && !fontName.startsWith('"') && !fontName.startsWith("'")
										? `"${fontName}"`
										: fontName;
										
								editor.chain().focus().setFontFamily(formattedFont).run();
							}
							this.updatedAt = Date.now();
						},

						getCurrentFont() {
                this.updatedAt;
                
                // Gunakan default baseFontFamily jika editor belum siap
                if (!editor) return 'default'; 

                const attributes = editor.getAttributes('textStyle');
                let font = attributes.fontFamily;

                if (font) {
                    // 🌟 KUNCI PERBAIKAN: Hapus semua tanda kutip (' atau ") dari string
                    return font.replace(/['"]/g, '').trim();
                }

                // Jika teks tidak memiliki inline font, kembali ke default
                return 'default';
            },
            // getCurrentFont() {
            //     this.updatedAt;
            //     // if (!editor) return 'default'; // 🌟 Ubah di sini
            //     if (!editor) return this.baseFontFamily;

            //     const attributes = editor.getAttributes('textStyle');
            //     // return attributes.fontFamily || 'default';
            //     return attributes.fontFamily || this.baseFontFamily;
            // },

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